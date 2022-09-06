<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Composer;

use Symfony\Component\HttpFoundation\Request;

final class ComposerEnvironmentFactory
{
    public function fromRequest(Request $request): ComposerEnvironment
    {
        $userAgent = $request->headers->get('User-Agent');

        return $this->fromUserAgent($userAgent);
    }

    public function fromUserAgent(string $userAgent): ComposerEnvironment
    {
        if (!str_starts_with($userAgent, 'Composer/')) {
            throw new \RuntimeException('User Agent appears not to be a composer User Agent');
        }

        preg_match('/^Composer\/(.+) \((.*)\)$/', $userAgent, $matches);

        return new ComposerEnvironment(
            $matches['1'],
        );
    }
}
