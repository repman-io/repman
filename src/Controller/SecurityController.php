<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Form\Type\User\ResetPasswordType;
use Buddy\Repman\Form\Type\User\SendResetPasswordLinkType;
use Buddy\Repman\Message\User\ResetPassword;
use Buddy\Repman\Message\User\SendPasswordResetLink;
use Buddy\Repman\Service\Config;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('index');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'localLoginEnabled' => $this->config->localLoginEnabled(),
        ]);
    }

    /**
     * @Route("/reset-password", name="app_send_reset_password_link", methods={"GET","POST"})
     */
    public function sendResetPasswordLink(Request $request): Response
    {
        $form = $this->createForm(SendResetPasswordLinkType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $browser = new \Browser();
            $this->dispatchMessage(new SendPasswordResetLink(
                $form->get('email')->getData(),
                $browser->getPlatform(),
                $browser->getBrowser().' '.$browser->getVersion()
            ));
            $this->addFlash('success', 'An email has been sent to your address');

            return $this->redirectToRoute('app_send_reset_password_link');
        }

        return $this->render('security/sendResetPasswordLink.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/reset-password/{token}", name="app_reset_password", methods={"GET", "POST"})
     */
    public function resetPassword(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordType::class, ['token' => $request->get('token')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->dispatchMessage(new ResetPassword(
                    $form->get('token')->getData(),
                    $form->get('password')->getData()
                ));
                $this->addFlash('success', 'Your password has been changed, you can now log in');
            } catch (HandlerFailedException $exception) {
                $this->addFlash('danger', 'Invalid or expired password reset token');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/resetPassword.html.twig', ['form' => $form->createView()]);
    }
}
