<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Entities\HouseDetails;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;
use Link1515\RentHouseCrawler\Services\MessageServices\MessageServiceInterface;
use Link1515\RentHouseCrawler\Utils\CryptoUtils;
use Link1515\RentHouseCrawler\Utils\ExtractNuxtParamsUtils;
use Link1515\RentHouseCrawler\Utils\LogUtils;
use Link1515\RentHouseCrawler\Utils\StringUrils;

class CrawlHouseService
{
    private HouseRepository $houseRepository;
    private MessageServiceInterface $messageService;
    private string $url;
    private array $houses                 = [];
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
        $this->url                     = $url;
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
            $this->crawlHouses();
            $this->excludeHousesByOptions();
            $this->filterNewHouses();
            $this->saveHouses();

            LogUtils::log('Done!');
        } catch (\Exception $e) {
            LogUtils::log($e->getMessage());
        }
    }

    private function crawlHouses(): void
    {
        LogUtils::log('Crawling houses...');
        $html      = file_get_contents($this->url);
        $houseList = $this->parseHouseList($html);

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
            array_push($this->houses, $house);
        }
    }

    private function parseHouseList(string $html): array
    {
        $paramsMap = ExtractNuxtParamsUtils::extract($html);
        $dataKey   = 'e';
        if (!array_key_exists($dataKey, $paramsMap)) {
            throw new \Exception('Failed to get house raw data');
        }
        $rawData  = $paramsMap[$dataKey];
        $jsonData = CryptoUtils::Decrypt($rawData);
        $data     = json_decode($jsonData, true);
        return $data['items'];
    }

    private function filterNewHouses()
    {
        if ($this->houseRepository->count() === 0) {
            return;
        }

        $newHouses = $this->houseRepository->findNewHouses($this->houses);
        if (count($newHouses) === 0) {
            $this->houses = [];
            LogUtils::log('No new houses found!');
            return;
        }

        LogUtils::log('Found new houses: ' . count($newHouses) . ', and will be sent to Discord...');
        /** @var House $house */
        foreach ($newHouses as $house) {
            $houseDetails = new HouseDetails($house->id);
            $this->messageService->sendHouseMessage($house, $houseDetails->description, $houseDetails->images);
        }

        $this->houses = $newHouses;
    }

    private function excludeHousesByOptions()
    {
        if ($this->excludeAgent) {
            $this->excludeAgentFromHouses($this->houses);
        }
        if ($this->excludeManOnly) {
            $this->excludeManOnlyFromHouses($this->houses);
        }
        if ($this->excludeWomanOnly) {
            $this->excludeWomanOnlyFromHouses($this->houses);
        }
        if ($this->excludeTopFloorAddition) {
            $this->excludeTopFloorAdditionFromHouse($this->houses);
        }
        if ($this->excludeBasement) {
            $this->excludeBasementFromHouse($this->houses);
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

    private function saveHouses()
    {
        if (count($this->houses) === 0) {
            return;
        }
        $this->houseRepository->insertHouses($this->houses);
    }
}
