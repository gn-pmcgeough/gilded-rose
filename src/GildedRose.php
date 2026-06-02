<?php

namespace App;

class GildedRose
{
    public $name;

    public $quality;

    public $sellIn;

    public function __construct($name, $quality, $sellIn)
    {
        $this->name = $name;
        $this->quality = $quality;
        $this->sellIn = $sellIn;
    }

    public static function of($name, $quality, $sellIn)
    {
        return new static($name, $quality, $sellIn);
    }

    public function tick(): void
    {
        $this->updaterFor($this->name)->update($this);
    }

    private function updaterFor(string $name): ItemUpdater
    {
        if ($name === 'Sulfuras, Hand of Ragnaros') {
            return new SulfurasUpdater();
        }

        if ($name === 'Aged Brie') {
            return new AgedBrieUpdater();
        }

        if ($name === 'Backstage passes to a TAFKAL80ETC concert') {
            return new BackstagePassUpdater();
        }

        if (strncmp($name, 'Conjured', 8) === 0) {
            return new ConjuredItemUpdater();
        }

        return new NormalItemUpdater();
    }
}
