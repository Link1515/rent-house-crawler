<?php

namespace Link1515\RentHouseCrawler;

use PDO;

class DB
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function getPDO(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        self::$pdo = new PDO(
            "{$_ENV['DB_DRIVER']}:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]
        );

        return self::$pdo;
    }
}
