<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

date_default_timezone_set('Asia/Taipei');

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
