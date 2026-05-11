<?php
/**
 * Admin AJAX Handlers
 *
 * Registers and handles all AJAX actions for the admin interface,
 * including customer search, product search, measurement saving,
 * and WhatsApp notification actions.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Admin_Ajax
 *
 * Provides AJAX endpoints for the Tailoring Manager admin panel and
 * the frontend My Account measurement form.
 */
class KCTM_Admin_Ajax {

	/**
	 * Initialize AJAX hooks.
	 *
	 * @return void
	 */
	public static function init() {
		/* ── Admin-only AJAX actions ─────────────────────── */
		add_action( 'wp_ajax_kctm_search_customers', array( __CLASS__, 'search_customers' ) );
		add_action( 'wp_ajax_kctm_search_products', array( __CLASS__, 'search_products' ) );
		add_action( 'wp_ajax_kctm_get_customer_details', array( __CLASS__, 'get_customer_details' ) );
		add_action( 'wp_ajax_kctm_save_measurements', array( __CLASS__, 'save_measurements' ) );
		add_action( 'wp_ajax_kctm_test_whatsapp', array( 'KCTM_Admin_Settings', 'test_whatsapp' ) );
		add_action( 'wp_ajax_kctm_send_manual_notification', array( __CLASS__, 'send_manual_notification' ) );

		/* ── Frontend (logged-in) AJAX action ────────────── */
		add_action( 'wp_ajax_kctm_save_my_measurements', array( __CLASS__, 'save_my_measurements' ) );

		/* ── Consultation AJAX (public — guests can book) ── */
		add_action( 'wp_ajax_kctm_get_available_dates', array( __CLASS__, 'get_available_dates' ) );
		add_action( 'wp_ajax_nopriv_kctm_get_available_dates', array( __CLASS__, 'get_available_dates' ) );
		add_action( 'wp_ajax_kctm_get_available_times', array( __CLASS__, 'get_available_times' ) );
		add_action( 'wp_ajax_nopriv_kctm_get_available_times', array( __CLASS__, 'get_available_times' ) );
		add_action( 'wp_ajax_kctm_book_consultation', array( __CLASS__, 'book_consultation' ) );
		add_action( 'wp_ajax_nopriv_kctm_book_consultation', array( __CLASS__, 'book_consultation' ) );

		/* ── Consultation admin AJAX ─────────────────────── */
		add_action( 'wp_ajax_kctm_cancel_consultation', array( __CLASS__, 'cancel_consultation' ) );
		add_action( 'wp_ajax_kctm_complete_consultation', array( __CLASS__, 'complete_consultation' ) );
		add_action( 'wp_ajax_kctm_resend_consultation_notification', array( __CLASS__, 'resend_consultation_notification' ) );

		/* ── Suit Configurator (public — guests can browse) ── */
		add_action( 'wp_ajax_kctm_add_configured_suit', array( __CLASS__, 'add_configured_suit' ) );
		add_action( 'wp_ajax_nopriv_kctm_add_configured_suit', array( __CLASS__, 'add_configured_suit' ) );
	}

	/* ================================================================
	 * Customer Search (Select2)
	 * ============================================================= */

	/**
	 * Search for customers by name, email, or phone.
	 *
	 * Returns a JSON array formatted for Select2: [{id, text}].
	 *
	 * @return void
	 */
	public static function search_customers() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		if ( empty( $term ) ) {
			$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		}

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		// Search users.
		$users = get_users( array(
			'role'           => 'customer',
			'search'         => '*' . $term . '*',
			'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
			'number'         => 20,
		) );

