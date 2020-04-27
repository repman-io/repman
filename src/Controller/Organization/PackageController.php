<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Organization;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Form\Type\Organization\AddPackageType;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Service\GitHubApi;
use Buddy\Repman\Service\GitLabApi;
use Http\Client\Exception as HttpException;
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

final class PackageController extends AbstractController
{
    /**
     * @Route("/organization/{organization}/package/new/{type?}", name="organization_package_new", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNew(Organization $organization, Request $request, GithubApi $githubApi, GitlabApi $gitlabApi, BitbucketApi $bitbucketApi, ?string $type): Response
    {
        $form = $this->createForm(AddPackageType::class);
        $form->get('formUrl')->setData($this->generateUrl(
            $request->attributes->get('_route'),
            ['organization' => $organization->alias()],
            RouterInterface::ABSOLUTE_URL
        ));
        $form->get('type')->setData($type);
        $response = null;

        try {
            switch ($type) {
                case null:
                    $form->remove('Add');
                    break;
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
        } catch (HttpException $exception) {
            $this->addFlash('danger', sprintf(
                'Failed to fetch repositories (reason: %s).
                Please try again. If the problem persists, try to remove Repman OAuth application
                from your provider or unlink %s integration in your Profile and try again.',
                $exception->getMessage(),
                \ucfirst((string) $type)
            ));
            $form->get('type')->setData(null);
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
    private function repositoriesChoiceType(array $choices): array
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

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }
}
