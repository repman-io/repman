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

    public function initialize()
    {
        $this->driver->initialize();
    }

    /**
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

    public function getFileContent($file, $identifier)
    {
        return $this->driver->getFileContent($file, $identifier);
    }

    public function getChangeDate($identifier)
    {
        return $this->driver->getChangeDate($identifier);
    }

    public function getRootIdentifier()
    {
        return $this->driver->getRootIdentifier();
    }

    public function getBranches()
    {
        return $this->driver->getBranches();
    }

    public function getTags()
    {
        return $this->driver->getTags();
    }

    public function getDist($identifier)
    {
        return $this->driver->getDist($identifier);
    }

    public function getSource($identifier)
    {
        return $this->driver->getSource($identifier);
    }

    public function getUrl()
    {
        return $this->driver->getUrl();
    }

    public function hasComposerFile($identifier)
    {
        return $this->driver->hasComposerFile($identifier);
    }

    public function cleanup()
    {
        $this->driver->cleanup();
    }

    public static function supports(IOInterface $io, Config $config, $url, $deep = false)
    {
        return false;
    }
}
