# Athletix — companion theme

A lightweight **classic WordPress theme** that ships Athletix-aware templates so
teams, players and matches look polished out of the box — no Elementor or
page-building required.

## What it provides

- Standard theme templates: `index`, `single`, `archive`, `page`, `404`,
  `header`, `footer`, plus `functions.php` and `style.css`.
- **Athletix templates**: `single-ax_team.php`, `single-ax_player.php`,
  `single-ax_match.php`, `archive-ax_team.php`, `archive-ax_player.php`.
- Uses the plugin's shortcodes (roster, form, schedule, player report, match
  card) — guarded by `athletix_theme_shortcode()` so the theme still works if
  the plugin is deactivated.

## Relationship to the plugin

You do **not** need this theme — the Athletix plugin also ships the same single/
archive layouts via a `template_include` fallback that works with *any* theme.
Use this theme when you want a clean, ready-made sporting look without designing
templates yourself; use your own theme (with the plugin's fallback) when you
have an existing design.

## Install

1. Copy the `athletix-theme/` folder into `wp-content/themes/`.
2. Activate **Athletix** under *Appearance → Themes*.
3. Activate the Athletix plugin for the sports templates to populate.

## Development

```bash
# from the repo root, using the plugin's dev tooling:
athletix/vendor/bin/phpcs --standard=athletix-theme/phpcs.xml.dist athletix-theme
```

All theme PHP passes `php -l` and the bundled WordPress Coding Standards ruleset.
