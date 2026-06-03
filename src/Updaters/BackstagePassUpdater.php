<?php

declare(strict_types=1);

namespace App\Updaters;

use App\GildedRose;

class BackstagePassUpdater implements ItemUpdaterInterface
{
    public function update(GildedRose $item): void
    {
        $item->sellIn--;

        if ($item->sellIn < 0) {
            $item->quality = 0;
        } elseif ($item->sellIn < 5) {
            $item->increaseQuality(3);
        } elseif ($item->sellIn < 10) {
            $item->increaseQuality(2);
        } else {
            $item->increaseQuality(1);
        }
    }
}
