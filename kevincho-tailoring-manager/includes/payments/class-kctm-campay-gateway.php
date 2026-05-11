<?php
/**
 * CamPay Payment Gateway for WooCommerce.
 *
 * Integrates with the CamPay API to accept MTN Mobile Money and
 * Orange Money payments in Cameroon (XAF).
 *
 * Flow:
 * 1. Customer enters phone number at checkout.
 * 2. Plugin requests a payment prompt via CamPay API.
 * 3. Customer receives a MoMo/OM prompt on their phone and confirms with PIN.
 * 4. Plugin polls CamPay for payment status (via AJAX from thank-you page).
 * 5. Once confirmed, the order moves from on-hold to processing.
 *
 * @package KevinCho_Tailoring_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KCTM_CamPay_Gateway
 */
class KCTM_CamPay_Gateway extends WC_Payment_Gateway {

    /** @var string CamPay app username */
    private $app_username;

    /** @var string CamPay app password */
    private $app_password;

    /** @var string Environment: test or live */
    private $environment;

    /** @var string Cached auth token */
    private $auth_token;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id                 = 'kctm_campay';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __( 'CamPay — Mobile Money (MTN / Orange)', 'kevincho-tailoring-manager' );
        $this->method_description = __( 'Accept MTN Mobile Money and Orange Money payments via CamPay. Customers receive a payment prompt on their phone.', 'kevincho-tailoring-manager' );

        $this->supports = array( 'products' );

        // Load settings.
        $this->init_form_fields();
        $this->init_settings();

        // Properties from settings.
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->app_username = $this->get_option( 'app_username' );
        $this->app_password = $this->get_option( 'app_password' );
        $this->environment  = $this->get_option( 'environment', 'test' );

        // Save admin settings.
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // Thank-you page: inject polling script.
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

        // AJAX: check payment status (logged-in and guest).
        add_action( 'wp_ajax_kctm_campay_check_status', array( $this, 'ajax_check_status' ) );
        add_action( 'wp_ajax_nopriv_kctm_campay_check_status', array( $this, 'ajax_check_status' ) );

