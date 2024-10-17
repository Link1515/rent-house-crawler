<?php

namespace Link1515\RentHouseCrawler\Utils;

class RegexUtils
{
    public static function findFirstGroup(string $pattern, string $string): string
    {
        preg_match($pattern, $string, $matches);
        return array_key_exists(1, $matches) ? $matches[1] : '';
    }
}
