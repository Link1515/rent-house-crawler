<?php

require_once __DIR__ . '/bootstrap.php';

use Link1515\RentHouseCrawler\DB;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;
use Link1515\RentHouseCrawler\Services\CrawlHouseService;
use Link1515\RentHouseCrawler\Services\MessageServices\ConsoleMessageService;
use Link1515\RentHouseCrawler\Services\MessageServices\DiscordMessageService;
use Link1515\RentHouseCrawler\Utils\UrlUtils;

$queryParams = [
    'region'  => 1,
    'metro'   => 162,
    'kind'    => 2,
    'price'   => '5000_10000',
    'sort'    => 'posttime_desc',
    'station' => 4232
];
$url = UrlUtils::getHouseListUrl($queryParams);

$houseRepository = new HouseRepository(DB::getPDO(), 'houses');
// $messageService  = new DiscordMessageService($_ENV['DISCORD_WEBHOOK']);
$messageService = new ConsoleMessageService();

$crawlHouseService = new CrawlHouseService(
    $houseRepository,
    $messageService,
    $url,
    [
        'excludeWomanOnly' => true,
    ]
);

$crawlHouseService->crawl();
