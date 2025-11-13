<?php
// Encryption utility using AES-256-CBC
class Encryption {
    private static $key;
    private static $cipher = 'AES-256-CBC';

    public static function setKey($key) {
        self::$key = $key;
    }

    public static function encrypt($data) {
        if (empty($data)) return $data;
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($data, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
        // Version 1: Store version byte (1), IV length, IV, then encrypted data
        $ivLength = strlen($iv);
        return base64_encode(chr(1) . chr($ivLength) . $iv . $encrypted);
    }

    public static function decrypt($data) {
        if (empty($data)) return $data;
        $decoded = base64_decode($data);

        if (strlen($decoded) < 2) {
            // Too short to be valid encrypted data, return as-is
            return $data;
        }

        $version = ord($decoded[0]);

        if ($version === 1) {
            // Version 1: version byte, IV length byte, IV, encrypted data
            $ivLength = ord($decoded[1]);
            $iv = substr($decoded, 2, $ivLength);
            $encrypted = substr($decoded, 2 + $ivLength);
        } else {
            // Legacy format: try to decrypt with 16-byte IV assumption
            // This handles existing data that may have been encrypted differently
            $ivLength = 16;
            $iv = substr($decoded, 0, min($ivLength, strlen($decoded)));
            $encrypted = substr($decoded, strlen($iv));

            // Ensure IV is exactly 16 bytes to avoid OpenSSL warnings
            $iv = str_pad($iv, $ivLength, "\0", STR_PAD_RIGHT);
        }

        // First try with current key
        $decrypted = @openssl_decrypt($encrypted, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);

        // If decryption failed, try with legacy padding (PKCS7)
        if ($decrypted === false || $decrypted === '') {
            $decrypted = @openssl_decrypt($encrypted, self::$cipher, self::$key, 0, $iv);
        }

        // If still failed, return original data
        if ($decrypted === false || $decrypted === '') {
            return $data;
        }

        return $decrypted;
    }
}
?>
