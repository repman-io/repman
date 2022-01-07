<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\OAuth;

use Buddy\Repman\Message\User\AddOAuthToken;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Security\UserGuardHelper;
use Buddy\Repman\Service\Config;
use Http\Client\Exception as HttpException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Exception\OAuth2ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class OAuthController extends AbstractController
{
    protected UserGuardHelper $guard;
    protected ClientRegistry $oauth;
    private Config $config;
    private MessageBusInterface $messageBus;

    public function __construct(
        UserGuardHelper $guard,
        ClientRegistry $oauth,
        Config $config,
        MessageBusInterface $messageBus
    ) {
        $this->guard = $guard;
        $this->oauth = $oauth;
        $this->config = $config;
        $this->messageBus = $messageBus;
    }

    /**
     * @param callable():string $emailProvider
     */
    protected function createAndAuthenticateUser(string $type, callable $emailProvider, Request $request): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('index');
        }

        try {
            $email = $emailProvider();
            $params = [];
            if (!$this->guard->userExists($email)) {
                $this->messageBus->dispatch(new CreateOAuthUser($email));
                $this->addFlash('success', 'Your account has been created. Please create a new organization.');
                $params['origin'] = $type;
            } else {
                $this->addFlash('success', 'Your account already exists. You have been logged in automatically');
            }
            $this->guard->authenticateUser($email, $request);

            return $this->redirectToRoute('organization_create', $params);
        } catch (OAuth2ClientException $exception) {
            $this->addFlash('danger', 'Authentication failed! Did you authorize our app?');
        } catch (IdentityProviderException | HttpException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_register');
    }

    protected function storeRepoToken(Request $request, string $type, callable $tokenProvider, string $route): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        try {
            /** @var AccessToken $token */
            $token = $tokenProvider();
            $this->messageBus->dispatch(
                new AddOAuthToken(
                    Uuid::uuid4()->toString(),
                    $user->id(),
                    $type,
                    $token->getToken(),
                    $token->getRefreshToken(),
                    $token->getExpires() !== null ? (new \DateTimeImmutable())->setTimestamp($token->getExpires()) : null
                )
            );

            return $this->redirectToRoute($route, [
                'organization' => $request->getSession()->get('organization', $user->firstOrganizationAlias()->getOrElseThrow(new NotFoundHttpException())),
                'type' => $type,
            ]);
        } catch (OAuth2ClientException | IdentityProviderException $e) {
            $this->addFlash('danger', 'Error while getting oauth token: '.$e->getMessage());

            return $this->redirectToRoute('organization_package_new', [
                'organization' => $request->getSession()->get('organization', $user->firstOrganizationAlias()->getOrElseThrow(new NotFoundHttpException())),
            ]);
        }
    }

    protected function ensureOAuthRegistrationIsEnabled(): void
    {
        if (!$this->config->oauthRegistrationEnabled()) {
            throw new NotFoundHttpException('Registration using OAuth is disabled');
        }
    }
}
