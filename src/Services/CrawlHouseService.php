<?php

namespace Link1515\RentHouseCrawler\Services;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Entities\HouseDetails;
use Link1515\RentHouseCrawler\Repositories\HouseRepository;
use Link1515\RentHouseCrawler\Services\MessageServices\MessageServiceInterface;
use Link1515\RentHouseCrawler\Strategy\ExcludeAgentFilter;
use Link1515\RentHouseCrawler\Strategy\ExcludeBasementFilter;
use Link1515\RentHouseCrawler\Strategy\ExcludeManOnlyFilter;
use Link1515\RentHouseCrawler\Strategy\ExcludeTopFloorAdditionFilter;
use Link1515\RentHouseCrawler\Strategy\ExcludeWomanOnlyFilter;
use Link1515\RentHouseCrawler\Strategy\HouseFilter;
use Link1515\RentHouseCrawler\Utils\CryptoUtils;
use Link1515\RentHouseCrawler\Utils\ExtractNuxtParamsUtils;
use Link1515\RentHouseCrawler\Utils\LogUtils;

class CrawlHouseService
{
    private HouseRepository $houseRepository;
    private MessageServiceInterface $messageService;
    private string $url;
    private HouseFilter $houseFilters;
    private bool $excludeAgent            = true;
    private bool $excludeManOnly          = false;
    private bool $excludeWomanOnly        = false;
    private bool $excludeTopFloorAddition = true;
    private bool $excludeBasement         = true;
    private array $houses                 = [];

    public function __construct(
        HouseRepository $houseRepository,
        MessageServiceInterface $messageService,
        string $url,
        array $options = []
    ) {
        $this->url                     = $url;
        $this->houseFilters            = new HouseFilter();
        $this->houseRepository         = $houseRepository;
        $this->messageService          = $messageService;
        $this->excludeAgent            = $options['excludeAgent'] ?? $this->excludeAgent;
        $this->excludeManOnly          = $options['excludeManOnly'] ?? $this->excludeManOnly;
        $this->excludeWomanOnly        = $options['excludeWomanOnly'] ?? $this->excludeWomanOnly;
        $this->excludeTopFloorAddition = $options['excludeTopFloorAddition'] ?? $this->excludeTopFloorAddition;
        $this->excludeBasement         = $options['excludeBasement'] ?? $this->excludeBasement;

        $this->initFilters();
    }

    private function initFilters(): void
    {
        if ($this->excludeAgent) {
            $this->houseFilters->addFilter(new ExcludeAgentFilter());
        }
        if ($this->excludeManOnly) {
            $this->houseFilters->addFilter(new ExcludeManOnlyFilter());
        }
        if ($this->excludeWomanOnly) {
            $this->houseFilters->addFilter(new ExcludeWomanOnlyFilter());
        }
        if ($this->excludeTopFloorAddition) {
            $this->houseFilters->addFilter(new ExcludeTopFloorAdditionFilter());
        }
        if ($this->excludeBasement) {
            $this->houseFilters->addFilter(new ExcludeBasementFilter());
        }
    }

    public function crawl(): void
    {
        try {
            $this->crawlHouses();
            $this->applyHouseFilters();
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

    private function applyHouseFilters()
    {
        $this->houses = $this->houseFilters->filterHouses($this->houses);
    }

    private function saveHouses()
    {
        if (count($this->houses) === 0) {
            return;
        }
        $this->houseRepository->insertHouses($this->houses);
    }
}
