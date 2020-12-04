<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function GuzzleHttp\Psr7\stream_for;
use function GuzzleHttp\Psr7\try_fopen;
use function in_array;
use function is_array;

final class GuzzleFactory
{
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @param StreamFactoryInterface|null $streamFactory
     * @param RequestFactoryInterface|null $requestFactory
     */
    public function __construct(
        StreamFactoryInterface $streamFactory = null,
        RequestFactoryInterface $requestFactory = null
    ) {
        $this->requestFactory = $requestFactory ?? $this->createRequestFactory();
        $this->streamFactory = $streamFactory ?? $this->createStreamFactory();
    }

    /**
     * @param string $address
     * @param GuzzleClientInterface|array $options Guzzle Client or Guzzle Client config options
     *
     * @return PushGatewayInterface
     */
    public function newGateway(string $address, $options = []): PushGatewayInterface
    {
        $client = is_array($options) ? new Client($options) : $options;

        if ($client instanceof ClientInterface) {
            return new PsrPushGateway($address, $client, $this->requestFactory, $this->streamFactory);
        }

        $psr7Client = new class ($client) implements ClientInterface {
            /**
             * @var GuzzleClientInterface
             */
            private $client;

            public function __construct(GuzzleClientInterface $client)
            {
                $this->client = $client;
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return $this->client->send($request, [
                    RequestOptions::SYNCHRONOUS => true,
                    RequestOptions::ALLOW_REDIRECTS => false,
                    RequestOptions::HTTP_ERRORS => false,
                ]);
            }
        };

        return new PsrPushGateway($address, $psr7Client, $this->requestFactory, $this->streamFactory);
    }

    private function createRequestFactory(): RequestFactoryInterface
    {
        return new class implements RequestFactoryInterface {
            public function createRequest(string $method, $uri): RequestInterface
            {
                return new Request($method, $uri);
            }
        };
    }

    private function createStreamFactory(): StreamFactoryInterface
    {
        return new class implements StreamFactoryInterface {
            public function createStream(string $content = ''): StreamInterface
            {
                return stream_for($content);
            }

            public function createStreamFromResource($resource): StreamInterface
            {
                return stream_for($resource);
            }

            public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
            {
                static $modeList = ['r', 'w', 'a', 'x', 'c'];
                try {
                    $resource = try_fopen($filename, $mode);
                } catch (RuntimeException $exception) {
                    if ('' === $mode || false === in_array($mode[0], $modeList, true)) {
                        throw new InvalidArgumentException('Invalid file opening mode "' . $mode . '"', 0, $exception);
                    }

                    throw $exception;
                }

                return stream_for($resource);
            }
        };
    }
}
