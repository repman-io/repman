<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageSynchronizer;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Repository\Vcs\VcsDriverInterface;

/**
 * Drops composer.json "version" so Composer uses the VCS tag/branch name instead.
 *
 * Without this, Composer silently skips tags when the hardcoded version does not match
 * the tag (sync succeeds, webhook returns 202, but the new release never appears).
 */
final class IgnoreComposerJsonVersionVcsDriver implements VcsDriverInterface
{
    private VcsDriverInterface $driver;

    public function __construct(VcsDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function initialize(): void
    {
        $this->driver->initialize();
    }

    /**
     * @param string $identifier
     *
     * @return mixed[]|null
     */
    public function getComposerInformation($identifier)
    {
        $data = $this->driver->getComposerInformation($identifier);

        if (\is_array($data)) {
            unset($data['version']);
        }

        return $data;
    }

    /**
     * @param string $file
     * @param string $identifier
     *
     * @return string|null
     */
    public function getFileContent($file, $identifier)
    {
        return $this->driver->getFileContent($file, $identifier);
    }

    /**
     * @param string $identifier
     *
     * @return \DateTime|null
     */
    public function getChangeDate($identifier)
    {
        return $this->driver->getChangeDate($identifier);
    }

    public function getRootIdentifier(): string
    {
        return $this->driver->getRootIdentifier();
    }

    /**
     * @return array<string, string>
     */
    public function getBranches(): array
    {
        return $this->driver->getBranches();
    }

    /**
     * @return array<string, string>
     */
    public function getTags(): array
    {
        return $this->driver->getTags();
    }

    /**
     * @param string $identifier
     *
     * @return mixed[]|null
     */
    public function getDist($identifier)
    {
        return $this->driver->getDist($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return mixed[]
     */
    public function getSource($identifier)
    {
        return $this->driver->getSource($identifier);
    }

    public function getUrl(): string
    {
        return $this->driver->getUrl();
    }

    /**
     * @param string $identifier
     */
    public function hasComposerFile($identifier): bool
    {
        return $this->driver->hasComposerFile($identifier);
    }

    public function cleanup(): void
    {
        $this->driver->cleanup();
    }

    /**
     * @param string $url
     * @param bool   $deep
     */
    public static function supports(IOInterface $io, Config $config, $url, $deep = false): bool
    {
        return false;
    }
}
