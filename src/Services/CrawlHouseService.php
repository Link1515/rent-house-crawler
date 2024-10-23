<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;
use Link1515\RentHouseCrawler\Services\MessageService;
use Link1515\RentHouseCrawler\Utils\RegexUtils;
use Link1515\RentHouseCrawler\Utils\StringUrils;
use Symfony\Component\DomCrawler\Crawler;

class CrawlHouseService
{
    private const ITEM_SELECTOR               = '.item';
    private const PRICE_DIGIAL_SELECTOR       = '.item-info-price i';
    private const TITLE_SELECTOR              = '.item-info-title a';
    private const ADDRESS_CHAR_SELECTOR       = '.item-info-txt:nth-child(3) > span > span > i';
    private const FLOOR_CHAR_SELECTOR         = '.item-info-txt:nth-child(2) > span > span > i';
    private const POSTER_SELECTOR             = '.role-name > span:nth-child(1)';
    private const DETAIL_DESCRIPTION_SELECTOR = '.house-condition-content .article';
    private const DETAIL_IMAGES_SELECTOR      = '.common-img';

    private Crawler $crawler;
    private Crawler $detailCrawler;
    private HouseRepository $houseRepository;
    private bool $excludeAgent            = true;
    private bool $excludeManOnly          = false;
    private bool $excludeWomanOnly        = false;
    private bool $excludeTopFloorAddition = true;

    public function __construct(HouseRepository $houseRepository, string $url, array $options = [])
    {
        $this->crawler                 = $this->createCrawler($url);
        $this->houseRepository         = $houseRepository;
        $this->excludeAgent            = $options['excludeAgent'] ?? $this->excludeAgent;
        $this->excludeManOnly          = $options['excludeManOnly'] ?? $this->excludeManOnly;
        $this->excludeWomanOnly        = $options['excludeWomanOnly'] ?? $this->excludeWomanOnly;
        $this->excludeTopFloorAddition = $options['excludeTopFloorAddition'] ?? $this->excludeTopFloorAddition;
    }

    public function crawl(): void
    {
        $houses = $this->crawlHouses();

        $storedHouseIds = $this->houseRepository->getAllHouseIds();
        if (empty($storedHouseIds)) {
            $this->houseRepository->insertHouses($houses);
            return;
        }

        $newHouses = $this->getNewHouses($houses, $storedHouseIds);
        foreach ($newHouses as $house) {
            MessageService::sendHouseMessage($house);
        }

        $this->houseRepository->truncateHousesTable();
        $this->houseRepository->insertHouses($houses);
    }

    private function createCrawler($url): Crawler
    {
        $html = file_get_contents($url);
        return new Crawler($html);
    }

    private function getNewHouses($houses, $storedHouseIds): array
    {
        $currentHouseIds = array_column($houses, 'id');
        $newHouseIds     = array_diff($currentHouseIds, $storedHouseIds);
        $newHouses       = array_filter($houses, function (House $house) use ($newHouseIds) {
            return in_array($house->id, $newHouseIds);
        });

        return $newHouses;
    }

    private function crawlHouses(): array
    {
        $houses = [];

        $this->crawler
            ->filter(static::ITEM_SELECTOR)
            ->each(function ($node) use (&$houses) {
                $id      = $this->getId($node);
                $title   = $this->getTitle($node);
                $price   = $this->getPrice($node);
                $address = $this->getAddress($node);
                $floor   = $this->getFloor($node);
                $poster  = $this->getPoster($node);

                $house = new House($id, $title, $price, $address, $floor, '', $poster);
                array_push($houses, $house);
            });

        $this->excludeHousesByOptions($houses);

        return $houses;
    }

    private function getTitle(Crawler $node): string
    {
        return $node->filter(static::TITLE_SELECTOR)->first()->text();
    }

    private function getId(Crawler $node): int
    {
        $url        = $node->filter(static::TITLE_SELECTOR)->link()->getUri();
        $urlPartial = explode('/', $url);
        return (int) end($urlPartial);
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

    private function excludeHousesByOptions(array &$houses)
    {
        if ($this->excludeAgent) {
            $this->excludeAgentFromHouses($houses);
        }
        if ($this->excludeManOnly) {
            $this->excludeManOnlyFromHouses($houses);
        }
        if ($this->excludeWomanOnly) {
            $this->excludeWomanOnlyFromHouses($houses);
        }
        if ($this->excludeTopFloorAddition) {
            $this->excludeTopFloorAdditionFromHouse($houses);
        }
    }

    private function excludeAgentFromHouses(array &$houses)
    {
        $needle = '仲介';
        $houses = array_filter(
            $houses,
            function (House $house) use ($needle) {
                return StringUrils::stringNotContain($house->poster, $needle);
            }
        );
    }

    private function excludeWomanOnlyFromHouses(array &$houses)
    {
        $needles = ['限女', '女性', '女生', '租女'];
        $houses  = array_filter(
            $houses,
            function (House $house) use ($needles) {
                return StringUrils::stringContainNone($house->title, $needles);
            }
        );
    }

    private function excludeManOnlyFromHouses(array &$houses)
    {
        $needles = ['限男', '男性', '男生', '租男'];
        $houses  = array_filter(
            $houses,
            function (House $house) use ($needles) {
                return StringUrils::stringContainNone($house->title, $needles);
            }
        );
    }

    private function excludeTopFloorAdditionFromHouse(array &$houses)
    {
        $needle = '頂樓加蓋';
        $houses = array_filter(
            $houses,
            function (House $house) use ($needle) {
                return StringUrils::stringNotContain($house->floor, $needle);
            }
        );
    }

    private function setDetailCrawler(string $url)
    {
        $this->detailCrawler = $this->createCrawler($url);
    }

    private function getDetailDescription(): string
    {
        $description = $this->detailCrawler
            ->filter(static::DETAIL_DESCRIPTION_SELECTOR)
            ->text();
        $description = str_replace('<br>', "\n", $description);
        return $description;
    }

    private function getDetailImages(): string
    {
        $images = [];
        $this->detailCrawler->filter('.common-img')->each(function ($node) use (&$images) {
            $src = $node->attr('data-src');
            if (str_contains($src, 'no-photo-new.png')) {
                return;
            }
            array_push($images, $src);
        });
        return join(',', $images);
    }
}
