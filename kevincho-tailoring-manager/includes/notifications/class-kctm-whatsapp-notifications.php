<?php
/**
 * WhatsApp Notifications for WooCommerce Orders
 *
 * Hooks into WooCommerce order status changes to send
 * WhatsApp notifications to customers via the Cloud API.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_WhatsApp_Notifications {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status_change' ), 10, 3 );
	}

	/**
	 * Handle WooCommerce order status changes.
	 *
	 * Checks whether the new status is in the configured notification trigger
	 * list, builds the appropriate message, sends it via WhatsApp, and logs
	 * the result.
	 *
	 * @param int    $order_id   WooCommerce order ID.
	 * @param string $old_status Previous order status (without wc- prefix).
	 * @param string $new_status New order status (without wc- prefix).
	 * @return void
	 */
	public static function on_status_change( $order_id, $old_status, $new_status ) {
		// If the new dispatcher system is active, skip this legacy handler
		// to avoid sending duplicate notifications.
		if ( class_exists( 'KCTM_Notification_Hooks' ) ) {
			return;
		}

		// Get the list of statuses that should trigger a notification.
		$trigger_statuses = get_option( 'kctm_notification_statuses', array( 'kctm-ready-pickup' ) );

		// WooCommerce passes statuses without the 'wc-' prefix.
		// Normalize the trigger list by stripping 'wc-' if present.
		$trigger_statuses = array_map( function ( $status ) {
			return str_replace( 'wc-', '', $status );
		}, $trigger_statuses );

		if ( ! in_array( $new_status, $trigger_statuses, true ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Get customer phone number: billing phone first, then user meta fallback.
		$phone = $order->get_billing_phone();

		if ( empty( $phone ) ) {
			$customer_id = $order->get_customer_id();
			if ( $customer_id ) {
				$phone = get_user_meta( $customer_id, '_kctm_phone', true );
			}
		}

		if ( empty( $phone ) ) {
			// Log the failure — no phone number available.
			KCTM_Notification_Log::log( array(
				'order_id'      => $order_id,
				'customer_id'   => $order->get_customer_id(),
				'phone'         => '',
				'status'        => $new_status,
				'template'      => '',
				'language'      => '',
				'message'       => __( 'No phone number available for this customer.', 'kevincho-tailoring-manager' ),
				'response_code' => 0,
				'response_body' => __( 'Notification skipped: no phone number on file.', 'kevincho-tailoring-manager' ),
			) );
			return;
		}

		// Determine customer language preference.
		$language = $order->get_meta( '_kctm_language' );
		if ( empty( $language ) || ! in_array( $language, array( 'en', 'fr' ), true ) ) {
			$language = 'en';
		}

		// Build the message.
		$messages     = self::get_status_messages();
		$order_number = $order->get_order_number();

		if ( ! isset( $messages[ $language ][ $new_status ] ) ) {
			return;
		}

		$message = str_replace( '{order_number}', $order_number, $messages[ $language ][ $new_status ] );

		// Send the message via WhatsApp API.
		$api    = new KCTM_WhatsApp_API();
		$result = $api->send_text_message( $phone, $message );

		// Log the notification attempt.
		KCTM_Notification_Log::log( array(
			'order_id'      => $order_id,
			'customer_id'   => $order->get_customer_id(),
			'phone'         => $phone,
			'status'        => $new_status,
			'template'      => '',
			'language'      => $language,
			'message'       => $message,
			'response_code' => $result['response_code'],
			'response_body' => $result['response_body'],
		) );
	}

	/**
	 * Send a manual notification to a customer for a specific order.
	 *
	 * Allows administrators to send a custom WhatsApp message to the
	 * customer associated with an order.
	 *
	 * @param int    $order_id WooCommerce order ID.
	 * @param string $message  The message text to send.
	 * @return array {
	 *     @type bool        $success       Whether the message was sent successfully.
	 *     @type int         $response_code HTTP response code.
	 *     @type string      $response_body Raw response body.
	 *     @type string|null $message_id    WhatsApp message ID if successful.
	 * }
	 */
	public static function send_manual_notification( $order_id, $message ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success'       => false,
				'response_code' => 0,
				'response_body' => __( 'Invalid order ID.', 'kevincho-tailoring-manager' ),
				'message_id'    => null,
			);
		}

		// Get customer phone number.
		$phone = $order->get_billing_phone();

		if ( empty( $phone ) ) {
			$customer_id = $order->get_customer_id();
			if ( $customer_id ) {
				$phone = get_user_meta( $customer_id, '_kctm_phone', true );
			}
		}

		if ( empty( $phone ) ) {
			return array(
				'success'       => false,
				'response_code' => 0,
				'response_body' => __( 'No phone number available for this customer.', 'kevincho-tailoring-manager' ),
				'message_id'    => null,
			);
		}

		// Determine language for the log.
		$language = $order->get_meta( '_kctm_language' );
		if ( empty( $language ) ) {
			$language = 'en';
		}

		// Send the message.
		$api    = new KCTM_WhatsApp_API();
		$result = $api->send_text_message( $phone, $message );

		// Log the notification attempt.
		KCTM_Notification_Log::log( array(
			'order_id'      => $order_id,
			'customer_id'   => $order->get_customer_id(),
			'phone'         => $phone,
			'status'        => 'manual',
			'template'      => '',
			'language'      => $language,
			'message'       => $message,
			'response_code' => $result['response_code'],
			'response_body' => $result['response_body'],
		) );

		return $result;
	}

	/**
	 * Get status-to-message mappings for both English and French.
	 *
	 * Returns an associative array keyed by language code, with each
	 * value being an associative array of order status => message template.
	 * Use {order_number} as a placeholder in messages.
	 *
	 * @return array Associative array of status messages by language.
	 */
	public static function get_status_messages() {
		return array(
			'en' => array(
				'kctm-confirmed'    => __( 'Your order #{order_number} has been confirmed. We\'ll start working on it soon!', 'kevincho-tailoring-manager' ),
				'kctm-in-progress'  => __( 'Great news! Your order #{order_number} is now being tailored.', 'kevincho-tailoring-manager' ),
				'kctm-ready-pickup' => __( 'Your order #{order_number} is ready for pickup! Visit us at KevinCho to collect your garment.', 'kevincho-tailoring-manager' ),
				'kctm-delivered'    => __( 'Your order #{order_number} has been delivered. Thank you for choosing KevinCho!', 'kevincho-tailoring-manager' ),
				'completed'         => __( 'Your order #{order_number} is complete. We hope you love it! Thank you for choosing KevinCho.', 'kevincho-tailoring-manager' ),
			),
			'fr' => array(
				'kctm-confirmed'    => __( 'Votre commande #{order_number} a été confirmée. Nous commencerons bientôt à y travailler !', 'kevincho-tailoring-manager' ),
				'kctm-in-progress'  => __( 'Bonne nouvelle ! Votre commande #{order_number} est en cours de confection.', 'kevincho-tailoring-manager' ),
				'kctm-ready-pickup' => __( 'Votre commande #{order_number} est prête à être récupérée ! Rendez-vous chez KevinCho pour récupérer votre vêtement.', 'kevincho-tailoring-manager' ),
				'kctm-delivered'    => __( 'Votre commande #{order_number} a été livrée. Merci d\'avoir choisi KevinCho !', 'kevincho-tailoring-manager' ),
				'completed'         => __( 'Votre commande #{order_number} est terminée. Nous espérons qu\'elle vous plaît ! Merci d\'avoir choisi KevinCho.', 'kevincho-tailoring-manager' ),
			),
		);
	}
}
