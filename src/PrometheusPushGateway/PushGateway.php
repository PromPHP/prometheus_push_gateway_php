<?php

namespace PrometheusPushGateway;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use RuntimeException;

class PushGateway
{
    const HTTP_PUT = 'PUT';
    const HTTP_POST = 'POST';
    const HTTP_DELETE = 'DELETE';

    /**
     * Prometheus Push Gateway base URL
     * @var string
     */
    private $gatewayUrl;

    /**
     * HTTP Client
     * @var \GuzzleHttp\Client
     */
    private $client;

    public function __construct($gatewayUrl, \GuzzleHttp\Client $client = null)
    {
        $this->gatewayUrl = $gatewayUrl;
        $this->client = $client ?: new \GuzzleHttp\Client();
    }

    public function push(CollectorRegistry $collectorRegistry, $job, array $groupingKey = [])
    {
        $this->doRequest($job, $groupingKey, self::HTTP_PUT, $collectorRegistry);
    }

    public function pushAdd(CollectorRegistry $collectorRegistry, $job, array $groupingKey = [])
    {
        $this->doRequest($job, $groupingKey, self::HTTP_POST, $collectorRegistry);
    }

    public function delete($job, array $groupingKey = [])
    {
        $this->doRequest($job, $groupingKey, self::HTTP_DELETE);
    }

    protected function doRequest($action, array $groupingKey, $method, CollectorRegistry $collectorRegistry = null)
    {
        $url = $this->gatewayUrl . "/metrics/job/" . $action;
        if (!empty($groupingKey)) {
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
                . $this->gatewayUrl . ": " . $response->getBody();
            throw new RuntimeException($msg);
        }
    }
}