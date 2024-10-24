<?php

namespace Link1515\RentHouseCrawler\Entities;

class House
{
    public function __construct(
        public int $id,
        public string $title,
        public int $price,
        public string $address,
        public string $floor,
        public string $poster
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
            id:      {$this->id}
            title:   {$this->title}
            price:   {$this->price}
            address: {$this->address}
            floor:   {$this->floor}
            poster:  {$this->poster}\n
            String;
    }
}
