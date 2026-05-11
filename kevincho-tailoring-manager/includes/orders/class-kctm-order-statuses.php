<?php
/**
 * Custom WooCommerce Order Statuses for the Tailoring Workflow.
 *
 * Workflow: Pending Payment -> Confirmed -> In Progress -> Ready for Pickup -> Delivered -> Completed
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Order_Statuses
 *
 * Registers and manages custom WooCommerce order statuses for the tailoring workflow.
 */
class KCTM_Order_Statuses {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_custom_statuses' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'add_custom_statuses_to_woocommerce' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'add_bulk_actions' ) );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'add_bulk_actions' ) );
		add_action( 'admin_head', array( __CLASS__, 'output_status_colors_css' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( __CLASS__, 'add_confirmed_to_payment_statuses' ), 10, 2 );
	}

	/**
	 * Return the custom statuses registered by this plugin.
	 *
	 * @return array Associative array of slug => label.
	 */
	public static function get_custom_statuses() {
		return array(
			'wc-kctm-confirmed'    => _x( 'Confirmed', 'Order status', 'kevincho-tailoring-manager' ),
			'wc-kctm-in-progress'  => _x( 'In Progress', 'Order status', 'kevincho-tailoring-manager' ),
			'wc-kctm-ready-pickup' => _x( 'Ready for Pickup', 'Order status', 'kevincho-tailoring-manager' ),
			'wc-kctm-with-driver'  => _x( 'With Driver', 'Order status', 'kevincho-tailoring-manager' ),
			'wc-kctm-delivered'    => _x( 'Delivered', 'Order status', 'kevincho-tailoring-manager' ),
		);
	}

	/**
	 * Return the full workflow progression including WooCommerce built-in statuses.
	 *
	 * @return array Ordered array of status slugs representing the workflow.
	 */
	public static function get_workflow_order() {
		return array(
			'wc-pending',
			'wc-kctm-confirmed',
			'wc-kctm-in-progress',
			'wc-kctm-ready-pickup',
			'wc-kctm-with-driver',
			'wc-kctm-delivered',
			'wc-completed',
		);
	}

	/**
	 * Register custom post statuses for each tailoring workflow status.
	 *
	 * @return void
	 */
	public static function register_custom_statuses() {
		register_post_status( 'wc-kctm-confirmed', array(
			'label'                     => _x( 'Confirmed', 'Order status', 'kevincho-tailoring-manager' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop(
				'Confirmed <span class="count">(%s)</span>',
				'Confirmed <span class="count">(%s)</span>',
				'kevincho-tailoring-manager'
			),
		) );

		register_post_status( 'wc-kctm-in-progress', array(
			'label'                     => _x( 'In Progress', 'Order status', 'kevincho-tailoring-manager' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop(
				'In Progress <span class="count">(%s)</span>',
				'In Progress <span class="count">(%s)</span>',
				'kevincho-tailoring-manager'
			),
		) );

		register_post_status( 'wc-kctm-ready-pickup', array(
			'label'                     => _x( 'Ready for Pickup', 'Order status', 'kevincho-tailoring-manager' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop(
				'Ready for Pickup <span class="count">(%s)</span>',
				'Ready for Pickup <span class="count">(%s)</span>',
				'kevincho-tailoring-manager'
			),
		) );

		register_post_status( 'wc-kctm-with-driver', array(
			'label'                     => _x( 'With Driver', 'Order status', 'kevincho-tailoring-manager' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop(
				'With Driver <span class="count">(%s)</span>',
				'With Driver <span class="count">(%s)</span>',
				'kevincho-tailoring-manager'
			),
		) );

		register_post_status( 'wc-kctm-delivered', array(
			'label'                     => _x( 'Delivered', 'Order status', 'kevincho-tailoring-manager' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop(
				'Delivered <span class="count">(%s)</span>',
				'Delivered <span class="count">(%s)</span>',
				'kevincho-tailoring-manager'
			),
		) );
	}

	/**
	 * Add custom statuses to the WooCommerce order statuses dropdown.
	 *
	 * Inserts the custom statuses into the correct workflow position.
	 *
	 * @param  array $order_statuses Existing WooCommerce order statuses.
	 * @return array Modified order statuses.
	 */
	public static function add_custom_statuses_to_woocommerce( $order_statuses ) {
		$new_statuses = array();

		foreach ( $order_statuses as $key => $status ) {
			$new_statuses[ $key ] = $status;

			// Insert custom statuses after "Pending payment".
			if ( 'wc-pending' === $key ) {
				$new_statuses['wc-kctm-confirmed']    = _x( 'Confirmed', 'Order status', 'kevincho-tailoring-manager' );
				$new_statuses['wc-kctm-in-progress']  = _x( 'In Progress', 'Order status', 'kevincho-tailoring-manager' );
				$new_statuses['wc-kctm-ready-pickup'] = _x( 'Ready for Pickup', 'Order status', 'kevincho-tailoring-manager' );
				$new_statuses['wc-kctm-with-driver']  = _x( 'With Driver', 'Order status', 'kevincho-tailoring-manager' );
				$new_statuses['wc-kctm-delivered']    = _x( 'Delivered', 'Order status', 'kevincho-tailoring-manager' );
			}
		}

		return $new_statuses;
	}

	/**
	 * Add bulk actions for custom statuses on the orders list screen.
	 *
	 * Works for both legacy (edit-shop_order) and HPOS (woocommerce_page_wc-orders) screens.
	 *
	 * @param  array $actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public static function add_bulk_actions( $actions ) {
		$custom_statuses = self::get_custom_statuses();

		foreach ( $custom_statuses as $slug => $label ) {
			// Bulk action keys use the status without the "wc-" prefix.
			$status_key = str_replace( 'wc-', '', $slug );
			/* translators: %s: order status label */
			$actions[ 'mark_' . $status_key ] = sprintf(
				__( 'Change status to %s', 'kevincho-tailoring-manager' ),
				$label
			);
		}

		return $actions;
	}

	/**
	 * Output inline CSS to color-code custom status labels in the admin order list.
	 *
	 * @return void
	 */
	public static function output_status_colors_css() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		// Only output on order list screens (legacy and HPOS).
		$valid_screens = array( 'edit-shop_order', 'woocommerce_page_wc-orders' );
		if ( ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

		$colors = array(
			'kctm-confirmed'    => '#2271b1',
			'kctm-in-progress'  => '#d63638',
			'kctm-ready-pickup' => '#00a32a',
			'kctm-with-driver'  => '#e67e22',
			'kctm-delivered'    => '#8c8f94',
		);

		echo '<style>';
		foreach ( $colors as $status => $color ) {
			printf(
				'mark.order-status.status-%s { background-color: %s; color: #fff; }',
				esc_attr( $status ),
				esc_attr( $color )
			);
		}
		echo '</style>';
	}

	/**
	 * Allow payment for orders in the "Confirmed" status.
	 *
	 * @param  array    $statuses Valid statuses for payment.
	 * @param  WC_Order $order    The order object.
	 * @return array Modified statuses.
	 */
	public static function add_confirmed_to_payment_statuses( $statuses, $order = null ) {
		$statuses[] = 'kctm-confirmed';
		return $statuses;
	}
}
