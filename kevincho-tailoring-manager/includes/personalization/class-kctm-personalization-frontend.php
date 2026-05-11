<?php
/**
 * KCTM Personalization Frontend
 *
 * Displays the Hockerty-style garment customization UI on WooCommerce product
 * pages for products that have personalization enabled. Also adds the
 * "Personalization" tab to the WooCommerce product data panel so shop admins
 * can toggle personalization on a per-product basis.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Personalization_Frontend
 */
class KCTM_Personalization_Frontend {

	/**
	 * Register all hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function init() {
		// Frontend: render customizer on product page.
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_customizer' ) );

		// Admin: product data tab & panel.
		add_filter( 'woocommerce_product_data_tabs',   array( __CLASS__, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels',  array( __CLASS__, 'render_product_data_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_meta' ) );
	}

	/* ================================================================
	 * Frontend — Product Page Customizer
	 * ============================================================= */

	/**
	 * Render the personalization options form on the single product page.
	 *
	 * Hooked to `woocommerce_before_add_to_cart_button` so the options
	 * appear directly above the Add to Cart button.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function render_customizer() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();

		// Only render for products with personalization enabled.
		if ( ! KCTM_Personalization_Options::is_product_personalizable( $product_id ) ) {
			return;
		}

		$groups = KCTM_Personalization_Options::get_groups_with_options();

		if ( empty( $groups ) ) {
			return;
		}

		// Make variables available to the template.
		$template = KCTM_PLUGIN_DIR . 'templates/personalization/product-customizer.php';

		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/* ================================================================
	 * Admin — WooCommerce Product Data Tab
	 * ============================================================= */

	/**
	 * Add a "Personalization" tab to the WooCommerce product data metabox.
	 *
	 * @since  1.0.0
	 * @param  array $tabs Existing product data tabs.
	 * @return array Modified tabs array.
	 */
	public static function add_product_data_tab( $tabs ) {
		$tabs['kctm_personalization'] = array(
			'label'    => __( 'Personalization', 'kevincho-tailoring-manager' ),
			'target'   => 'kctm_personalization_product_data',
			'class'    => array(),
			'priority' => 80,
		);

		return $tabs;
	}

	/**
	 * Render the content of the Personalization product data panel.
	 *
	 * Displays a single checkbox that allows the shop admin to enable or
	 * disable garment personalization for the current product.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function render_product_data_panel() {
		global $post;

		$is_enabled = KCTM_Personalization_Options::is_product_personalizable( $post->ID );
		?>
		<div id="kctm_personalization_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox( array(
					'id'          => '_kctm_personalizable',
					'label'       => __( 'Enable personalization', 'kevincho-tailoring-manager' ),
					'description' => __( 'Enable garment personalization for this product. Customers will be able to choose collar style, sleeve type, fit, and other customization options.', 'kevincho-tailoring-manager' ),
					'value'       => $is_enabled ? 'yes' : 'no',
				) );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save the personalization meta when a product is saved.
	 *
	 * @since  1.0.0
	 * @param  int $post_id The product (post) ID.
	 * @return void
	 */
	public static function save_product_meta( $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles the nonce.
		$enabled = isset( $_POST['_kctm_personalizable'] ) && 'yes' === $_POST['_kctm_personalizable'];

		KCTM_Personalization_Options::set_product_personalizable( $post_id, $enabled );
	}
}
