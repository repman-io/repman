<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\BuddyApi;

use Http\Client\Exception as HttpException;

final class BuddyApiException extends \Exception implements HttpException
{
}
