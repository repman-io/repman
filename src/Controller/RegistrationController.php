<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Form\Type\User\RegisterType;
use Buddy\Repman\Message\User\ConfirmEmail;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Message\User\SendConfirmToken;
use Buddy\Repman\Security\UserGuardHelper;
use Buddy\Repman\Service\Config;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private UserGuardHelper $guard;
    private Config $config;
    private MessageBusInterface $messageBus;

    public function __construct(
        UserGuardHelper $guard,
        Config $config,
        MessageBusInterface $messageBus
    ) {
        $this->guard = $guard;
        $this->config = $config;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/register", name="app_register", methods={"GET","POST"})
     */
    public function register(Request $request): Response
    {
        $this->ensureRegistrationIsEnabled();

        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->ensureLocalRegistrationIsEnabled();
            $this->messageBus->dispatch(new CreateUser(
                Uuid::uuid4()->toString(),
                $email = $form->get('email')->getData(),
                $form->get('plainPassword')->getData(),
                $confirmToken = Uuid::uuid4()->toString(),
                ['ROLE_USER']
            ));
            $this->messageBus->dispatch(new SendConfirmToken(
                $email,
                $confirmToken
            ));

            $this->addFlash('warning', "Please click the activation link for {$email} to verify your email.");

            $this->guard->authenticateUser($email, $request);

            if ($request->getSession()->has('organization-token')) {
                $this->addFlash('success', 'Your account has been created.');

                return $this->redirectToRoute('organization_accept_invitation', ['token' => $request->getSession()->remove('organization-token')]);
            }

            $this->addFlash('success', 'Your account has been created. Please create a new organization.');

            return $this->redirectToRoute('organization_create');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'oauthRegistrationEnabled' => $this->config->oauthRegistrationEnabled(),
            'localRegistrationEnabled' => $this->config->localRegistrationEnabled(),
        ]);
    }

    /**
     * @Route("/register/confirm/{token}", name="app_register_confirm", methods={"GET"}, requirements={"token":"%uuid_pattern%"})
     */
    public function confirm(string $token): Response
    {
        $this->ensureLocalRegistrationIsEnabled();

        try {
            $this->messageBus->dispatch(new ConfirmEmail($token));
            $this->addFlash('success', 'E-mail address was confirmed. Enjoy your Repman account.');
        } catch (\RuntimeException | \InvalidArgumentException $exception) {
            $this->addFlash('danger', 'Invalid or expired e-mail confirm token');
        }

        return $this->redirectToRoute('index');
    }

    private function ensureRegistrationIsEnabled(): void
    {
        if (!$this->config->userRegistrationEnabled()) {
            throw new NotFoundHttpException('Registration is disabled');
        }
    }

    private function ensureLocalRegistrationIsEnabled(): void
    {
        if (!$this->config->localRegistrationEnabled()) {
            throw new NotFoundHttpException('Local registration is disabled');
        }
    }
}
