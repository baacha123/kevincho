<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap kctm-create-order">
    <h1><?php esc_html_e( 'Create In-Store Order', 'kevincho-tailoring-manager' ); ?></h1>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'created' ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Order created successfully!', 'kevincho-tailoring-manager' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="kctm-create-order-form">
        <input type="hidden" name="action" value="kctm_create_instore_order">
        <?php wp_nonce_field( 'kctm_create_order_nonce', 'kctm_order_nonce' ); ?>

        <!-- Customer Selection -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'Customer', 'kevincho-tailoring-manager' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="kctm-customer-select"><?php esc_html_e( 'Select Customer', 'kevincho-tailoring-manager' ); ?></label></th>
                    <td>
                        <select name="customer_id" id="kctm-customer-select" class="kctm-select2-customer" style="width:400px;" required>
                            <option value=""><?php esc_html_e( 'Search for a customer...', 'kevincho-tailoring-manager' ); ?></option>
                        </select>
                        <p class="description">
                            <?php printf( __( 'Customer not found? <a href="%s">Add a walk-in customer</a> first.', 'kevincho-tailoring-manager' ), esc_url( admin_url( 'admin.php?page=kctm-walkin' ) ) ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <div id="kctm-customer-info" style="display:none;">
                <p><strong><?php esc_html_e( 'Customer Details:', 'kevincho-tailoring-manager' ); ?></strong></p>
                <p id="kctm-customer-details"></p>
            </div>
        </div>

        <!-- WooCommerce Products -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'Products', 'kevincho-tailoring-manager' ); ?></h2>
            <div id="kctm-product-rows">
                <div class="kctm-product-row">
                    <select name="items[0][product_id]" class="kctm-select2-product" style="width:300px;">
                        <option value=""><?php esc_html_e( 'Search for a product...', 'kevincho-tailoring-manager' ); ?></option>
                    </select>
                    <input type="number" name="items[0][quantity]" value="1" min="1" style="width:70px;" placeholder="<?php esc_attr_e( 'Qty', 'kevincho-tailoring-manager' ); ?>">
                    <input type="number" name="items[0][price]" step="0.01" min="0" style="width:120px;" placeholder="<?php esc_attr_e( 'Price (override)', 'kevincho-tailoring-manager' ); ?>">
                    <button type="button" class="button kctm-remove-row">&times;</button>
                </div>
            </div>
            <button type="button" class="button" id="kctm-add-product-row">+ <?php esc_html_e( 'Add Product', 'kevincho-tailoring-manager' ); ?></button>
        </div>

        <!-- Custom Line Items -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'Custom Items', 'kevincho-tailoring-manager' ); ?></h2>
            <p class="description"><?php esc_html_e( 'For items not in the product catalog (e.g., alterations, custom fabrics).', 'kevincho-tailoring-manager' ); ?></p>
            <div id="kctm-custom-rows">
                <div class="kctm-custom-row">
                    <input type="text" name="custom_items[0][name]" style="width:300px;" placeholder="<?php esc_attr_e( 'Item description', 'kevincho-tailoring-manager' ); ?>">
                    <input type="number" name="custom_items[0][quantity]" value="1" min="1" style="width:70px;" placeholder="<?php esc_attr_e( 'Qty', 'kevincho-tailoring-manager' ); ?>">
                    <input type="number" name="custom_items[0][price]" step="0.01" min="0" style="width:120px;" placeholder="<?php esc_attr_e( 'Price', 'kevincho-tailoring-manager' ); ?>" required>
                    <button type="button" class="button kctm-remove-row">&times;</button>
                </div>
            </div>
            <button type="button" class="button" id="kctm-add-custom-row">+ <?php esc_html_e( 'Add Custom Item', 'kevincho-tailoring-manager' ); ?></button>
        </div>

        <!-- Order Note -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'Order Note', 'kevincho-tailoring-manager' ); ?></h2>
            <textarea name="order_note" rows="3" style="width:100%;max-width:600px;" placeholder="<?php esc_attr_e( 'Optional note about this order...', 'kevincho-tailoring-manager' ); ?>"></textarea>
        </div>

        <?php submit_button( __( 'Create Order', 'kevincho-tailoring-manager' ), 'primary', 'submit', true ); ?>
    </form>
</div>
