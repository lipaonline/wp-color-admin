<?php
/**
 * Plugin Name: Color the WP
 * Description: Colorize WP admin list rows (and edit-screen banners) based on taxonomy terms. Pick colors directly or read them from an ACF field on each term.
 * Version: 0.1.0
 * Author: Lipa
 * License: GPL-2.0-or-later
 * Text Domain: color-the-wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CTWP_VERSION', '0.1.0' );
define( 'CTWP_FILE', __FILE__ );
define( 'CTWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTWP_URL', plugin_dir_url( __FILE__ ) );

require_once CTWP_DIR . 'includes/class-ctwp-plugin.php';

CTWP_Plugin::instance();
