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
use Buddy\Repman\Message\Organization\Package\RemoveBitbucketHook;
use Buddy\Repman\Message\Organization\Package\RemoveGitHubHook;
use Buddy\Repman\Message\Organization\Package\RemoveGitLabHook;
use Buddy\Repman\Message\Organization\Package\Update;
use Buddy\Repman\Message\Organization\RemovePackage;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\Api\Model\Errors;
use Buddy\Repman\Query\Api\Model\Package;
use Buddy\Repman\Query\Api\Model\Packages;
use Buddy\Repman\Query\Api\PackageQuery;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Service\IntegrationRegister;
use Buddy\Repman\Service\User\UserOAuthTokenProvider;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PackageController extends ApiController
{
    private PackageQuery $packageQuery;
    private UserOAuthTokenProvider $oauthProvider;
    private IntegrationRegister $integrations;
    private MessageBusInterface $messageBus;

    public function __construct(
        PackageQuery $packageQuery,
        UserOAuthTokenProvider $oauthProvider,
        IntegrationRegister $integrations,
        MessageBusInterface $messageBus
    ) {
        $this->packageQuery = $packageQuery;
        $this->oauthProvider = $oauthProvider;
        $this->integrations = $integrations;
        $this->messageBus = $messageBus;
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
     *     description="Package removed, if there was a problem with removing the webhook, the 'warning' field will appear"
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
        $warning = $this->tryToRemoveWebhook($package);
        $this->messageBus->dispatch(new RemovePackage($package->getId(), $organization->id()));

        return new JsonResponse($warning !== null ? [
            'warning' => $warning,
        ] : null);
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
        $this->messageBus->dispatch(new SynchronizePackage($package->getId()));

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

        $this->messageBus->dispatch(new Update(
            $package->getId(),
            $form->get('url')->getData(),
            $form->get('keepLastReleases')->getData(),
        ));

        $this->messageBus->dispatch(new SynchronizePackage($package->getId()));

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
            if (!$form->isSubmitted()) {
                $form->submit($json);
            }
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
        $this->messageBus->dispatch(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            $form->get('repository')->getData(),
            in_array($type, ['git', 'mercurial', 'subversion'], true) ? 'vcs' : $type,
            [],
            $form->get('keepLastReleases')->getData()
        ));
        $this->messageBus->dispatch(new SynchronizePackage($id));

        return $id;
    }

    /**
     * @param array<string,string> $json
     */
    private function packageNewFromGitHub(FormInterface $form, Organization $organization, array $json): ?string
    {
        // verification if the user has the appropriate integration
        $this->getToken(OAuthToken::TYPE_GITHUB);

        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get('repository')->getData();
        $this->messageBus->dispatch(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            "https://github.com/{$repo}",
            'github-oauth',
            [Metadata::GITHUB_REPO_NAME => $repo],
            $form->get('keepLastReleases')->getData()
        ));
        $this->messageBus->dispatch(new SynchronizePackage($id));
        $this->messageBus->dispatch(new AddGitHubHook($id));

        return $id;
    }

    /**
     * @param array<string,string> $json
     */
    private function packageNewFromGitLab(FormInterface $form, Organization $organization, array $json): ?string
    {
        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get('repository')->getData();
        $projects = $this->integrations->gitLabApi()->projects($this->getToken(OAuthToken::TYPE_GITLAB));
        $byNames = array_flip($projects->names());
        $projectId = $byNames[$repo] ?? null;

        if ($projectId === null) {
            throw new \RuntimeException("Repository '$repo' not found.");
        }

        $this->messageBus->dispatch(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            $projects->get($projectId)->url(),
            'gitlab-oauth',
            [Metadata::GITLAB_PROJECT_ID => $projectId],
            $form->get('keepLastReleases')->getData()
        ));
        $this->messageBus->dispatch(new SynchronizePackage($id));
        $this->messageBus->dispatch(new AddGitLabHook($id));

        return $id;
    }

    /**
     * @param array<string,string> $json
     */
    private function packageNewFromBitbucket(FormInterface $form, Organization $organization, array $json): ?string
    {
        $form->submit($json);
        if (!$form->isValid()) {
            return null;
        }

        $repo = $form->get('repository')->getData();
        $repos = $this->integrations->bitbucketApi()->repositories($this->getToken(OAuthToken::TYPE_BITBUCKET));
        $byNames = array_flip($repos->names());
        $repoUuid = $byNames[$repo] ?? null;

        if ($repoUuid === null) {
            throw new \RuntimeException("Repository '$repo' not found.");
        }

        $this->messageBus->dispatch(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id(),
            $repos->get($repoUuid)->url(),
            'bitbucket-oauth',
            [Metadata::BITBUCKET_REPO_NAME => $repos->get($repoUuid)->name()],
            $form->get('keepLastReleases')->getData()
        ));
        $this->messageBus->dispatch(new SynchronizePackage($id));
        $this->messageBus->dispatch(new AddBitbucketHook($id));

        return $id;
    }

    private function getToken(string $type): string
    {
        $token = $this->oauthProvider->findAccessToken($this->getUser()->id(), $type);
        if ($token === null) {
            throw new \InvalidArgumentException("Missing $type integration.");
        }

        return $token;
    }

    private function tryToRemoveWebhook(Package $package): ?string
    {
        $warning = null;
        if ($package->getWebhookCreatedAt() !== null) {
            try {
                switch ($package->getType()) {
                    case 'github-oauth':
                        $this->messageBus->dispatch(new RemoveGitHubHook($package->getId()));
                        break;
                    case 'gitlab-oauth':
                        $this->messageBus->dispatch(new RemoveGitLabHook($package->getId()));
                        break;
                    case 'bitbucket-oauth':
                        $this->messageBus->dispatch(new RemoveBitbucketHook($package->getId()));
                        break;
                }
            } catch (HandlerFailedException $exception) {
                $reason = current($exception->getNestedExceptions());

                $warning = sprintf(
                    'Webhook removal failed due to "%s". Please remove it manually.',
                    $reason !== false ? $reason->getMessage() : $exception->getMessage()
                );
            }
        }

        return $warning;
    }
}
