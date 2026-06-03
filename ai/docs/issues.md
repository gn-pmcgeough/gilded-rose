# Implementation Issues

## Parent

PRD: Gilded Rose — Phase 1 Structural Refactor (`ai/docs/prd-gilded-rose-refactor.md`)

## Status Legend

- [ ] Not started
- [x] Complete

---

## Phase 1: Foundation

Phase outcome: `GildedRose` is hardened as a data object — constants, quality helpers, and private constructor in place. No behavior changes; all 20 existing tests remain green throughout.

### ISSUE-001: Add constants, quality helpers, and private constructor to GildedRose

## Type

AFK

## What to build

Strengthen the `GildedRose` class as a pure data object without changing any update behavior.

Add named string constants for all four item types (`AGED_BRIE`, `SULFURAS`, `BACKSTAGE_PASS`, `CONJURED`). Add two bound-enforcing helpers — one that increases quality capped at 50, one that decreases quality floored at 0. Make the constructor private so `of()` is the sole public entry point. Add `declare(strict_types=1)` and full type hints throughout.

At the end of this issue the class is identical in behavior to before — no updater logic is touched yet.

## Tasks

- [ ] Add `declare(strict_types=1)` to `GildedRose.php`
- [ ] Add type hints to all properties, constructor parameters, and `of()` return type
- [ ] Define constants: `AGED_BRIE`, `SULFURAS`, `BACKSTAGE_PASS`, `CONJURED`
- [ ] Add `increaseQuality(int $by): void` — increments quality, caps at 50
- [ ] Add `decreaseQuality(int $by): void` — decrements quality, floors at 0
- [ ] Make `__construct` private

## Acceptance criteria

- [ ] All 20 existing tests pass without modification
- [ ] `new GildedRose(...)` is no longer callable from outside the class
- [ ] Item name strings appear nowhere in `GildedRose.php` except the constant definitions

## Blocked by

None — can start immediately.

---

## Phase 2: Per-type vertical slices

Phase outcome: Every item type is handled by its own updater class wired end-to-end through a resolver. The old nested conditional logic in `tick()` is fully deleted. All 20 tests pass through the strategy path.

Sulfuras goes first (simplest — no-op). Normal items go last so `NormalItemUpdater` becomes the `default` arm, enabling complete removal of the old conditionals.

---

### ISSUE-002: Migrate Sulfuras end-to-end via SulfurasUpdater

## Type

AFK

## What to build

Introduce the `ItemUpdaterInterface` and the first concrete implementation: `SulfurasUpdater`.

Create the interface in `src/Updaters/` with namespace `App\Updaters`, defining a single method `update(GildedRose $item): void`. Implement `SulfurasUpdater` as a no-op (Sulfuras never changes). Add a private `resolveUpdater(): ItemUpdaterInterface` method to `GildedRose` using a `match` expression; for now it handles only Sulfuras and falls through to the existing inline logic for all other types. Wire `tick()` to call `resolveUpdater()->update($this)` for Sulfuras, delegating to old logic otherwise.

Sulfuras tests now exercise the new strategy path; all other tests remain on the old path.

## Tasks

- [ ] Create `src/Updaters/` directory
- [ ] Create `ItemUpdaterInterface` with `update(GildedRose $item): void` and `declare(strict_types=1)`
- [ ] Create `SulfurasUpdater` implementing `ItemUpdaterInterface` (no-op body)
- [ ] Add `resolveUpdater()` to `GildedRose` — returns `SulfurasUpdater` for `SULFURAS`, falls back for others
- [ ] Update `tick()` to use the resolver for Sulfuras; retain old inline logic as fallback for other types

## Acceptance criteria

- [ ] All 20 existing tests pass
- [ ] Sulfuras `sellIn` and `quality` remain unchanged after `tick()`
- [ ] `ItemUpdaterInterface` is the only contract needed to add a new item type

## Blocked by

- ISSUE-001

---

### ISSUE-003: Migrate Aged Brie end-to-end via AgedBrieUpdater

