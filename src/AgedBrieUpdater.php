<?php

namespace App;

class AgedBrieUpdater implements ItemUpdater
{
    public function update(GildedRose $item): void
    {
        $item->sellIn -= 1;
        $improveBy = $item->sellIn < 0 ? 2 : 1;
        $item->quality = min(50, $item->quality + $improveBy);
    }
}
