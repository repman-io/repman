<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

final class HttpClientStub implements ClientInterface
{
    /**
     * @var ResponseInterface[];
     */
    private array $nextResponses = [];

    /**
     * @param ResponseInterface[] $nextResponses
     */
    public function setNextResponses(array $nextResponses): void
    {
        $this->nextResponses = $nextResponses;
    }

    /**
     * @param mixed[] $options
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->getNextResponse();
    }

    /**
     * @param mixed[] $options
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->getNextResponse());
    }

    /**
     * @param string              $method
     * @param string|UriInterface $uri
     * @param mixed[]             $options
     */
    public function request($method, $uri, array $options = []): ResponseInterface
    {
        return $this->getNextResponse();
    }

    /**
     * @param string              $method
     * @param string|UriInterface $uri
     * @param mixed[]             $options
     */
    public function requestAsync($method, $uri, array $options = []): PromiseInterface
    {
        return new FulfilledPromise($this->getNextResponse());
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

    private function getNextResponse(): ResponseInterface
    {
        return array_shift($this->nextResponses) ?? new Response(200, [], 'Sample response');
    }
}
