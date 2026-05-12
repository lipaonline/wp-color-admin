<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CTWP_Admin_Filters {

	const TPL_QUERY_VAR = 'ctwp_tpl';

	public function __construct() {
		add_action( 'restrict_manage_posts', array( $this, 'render_dropdowns' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'apply_template_filter' ) );
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

			if ( CTWP_Plugin::is_template_rule( $rule ) ) {
				$this->render_template_dropdown( $post_type );
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

	private function render_template_dropdown( $post_type ) {
		$templates = CTWP_Plugin::get_post_type_templates( $post_type );
		if ( count( $templates ) <= 1 ) {
			return;
		}
		$selected = isset( $_GET[ self::TPL_QUERY_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::TPL_QUERY_VAR ] ) ) : '';
		?>
		<select name="<?php echo esc_attr( self::TPL_QUERY_VAR ); ?>">
			<option value=""><?php esc_html_e( 'All templates', 'color-the-wp' ); ?></option>
			<?php foreach ( $templates as $slug => $label ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $selected, $slug ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function apply_template_filter( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( empty( $_GET[ self::TPL_QUERY_VAR ] ) ) {
			return;
		}
		$slug = sanitize_text_field( wp_unslash( $_GET[ self::TPL_QUERY_VAR ] ) );
		if ( $slug === '' ) {
			return;
		}
		if ( $slug === CTWP_Plugin::TEMPLATE_DEFAULT ) {
			$query->set(
				'meta_query',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_wp_page_template',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_wp_page_template',
						'value'   => array( '', 'default' ),
						'compare' => 'IN',
					),
				)
			);
			return;
		}
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => '_wp_page_template',
					'value' => $slug,
				),
			)
		);
	}
}
