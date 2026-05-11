<?php
/**
 * Notification Log
 *
 * Logs all WhatsApp notification attempts to a custom database table
 * for tracking and debugging purposes.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_Notification_Log {

	/**
	 * Log a notification attempt.
	 *
	 * Inserts a row into the notification log table with details about
	 * the notification that was sent or attempted.
	 *
	 * @param array $data {
	 *     Notification data to log.
	 *
	 *     @type int    $order_id      WooCommerce order ID.
	 *     @type int    $customer_id   WordPress user ID of the customer.
	 *     @type string $phone         Recipient phone number.
	 *     @type string $status        Order status that triggered the notification.
	 *     @type string $template      WhatsApp template name (if applicable).
	 *     @type string $language      Language code (e.g. 'en', 'fr').
	 *     @type string $message       The message text that was sent.
	 *     @type int    $response_code HTTP response code from the API.
	 *     @type string $response_body Raw response body from the API.
	 * }
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public static function log( $data ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'order_id'      => 0,
			'customer_id'   => 0,
			'phone'         => '',
			'status'        => '',
			'template'      => '',
			'language'      => 'en',
			'message'       => '',
			'response_code' => 0,
			'response_body' => '',
			'channel'       => 'whatsapp',
		);

		$data = wp_parse_args( $data, $defaults );

		// Ensure channel column exists (added in v1.3.0).
		self::maybe_add_channel_column();

		return $wpdb->insert(
			$table_name,
			array(
				'order_id'      => absint( $data['order_id'] ),
				'customer_id'   => absint( $data['customer_id'] ),
				'phone'         => sanitize_text_field( $data['phone'] ),
				'status'        => sanitize_text_field( $data['status'] ),
				'template'      => sanitize_text_field( $data['template'] ),
				'language'      => sanitize_text_field( $data['language'] ),
				'message'       => sanitize_textarea_field( $data['message'] ),
				'response_code' => absint( $data['response_code'] ),
				'response_body' => wp_kses_post( $data['response_body'] ),
				'channel'       => sanitize_text_field( $data['channel'] ),
				'sent_at'       => current_time( 'mysql' ),
			),
			array(
				'%d', // order_id
				'%d', // customer_id
				'%s', // phone
				'%s', // status
				'%s', // template
				'%s', // language
				'%s', // message
				'%d', // response_code
				'%s', // response_body
				'%s', // channel
				'%s', // sent_at
			)
		);
	}

	/**
	 * Retrieve notification logs with pagination and optional filtering.
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type int $per_page    Number of results per page. Default 20.
	 *     @type int $page        Page number (1-indexed). Default 1.
	 *     @type int $order_id    Filter by WooCommerce order ID. Default null.
	 *     @type int $customer_id Filter by customer user ID. Default null.
	 * }
	 * @return array {
	 *     @type array $items Array of log entry objects.
	 *     @type int   $total Total number of matching log entries.
	 *     @type int   $pages Total number of pages.
	 * }
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'per_page'    => 20,
			'page'        => 1,
			'order_id'    => null,
			'customer_id' => null,
		);

		$args     = wp_parse_args( $args, $defaults );
		$per_page = absint( $args['per_page'] );
		$page     = max( 1, absint( $args['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		// Build WHERE clauses.
		$where  = array();
		$values = array();

		if ( null !== $args['order_id'] ) {
			$where[]  = 'order_id = %d';
			$values[] = absint( $args['order_id'] );
		}

		if ( null !== $args['customer_id'] ) {
			$where[]  = 'customer_id = %d';
			$values[] = absint( $args['customer_id'] );
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		// Get total count.
		if ( ! empty( $values ) ) {
			$count_query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} {$where_clause}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$values
			);
		} else {
			$count_query = "SELECT COUNT(*) FROM {$table_name}";
		}

		$total = (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Get the items.
		$query_values   = $values;
		$query_values[] = $per_page;
		$query_values[] = $offset;

		if ( ! empty( $values ) ) {
			$items_query = $wpdb->prepare(
				"SELECT * FROM {$table_name} {$where_clause} ORDER BY sent_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query_values
			);
		} else {
			$items_query = $wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY sent_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
	 * Get a single log entry by its ID.
	 *
	 * @param int $id Log entry ID.
	 * @return object|null Log entry object or null if not found.
	 */
	public static function get_log( $id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $id )
			)
		);
	}

	/**
	 * Get the full notification log table name including the WordPress prefix.
	 *
	 * @return string Table name.
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'kctm_notification_log';
	}

	/**
	 * Add the `channel` column if it doesn't exist yet.
	 *
	 * This is a migration helper for upgrading from v1.2.x to v1.3.0.
	 * Uses a transient to avoid running the check on every log() call.
	 *
	 * @return void
	 */
	private static function maybe_add_channel_column() {
		if ( get_transient( 'kctm_notification_log_has_channel' ) ) {
			return;
		}

		global $wpdb;
		$table = self::get_table_name();

		// Check if column exists.
		$column = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM {$table} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'channel'
		) );

		if ( empty( $column ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN channel varchar(20) NOT NULL DEFAULT 'whatsapp' AFTER response_body" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Cache for 24 hours — column check only needs to run once.
		set_transient( 'kctm_notification_log_has_channel', 1, DAY_IN_SECONDS );
	}
}
