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
use Link1515\RentHouseCrawler\Utils\Decrypter\AesCtrDecrypter;
use Link1515\RentHouseCrawler\Utils\ExtractNuxtParamsUtils;
use Link1515\RentHouseCrawler\Utils\HttpUtils;
use Link1515\RentHouseCrawler\Utils\LogUtils;

class CrawlHouseService
{
    private ?HouseRepository $houseRepository;
    private MessageServiceInterface $messageService;
    private string $url;
    private HouseFilter $houseFilters;
    private array $houses  = [];
    private array $options = [
        'excludeAgent'            => true,
        'excludeManOnly'          => false,
        'excludeWomanOnly'        => false,
        'excludeTopFloorAddition' => true,
        'excludeBasement'         => true
    ];

    public function __construct(
        ?HouseRepository $houseRepository,
        MessageServiceInterface $messageService,
        string $url,
        array $options = []
    ) {
        $this->url             = $url;
        $this->houseFilters    = new HouseFilter();
        $this->houseRepository = $houseRepository;
        $this->messageService  = $messageService;
        $this->options         = array_merge($this->options, $options);

        $this->initFilters();
    }

    private function initFilters(): void
    {
        if ($this->options['excludeAgent']) {
            $this->houseFilters->addFilter(new ExcludeAgentFilter());
        }
        if ($this->options['excludeManOnly']) {
            $this->houseFilters->addFilter(new ExcludeManOnlyFilter());
        }
        if ($this->options['excludeWomanOnly']) {
            $this->houseFilters->addFilter(new ExcludeWomanOnlyFilter());
        }
        if ($this->options['excludeTopFloorAddition']) {
            $this->houseFilters->addFilter(new ExcludeTopFloorAdditionFilter());
        }
        if ($this->options['excludeBasement']) {
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
            $this->sendMessages();

            LogUtils::log('Done!');
        } catch (\Exception $e) {
            LogUtils::log($e->getMessage());
        }
    }

    private function crawlHouses(): void
    {
        LogUtils::log('Crawling houses...');
        $html      = HttpUtils::get($this->url);
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
        $jsonData = AesCtrDecrypter::Decrypt($rawData);
        $data     = json_decode($jsonData, true);

        return $data['items'];
    }

    private function filterNewHouses()
    {
        if (is_null($this->houseRepository)) {
            LogUtils::log('House repository is not initialized. Return all houses...');
            return;
        }

        if ($this->houseRepository->count() === 0) {
            return;
        }

        $newHouses = $this->houseRepository->findNewHouses($this->houses);
        if (count($newHouses) === 0) {
            $this->houses = [];
            return;
        }

        $this->houses = $newHouses;
    }

    private function applyHouseFilters()
    {
        $this->houses = $this->houseFilters->filterHouses($this->houses);
    }

    private function saveHouses()
    {
        if (is_null($this->houseRepository)) {
            LogUtils::log('House repository is not initialized. Skipping save operation...');
            return;
        }

        if (count($this->houses) === 0) {
            return;
        }
        $this->houseRepository->insertHouses($this->houses);
    }

    private function sendMessages()
    {
        if (count($this->houses) === 0) {
            LogUtils::log('No new houses found!');
        }

        LogUtils::log('Found new houses: ' . count($this->houses) . ', and will be sent to ' . $this->messageService->getName() . '...');
        /** @var House $house */
        foreach ($this->houses as $house) {
            $houseDetails = new HouseDetails($house->id);
            $this->messageService->sendHouseMessage($house, $houseDetails->description, $houseDetails->images);
        }
    }
}
