<?php
/**
 * My Account Endpoints
 *
 * Adds a "My Measurements" tab to the WooCommerce My Account page.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_My_Account_Endpoints
 *
 * Registers the measurements endpoint and menu item
 * within the WooCommerce My Account area.
 */
class KCTM_My_Account_Endpoints {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_endpoint' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ) );
		add_action( 'woocommerce_account_measurements_endpoint', array( __CLASS__, 'render_endpoint' ) );
	}

	/**
	 * Register the "measurements" rewrite endpoint.
	 *
	 * @return void
	 */
	public static function register_endpoint() {
		add_rewrite_endpoint( 'measurements', EP_ROOT | EP_PAGES );
	}

	/**
	 * Add "measurements" to the list of recognised query vars.
	 *
	 * @param  array $vars Existing query vars.
	 * @return array
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'measurements';
		return $vars;
	}

	/**
	 * Insert "My Measurements" into the My Account menu,
	 * immediately before the "Log out" link.
	 *
	 * @param  array $items Existing menu items.
	 * @return array
	 */
	public static function add_menu_item( $items ) {
		$new_items = array();

		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$new_items['measurements'] = __( 'My Measurements', 'kevincho-tailoring-manager' );
			}
			$new_items[ $key ] = $label;
		}

		// Fallback: if logout was not found, append at the end.
		if ( ! isset( $new_items['measurements'] ) ) {
			$new_items['measurements'] = __( 'My Measurements', 'kevincho-tailoring-manager' );
		}

		return $new_items;
	}

	/**
	 * Render the measurements endpoint content.
	 *
	 * Looks for the template in the active theme first, then falls back
	 * to the plugin's bundled template.
	 *
	 * Template search order:
	 *  1. {theme}/woocommerce/myaccount/measurements.php
	 *  2. {plugin}/templates/myaccount/measurements.php
	 *
	 * @return void
	 */
	public static function render_endpoint() {
		$template_name = 'myaccount/measurements.php';

		// Check the theme / child-theme first.
		$theme_template = locate_template(
			array(
				'woocommerce/' . $template_name,
			)
		);

		if ( $theme_template ) {
			load_template( $theme_template, false );
		} else {
			$plugin_template = plugin_dir_path( dirname( __FILE__, 2 ) ) . 'templates/' . $template_name;

			if ( file_exists( $plugin_template ) ) {
				load_template( $plugin_template, false );
			}
		}
	}
}
