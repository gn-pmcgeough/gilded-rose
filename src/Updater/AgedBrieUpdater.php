<?php

namespace App\Updater;

use App\GildedRose;

class AgedBrieUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        $this->incrementQuality($item);
        $item->sellIn--;
        if ($item->sellIn < 0) {
            $this->incrementQuality($item);
        }
    }
}