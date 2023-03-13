<?php

namespace Test;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use PrometheusPushGateway\PushGateway;

class BlackBoxPushGatewayTest extends TestCase
{
    /**
     * @test
     */
    public function pushGatewayShouldWork()
    {
        $adapter = new InMemory();
        $registry = new CollectorRegistry($adapter);

        $counter = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
        $counter->incBy(6, ['blue']);

        $pushGateway = new PushGateway('pushgateway:9091');
        $pushGateway->push($registry, 'my_job', ['instance' => 'foo']);

        $httpClient = new Client();
        $metrics = $httpClient->get("http://pushgateway:9091/metrics")->getBody()->getContents();
        self::assertContains(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );

        $pushGateway = new PushGateway('http://pushgateway:9091');
        $pushGateway->delete('my_job', ['instance' => 'foo']);

        $httpClient = new Client();
        $metrics = $httpClient->get("http://pushgateway:9091/metrics")->getBody()->getContents();
        self::assertNotContains(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );
    }
}
