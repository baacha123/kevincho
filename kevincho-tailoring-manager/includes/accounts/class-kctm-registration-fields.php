<?php
/**
 * Registration Fields
 *
 * Adds phone and body measurements to the WooCommerce registration
 * and Edit Account forms. Measurement fields are pulled from
 * KCTM_Measurement_Fields so they always match the Store Manager portal.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_Registration_Fields {

	public static function init() {
		// Registration form.
		add_action( 'woocommerce_register_form', array( __CLASS__, 'render_registration_fields' ) );
		add_filter( 'woocommerce_registration_errors', array( __CLASS__, 'validate_registration' ), 10, 3 );
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'save_registration_data' ) );

		// Edit Account form.
		add_action( 'woocommerce_edit_account_form', array( __CLASS__, 'render_edit_account_phone_field' ) );
		add_action( 'woocommerce_save_account_details_errors', array( __CLASS__, 'validate_edit_account_phone' ) );
		add_action( 'woocommerce_save_account_details', array( __CLASS__, 'save_edit_account_phone' ) );

		// Enqueue registration JS on my-account page.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_registration_scripts' ) );
	}

	public static function enqueue_registration_scripts() {
		if ( ! is_account_page() ) {
			return;
		}

		wp_enqueue_script(
			'kctm-registration',
			KCTM_PLUGIN_URL . 'assets/js/kctm-registration.js',
			array( 'jquery' ),
			KCTM_VERSION,
			true
		);
	}

	private static function sanitize_phone( $raw ) {
		return preg_replace( '/[^\d+]/', '', $raw );
	}

	private static function is_valid_phone( $phone ) {
		$digits = preg_replace( '/[^\d]/', '', $phone );
		return strlen( $digits ) >= 8;
	}

	// ------------------------------------------------------------------
	// Registration form — render
	// ------------------------------------------------------------------

	public static function render_registration_fields() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$phone            = isset( $_POST['kctm_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_phone'] ) ) : '';
		$has_measurements = isset( $_POST['kctm_has_measurements'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_has_measurements'] ) ) : ''; // phpcs:ignore
		?>

		<!-- Phone -->
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="kctm_phone">
				<?php esc_html_e( 'Phone (WhatsApp)', 'kevincho-tailoring-manager' ); ?>
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text"
				name="kctm_phone" id="kctm_phone" autocomplete="tel"
				placeholder="+237..."
				value="<?php echo esc_attr( $phone ); ?>" required />
		</p>

		<!-- Measurements toggle -->
		<div class="kctm-reg-divider">
			<span><?php esc_html_e( 'Body Measurements', 'kevincho-tailoring-manager' ); ?></span>
		</div>

		<div class="kctm-reg-measurements-toggle">
			<p class="kctm-reg-question">
				<?php esc_html_e( 'Do you have your body measurements?', 'kevincho-tailoring-manager' ); ?>
			</p>
			<div class="kctm-reg-toggle-options">
				<label class="kctm-reg-toggle-label">
					<input type="radio" name="kctm_has_measurements" value="yes" <?php checked( $has_measurements, 'yes' ); ?>>
					<span class="kctm-reg-toggle-btn"><?php esc_html_e( 'Yes, I have them', 'kevincho-tailoring-manager' ); ?></span>
				</label>
				<label class="kctm-reg-toggle-label">
					<input type="radio" name="kctm_has_measurements" value="no" <?php checked( $has_measurements, 'no' ); ?>>
					<span class="kctm-reg-toggle-btn"><?php esc_html_e( 'No, not yet', 'kevincho-tailoring-manager' ); ?></span>
				</label>
			</div>
		</div>

		<!-- No measurements message -->
		<div class="kctm-reg-no-measurements" id="kctm-reg-no-measurements" style="display:none;">
			<div class="kctm-reg-info-card">
				<p>
					<?php esc_html_e( 'No worries! You can update your measurements anytime from your account under "My Measurements", or when you visit us in store.', 'kevincho-tailoring-manager' ); ?>
				</p>
			</div>
		</div>

		<!-- Measurements form (expandable) — fields from KCTM_Measurement_Fields -->
		<div class="kctm-reg-measurements-form" id="kctm-reg-measurements-form" style="display:none;">
			<div class="kctm-reg-info-card kctm-reg-info-partial">
				<p>
					<?php esc_html_e( 'Fill in what you have — nothing is required. You can always complete the rest later.', 'kevincho-tailoring-manager' ); ?>
				</p>
			</div>

			<!-- Measurement guide link -->
			<div class="kctm-reg-guide-link">
				<a href="#kctm-measurement-guide" id="kctm-toggle-guide">
					<?php esc_html_e( 'How to take your measurements', 'kevincho-tailoring-manager' ); ?>
					<span class="kctm-guide-arrow">&#9662;</span>
				</a>
			</div>

			<!-- Measurement guide (collapsible) -->
			<div class="kctm-measurement-guide" id="kctm-measurement-guide" style="display:none;">
				<div class="kctm-guide-content">
					<?php
					$guide_image = KCTM_PLUGIN_URL . 'assets/images/measurements/measurement-guide.svg';
					?>
					<div class="kctm-guide-image">
						<img src="<?php echo esc_url( $guide_image ); ?>" alt="<?php esc_attr_e( 'Measurement Guide', 'kevincho-tailoring-manager' ); ?>" />
					</div>
					<div class="kctm-guide-tips">
						<h4><?php esc_html_e( 'Tips for accurate measurements', 'kevincho-tailoring-manager' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'Use a flexible tape measure', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Stand naturally with arms relaxed', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Measure over light clothing', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Keep the tape snug but not tight', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Have someone help you for best results', 'kevincho-tailoring-manager' ); ?></li>
						</ul>
					</div>
				</div>
			</div>

			<?php
			/* Pull all fields from the central registry — same source as Store Manager portal */
			$profile_fields     = KCTM_Measurement_Fields::get_profile_fields();
			$measurement_fields = KCTM_Measurement_Fields::get_measurement_fields();

			$groups = array(
				'profile'    => array( 'title' => __( 'Profile', 'kevincho-tailoring-manager' ), 'fields' => array() ),
				'upper_body' => array( 'title' => __( 'Upper Body', 'kevincho-tailoring-manager' ), 'fields' => array() ),
				'core'       => array( 'title' => __( 'Core', 'kevincho-tailoring-manager' ), 'fields' => array() ),
				'lower_body' => array( 'title' => __( 'Lower Body', 'kevincho-tailoring-manager' ), 'fields' => array() ),
			);

			foreach ( array_merge( $profile_fields, $measurement_fields ) as $f ) {
				/* Skip gender — not a measurement the customer fills here */
				if ( $f['key'] === 'gender' ) {
					continue;
				}
				$g = isset( $f['group'] ) ? $f['group'] : 'profile';
				if ( isset( $groups[ $g ] ) ) {
					$groups[ $g ]['fields'][] = $f;
				}
			}

			foreach ( $groups as $group ) :
				if ( empty( $group['fields'] ) ) {
					continue;
				}
			?>
			<div class="kctm-reg-section">
				<h4 class="kctm-reg-section-title"><?php echo esc_html( $group['title'] ); ?></h4>
				<div class="kctm-reg-fields-grid">
					<?php foreach ( $group['fields'] as $f ) :
						// phpcs:ignore WordPress.Security.NonceVerification.Missing
						$val = isset( $_POST[ 'kctm_m_' . $f['key'] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'kctm_m_' . $f['key'] ] ) ) : '';
					?>
					<p class="kctm-reg-field">
						<label for="kctm_m_<?php echo esc_attr( $f['key'] ); ?>">
							<?php echo esc_html( $f['label'] ); ?>
							<?php if ( $f['unit'] ) : ?>
								<small>(<?php echo esc_html( $f['unit'] ); ?>)</small>
							<?php endif; ?>
						</label>
						<input type="text" id="kctm_m_<?php echo esc_attr( $f['key'] ); ?>"
							name="kctm_m_<?php echo esc_attr( $f['key'] ); ?>"
							class="input-text"
							value="<?php echo esc_attr( $val ); ?>" />
					</p>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// ------------------------------------------------------------------
	// Registration form — validate
	// ------------------------------------------------------------------

	public static function validate_registration( $errors, $username, $email ) {
		/* Skip strict validation for portal/admin walkin creation. */
		if ( wp_doing_ajax() && isset( $_POST['action'] ) && strpos( sanitize_text_field( wp_unslash( $_POST['action'] ) ), 'kctm_portal' ) !== false ) {
			return $errors;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_phone = isset( $_POST['kctm_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_phone'] ) ) : '';

		if ( empty( $raw_phone ) ) {
			$errors->add( 'kctm_phone_required', __( 'Phone number is required.', 'kevincho-tailoring-manager' ) );
		} elseif ( ! self::is_valid_phone( self::sanitize_phone( $raw_phone ) ) ) {
			$errors->add( 'kctm_phone_invalid', __( 'Please enter a valid phone number (at least 8 digits).', 'kevincho-tailoring-manager' ) );
		}

		/* No validation on measurements — accept anything, nothing required. */

		return $errors;
	}

	// ------------------------------------------------------------------
	// Registration form — save
	// ------------------------------------------------------------------

	public static function save_registration_data( $customer_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_phone = isset( $_POST['kctm_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_phone'] ) ) : '';

		if ( ! empty( $raw_phone ) ) {
			$phone = self::sanitize_phone( $raw_phone );
			update_user_meta( $customer_id, 'billing_phone', $phone );
			update_user_meta( $customer_id, '_kctm_phone', $phone );
		}

		$prefix = '_kctm_measurement_';

		/* Save gender if provided. */
		$gender = isset( $_POST['kctm_gender'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_gender'] ) ) : ''; // phpcs:ignore
		if ( ! empty( $gender ) ) {
			update_user_meta( $customer_id, $prefix . 'gender', $gender );
		}

		/* Save all measurement fields — pull keys from central registry.
		 * Accept any value (text, fractions, etc.), no numeric validation. */
		$has_measurements = isset( $_POST['kctm_has_measurements'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_has_measurements'] ) ) : 'no'; // phpcs:ignore
		if ( 'yes' === $has_measurements ) {
			$all_fields = array_merge(
				KCTM_Measurement_Fields::get_profile_fields(),
				KCTM_Measurement_Fields::get_measurement_fields()
			);
			foreach ( $all_fields as $f ) {
				$key = $f['key'];
				if ( $key === 'gender' ) {
					continue;
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$val = isset( $_POST[ 'kctm_m_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'kctm_m_' . $key ] ) ) : '';
				if ( '' !== $val ) {
					update_user_meta( $customer_id, $prefix . $key, $val );
				}
			}
		}
	}

	// ------------------------------------------------------------------
	// Edit Account form
	// ------------------------------------------------------------------

	public static function render_edit_account_phone_field() {
		$user_id = get_current_user_id();
		$value   = get_user_meta( $user_id, '_kctm_phone', true );
		if ( empty( $value ) ) {
			$value = get_user_meta( $user_id, 'billing_phone', true );
		}
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="kctm_phone">
				<?php esc_html_e( 'Phone (WhatsApp)', 'kevincho-tailoring-manager' ); ?>
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text"
				name="kctm_phone" id="kctm_phone" autocomplete="tel"
				value="<?php echo esc_attr( $value ); ?>" required />
		</p>
		<?php
	}

	public static function validate_edit_account_phone( $errors ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw = isset( $_POST['kctm_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_phone'] ) ) : '';
		if ( empty( $raw ) ) {
			$errors->add( 'kctm_phone_required', __( '<strong>Error</strong>: Phone number is required.', 'kevincho-tailoring-manager' ) );
			return;
		}
		if ( ! self::is_valid_phone( self::sanitize_phone( $raw ) ) ) {
			$errors->add( 'kctm_phone_invalid', __( '<strong>Error</strong>: Please enter a valid phone number (at least 8 digits).', 'kevincho-tailoring-manager' ) );
		}
	}

	public static function save_edit_account_phone( $user_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw = isset( $_POST['kctm_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_phone'] ) ) : '';
		if ( empty( $raw ) ) {
			return;
		}
		$phone = self::sanitize_phone( $raw );
		update_user_meta( $user_id, 'billing_phone', $phone );
		update_user_meta( $user_id, '_kctm_phone', $phone );
	}
}
