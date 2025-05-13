<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use InvalidArgumentException;
use League\Flysystem\Exception;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20200720112146 extends AbstractMigration implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(?ContainerInterface $container = null): void
    {
        if (!$container instanceof ContainerInterface) {
            throw new InvalidArgumentException('Container is required');
        }

        $this->container = $container;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $filesystem = $this->container->get('proxy.storage.public');
        $register = $this->container->get(ProxyRegister::class);

        $register->all()->forEach(function (Proxy $proxy) use ($filesystem): void {
            foreach ($filesystem->listContents((string) parse_url($proxy->url(), PHP_URL_HOST), true) as $file) {
                if ($file['type'] !== 'file') {
                    continue;
                }

                // remove old metadata
                if ($file['extension'] === 'json') {
                    $filesystem->delete($file['path']);
                }

                if (!str_contains((string) $file['basename'], '_')) {
                    continue;
                }

                // rename old dist files to new format
                $newName = $file['dirname'].DIRECTORY_SEPARATOR.substr((string) $file['basename'], strpos((string) $file['basename'], '_') + 1);
                if ($filesystem->has($newName)) {
                    continue;
                }

                try {
                    $filesystem->rename($file['path'], $newName);
                } catch (Exception $exception) {
                    $this->write(sprintf('Error when renaming %s: %s', $file['path'], $exception->getMessage()));
                }
            }
        });
    }

    public function down(Schema $schema): void
    {
        // nothing to do here
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
