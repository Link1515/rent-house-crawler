<?php

require_once __DIR__ . '/bootstrap.php';

use Link1515\RentHouseCrawler\DB;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;
use Link1515\RentHouseCrawler\Services\CrawlHouseService;
use Link1515\RentHouseCrawler\Utils\UrlUtils;
use Symfony\Component\DomCrawler\Crawler;

$baseUrl     = 'https://rent.591.com.tw/list';
$queryParams = [
    'region'  => 1,
    'metro'   => 162,
    'kind'    => 2,
    'price'   => '5000_10000',
    'sort'    => 'posttime_desc',
    'station' => 4232
];
$url  = UrlUtils::buildUrlWithQuery($baseUrl, $queryParams);
$html = file_get_contents($url);

$crawler         = new Crawler($html);
$pdo             = DB::getPDO();
$houseRepository = new HouseRepository($pdo);

$crawlHouseService = new CrawlHouseService(
    $houseRepository,
    $crawler,
    [
        'excludeAgent'            => true,
        'excludeWomanOnly'        => true,
        'excludeTopFloorAddition' => true
    ]
);

$crawlHouseService->crawl();
