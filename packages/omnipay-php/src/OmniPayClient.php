<?php

namespace OmniPay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OmniPay\Resources\Invoice;

class OmniPayClient
{
    private string $apiKey;
    private string $baseUrl;
    private Client $httpClient;

    public function __construct(string $apiKey, string $baseUrl = 'https://api.omnipay.com/v1')
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Create a new invoice/checkout session
     *
     * @param array $data
     * @return Invoice
     * @throws \Exception
     */
    public function createInvoice(array $data): Invoice
    {
        try {
            $response = $this->httpClient->post('invoices', [
                'json' => $data
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 201 && $response->getStatusCode() !== 200) {
                throw new \Exception($body['message'] ?? 'Failed to create invoice', $response->getStatusCode());
            }

            return new Invoice($body['data'] ?? $body);
        } catch (GuzzleException $e) {
            throw new \Exception('Network error: ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Get an existing invoice by ID
     *
     * @param string $invoiceId
     * @return Invoice
     * @throws \Exception
     */
    public function getInvoice(string $invoiceId): Invoice
    {
        try {
            $response = $this->httpClient->get("invoices/{$invoiceId}");
            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception($body['message'] ?? 'Invoice not found', $response->getStatusCode());
            }

            return new Invoice($body['data'] ?? $body);
        } catch (GuzzleException $e) {
            throw new \Exception('Network error: ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Verify a webhook signature
     *
     * @param string $payload Raw request body
     * @param string $signature Signature from X-OmniPay-Signature header
     * @param string $webhookSecret The store's webhook secret key
     * @return bool
     */
    public function verifyWebhook(string $payload, string $signature, string $webhookSecret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }
}
