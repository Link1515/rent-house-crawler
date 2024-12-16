<?php

namespace Link1515\RentHouseCrawler\Entities;

use Link1515\RentHouseCrawler\Utils\StringUrils;
use Symfony\Component\DomCrawler\Crawler;

class HouseDetails
{
    private const DESCRIPTION_SELECTOR = '.house-condition-content .article';
    private const IMAGES_SELECTOR      = '.common-img';
    private const IMAGES_PLACEHOLDER   = 'no-photo-new.png';

    public readonly int $id;
    public readonly string $description;
    public readonly array $images;
    private readonly Crawler $crawler;

    public function __construct(int $id) {
        $this->id = $id;
        $this->setCrawler($id);
        $this->description = $this->getDetailDescription();
        $this->images = $this->getDetailImages();
    }

    private function setCrawler(string $id) 
    {
        $url = $this->getLink($id);
        $html = file_get_contents($url);
        $this->crawler = new Crawler($html);
    }

    private function getLink(string $id): string {
        return "https://rent.591.com.tw/{$id}";
    }

    private function getDetailDescription(): string
    {
        $description = $this->crawler
            ->filter(self::DESCRIPTION_SELECTOR)
            ->html();
        $description = StringUrils::brToLineBreak($description);
        $description = strip_tags($description);
        $description = StringUrils::clearAbnormalSpace($description);

        return $description;
    }

    private function getDetailImages(): array
    {
        $images = [];
        $this->crawler->filter(self::IMAGES_SELECTOR)->each(function ($node) use (&$images) {
            $src = $node->attr('data-src');
            if (str_contains($src, self::IMAGES_PLACEHOLDER)) {
                return;
            }
            array_push($images, $src);
        });
        return $images;
    }
}
