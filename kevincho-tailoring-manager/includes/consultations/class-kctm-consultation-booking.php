<?php
/**
 * Consultation Booking
 *
 * Handles the full lifecycle of consultation bookings: shortcode rendering,
 * CRUD operations on the consultations table, payment integration via
 * WooCommerce order hooks, and booking status management.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Consultation_Booking
 *
 * Manages consultation booking records and integrates with
 * WooCommerce order events for payment and cancellation handling.
 */
class KCTM_Consultation_Booking {

	/**
	 * Initialize hooks.
	 *
	 * Registers the booking shortcode and WooCommerce order status
	 * hooks for payment completion and cancellation handling.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function init() {
		// Register the booking form shortcode.
		add_shortcode( 'kctm_consultation_booking', array( __CLASS__, 'render_shortcode' ) );

		// Payment completion hooks.
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'handle_payment_complete' ) );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'handle_payment_complete' ) );

		// Cancellation / refund hooks.
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'handle_order_cancelled' ) );
		add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'handle_order_cancelled' ) );
	}

	/**
	 * Render the consultation booking form shortcode.
	 *
	 * Loads the booking form template via output buffering and returns
	 * the rendered HTML content.
	 *
	 * @since  1.0.0
	 * @param  array $atts Shortcode attributes (unused).
	 * @return string Rendered shortcode HTML.
	 */
	public static function render_shortcode( $atts ) {
		ob_start();

		$template = KCTM_PLUGIN_DIR . 'templates/consultation-booking-form.php';

		if ( file_exists( $template ) ) {
			include $template;
		}

		return ob_get_clean();
	}

	/**
	 * Create a new consultation booking record.
	 *
	 * Inserts a row into the consultations table with the provided
	 * booking data.
	 *
	 * @since  1.0.0
	 * @param  array $data {
	 *     Booking data.
	 *
	 *     @type string $first_name        Customer first name.
	 *     @type string $last_name         Customer last name.
	 *     @type string $email             Customer email address.
	 *     @type string $phone             Customer phone number.
	 *     @type string $consultation_date Consultation date (Y-m-d).
	 *     @type string $consultation_time Consultation time slot (HH:MM).
	 *     @type int    $order_id          WooCommerce order ID.
	 *     @type int    $customer_id       WordPress user ID.
	 *     @type string $payment_status    Payment status (e.g. 'pending', 'paid').
	 *     @type string $status            Booking status (e.g. 'pending', 'confirmed').
	 *     @type string $notes             Optional notes.
	 * }
	 * @return int|false The inserted booking ID, or false on failure.
	 */
	public static function create_booking( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultations';

		$defaults = array(
			'first_name'        => '',
			'last_name'         => '',
			'email'             => '',
			'phone'             => '',
			'consultation_date' => '',
			'consultation_time' => '',
			'order_id'          => 0,
			'customer_id'       => 0,
			'payment_status'    => 'pending',
			'status'            => 'pending',
			'notes'             => '',
		);

		$data = wp_parse_args( $data, $defaults );

		$inserted = $wpdb->insert(
			$table,
			array(
				'first_name'        => sanitize_text_field( $data['first_name'] ),
				'last_name'         => sanitize_text_field( $data['last_name'] ),
				'email'             => sanitize_email( $data['email'] ),
				'phone'             => sanitize_text_field( $data['phone'] ),
				'consultation_date' => sanitize_text_field( $data['consultation_date'] ),
				'consultation_time' => sanitize_text_field( $data['consultation_time'] ),
				'order_id'          => absint( $data['order_id'] ),
				'customer_id'       => absint( $data['customer_id'] ),
				'payment_status'    => sanitize_text_field( $data['payment_status'] ),
				'status'            => sanitize_text_field( $data['status'] ),
				'notes'             => sanitize_textarea_field( $data['notes'] ),
				'created_at'        => current_time( 'mysql' ),
				'updated_at'        => current_time( 'mysql' ),
			),
			array(
				'%s', // first_name
				'%s', // last_name
				'%s', // email
				'%s', // phone
				'%s', // consultation_date
				'%s', // consultation_time
				'%d', // order_id
				'%d', // customer_id
				'%s', // payment_status
				'%s', // status
				'%s', // notes
				'%s', // created_at
				'%s', // updated_at
			)
		);

		if ( false === $inserted ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get a single consultation booking by ID.
	 *
	 * @since  1.0.0
	 * @param  int $id The booking ID.
	 * @return object|null The booking row object, or null if not found.
	 */
	public static function get_booking( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultations';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $id )
			)
		);
	}

