<?php

declare(strict_types=1);

namespace PrometheusPushGateway;

interface FactoryInterface
{
    /**
     * @param string $address
     *
     * @return PushGatewayInterface
     */
    public function newGateway(string $address): PushGatewayInterface;
}
