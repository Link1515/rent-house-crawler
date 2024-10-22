<?php

namespace Link1515\RentHouseCrawler\Repositories;

use PDO;

class HouseRepository
{
    public const tableName = 'houses';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createHousesTable(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS houses (
                id varchar(255) NOT NULL,
                title varchar(255) NOT NULL,
                price int NOT NULL,
                address varchar(255) NOT NULL,
                floor varchar(255) NOT NULL,
                description text NULL,
                poster varchar(255) NOT NULL,
                CONSTRAINT houses_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
        SQL;

        $this->pdo->exec($sql);
    }
}
