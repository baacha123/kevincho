<?php
/**
 * KCTM Personalization Storage
 *
 * Handles the full lifecycle of personalization choices: capturing them from the
 * product page form, storing them in the WooCommerce cart, adjusting prices,
 * displaying choices in the cart/checkout, and persisting them as order item meta.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Personalization_Storage
 */
class KCTM_Personalization_Storage {

	/**
	 * Register all hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function init() {
		// Cart: capture personalization from POST data.
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_personalization_to_cart' ), 10, 2 );

		// Cart: display personalization choices as line items.
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_personalization_in_cart' ), 10, 2 );

		// Cart: adjust product prices to include personalization modifiers.
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'adjust_cart_prices' ), 20, 1 );

		// Checkout: save personalization to order item meta.
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_personalization_to_order' ), 10, 3 );

		// Order display: format meta key label.
		add_filter( 'woocommerce_order_item_display_meta_key', array( __CLASS__, 'format_meta_key' ), 10, 3 );

		// Order display: format personalization meta as readable list.
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( __CLASS__, 'format_meta_display' ), 10, 2 );
	}

	/* ================================================================
	 * Cart — Capture Personalization
	 * ============================================================= */

	/**
	 * Add personalization choices to cart item data.
	 *
	 * Reads the `kctm_personalization` array (group_slug => option_slug) and
	 * the optional `kctm_monogram_text` free-text field from $_POST, validates
	 * each choice against the database, and attaches the data to the cart item.
	 *
	 * @since  1.0.0
	 * @param  array $cart_item_data Existing cart item data.
	 * @param  int   $product_id    The product being added to cart.
	 * @return array Modified cart item data.
	 */
	public static function add_personalization_to_cart( $cart_item_data, $product_id ) {
		// Only process personalizable products.
		if ( ! KCTM_Personalization_Options::is_product_personalizable( $product_id ) ) {
			return $cart_item_data;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- add-to-cart nonce handled by WooCommerce.
		if ( empty( $_POST['kctm_personalization'] ) || ! is_array( $_POST['kctm_personalization'] ) ) {
			return $cart_item_data;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$posted = array_map( 'sanitize_text_field', wp_unslash( $_POST['kctm_personalization'] ) );

		$groups          = KCTM_Personalization_Options::get_groups_with_options();
		$personalization = array();
		$total_modifier  = 0;

		// Build a lookup: group_slug => group object (with options indexed by slug).
		$group_map = array();
		foreach ( $groups as $group ) {
			$option_map = array();
			foreach ( $group->options as $option ) {
				$option_map[ $option->slug ] = $option;
			}
			$group->options_map   = $option_map;
			$group_map[ $group->slug ] = $group;
		}

		// Validate each posted choice.
		foreach ( $posted as $group_slug => $option_slug ) {
			if ( ! isset( $group_map[ $group_slug ] ) ) {
				continue; // Unknown group — skip.
			}

			$group = $group_map[ $group_slug ];

			if ( ! isset( $group->options_map[ $option_slug ] ) ) {
				continue; // Unknown option — skip.
			}

			$option   = $group->options_map[ $option_slug ];
			$modifier = floatval( $option->price_modifier );

			$personalization[] = array(
				'group_slug'     => $group->slug,
				'group_title'    => $group->title,
				'option_slug'    => $option->slug,
				'option_title'   => $option->title,
				'price_modifier' => $modifier,
			);

			$total_modifier += $modifier;
		}

		// Monogram free-text field.
		// phpcs:ignore WordPress.Security.NonceVerification
		$monogram_text = isset( $_POST['kctm_monogram_text'] )
			? sanitize_text_field( wp_unslash( $_POST['kctm_monogram_text'] ) )
			: '';

		if ( ! empty( $personalization ) ) {
			$cart_item_data['kctm_personalization'] = array(
				'choices'        => $personalization,
				'monogram_text'  => $monogram_text,
				'total_modifier' => $total_modifier,
			);
		}

		return $cart_item_data;
	}

	/* ================================================================
	 * Cart — Display Choices
	 * ============================================================= */

	/**
	 * Display personalization choices as extra line items in the cart.
	 *
	 * Each choice is shown as "Group Title: Option Title (+price)" so the
	 * customer can review their customizations before checkout.
	 *
	 * @since  1.0.0
	 * @param  array $item_data Existing item display data.
	 * @param  array $cart_item Cart item array.
	 * @return array Modified item display data.
	 */
	public static function display_personalization_in_cart( $item_data, $cart_item ) {
		if ( empty( $cart_item['kctm_personalization']['choices'] ) ) {
			return $item_data;
		}

		// Show fabric if present (from suit configurator).
		if ( ! empty( $cart_item['kctm_personalization']['fabric'] ) ) {
			$fabric = $cart_item['kctm_personalization']['fabric'];
			$fab_value = esc_html( $fabric['name'] );

			if ( ! empty( $fabric['price_modifier'] ) && floatval( $fabric['price_modifier'] ) > 0 ) {
				$fab_value .= ' (+' . wc_price( $fabric['price_modifier'] ) . ')';
			}

			$item_data[] = array(
				'key'   => __( 'Fabric', 'kevincho-tailoring-manager' ),
				'value' => $fab_value,
			);
		}

		foreach ( $cart_item['kctm_personalization']['choices'] as $choice ) {
			$value = esc_html( $choice['option_title'] );

			if ( $choice['price_modifier'] > 0 ) {
				$value .= ' (+' . wc_price( $choice['price_modifier'] ) . ')';
			}

			$item_data[] = array(
				'key'   => esc_html( $choice['group_title'] ),
				'value' => $value,
			);
		}

		// Show monogram text if provided.
		if ( ! empty( $cart_item['kctm_personalization']['monogram_text'] ) ) {
			$item_data[] = array(
				'key'   => __( 'Monogram Text', 'kevincho-tailoring-manager' ),
				'value' => esc_html( $cart_item['kctm_personalization']['monogram_text'] ),
			);
		}

		return $item_data;
	}

	/* ================================================================
	 * Cart — Price Adjustment
	 * ============================================================= */

	/**
	 * Adjust cart item prices to include personalization price modifiers.
	 *
	 * Adds the cumulative `total_modifier` from all personalization choices
	 * to the product's base price. Runs during cart total calculation.
	 *
	 * @since  1.0.0
	 * @param  WC_Cart $cart The WooCommerce cart object.
	 * @return void
	 */
	public static function adjust_cart_prices( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['kctm_personalization']['total_modifier'] ) ) {
				continue;
			}

			$modifier = floatval( $cart_item['kctm_personalization']['total_modifier'] );

			if ( $modifier <= 0 ) {
				continue;
			}

			$product    = $cart_item['data'];
			$base_price = floatval( $product->get_price() );

			$product->set_price( $base_price + $modifier );
		}
	}

	/* ================================================================
	 * Checkout — Save to Order
	 * ============================================================= */

	/**
	 * Save personalization data to order line item meta during checkout.
	 *
	 * Stores the full personalization array (choices with group titles,
	 * option titles, and price modifiers plus monogram text) as serialized
	 * order item meta under `_kctm_personalization`.
	 *
	 * @since  1.0.0
	 * @param  WC_Order_Item_Product $item          The order line item.
	 * @param  string                $cart_item_key  Cart item key.
	 * @param  array                 $values         Cart item values.
	 * @return void
	 */
	public static function save_personalization_to_order( $item, $cart_item_key, $values ) {
		if ( empty( $values['kctm_personalization'] ) ) {
			return;
		}

		$item->add_meta_data( '_kctm_personalization', $values['kctm_personalization'], true );
	}

	/* ================================================================
	 * Order Display — Format Meta
	 * ============================================================= */

	/**
	 * Format the `_kctm_personalization` meta key for display in order details.
	 *
	 * Converts the internal meta key to a human-readable label.
	 *
	 * @since  1.0.0
	 * @param  string        $display_key The raw meta key.
	 * @param  WC_Meta_Data  $meta        The meta data object.
	 * @param  WC_Order_Item $item        The order item.
	 * @return string Modified display key.
	 */
	public static function format_meta_key( $display_key, $meta, $item ) {
		if ( '_kctm_personalization' === $meta->key ) {
			$display_key = __( 'Personalization', 'kevincho-tailoring-manager' );
		}

		return $display_key;
	}

	/**
	 * Format personalization meta as a readable list in order details.
	 *
	 * Replaces the raw serialized array with a nicely formatted string
	 * showing each personalization choice and the monogram text.
	 *
	 * @since  1.0.0
	 * @param  array         $formatted_meta Array of formatted meta objects.
	 * @param  WC_Order_Item $item           The order item.
	 * @return array Modified formatted meta array.
	 */
	public static function format_meta_display( $formatted_meta, $item ) {
		foreach ( $formatted_meta as $meta_id => $meta ) {
			if ( '_kctm_personalization' !== $meta->key ) {
				continue;
			}

			$data = $meta->value;

			if ( ! is_array( $data ) || empty( $data['choices'] ) ) {
				continue;
			}

			$lines = array();

			// Show fabric if present (from suit configurator).
			if ( ! empty( $data['fabric'] ) ) {
				$fab_line = esc_html__( 'Fabric', 'kevincho-tailoring-manager' ) . ': ' . esc_html( $data['fabric']['name'] );

				if ( ! empty( $data['fabric']['price_modifier'] ) && floatval( $data['fabric']['price_modifier'] ) > 0 ) {
					$fab_line .= ' (+' . wc_price( $data['fabric']['price_modifier'] ) . ')';
				}

				$lines[] = $fab_line;
			}

			foreach ( $data['choices'] as $choice ) {
				$line = esc_html( $choice['group_title'] ) . ': ' . esc_html( $choice['option_title'] );

				if ( ! empty( $choice['price_modifier'] ) && floatval( $choice['price_modifier'] ) > 0 ) {
					$line .= ' (+' . wc_price( $choice['price_modifier'] ) . ')';
				}

				$lines[] = $line;
			}

			if ( ! empty( $data['monogram_text'] ) ) {
				$lines[] = esc_html__( 'Monogram Text', 'kevincho-tailoring-manager' ) . ': ' . esc_html( $data['monogram_text'] );
			}

			// Update the display key and value.
			$meta->display_key   = __( 'Personalization', 'kevincho-tailoring-manager' );
			$meta->display_value = '<br>' . implode( '<br>', $lines );
		}

		return $formatted_meta;
	}
}
