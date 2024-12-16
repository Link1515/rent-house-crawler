<?php

namespace Link1515\RentHouseCrawler\Strategy;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Strategy\Interface\FilterInterface;
use Link1515\RentHouseCrawler\Utils\StringUrils;

class ExcludeAgentFilter implements FilterInterface
{
    public function apply(array $houses): array
    {
        $needle = '仲介';
        $houses = array_filter(
            $houses,
            function (House $house) use ($needle) {
                return StringUrils::stringNotContain($house->poster, $needle);
            }
        );

        return $houses;
    }
}
