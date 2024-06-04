<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use RuntimeException;

class PushGateway
{
    public const HTTP_PUT = "PUT";
    public const HTTP_POST = "POST";
    public const HTTP_DELETE = "DELETE";
    /**
     * @var string
     */
    private $address;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * PushGateway constructor.
     * @param string $address (http|https)://host:port of the push gateway
     * @param ClientInterface|null $client
     */
    public function __construct(string $address, ?ClientInterface $client = null)
    {
        $this->address = strpos($address, 'http') === false ? 'http://' . $address : $address;
        $this->client = $client ?? new Client(['connect_timeout' => 10, 'timeout' => 20]);
    }

    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     * @param CollectorRegistry $collectorRegistry
     * @param string $job
     * @param array<string,string> $groupingKey
     * @throws GuzzleException
     */
    public function push(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, self::HTTP_PUT);
    }

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     * @param CollectorRegistry $collectorRegistry
     * @param string $job
     * @param array<string,string> $groupingKey
     * @throws GuzzleException
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, self::HTTP_POST);
    }

    /**
     * Deletes metrics from the Push Gateway.
     * Uses HTTP POST.
     * @param string $job
     * @param array<string,string> $groupingKey
     * @throws GuzzleException
     */
    public function delete(string $job, array $groupingKey = []): void
    {
        $this->doRequest(null, $job, $groupingKey, self::HTTP_DELETE);
    }

    /**
     * @param CollectorRegistry|null $collectorRegistry
     * @param string $job
     * @param array<string,string> $groupingKey
     * @param string $method
     * @throws GuzzleException
     */
    private function doRequest(?CollectorRegistry $collectorRegistry, string $job, array $groupingKey, string $method): void
    {
        $url = $this->address . "/metrics/job/" . $job;
        if ($groupingKey !== []) {
            foreach ($groupingKey as $label => $value) {
                $url .= "/" . $label . "/" . $value;
            }
        }

        $requestOptions = [
            'headers' => [
                'Content-Type' => RenderTextFormat::MIME_TYPE,
            ],
        ];

        if ($method !== self::HTTP_DELETE && $collectorRegistry !== null) {
            $renderer = new RenderTextFormat();
            $requestOptions['body'] = $renderer->render($collectorRegistry->getMetricFamilySamples());
        }
        $response = $this->client->request($method, $url, $requestOptions);
        $statusCode = $response->getStatusCode();
        if (!in_array($statusCode, [200, 202], true)) {
            $msg = "Unexpected status code "
                . $statusCode
                . " received from push gateway "
                . $this->address . ": " . $response->getBody();
            throw new RuntimeException($msg);
        }
    }
}
