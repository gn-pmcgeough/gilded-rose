# Gilded Rose Refactoring Design

Date: 2026-06-03
Branch: 03-superpowers

## Goal

Refactor `src/GildedRose.php` to replace the deeply nested conditional logic with a Strategy pattern. Add the "Conjured" item type (currently commented out in tests). Preserve the existing public API exactly. Target PHP 7.4.

## Requirements (from readme.md)

- All items have `sellIn` (days to sell) and `quality` (value)
- Quality never goes negative, never exceeds 50
- Normal items: quality -1/day; -2/day after sell date
- Aged Brie: quality +1/day; +2/day after sell date (capped at 50)
- Sulfuras: legendary — quality and sellIn never change
- Backstage passes: +1/day normally; +2 with ≤10 days left; +3 with ≤5 days left; drops to 0 after concert. Tier is evaluated using `sellIn` *before* it is decremented. Once `sellIn` reaches < 0 (after decrement), quality is always set to 0 — even if already expired at the start of a tick.
- Conjured items: degrade 2× normal rate (-2/day before sell date; -4/day after)

## Architecture

### Approach: Strategy + Constructor Injection

`GildedRose` retains its existing public API (`name`, `quality`, `sellIn`, constructor, static `of()`, `tick()`). An optional `ItemUpdaterFactory` is injected via the constructor; if omitted, a default instance is created. `tick()` delegates entirely to the appropriate updater.

### File Structure

```
src/
  GildedRose.php                     ← refactored; keeps public API unchanged
  Updater/
    ItemUpdaterInterface.php         ← interface: update(GildedRose): void
    AbstractItemUpdater.php          ← abstract; provides incrementQuality / decrementQuality helpers
    NormalItemUpdater.php            ← -1/day; -2/day after sell date
    AgedBrieUpdater.php              ← +1/day; +2/day after sell date; cap 50
    SulfurasUpdater.php              ← no-op
    BackstagePassUpdater.php         ← tiered +1/+2/+3; drops to 0 after concert
    ConjuredItemUpdater.php          ← -2/day; -4/day after sell date; floor 0
    ItemUpdaterFactory.php           ← maps item name → updater; unknown → NormalItemUpdater
```

Namespace: `App\Updater\*` under `src/Updater/` — picked up automatically by the existing PSR-4 autoloader.

### GildedRose Changes

```php
private ItemUpdaterFactory $factory;

public function __construct(string $name, int $quality, int $sellIn, ItemUpdaterFactory $factory = null)
{
    $this->name    = $name;
    $this->quality = $quality;
    $this->sellIn  = $sellIn;
    $this->factory = $factory ?? new ItemUpdaterFactory();
}

public function tick(): void
{
    $this->factory->getUpdater($this->name)->update($this);
}
```

Static `of()` passes `null` for the factory, triggering the default. Existing tests require no changes.

### Quality Bounds

All bounds enforcement lives in `AbstractItemUpdater`:

```php
protected function incrementQuality(GildedRose $item, int $amount = 1): void
{
    $item->quality = min(50, $item->quality + $amount);
}

protected function decrementQuality(GildedRose $item, int $amount = 1): void
{
    $item->quality = max(0, $item->quality - $amount);
}
```

No concrete updater class ever manually checks `< 50` or `> 0`.

### ItemUpdaterFactory

```php
class ItemUpdaterFactory
{
    private array $updaters;

    public function __construct()
    {
        $this->updaters = [
            'Aged Brie'                                  => new AgedBrieUpdater(),
            'Sulfuras, Hand of Ragnaros'                 => new SulfurasUpdater(),
            'Backstage passes to a TAFKAL80ETC concert'  => new BackstagePassUpdater(),
            'Conjured Mana Cake'                         => new ConjuredItemUpdater(),
        ];
    }

    public function getUpdater(string $name): ItemUpdaterInterface
    {
        return $this->updaters[$name] ?? new NormalItemUpdater();
    }
}
```

## SOLID Compliance

| Principle | How it's met |
|-----------|-------------|
| **S** Single Responsibility | Each updater class handles exactly one item type's logic |
| **O** Open/Closed | New item types added by creating a new class + one factory entry; existing classes untouched |
| **L** Liskov Substitution | All updaters are interchangeable via `ItemUpdaterInterface` |
| **I** Interface Segregation | `ItemUpdaterInterface` has one method: `update()` |
| **D** Dependency Inversion | `GildedRose` depends on `ItemUpdaterInterface`/`ItemUpdaterFactory` abstractions, not concretes |

## Tests

- All existing tests pass without modification
- Commented-out Conjured item tests in `GildedRoseTest.php` are uncommented

## Out of Scope

- No changes to `composer.json` (autoloader already handles `App\Updater\*`)
- No type changes to existing public properties on `GildedRose` (PHP 7.4 typed properties are optional additions only if they don't break tests)
- No new test file — Conjured tests are in the existing test class
