<?php

namespace Link1515\RentHouseCrawler\Utils\Decrypter;

class AesCtrDecrypter
{
    // remember to tranfer from '\u002F' to '/'
    public static function Decrypt(string $rawData): string
    {
        $rawIv      = self::extractRawIv($rawData);
        $rawKey     = self::extractRawKey($rawData);
        $cryptoData = self::extractCryptoData($rawData, $rawKey, $rawIv);
        $iv         = base64_decode($rawIv);
        $key        = base64_decode($rawKey);
        $plaintext  = self::aesCtrDecrypt($cryptoData, $key, $iv);

        return $plaintext;
    }

    private static function extractRawIv(string $rawData): string
    {
        $rawIvStartIndex = 24;
        $rawIvLength     = 24;
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

    private static function extractCryptoData(string $rawData, string $rawKey, string $rawIv): string
    {
        $rawData = str_replace($rawKey, '', $rawData);
        $rawData = str_replace($rawIv, '', $rawData);
        return base64_decode($rawData);
    }

    public static function aesCtrDecrypt($ciphertext, $key, $iv)
    {
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-ctr',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($plaintext === false) {
            throw new \Exception('Decryption failed');
        }

        return $plaintext;
    }
}
