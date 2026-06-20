<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly.");
}

function omnipay_MetaData()
{
    return [
        'DisplayName' => 'OmniPay Payment Gateway',
        'APIVersion' => '1.1.0',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

function omnipay_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'OmniPay',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Accept MFS, Web3 Crypto, and Binance Pay via the OmniPay payment gateway.',
        ],
        'apikey' => [
            'FriendlyName' => 'Store API Key',
            'Type' => 'password',
            'Size' => '64',
            'Description' => 'Enter your Store API Key.',
        ],
        'baseUrl' => [
            'FriendlyName' => 'API Base URL',
            'Type' => 'text',
            'Default' => 'http://localhost:8000',
            'Description' => 'Enter the base URL of your OmniPay server.',
        ],
    ];
}

function omnipay_link($params)
{
    $apiKey = $params['apikey'];
    $baseUrl = rtrim($params['baseUrl'], '/');
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];
    
    $parsedUrl = parse_url($params['systemurl']);
    $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'http';
    $host = $parsedUrl['host'] ?? '';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
    $systemUrl = $scheme . '://' . $host . $port;
    
    $callbackUrl = $systemUrl . '/modules/gateways/callback/omnipay.php';

    $postData = [
        'amount'         => $amount,
        'customer_name'  => $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'],
        'customer_email' => empty($params['clientdetails']['email']) ? 'customer@whmcs.com' : $params['clientdetails']['email'],
        'currency'       => $params['currency'],
        'callback_url'   => $callbackUrl,
        'cancel_url'     => $params['returnurl'],
        'meta_data'      => ['invoice_id' => $invoiceId]
    ];

    $ch = curl_init($baseUrl . '/api/v1/payment');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-API-KEY: ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    if (isset($result['payment_link'])) {
        return '<a href="' . $result['payment_link'] . '" class="btn btn-primary" style="padding: 10px 20px; background-color:#6366f1; color:#fff; border-radius:8px; text-decoration:none; font-weight:bold;">Pay with OmniPay</a>';
    } else {
        return '<div style="color:red; font-weight:bold;">Error initializing payment: ' . ($result['error'] ?? 'Check API Settings') . '</div>';
    }
}