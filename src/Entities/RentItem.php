<?php

namespace Link1515\RentCrawler\Entities;

class RentItem
{
    public function __construct(
        private string $id,
        private string $title,
        private string $url,
        private int $price,
        private string $address,
        private string $floor,
        private string $description,
        private string $poster
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
