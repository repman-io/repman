<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Form\Type\Organization\CreateType;
use Buddy\Repman\Form\Type\Organization\GenerateApiTokenType;
use Buddy\Repman\Form\Type\User\ChangeEmailPreferencesType;
use Buddy\Repman\Form\Type\User\ChangePasswordType;
use Buddy\Repman\Message\Organization\CreateOrganization;
use Buddy\Repman\Message\Organization\GenerateToken;
use Buddy\Repman\Message\User\ChangeEmailPreferences;
use Buddy\Repman\Message\User\ChangePassword;
use Buddy\Repman\Message\User\GenerateApiToken;
use Buddy\Repman\Message\User\RegenerateApiToken;
use Buddy\Repman\Message\User\RemoveApiToken;
use Buddy\Repman\Message\User\RemoveOAuthToken;
use Buddy\Repman\Message\User\RemoveUser;
use Buddy\Repman\Message\User\SendConfirmToken;
use Buddy\Repman\Query\User\UserQuery;
use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Service\Organization\AliasGenerator;
use Ramsey\Uuid\Uuid;
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
        $oauthTokens = $this->userQuery->findAllOAuthTokens($this->getUser()->id());

        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        $emailPreferencesForm = $this->createForm(ChangeEmailPreferencesType::class, [
            'emailScanResult' => $this->getUser()->emailScanResult(),
        ], [
            'action' => $this->generateUrl('user_email_preferences'),
        ]);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $this->dispatchMessage(new ChangePassword(
                $this->getUser()->id(),
                $passwordForm->get('plainPassword')->getData()
            ));
            $this->addFlash('success', 'Your password has been changed');

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'passwordForm' => $passwordForm->createView(),
            'emailPreferencesForm' => $emailPreferencesForm->createView(),
            'oauthTokens' => $oauthTokens,
        ]);
    }

    /**
     * @Route(path="/user/email-preferences", name="user_email_preferences", methods={"POST"})
     */
    public function emailPreferences(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $emailPreferencesForm = $this->createForm(ChangeEmailPreferencesType::class);
        $emailPreferencesForm->handleRequest($request);

        $this->dispatchMessage(new ChangeEmailPreferences(
            $this->getUser()->id(),
            $emailPreferencesForm->get('emailScanResult')->getData()
        ));
        $this->addFlash('success', 'Email preferences have been changed');

        return $this->redirectToRoute('user_profile');
    }

    /**
     * @Route(path="/user/remove", name="user_remove", methods={"DELETE"})
     */
    public function remove(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->dispatchMessage(new RemoveUser($this->getUser()->id()));
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
            $this->getUser()->email(),
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
        $this->dispatchMessage(new RemoveOAuthToken($this->getUser()->id(), $type));
        $this->addFlash('success', sprintf('%s has been successfully unlinked.', \ucfirst($type)));

        return $this->redirectToRoute('user_profile');
    }

    /**
     * @Route("/user/token",
     *  name="user_api_tokens",
     *  methods={"GET"})
     */
    public function apiTokens(Request $request): Response
    {
        return $this->render('user/apiTokens.html.twig', [
            'tokens' => $this->userQuery->findAllApiTokens($this->getUser()->id(), 20, (int) $request->get('offset', 0)),
            'count' => $this->userQuery->apiTokenCount($this->getUser()->id()),
        ]);
    }

    /**
     * @Route("/user/api-token/new",
     *  name="user_api_token_new",
     *  methods={"GET","POST"})
     */
    public function generateApiToken(Request $request): Response
    {
        $form = $this->createForm(GenerateApiTokenType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatchMessage(new GenerateApiToken(
                $this->getUser()->id(),
                $name = $form->get('name')->getData()
            ));

            $this->addFlash('success', sprintf('API Token "%s" has been successfully generated.', $name));

            return $this->redirectToRoute('user_api_tokens');
        }

        return $this->render('user/generateApiToken.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/user/token/{token}/regenerate",
     *  name="user_api_token_regenerate",
     * methods={"POST"})
     */
    public function regenerateApiToken(string $token): Response
    {
        $this->dispatchMessage(new RegenerateApiToken($this->getUser()->id(), $token));

        $this->addFlash('success', 'API token has been successfully regenerated');

        return $this->redirectToRoute('user_api_tokens');
    }

    /**
     * @Route("/user/token/{token}",
     *  name="user_api_token_remove",
     * methods={"DELETE"})
     */
    public function removeApiToken(string $token): Response
    {
        $this->dispatchMessage(new RemoveApiToken($this->getUser()->id(), $token));

        $this->addFlash('success', 'API token has been successfully removed');

        return $this->redirectToRoute('user_api_tokens');
    }

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }

    /**
     * @Route("/user/organization/new", name="organization_create", methods={"GET","POST"})
     */
    public function createOrganization(Request $request, AliasGenerator $aliasGenerator): Response
    {
        $form = $this->createForm(CreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatchMessage(new CreateOrganization(
                $id = Uuid::uuid4()->toString(),
                $this->getUser()->id(),
                $name = $form->get('name')->getData()
            ));
            $this->dispatchMessage(new GenerateToken($id, 'default'));

            $this->addFlash('success', sprintf('Organization "%s" has been created', $name));

            return $this->redirectToRoute('organization_overview', ['organization' => $aliasGenerator->generate($name)]);
        }

        return $this->render('organization/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
