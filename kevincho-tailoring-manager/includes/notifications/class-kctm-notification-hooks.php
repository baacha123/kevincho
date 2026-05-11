<?php
/**
 * Notification Hooks
 *
 * Hooks into all WooCommerce and KCTM events that should trigger
 * multi-channel notifications (WhatsApp + SMS) via the Dispatcher.
 *
 * This replaces the old single-channel approach in KCTM_WhatsApp_Notifications
 * and KCTM_Consultation_Notifications.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_Notification_Hooks {

	/**
	 * Initialize all notification hooks.
	 *
	 * @return void
	 */
	public static function init() {

		// ── Order status changes (covers ALL statuses) ──
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_order_status_changed' ), 10, 3 );

		// ── New order placed (fires once when order is first created) ──
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_new_order' ), 20, 1 );

		// ── Customer note added by admin ──
		add_action( 'woocommerce_new_customer_note', array( __CLASS__, 'on_customer_note' ), 10, 1 );

		// ── New customer account created ──
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'on_new_customer' ), 20, 1 );

		// ── Walk-in customer created (KCTM custom hook) ──
		add_action( 'kctm_walkin_customer_created', array( __CLASS__, 'on_walkin_created' ), 10, 1 );

		// ── Consultation events (KCTM custom hooks) ──
		add_action( 'kctm_consultation_confirmed', array( __CLASS__, 'on_consultation_confirmed' ), 10, 1 );
		add_action( 'kctm_consultation_reminder', array( __CLASS__, 'on_consultation_reminder' ), 10, 1 );
		add_action( 'kctm_consultation_cancelled', array( __CLASS__, 'on_consultation_cancelled' ), 10, 1 );

		// ── Order tracking update (KCTM custom hook) ──
		add_action( 'kctm_tracking_updated', array( __CLASS__, 'on_tracking_updated' ), 10, 2 );
	}

	/**
	 * Handle WooCommerce order status changes.
	 *
	 * Maps WooCommerce status slugs to our event keys and dispatches.
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Old status (without wc- prefix).
	 * @param string $new_status New status (without wc- prefix).
	 */
	public static function on_order_status_changed( $order_id, $old_status, $new_status ) {
		// Map WC status → dispatcher event key.
		$status_to_event = array(
			'processing'       => 'order_processing',
			'kctm-confirmed'   => 'order_confirmed',
			'kctm-in-progress' => 'order_in_progress',
			'kctm-ready-pickup' => 'order_ready_pickup',
			'kctm-delivered'   => 'order_delivered',
			'completed'        => 'order_completed',
			'cancelled'        => 'order_cancelled',
			'refunded'         => 'order_refunded',
		);

		if ( ! isset( $status_to_event[ $new_status ] ) ) {
			return;
		}

		$event_key = $status_to_event[ $new_status ];
		$order     = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$context = KCTM_Notification_Dispatcher::context_from_order( $order );

		KCTM_Notification_Dispatcher::dispatch( $event_key, $context );
	}

	/**
	 * Handle new order placed via checkout.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public static function on_new_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Skip if this is a consultation-only order (handled separately).
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && 'consultation' === $product->get_slug() ) {
				return;
			}
		}

		$context = KCTM_Notification_Dispatcher::context_from_order( $order );

		KCTM_Notification_Dispatcher::dispatch( 'order_new', $context );
	}

	/**
	 * Handle customer note added by admin.
	 *
	 * @param array $data {
	 *     @type int    $order_id      Order ID.
	 *     @type string $customer_note Note text.
	 * }
	 */
	public static function on_customer_note( $data ) {
		$order_id = isset( $data['order_id'] ) ? $data['order_id'] : 0;
		$note     = isset( $data['customer_note'] ) ? $data['customer_note'] : '';

		if ( ! $order_id || empty( $note ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$context         = KCTM_Notification_Dispatcher::context_from_order( $order );
		$context['note'] = $note;

		KCTM_Notification_Dispatcher::dispatch( 'order_note', $context );
	}

	/**
	 * Handle new customer account created.
	 *
	 * @param int $customer_id WordPress user ID.
	 */
	public static function on_new_customer( $customer_id ) {
		$user = get_userdata( $customer_id );
		if ( ! $user ) {
			return;
		}

		$phone = get_user_meta( $customer_id, 'billing_phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $customer_id, '_kctm_phone', true );
		}

		$customer_name = $user->first_name;
		if ( empty( $customer_name ) ) {
			$customer_name = $user->display_name;
		}

		KCTM_Notification_Dispatcher::dispatch( 'customer_new_account', array(
			'phone'         => $phone,
			'email'         => $user->user_email,
			'customer_name' => $customer_name,
			'customer_id'   => $customer_id,
			'order_id'      => 0,
			'language'      => 'en',
		) );
	}

	/**
	 * Handle walk-in customer created by admin.
	 *
	 * @param int $customer_id WordPress user ID.
	 */
	public static function on_walkin_created( $customer_id ) {
		$user = get_userdata( $customer_id );
		if ( ! $user ) {
			return;
		}

		$phone = get_user_meta( $customer_id, 'billing_phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $customer_id, '_kctm_phone', true );
		}

		$customer_name = $user->first_name;
		if ( empty( $customer_name ) ) {
			$customer_name = $user->display_name;
		}

		KCTM_Notification_Dispatcher::dispatch( 'customer_walkin_created', array(
			'phone'         => $phone,
			'email'         => $user->user_email,
			'customer_name' => $customer_name,
			'customer_id'   => $customer_id,
			'order_id'      => 0,
			'language'      => 'en',
		) );
	}

	/**
	 * Handle consultation confirmed.
	 *
	 * @param int $booking_id Consultation booking ID.
	 */
	public static function on_consultation_confirmed( $booking_id ) {
		$booking = KCTM_Consultation_Booking::get_booking( $booking_id );
		if ( ! $booking ) {
			return;
		}

		$context = KCTM_Notification_Dispatcher::context_from_booking( $booking );
		KCTM_Notification_Dispatcher::dispatch( 'consultation_confirmed', $context );
	}

	/**
	 * Handle consultation reminder (day before).
	 *
	 * @param int $booking_id Consultation booking ID.
	 */
	public static function on_consultation_reminder( $booking_id ) {
		$booking = KCTM_Consultation_Booking::get_booking( $booking_id );
		if ( ! $booking ) {
			return;
		}

		$context = KCTM_Notification_Dispatcher::context_from_booking( $booking );
		KCTM_Notification_Dispatcher::dispatch( 'consultation_reminder', $context );
	}

	/**
	 * Handle consultation cancelled.
	 *
	 * @param int $booking_id Consultation booking ID.
	 */
	public static function on_consultation_cancelled( $booking_id ) {
		$booking = KCTM_Consultation_Booking::get_booking( $booking_id );
		if ( ! $booking ) {
			return;
		}

		$context = KCTM_Notification_Dispatcher::context_from_booking( $booking );
		KCTM_Notification_Dispatcher::dispatch( 'consultation_cancelled', $context );
	}

	/**
	 * Handle tracking number updated for an order.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $tracking {
	 *     @type string $tracking_number Tracking number.
	 *     @type string $carrier         Carrier name.
	 * }
	 */
	public static function on_tracking_updated( $order_id, $tracking ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$context                    = KCTM_Notification_Dispatcher::context_from_order( $order );
		$context['tracking_number'] = isset( $tracking['tracking_number'] ) ? $tracking['tracking_number'] : '';
		$context['carrier']         = isset( $tracking['carrier'] ) ? $tracking['carrier'] : '';

		KCTM_Notification_Dispatcher::dispatch( 'order_tracking', $context );
	}
}
