# Gilded Rose Refactor — Design Decisions

## Phases

- **Phase 1** (this refactor): structural cleanup only, all existing tests pass
- **Phase 2** (follow-on): uncomment and enable Conjured item tests

---

## Decisions

### Architecture
- **Pattern**: Strategy — one updater class per item type
- **Interface**: `ItemUpdaterInterface` in `src/Updaters/`, namespace `App\Updaters`
- **Method**: `update(GildedRose $item): void`
- **Resolver**: private method `resolveUpdater()` on `GildedRose` — a `match` expression mapping item name to updater instance; no separate factory class

### Code style
- `declare(strict_types=1)` and full type hints on all files (new and refactored)
- `__construct` made private; `of()` is the sole public entry point

### Quality bounds
- `GildedRose` gains two bound-enforcing helpers used by all updaters:
  - `increaseQuality(int $by): void` — caps at 50
  - `decreaseQuality(int $by): void` — floors at 0
- Keeps the 0–50 constraint in one place; updaters never call `min`/`max` directly

### Item name constants
- Defined on `GildedRose` (natural home for what a GildedRose item can be named):
  - `GildedRose::AGED_BRIE`
  - `GildedRose::SULFURAS`
  - `GildedRose::BACKSTAGE_PASS`
  - `GildedRose::CONJURED` _(defined now, wired in Phase 2)_

### Tests
- Existing tests kept as-is; all must pass after refactor
- No per-updater unit tests added in Phase 1
- Conjured tests remain commented out until Phase 2
- Conjured name matching: exact string (`=== GildedRose::CONJURED`), matching the test fixture `'Conjured Mana Cake'`

---

## File structure

```
src/
  GildedRose.php                        (refactored — private constructor, constants, helpers, resolver)
  Updaters/
    ItemUpdaterInterface.php
    NormalItemUpdater.php
    AgedBrieUpdater.php
    SulfurasUpdater.php
    BackstagePassUpdater.php
```

---

## Optional areas for Phase 2+
- Consider a shared `AbstractItemUpdater` base if updaters accumulate duplicated logic
- Evaluate whether `of()` should validate initial quality bounds (currently unconstrained)
