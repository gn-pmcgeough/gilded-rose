<?php

namespace App\Updater;

use App\GildedRose;

class SulfurasUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        // legendary item: never changes
    }
}