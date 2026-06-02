<?php

namespace App;

class NormalItemUpdater implements ItemUpdater
{
    public function update(GildedRose $item): void
    {
        $item->sellIn -= 1;
        $degradeBy = $item->sellIn < 0 ? 2 : 1;
        $item->quality = max(0, $item->quality - $degradeBy);
    }
}
