<?php

declare(strict_types=1);

namespace Test\PrometheusPushGateway;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\MetricFamilySamples;
use PrometheusPushGateway\GuzzleFactory;

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

        $gatewayFactory = new GuzzleFactory(['handler' => $handler]);
        $pushGateway = $gatewayFactory->newGateway('http://foo.bar');
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

        $gatewayFactory = new GuzzleFactory(['handler' => $handler]);
        $pushGateway = $gatewayFactory->newGateway('http://foo.bar');
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

        $gatewayFactory = new GuzzleFactory();
        $pushGateway = $gatewayFactory->newGateway('http://foo.bar');
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

        $gatewayFactory = new GuzzleFactory(['handler' => $handler]);
        $pushGateway = $gatewayFactory->newGateway($address);
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
    public function validAddressAndRequestsProvider(): array
    {
        return [
            ['foo.bar:123', 'http', 'foo.bar', 123],
            ['http://foo.bar:123', 'http', 'foo.bar', 123],
            ['https://foo.bar:123', 'https', 'foo.bar', 123],
        ];
    }
}
