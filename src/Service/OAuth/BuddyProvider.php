<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * OAuth2 provider for Buddy.Works. Taken from buddy-works/oauth2-client (MIT), which is no longer
 * maintained, trimmed to what Repman uses.
 */
final class BuddyProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public const SCOPE_USER_INFO = 'USER_INFO';
    public const SCOPE_USER_EMAIL = 'USER_EMAIL';

    private string $baseUrl;

    /**
     * @param mixed[] $options
     * @param mixed[] $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
        $this->baseUrl = rtrim((string) ($options['baseApiUrl'] ?? 'https://api.buddy.works'), '/');
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->baseUrl.'/oauth2/authorize';
    }

    /**
     * @param mixed[] $params
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->baseUrl.'/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->baseUrl.'/user';
    }

    /**
     * @return string[]
     */
    protected function getDefaultScopes(): array
    {
        return [self::SCOPE_USER_INFO];
    }

    protected function getScopeSeparator(): string
    {
        // sent encoded as "+", the separator Buddy expects
        return ' ';
    }

    /**
     * @param mixed[]|string $data
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() < 400 && !isset($data['errors'])) {
            return;
        }

        $message = \is_array($data) && \is_string($data['errors'][0]['message'] ?? null)
            ? $data['errors'][0]['message']
            : $response->getReasonPhrase();

        throw new IdentityProviderException($message, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @param mixed[] $response
     */
    protected function createResourceOwner(array $response, AccessToken $token): BuddyResourceOwner
    {
        return new BuddyResourceOwner($response);
    }
}
