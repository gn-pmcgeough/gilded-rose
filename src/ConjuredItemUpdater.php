<?php

namespace App;

class ConjuredItemUpdater implements ItemUpdater
{
    public function update(GildedRose $item): void
    {
        $item->sellIn -= 1;
        $degradeBy = $item->sellIn < 0 ? 4 : 2;
        $item->quality = max(0, $item->quality - $degradeBy);
    }
}
