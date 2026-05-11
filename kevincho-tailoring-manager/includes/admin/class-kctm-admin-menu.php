<?php
/**
 * Admin Menu Registration
 *
 * Registers the "Tailoring" top-level admin menu and all submenus
 * for the KevinCho Tailoring Manager plugin.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Admin_Menu
 *
 * Registers admin menus and handles rendering for the dashboard,
 * notification log, and personalization admin pages. Also processes
 * personalization form submissions via admin_post hooks.
 */
class KCTM_Admin_Menu {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );

		/* ── Personalization form handlers ────────────────── */
		add_action( 'admin_post_kctm_save_personalization_group', array( __CLASS__, 'handle_save_personalization_group' ) );
		add_action( 'admin_post_kctm_save_personalization_option', array( __CLASS__, 'handle_save_personalization_option' ) );
		add_action( 'admin_post_kctm_delete_personalization_group', array( __CLASS__, 'handle_delete_personalization_group' ) );
		add_action( 'admin_post_kctm_delete_personalization_option', array( __CLASS__, 'handle_delete_personalization_option' ) );
	}

	/**
	 * Register the top-level menu and all submenus.
	 *
	 * @return void
	 */
	public static function register_menus() {

		/* ── Top-level: Tailoring ────────────────────────── */
		add_menu_page(
			__( 'Tailoring Manager', 'kevincho-tailoring-manager' ),
			__( 'Tailoring', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-dashboard',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-scissors',
			56
		);

		/* ── Dashboard (re-uses parent slug) ─────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Dashboard', 'kevincho-tailoring-manager' ),
			__( 'Dashboard', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-dashboard',
			array( __CLASS__, 'render_dashboard' )
		);

		/* ── Customers ───────────────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Customers', 'kevincho-tailoring-manager' ),
			__( 'Customers', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-customers',
			array( 'KCTM_Admin_Customers', 'render' )
		);

		/* ── Customer Measurements (hidden — no parent) ──── */
		add_submenu_page(
			null,
			__( 'Customer Measurements', 'kevincho-tailoring-manager' ),
			__( 'Customer Measurements', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-customer-measurements',
			array( __CLASS__, 'render_customer_measurements' )
		);

		/* ── Add Walk-in ─────────────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Add Walk-in', 'kevincho-tailoring-manager' ),
			__( 'Add Walk-in', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-walkin',
			array( 'KCTM_Admin_Walkin', 'render' )
		);

		/* ── New In-Store Order ───────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'New In-Store Order', 'kevincho-tailoring-manager' ),
			__( 'New In-Store Order', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-create-order',
			array( 'KCTM_Admin_Create_Order', 'render' )
		);

		/* ── Notification Log ────────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Notification Log', 'kevincho-tailoring-manager' ),
			__( 'Notification Log', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-notification-log',
			array( __CLASS__, 'render_notification_log' )
		);

		/* ── Personalization ─────────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Personalization', 'kevincho-tailoring-manager' ),
			__( 'Personalization', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-personalization',
			array( __CLASS__, 'render_personalization' )
		);

		/* ── Consultations ───────────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Consultations', 'kevincho-tailoring-manager' ),
			__( 'Consultations', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-consultations',
			array( 'KCTM_Admin_Consultations', 'render' )
		);

		/* ── Consultation Settings ───────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Consultation Settings', 'kevincho-tailoring-manager' ),
			__( 'Consultation Settings', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-consultation-settings',
			array( 'KCTM_Admin_Consultation_Settings', 'render' )
		);

		/* ── Fabrics ─────────────────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Fabrics', 'kevincho-tailoring-manager' ),
			__( 'Fabrics', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-fabrics',
			array( 'KCTM_Admin_Fabrics', 'render' )
		);

		/* ── Settings ────────────────────────────────────── */
		add_submenu_page(
			'kctm-dashboard',
			__( 'Settings', 'kevincho-tailoring-manager' ),
			__( 'Settings', 'kevincho-tailoring-manager' ),
			'manage_woocommerce',
			'kctm-settings',
			array( 'KCTM_Admin_Settings', 'render' )
		);
	}

	/* ================================================================
	 * Dashboard
	 * ============================================================= */

	/**
	 * Render the Tailoring dashboard overview page.
	 *
	 * Shows aggregate counts for customers, measurements, order
	 * statuses, and the last five notification log entries.
	 *
	 * @return void
	 */
	public static function render_dashboard() {
		/* ── Customer counts ────────────────────────────── */
		$total_customers = count_users();
		$customer_count  = isset( $total_customers['avail_roles']['customer'] )
			? (int) $total_customers['avail_roles']['customer']
			: 0;

		// Customers who have at least one measurement stored.
		$users_with_measurements = get_users( array(
			'role'       => 'customer',
			'meta_key'   => '_kctm_measurement_gender',
			'compare'    => 'EXISTS',
			'fields'     => 'ID',
			'number'     => -1,
		) );
		$measurement_count = count( $users_with_measurements );

		/* ── Order status counts ────────────────────────── */
		$custom_statuses = KCTM_Order_Statuses::get_custom_statuses();
		$status_counts   = array();

		foreach ( $custom_statuses as $slug => $label ) {
			$status_key = str_replace( 'wc-', '', $slug );
			$orders     = wc_get_orders( array(
				'status' => $status_key,
				'limit'  => -1,
				'return' => 'ids',
			) );
			$status_counts[ $label ] = count( $orders );
		}

		// Include core WC statuses of interest.
		$pending_orders = wc_get_orders( array(
			'status' => 'pending',
			'limit'  => -1,
			'return' => 'ids',
		) );
		$status_counts[ __( 'Pending Payment', 'kevincho-tailoring-manager' ) ] = count( $pending_orders );

		$completed_orders = wc_get_orders( array(
			'status' => 'completed',
			'limit'  => -1,
			'return' => 'ids',
		) );
		$status_counts[ __( 'Completed', 'kevincho-tailoring-manager' ) ] = count( $completed_orders );

		/* ── Recent notifications ───────────────────────── */
		$recent_logs = KCTM_Notification_Log::get_logs( array(
			'per_page' => 5,
			'page'     => 1,
		) );

		/* ── Consultation counts ────────────────────────── */
		global $wpdb;
		$consult_table  = $wpdb->prefix . 'kctm_consultations';
		$table_exists   = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $consult_table ) );
		$consult_counts = array();

		if ( $table_exists ) {
			$consult_total     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$consult_table}" );
			$consult_upcoming  = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$consult_table} WHERE consultation_date >= %s AND status IN ('pending','confirmed')",
				current_time( 'Y-m-d' )
			) );
			$consult_completed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$consult_table} WHERE status = 'completed'" );
			$consult_counts    = array(
				__( 'Total Consultations', 'kevincho-tailoring-manager' ) => $consult_total,
				__( 'Upcoming', 'kevincho-tailoring-manager' )           => $consult_upcoming,
				__( 'Completed', 'kevincho-tailoring-manager' )          => $consult_completed,
			);
		}

		/* ── Output ─────────────────────────────────────── */
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tailoring Manager Dashboard', 'kevincho-tailoring-manager' ); ?></h1>

			<div class="kctm-dashboard-widgets" style="display:flex;flex-wrap:wrap;gap:20px;margin-top:20px;">

				<!-- Customer Stats -->
				<div class="kctm-widget" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;min-width:220px;flex:1;">
					<h2 style="margin-top:0;"><?php esc_html_e( 'Customers', 'kevincho-tailoring-manager' ); ?></h2>
					<table class="widefat striped">
						<tr>
							<td><?php esc_html_e( 'Total Registered Customers', 'kevincho-tailoring-manager' ); ?></td>
							<td><strong><?php echo esc_html( $customer_count ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Customers with Measurements', 'kevincho-tailoring-manager' ); ?></td>
							<td><strong><?php echo esc_html( $measurement_count ); ?></strong></td>
						</tr>
					</table>
				</div>

				<!-- Order Status Counts -->
				<div class="kctm-widget" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;min-width:220px;flex:1;">
					<h2 style="margin-top:0;"><?php esc_html_e( 'Orders by Status', 'kevincho-tailoring-manager' ); ?></h2>
					<table class="widefat striped">
						<?php foreach ( $status_counts as $label => $count ) : ?>
							<tr>
								<td><?php echo esc_html( $label ); ?></td>
								<td><strong><?php echo esc_html( $count ); ?></strong></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>

			</div>

			<?php if ( ! empty( $consult_counts ) ) : ?>
			<!-- Consultation Stats -->
			<div class="kctm-widget" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;margin-top:20px;">
				<h2 style="margin-top:0;">
					<?php esc_html_e( 'Consultations', 'kevincho-tailoring-manager' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-consultations' ) ); ?>" class="page-title-action" style="font-size:13px;"><?php esc_html_e( 'View All', 'kevincho-tailoring-manager' ); ?></a>
				</h2>
				<table class="widefat striped">
					<?php foreach ( $consult_counts as $label => $count ) : ?>
						<tr>
							<td><?php echo esc_html( $label ); ?></td>
							<td><strong><?php echo esc_html( $count ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>
			<?php endif; ?>

			<!-- Recent Notifications -->
			<div class="kctm-widget" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;margin-top:20px;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Last 5 Notifications', 'kevincho-tailoring-manager' ); ?></h2>
				<?php if ( empty( $recent_logs['items'] ) ) : ?>
					<p><?php esc_html_e( 'No notifications sent yet.', 'kevincho-tailoring-manager' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'kevincho-tailoring-manager' ); ?></th>
								<th><?php esc_html_e( 'Order', 'kevincho-tailoring-manager' ); ?></th>
								<th><?php esc_html_e( 'Phone', 'kevincho-tailoring-manager' ); ?></th>
								<th><?php esc_html_e( 'Status', 'kevincho-tailoring-manager' ); ?></th>
								<th><?php esc_html_e( 'Response', 'kevincho-tailoring-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_logs['items'] as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $log->sent_at ); ?></td>
									<td>
										<?php if ( $log->order_id ) : ?>
											<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $log->order_id . '&action=edit' ) ); ?>">
												#<?php echo esc_html( $log->order_id ); ?>
											</a>
										<?php else : ?>
											&mdash;
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $log->phone ); ?></td>
									<td><?php echo esc_html( $log->status ); ?></td>
									<td>
										<span class="<?php echo absint( $log->response_code ) >= 200 && absint( $log->response_code ) < 300 ? 'dashicons dashicons-yes-alt' : 'dashicons dashicons-dismiss'; ?>"
											  style="color:<?php echo absint( $log->response_code ) >= 200 && absint( $log->response_code ) < 300 ? '#00a32a' : '#d63638'; ?>;"></span>
										<?php echo esc_html( $log->response_code ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/* ================================================================
	 * Customer Measurements (individual view/edit)
	 * ============================================================= */

	/**
	 * Render the individual customer measurements edit page.
	 *
	 * Accessed from the Customers list table via a link. Shows all
	 * measurement fields with current values and allows editing.
	 *
	 * @return void
	 */
	public static function render_customer_measurements() {
		$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $customer_id ) {
			wp_die( esc_html__( 'No customer specified.', 'kevincho-tailoring-manager' ) );
		}

		$customer = get_userdata( $customer_id );
		if ( ! $customer ) {
			wp_die( esc_html__( 'Customer not found.', 'kevincho-tailoring-manager' ) );
		}

		$measurements = KCTM_Measurement_Storage::get_measurements( $customer_id );
		$gender       = isset( $measurements['gender'] ) && $measurements['gender'] ? $measurements['gender'] : 'male';
		$fields       = KCTM_Measurement_Fields::get_fields_for_gender( $gender );

		$success = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<div class="wrap">
			<h1>
				<?php
				printf(
					/* translators: %s: customer name */
					esc_html__( 'Measurements for %s', 'kevincho-tailoring-manager' ),
					esc_html( $customer->display_name )
				);
				?>
			</h1>

			<?php if ( $success ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Measurements saved successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<p>
				<strong><?php esc_html_e( 'Email:', 'kevincho-tailoring-manager' ); ?></strong> <?php echo esc_html( $customer->user_email ); ?><br>
				<strong><?php esc_html_e( 'Phone:', 'kevincho-tailoring-manager' ); ?></strong> <?php echo esc_html( get_user_meta( $customer_id, '_kctm_phone', true ) ); ?><br>
				<strong><?php esc_html_e( 'Type:', 'kevincho-tailoring-manager' ); ?></strong>
				<?php
				$type = get_user_meta( $customer_id, '_kctm_customer_type', true );
				echo esc_html( 'walkin' === $type ? __( 'Walk-in', 'kevincho-tailoring-manager' ) : __( 'Regular', 'kevincho-tailoring-manager' ) );
				?>
			</p>

			<form id="kctm-admin-measurements-form">
				<?php wp_nonce_field( 'kctm_admin_nonce', 'kctm_nonce' ); ?>
				<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer_id ); ?>">

				<table class="form-table">
					<?php foreach ( $fields as $field ) : ?>
						<tr>
							<th scope="row">
								<label for="kctm_<?php echo esc_attr( $field['key'] ); ?>">
									<?php echo esc_html( $field['label'] ); ?>
									<?php if ( $field['unit'] ) : ?>
										<small>(<?php echo esc_html( $field['unit'] ); ?>)</small>
									<?php endif; ?>
									<?php if ( $field['required'] ) : ?>
										<span class="required" style="color:#d63638;">*</span>
									<?php endif; ?>
								</label>
							</th>
							<td>
								<?php if ( 'select' === $field['type'] && ! empty( $field['options'] ) ) : ?>
									<select name="<?php echo esc_attr( $field['key'] ); ?>" id="kctm_<?php echo esc_attr( $field['key'] ); ?>">
										<?php foreach ( $field['options'] as $val => $label ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '', $val ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								<?php else : ?>
									<input type="number"
										   name="<?php echo esc_attr( $field['key'] ); ?>"
										   id="kctm_<?php echo esc_attr( $field['key'] ); ?>"
										   value="<?php echo esc_attr( isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '' ); ?>"
										   step="0.1"
										   <?php if ( $field['min'] ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
										   <?php if ( $field['max'] ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
										   class="regular-text">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary" id="kctm-save-measurements">
						<?php esc_html_e( 'Save Measurements', 'kevincho-tailoring-manager' ); ?>
					</button>
					<span class="spinner" id="kctm-measurements-spinner"></span>
				</p>
				<div id="kctm-measurements-response"></div>
			</form>
		</div>

		<script>
		jQuery(function($) {
			$('#kctm-admin-measurements-form').on('submit', function(e) {
				e.preventDefault();
				var $btn     = $('#kctm-save-measurements');
				var $spinner = $('#kctm-measurements-spinner');
				var $resp    = $('#kctm-measurements-response');

				$btn.prop('disabled', true);
				$spinner.addClass('is-active');
				$resp.html('');

				$.post(ajaxurl, {
					action:      'kctm_save_measurements',
					_ajax_nonce: $('#kctm_nonce').val(),
					customer_id: $('input[name="customer_id"]').val(),
					data:        $(this).serialize()
				}, function(response) {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					if (response.success) {
						$resp.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					} else {
						var msg = response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'An error occurred.', 'kevincho-tailoring-manager' ) ); ?>';
						$resp.html('<div class="notice notice-error inline"><p>' + msg + '</p></div>');
					}
				}).fail(function() {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					$resp.html('<div class="notice notice-error inline"><p><?php echo esc_js( __( 'Request failed. Please try again.', 'kevincho-tailoring-manager' ) ); ?></p></div>');
				});
			});
		});
		</script>
		<?php
	}

	/* ================================================================
	 * Notification Log
	 * ============================================================= */

	/**
	 * Render the notification log page with a paginated table.
	 *
	 * @return void
	 */
	public static function render_notification_log() {
		$page     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 20;

		$result = KCTM_Notification_Log::get_logs( array(
			'per_page' => $per_page,
			'page'     => $page,
		) );

		$items = $result['items'];
		$total = $result['total'];
		$pages = $result['pages'];

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Notification Log', 'kevincho-tailoring-manager' ); ?></h1>

			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'No notification logs found.', 'kevincho-tailoring-manager' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Date', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Order', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Phone', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Status', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Language', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Message', 'kevincho-tailoring-manager' ); ?></th>
							<th><?php esc_html_e( 'Response Code', 'kevincho-tailoring-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log->id ); ?></td>
								<td><?php echo esc_html( $log->sent_at ); ?></td>
								<td>
									<?php if ( $log->order_id ) : ?>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $log->order_id . '&action=edit' ) ); ?>">
											#<?php echo esc_html( $log->order_id ); ?>
										</a>
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
								<td>
									<?php
									if ( $log->customer_id ) {
										$user = get_userdata( $log->customer_id );
										echo $user ? esc_html( $user->display_name ) : esc_html( '#' . $log->customer_id );
									} else {
										echo '&mdash;';
									}
									?>
								</td>
								<td><?php echo esc_html( $log->phone ); ?></td>
								<td><?php echo esc_html( $log->status ); ?></td>
								<td><?php echo esc_html( strtoupper( $log->language ) ); ?></td>
								<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr( $log->message ); ?>">
									<?php echo esc_html( wp_trim_words( $log->message, 12, '...' ) ); ?>
								</td>
								<td>
									<span class="<?php echo absint( $log->response_code ) >= 200 && absint( $log->response_code ) < 300 ? 'dashicons dashicons-yes-alt' : 'dashicons dashicons-dismiss'; ?>"
										  style="color:<?php echo absint( $log->response_code ) >= 200 && absint( $log->response_code ) < 300 ? '#00a32a' : '#d63638'; ?>;"></span>
									<?php echo esc_html( $log->response_code ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<span class="displaying-num">
								<?php
								printf(
									/* translators: %s: number of items */
									esc_html( _n( '%s item', '%s items', $total, 'kevincho-tailoring-manager' ) ),
									esc_html( number_format_i18n( $total ) )
								);
								?>
							</span>
							<span class="pagination-links">
								<?php
								echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'current'   => $page,
									'total'     => $pages,
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
								) );
								?>
							</span>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ================================================================
	 * Personalization Admin UI
	 * ============================================================= */

	/**
	 * Render the personalization management page.
	 *
	 * Lists all groups with their options, and provides forms for
	 * adding, editing, deleting, and reordering groups and options.
	 *
	 * @return void
	 */
	public static function render_personalization() {
		$groups = KCTM_Personalization_Options::get_groups( false );

		// Check for edit/add context.
		$editing_group_id  = isset( $_GET['edit_group'] ) ? absint( $_GET['edit_group'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$editing_option_id = isset( $_GET['edit_option'] ) ? absint( $_GET['edit_option'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$add_option_to     = isset( $_GET['add_option_to'] ) ? absint( $_GET['add_option_to'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$editing_group  = $editing_group_id ? KCTM_Personalization_Options::get_group( $editing_group_id ) : null;
		$editing_option = $editing_option_id ? KCTM_Personalization_Options::get_option_item( $editing_option_id ) : null;

		$success = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$deleted = isset( $_GET['deleted'] ) && '1' === $_GET['deleted']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Garment Personalization', 'kevincho-tailoring-manager' ); ?></h1>

			<?php if ( $success ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved successfully.', 'kevincho-tailoring-manager' ); ?></p></div>
			<?php endif; ?>
			<?php if ( $deleted ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Deleted successfully.', 'kevincho-tailoring-manager' ); ?></p></div>
			<?php endif; ?>

			<!-- ── Add / Edit Group Form ──────────────────── -->
			<div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;margin-bottom:20px;">
				<h2 style="margin-top:0;">
					<?php echo $editing_group ? esc_html__( 'Edit Group', 'kevincho-tailoring-manager' ) : esc_html__( 'Add New Group', 'kevincho-tailoring-manager' ); ?>
				</h2>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'kctm_personalization_group_nonce', 'kctm_nonce' ); ?>
					<input type="hidden" name="action" value="kctm_save_personalization_group">
					<?php if ( $editing_group ) : ?>
						<input type="hidden" name="id" value="<?php echo esc_attr( $editing_group->id ); ?>">
					<?php endif; ?>

					<table class="form-table">
						<tr>
							<th><label for="group_title"><?php esc_html_e( 'Title', 'kevincho-tailoring-manager' ); ?></label></th>
							<td><input type="text" name="title" id="group_title" class="regular-text" value="<?php echo $editing_group ? esc_attr( $editing_group->title ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="group_slug"><?php esc_html_e( 'Slug', 'kevincho-tailoring-manager' ); ?></label></th>
							<td><input type="text" name="slug" id="group_slug" class="regular-text" value="<?php echo $editing_group ? esc_attr( $editing_group->slug ) : ''; ?>">
							<p class="description"><?php esc_html_e( 'Leave empty to auto-generate from title.', 'kevincho-tailoring-manager' ); ?></p></td>
						</tr>
						<tr>
							<th><label for="group_description"><?php esc_html_e( 'Description', 'kevincho-tailoring-manager' ); ?></label></th>
							<td><textarea name="description" id="group_description" class="large-text" rows="3"><?php echo $editing_group ? esc_textarea( $editing_group->description ) : ''; ?></textarea></td>
						</tr>
						<tr>
							<th><label for="group_sort_order"><?php esc_html_e( 'Sort Order', 'kevincho-tailoring-manager' ); ?></label></th>
							<td><input type="number" name="sort_order" id="group_sort_order" class="small-text" value="<?php echo $editing_group ? esc_attr( $editing_group->sort_order ) : '0'; ?>" min="0"></td>
						</tr>
						<tr>
							<th><label for="group_applies_to"><?php esc_html_e( 'Applies To', 'kevincho-tailoring-manager' ); ?></label></th>
							<td>
								<input type="text" name="applies_to" id="group_applies_to" class="regular-text" value="<?php echo $editing_group ? esc_attr( $editing_group->applies_to ) : 'all'; ?>">
								<p class="description"><?php esc_html_e( 'Product type scope. Use "all" for all products.', 'kevincho-tailoring-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="is_active" value="1" <?php checked( ! $editing_group || $editing_group->is_active ); ?>>
									<?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?>
								</label>
							</td>
						</tr>
					</table>

					<?php submit_button( $editing_group ? __( 'Update Group', 'kevincho-tailoring-manager' ) : __( 'Add Group', 'kevincho-tailoring-manager' ) ); ?>
					<?php if ( $editing_group ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-personalization' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'kevincho-tailoring-manager' ); ?></a>
					<?php endif; ?>
				</form>
			</div>

			<!-- ── Add / Edit Option Form (contextual) ────── -->
			<?php if ( $add_option_to || $editing_option ) : ?>
				<?php
				$opt_group_id = $editing_option ? $editing_option->group_id : $add_option_to;
				$opt_group    = KCTM_Personalization_Options::get_group( $opt_group_id );
				?>
				<div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;margin-bottom:20px;">
					<h2 style="margin-top:0;">
						<?php
						if ( $editing_option ) {
							printf(
								/* translators: %s: group title */
								esc_html__( 'Edit Option in "%s"', 'kevincho-tailoring-manager' ),
								$opt_group ? esc_html( $opt_group->title ) : ''
							);
						} else {
							printf(
								/* translators: %s: group title */
								esc_html__( 'Add Option to "%s"', 'kevincho-tailoring-manager' ),
								$opt_group ? esc_html( $opt_group->title ) : ''
							);
						}
						?>
					</h2>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'kctm_personalization_option_nonce', 'kctm_nonce' ); ?>
						<input type="hidden" name="action" value="kctm_save_personalization_option">
						<input type="hidden" name="group_id" value="<?php echo esc_attr( $opt_group_id ); ?>">
						<?php if ( $editing_option ) : ?>
							<input type="hidden" name="id" value="<?php echo esc_attr( $editing_option->id ); ?>">
						<?php endif; ?>

						<table class="form-table">
							<tr>
								<th><label for="opt_title"><?php esc_html_e( 'Title', 'kevincho-tailoring-manager' ); ?></label></th>
								<td><input type="text" name="title" id="opt_title" class="regular-text" value="<?php echo $editing_option ? esc_attr( $editing_option->title ) : ''; ?>" required></td>
							</tr>
							<tr>
								<th><label for="opt_slug"><?php esc_html_e( 'Slug', 'kevincho-tailoring-manager' ); ?></label></th>
								<td><input type="text" name="slug" id="opt_slug" class="regular-text" value="<?php echo $editing_option ? esc_attr( $editing_option->slug ) : ''; ?>"></td>
							</tr>
							<tr>
								<th><label for="opt_description"><?php esc_html_e( 'Description', 'kevincho-tailoring-manager' ); ?></label></th>
								<td><textarea name="description" id="opt_description" class="large-text" rows="2"><?php echo $editing_option ? esc_textarea( $editing_option->description ) : ''; ?></textarea></td>
							</tr>
							<tr>
								<th><label for="opt_image_url"><?php esc_html_e( 'Image URL', 'kevincho-tailoring-manager' ); ?></label></th>
								<td>
									<input type="text" name="image_url" id="opt_image_url" class="regular-text" value="<?php echo $editing_option ? esc_attr( $editing_option->image_url ) : ''; ?>">
									<button type="button" class="button kctm-upload-image"><?php esc_html_e( 'Upload', 'kevincho-tailoring-manager' ); ?></button>
									<?php if ( $editing_option && $editing_option->image_url ) : ?>
										<br><img src="<?php echo esc_url( $editing_option->image_url ); ?>" alt="" style="max-width:100px;max-height:100px;margin-top:10px;">
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th><label for="opt_price_modifier"><?php esc_html_e( 'Price Modifier', 'kevincho-tailoring-manager' ); ?></label></th>
								<td><input type="number" name="price_modifier" id="opt_price_modifier" class="small-text" step="0.01" value="<?php echo $editing_option ? esc_attr( $editing_option->price_modifier ) : '0'; ?>"></td>
							</tr>
							<tr>
								<th><label for="opt_sort_order"><?php esc_html_e( 'Sort Order', 'kevincho-tailoring-manager' ); ?></label></th>
								<td><input type="number" name="sort_order" id="opt_sort_order" class="small-text" value="<?php echo $editing_option ? esc_attr( $editing_option->sort_order ) : '0'; ?>" min="0"></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Default', 'kevincho-tailoring-manager' ); ?></th>
								<td><label><input type="checkbox" name="is_default" value="1" <?php checked( $editing_option && $editing_option->is_default ); ?>> <?php esc_html_e( 'Default selection', 'kevincho-tailoring-manager' ); ?></label></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?></th>
								<td><label><input type="checkbox" name="is_active" value="1" <?php checked( ! $editing_option || $editing_option->is_active ); ?>> <?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?></label></td>
							</tr>
						</table>

						<?php submit_button( $editing_option ? __( 'Update Option', 'kevincho-tailoring-manager' ) : __( 'Add Option', 'kevincho-tailoring-manager' ) ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-personalization' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'kevincho-tailoring-manager' ); ?></a>
					</form>
				</div>
			<?php endif; ?>

			<!-- ── Groups & Options List ───────────────────── -->
			<?php if ( empty( $groups ) ) : ?>
				<p><?php esc_html_e( 'No personalization groups found. Add one above.', 'kevincho-tailoring-manager' ); ?></p>
			<?php else : ?>
				<?php foreach ( $groups as $group ) : ?>
					<?php $options = KCTM_Personalization_Options::get_options( $group->id, false ); ?>
					<div style="background:#fff;padding:15px 20px;border:1px solid #ccd0d4;border-radius:4px;margin-bottom:15px;">
						<h3 style="margin-top:0;">
							<?php echo esc_html( $group->title ); ?>
							<small style="color:#666;">(<?php echo esc_html( $group->slug ); ?>) &mdash; <?php printf( esc_html__( 'Order: %d', 'kevincho-tailoring-manager' ), (int) $group->sort_order ); ?></small>
							<?php if ( ! $group->is_active ) : ?>
								<span style="color:#d63638;font-size:12px;"><?php esc_html_e( '[Inactive]', 'kevincho-tailoring-manager' ); ?></span>
							<?php endif; ?>
						</h3>
						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-personalization&edit_group=' . $group->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit Group', 'kevincho-tailoring-manager' ); ?></a>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kctm_delete_personalization_group&id=' . $group->id ), 'kctm_delete_group_' . $group->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this group and all its options?', 'kevincho-tailoring-manager' ) ); ?>');"><?php esc_html_e( 'Delete Group', 'kevincho-tailoring-manager' ); ?></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-personalization&add_option_to=' . $group->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Add Option', 'kevincho-tailoring-manager' ); ?></a>
						</p>

						<?php if ( ! empty( $options ) ) : ?>
							<table class="widefat striped" style="margin-top:10px;">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Image', 'kevincho-tailoring-manager' ); ?></th>
										<th><?php esc_html_e( 'Title', 'kevincho-tailoring-manager' ); ?></th>
										<th><?php esc_html_e( 'Slug', 'kevincho-tailoring-manager' ); ?></th>
										<th><?php esc_html_e( 'Price Mod.', 'kevincho-tailoring-manager' ); ?></th>
										<th><?php esc_html_e( 'Order', 'kevincho-tailoring-manager' ); ?></th>
										<th><?php esc_html_e( 'Default', 'kevincho-tailoring-manager' ); ?></th>
										<th><?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'kevincho-tailoring-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $options as $option ) : ?>
										<tr>
											<td>
												<?php if ( $option->image_url ) : ?>
													<img src="<?php echo esc_url( $option->image_url ); ?>" alt="" style="max-width:50px;max-height:50px;">
												<?php else : ?>
													&mdash;
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $option->title ); ?></td>
											<td><?php echo esc_html( $option->slug ); ?></td>
											<td><?php echo esc_html( number_format( (float) $option->price_modifier, 2 ) ); ?></td>
											<td><?php echo esc_html( $option->sort_order ); ?></td>
											<td><?php echo $option->is_default ? '<span class="dashicons dashicons-yes" style="color:#00a32a;"></span>' : '&mdash;'; ?></td>
											<td><?php echo $option->is_active ? '<span class="dashicons dashicons-yes" style="color:#00a32a;"></span>' : '<span class="dashicons dashicons-no" style="color:#d63638;"></span>'; ?></td>
											<td>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-personalization&edit_option=' . $option->id ) ); ?>"><?php esc_html_e( 'Edit', 'kevincho-tailoring-manager' ); ?></a>
												|
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kctm_delete_personalization_option&id=' . $option->id ), 'kctm_delete_option_' . $option->id ) ); ?>" class="delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this option?', 'kevincho-tailoring-manager' ) ); ?>');"><?php esc_html_e( 'Delete', 'kevincho-tailoring-manager' ); ?></a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p style="color:#666;"><em><?php esc_html_e( 'No options in this group yet.', 'kevincho-tailoring-manager' ); ?></em></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<script>
		jQuery(function($) {
			$('.kctm-upload-image').on('click', function(e) {
				e.preventDefault();
				var $input = $(this).prev('input');
				var frame = wp.media({
					title: '<?php echo esc_js( __( 'Select Image', 'kevincho-tailoring-manager' ) ); ?>',
					button: { text: '<?php echo esc_js( __( 'Use Image', 'kevincho-tailoring-manager' ) ); ?>' },
					multiple: false
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$input.val(attachment.url);
				});
				frame.open();
			});
		});
		</script>
		<?php
	}

	/* ================================================================
	 * Personalization Form Handlers
	 * ============================================================= */

	/**
	 * Handle saving a personalization group.
	 *
	 * @return void
	 */
	public static function handle_save_personalization_group() {
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_personalization_group_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		$data = array(
			'title'       => isset( $_POST['title'] )       ? sanitize_text_field( wp_unslash( $_POST['title'] ) )       : '',
			'slug'        => isset( $_POST['slug'] )         ? sanitize_text_field( wp_unslash( $_POST['slug'] ) )        : '',
			'description' => isset( $_POST['description'] )  ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'sort_order'  => isset( $_POST['sort_order'] )   ? absint( $_POST['sort_order'] )  : 0,
			'is_active'   => isset( $_POST['is_active'] )    ? 1 : 0,
			'applies_to'  => isset( $_POST['applies_to'] )   ? sanitize_text_field( wp_unslash( $_POST['applies_to'] ) ) : 'all',
		);

		if ( ! empty( $_POST['id'] ) ) {
			$data['id'] = absint( $_POST['id'] );
		}

		KCTM_Personalization_Options::save_group( $data );

		wp_safe_redirect( admin_url( 'admin.php?page=kctm-personalization&updated=1' ) );
		exit;
	}

	/**
	 * Handle saving a personalization option.
	 *
	 * @return void
	 */
	public static function handle_save_personalization_option() {
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_personalization_option_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		$data = array(
			'group_id'       => isset( $_POST['group_id'] )       ? absint( $_POST['group_id'] )                                       : 0,
			'title'          => isset( $_POST['title'] )           ? sanitize_text_field( wp_unslash( $_POST['title'] ) )               : '',
			'slug'           => isset( $_POST['slug'] )            ? sanitize_text_field( wp_unslash( $_POST['slug'] ) )                : '',
			'description'    => isset( $_POST['description'] )     ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) )     : '',
			'image_url'      => isset( $_POST['image_url'] )       ? esc_url_raw( wp_unslash( $_POST['image_url'] ) )                   : '',
			'price_modifier' => isset( $_POST['price_modifier'] )  ? floatval( $_POST['price_modifier'] )                               : 0.00,
			'is_default'     => isset( $_POST['is_default'] )      ? 1 : 0,
			'sort_order'     => isset( $_POST['sort_order'] )      ? absint( $_POST['sort_order'] )                                     : 0,
			'is_active'      => isset( $_POST['is_active'] )       ? 1 : 0,
		);

		if ( ! empty( $_POST['id'] ) ) {
			$data['id'] = absint( $_POST['id'] );
		}

		KCTM_Personalization_Options::save_option( $data );

		wp_safe_redirect( admin_url( 'admin.php?page=kctm-personalization&updated=1' ) );
		exit;
	}

	/**
	 * Handle deleting a personalization group.
	 *
	 * @return void
	 */
	public static function handle_delete_personalization_group() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $id || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'kctm_delete_group_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		KCTM_Personalization_Options::delete_group( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=kctm-personalization&deleted=1' ) );
		exit;
	}

	/**
	 * Handle deleting a personalization option.
	 *
	 * @return void
	 */
	public static function handle_delete_personalization_option() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $id || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'kctm_delete_option_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		KCTM_Personalization_Options::delete_option( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=kctm-personalization&deleted=1' ) );
		exit;
	}
}
