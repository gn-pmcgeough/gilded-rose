<?php

namespace App;

use App\Updater\ItemUpdaterFactory;

class GildedRose
{
    public $name;

    public $quality;

    public $sellIn;

    private ItemUpdaterFactory $factory;

    public function __construct(string $name, int $quality, int $sellIn, ?ItemUpdaterFactory $factory = null)
    {
        $this->name    = $name;
        $this->quality = $quality;
        $this->sellIn  = $sellIn;
        $this->factory = $factory ?? new ItemUpdaterFactory();
    }

    public static function of($name, $quality, $sellIn)
    {
        return new static($name, $quality, $sellIn);
    }

    public function tick(): void
    {
        $this->factory->getUpdater($this->name)->update($this);
    }
}
