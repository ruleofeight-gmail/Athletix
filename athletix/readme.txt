=== Athletix ===
Contributors: athletix
Tags: sports, fitness, athletes, elementor, teams, events
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sports & fitness toolkit with a full Elementor integration: athlete, team and event management plus custom widgets and dynamic tags.

== Description ==

Athletix adds three custom post types — Athletes, Teams and Events — together with Sport and Season taxonomies, and ships a complete Elementor integration:

* **Athlete Card** widget — photo, name and stats for a single athlete.
* **Team Roster** widget — responsive grid of athletes, optionally filtered by sport.
* **Event Schedule** widget — upcoming fixtures or past results as a styled table.
* **Athlete Field** dynamic tag — bind athlete meta (position, number, height, weight, country, DOB) into any Elementor element.
* A dedicated **Athletix** panel category and dynamic-tags group.

The Elementor pieces load only when a compatible version of Elementor (3.5.0+) is active; without it, the post types and admin still work and a dismissible notice explains what is missing.

== Installation ==

1. Upload the `athletix` folder to `/wp-content/plugins/`.
2. Activate the plugin through the *Plugins* screen in WordPress.
3. (Optional) Install and activate Elementor to use the widgets and dynamic tags.
4. Add athletes, teams and events under the **Athletix** menu.

== Frequently Asked Questions ==

= Does it require Elementor? =

No. The content types and admin work standalone. Elementor is only needed for the drag-and-drop widgets and dynamic tags.

= Where are athlete stats stored? =

As post meta on the athlete, editable from the *Athlete Details* meta box.

== Changelog ==

= 1.0.0 =
* Initial release: Athlete/Team/Event post types, taxonomies, meta boxes, three Elementor widgets and the Athlete Field dynamic tag.
