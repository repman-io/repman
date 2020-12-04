<?php
/**
 * CRM PROJECT
 * THIS FILE IS A PART OF CRM PROJECT
 * CRM PROJECT IS PROPERTY OF Legal One GmbH.
 *
 * @copyright Copyright (c) 2020 Legal One GmbH (http://www.legal.one)
 */

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Aws\Credentials\Credentials;
use Buddy\Repman\Service\AmazonWebServices\S3AdapterFactory;
use PHPUnit\Framework\TestCase;

class S3AdapterFactoryTest extends TestCase
{
    public function testCreateWithMachineCredentials(): void
    {
        $factory = new S3AdapterFactory('eu-east-1', false, '', '');

        $instance = $factory->create();
        $cfg = $instance->getConfig();

        self::assertSame('eu-east-1', $cfg['signing_region']);
        self::assertSame('s3v4', $cfg['signature_version']);
    }

    public function testCreateWithOpaqueCredentials(): void
    {
        $factory = new S3AdapterFactory('eu-east-1', true, 'mykey', 'secret');

        $instance = $factory->create();
        $cfg = $instance->getConfig();

        self::assertSame('eu-east-1', $cfg['signing_region']);
        self::assertSame('s3v4', $cfg['signature_version']);

        /** @var Credentials $creds */
        $creds = $instance->getCredentials()->wait();
        self::assertSame('mykey', $creds->getAccessKeyId());
        self::assertSame('secret', $creds->getSecretKey());
    }
}
