<?php

namespace App\Services;

class MfsParser
{
    /**
     * Map sender names to MFS system names.
     */
    public static function normalizeMfsName(string $sender): ?string
    {
        $sender = strtolower($sender);
        if (strpos($sender, 'bkash') !== false) {
            return 'bkash';
        }
        if (strpos($sender, 'nagad') !== false || $sender === '16167') {
            return 'nagad';
        }
        if (strpos($sender, 'rocket') !== false || $sender === '16216') {
            return 'rocket';
        }
        if (strpos($sender, 'upay') !== false || $sender === '16268') {
            return 'upay';
        }
        if (strpos($sender, 'tap') !== false || $sender === '16118') {
            return 'tap';
        }
        if (strpos($sender, 'cellfin') !== false || strpos($sender, 'ibbl') !== false) {
            return 'cellfin';
        }
        if (strpos($sender, 'okwallet') !== false || strpos($sender, 'ok wallet') !== false || $sender === '16247') {
            return 'okwallet';
        }
        if (strpos($sender, 'mcash') !== false) {
            return 'mcash';
        }
        if (strpos($sender, 'pathao') !== false) {
            return 'pathaopay';
        }
        return null;
    }

    /**
     * Parse message string based on provider settings.
     */
    public static function parse(string $mfs, string $message): ?array
    {
        $mfs = strtolower(trim($mfs));
        $message = trim(preg_replace('/\s+/', ' ', $message));

        $formats = [
            'bkash' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/You have received Tk ([\d,.]+) from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'fee', 'balance', 'trxid', 'datetime']
                ],
                [
                    'type'     => 'Personal',
                    'priority' => 90,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'fee', 'balance', 'trxid', 'datetime']
                ],
                [
                    'type'     => 'Merchant',
                    'priority' => 80,
                    'pattern'  => '/You have received payment Tk ([\d,.]+) from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'fee', 'balance', 'trxid', 'datetime']
                ],
            ],
            'nagad' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Money Received\. Amount: Tk ([\d,.]+) Sender: (\d+)(?:\s*Ref[:\-]?\s*(\S+))? TxnID: ([A-Z0-9]+) Balance: Tk ([\d,.]+) ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'trxid', 'balance', 'datetime']
                ],
                [
                    'type'     => 'Personal',
                    'priority' => 90,
                    'pattern'  => '/Cash In Received\. Amount: Tk ([\d,.]+) Uddokta: (\d+) TxnID: ([A-Z0-9]+) Balance: ([\d,.]+) ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'balance', 'datetime']
                ],
            ],
            'rocket' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Tk([\d,.]+) received from A\/C:([*\d]+) Fee:Tk([\d,.]+)\, Your A\/C Balance: Tk([\d,.]+) TxnId:([A-Z0-9]+)(?: Date:([\w\-:\s]+))?/i',
                    'map'      => ['amount', 'sender', 'fee', 'balance', 'trxid', 'datetime']
                ],
            ],
            'upay' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Tk\. ([\d,.]+) has been received from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Balance Tk\. ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)\./i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid', 'datetime']
                ],
            ],
            'tap' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Received Tk ([\d,.]+) from (\d+)\. Balance Tk\. ([\d,.]+)\. TxID: ([A-Z0-9]+)\./i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],
            ],
            'cellfin' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Islami Bank CellFin Received ([\d,.]+) Tk From CellFin: (\d+) To CellFin: (\d+) TrxId: ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'receiver', 'trxid']
                ],
            ],
            'okwallet' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/\(OK Wallet\) Successfully received Tk ([\d,.]+) from A\/C (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid']
                ],
            ],
            'mcash' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/IBBL mCash You have received Tk: ([\d,.]+) From: (\d+)(?:\s*Reference:\s*(\S*))? Balance Tk: ([\d,.]+) TrxID: ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid']
                ],
            ],
            'pathaopay' => [
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/You have received BDT ([\d,.]+) from (\+?\d+)\. Balance BDT ([\d,.]+) TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],
            ],
        ];

        if (!isset($formats[$mfs])) {
            return null;
        }

        // Sort formats by priority DESC
        usort($formats[$mfs], fn($a, $b) => $b['priority'] <=> $a['priority']);

        foreach ($formats[$mfs] as $format) {
            if (preg_match($format['pattern'], $message, $matches)) {
                $data = [
                    'mfs' => $mfs,
                    'type' => $format['type'],
                    'raw' => $message,
                ];

                foreach ($format['map'] as $index => $key) {
                    $data[$key] = $matches[$index + 1] ?? null;
                }

                // Clean and normalize numeric formats
                foreach (['amount', 'balance', 'fee'] as $field) {
                    if (isset($data[$field]) && $data[$field] !== null) {
                        $data[$field] = floatval(str_replace(',', '', $data[$field]));
                    }
                }

                // Standardize transaction ID
                if (isset($data['trxid'])) {
                    $data['trxid'] = strtoupper(trim($data['trxid']));
                }

                return $data;
            }
        }

        // Fallback: If no strict regex matches but the message has amount/trxid keywords, try to extract them
        $amount = null;
        if (preg_match('/(?:Tk\.?|Amount:|BDT)\s*([0-9,]+(?:\.[0-9]+)?)/i', $message, $amtMatches)) {
            $amount = floatval(str_replace(',', '', $amtMatches[1]));
        }
        $trxid = null;
        if (preg_match('/(?:TrxID|TxnID|TxnId|TxID|TrnxID|trnxid|TrnxId|trnx_id|trx_id|txn_id)[:\s]*([A-Z0-9]+)/i', $message, $trxMatches)) {
            $trxid = strtoupper(trim($trxMatches[1]));
        }

        if ($amount && $trxid) {
            return [
                'mfs' => $mfs,
                'type' => 'Generic',
                'amount' => $amount,
                'trxid' => $trxid,
                'raw' => $message,
            ];
        }

        return null;
    }
}
