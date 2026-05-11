<?php
/**
 * Consultation Settings — Admin settings template.
 *
 * Manages consultation price, weekly availability schedule,
 * blocked dates, and language preference.
 *
 * Expected variables (set by the render() method):
 *   $settings      — array with keys: 'price', 'language', 'slot_duration'
 *   $schedule      — array from KCTM_Consultation_Availability::get_weekly_schedule()
 *   $blocked_dates — array from KCTM_Consultation_Availability::get_blocked_dates()
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Defaults ─────────────────────────────────────────────── */
$price         = isset( $settings['price'] ) ? absint( $settings['price'] ) : 15000;
$language      = isset( $settings['language'] ) ? sanitize_text_field( $settings['language'] ) : 'en';
$slot_duration = isset( $settings['slot_duration'] ) ? absint( $settings['slot_duration'] ) : 30;

/* ── Day labels ───────────────────────────────────────────── */
$day_labels = array(
	0 => __( 'Sunday', 'kevincho-tailoring-manager' ),
	1 => __( 'Monday', 'kevincho-tailoring-manager' ),
	2 => __( 'Tuesday', 'kevincho-tailoring-manager' ),
	3 => __( 'Wednesday', 'kevincho-tailoring-manager' ),
	4 => __( 'Thursday', 'kevincho-tailoring-manager' ),
	5 => __( 'Friday', 'kevincho-tailoring-manager' ),
	6 => __( 'Saturday', 'kevincho-tailoring-manager' ),
);

/* ── Time columns (09:00 through 17:00) ──────────────────── */
$time_columns = array(
	'09:00', '10:00', '11:00', '12:00',
	'13:00', '14:00', '15:00', '16:00', '17:00',
);

/* ── Build lookup for active schedule slots ───────────────── */
$active_slots = array();
if ( ! empty( $schedule ) ) {
	foreach ( $schedule as $row ) {
		$key = intval( $row->day_of_week ) . '_' . $row->time_slot;
		if ( ! empty( $row->is_active ) ) {
			$active_slots[ $key ] = true;
		}
	}
}
?>

