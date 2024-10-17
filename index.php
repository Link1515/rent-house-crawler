<?php

require_once __DIR__ . '/vendor/autoload.php';

use Link1515\RentCrawler\Services\CrawlRentInfoService;
use Symfony\Component\DomCrawler\Crawler;

$url  = 'https://rent.591.com.tw/list?region=1&metro=162&station=4232';
$html = file_get_contents($url);

$crawler = new Crawler($html);

$crawlerRentInfoService = new CrawlRentInfoService($crawler);

$crawlerRentInfoService->getRentItems();
