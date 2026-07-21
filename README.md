# Athletix

A modular **sports-league management** plugin for WordPress with a full **Elementor** integration.

This repository contains **Athletix 2.0** — a ground-up rebuild converted from a
ChatGPT-authored draft spec (see [`docs/athletix-2.0-review.md`](docs/athletix-2.0-review.md)
for the original code review that guided the rewrite).

## Highlights

- **Modular architecture** — PSR-4 autoloading, namespaced code under `Athletix\`,
  a service container and a hook-based module registry (`athletix/modules`).
  Modules self-register; adding one never touches the bootstrap or autoloader.
- **Real data layer** — standings, player stats and relationships live in
  indexed custom tables, not serialized post meta.
- **Event-driven engines** — a match save recomputes standings, statistics and
  rankings; the standings/schedule/bracket math is pure and **unit-tested**.
- **Competition** — round-robin scheduling that persists real matches, correctly
  seeded single-elimination brackets with byes, and standings-seeded playoffs.
- **REST API** (`athletix/v1`), **shortcodes**, and **Elementor** widgets +
  dynamic tag.
- **Platform modules** — security/roles/audit, dashboard, search, import/export,
  calendar (iCal), notifications, analytics, media, reports, automation,
  membership and a PCI-safe payment ledger.

## Structure

```
athletix/
├── athletix.php            # Bootstrap (constants, autoloader, lifecycle)
├── uninstall.php           # Opt-in data removal
├── composer.json           # PSR-4 + PHPUnit/PHPCS
├── phpunit.xml.dist
├── src/                    # Namespaced source (80 classes / 19 modules)
│   ├── Plugin.php          # Container + module registry
│   ├── Core/               # Config, Events, Cache, Logger, Validator, lifecycle
│   ├── Support/            # Autoloader, Keys (single source of truth)
│   ├── PostTypes/ Taxonomies/ Meta/
│   ├── Data/               # Schema + repositories (incl. custom tables)
│   ├── Engine/             # Sport/Match/Standings/Statistics/Ranking + calculators
│   ├── Competition/        # Divisions, scheduler, brackets, playoffs, tournaments
│   ├── Rest/ Frontend/ Elementor/
│   ├── Security/ Dashboard/ Search/ ImportExport/ Calendar/
│   ├── Notifications/ Analytics/ Media/ Reports/ Automation/
│   └── Membership/ Payments/
├── templates/              # Escaped front-end partials
├── tests/                  # PHPUnit (standings, schedule, bracket)
└── assets/                 # CSS
```

## Shortcodes

| Shortcode | Purpose |
|---|---|
| `[athletix_standings league="12"]` | League table |
| `[athletix_roster team="5" columns="3"]` | Team roster grid |
| `[athletix_schedule league="12"]` | Fixtures / results |
| `[athletix_leaderboard metric="goals"]` | Player leaderboard |
| `[athletix_report league="12"]` | Standings + top scorers |
| `[athletix_gallery team="5"]` | Team image gallery |
| `[athletix_search]` | Search teams & players |
| `[athletix_register_team]` | Front-end team registration |

## Development

```bash
cd athletix
composer install               # optional: enables PHPUnit + PHPCS
composer test                  # pure unit tests (standings/schedule/bracket math)
composer lint                  # WordPress Coding Standards

# WordPress integration tests (need a test DB + the WP test suite):
bin/install-wp-tests.sh wordpress_test root '' localhost latest
composer test:integration      # schema/tables, repositories, standings recompute
```

Every PHP file passes `php -l`; the full plugin boots with all modules, the
domain algorithms are covered by fast unit tests, and the WordPress-backed
behaviour (custom tables, repositories, the match→standings event flow) is
covered by integration tests.

## Installation

1. Copy the `athletix/` folder into `wp-content/plugins/`.
2. Activate **Athletix** (custom tables are created on activation).
3. (Optional) Activate Elementor for the widgets and dynamic tag.
4. Add leagues, teams, players and matches under the **Athletix** menu; use the
   **Competitions** screen to generate schedules and playoffs.
```
