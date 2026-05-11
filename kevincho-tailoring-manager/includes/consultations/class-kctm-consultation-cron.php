<?php
/**
 * Consultation Cron
 *
 * Schedules and processes automated consultation reminder notifications.
 * Runs hourly to find upcoming consultations and send WhatsApp reminders
 * the day before each appointment.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Consultation_Cron
 *
 * Manages the WordPress cron event for sending consultation
 * reminder notifications one day before the appointment.
 */
class KCTM_Consultation_Cron {

	/**
	 * The cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'kctm_send_consultation_reminders';

	/**
	 * Initialize the cron module.
	 *
	 * Registers the cron action handler and schedules the recurring
	 * event if it is not already scheduled.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'process_reminders' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Process consultation reminders.
	 *
	 * Queries for confirmed consultations scheduled for tomorrow that
	 * have not yet received a reminder. For each matching booking, sends
	 * a reminder notification and marks it as sent.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function process_reminders() {
		global $wpdb;

		$table    = $wpdb->prefix . 'kctm_consultations';
		$tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );

		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE consultation_date = %s AND status = %s AND reminder_sent = 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$tomorrow,
				'confirmed'
			)
		);

		if ( empty( $bookings ) ) {
			return;
		}

		foreach ( $bookings as $booking ) {
			KCTM_Consultation_Notifications::send_reminder( $booking->id );
			do_action( 'kctm_consultation_reminder', $booking->id );

			// Mark the reminder as sent.
			$wpdb->update(
				$table,
				array(
					'reminder_sent' => 1,
					'updated_at'    => current_time( 'mysql' ),
				),
				array( 'id' => absint( $booking->id ) ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Clear the scheduled cron event.
	 *
	 * Should be called during plugin deactivation to clean up
	 * the recurring cron event.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function clear_scheduled() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}
}
