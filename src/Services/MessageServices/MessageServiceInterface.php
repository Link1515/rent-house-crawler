<?php

namespace Link1515\RentHouseCrawler\Services\MessageServices;

use Link1515\RentHouseCrawler\Entities\House;

interface MessageServiceInterface
{
    public function sendHouseMessage(House $house, string $description, array $images): void;

    public function sendMessage(string $message): void;
}
