<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Form\Type\Organization\ChangeAliasType;
use Buddy\Repman\Form\Type\Organization\ChangeAnonymousAccessType;
use Buddy\Repman\Form\Type\Organization\ChangeNameType;
use Buddy\Repman\Form\Type\Organization\GenerateTokenType;
use Buddy\Repman\Message\Organization\ChangeAlias;
use Buddy\Repman\Message\Organization\ChangeAnonymousAccess;
use Buddy\Repman\Message\Organization\ChangeName;
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
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\Installs\Day;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Token;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\Model\PackageDetails;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Query\User\PackageQuery\Filter as PackageFilter;
use Buddy\Repman\Security\Model\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class OrganizationController extends AbstractController
{
    private PackageQuery $packageQuery;
    private OrganizationQuery $organizationQuery;
    private MessageBusInterface $messageBus;

    public function __construct(
        PackageQuery $packageQuery,
        OrganizationQuery $organizationQuery,
        MessageBusInterface $messageBus
    ) {
        $this->packageQuery = $packageQuery;
        $this->organizationQuery = $organizationQuery;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/organization/{organization}/overview", name="organization_overview", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function overview(Organization $organization): Response
    {
        return $this->render('organization/overview.html.twig', [
            'organization' => $organization,
            'token' => $this->organizationQuery->findAnyToken($organization->id()),
            'tokenCount' => $this->organizationQuery->tokenCount($organization->id()),
        ]);
    }

    /**
     * @Route("/organization/{organization}/package", name="organization_packages", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packages(Organization $organization, Request $request): Response
    {
        $filter = PackageFilter::fromRequest($request, 'name');

        $count = $this->packageQuery->count($organization->id(), $filter);

        // If the filtered count has no results, we need to check if the organization has no packages
        if ($count === 0 && $this->hasNoPackages($organization)) {
            return $this->redirectToRoute('organization_package_new', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/packages.html.twig', [
            'packages' => $this->packageQuery->findAll($organization->id(), $filter),
            'filter' => $filter,
            'count' => $count,
            'organization' => $organization,
        ]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}/package/{package}", name="organization_package_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function removePackage(Organization $organization, Package $package): Response
    {
        $this->tryToRemoveWebhook($package);

        $this->messageBus->dispatch(new RemovePackage(
            $package->id(),
            $organization->id()
        ));

        $this->addFlash('success', 'Package has been successfully removed');

        return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
    }

    /**
     * @Route("/organization/{organization}/package/{package}/details", name="organization_package_details", methods={"GET"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function packageDetails(Organization $organization, PackageDetails $package, Request $request): Response
    {
        $filter = Filter::fromRequest($request);

        return $this->render('organization/package/details.html.twig', [
            'organization' => $organization,
            'package' => $package,
            'filter' => $filter,
            'count' => $this->packageQuery->versionCount($package->id()),
            'versions' => $this->packageQuery->getVersions($package->id(), $filter),
            'installs' => $this->packageQuery->getInstalls($package->id(), 0),
            'packageLinks' => $this->packageQuery->getLinks($package->id(), $organization->id()),
            'dependantCount' => $package->name() !== null ? $this->packageQuery->getDependantCount($package->name(), $organization->id()) : 0,
        ]);
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
            'versions' => $this->packageQuery->getInstallVersions($package->id()),
            'days' => $days,
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/{package}/stats/{version}", name="organization_package_version_stats", methods={"GET"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function packageVersionStats(Organization $organization, Package $package, string $version, Request $request): JsonResponse
    {
        $days = min(max((int) $request->get('days', 30), 7), 365);

        return new JsonResponse(array_map(fn (Day $day) => [
            'x' => $day->date(),
            'y' => $day->installs(),
        ], $this->packageQuery->getInstalls($package->id(), $days, $version)->days()));
    }

    /**
     * @Route("/organization/{organization}/package/{package}/webhook", name="organization_package_webhook", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function packageWebhook(Organization $organization, Package $package, Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            switch ($package->type()) {
                case 'github-oauth':
                    $this->messageBus->dispatch(new AddGitHubHook($package->id()));
                    break;
                case 'gitlab-oauth':
                    $this->messageBus->dispatch(new AddGitLabHook($package->id()));
                    break;
                case 'bitbucket-oauth':
                    $this->messageBus->dispatch(new AddBitbucketHook($package->id()));
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
            $this->messageBus->dispatch(new GenerateToken(
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
        $filter = Filter::fromRequest($request);

        return $this->render('organization/tokens.html.twig', [
            'tokens' => $this->organizationQuery->findAllTokens($organization->id(), $filter),
            'count' => $this->organizationQuery->tokenCount($organization->id()),
            'organization' => $organization,
            'filter' => $filter,
        ]);
    }

    /**
     * @Route("/organization/{organization}/token/{token}/regenerate", name="organization_token_regenerate", methods={"POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function regenerateToken(Organization $organization, Token $token): Response
    {
        $this->messageBus->dispatch(new RegenerateToken(
            $organization->id(),
            $token->value()
        ));

        $this->addFlash('success', 'Token has been successfully regenerated');

        return $this->redirectToRoute('organization_tokens', ['organization' => $organization->alias()]);
    }

    /**
     * @Route("/organization/{organization}/token/{token}", name="organization_token_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function removeToken(Organization $organization, Token $token): Response
    {
        $this->messageBus->dispatch(new RemoveToken(
            $organization->id(),
            $token->value()
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
            $this->messageBus->dispatch(new ChangeName($organization->id(), $renameForm->get('name')->getData()));
            $this->addFlash('success', 'Organization name been successfully changed.');

            return $this->redirectToRoute('organization_settings', ['organization' => $organization->alias()]);
        }

        $aliasForm = $this->createForm(ChangeAliasType::class, ['alias' => $organization->alias()]);
        $aliasForm->handleRequest($request);
        if ($aliasForm->isSubmitted() && $aliasForm->isValid()) {
            $this->messageBus->dispatch(new ChangeAlias($organization->id(), $aliasForm->get('alias')->getData()));
            $this->addFlash('success', 'Organization alias has been successfully changed.');

            return $this->redirectToRoute('organization_settings', ['organization' => $aliasForm->get('alias')->getData()]);
        }

        $anonymousAccessForm = $this->createForm(ChangeAnonymousAccessType::class, ['hasAnonymousAccess' => $organization->hasAnonymousAccess()]);
        $anonymousAccessForm->handleRequest($request);
        if ($anonymousAccessForm->isSubmitted() && $anonymousAccessForm->isValid()) {
            $this->messageBus->dispatch(new ChangeAnonymousAccess($organization->id(), $anonymousAccessForm->get('hasAnonymousAccess')->getData()));
            $this->addFlash('success', 'Anonymous access has been successfully changed.');

            return $this->redirectToRoute('organization_settings', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/settings.html.twig', [
            'organization' => $organization,
            'renameForm' => $renameForm->createView(),
            'aliasForm' => $aliasForm->createView(),
            'anonymousAccessForm' => $anonymousAccessForm->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     * @Route("/organization/{organization}", name="organization_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%"})
     */
    public function removeOrganization(Organization $organization): Response
    {
        $offset = 0;
        while (($packages = $this->packageQuery->findAll($organization->id(), new PackageQuery\Filter($offset, $limit = 100))) !== []) {
            array_walk($packages, [$this, 'tryToRemoveWebhook']);
            $offset += $limit;
        }

        $this->messageBus->dispatch(new RemoveOrganization($organization->id()));
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

    /**
     * @Route("/organization/{organization}/package/{package}/scan", name="organization_package_scan", methods={"POST"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function scanPackage(Organization $organization, Package $package): Response
    {
        $this->messageBus->dispatch(new ScanPackage($package->id()));

        $this->addFlash('success', 'Package will be scanned in the background');

        return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
    }

    /**
     * @Route("/organization/{organization}/package/{package}/scan-results", name="organization_package_scan_results", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function packageScanResults(Organization $organization, Package $package, Request $request): Response
    {
        $filter = Filter::fromRequest($request);

        return $this->render('organization/package/scanResults.html.twig', [
            'organization' => $organization,
            'package' => $package,
            'filter' => $filter,
            'results' => $this->packageQuery->getScanResults($package->id(), $filter),
            'count' => $this->packageQuery->getScanResultsCount($package->id()),
        ]);
    }

    private function tryToRemoveWebhook(Package $package): void
    {
        if ($package->webhookCreatedAt() !== null) {
            try {
                switch ($package->type()) {
                    case 'github-oauth':
                        $this->messageBus->dispatch(new RemoveGitHubHook($package->id()));
                        break;
                    case 'gitlab-oauth':
                        $this->messageBus->dispatch(new RemoveGitLabHook($package->id()));
                        break;
                    case 'bitbucket-oauth':
                        $this->messageBus->dispatch(new RemoveBitbucketHook($package->id()));
                        break;
                }
            } catch (HandlerFailedException $exception) {
                $reason = current($exception->getNestedExceptions());
                $this->addFlash('danger', sprintf(
                    'Webhook removal failed due to "%s". Please remove it manually.',
                    $reason !== false ? $reason->getMessage() : $exception->getMessage()
                ));
            }
        }
    }

    private function hasNoPackages(Organization $organization): bool
    {
        $user = parent::getUser();

        return $user instanceof User &&
            $organization->isOwner($user->id()) &&
            $this->packageQuery->count($organization->id(), new PackageFilter()) === 0;
    }
}
