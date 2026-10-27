<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Buddy\Repman\Migrations\Factory\ContainerAwareMigration;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use League\Flysystem\FilesystemException;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20200720112146 extends AbstractMigration implements ContainerAwareMigration
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
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
            foreach ($filesystem->listContents(sprintf('%s', (string) parse_url($proxy->url(), PHP_URL_HOST)), true) as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $path = $file->path();
                $basename = basename($path);

                // remove old metadata
                if (pathinfo($path, PATHINFO_EXTENSION) === 'json') {
                    $filesystem->delete($path);
                    continue;
                }

                if (strpos($basename, '_') === false) {
                    continue;
                }

                // rename old dist files to new format
                $newName = dirname($path).'/'.substr($basename, strpos($basename, '_') + 1);
                if ($filesystem->fileExists($newName)) {
                    continue;
                }

                try {
                    $filesystem->move($path, $newName);
                } catch (FilesystemException $exception) {
                    $this->write(sprintf('Error when renaming %s: %s', $path, $exception->getMessage()));
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
