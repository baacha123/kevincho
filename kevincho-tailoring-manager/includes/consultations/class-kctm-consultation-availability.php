<?php
/**
 * Consultation Availability
 *
 * Manages consultation time slot availability, weekly schedules,
 * blocked dates, and real-time slot availability checking.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Consultation_Availability
 *
 * Provides static methods for querying and managing consultation
 * availability, including weekly schedules, blocked dates, and
 * real-time slot availability based on existing bookings.
 */
class KCTM_Consultation_Availability {

	/**
	 * Get the full weekly schedule from the availability table.
	 *
	 * Returns all rows ordered by day of week and time slot,
	 * regardless of active status.
	 *
	 * @since  1.0.0
	 * @return array Array of row objects from the availability table.
	 */
	public static function get_weekly_schedule() {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultation_availability';

		$results = $wpdb->get_results(
			"SELECT * FROM {$table} ORDER BY day_of_week ASC, time_slot ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $results ? $results : array();
	}

	/**
	 * Get active time slots for a specific day of the week.
	 *
	 * @since  1.0.0
	 * @param  int $day_of_week Day of the week (0 = Sunday, 6 = Saturday).
	 * @return array Array of active slot objects for the given day.
	 */
	public static function get_schedule_for_day( $day_of_week ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultation_availability';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE day_of_week = %d AND is_active = 1 ORDER BY time_slot ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $day_of_week )
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Save the entire weekly schedule (bulk replace).
	 *
	 * Deletes all existing availability rows and re-inserts the
	 * provided schedule data. This is an atomic bulk-save operation.
	 *
	 * @since  1.0.0
	 * @param  array $schedule_data Array of schedule entries, each with keys:
	 *                              'day' (int 0-6), 'time' (string 'HH:MM'),
	 *                              'active' (bool).
	 * @return bool True on success, false on failure.
	 */
	public static function save_weekly_schedule( $schedule_data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultation_availability';

		// Delete all existing rows.
		$wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $schedule_data ) || ! is_array( $schedule_data ) ) {
			return true;
		}

		foreach ( $schedule_data as $slot ) {
			$day_of_week = isset( $slot['day'] ) ? absint( $slot['day'] ) : 0;
			$time_slot   = isset( $slot['time'] ) ? sanitize_text_field( $slot['time'] ) : '00:00';
			$is_active   = isset( $slot['active'] ) ? ( $slot['active'] ? 1 : 0 ) : 1;

			$inserted = $wpdb->insert(
				$table,
				array(
					'day_of_week' => $day_of_week,
					'time_slot'   => $time_slot,
					'is_active'   => $is_active,
				),
				array( '%d', '%s', '%d' )
			);

			if ( false === $inserted ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get blocked dates, optionally filtering by a start date.
	 *
	 * @since  1.0.0
	 * @param  string|null $from_date Optional. Only return blocked dates on or after
	 *                                this date (format 'Y-m-d'). Default null (all).
	 * @return array Array of blocked date row objects.
	 */
	public static function get_blocked_dates( $from_date = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultation_blocked_dates';

		if ( null !== $from_date ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE blocked_date >= %s ORDER BY blocked_date ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					sanitize_text_field( $from_date )
				)
			);
		} else {
			$results = $wpdb->get_results(
				"SELECT * FROM {$table} ORDER BY blocked_date ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}

		return $results ? $results : array();
	}

	/**
	 * Add a blocked date.
	 *
	 * Inserts a new row into the blocked dates table. If the date
	 * is already blocked, the insertion may fail due to a unique key.
	 *
	 * @since  1.0.0
	 * @param  string $date   The date to block (format 'Y-m-d').
	 * @param  string $reason Optional. Reason for blocking. Default empty.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public static function add_blocked_date( $date, $reason = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultation_blocked_dates';

		return $wpdb->insert(
			$table,
			array(
				'blocked_date' => sanitize_text_field( $date ),
				'reason'       => sanitize_text_field( $reason ),
			),
			array( '%s', '%s' )
		);
	}

	/**
	 * Remove a blocked date.
	 *
	 * Deletes the row matching the specified date from the blocked dates table.
	 *
	 * @since  1.0.0
	 * @param  string $date The date to unblock (format 'Y-m-d').
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function remove_blocked_date( $date ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_consultation_blocked_dates';

		return $wpdb->delete(
			$table,
			array( 'blocked_date' => sanitize_text_field( $date ) ),
			array( '%s' )
		);
	}

	/**
	 * Get available dates for a given month.
	 *
	 * Iterates through every day in the specified month and returns
	 * dates that satisfy all of the following conditions:
	 *  - The day of the week has at least one active slot in the availability table.
	 *  - The date is not in the blocked dates table.
	 *  - The date is not in the past.
	 *  - Not all slots for that day are already booked (confirmed status).
	 *
	 * @since  1.0.0
	 * @param  int $year  The year (e.g. 2025).
	 * @param  int $month The month (1-12).
	 * @return array Array of available date strings in 'Y-m-d' format.
	 */
	public static function get_available_dates_for_month( $year, $month ) {
		global $wpdb;

		$year  = absint( $year );
		$month = absint( $month );

		if ( $year < 2000 || $month < 1 || $month > 12 ) {
			return array();
		}

		$availability_table = $wpdb->prefix . 'kctm_consultation_availability';
		$blocked_table      = $wpdb->prefix . 'kctm_consultation_blocked_dates';
		$bookings_table     = $wpdb->prefix . 'kctm_consultations';

		// Fetch all active slots grouped by day of week.
		$all_slots = $wpdb->get_results(
			"SELECT day_of_week, time_slot FROM {$availability_table} WHERE is_active = 1 ORDER BY day_of_week, time_slot", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$slots_by_day = array();
		if ( $all_slots ) {
			foreach ( $all_slots as $slot ) {
				$dow = (int) $slot['day_of_week'];
				if ( ! isset( $slots_by_day[ $dow ] ) ) {
					$slots_by_day[ $dow ] = array();
				}
				$slots_by_day[ $dow ][] = $slot['time_slot'];
			}
		}

		// If no active slots exist at all, no dates are available.
		if ( empty( $slots_by_day ) ) {
			return array();
		}

		// Fetch blocked dates for this month.
		$month_start = sprintf( '%04d-%02d-01', $year, $month );
		$days_in_month = (int) gmdate( 't', mktime( 0, 0, 0, $month, 1, $year ) );
		$month_end   = sprintf( '%04d-%02d-%02d', $year, $month, $days_in_month );

		$blocked_rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT blocked_date FROM {$blocked_table} WHERE blocked_date >= %s AND blocked_date <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$month_start,
				$month_end
			)
		);

		$blocked_set = array_flip( $blocked_rows ? $blocked_rows : array() );

		// Fetch confirmed booking counts per date for this month.
		$booking_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT consultation_date, COUNT(*) AS booked_count FROM {$bookings_table} WHERE consultation_date >= %s AND consultation_date <= %s AND status = %s GROUP BY consultation_date", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$month_start,
				$month_end,
				'confirmed'
			),
			ARRAY_A
		);

		$bookings_per_date = array();
		if ( $booking_counts ) {
			foreach ( $booking_counts as $row ) {
				$bookings_per_date[ $row['consultation_date'] ] = (int) $row['booked_count'];
			}
		}

		$today           = current_time( 'Y-m-d' );
		$available_dates = array();

		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$date_str = sprintf( '%04d-%02d-%02d', $year, $month, $day );

			// Skip dates in the past.
			if ( $date_str < $today ) {
				continue;
			}

			// Skip blocked dates.
			if ( isset( $blocked_set[ $date_str ] ) ) {
				continue;
			}

			// Check if this day of the week has active slots.
			$dow = (int) gmdate( 'w', mktime( 0, 0, 0, $month, $day, $year ) );

			if ( ! isset( $slots_by_day[ $dow ] ) ) {
				continue;
			}

			$total_slots = count( $slots_by_day[ $dow ] );
			$booked      = isset( $bookings_per_date[ $date_str ] ) ? $bookings_per_date[ $date_str ] : 0;

			// Skip if all slots are already booked.
			if ( $booked >= $total_slots ) {
				continue;
			}

			$available_dates[] = $date_str;
		}