## Type

AFK

## What to build

Add `AgedBrieUpdater` and wire it into the resolver, removing the Aged Brie branch from the old inline logic.

Aged Brie increases quality by 1 before the sell date and by 2 on or after, capped at 50. The updater calls `increaseQuality()` from ISSUE-001 so the cap is enforced centrally. After wiring, the Aged Brie conditional is removed from the old fallback logic in `tick()`.

## Tasks

- [ ] Create `AgedBrieUpdater` implementing `ItemUpdaterInterface`
- [ ] Implement: decrement `sellIn`, then increase quality by 1 (before sell date) or 2 (on/after), using `increaseQuality()`
- [ ] Add `AGED_BRIE` arm to `resolveUpdater()`
- [ ] Remove the Aged Brie branch from the old inline fallback logic in `tick()`

## Acceptance criteria

- [ ] All 20 existing tests pass
- [ ] Aged Brie quality increases correctly before, on, and after sell date
- [ ] Quality never exceeds 50 for Aged Brie

## Blocked by

- ISSUE-002

---

### ISSUE-004: Migrate Backstage Passes end-to-end via BackstagePassUpdater

## Type

AFK

## What to build

Add `BackstagePassUpdater` and wire it into the resolver, removing the Backstage Pass branch from the old inline logic.

Backstage passes increase quality by 1 (more than 10 days), 2 (10 days or fewer), or 3 (5 days or fewer), then drop to 0 after the concert. The updater uses `increaseQuality()` for the cap and sets quality directly to 0 post-concert.

## Tasks

- [ ] Create `BackstagePassUpdater` implementing `ItemUpdaterInterface`
- [ ] Implement: decrement `sellIn`, then apply tiered quality increase or zero it out post-concert
- [ ] Use `increaseQuality()` for all positive adjustments
- [ ] Add `BACKSTAGE_PASS` arm to `resolveUpdater()`
- [ ] Remove the Backstage Pass branch from the old inline fallback logic in `tick()`

## Acceptance criteria

- [ ] All 20 existing tests pass
- [ ] Quality increases by correct amount at each threshold (>10 days, ≤10, ≤5)
- [ ] Quality drops to exactly 0 on and after the concert date
- [ ] Quality never exceeds 50

## Blocked by

- ISSUE-003

---

### ISSUE-005: Migrate Normal items via NormalItemUpdater and remove all old tick() logic

## Type

AFK

## What to build

Add `NormalItemUpdater` as the `default` arm of the resolver, then delete all remaining inline conditional logic from `tick()`.

Normal items degrade by 1 before the sell date and by 2 on or after, floored at 0. Once `NormalItemUpdater` is the `default`, the old nested if/else block in `tick()` is entirely gone. `tick()` becomes a single delegation: `$this->resolveUpdater()->update($this)`.

This issue also confirms that the `CONJURED` constant is defined (from ISSUE-001) and that the resolver's `default` arm routes unknown names through `NormalItemUpdater`, ready for Phase 2 to add `ConjuredItemUpdater`.

## Tasks

- [ ] Create `NormalItemUpdater` implementing `ItemUpdaterInterface`
- [ ] Implement: decrement `sellIn`, then decrease quality by 1 (before sell date) or 2 (on/after), using `decreaseQuality()`
- [ ] Set `NormalItemUpdater` as the `default` arm in `resolveUpdater()`
- [ ] Delete all remaining old inline conditional logic from `tick()`
- [ ] Confirm `tick()` body is now a single line: `$this->resolveUpdater()->update($this)`
- [ ] Verify `CONJURED` constant exists and resolver is structured to accept a new arm in Phase 2

## Acceptance criteria

- [ ] All 20 existing tests pass
- [ ] `tick()` contains no inline quality or sellIn logic — only the resolver delegation
- [ ] Normal items degrade correctly before and after sell date, never below 0
- [ ] Adding a new item type requires only a new class and a new `match` arm — no other changes

## Blocked by

- ISSUE-004
