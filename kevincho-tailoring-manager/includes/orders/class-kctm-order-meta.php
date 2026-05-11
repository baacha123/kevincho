<?php
/**
 * Order Meta — Measurement Snapshots and In-Store Order Tagging.
 *
 * Saves a snapshot of the customer's measurements at the time of order placement
 * and provides helpers for managing tailoring-specific order metadata.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Order_Meta
 *
 * Handles measurement snapshots on order creation and in-store order tagging.
 * Uses HPOS-compatible WC_Order object methods throughout.
 */
class KCTM_Order_Meta {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'on_order_created' ), 10, 1 );
		add_action( 'woocommerce_new_order', array( __CLASS__, 'on_new_order' ), 10, 2 );
	}

	/**
	 * Callback for `woocommerce_checkout_order_created`.
	 *
	 * Saves the measurement snapshot and personalization choices when an order
	 * is created through the checkout flow.
	 *
	 * @param WC_Order $order The newly created order object.
	 * @return void
	 */
	public static function on_order_created( $order ) {
		self::save_measurement_snapshot( $order );
		self::save_personalization_choices( $order );
	}

	/**
	 * Callback for `woocommerce_new_order`.
	 *
	 * Fallback hook for orders created outside the standard checkout
	 * (e.g., admin-created or REST API orders). Only processes if no
	 * snapshot has been saved yet to avoid duplicating work.
	 *
	 * @param int           $order_id The order ID.
	 * @param WC_Order|null $order    The order object (may be null in older WC versions).
	 * @return void
	 */
	public static function on_new_order( $order_id, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		// Avoid double-saving if the checkout hook already ran.
		$existing = $order->get_meta( '_kctm_measurement_snapshot' );
		if ( ! empty( $existing ) ) {
			return;
		}

		self::save_measurement_snapshot( $order );
		self::save_personalization_choices( $order );
	}

	/**
	 * Save a snapshot of the customer's current measurements as order meta.
	 *
	 * @param WC_Order $order The order object.
	 * @return bool True on success, false if no customer or no measurements found.
	 */
	public static function save_measurement_snapshot( $order ) {
		$customer_id = $order->get_customer_id();

		if ( ! $customer_id ) {
			return false;
		}

		if ( ! class_exists( 'KCTM_Measurement_Storage' ) ) {
			return false;
		}

		$measurements = KCTM_Measurement_Storage::get_measurement_snapshot( $customer_id );

		if ( empty( $measurements ) ) {
			return false;
		}

		return self::save_snapshot_to_order( $order, $measurements );
	}

	/**
	 * Retrieve the measurement snapshot stored on an order.
	 *
	 * @param int $order_id The order ID.
	 * @return array|false The measurement snapshot array, or false if not found.
	 */
	public static function get_measurement_snapshot( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		$snapshot = $order->get_meta( '_kctm_measurement_snapshot' );

		if ( empty( $snapshot ) ) {
			return false;
		}

		return $snapshot;
	}

	/**
	 * Mark an order as an in-store order.
	 *
	 * @param int $order_id The order ID.
	 * @return bool True on success, false on failure.
	 */
	public static function mark_instore_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		$order->update_meta_data( '_kctm_order_type', 'instore' );
		$order->save();

		return true;
	}

	/**
	 * Check whether an order is an in-store order.
	 *
	 * @param int $order_id The order ID.
	 * @return bool True if the order is in-store, false otherwise.
	 */
	public static function is_instore_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		return 'instore' === $order->get_meta( '_kctm_order_type' );
	}

	/**
	 * Write the measurement snapshot to order meta (HPOS compatible).
	 *
	 * @param WC_Order $order        The order object.
	 * @param array    $measurements The measurements data to save.
	 * @return bool True on success.
	 */
	public static function save_snapshot_to_order( $order, $measurements ) {
		$order->update_meta_data( '_kctm_measurement_snapshot', $measurements );
		$order->save();

		return true;
	}

	/**
	 * Save personalization choices from cart items into order item meta.
	 *
	 * Iterates through order items, checks for personalization data stored
	 * in the corresponding cart item, and persists it as order item meta.
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	private static function save_personalization_choices( $order ) {
		$cart = WC()->cart;

		if ( ! $cart ) {
			return;
		}

		$cart_contents = $cart->get_cart();

		foreach ( $order->get_items() as $item_id => $item ) {
			// Try to match order items back to cart items.
			foreach ( $cart_contents as $cart_item_key => $cart_item ) {
				if ( ! isset( $cart_item['kctm_personalization'] ) ) {
					continue;
				}

				// Match by product ID and variation ID.
				$product_id   = $item->get_product_id();
				$variation_id = $item->get_variation_id();

				if ( $cart_item['product_id'] === $product_id
					&& (int) ( $cart_item['variation_id'] ?? 0 ) === $variation_id
				) {
					$personalization = $cart_item['kctm_personalization'];

					wc_add_order_item_meta( $item_id, '_kctm_personalization', $personalization );
				}
			}
		}
	}
}
