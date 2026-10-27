<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\PackageScanner;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\ScanResult;
use Buddy\Repman\Message\Security\SendScanResult;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Repository\ScanResultRepository;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\Security\PackageScanner;
use Buddy\Repman\Service\Security\SecurityChecker;
use Composer\Semver\VersionParser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

final class SensioLabsPackageScanner implements PackageScanner
{
    private SecurityChecker $checker;
    private VersionParser $versionParser;
    private PackageManager $packageManager;
    private ScanResultRepository $results;
    private MessageBusInterface $messageBus;
    private Storage $distStorage;

    public function __construct(
        SecurityChecker $checker,
        PackageManager $packageManager,
        ScanResultRepository $results,
        MessageBusInterface $messageBus,
        Storage $distStorage,
    ) {
        $this->checker = $checker;
        $this->packageManager = $packageManager;
        $this->results = $results;
        $this->messageBus = $messageBus;
        $this->versionParser = new VersionParser();
        $this->distStorage = $distStorage;
    }

    public function scan(Package $package): void
    {
        $packageName = $package->name();
        $result = [];
        $status = ScanResult::STATUS_OK;

        // checked here because every path goes through it: the scan-all command and the
        // advisories update run it directly, not through ScanPackageHandler
        if ($packageName === null || !$package->isEnabledSecurityScan()) {
            return;
        }

        try {
            $lockFiles = $this->lockFiles($package);
            if ($lockFiles === []) {
                $status = ScanResult::STATUS_NOT_AVAILABLE;
            }

            foreach ($lockFiles as $lockFileName => $content) {
                $scanResults = $this->checker->check($content);
                if ($scanResults !== []) {
                    $status = ScanResult::STATUS_WARNING;
                }

                $result[$lockFileName] = $scanResults;
            }
        } catch (\Throwable $exception) {
            $this->saveResult(
                $package,
                ScanResult::STATUS_ERROR,
                [
                    'exception' => [
                        \get_class($exception) => $exception->getMessage(),
                    ],
                ],
            );

            return;
        }

        $this->saveResult($package, $status, $result);
    }

    /**
     * @param mixed[] $result
     */
    private function saveResult(Package $package, string $status, array $result): void
    {
        $date = new \DateTimeImmutable();
        $package->setScanResult($status, $date, $result);
        $this->results->add(new ScanResult(Uuid::uuid4(), $package, $date, $status, $result));

        if ($status === ScanResult::STATUS_WARNING) {
            $this->messageBus->dispatch(
                new SendScanResult(
                    $package->scanResultEmails(),
                    $package->organizationAlias(),
                    (string) $package->name(),
                    $package->id()->toString(),
                    $result
                )
            );
        }
    }

    /**
     * @return array<string,string> composer.lock contents by their path in the package
     */
    private function lockFiles(Package $package): array
    {
        $packageName = (string) $package->name();
        $latestReleasedVersion = (string) $package->latestReleasedVersion();

        [, $providerData] = $this->packageManager->findProviders(
            $package->organizationAlias(),
            [new PackageName($package->id()->toString(), $packageName)]
        );
        $versions = $providerData[$packageName] ?? [];

        $packageData = $latestReleasedVersion === 'no stable release'
            ? $this->defaultBranch($versions)
            : $this->versionData($versions, $this->versionParser->normalize($latestReleasedVersion));
        if ($packageData === null) {
            throw new \RuntimeException(sprintf('Version %s for package %s not found', $latestReleasedVersion, $packageName));
        }

        $packageVersion = $this->normalizedVersion($packageData);
        $packageDist = $packageData['dist'] ?? [];
        $reference = $packageDist['reference'] ?? $packageDist['shasum'] ?? null;
        if (isset($packageDist['url'], $packageDist['type']) && $reference !== null) {
            return $this->extractLockFiles($this->packageManager
                ->distFilename($package->organizationAlias(), $packageName, $packageVersion, $reference, $packageDist['type'])
                ->getOrElseThrow(new \RuntimeException("Distribution file for version $packageVersion not found")));
        }

        // installed from source (plain git, hg, svn): the sync kept the composer.lock of the version
        $sourceReference = $packageData['source']['reference'] ?? null;
        $lock = \is_string($sourceReference)
            ? $this->packageManager->sourceLock($package->organizationAlias(), $packageName, $packageVersion, $sourceReference)
            : null;

        return $lock !== null ? ['/composer.lock' => $lock] : [];
    }

    /**
     * The version to scan when there is no stable release. Composer 2 marks the default branch; data
     * synced with Composer 1 named it 9999999-dev (Composer 2 normalizes dev-master to dev-master).
     *
     * @param array<string, array<string, mixed>> $versions
     *
     * @return array<string, mixed>|null
     */
    private function defaultBranch(array $versions): ?array
    {
        foreach ($versions as $packageData) {
            if (($packageData['default-branch'] ?? false) === true) {
                return $packageData;
            }
        }

        foreach ($versions as $packageData) {
            if (\in_array($this->normalizedVersion($packageData), ['dev-master', '9999999-dev'], true)) {
                return $packageData;
            }
        }

        return null;
    }

    /**
     * @param array<string, array<string, mixed>> $versions
     *
     * @return array<string, mixed>|null
     */
    private function versionData(array $versions, string $normalizedVersion): ?array
    {
        foreach ($versions as $packageData) {
            if ($this->normalizedVersion($packageData) === $normalizedVersion) {
                return $packageData;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $packageData
     */
    private function normalizedVersion(array $packageData): string
    {
        return (string) ($packageData['version_normalized'] ?? $this->versionParser->normalize((string) $packageData['version']));
    }

    /**
     * @return array<string,string>
     */
    private function extractLockFiles(string $distFilename): array
    {
        $tmpZipFile = $this->distStorage->getLocalFileForDistUrl($distFilename);
        $zip = new \ZipArchive();
        $result = $zip->open($tmpZipFile->get());
        if ($result !== true) {
            throw new \RuntimeException("Error while opening ZIP file '$distFilename', code: $result");
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $names[$i] = (string) $zip->getNameIndex($i);
        }

        // archives of GitHub, GitLab and Bitbucket wrap everything in one directory, artifacts usually do not
        $first = \strstr($names[0] ?? '', '/', true);
        $root = $first !== false && \array_filter($names, fn (string $name): bool => !\str_starts_with($name, $first.'/')) === [] ? $first.'/' : '';

        $lockFiles = [];
        foreach ($names as $i => $filename) {
            if (\preg_match('{(^|/)composer\.lock$}', $filename) === 1) {
                $lockFiles['/'.\substr($filename, \strlen($root))] = (string) $zip->getFromIndex($i);
            }
        }

        $zip->close();

        @\unlink($tmpZipFile->get());

        return $lockFiles;
    }
}
