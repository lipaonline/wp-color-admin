# Color the WP

> 🇬🇧 English documentation: [README.md](README.md)

Extension WordPress qui colore les lignes des listes admin et les écrans d'édition selon les termes de taxonomie. Les couleurs sont automatiquement pastelisées pour que le texte reste lisible, quelle que soit la teinte choisie.

Conçue pour les sites qui gèrent des dizaines (ou centaines) de pages ou de custom post types et où l'on veut repérer les familles d'un coup d'œil — modèles de page, familles de page, statuts, catégories, ou n'importe quoi qu'on peut modéliser comme taxonomie.

## Fonctionnalités

- **Coloration des lignes** dans n'importe quelle liste admin (pages, articles, CPT) selon les termes assignés au post.
- **Coloration par template de page** — colore selon la méta WordPress native `_wp_page_template`. Pas besoin de créer une taxonomie.
- **Dropdown de filtre** dans la liste admin, par taxonomie ou par template de page.
- **Bandeau sur l'écran d'édition** indiquant les termes (ou le template) du post, avec la couleur associée.
- **Deux sources de couleur par règle de taxonomie :**
  - **Direct** — un color-picker WP par terme, défini dans les réglages du plugin.
  - **ACF** — lecture de la couleur depuis un champ ACF attaché à la taxonomie (un champ ACF Color Picker fonctionne très bien).
- **Pastelisation automatique** — toute couleur saturée est mélangée avec du blanc selon une intensité configurable, donc le texte reste lisible. Réglable de `0` (couleur brute) à `1` (blanc pur). Défaut : `0.78`.
- **Auto-découverte** — aucun post type, taxonomie ou template codé en dur. L'extension liste toutes les combinaisons (post_type × taxonomie) `show_ui` du site, plus les templates déclarés par le thème.

## Installation

1. Copier ce dossier dans `wp-content/plugins/wp-color-admin/`.
2. Activer l'extension depuis *Extensions* dans l'admin WordPress.
3. Aller dans *Réglages → Color the WP*.
4. Pour chaque paire (post type × taxonomie) qui t'intéresse, coche ce que tu veux afficher :
   - **Color list rows** — peindre chaque ligne avec la couleur du terme
   - **Show banner on edit screen** — badge coloré en haut de chaque écran d'édition
   - **Show filter dropdown in list** — ajoute un filtre par terme dans la barre d'outils de la liste
5. Choisis la source de couleur (Direct ou ACF), pick les couleurs ou renseigne le nom du champ ACF, sauvegarde.

## Sources de couleur

### Direct

Chaque terme de la taxonomie obtient un color-picker WP. Stocké dans l'option `ctwp_settings` du plugin. Idéal quand les couleurs sont stables et gérées par un admin.

### ACF

L'extension lit `get_field( <slug>, $term )` pour chaque terme. Fallback vers `get_term_meta()` si ACF n'est pas installé.

Cas d'usage : équipes éditoriales qui gèrent les couleurs directement sur chaque terme (Articles → Taxonomies → Modifier le terme → Champ couleur). Nécessite un groupe de champs ACF rattaché à la taxonomie, avec un champ de type *Color Picker*.

## Templates de page