	/**
	 * Query consultation bookings with pagination and filtering.
	 *
	 * @since  1.0.0
	 * @param  array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type int    $per_page  Number of results per page. Default 20.
	 *     @type int    $page      Page number (1-indexed). Default 1.
	 *     @type string $status    Filter by booking status. Default null.
	 *     @type string $date_from Filter bookings on or after this date (Y-m-d). Default null.
	 *     @type string $date_to   Filter bookings on or before this date (Y-m-d). Default null.
	 *     @type string $search    Search term for first name, last name, email, or phone. Default null.
	 * }
	 * @return array {
	 *     @type array $items Array of booking row objects.
	 *     @type int   $total Total number of matching bookings.
	 *     @type int   $pages Total number of pages.
	 * }
	 */
	public static function get_bookings( $args = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultations';

		$defaults = array(
			'per_page'  => 20,
			'page'      => 1,
			'status'    => null,
			'date_from' => null,
			'date_to'   => null,
			'search'    => null,
		);

		$args     = wp_parse_args( $args, $defaults );
		$per_page = absint( $args['per_page'] );
		$page     = max( 1, absint( $args['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		// Build WHERE clauses.
		$where  = array();
		$values = array();

		if ( null !== $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}

		if ( null !== $args['date_from'] ) {
			$where[]  = 'consultation_date >= %s';
			$values[] = sanitize_text_field( $args['date_from'] );
		}

		if ( null !== $args['date_to'] ) {
			$where[]  = 'consultation_date <= %s';
			$values[] = sanitize_text_field( $args['date_to'] );
		}

		if ( null !== $args['search'] && '' !== $args['search'] ) {
			$search_term = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]     = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)';
			$values[]    = $search_term;
			$values[]    = $search_term;
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		// Get total count.
		if ( ! empty( $values ) ) {
			$count_query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} {$where_clause}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$values
			);
		} else {
			$count_query = "SELECT COUNT(*) FROM {$table}";
		}

		$total = (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Get the items.
		$query_values   = $values;
		$query_values[] = $per_page;
		$query_values[] = $offset;

		if ( ! empty( $values ) ) {
			$items_query = $wpdb->prepare(
				"SELECT * FROM {$table} {$where_clause} ORDER BY consultation_date DESC, consultation_time DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query_values
			);
		} else {
			$items_query = $wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY consultation_date DESC, consultation_time DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$per_page,
				$offset
			);
		}

		$items = $wpdb->get_results( $items_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$pages = ( $per_page > 0 ) ? (int) ceil( $total / $per_page ) : 1;

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
			'pages' => $pages,
		);
	}

