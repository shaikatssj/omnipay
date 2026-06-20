<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$gatewayModuleName = 'omnipay';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$rawBody = file_get_contents("php://input");
$data = json_decode($rawBody, true);

if (empty($rawBody) || empty($data)) {
    die("Empty payload");
}

// Verify signature
$headers = getallheaders();
$receivedSignature = $headers['X-OMNIPAY-SIGNATURE'] ?? $headers['x-omnipay-signature'] ?? '';
$expectedSignature = hash_hmac('sha256', $rawBody, $gatewayParams['apikey']);

if (empty($receivedSignature) || !hash_equals($expectedSignature, $receivedSignature)) {
    header("HTTP/1.1 401 Unauthorized");
    die("Invalid Signature");
}

$whmcsInvoiceId = $data['meta_data']['invoice_id'] ?? null;
$status = strtolower($data['status'] ?? '');
$amount = $data['amount'] ?? 0;
$txId = $data['invoice_id'] ?? '';

if ($whmcsInvoiceId && $status === 'paid') {
    addInvoicePayment(
        $whmcsInvoiceId,
        $txId,
        $amount,
        0.00,
        $gatewayModuleName
    );
    logTransaction($gatewayModuleName, $data, 'Success');
    echo "Success";
} else {
    echo "Ignored";
}