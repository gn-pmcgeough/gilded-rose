<?php

namespace App\Updater;

use App\GildedRose;

class NormalItemUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        $this->decrementQuality($item);
        $item->sellIn--;
        if ($item->sellIn < 0) {
            $this->decrementQuality($item);
        }
    }
}