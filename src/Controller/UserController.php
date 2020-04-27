<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Form\Type\User\ChangePasswordType;
use Buddy\Repman\Message\User\ChangePassword;
use Buddy\Repman\Message\User\RemoveOAuthToken;
use Buddy\Repman\Message\User\RemoveUser;
use Buddy\Repman\Message\User\SendConfirmToken;
use Buddy\Repman\Query\User\UserQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class UserController extends AbstractController
{
    private UserQuery $userQuery;

    public function __construct(UserQuery $userQuery)
    {
        $this->userQuery = $userQuery;
    }

    /**
     * @Route(path="/user", name="user_profile", methods={"GET","POST"})
     */
    public function profile(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $oauthTokens = $this->userQuery->findAllOAuthTokens($this->getUser()->id()->toString());
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $this->dispatchMessage(new ChangePassword(
                $this->getUser()->id()->toString(),
                $form->get('plainPassword')->getData()
            ));
            $this->addFlash('success', 'Your password has been changed');

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
            'oauth_tokens' => $oauthTokens,
        ]);
    }

    /**
     * @Route(path="/user/remove", name="user_remove", methods={"DELETE"})
     */
    public function remove(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->dispatchMessage(new RemoveUser($this->getUser()->id()->toString()));
        $this->addFlash('success', 'User has been successfully removed');

        return $this->redirectToRoute('index');
    }

    /**
     * @Route(path="/user/resend-verification", name="user_resend_verification", methods={"POST"})
     */
    public function resendVerificationEmail(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->dispatchMessage(new SendConfirmToken(
            $this->getUser()->getEmail(),
            $this->getUser()->emailConfirmToken()
        ));
        $this->addFlash('success', 'Email sent successfully');

        return $this->redirectToRoute('user_profile');
    }

    /**
     * @Route(path="/user/remove-oauth-token/{type}", name="user_remove_oauth_token", methods={"DELETE"}, requirements={"type"="github|gitlab|bitbucket|buddy"})
     */
    public function removeOAuthToken(string $type): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->dispatchMessage(new RemoveOAuthToken($this->getUser()->id()->toString(), $type));
        $this->addFlash('success', sprintf('%s has been successfully unlinked.', \ucfirst($type)));

        return $this->redirectToRoute('user_profile');
    }

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }
}
