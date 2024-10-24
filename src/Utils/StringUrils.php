<?php

namespace Link1515\RentHouseCrawler\Utils;

class StringUrils
{
    public static function stringContain(string $string, string $needle): string
    {
        return str_contains($string, $needle);
    }

    public static function stringNotContain(string $string, string $needle): string
    {
        return !str_contains($string, $needle);
    }

    public static function stringContainAny(string $string, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($string, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function stringContainNone(string $string, array $needles): bool
    {
        return !self::stringContainAny($string, $needles);
    }

    public static function brToLineBreak(string $string): string
    {
        return preg_replace('/<br>/', "\n", $string);
    }

    public static function clearAbnormalSpace(string $string): string
    {
        return preg_replace('/\p{Zs}/u', '', $string);
    }
}
