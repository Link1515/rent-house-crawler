<?php

require_once __DIR__ . '/../bootstrap.php';

use Link1515\RentHouseCrawler\DB;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;

try {
    $pdo = DB::getPDO();

    $houseRepository = new HouseRepository($pdo);
    $houseRepository->createHousesTable();
    $houseRepository->truncateHousesTable();

    echo "Table 'houses' created successfully.";
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
