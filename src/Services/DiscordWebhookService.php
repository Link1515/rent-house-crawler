<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Utils\HttpUtils;

class DiscordWebhookService
{
    public static function sendHouseMessage(House $house, string $description, array $images): void
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
        位置: {$house->address}
        樓層: {$house->floor}
        發布者: {$house->poster}

        {$description}
        \n
        Message;

        self::sendEmbedMessage([
            [
                'title'       => $house->title,
                'description' => $message,
                'url'         => $url,
            ],
            ...$imageEmbeds
        ]);
    }

    public static function sendMessage(string $message): void
    {
        HttpUtils::postJson($_ENV['DISCORD_WEBHOOK'], [
            'content' => $message,
        ]);
    }

    /**
     * Embed structure: https://discord.com/developers/docs/resources/message#embed-object-embed-structure
     * @return void
     */
    public static function sendEmbedMessage(array $options): void
    {
        HttpUtils::postJson($_ENV['DISCORD_WEBHOOK'], [
            'embeds' => $options,
        ]);
    }
}
