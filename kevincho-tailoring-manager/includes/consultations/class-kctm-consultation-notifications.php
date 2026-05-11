<?php
/**
 * Consultation Notifications
 *
 * Sends WhatsApp notifications for consultation booking events
 * (confirmation, reminder, cancellation) and logs all attempts
 * to the notification log table.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Consultation_Notifications
 *
 * Provides static methods for sending consultation-related WhatsApp
 * messages using the existing KCTM_WhatsApp_API and KCTM_Notification_Log.
 */
class KCTM_Consultation_Notifications {

	/**
	 * Initialize the notifications module.
	 *
	 * No hooks are needed here; methods are called directly by the
	 * booking class when consultation events occur.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function init() {
		// Intentionally empty — methods are invoked by KCTM_Consultation_Booking.
	}

	/**
	 * Send a booking confirmation notification via WhatsApp.
	 *
	 * Retrieves the booking data, builds a confirmation message in the
	 * configured language, sends it via the WhatsApp API, and logs
	 * the result to the notification log.
	 *
	 * @since  1.0.0
	 * @param  int $booking_id The consultation booking ID.
	 * @return void
	 */
	public static function send_confirmation( $booking_id ) {
		$booking = KCTM_Consultation_Booking::get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$language = self::get_language();
		$messages = self::get_confirmation_messages();

		if ( ! isset( $messages[ $language ] ) ) {
			$language = 'en';
		}

		$message = str_replace(
			array( '{first_name}', '{date}', '{time}' ),
			array(
				$booking->first_name,
				$booking->consultation_date,
				$booking->consultation_time,
			),
			$messages[ $language ]
		);

		self::send_and_log( $booking, $message, $language, 'consultation_confirmation' );
	}

	/**
	 * Send a booking reminder notification via WhatsApp.
	 *
	 * Typically called by the cron job the day before the consultation.
	 *
	 * @since  1.0.0
	 * @param  int $booking_id The consultation booking ID.
	 * @return void
	 */
	public static function send_reminder( $booking_id ) {
		$booking = KCTM_Consultation_Booking::get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$language = self::get_language();
		$messages = self::get_reminder_messages();

		if ( ! isset( $messages[ $language ] ) ) {
			$language = 'en';
		}

		$message = str_replace(
			array( '{first_name}', '{time}' ),
			array(
				$booking->first_name,
				$booking->consultation_time,
			),
			$messages[ $language ]
		);

		self::send_and_log( $booking, $message, $language, 'consultation_reminder' );
	}

	/**
	 * Send a booking cancellation notification via WhatsApp.
	 *
	 * @since  1.0.0
	 * @param  int $booking_id The consultation booking ID.
	 * @return void
	 */
	public static function send_cancellation( $booking_id ) {
		$booking = KCTM_Consultation_Booking::get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$language = self::get_language();
		$messages = self::get_cancellation_messages();

		if ( ! isset( $messages[ $language ] ) ) {
			$language = 'en';
		}

		$message = str_replace(
			array( '{date}', '{time}' ),
			array(
				$booking->consultation_date,
				$booking->consultation_time,
			),
			$messages[ $language ]
		);

		self::send_and_log( $booking, $message, $language, 'consultation_cancellation' );
	}

	/**
	 * Send a WhatsApp message and log the result.
	 *
	 * @since  1.0.0
	 * @param  object $booking  The booking row object.
	 * @param  string $message  The message text to send.
	 * @param  string $language The language code ('en' or 'fr').
	 * @param  string $template The notification template identifier for logging.
	 * @return void
	 */
	private static function send_and_log( $booking, $message, $language, $template ) {
		$phone = $booking->phone;

		if ( empty( $phone ) ) {
			// Log the failure — no phone number available.
			KCTM_Notification_Log::log( array(
				'order_id'      => absint( $booking->order_id ),
				'customer_id'   => absint( $booking->customer_id ),
				'phone'         => '',
				'status'        => $template,
				'template'      => $template,
				'language'      => $language,
				'message'       => __( 'No phone number available for this customer.', 'kevincho-tailoring-manager' ),
				'response_code' => 0,
				'response_body' => __( 'Notification skipped: no phone number on file.', 'kevincho-tailoring-manager' ),
			) );
			return;
		}

		$api    = new KCTM_WhatsApp_API();
		$result = $api->send_text_message( $phone, $message );

		KCTM_Notification_Log::log( array(
			'order_id'      => absint( $booking->order_id ),
			'customer_id'   => absint( $booking->customer_id ),
			'phone'         => $phone,
			'status'        => $template,
			'template'      => $template,
			'language'      => $language,
			'message'       => $message,
			'response_code' => $result['response_code'],
			'response_body' => $result['response_body'],
		) );
	}

	/**
	 * Get the configured notification language.
	 *
	 * Reads the language setting from the consultation settings option,
	 * defaulting to 'en' if not configured.
	 *
	 * @since  1.0.0
	 * @return string Language code ('en' or 'fr').
	 */
	private static function get_language() {
		$settings = get_option( 'kctm_consultation_settings', array() );
		$language = isset( $settings['language'] ) ? $settings['language'] : 'en';

		if ( ! in_array( $language, array( 'en', 'fr' ), true ) ) {
			$language = 'en';
		}

		return $language;
	}

	/**
	 * Get confirmation message templates for all supported languages.
	 *
	 * @since  1.0.0
	 * @return array Associative array keyed by language code.
	 */
	private static function get_confirmation_messages() {
		return array(
			'en' => __( 'Hello {first_name}! Your consultation with Kevin Cho is confirmed for {date} at {time}. We look forward to speaking with you!', 'kevincho-tailoring-manager' ),
			'fr' => __( 'Bonjour {first_name}! Votre consultation avec Kevin Cho est confirmée pour le {date} à {time}. Au plaisir de vous parler!', 'kevincho-tailoring-manager' ),
		);
	}

	/**
	 * Get reminder message templates for all supported languages.
	 *
	 * @since  1.0.0
	 * @return array Associative array keyed by language code.
	 */
	private static function get_reminder_messages() {
		return array(
			'en' => __( 'Reminder: Your consultation with Kevin Cho is tomorrow at {time}. Please be ready!', 'kevincho-tailoring-manager' ),
			'fr' => __( 'Rappel: Votre consultation avec Kevin Cho est demain à {time}. Soyez prêt(e)!', 'kevincho-tailoring-manager' ),
		);
	}

	/**
	 * Get cancellation message templates for all supported languages.
	 *
	 * @since  1.0.0
	 * @return array Associative array keyed by language code.
	 */
	private static function get_cancellation_messages() {
		return array(
			'en' => __( 'Your consultation scheduled for {date} at {time} has been cancelled. Please contact us to reschedule.', 'kevincho-tailoring-manager' ),
			'fr' => __( 'Votre consultation prévue le {date} à {time} a été annulée. Contactez-nous pour reprogrammer.', 'kevincho-tailoring-manager' ),
		);
	}
}
