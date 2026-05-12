<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once CTWP_DIR . 'includes/class-ctwp-settings.php';
require_once CTWP_DIR . 'includes/class-ctwp-admin-colors.php';
require_once CTWP_DIR . 'includes/class-ctwp-admin-filters.php';

class CTWP_Plugin {

	const OPTION_KEY          = 'ctwp_settings';
	const DEFAULT_PASTEL_MIX  = 0.78;
	const TEMPLATE_PSEUDO_TAX = '_wp_page_template';
	const TEMPLATE_DEFAULT    = 'default';

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new CTWP_Settings();
		new CTWP_Admin_Colors();
		new CTWP_Admin_Filters();
	}

	public static function get_rules() {
		$opt   = get_option( self::OPTION_KEY, array() );
		$rules = ( isset( $opt['rules'] ) && is_array( $opt['rules'] ) ) ? $opt['rules'] : array();
		/**
		 * Filter the list of color rules.
		 *
		 * @param array $rules Array of rules. Each rule has post_type, taxonomy, source,
		 *                     acf_field, rows, banner, filter, colors.
		 */
		return apply_filters( 'ctwp_rules', $rules );
	}

	public static function is_template_rule( $rule ) {
		return isset( $rule['taxonomy'] ) && $rule['taxonomy'] === self::TEMPLATE_PSEUDO_TAX;
	}

	public static function get_post_type_templates( $post_type ) {
		$theme = wp_get_theme();
		$templates = $theme ? $theme->get_page_templates( null, $post_type ) : array();
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}
		$templates = array_merge(
			array( self::TEMPLATE_DEFAULT => __( 'Default template', 'color-the-wp' ) ),
			$templates
		);
		/**
		 * Filter the list of templates shown for a post type.
		 *
		 * @param array  $templates Map of slug => human label. Includes 'default'.
		 * @param string $post_type Post type slug.
		 */
		return apply_filters( 'ctwp_post_type_templates', $templates, $post_type );
	}

	public static function get_post_template_key( $post_id ) {
		$slug = get_page_template_slug( $post_id );
		$key  = $slug ? $slug : self::TEMPLATE_DEFAULT;
		/**
		 * Filter the template key resolved for a given post.
		 *
		 * Useful to map to alternative sources (Beaver Themer location rules,
		 * Elementor templates, custom logic).
		 *
		 * @param string $key     Template slug, or 'default'.
		 * @param int    $post_id Post ID.
		 */
		return apply_filters( 'ctwp_post_template_key', $key, $post_id );
	}

	public static function get_pastel_mix() {
		$opt = get_option( self::OPTION_KEY, array() );
		$mix = ( isset( $opt['pastel_mix'] ) && is_numeric( $opt['pastel_mix'] ) )
			? max( 0.0, min( 1.0, (float) $opt['pastel_mix'] ) )
			: self::DEFAULT_PASTEL_MIX;
		/**
		 * Filter the pastel intensity used for color tinting.
		 *
		 * @param float $mix Value between 0 (raw color) and 1 (pure white).
		 */
		return (float) apply_filters( 'ctwp_pastel_mix', $mix );
	}

	public static function pastelize( $color, $mix = null ) {
		if ( $mix === null ) {
			$mix = self::get_pastel_mix();
		}
		$color = trim( (string) $color );
		$rgb   = null;

		if ( preg_match( '/^#([0-9a-f]{3})$/i', $color, $m ) ) {
			$rgb = array(
				hexdec( str_repeat( $m[1][0], 2 ) ),
				hexdec( str_repeat( $m[1][1], 2 ) ),
				hexdec( str_repeat( $m[1][2], 2 ) ),
			);
		} elseif ( preg_match( '/^#([0-9a-f]{6})$/i', $color, $m ) ) {
			$rgb = array(
				hexdec( substr( $m[1], 0, 2 ) ),
				hexdec( substr( $m[1], 2, 2 ) ),
				hexdec( substr( $m[1], 4, 2 ) ),
			);
		} elseif ( preg_match( '/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $color, $m ) ) {
			$rgb = array( (int) $m[1], (int) $m[2], (int) $m[3] );
		}

		if ( $rgb === null ) {
			return $color;
		}

		$mix = max( 0.0, min( 1.0, (float) $mix ) );
		foreach ( $rgb as &$c ) {
			$c = (int) round( $c + ( 255 - $c ) * $mix );
		}
		unset( $c );

		$hex = sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );
		/**
		 * Filter the final pastelized color string.
		 *
		 * @param string $hex      Pastelized hex color.
		 * @param string $original Original color passed in.
		 * @param float  $mix      Mix factor applied.
		 */
		return apply_filters( 'ctwp_pastelize_color', $hex, $color, $mix );
	}

	public static function sanitize_css_color( $val ) {
		$val = trim( (string) $val );
		if ( $val === '' ) {
			return '';
		}
		if ( preg_match( '/^#[a-f0-9]{3,8}$/i', $val ) ) {
			return $val;
		}
		if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\)$/i', $val ) ) {
			return $val;
		}
		if ( preg_match( '/^hsla?\(\s*\d{1,3}\s*,\s*[\d.]+%\s*,\s*[\d.]+%\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\)$/i', $val ) ) {
			return $val;
		}
		return '';
	}
}
