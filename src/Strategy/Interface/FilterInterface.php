<?php

namespace Link1515\RentHouseCrawler\Strategy\Interface;

use Link1515\RentHouseCrawler\Entities\House;

interface FilterInterface
{
    /**
     * @param House[] $houses
     * @return House[]
     */
    public function apply(array $houses): array;
}
