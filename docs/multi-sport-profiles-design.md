# Athletix — Multi-Sport Profiles Design

**Author:** Claude (Athletix working branch)
**Status:** Proposed design — not yet implemented. Ships behind a default `SoccerProfile`, so adoption is non-breaking.
**Related code:** `src/Engine/SportEngine.php`, `src/Taxonomies/Taxonomies.php`, `src/Meta/MatchStatsMetaBox.php`, `src/Meta/MetaBoxManager.php`, `src/Engine/StandingsCalculator.php`, `src/Engine/StandingsSorter.php`, `src/Data/Repositories/PlayerStatsRepository.php`, `src/Frontend/Shortcodes.php`, `src/Blocks/BlocksModule.php`, `src/Rest/*`.

---

## 1. Goal

Support **multiple sports in one installation** where each sport has its own fields, scoring,
statistics, standings rules and terminology — **without duplicating tables**. Views, shortcodes,
blocks, REST endpoints and Elementor widgets accept a **`sport` parameter**; that parameter selects
a **sport profile** which supplies the appropriate fields and logic. Everything else — storage,
CPTs, the event system — stays generic and shared.

### Principles

1. **One schema, sport as a parameter.** No per-sport tables or CPTs. Sport is data + strategy.
2. **Declarative profiles.** A sport is described once, in one object, read everywhere.
3. **Standard methodology.** Strategy + Registry + a resolver with precedence, extended via
   WordPress filters — the same pattern WordPress uses for post types/blocks and WooCommerce for
   product types.
4. **Extensible without core edits.** A new sport is registered through a filter.
5. **Non-breaking.** Soccer becomes the default profile encoding today's exact behavior.

---

## 2. What already exists

The groundwork is ~30% in place; this design builds on it rather than replacing it.

| Asset | Location | Relevance |
|---|---|---|
| `ax_sport` taxonomy on Team, Player, Match, **League** | `src/Taxonomies/Taxonomies.php:29` | Entities can already *carry* a sport. |
| `SportEngine` with `points()` / `tiebreakers()` behind `athletix/sport_*` filters | `src/Engine/SportEngine.php` | A proto-strategy to generalize. |
| `player_stats(player, metric, value, …)` | custom table | `metric` is a free string — **any** sport's metrics already fit, no schema change. |
| `StandingsCalculator` / `StandingsSorter` take injected points / tie-break chain | `src/Engine/` | Already parameterized; feed them from a profile. |

Hardcoded, soccer-specific spots to generalize: `MatchStatsMetaBox::METRICS`
(`goals/assists/yellow_cards/red_cards/minutes/saves`), the `MetaBoxManager` field schemas, and
`active_sport` as a single global string.

---

## 3. Core concept: the Sport Profile

One first-class object per sport that **declares its differences**. Everything else reads from it.

| Domain | What differs between sports |
|---|---|
| **Identity** | slug, label, icon |
| **Scoring** | win/draw/loss points (soccer) vs points-for/against (basketball) vs sets (volleyball) |
| **Result model** | single score, sets, innings, periods, OT/shootout; *are draws allowed?* |
| **Standings** | which columns show + their labels (GF/GA vs PF/PA), the tie-break chain |
| **Stat metrics** | soccer: goals/assists/cards; basketball: points/rebounds/assists/steals |
| **Player fields** | positions list; sport-specific meta (handedness, weight class, …) |
| **Terminology** | "Match" vs "Game" vs "Bout"; "Goals" vs "Points" |
| **Validation** | per-metric ranges, score constraints |

---

## 4. Architecture

```
Contracts\SportProfile            interface: slug(), label(), scoring(), metrics(),
                                  standingsColumns(), tiebreakers(), playerFields(),
                                  positions(), labels(), validateResult()

Sports\Profiles\SoccerProfile     concrete strategies (declarative config inside)
Sports\Profiles\BasketballProfile
Sports\Profiles\GenericProfile    Null-Object default — never fatal on an unknown sport

Sports\SportRegistry              register / get / all; fires `athletix/register_sports`
Sports\SportResolver              resolves a slug -> profile by precedence
Engine\SportEngine (evolve)       delegates points()/tiebreakers() to the resolved profile
```

