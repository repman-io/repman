<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

use Buddy\Repman\Service\Security\SecurityChecker;
use Composer\Semver\Semver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Checks composer.lock files against Packagist's security advisories API, the source `composer audit`
 * uses: it merges the GitHub Advisory Database with FriendsOfPHP/security-advisories, which alone (the
 * git clone this replaced) missed most advisories, e.g. 2 of 18 for league/commonmark.
 *
 * @see https://packagist.org/apidoc#list-security-advisories
 */
final class PackagistSecurityChecker implements SecurityChecker
{
    // names per request, the API reads them from the query or the form body
    private const BATCH_SIZE = 100;

    public function __construct(
        private HttpClientInterface $client,
        private string $apiUrl,
        private string $stateDir,
    ) {
    }

    /**
     * Tells whether advisories were published or changed since the last call, so the packages are only
     * rescanned then. The time of the last call is kept in a file: the cache is cleared on every start.
     */
    public function update(): bool
    {
        $stateFile = $this->stateDir.'/last-update';
        $since = is_file($stateFile) ? (int) file_get_contents($stateFile) : null;
        $now = time();

        $updated = $since === null || $this->advisories(['updatedSince' => $since]) !== [];

        if (!is_dir($this->stateDir)) {
            mkdir($this->stateDir, 0777, true);
        }
        file_put_contents($stateFile, (string) $now);

        return $updated;
    }

    /**
     * @return array<string,array{version:string,advisories:list<array{title:string,cve:string,link:string}>}>
     */
    public function check(string $lockFile): array
    {
        $alerts = [];
        foreach (array_chunk($this->packages($lockFile), self::BATCH_SIZE, true) as $packages) {
            $advisories = $this->advisories(['packages' => array_keys($packages)]);

            foreach ($packages as $name => $version) {
                $matching = [];
                foreach ($advisories[$name] ?? [] as $advisory) {
                    if ($this->affects($version, (string) ($advisory['affectedVersions'] ?? ''))) {
                        $matching[] = [
                            'title' => (string) ($advisory['title'] ?? ''),
                            'cve' => (string) ($advisory['cve'] ?? ''),
                            'link' => (string) ($advisory['link'] ?? 'https://packagist.org/security-advisories/'.($advisory['advisoryId'] ?? '')),
                        ];
                    }
                }

                if ($matching !== []) {
                    $alerts[$name] = ['version' => $version, 'advisories' => $matching];
                }
            }
        }

        return $alerts;
    }

    /**
     * @param array<string,mixed> $query
     *
     * @return array<string,list<array<string,mixed>>>
     */
    private function advisories(array $query): array
    {
        // names go in a POST body, a long list does not fit into a URL; updatedSince alone is GET only
        $response = $this->client->request(isset($query['packages']) ? 'POST' : 'GET', $this->apiUrl, [
            isset($query['packages']) ? 'body' : 'query' => $query,
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 30,
        ])->toArray();

        $advisories = $response['advisories'] ?? [];

        return is_array($advisories) ? array_filter($advisories, fn (mixed $list): bool => $list !== []) : [];
    }

    /**
     * @return array<string,string> name => version
     */
    private function packages(string $lockFile): array
    {
        $contents = json_decode($lockFile, true);
        if (!is_array($contents)) {
            throw new \UnexpectedValueException('Invalid composer.lock');
        }

        $packages = [];
        foreach (['packages', 'packages-dev'] as $key) {
            foreach (is_array($contents[$key] ?? null) ? $contents[$key] : [] as $package) {
                if (isset($package['name'], $package['version'])) {
                    $packages[(string) $package['name']] = (string) $package['version'];
                }
            }
        }

        return $packages;
    }

    private function affects(string $version, string $affectedVersions): bool
    {
        if ($affectedVersions === '') {
            return false;
        }

        try {
            return Semver::satisfies($version, $affectedVersions);
        } catch (\UnexpectedValueException) {
            // a branch such as dev-main has no version to compare
            return false;
        }
    }
}
