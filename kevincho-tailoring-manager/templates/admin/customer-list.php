<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Customers', 'kevincho-tailoring-manager' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-walkin' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Walk-in Customer', 'kevincho-tailoring-manager' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'deleted' ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Customer deleted.', 'kevincho-tailoring-manager' ); ?></p></div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="kctm-customers">
        <?php
        // $table is set before including this template
        $table->search_box( __( 'Search Customers', 'kevincho-tailoring-manager' ), 'kctm-customer-search' );
        $table->display();
        ?>
    </form>
</div>