        // Webhook endpoint.
        add_action( 'woocommerce_api_kctm_campay_webhook', array( $this, 'handle_webhook' ) );
    }

    /**
     * Admin settings fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled'      => array(
                'title'   => __( 'Enable/Disable', 'kevincho-tailoring-manager' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable CamPay Mobile Money payments', 'kevincho-tailoring-manager' ),
                'default' => 'yes',
            ),
            'title'        => array(
                'title'       => __( 'Title', 'kevincho-tailoring-manager' ),
                'type'        => 'text',
                'description' => __( 'Payment method title shown at checkout.', 'kevincho-tailoring-manager' ),
                'default'     => __( 'Mobile Money (MTN / Orange)', 'kevincho-tailoring-manager' ),
                'desc_tip'    => true,
            ),
            'description'  => array(
                'title'       => __( 'Description', 'kevincho-tailoring-manager' ),
                'type'        => 'textarea',
                'description' => __( 'Description shown at checkout.', 'kevincho-tailoring-manager' ),
                'default'     => __( 'Pay with MTN Mobile Money or Orange Money. You will receive a payment prompt on your phone — confirm with your PIN.', 'kevincho-tailoring-manager' ),
                'desc_tip'    => true,
            ),
            'environment'  => array(
                'title'       => __( 'Environment', 'kevincho-tailoring-manager' ),
                'type'        => 'select',
                'description' => __( 'Select test (demo) or live (production).', 'kevincho-tailoring-manager' ),
                'default'     => 'test',
                'options'     => array(
                    'test' => __( 'Test (demo.campay.net)', 'kevincho-tailoring-manager' ),
                    'live' => __( 'Live (campay.net)', 'kevincho-tailoring-manager' ),
                ),
                'desc_tip'    => true,
            ),
            'app_username' => array(
                'title'       => __( 'App Username', 'kevincho-tailoring-manager' ),
                'type'        => 'text',
                'description' => __( 'Your CamPay application username.', 'kevincho-tailoring-manager' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'app_password' => array(
                'title'       => __( 'App Password', 'kevincho-tailoring-manager' ),
                'type'        => 'password',
                'description' => __( 'Your CamPay application password.', 'kevincho-tailoring-manager' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Get the CamPay base URL based on environment.
     *
     * @return string
     */
    private function get_base_url() {
        return $this->environment === 'live'
            ? 'https://campay.net'
            : 'https://demo.campay.net';
    }

    /**
     * Authenticate with CamPay and get an access token.
     *
     * @return string|WP_Error Token string or WP_Error.
     */
    private function get_token() {
        if ( $this->auth_token ) {
            return $this->auth_token;
        }

        $url = $this->get_base_url() . '/api/token/';

        $response = wp_remote_post( $url, array(
            'timeout' => 30,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'username' => $this->app_username,
                'password' => $this->app_password,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['token'] ) ) {
            $msg = isset( $body['detail'] ) ? $body['detail'] : 'Authentication failed';
            return new WP_Error( 'campay_auth', $msg );
        }

        $this->auth_token = $body['token'];
        return $this->auth_token;
    }

    /**
     * Display payment fields on the checkout page (classic checkout).
     */
    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( wptexturize( wp_kses_post( $this->description ) ) );
        }
        ?>
        <div id="kctm-campay-fields" style="margin-top:10px;">
            <p class="form-row form-row-wide">
                <label for="kctm_campay_phone" style="font-weight:600;color:#402417;">
                    <?php esc_html_e( 'Mobile Money Phone Number', 'kevincho-tailoring-manager' ); ?>
                    <abbr class="required" title="required">*</abbr>
                </label>
                <input type="tel" class="input-text" name="kctm_campay_phone" id="kctm_campay_phone"
                       placeholder="<?php esc_attr_e( 'e.g. 6XXXXXXXX', 'kevincho-tailoring-manager' ); ?>"
                       maxlength="9" style="margin-top:4px;" autocomplete="tel" />
                <span style="font-size:12px;color:#888;margin-top:4px;display:block;">
                    <?php esc_html_e( 'Enter your 9-digit Cameroon phone number (without country code).', 'kevincho-tailoring-manager' ); ?>
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Validate the phone number field.
     *
     * @return bool
     */
    public function validate_fields() {
        $phone = isset( $_POST['kctm_campay_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_campay_phone'] ) ) : '';

        // Strip spaces and dashes.
        $phone = preg_replace( '/[\s\-]/', '', $phone );

        if ( empty( $phone ) ) {
            wc_add_notice( __( 'Please enter your Mobile Money phone number.', 'kevincho-tailoring-manager' ), 'error' );
            return false;
        }

        if ( ! preg_match( '/^[26]\d{8}$/', $phone ) ) {
            wc_add_notice( __( 'Please enter a valid 9-digit Cameroon phone number starting with 6 or 2.', 'kevincho-tailoring-manager' ), 'error' );
            return false;
        }

        return true;
    }

    /**
     * Process the payment.
     *
     * @param int $order_id WooCommerce order ID.
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wc_add_notice( __( 'Order not found. Please try again.', 'kevincho-tailoring-manager' ), 'error' );
            return array( 'result' => 'failure' );
        }

        // Get and sanitize phone number.
        $phone = isset( $_POST['kctm_campay_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_campay_phone'] ) ) : '';
        $phone = preg_replace( '/[\s\-]/', '', $phone );

        // Prepend Cameroon country code.
        $phone_full = '237' . $phone;

        // 1. Get auth token.
        $token = $this->get_token();
        if ( is_wp_error( $token ) ) {
            wc_add_notice(
                __( 'Payment service unavailable. Please try again later.', 'kevincho-tailoring-manager' ),
                'error'
            );
            $order->add_order_note( 'CamPay auth failed: ' . $token->get_error_message() );
            return array( 'result' => 'failure' );
        }

        // 2. Create payment request.
        $amount = $order->get_total();
        $external_ref = 'order_' . $order_id . '_' . time();

        $collect_url = $this->get_base_url() . '/api/collect/';
        $webhook_url = home_url( '/wc-api/kctm_campay_webhook/' );

        $payload = array(
            'amount'             => strval( intval( $amount ) ),
            'currency'           => 'XAF',
            'from'               => $phone_full,
            'description'        => sprintf( 'Order #%d — Kevin Cho Tailoring', $order_id ),
            'external_reference' => $external_ref,
        );

        $response = wp_remote_post( $collect_url, array(
            'timeout' => 45,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Token ' . $token,
            ),
            'body' => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) {
            wc_add_notice(
                __( 'Could not initiate payment. Please try again.', 'kevincho-tailoring-manager' ),
                'error'
            );
            $order->add_order_note( 'CamPay collect request failed: ' . $response->get_error_message() );
            return array( 'result' => 'failure' );
        }

        $resp_code = wp_remote_retrieve_response_code( $response );
        $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $resp_code < 200 || $resp_code >= 300 ) {
            $err_msg = isset( $resp_body['detail'] ) ? $resp_body['detail'] : ( isset( $resp_body['message'] ) ? $resp_body['message'] : 'Unknown error' );
            wc_add_notice(
                __( 'Payment request failed. Please check your phone number and try again.', 'kevincho-tailoring-manager' ),
                'error'
            );
            $order->add_order_note( 'CamPay collect error (' . $resp_code . '): ' . $err_msg );
            return array( 'result' => 'failure' );
        }

        // CamPay returns a reference for tracking the payment.
        $campay_ref = '';
        if ( isset( $resp_body['reference'] ) ) {
            $campay_ref = sanitize_text_field( $resp_body['reference'] );
        } elseif ( isset( $resp_body['ref'] ) ) {
            $campay_ref = sanitize_text_field( $resp_body['ref'] );
        }

        // Save meta.
        $order->update_meta_data( '_kctm_campay_ref', $campay_ref );
        $order->update_meta_data( '_kctm_campay_ext_ref', $external_ref );
        $order->update_meta_data( '_kctm_campay_phone', $phone_full );
        $order->save();

        // Set order to on-hold.
        $order->update_status(
            'on-hold',
            sprintf(
                /* translators: %s: CamPay reference */
                __( 'Awaiting CamPay Mobile Money payment. Ref: %s', 'kevincho-tailoring-manager' ),
                $campay_ref ? $campay_ref : $external_ref
            )
        );

        // Reduce stock.
        wc_reduce_stock_levels( $order_id );

        // Empty cart.
        WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    /**
     * Thank-you page: show payment status and poll for confirmation.
     *
     * @param int $order_id Order ID.
     */
    public function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $campay_ref = $order->get_meta( '_kctm_campay_ref' );
        $ext_ref    = $order->get_meta( '_kctm_campay_ext_ref' );
        $phone      = $order->get_meta( '_kctm_campay_phone' );
        ?>
        <div id="kctm-campay-status-box" style="background:#fef9e7;border:2px solid #c9a96e;border-radius:10px;padding:20px;margin:20px 0;font-size:14px;line-height:1.7;">
            <h3 style="color:#402417;margin:0 0 12px;font-size:16px;">
                <?php esc_html_e( 'Mobile Money Payment', 'kevincho-tailoring-manager' ); ?>
            </h3>
            <p id="kctm-campay-status-msg" style="color:#402417;font-weight:600;">
                <?php esc_html_e( 'A payment prompt has been sent to your phone. Please confirm with your PIN.', 'kevincho-tailoring-manager' ); ?>
            </p>
            <div id="kctm-campay-spinner" style="text-align:center;padding:15px 0;">
                <span style="display:inline-block;width:32px;height:32px;border:3px solid #e8d5a3;border-top-color:#c9a96e;border-radius:50%;animation:kctm-spin 1s linear infinite;"></span>
                <style>@keyframes kctm-spin{to{transform:rotate(360deg)}}</style>
            </div>
            <table style="width:100%;border-collapse:collapse;display:none;" id="kctm-campay-details">
                <?php if ( $phone ) : ?>
                <tr>
                    <td style="padding:4px 0;color:#666;width:45%;"><?php esc_html_e( 'Phone:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:600;color:#402417;"><?php echo esc_html( $phone ); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding:4px 0;color:#666;"><?php esc_html_e( 'Amount:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:700;color:#402417;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#666;"><?php esc_html_e( 'Status:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:700;" id="kctm-campay-status-val"><?php esc_html_e( 'Pending...', 'kevincho-tailoring-manager' ); ?></td>
                </tr>
            </table>
        </div>
        <script>
        (function(){
            var orderId = <?php echo intval( $order_id ); ?>;
            var nonce = '<?php echo esc_js( wp_create_nonce( 'kctm_campay_check_' . $order_id ) ); ?>';
            var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var maxAttempts = 40;
            var interval = 5000; // 5 seconds
            var attempts = 0;

            var msgEl = document.getElementById('kctm-campay-status-msg');
            var spinnerEl = document.getElementById('kctm-campay-spinner');
            var detailsEl = document.getElementById('kctm-campay-details');
            var statusVal = document.getElementById('kctm-campay-status-val');

            function checkStatus() {
                attempts++;
                if (attempts > maxAttempts) {
                    msgEl.textContent = 'Payment confirmation is taking longer than expected. Your order has been saved — we will update you once payment is confirmed.';
                    spinnerEl.style.display = 'none';
                    detailsEl.style.display = 'table';
                    statusVal.textContent = 'Pending';
                    statusVal.style.color = '#e67e22';
                    return;
                }

                var formData = new FormData();
                formData.append('action', 'kctm_campay_check_status');
                formData.append('order_id', orderId);
                formData.append('nonce', nonce);

                fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(function(r){ return r.json(); })
                    .then(function(data) {
                        if (data.success && data.data) {
                            var st = data.data.status;
                            if (st === 'SUCCESSFUL' || st === 'completed') {
                                msgEl.textContent = 'Payment confirmed! Thank you for your order.';
                                msgEl.style.color = '#27ae60';
                                spinnerEl.style.display = 'none';
                                detailsEl.style.display = 'table';
                                statusVal.textContent = 'Confirmed';
                                statusVal.style.color = '#27ae60';
                                return; // Stop polling.
                            } else if (st === 'FAILED') {
                                msgEl.textContent = 'Payment was not completed. Please contact us if you believe this is an error.';
                                msgEl.style.color = '#e74c3c';
                                spinnerEl.style.display = 'none';
                                detailsEl.style.display = 'table';
                                statusVal.textContent = 'Failed';
                                statusVal.style.color = '#e74c3c';
                                return; // Stop polling.
                            }
                        }
                        // Still pending — keep polling.
                        setTimeout(checkStatus, interval);
                    })
                    .catch(function(){
                        setTimeout(checkStatus, interval);
                    });
            }

            // Start polling after a short delay.
            setTimeout(checkStatus, 3000);
        })();
        </script>
        <?php
    }

    /**
     * AJAX handler: check payment status with CamPay.
     */
    public function ajax_check_status() {
        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $nonce    = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'kctm_campay_check_' . $order_id ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( 'Order not found' );
        }

        // If order is already processing/completed, return success without API call.
        if ( $order->is_paid() ) {
            wp_send_json_success( array( 'status' => 'SUCCESSFUL' ) );
        }

        // If order was cancelled/failed.
        if ( $order->has_status( array( 'cancelled', 'failed' ) ) ) {
            wp_send_json_success( array( 'status' => 'FAILED' ) );
        }

        $campay_ref = $order->get_meta( '_kctm_campay_ref' );
        $ext_ref    = $order->get_meta( '_kctm_campay_ext_ref' );

        $ref = $campay_ref ? $campay_ref : $ext_ref;
        if ( empty( $ref ) ) {
            wp_send_json_error( 'No reference found' );
        }

        // Get token and check status.
        $token = $this->get_token();
        if ( is_wp_error( $token ) ) {
            wp_send_json_error( 'Auth failed' );
        }

        $status_url = $this->get_base_url() . '/api/transaction/' . urlencode( $ref ) . '/';

        $response = wp_remote_get( $status_url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Token ' . $token,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'API error' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $status = isset( $body['status'] ) ? strtoupper( sanitize_text_field( $body['status'] ) ) : 'PENDING';

        // Update order based on status.
        if ( $status === 'SUCCESSFUL' ) {
            if ( $order->has_status( 'on-hold' ) ) {
                $order->payment_complete( $ref );
                $order->add_order_note(
                    sprintf(
                        /* translators: %s: CamPay reference */
                        __( 'CamPay payment confirmed. Ref: %s', 'kevincho-tailoring-manager' ),
                        $ref
                    )
                );
            }
        } elseif ( $status === 'FAILED' ) {
            if ( $order->has_status( 'on-hold' ) ) {
                $order->update_status(
                    'failed',
                    __( 'CamPay payment failed or was rejected by the customer.', 'kevincho-tailoring-manager' )
                );
            }
        }

        wp_send_json_success( array( 'status' => $status ) );
    }

    /**
     * Handle CamPay webhook notification.
     *
     * CamPay can POST a notification when payment status changes.
     * URL: /wc-api/kctm_campay_webhook/
     */
    public function handle_webhook() {
        $raw = file_get_contents( 'php://input' );
        $data = json_decode( $raw, true );

        if ( empty( $data ) ) {
            status_header( 400 );
            exit( 'Invalid payload' );
        }

        $status     = isset( $data['status'] ) ? strtoupper( sanitize_text_field( $data['status'] ) ) : '';
        $ext_ref    = isset( $data['external_reference'] ) ? sanitize_text_field( $data['external_reference'] ) : '';
        $campay_ref = isset( $data['reference'] ) ? sanitize_text_field( $data['reference'] ) : '';

        // Extract order ID from external reference (format: order_123_timestamp).
        $order_id = 0;
        if ( preg_match( '/^order_(\d+)_/', $ext_ref, $m ) ) {
            $order_id = intval( $m[1] );
        }

        if ( ! $order_id ) {
            // Try to find by meta.
            $orders = wc_get_orders( array(
                'meta_key'   => '_kctm_campay_ref',
                'meta_value' => $campay_ref,
                'limit'      => 1,
            ) );
            if ( ! empty( $orders ) ) {
                $order_id = $orders[0]->get_id();
            }
        }

        if ( ! $order_id ) {
            status_header( 404 );
            exit( 'Order not found' );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            status_header( 404 );
            exit( 'Order not found' );
        }

        if ( $status === 'SUCCESSFUL' ) {
            if ( $order->has_status( 'on-hold' ) ) {
                $ref = $campay_ref ? $campay_ref : $ext_ref;
                $order->payment_complete( $ref );
                $order->add_order_note(
                    sprintf(
                        /* translators: %s: reference */
                        __( 'CamPay webhook: payment confirmed. Ref: %s', 'kevincho-tailoring-manager' ),
                        $ref
                    )
                );
            }
        } elseif ( $status === 'FAILED' ) {
            if ( $order->has_status( 'on-hold' ) ) {
                $order->update_status(
                    'failed',
                    __( 'CamPay webhook: payment failed.', 'kevincho-tailoring-manager' )
                );
            }
        }

        status_header( 200 );
        exit( 'OK' );
    }
}
