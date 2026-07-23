=== Athletix ===
Contributors: athletix
Tags: sports, league, teams, players, standings, elementor, competition
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modular sports-league management: teams, players, matches, standings and
competitions, with a full Elementor integration.

== Description ==

Athletix is a modular framework for running sports leagues on WordPress.

Content types: Leagues, Seasons, Teams, Players, Matches and Divisions, with a
shared Sport taxonomy and secure meta boxes.

Engine: matches are processed through an event system that recomputes standings
(stored in a real, indexed table — not post meta), player statistics and
rankings. Standings sort by points, goal difference then goals for.

Competition: generate a balanced round-robin schedule (circle method, single or
double), and seed a single-elimination playoff bracket directly from the
standings, with correct byes for non-power-of-two fields.

REST API (namespace `athletix/v1`): `/standings`, `/teams`, `/players`,
`/matches`, with a filterable public-read permission.

Shortcodes:

* `[athletix_standings league="12" season="0"]`
* `[athletix_roster team="5" columns="3"]`
* `[athletix_schedule league="12" limit="20"]`
* `[athletix_match id="42"]`
* `[athletix_player id="7" stats="yes"]`
* `[athletix_bracket league="12" season="3"]`

Blocks: the same views are available as server-rendered Gutenberg blocks in the
"Athletix" inserter category (Standings, Roster, Schedule, Bracket, Match,
Player), each registered from block.json metadata with League/Season pickers —
the Season picker is scoped to the chosen League.

List screens: the Teams, Players and Matches admin lists gain sortable,
filterable meta columns (venue/founded/colour, team/position/number,
date/home/away/round/status) through the standard WordPress list-table hooks —
so they sort natively, filter by Sport/League/Season/Division from dropdowns,
and toggle via Screen Options. The columns themselves are admin-defined under
Athletix → Customize → List Columns: each column picks a list and a source
field, is renamed by its title and reordered by its Order attribute, so you
control which columns show and in what order — with the built-in field
catalogue used as the default until you change it.

Customize (Phase 1): an Athletix → Customize area where admins define the maths
behind the standings. "Standings Columns" and "Outcomes" are variables you
create with a free-form equation (e.g. Points `($w * 3) + $d`, Goal Difference
`$gf - $ga`, Win `$gf > $ga`) validated when saved. A column's sort priority
sets the default table order — every column is sortable — and a variable can be
scoped to one sport or left as the default for all. The soccer rules are seeded
on activation, so standings look and sort exactly as before until you change
them. Equations are evaluated by a safe built-in parser (arithmetic,
comparisons, round/min/max/abs) — never PHP eval().

Guided add screens: under the Athletix menu, "Add Team" ties a new team to a
sport (plus optional league/division), and "Add Player" binds a player to a
team and offers the position list that belongs to that team's sport — so the
form always matches the sport being entered. Both have an "Add Another" button
for fast batch entry: it saves over AJAX (no reload), keeps the shared context
(Add Team → sport/league/division; Add Player → team) while clearing the
per-item fields, and builds a running "added this session" list. With
JavaScript off the form posts normally and carries the context back.

Elementor: a dedicated "Athletix" category with League Table, Team Roster and
Match Schedule widgets, plus a Player Field dynamic tag. The Elementor layer
loads only when a compatible Elementor (3.5+) is active; without it the rest of
the plugin works normally.

== Installation ==

1. Upload the `athletix` folder to `/wp-content/plugins/`.
2. Activate the plugin. Custom tables are created on activation.
3. (Optional) `composer install` to enable the PHPUnit/PHPCS dev tooling.
4. Add leagues, teams, players and matches under the Athletix menu, then use
   the Competitions screen to generate schedules and playoffs.

== Frequently Asked Questions ==

= Does it require Elementor? =

No. Elementor is only needed for the drag-and-drop widgets and dynamic tags.

= Where are standings stored? =

In a dedicated custom table, recomputed from completed matches whenever a match
changes, so the table can never drift from the results.

== Changelog ==

= 2.0.0 =
* Complete rebuild on a modular, namespaced (PSR-4) architecture.
* Event-driven engines; standings/stats/relationships in custom tables.
* Competition module: round-robin scheduling and standings-seeded playoffs.
* REST API, shortcodes and Elementor widgets + dynamic tag.
