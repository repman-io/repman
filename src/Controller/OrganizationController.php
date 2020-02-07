<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\Organization\AddPackageType;
use Buddy\Repman\Form\Type\Organization\GenerateTokenType;
use Buddy\Repman\Form\Type\Organization\RegisterType;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\Organization\RemoveToken;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\OrganizationQuery;
use Buddy\Repman\Query\User\PackageQuery;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function create(Request $request): Response
    {
        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User */
            $user = $this->getUser();

            $this->dispatchMessage(new CreateOrganization(
                Uuid::uuid4()->toString(),
                $user->id()->toString(),
                $name = $form->get('name')->getData()
            ));

            $this->addFlash('success', sprintf('Organization "%s" has been created', $name));

            return $this->redirectToRoute('organization_create');
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
    public function packageNew(Organization $organization, Request $request): Response
    {
        $form = $this->createForm(AddPackageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatchMessage(new AddPackage(
                Uuid::uuid4()->toString(),
                $organization->id(),
                $form->get('url')->getData()
            ));

            $this->addFlash('success', 'Package has been added');

            return $this->redirectToRoute('organization_packages', ['organization' => $organization->alias()]);
        }

        return $this->render('organization/addPackage.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
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
}
