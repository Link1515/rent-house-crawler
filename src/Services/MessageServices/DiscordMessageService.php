<?php

namespace Link1515\RentHouseCrawler\Services\MessageServices;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Utils\HttpUtils;

class DiscordMessageService implements MessageServiceInterface
{
    private string $webhook;

    public function __construct(string $webhook)
    {
        $this->webhook = $webhook;
    }

    public function getName(): string
    {
        return 'Discord';
    }

    public function sendHouseMessage(House $house, string $description, array $images): void
    {
        $url         = $house->getLink();
        $imageEmbeds = [];
        foreach ($images as $image) {
            $imageEmbeds[] = [
                'url'   => $url,
                'image' => [
                    'url' => $image,
                ]
            ];
        }

        $message = <<<Message
        房租: {$house->price}
        類型: {$house->type}
        坪數: {$house->area}
        位置: [{$house->address}](https://www.google.com/maps/search/{$house->address})
        周邊: {$house->surrounding}
        樓層: {$house->floor}
        發布者: {$house->poster}

        ### 房屋描述

        {$description}
        \n
        Message;

        $this->sendEmbedMessage([
            [
                'title'       => $house->title,
                'description' => $message,
                'url'         => $url,
                'color'       => 16744448
            ],
            ...$imageEmbeds
        ]);
    }

    public function sendMessage(string $message): void
    {
        HttpUtils::postJson($this->webhook, [
            'content' => $message,
        ]);
    }

    /**
     * Embed structure: https://discord.com/developers/docs/resources/message#embed-object-embed-structure
     * @return void
     */
    public function sendEmbedMessage(array $options): void
    {
        HttpUtils::postJson($this->webhook, [
            'embeds' => $options,
        ]);
    }
}
