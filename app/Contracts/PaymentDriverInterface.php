<?php

namespace App\Contracts;

use App\Models\Invoice;

interface PaymentDriverInterface
{
    /**
     * Get the code representation of the driver (e.g. bkash, binance).
     */
    public function getCode(): string;

    /**
     * Get the descriptive name of the driver.
     */
    public function getName(): string;

    /**
     * Initiate payment processing (returns view parameters or redirect data).
     *
     * @param Invoice $invoice
     * @param array $settings Store-specific gateway settings
     * @return array
     */
    public function initiatePayment(Invoice $invoice, array $settings): array;

    /**
     * Verify payment status.
     *
     * @param Invoice $invoice
     * @param array $settings Store-specific gateway settings
     * @param array $requestData Request data submitted by checkout or webhooks
     * @return array
     */
    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array;

    /**
     * Process refund for a paid invoice.
     *
     * @param Invoice $invoice
     * @param array $settings Store-specific gateway settings
     * @param array $refundData Refund details (amount, SKU, reason)
     * @return array
     */
    public function refund(Invoice $invoice, array $settings, array $refundData): array;
}
