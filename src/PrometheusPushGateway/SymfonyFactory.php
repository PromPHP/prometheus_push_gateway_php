<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class SymfonyFactory
{
    /**
     * @var ?StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var ?ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var Psr18Client
     */
    private $client;

    /**
     * SymfonyFactory constructor.
     * @param HttpClientInterface|array $defaultOptions
     * @param int $maxHostConnections
     * @param int $maxPendingPushes
     * @param StreamFactoryInterface|null $streamFactory
     * @param ResponseFactoryInterface|null $responseFactory
     */
    public function __construct(
        $defaultOptions = [],
        int $maxHostConnections = 6,
        int $maxPendingPushes = 50,
        StreamFactoryInterface $streamFactory = null,
        ResponseFactoryInterface $responseFactory = null
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->client = $this->createClient($defaultOptions, $maxHostConnections, $maxPendingPushes);
    }

    /**
     * @param HttpClientInterface|array $defaultOptions
     * @param int $maxHostConnections
     * @param int $maxPendingPushes
     *
     * @return Psr18Client
     */
    private function createClient($defaultOptions, int $maxHostConnections, int $maxPendingPushes): Psr18Client
    {
        if ($defaultOptions instanceof HttpClientInterface) {
            return new Psr18Client($defaultOptions, $this->responseFactory, $this->streamFactory);
        }

        $httpClient = HttpClient::create($defaultOptions, $maxHostConnections, $maxPendingPushes);

        return new Psr18Client($httpClient, $this->responseFactory, $this->streamFactory);
    }

    /**
     * @param string $address
     *
     * @return PushGatewayInterface
     */
    public function newGateway(string $address): PushGatewayInterface
    {
        return new PsrPushGateway($address, $this->client, $this->client, $this->client);
    }
}
