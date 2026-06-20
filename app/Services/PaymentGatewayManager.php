<?php

namespace App\Services;

use App\Contracts\PaymentDriverInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * The registered payment drivers.
     *
     * @var array<string, PaymentDriverInterface>
     */
    protected array $drivers = [];

    /**
     * Register a new payment driver.
     */
    public function registerDriver(PaymentDriverInterface $driver): void
    {
        $this->drivers[$driver->getCode()] = $driver;
    }

    /**
     * Check if a payment driver is registered.
     */
    public function hasDriver(string $code): bool
    {
        return isset($this->drivers[$code]);
    }

    /**
     * Get a payment driver by its code.
     */
    public function getDriver(string $code): PaymentDriverInterface
    {
        if (!$this->hasDriver($code)) {
            throw new InvalidArgumentException("Payment driver [{$code}] is not registered.");
        }

        return $this->drivers[$code];
    }

    /**
     * Get all registered drivers.
     *
     * @return array<string, PaymentDriverInterface>
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }
}
