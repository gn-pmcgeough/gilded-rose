# PRD: Gilded Rose — Phase 1 Structural Refactor

## Problem Statement

The Gilded Rose inventory system has a single `tick()` method that drives all item quality and sell-in updates. It uses deeply nested string comparisons to branch on item type, making the logic difficult to read and impossible to extend without modifying existing code. Every new item type requires touching the same method, increasing the risk of regressions.

A new item category ("Conjured") is waiting to be introduced, but the current structure makes it unsafe to add without first untangling the existing logic.

## Solution

Refactor the `tick()` method using the Strategy pattern: extract each item type's update logic into its own dedicated updater class behind a shared interface. The `GildedRose` class resolves the correct updater at runtime based on the item name. Adding a new item type becomes a matter of adding a new file — no existing code needs to change.

This refactor preserves all existing behavior and passes all current tests unchanged. It also lays the groundwork for enabling Conjured items in Phase 2.

## User Stories

1. As a developer, I want each item type's update logic in its own class, so that I can understand and modify one type without risking regressions in others.
2. As a developer, I want a shared interface for all item updaters, so that the system has a clear, enforced contract for how item types are added.
3. As a developer, I want item names defined as constants, so that magic strings are never duplicated across the codebase.
4. As a developer, I want quality bounds (0 minimum, 50 maximum) enforced in one place, so that individual updaters cannot accidentally produce out-of-range quality values.
5. As a developer, I want strict PHP types on all classes, so that type errors are caught at parse time rather than at runtime.
6. As a developer, I want the `GildedRose` constructor to be private with a named factory method as the sole entry point, so that object creation is consistent and controlled.
7. As a developer, I want to add a new item type by creating a single new file, so that the open/closed principle is respected and the risk of regression is minimised.
8. As a developer, I want all existing tests to pass without modification after the refactor, so that I can be confident existing behavior is fully preserved.
9. As a developer, I want the Conjured item constant defined (but not wired) in Phase 1, so that Phase 2 can connect it without touching the core data model.
10. As a maintainer, I want the item name resolution logic co-located with the `GildedRose` class rather than in a separate factory, so that the codebase stays navigable without unnecessary indirection.

## Implementation Decisions

### Modules

**`GildedRose` (modified)**
- Gains named constants for all item types: `AGED_BRIE`, `SULFURAS`, `BACKSTAGE_PASS`, `CONJURED`
- Constructor becomes private; `of(string $name, int $quality, int $sellIn)` remains the sole public factory
- Gains two quality-bound helpers: `increaseQuality(int $by): void` (caps at 50) and `decreaseQuality(int $by): void` (floors at 0)
- `tick()` delegates to a private `resolveUpdater(): ItemUpdaterInterface` method, which uses a `match` expression to return the correct updater for the item's name
- All public properties (`name`, `quality`, `sellIn`) remain public to avoid breaking existing test access patterns

**`ItemUpdaterInterface` (new)**
- Single method: `update(GildedRose $item): void`
- Implemented by all concrete updater classes

**`NormalItemUpdater` (new)**
- Decrements `sellIn` by 1
- Decrements quality by 1 before sell date, by 2 on or after

**`AgedBrieUpdater` (new)**
- Decrements `sellIn` by 1
- Increments quality by 1 before sell date, by 2 on or after

**`SulfurasUpdater` (new)**
- No-op: neither `sellIn` nor `quality` changes

**`BackstagePassUpdater` (new)**
- Decrements `sellIn` by 1
- Quality drops to 0 after the concert (sellIn < 0)
- Quality increases by 1 when more than 10 days remain, by 2 within 10 days, by 3 within 5 days

### Key decisions

- **Strategy over switch/match in one class**: updaters are separately testable and the addition of new types requires no modification to existing code.
- **Resolver stays on `GildedRose`**: the `match` mapping item name to updater is a private concern of `GildedRose`, not a separate factory — the logic is trivial and splitting it adds a file without benefit.
- **Exact string matching for Conjured**: tests use `'Conjured Mana Cake'`; prefix matching is not used.
- **Quality helpers on `GildedRose`**: the 0–50 invariant is encoded once and called by every updater, not duplicated as `min`/`max` in each class.
- **Strict types everywhere**: `declare(strict_types=1)` on all files; all method signatures carry full type hints.

## Testing Decisions

**What makes a good test here**: tests should assert externally observable state — `quality` and `sellIn` values after calling `tick()` — without knowing which updater class was invoked or how the resolver works internally. Tests are behavior contracts, not implementation audits.

**What is tested**:
- `GildedRose` end-to-end via the existing `GildedRoseTest` suite (20 tests across all item types and boundary conditions)
- No per-updater unit tests in Phase 1 — the existing tests provide sufficient coverage, and the updaters have no public interface beyond what `GildedRose` exposes

**Prior art**: all existing tests follow the pattern of constructing an item with `GildedRose::of(name, quality, sellIn)`, calling `tick()` once, and asserting the resulting `quality` and `sellIn`. New tests (Phase 2) should follow this same pattern.

## Out of Scope

- **Conjured item behavior**: the `CONJURED` constant is defined in Phase 1 but the updater is not wired in. The six commented-out Conjured tests remain commented out and are addressed in Phase 2.
- **Per-updater unit tests**: not added in Phase 1; may be revisited if updater logic grows more complex.
- **Validation of initial quality bounds in `of()`**: the factory does not enforce that starting quality is within 0–50. This is a potential future improvement but is not in scope.
- **Changes to test files**: all tests are kept exactly as written.

## Further Notes

- The `readme.md` constraint ("do not alter the Item class") does not apply here since `GildedRose` itself is the item — there is no separate `Item` class in this implementation.
- Phase 2 work: add `ConjuredItemUpdater`, wire it in `resolveUpdater()`, and uncomment the six Conjured tests.
