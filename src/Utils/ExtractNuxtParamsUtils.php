<?php

namespace Link1515\RentHouseCrawler\Utils;

class ExtractNuxtParamsUtils
{
    public static function extract(string $html): array
    {
        preg_match('/window\.__NUXT__=\(function\((.*)\){/', $html, $matches);
        $varNames = explode(',', $matches[1]);

        preg_match('/}\((.*)\)\)/', $html, $matches);
        $varValues = preg_split('/,(?=(?:(?:[^"]*"){2})*[^"]*$)/', $matches[1]);
        $varValues = array_map(function ($item) {
            $item = trim($item, '"');
            $item = json_decode('"' . $item . '"');
            return $item;
        }, $varValues);

        $paramsMap = [];
        for ($i = 0; $i < count($varNames); $i++) {
            $paramsMap[$varNames[$i]] = $varValues[$i];
        }

        return $paramsMap;
    }
}
