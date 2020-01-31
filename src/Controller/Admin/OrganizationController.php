<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Admin;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\Organization\RegisterType;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

final class OrganizationController extends AbstractController
{
    /**
     * @Route("/admin/register", name="admin_organization_register", methods={"GET","POST"})
     */
    public function register(Request $request): Response
    {
        /** @var User */
        $user = $this->getUser();

        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $envelope = $this->dispatchMessage(new CreateOrganization(
                Uuid::uuid4()->toString(),
                $user->getId()->toString(),
                $form->get('name')->getData()
            ));

            /** @var HandledStamp */
            $stamp = $envelope->last(HandledStamp::class);
            $error = $stamp->getResult();

            $error->isEmpty() ?
                $this->addFlash('success', 'Organization has been created') :
                $this->addFlash('danger', $error->get());

            return $this->redirectToRoute('admin_organization_register');
        }

        return $this->render('admin/organization/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
