<?php

namespace App\Plugins;

use App\Contracts\PaymentDriverInterface;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class Web3Driver implements PaymentDriverInterface
{
    public function getCode(): string
    {
        return 'web3';
    }

    public function getName(): string
    {
        return 'Web3 Crypto Wallet';
    }

    protected $defaultContracts = [
        'bsc' => '0x55d398326f99059ff775485246999027b3197955', // BEP20 USDT (18 decimals)
        'eth' => '0xdac17f958d2ee523a2206206994597c13d831ec7', // ERC20 USDT (6 decimals)
        'arb' => '0xfd086bc7cd5c481dcc9c85ebe478a1c0b69fcbb9', // ERC20 USDT (6 decimals)
        'opmain' => '0x94b008aa00579c1307b0ef2c499ad98a8ce58e58', // ERC20 USDT (6 decimals)
        'tron' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', // TRC20 USDT (6 decimals)
    ];

    protected $apiEndpoints = [
        'bsc' => 'https://api.bscscan.com/api',
        'eth' => 'https://api.etherscan.io/api',
        'arb' => 'https://api.arbiscan.io/api',
        'opmain' => 'https://api-optimistic.etherscan.io/api',
    ];

    public function initiatePayment(Invoice $invoice, array $settings): array
    {
        return [
            'instructions' => 'Send USDT on your selected network to the address shown. We will verify the transaction on the block explorer.',
            'coin' => 'USDT',
            'amount' => $invoice->expected_amount,
            'wallets' => [
                'bsc' => $settings['bsc_wallet'] ?? '',
                'eth' => $settings['eth_wallet'] ?? '',
                'tron' => $settings['tron_wallet'] ?? '',
                'arb' => $settings['arb_wallet'] ?? '',
                'opmain' => $settings['opmain_wallet'] ?? '',
            ]
        ];
    }

    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array
    {
        $network = strtolower($requestData['network'] ?? '');
        if (!in_array($network, ['bsc', 'eth', 'arb', 'opmain', 'tron'])) {
            return ['status' => 'error', 'message' => 'Invalid blockchain network specified'];
        }

        $walletField = $network . '_wallet';
        $recipientWallet = trim($settings[$walletField] ?? '');

        if (empty($recipientWallet)) {
            return ['status' => 'error', 'message' => "Recipient wallet address for {$network} is not configured."];
        }

        // Standardized decimals
        $decimals = 6;
        if ($network === 'bsc') {
            $decimals = 18; // BSC USDT contract uses 18 decimals
        }

        if ($network === 'tron') {
            return $this->verifyTronTransaction($invoice, $recipientWallet, $invoice->expected_amount);
        }

        return $this->verifyEvmTransaction($invoice, $network, $recipientWallet, $invoice->expected_amount, $decimals);
    }

    /**
     * Convert decimal amount to raw block units.
     */
    protected function amountToUnits(float $amount, int $decimals): string
    {
        $amountStr = number_format($amount, $decimals, '.', '');
        if (strpos($amountStr, '.') !== false) {
            list($whole, $fraction) = explode('.', $amountStr);
        } else {
            $whole = $amountStr;
            $fraction = '';
        }
        $fraction = str_pad($fraction, $decimals, '0', STR_PAD_RIGHT);
        if (strlen($fraction) > $decimals) {
            $fraction = substr($fraction, 0, $decimals);
        }
        $units = bcmul($whole, bcpow('10', (string)$decimals), 0);
        $units = bcadd($units, $fraction, 0);
        return $units;
    }

    /**
     * Verify EVM block explorer transaction logs.
     */
    protected function verifyEvmTransaction(Invoice $invoice, string $network, string $walletAddress, float $expectedAmount, int $decimals): array
    {
        // For local development simulation / mock keys
        $apiKey = config("services.explorers.{$network}_key", 'dummy_key');
        if ($apiKey === 'dummy_key') {
            // Simulated local testing support
            if (session('simulate_web3_success')) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'meta_data' => array_merge($invoice->meta_data ?? [], ['txHash' => '0x' . bin2hex(random_bytes(32))])
                ]);
                return ['status' => 'success', 'txHash' => 'mock_tx_hash'];
            }
        }

        $contract = $this->defaultContracts[$network];
        $apiEndpoint = $this->apiEndpoints[$network];

        $params = [
            'module' => 'account',
            'action' => 'tokentx',
            'contractaddress' => $contract,
            'address' => $walletAddress,
            'page' => 1,
            'offset' => 100,
            'startblock' => 0,
            'endblock' => 99999999,
            'sort' => 'desc',
            'apikey' => $apiKey
        ];

        try {
            $response = Http::timeout(15)->get($apiEndpoint, $params);
            if ($response->failed()) {
                throw new Exception("Explorer API request failed");
            }

            $data = $response->json();
            if (($data['status'] ?? '0') !== '1' || empty($data['result'])) {
                return [
                    'status' => 'false',
                    'message' => 'Pending Transaction...',
                    'expected_amount' => $expectedAmount
                ];
            }

            $expectedUnits = $this->amountToUnits($expectedAmount, $decimals);
            $walletAddressLower = strtolower($walletAddress);

            foreach ($data['result'] as $tx) {
                $toAddress = strtolower($tx['to'] ?? '');
                $value = $tx['value'] ?? '0';

                if ($toAddress === $walletAddressLower && bccomp($value, $expectedUnits, 0) === 0) {
                    $txHash = $tx['hash'] ?? '';

                    // Replay Check across all invoices
                    $alreadyUsed = Invoice::where('meta_data', 'like', '%' . $txHash . '%')->exists();
                    if ($alreadyUsed) {
                        continue;
                    }

                    $txTime = (int)($tx['timeStamp'] ?? 0);
                    $expired = (time() - $txTime) > (35 * 60);

                    if ($expired) {
                        return ['status' => 'fraud', 'message' => 'Transaction timed out / expired'];
                    }

                    // Success!
                    $meta = $invoice->meta_data ?? [];
                    $meta['txHash'] = $txHash;
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'meta_data' => $meta
                    ]);

                    return [
                        'status' => 'success',
                        'txHash' => $txHash,
                        'from' => $tx['from'] ?? '',
                        'to' => $tx['to'] ?? '',
                        'amount' => bcdiv($value, bcpow('10', (string)$decimals), $decimals),
                        'timestamp' => $txTime
                    ];
                }
            }

            return [
                'status' => 'false',
                'message' => 'Matching transaction not found yet',
                'expected_amount' => $expectedAmount
            ];
        } catch (\Exception $e) {
            Log::error("Web3 EVM verification failed: " . $e->getMessage());
            return ['status' => 'false', 'message' => 'Verification engine temporary down. waiting check...'];
        }
    }

    /**
     * Verify Tron USDT transactions.
     */
    protected function verifyTronTransaction(Invoice $invoice, string $walletAddress, float $expectedAmount): array
    {
        // For local development simulation
        if (session('simulate_web3_success')) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'meta_data' => array_merge($invoice->meta_data ?? [], ['txHash' => 'tron_mock_tx_hash'])
            ]);
            return ['status' => 'success', 'txHash' => 'tron_mock_tx_hash'];
        }

        $expectedSun = bcmul(number_format($expectedAmount, 6, '.', ''), '1000000', 0);
        $url = "https://api.trongrid.io/v1/accounts/{$walletAddress}/transactions/trc20?limit=100&only_confirmed=true";

        try {
            $response = Http::timeout(15)->get($url);
            if ($response->failed()) {
                throw new Exception("Tron Grid connection failed");
            }

            $data = $response->json();
            if (!isset($data['data']) || !is_array($data['data'])) {
                return ['status' => 'false', 'message' => 'Error reading TRON explorer'];
            }

            foreach ($data['data'] as $tx) {
                if (
                    strtolower($tx['to'] ?? '') === strtolower($walletAddress) &&
                    ($tx['token_info']['address'] ?? '') === $this->defaultContracts['tron'] &&
                    ($tx['type'] ?? '') === 'Transfer' &&
                    bccomp($tx['value'] ?? '0', $expectedSun, 0) === 0
                ) {
                    $txHash = $tx['transaction_id'] ?? '';

                    // Replay Check
                    $alreadyUsed = Invoice::where('meta_data', 'like', '%' . $txHash . '%')->exists();
                    if ($alreadyUsed) {
                        continue;
                    }

                    $txTime = (int)(($tx['block_timestamp'] ?? 0) / 1000);
                    $expired = (time() - $txTime) > (35 * 60);

                    if ($expired) {
                        return ['status' => 'fraud', 'message' => 'Transaction has timed out / expired'];
                    }

                    // Success!
                    $meta = $invoice->meta_data ?? [];
                    $meta['txHash'] = $txHash;
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'meta_data' => $meta
                    ]);

                    return [
                        'status' => 'success',
                        'txHash' => $txHash,
                        'from' => $tx['from'] ?? '',
                        'to' => $tx['to'] ?? '',
                        'amount' => bcdiv($tx['value'], '1000000', 6),
                        'timestamp' => $txTime
                    ];
                }
            }

            return [
                'status' => 'false',
                'message' => 'Matching TRON transaction not found yet',
                'expected_amount' => $expectedAmount
            ];
        } catch (\Exception $e) {
            Log::error("TRON verification failed: " . $e->getMessage());
            return ['status' => 'false', 'message' => 'TRON explorer polling error'];
        }
    }

    public function refund(Invoice $invoice, array $settings, array $refundData): array
    {
        return [
            'status' => 'manual',
            'message' => 'Manual refund required. Please transfer the USDT back to the customer\'s wallet address on-chain.'
        ];
    }
}
