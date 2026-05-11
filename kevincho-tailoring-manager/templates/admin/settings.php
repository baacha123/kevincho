<?php if ( ! defined( 'ABSPATH' ) ) exit;
$wa_settings = get_option( 'kctm_whatsapp_settings', array() );
$notification_statuses = get_option( 'kctm_notification_statuses', array( 'kctm-ready-pickup' ) );
$all_statuses = KCTM_Order_Statuses::get_custom_statuses();
?>
<div class="wrap kctm-settings">
    <h1><?php esc_html_e( 'Tailoring Manager Settings', 'kevincho-tailoring-manager' ); ?></h1>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'kevincho-tailoring-manager' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="kctm_save_settings">
        <?php wp_nonce_field( 'kctm_save_settings', 'kctm_settings_nonce' ); ?>

        <!-- WhatsApp API Settings -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'WhatsApp Business API', 'kevincho-tailoring-manager' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure your Meta WhatsApp Business Cloud API credentials.', 'kevincho-tailoring-manager' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="wa_access_token"><?php esc_html_e( 'Access Token', 'kevincho-tailoring-manager' ); ?></label></th>
                    <td>
                        <input type="password" name="whatsapp[access_token]" id="wa_access_token" class="large-text" value="<?php echo esc_attr( isset( $wa_settings['access_token'] ) ? $wa_settings['access_token'] : '' ); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_phone_id"><?php esc_html_e( 'Phone Number ID', 'kevincho-tailoring-manager' ); ?></label></th>
                    <td>
                        <input type="text" name="whatsapp[phone_number_id]" id="wa_phone_id" class="regular-text" value="<?php echo esc_attr( isset( $wa_settings['phone_number_id'] ) ? $wa_settings['phone_number_id'] : '' ); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_biz_id"><?php esc_html_e( 'Business Account ID', 'kevincho-tailoring-manager' ); ?></label></th>
                    <td>
                        <input type="text" name="whatsapp[business_account_id]" id="wa_biz_id" class="regular-text" value="<?php echo esc_attr( isset( $wa_settings['business_account_id'] ) ? $wa_settings['business_account_id'] : '' ); ?>">
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                        <button type="button" class="button" id="kctm-test-whatsapp"><?php esc_html_e( 'Test Connection', 'kevincho-tailoring-manager' ); ?></button>
                        <span id="kctm-test-result"></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Notification Triggers -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'Notification Triggers', 'kevincho-tailoring-manager' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Select which order status changes should trigger a WhatsApp notification to the customer.', 'kevincho-tailoring-manager' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Statuses', 'kevincho-tailoring-manager' ); ?></th>
                    <td>
                        <fieldset>
                            <?php foreach ( $all_statuses as $slug => $label ) :
                                $slug_clean = str_replace( 'wc-', '', $slug );
                            ?>
                            <label>
                                <input type="checkbox" name="notification_statuses[]" value="<?php echo esc_attr( $slug_clean ); ?>" <?php checked( in_array( $slug_clean, $notification_statuses ) ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </label><br>
                            <?php endforeach; ?>
                            <label>
                                <input type="checkbox" name="notification_statuses[]" value="completed" <?php checked( in_array( 'completed', $notification_statuses ) ); ?>>
                                <?php esc_html_e( 'Completed', 'kevincho-tailoring-manager' ); ?>
                            </label><br>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Payment Gateways Guide -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'Payment Gateways', 'kevincho-tailoring-manager' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Payment gateways are managed via separate WooCommerce plugins. Recommended setup:', 'kevincho-tailoring-manager' ); ?></p>
            <table class="widefat striped" style="max-width:700px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Method', 'kevincho-tailoring-manager' ); ?></th>
                        <th><?php esc_html_e( 'Plugin', 'kevincho-tailoring-manager' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'kevincho-tailoring-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e( 'Credit Cards / Apple Pay', 'kevincho-tailoring-manager' ); ?></td>
                        <td>WooCommerce Stripe Gateway</td>
                        <td><?php echo class_exists( 'WC_Stripe' ) || defined( 'WC_STRIPE_VERSION' ) ? '<span style="color:green;">&#10003; Active</span>' : '<span style="color:#999;">Not installed</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'PayPal', 'kevincho-tailoring-manager' ); ?></td>
                        <td>WooCommerce PayPal Payments</td>
                        <td><?php echo defined( 'PPCP_FLAG_SUBSCRIPTION' ) || class_exists( 'WC_PayPal_Payments' ) ? '<span style="color:green;">&#10003; Active</span>' : '<span style="color:#999;">Not installed</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'MTN MoMo / Orange Money', 'kevincho-tailoring-manager' ); ?></td>
                        <td>Flutterwave for WooCommerce</td>
                        <td><?php echo defined( 'FLW_WC_PLUGIN_FILE' ) || class_exists( 'FLW_WC_Payment_Gateway' ) ? '<span style="color:green;">&#10003; Active</span>' : '<span style="color:#999;">Not installed</span>'; ?></td>
                    </tr>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e( 'Install and configure these plugins from Plugins → Add New.', 'kevincho-tailoring-manager' ); ?></p>
        </div>

        <?php submit_button( __( 'Save Settings', 'kevincho-tailoring-manager' ) ); ?>
    </form>
</div>
