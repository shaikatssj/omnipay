<?php

namespace OmniPay\Resources;

class Invoice
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getId(): ?string
    {
        return $this->data['invoice_id'] ?? null;
    }

    public function getAmount(): ?float
    {
        return $this->data['amount'] ?? null;
    }

    public function getExpectedAmount(): ?float
    {
        return $this->data['expected_amount'] ?? null;
    }

    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? null;
    }

    public function getStatus(): ?string
    {
        return $this->data['status'] ?? null;
    }

    public function getPaymentLink(): ?string
    {
        return $this->data['payment_link'] ?? null;
    }

    public function isPaid(): bool
    {
        return $this->getStatus() === 'paid';
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }
}
