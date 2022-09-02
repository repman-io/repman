<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\Aws;

use Aws\S3\S3Client;

final class S3AdapterFactory
{
    private string $region;

    private bool $isOpaqueAuth;

    private string $key;

    private string $secret;

    private ?string $endpoint;

    private ?bool $pathStyleEndpoints;

    public function __construct(
        string $region,
        bool $isOpaqueAuth,
        ?string $key = null,
        ?string $secret = null,
        ?string $endpoint = null,
        ?bool $pathStyleEndpoints = false
    ) {
        $this->region = $region;
        $this->isOpaqueAuth = $isOpaqueAuth;
        $this->endpoint = $endpoint;
        $this->pathStyleEndpoints = $pathStyleEndpoints;

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
            'use_path_style_endpoint' => $this->pathStyleEndpoints,
        ];

        if ($this->isOpaqueAuth) {
            $args['credentials'] = [
                'key' => $this->key,
                'secret' => $this->secret,
            ];
        }

        if ($this->endpoint !== null) {
            $args['endpoint'] = $this->endpoint;
        }

        return new S3Client($args);
    }
}
