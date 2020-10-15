<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\MarkdownConverterInterface;

final class Markdown
{
    private MarkdownConverterInterface $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter(
            [
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ]
        );
    }

    public function convertToHTML(string $markdown): string
    {
        return $this->converter->convertToHtml($markdown);
    }
}
