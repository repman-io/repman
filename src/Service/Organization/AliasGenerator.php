<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

class AliasGenerator
{
    public function generate(string $name): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; [^\u001F-\u007f] remove', $name);
        $text = strtolower((string) $text);
        $text = preg_replace('~[^\\-\w]+~', ' ', $text);
        $text = preg_replace('~\s+~', '-', (string) $text);

        return trim((string) $text, '-');
    }
}
