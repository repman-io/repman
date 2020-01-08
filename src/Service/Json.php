<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

final class Json
{
    /**
     * @return array<mixed>
     */
    public static function decode(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $data = [];
        }

        return $data;
    }
}