### 4.1 The contract

```php
interface SportProfile {
    public function slug(): string;                  // 'soccer'
    public function label(): string;                 // 'Soccer'
    public function scoring(): array;                // ['win'=>3,'draw'=>1,'loss'=>0,'draws_allowed'=>true]
    public function metrics(): array;                // ['goals'=>['label'=>…,'max'=>50], 'assists'=>…]
    public function standingsColumns(): array;       // ordered [slug=>label], semantic mapping
    public function tiebreakers(): array;            // ['points','goal_difference','goals_for']
    public function playerFields(): array;           // extra meta schema for the player editor
    public function positions(): array;              // ['GK','DF','MF','FW']
    public function labels(): array;                 // ['match'=>'Match','score'=>'Goals']
    public function validateResult(array $result): array; // per-sport score validation
}
```

### 4.2 Registry (extensible, zero core edits)

```php
final class SportRegistry {
    /** @var SportProfile[] */
    private $profiles = [];

    public function boot(): void {
        $defaults = [
            'soccer'  => new Profiles\SoccerProfile(),
            'generic' => new Profiles\GenericProfile(),
        ];
        /** Third parties add sports here — no core edits required. */
        $this->profiles = apply_filters( 'athletix/register_sports', $defaults );
    }

    public function get( string $slug ): SportProfile {
        return $this->profiles[ $slug ] ?? $this->profiles['generic'];
    }

    /** @return SportProfile[] */
    public function all(): array { return $this->profiles; }
}
```

```php
// A whole new sport, from another plugin, with no core changes:
add_filter('athletix/register_sports', function (array $sports) {
    $sports['pickleball'] = new My\PickleballProfile();
    return $sports;
});
```

### 4.3 Resolver — the "pass a sport parameter" choke point

`SportResolver::resolve()` walks a precedence chain and **always** lands on a valid profile, so every
layer behaves consistently by calling one place.

```php
final class SportResolver {
    public function __construct( private SportRegistry $registry, private Config $config ) {}

    public function resolve( string $explicit = '', int $context_id = 0 ): SportProfile {
        $slug = $explicit
            ?: $this->term_slug( $context_id )                 // the entity's ax_sport
            ?: $this->term_slug( $this->league_of( $context_id ) ) // its league's ax_sport
            ?: (string) $this->config->get( 'active_sport', 'soccer' );

        return $this->registry->get( $slug ); // GenericProfile if unknown — never fatal
    }
}
```

**Precedence:**

1. **Explicit parameter** — `[athletix_standings sport="basketball"]`, a block attribute, REST `?sport=`, an Elementor control.
2. **The entity's own `ax_sport` term** — the match/team/player being rendered.
3. **Its League's `ax_sport` term** — a league is single-sport in practice.
4. **Global `active_sport`** setting.
5. **`GenericProfile`** (Null Object) — safe fallback.

---

## 5. How each layer becomes sport-aware

| Layer | Change |
|---|---|
| **Shortcodes** (`Frontend\Shortcodes`) | Add `sport` to every `shortcode_atts`; resolve → profile drives columns/labels/metrics passed to the template. |
| **Blocks** (`Blocks\BlocksModule`) | Add a `sport` attribute + a sport `SelectControl` in the editor. Render callbacks already delegate to shortcodes, so they inherit the behavior for free. |
| **REST** (`Rest\*Controller`) | Add a `sport` arg; shape response metric keys/labels from the profile. |
| **Elementor widgets** | A "Sport" `SELECT` control (options from `SportRegistry::all()`), default *auto/inherit*. |
| **Standings** (`Engine\StandingsCalculator` + `StandingsSorter`) | Already take `points`/`tiebreakers` — feed them from the profile. Column labels/visibility from `standingsColumns()`; the `Table` object renders whatever the profile declares. |
| **Stat entry** (`Meta\MatchStatsMetaBox`) | Replace the hardcoded `METRICS` const with `profile->metrics()` for the match's resolved sport. |
| **Player editor** (`Meta\MetaBoxManager`) | Merge `profile->playerFields()` / `positions()` into the schema for that player's sport. |
| **Validation** | Route results through `profile->validateResult()`. |

