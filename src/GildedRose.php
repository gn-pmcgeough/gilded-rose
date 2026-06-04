<?php

declare(strict_types=1);

namespace App;

use App\Updaters\AgedBrieUpdater;
use App\Updaters\BackstagePassUpdater;
use App\Updaters\ItemUpdaterInterface;
use App\Updaters\NormalItemUpdater;
use App\Updaters\SulfurasUpdater;

class GildedRose
{
    public const AGED_BRIE = 'Aged Brie';
    public const SULFURAS = 'Sulfuras, Hand of Ragnaros';
    public const BACKSTAGE_PASS = 'Backstage passes to a TAFKAL80ETC concert';
    public const CONJURED = 'Conjured Mana Cake';

    public string $name;
    public int $quality;
    public int $sellIn;

    private function __construct(string $name, int $quality, int $sellIn)
    {
        $this->name = $name;
        $this->quality = $quality;
        $this->sellIn = $sellIn;
    }

    public static function of(string $name, int $quality, int $sellIn): self
    {
        return new static($name, $quality, $sellIn);
    }

    public function increaseQuality(int $by = 1): void
    {
        $this->quality = min(50, $this->quality + $by);
    }

    public function decreaseQuality(int $by = 1): void
    {
        $this->quality = max(0, $this->quality - $by);
    }

    private function resolveUpdater(): ItemUpdaterInterface
    {
        switch ($this->name) {
            case self::SULFURAS:
                return new SulfurasUpdater();
            case self::AGED_BRIE:
                return new AgedBrieUpdater();
            case self::BACKSTAGE_PASS:
                return new BackstagePassUpdater();
            default:
                return new NormalItemUpdater();
        }
    }

    public function tick(): void
    {
        $this->resolveUpdater()->update($this);
    }
}
