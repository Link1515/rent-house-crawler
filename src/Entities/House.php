<?php

namespace Link1515\RentHouseCrawler\Entities;

class House
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $type,
        public readonly string $area,
        public readonly string $price,
        public readonly string $address,
        public readonly string $surrounding,
        public readonly string $floor,
        public readonly string $poster
    ) {
    }

    public function getLink(): string
    {
        return "https://rent.591.com.tw/{$this->id}";
    }

    public function __toString()
    {
        return
            <<<String
            id:          {$this->id}
            title:       {$this->title}
            type:        {$this->type}
            area:        {$this->area}
            price:       {$this->price}
            address:     {$this->address}
            surrounding: {$this->surrounding}
            floor:       {$this->floor}
            poster:      {$this->poster}
            \n
            String;
    }
}
