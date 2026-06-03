<?php

namespace App\Updater;

use App\GildedRose;

class BackstagePassUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        if ($item->sellIn > 10) {
            $this->incrementQuality($item, 1);
        } elseif ($item->sellIn > 5) {
            $this->incrementQuality($item, 2);
        } else {
            $this->incrementQuality($item, 3);
        }

        $item->sellIn--;

        if ($item->sellIn < 0) {
            $item->quality = 0;
        }
    }
}