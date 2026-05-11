<?php
/**
 * Admin Consultations List Table
 *
 * Displays a paginated, searchable list of all consultation bookings
 * with status badges, payment status, and inline actions.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class KCTM_Admin_Consultations
 *
 * Extends WP_List_Table to provide a consultation booking management
 * interface within the Tailoring admin area.
 */
class KCTM_Admin_Consultations extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'consultation',
			'plural'   => 'consultations',
			'ajax'     => false,
		) );
	}

	/**
	 * Define the table columns.
	 *
	 * @return array Column slug => label.
	 */
	public function get_columns() {
		return array(
			'cb'                => '<input type="checkbox">',
			'id'                => __( 'ID', 'kevincho-tailoring-manager' ),
			'customer_name'     => __( 'Customer', 'kevincho-tailoring-manager' ),
			'phone'             => __( 'Phone', 'kevincho-tailoring-manager' ),
			'consultation_date' => __( 'Date', 'kevincho-tailoring-manager' ),
			'consultation_time' => __( 'Time', 'kevincho-tailoring-manager' ),
			'status'            => __( 'Status', 'kevincho-tailoring-manager' ),
			'payment_status'    => __( 'Payment', 'kevincho-tailoring-manager' ),
			'actions'           => __( 'Actions', 'kevincho-tailoring-manager' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array Column slug => array( orderby, default_desc ).
	 */
	public function get_sortable_columns() {
		return array(
			'consultation_date' => array( 'consultation_date', true ),
			'status'            => array( 'status', false ),
		);
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array Action slug => label.
	 */
	public function get_bulk_actions() {
		return array(
			'mark_completed' => __( 'Mark Completed', 'kevincho-tailoring-manager' ),
			'cancel'         => __( 'Cancel', 'kevincho-tailoring-manager' ),
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * Handles mark_completed and cancel bulk actions with nonce
	 * verification and capability checks.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		// Verify bulk action nonce.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-consultations' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		$ids = isset( $_REQUEST['consultation'] ) && is_array( $_REQUEST['consultation'] )
			? array_map( 'absint', $_REQUEST['consultation'] )
			: array();

		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'kctm_consultations';

		foreach ( $ids as $id ) {
			if ( 'mark_completed' === $action ) {
				$wpdb->update(
					$table,
					array( 'status' => 'completed' ),
					array( 'id' => $id ),
					array( '%s' ),
					array( '%d' )
				);
			} elseif ( 'cancel' === $action ) {
				$wpdb->update(
					$table,
					array( 'status' => 'cancelled' ),
					array( 'id' => $id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * Prepare the table items.
	 *
	 * Queries the kctm_consultations table with search, status filter,
	 * pagination, and ordering support.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 20;
		$paged    = $this->get_pagenum();
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status   = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Process bulk actions before querying.
		$this->process_bulk_action();

		global $wpdb;
		$table = $wpdb->prefix . 'kctm_consultations';

		$where  = array( '1=1' );
		$values = array();

		// Filter by status.
		$allowed_statuses = array( 'pending', 'confirmed', 'completed', 'cancelled', 'no-show' );
		if ( ! empty( $status ) && in_array( $status, $allowed_statuses, true ) ) {
			$where[]  = 'status = %s';
			$values[] = $status;
		}

		// Search by customer name or phone.
		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '( first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s )';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_clause = implode( ' AND ', $where );

		// Handle ordering.
		$orderby = 'consultation_date';
		$order   = 'DESC';

		if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$allowed_orderby = array( 'consultation_date', 'status' );
			$req_orderby     = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $req_orderby, $allowed_orderby, true ) ) {
				$orderby = $req_orderby;
			}
		}

		if ( isset( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$req_order = strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $req_order, array( 'ASC', 'DESC' ), true ) ) {
				$order = $req_order;
			}
		}

		// Count total items.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$total_items = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Fetch items.
		$offset    = ( $paged - 1 ) * $per_page;
		$query_sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$values[]  = $per_page;
		$values[]  = $offset;

		$this->items = $wpdb->get_results( $wpdb->prepare( $query_sql, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Default column renderer.
	 *
	 * @param object $item        The consultation row object.
	 * @param string $column_name The column key.
	 * @return string Column output.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return esc_html( $item->id );

			case 'phone':
				return ! empty( $item->phone ) ? esc_html( $item->phone ) : '<span style="color:#999;">&mdash;</span>';

			case 'consultation_date':
				return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->consultation_date ) ) );

			case 'consultation_time':
				return esc_html( date_i18n( get_option( 'time_format' ), strtotime( $item->consultation_time ) ) );

			default:
				return '';
		}
	}

	/**
	 * Render the checkbox column for bulk actions.
	 *
	 * @param object $item The consultation row object.
	 * @return string Column output.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="consultation[]" value="%d">',
			absint( $item->id )
		);
	}

	/**
	 * Render the customer name column with row actions.
	 *
	 * Shows first_name + last_name with Edit, Mark Complete, and Cancel
	 * row actions.
	 *
	 * @param object $item The consultation row object.
	 * @return string Column output.
	 */
	public function column_customer_name( $item ) {
		$name = trim( $item->first_name . ' ' . $item->last_name );

		if ( empty( $name ) ) {
			$name = __( '(No name)', 'kevincho-tailoring-manager' );
		}

		$edit_url = admin_url( 'admin.php?page=kctm-consultation-settings&action=edit&booking_id=' . absint( $item->id ) );

		$mark_complete_url = wp_nonce_url(
			admin_url( 'admin.php?page=kctm-consultations&action=mark_complete&booking_id=' . absint( $item->id ) ),
			'kctm_consultation_action_' . $item->id
		);

		$cancel_url = wp_nonce_url(
			admin_url( 'admin.php?page=kctm-consultations&action=cancel_booking&booking_id=' . absint( $item->id ) ),
			'kctm_consultation_action_' . $item->id
		);

		$actions = array(
			'edit' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'kevincho-tailoring-manager' )
			),
		);

		if ( 'completed' !== $item->status && 'cancelled' !== $item->status ) {
			$actions['mark_complete'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $mark_complete_url ),
				esc_html__( 'Mark Complete', 'kevincho-tailoring-manager' )
			);

			$actions['cancel'] = sprintf(
				'<a href="%s" style="color:#d63638;">%s</a>',
				esc_url( $cancel_url ),
				esc_html__( 'Cancel', 'kevincho-tailoring-manager' )
			);
		}

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render the status column with a colored badge.
	 *
	 * Colours follow the same style as order statuses:
	 * pending=orange, confirmed=blue, completed=green,
	 * cancelled=red, no-show=gray.
	 *
	 * @param object $item The consultation row object.
	 * @return string Column output.
	 */
	public function column_status( $item ) {
		$status = $item->status;

		$colors = array(
			'pending'   => '#dba617',
			'confirmed' => '#2271b1',
			'completed' => '#00a32a',
			'cancelled' => '#d63638',
			'no-show'   => '#787c82',
		);

		$bg_colors = array(
			'pending'   => '#fcf0c3',
			'confirmed' => '#d5e5f2',
			'completed' => '#d1f0d9',
			'cancelled' => '#f5d5d6',
			'no-show'   => '#e2e4e7',
		);

		$labels = array(
			'pending'   => __( 'Pending', 'kevincho-tailoring-manager' ),
			'confirmed' => __( 'Confirmed', 'kevincho-tailoring-manager' ),
			'completed' => __( 'Completed', 'kevincho-tailoring-manager' ),
			'cancelled' => __( 'Cancelled', 'kevincho-tailoring-manager' ),
			'no-show'   => __( 'No Show', 'kevincho-tailoring-manager' ),
		);

		$color    = isset( $colors[ $status ] ) ? $colors[ $status ] : '#787c82';
		$bg_color = isset( $bg_colors[ $status ] ) ? $bg_colors[ $status ] : '#e2e4e7';
		$label    = isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );

		return sprintf(
			'<span style="display:inline-block;padding:3px 8px;border-radius:3px;font-size:12px;font-weight:600;color:%s;background:%s;">%s</span>',
			esc_attr( $color ),
			esc_attr( $bg_color ),
			esc_html( $label )
		);
	}

	/**
	 * Render the payment status column with a badge.
	 *
	 * @param object $item The consultation row object.
	 * @return string Column output.
	 */
	public function column_payment_status( $item ) {
		$payment_status = isset( $item->payment_status ) ? $item->payment_status : 'unpaid';

		if ( 'paid' === $payment_status ) {
			return sprintf(
				'<span style="display:inline-block;padding:3px 8px;border-radius:3px;font-size:12px;font-weight:600;color:#00a32a;background:#d1f0d9;">%s</span>',
				esc_html__( 'Paid', 'kevincho-tailoring-manager' )
			);
		}

		return sprintf(
			'<span style="display:inline-block;padding:3px 8px;border-radius:3px;font-size:12px;font-weight:600;color:#dba617;background:#fcf0c3;">%s</span>',
			esc_html__( 'Unpaid', 'kevincho-tailoring-manager' )
		);
	}

	/**
	 * Render the actions column with action links.
	 *
	 * @param object $item The consultation row object.
	 * @return string Column output.
	 */
	public function column_actions( $item ) {
		$edit_url = admin_url( 'admin.php?page=kctm-consultation-settings&action=edit&booking_id=' . absint( $item->id ) );

		$output = sprintf(
			'<a href="%s" class="button button-small">%s</a>',
			esc_url( $edit_url ),
			esc_html__( 'Edit', 'kevincho-tailoring-manager' )
		);

		return $output;
	}

	/**
	 * Get filter links displayed above the table.
	 *
	 * Provides All, Pending, Confirmed, Completed, and Cancelled
	 * filter views with counts.
	 *
	 * @return array Array of view links.
	 */
	protected function get_views() {
		global $wpdb;

		$table   = $wpdb->prefix . 'kctm_consultations';
		$current = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Get counts for each status.
		$total_count     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pending_count   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'pending' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$confirmed_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'confirmed' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$completed_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'completed' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cancelled_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'cancelled' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$base_url = admin_url( 'admin.php?page=kctm-consultations' );

		$views = array(
			'all' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( $base_url ),
				empty( $current ) ? 'current' : '',
				esc_html__( 'All', 'kevincho-tailoring-manager' ),
				$total_count
			),
			'pending' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', 'pending', $base_url ) ),
				'pending' === $current ? 'current' : '',
				esc_html__( 'Pending', 'kevincho-tailoring-manager' ),
				$pending_count
			),
			'confirmed' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', 'confirmed', $base_url ) ),
				'confirmed' === $current ? 'current' : '',
				esc_html__( 'Confirmed', 'kevincho-tailoring-manager' ),
				$confirmed_count
			),
			'completed' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', 'completed', $base_url ) ),
				'completed' === $current ? 'current' : '',
				esc_html__( 'Completed', 'kevincho-tailoring-manager' ),
				$completed_count
			),
			'cancelled' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', 'cancelled', $base_url ) ),
				'cancelled' === $current ? 'current' : '',
				esc_html__( 'Cancelled', 'kevincho-tailoring-manager' ),
				$cancelled_count
			),
		);

		return $views;
	}

	/**
	 * Add status filter dropdown above the table.
	 *
	 * @param string $which Position: 'top' or 'bottom'.
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$statuses = array(
			''          => __( 'All Statuses', 'kevincho-tailoring-manager' ),
			'pending'   => __( 'Pending', 'kevincho-tailoring-manager' ),
			'confirmed' => __( 'Confirmed', 'kevincho-tailoring-manager' ),
			'completed' => __( 'Completed', 'kevincho-tailoring-manager' ),
			'cancelled' => __( 'Cancelled', 'kevincho-tailoring-manager' ),
			'no-show'   => __( 'No Show', 'kevincho-tailoring-manager' ),
		);

		?>
		<div class="alignleft actions">
			<select name="status">
				<?php foreach ( $statuses as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Filter', 'kevincho-tailoring-manager' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Display a message when no consultations are found.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No consultations found.', 'kevincho-tailoring-manager' );
	}

	/**
	 * Static render method called by the admin menu callback.
	 *
	 * Creates the table instance, handles single-row actions,
	 * prepares items, and displays the full page including search box.
	 *
	 * @return void
	 */
	public static function render() {

		// Handle single-row actions (mark complete / cancel).
		if ( isset( $_GET['action'] ) && isset( $_GET['booking_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action     = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$booking_id = absint( $_GET['booking_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $booking_id && in_array( $action, array( 'mark_complete', 'cancel_booking' ), true ) ) {
				$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

				if ( wp_verify_nonce( $nonce, 'kctm_consultation_action_' . $booking_id ) && current_user_can( 'manage_woocommerce' ) ) {
					global $wpdb;
					$table = $wpdb->prefix . 'kctm_consultations';

					$new_status = ( 'mark_complete' === $action ) ? 'completed' : 'cancelled';

					$wpdb->update(
						$table,
						array( 'status' => $new_status ),
						array( 'id' => $booking_id ),
						array( '%s' ),
						array( '%d' )
					);

					wp_safe_redirect( admin_url( 'admin.php?page=kctm-consultations&updated=1' ) );
					exit;
				}
			}
		}

		$table = new self();
		$table->prepare_items();

		$total_items = $table->get_pagination_arg( 'total_items' );

		$success = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Consultations', 'kevincho-tailoring-manager' ); ?>
				<span class="title-count theme-count"><?php echo esc_html( $total_items ); ?></span>
			</h1>
			<hr class="wp-header-end">

			<?php if ( $success ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Consultation updated successfully.', 'kevincho-tailoring-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="get">
				<input type="hidden" name="page" value="kctm-consultations">
				<?php
				$table->views();
				$table->search_box( __( 'Search Consultations', 'kevincho-tailoring-manager' ), 'kctm_consultation_search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
}
