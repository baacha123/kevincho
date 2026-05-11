<?php
/**
 * Admin Customers List Table
 *
 * Displays a paginated, searchable list of all customers with
 * measurement status, order counts, and type information.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class KCTM_Admin_Customers
 *
 * Extends WP_List_Table to provide a customer management interface
 * within the Tailoring admin area.
 */
class KCTM_Admin_Customers extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'customer',
			'plural'   => 'customers',
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
			'customer'     => __( 'Customer', 'kevincho-tailoring-manager' ),
			'email'        => __( 'Email', 'kevincho-tailoring-manager' ),
			'phone'        => __( 'Phone', 'kevincho-tailoring-manager' ),
			'type'         => __( 'Type', 'kevincho-tailoring-manager' ),
			'measurements' => __( 'Measurements', 'kevincho-tailoring-manager' ),
			'orders'       => __( 'Orders', 'kevincho-tailoring-manager' ),
			'registered'   => __( 'Registered', 'kevincho-tailoring-manager' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array Column slug => array( orderby, default_desc ).
	 */
	public function get_sortable_columns() {
		return array(
			'customer'   => array( 'display_name', false ),
			'email'      => array( 'user_email', false ),
			'registered' => array( 'user_registered', true ),
		);
	}

	/**
	 * Render the customer name column with a link to the measurements page.
	 *
	 * @param WP_User $item The user object.
	 * @return string Column output.
	 */
	public function column_customer( $item ) {
		$edit_url = admin_url( 'admin.php?page=kctm-customer-measurements&customer_id=' . $item->ID );
		$name     = trim( $item->first_name . ' ' . $item->last_name );

		if ( empty( $name ) ) {
			$name = $item->display_name;
		}

		$actions = array(
			'measurements' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'View / Edit Measurements', 'kevincho-tailoring-manager' )
			),
			'wp_profile' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_user_link( $item->ID ) ),
				esc_html__( 'Edit User', 'kevincho-tailoring-manager' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render the email column.
	 *
	 * @param WP_User $item The user object.
	 * @return string Column output.
	 */
	public function column_email( $item ) {
		return esc_html( $item->user_email );
	}

	/**
	 * Render the phone column.
	 *
	 * @param WP_User $item The user object.
	 * @return string Column output.
	 */
	public function column_phone( $item ) {
		$phone = get_user_meta( $item->ID, '_kctm_phone', true );

		if ( empty( $phone ) ) {
			$phone = get_user_meta( $item->ID, 'billing_phone', true );
		}

		return $phone ? esc_html( $phone ) : '<span style="color:#999;">&mdash;</span>';
	}

	/**
	 * Render the type column (Regular / Walk-in).
	 *
	 * @param WP_User $item The user object.
	 * @return string Column output.
	 */
	public function column_type( $item ) {
		$type = get_user_meta( $item->ID, '_kctm_customer_type', true );

		if ( 'walkin' === $type ) {
			return '<span style="color:#d63638;">' . esc_html__( 'Walk-in', 'kevincho-tailoring-manager' ) . '</span>';
		}

		return esc_html__( 'Regular', 'kevincho-tailoring-manager' );
	}

	/**
	 * Render the measurements status column.
	 *
	 * Green checkmark if the customer has measurements, red X if not.
	 *
	 * @param WP_User $item The user object.
	 * @return string Column output.
	 */
	public function column_measurements( $item ) {
		$gender = get_user_meta( $item->ID, '_kctm_measurement_gender', true );

		if ( $gender ) {
			return '<span class="dashicons dashicons-yes-alt" style="color:#00a32a;" title="' . esc_attr__( 'Has measurements', 'kevincho-tailoring-manager' ) . '"></span>';
		}

		return '<span class="dashicons dashicons-dismiss" style="color:#d63638;" title="' . esc_attr__( 'No measurements', 'kevincho-tailoring-manager' ) . '"></span>';
	}

	/**
	 * Render the order count column.
	 *
	 * @param WP_User $item The user object.
	 * @return string Column output.
	 */
	public function column_orders( $item ) {
		$orders = wc_get_orders( array(
			'customer_id' => $item->ID,
			'limit'       => -1,
			'return'      => 'ids',
		) );

		$count = count( $orders );

		if ( $count > 0 ) {
			$url = admin_url( 'edit.php?post_type=shop_order&_customer_user=' . $item->ID );
			return '<a href="' . esc_url( $url ) . '">' . esc_html( $count ) . '</a>';
		}

		return '0';
	}

	/**
	 * Render the registration date column.
	 *
	 * @param WP_User $item The user object.
	 * @return string Column output.
	 */
	public function column_registered( $item ) {
		return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->user_registered ) ) );
	}

	/**
	 * Default column renderer.
	 *
	 * @param WP_User $item        The user object.
	 * @param string  $column_name The column key.
	 * @return string Column output.
	 */
	public function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Get filter links displayed above the table.
	 *
	 * Provides "All" and "Has Measurements" filter views.
	 *
	 * @return array Array of view links.
	 */
	protected function get_views() {
		$current = isset( $_GET['has_measurements'] ) ? sanitize_text_field( wp_unslash( $_GET['has_measurements'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$all_url  = admin_url( 'admin.php?page=kctm-customers' );
		$meas_url = admin_url( 'admin.php?page=kctm-customers&has_measurements=1' );

		$views = array(
			'all' => sprintf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $all_url ),
				empty( $current ) ? 'current' : '',
				esc_html__( 'All', 'kevincho-tailoring-manager' )
			),
			'has_measurements' => sprintf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $meas_url ),
				'1' === $current ? 'current' : '',
				esc_html__( 'Has Measurements', 'kevincho-tailoring-manager' )
			),
		);

		return $views;
	}

	/**
	 * Prepare the table items.
	 *
	 * Queries users with the 'customer' role, applies search, pagination,
	 * and filtering.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 20;
		$paged    = $this->get_pagenum();
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args = array(
			'role'    => 'customer',
			'number'  => $per_page,
			'offset'  => ( $paged - 1 ) * $per_page,
			'orderby' => 'user_registered',
			'order'   => 'DESC',
		);

		// Handle sorting.
		if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$allowed_orderby = array( 'display_name', 'user_email', 'user_registered' );
			$orderby         = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $orderby, $allowed_orderby, true ) ) {
				$args['orderby'] = $orderby;
			}
		}

		if ( isset( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order = strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
				$args['order'] = $order;
			}
		}

		// Search by name, email, or phone.
		if ( ! empty( $search ) ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );

			// Also search by phone via meta query.
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_kctm_phone',
					'value'   => $search,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'billing_phone',
					'value'   => $search,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'first_name',
					'value'   => $search,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'last_name',
					'value'   => $search,
					'compare' => 'LIKE',
				),
			);
		}

		// Filter: only customers with measurements.
		if ( isset( $_GET['has_measurements'] ) && '1' === $_GET['has_measurements'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

			// Wrap existing meta query if present.
			if ( ! empty( $meta_query ) ) {
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					$meta_query,
					array(
						'key'     => '_kctm_measurement_gender',
						'compare' => 'EXISTS',
					),
				);
			} else {
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_kctm_measurement_gender',
						'compare' => 'EXISTS',
					),
				);
			}
		}

		$query = new WP_User_Query( $args );

		$this->items = $query->get_results();
		$total_items = $query->get_total();

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
	 * Display a message when no customers are found.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No customers found.', 'kevincho-tailoring-manager' );
	}

	/**
	 * Static render method called by the admin menu callback.
	 *
	 * Creates the table instance, prepares items, and displays
	 * the full page including search box.
	 *
	 * @return void
	 */
	public static function render() {
		$table = new self();
		$table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Customers', 'kevincho-tailoring-manager' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-walkin' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add Walk-in Customer', 'kevincho-tailoring-manager' ); ?></a>
			<hr class="wp-header-end">

			<form method="get">
				<input type="hidden" name="page" value="kctm-customers">
				<?php
				$table->views();
				$table->search_box( __( 'Search Customers', 'kevincho-tailoring-manager' ), 'kctm_customer_search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
}
