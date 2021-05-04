<?php

declare(strict_types=1);

namespace Test;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use PrometheusPushGateway\GuzzleFactory;
use PrometheusPushGateway\PsrFactory;
use PrometheusPushGateway\FactoryInterface;
use PrometheusPushGateway\PushGatewayInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

class BlackBoxPushGatewayTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider pushGatewayProvider
     *
     * @param FactoryInterface $factory
     */
    public function pushGatewayShouldWork($factory): void
    {
        $adapter = new InMemory();
        $registry = new CollectorRegistry($adapter);

        $counter = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
        $counter->incBy(6, ['blue']);

        $pushGateway = $factory->newGateway('pushgateway:9091');
        $pushGateway->push($registry, 'my_job', ['instance' => 'foo']);

        $httpClient = new Client();
        $metrics = $httpClient->get("http://pushgateway:9091/metrics")->getBody()->getContents();
        self::assertStringContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );

        $pushGateway = $factory->newGateway('http://pushgateway:9091');
        $pushGateway->delete('my_job', ['instance' => 'foo']);

        $httpClient = new Client();
        $metrics = $httpClient->get("http://pushgateway:9091/metrics")->getBody()->getContents();
        self::assertStringNotContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );
    }

    /**
     * @return iterable<string,array<FactoryInterface>>
     */
    public function pushGatewayProvider(): iterable
    {
        yield 'guzzle'  => [new GuzzleFactory()];

        $symfonyFactory = new class () implements FactoryInterface {
            /** @var PsrFactory */
            private $factory;

            public function __construct()
            {
                $client = new Psr18Client(HttpClient::create());
                $this->factory = new PsrFactory($client, $client, $client);
            }

            public function newGateway(string $address): PushGatewayInterface
            {
                return $this->factory->newGateway($address);
            }
        };

        yield 'symfony' => [$symfonyFactory];
    }
}
