<?php
/**
 * Admin Walk-in Customer Creation
 *
 * Provides the interface and processing logic for registering
 * walk-in customers directly from the admin panel.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Admin_Walkin
 *
 * Handles rendering the walk-in customer form and processing
 * the form submission to create a new WordPress user with
 * the 'customer' role and walk-in metadata.
 */
class KCTM_Admin_Walkin {

	/**
	 * Render the walk-in customer creation page.
	 *
	 * Loads the admin template file for the walk-in form.
	 *
	 * @return void
	 */
	public static function render() {
		$template = KCTM_PLUGIN_DIR . 'templates/admin/walk-in-customer.php';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			self::render_inline();
		}
	}

	/**
	 * Inline fallback render if the template file is not found.
	 *
	 * @return void
	 */
	private static function render_inline() {
		$fields  = KCTM_Measurement_Fields::get_all_fields();
		$success = isset( $_GET['created'] ) && '1' === $_GET['created']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Add Walk-in Customer', 'kevincho-tailoring-manager' ); ?></h1>

			<?php if ( $success ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Walk-in customer created successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'kctm_walkin_nonce', 'kctm_nonce' ); ?>
				<input type="hidden" name="action" value="kctm_create_walkin">

				<h2><?php esc_html_e( 'Customer Information', 'kevincho-tailoring-manager' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="first_name"><?php esc_html_e( 'First Name', 'kevincho-tailoring-manager' ); ?> <span class="required" style="color:#d63638;">*</span></label></th>
						<td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="last_name"><?php esc_html_e( 'Last Name', 'kevincho-tailoring-manager' ); ?> <span class="required" style="color:#d63638;">*</span></label></th>
						<td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="phone"><?php esc_html_e( 'Phone Number', 'kevincho-tailoring-manager' ); ?> <span class="required" style="color:#d63638;">*</span></label></th>
						<td><input type="tel" name="phone" id="phone" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="email"><?php esc_html_e( 'Email', 'kevincho-tailoring-manager' ); ?></label></th>
						<td>
							<input type="email" name="email" id="email" class="regular-text">
							<p class="description"><?php esc_html_e( 'Optional. A placeholder email will be generated if left empty.', 'kevincho-tailoring-manager' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gender"><?php esc_html_e( 'Gender', 'kevincho-tailoring-manager' ); ?></label></th>
						<td>
							<select name="gender" id="gender">
								<option value=""><?php esc_html_e( '-- Select --', 'kevincho-tailoring-manager' ); ?></option>
								<option value="male"><?php esc_html_e( 'Male', 'kevincho-tailoring-manager' ); ?></option>
								<option value="female"><?php esc_html_e( 'Female', 'kevincho-tailoring-manager' ); ?></option>
								<option value="child"><?php esc_html_e( 'Child', 'kevincho-tailoring-manager' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Measurements (Optional)', 'kevincho-tailoring-manager' ); ?></h2>
				<p class="description"><?php esc_html_e( 'You can add measurements now or later from the customer measurements page.', 'kevincho-tailoring-manager' ); ?></p>

				<table class="form-table">
					<?php foreach ( $fields as $field ) : ?>
						<?php if ( 'gender' === $field['key'] ) {
							continue;
						} ?>
						<tr>
							<th scope="row">
								<label for="measurement_<?php echo esc_attr( $field['key'] ); ?>">
									<?php echo esc_html( $field['label'] ); ?>
									<?php if ( $field['unit'] ) : ?>
										<small>(<?php echo esc_html( $field['unit'] ); ?>)</small>
									<?php endif; ?>
								</label>
							</th>
							<td>
								<?php if ( 'select' === $field['type'] && ! empty( $field['options'] ) ) : ?>
									<select name="measurements[<?php echo esc_attr( $field['key'] ); ?>]" id="measurement_<?php echo esc_attr( $field['key'] ); ?>">
										<option value=""><?php esc_html_e( '-- Select --', 'kevincho-tailoring-manager' ); ?></option>
										<?php foreach ( $field['options'] as $val => $label ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php else : ?>
									<input type="number"
										   name="measurements[<?php echo esc_attr( $field['key'] ); ?>]"
										   id="measurement_<?php echo esc_attr( $field['key'] ); ?>"
										   step="0.1"
										   class="small-text"
										   <?php if ( $field['min'] ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
										   <?php if ( $field['max'] ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
									>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php submit_button( __( 'Create Walk-in Customer', 'kevincho-tailoring-manager' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Process the walk-in customer creation form.
	 *
	 * Hooked to `admin_post_kctm_create_walkin`.
	 *
	 * @return void
	 */
	public static function process() {
		// Verify nonce.
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_walkin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		// Get required fields.
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] )  ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) )  : '';
		$phone      = isset( $_POST['phone'] )       ? sanitize_text_field( wp_unslash( $_POST['phone'] ) )      : '';
		$email      = isset( $_POST['email'] )        ? sanitize_email( wp_unslash( $_POST['email'] ) )           : '';
		$gender     = isset( $_POST['gender'] )       ? sanitize_text_field( wp_unslash( $_POST['gender'] ) )     : '';

		if ( empty( $first_name ) || empty( $last_name ) || empty( $phone ) ) {
			wp_die( esc_html__( 'First name, last name, and phone number are required.', 'kevincho-tailoring-manager' ) );
		}

		// Generate placeholder email if none provided.
		if ( empty( $email ) ) {
			$clean_phone = preg_replace( '/[^0-9]/', '', $phone );
			$email       = 'walkin_' . $clean_phone . '@kevincho.local';
		}

		// Check if email is already in use.
		$existing_user = email_exists( $email );
		if ( $existing_user ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=kctm-customer-measurements&customer_id=' . $existing_user )
			);
			exit;
		}

		// Create user via WooCommerce helper or wp_insert_user fallback.
		$username = sanitize_user( strtolower( $first_name . '.' . $last_name . '.' . substr( md5( $phone ), 0, 4 ) ) );
		$password = wp_generate_password( 12, true );

		if ( function_exists( 'wc_create_new_customer' ) ) {
			$user_id = wc_create_new_customer( $email, $username, $password );
		} else {
			$user_id = wp_insert_user( array(
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => $password,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'role'       => 'customer',
			) );
		}

		if ( is_wp_error( $user_id ) ) {
			wp_die( esc_html( $user_id->get_error_message() ) );
		}

		// Set user meta.
		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'last_name', $last_name );
		update_user_meta( $user_id, '_kctm_customer_type', 'walkin' );
		update_user_meta( $user_id, '_kctm_phone', $phone );
		update_user_meta( $user_id, 'billing_phone', $phone );
		update_user_meta( $user_id, 'billing_first_name', $first_name );
		update_user_meta( $user_id, 'billing_last_name', $last_name );
		update_user_meta( $user_id, 'billing_email', $email );

		// Set display name.
		wp_update_user( array(
			'ID'           => $user_id,
			'display_name' => $first_name . ' ' . $last_name,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
		) );

		// Save gender if provided.
		if ( ! empty( $gender ) && in_array( $gender, array( 'male', 'female', 'child' ), true ) ) {
			update_user_meta( $user_id, '_kctm_measurement_gender', $gender );
		}

		// Save measurements if submitted.
		$measurements = isset( $_POST['measurements'] ) && is_array( $_POST['measurements'] ) ? $_POST['measurements'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Filter out empty values.
		$measurements = array_filter( $measurements, function ( $value ) {
			return '' !== $value && null !== $value;
		} );

		if ( ! empty( $measurements ) ) {
			// Include gender in measurement data for validation.
			if ( ! empty( $gender ) && ! isset( $measurements['gender'] ) ) {
				$measurements['gender'] = $gender;
			}

			KCTM_Measurement_Storage::save_measurements( $user_id, $measurements );
		}

		// Fire the walk-in customer created hook for notifications.
		do_action( 'kctm_walkin_customer_created', $user_id );

		// Redirect to customer measurements page.
		wp_safe_redirect(
			admin_url( 'admin.php?page=kctm-customer-measurements&customer_id=' . $user_id . '&created=1' )
		);
		exit;
	}
}

/* ── Hook the form processor ─────────────────────────────── */
add_action( 'admin_post_kctm_create_walkin', array( 'KCTM_Admin_Walkin', 'process' ) );
