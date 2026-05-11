<?php
/**
 * Consultation Booking Form — Frontend multi-step booking template.
 *
 * Displayed via shortcode on the /consultation/ page.
 * Pre-fills contact fields from logged-in WP user data.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Consultation price from settings ─────────────────────── */
$consultation_settings = get_option( 'kctm_consultation_settings', array() );
$consultation_price    = isset( $consultation_settings['price'] ) ? absint( $consultation_settings['price'] ) : 15000;
$formatted_price       = number_format( $consultation_price, 0, '.', ',' ) . ' FCFA';

/* ── Pre-fill from logged-in user ─────────────────────────── */
$current_user   = wp_get_current_user();
$prefill_first  = $current_user->ID ? esc_attr( $current_user->first_name ) : '';
$prefill_last   = $current_user->ID ? esc_attr( $current_user->last_name ) : '';
$prefill_email  = $current_user->ID ? esc_attr( $current_user->user_email ) : '';
$prefill_phone  = $current_user->ID ? esc_attr( get_user_meta( $current_user->ID, '_kctm_phone', true ) ) : '';
?>

<div class="kctm-consultation-booking">

	<!-- ============================================================
	     Step 1: Introduction
	     ============================================================ -->
	<div class="kctm-step kctm-step-1 kctm-step-active" id="kctm-step-intro">
		<div class="kctm-consultation-hero">
			<h2><?php esc_html_e( 'Consult Directly with Kevin Cho', 'kevincho-tailoring-manager' ); ?></h2>
			<p class="kctm-consultation-desc">
				<?php esc_html_e( 'Get personalized fashion advice directly from our CEO, Kevin Cho. Whether you need styling guidance, fabric selection help, or custom design consultation, Kevin will personally assist you.', 'kevincho-tailoring-manager' ); ?>
			</p>
			<div class="kctm-consultation-price">
				<span class="kctm-price-label"><?php esc_html_e( 'Consultation Fee:', 'kevincho-tailoring-manager' ); ?></span>
				<span class="kctm-price-amount"><?php echo esc_html( $formatted_price ); ?></span>
			</div>
			<div class="kctm-consultation-details">
				<ul>
					<li><span class="dashicons dashicons-clock"></span> <?php esc_html_e( '30-minute session', 'kevincho-tailoring-manager' ); ?></li>
					<li><span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Direct phone/WhatsApp call', 'kevincho-tailoring-manager' ); ?></li>
					<li><span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e( 'Personalized style advice', 'kevincho-tailoring-manager' ); ?></li>
					<li><span class="dashicons dashicons-welcome-write-blog"></span> <?php esc_html_e( 'Custom design discussion', 'kevincho-tailoring-manager' ); ?></li>
				</ul>
			</div>
			<button type="button" class="kctm-btn kctm-btn-primary" id="kctm-start-booking">
				<?php esc_html_e( 'Book Now', 'kevincho-tailoring-manager' ); ?>
			</button>
		</div>
	</div>

	<!-- ============================================================
	     Step 2: Pick a Date
	     ============================================================ -->
	<div class="kctm-step kctm-step-2" id="kctm-step-date" style="display:none;">
		<h3><?php esc_html_e( 'Select a Date', 'kevincho-tailoring-manager' ); ?></h3>
		<div class="kctm-calendar-nav">
			<button type="button" class="kctm-btn kctm-btn-sm" id="kctm-prev-month">&laquo; <?php esc_html_e( 'Previous', 'kevincho-tailoring-manager' ); ?></button>
			<span id="kctm-calendar-month-label"></span>
			<button type="button" class="kctm-btn kctm-btn-sm" id="kctm-next-month"><?php esc_html_e( 'Next', 'kevincho-tailoring-manager' ); ?> &raquo;</button>
		</div>
		<div id="kctm-calendar-grid" class="kctm-calendar-grid">
			<!-- Calendar rendered by JS -->
		</div>
		<input type="hidden" id="kctm-selected-date" name="consultation_date" value="">
		<div class="kctm-step-nav">
			<button type="button" class="kctm-btn kctm-btn-secondary" id="kctm-back-to-intro">&laquo; <?php esc_html_e( 'Back', 'kevincho-tailoring-manager' ); ?></button>
		</div>
	</div>

	<!-- ============================================================
	     Step 3: Pick a Time
	     ============================================================ -->
	<div class="kctm-step kctm-step-3" id="kctm-step-time" style="display:none;">
		<h3><?php esc_html_e( 'Select a Time', 'kevincho-tailoring-manager' ); ?></h3>
		<p>
			<?php esc_html_e( 'Available slots for', 'kevincho-tailoring-manager' ); ?>
			<strong id="kctm-selected-date-label"></strong>
		</p>
		<div id="kctm-time-slots" class="kctm-time-slots">
			<!-- Time slots rendered by JS -->
		</div>
		<input type="hidden" id="kctm-selected-time" name="consultation_time" value="">
		<div class="kctm-step-nav">
			<button type="button" class="kctm-btn kctm-btn-secondary" id="kctm-back-to-date">&laquo; <?php esc_html_e( 'Back', 'kevincho-tailoring-manager' ); ?></button>
		</div>
	</div>

	<!-- ============================================================
	     Step 4: Contact Info
	     ============================================================ -->
	<div class="kctm-step kctm-step-4" id="kctm-step-contact" style="display:none;">
		<h3><?php esc_html_e( 'Your Details', 'kevincho-tailoring-manager' ); ?></h3>

		<div class="kctm-form-group">
			<label for="kctm-first-name">
				<?php esc_html_e( 'First Name', 'kevincho-tailoring-manager' ); ?> <span class="required">*</span>
			</label>
			<input
				type="text"
				id="kctm-first-name"
				name="first_name"
				required
				class="kctm-input"
				value="<?php echo esc_attr( $prefill_first ); ?>"
			>
		</div>

		<div class="kctm-form-group">
			<label for="kctm-last-name">
				<?php esc_html_e( 'Last Name', 'kevincho-tailoring-manager' ); ?> <span class="required">*</span>
			</label>
			<input
				type="text"
				id="kctm-last-name"
				name="last_name"
				required
				class="kctm-input"
				value="<?php echo esc_attr( $prefill_last ); ?>"
			>
		</div>

		<div class="kctm-form-group">
			<label for="kctm-phone">
				<?php esc_html_e( 'Phone Number (WhatsApp)', 'kevincho-tailoring-manager' ); ?> <span class="required">*</span>
			</label>
			<input
				type="tel"
				id="kctm-phone"
				name="phone"
				required
				class="kctm-input"
				placeholder="+237..."
				value="<?php echo esc_attr( $prefill_phone ); ?>"
			>
		</div>

		<div class="kctm-form-group">
			<label for="kctm-email">
				<?php esc_html_e( 'Email (optional)', 'kevincho-tailoring-manager' ); ?>
			</label>
			<input
				type="email"
				id="kctm-email"
				name="email"
				class="kctm-input"
				value="<?php echo esc_attr( $prefill_email ); ?>"
			>
		</div>

		<div class="kctm-form-group">
			<label for="kctm-notes">
				<?php esc_html_e( 'Questions or Notes (optional)', 'kevincho-tailoring-manager' ); ?>
			</label>
			<textarea id="kctm-notes" name="notes" rows="3" class="kctm-input"></textarea>
		</div>

		<div class="kctm-step-nav">
			<button type="button" class="kctm-btn kctm-btn-secondary" id="kctm-back-to-time">&laquo; <?php esc_html_e( 'Back', 'kevincho-tailoring-manager' ); ?></button>
			<button type="button" class="kctm-btn kctm-btn-primary" id="kctm-to-summary"><?php esc_html_e( 'Continue', 'kevincho-tailoring-manager' ); ?> &raquo;</button>
		</div>
	</div>

	<!-- ============================================================
	     Step 5: Summary & Pay
	     ============================================================ -->
	<div class="kctm-step kctm-step-5" id="kctm-step-summary" style="display:none;">
		<h3><?php esc_html_e( 'Booking Summary', 'kevincho-tailoring-manager' ); ?></h3>

		<div class="kctm-summary-card">
			<div class="kctm-summary-row">
				<span><?php esc_html_e( 'Date:', 'kevincho-tailoring-manager' ); ?></span>
				<strong id="kctm-summary-date"></strong>
			</div>
			<div class="kctm-summary-row">
				<span><?php esc_html_e( 'Time:', 'kevincho-tailoring-manager' ); ?></span>
				<strong id="kctm-summary-time"></strong>
			</div>
			<div class="kctm-summary-row">
				<span><?php esc_html_e( 'Name:', 'kevincho-tailoring-manager' ); ?></span>
				<strong id="kctm-summary-name"></strong>
			</div>
			<div class="kctm-summary-row">
				<span><?php esc_html_e( 'Phone:', 'kevincho-tailoring-manager' ); ?></span>
				<strong id="kctm-summary-phone"></strong>
			</div>
			<div class="kctm-summary-row kctm-summary-total">
				<span><?php esc_html_e( 'Total:', 'kevincho-tailoring-manager' ); ?></span>
				<strong><?php echo esc_html( $formatted_price ); ?></strong>
			</div>
		</div>

		<div class="kctm-step-nav">
			<button type="button" class="kctm-btn kctm-btn-secondary" id="kctm-back-to-contact">&laquo; <?php esc_html_e( 'Back', 'kevincho-tailoring-manager' ); ?></button>
			<button type="button" class="kctm-btn kctm-btn-primary kctm-btn-large" id="kctm-proceed-payment">
				<?php esc_html_e( 'Proceed to Payment', 'kevincho-tailoring-manager' ); ?>
			</button>
		</div>

		<div id="kctm-booking-error" class="kctm-error" style="display:none;"></div>
		<div id="kctm-booking-loading" class="kctm-loading" style="display:none;">
			<?php esc_html_e( 'Processing your booking...', 'kevincho-tailoring-manager' ); ?>
		</div>
	</div>

</div>