		// Also search by phone (meta).
		$phone_users = get_users( array(
			'role'       => 'customer',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_kctm_phone',
					'value'   => $term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'billing_phone',
					'value'   => $term,
					'compare' => 'LIKE',
				),
			),
			'number'     => 20,
		) );

		// Also search by first_name / last_name meta.
		$name_users = get_users( array(
			'role'       => 'customer',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => 'first_name',
					'value'   => $term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'last_name',
					'value'   => $term,
					'compare' => 'LIKE',
				),
			),
			'number'     => 20,
		) );

		// Merge and deduplicate.
		$all_users = array_merge( $users, $phone_users, $name_users );
		$seen_ids  = array();
		$results   = array();

		foreach ( $all_users as $user ) {
			if ( in_array( $user->ID, $seen_ids, true ) ) {
				continue;
			}
			$seen_ids[] = $user->ID;

			$name = trim( $user->first_name . ' ' . $user->last_name );
			if ( empty( $name ) ) {
				$name = $user->display_name;
			}

			$results[] = array(
				'id'   => $user->ID,
				'text' => $name . ' (' . $user->user_email . ')',
			);
		}

		wp_send_json_success( $results );
	}

	/* ================================================================
	 * Product Search (Select2)
	 * ============================================================= */

	/**
	 * Search for WooCommerce products by title.
	 *
	 * Returns a JSON array formatted for Select2: [{id, text}].
	 *
	 * @return void
	 */
	public static function search_products() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		if ( empty( $term ) ) {
			$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		}

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$products = wc_get_products( array(
			's'      => $term,
			'limit'  => 20,
			'status' => 'publish',
		) );

		$results = array();

		foreach ( $products as $product ) {
			$price_display = $product->get_price()
				? wp_strip_all_tags( wc_price( $product->get_price() ) )
				: __( 'N/A', 'kevincho-tailoring-manager' );

			$results[] = array(
				'id'   => $product->get_id(),
				'text' => $product->get_name() . ' - ' . $price_display,
			);
		}

		wp_send_json_success( $results );
	}

	/* ================================================================
	 * Get Customer Details
	 * ============================================================= */

	/**
	 * Retrieve details for a specific customer.
	 *
	 * Returns name, email, phone, and measurement status.
	 *
	 * @return void
	 */
	public static function get_customer_details() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;

		if ( empty( $customer_id ) ) {
			$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		}

		if ( ! $customer_id ) {
			wp_send_json_error( array( 'message' => __( 'No customer ID provided.', 'kevincho-tailoring-manager' ) ) );
		}

		$user = get_userdata( $customer_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'Customer not found.', 'kevincho-tailoring-manager' ) ) );
		}

		$name = trim( $user->first_name . ' ' . $user->last_name );
		if ( empty( $name ) ) {
			$name = $user->display_name;
		}

		$phone = get_user_meta( $customer_id, '_kctm_phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $customer_id, 'billing_phone', true );
		}

		$has_measurements = (bool) get_user_meta( $customer_id, '_kctm_measurement_gender', true );

		wp_send_json_success( array(
			'id'               => $customer_id,
			'name'             => $name,
			'email'            => $user->user_email,
			'phone'            => $phone,
			'has_measurements' => $has_measurements,
		) );
	}

	/* ================================================================
	 * Save Measurements (Admin)
	 * ============================================================= */

	/**
	 * Save measurements for a customer from the admin panel.
	 *
	 * Expects `customer_id` and measurement field data in POST.
	 *
	 * @return void
	 */
	public static function save_measurements() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;

		if ( ! $customer_id ) {
			wp_send_json_error( array( 'message' => __( 'No customer ID provided.', 'kevincho-tailoring-manager' ) ) );
		}

		$user = get_userdata( $customer_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'Customer not found.', 'kevincho-tailoring-manager' ) ) );
		}

		// Parse measurement data.
		$data = array();

		if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
			// Data comes as a serialized form string.
			parse_str( wp_unslash( $_POST['data'] ), $data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} elseif ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) {
			$data = $_POST['data']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} else {
			// Try getting fields directly from POST.
			$fields = KCTM_Measurement_Fields::get_all_fields();
			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field['key'] ] ) ) {
					$data[ $field['key'] ] = sanitize_text_field( wp_unslash( $_POST[ $field['key'] ] ) );
				}
			}
		}

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'No measurement data received.', 'kevincho-tailoring-manager' ) ) );
		}

		$result = KCTM_Measurement_Storage::save_measurements( $customer_id, $data );

		if ( $result['valid'] ) {
			wp_send_json_success( array(
				'message' => __( 'Measurements saved successfully.', 'kevincho-tailoring-manager' ),
			) );
		} else {
			$error_messages = array();
			foreach ( $result['errors'] as $key => $error ) {
				$error_messages[] = $key . ': ' . $error;
			}

			wp_send_json_error( array(
				'message' => __( 'Validation errors:', 'kevincho-tailoring-manager' ) . ' ' . implode( ', ', $error_messages ),
				'errors'  => $result['errors'],
			) );
		}
	}

	/* ================================================================
	 * Send Manual Notification
	 * ============================================================= */

	/**
	 * Send a manual WhatsApp notification for an order.
	 *
	 * Expects `order_id` and `message` in POST.
	 *
	 * @return void
	 */
	public static function send_manual_notification() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$message  = isset( $_POST['message'] )  ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'No order ID provided.', 'kevincho-tailoring-manager' ) ) );
		}

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Message cannot be empty.', 'kevincho-tailoring-manager' ) ) );
		}

		$result = KCTM_WhatsApp_Notifications::send_manual_notification( $order_id, $message );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message'    => __( 'Notification sent successfully.', 'kevincho-tailoring-manager' ),
				'message_id' => $result['message_id'],
			) );
		} else {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error details */
					__( 'Failed to send notification: %s', 'kevincho-tailoring-manager' ),
					$result['response_body']
				),
			) );
		}
	}

	/* ================================================================
	 * Consultation: Get Available Dates
	 * ============================================================= */

	/**
	 * Return available dates for a given month.
	 *
	 * @return void
	 */
	public static function get_available_dates() {
		check_ajax_referer( 'kctm_consultation_nonce' );

		$year  = isset( $_POST['year'] )  ? absint( $_POST['year'] )  : (int) date( 'Y' );
		$month = isset( $_POST['month'] ) ? absint( $_POST['month'] ) : (int) date( 'n' );

		if ( $month < 1 || $month > 12 || $year < 2020 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date parameters.', 'kevincho-tailoring-manager' ) ) );
		}

		$dates = KCTM_Consultation_Availability::get_available_dates_for_month( $year, $month );

		wp_send_json_success( array( 'dates' => $dates ) );
	}

	/* ================================================================
	 * Consultation: Get Available Times
	 * ============================================================= */

	/**
	 * Return available time slots for a given date.
	 *
	 * @return void
	 */
	public static function get_available_times() {
		check_ajax_referer( 'kctm_consultation_nonce' );

		$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

		if ( empty( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date.', 'kevincho-tailoring-manager' ) ) );
		}

		$times = KCTM_Consultation_Availability::get_available_times_for_date( $date );

		wp_send_json_success( array( 'times' => $times ) );
	}

	/* ================================================================
	 * Consultation: Book Consultation
	 * ============================================================= */

	/**
	 * Create a consultation booking and add the product to cart.
	 *
	 * @return void
	 */
	public static function book_consultation() {
		check_ajax_referer( 'kctm_consultation_nonce' );

		$date       = isset( $_POST['consultation_date'] ) ? sanitize_text_field( wp_unslash( $_POST['consultation_date'] ) ) : '';
		$time       = isset( $_POST['consultation_time'] ) ? sanitize_text_field( wp_unslash( $_POST['consultation_time'] ) ) : '';
		$first_name = isset( $_POST['first_name'] )        ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) )        : '';
		$last_name  = isset( $_POST['last_name'] )         ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) )         : '';
		$phone      = isset( $_POST['phone'] )             ? sanitize_text_field( wp_unslash( $_POST['phone'] ) )             : '';
		$email      = isset( $_POST['email'] )             ? sanitize_email( wp_unslash( $_POST['email'] ) )                  : '';
		$notes      = isset( $_POST['notes'] )             ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) )         : '';

		// Validate required fields.
		if ( empty( $date ) || empty( $time ) || empty( $first_name ) || empty( $last_name ) || empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'kevincho-tailoring-manager' ) ) );
		}

		// Validate date format.
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'kevincho-tailoring-manager' ) ) );
		}

		// Check slot is still available.
		if ( ! KCTM_Consultation_Booking::is_slot_available( $date, $time ) ) {
			wp_send_json_error( array( 'message' => __( 'Sorry, this time slot is no longer available. Please choose another.', 'kevincho-tailoring-manager' ) ) );
		}

		// Add consultation product to cart with booking data.
		$booking_data = array(
			'consultation_date' => $date,
			'consultation_time' => $time,
			'first_name'        => $first_name,
			'last_name'         => $last_name,
			'phone'             => $phone,
			'email'             => $email,
			'notes'             => $notes,
		);

		$added = KCTM_Consultation_Product::add_to_cart( $booking_data );

		if ( ! $added ) {
			wp_send_json_error( array( 'message' => __( 'Could not add consultation to cart. Please try again.', 'kevincho-tailoring-manager' ) ) );
		}

		wp_send_json_success( array(
			'message'  => __( 'Redirecting to checkout...', 'kevincho-tailoring-manager' ),
			'redirect' => wc_get_checkout_url(),
		) );
	}

	/* ================================================================
	 * Consultation: Cancel (Admin)
	 * ============================================================= */

	/**
	 * Cancel a consultation booking (admin only).
	 *
	 * @return void
	 */
	public static function cancel_consultation() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => __( 'No booking ID provided.', 'kevincho-tailoring-manager' ) ) );
		}

		$result = KCTM_Consultation_Booking::cancel_booking( $booking_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Consultation cancelled.', 'kevincho-tailoring-manager' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to cancel consultation.', 'kevincho-tailoring-manager' ) ) );
		}
	}

	/* ================================================================
	 * Consultation: Complete (Admin)
	 * ============================================================= */

	/**
	 * Mark a consultation as completed (admin only).
	 *
	 * @return void
	 */
	public static function complete_consultation() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => __( 'No booking ID provided.', 'kevincho-tailoring-manager' ) ) );
		}

		$result = KCTM_Consultation_Booking::complete_booking( $booking_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Consultation marked as completed.', 'kevincho-tailoring-manager' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update consultation.', 'kevincho-tailoring-manager' ) ) );
		}
	}

	/* ================================================================
	 * Consultation: Resend Notification (Admin)
	 * ============================================================= */

	/**
	 * Resend a WhatsApp confirmation for a consultation (admin only).
	 *
	 * @return void
	 */
	public static function resend_consultation_notification() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kevincho-tailoring-manager' ) ) );
		}

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => __( 'No booking ID provided.', 'kevincho-tailoring-manager' ) ) );
		}

		$result = KCTM_Consultation_Notifications::send_confirmation( $booking_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Notification sent.', 'kevincho-tailoring-manager' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send notification.', 'kevincho-tailoring-manager' ) ) );
		}
	}

	/* ================================================================
	 * Suit Configurator: Add Configured Suit to Cart
	 * ============================================================= */

	/**
	 * Add a configured suit to the WooCommerce cart.
	 *
	 * Receives product_id, fabric_id, personalization choices (group_slug => option_slug),
	 * and optional monogram_text. Validates all selections against the database,
	 * calculates price modifiers, and adds the product to cart with full custom data.
	 *
	 * @return void
	 */
	public static function add_configured_suit() {
		check_ajax_referer( 'kctm_configurator_nonce' );

		$product_id    = isset( $_POST['product_id'] )    ? absint( $_POST['product_id'] )    : 0;
		$fabric_id     = isset( $_POST['fabric_id'] )     ? absint( $_POST['fabric_id'] )     : 0;
		$posted        = isset( $_POST['personalization'] ) && is_array( $_POST['personalization'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['personalization'] ) )
			: array();
		$monogram_text = isset( $_POST['monogram_text'] ) ? sanitize_text_field( wp_unslash( $_POST['monogram_text'] ) ) : '';

		// Validate product.
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'kevincho-tailoring-manager' ) ) );
		}

		// Validate fabric.
		$fabric = KCTM_Fabric_Catalog::get_fabric( $fabric_id );
		if ( ! $fabric ) {
			wp_send_json_error( array( 'message' => __( 'Please select a valid fabric.', 'kevincho-tailoring-manager' ) ) );
		}

		// Validate personalization choices against database.
		$groups          = KCTM_Personalization_Options::get_groups_with_options();
		$personalization = array();
		$total_modifier  = 0;

		$group_map = array();
		foreach ( $groups as $group ) {
			$option_map = array();
			foreach ( $group->options as $option ) {
				$option_map[ $option->slug ] = $option;
			}
			$group->options_map        = $option_map;
			$group_map[ $group->slug ] = $group;
		}

		foreach ( $posted as $group_slug => $option_slug ) {
			if ( ! isset( $group_map[ $group_slug ] ) ) {
				continue;
			}

			$group = $group_map[ $group_slug ];

			if ( ! isset( $group->options_map[ $option_slug ] ) ) {
				continue;
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

		// Add fabric price modifier.
		$fabric_modifier = floatval( $fabric->price_modifier );
		$total_modifier += $fabric_modifier;

		// Build cart item data.
		$cart_item_data = array(
			'kctm_personalization' => array(
				'choices'        => $personalization,
				'monogram_text'  => $monogram_text,
				'total_modifier' => $total_modifier,
				'fabric'         => array(
					'id'             => $fabric->id,
					'name'           => $fabric->name,
					'color_hex'      => $fabric->color_hex,
					'pattern_type'   => $fabric->pattern_type,
					'price_modifier' => $fabric_modifier,
				),
			),
		);

		// Add to WooCommerce cart.
		$cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

		if ( ! $cart_item_key ) {
			wp_send_json_error( array( 'message' => __( 'Could not add to cart. Please try again.', 'kevincho-tailoring-manager' ) ) );
		}

		wp_send_json_success( array(
			'message'  => __( 'Custom suit added to cart!', 'kevincho-tailoring-manager' ),
			'redirect' => wc_get_cart_url(),
		) );
	}

	/* ================================================================
	 * Save My Measurements (Frontend)
	 * ============================================================= */

	/**
	 * Save measurements for the currently logged-in user.
	 *
	 * Hooked to `wp_ajax_kctm_save_my_measurements`. Used by the
	 * frontend My Account measurement form.
	 *
	 * @return void
	 */
	public static function save_my_measurements() {
		check_ajax_referer( 'kctm_save_measurements' );

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in to save measurements.', 'kevincho-tailoring-manager' ),
			) );
		}

		// Parse measurement data from the form.
		$data = array();

		if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
			parse_str( wp_unslash( $_POST['data'] ), $data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} elseif ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) {
			$data = $_POST['data']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} else {
			$fields = KCTM_Measurement_Fields::get_all_fields();
			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field['key'] ] ) ) {
					$data[ $field['key'] ] = sanitize_text_field( wp_unslash( $_POST[ $field['key'] ] ) );
				}
			}
		}

		if ( empty( $data ) ) {
			wp_send_json_error( array(
				'message' => __( 'No measurement data received.', 'kevincho-tailoring-manager' ),
			) );
		}

		$result = KCTM_Measurement_Storage::save_measurements( $user_id, $data );

		if ( $result['valid'] ) {
			wp_send_json_success( array(
				'message' => __( 'Your measurements have been saved.', 'kevincho-tailoring-manager' ),
			) );
		} else {
			$error_messages = array();
			foreach ( $result['errors'] as $key => $error ) {
				$error_messages[] = $key . ': ' . $error;
			}

			wp_send_json_error( array(
				'message' => __( 'Please correct the following errors:', 'kevincho-tailoring-manager' ) . ' ' . implode( ', ', $error_messages ),
				'errors'  => $result['errors'],
			) );
		}
	}
}
