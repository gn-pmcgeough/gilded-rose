<?php

declare(strict_types=1);

namespace App\Updaters;

use App\GildedRose;

class NormalItemUpdater implements ItemUpdaterInterface
{
    public function update(GildedRose $item): void
    {
        $item->sellIn--;

        if ($item->sellIn < 0) {
            $item->decreaseQuality(2);
        } else {
            $item->decreaseQuality(1);
        }
    }
}
