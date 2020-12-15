<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class SymfonyFactory implements PsrFactoryInterface
{
    /**
     * @var PsrFactory
     */
    private $factory;

    /**
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
        if (!$defaultOptions instanceof HttpClientInterface) {
            $defaultOptions = HttpClient::create($defaultOptions, $maxHostConnections, $maxPendingPushes);
        }

        $client = new Psr18Client($defaultOptions, $responseFactory, $streamFactory);
        $this->factory = new PsrFactory($client, $client, $client);
    }

    /**
     * @param string $address
     *
     * @return PushGatewayInterface
     */
    public function newGateway(string $address): PushGatewayInterface
    {
        return $this->factory->newGateway($address);
    }
}
