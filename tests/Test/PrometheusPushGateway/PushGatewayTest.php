<?php

declare(strict_types=1);

namespace Test\PrometheusPushGateway;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\MetricFamilySamples;
use PrometheusPushGateway\PushGateway;

class PushGatewayTest extends TestCase
{
    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function validResponseShouldNotThrowException(): void
    {
        $mockedCollectorRegistry = $this->createMock(CollectorRegistry::class);
        $mockedCollectorRegistry->method('getMetricFamilySamples')->with()->willReturn([
            $this->createMock(MetricFamilySamples::class)
        ]);

        $mockHandler = new MockHandler([
            new Response(200),
            new Response(202),
        ]);
        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $pushGateway = new PushGateway('http://foo.bar', $client);
        $pushGateway->push($mockedCollectorRegistry, 'foo');
    }

    /**
     * @test
     *
     * @doesNotPerformAnyAssertions
     */
    public function invalidResponseShouldThrowRuntimeException(): void
    {
        self::expectException(\RuntimeException::class);

        $mockedCollectorRegistry = $this->createMock(CollectorRegistry::class);
        $mockedCollectorRegistry->method('getMetricFamilySamples')->with()->willReturn([
            $this->createMock(MetricFamilySamples::class)
        ]);

        $mockHandler = new MockHandler([
            new Response(201),
            new Response(300),
        ]);
        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $pushGateway = new PushGateway('http://foo.bar', $client);
        $pushGateway->push($mockedCollectorRegistry, 'foo');
    }

    /**
     * @test
     */
    public function clientGetsDefinedIfNotSpecified(): void
    {
        $this->expectException(\RuntimeException::class);

        $mockedCollectorRegistry = $this->createMock(CollectorRegistry::class);
        $mockedCollectorRegistry->method('getMetricFamilySamples')->with()->willReturn([
            $this->createMock(MetricFamilySamples::class)
        ]);

        $pushGateway = new PushGateway('http://foo.bar');
        $pushGateway->push($mockedCollectorRegistry, 'foo');
    }

    /**
     * @test
     *
     * @dataProvider validAddressAndRequestsProvider
     * @param string $address
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validAddressShouldCreateValidRequests(string $address, string $scheme, string $host, int $port): void
    {
        $mockedCollectorRegistry = $this->createMock(CollectorRegistry::class);
        $mockedCollectorRegistry->method('getMetricFamilySamples')->with()->willReturn([
            $this->createMock(MetricFamilySamples::class)
        ]);

        $mockHandler = new MockHandler([
            new Response(200),
        ]);
        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $pushGateway = new PushGateway($address, $client);
        $pushGateway->push($mockedCollectorRegistry, 'foo');
        if ($mockHandler->getLastRequest() !== null) {
            $uri = $mockHandler->getLastRequest()->getUri();
            self::assertEquals($scheme, $uri->getScheme());
            self::assertEquals($host, $uri->getHost());
            self::assertEquals($port, $uri->getPort());
            self::assertEquals('/metrics/job/foo', $uri->getPath());
        } else {
            self::fail("No request performed");
        }
    }

    /**
     * @return array[]
     */
    public function validAddressAndRequestsProvider(): array /** @phpstan-ignore-line */
    {
        return [
            ['foo.bar:123', 'http', 'foo.bar', 123],
            ['http://foo.bar:123', 'http', 'foo.bar', 123],
            ['https://foo.bar:123', 'https', 'foo.bar', 123],
        ];
    }
}
