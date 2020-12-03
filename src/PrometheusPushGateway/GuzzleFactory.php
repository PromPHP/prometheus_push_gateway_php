<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

use function class_exists;
use function GuzzleHttp\Psr7\stream_for;
use function GuzzleHttp\Psr7\try_fopen;
use function sprintf;

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
     * @param array $options Guzzle Client config options
     *
     * @return PushGateway
     */
    public function newGateway(string $address, array $options = []): PushGateway
    {
        return new PsrPushGateway(
            $address,
            new Client($options),
            $this->requestFactory,
            $this->streamFactory
        );
    }

    private function createRequestFactory(): RequestFactoryInterface
    {
        if (class_exists(HttpFactory::class)) {
            return new HttpFactory();
        }

        return new class implements RequestFactoryInterface {
            public function createRequest(string $method, $uri): RequestInterface
            {
                return new Request($method, $uri);
            }
        };
    }

    private function createStreamFactory(): StreamFactoryInterface
    {
        if (class_exists(HttpFactory::class)) {
            return new HttpFactory();
        }

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
                try {
                    $resource = try_fopen($filename, $mode);
                } catch (\RuntimeException $exception) {
                    if ('' === $mode || false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
                        throw new \InvalidArgumentException(sprintf('Invalid file opening mode "%s"', $mode), 0, $exception);
                    }

                    throw $exception;
                }

                return stream_for($resource);
            }
        };
    }
}
