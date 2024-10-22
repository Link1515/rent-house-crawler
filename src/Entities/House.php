<?php

namespace Link1515\RentHouseCrawler\Entities;

class House
{
    public function __construct(
        public string $id,
        public string $title,
        public string $url,
        public int $price,
        public string $address,
        public string $floor,
        public string $description,
        public string $poster
    ) {
    }

    public function __toString()
    {
        return
            <<<String
            id:      {$this->id}
            title:   {$this->title}
            url:     {$this->url}
            price:   {$this->price}
            address: {$this->address}
            floor:   {$this->floor}
            poster:  {$this->poster}\n
            String;
    }
}
