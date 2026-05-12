=== Color the WP ===
Contributors: lipa
Tags: admin, taxonomy, color, acf, pages, custom post type
Requires at least: 5.5
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 0.1.0
License: GPLv2 or later

Colorize WP admin list rows (pages, posts, custom post types) based on taxonomy terms. Pick colors directly or read them from an ACF color field on each term.

== Description ==

Use it when you have many pages or custom post types and you want to spot families at a glance — page templates, page families, categories, statuses, anything you can model as a taxonomy.

Two color modes per rule:

* **Direct** — pick a color per term in the plugin settings.
* **ACF** — read the color from an ACF field attached to the taxonomy (an ACF Color Picker field works best). Falls back to `get_term_meta()` if ACF is not installed.

A small banner on the edit screen tells you which term(s) the current post belongs to, in the matching color.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` and activate it.
2. Go to *Settings → Color the WP*.
3. Enable the (post type, taxonomy) pairs you want to colorize, pick a mode (Direct or ACF), and save.

== Notes ==

* Use pastel-ish colors for direct mode — saturated colors make rows hard to read.
* For ACF mode, the field name is the field slug (e.g. `color`), and the field group must be tied to the taxonomy.
* If a post has terms in multiple configured taxonomies, the first matching rule (in plugin order) wins.

== Changelog ==

= 0.1.0 =
* Initial release.
