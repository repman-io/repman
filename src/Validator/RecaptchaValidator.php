<?php

declare(strict_types=1);

namespace Buddy\Repman\Validator;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RecaptchaValidator extends ConstraintValidator
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private bool $enabled,
        private string $secret,
        private RequestStack $requestStack,
        private HttpClientInterface $client,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Recaptcha) {
            throw new UnexpectedTypeException($constraint, Recaptcha::class);
        }

        if (!$this->enabled) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $token = $request?->request->get('g-recaptcha-response');
        if (!\is_string($token) || $token === '') {
            $this->context->buildViolation($constraint->message)->addViolation();

            return;
        }

        try {
            $result = $this->client->request('POST', self::VERIFY_URL, [
                'body' => ['secret' => $this->secret, 'response' => $token, 'remoteip' => $request->getClientIp()],
                'timeout' => 10,
            ])->toArray();
        } catch (ExceptionInterface $exception) {
            $this->context->buildViolation($constraint->unavailableMessage)->addViolation();

            return;
        }

        if (($result['success'] ?? false) !== true) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
