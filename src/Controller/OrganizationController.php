<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\Organization\RegisterType;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Query\User\Model\Organization;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OrganizationController extends AbstractController
{
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
    public function packages(Organization $organization): Response
    {
        return $this->render('organization/packages.html.twig', [
            'organization' => $organization,
        ]);
    }
}
