<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverterInterface;

final class Markdown
{
    private MarkdownConverterInterface $converter;

    public function __construct()
    {
        $environment = Environment::createGFMEnvironment();
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new HeadingPermalinkExtension());

        $this->converter = new CommonMarkConverter(
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

    public function convertToHTML(string $markdown): string
    {
        return $this->converter->convertToHtml($markdown);
    }
}
