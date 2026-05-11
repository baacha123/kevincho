<?php
/**
 * KCTM Measurement Storage
 *
 * Handles reading and writing measurement data to WordPress user meta.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Measurement_Storage
 *
 * Persists measurement values as WordPress user meta with a consistent prefix.
 */
class KCTM_Measurement_Storage {

	/**
	 * Meta key prefix for all measurement values.
	 *
	 * @var string
	 */
	const META_PREFIX = '_kctm_measurement_';

	/**
	 * Get all measurements for a user.
	 *
	 * Reads every registered field from user meta and returns an associative array
	 * keyed by field key.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return array Associative array of field_key => value.
	 */
	public static function get_measurements( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array();
		}

		$fields       = KCTM_Measurement_Fields::get_all_fields();
		$measurements = array();

		foreach ( $fields as $field ) {
			$meta_key = self::META_PREFIX . $field['key'];
			$value    = get_user_meta( $user_id, $meta_key, true );

			$measurements[ $field['key'] ] = $value;
		}

		return $measurements;
	}

	/**
	 * Save measurements for a user.
	 *
	 * Validates and sanitizes the provided data array, then writes each value
	 * to user meta. Returns the validation result.
	 *
	 * @since  1.0.0
	 * @param  int   $user_id WordPress user ID.
	 * @param  array $data    Associative array of field_key => value to save.
	 * @return array Validation result: ['valid' => bool, 'errors' => [], 'sanitized' => []].
	 */
	public static function save_measurements( $user_id, $data ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array(
				'valid'     => false,
				'errors'    => array( 'user_id' => __( 'Invalid user ID.', 'kevincho-tailoring-manager' ) ),
				'sanitized' => array(),
			);
		}

		// Determine gender for validation context.
		$gender = isset( $data['gender'] ) ? sanitize_text_field( $data['gender'] ) : 'male';

		$result = KCTM_Measurement_Validator::validate( $data, $gender );

		if ( $result['valid'] ) {
			foreach ( $result['sanitized'] as $key => $value ) {
				$meta_key = self::META_PREFIX . $key;
				update_user_meta( $user_id, $meta_key, $value );
			}
		}

		return $result;
	}

	/**
	 * Get a single measurement value for a user.
	 *
	 * @since  1.0.0
	 * @param  int    $user_id WordPress user ID.
	 * @param  string $key     The field key (e.g., 'neck', 'chest').
	 * @return mixed The stored value, or an empty string if not set.
	 */
	public static function get_measurement( $user_id, $key ) {
		$user_id = absint( $user_id );
		$key     = sanitize_key( $key );

		if ( ! $user_id || empty( $key ) ) {
			return '';
		}

		$meta_key = self::META_PREFIX . $key;

		return get_user_meta( $user_id, $meta_key, true );
	}

	/**
	 * Save a single measurement value for a user.
	 *
	 * Sanitizes the value before saving. For full validation, use save_measurements().
	 *
	 * @since  1.0.0
	 * @param  int    $user_id WordPress user ID.
	 * @param  string $key     The field key (e.g., 'neck', 'chest').
	 * @param  mixed  $value   The value to store.
	 * @return bool True on success, false on failure.
	 */
	public static function save_measurement( $user_id, $key, $value ) {
		$user_id = absint( $user_id );
		$key     = sanitize_key( $key );

		if ( ! $user_id || empty( $key ) ) {
			return false;
		}

		// Find the field definition for proper sanitization.
		$fields    = KCTM_Measurement_Fields::get_all_fields();
		$field_def = null;

		foreach ( $fields as $field ) {
			if ( $field['key'] === $key ) {
				$field_def = $field;
				break;
			}
		}

		if ( null === $field_def ) {
			return false;
		}

		$sanitized = KCTM_Measurement_Validator::sanitize_value( $key, $value, $field_def );
		$meta_key  = self::META_PREFIX . $key;

		return (bool) update_user_meta( $user_id, $meta_key, $sanitized );
	}

	/**
	 * Get a full measurement snapshot for a user.
	 *
	 * Returns all profile and body measurement values as a single flat array,
	 * suitable for attaching to an order as a point-in-time record.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return array Associative array with all measurement and profile data plus metadata.
	 */
	public static function get_measurement_snapshot( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array();
		}

		$measurements = self::get_measurements( $user_id );

		$snapshot = array(
			'user_id'    => $user_id,
			'timestamp'  => current_time( 'mysql' ),
			'fields'     => $measurements,
		);

		return $snapshot;
	}

	/**
	 * Delete all measurement meta for a user.
	 *
	 * Removes every registered measurement and profile meta key from the user.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return bool True if any meta was deleted, false otherwise.
	 */
	public static function delete_all_measurements( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return false;
		}

		$fields  = KCTM_Measurement_Fields::get_all_fields();
		$deleted = false;

		foreach ( $fields as $field ) {
			$meta_key = self::META_PREFIX . $field['key'];
			if ( delete_user_meta( $user_id, $meta_key ) ) {
				$deleted = true;
			}
		}

		return $deleted;
	}
}
