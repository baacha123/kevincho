<?php
/**
 * Africa's Talking SMS API Client
 *
 * Sends SMS text messages via Africa's Talking API.
 * Supports both sandbox and production environments.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_SMS_API {

	/**
	 * API username.
	 *
	 * @var string
	 */
	private $username;

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Sender ID (alphanumeric, max 11 chars).
	 *
	 * @var string
	 */
	private $sender_id;

	/**
	 * Whether to use sandbox environment.
	 *
	 * @var bool
	 */
	private $sandbox;

	/**
	 * API base URLs.
	 */
	const LIVE_URL    = 'https://api.africastalking.com/version1/messaging';
	const SANDBOX_URL = 'https://api.sandbox.africastalking.com/version1/messaging';

	/**
	 * Constructor.
	 *
	 * Reads SMS settings from the WordPress options table.
	 */
	public function __construct() {
		$settings = get_option( 'kctm_sms_settings', array() );

		$this->username  = isset( $settings['username'] ) ? $settings['username'] : '';
		$this->api_key   = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$this->sender_id = isset( $settings['sender_id'] ) ? $settings['sender_id'] : 'KevinCho';
		$this->sandbox   = isset( $settings['sandbox'] ) ? (bool) $settings['sandbox'] : true;
	}

	/**
	 * Send an SMS message.
	 *
	 * @param string $to      Recipient phone number.
	 * @param string $message Message text (max 160 chars for 1 SMS, up to 918 for concatenated).
	 * @return array {
	 *     @type bool   $success       Whether the message was sent successfully.
	 *     @type int    $response_code HTTP response code.
	 *     @type string $response_body Raw response body.
	 *     @type string $message_id    Message ID if successful.
	 * }
	 */
	public function send( $to, $message ) {
		if ( empty( $this->username ) || empty( $this->api_key ) ) {
			return array(
				'success'       => false,
				'response_code' => 0,
				'response_body' => __( 'SMS API credentials are not configured.', 'kevincho-tailoring-manager' ),
				'message_id'    => null,
			);
		}

		$to  = $this->format_phone( $to );
		$url = $this->sandbox ? self::SANDBOX_URL : self::LIVE_URL;

		$body = array(
			'username' => $this->sandbox ? 'sandbox' : $this->username,
			'to'       => $to,
			'message'  => $message,
		);

		// Add sender ID only in production (sandbox doesn't support custom sender IDs).
		if ( ! $this->sandbox && ! empty( $this->sender_id ) ) {
			$body['from'] = $this->sender_id;
		}

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'apiKey'       => $this->api_key,
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Accept'       => 'application/json',
				),
				'body'    => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'       => false,
				'response_code' => 0,
				'response_body' => $response->get_error_message(),
				'message_id'    => null,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		// Africa's Talking returns 201 for successful sends.
		$success    = ( 201 === $response_code );
		$message_id = null;

		if ( $success && isset( $data['SMSMessageData']['Recipients'][0] ) ) {
			$recipient  = $data['SMSMessageData']['Recipients'][0];
			$message_id = isset( $recipient['messageId'] ) ? $recipient['messageId'] : null;

			// Check per-recipient status.
			$status_code = isset( $recipient['statusCode'] ) ? (int) $recipient['statusCode'] : 0;
			if ( $status_code !== 101 ) {
				// 101 = "Sent", anything else is a failure.
				$success = false;
			}
		}

		return array(
			'success'       => $success,
			'response_code' => $response_code,
			'response_body' => $response_body,
			'message_id'    => $message_id,
		);
	}

	/**
	 * Format a phone number for the Africa's Talking API.
	 *
	 * Africa's Talking requires phone numbers in international format
	 * with the + prefix (e.g. +237612345678).
	 *
	 * @param string $phone Raw phone number.
	 * @return string Formatted phone number.
	 */
	public function format_phone( $phone ) {
		// Strip all non-digit characters.
		$phone = preg_replace( '/[^0-9]/', '', $phone );

		// Remove leading zeros.
		$phone = ltrim( $phone, '0' );

		if ( empty( $phone ) ) {
			return '';
		}

		// If the number is 9 digits or fewer, prepend Cameroon country code.
		if ( strlen( $phone ) <= 9 ) {
			$phone = '237' . $phone;
		}

		return '+' . $phone;
	}

	/**
	 * Test the API connection.
	 *
	 * Sends a test request to check if credentials are valid.
	 * Uses the sandbox endpoint to avoid costs.
	 *
	 * @param string $test_phone Optional phone number to send a test SMS to.
	 * @return array {
	 *     @type bool   $success Whether the test passed.
	 *     @type string $message Human-readable result.
	 * }
	 */
	public function test_connection( $test_phone = '' ) {
		if ( empty( $this->username ) || empty( $this->api_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'SMS API credentials are not configured. Please enter your Africa\'s Talking username and API key.', 'kevincho-tailoring-manager' ),
			);
		}

		if ( ! empty( $test_phone ) ) {
			$result = $this->send( $test_phone, __( 'Test SMS from Kevin Cho Tailoring Manager. If you received this, your SMS integration is working!', 'kevincho-tailoring-manager' ) );

			if ( $result['success'] ) {
				return array(
					'success' => true,
					'message' => sprintf(
						/* translators: %s: phone number */
						__( 'Test SMS sent successfully to %s!', 'kevincho-tailoring-manager' ),
						$test_phone
					),
				);
			}

			$error_detail = $result['response_body'];
			$decoded      = json_decode( $error_detail, true );
			if ( isset( $decoded['SMSMessageData']['Message'] ) ) {
				$error_detail = $decoded['SMSMessageData']['Message'];
			}

			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'SMS test failed: %s', 'kevincho-tailoring-manager' ),
					$error_detail
				),
			);
		}

		// No phone number — just validate credentials by checking balance.
		$balance_url = $this->sandbox
			? 'https://api.sandbox.africastalking.com/version1/user?username=sandbox'
			: 'https://api.africastalking.com/version1/user?username=' . rawurlencode( $this->username );

		$response = wp_remote_get(
			$balance_url,
			array(
				'headers' => array(
					'apiKey' => $this->api_key,
					'Accept' => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'kevincho-tailoring-manager' ),
					$response->get_error_message()
				),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $code && isset( $body['UserData'] ) ) {
			$balance = isset( $body['UserData']['balance'] ) ? $body['UserData']['balance'] : 'N/A';
			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: account balance */
					__( 'Connection successful! Account balance: %s', 'kevincho-tailoring-manager' ),
					$balance
				),
			);
		}

		return array(
			'success' => false,
			'message' => sprintf(
				/* translators: %d: HTTP status code */
				__( 'Connection failed (HTTP %d). Please check your API key and username.', 'kevincho-tailoring-manager' ),
				$code
			),
		);
	}
}
