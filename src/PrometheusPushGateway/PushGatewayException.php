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
    private $response;

    private function __construct(
        string $message = "",
        Throwable $previous = null,
        ResponseInterface $response = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->response = $response;
    }

    public static function dueToServiceUnavailable(Throwable $exception): self
    {
        return new self('The request could not be send or process', $exception);
    }

    public static function dueToUnexpectedStatusCode(string $address, ResponseInterface $response): self
    {
        $msg = "Unexpected status code "
            . $response->getStatusCode()
            . " received from push gateway "
            . $address . ": " . $response->getBody()->getContents();

        return new self($msg, null, $response);
    }

    /**
     * @return ResponseInterface|null
     */
    public function fetchResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
