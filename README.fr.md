# Color the WP

> 🇬🇧 English documentation: [README.md](README.md)

Extension WordPress qui colore les lignes des listes admin et les écrans d'édition selon les termes de taxonomie. Les couleurs sont automatiquement pastelisées pour que le texte reste lisible, quelle que soit la teinte choisie.

Conçue pour les sites qui gèrent des dizaines (ou centaines) de pages ou de custom post types et où l'on veut repérer les familles d'un coup d'œil — modèles de page, familles de page, statuts, catégories, ou n'importe quoi qu'on peut modéliser comme taxonomie.

## Fonctionnalités

- **Coloration des lignes** dans n'importe quelle liste admin (pages, articles, CPT) selon les termes assignés au post.
- **Dropdown de filtre** dans la liste admin, par taxonomie.
- **Bandeau sur l'écran d'édition** indiquant les termes du post, avec la couleur associée.
- **Deux sources de couleur par règle :**
  - **Direct** — un color-picker WP par terme, défini dans les réglages du plugin.
  - **ACF** — lecture de la couleur depuis un champ ACF attaché à la taxonomie (un champ ACF Color Picker fonctionne très bien).
- **Pastelisation automatique** — toute couleur saturée est mélangée avec du blanc selon une intensité configurable, donc le texte reste lisible. Réglable de `0` (couleur brute) à `1` (blanc pur). Défaut : `0.78`.
- **Auto-découverte** — aucun post type ou taxonomie codé en dur. L'extension liste toutes les combinaisons (post_type × taxonomie) `show_ui` du site.

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

## Hooks

- `ctwp_settings` (option) — la config stockée.
- `CTWP_Plugin::pastelize( $color, $mix = null )` — helper statique public.
- `CTWP_Plugin::get_rules()` — helper statique public qui retourne le tableau des règles brut.

Aucun filtre n'est exposé pour l'instant ; si tu en as besoin, ouvre une issue.

## Prérequis

- WordPress 5.5+
- PHP 7.2+
- ACF est **optionnel** — seulement requis si tu utilises la source ACF.

## Licence

GPL-2.0-or-later. Même licence que WordPress core.
