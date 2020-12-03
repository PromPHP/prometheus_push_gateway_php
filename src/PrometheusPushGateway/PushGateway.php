<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Prometheus\CollectorRegistry;

interface PushGateway
{
    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     * @param CollectorRegistry $collectorRegistry
     * @param string $job
     * @param array<string> $groupingKey
     *
     * @throws PushGatewayException
     */
    public function push(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void;

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     * @param CollectorRegistry $collectorRegistry
     * @param string $job
     * @param array<string> $groupingKey
     *
     * @throws PushGatewayException
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void;

    /**
     * Deletes metrics from the Push Gateway.
     * Uses HTTP POST.
     * @param string $job
     * @param array<string> $groupingKey
     *
     * @throws PushGatewayException
     */
    public function delete(string $job, array $groupingKey = []): void;
}
