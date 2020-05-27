<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller;

use Buddy\Repman\Form\Type\User\RegisterType;
use Buddy\Repman\Message\User\ConfirmEmail;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Message\User\SendConfirmToken;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\UserGuardHelper;
use Buddy\Repman\Service\Config;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private UserGuardHelper $guard;
    private Config $config;
    private UserRepository $users;

    public function __construct(UserGuardHelper $guard, Config $config, UserRepository $users)
    {
        $this->guard = $guard;
        $this->config = $config;
        $this->users = $users;
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
            $this->dispatchMessage(new CreateUser(
                Uuid::uuid4()->toString(),
                $email = $form->get('email')->getData(),
                $form->get('plainPassword')->getData(),
                $confirmToken = Uuid::uuid4()->toString(),
                $this->users->anyExists() ? ['ROLE_USER'] : ['ROLE_USER', 'ROLE_ADMIN'],
            ));
            $this->dispatchMessage(new SendConfirmToken(
                $email,
                $confirmToken
            ));

            $this->addFlash('warning', "Please click the activation link for {$email} to verify your email.");
            $this->addFlash('success', 'Your account has been created. Please create a new organization.');
            $this->guard->authenticateUser($email, $request);

            return $this->redirectToRoute('organization_create');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register/confirm/{token}", name="app_register_confirm", methods={"GET"}, requirements={"token":"%uuid_pattern%"})
     */
    public function confirm(string $token): Response
    {
        $this->ensureRegistrationIsEnabled();

        try {
            $this->dispatchMessage(new ConfirmEmail($token));
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
}
