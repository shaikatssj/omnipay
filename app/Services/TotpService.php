<?php

namespace App\Services;

class TotpService
{
    /**
     * Generate a random 16-character Base32 secret key.
     */
    public static function generateSecret($length = 16): string
    {
        $b32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $b32[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Get the OTPAuth URL to register in Google Authenticator or other authenticator apps.
     */
    public static function getQrCodeUrl(string $label, string $secret, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($label) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }

    /**
     * Verify a 6-digit TOTP code against a secret with a given time step discrepancy.
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }

        $currentTimeSlice = (int)floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate the 6-digit code for a given secret at a specific time slice.
     */
    public static function getCode(string $secret, int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = (int)floor(time() / 30);
        }
        $secretKey = self::base32Decode($secret);

        // Pack time slice into 8-byte binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N', $timeSlice);

        // Generate HMAC-SHA1 signature
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic truncation to extract 4 bytes
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);

        // Unpack value as big-endian 32-bit unsigned integer
        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;

        $modulo = 10 ** 6;
        return str_pad((string)($value % $modulo), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decodes a Base32 string into a raw binary string.
     */
    protected static function base32Decode(string $secret): string
    {
        if (empty($secret)) {
            return '';
        }

        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));

        $secret = strtoupper($secret);
        $secret = str_replace('=', '', $secret);
        $secretChars = str_split($secret);
        
        $binaryString = "";
        $x = "";

        foreach ($secretChars as $char) {
            if (!isset($base32charsFlipped[$char])) {
                continue;
            }
            $x .= str_pad(decbin($base32charsFlipped[$char]), 5, '0', STR_PAD_LEFT);
        }

        $eightBits = str_split($x, 8);
        foreach ($eightBits as $bits) {
            if (strlen($bits) === 8) {
                $binaryString .= chr((int)bindec($bits));
            }
        }

        return $binaryString;
    }
}
