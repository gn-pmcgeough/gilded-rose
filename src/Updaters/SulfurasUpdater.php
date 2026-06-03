<?php

declare(strict_types=1);

namespace App\Updaters;

use App\GildedRose;

class SulfurasUpdater implements ItemUpdaterInterface
{
    public function update(GildedRose $item): void
    {
        // Sulfuras never changes — no-op
    }
}
