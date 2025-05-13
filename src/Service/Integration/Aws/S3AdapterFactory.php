<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration\Aws;

use Aws\S3\S3Client;
use InvalidArgumentException;

final class S3AdapterFactory
{
    private string $key;

    private string $secret;

    public function __construct(
        private readonly string $region,
        private readonly bool $isOpaqueAuth,
        ?string $key = null,
        ?string $secret = null,
        private readonly ?string $endpoint = null,
        private readonly ?bool $pathStyleEndpoint = false,
    ) {
        if ($this->isOpaqueAuth) {
            if ($key === null || $key === '') {
                throw new InvalidArgumentException('Must pass AWS key when authentication is opaque');
            }

            if ($secret === null || $secret === '') {
                throw new InvalidArgumentException('Must pass AWS secret when authentication is opaque');
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
