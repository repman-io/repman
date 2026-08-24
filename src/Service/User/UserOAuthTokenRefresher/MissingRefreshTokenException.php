<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User\UserOAuthTokenRefresher;

/**
 * The stored token has nothing to refresh with. A state of the data rather than a
 * failure of the refresh: no amount of retrying changes it, and nothing will until the
 * user authorizes the app again.
 *
 * It has its own type because callers have to be able to tell it apart from a refresh
 * that merely went wrong, and \LogicException is too wide a net to do that with - the
 * OAuth and HTTP libraries throw \InvalidArgumentException, which is one, on malformed
 * responses and bad option arrays.
 */
final class MissingRefreshTokenException extends \LogicException
{
}
