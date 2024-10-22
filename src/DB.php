<?php

namespace Link1515\RentHouseCrawler;

use PDO;

class DB
{
    public static function getPDO(): PDO
    {
        $pdo = new PDO(
            "{$_ENV['DB_DRIVER']}:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]
        );
        return $pdo;
    }
}
