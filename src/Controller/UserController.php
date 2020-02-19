<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\User\ChangePasswordType;
use Buddy\Repman\Message\User\ChangePassword;
use Buddy\Repman\Message\User\RemoveUser;
use Buddy\Repman\Message\User\SendConfirmToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class UserController extends AbstractController
{
    /**
     * @Route(path="/user", name="user_profile", methods={"GET","POST"})
     */
    public function profile(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $this->dispatchMessage(new ChangePassword(
                $user->id()->toString(),
                $form->get('plainPassword')->getData()
            ));
            $this->addFlash('success', 'Your password has been changed');

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
            'is_user_confirmed' => $user->isEmailConfirmed(),
        ]);
    }

    /**
     * @Route(path="/user/remove", name="user_remove", methods={"DELETE"})
     */
    public function remove(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User */
        $user = $this->getUser();
        $this->dispatchMessage(new RemoveUser($user->id()->toString()));
        $this->addFlash('success', 'User has been successfully removed');

        return $this->redirectToRoute('index');
    }

    /**
     * @Route(path="/user/resend-verification", name="user_resend_verification", methods={"POST"})
     */
    public function resendVerificationEmail(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User */
        $user = $this->getUser();

        // TODO: move to async queue
        $this->dispatchMessage(new SendConfirmToken(
            $user->getEmail(),
            $user->emailConfirmToken()
        ));
        $this->addFlash('success', 'Email sent successfully');

        return $this->redirectToRoute('user_profile');
    }
}
