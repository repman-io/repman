<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\PackageScanner;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\ScanResult;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Repository\ScanResultRepository;
use Buddy\Repman\Service\Organization\PackageManager;
use Buddy\Repman\Service\PackageScanner;
use Composer\Semver\VersionParser;
use Ramsey\Uuid\Uuid;
use SensioLabs\Security\SecurityChecker;

class SensioLabPackageScanner implements PackageScanner
{
    private SecurityChecker $checker;
    private VersionParser $versionParser;
    private PackageManager $packageManager;
    private ScanResultRepository $results;

    public function __construct(SecurityChecker $checker, PackageManager $packageManager, ScanResultRepository $results)
    {
        $this->checker = $checker;
        $this->packageManager = $packageManager;
        $this->results = $results;
        $this->versionParser = new VersionParser();
    }

    public function scan(Package $package): void
    {
        $packageName = $package->name();
        $result = [];
        if ($packageName === null) {
            return;
        }

        try {
            $result = $this->performScan(
                $this->extractLockFile($this->findDistribution($package))
            );
        } catch (\Throwable $exception) {
            $result[get_class($exception)] = $exception->getMessage();
            $this->saveResult($package, ScanResult::STATUS_ERROR, $result);

            return;
        }

        if ($result === []) {
            $this->saveResult($package, ScanResult::STATUS_OK, $result);

            return;
        }

        $this->saveResult($package, ScanResult::STATUS_WARNING, $result);
    }

    /**
     * @param mixed[] $result
     */
    private function saveResult(Package $package, string $status, array $result): void
    {
        $this->results->add(
            new ScanResult(
                Uuid::uuid4(),
                $package,
                new \DateTimeImmutable(),
                $status,
                $result
            )
        );
    }

    private function findDistribution(Package $package): string
    {
        $packageName = $package->name();
        $latestReleasedVersion = $package->latestReleasedVersion();

        $normalizedVersion = $latestReleasedVersion === 'no stable release' ?
            '9999999-dev' : $this->versionParser->normalize((string) $latestReleasedVersion);

        $providerData = $this->packageManager->findProviders(
            $package->organizationAlias(),
            [new PackageName($package->id()->toString(), (string) $package->name())]
        );

        foreach ($providerData[$packageName] ?? [] as $packageData) {
            $packageVersion = $packageData['version_normalized'] ?? $this->versionParser->normalize($packageData['version']);
            $packageDist = $packageData['dist'];
            $reference = $packageDist['reference'];

            if ($packageVersion === $normalizedVersion && isset($packageDist['url'], $reference)) {
                $archiveType = $packageDist['type'];
                $filename = $this->packageManager->distFilename(
                    $package->organizationAlias(),
                    (string) $packageName,
                    $normalizedVersion,
                    $reference,
                    $archiveType
                );

                return $filename->getOrElseThrow(
                    new \RuntimeException('Distribution file not found')
                );
            }
        }

        throw new \RuntimeException("Version $normalizedVersion for package $packageName not found");
    }

    private function extractLockFile(string $distFilename): string
    {
        $zip = new \ZipArchive();
        $result = $zip->open($distFilename);
        if ($result !== true) {
            throw new \RuntimeException("Error while opening ZIP file '$distFilename', code: $result");
        }

        $content = null;
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            if (preg_match('/\/composer.lock$/', (string) $zip->getNameIndex($i)) === 1) {
                $content = $zip->getFromIndex($i);

                break;
            }
        }

        $zip->close();

        if (!is_string($content)) {
            throw new \RuntimeException('Lock file not found');
        }

        return $content;
    }

    /**
     * @return mixed[]
     */
    private function performScan(string $content): array
    {
        $result = $this->checker->check(
            'data://text/plain;base64,'.base64_encode($content),
            'json'
        );

        return json_decode((string) $result, true) ?? [];
    }
}
