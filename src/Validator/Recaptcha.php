<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class Recaptcha extends Constraint
{
    public string $message = 'Please confirm you are not a robot.';
    public string $unavailableMessage = 'The captcha could not be verified, please try again.';
}
