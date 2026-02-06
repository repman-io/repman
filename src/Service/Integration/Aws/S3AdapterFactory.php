<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\Aws;

use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;

/**
 * Factory for creating S3Client instances with flexible authentication.
 *
 * Supports multiple authentication methods:
 * - Explicit credentials (STORAGE_AWS_OPAQUE_AUTH=true): Uses provided key/secret
 * - Default credential chain (STORAGE_AWS_OPAQUE_AUTH=false): Uses AWS SDK's default
 *   credential provider chain which supports:
 *   - Environment variables (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY)
 *   - Web identity token (EKS Pod Identity / IRSA)
 *   - ECS container credentials
 *   - EC2 instance profile credentials
 */
final class S3AdapterFactory
{
    private string $region;

    private bool $isOpaqueAuth;

    private string $key;

    private string $secret;

    private ?string $endpoint;

    private ?bool $pathStyleEndpoint;

    public function __construct(
        string $region,
        bool $isOpaqueAuth,
        ?string $key = null,
        ?string $secret = null,
        ?string $endpoint = null,
        ?bool $pathStyleEndpoint = false
    ) {
        $this->region = $region;
        $this->isOpaqueAuth = $isOpaqueAuth;
        $this->endpoint = $endpoint;
        $this->pathStyleEndpoint = $pathStyleEndpoint;

        if ($this->isOpaqueAuth) {
            if ($key === null || $key === '') {
                throw new \InvalidArgumentException('Must pass AWS key when authentication is opaque');
            }
            if ($secret === null || $secret === '') {
                throw new \InvalidArgumentException('Must pass AWS secret when authentication is opaque');
            }

            $this->key = $key;
            $this->secret = $secret;
        }
    }

    public function create(): S3Client
    {
        $args = [
            'region' => $this->region,
            'version' => 'latest',
            'use_path_style_endpoint' => $this->pathStyleEndpoint,
        ];

        if ($this->isOpaqueAuth) {
            // Use explicit credentials when provided
            $args['credentials'] = [
                'key' => $this->key,
                'secret' => $this->secret,
            ];
        } else {
            // Use the default credential provider chain with memoization
            // This supports: env vars, web identity (Pod Identity/IRSA), ECS, instance profile
            $provider = CredentialProvider::defaultProvider();
            $args['credentials'] = CredentialProvider::memoize($provider);
        }

        if ($this->endpoint !== null) {
            $args['endpoint'] = $this->endpoint;
        }

        return new S3Client($args);
    }
}
