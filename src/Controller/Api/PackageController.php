<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Form\Type\Api\AddPackageType;
use Buddy\Repman\Form\Type\Api\EditPackageType;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Organization\Package\Update;
use Buddy\Repman\Message\Organization\RemovePackage;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\Api\Model\Errors;
use Buddy\Repman\Query\Api\Model\Package;
use Buddy\Repman\Query\Api\Model\Packages;
use Buddy\Repman\Query\Api\PackageQuery;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\UserQuery;
use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Service\GitLabApi;
use Munus\Control\Option;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * List organization's packages.
     *
     * @Route("/api/organization/{organization}/package",
     *     name="api_packages",
     *     methods={"GET"},
     *     requirements={"organization"="%organization_pattern%"}
     * )
     *
     * @Oa\Parameter(
     *     name="page",
     *     in="query"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns list of organization's packages",
     *     @OA\JsonContent(
     *        ref=@Model(type=Packages::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Package")
     */
    public function packages(Organization $organization, Request $request): JsonResponse
    {
        return $this->json(
            new Packages(...$this->paginate(
                fn ($perPage, $offset) => $this->packageQuery->findAll($organization->id(), $perPage, $offset),
                $this->packageQuery->count($organization->id()),
                20,
                (int) $request->get('page', 1),
                $this->generateUrl('api_packages', [
                    'organization' => $organization->alias(),
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            ))
        );
    }

    /**
     * Find package.
     *
     * @Route("/api/organization/{organization}/package/{package}",
     *     name="api_package_get",
     *     methods={"GET"},
     *     requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"}
     * )
     *
     * @Oa\Parameter(
     *     name="package",
     *     in="path",
     *     description="UUID"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns a single package",
     *     @OA\JsonContent(
     *        ref=@Model(type=Package::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Package not found"
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Package")
     */
    public function getPackage(Organization $organization, Package $package): JsonResponse
    {
        return $this->json($package);
    }

    /**
     * Remove package.
     *
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     *
     * @Route("/api/organization/{organization}/package/{package}",
     *     name="api_package_remove",
     *     methods={"DELETE"},
     *     requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"}
     * )
     *
     * @Oa\Parameter(
     *     name="package",
     *     in="path",
     *     description="UUID"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Package removed"
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Package not found"
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Package")
     */
    public function removePackage(Organization $organization, Package $package): JsonResponse
    {
        $this->dispatchMessage(new RemovePackage($package->getId(), $organization->id()));

        return new JsonResponse();
    }

    /**
     * Synchronize package.
     *
     * @Route("/api/organization/{organization}/package/{package}",
     *     name="api_synchronize_update",
     *     methods={"PUT"},
     *     requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"}
     * )
     *
     * @Oa\Parameter(
     *     name="package",
     *     in="path",
     *     description="UUID"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Package updated"
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Package not found"
     * )
     *
     * @OA\Tag(name="Package")
     */
    public function synchronizePackage(Organization $organization, Package $package): JsonResponse
    {
        $this->dispatchMessage(new SynchronizePackage($package->getId()));

        return new JsonResponse();
    }

    /**
     * Update and synchronize package.
     *
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     *
     * @Route("/api/organization/{organization}/package/{package}",
     *     name="api_package_update",
     *     methods={"PATCH"},
     *     requirements={"organization"="%organization_pattern%","package"="%uuid_pattern%"}
     * )
     *
     * @Oa\Parameter(
     *     name="package",
     *     in="path",
     *     description="UUID"
     * )
     *
     * @OA\RequestBody(
     *     @Model(type=EditPackageType::class)
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Package updated"
     * )
     *
     * @OA\Response(
     *     response=404,
     *     description="Package not found"
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Response(
     *     response=400,
     *     description="Bad request"
     * )
     *
     * @OA\Tag(name="Package")
     */
    public function updatePackage(Organization $organization, Package $package, Request $request): JsonResponse
    {
        $form = $this->createApiForm(EditPackageType::class);

        $form->submit(array_merge([
            'url' => $package->getUrl(),
            'keepLastReleases' => $package->getKeepLastReleases(),
        ], $this->parseJson($request)));

        if (!$form->isValid()) {
            return $this->badRequest($this->getErrors($form));
        }

        $this->dispatchMessage(new Update(
            $package->getId(),
            $form->get('url')->getData(),
            $form->get('keepLastReleases')->getData(),
        ));

        $this->dispatchMessage(new SynchronizePackage($package->getId()));

        return new JsonResponse();
    }

    /**
     * Add new package.
     *
     * @IsGranted("ROLE_ORGANIZATION_OWNER", subject="organization")
     *
     * @Route("/api/organization/{organization}/package",
     *     name="api_package_add",
     *     methods={"POST"},
     *     requirements={"organization"="%organization_pattern%"}
     * )
     *
     * @OA\RequestBody(
     *     @Model(type=AddPackageType::class)
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="Returns added package",
     *     @OA\JsonContent(
     *        ref=@Model(type=Package::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(
     *        ref=@Model(type=Errors::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=403,
     *     description="Forbidden"
     * )
     *
     * @OA\Tag(name="Package")
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
            return $this->badRequest($this->getErrors($form));
        }

        return $this->created($this->packageQuery->getById(
            $organization->id(),
            (string) $id
        )->get());
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
        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $type = $form->get('type')->getData();
        $this->dispatchMessage(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            $form->get('repository')->getData(),
            in_array($type, ['git', 'mercurial', 'subversion'], true) ? 'vcs' : $type,
            [],
            $form->get('keepLastReleases')->getData()
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

        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get('repository')->getData();
        $this->dispatchMessage(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            "https://github.com/{$repo}",
            'github-oauth',
            [Metadata::GITHUB_REPO_NAME => $repo],
            $form->get('keepLastReleases')->getData()
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

        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get('repository')->getData();
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
            [Metadata::GITLAB_PROJECT_ID => $projectId],
            $form->get('keepLastReleases')->getData()
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

        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get('repository')->getData();
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
            [Metadata::BITBUCKET_REPO_NAME => $repos->get($repoUuid)->name()],
            $form->get('keepLastReleases')->getData()
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
}
