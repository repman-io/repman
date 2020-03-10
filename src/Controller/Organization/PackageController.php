<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Organization;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Entity\User\OauthToken;
use Buddy\Repman\Form\Type\Organization\AddPackageFromVcsType;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Service\GitHubApi;
use Buddy\Repman\Service\GitLabApi;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PackageController extends AbstractController
{
    /**
     * @Route("/organization/{organization}/package/new-from-gitlab", name="organization_package_new_from_gitlab", methods={"GET", "POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNewFromGitLab(Organization $organization, Request $request, GitLabApi $api): Response
    {
        $token = $this->getUser()->oauthToken(OauthToken::TYPE_GITLAB);
        if ($token === null) {
            return $this->redirectToRoute('fetch_gitlab_package_token', ['organization' => $organization->alias()]);
        }

        $projects = $api->projects($token->accessToken());
        $form = $this->createForm(AddPackageFromVcsType::class, null, ['repositories' => array_flip($projects->names())]);
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

            $this->addFlash('success', 'Packages has been added and will be synchronized in the background');

            return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/addPackageFromVcs.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
            'type' => 'GitLab',
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/new-from-github", name="organization_package_new_from_github", methods={"GET","POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNewFromGitHub(Organization $organization, Request $request, GithubApi $api): Response
    {
        $token = $this->getUser()->oauthToken(OauthToken::TYPE_GITHUB);
        if ($token === null) {
            return $this->redirectToRoute('fetch_github_package_token', ['organization' => $organization->alias()]);
        }

        $repos = $api->repositories($token->accessToken());
        $form = $this->createForm(AddPackageFromVcsType::class, null, ['repositories' => array_combine($repos, $repos)]);
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

            $this->addFlash('success', 'Packages has been added and will be synchronized in the background');

            return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/addPackageFromVcs.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
            'type' => 'GitHub',
        ]);
    }

    /**
     * @Route("/organization/{organization}/package/new-from-bitbucket", name="organization_package_new_from_bitbucket", methods={"GET", "POST"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNewFromBitbucket(Organization $organization, Request $request, BitbucketApi $api): Response
    {
        $token = $this->getUser()->oauthToken(OauthToken::TYPE_BITBUCKET);
        if ($token === null) {
            return $this->redirectToRoute('fetch_bitbucket_package_token', ['organization' => $organization->alias()]);
        }

        $repos = $api->repositories($token->accessToken());
        $form = $this->createForm(AddPackageFromVcsType::class, null, ['repositories' => array_flip($repos->names())]);
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

            $this->addFlash('success', 'Packages has been added and will be synchronized in the background');

            return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/addPackageFromVcs.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
            'type' => 'Bitbucket',
        ]);
    }

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }
}
