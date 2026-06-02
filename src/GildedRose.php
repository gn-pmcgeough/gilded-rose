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

    public function tick()
    {
        if ($this->name === 'Sulfuras, Hand of Ragnaros') {
            return;
        }

        $this->sellIn--;

        if ($this->name === 'Aged Brie') {
            $increase = $this->sellIn < 0 ? 2 : 1;
            $this->quality = min(50, $this->quality + $increase);
        } elseif ($this->name === 'Backstage passes to a TAFKAL80ETC concert') {
            if ($this->sellIn < 0) {
                $this->quality = 0;
            } elseif ($this->sellIn < 5) {
                $this->quality = min(50, $this->quality + 3);
            } elseif ($this->sellIn < 10) {
                $this->quality = min(50, $this->quality + 2);
            } else {
                $this->quality = min(50, $this->quality + 1);
            }
        } elseif ($this->name === 'Conjured Mana Cake') {
            $decrease = $this->sellIn < 0 ? 4 : 2;
            $this->quality = max(0, $this->quality - $decrease);
        } else {
            $decrease = $this->sellIn < 0 ? 2 : 1;
            $this->quality = max(0, $this->quality - $decrease);
        }
    }
}
