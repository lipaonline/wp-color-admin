<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CTWP_Admin_Filters {

	public function __construct() {
		add_action( 'restrict_manage_posts', array( $this, 'render_dropdowns' ), 10, 2 );
	}

	public function render_dropdowns( $post_type, $which ) {
		if ( $which !== 'top' ) {
			return;
		}
		foreach ( CTWP_Plugin::get_rules() as $rule ) {
			if ( $rule['post_type'] !== $post_type ) {
				continue;
			}
			if ( empty( $rule['filter'] ) ) {
				continue;
			}
			$tx = get_taxonomy( $rule['taxonomy'] );
			if ( ! $tx ) {
				continue;
			}

			$query_var = $tx->query_var ? $tx->query_var : $tx->name;
			$selected  = isset( $_GET[ $query_var ] ) ? sanitize_text_field( wp_unslash( $_GET[ $query_var ] ) ) : '';

			wp_dropdown_categories(
				array(
					/* translators: %s: taxonomy plural label */
					'show_option_all' => sprintf( __( 'All %s', 'color-the-wp' ), strtolower( $tx->labels->name ) ),
					'taxonomy'        => $tx->name,
					'name'            => $query_var,
					'value_field'     => 'slug',
					'selected'        => $selected,
					'hide_empty'      => false,
					'hierarchical'    => $tx->hierarchical,
					'show_count'      => false,
					'orderby'         => 'name',
				)
			);
		}
	}
}
