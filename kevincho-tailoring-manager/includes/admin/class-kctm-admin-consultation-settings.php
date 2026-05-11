<?php
/**
 * Admin Consultation Settings Page
 *
 * Manages consultation module settings including pricing, weekly
 * availability schedule, blocked dates, notification language,
 * and booking editing.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Admin_Consultation_Settings
 *
 * Handles rendering the consultation settings page and processing
 * form submissions for pricing, availability, blocked dates, and
 * booking edits.
 */
class KCTM_Admin_Consultation_Settings {

	/**
	 * Initialize admin_post action hooks for form handlers.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_kctm_save_consultation_settings', array( __CLASS__, 'handle_save_consultation_settings' ) );
		add_action( 'admin_post_kctm_add_consultation_blocked_date', array( __CLASS__, 'handle_add_blocked_date' ) );
		add_action( 'admin_post_kctm_remove_consultation_blocked_date', array( __CLASS__, 'handle_remove_blocked_date' ) );
	}

	/**
	 * Render the consultation settings page.
	 *
	 * Renders the booking edit view when ?action=edit&booking_id=X is
	 * present, otherwise renders the full settings page with sections
	 * for price, weekly availability, blocked dates, and language.
	 *
	 * @return void
	 */
	public static function render() {
		// Check if we are editing a booking.
		$action     = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'edit' === $action && $booking_id > 0 ) {
			self::render_edit_booking( $booking_id );
			return;
		}

