<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Form\Type\Organization\AddPackageType;
use Buddy\Repman\Form\Type\Organization\ChangeAliasType;
use Buddy\Repman\Form\Type\Organization\ChangeNameType;
use Buddy\Repman\Form\Type\Organization\CreateType;
use Buddy\Repman\Form\Type\Organization\GenerateTokenType;
use Buddy\Repman\Message\Organization\AddPackage;
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
use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Service\GitHubApi;
use Buddy\Repman\Service\GitLabApi;
use Buddy\Repman\Service\Organization\AliasGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

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
        $form = $this->createForm(CreateType::class);
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
        if ($count === 0) {
            return $this->redirectToRoute('organization_package_new', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/packages.html.twig', [
            'packages' => $this->packageQuery->findAll($organization->id(), 20, (int) $request->get('offset', 0)),
            'count' => $count,
            'organization' => $organization,
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/new/{type?}", name="organization_package_new", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNew(Organization $organization, Request $request, GithubApi $githubApi, GitlabApi $gitlabApi, BitbucketApi $bitbucketApi, ?string $type): Response
    {
        $type ??= 'git';
        $form = $this->createForm(AddPackageType::class);
        $form->get('formUrl')->setData($this->generateUrl(
            $request->attributes->get('_route'),
            ['organization' => $organization->alias()],
            RouterInterface::ABSOLUTE_URL
        ));
        $form->get('type')->setData($type);

        switch ($type) {
            case 'git':
            case 'mercurial':
            case 'subversion':
            case 'pear':
                $response = $this->packageNewFromUrl('url', $form, $organization, $request);
                break;
            case 'path':
            case 'artifact':
                $response = $this->packageNewFromUrl($type, $form, $organization, $request);
                break;
            case 'github':
                $response = $this->packageNewFromGitHub($form, $organization, $request, $githubApi);
                break;
            case 'gitlab':
                $response = $this->packageNewFromGitLab($form, $organization, $request, $gitlabApi);
                break;
            case 'bitbucket':
                $response = $this->packageNewFromBitbucket($form, $organization, $request, $bitbucketApi);
                break;
            default:
                throw new NotFoundHttpException();
        }

        if ($response instanceof Response) {
            return $response;
        }

        return $this->render('organization/addPackage.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param string[] $choices
     *
     * @return array<string|array>
     */
    public function repositoriesChoiceType(array $choices): array
    {
        return [
            'repositories',
            ChoiceType::class, [
                'choices' => $choices,
                'label' => false,
                'expanded' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'form-control selectpicker',
                    'data-live-search' => 'true',
                    'data-style' => 'btn-secondary',
                    'title' => 'select repository',
                ],
            ],
        ];
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
        if ($package->webhookCreatedAt() !== null) {
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
        }
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
     * @Route("/organization/{organization}/package/{package}/webhook", name="organization_package_webhook", methods={"GET"}, requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function packageWebhook(Organization $organization, Package $package): Response
    {
        return $this->render('organization/package/webhook.html.twig', [
            'organization' => $organization,
            'package' => $package,
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

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }

    private function packageHasBeenAdded(Organization $organization): Response
    {
        $this->addFlash('success', 'Packages has been added and will be synchronized in the background');

        return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
    }

    private function packageNewFromUrl(string $label, FormInterface $form, Organization $organization, Request $request): ?Response
    {
        $form->add(
            'url',
            TextType::class,
            [
                'constraints' => [new NotBlank()],
                'label' => $label,
            ],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type = $form->get('type')->getData();

            $this->dispatchMessage(new AddPackage(
                $id = Uuid::uuid4()->toString(),
                $organization->id(),
                $form->get('url')->getData(),
                in_array($type, ['git', 'mercurial', 'subversion'], true) ? 'vcs' : $type
            ));
            $this->dispatchMessage(new SynchronizePackage($id));

            return $this->packageHasBeenAdded($organization);
        }

        return null;
    }

    private function packageNewFromGitHub(FormInterface $form, Organization $organization, Request $request, GithubApi $api): ?Response
    {
        $token = $this->getUser()->oauthToken(OAuthToken::TYPE_GITHUB);
        if ($token === null) {
            return $this->redirectToRoute('fetch_github_package_token', ['organization' => $organization->alias()]);
        }

        $repos = $api->repositories($token->accessToken());
        $choices = array_combine($repos, $repos);
        $form->add(...$this->repositoriesChoiceType(is_array($choices) ? $choices : []));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('repositories')->getData() as $repo) {
                $this->dispatchMessage(new AddPackage(
                    $id = Uuid::uuid4()->toString(),
                    $organization->id(),
                    "https://github.com/{$repo}",
                    'github-oauth',
                    [Metadata::GITHUB_REPO_NAME => $repo]
                ));
                $this->dispatchMessage(new SynchronizePackage($id));
                $this->dispatchMessage(new AddGitHubHook($id));
            }

            return $this->packageHasBeenAdded($organization);
        }

        return null;
    }

    private function packageNewFromGitLab(FormInterface $form, Organization $organization, Request $request, GitlabApi $api): ?Response
    {
        $token = $this->getUser()->oauthToken(OAuthToken::TYPE_GITLAB);
        if ($token === null) {
            return $this->redirectToRoute('fetch_gitlab_package_token', ['organization' => $organization->alias()]);
        }

        $projects = $api->projects($token->accessToken());
        $form->add(...$this->repositoriesChoiceType(array_flip($projects->names())));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('repositories')->getData() as $projectId) {
                $this->dispatchMessage(new AddPackage(
                    $id = Uuid::uuid4()->toString(),
                    $organization->id(),
                    $projects->get($projectId)->url(),
                    'gitlab-oauth',
                    [Metadata::GITLAB_PROJECT_ID => $projectId]
                ));
                $this->dispatchMessage(new SynchronizePackage($id));
                $this->dispatchMessage(new AddGitLabHook($id));
            }

            return $this->packageHasBeenAdded($organization);
        }

        return null;
    }

    private function packageNewFromBitbucket(FormInterface $form, Organization $organization, Request $request, BitbucketApi $api): ?Response
    {
        $token = $this->getUser()->oauthToken(OAuthToken::TYPE_BITBUCKET);
        if ($token === null) {
            return $this->redirectToRoute('fetch_bitbucket_package_token', ['organization' => $organization->alias()]);
        }

        $repos = $api->repositories($token->accessToken());
        $form->add(...$this->repositoriesChoiceType(array_flip($repos->names())));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('repositories')->getData() as $repoUuid) {
                $this->dispatchMessage(new AddPackage(
                    $id = Uuid::uuid4()->toString(),
                    $organization->id(),
                    $repos->get($repoUuid)->url(),
                    'bitbucket-oauth',
                    [Metadata::BITBUCKET_REPO_NAME => $repos->get($repoUuid)->name()]
                ));
                $this->dispatchMessage(new SynchronizePackage($id));
                $this->dispatchMessage(new AddBitbucketHook($id));
            }

            return $this->packageHasBeenAdded($organization);
        }

        return null;
    }
}
