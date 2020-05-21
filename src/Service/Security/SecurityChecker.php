<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security;

use Buddy\Repman\Service\Security\SecurityChecker\Advisory;
use Buddy\Repman\Service\Security\SecurityChecker\Package;
use Buddy\Repman\Service\Security\SecurityChecker\Result;
use Buddy\Repman\Service\Security\SecurityChecker\Versions;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Parser;

class SecurityChecker
{
    private Parser $yamlParser;
    private string $databaseDir;
    private string $databaseRepo;

    /**
     * @var array<string,Advisory[]>
     */
    private array $advisories = [];

    public function __construct(string $databaseDir, string $databaseRepo)
    {
        $this->yamlParser = new Parser();
        $this->databaseDir = $databaseDir;
        $this->databaseRepo = $databaseRepo;
    }

    public function update(): string
    {
        if (!is_dir($this->databaseDir.'/.git')) {
            @mkdir($this->databaseDir, 0777, true);

            return $this->cloneRepo();
        }

        return $this->updateRepo();
    }

    public function databaseDir(): string
    {
        $path = realpath($this->databaseDir);
        if ($path === false) {
            throw new \RuntimeException('Database directory does not exist');
        }

        return $path;
    }

    /**
     * @return mixed[]
     */
    public function check(string $lockFile): array
    {
        $packages = $this->getPackages($lockFile);
        $this->loadAdvisoriesDatabase();

        $alerts = [];
        foreach ($packages as $package) {
            $packageAdvisories = $this->checkPackage($package);
            if ($packageAdvisories === []) {
                continue;
            }

            $alerts[$package->name()] = (new Result(
                $package->version(),
                $packageAdvisories
            ))->toArray();
        }

        return $alerts;
    }

    /**
     * @return Advisory[]
     */
    private function checkPackage(Package $package): array
    {
        $packageAdvisories = $this->advisories[$package->name()] ?? [];

        $alerts = [];
        foreach ($packageAdvisories as $advisory) {
            foreach ($advisory->branches() as $versions) {
                if ($versions->include($package->version())) {
                    $alerts[] = $advisory;
                }
            }
        }

        return $alerts;
    }

    /**
     * @return Package[]
     */
    private function getPackages(string $lockFile): array
    {
        $contents = json_decode($lockFile, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException('Invalid composer.lock');
        }

        $packages = [];
        foreach (['packages', 'packages-dev'] as $key) {
            if (!isset($contents[$key]) || !is_array($contents[$key])) {
                continue;
            }

            foreach ($contents[$key] as $package) {
                $packages[] = new Package($package['name'], $package['version']);
            }
        }

        return $packages;
    }

    /**
     * @return array<string,Advisory[]>
     */
    private function getAdvisories(): array
    {
        $advisories = [];
        foreach ($this->getDatabase() as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'yaml') {
                continue;
            }

            $packageName = $this->parsePackageName(
                str_replace($this->databaseDir(), '', $file->getPathname())
            );

            if ($packageName === null) {
                continue;
            }

            $data = $this->yamlParser->parse(
                (string) file_get_contents($file->getRealPath())
            );

            if (!isset($advisories[$packageName])) {
                $advisories[$packageName] = [];
            }

            $advisories[$packageName][] = new Advisory(
                $data['title'],
                $data['cve'] ?? '',
                $data['link'],
                array_map(
                    fn ($branch) => new Versions(...$branch['versions']),
                    array_values($data['branches'])
                )
            );
        }

        return $advisories;
    }

    private function getDatabase(): \RecursiveIteratorIterator
    {
        $advisoryFilter = function (\SplFileInfo $file): bool {
            if ($file->isFile() && $file->getPath() === $this->databaseDir()) {
                return false;
            }

            if ($file->isDir()) {
                $dirName = $file->getFilename();
                if ($dirName[0] == '.') {
                    return false;
                }
            }

            return true;
        };

        return new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($this->databaseDir()),
                $advisoryFilter
            )
        );
    }

    private function parsePackageName(string $path): ?string
    {
        $matches = [];
        preg_match('~^/(?<name>.+/.+)/.+yaml$~', $path, $matches);

        return $matches['name'] ?? null;
    }

    private function loadAdvisoriesDatabase(): void
    {
        $this->advisories = $this->getAdvisories();
    }

    private function cloneRepo(): string
    {
        $process = new Process([
            'git', 'clone',
            '--depth', '1',
            '--branch', 'master',
            $this->databaseRepo, $this->databaseDir(),
        ]);

        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    private function updateRepo(): string
    {
        $process = new Process([
            'git', '-C', $this->databaseDir(),
            'pull', '--depth', '1', '--force',
        ]);

        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
