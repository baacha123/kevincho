<?php
/**
 * Admin Consultations List — WP_List_Table wrapper template.
 *
 * The actual table content is rendered by the KCTM_Admin_Consultations
 * class via its render() method. This template provides the page wrapper.
 *
 * Expected variable:
 *   $table — Instance of the consultations WP_List_Table.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Consultations', 'kevincho-tailoring-manager' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['message'] ) ) : ?>
		<?php if ( 'updated' === $_GET['message'] ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Consultation updated successfully.', 'kevincho-tailoring-manager' ); ?></p>
			</div>
		<?php elseif ( 'deleted' === $_GET['message'] ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Consultation deleted.', 'kevincho-tailoring-manager' ); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<form method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'kctm-consultations' ); ?>">
		<?php
		if ( isset( $table ) ) {
			$table->search_box( __( 'Search Consultations', 'kevincho-tailoring-manager' ), 'kctm-consultation-search' );
			$table->display();
		}
		?>
	</form>
</div>
