<?php

namespace Link1515\RentHouseCrawler\Repositories;

use Link1515\RentHouseCrawler\Entities\House;
use PDO;

class HouseRepository
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName)
    {
        $this->pdo       = $pdo;
        $this->tableName = $tableName;

        if (!$this->tableExists()) {
            $this->createHousesTable();
        }
    }

    public function tableExists(): bool
    {
        $sql    = "SHOW TABLES LIKE '{$this->tableName}'";
        $result = $this->pdo->query($sql);
        return $result->rowCount() > 0;
    }

    public function createHousesTable(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
                id int NOT NULL,
                title varchar(255) NOT NULL,
                price int NOT NULL,
                address varchar(255) NOT NULL,
                floor varchar(255) NOT NULL,
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
        $this->pdo->exec("TRUNCATE TABLE `{$this->tableName}`");
    }

    public function getAllHouseIds(): array
    {
        $result = [];
        $rows   = $this->pdo->query("SELECT id FROM `{$this->tableName}`");
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }
        return $result;
    }

    public function insertHouse(House $house): bool
    {
        $result = $this->pdo->prepare(
            "INSERT INTO 
                        `{$this->tableName}` (id, title, price, address, floor, poster) 
                    VALUES 
                        (:id, :title, :price, :address, :floor, :poster)"
        )->execute([
            'id'      => $house->id,
            'title'   => $house->title,
            'price'   => $house->price,
            'address' => $house->address,
            'floor'   => $house->floor,
            'poster'  => $house->poster,
        ]);

        return $result;
    }

    public function insertHouses(array $houses)
    {
        $sql = "INSERT INTO `{$this->tableName}` (id, title, price, address, floor, poster) VALUES ";
        foreach ($houses as $house) {
            $sql .= "({$house->id}, '{$house->title}', {$house->price}, '{$house->address}', '{$house->floor}', '{$house->poster}'), ";
        }
        $sql = substr($sql, 0, -2);

        $this->pdo->exec($sql);
    }
}