		return $available_dates;
	}

	/**
	 * Get available time slots for a specific date.
	 *
	 * Retrieves the active slots for the date's day of the week, then
	 * subtracts any slots that already have a pending or confirmed booking.
	 *
	 * @since  1.0.0
	 * @param  string $date The date to check (format 'Y-m-d').
	 * @return array Array of available time strings (e.g. ['09:00', '10:00', '11:00']).
	 */
	public static function get_available_times_for_date( $date ) {
		global $wpdb;

		$date       = sanitize_text_field( $date );
		$day_of_week = (int) gmdate( 'w', strtotime( $date ) );

		$availability_table = $wpdb->prefix . 'kctm_consultation_availability';
		$bookings_table     = $wpdb->prefix . 'kctm_consultations';

		// Get active slots for this day of the week.
		$active_slots = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT time_slot FROM {$availability_table} WHERE day_of_week = %d AND is_active = 1 ORDER BY time_slot ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$day_of_week
			)
		);

		if ( empty( $active_slots ) ) {
			return array();
		}

		// Get booked times for this date (pending or confirmed).
		$booked_times = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT consultation_time FROM {$bookings_table} WHERE consultation_date = %s AND status IN ('pending','confirmed')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$date
			)
		);

		$booked_set = array_flip( $booked_times ? $booked_times : array() );

		$available_times = array();

		foreach ( $active_slots as $time ) {
			if ( ! isset( $booked_set[ $time ] ) ) {
				$available_times[] = $time;
			}
		}

		return $available_times;
	}
}
