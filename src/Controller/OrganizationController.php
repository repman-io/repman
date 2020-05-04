<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\Organization\ChangeAliasType;
use Buddy\Repman\Form\Type\Organization\ChangeNameType;
use Buddy\Repman\Form\Type\Organization\CreateType;
use Buddy\Repman\Form\Type\Organization\GenerateTokenType;
use Buddy\Repman\Message\Organization\ChangeAlias;
use Buddy\Repman\Message\Organization\ChangeName;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Organization\Package\RemoveBitbucketHook;
use Buddy\Repman\Message\Organization\Package\RemoveGitHubHook;
use Buddy\Repman\Message\Organization\Package\RemoveGitLabHook;
use Buddy\Repman\Message\Organization\RegenerateToken;
use Buddy\Repman\Message\Organization\RemoveOrganization;
use Buddy\Repman\Message\Organization\RemovePackage;
use Buddy\Repman\Message\Organization\RemoveToken;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\ExceptionHandler;
use Buddy\Repman\Service\Organization\AliasGenerator;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Routing\Annotation\Route;

final class OrganizationController extends AbstractController
{
    private PackageQuery $packageQuery;
    private OrganizationQuery $organizationQuery;
    private ExceptionHandler $exceptionHandler;

    public function __construct(PackageQuery $packageQuery, OrganizationQuery $organizationQuery, ExceptionHandler $exceptionHandler)
    {
        $this->packageQuery = $packageQuery;
        $this->organizationQuery = $organizationQuery;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * @Route("/organization/new", name="organization_create", methods={"GET","POST"})
     */
    public function create(Request $request, AliasGenerator $aliasGenerator): Response
    {
        $form = $this->createForm(CreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            $this->dispatchMessage(new CreateOrganization(
                $id = Uuid::uuid4()->toString(),
                $user->id()->toString(),
                $name = $form->get('name')->getData()
            ));
            $this->dispatchMessage(new GenerateToken($id, 'default'));

            $this->addFlash('success', sprintf('Organization "%s" has been created', $name));

            return $this->redirectToRoute('organization_overview', ['organization' => $aliasGenerator->generate($name)]);
        }

        return $this->render('organization/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/organization/{organization}/overview", name="organization_overview", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function overview(Organization $organization): Response
    {
        return $this->render('organization/overview.html.twig', [
            'organization' => $organization,
            'tokenCount' => $this->organizationQuery->tokenCount($organization->id()),
        ]);
    }

    /**
     * @Route("/organization/{organization}/package", name="organization_packages", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packages(Organization $organization, Request $request): Response
    {
        $count = $this->packageQuery->count($organization->id());
        if ($count === 0 && $organization->isOwner($this->getUser()->id()->toString())) {
            return $this->redirectToRoute('organization_package_new', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/packages.html.twig', [
            'packages' => $this->packageQuery->findAll($organization->id(), 20, (int) $request->get('offset', 0)),
            'count' => $count,
            'organization' => $organization,
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/{package}", name="organization_package_update", methods={"POST"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function updatePackage(Organization $organization, Package $package): Response
    {
        $this->dispatchMessage(new SynchronizePackage($package->id()));

        $this->addFlash('success', 'Package will be updated in the background');

        return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/package/{package}", name="organization_package_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function removePackage(Organization $organization, Package $package): Response
    {
        $this->tryToRemoveWebhook($package);

        $this->dispatchMessage(new RemovePackage(
            $package->id(),
            $organization->id()
        ));

        $this->addFlash('success', 'Package has been successfully removed');

        return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
    }

    /**
     * @Route("/organization/{organization}/package/{package}/stats", name="organization_package_stats", methods={"GET"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function packageStats(Organization $organization, Package $package, Request $request): Response
    {
        $days = min(max((int) $request->get('days', 30), 7), 365);

        return $this->render('organization/package/stats.html.twig', [
            'organization' => $organization,
            'package' => $package,
            'installs' => $this->packageQuery->getInstalls($package->id(), $days),
            'days' => $days,
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/{package}/webhook", name="organization_package_webhook", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function packageWebhook(Organization $organization, Package $package, Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            switch ($package->type()) {
                case 'github-oauth':
                    $this->dispatchMessage(new AddGitHubHook($package->id()));
                    break;
                case 'gitlab-oauth':
                    $this->dispatchMessage(new AddGitLabHook($package->id()));
                    break;
                case 'bitbucket-oauth':
                    $this->dispatchMessage(new AddBitbucketHook($package->id()));
                    break;
            }
            $this->addFlash('success', sprintf('Webhook for "%s" will be synchronized in background.', $package->name()));

            return $this->redirectToRoute('organization_package_webhook', ['organization' => $organization->alias(), 'package' => $package->id()]);
        }

        return $this->render('organization/package/webhook.html.twig', [
            'organization' => $organization,
            'package' => $package,
            'recentRequests' => $this->packageQuery->findRecentWebhookRequests($package->id()),
        ]);
    }

    /**
     * @Route("/organization/{organization}/token/new", name="organization_token_new", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function generateToken(Organization $organization, Request $request): Response
    {
        $form = $this->createForm(GenerateTokenType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatchMessage(new GenerateToken(
                $organization->id(),
                $name = $form->get('name')->getData()
            ));

            $this->addFlash('success', sprintf('Token "%s" has been successfully generated.', $name));

            return $this->redirectToRoute('organization_tokens', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/generateToken.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/organization/{organization}/token", name="organization_tokens", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function tokens(Organization $organization, Request $request): Response
    {
        return $this->render('organization/tokens.html.twig', [
            'tokens' => $this->organizationQuery->findAllTokens($organization->id(), 20, (int) $request->get('offset', 0)),
            'count' => $this->organizationQuery->tokenCount($organization->id()),
            'organization' => $organization,
        ]);
    }

    /**
     * @Route("/organization/{organization}/token/{token}/regenerate", name="organization_token_regenerate", methods={"POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function regenerateToken(Organization $organization, string $token): Response
    {
        $this->dispatchMessage(new RegenerateToken(
            $organization->id(),
            $token
        ));

        $this->addFlash('success', 'Token has been successfully regenerated');

        return $this->redirectToRoute('organization_tokens', ['organization' => $organization->alias()]);
    }

    /**
     * @Route("/organization/{organization}/token/{token}", name="organization_token_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function removeToken(Organization $organization, string $token): Response
    {
        $this->dispatchMessage(new RemoveToken(
            $organization->id(),
            $token
        ));

        $this->addFlash('success', 'Token has been successfully removed');

        return $this->redirectToRoute('organization_tokens', ['organization' => $organization->alias()]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/settings", name="organization_settings", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function settings(Organization $organization, Request $request): Response
    {
        $renameForm = $this->createForm(ChangeNameType::class, ['name' => $organization->name()]);
        $renameForm->handleRequest($request);
        if ($renameForm->isSubmitted() && $renameForm->isValid()) {
            $this->dispatchMessage(new ChangeName($organization->id(), $renameForm->get('name')->getData()));
            $this->addFlash('success', 'Organization name been successfully changed.');

            return $this->redirectToRoute('organization_settings', ['organization' => $organization->alias()]);
        }

        $aliasForm = $this->createForm(ChangeAliasType::class, ['alias' => $organization->alias()]);
        $aliasForm->handleRequest($request);
        if ($aliasForm->isSubmitted() && $aliasForm->isValid()) {
            $this->dispatchMessage(new ChangeAlias($organization->id(), $aliasForm->get('alias')->getData()));
            $this->addFlash('success', 'Organization alias has been successfully changed.');

            return $this->redirectToRoute('organization_settings', ['organization' => $aliasForm->get('alias')->getData()]);
        }

        return $this->render('organization/settings.html.twig', [
            'organization' => $organization,
            'renameForm' => $renameForm->createView(),
            'aliasForm' => $aliasForm->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}", name="organization_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function removeOrganization(Organization $organization): Response
    {
        $this->dispatchMessage(new RemoveOrganization($organization->id()));
        $this->addFlash('success', sprintf('Organization %s has been successfully removed', $organization->name()));

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/organization/{organization}/stats", name="organizations_stats")
     */
    public function stats(Organization $organization, Request $request): Response
    {
        $days = min(max((int) $request->get('days', 30), 7), 365);

        return $this->render('organization/stats.html.twig', [
            'organization' => $organization,
            'installs' => $this->organizationQuery->getInstalls($organization->id(), $days),
            'days' => $days,
        ]);
    }

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }

    private function tryToRemoveWebhook(Package $package): void
    {
        if ($package->webhookCreatedAt() !== null) {
            try {
                switch ($package->type()) {
                    case 'github-oauth':
                        $this->dispatchMessage(new RemoveGitHubHook($package->id()));
                        break;
                    case 'gitlab-oauth':
                        $this->dispatchMessage(new RemoveGitLabHook($package->id()));
                        break;
                    case 'bitbucket-oauth':
                        $this->dispatchMessage(new RemoveBitbucketHook($package->id()));
                        break;
                }
            } catch (HandlerFailedException $exception) {
                $this->exceptionHandler->handle($exception);
            }
        }
    }
}
