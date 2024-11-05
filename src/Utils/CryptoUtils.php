<?php

namespace Link1515\RentHouseCrawler\Utils;

class CryptoUtils
{
    public static function Decrypt(string $rawData): string
    {
        $rawIv        = self::extractRawIv($rawData);
        $rawKey       = self::extractRawKey($rawData);
        $combinedData = self::extractCombinedData($rawData, $rawKey, $rawIv);
        $tag          = self::extractTag($combinedData);
        $ciphertext   = self::extractCiphertext($combinedData);
        $iv           = base64_decode($rawIv);
        $key          = base64_decode($rawKey);
        $plaintext    = self::aesGcmDecrypt($ciphertext, $key, $iv, $tag);

        return $plaintext;
    }

    private static function extractRawIv(string $rawData): string
    {
        $rawIvStartIndex = 16;
        $rawIvLength     = 16;
        $rawIv           = substr($rawData, $rawIvStartIndex, $rawIvLength);
        return $rawIv;
    }

    private static function extractRawKey($rawData): string
    {
        $rawKeyLength     = 44;
        $rawKeyStartIndex = strlen($rawData) - $rawKeyLength;
        $rawKey           = substr($rawData, $rawKeyStartIndex, $rawKeyLength);
        return $rawKey;
    }

    private static function extractCombinedData(string $rawData, string $rawKey, string $rawIv): string
    {
        $rawData = str_replace($rawKey, '', $rawData);
        $rawData = str_replace($rawIv, '', $rawData);
        return base64_decode($rawData);
    }

    private static function extractTag(string $combinedData): string
    {
        return substr($combinedData, -16);
    }

    private static function extractCiphertext(string $combinedData): string
    {
        return substr($combinedData, 0, -16);
    }

    public static function aesGcmDecrypt($ciphertext, $key, $iv, $tag)
    {
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \Exception('Decryption failed');
        }

        return $plaintext;
    }
}
