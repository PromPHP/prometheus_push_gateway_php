<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Prometheus\CollectorRegistry;

final class PushGateway implements PushGatewayInterface
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
        $this->decorator = (new GuzzleFactory())->newGateway(
            $address,
            $client ?? [RequestOptions::TIMEOUT => 10, RequestOptions::CONNECT_TIMEOUT => 2]
        );
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
