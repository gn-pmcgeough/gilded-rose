<?php

namespace App;

interface ItemUpdater
{
    public function update(GildedRose $item): void;
}
