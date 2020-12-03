<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Psr\Http\Message\RequestFactoryInterface;
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
     * @var ?RequestFactoryInterface
     */
    private $responseFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory = null,
        ResponseFactoryInterface $responseFactory = null
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param string $address
     * @param HttpClientInterface|array $defaultOptions
     * @param int $maxHostConnections
     * @param int $maxPendingPushes
     *
     * @return PushGateway
     */
    public function newGateway(
        string $address,
        $defaultOptions = [],
        int $maxHostConnections = 6,
        int $maxPendingPushes = 50
    ): PushGateway {
        if ($defaultOptions instanceof HttpClientInterface) {
            $client = new Psr18Client($defaultOptions, $this->responseFactory, $this->streamFactory);

            return new PsrPushGateway($address, $client, $client, $client);
        }

        $httpClient = HttpClient::create($defaultOptions, $maxHostConnections, $maxPendingPushes);
        $client = new Psr18Client($httpClient, $this->responseFactory, $this->streamFactory);

        return new PsrPushGateway($address, $client, $client, $client);
    }
}
