<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\House;

class MessageService
{
    public static function sendHouseMessage(House $house): void
    {
        $message = <<<Message
        通知：
        {$house->title}
        Message;

        self::sendMessage($message);
    }

    public static function sendMessage(string $message): void
    {
        echo $message;
    }
}
