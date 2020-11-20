# A prometheus push gateway client library written in PHP

![Tests](https://github.com/promphp/prometheus_push_gateway_php/workflows/Tests/badge.svg)

This package provides an easy PHP API for Prometheus Push Gateway. It was part of [promphp/prometheus_client_php](https://github.com/promphp/prometheus_client_php) and was moved into a seperate package as of Prometheus Client PHP Version 2.0.0. 

## How does it work?

The PushGateway allows Prometheus to get Metrics from Systems that are not scrableable (Your Prometheus cannot access that systems). With this library you can easily send your metrics to the PushGateway. 
## Installation

Add as [Composer](https://getcomposer.org/) dependency:

```sh
composer require promphp/prometheus_push_gateway_php
```

## Usage

Let's assume you have that simple counter and want to send it to your PushGateway. 
```php
\Prometheus\CollectorRegistry::getDefault()
    ->getOrRegisterCounter('', 'some_quick_counter', 'just a quick measurement')
    ->inc();

// Now send it to the PushGateway:
$pushGateway = new \PrometheusPushGateway\PushGateway('http://192.168.59.100:9091');
$pushGateway->push(\Prometheus\CollectorRegistry::getDefault(), 'my_job', ['instance' => 'foo']);
```

Also look at the [examples](examples).

## Development

### Dependencies

* PHP ^7.2
* [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

## Black box testing

Just start the PushGateway by using docker-compose
```
docker-compose up
```

Use composer to grab all dependencies
```
docker run --rm --interactive --tty --volume $PWD:/app composer install
```

Execute the tests:
```
docker-compose run phpunit vendor/bin/phpunit tests/Test/
```
