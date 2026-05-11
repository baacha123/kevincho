<?php
/**
 * Admin Fabrics Management
 *
 * Handles the admin Fabrics page under the "Tailoring" menu,
 * including form processing for adding, editing, and deleting fabrics.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Admin_Fabrics
 *
 * Registers admin_post hooks for saving and deleting fabrics,
 * and renders the fabrics management page via an included template.
 */
class KCTM_Admin_Fabrics {

	/**
	 * Initialize hooks.
	 *
	 * Registers admin_post actions for saving and deleting fabrics.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_kctm_save_fabric', array( __CLASS__, 'handle_save_fabric' ) );
		add_action( 'admin_post_kctm_delete_fabric', array( __CLASS__, 'handle_delete_fabric' ) );
	}

	/**
	 * Render the fabrics management page.
	 *
	 * Includes the admin template that displays the add/edit form
	 * and the full fabric listing table.
	 *
	 * @return void
	 */
	public static function render() {
		$template = KCTM_PLUGIN_DIR . 'templates/admin/fabrics-list.php';

		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Handle saving a fabric (add or update).
	 *
	 * Validates the nonce, checks capability, sanitizes all input
	 * fields, and delegates to KCTM_Fabric_Catalog::save_fabric().
	 *
	 * @return void
	 */
	public static function handle_save_fabric() {
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_fabric_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		$allowed_patterns = array( 'solid', 'striped', 'checkered', 'herringbone', 'plaid' );
		$pattern_type     = isset( $_POST['pattern_type'] ) ? sanitize_text_field( wp_unslash( $_POST['pattern_type'] ) ) : 'solid';

		if ( ! in_array( $pattern_type, $allowed_patterns, true ) ) {
			$pattern_type = 'solid';
		}

		$data = array(
			'name'           => isset( $_POST['name'] )           ? sanitize_text_field( wp_unslash( $_POST['name'] ) )           : '',
			'slug'           => isset( $_POST['slug'] )           ? sanitize_title( wp_unslash( $_POST['slug'] ) )                : '',
			'color_hex'      => isset( $_POST['color_hex'] )      ? sanitize_hex_color( wp_unslash( $_POST['color_hex'] ) )       : '',
			'pattern_type'   => $pattern_type,
			'swatch_url'     => isset( $_POST['swatch_url'] )     ? esc_url_raw( wp_unslash( $_POST['swatch_url'] ) )             : '',
			'price_modifier' => isset( $_POST['price_modifier'] ) ? floatval( $_POST['price_modifier'] )                          : 0.00,
			'is_active'      => isset( $_POST['is_active'] )      ? 1 : 0,
			'sort_order'     => isset( $_POST['sort_order'] )     ? absint( $_POST['sort_order'] )                                : 0,
		);

		if ( ! empty( $_POST['id'] ) ) {
			$data['id'] = absint( $_POST['id'] );
		}

		KCTM_Fabric_Catalog::save_fabric( $data );

		wp_safe_redirect( admin_url( 'admin.php?page=kctm-fabrics&updated=1' ) );
		exit;
	}

	/**
	 * Handle deleting a fabric.
	 *
	 * Validates the nonce from the URL, checks capability, and
	 * delegates to KCTM_Fabric_Catalog::delete_fabric().
	 *
	 * @return void
	 */
	public static function handle_delete_fabric() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $id || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'kctm_delete_fabric_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kevincho-tailoring-manager' ) );
		}

		KCTM_Fabric_Catalog::delete_fabric( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=kctm-fabrics&deleted=1' ) );
		exit;
	}
}
