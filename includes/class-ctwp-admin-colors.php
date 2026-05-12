<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CTWP_Admin_Colors {

	private $rules_by_post_type = array();

	public function __construct() {
		add_action( 'current_screen', array( $this, 'maybe_init' ) );
	}

	public function maybe_init( $screen ) {
		if ( ! $screen || empty( $screen->post_type ) ) {
			return;
		}
		$rules = $this->get_rules_for_post_type( $screen->post_type );
		if ( empty( $rules ) ) {
			return;
		}
		$this->rules_by_post_type[ $screen->post_type ] = $rules;

		if ( $screen->base === 'edit' ) {
			add_action( 'admin_head', array( $this, 'output_list_styles' ) );
		}
		if ( $screen->base === 'post' ) {
			add_action( 'admin_head', array( $this, 'output_edit_styles' ) );
			add_action( 'edit_form_top', array( $this, 'output_edit_banner' ) );
		}
	}

	private function get_rules_for_post_type( $post_type ) {
		$out = array();
		foreach ( CTWP_Plugin::get_rules() as $rule ) {
			if ( $rule['post_type'] === $post_type ) {
				$out[] = $rule;
			}
		}
		return $out;
	}

	public function output_list_styles() {
		global $wp_query;
		if ( empty( $wp_query->posts ) ) {
			return;
		}
		$screen = get_current_screen();
		$rules  = isset( $this->rules_by_post_type[ $screen->post_type ] ) ? $this->rules_by_post_type[ $screen->post_type ] : array();
		$rules  = array_values(
			array_filter(
				$rules,
				static function ( $r ) {
					return ! empty( $r['rows'] );
				}
			)
		);
		if ( empty( $rules ) ) {
			return;
		}

		$css = '';
		foreach ( $wp_query->posts as $post ) {
			$resolved = $this->resolve_color_for_post( $post->ID, $rules );
			if ( ! $resolved ) {
				continue;
			}
			$color = CTWP_Plugin::sanitize_css_color( $resolved['color'] );
			if ( $color === '' ) {
				continue;
			}
			$tinted = CTWP_Plugin::pastelize( $color );
			$css   .= sprintf(
				"#post-%1\$d, #post-%1\$d > * { background-color: %2\$s !important; }\n",
				(int) $post->ID,
				$tinted
			);
		}
		if ( $css !== '' ) {
			echo "<style id=\"ctwp-list-colors\">\n" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function output_edit_styles() {
		echo '<style id="ctwp-edit-styles">'
			. '.ctwp-edit-banner{padding:10px 14px;margin:12px 0;border-radius:4px;border:1px solid rgba(0,0,0,.08);font-size:13px;line-height:1.5;}'
			. '.ctwp-edit-banner .ctwp-bb-label{opacity:.75;margin-right:6px;}'
			. '.ctwp-edit-banner + .ctwp-edit-banner{margin-top:-4px;}'
			. '</style>';
	}

	public function output_edit_banner() {
		global $post;
		if ( ! $post ) {
			return;
		}
		$screen = get_current_screen();
		$rules  = isset( $this->rules_by_post_type[ $screen->post_type ] ) ? $this->rules_by_post_type[ $screen->post_type ] : array();

		foreach ( $rules as $rule ) {
			if ( empty( $rule['banner'] ) ) {
				continue;
			}
			$terms = wp_get_object_terms( $post->ID, $rule['taxonomy'] );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			$tx       = get_taxonomy( $rule['taxonomy'] );
			$tx_label = $tx ? $tx->labels->singular_name : $rule['taxonomy'];

			foreach ( $terms as $term ) {
				$color  = CTWP_Plugin::sanitize_css_color( $this->color_for_term( $term, $rule ) );
				$tinted = $color !== '' ? CTWP_Plugin::pastelize( $color ) : '';
				$style  = $tinted !== '' ? 'background-color:' . $tinted . ';' : '';
				echo '<div class="ctwp-edit-banner" style="' . esc_attr( $style ) . '">'
					. '<span class="ctwp-bb-label">' . esc_html( $tx_label ) . ' :</span>'
					. '<strong>' . esc_html( $term->name ) . '</strong>'
					. '</div>';
			}
		}
	}

	private function resolve_color_for_post( $post_id, $rules ) {
		foreach ( $rules as $rule ) {
			$terms = wp_get_object_terms( $post_id, $rule['taxonomy'] );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$c = $this->color_for_term( $term, $rule );
				if ( $c !== '' ) {
					return array(
						'color' => $c,
						'term'  => $term,
						'rule'  => $rule,
					);
				}
			}
		}
		return null;
	}

	private function color_for_term( $term, $rule ) {
		if ( $rule['source'] === 'direct' ) {
			return isset( $rule['colors'][ $term->term_id ] ) ? $rule['colors'][ $term->term_id ] : '';
		}
		if ( $rule['source'] === 'acf' && ! empty( $rule['acf_field'] ) ) {
			if ( function_exists( 'get_field' ) ) {
				$val = get_field( $rule['acf_field'], $term );
				if ( is_string( $val ) && $val !== '' ) {
					return $val;
				}
			}
			$val = get_term_meta( $term->term_id, $rule['acf_field'], true );
			if ( is_string( $val ) && $val !== '' ) {
				return $val;
			}
		}
		return '';
	}
}
