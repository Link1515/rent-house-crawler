<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\RentItem;
use Link1515\RentHouseCrawler\Utils\RegexUtils;
use Symfony\Component\DomCrawler\Crawler;

class CrawlHouseService
{
    private const ITEM_SELECTOR         = '.item';
    private const PRICE_DIGIAL_SELECTOR = '.item-info-price i';
    private const TITLE_SELECTOR        = '.item-info-title a';
    private const ADDRESS_CHAR_SELECTOR = '.item-info-txt:nth-child(3) > span > span > i';
    private const FLOOR_CHAR_SELECTOR   = '.item-info-txt:nth-child(2) > span > span > i';
    private const POSTER_SELECTOR       = '.role-name > span:nth-child(1)';

    private Crawler $crawler;

    public function __construct($crawler)
    {
        $this->crawler = $crawler;
    }

    public function getRentItems()
    {
        $this->crawler->filter(static::ITEM_SELECTOR)->each(function ($node) {
            $id      = $this->getId($node);
            $title   = $this->getTitle($node);
            $url     = $this->getUrl($node);
            $price   = $this->getPrice($node);
            $address = $this->getAddress($node);
            $floor   = $this->getFloor($node);
            $poster  = $this->getPoster($node);

            $rentItem = new RentItem($id, $title, $url, $price, $address, $floor, '', $poster);
            echo $rentItem;
            echo PHP_EOL;
        });
    }

    private function getTitle(Crawler $node): string
    {
        return $node->filter(static::TITLE_SELECTOR)->first()->text();
    }

    private function getId(Crawler $node): string
    {
        $url        = $this->getUrl($node);
        $urlPartial = explode('/', $url);
        return end($urlPartial);
    }

    private function getUrl(Crawler $node): string
    {
        return $node->filter(static::TITLE_SELECTOR)->link()->getUri();
    }

    private function getPrice(Crawler $node): int
    {
        $price = $this->restoreTextOrder($node, static::PRICE_DIGIAL_SELECTOR);
        $price = str_replace(',', '', $price);
        return (int) $price;
    }

    private function getAddress(Crawler $node): string
    {
        return $this->restoreTextOrder($node, static::ADDRESS_CHAR_SELECTOR);
    }

    private function getFloor(Crawler $node): string
    {
        return $this->restoreTextOrder($node, static::FLOOR_CHAR_SELECTOR);
    }

    private function restoreTextOrder(Crawler $node, string $selector): string
    {
        $orderRecorder = [];
        $node
            ->filter($selector)
            ->each(function ($node) use (&$orderRecorder) {
                $digial = $node->text();

                $style = $node->attr('style');
                $order = RegexUtils::findFirstGroup('/order:\s*?(\d+)/', $style);

                $orderRecorder[$order] = $digial;
            });

        ksort($orderRecorder);
        $text = implode('', $orderRecorder);

        return $text;
    }

    private function getPoster(Crawler $node): string
    {
        return $node->filter(static::POSTER_SELECTOR)->first()->text();
    }
}
