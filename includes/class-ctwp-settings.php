<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CTWP_Settings {

	const SLUG = 'ctwp-settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function register_menu() {
		add_options_page(
			__( 'Color the WP', 'color-the-wp' ),
			__( 'Color the WP', 'color-the-wp' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'ctwp_group',
			CTWP_Plugin::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	public function enqueue( $hook ) {
		if ( $hook !== 'settings_page_' . self::SLUG ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'ctwp-settings', CTWP_URL . 'assets/css/settings.css', array(), CTWP_VERSION );
		wp_enqueue_script(
			'ctwp-settings',
			CTWP_URL . 'assets/js/settings.js',
			array( 'jquery', 'wp-color-picker' ),
			CTWP_VERSION,
			true
		);
	}

	public function sanitize( $input ) {
		$output = array(
			'pastel_mix' => CTWP_Plugin::DEFAULT_PASTEL_MIX,
			'rules'      => array(),
		);
		if ( ! is_array( $input ) ) {
			return $output;
		}
		if ( isset( $input['pastel_mix'] ) && is_numeric( $input['pastel_mix'] ) ) {
			$output['pastel_mix'] = max( 0.0, min( 1.0, (float) $input['pastel_mix'] ) );
		}
		if ( empty( $input['rules'] ) ) {
			return $output;
		}

		foreach ( $input['rules'] as $rule ) {
			if ( empty( $rule['post_type'] ) || empty( $rule['taxonomy'] ) ) {
				continue;
			}
			$clean = array(
				'post_type' => sanitize_key( $rule['post_type'] ),
				'taxonomy'  => sanitize_key( $rule['taxonomy'] ),
				'source'    => ( isset( $rule['source'] ) && in_array( $rule['source'], array( 'direct', 'acf' ), true ) ) ? $rule['source'] : 'direct',
				'acf_field' => isset( $rule['acf_field'] ) ? sanitize_key( $rule['acf_field'] ) : '',
				'rows'      => ! empty( $rule['rows'] ),
				'banner'    => ! empty( $rule['banner'] ),
				'filter'    => ! empty( $rule['filter'] ),
				'colors'    => array(),
			);
			$is_template = $clean['taxonomy'] === CTWP_Plugin::TEMPLATE_PSEUDO_TAX;
			if ( ! empty( $rule['colors'] ) && is_array( $rule['colors'] ) ) {
				foreach ( $rule['colors'] as $key => $color ) {
					$clean_color = CTWP_Plugin::sanitize_css_color( $color );
					if ( $clean_color === '' ) {
						continue;
					}
					if ( $is_template ) {
						$clean_key = sanitize_text_field( $key );
						if ( $clean_key !== '' ) {
							$clean['colors'][ $clean_key ] = $clean_color;
						}
					} else {
						$clean['colors'][ (int) $key ] = $clean_color;
					}
				}
			}

			$has_toggle = $clean['rows'] || $clean['banner'] || $clean['filter'];
			$has_data   = ! empty( $clean['colors'] ) || $clean['acf_field'] !== '';
			if ( ! $has_toggle && ! $has_data ) {
				continue;
			}
			if ( ! $has_toggle && $has_data ) {
				$clean['rows'] = true;
			}
			$output['rules'][] = $clean;
		}
		return $output;
	}

	public function render_page() {
		$settings = get_option( CTWP_Plugin::OPTION_KEY, array( 'rules' => array() ) );
		$by_key   = array();
		foreach ( ( isset( $settings['rules'] ) ? $settings['rules'] : array() ) as $rule ) {
			$by_key[ $rule['post_type'] . '|' . $rule['taxonomy'] ] = $rule;
		}

		$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
		/**
		 * Filter the list of post type slugs excluded from the settings page.
		 *
		 * @param array $excluded Post type slugs to hide.
		 */
		$excluded_pt = apply_filters( 'ctwp_excluded_post_types', array( 'attachment' ) );
		foreach ( $excluded_pt as $slug ) {
			unset( $post_types[ $slug ] );
		}
		?>
		<div class="wrap ctwp-settings">
			<h1><?php esc_html_e( 'Color the WP', 'color-the-wp' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Colorize admin list rows and edit-screen banners based on taxonomy terms. Pick colors directly, or read them from an ACF color field on each term.', 'color-the-wp' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'ctwp_group' ); ?>

				<?php $pastel_mix = isset( $settings['pastel_mix'] ) ? $settings['pastel_mix'] : CTWP_Plugin::DEFAULT_PASTEL_MIX; ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ctwp-pastel-mix"><?php esc_html_e( 'Pastel intensity', 'color-the-wp' ); ?></label></th>
						<td>
							<input type="number" id="ctwp-pastel-mix" name="ctwp_settings[pastel_mix]" value="<?php echo esc_attr( $pastel_mix ); ?>" min="0" max="1" step="0.01" class="small-text">
							<p class="description"><?php esc_html_e( '0 = raw saturated color, 1 = pure white. Default: 0.78.', 'color-the-wp' ); ?></p>
						</td>
					</tr>
				</table>

				<?php
				$rule_index = 0;
				foreach ( $post_types as $pt ) {
					/**
					 * Filter the taxonomy slugs excluded from the settings page.
					 *
					 * @param array  $excluded  Taxonomy slugs to hide.
					 * @param string $post_type Post type being rendered.
					 */
					$excluded_tx = apply_filters(
						'ctwp_excluded_taxonomies',
						array( 'post_format', 'nav_menu', 'link_category' ),
						$pt->name
					);
					$taxes       = array_filter(
						get_object_taxonomies( $pt->name, 'objects' ),
						static function ( $tx ) use ( $excluded_tx ) {
							if ( empty( $tx->show_ui ) ) {
								return false;
							}
							return ! in_array( $tx->name, $excluded_tx, true );
						}
					);

					$theme_templates = CTWP_Plugin::get_post_type_templates( $pt->name );
					$has_custom_tpl  = count( $theme_templates ) > 1;

					if ( empty( $taxes ) && ! $has_custom_tpl ) {
						continue;
					}
					?>
					<h2><?php echo esc_html( $pt->labels->name ); ?> <code><?php echo esc_html( $pt->name ); ?></code></h2>
					<?php
					foreach ( $taxes as $tx ) {
						$key       = $pt->name . '|' . $tx->name;
						$rule      = isset( $by_key[ $key ] ) ? $by_key[ $key ] : array();
						$is_saved  = ! empty( $rule );
						$source    = isset( $rule['source'] ) ? $rule['source'] : 'direct';
						$acf_field = isset( $rule['acf_field'] ) ? $rule['acf_field'] : '';
						$rows      = $is_saved ? ! empty( $rule['rows'] )   : false;
						$banner    = $is_saved ? ! empty( $rule['banner'] ) : true;
						$filter    = $is_saved ? ! empty( $rule['filter'] ) : true;
						$colors    = isset( $rule['colors'] ) ? $rule['colors'] : array();
						$name      = 'ctwp_settings[rules][' . $rule_index . ']';
						?>
						<fieldset class="ctwp-rule" data-pt="<?php echo esc_attr( $pt->name ); ?>" data-tx="<?php echo esc_attr( $tx->name ); ?>">
							<legend>
								<strong><?php echo esc_html( $tx->labels->singular_name ); ?></strong>
								<code><?php echo esc_html( $tx->name ); ?></code>
							</legend>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[post_type]" value="<?php echo esc_attr( $pt->name ); ?>">
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[taxonomy]" value="<?php echo esc_attr( $tx->name ); ?>">

							<div class="ctwp-rule-body">
								<p>
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[rows]" value="1" <?php checked( $rows ); ?>> <?php esc_html_e( 'Color list rows', 'color-the-wp' ); ?></label>
									&nbsp;&nbsp;
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[banner]" value="1" <?php checked( $banner ); ?>> <?php esc_html_e( 'Show banner on edit screen', 'color-the-wp' ); ?></label>
									&nbsp;&nbsp;
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[filter]" value="1" <?php checked( $filter ); ?>> <?php esc_html_e( 'Show filter dropdown in list', 'color-the-wp' ); ?></label>
								</p>
								<p>
									<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[source]" value="direct" <?php checked( $source, 'direct' ); ?>> <?php esc_html_e( 'Pick colors directly', 'color-the-wp' ); ?></label>
									&nbsp;&nbsp;
									<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[source]" value="acf" <?php checked( $source, 'acf' ); ?>> <?php esc_html_e( 'Read from ACF field on each term', 'color-the-wp' ); ?></label>
								</p>

								<p class="ctwp-acf-row"<?php echo $source === 'acf' ? '' : ' style="display:none"'; ?>>
									<label>
										<?php esc_html_e( 'ACF field name (slug):', 'color-the-wp' ); ?>
										<input type="text" name="<?php echo esc_attr( $name ); ?>[acf_field]" value="<?php echo esc_attr( $acf_field ); ?>" placeholder="color">
									</label>
									<span class="description"><?php esc_html_e( 'Tip: attach an ACF Color Picker field to this taxonomy.', 'color-the-wp' ); ?></span>
								</p>

								<div class="ctwp-direct-row"<?php echo $source === 'direct' ? '' : ' style="display:none"'; ?>>
									<?php
									$terms = get_terms(
										array(
											'taxonomy'   => $tx->name,
											'hide_empty' => false,
										)
									);
									if ( is_wp_error( $terms ) || empty( $terms ) ) {
										echo '<p><em>' . esc_html__( 'No terms yet in this taxonomy.', 'color-the-wp' ) . '</em></p>';
									} else {
										echo '<table class="widefat striped ctwp-terms"><tbody>';
										foreach ( $terms as $term ) {
											$val = isset( $colors[ $term->term_id ] ) ? $colors[ $term->term_id ] : '';
											echo '<tr>';
											echo '<td>' . esc_html( $term->name ) . '</td>';
											echo '<td><input type="text" class="ctwp-color" name="' . esc_attr( $name ) . '[colors][' . (int) $term->term_id . ']" value="' . esc_attr( $val ) . '"></td>';
											echo '</tr>';
										}
										echo '</tbody></table>';
									}
									?>
								</div>
							</div>
						</fieldset>
						<?php
						$rule_index++;
					}

					if ( $has_custom_tpl ) {
						$key      = $pt->name . '|' . CTWP_Plugin::TEMPLATE_PSEUDO_TAX;
						$rule     = isset( $by_key[ $key ] ) ? $by_key[ $key ] : array();
						$is_saved = ! empty( $rule );
						$rows     = $is_saved ? ! empty( $rule['rows'] )   : false;
						$banner   = $is_saved ? ! empty( $rule['banner'] ) : true;
						$filter   = $is_saved ? ! empty( $rule['filter'] ) : true;
						$colors   = isset( $rule['colors'] ) ? $rule['colors'] : array();
						$name     = 'ctwp_settings[rules][' . $rule_index . ']';
						?>
						<fieldset class="ctwp-rule ctwp-rule-template" data-pt="<?php echo esc_attr( $pt->name ); ?>">
							<legend>
								<strong><?php esc_html_e( 'Page Template', 'color-the-wp' ); ?></strong>
								<code>_wp_page_template</code>
							</legend>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[post_type]" value="<?php echo esc_attr( $pt->name ); ?>">
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[taxonomy]" value="<?php echo esc_attr( CTWP_Plugin::TEMPLATE_PSEUDO_TAX ); ?>">
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[source]" value="direct">

							<div class="ctwp-rule-body">
								<p>
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[rows]" value="1" <?php checked( $rows ); ?>> <?php esc_html_e( 'Color list rows', 'color-the-wp' ); ?></label>
									&nbsp;&nbsp;
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[banner]" value="1" <?php checked( $banner ); ?>> <?php esc_html_e( 'Show banner on edit screen', 'color-the-wp' ); ?></label>
									&nbsp;&nbsp;
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[filter]" value="1" <?php checked( $filter ); ?>> <?php esc_html_e( 'Show filter dropdown in list', 'color-the-wp' ); ?></label>
								</p>
								<table class="widefat striped ctwp-terms"><tbody>
								<?php foreach ( $theme_templates as $slug => $label ) :
									$val = isset( $colors[ $slug ] ) ? $colors[ $slug ] : '';
									?>
									<tr>
										<td><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $slug ); ?></code></td>
										<td><input type="text" class="ctwp-color" name="<?php echo esc_attr( $name ); ?>[colors][<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $val ); ?>"></td>
									</tr>
								<?php endforeach; ?>
								</tbody></table>
							</div>
						</fieldset>
						<?php
						$rule_index++;
					}
				}
				?>

				<?php submit_button(); ?>
			</form>

			<?php
			/**
			 * Fires after the settings form is rendered, inside the wrap.
			 *
			 * Use it to inject extra sections, debug info, or links to docs.
			 */
			do_action( 'ctwp_render_settings_after' );
			?>
		</div>
		<?php
	}
}
