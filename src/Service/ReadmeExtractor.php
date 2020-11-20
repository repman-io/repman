<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\Dist\Storage;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverterInterface;
use Munus\Control\Option;

final class ReadmeExtractor
{
    private Storage $distStorage;
    private MarkdownConverterInterface $markdownConverter;

    public function __construct(Storage $distStorage)
    {
        $this->distStorage = $distStorage;

        $environment = Environment::createGFMEnvironment();
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new HeadingPermalinkExtension());

        $this->markdownConverter = new CommonMarkConverter(
            [
                'allow_unsafe_links' => false,
                'external_link' => [
                    'open_in_new_window' => true,
                    'nofollow' => '',
                    'noopener' => 'external',
                    'noreferrer' => 'external',
                ],
                'heading_permalink' => [
                    'symbol' => '',
                    'html_class' => '',
                    'title' => '',
                ],
            ],
            $environment
        );
    }

    public function extractReadme(Package $package, Dist $dist): void
    {
        $package->setReadme($this->loadREADME($dist));
    }

    private function loadREADME(Dist $dist): ?string
    {
        $tmpLocalFilename = $this->createTemporaryZipFile($dist);
        if (null === $tmpLocalFilename->getOrNull()) {
            return null;
        }

        $zip = new \ZipArchive();
        $result = $zip->open($tmpLocalFilename->get());
        if ($result !== true) {
            return null;
        }

        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $filename = (string) $zip->getNameIndex($i);
                if (preg_match('/^([^\/]+\/)?README.md$/i', $filename) === 1) {
                    return $this->markdownConverter->convertToHtml((string) $zip->getFromIndex($i));
                }
            }
        } finally {
            $zip->close();
            @unlink($tmpLocalFilename->get());
        }

        return null;
    }

    private function getTempFileName(): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('readme-loader-', true);
    }

    /**
     * @return Option<string>
     */
    private function createTemporaryZipFile(Dist $dist): Option
    {
        $tmpLocalFilename = $this->getTempFileName();
        $tmpLocalFileHandle = fopen(
            $tmpLocalFilename,
            'wb'
        );

        $distReadStream = $this->distStorage->readDistStream($dist)->getOrNull();
        if (null === $distReadStream) {
            return Option::none();
        }
        stream_copy_to_stream($distReadStream, $tmpLocalFileHandle);
        fclose($tmpLocalFileHandle);

        return Option::of($tmpLocalFilename);
    }
}
