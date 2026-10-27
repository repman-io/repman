<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\ReadmeExtractor;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

final class ReadmeExtractorTest extends TestCase
{
    private Storage $storage;
    private ReadmeExtractor $extractor;
    private Dist $dist;

    protected function setUp(): void
    {
        $this->storage = new Storage(new FakeDownloader(), new Filesystem(new InMemoryFilesystemAdapter()));
        $this->extractor = new ReadmeExtractor($this->storage);
        $this->dist = new Dist('buddy', 'buddy-works/alpha', '1.0.0.0', 'ref', 'zip');
    }

    public function testRawHtmlInReadmeIsEscaped(): void
    {
        $this->storeDist(['package/README.md' => "# Title\n\n<script>alert('xss')</script>\n\n<img src=x onerror=alert(1)>\n"]);
        $package = PackageMother::some();

        $this->extractor->extractReadme($package, $this->dist);

        $readme = (string) $package->readme();
        self::assertStringContainsString('<h1>', $readme);
        self::assertStringNotContainsString('<script', $readme);
        self::assertStringNotContainsString('<img', $readme);
        self::assertStringContainsString('&lt;script&gt;', $readme);
    }

    public function testUnsafeLinksAreDropped(): void
    {
        $this->storeDist(['README.md' => "[click](javascript:alert(1))\n"]);
        $package = PackageMother::some();

        $this->extractor->extractReadme($package, $this->dist);

        self::assertStringNotContainsString('javascript:', (string) $package->readme());
    }

    public function testGithubFlavoredMarkdownAndExternalLinks(): void
    {
        $this->storeDist(['README.md' => "| a | b |\n|---|---|\n| 1 | 2 |\n\n~~old~~ https://example.com\n"]);
        $package = PackageMother::some();

        $this->extractor->extractReadme($package, $this->dist);

        $readme = (string) $package->readme();
        self::assertStringContainsString('<table>', $readme);
        self::assertStringContainsString('<del>old</del>', $readme);
        self::assertStringContainsString('target="_blank"', $readme);
        self::assertStringContainsString('rel="noopener noreferrer"', $readme);
    }

    public function testReadmeInLatin1IsConverted(): void
    {
        $this->storeDist(['README.md' => "# Caf\xE9\n\nna\xEFve\n"]);
        $package = PackageMother::some();

        $this->extractor->extractReadme($package, $this->dist);

        self::assertStringContainsString('Café', (string) $package->readme());
        self::assertStringContainsString('naïve', (string) $package->readme());
    }

    public function testReadmeInUtf16IsConverted(): void
    {
        $this->storeDist(['README.md' => "\xFF\xFE".mb_convert_encoding("# Zürich\n", 'UTF-16LE', 'UTF-8')]);
        $package = PackageMother::some();

        $this->extractor->extractReadme($package, $this->dist);

        self::assertStringContainsString('Zürich</h1>', (string) $package->readme());
    }

    public function testReadmeIsNullWhenDistIsMissing(): void
    {
        $package = PackageMother::some();

        $this->extractor->extractReadme($package, $this->dist);

        self::assertNull($package->readme());
    }

    public function testReadmeIsNullWhenDistIsNotAZip(): void
    {
        $notZip = (string) tempnam(sys_get_temp_dir(), 'readme-test');
        file_put_contents($notZip, 'definitely not a zip archive');
        $this->storage->download($notZip, $this->dist);
        @unlink($notZip);
        $package = PackageMother::some();
        $tempFiles = (array) glob(sys_get_temp_dir().'/repman-dist-*');

        $this->extractor->extractReadme($package, $this->dist);

        self::assertNull($package->readme());
        self::assertSame($tempFiles, (array) glob(sys_get_temp_dir().'/repman-dist-*'), 'temporary dist copy must be removed');
    }

    /**
     * @param array<string,string> $files
     */
    private function storeDist(array $files): void
    {
        $zipPath = (string) tempnam(sys_get_temp_dir(), 'readme-test');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        $this->storage->download($zipPath, $this->dist);
        @unlink($zipPath);
    }
}
