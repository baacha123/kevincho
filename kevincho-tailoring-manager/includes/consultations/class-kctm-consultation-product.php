<?php
/**
 * Consultation Product
 *
 * Manages the WooCommerce product used for consultation bookings.
 * Ensures a virtual consultation product exists, handles cart operations,
 * and persists consultation metadata through the checkout flow.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Consultation_Product
 *
 * Creates and manages a hidden WooCommerce product for consultation
 * bookings, including add-to-cart, cart display, and order meta saving.
 */
class KCTM_Consultation_Product {

	/**
	 * Initialize hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'ensure_product_exists' ) );

		// Always allow the consultation product to be purchasable.
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'filter_is_purchasable' ), 10, 2 );

		// Hide the consultation product from the shop catalog.
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'filter_is_visible' ), 10, 2 );

		// Display consultation details in the cart.
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_consultation_in_cart' ), 10, 2 );

		// Save consultation meta to order line items.
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_consultation_to_order' ), 10, 3 );
	}

	/**
	 * Ensure the consultation product exists in WooCommerce.
	 *
	 * Checks the stored product ID option. If the product does not exist
	 * or has been deleted, creates a new hidden virtual product for
	 * consultation bookings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function ensure_product_exists() {
		$product_id = get_option( 'kctm_consultation_product_id', 0 );

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				return;
			}
		}

		// Create a new consultation product.
		$product = new WC_Product_Simple();

		$product->set_name( __( 'Consultation with Kevin Cho', 'kevincho-tailoring-manager' ) );
		$product->set_regular_price( self::get_price() );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_virtual( true );
		$product->set_sold_individually( true );

		$product_id = $product->save();

		if ( $product_id ) {
			update_option( 'kctm_consultation_product_id', $product_id );
		}
	}

	/**
	 * Get the consultation product ID.
	 *
	 * @since  1.0.0
	 * @return int The WooCommerce product ID, or 0 if not set.
	 */
	public static function get_product_id() {
		return absint( get_option( 'kctm_consultation_product_id', 0 ) );
	}

	/**
	 * Get the consultation price.
	 *
	 * Reads from the consultation settings option, falling back
	 * to the default price of 15000.
	 *
	 * @since  1.0.0
	 * @return float The consultation price.
	 */
	public static function get_price() {
		$settings = get_option( 'kctm_consultation_settings', array() );

		return isset( $settings['price'] ) ? floatval( $settings['price'] ) : 15000;
	}

	/**
	 * Add the consultation product to the cart with booking data.
	 *
	 * Empties the cart first (consultation is a standalone purchase),
	 * then adds the consultation product with custom cart item data.
	 *
	 * @since  1.0.0
	 * @param  array $booking_data {
	 *     Consultation booking details.
	 *
	 *     @type string $consultation_date Date of the consultation (Y-m-d).
	 *     @type string $consultation_time Time slot (HH:MM).
	 *     @type string $first_name        Customer first name.
	 *     @type string $last_name         Customer last name.
	 *     @type string $phone             Customer phone number.
	 *     @type string $email             Customer email address.
	 *     @type string $notes             Optional notes from the customer.
	 * }
	 * @return bool True if successfully added, false otherwise.
	 */
	public static function add_to_cart( $booking_data ) {
		$product_id = self::get_product_id();

		if ( ! $product_id ) {
			return false;
		}

		// Empty the cart before adding the consultation.
		WC()->cart->empty_cart();

		$cart_item_data = array(
			'kctm_consultation' => array(
				'consultation_date' => sanitize_text_field( $booking_data['consultation_date'] ),
				'consultation_time' => sanitize_text_field( $booking_data['consultation_time'] ),
				'first_name'        => sanitize_text_field( $booking_data['first_name'] ),
				'last_name'         => sanitize_text_field( $booking_data['last_name'] ),
				'phone'             => sanitize_text_field( $booking_data['phone'] ),
				'email'             => sanitize_email( $booking_data['email'] ),
				'notes'             => sanitize_textarea_field( isset( $booking_data['notes'] ) ? $booking_data['notes'] : '' ),
			),
		);

		$result = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

		return (bool) $result;
	}

	/**
	 * Filter: always allow the consultation product to be purchasable.
	 *
	 * @since  1.0.0
	 * @param  bool       $purchasable Whether the product is purchasable.
	 * @param  WC_Product $product     The product object.
	 * @return bool Modified purchasable status.
	 */
	public static function filter_is_purchasable( $purchasable, $product ) {
		$consultation_id = self::get_product_id();

		if ( $consultation_id && $product->get_id() === $consultation_id ) {
			return true;
		}

		return $purchasable;
	}

	/**
	 * Filter: hide the consultation product from the shop catalog.
	 *
	 * @since  1.0.0
	 * @param  bool $visible    Whether the product is visible.
	 * @param  int  $product_id The product ID.
	 * @return bool Modified visibility status.
	 */
	public static function filter_is_visible( $visible, $product_id ) {
		$consultation_id = self::get_product_id();

		if ( $consultation_id && $product_id === $consultation_id ) {
			return false;
		}

		return $visible;
	}

	/**
	 * Display consultation details in the cart.
	 *
	 * Shows the consultation date, time, and customer name as extra
	 * line items beneath the product name in the cart and checkout.
	 *
	 * @since  1.0.0
	 * @param  array $item_data Existing item display data.
	 * @param  array $cart_item Cart item array.
	 * @return array Modified item display data.
	 */
	public static function display_consultation_in_cart( $item_data, $cart_item ) {
		if ( empty( $cart_item['kctm_consultation'] ) ) {
			return $item_data;
		}

		$consultation = $cart_item['kctm_consultation'];

		$item_data[] = array(
			'key'   => __( 'Date', 'kevincho-tailoring-manager' ),
			'value' => esc_html( $consultation['consultation_date'] ),
		);

		$item_data[] = array(
			'key'   => __( 'Time', 'kevincho-tailoring-manager' ),
			'value' => esc_html( $consultation['consultation_time'] ),
		);

		$customer_name = trim( $consultation['first_name'] . ' ' . $consultation['last_name'] );
		if ( ! empty( $customer_name ) ) {
			$item_data[] = array(
				'key'   => __( 'Customer', 'kevincho-tailoring-manager' ),
				'value' => esc_html( $customer_name ),
			);
		}

		return $item_data;
	}

	/**
	 * Save consultation meta to order line items during checkout.
	 *
	 * Persists the full consultation booking data array as order item
	 * meta under `_kctm_consultation` for later processing when
	 * payment is completed.
	 *
	 * @since  1.0.0
	 * @param  WC_Order_Item_Product $item          The order line item.
	 * @param  string                $cart_item_key Cart item key.
	 * @param  array                 $values        Cart item values.
	 * @return void
	 */
	public static function save_consultation_to_order( $item, $cart_item_key, $values ) {
		if ( empty( $values['kctm_consultation'] ) ) {
			return;
		}

		$item->add_meta_data( '_kctm_consultation', $values['kctm_consultation'], true );
	}
}
