<?php

namespace App\Updater;

class ItemUpdaterFactory
{
    private array $updaters;

    public function __construct()
    {
        $this->updaters = [
            'Aged Brie'                                 => new AgedBrieUpdater(),
            'Sulfuras, Hand of Ragnaros'                => new SulfurasUpdater(),
            'Backstage passes to a TAFKAL80ETC concert' => new BackstagePassUpdater(),
            'Conjured Mana Cake'                        => new ConjuredItemUpdater(),
            '_default'                                  => new NormalItemUpdater(),
        ];
    }

    public function getUpdater(string $name): ItemUpdaterInterface
    {
        return $this->updaters[$name] ?? $this->updaters['_default'];
    }
}