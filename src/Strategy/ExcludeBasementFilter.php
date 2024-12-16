<?php

namespace Link1515\RentHouseCrawler\Strategy;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Strategy\Interface\FilterInterface;
use Link1515\RentHouseCrawler\Utils\StringUrils;

class ExcludeBasementFilter implements FilterInterface
{
    public function apply(array $houses): array
    {
        $needle = 'B';
        $houses = array_filter(
            $houses,
            function (House $house) use ($needle) {
                return StringUrils::stringNotContain($house->floor, $needle);
            }
        );

        return $houses;
    }
}
