<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OauthToken;
use Buddy\Repman\Form\Type\Organization\AddPackageType;
use Buddy\Repman\Form\Type\Organization\GenerateTokenType;
use Buddy\Repman\Form\Type\Organization\RegisterType;
use Buddy\Repman\Message\Organization\AddHook;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\Organization\RegenerateToken;
use Buddy\Repman\Message\Organization\RemoveOrganization;
use Buddy\Repman\Message\Organization\RemovePackage;
use Buddy\Repman\Message\Organization\RemoveToken;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Message\Organization\UpdatePackage;
use Buddy\Repman\Message\User\CreateOauthToken;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\GitHubApi;
use Buddy\Repman\Service\Organization\AliasGenerator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GithubClient;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class OrganizationController extends AbstractController
{
    private PackageQuery $packageQuery;
    private OrganizationQuery $organizationQuery;

    public function __construct(PackageQuery $packageQuery, OrganizationQuery $organizationQuery)
    {
        $this->packageQuery = $packageQuery;
        $this->organizationQuery = $organizationQuery;
    }

    /**
     * @Route("/organization/new", name="organization_create", methods={"GET","POST"})
     */
    public function create(Request $request, AliasGenerator $aliasGenerator): Response
    {
        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User */
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

        return $this->render('admin/organization/register.html.twig', [
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
        ]);
    }

    /**
     * @Route("/organization/{organization}/package", name="organization_packages", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packages(Organization $organization, Request $request): Response
    {
        return $this->render('organization/packages.html.twig', [
            'packages' => $this->packageQuery->findAll(
                $organization->id(),
                20,
                (int)
                $request->get('offset', 0)
            ),
            'count' => $this->packageQuery->count($organization->id()),
            'organization' => $organization,
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/new", name="organization_package_new", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNew(Organization $organization, Request $request, GithubApi $api): Response
    {
        $form = $this->createForm(AddPackageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatchMessage(new AddPackage(
                $id = Uuid::uuid4()->toString(),
                $organization->id(),
                $form->get('url')->getData(),
                $form->get('type')->getData()
            ));
            $this->dispatchMessage(new SynchronizePackage($id));

            $this->addFlash('success', 'Package has been added and will be synchronized in the background');

            return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/addPackage.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
            'vcsType' => 'GitHub',
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/new-from-github", name="organization_package_new_from_github", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNewFromGithub(Organization $organization, Request $request, ClientRegistry $clientRegistry, GithubApi $api): Response
    {
        $tokenType = OauthToken::TYPE_GITHUB;
        /** @var User */
        $user = $this->getUser();
        $userId = $user->id()->toString();
        $oauthToken = $user->oauthToken($tokenType);

        if (empty($oauthToken)) {
            /** @var GithubClient $oauthClient */
            $oauthClient = $clientRegistry->getClient('github');
            $tokenValue = $oauthClient->getAccessToken()->getToken();

            $this->dispatchMessage(
                new CreateOauthToken(
                    $oauthTokenId = Uuid::uuid4()->toString(),
                    $userId,
                    $tokenType,
                    $tokenValue
                )
            );
        } else {
            $tokenValue = $oauthToken->value();
            $oauthTokenId = $oauthToken->id()->toString();
        }

        $form = $this
            ->createFormBuilder()
            ->setAction($this->generateUrl(
                'organization_package_new_from_github',
                ['organization' => $organization->alias()]
            ));

        $choices = $api->repositories($tokenValue);

        $form->add('repos', ChoiceType::class, [
            'choices' => $choices,
            'label' => false,
            'expanded' => true,
            'multiple' => true,
            'data' => array_values($choices),
        ]);

        $form->add('save', SubmitType::class, ['label' => 'Import selected']);
        $form = $form->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('repos')->getData() as $repo) {
                $this->dispatchMessage(new AddPackage(
                    $id = Uuid::uuid4()->toString(),
                    $organization->id(),
                    "https://github.com/{$repo}",
                    'vcs',
                    $oauthTokenId
                ));
                $this->dispatchMessage(new SynchronizePackage($id));

                $this->dispatchMessage(
                    new AddHook(
                        $id,
                        $repo,
                        $tokenValue,
                        $this->generateUrl(
                            'package_webhook',
                            ['package' => $id], RouterInterface::ABSOLUTE_URL
                        )
                    )
                );
            }

            $this->addFlash('success', 'Packages has been added and will be synchronized in the background');

            return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/addPackageFromVcs.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
            'type' => $tokenType,
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/add-from-github", name="organization_package_add_from_github", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageAddFromGithub(Organization $organization, Request $request, ClientRegistry $clientRegistry, GithubApi $api): Response
    {
        /** @var User */
        $user = $this->getUser();
        $oauthToken = $user->oauthToken(OauthToken::TYPE_GITHUB);

        if ($oauthToken) {
            return $this->redirectToRoute(
                'organization_package_new_from_github',
                ['organization' => $organization->alias()]
            );
        }

        /** @var GithubClient $oauthClient */
        $oauthClient = $clientRegistry->getClient('github');

        return $oauthClient
            ->redirect(
                ['read:org', 'repo'],
                [
                    'redirect_uri' => $this->generateUrl(
                        'organization_package_new_from_github',
                        ['organization' => $organization->alias()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ]
            );
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
     * @Route("/organization/{organization}/package/{package}", name="organization_package_remove", methods={"DELETE"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function removePackage(Organization $organization, Package $package): Response
    {
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
    public function packageStats(Organization $organization, Package $package): Response
    {
        return $this->render('organization/package/stats.html.twig', [
            'organization' => $organization,
            'package' => $package,
            'installs' => $this->packageQuery->getInstalls($package->id()),
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
    public function tokens(Organization $organization): Response
    {
        return $this->render('organization/tokens.html.twig', [
            'tokens' => $this->organizationQuery->findAllTokens($organization->id()),
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
     * @Route("/organization/{organization}/settings", name="organization_settings", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function settings(Organization $organization): Response
    {
        return $this->render('organization/settings.html.twig', [
            'organization' => $organization,
        ]);
    }

    /**
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
    public function stats(Organization $organization): Response
    {
        return $this->render('organization/stats.html.twig', [
            'organization' => $organization,
            'installs' => $this->organizationQuery->getInstalls($organization->id()),
        ]);
    }
}
