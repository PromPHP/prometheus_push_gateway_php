<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class PushGatewayException extends RuntimeException
{
    /**
     * @var ResponseInterface|null
     */
    private $response = null;

    public static function dueToServiceUnavailable(Throwable $exception): self
    {
        return new self('The request could not be send or process', 0, $exception);
    }

    public static function dueToUnexpectedStatusCode(string $address, ResponseInterface $response): self
    {
        $msg = "Unexpected status code "
            . $response->getStatusCode()
            . " received from push gateway "
            . $address . ": " . $response->getBody()->getContents();

        $exception = new self($msg);
        $exception->response = $response;

        return $exception;
    }

    /**
     * @return ResponseInterface|null
     */
    public function fetchResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
