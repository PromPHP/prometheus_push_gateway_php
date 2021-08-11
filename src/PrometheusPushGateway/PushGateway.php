<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\HttpFactory;
use Prometheus\CollectorRegistry;

class PushGateway implements PushGatewayInterface
{
    /**
     * @var PushGatewayInterface
     */
    private $decorator;

    /**
     * @param string $address (http|https)://host:port of the push gateway
     * @param ClientInterface|null $client
     */
    public function __construct(string $address, ?ClientInterface $client = null)
    {
        $client = $client ?? [RequestOptions::TIMEOUT => 10, RequestOptions::CONNECT_TIMEOUT => 2];

        $this->decorator = $this->setFactory($client)->newGateway($address);
    }

    private function setFactory(ClientInterface $client): GuzzleFactory
    {
        if (class_exists(HttpFactory::class)) {
            $psrFactory = new HttpFactory();

            return new GuzzleFactory($client, $psrFactory, $psrFactory);
        }

        return new GuzzleFactory($client);
    }

    /**
     * {@inheritDoc}
     */
    public function push(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void
    {
        $this->decorator->push($collectorRegistry, $job, $groupingKey);
    }

    /**
     * {@inheritDoc}
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void
    {
        $this->decorator->pushAdd($collectorRegistry, $job, $groupingKey);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $job, array $groupingKey = []): void
    {
        $this->decorator->delete($job, $groupingKey);
    }
}
