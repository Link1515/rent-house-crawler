<?php

namespace Link1515\RentHouseCrawler\Strategy;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Strategy\Interface\FilterInterface;
use Link1515\RentHouseCrawler\Utils\StringUrils;

class ExcludeManOnlyFilter implements FilterInterface
{
    public function apply(array $houses): array
    {
        $needles = ['限男', '男性', '男生', '租男', '男學'];
        $houses  = array_filter(
            $houses,
            function (House $house) use ($needles) {
                return StringUrils::stringContainNone($house->title, $needles);
            }
        );

        return $houses;
    }
}
