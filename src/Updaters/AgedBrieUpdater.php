<?php

declare(strict_types=1);

namespace App\Updaters;

use App\GildedRose;

class AgedBrieUpdater implements ItemUpdaterInterface
{
    public function update(GildedRose $item): void
    {
        $item->sellIn--;

        if ($item->sellIn >= 0) {
            $item->increaseQuality(1);
        } else {
            $item->increaseQuality(2);
        }
    }
}
