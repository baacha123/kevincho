<?php
/**
 * WhatsApp Business Cloud API Client
 *
 * Handles communication with the WhatsApp Business Cloud API
 * for sending messages to customers.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_WhatsApp_API {

	/**
	 * Meta API access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * WhatsApp Business phone number ID.
	 *
	 * @var string
	 */
	private $phone_number_id;

	/**
	 * WhatsApp Business Account ID.
	 *
	 * @var string
	 */
	private $business_account_id;

	/**
	 * Base URL for the Graph API.
	 *
	 * @var string
	 */
	private $api_base = 'https://graph.facebook.com/v21.0';

	/**
	 * Constructor.
	 *
	 * Reads WhatsApp settings from the WordPress options table.
	 */
	public function __construct() {
		$settings = get_option( 'kctm_whatsapp_settings', array() );

		$this->access_token        = isset( $settings['access_token'] ) ? $settings['access_token'] : '';
		$this->phone_number_id     = isset( $settings['phone_number_id'] ) ? $settings['phone_number_id'] : '';
		$this->business_account_id = isset( $settings['business_account_id'] ) ? $settings['business_account_id'] : '';
	}

	/**
	 * Send a template message via the WhatsApp Cloud API.
	 *
	 * @param string $to             Recipient phone number.
	 * @param string $template_name  Pre-approved template name.
	 * @param string $language       Language code (e.g. 'en', 'fr').
	 * @param array  $components     Optional template components.
	 * @return array {
	 *     @type bool        $success       Whether the message was sent successfully.
	 *     @type int         $response_code HTTP response code.
	 *     @type string      $response_body Raw response body.
	 *     @type string|null $message_id    WhatsApp message ID if successful.
	 * }
	 */
	public function send_template_message( $to, $template_name, $language, $components = array() ) {
		$to = $this->format_phone( $to );

		$body = array(
			'messaging_product' => 'whatsapp',
			'to'                => $to,
			'type'              => 'template',
			'template'          => array(
				'name'     => $template_name,
				'language' => array(
					'code' => $language,
				),
			),
		);

		if ( ! empty( $components ) ) {
			$body['template']['components'] = $components;
		}

		return $this->send_request( $body );
	}

	/**
	 * Send a free-form text message via the WhatsApp Cloud API.
	 *
	 * @param string $to   Recipient phone number.
	 * @param string $body Message text.
	 * @return array {
	 *     @type bool        $success       Whether the message was sent successfully.
	 *     @type int         $response_code HTTP response code.
	 *     @type string      $response_body Raw response body.
	 *     @type string|null $message_id    WhatsApp message ID if successful.
	 * }
	 */
	public function send_text_message( $to, $body ) {
		$to = $this->format_phone( $to );

		$payload = array(
			'messaging_product' => 'whatsapp',
			'to'                => $to,
			'type'              => 'text',
			'text'              => array(
				'body' => $body,
			),
		);

		return $this->send_request( $payload );
	}

	/**
	 * Format a phone number for the WhatsApp API.
	 *
	 * Strips all non-digit characters and ensures the number starts with
	 * a country code. Defaults to 237 (Cameroon) if no country code is detected.
	 *
	 * @param string $phone Raw phone number.
	 * @return string Formatted phone number with country code.
	 */
	public function format_phone( $phone ) {
		// Strip all non-digit characters.
		$phone = preg_replace( '/[^0-9]/', '', $phone );

		// Remove leading zeros.
		$phone = ltrim( $phone, '0' );

		if ( empty( $phone ) ) {
			return '';
		}

		// If the number does not start with a country code, prepend 237 (Cameroon).
		// Cameroon numbers are typically 9 digits (without country code).
		// Numbers starting with 237 and having 12+ digits are already formatted.
		if ( strlen( $phone ) <= 9 ) {
			$phone = '237' . $phone;
		}

		return $phone;
	}

	/**
	 * Test the API connection by verifying credentials.
	 *
	 * Makes a lightweight GET request to the phone number endpoint
	 * to confirm the access token and phone number ID are valid.
	 *
	 * @return array {
	 *     @type bool   $success Whether the connection test passed.
	 *     @type string $message Human-readable result message.
	 * }
	 */
	public function test_connection() {
		if ( empty( $this->access_token ) || empty( $this->phone_number_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'WhatsApp API credentials are not configured. Please enter your access token and phone number ID.', 'kevincho-tailoring-manager' ),
			);
		}

		$url = $this->api_base . '/' . $this->phone_number_id;

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
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
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 200 === $code ) {
			$display_name = isset( $data['verified_name'] ) ? $data['verified_name'] : '';
			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: verified business name */
					__( 'Connection successful! Verified name: %s', 'kevincho-tailoring-manager' ),
					$display_name
				),
			);
		}

		$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown error', 'kevincho-tailoring-manager' );
		return array(
			'success' => false,
			'message' => sprintf(
				/* translators: %1$d: HTTP status code, %2$s: error message */
				__( 'Connection failed (HTTP %1$d): %2$s', 'kevincho-tailoring-manager' ),
				$code,
				$error_message
			),
		);
	}

	/**
	 * Send a request to the WhatsApp Cloud API messages endpoint.
	 *
	 * @param array $body Request body as an associative array.
	 * @return array {
	 *     @type bool        $success       Whether the message was sent successfully.
	 *     @type int         $response_code HTTP response code.
	 *     @type string      $response_body Raw response body.
	 *     @type string|null $message_id    WhatsApp message ID if successful.
	 * }
	 */
	private function send_request( $body ) {
		if ( empty( $this->access_token ) || empty( $this->phone_number_id ) ) {
			return array(
				'success'       => false,
				'response_code' => 0,
				'response_body' => __( 'WhatsApp API credentials are not configured.', 'kevincho-tailoring-manager' ),
				'message_id'    => null,
			);
		}

		$url = $this->api_base . '/' . $this->phone_number_id . '/messages';

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
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

		$message_id = null;
		if ( isset( $data['messages'][0]['id'] ) ) {
			$message_id = $data['messages'][0]['id'];
		}

		return array(
			'success'       => ( $response_code >= 200 && $response_code < 300 ),
			'response_code' => $response_code,
			'response_body' => $response_body,
			'message_id'    => $message_id,
		);
	}
}
