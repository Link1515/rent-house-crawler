<?php

namespace Link1515\RentHouseCrawler\Strategy;

use Link1515\RentHouseCrawler\Strategy\Interface\FilterInterface;

class HouseFilter
{
    private array $filters = [];

    public function addFilter(FilterInterface $filter): void
    {
        $this->filters[] = $filter;
    }

    public function filterHouses(array $houses): array
    {
        foreach ($this->filters as $filter) {
            $houses = $filter->apply($houses);
        }

        return $houses;
    }
}
