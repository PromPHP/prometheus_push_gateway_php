<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

use function in_array;

final class PsrPushGateway implements PushGateway
{
    private const HTTP_PUT = "PUT";
    private const HTTP_POST = "POST";
    private const HTTP_DELETE = "DELETE";

    /**
     * @var string
     */
    private $address;

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

    /**
     * @param string $address (http|https)://host:port of the push gateway
     * @param ClientInterface $client
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        string $address,
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->address = strpos($address, 'http') === false ? 'http://' . $address : $address;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function push(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void
    {
        $this->doRequest(self::HTTP_PUT, $job, $groupingKey, $collectorRegistry);
    }

    /**
     * {@inheritDoc}
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void
    {
        $this->doRequest(self::HTTP_POST, $job, $groupingKey, $collectorRegistry);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $job, array $groupingKey = []): void
    {
        $this->doRequest(self::HTTP_DELETE, $job, $groupingKey);
    }

    /**
     * @param string $method
     * @param string $job
     * @param array<string,string> $groupingKey
     * @param CollectorRegistry|null $collectorRegistry
     *
     * @throws RuntimeException
     */
    private function doRequest(
        string $method,
        string $job,
        array $groupingKey,
        ?CollectorRegistry $collectorRegistry = null
    ): void {
        $url = $this->setUrl($job, $groupingKey);
        $request = $this->createRequest($method, $url, $collectorRegistry);
        try {
            $response = $this->client->sendRequest($request);
            $statusCode = $response->getStatusCode();
            if (!in_array($statusCode, [200, 202], true)) {
                $msg = "Unexpected status code "
                    . $statusCode
                    . " received from push gateway "
                    . $this->address . ": " . $response->getBody()->getContents();
                throw new RuntimeException($msg);
            }
        } catch (ClientExceptionInterface $exception) {
            throw new RuntimeException('The request could not be send or process', 0, $exception);
        }
    }

    /**
     * @param string $job
     * @param array<string,string> $groupingKey
     *
     * @return string
     */
    private function setUrl(string $job, array $groupingKey): string
    {
        $url = $this->address . "/metrics/job/" . $job;

        foreach ($groupingKey as $label => $value) {
            $url .= "/" . $label . "/" . $value;
        }

        return $url;
    }

    /**
     * @param string $method
     * @param string $url
     * @param CollectorRegistry|null $collectorRegistry
     *
     * @return RequestInterface
     */
    private function createRequest(string $method, string $url, ?CollectorRegistry $collectorRegistry): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Content-Type', RenderTextFormat::MIME_TYPE);

        if (self::HTTP_DELETE === $request->getMethod()) {
            return $request;
        }

        if (null === $collectorRegistry) {
            return $request;
        }

        $renderer = new RenderTextFormat();

        return $request->withBody(
            $this->streamFactory->createStream($renderer->render($collectorRegistry->getMetricFamilySamples()))
        );
    }
}
