<?php
/**
 * Unified Notification Dispatcher
 *
 * Central hub that receives notification events and dispatches them
 * to all configured channels: Email, WhatsApp, and SMS.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_Notification_Dispatcher {

	/**
	 * All notification event types with default messages.
	 *
	 * @var array
	 */
	private static $event_definitions = null;

	/**
	 * Get all notification event definitions.
	 *
	 * Each event has: label, default_channels, and message templates (en/fr).
	 * Placeholders: {customer_name}, {order_number}, {order_total}, {site_name},
	 *               {date}, {time}, {tracking_number}, {carrier}
	 *
	 * @return array
	 */
	public static function get_event_definitions() {
		if ( null !== self::$event_definitions ) {
			return self::$event_definitions;
		}

		self::$event_definitions = array(
			// ── Order lifecycle ──
			'order_new' => array(
				'label'            => __( 'New Order Placed', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}! Thank you for your order #{order_number} ({order_total}). We\'ve received it and will begin processing shortly. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Merci pour votre commande #{order_number} ({order_total}). Nous l\'avons reçue et allons la traiter bientôt. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_processing' => array(
				'label'            => __( 'Order Processing', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array(),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}! Your order #{order_number} is now being processed. We\'ll keep you updated! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Votre commande #{order_number} est en cours de traitement. Nous vous tiendrons au courant! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_confirmed' => array(
				'label'            => __( 'Order Confirmed', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}! Your order #{order_number} has been confirmed. We\'ll start working on it soon! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Votre commande #{order_number} a été confirmée. Nous commencerons bientôt à y travailler! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_in_progress' => array(
				'label'            => __( 'Order In Progress (Tailoring)', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Great news, {customer_name}! Your order #{order_number} is now being tailored. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonne nouvelle, {customer_name}! Votre commande #{order_number} est en cours de confection. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_ready_pickup' => array(
				'label'            => __( 'Order Ready for Pickup', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp', 'sms' ),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}! Your order #{order_number} is ready for pickup! Visit us at Kevin Cho to collect your garment. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Votre commande #{order_number} est prête à être récupérée! Rendez-vous chez Kevin Cho pour récupérer votre vêtement. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_delivered' => array(
				'label'            => __( 'Order Delivered', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}! Your order #{order_number} has been delivered. Thank you for choosing Kevin Cho! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Votre commande #{order_number} a été livrée. Merci d\'avoir choisi Kevin Cho! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_completed' => array(
				'label'            => __( 'Order Completed', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}! Your order #{order_number} is complete. We hope you love it! Thank you for choosing Kevin Cho. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Votre commande #{order_number} est terminée. Nous espérons qu\'elle vous plaît! Merci d\'avoir choisi Kevin Cho. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_cancelled' => array(
				'label'            => __( 'Order Cancelled', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array(),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}, your order #{order_number} has been cancelled. If this was a mistake, please contact us. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}, votre commande #{order_number} a été annulée. Si c\'est une erreur, contactez-nous. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_refunded' => array(
				'label'            => __( 'Order Refunded', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array(),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}, your order #{order_number} has been refunded. The refund will be processed shortly. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}, votre commande #{order_number} a été remboursée. Le remboursement sera traité bientôt. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_note' => array(
				'label'            => __( 'Customer Note Added', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}, a note has been added to your order #{order_number}: {note} — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}, une note a été ajoutée à votre commande #{order_number}: {note} — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'order_tracking' => array(
				'label'            => __( 'Tracking Update', 'kevincho-tailoring-manager' ),
				'group'            => 'orders',
				'default_channels' => array( 'whatsapp', 'sms' ),
				'messages'         => array(
					'en' => __( 'Hi {customer_name}! Your order #{order_number} is on the way! Tracking: {tracking_number} via {carrier}. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Votre commande #{order_number} est en route! Suivi: {tracking_number} via {carrier}. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),

			// ── Customer account ──
			'customer_new_account' => array(
				'label'            => __( 'New Account Created', 'kevincho-tailoring-manager' ),
				'group'            => 'customers',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Welcome to Kevin Cho Tailoring, {customer_name}! Your account has been created. Visit us at kevincho.com to explore our collection. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bienvenue chez Kevin Cho Tailoring, {customer_name}! Votre compte a été créé. Visitez kevincho.com pour découvrir notre collection. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'customer_walkin_created' => array(
				'label'            => __( 'Walk-in Customer Created', 'kevincho-tailoring-manager' ),
				'group'            => 'customers',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Welcome to Kevin Cho Tailoring, {customer_name}! We\'ve created a profile for you. Visit kevincho.com to view your measurements and orders anytime. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bienvenue chez Kevin Cho Tailoring, {customer_name}! Nous avons créé un profil pour vous. Visitez kevincho.com pour voir vos mesures et commandes. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),

			// ── Consultations ──
			'consultation_confirmed' => array(
				'label'            => __( 'Consultation Confirmed', 'kevincho-tailoring-manager' ),
				'group'            => 'consultations',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Hello {customer_name}! Your consultation with Kevin Cho is confirmed for {date} at {time}. We look forward to seeing you! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Bonjour {customer_name}! Votre consultation avec Kevin Cho est confirmée pour le {date} à {time}. Au plaisir de vous voir! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'consultation_reminder' => array(
				'label'            => __( 'Consultation Reminder', 'kevincho-tailoring-manager' ),
				'group'            => 'consultations',
				'default_channels' => array( 'whatsapp', 'sms' ),
				'messages'         => array(
					'en' => __( 'Reminder: Your consultation with Kevin Cho is tomorrow at {time}. We look forward to seeing you! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Rappel: Votre consultation avec Kevin Cho est demain à {time}. Au plaisir de vous voir! — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
			'consultation_cancelled' => array(
				'label'            => __( 'Consultation Cancelled', 'kevincho-tailoring-manager' ),
				'group'            => 'consultations',
				'default_channels' => array( 'whatsapp' ),
				'messages'         => array(
					'en' => __( 'Your consultation scheduled for {date} at {time} has been cancelled. Please contact us to reschedule. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
					'fr' => __( 'Votre consultation prévue le {date} à {time} a été annulée. Contactez-nous pour reprogrammer. — Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
				),
			),
		);

		return self::$event_definitions;
	}

	/**
	 * Get the event groups for settings display.
	 *
	 * @return array
	 */
	public static function get_event_groups() {
		return array(
			'orders'        => __( 'Order Notifications', 'kevincho-tailoring-manager' ),
			'customers'     => __( 'Customer Notifications', 'kevincho-tailoring-manager' ),
			'consultations' => __( 'Consultation Notifications', 'kevincho-tailoring-manager' ),
		);
	}

	/**
	 * Get the enabled channels for a specific event.
	 *
	 * @param string $event_key Event key (e.g. 'order_ready_pickup').
	 * @return array Array of channel strings: 'whatsapp', 'sms'.
	 */
	public static function get_event_channels( $event_key ) {
		$all_settings = get_option( 'kctm_notification_channels', array() );

		if ( isset( $all_settings[ $event_key ] ) && is_array( $all_settings[ $event_key ] ) ) {
			return $all_settings[ $event_key ];
		}

		// Fall back to default channels from the event definition.
		$events = self::get_event_definitions();
		if ( isset( $events[ $event_key ] ) ) {
			return $events[ $event_key ]['default_channels'];
		}

		return array();
	}

	/**
	 * Check if a specific channel is enabled for an event.
	 *
	 * @param string $event_key Event key.
	 * @param string $channel   Channel name ('whatsapp' or 'sms').
	 * @return bool
	 */
	public static function is_channel_enabled( $event_key, $channel ) {
		$channels = self::get_event_channels( $event_key );
		return in_array( $channel, $channels, true );
	}

	/**
	 * Dispatch a notification to all configured channels.
	 *
	 * @param string $event_key   The event key (e.g. 'order_ready_pickup').
	 * @param array  $context     Context data for building the message.
	 *     @type string $phone        Recipient phone number.
	 *     @type string $email        Recipient email (for future use).
	 *     @type string $customer_name Customer display name.
	 *     @type int    $customer_id  WordPress user ID.
	 *     @type int    $order_id     WooCommerce order ID (0 if N/A).
	 *     @type string $order_number Order number string.
	 *     @type string $order_total  Formatted order total.
	 *     @type string $language     Language code ('en' or 'fr').
	 *     @type string $note         Customer note text (for order_note event).
	 *     @type string $tracking_number Tracking number.
	 *     @type string $carrier      Shipping carrier.
	 *     @type string $date         Date string.
	 *     @type string $time         Time string.
	 *     @type string $custom_message Override the default message entirely.
	 * @return array Results per channel.
	 */
	public static function dispatch( $event_key, $context = array() ) {
		$channels = self::get_event_channels( $event_key );
		$results  = array();

		if ( empty( $channels ) ) {
			return $results;
		}

		// Build the message.
		$message = self::build_message( $event_key, $context );

		if ( empty( $message ) ) {
			return $results;
		}

		$phone       = isset( $context['phone'] ) ? $context['phone'] : '';
		$customer_id = isset( $context['customer_id'] ) ? absint( $context['customer_id'] ) : 0;
		$order_id    = isset( $context['order_id'] ) ? absint( $context['order_id'] ) : 0;
		$language    = isset( $context['language'] ) ? $context['language'] : 'en';

		if ( empty( $phone ) ) {
			// Log once for all channels.
			KCTM_Notification_Log::log( array(
				'order_id'      => $order_id,
				'customer_id'   => $customer_id,
				'phone'         => '',
				'status'        => $event_key,
				'template'      => $event_key,
				'language'      => $language,
				'message'       => __( 'No phone number available.', 'kevincho-tailoring-manager' ),
				'response_code' => 0,
				'response_body' => __( 'Notification skipped: no phone number on file.', 'kevincho-tailoring-manager' ),
				'channel'       => 'none',
			) );
			return $results;
		}

		// ── Send to each channel ──
		foreach ( $channels as $channel ) {
			$result = null;

			switch ( $channel ) {
				case 'whatsapp':
					$result = self::send_whatsapp( $phone, $message );
					break;

				case 'sms':
					$result = self::send_sms( $phone, $message );
					break;
			}

			if ( null !== $result ) {
				// Log the attempt.
				KCTM_Notification_Log::log( array(
					'order_id'      => $order_id,
					'customer_id'   => $customer_id,
					'phone'         => $phone,
					'status'        => $event_key,
					'template'      => $event_key,
					'language'      => $language,
					'message'       => $message,
					'response_code' => $result['response_code'],
					'response_body' => $result['response_body'],
					'channel'       => $channel,
				) );

				$results[ $channel ] = $result;
			}
		}

		return $results;
	}

	/**
	 * Build a message from the event definition and context.
	 *
	 * @param string $event_key Event key.
	 * @param array  $context   Context data with placeholder values.
	 * @return string The built message, or empty string if event not found.
	 */
	private static function build_message( $event_key, $context ) {
		// Allow custom message override.
		if ( ! empty( $context['custom_message'] ) ) {
			return $context['custom_message'];
		}

		$events  = self::get_event_definitions();
		if ( ! isset( $events[ $event_key ] ) ) {
			return '';
		}

		$language = isset( $context['language'] ) ? $context['language'] : 'en';
		if ( ! in_array( $language, array( 'en', 'fr' ), true ) ) {
			$language = 'en';
		}

		$template = isset( $events[ $event_key ]['messages'][ $language ] )
			? $events[ $event_key ]['messages'][ $language ]
			: $events[ $event_key ]['messages']['en'];

		$placeholders = array(
			'{customer_name}'   => isset( $context['customer_name'] ) ? $context['customer_name'] : '',
			'{order_number}'    => isset( $context['order_number'] ) ? $context['order_number'] : '',
			'{order_total}'     => isset( $context['order_total'] ) ? $context['order_total'] : '',
			'{site_name}'       => get_bloginfo( 'name' ),
			'{date}'            => isset( $context['date'] ) ? $context['date'] : '',
			'{time}'            => isset( $context['time'] ) ? $context['time'] : '',
			'{tracking_number}' => isset( $context['tracking_number'] ) ? $context['tracking_number'] : '',
			'{carrier}'         => isset( $context['carrier'] ) ? $context['carrier'] : '',
			'{note}'            => isset( $context['note'] ) ? $context['note'] : '',
		);

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
	}

	/**
	 * Send a WhatsApp message.
	 *
	 * @param string $phone   Recipient phone.
	 * @param string $message Message text.
	 * @return array Result with success, response_code, response_body.
	 */
	private static function send_whatsapp( $phone, $message ) {
		$api = new KCTM_WhatsApp_API();
		return $api->send_text_message( $phone, $message );
	}

	/**
	 * Send an SMS message via Africa's Talking.
	 *
	 * @param string $phone   Recipient phone.
	 * @param string $message Message text.
	 * @return array Result with success, response_code, response_body.
	 */
	private static function send_sms( $phone, $message ) {
		$sms = new KCTM_SMS_API();
		return $sms->send( $phone, $message );
	}

	/**
	 * Helper: Extract notification context from a WooCommerce order.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array Context array ready for dispatch().
	 */
	public static function context_from_order( $order ) {
		$customer_id   = $order->get_customer_id();
		$customer_name = $order->get_billing_first_name();

		if ( empty( $customer_name ) ) {
			$customer_name = $order->get_formatted_billing_full_name();
		}

		$phone = $order->get_billing_phone();
		if ( empty( $phone ) && $customer_id ) {
			$phone = get_user_meta( $customer_id, '_kctm_phone', true );
		}

		$language = $order->get_meta( '_kctm_language' );
		if ( empty( $language ) || ! in_array( $language, array( 'en', 'fr' ), true ) ) {
			$language = 'en';
		}

		return array(
			'phone'         => $phone,
			'email'         => $order->get_billing_email(),
			'customer_name' => $customer_name,
			'customer_id'   => $customer_id,
			'order_id'      => $order->get_id(),
			'order_number'  => $order->get_order_number(),
			'order_total'   => $order->get_formatted_order_total(),
			'language'      => $language,
		);
	}

	/**
	 * Helper: Extract notification context from a consultation booking.
	 *
	 * @param object $booking Booking row from the consultations table.
	 * @return array Context array ready for dispatch().
	 */
	public static function context_from_booking( $booking ) {
		return array(
			'phone'         => isset( $booking->phone ) ? $booking->phone : '',
			'email'         => isset( $booking->email ) ? $booking->email : '',
			'customer_name' => isset( $booking->first_name ) ? $booking->first_name : '',
			'customer_id'   => isset( $booking->customer_id ) ? absint( $booking->customer_id ) : 0,
			'order_id'      => isset( $booking->order_id ) ? absint( $booking->order_id ) : 0,
			'date'          => isset( $booking->consultation_date ) ? $booking->consultation_date : '',
			'time'          => isset( $booking->consultation_time ) ? $booking->consultation_time : '',
			'language'      => self::get_consultation_language(),
		);
	}

	/**
	 * Get the consultation notification language.
	 *
	 * @return string
	 */
	private static function get_consultation_language() {
		$settings = get_option( 'kctm_consultation_settings', array() );
		$language = isset( $settings['language'] ) ? $settings['language'] : 'en';
		return in_array( $language, array( 'en', 'fr' ), true ) ? $language : 'en';
	}
}
