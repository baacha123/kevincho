<?php
/**
 * KCTM Measurement Validator
 *
 * Validates and sanitizes measurement data against field definitions.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Measurement_Validator
 *
 * Provides validation and sanitization for measurement input data.
 */
class KCTM_Measurement_Validator {

	/**
	 * Validate an array of measurement data.
	 *
	 * Checks every submitted value against its field definition, collecting errors
	 * and building a sanitized output array.
	 *
	 * @since  1.0.0
	 * @param  array  $data   Associative array of field_key => value.
	 * @param  string $gender The gender context for validation: 'male', 'female', or 'child'.
	 * @return array {
	 *     @type bool  $valid     Whether all submitted data passed validation.
	 *     @type array $errors    Associative array of field_key => error message.
	 *     @type array $sanitized Associative array of field_key => sanitized value.
	 * }
	 */
	public static function validate( $data, $gender = 'male' ) {
		$errors    = array();
		$sanitized = array();
		$gender    = sanitize_text_field( $gender );

		// Validate gender value itself.
		if ( ! in_array( $gender, array( 'male', 'female', 'child' ), true ) ) {
			$gender = 'male';
		}

		// Get fields applicable to this gender.
		$fields = KCTM_Measurement_Fields::get_fields_for_gender( $gender );

		// Build a lookup map of field definitions keyed by field key.
		$field_map = array();
		foreach ( $fields as $field ) {
			$field_map[ $field['key'] ] = $field;
		}

		// Validate each submitted value.
		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );

			// Skip keys that are not registered fields.
			if ( ! isset( $field_map[ $key ] ) ) {
				continue;
			}

			$field_def = $field_map[ $key ];

			// Sanitize the value.
			$clean = self::sanitize_value( $key, $value, $field_def );

			// Handle select fields (e.g., gender).
			if ( 'select' === $field_def['type'] ) {
				if ( ! empty( $field_def['options'] ) && ! array_key_exists( $clean, $field_def['options'] ) ) {
					$errors[ $key ] = sprintf(
						/* translators: %s: field label */
						__( '%s has an invalid selection.', 'kevincho-tailoring-manager' ),
						$field_def['label']
					);
					continue;
				}

				$sanitized[ $key ] = $clean;
				continue;
			}

			// Handle number fields.
			if ( 'number' === $field_def['type'] ) {
				// Required field check.
				if ( $field_def['required'] && ( '' === $value || null === $value ) ) {
					$errors[ $key ] = sprintf(
						/* translators: %s: field label */
						__( '%s is required.', 'kevincho-tailoring-manager' ),
						$field_def['label']
					);
					continue;
				}

				// Skip optional empty fields.
				if ( ! $field_def['required'] && ( '' === $value || null === $value ) ) {
					$sanitized[ $key ] = '';
					continue;
				}

				// Must be a positive number.
				if ( $clean <= 0 ) {
					$errors[ $key ] = sprintf(
						/* translators: %s: field label */
						__( '%s must be a positive number.', 'kevincho-tailoring-manager' ),
						$field_def['label']
					);
					continue;
				}

				// Range validation.
				$range_error = self::validate_range( $key, $clean, $field_def, $gender );

				if ( is_wp_error( $range_error ) ) {
					$errors[ $key ] = $range_error->get_error_message();
					continue;
				}

				$sanitized[ $key ] = $clean;
				continue;
			}

			// Fallback: store sanitized string.
			$sanitized[ $key ] = $clean;
		}

		// Check for missing required fields that were not submitted at all.
		foreach ( $field_map as $key => $field_def ) {
			if ( $field_def['required'] && ! array_key_exists( $key, $data ) ) {
				$errors[ $key ] = sprintf(
					/* translators: %s: field label */
					__( '%s is required.', 'kevincho-tailoring-manager' ),
					$field_def['label']
				);
			}
		}

		return array(
			'valid'     => empty( $errors ),
			'errors'    => $errors,
			'sanitized' => $sanitized,
		);
	}

	/**
	 * Sanitize a single value based on its field definition.
	 *
	 * @since  1.0.0
	 * @param  string $key       The field key.
	 * @param  mixed  $value     The raw input value.
	 * @param  array  $field_def The field definition array.
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_value( $key, $value, $field_def ) {
		if ( 'select' === $field_def['type'] ) {
			return sanitize_text_field( $value );
		}

		if ( 'number' === $field_def['type'] ) {
			// Age is always an integer.
			if ( 'age' === $key ) {
				return intval( $value );
			}

			// All other numeric fields are floats.
			return floatval( $value );
		}

		// Default string sanitization.
		return sanitize_text_field( $value );
	}

	/**
	 * Validate that a numeric value falls within the allowed range.
	 *
	 * For children, the maximum range is scaled down using a modifier to account
	 * for smaller body proportions.
	 *
	 * @since  1.0.0
	 * @param  string $key       The field key.
	 * @param  float  $value     The sanitized numeric value.
	 * @param  array  $field_def The field definition array containing min/max.
	 * @param  string $gender    The gender context.
	 * @return true|WP_Error True if within range, WP_Error with message if not.
	 */
	public static function validate_range( $key, $value, $field_def, $gender ) {
		$min = floatval( $field_def['min'] );
		$max = floatval( $field_def['max'] );

		// Skip range check for fields without defined ranges.
		if ( 0 === $min && 0 === $max ) {
			return true;
		}

		// Adjust ranges for children.
		if ( 'child' === $gender && 'age' !== $key ) {
			$modifier = self::get_child_range_modifier( $key );
			$min      = $min * $modifier;
			$max      = $max * $modifier;
		}

		if ( $value < $min ) {
			return new WP_Error(
				'below_minimum',
				sprintf(
					/* translators: 1: field label, 2: minimum value */
					__( '%1$s must be at least %2$s.', 'kevincho-tailoring-manager' ),
					$field_def['label'],
					$min
				)
			);
		}

		if ( $value > $max ) {
			return new WP_Error(
				'above_maximum',
				sprintf(
					/* translators: 1: field label, 2: maximum value */
					__( '%1$s must not exceed %2$s.', 'kevincho-tailoring-manager' ),
					$field_def['label'],
					$max
				)
			);
		}

		return true;
	}

	/**
	 * Get the child range modifier.
	 *
	 * Children's body measurements are roughly 60-80% of adult measurements.
	 * This method returns an appropriate modifier for the given field to scale
	 * the adult min/max ranges.
	 *
	 * @since  1.0.0
	 * @param  string $key Optional. The field key to get a specific modifier for.
	 * @return float The modifier multiplier (0.6 to 0.8).
	 */
	public static function get_child_range_modifier( $key = '' ) {
		// Some fields scale differently for children.
		$field_modifiers = array(
			'height'         => 0.8,
			'weight'         => 0.7,
			'chest'          => 0.7,
			'waist'          => 0.7,
			'hips'           => 0.7,
			'shoulder_width' => 0.7,
			'sleeve_length'  => 0.7,
			'inseam'         => 0.7,
			'outseam'        => 0.7,
			'thigh'          => 0.7,
			'shoe_size'      => 0.8,
		);

		if ( ! empty( $key ) && isset( $field_modifiers[ $key ] ) ) {
			return $field_modifiers[ $key ];
		}

		// Default modifier for all other fields.
		return 0.6;
	}
}
