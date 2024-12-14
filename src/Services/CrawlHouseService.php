<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;
use Link1515\RentHouseCrawler\Services\MessageServices\MessageServiceInterface;
use Link1515\RentHouseCrawler\Utils\CryptoUtils;
use Link1515\RentHouseCrawler\Utils\LogUtils;
use Link1515\RentHouseCrawler\Utils\StringUrils;
use Symfony\Component\DomCrawler\Crawler;

class CrawlHouseService
{
    private const DETAIL_DESCRIPTION_SELECTOR = '.house-condition-content .article';
    private const DETAIL_IMAGES_SELECTOR      = '.common-img';
    private const DETAIL_IMAGES_PLACEHOLDER   = 'no-photo-new.png';

    private Crawler $crawler;
    private Crawler $detailCrawler;
    private HouseRepository $houseRepository;
    private MessageServiceInterface $messageService;
    private bool $excludeAgent            = true;
    private bool $excludeManOnly          = false;
    private bool $excludeWomanOnly        = false;
    private bool $excludeTopFloorAddition = true;
    private bool $excludeBasement         = true;

    public function __construct(
        HouseRepository $houseRepository,
        MessageServiceInterface $messageService,
        string $url,
        array $options = []
    ) {
        $this->crawler                 = $this->createCrawler($url);
        $this->houseRepository         = $houseRepository;
        $this->messageService          = $messageService;
        $this->excludeAgent            = $options['excludeAgent'] ?? $this->excludeAgent;
        $this->excludeManOnly          = $options['excludeManOnly'] ?? $this->excludeManOnly;
        $this->excludeWomanOnly        = $options['excludeWomanOnly'] ?? $this->excludeWomanOnly;
        $this->excludeTopFloorAddition = $options['excludeTopFloorAddition'] ?? $this->excludeTopFloorAddition;
        $this->excludeBasement         = $options['excludeBasement'] ?? $this->excludeBasement;
    }

    public function crawl(): void
    {
        try {
            LogUtils::log('Crawling houses...');
            $houses = $this->crawlHouses();

            if ($this->houseRepository->count() === 0) {
                $this->houseRepository->insertHouses($houses);
                return;
            }

            $newHouses = $this->houseRepository->findNewHouses($houses);
            if (count($newHouses) === 0) {
                LogUtils::log('No new houses found!');
                return;
            }

            LogUtils::log('Found new houses: ' . count($newHouses) . ', and will be sent to Discord...');
            /** @var House $house */
            foreach ($newHouses as $house) {
                $this->setDetailCrawler($house->getLink());
                $description = $this->getDetailDescription();
                $images      = $this->getDetailImages();
                $this->messageService->sendHouseMessage($house, $description, $images);
            }

            $this->houseRepository->truncateHousesTable();
            $this->houseRepository->insertHouses($houses);

            LogUtils::log('Done!');
        } catch (\Throwable $e) {
            LogUtils::log($e->getMessage());
        }
    }

    private function createCrawler($url): Crawler
    {
        $html = file_get_contents($url);
        return new Crawler($html);
    }

    private function crawlHouses(): array
    {
        $houses    = [];
        $houseList = $this->getHouseList();

        foreach ($houseList as $houseItem) {
            $id      = $houseItem['id'];
            $title   = $houseItem['title'];
            $type    = $houseItem['kind_name'];
            $area    = $houseItem['area_name'];
            $price   = $houseItem['price'] . $houseItem['price_unit'];
            $address = $houseItem['address'];
            $floor   = $houseItem['floor_name'];
            $poster  = $houseItem['role_name'];

            $surrounding = '';
            if ($houseItem['surrounding']) {
                $surrounding = $houseItem['surrounding']['desc'] . $houseItem['surrounding']['distance'];
            }

            $house = new House($id, $title, $type, $area, $price, $address, $surrounding, $floor, $poster);
            array_push($houses, $house);
        }

        $this->excludeHousesByOptions($houses);

        return $houses;
    }

    private function getHouseList(): array
    {
        $html      = $this->crawler->html();
        $paramsMap = $this->extractNuxtParams($html);
        $dataKey   = 'e';
        if (!array_key_exists($dataKey, $paramsMap)) {
            throw new \Exception('Failed to get house raw data');
        }
        $rawData  = $paramsMap[$dataKey];
        $jsonData = CryptoUtils::Decrypt($rawData);
        $data     = json_decode($jsonData, true);
        return $data['items'];
    }

    private function extractNuxtParams(string $html): array
    {
        preg_match('/window\.__NUXT__=\(function\((.*)\){/', $html, $matches);
        $varNames = explode(',', $matches[1]);

        preg_match('/}\((.*)\)\)/', $html, $matches);
        $varValues = preg_split('/,(?=(?:(?:[^"]*"){2})*[^"]*$)/', $matches[1]);
        $varValues = array_map(function ($item) {
            $item = trim($item, '"');
            $item = json_decode('"' . $item . '"');
            return $item;
        }, $varValues);

        $paramsMap = [];
        for ($i = 0; $i < count($varNames); $i++) {
            $paramsMap[$varNames[$i]] = $varValues[$i];
        }

        return $paramsMap;
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
        if ($this->excludeBasement) {
            $this->excludeBasementFromHouse($houses);
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
        $needles = ['限女', '女性', '女生', '租女', '女學'];
        $houses  = array_filter(
            $houses,
            function (House $house) use ($needles) {
                return StringUrils::stringContainNone($house->title, $needles);
            }
        );
    }

    private function excludeManOnlyFromHouses(array &$houses)
    {
        $needles = ['限男', '男性', '男生', '租男', '男學'];
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

    private function excludeBasementFromHouse(array &$houses)
    {
        $needle = 'B';
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
            ->filter(self::DETAIL_DESCRIPTION_SELECTOR)
            ->html();
        $description = StringUrils::brToLineBreak($description);
        $description = strip_tags($description);
        $description = StringUrils::clearAbnormalSpace($description);

        return $description;
    }

    private function getDetailImages(): array
    {
        $images = [];
        $this->detailCrawler->filter(self::DETAIL_IMAGES_SELECTOR)->each(function ($node) use (&$images) {
            $src = $node->attr('data-src');
            if (str_contains($src, self::DETAIL_IMAGES_PLACEHOLDER)) {
                return;
            }
            array_push($images, $src);
        });
        return $images;
    }
}
