<?php

namespace Link1515\RentHouseCrawler\Repositories;

use DateTime;
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
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

    public function count(): int
    {
        $sql    = "SELECT COUNT(*) FROM `{$this->tableName}`";
        $result = $this->pdo->query($sql);
        return $result->fetchColumn();
    }

    public function findNewHouses(array $houses): array
    {
        $ids        = array_column($houses, 'id');
        $sql        = "SELECT id FROM `{$this->tableName}` WHERE id IN (" . implode(',', $ids) . ')';
        $result     = $this->pdo->query($sql);
        $existedIds = $result->fetchAll(PDO::FETCH_COLUMN);

        $newHouses = array_filter($houses, function (House $house) use ($existedIds) {
            return !in_array($house->id, $existedIds);
        });

        return $newHouses;
    }

    public function insertHouses(array $houses)
    {
        $sql = "INSERT INTO `{$this->tableName}` (id) VALUES ";
        foreach ($houses as $house) {
            $sql .= "({$house->id}), ";
        }
        $sql = substr($sql, 0, -2);

        $this->pdo->exec($sql);
    }

    public function findHousesCreatedBefore(DateTime $dateTime): array
    {
        $dateTimeStr = $dateTime->format('Y-m-d H:i:s');
        $sql         = "SELECT id FROM `{$this->tableName}` WHERE created_at < '{$dateTimeStr}'";
        $result      = $this->pdo->query($sql);
        $ids         = $result->fetchAll(PDO::FETCH_COLUMN);

        return $ids;
    }

    public function deleteHouses(array $houseIds)
    {
        $sql = "DELETE FROM `{$this->tableName}` WHERE id IN (" . implode(',', $houseIds) . ')';
        $this->pdo->exec($sql);
    }
}
