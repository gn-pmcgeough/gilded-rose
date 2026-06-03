<?php

namespace App\Updater;

use App\GildedRose;

class ConjuredItemUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        $this->decrementQuality($item, 2);
        $item->sellIn--;
        if ($item->sellIn < 0) {
            $this->decrementQuality($item, 2);
        }
    }
}
