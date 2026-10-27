<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\Dist\Storage;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

final class ReadmeExtractor
{
    private Storage $distStorage;
    private MarkdownConverter $markdownConverter;

    public function __construct(Storage $distStorage)
    {
        $this->distStorage = $distStorage;

        // README comes from the package author and is rendered unescaped on the package page,
        // so raw HTML must never reach the output.
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'external_link' => [
                'open_in_new_window' => true,
                'nofollow' => '',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
            'heading_permalink' => [
                'id_prefix' => 'user-content',
                'fragment_prefix' => '',
                'symbol' => '',
                'html_class' => '',
                'title' => '',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new HeadingPermalinkExtension());

        $this->markdownConverter = new MarkdownConverter($environment);
    }

    public function extractReadme(Package $package, Dist $dist): void
    {
        $package->setReadme($this->loadREADME($dist));
    }

    private function loadREADME(Dist $dist): ?string
    {
        $tmpLocalFilename = $this->distStorage->getLocalFileForDist($dist);
        if (null === $tmpLocalFilename->getOrNull()) {
            return null;
        }

        $zip = new \ZipArchive();
        $result = $zip->open($tmpLocalFilename->get());
        if ($result !== true) {
            @unlink($tmpLocalFilename->get());

            return null;
        }

        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $filename = (string) $zip->getNameIndex($i);
                if (preg_match('/^([^\/]+\/)?README.md$/i', $filename) === 1) {
                    return $this->render((string) $zip->getFromIndex($i));
                }
            }
        } finally {
            $zip->close();
            @unlink($tmpLocalFilename->get());
        }

        return null;
    }

    /**
     * CommonMark only takes UTF-8; a README in another encoding must not fail the whole package sync.
     */
    private function render(string $markdown): ?string
    {
        if (str_starts_with($markdown, "\xFF\xFE") || str_starts_with($markdown, "\xFE\xFF")) {
            $markdown = mb_convert_encoding($markdown, 'UTF-8', 'UTF-16');
        } elseif (str_starts_with($markdown, "\xEF\xBB\xBF")) {
            $markdown = substr($markdown, 3);
        }

        if (!mb_check_encoding($markdown, 'UTF-8')) {
            $markdown = mb_convert_encoding($markdown, 'UTF-8', 'Windows-1252');
        }

        try {
            return $this->markdownConverter->convert($markdown)->getContent();
        } catch (CommonMarkException $exception) {
            return null;
        }
    }
}
