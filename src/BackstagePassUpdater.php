<?php

namespace App;

class BackstagePassUpdater implements ItemUpdater
{
    public function update(GildedRose $item): void
    {
        if ($item->sellIn <= 0) {
            $item->sellIn -= 1;
            $item->quality = 0;
            return;
        }

        $improveBy = 1;
        if ($item->sellIn <= 5) {
            $improveBy = 3;
        } elseif ($item->sellIn <= 10) {
            $improveBy = 2;
        }

        $item->quality = min(50, $item->quality + $improveBy);
        $item->sellIn -= 1;
    }
}
