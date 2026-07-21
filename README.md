# Athletix

A WordPress plugin — a sports & fitness toolkit with a **full Elementor integration**.

## What's inside

The plugin lives in [`athletix/`](athletix/) and is a drop-in WordPress plugin.

### WordPress side
- **Custom post types**: Athletes, Teams, Events (fixtures/results)
- **Taxonomies**: Sport, Season (shared across the types)
- **Meta boxes**: athlete stats (position, number, height, weight, country, DOB) and event details (date, location, home/away, score) — nonce-protected, capability-checked and sanitized
- **REST-enabled** post types and clean permalinks
- **Uninstall cleanup** that removes plugin content on deletion

### Elementor integration
- Dedicated **Athletix** panel category and dynamic-tags group
- **Athlete Card** widget — photo, name and stats, with content + style controls
- **Team Roster** widget — responsive grid, filterable by sport, responsive gap control
- **Event Schedule** widget — upcoming fixtures or past results as a styled, mobile-friendly table
- **Athlete Field** dynamic tag — binds athlete meta into any text-capable Elementor control
- Version/activation guards: Elementor pieces load only when Elementor ≥ 3.5.0 is active, with admin notices otherwise

## Structure

```
athletix/
├── athletix.php                 # Main plugin file (headers, constants, bootstrap)
├── uninstall.php                # Cleanup on delete
├── readme.txt                   # WordPress.org-style readme
├── includes/
│   ├── class-athletix.php       # Singleton loader
│   ├── class-meta-boxes.php     # Custom fields + secure save
│   ├── class-assets.php         # Front-end CSS registration
│   ├── class-elementor.php      # Elementor bootstrapper + guards
│   ├── post-types/
│   │   └── class-post-types.php # CPTs + taxonomies
│   └── elementor/
│       ├── widgets/             # Athlete Card, Team Roster, Event Schedule
│       └── tags/                # Athlete Field dynamic tag
├── assets/css/athletix.css      # Widget styles
└── languages/                   # i18n
```

## Installation

1. Copy the `athletix/` folder into `wp-content/plugins/`.
2. Activate **Athletix** from the Plugins screen.
3. (Optional) Activate Elementor to use the widgets and dynamic tags.
4. Add content under the **Athletix** admin menu.

## Development notes

- Text domain: `athletix`; all user-facing strings are translation-ready.
- Coding follows WordPress standards: escaping on output, sanitization on input, nonces on saves, and capability checks.
- All PHP passes `php -l` with no syntax errors.
