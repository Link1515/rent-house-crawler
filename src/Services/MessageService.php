<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\House;

class MessageService
{
    public static function sendHouseMessage(House $house, string $description, array $images): void
    {
        $images = join("\n", $images);

        $message = <<<Message
        新通知：

        {$house->title}

        房租: {$house->price}
        位置: {$house->address}
        樓層: {$house->floor}
        發布者: {$house->poster}

        {$description}

        {$house->getLink()}

        {$images}
        \n
        Message;

        self::sendMessage($message);
    }

    public static function sendMessage(string $message): void
    {
        echo $message;
    }
}
