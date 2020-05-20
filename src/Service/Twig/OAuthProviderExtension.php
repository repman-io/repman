<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class OAuthProviderExtension extends AbstractExtension
{
    /**
     * @var array<string,?string>
     */
    private array $providers;

    /**
     * @param array<string,?string> $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oauth_enabled', [$this, 'oAuthEnabled']),
        ];
    }

    public function oAuthEnabled(?string $provider = null): bool
    {
        if ($provider !== null) {
            return isset($this->providers[$provider]) && strlen($this->providers[$provider]) > 0;
        }

        return count(array_filter($this->providers, fn ($id) => strlen((string) $id) > 0)) > 0;
    }
}
