# Athletix — Leagues / Seasons / Divisions as Taxonomies

**Author:** Claude (Athletix working branch)
**Status:** In progress — destructive re-model (approved). Hard cutover: a slug cannot be both a post type and a taxonomy.
**Goal:** Model **Leagues, Seasons, Divisions** as taxonomies (like **Sports**), the SportsPress data model. Sports and these three are managed **only under Athletix**; their per-entity submenus are removed.

---

## 1. New data model

| Entity | Was | Becomes | Attached to (objects) |
|---|---|---|---|
| League (`ax_league`) | post type | **taxonomy** (hierarchical) | `ax_team`, `ax_player`, `ax_match` |
| Season (`ax_season`) | post type | **taxonomy** | `ax_team`, `ax_match` |
| Division (`ax_division`) | post type | **taxonomy** | `ax_team`, `ax_match` |
| Sport (`ax_sport`) | taxonomy (unchanged) | taxonomy | `ax_team`, `ax_player`, `ax_match` |
| Team / Player / Match | post types (unchanged) | post types | — |

- A **Match** carries one League term, one Season term, and optionally one Division term (as taxonomy assignments, not post-id meta).
- A **Team** carries a League term and optionally a Division term.
- Season↔League association (which league a season belongs to) is kept as **term meta** `_ax_season_league` (a league term id), optional.

### Keys changes

- `Keys::LEAGUE/SEASON/DIVISION` stay `'ax_league'/'ax_season'/'ax_division'` but move from the "post types" group to a new **taxonomies** group; `Keys::post_types()` drops them.
- The post-id meta keys `MATCH_LEAGUE`, `MATCH_SEASON`, `TEAM_LEAGUE`, `SEASON_LEAGUE`, `DIVISION_LEAGUE` are retired from the write path (kept as constants only for the one-time migration reader).

---

## 2. Storage impact (no table changes)

- **`{prefix}athletix_standings`** columns `league_id / season_id / division_id` now hold **term ids** instead of post ids. Same integer columns — no schema change.
- **`player_stats`** unaffected (keyed by player post id + season term id where used).
- **Match/team** league/season/division move from post meta to `wp_term_relationships` (native taxonomy storage).

---

## 3. Code changes (by area)

- **Registration:** `Taxonomies.php` registers `ax_league/ax_season/ax_division` (hierarchical, `show_in_menu => false`); `PostTypes.php` drops those three CPTs.
- **Repositories:** `LeagueRepository/SeasonRepository/DivisionRepository` become **term repositories** on a new `TermRepository` base (`all()`, `find()`, `name()`, `create()`), replacing the post-based `BaseRepository`.
- **Match layer:** `MatchRepository::details()` reads assigned terms; `completed($league,$season)` uses `tax_query`; `MatchEngine` reads terms on save.
- **Standings:** `StandingsEngine` scope args are term ids (no logic change); `MatchStatsMetaBox`, `Shortcodes`, `Blocks`, REST `ContentController/MobileController`, `Search\FilterEngine`, `Calendar`, `Reports`, `Elementor`, `Media` switch league/season lookups from `get_the_title($postId)` to `get_term($termId)->name`.
- **Meta boxes:** `MetaBoxManager` drops the league/season/division `post` selector fields for those entities; Team/Match editors get taxonomy meta boxes (native) plus, where useful, a single-term selector.
- **Backup:** export/import serialize term taxonomies + term meta and remap ids on restore.
- **Admin menu:** Sports + Leagues + Seasons + Divisions appear once, as submenus under the **Athletix** top-level (pointing at `edit-tags.php?taxonomy=…&post_type=…`); removed from Team/Player/Match menus (`show_in_menu => false` on every Athletix taxonomy, re-added centrally).

---

## 4. Data migration (one-time, on upgrade)

Guarded by a `db_version` bump. For each of league/season/division:

1. Create a term for every existing post of that type (name = title), remembering `old_post_id → new_term_id`.
2. Re-point `standings` rows: `league_id/season_id/division_id` post ids → the mapped term ids.
3. Re-assign matches/teams: read the old post-id meta, `wp_set_object_terms()` the mapped term, delete the old meta.
4. Copy `SEASON_LEAGUE` post meta → season **term meta**.
5. Move the old league/season/division posts to trash (kept, not hard-deleted, so nothing is lost if a rollback is needed).

Idempotent and safe to re-run; skips entities already migrated.

---

## 5. Phasing (each phase ends green)

1. **Registration + Keys + term repositories + admin menu** — taxonomies live, CPTs gone, managed under Athletix. Repos return terms.
2. **Match/standings/meta rewiring** — matches store terms; standings recompute by term.
3. **Consumers** — shortcodes, blocks, REST, search, calendar, reports, Elementor, media, backup.
4. **Migration routine + tests** — upgrade migration; unit/integration tests updated and added; regenerate `.pot`.

---

## 6. Tests

- **Unit:** term-repository behaviour (pure where possible); standings math unchanged.
- **Integration:** register taxonomies; assign a match league+season term; assert standings recompute keyed by term; migration remaps a sample post-based dataset to terms; menus expose the four taxonomies only under Athletix.
