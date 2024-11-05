<?php

namespace Link1515\RentHouseCrawler\Services\MessageServices;

use Link1515\RentHouseCrawler\Entities\House;

class ConsoleMessageService implements MessageServiceInterface
{
    public function sendHouseMessage(House $house, string $description, array $images): void
    {
        $images = join("\n", $images);

        $message = <<<Message
        新通知：

        {$house->title}

        房租: {$house->price}
        類型: {$house->type}
        坪數: {$house->area}
        位置: {$house->address}
        周邊: {$house->surrounding}
        樓層: {$house->floor}
        發布者: {$house->poster}

        {$description}

        {$house->getLink()}

        {$images}
        \n
        Message;

        $this->sendMessage($message);
    }

    public function sendMessage(string $message): void
    {
        echo $message;
    }
}
