<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Form\Type\Organization\AddPackageType;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Organization\RemovePackage;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Query\User\UserQuery;
use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Service\GitLabApi;
use Munus\Control\Option;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PackageController extends ApiController
{
    private PackageQuery $packageQuery;
    private UserQuery $userQuery;
    private GitlabApi $gitlabApi;
    private BitbucketApi $bitbucketApi;

    public function __construct(PackageQuery $packageQuery, UserQuery $userQuery, GitLabApi $gitlabApi, BitbucketApi $bitbucketApi)
    {
        $this->packageQuery = $packageQuery;
        $this->userQuery = $userQuery;
        $this->gitlabApi = $gitlabApi;
        $this->bitbucketApi = $bitbucketApi;
    }

    /**
     * @Route("/api/{organization}/package",
     *     name="api_packages",
     *     methods={"GET"}),
     *     requirements={"organization"="%organization_pattern%"})
     */
    public function packages(Organization $organization, Request $request): JsonResponse
    {
        return $this->json($this->paginate(
            fn ($perPage, $offset) => $this->packageQuery->findAll($organization->id(), $perPage, $offset),
            $this->packageQuery->count($organization->id()),
            20,
            (int) $request->get('page', 1)
        ));
    }

    /**
     * @Route("/api/{organization}/package/{package}",
     *     name="api_package_get",
     *     methods={"GET"},
     *     requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function getPackage(Organization $organization, string $package): JsonResponse
    {
        $package = $this->packageQuery->findWithinOrganization($organization->id(), $package);
        if ($package->isEmpty()) {
            return $this->notFound();
        }

        return $this->json($package->get());
    }

    /**
     * @Route("/api/{organization}/package/{package}",
     *     name="api_package_remove",
     *     methods={"DELETE"},
     *     requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function removePackage(Organization $organization, string $package): JsonResponse
    {
        $package = $this->packageQuery->findWithinOrganization($organization->id(), $package);
        if ($package->isEmpty()) {
            return $this->notFound();
        }

        $this->dispatchMessage(new RemovePackage($package->get()->id(), $organization->id()));

        return $this->json(null);
    }

    /**
     * @Route("/api/{organization}/package/{package}",
     *     name="api_package_update",
     *     methods={"PUT"},
     *     requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"})
     */
    public function updatePackage(Organization $organization, string $package): JsonResponse
    {
        $package = $this->packageQuery->findWithinOrganization($organization->id(), $package);
        if ($package->isEmpty()) {
            return $this->notFound();
        }

        $this->dispatchMessage(new SynchronizePackage($package->get()->id()));

        return $this->json(null);
    }

    /**
     * @Route("/api/{organization}/package",
     *     name="api_package_add",
     *     methods={"POST"}),
     *     requirements={"organization"="%organization_pattern%"})
     */
    public function addPackage(Organization $organization, Request $request): JsonResponse
    {
        $form = $this->createApiForm(AddPackageType::class);
        $json = $this->parseJson($request);
        $type = $json['type'] ?? null;
        $id = '';

        try {
            $id = $this->handleAddPackage($type, $form, $organization, $json);
        } catch (\InvalidArgumentException $exception) {
            $form->submit($json);
            $form->get('type')->addError(new FormError($exception->getMessage()));
        } catch (\RuntimeException $exception) {
            $form->get('repository')->addError(new FormError($exception->getMessage()));
        }

        if (!$form->isValid()) {
            return $this->renderFormErrors($form);
        }

        return $this->created([
            'id' => $id,
        ]);
    }

    /**
     * @param array<string,string> $json
     */
    private function handleAddPackage(?string $type, FormInterface $form, Organization $organization, array $json): ?string
    {
        $id = null;

        switch ($type) {
            case 'git':
            case 'mercurial':
            case 'subversion':
            case 'pear':
                $id = $this->packageNewFromUrl($form, $organization, $json);
                break;
            case 'path':
            case 'artifact':
                $id = $this->packageNewFromUrl($form, $organization, $json);
                break;
            case 'github':
                $id = $this->packageNewFromGitHub($form, $organization, $json);
                break;
            case 'gitlab':
                $id = $this->packageNewFromGitLab($form, $organization, $json);
                break;
            case 'bitbucket':
                $id = $this->packageNewFromBitbucket($form, $organization, $json);
                break;
            default:
                $form->submit($json);
        }

        return $id;
    }

    /**
     * @param array<string,string> $json
     */
    private function packageNewFromUrl(FormInterface $form, Organization $organization, array $json): ?string
    {
        $form->add('url', TextType::class, ['constraints' => [new NotBlank()]]);
        $form->submit($json);

        if (!$form->isValid()) {
            return null;
        }

        $type = $form->get('type')->getData();
        $this->dispatchMessage(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            $form->get('url')->getData(),
            in_array($type, ['git', 'mercurial', 'subversion'], true) ? 'vcs' : $type
        ));
        $this->dispatchMessage(new SynchronizePackage($id));

        return $id;
    }

    /**
     * @param array<string,string> $json
     */
    private function packageNewFromGitHub(FormInterface $form, Organization $organization, array $json): ?string
    {
        $this->getToken(OAuthToken::TYPE_GITHUB);
        $fieldName = 'repository';
        $form->add($fieldName, TextType::class, ['constraints' => [new NotBlank()]]);

        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get($fieldName)->getData();
        $this->dispatchMessage(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            "https://github.com/{$repo}",
            'github-oauth',
            [Metadata::GITHUB_REPO_NAME => $repo]
        ));
        $this->dispatchMessage(new SynchronizePackage($id));
        $this->dispatchMessage(new AddGitHubHook($id));

        return $id;
    }

    /**
     * @param array<string,string> $json
     */
    private function packageNewFromGitLab(FormInterface $form, Organization $organization, array $json): ?string
    {
        $token = $this->getToken(OAuthToken::TYPE_GITLAB);
        $fieldName = 'repository';
        $form->add($fieldName, TextType::class, ['constraints' => [new NotBlank()]]);

        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get($fieldName)->getData();
        $projects = $this->gitlabApi->projects($token->get());
        $byNames = array_flip($projects->names());
        $projectId = $byNames[$repo] ?? null;

        if ($projectId === null) {
            throw new \RuntimeException("Repository '$repo' not found.");
        }

        $this->dispatchMessage(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            $projects->get($projectId)->url(),
            'gitlab-oauth',
            [Metadata::GITLAB_PROJECT_ID => $projectId]
        ));
        $this->dispatchMessage(new SynchronizePackage($id));
        $this->dispatchMessage(new AddGitLabHook($id));

        return $id;
    }

    /**
     * @param array<string,string> $json
     */
    private function packageNewFromBitbucket(FormInterface $form, Organization $organization, array $json): ?string
    {
        $token = $this->getToken(OAuthToken::TYPE_BITBUCKET);
        $fieldName = 'repository';
        $form->add($fieldName, TextType::class, ['constraints' => [new NotBlank()]]);

        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get($fieldName)->getData();
        $repos = $this->bitbucketApi->repositories($token->get());
        $byNames = array_flip($repos->names());
        $repoUuid = $byNames[$repo] ?? null;

        if ($repoUuid === null) {
            throw new \RuntimeException("Repository '$repo' not found.");
        }

        $this->dispatchMessage(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            $repos->get($repoUuid)->url(),
            'bitbucket-oauth',
            [Metadata::BITBUCKET_REPO_NAME => $repos->get($repoUuid)->name()]
        ));
        $this->dispatchMessage(new SynchronizePackage($id));
        $this->dispatchMessage(new AddBitbucketHook($id));

        return $id;
    }

    /**
     * @return Option<string>
     */
    private function getToken(string $type): Option
    {
        $token = $this->userQuery->findOAuthAccessToken($this->getUser()->id(), $type);
        if ($token->isEmpty()) {
            throw new \InvalidArgumentException("Missing $type integration.");
        }

        return $token;
    }

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }
}
