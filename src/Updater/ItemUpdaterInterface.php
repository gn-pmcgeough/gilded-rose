<?php

namespace App\Updater;

use App\GildedRose;

interface ItemUpdaterInterface
{
    public function update(GildedRose $item): void;
}