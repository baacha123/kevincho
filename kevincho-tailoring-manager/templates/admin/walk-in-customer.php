<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap kctm-walkin">
    <h1><?php esc_html_e( 'Add Walk-in Customer', 'kevincho-tailoring-manager' ); ?></h1>

    <?php if ( isset( $_GET['message'] ) ) : ?>
        <?php if ( $_GET['message'] === 'created' ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php esc_html_e( 'Customer created successfully!', 'kevincho-tailoring-manager' ); ?>
                    <?php if ( isset( $_GET['customer_id'] ) ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-customer-measurements&customer_id=' . absint( $_GET['customer_id'] ) ) ); ?>">
                            <?php esc_html_e( 'Edit their measurements', 'kevincho-tailoring-manager' ); ?>
                        </a> |
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-create-order' ) ); ?>">
                            <?php esc_html_e( 'Create an order', 'kevincho-tailoring-manager' ); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </div>
        <?php elseif ( $_GET['message'] === 'error' ) : ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html( isset( $_GET['error'] ) ? $_GET['error'] : __( 'An error occurred.', 'kevincho-tailoring-manager' ) ); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="kctm_create_walkin">
        <?php wp_nonce_field( 'kctm_walkin_nonce', 'kctm_walkin_nonce_field' ); ?>

        <div class="kctm-section">
            <h2><?php esc_html_e( 'Customer Details', 'kevincho-tailoring-manager' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="first_name"><?php esc_html_e( 'First Name', 'kevincho-tailoring-manager' ); ?> <span class="required">*</span></label></th>
                    <td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="last_name"><?php esc_html_e( 'Last Name', 'kevincho-tailoring-manager' ); ?> <span class="required">*</span></label></th>
                    <td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="phone"><?php esc_html_e( 'Phone Number', 'kevincho-tailoring-manager' ); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="tel" name="phone" id="phone" class="regular-text" required placeholder="+237 6XX XXX XXX">
                        <p class="description"><?php esc_html_e( 'Used for WhatsApp notifications.', 'kevincho-tailoring-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="email"><?php esc_html_e( 'Email', 'kevincho-tailoring-manager' ); ?></label></th>
                    <td>
                        <input type="email" name="email" id="email" class="regular-text" placeholder="<?php esc_attr_e( 'Optional', 'kevincho-tailoring-manager' ); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="gender"><?php esc_html_e( 'Gender', 'kevincho-tailoring-manager' ); ?></label></th>
                    <td>
                        <select name="gender" id="gender">
                            <option value=""><?php esc_html_e( 'Select...', 'kevincho-tailoring-manager' ); ?></option>
                            <option value="male"><?php esc_html_e( 'Male', 'kevincho-tailoring-manager' ); ?></option>
                            <option value="female"><?php esc_html_e( 'Female', 'kevincho-tailoring-manager' ); ?></option>
                            <option value="child"><?php esc_html_e( 'Child', 'kevincho-tailoring-manager' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Create Customer', 'kevincho-tailoring-manager' ), 'primary' ); ?>
    </form>
</div>
