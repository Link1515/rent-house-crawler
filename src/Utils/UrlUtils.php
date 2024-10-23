<?php

namespace Link1515\RentHouseCrawler\Utils;

class UrlUtils
{
    public const HOUSE_RENT_URL = 'https://rent.591.com.tw';

    public static function getHouseListUrl($queryParams): string
    {
        return self::buildUrlWithQuery(self::HOUSE_RENT_URL . '/list', $queryParams);
    }

    public static function buildUrlWithQuery(string $url, array $queryParms = [])
    {
        $url         = self::getCleanUrl($url);
        $queryString = http_build_query($queryParms);
        $fullUrl     = "{$url}?{$queryString}";

        return $fullUrl;
    }

    public static function getCleanUrl(string $url)
    {
        $parsedUrl = parse_url($url);
        $cleanUrl  = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        $path = $parsedUrl['path'] ?? '/';
        if (self::pathIsNotRoot($path)) {
            $cleanUrl .= $parsedUrl['path'];
        }

        return $cleanUrl;
    }

    private static function pathIsNotRoot(string $path)
    {
        return $path !== '/' && $path !== '';
    }
}
