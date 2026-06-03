<?php

namespace App\Updater;

use App\GildedRose;

abstract class AbstractItemUpdater implements ItemUpdaterInterface
{
    protected function incrementQuality(GildedRose $item, int $amount = 1): void
    {
        $item->quality = min(50, $item->quality + $amount);
    }

    protected function decrementQuality(GildedRose $item, int $amount = 1): void
    {
        $item->quality = max(0, $item->quality - $amount);
    }
}