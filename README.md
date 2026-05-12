# Color the WP

> 🇫🇷 Documentation française : [README.fr.md](README.fr.md)

A WordPress plugin that colorizes admin list rows and edit-screen banners based on taxonomy terms. Auto-pastelizes whatever color you pick so rows stay readable.

Built for sites where you manage dozens (or hundreds) of pages or custom post types and want to spot families at a glance — page templates, page families, statuses, categories, or anything else you can model as a taxonomy.

## Features

- **Row coloring** on any admin list (pages, posts, custom post types) based on the terms attached to each post.
- **Page-template coloring** — color rows by the native WordPress `_wp_page_template` meta. No taxonomy needed.
- **Filter dropdown** in the admin list, per taxonomy or per page template.
- **Edit-screen banner** showing which terms (or which template) the current post belongs to, in the matching color.
- **Two color sources per taxonomy rule:**
  - **Direct** — pick a color per term in the plugin settings (WP color picker).
  - **ACF** — read the color from an ACF field attached to the taxonomy (an ACF Color Picker field works best).
- **Auto-pastelization** — any saturated color gets mixed with white at a configurable intensity, so text stays readable. Tunable from `0` (raw color) to `1` (pure white). Default: `0.78`.
- **Auto-discovery** — no hardcoded post types, taxonomies, or templates. The plugin lists every `show_ui` post type × taxonomy combo, plus any page templates declared in your theme.

## Installation

1. Copy this folder to `wp-content/plugins/wp-color-admin/`.
2. Activate the plugin from *Plugins* in your WordPress admin.
3. Open *Settings → Color the WP*.
4. For each (post type × taxonomy) pair you care about, toggle what you want to show:
   - **Color list rows** — paint each row with the term's color
   - **Show banner on edit screen** — colored badge at the top of each post edit screen
   - **Show filter dropdown in list** — adds a term filter to the admin list toolbar
5. Choose the color source (Direct or ACF), pick colors or set the ACF field name, save.

## Color sources

### Direct

Each term of the taxonomy gets a WordPress color picker. Stored in the plugin's option (`ctwp_settings`). Best when colors are stable and managed by an admin.

### ACF

The plugin reads `get_field( <slug>, $term )` for each term. Falls back to `get_term_meta()` if ACF isn't installed.

Use case: editorial teams who want to manage colors directly on each term (Posts → Taxonomies → Edit term → Color field). Requires an ACF field group attached to the taxonomy, with a field of type *Color Picker*.

## Page templates

If your theme declares custom page templates (with the `Template Name:` header), the plugin auto-detects them and shows a dedicated **Page Template** section in the settings for each affected post type. You can:

- Pick a color per template (including the implicit "Default template").
- Color list rows by template, show a template banner on the edit screen, and add a "filter by template" dropdown to the admin list — same toggles as taxonomy rules.

Filtering uses the URL parameter `?ctwp_tpl=<template-file.php>` and a `meta_query` against `_wp_page_template`. The "Default template" filter matches posts with no template assigned (NOT EXISTS or empty).

## Pastel intensity

Bright user colors are mixed with white at the `pastel_mix` factor (defaults to `0.78`). Adjustable in the settings page.

- `0.50` → mid-saturated tints
- `0.78` → soft pastels (default)
- `0.90` → barely-visible washes

Pastelization happens at render time only — your saved colors are never overwritten, so you can change the intensity and revert anytime.

## Conflict resolution

If a post belongs to terms in multiple configured taxonomies, the first rule (in saved order) that resolves a non-empty color wins. For now this isn't configurable; if you need explicit priority, open an issue.

## Compatibility notes

- **Polylang** — the plugin filters out `post_translations` (Polylang's `show_ui=false` internal taxonomy) so the settings page stays clean. The `language` taxonomy is still shown because Polylang declares it visible.
- **Custom taxonomies registered with `show_ui=false`** are hidden from the settings page on purpose.
- **Beaver Themer** — works without any extra setup:
  - The `fl-theme-layout` post type is auto-detected, and its taxonomies (`fl-theme-layout-type`, `fl-theme-layout-location`) can be used for coloring.
  - Themer Layouts that get registered as page templates (via `theme_page_templates`) appear in the Page Template section like regular theme templates.
  - **Caveat**: Beaver Themer location rules ("All Singular Posts", "Pages with category X", etc.) are *not* reflected. The plugin only sees the explicit per-page template selection stored in `_wp_page_template`.

## Hooks

- `ctwp_settings` (option) — the stored config.
- `CTWP_Plugin::pastelize( $color, $mix = null )` — public static helper.
- `CTWP_Plugin::get_rules()` — public static helper that returns the raw rules array.

No filters are currently exposed; if you need one, open an issue.

## Requirements

- WordPress 5.5+
- PHP 7.2+
- ACF is **optional** — only required if you use the ACF color source.

## License

GPL-2.0-or-later. Same as WordPress core.