Si ton thème déclare des templates personnalisés (via l'en-tête `Template Name:`), l'extension les détecte automatiquement et affiche une section dédiée **Page Template** dans les réglages, pour chaque post type concerné. Tu peux :

- Choisir une couleur par template (y compris pour le "Template par défaut" implicite).
- Activer la coloration des lignes, le bandeau d'édition et le dropdown de filtre par template — mêmes toggles que pour les règles de taxonomie.

Le filtre passe par le paramètre d'URL `?ctwp_tpl=<template-file.php>` et un `meta_query` sur `_wp_page_template`. Le filtre "Template par défaut" matche les posts sans template assigné (NOT EXISTS ou vide).

## Intensité du pastel

Les couleurs vives saisies par l'utilisateur sont mélangées avec du blanc selon le facteur `pastel_mix` (défaut `0.78`). Réglable depuis la page de réglages.

- `0.50` → teintes moyennement saturées
- `0.78` → pastels doux (défaut)
- `0.90` → lavis à peine visibles

La pastelisation est appliquée au rendu uniquement — tes couleurs sauvegardées ne sont jamais réécrites, donc tu peux changer l'intensité et revenir en arrière sans perte.

## Résolution de conflits

Si un post a des termes dans plusieurs taxonomies configurées, la première règle (dans l'ordre de sauvegarde) qui retourne une couleur non-vide gagne. Ce n'est pas configurable pour l'instant ; si tu as besoin d'une priorité explicite, ouvre une issue.

## Notes de compatibilité

- **Polylang** — le plugin filtre `post_translations` (taxonomie interne de Polylang en `show_ui=false`) pour garder la page de réglages propre. La taxonomie `language` reste affichée car Polylang la déclare visible.
- **Taxonomies custom enregistrées avec `show_ui=false`** sont masquées de la page de réglages volontairement.
- **Beaver Themer** — fonctionne sans config supplémentaire :
  - Le post type `fl-theme-layout` est auto-détecté, et ses taxonomies (`fl-theme-layout-type`, `fl-theme-layout-location`) peuvent être utilisées pour la coloration.
  - Les Themer Layouts enregistrés comme templates de page (via `theme_page_templates`) apparaissent dans la section Page Template au même titre que les templates de thème classiques.
  - **Limite connue** : les location rules de Beaver Themer ("All Singular Posts", "Pages with category X", etc.) ne sont *pas* reflétées. Le plugin ne voit que la sélection explicite par page stockée dans `_wp_page_template`.

## Hooks

### Filtres

| Hook | Signature | Usage |
|---|---|---|
| `ctwp_rules` | `array $rules` | Filtrer la liste des règles à la lecture. |
| `ctwp_pastel_mix` | `float $mix` | Outrepasser l'intensité du pastel configurée (0..1). |
| `ctwp_pastelize_color` | `string $hex, string $original, float $mix` | Remplacer entièrement l'algorithme de pastelisation. |
| `ctwp_color_for_term` | `string $color, WP_Term $term, array $rule` | Personnaliser la couleur d'un terme (par ex. lire depuis une autre méta). |
| `ctwp_post_template_key` | `string $slug, int $post_id` | Mapper un post sur une "identité template" différente (location rules Beaver Themer, Elementor, etc.). |
| `ctwp_resolved_color` | `array\|null $result, int $post_id, array $rules` | Override final de la couleur résolue pour un post. Retourner `null` pour skip. |
| `ctwp_excluded_taxonomies` | `array $excluded, string $post_type` | Filtrer les slugs de taxonomie masqués de la page de réglages. Défaut : `post_format`, `nav_menu`, `link_category`. |
| `ctwp_excluded_post_types` | `array $excluded` | Filtrer les slugs de post type masqués. Défaut : `attachment`. |
| `ctwp_post_type_templates` | `array $templates, string $post_type` | Ajouter/retirer des templates listés pour un post type. |
| `ctwp_row_selector` | `string $sprintf_pattern, WP_Post $post` | Outrepasser le sélecteur CSS d'une ligne. Défaut : `#post-%1$d, #post-%1$d > *`. |
| `ctwp_banner_html` | `string $html, array $context` | Remplacer le markup du bandeau. Context : `post`, `rule`, optionnel `term`, `label`, `color`. |

### Actions

| Hook | Quand |
|---|---|
| `ctwp_render_settings_after` | Après le formulaire de réglages, dans le wrap. Injecter du contenu additionnel ici. |

### Hooks WordPress core utiles

- `update_option_ctwp_settings` / `updated_option` — réagir à la sauvegarde des réglages.
- `default_option_ctwp_settings` — fournir une valeur par défaut si l'option n'est pas définie.

### Helpers publics

- `CTWP_Plugin::get_rules()` — retourne le tableau des règles.
- `CTWP_Plugin::pastelize( $color, $mix = null )` — pastelise une couleur hex/rgb.
- `CTWP_Plugin::get_pastel_mix()` — intensité du pastel courante (0..1).
- `CTWP_Plugin::get_post_type_templates( $post_type )` — `[ slug => label ]` incluant default.
- `CTWP_Plugin::get_post_template_key( $post_id )` — slug du template ou `'default'`.
- `CTWP_Plugin::is_template_rule( $rule )` — vérifie si une règle cible `_wp_page_template`.

## Prérequis

- WordPress 5.5+
- PHP 7.2+
- ACF est **optionnel** — seulement requis si tu utilises la source ACF.

## Licence

GPL-2.0-or-later. Même licence que WordPress core.
