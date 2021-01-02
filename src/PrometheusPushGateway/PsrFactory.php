<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PsrFactory implements FactoryInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param string $address
     *
     * @return PushGatewayInterface
     */
    public function newGateway(string $address): PushGatewayInterface
    {
        return new PsrPushGateway($address, $this->client, $this->requestFactory, $this->streamFactory);
    }
}
