<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

use Buddy\Repman\Service\Security\SecurityChecker;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Parser;

final class SensioLabsSecurityChecker implements SecurityChecker
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

    public function update(): bool
    {
        if (!is_dir($this->databaseDir.'/.git')) {
            @mkdir($this->databaseDir, 0777, true);
            $this->cloneRepo();

            return true;
        }

        return $this->updateRepo();
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
                str_replace($this->databaseDir, '', $file->getPathname())
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

    /**
     * @return \RecursiveIteratorIterator<\RecursiveCallbackFilterIterator>
     */
    private function getDatabase(): \RecursiveIteratorIterator
    {
        if (!is_dir($this->databaseDir)) {
            throw new \RuntimeException('Advisories database does not exist');
        }

        $advisoryFilter = function (\SplFileInfo $file): bool {
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
                new \RecursiveDirectoryIterator($this->databaseDir),
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
        if ($this->advisories === []) {
            $this->advisories = $this->getAdvisories();
        }
    }

    private function cloneRepo(): void
    {
        $this->runProcess([
            'git', 'clone', '--depth', '1', '--branch', 'master', $this->databaseRepo, '.',
        ]);
    }

    private function updateRepo(): bool
    {
        $this->runProcess(['git', '--git-dir=.git', 'clean', '-f']);
        $this->runProcess(['git', '--git-dir=.git', 'reset', '--hard', 'origin/master']);
        $output = $this->runProcess(['git', '--git-dir=.git', 'pull']);

        return preg_match('/up to date/i', $output) !== 1;
    }

    /**
     * @param string[] $command
     */
    protected function runProcess(array $command): string
    {
        $process = new Process($command, $this->databaseDir);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
