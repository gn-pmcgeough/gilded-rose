# Gilded Rose Refactoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the deeply nested conditional logic in `GildedRose::tick()` with a Strategy pattern, then add the Conjured item type.

**Architecture:** An `ItemUpdaterInterface` defines a single `update(GildedRose $item): void` contract. An `AbstractItemUpdater` provides bounds-safe quality helpers. Five concrete updater classes (Normal, AgedBrie, Sulfuras, BackstagePass, Conjured) each handle one item type. An `ItemUpdaterFactory` maps item names to updater instances and is injected into `GildedRose` via an optional constructor parameter.

**Tech Stack:** PHP 7.4, PHPUnit 9, Composer PSR-4 autoloader (`App\` → `src/`)

---

## File Map

| Action | Path | Responsibility |
|--------|------|---------------|
| Modify | `src/GildedRose.php` | Add `$factory` property; update constructor; replace `tick()` |
| Create | `src/Updater/ItemUpdaterInterface.php` | Contract: `update(GildedRose): void` |
| Create | `src/Updater/AbstractItemUpdater.php` | Implements interface; provides `incrementQuality` / `decrementQuality` helpers |
| Create | `src/Updater/NormalItemUpdater.php` | −1/day; −2/day after sell date |
| Create | `src/Updater/AgedBrieUpdater.php` | +1/day; +2/day after sell date; capped at 50 |
| Create | `src/Updater/SulfurasUpdater.php` | No-op; legendary item never changes |
| Create | `src/Updater/BackstagePassUpdater.php` | Tiered +1/+2/+3; drops to 0 after concert |
| Create | `src/Updater/ItemUpdaterFactory.php` | Maps item name → updater; falls back to `NormalItemUpdater` |
| Create | `src/Updater/ConjuredItemUpdater.php` | −2/day; −4/day after sell date; floor 0 |
| Modify | `tests/GildedRoseTest.php` | Uncomment Conjured item tests |

---

## Task 1: Verify baseline

- [ ] **Run the full test suite**

```bash
./vendor/bin/phpunit --testdox
```

Expected output: `OK (23 tests, 46 assertions)` — all green. If any tests are failing before you start, stop and investigate before making changes.

---

## Task 2: Create `ItemUpdaterInterface` and `AbstractItemUpdater`

**Files:**
- Create: `src/Updater/ItemUpdaterInterface.php`
- Create: `src/Updater/AbstractItemUpdater.php`

These are pure infrastructure — no behaviour change yet, no test step required here. They will be exercised once `GildedRose` is wired up in Task 8.

- [ ] **Create `src/Updater/ItemUpdaterInterface.php`**

```php
<?php

namespace App\Updater;

use App\GildedRose;

interface ItemUpdaterInterface
{
    public function update(GildedRose $item): void;
}
```

- [ ] **Create `src/Updater/AbstractItemUpdater.php`**

```php
<?php

namespace App\Updater;

use App\GildedRose;

abstract class AbstractItemUpdater implements ItemUpdaterInterface
{
    protected function incrementQuality(GildedRose $item, int $amount = 1): void
    {
        $item->quality = min(50, $item->quality + $amount);
    }

    protected function decrementQuality(GildedRose $item, int $amount = 1): void
    {
        $item->quality = max(0, $item->quality - $amount);
    }
}
```

---

## Task 3: Create `NormalItemUpdater`

**Files:**
- Create: `src/Updater/NormalItemUpdater.php`

Normal items lose 1 quality per day. Once the sell date has passed (`sellIn` goes below 0 after decrement), they lose an additional 1 quality (total −2/day).

- [ ] **Create `src/Updater/NormalItemUpdater.php`**

```php
<?php

namespace App\Updater;

use App\GildedRose;

class NormalItemUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        $this->decrementQuality($item);
        $item->sellIn--;
        if ($item->sellIn < 0) {
            $this->decrementQuality($item);
        }
    }
}
```

---

## Task 4: Create `AgedBrieUpdater`

**Files:**
- Create: `src/Updater/AgedBrieUpdater.php`

Aged Brie gains 1 quality per day. After the sell date has passed (`sellIn` goes below 0 after decrement), it gains an additional 1 quality (total +2/day). Quality is capped at 50 by the `incrementQuality` helper.

- [ ] **Create `src/Updater/AgedBrieUpdater.php`**

```php
<?php

namespace App\Updater;

use App\GildedRose;

class AgedBrieUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        $this->incrementQuality($item);
        $item->sellIn--;
        if ($item->sellIn < 0) {
            $this->incrementQuality($item);
        }
    }
}
```

---

## Task 5: Create `SulfurasUpdater`

**Files:**
- Create: `src/Updater/SulfurasUpdater.php`

Sulfuras is a legendary item. It never changes quality or sellIn. The updater is intentionally a no-op.

- [ ] **Create `src/Updater/SulfurasUpdater.php`**

```php
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
```

---

## Task 6: Create `BackstagePassUpdater`

**Files:**
- Create: `src/Updater/BackstagePassUpdater.php`

Backstage passes gain quality as the concert approaches. The tier is determined by `sellIn` *before* it is decremented:
- `sellIn > 10`: +1
- `6 ≤ sellIn ≤ 10`: +2
- `sellIn ≤ 5`: +3

After `sellIn` is decremented, if `sellIn < 0` (concert has passed), quality drops to exactly 0 — even if the item was already expired at the start of the tick.

- [ ] **Create `src/Updater/BackstagePassUpdater.php`**

```php
<?php

namespace App\Updater;

use App\GildedRose;

class BackstagePassUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        if ($item->sellIn > 10) {
            $this->incrementQuality($item, 1);
        } elseif ($item->sellIn > 5) {
            $this->incrementQuality($item, 2);
        } else {
            $this->incrementQuality($item, 3);
        }

        $item->sellIn--;

        if ($item->sellIn < 0) {
            $item->quality = 0;
        }
    }
}
```

---

## Task 7: Create `ItemUpdaterFactory`

**Files:**
- Create: `src/Updater/ItemUpdaterFactory.php`

The factory maps item name strings to their updater instances. Any name not in the map falls back to `NormalItemUpdater`. Note: `ConjuredItemUpdater` is registered here even though it is implemented in Task 9 — the factory is created first so that wiring `GildedRose` in Task 8 is a single, self-contained change.

- [ ] **Create `src/Updater/ItemUpdaterFactory.php`**

```php
<?php

namespace App\Updater;

class ItemUpdaterFactory
{
    private array $updaters;

    public function __construct()
    {
        $this->updaters = [
            'Aged Brie'                                 => new AgedBrieUpdater(),
            'Sulfuras, Hand of Ragnaros'                => new SulfurasUpdater(),
            'Backstage passes to a TAFKAL80ETC concert' => new BackstagePassUpdater(),
            'Conjured Mana Cake'                        => new ConjuredItemUpdater(),
        ];
    }

    public function getUpdater(string $name): ItemUpdaterInterface
    {
        return $this->updaters[$name] ?? new NormalItemUpdater();
    }
}
```

**Note:** `ConjuredItemUpdater` is referenced here but will be created in Task 9. The factory file is written now but `GildedRose` will not be wired up until Task 8, and tests will not run until after Task 9 completes. If you need to run tests between tasks, temporarily comment out the Conjured line in the constructor.

---

## Task 8: Refactor `GildedRose` to delegate via factory; run tests

**Files:**
- Modify: `src/GildedRose.php`

Replace the entire body of `tick()` with a factory call. Add an optional `ItemUpdaterFactory` parameter to the constructor. The static `of()` factory method passes `null`, which triggers the default factory — existing tests require no changes.

- [ ] **Replace `src/GildedRose.php` with the refactored version**

```php
<?php

namespace App;

use App\Updater\ItemUpdaterFactory;

class GildedRose
{
    public $name;

    public $quality;

    public $sellIn;

    private ItemUpdaterFactory $factory;

    public function __construct($name, $quality, $sellIn, ItemUpdaterFactory $factory = null)
    {
        $this->name    = $name;
        $this->quality = $quality;
        $this->sellIn  = $sellIn;
        $this->factory = $factory ?? new ItemUpdaterFactory();
    }

    public static function of($name, $quality, $sellIn)
    {
        return new static($name, $quality, $sellIn);
    }

    public function tick(): void
    {
        $this->factory->getUpdater($this->name)->update($this);
    }
}
```

- [ ] **Create `src/Updater/ConjuredItemUpdater.php` as a stub (needed for factory to load)**

This placeholder satisfies the factory's constructor so the autoloader does not error. It will be replaced with the real implementation in Task 9.

```php
<?php

namespace App\Updater;

use App\GildedRose;

class ConjuredItemUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        // stub — implemented in Task 9
    }
}
```

- [ ] **Run the full test suite**

```bash
./vendor/bin/phpunit --testdox
```

Expected output: `OK (23 tests, 46 assertions)` — all green. If any tests fail, compare the failing item type's updater logic against the original `tick()` code in git (`git diff HEAD src/GildedRose.php` shows the old implementation).

- [ ] **Commit**

```bash
git add src/GildedRose.php src/Updater/
git commit -m "refactor: extract item updater strategy pattern"
```

---

## Task 9: Implement `ConjuredItemUpdater` and enable Conjured tests

**Files:**
- Modify: `src/Updater/ConjuredItemUpdater.php`
- Modify: `tests/GildedRoseTest.php`

Conjured items degrade at twice the normal rate: −2/day before the sell date, and an additional −2 after the sell date (−4/day total). Quality floors at 0.

- [ ] **Uncomment the Conjured tests in `tests/GildedRoseTest.php`**

Remove the `//` prefix from every line of the six commented-out Conjured test methods. Also remove the leading underscore from the first test's function name, changing `_updates_conjured_items_before_the_sell_date` to `updates_conjured_items_before_the_sell_date`.

The six test methods to uncomment are:

```
updates_conjured_items_before_the_sell_date
updates_conjured_items_at_zero_quality
updates_conjured_items_on_the_sell_date
updates_conjured_items_on_the_sell_date_at_0_quality
updates_conjured_items_after_the_sell_date
updates_conjured_items_after_the_sell_date_at_zero_quality
```

The uncommented block should look exactly like this (lines 304–369 of the original file):

```php
    /** @test */
    function updates_conjured_items_before_the_sell_date()
    {
        $item = GildedRose::of('Conjured Mana Cake', 10, 10);

        $item->tick();

        $this->assertEquals(8, $item->quality);
        $this->assertEquals(9, $item->sellIn);
    }

    /** @test */
    function updates_conjured_items_at_zero_quality()
    {
        $item = GildedRose::of('Conjured Mana Cake', 0, 10);

        $item->tick();

        $this->assertEquals(0, $item->quality);
        $this->assertEquals(9, $item->sellIn);
    }

    /** @test */
    function updates_conjured_items_on_the_sell_date()
    {
        $item = GildedRose::of('Conjured Mana Cake', 10, 0);

        $item->tick();

        $this->assertEquals(6, $item->quality);
        $this->assertEquals(-1, $item->sellIn);
    }

    /** @test */
    function updates_conjured_items_on_the_sell_date_at_0_quality()
    {
        $item = GildedRose::of('Conjured Mana Cake', 0, 0);

        $item->tick();

        $this->assertEquals(0, $item->quality);
        $this->assertEquals(-1, $item->sellIn);
    }

    /** @test */
    function updates_conjured_items_after_the_sell_date()
    {
        $item = GildedRose::of('Conjured Mana Cake', 10, -10);

        $item->tick();

        $this->assertEquals(6, $item->quality);
        $this->assertEquals(-11, $item->sellIn);
    }

    /** @test */
    function updates_conjured_items_after_the_sell_date_at_zero_quality()
    {
        $item = GildedRose::of('Conjured Mana Cake', 0, -10);

        $item->tick();

        $this->assertEquals(0, $item->quality);
        $this->assertEquals(-11, $item->sellIn);
    }
```

- [ ] **Run the test suite — verify the Conjured tests fail**

```bash
./vendor/bin/phpunit --testdox
```

Expected: 6 Conjured tests fail (the stub updater is a no-op). The other 23 tests remain green. If more tests fail, the stub introduced a regression — check that `ConjuredItemUpdater` still extends `AbstractItemUpdater` correctly.

- [ ] **Replace the stub in `src/Updater/ConjuredItemUpdater.php` with the real implementation**

```php
<?php

namespace App\Updater;

use App\GildedRose;

class ConjuredItemUpdater extends AbstractItemUpdater
{
    public function update(GildedRose $item): void
    {
        $this->decrementQuality($item, 2);
        $item->sellIn--;
        if ($item->sellIn < 0) {
            $this->decrementQuality($item, 2);
        }
    }
}
```

- [ ] **Run the full test suite — verify all 29 tests pass**

```bash
./vendor/bin/phpunit --testdox
```

Expected output: `OK (29 tests, 58 assertions)` — all green.

- [ ] **Commit**

```bash
git add src/Updater/ConjuredItemUpdater.php tests/GildedRoseTest.php
git commit -m "feat: add Conjured item type"
```

---

## Done

The refactoring is complete. The final state:
- `GildedRose::tick()` is a one-liner delegation
- Each item type's logic lives in its own focused class
- Adding a new item type requires only a new class + one factory entry
- 29 tests, all green, including the previously-disabled Conjured suite
