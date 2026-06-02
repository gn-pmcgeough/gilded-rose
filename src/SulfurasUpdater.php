<?php

namespace App;

class SulfurasUpdater implements ItemUpdater
{
    public function update(GildedRose $item): void
    {
        // Sulfuras is a legendary item — quality and sellIn never change
    }
}
