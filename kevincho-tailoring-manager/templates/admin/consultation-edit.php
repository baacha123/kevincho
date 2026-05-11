<?php
/**
 * Edit Consultation Booking — Admin single booking view.
 *
 * Displays full booking details with editable status, payment status,
 * and admin notes. Provides actions for saving and resending confirmation.
 *
 * Expected variable (set by the render() method):
 *   $booking — object with properties: id, first_name, last_name, phone,
 *              email, consultation_date, consultation_time, status,
 *              payment_status, notes, order_id, created_at
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Statuses ─────────────────────────────────────────────── */
$statuses = array(
	'pending'   => __( 'Pending', 'kevincho-tailoring-manager' ),
	'confirmed' => __( 'Confirmed', 'kevincho-tailoring-manager' ),
	'completed' => __( 'Completed', 'kevincho-tailoring-manager' ),
	'cancelled' => __( 'Cancelled', 'kevincho-tailoring-manager' ),
	'no-show'   => __( 'No-Show', 'kevincho-tailoring-manager' ),
);

$payment_statuses = array(
	'unpaid'   => __( 'Unpaid', 'kevincho-tailoring-manager' ),
	'paid'     => __( 'Paid', 'kevincho-tailoring-manager' ),
	'refunded' => __( 'Refunded', 'kevincho-tailoring-manager' ),
);

/* ── Safe values from booking object ──────────────────────── */
$booking_id     = isset( $booking->id ) ? absint( $booking->id ) : 0;
$first_name     = isset( $booking->first_name ) ? $booking->first_name : '';
$last_name      = isset( $booking->last_name ) ? $booking->last_name : '';
$phone          = isset( $booking->phone ) ? $booking->phone : '';
$email          = isset( $booking->email ) ? $booking->email : '';
$consult_date   = isset( $booking->consultation_date ) ? $booking->consultation_date : '';
$consult_time   = isset( $booking->consultation_time ) ? $booking->consultation_time : '';
$current_status = isset( $booking->status ) ? $booking->status : 'pending';
$pay_status     = isset( $booking->payment_status ) ? $booking->payment_status : 'unpaid';
$notes          = isset( $booking->notes ) ? $booking->notes : '';
$order_id       = isset( $booking->order_id ) ? absint( $booking->order_id ) : 0;
$created_at     = isset( $booking->created_at ) ? $booking->created_at : '';

/* ── List page URL ────────────────────────────────────────── */
$list_url = admin_url( 'admin.php?page=kctm-consultations' );

/* ── Resend confirmation URL ──────────────────────────────── */
$resend_url = wp_nonce_url(
	add_query_arg(
		array(
			'action'     => 'kctm_resend_consultation_confirmation',
			'booking_id' => $booking_id,
		),
		admin_url( 'admin-post.php' )
	),
	'kctm_resend_consultation_' . $booking_id,
	'kctm_resend_nonce'
);
?>

