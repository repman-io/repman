<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\Organization\RegisterType;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Query\Admin\OrganizationQuery;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OrganizationController extends AbstractController
{
    private OrganizationQuery $organizationQuery;

    public function __construct(OrganizationQuery $organizationQuery)
    {
        $this->organizationQuery = $organizationQuery;
    }

    /**
     * @Route("/admin/register", name="admin_organization_register", methods={"GET","POST"})
     */
    public function register(Request $request): Response
    {
        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form->get('name')->getData();
            $orgOption = $this->organizationQuery->getByAlias(
                Organization::generateAlias($name)
            );

            if (!$orgOption->isEmpty()) {
                $this->addFlash('danger', 'Organization already exist');

                return $this->renderForm($form);
            }

            /** @var User */
            $user = $this->getUser();

            $this->dispatchMessage(new CreateOrganization(
                Uuid::uuid4()->toString(),
                $user->id()->toString(),
                $name
            ));

            $this->addFlash('success', 'Organization has been created');

            return $this->redirectToRoute('admin_organization_register');
        }

        return $this->renderForm($form);
    }

    /**
     * @Route("/admin/organization", name="admin_organization_list", methods={"GET"})
     */
    public function list(Request $request): Response
    {
        return $this->render('admin/organization/list.html.twig', [
            'organizations' => $this->organizationQuery->findAll(20, (int) $request->get('offset', 0)),
            'count' => $this->organizationQuery->count(),
        ]);
    }

    private function renderForm(FormInterface $form): Response
    {
        return $this->render('admin/organization/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
