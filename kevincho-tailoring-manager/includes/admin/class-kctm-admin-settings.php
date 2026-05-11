<?php
/**
 * Admin Settings Page
 *
 * Manages plugin settings including WhatsApp API, SMS API (Africa's Talking),
 * and per-event notification channel configuration.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_Admin_Settings {

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render() {
		self::render_inline();
	}

	/**
	 * Inline fallback render.
	 *
	 * @return void
	 */
	private static function render_inline() {
		// ── WhatsApp settings ──
		$whatsapp_settings = get_option( 'kctm_whatsapp_settings', array() );
		$wa_access_token   = isset( $whatsapp_settings['access_token'] )        ? $whatsapp_settings['access_token']        : '';
		$wa_phone_id       = isset( $whatsapp_settings['phone_number_id'] )     ? $whatsapp_settings['phone_number_id']     : '';
		$wa_business_id    = isset( $whatsapp_settings['business_account_id'] ) ? $whatsapp_settings['business_account_id'] : '';

		// ── SMS settings ──
		$sms_settings = get_option( 'kctm_sms_settings', array() );
		$sms_username = isset( $sms_settings['username'] )  ? $sms_settings['username']  : '';
		$sms_api_key  = isset( $sms_settings['api_key'] )   ? $sms_settings['api_key']   : '';
		$sms_sender   = isset( $sms_settings['sender_id'] ) ? $sms_settings['sender_id'] : 'KevinCho';
		$sms_sandbox  = isset( $sms_settings['sandbox'] )   ? (bool) $sms_settings['sandbox'] : true;

		// ── Per-event channel settings ──
		$channel_settings = get_option( 'kctm_notification_channels', array() );

		// ── Event definitions ──
		$events       = KCTM_Notification_Dispatcher::get_event_definitions();
		$event_groups = KCTM_Notification_Dispatcher::get_event_groups();

		$success = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce   = wp_create_nonce( 'kctm_admin_nonce' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Notification Settings', 'kevincho-tailoring-manager' ); ?></h1>

			<?php if ( $success ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Tab navigation -->
			<nav class="nav-tab-wrapper" id="kctm-settings-tabs">
				<a href="#tab-whatsapp" class="nav-tab nav-tab-active"><?php esc_html_e( 'WhatsApp', 'kevincho-tailoring-manager' ); ?></a>
				<a href="#tab-sms" class="nav-tab"><?php esc_html_e( 'SMS', 'kevincho-tailoring-manager' ); ?></a>
				<a href="#tab-channels" class="nav-tab"><?php esc_html_e( 'Notification Channels', 'kevincho-tailoring-manager' ); ?></a>
			</nav>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'kctm_save_settings_nonce', 'kctm_nonce' ); ?>
				<input type="hidden" name="action" value="kctm_save_settings">

				<!-- ═══ TAB: WhatsApp ═══ -->
				<div id="tab-whatsapp" class="kctm-tab-content" style="display:block;">
					<h2><?php esc_html_e( 'WhatsApp Business Cloud API', 'kevincho-tailoring-manager' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Connect to the Meta WhatsApp Business Cloud API to send WhatsApp messages to your customers.', 'kevincho-tailoring-manager' ); ?></p>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="whatsapp_access_token"><?php esc_html_e( 'Access Token', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="password"
									   name="whatsapp[access_token]"
									   id="whatsapp_access_token"
									   class="large-text"
									   value="<?php echo esc_attr( $wa_access_token ); ?>"
									   autocomplete="off">
								<p class="description"><?php esc_html_e( 'Permanent access token from Meta Business Manager > System Users.', 'kevincho-tailoring-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="whatsapp_phone_number_id"><?php esc_html_e( 'Phone Number ID', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="text"
									   name="whatsapp[phone_number_id]"
									   id="whatsapp_phone_number_id"
									   class="regular-text"
									   value="<?php echo esc_attr( $wa_phone_id ); ?>">
								<p class="description"><?php esc_html_e( 'From Meta Business Manager > WhatsApp > Phone Numbers.', 'kevincho-tailoring-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="whatsapp_business_account_id"><?php esc_html_e( 'Business Account ID', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="text"
									   name="whatsapp[business_account_id]"
									   id="whatsapp_business_account_id"
									   class="regular-text"
									   value="<?php echo esc_attr( $wa_business_id ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Test Connection', 'kevincho-tailoring-manager' ); ?></th>
							<td>
								<button type="button" class="button" id="kctm-test-whatsapp"><?php esc_html_e( 'Test WhatsApp', 'kevincho-tailoring-manager' ); ?></button>
								<span class="spinner" id="kctm-wa-spinner" style="float:none;"></span>
								<div id="kctm-wa-result" style="margin-top:10px;"></div>
							</td>
						</tr>
					</table>

					<div class="kctm-setup-guide" style="background:#f9f9f9;border:1px solid #ddd;border-left:4px solid #c9a96e;padding:15px 20px;margin:20px 0;max-width:700px;">
						<h3 style="margin-top:0;"><?php esc_html_e( 'Setup Guide', 'kevincho-tailoring-manager' ); ?></h3>
						<ol style="margin-left:20px;">
							<li><?php esc_html_e( 'Go to business.facebook.com and create a Meta Business Account', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Go to developers.facebook.com > Create App > Business type', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Add the "WhatsApp" product to your app', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Register your business phone number (your MTN number)', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Create a System User and generate a permanent access token', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Copy the Phone Number ID and Access Token into the fields above', 'kevincho-tailoring-manager' ); ?></li>
						</ol>
					</div>
				</div>

				<!-- ═══ TAB: SMS ═══ -->
				<div id="tab-sms" class="kctm-tab-content" style="display:none;">
					<h2><?php esc_html_e( 'SMS — Africa\'s Talking', 'kevincho-tailoring-manager' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Send SMS text messages to customers via Africa\'s Talking. Great rates for Cameroon numbers.', 'kevincho-tailoring-manager' ); ?></p>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="sms_username"><?php esc_html_e( 'Username', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="text"
									   name="sms[username]"
									   id="sms_username"
									   class="regular-text"
									   value="<?php echo esc_attr( $sms_username ); ?>">
								<p class="description"><?php esc_html_e( 'Your Africa\'s Talking app username (not your login email).', 'kevincho-tailoring-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="sms_api_key"><?php esc_html_e( 'API Key', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="password"
									   name="sms[api_key]"
									   id="sms_api_key"
									   class="large-text"
									   value="<?php echo esc_attr( $sms_api_key ); ?>"
									   autocomplete="off">
								<p class="description"><?php esc_html_e( 'API key from your Africa\'s Talking dashboard.', 'kevincho-tailoring-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="sms_sender_id"><?php esc_html_e( 'Sender ID', 'kevincho-tailoring-manager' ); ?></label>
							</th>
							<td>
								<input type="text"
									   name="sms[sender_id]"
									   id="sms_sender_id"
									   class="regular-text"
									   value="<?php echo esc_attr( $sms_sender ); ?>"
									   maxlength="11">
								<p class="description"><?php esc_html_e( 'The name shown as SMS sender (max 11 chars). E.g. "KevinCho". Requires registration with Africa\'s Talking.', 'kevincho-tailoring-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Environment', 'kevincho-tailoring-manager' ); ?></th>
							<td>
								<label>
									<input type="radio" name="sms[sandbox]" value="1" <?php checked( $sms_sandbox, true ); ?>>
									<?php esc_html_e( 'Sandbox (testing)', 'kevincho-tailoring-manager' ); ?>
								</label>
								<br>
								<label>
									<input type="radio" name="sms[sandbox]" value="0" <?php checked( $sms_sandbox, false ); ?>>
									<?php esc_html_e( 'Production (live)', 'kevincho-tailoring-manager' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Test Connection', 'kevincho-tailoring-manager' ); ?></th>
							<td>
								<button type="button" class="button" id="kctm-test-sms"><?php esc_html_e( 'Test SMS', 'kevincho-tailoring-manager' ); ?></button>
								<span class="spinner" id="kctm-sms-spinner" style="float:none;"></span>
								<div id="kctm-sms-result" style="margin-top:10px;"></div>
							</td>
						</tr>
					</table>

					<div class="kctm-setup-guide" style="background:#f9f9f9;border:1px solid #ddd;border-left:4px solid #c9a96e;padding:15px 20px;margin:20px 0;max-width:700px;">
						<h3 style="margin-top:0;"><?php esc_html_e( 'Setup Guide', 'kevincho-tailoring-manager' ); ?></h3>
						<ol style="margin-left:20px;">
							<li><?php esc_html_e( 'Sign up at africastalking.com', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Create an app in your dashboard', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Go to Settings > API Key to generate your key', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Start with Sandbox mode for testing (no cost)', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Switch to Production when ready and top up your account', 'kevincho-tailoring-manager' ); ?></li>
							<li><?php esc_html_e( 'Optional: Register a Sender ID (e.g. "KevinCho") for branded SMS', 'kevincho-tailoring-manager' ); ?></li>
						</ol>
					</div>
				</div>

				<!-- ═══ TAB: Notification Channels ═══ -->
				<div id="tab-channels" class="kctm-tab-content" style="display:none;">
					<h2><?php esc_html_e( 'Notification Channels per Event', 'kevincho-tailoring-manager' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Choose which channels (WhatsApp, SMS) to use for each notification event. Email notifications are handled by WooCommerce separately.', 'kevincho-tailoring-manager' ); ?></p>

					<?php foreach ( $event_groups as $group_key => $group_label ) : ?>
						<h3 style="margin-top:25px;padding-bottom:8px;border-bottom:2px solid #c9a96e;"><?php echo esc_html( $group_label ); ?></h3>
						<table class="widefat striped" style="max-width:700px;">
							<thead>
								<tr>
									<th style="width:45%;"><?php esc_html_e( 'Event', 'kevincho-tailoring-manager' ); ?></th>
									<th style="width:25%;text-align:center;"><?php esc_html_e( 'WhatsApp', 'kevincho-tailoring-manager' ); ?></th>
									<th style="width:25%;text-align:center;"><?php esc_html_e( 'SMS', 'kevincho-tailoring-manager' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $events as $event_key => $event ) :
									if ( $event['group'] !== $group_key ) {
										continue;
									}

									// Get saved channels or defaults.
									$saved_channels = isset( $channel_settings[ $event_key ] ) && is_array( $channel_settings[ $event_key ] )
										? $channel_settings[ $event_key ]
										: $event['default_channels'];
									?>
									<tr>
										<td><strong><?php echo esc_html( $event['label'] ); ?></strong></td>
										<td style="text-align:center;">
											<input type="checkbox"
												   name="channels[<?php echo esc_attr( $event_key ); ?>][]"
												   value="whatsapp"
												   <?php checked( in_array( 'whatsapp', $saved_channels, true ) ); ?>
											>
										</td>
										<td style="text-align:center;">
											<input type="checkbox"
												   name="channels[<?php echo esc_attr( $event_key ); ?>][]"
												   value="sms"
												   <?php checked( in_array( 'sms', $saved_channels, true ) ); ?>
											>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save All Settings', 'kevincho-tailoring-manager' ) ); ?>
			</form>
		</div>

		<style>
			.nav-tab-wrapper { margin-bottom: 0; }
			.kctm-tab-content { padding: 20px 0; }
			.nav-tab-active { background: #fff; border-bottom-color: #fff; }
			.widefat th, .widefat td { padding: 10px 12px; }
		</style>

		<script>
		jQuery(function($) {
			/* ── Tab switching ── */
			$('#kctm-settings-tabs .nav-tab').on('click', function(e) {
				e.preventDefault();
				$('#kctm-settings-tabs .nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				$('.kctm-tab-content').hide();
				$($(this).attr('href')).show();
			});

			/* ── Test WhatsApp ── */
			$('#kctm-test-whatsapp').on('click', function() {
				var $btn = $(this), $spin = $('#kctm-wa-spinner'), $res = $('#kctm-wa-result');
				$btn.prop('disabled', true); $spin.addClass('is-active'); $res.html('');
				$.post(ajaxurl, {
					action: 'kctm_test_whatsapp',
					_ajax_nonce: <?php echo wp_json_encode( $nonce ); ?>
				}, function(r) {
					$btn.prop('disabled', false); $spin.removeClass('is-active');
					var cls = r.success ? 'notice-success' : 'notice-error';
					$res.html('<div class="notice ' + cls + ' inline"><p>' + r.data.message + '</p></div>');
				}).fail(function() {
					$btn.prop('disabled', false); $spin.removeClass('is-active');
					$res.html('<div class="notice notice-error inline"><p><?php echo esc_js( __( 'Request failed.', 'kevincho-tailoring-manager' ) ); ?></p></div>');
				});
			});

			/* ── Test SMS ── */
			$('#kctm-test-sms').on('click', function() {
				var $btn = $(this), $spin = $('#kctm-sms-spinner'), $res = $('#kctm-sms-result');
				$btn.prop('disabled', true); $spin.addClass('is-active'); $res.html('');
				$.post(ajaxurl, {
					action: 'kctm_test_sms',
					_ajax_nonce: <?php echo wp_json_encode( $nonce ); ?>
				}, function(r) {
					$btn.prop('disabled', false); $spin.removeClass('is-active');
					var cls = r.success ? 'notice-success' : 'notice-error';
					$res.html('<div class="notice ' + cls + ' inline"><p>' + r.data.message + '</p></div>');
				}).fail(function() {
					$btn.prop('disabled', false); $spin.removeClass('is-active');
					$res.html('<div class="notice notice-error inline"><p><?php echo esc_js( __( 'Request failed.', 'kevincho-tailoring-manager' ) ); ?></p></div>');
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Process the settings form submission.
	 *
	 * @return void
	 */
	public static function process() {
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_save_settings_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage settings.', 'kevincho-tailoring-manager' ) );
		}

		// ── Save WhatsApp settings ──
		$wa_raw = isset( $_POST['whatsapp'] ) && is_array( $_POST['whatsapp'] ) ? $_POST['whatsapp'] : array(); // phpcs:ignore
		update_option( 'kctm_whatsapp_settings', array(
			'access_token'        => isset( $wa_raw['access_token'] )        ? sanitize_text_field( wp_unslash( $wa_raw['access_token'] ) )        : '',
			'phone_number_id'     => isset( $wa_raw['phone_number_id'] )     ? sanitize_text_field( wp_unslash( $wa_raw['phone_number_id'] ) )     : '',
			'business_account_id' => isset( $wa_raw['business_account_id'] ) ? sanitize_text_field( wp_unslash( $wa_raw['business_account_id'] ) ) : '',
		) );

		// ── Save SMS settings ──
		$sms_raw = isset( $_POST['sms'] ) && is_array( $_POST['sms'] ) ? $_POST['sms'] : array(); // phpcs:ignore
		update_option( 'kctm_sms_settings', array(
			'username'  => isset( $sms_raw['username'] )  ? sanitize_text_field( wp_unslash( $sms_raw['username'] ) )  : '',
			'api_key'   => isset( $sms_raw['api_key'] )   ? sanitize_text_field( wp_unslash( $sms_raw['api_key'] ) )   : '',
			'sender_id' => isset( $sms_raw['sender_id'] ) ? sanitize_text_field( wp_unslash( $sms_raw['sender_id'] ) ) : 'KevinCho',
			'sandbox'   => isset( $sms_raw['sandbox'] )   ? (bool) $sms_raw['sandbox'] : true,
		) );

		// ── Save per-event channel settings ──
		$channels_raw = isset( $_POST['channels'] ) && is_array( $_POST['channels'] ) ? $_POST['channels'] : array(); // phpcs:ignore
		$channels     = array();

		$valid_channels = array( 'whatsapp', 'sms' );
		foreach ( $channels_raw as $event_key => $event_channels ) {
			$event_key = sanitize_text_field( $event_key );
			if ( is_array( $event_channels ) ) {
				$channels[ $event_key ] = array_values( array_intersect(
					array_map( 'sanitize_text_field', $event_channels ),
					$valid_channels
				) );
			} else {
				$channels[ $event_key ] = array();
			}
		}

		// Events with no checkboxes checked should be saved as empty array.
		$all_events = KCTM_Notification_Dispatcher::get_event_definitions();
		foreach ( array_keys( $all_events ) as $event_key ) {
			if ( ! isset( $channels[ $event_key ] ) ) {
				$channels[ $event_key ] = array();
			}
		}

		update_option( 'kctm_notification_channels', $channels );

		// ── Also update legacy option for backward compatibility ──
		$legacy_statuses = array();
		$status_events   = array(
			'order_confirmed'    => 'kctm-confirmed',
			'order_in_progress'  => 'kctm-in-progress',
			'order_ready_pickup' => 'kctm-ready-pickup',
			'order_delivered'    => 'kctm-delivered',
			'order_completed'    => 'completed',
		);
		foreach ( $status_events as $event => $status_slug ) {
			if ( isset( $channels[ $event ] ) && ! empty( $channels[ $event ] ) ) {
				$legacy_statuses[] = $status_slug;
			}
		}
		update_option( 'kctm_notification_statuses', $legacy_statuses );

		wp_safe_redirect( admin_url( 'admin.php?page=kctm-settings&updated=1' ) );
		exit;
	}

	/**
	 * Test the WhatsApp API connection (AJAX).
	 *
	 * @return void
	 */
	public static function test_whatsapp() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'kevincho-tailoring-manager' ) ) );
		}

		$api    = new KCTM_WhatsApp_API();
		$result = $api->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Test the SMS API connection (AJAX).
	 *
	 * @return void
	 */
	public static function test_sms() {
		check_ajax_referer( 'kctm_admin_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'kevincho-tailoring-manager' ) ) );
		}

		$sms    = new KCTM_SMS_API();
		$result = $sms->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}
}

/* ── Hook the form processor and AJAX handlers ─────────────── */
add_action( 'admin_post_kctm_save_settings', array( 'KCTM_Admin_Settings', 'process' ) );
add_action( 'wp_ajax_kctm_test_sms', array( 'KCTM_Admin_Settings', 'test_sms' ) );
