<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Validator;

use Buddy\Repman\Validator\Recaptcha;
use Buddy\Repman\Validator\RecaptchaValidator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<RecaptchaValidator>
 */
final class RecaptchaValidatorTest extends ConstraintValidatorTestCase
{
    private RequestStack $requestStack;
    private MockHttpClient $client;
    private bool $enabled = true;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->client = new MockHttpClient();
        parent::setUp();
    }

    protected function createValidator(): RecaptchaValidator
    {
        return new RecaptchaValidator($this->enabled, 'secret', $this->requestStack, $this->client);
    }

    public function testPassesWhenDisabled(): void
    {
        $this->enabled = false;
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate(null, new Recaptcha());

        $this->assertNoViolation();
    }

    public function testFailsWithoutToken(): void
    {
        $this->requestStack->push(new Request());

        $this->validator->validate(null, new Recaptcha());

        $this->buildViolation('Please confirm you are not a robot.')->assertRaised();
    }

    public function testPassesWhenGoogleConfirms(): void
    {
        $sent = [];
        $this->client->setResponseFactory(function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = [$method, $url, $options['body']];

            return new MockResponse('{"success":true}');
        });
        $this->requestStack->push(new Request([], ['g-recaptcha-response' => 'token'], [], [], [], ['REMOTE_ADDR' => '10.0.0.1']));

        $this->validator->validate(null, new Recaptcha());

        $this->assertNoViolation();
        self::assertSame(['POST', 'https://www.google.com/recaptcha/api/siteverify', 'secret=secret&response=token&remoteip=10.0.0.1'], $sent);
    }

    public function testFailsWhenGoogleRejects(): void
    {
        $this->client->setResponseFactory(new MockResponse('{"success":false,"error-codes":["invalid-input-response"]}'));
        $this->requestStack->push(new Request([], ['g-recaptcha-response' => 'forged']));

        $this->validator->validate(null, new Recaptcha());

        $this->buildViolation('Please confirm you are not a robot.')->assertRaised();
    }

    public function testFailsWhenGoogleIsUnreachable(): void
    {
        $this->client->setResponseFactory(new MockResponse('', ['error' => 'Could not resolve host']));
        $this->requestStack->push(new Request([], ['g-recaptcha-response' => 'token']));

        $this->validator->validate(null, new Recaptcha());

        $this->buildViolation('The captcha could not be verified, please try again.')->assertRaised();
    }
}