		self::render_settings_page();
	}

	/**
	 * Render the main settings page with all sections.
	 *
	 * @return void
	 */
	private static function render_settings_page() {
		$settings = get_option( 'kctm_consultation_settings', array() );
		$price    = isset( $settings['price'] ) ? $settings['price'] : '15000';
		$language = isset( $settings['language'] ) ? $settings['language'] : 'en';

		$success = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$deleted = isset( $_GET['blocked_removed'] ) && '1' === $_GET['blocked_removed']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$added   = isset( $_GET['blocked_added'] ) && '1' === $_GET['blocked_added']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Load weekly availability data.
		global $wpdb;
		$availability_table = $wpdb->prefix . 'kctm_consultation_availability';

		$availability_rows = array();
		$table_exists      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $availability_table ) );

		if ( $table_exists ) {
			$rows = $wpdb->get_results( "SELECT day_of_week, time_slot FROM {$availability_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			foreach ( $rows as $row ) {
				$availability_rows[ $row->day_of_week . '_' . $row->time_slot ] = true;
			}
		}

		// Load blocked dates.
		$blocked_table = $wpdb->prefix . 'kctm_consultation_blocked_dates';
		$blocked_dates = array();

		$blocked_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $blocked_table ) );

		if ( $blocked_table_exists ) {
			$blocked_dates = $wpdb->get_results( "SELECT * FROM {$blocked_table} ORDER BY blocked_date ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Day and time definitions.
		$days = array(
			'monday'    => __( 'Monday', 'kevincho-tailoring-manager' ),
			'tuesday'   => __( 'Tuesday', 'kevincho-tailoring-manager' ),
			'wednesday' => __( 'Wednesday', 'kevincho-tailoring-manager' ),
			'thursday'  => __( 'Thursday', 'kevincho-tailoring-manager' ),
			'friday'    => __( 'Friday', 'kevincho-tailoring-manager' ),
			'saturday'  => __( 'Saturday', 'kevincho-tailoring-manager' ),
			'sunday'    => __( 'Sunday', 'kevincho-tailoring-manager' ),
		);

		$time_slots = array(
			'09:00', '10:00', '11:00', '12:00',
			'13:00', '14:00', '15:00', '16:00', '17:00',
		);

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Consultation Settings', 'kevincho-tailoring-manager' ); ?></h1>

			<?php if ( $success ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $deleted ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Blocked date removed successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $added ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Blocked date added successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Main Settings Form (Price, Schedule, Language) -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'kctm_save_consultation_settings_nonce', 'kctm_nonce' ); ?>
				<input type="hidden" name="action" value="kctm_save_consultation_settings">

				<!-- Section 1: Consultation Price -->
				<h2><?php esc_html_e( 'Consultation Price', 'kevincho-tailoring-manager' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="consultation_price"><?php esc_html_e( 'Price', 'kevincho-tailoring-manager' ); ?></label>
						</th>
						<td>
							<input type="number"
								   name="consultation_price"
								   id="consultation_price"
								   class="regular-text"
								   value="<?php echo esc_attr( $price ); ?>"
								   min="0"
								   step="1">
							<p class="description">
								<?php
								printf(
									/* translators: %s: currency symbol */
									esc_html__( 'Consultation fee in %s. Default: 15000.', 'kevincho-tailoring-manager' ),
									esc_html( function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : 'CFA' )
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<!-- Section 2: Weekly Availability Schedule -->
				<h2><?php esc_html_e( 'Weekly Availability Schedule', 'kevincho-tailoring-manager' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Check the time slots when consultations are available for each day of the week.', 'kevincho-tailoring-manager' ); ?></p>

				<table class="widefat striped" style="margin-top:10px;max-width:900px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Day', 'kevincho-tailoring-manager' ); ?></th>
							<?php foreach ( $time_slots as $slot ) : ?>
								<th style="text-align:center;"><?php echo esc_html( $slot ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $days as $day_key => $day_label ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $day_label ); ?></strong></td>
								<?php foreach ( $time_slots as $slot ) : ?>
									<?php
									$checkbox_key = $day_key . '_' . $slot;
									$is_checked   = isset( $availability_rows[ $checkbox_key ] );
									?>
									<td style="text-align:center;">
										<input type="checkbox"
											   name="availability[<?php echo esc_attr( $day_key ); ?>][]"
											   value="<?php echo esc_attr( $slot ); ?>"
											   <?php checked( $is_checked ); ?>>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Section 4: Notification Language -->
				<h2><?php esc_html_e( 'Notification Language', 'kevincho-tailoring-manager' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Language', 'kevincho-tailoring-manager' ); ?></th>
						<td>
							<fieldset>
								<label style="display:block;margin-bottom:8px;">
									<input type="radio"
										   name="consultation_language"
										   value="en"
										   <?php checked( $language, 'en' ); ?>>
									<?php esc_html_e( 'English', 'kevincho-tailoring-manager' ); ?>
								</label>
								<label style="display:block;margin-bottom:8px;">
									<input type="radio"
										   name="consultation_language"
										   value="fr"
										   <?php checked( $language, 'fr' ); ?>>
									<?php esc_html_e( 'French', 'kevincho-tailoring-manager' ); ?>
								</label>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Language used for consultation notification messages sent to customers.', 'kevincho-tailoring-manager' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'kevincho-tailoring-manager' ) ); ?>
			</form>

			<!-- Section 3: Blocked Dates -->
			<hr>
			<h2><?php esc_html_e( 'Blocked Dates', 'kevincho-tailoring-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Dates when no consultations can be booked (holidays, closures, etc.).', 'kevincho-tailoring-manager' ); ?></p>

			<?php if ( ! empty( $blocked_dates ) ) : ?>
				<table class="widefat striped" style="margin-top:10px;max-width:600px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Reason', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'kevincho-tailoring-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blocked_dates as $blocked ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $blocked->blocked_date ) ) ); ?></td>
								<td><?php echo esc_html( $blocked->reason ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kctm_remove_consultation_blocked_date&blocked_id=' . absint( $blocked->id ) ), 'kctm_remove_blocked_date_' . $blocked->id ) ); ?>"
									   class="button button-small button-link-delete"
									   onclick="return confirm('<?php echo esc_js( __( 'Remove this blocked date?', 'kevincho-tailoring-manager' ) ); ?>');">
										<?php esc_html_e( 'Remove', 'kevincho-tailoring-manager' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p style="color:#666;"><em><?php esc_html_e( 'No blocked dates configured.', 'kevincho-tailoring-manager' ); ?></em></p>
			<?php endif; ?>

			<!-- Add Blocked Date Form -->
			<div style="background:#fff;padding:15px 20px;border:1px solid #ccd0d4;border-radius:4px;margin-top:15px;max-width:600px;">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Add Blocked Date', 'kevincho-tailoring-manager' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'kctm_add_consultation_blocked_date_nonce', 'kctm_nonce' ); ?>
					<input type="hidden" name="action" value="kctm_add_consultation_blocked_date">

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="blocked_date"><?php esc_html_e( 'Date', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="date"
									   name="blocked_date"
									   id="blocked_date"
									   class="regular-text"
									   required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="blocked_reason"><?php esc_html_e( 'Reason', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="text"
									   name="blocked_reason"
									   id="blocked_reason"
									   class="regular-text"
									   placeholder="<?php esc_attr_e( 'e.g. Public Holiday', 'kevincho-tailoring-manager' ); ?>">
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Add Blocked Date', 'kevincho-tailoring-manager' ), 'secondary' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the consultation booking edit view.
	 *
	 * Shows booking details in a form-table and allows editing
	 * of status, payment_status, and notes. Provides a save button
	 * and a resend confirmation button.
	 *
	 * @param int $booking_id The booking ID to edit.
	 * @return void
	 */
	private static function render_edit_booking( $booking_id ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'kctm_consultations';
		$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $booking_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $booking ) {
			wp_die( esc_html__( 'Consultation booking not found.', 'kevincho-tailoring-manager' ) );
		}

		$success = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$resent  = isset( $_GET['resent'] ) && '1' === $_GET['resent']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Process save if form was submitted.
		if ( isset( $_POST['kctm_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_edit_consultation_nonce' ) ) {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				$new_status         = isset( $_POST['booking_status'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_status'] ) ) : $booking->status;
				$new_payment_status = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : $booking->payment_status;
				$new_notes          = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

				// Validate status values.
				$allowed_statuses  = array( 'pending', 'confirmed', 'completed', 'cancelled', 'no-show' );
				$allowed_payments  = array( 'paid', 'unpaid' );

				if ( ! in_array( $new_status, $allowed_statuses, true ) ) {
					$new_status = $booking->status;
				}

				if ( ! in_array( $new_payment_status, $allowed_payments, true ) ) {
					$new_payment_status = $booking->payment_status;
				}

				// Use KCTM_Consultation_Booking if available, otherwise direct update.
				if ( class_exists( 'KCTM_Consultation_Booking' ) && method_exists( 'KCTM_Consultation_Booking', 'update_booking' ) ) {
					KCTM_Consultation_Booking::update_booking( $booking_id, array(
						'status'         => $new_status,
						'payment_status' => $new_payment_status,
						'notes'          => $new_notes,
					) );
				} else {
					$wpdb->update(
						$table,
						array(
							'status'         => $new_status,
							'payment_status' => $new_payment_status,
							'notes'          => $new_notes,
						),
						array( 'id' => $booking_id ),
						array( '%s', '%s', '%s' ),
						array( '%d' )
					);
				}

				wp_safe_redirect( admin_url( 'admin.php?page=kctm-consultation-settings&action=edit&booking_id=' . $booking_id . '&updated=1' ) );
				exit;
			}
		}

		// Handle resend confirmation.
		if ( isset( $_GET['resend_confirmation'] ) && '1' === $_GET['resend_confirmation'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$resend_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			if ( wp_verify_nonce( $resend_nonce, 'kctm_resend_confirmation_' . $booking_id ) && current_user_can( 'manage_woocommerce' ) ) {
				if ( class_exists( 'KCTM_Consultation_Notifications' ) && method_exists( 'KCTM_Consultation_Notifications', 'send_confirmation' ) ) {
					KCTM_Consultation_Notifications::send_confirmation( $booking_id );
				}

				wp_safe_redirect( admin_url( 'admin.php?page=kctm-consultation-settings&action=edit&booking_id=' . $booking_id . '&resent=1' ) );
				exit;
			}
		}

		// Refresh booking data after potential save.
		$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $booking_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$customer_name = trim( $booking->first_name . ' ' . $booking->last_name );

		if ( empty( $customer_name ) ) {
			$customer_name = __( '(No name)', 'kevincho-tailoring-manager' );
		}

		?>
		<div class="wrap">
			<h1>
				<?php
				printf(
					/* translators: %d: booking ID */
					esc_html__( 'Edit Consultation #%d', 'kevincho-tailoring-manager' ),
					absint( $booking_id )
				);
				?>
			</h1>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-consultations' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to Consultations', 'kevincho-tailoring-manager' ); ?></a>
			<hr class="wp-header-end">

			<?php if ( $success ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Booking updated successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $resent ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Confirmation notification resent successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Booking Details -->
			<div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;margin-top:15px;max-width:700px;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Booking Details', 'kevincho-tailoring-manager' ); ?></h2>

				<form method="post">
					<?php wp_nonce_field( 'kctm_edit_consultation_nonce', 'kctm_nonce' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Booking ID', 'kevincho-tailoring-manager' ); ?></th>
							<td><strong>#<?php echo esc_html( $booking->id ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Customer', 'kevincho-tailoring-manager' ); ?></th>
							<td><?php echo esc_html( $customer_name ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Phone', 'kevincho-tailoring-manager' ); ?></th>
							<td><?php echo esc_html( $booking->phone ); ?></td>
						</tr>
						<?php if ( ! empty( $booking->email ) ) : ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Email', 'kevincho-tailoring-manager' ); ?></th>
								<td><?php echo esc_html( $booking->email ); ?></td>
							</tr>
						<?php endif; ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Date', 'kevincho-tailoring-manager' ); ?></th>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->consultation_date ) ) ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Time', 'kevincho-tailoring-manager' ); ?></th>
							<td><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking->consultation_time ) ) ); ?></td>
						</tr>
						<?php if ( ! empty( $booking->created_at ) ) : ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Booked On', 'kevincho-tailoring-manager' ); ?></th>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $booking->created_at ) ) ); ?></td>
							</tr>
						<?php endif; ?>
						<tr>
							<th scope="row">
								<label for="booking_status"><?php esc_html_e( 'Status', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<select name="booking_status" id="booking_status">
									<option value="pending" <?php selected( $booking->status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'kevincho-tailoring-manager' ); ?></option>
									<option value="confirmed" <?php selected( $booking->status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'kevincho-tailoring-manager' ); ?></option>
									<option value="completed" <?php selected( $booking->status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'kevincho-tailoring-manager' ); ?></option>
									<option value="cancelled" <?php selected( $booking->status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'kevincho-tailoring-manager' ); ?></option>
									<option value="no-show" <?php selected( $booking->status, 'no-show' ); ?>><?php esc_html_e( 'No Show', 'kevincho-tailoring-manager' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="payment_status"><?php esc_html_e( 'Payment Status', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<select name="payment_status" id="payment_status">
									<option value="unpaid" <?php selected( isset( $booking->payment_status ) ? $booking->payment_status : 'unpaid', 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'kevincho-tailoring-manager' ); ?></option>
									<option value="paid" <?php selected( isset( $booking->payment_status ) ? $booking->payment_status : '', 'paid' ); ?>><?php esc_html_e( 'Paid', 'kevincho-tailoring-manager' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="notes"><?php esc_html_e( 'Notes', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<textarea name="notes"
										  id="notes"
										  class="large-text"
										  rows="4"><?php echo esc_textarea( isset( $booking->notes ) ? $booking->notes : '' ); ?></textarea>
							</td>
						</tr>
					</table>

					<p class="submit">
						<?php submit_button( __( 'Save Changes', 'kevincho-tailoring-manager' ), 'primary', 'submit', false ); ?>

						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=kctm-consultation-settings&action=edit&booking_id=' . $booking_id . '&resend_confirmation=1' ), 'kctm_resend_confirmation_' . $booking_id ) ); ?>"
						   class="button"
						   style="margin-left:8px;"
						   onclick="return confirm('<?php echo esc_js( __( 'Resend the confirmation notification to the customer?', 'kevincho-tailoring-manager' ) ); ?>');">
							<?php esc_html_e( 'Resend Confirmation', 'kevincho-tailoring-manager' ); ?>
						</a>
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle saving consultation settings (price, language, weekly schedule).
	 *
	 * Hooked to `admin_post_kctm_save_consultation_settings`.
	 *
	 * @return void
	 */
	public static function handle_save_consultation_settings() {
		// Verify nonce.
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_save_consultation_settings_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage settings.', 'kevincho-tailoring-manager' ) );
		}

		// Save price and language.
		$price    = isset( $_POST['consultation_price'] ) ? absint( $_POST['consultation_price'] ) : 15000;
		$language = isset( $_POST['consultation_language'] ) ? sanitize_text_field( wp_unslash( $_POST['consultation_language'] ) ) : 'en';

		// Validate language.
		if ( ! in_array( $language, array( 'en', 'fr' ), true ) ) {
			$language = 'en';
		}

		$settings = array(
			'price'    => $price,
			'language' => $language,
		);

		update_option( 'kctm_consultation_settings', $settings );

		// Save weekly availability schedule.
		$availability = isset( $_POST['availability'] ) && is_array( $_POST['availability'] )
			? $_POST['availability'] // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();

		$allowed_days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
		$allowed_slots = array( '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00' );

		// Build sanitized schedule data.
		$schedule = array();

		foreach ( $availability as $day => $slots ) {
			$day = sanitize_text_field( $day );

			if ( ! in_array( $day, $allowed_days, true ) ) {
				continue;
			}

			if ( ! is_array( $slots ) ) {
				continue;
			}

			foreach ( $slots as $slot ) {
				$slot = sanitize_text_field( $slot );

				if ( in_array( $slot, $allowed_slots, true ) ) {
					$schedule[] = array(
						'day_of_week' => $day,
						'time_slot'   => $slot,
					);
				}
			}
		}

		// Save via model class if available, otherwise direct DB insert.
		if ( class_exists( 'KCTM_Consultation_Availability' ) && method_exists( 'KCTM_Consultation_Availability', 'save_weekly_schedule' ) ) {
			KCTM_Consultation_Availability::save_weekly_schedule( $schedule );
		} else {
			global $wpdb;
			$table = $wpdb->prefix . 'kctm_consultation_availability';

			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

			if ( $table_exists ) {
				// Clear existing schedule.
				$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				// Insert new schedule.
				foreach ( $schedule as $row ) {
					$wpdb->insert(
						$table,
						$row,
						array( '%s', '%s' )
					);
				}
			}
		}

		// Redirect with success message.
		wp_safe_redirect( admin_url( 'admin.php?page=kctm-consultation-settings&updated=1' ) );
		exit;
	}

	/**
	 * Handle adding a blocked date.
	 *
	 * Hooked to `admin_post_kctm_add_consultation_blocked_date`.
	 *
	 * @return void
	 */
	public static function handle_add_blocked_date() {
		// Verify nonce.
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_add_consultation_blocked_date_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		$blocked_date = isset( $_POST['blocked_date'] ) ? sanitize_text_field( wp_unslash( $_POST['blocked_date'] ) ) : '';
		$reason       = isset( $_POST['blocked_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['blocked_reason'] ) ) : '';

		if ( empty( $blocked_date ) ) {
			wp_die( esc_html__( 'A date is required.', 'kevincho-tailoring-manager' ) );
		}

		// Validate date format (YYYY-MM-DD).
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $blocked_date ) ) {
			wp_die( esc_html__( 'Invalid date format.', 'kevincho-tailoring-manager' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'kctm_consultation_blocked_dates';

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $table_exists ) {
			// Check for duplicate.
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE blocked_date = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$blocked_date
			) );

			if ( ! $exists ) {
				$wpdb->insert(
					$table,
					array(
						'blocked_date' => $blocked_date,
						'reason'       => $reason,
					),
					array( '%s', '%s' )
				);
			}
		}

		// Redirect with success message.
		wp_safe_redirect( admin_url( 'admin.php?page=kctm-consultation-settings&blocked_added=1' ) );
		exit;
	}

	/**
	 * Handle removing a blocked date.
	 *
	 * Hooked to `admin_post_kctm_remove_consultation_blocked_date`.
	 *
	 * @return void
	 */
	public static function handle_remove_blocked_date() {
		$blocked_id = isset( $_GET['blocked_id'] ) ? absint( $_GET['blocked_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Verify nonce.
		if ( ! $blocked_id || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'kctm_remove_blocked_date_' . $blocked_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'kctm_consultation_blocked_dates';

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $table_exists ) {
			$wpdb->delete(
				$table,
				array( 'id' => $blocked_id ),
				array( '%d' )
			);
		}

		// Redirect with success message.
		wp_safe_redirect( admin_url( 'admin.php?page=kctm-consultation-settings&blocked_removed=1' ) );
		exit;
	}
}