	/**
	 * Update a consultation booking record.
	 *
	 * @since  1.0.0
	 * @param  int   $id   The booking ID.
	 * @param  array $data Associative array of columns to update.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function update_booking( $id, $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultations';

		// Sanitize known fields.
		$sanitized = array();
		$formats   = array();

		$text_fields = array( 'first_name', 'last_name', 'phone', 'consultation_date', 'consultation_time', 'payment_status', 'status' );
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
				$formats[]           = '%s';
			}
		}

		if ( isset( $data['email'] ) ) {
			$sanitized['email'] = sanitize_email( $data['email'] );
			$formats[]          = '%s';
		}

		if ( isset( $data['notes'] ) ) {
			$sanitized['notes'] = sanitize_textarea_field( $data['notes'] );
			$formats[]          = '%s';
		}

		$int_fields = array( 'order_id', 'customer_id', 'reminder_sent' );
		foreach ( $int_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = absint( $data[ $field ] );
				$formats[]           = '%d';
			}
		}

		if ( empty( $sanitized ) ) {
			return false;
		}

		// Always update the timestamp.
		$sanitized['updated_at'] = current_time( 'mysql' );
		$formats[]               = '%s';

		return $wpdb->update(
			$table,
			$sanitized,
			array( 'id' => absint( $id ) ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Cancel a consultation booking.
	 *
	 * Updates the booking status to 'cancelled' and triggers
	 * a cancellation notification.
	 *
	 * @since  1.0.0
	 * @param  int $id The booking ID.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function cancel_booking( $id ) {
		$result = self::update_booking( $id, array( 'status' => 'cancelled' ) );

		if ( $result ) {
			KCTM_Consultation_Notifications::send_cancellation( $id );
			do_action( 'kctm_consultation_cancelled', $id );
		}

		return $result;
	}

	/**
	 * Mark a consultation booking as completed.
	 *
	 * Updates the booking status to 'completed'.
	 *
	 * @since  1.0.0
	 * @param  int $id The booking ID.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function complete_booking( $id ) {
		return self::update_booking( $id, array( 'status' => 'completed' ) );
	}

	/**
	 * Handle payment completion for a WooCommerce order.
	 *
	 * Checks if the order contains the consultation product. If so,
	 * retrieves the consultation booking data from the order item meta,
	 * creates or updates the booking record with a confirmed status,
	 * and triggers a confirmation notification.
	 *
	 * @since  1.0.0
	 * @param  int $order_id The WooCommerce order ID.
	 * @return void
	 */
	public static function handle_payment_complete( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$consultation_product_id = KCTM_Consultation_Product::get_product_id();

		if ( ! $consultation_product_id ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( $item->get_product_id() !== $consultation_product_id ) {
				continue;
			}

			$consultation_data = $item->get_meta( '_kctm_consultation' );

			if ( empty( $consultation_data ) ) {
				continue;
			}

			// Check if a booking already exists for this order.
			$existing_booking = self::get_booking_by_order_id( $order_id );

			if ( $existing_booking ) {
				// Update existing booking.
				self::update_booking( $existing_booking->id, array(
					'status'         => 'confirmed',
					'payment_status' => 'paid',
				) );

				KCTM_Consultation_Notifications::send_confirmation( $existing_booking->id );
				do_action( 'kctm_consultation_confirmed', $existing_booking->id );
			} else {
				// Create a new booking record.
				$booking_id = self::create_booking( array(
					'first_name'        => isset( $consultation_data['first_name'] ) ? $consultation_data['first_name'] : '',
					'last_name'         => isset( $consultation_data['last_name'] ) ? $consultation_data['last_name'] : '',
					'email'             => isset( $consultation_data['email'] ) ? $consultation_data['email'] : '',
					'phone'             => isset( $consultation_data['phone'] ) ? $consultation_data['phone'] : '',
					'consultation_date' => isset( $consultation_data['consultation_date'] ) ? $consultation_data['consultation_date'] : '',
					'consultation_time' => isset( $consultation_data['consultation_time'] ) ? $consultation_data['consultation_time'] : '',
					'order_id'          => $order_id,
					'customer_id'       => $order->get_customer_id(),
					'payment_status'    => 'paid',
					'status'            => 'confirmed',
					'notes'             => isset( $consultation_data['notes'] ) ? $consultation_data['notes'] : '',
				) );

				if ( $booking_id ) {
					KCTM_Consultation_Notifications::send_confirmation( $booking_id );
					do_action( 'kctm_consultation_confirmed', $booking_id );
				}
			}

			break; // Only process one consultation per order.
		}
	}

	/**
	 * Handle order cancellation or refund.
	 *
	 * If the cancelled/refunded order contains a consultation product,
	 * cancels the related booking record.
	 *
	 * @since  1.0.0
	 * @param  int $order_id The WooCommerce order ID.
	 * @return void
	 */
	public static function handle_order_cancelled( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$consultation_product_id = KCTM_Consultation_Product::get_product_id();

		if ( ! $consultation_product_id ) {
			return;
		}

		$has_consultation = false;
		foreach ( $order->get_items() as $item ) {
			if ( $item->get_product_id() === $consultation_product_id ) {
				$has_consultation = true;
				break;
			}
		}

		if ( ! $has_consultation ) {
			return;
		}

		$booking = self::get_booking_by_order_id( $order_id );

		if ( $booking && 'cancelled' !== $booking->status ) {
			self::cancel_booking( $booking->id );
		}
	}

	/**
	 * Check if a specific date and time slot is available.
	 *
	 * Verifies that no confirmed or pending booking exists for
	 * the given date and time combination.
	 *
	 * @since  1.0.0
	 * @param  string $date The consultation date (Y-m-d).
	 * @param  string $time The consultation time (HH:MM).
	 * @return bool True if the slot is available, false if already booked.
	 */
	public static function is_slot_available( $date, $time ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultations';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE consultation_date = %s AND consultation_time = %s AND status IN ('pending','confirmed')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_text_field( $date ),
				sanitize_text_field( $time )
			)
		);

		return 0 === (int) $count;
	}

	/**
	 * Get a booking by its associated WooCommerce order ID.
	 *
	 * @since  1.0.0
	 * @param  int $order_id The WooCommerce order ID.
	 * @return object|null The booking row object, or null if not found.
	 */
	private static function get_booking_by_order_id( $order_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultations';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE order_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $order_id )
			)
		);
	}
}
