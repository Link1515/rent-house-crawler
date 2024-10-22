<?php

namespace Link1515\RentHouseCrawler\Repositories;

use Link1515\RentHouseCrawler\Entities\House;
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
                id int NOT NULL,
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

    public function truncateHousesTable(): void
    {
        $this->pdo->exec('TRUNCATE TABLE houses');
    }

    public function getAllHouseIds(): array
    {
        $result = [];
        $rows   = $this->pdo->query('SELECT id FROM houses');
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }
        return $result;
    }

    public function insertHouse(House $house): bool
    {
        $result = $this->pdo->prepare(
            'INSERT INTO houses (id, title, price, address, floor, description, poster) VALUES (:id, :title, :price, :address, :floor, :description, :poster)'
        )->execute([
            'id'          => $house->id,
            'title'       => $house->title,
            'price'       => $house->price,
            'address'     => $house->address,
            'floor'       => $house->floor,
            'description' => $house->description,
            'poster'      => $house->poster,
        ]);

        return $result;
    }

    public function insertHouses(array $houses)
    {
        $sql = 'INSERT INTO houses (id, title, price, address, floor, description, poster) VALUES ';
        foreach ($houses as $house) {
            $sql .= "({$house->id}, '{$house->title}', {$house->price}, '{$house->address}', '{$house->floor}', '{$house->description}', '{$house->poster}'), ";
        }
        $sql = substr($sql, 0, -2);

        $this->pdo->exec($sql);
    }
}
