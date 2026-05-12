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
		$opt = get_option( self::OPTION_KEY, array() );
		return ( isset( $opt['rules'] ) && is_array( $opt['rules'] ) ) ? $opt['rules'] : array();
	}

	public static function is_template_rule( $rule ) {
		return isset( $rule['taxonomy'] ) && $rule['taxonomy'] === self::TEMPLATE_PSEUDO_TAX;
	}

	public static function get_post_type_templates( $post_type ) {
		$theme = wp_get_theme();
		if ( ! $theme ) {
			return array();
		}
		$templates = $theme->get_page_templates( null, $post_type );
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}
		return array_merge(
			array( self::TEMPLATE_DEFAULT => __( 'Default template', 'color-the-wp' ) ),
			$templates
		);
	}

	public static function get_post_template_key( $post_id ) {
		$slug = get_page_template_slug( $post_id );
		if ( ! $slug ) {
			return self::TEMPLATE_DEFAULT;
		}
		return $slug;
	}

	public static function get_pastel_mix() {
		$opt = get_option( self::OPTION_KEY, array() );
		if ( isset( $opt['pastel_mix'] ) && is_numeric( $opt['pastel_mix'] ) ) {
			return max( 0.0, min( 1.0, (float) $opt['pastel_mix'] ) );
		}
		return self::DEFAULT_PASTEL_MIX;
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

		return sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );
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
