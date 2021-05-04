<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function in_array;

final class PsrPushGateway implements PushGatewayInterface
{
    private const METHOD_PUT = "PUT";
    private const METHOD_POST = "POST";
    private const METHOD_DELETE = "DELETE";
    private const STATUS_OK = 200;
    private const STATUS_ACCEPTED = 202;

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
        $this->doRequest(self::METHOD_PUT, $job, $groupingKey, $collectorRegistry);
    }

    /**
     * {@inheritDoc}
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = []): void
    {
        $this->doRequest(self::METHOD_POST, $job, $groupingKey, $collectorRegistry);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $job, array $groupingKey = []): void
    {
        $this->doRequest(self::METHOD_DELETE, $job, $groupingKey);
    }

    /**
     * @param string $method
     * @param string $job
     * @param array<string,string> $groupingKey
     * @param CollectorRegistry|null $collectorRegistry
     *
     * @throws PushGatewayException
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
            if (!in_array($statusCode, [self::STATUS_OK, self::STATUS_ACCEPTED], true)) {
                throw PushGatewayException::dueToUnexpectedStatusCode($this->address, $response);
            }
        } catch (ClientExceptionInterface $exception) {
            throw PushGatewayException::dueToServiceUnavailable($exception);
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

        if (self::METHOD_DELETE === $request->getMethod()) {
            return $request;
        }

        if (null === $collectorRegistry) {
            return $request;
        }

        return $request->withBody(
            $this->streamFactory->createStream(
                (new RenderTextFormat())->render($collectorRegistry->getMetricFamilySamples())
            )
        );
    }
}
