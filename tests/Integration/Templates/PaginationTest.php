<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\Templates;

use Buddy\Repman\Query\Filter;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Environment;

final class PaginationTest extends IntegrationTestCase
{
    /**
     * @param string[] $expectedLinks
     */
    #[DataProvider('pages')]
    public function testRendersOnlyExistingPagesAroundTheCurrentOne(int $count, int $offset, array $expectedLinks): void
    {
        $html = $this->container()->get(Environment::class)->render('component/pagination.html.twig', [
            'count' => $count,
            'filter' => new Filter($offset, 20),
            'path_name' => 'admin_user_list',
        ]);

        preg_match_all('{<(?:a|span) class="page-link"[^>]*>([^<]+)</(?:a|span)>}', $html, $matches);
        self::assertSame($expectedLinks, $matches[1]);
    }

    /**
     * @return iterable<string, array{int, int, string[]}>
     */
    public static function pages(): iterable
    {
        yield 'single page' => [20, 0, []];
        yield 'exact multiple of the limit' => [40, 0, ['1', '2']];
        yield 'one more than a multiple' => [41, 20, ['1', '2', '3']];
        yield 'many pages, at the start' => [400, 0, ['1', '2', '3', '&hellip;', '20']];
        yield 'many pages, in the middle' => [400, 200, ['1', '&hellip;', '9', '10', '11', '12', '13', '&hellip;', '20']];
    }
}