---

## 6. Data model — reuse, don't multiply

The hard constraint: **no new tables.**

- **`player_stats(player, metric, value, …)`** is already generic. Basketball `rebounds`, cricket
  `wickets` — all store as `metric` strings. **Zero change.**
- **`standings`** is the only table with fixed columns
  (`played/won/drawn/lost/goals_for/goals_against/points`). Two options, **both the same table**:

  - **(A) Semantic reuse — recommended.** Treat `goals_for/against` as generic *for/against*,
    `drawn` as `0` for no-draw sports; the profile supplies the *labels* ("PF/PA", hide "Drawn").
    Covers ~90% of sports with **no migration**.
  - **(B) One nullable `extra` JSON column** on `standings` for sports needing more aggregates
    (e.g. set ratio). Still one table, additive, nullable — no disruption to existing rows.

This is the main open decision (§9).

---

## 7. Backward compatibility & migration

- Ship **`SoccerProfile` as the default**, encoding today's exact behavior (3/1/0 points, the current
  goals/assists/cards metrics, the current tie-break chain). Existing sites are byte-for-byte
  unchanged.
- `active_sport` already defaults to `'soccer'` (`src/Plugin.php`), and `GenericProfile` guarantees an
  unknown slug never fatals.
- No data migration required for option (A); option (B) is a single additive, nullable column via the
  existing `dbDelta` schema installer.

---

## 8. Testing strategy

Fits the existing harness (`tests/Unit`, `tests/Integration`).

- **Unit (pure, no WordPress):** resolver precedence; each profile's scoring/tiebreakers/metrics;
  `GenericProfile` fallback for unknown slugs.
- **Integration:** `[athletix_standings sport="basketball"]` renders basketball columns; a basketball
  match records `points/rebounds`; **soccer output is unchanged** (regression lock).

---

## 9. Open decisions

1. **Standings storage** — semantic reuse **(A)** vs a nullable `extra` JSON column **(B)**.
   *Recommendation: A* (zero migration, covers most sports).
2. **Single-sport vs mixed leagues** — is a League always one sport (simplifies resolution and lets the
   league default cascade), or can one league span sports? *Recommendation: single-sport leagues.*
3. **First additional profile** — Basketball (points-based, no draws) is the cleanest contrast to
   Soccer for proving the abstraction end-to-end. *Recommendation: Basketball.*

Recommended Phase-1 defaults: **A + single-sport leagues + Basketball**.

---

## 10. Phased rollout

| Phase | Deliverable | Visible change |
|---|---|---|
| **1** | Contracts + registry + resolver + `SoccerProfile` + `GenericProfile`; refactor `SportEngine` to delegate. | None — pure foundation, fully green. |
| **2** | Thread `sport` through shortcodes → blocks → REST → Elementor (resolver-backed). | `sport` parameter accepted everywhere. |
| **3** | Metrics/fields become profile-driven (`MatchStatsMetaBox`, `MetaBoxManager`, standings labels). | Editors + tables adapt to sport. |
| **4** | Ship `BasketballProfile` end-to-end + regression tests. | Second sport works fully. |
| **5** | Settings UI: manage enabled sports; per-league default sport. | Admins configure sports. |

Each phase is independently shippable and leaves CI green; Phase 1 is invisible to end users because
Soccer stays the default.