<div class="wrap kctm-consultation-edit">
	<h1>
		<?php
		printf(
			/* translators: %d: booking ID */
			esc_html__( 'Edit Consultation #%d', 'kevincho-tailoring-manager' ),
			$booking_id
		);
		?>
	</h1>

	<?php if ( isset( $_GET['message'] ) && 'updated' === $_GET['message'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Consultation updated successfully.', 'kevincho-tailoring-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['message'] ) && 'resent' === $_GET['message'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Confirmation message resent via WhatsApp.', 'kevincho-tailoring-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="kctm_update_consultation">
		<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking_id ); ?>">
		<?php wp_nonce_field( 'kctm_update_consultation_' . $booking_id, 'kctm_consultation_nonce' ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<!-- ── Main Column ─────────────────────────── -->
				<div id="post-body-content">

					<!-- Booking Details (read-only) -->
					<div class="kctm-section postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Booking Details', 'kevincho-tailoring-manager' ); ?></span></h2>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><?php esc_html_e( 'Booking ID', 'kevincho-tailoring-manager' ); ?></th>
									<td><code>#<?php echo esc_html( $booking_id ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Created', 'kevincho-tailoring-manager' ); ?></th>
									<td><?php echo esc_html( $created_at ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $created_at ) ) : '—' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Consultation Date', 'kevincho-tailoring-manager' ); ?></th>
									<td><?php echo esc_html( $consult_date ? date_i18n( get_option( 'date_format' ), strtotime( $consult_date ) ) : '—' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Consultation Time', 'kevincho-tailoring-manager' ); ?></th>
									<td><?php echo esc_html( $consult_time ? date_i18n( get_option( 'time_format' ), strtotime( '2000-01-01 ' . $consult_time ) ) : '—' ); ?></td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Customer Info (read-only) -->
					<div class="kctm-section postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Customer Information', 'kevincho-tailoring-manager' ); ?></span></h2>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><?php esc_html_e( 'First Name', 'kevincho-tailoring-manager' ); ?></th>
									<td><?php echo esc_html( $first_name ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Last Name', 'kevincho-tailoring-manager' ); ?></th>
									<td><?php echo esc_html( $last_name ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Phone', 'kevincho-tailoring-manager' ); ?></th>
									<td>
										<a href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $phone ) ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $phone ); ?>
										</a>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Email', 'kevincho-tailoring-manager' ); ?></th>
									<td>
										<?php if ( $email ) : ?>
											<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
										<?php else : ?>
											<em><?php esc_html_e( 'Not provided', 'kevincho-tailoring-manager' ); ?></em>
										<?php endif; ?>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Notes (editable) -->
					<div class="kctm-section postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Notes', 'kevincho-tailoring-manager' ); ?></span></h2>
						<div class="inside">
							<textarea
								name="notes"
								id="kctm-consultation-notes"
								rows="5"
								class="large-text"
								placeholder="<?php esc_attr_e( 'Add internal notes about this consultation...', 'kevincho-tailoring-manager' ); ?>"
							><?php echo esc_textarea( $notes ); ?></textarea>
						</div>
					</div>

				</div>

				<!-- ── Sidebar ─────────────────────────────── -->
				<div id="postbox-container-1" class="postbox-container">

					<!-- Status & Payment -->
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Status', 'kevincho-tailoring-manager' ); ?></span></h2>
						<div class="inside">
							<div class="kctm-form-group" style="margin-bottom:15px;">
								<label for="kctm-consultation-status">
									<strong><?php esc_html_e( 'Booking Status', 'kevincho-tailoring-manager' ); ?></strong>
								</label>
								<select name="status" id="kctm-consultation-status" style="width:100%;margin-top:5px;">
									<?php foreach ( $statuses as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="kctm-form-group" style="margin-bottom:15px;">
								<label for="kctm-payment-status">
									<strong><?php esc_html_e( 'Payment Status', 'kevincho-tailoring-manager' ); ?></strong>
								</label>
								<select name="payment_status" id="kctm-payment-status" style="width:100%;margin-top:5px;">
									<?php foreach ( $payment_statuses as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $pay_status, $value ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<?php submit_button( __( 'Save Changes', 'kevincho-tailoring-manager' ), 'primary', 'submit', false ); ?>
						</div>
					</div>

					<!-- Actions -->
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Actions', 'kevincho-tailoring-manager' ); ?></span></h2>
						<div class="inside">
							<p>
								<a
									href="<?php echo esc_url( $resend_url ); ?>"
									class="button button-secondary"
									style="width:100%;text-align:center;"
									onclick="return confirm('<?php echo esc_js( __( 'Resend the confirmation message via WhatsApp?', 'kevincho-tailoring-manager' ) ); ?>');"
								>
									<?php esc_html_e( 'Resend Confirmation WhatsApp', 'kevincho-tailoring-manager' ); ?>
								</a>
							</p>

							<?php if ( $order_id ) : ?>
								<p>
									<a
										href="<?php echo esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ); ?>"
										class="button button-secondary"
										style="width:100%;text-align:center;"
									>
										<?php
										printf(
											/* translators: %d: WooCommerce order ID */
											esc_html__( 'View Order #%d', 'kevincho-tailoring-manager' ),
											$order_id
										);
										?>
									</a>
								</p>
							<?php endif; ?>

							<p>
								<a href="<?php echo esc_url( $list_url ); ?>" class="button button-link" style="width:100%;text-align:center;">
									&laquo; <?php esc_html_e( 'Back to List', 'kevincho-tailoring-manager' ); ?>
								</a>
							</p>
						</div>
					</div>

				</div>

			</div>
		</div>
	</form>
</div>
