<?php

require_once __DIR__ . '/bootstrap.php';

use Link1515\RentHouseCrawler\DB;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;

$houseRepository = new HouseRepository(DB::getPDO(), 'houses');
$threeMonthsAgo  = new DateTime('-3 months');
$ids             = $houseRepository->findHousesCreatedBefore($threeMonthsAgo);
$houseRepository->deleteHouses($ids);
