<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpClientStub implements ClientInterface
{
    private ResponseInterface $nextResponse;

    public function __construct()
    {
        $this->nextResponse = new Response(200, [], 'Sample response');
    }

    public function setNextResponse(ResponseInterface $nextResponse): void
    {
        $this->nextResponse = $nextResponse;
    }

    /**
     * @param mixed[] $options
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->nextResponse;
    }

    /**
     * @param mixed[] $options
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->nextResponse);
    }

    /**
     * @param string  $method
     * @param string  $uri
     * @param mixed[] $options
     */
    public function request($method, $uri, array $options = []): ResponseInterface
    {
        return $this->nextResponse;
    }

    /**
     * @param string  $method
     * @param string  $uri
     * @param mixed[] $options
     */
    public function requestAsync($method, $uri, array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->nextResponse);
    }

    /**
     * @param string|null $option
     *
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return [];
    }
}
