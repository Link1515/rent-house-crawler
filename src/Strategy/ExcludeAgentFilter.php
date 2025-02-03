<?php

namespace Link1515\RentHouseCrawler\Strategy;

use Link1515\RentHouseCrawler\Entities\House;
use Link1515\RentHouseCrawler\Strategy\Interface\FilterInterface;
use Link1515\RentHouseCrawler\Utils\StringUrils;

class ExcludeAgentFilter implements FilterInterface
{
    public function apply(array $houses): array
    {
        $needles = ['仲介', '代理'];
        $houses  = array_filter(
            $houses,
            function (House $house) use ($needles) {
                return StringUrils::stringContainNone($house->poster, $needles);
            }
        );

        return $houses;
    }
}