<div class="wrap kctm-settings kctm-consultation-settings">
	<h1><?php esc_html_e( 'Consultation Settings', 'kevincho-tailoring-manager' ); ?></h1>

	<?php if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'kevincho-tailoring-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['message'] ) && 'blocked_added' === $_GET['message'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Blocked date added.', 'kevincho-tailoring-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['message'] ) && 'blocked_removed' === $_GET['message'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Blocked date removed.', 'kevincho-tailoring-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- ============================================================
	     Section 1: Consultation Price
	     ============================================================ -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="kctm_save_consultation_settings">
		<?php wp_nonce_field( 'kctm_save_consultation_settings', 'kctm_consultation_settings_nonce' ); ?>

		<div class="kctm-section">
			<h2><?php esc_html_e( 'Consultation Price', 'kevincho-tailoring-manager' ); ?></h2>
			<table class="form-table">
				<tr>
					<th>
						<label for="kctm-consultation-price"><?php esc_html_e( 'Price (FCFA)', 'kevincho-tailoring-manager' ); ?></label>
					</th>
					<td>
						<input
							type="number"
							name="consultation_price"
							id="kctm-consultation-price"
							class="regular-text"
							value="<?php echo esc_attr( $price ); ?>"
							min="0"
							step="500"
						>
						<p class="description">
							<?php esc_html_e( 'The fee charged for a single consultation session.', 'kevincho-tailoring-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="kctm-slot-duration"><?php esc_html_e( 'Slot Duration (minutes)', 'kevincho-tailoring-manager' ); ?></label>
					</th>
					<td>
						<input
							type="number"
							name="slot_duration"
							id="kctm-slot-duration"
							class="small-text"
							value="<?php echo esc_attr( $slot_duration ); ?>"
							min="15"
							max="120"
							step="15"
						>
					</td>
				</tr>
			</table>
		</div>

		<!-- ============================================================
		     Section 2: Weekly Schedule
		     ============================================================ -->
		<div class="kctm-section">
			<h2><?php esc_html_e( 'Weekly Schedule', 'kevincho-tailoring-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Check the time slots when consultations are available for each day of the week.', 'kevincho-tailoring-manager' ); ?>
			</p>

			<table class="widefat striped kctm-schedule-table" style="max-width:900px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Day', 'kevincho-tailoring-manager' ); ?></th>
						<?php foreach ( $time_columns as $time ) : ?>
							<th class="kctm-time-col"><?php echo esc_html( $time ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					/* ── Rows: Monday (1) through Sunday (0) ── */
					$day_order = array( 1, 2, 3, 4, 5, 6, 0 );
					foreach ( $day_order as $day_num ) :
					?>
						<tr>
							<td><strong><?php echo esc_html( $day_labels[ $day_num ] ); ?></strong></td>
							<?php foreach ( $time_columns as $time ) :
								$slot_key  = $day_num . '_' . $time;
								$is_active = isset( $active_slots[ $slot_key ] );
								$cb_name   = 'schedule[' . $day_num . '][' . $time . ']';
							?>
								<td class="kctm-time-col">
									<input
										type="checkbox"
										name="<?php echo esc_attr( $cb_name ); ?>"
										value="1"
										<?php checked( $is_active ); ?>
									>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- ============================================================
		     Section 4: Language
		     ============================================================ -->
		<div class="kctm-section">
			<h2><?php esc_html_e( 'Language', 'kevincho-tailoring-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Select the default language for consultation notifications and confirmations.', 'kevincho-tailoring-manager' ); ?>
			</p>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Default Language', 'kevincho-tailoring-manager' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input
									type="radio"
									name="consultation_language"
									value="en"
									<?php checked( $language, 'en' ); ?>
								>
								<?php esc_html_e( 'English', 'kevincho-tailoring-manager' ); ?>
							</label>
							<br>
							<label>
								<input
									type="radio"
									name="consultation_language"
									value="fr"
									<?php checked( $language, 'fr' ); ?>
								>
								<?php esc_html_e( 'French', 'kevincho-tailoring-manager' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'kevincho-tailoring-manager' ) ); ?>
	</form>

	<!-- ============================================================
	     Section 3: Blocked Dates
	     ============================================================ -->
	<div class="kctm-section">
		<h2><?php esc_html_e( 'Blocked Dates', 'kevincho-tailoring-manager' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Dates listed here will not be available for booking, regardless of the weekly schedule.', 'kevincho-tailoring-manager' ); ?>
		</p>

		<?php if ( ! empty( $blocked_dates ) ) : ?>
			<table class="widefat striped" style="max-width:600px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Action', 'kevincho-tailoring-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $blocked_dates as $blocked ) :
						$remove_url = wp_nonce_url(
							add_query_arg(
								array(
									'action'       => 'kctm_remove_blocked_date',
									'blocked_date' => $blocked->blocked_date,
								),
								admin_url( 'admin-post.php' )
							),
							'kctm_remove_blocked_date',
							'kctm_blocked_nonce'
						);
					?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $blocked->blocked_date ) ) ); ?></td>
							<td><?php echo esc_html( $blocked->reason ); ?></td>
							<td>
								<a
									href="<?php echo esc_url( $remove_url ); ?>"
									class="kctm-delete-link"
									onclick="return confirm('<?php echo esc_js( __( 'Remove this blocked date?', 'kevincho-tailoring-manager' ) ); ?>');"
								>
									<?php esc_html_e( 'Remove', 'kevincho-tailoring-manager' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><em><?php esc_html_e( 'No blocked dates configured.', 'kevincho-tailoring-manager' ); ?></em></p>
		<?php endif; ?>

		<!-- Add Blocked Date Form -->
		<h3><?php esc_html_e( 'Add Blocked Date', 'kevincho-tailoring-manager' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:600px;">
			<input type="hidden" name="action" value="kctm_add_blocked_date">
			<?php wp_nonce_field( 'kctm_add_blocked_date', 'kctm_blocked_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th>
						<label for="kctm-blocked-date"><?php esc_html_e( 'Date', 'kevincho-tailoring-manager' ); ?></label>
					</th>
					<td>
						<input
							type="date"
							name="blocked_date"
							id="kctm-blocked-date"
							class="regular-text"
							required
							min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"
						>
					</td>
				</tr>
				<tr>
					<th>
						<label for="kctm-blocked-reason"><?php esc_html_e( 'Reason (optional)', 'kevincho-tailoring-manager' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							name="blocked_reason"
							id="kctm-blocked-reason"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'e.g. Public holiday', 'kevincho-tailoring-manager' ); ?>"
						>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Add Blocked Date', 'kevincho-tailoring-manager' ), 'secondary' ); ?>
		</form>
	</div>

</div>
