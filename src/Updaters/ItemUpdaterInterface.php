<?php

declare(strict_types=1);

namespace App\Updaters;

use App\GildedRose;

interface ItemUpdaterInterface
{
    public function update(GildedRose $item): void;
}
