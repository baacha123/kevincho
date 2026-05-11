<?php
/**
 * Manual MoMo Payment Gateway for WooCommerce.
 *
 * Customer sends MoMo payment to the store's number, enters their
 * financial transaction ID at checkout, optionally uploads a screenshot,
 * and the order goes on-hold until the store owner verifies payment.
 *
 * @package KevinCho_Tailoring_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KCTM_MoMo_Manual_Gateway
 */
class KCTM_MoMo_Manual_Gateway extends WC_Payment_Gateway {

    /**
     * MoMo phone number to receive payments.
     *
     * @var string
     */
    private $momo_number;

    /**
     * MoMo account holder name.
     *
     * @var string
     */
    private $momo_name;

    /**
     * Instructions shown on thank-you page and in emails.
     *
     * @var string
     */
    private $instructions;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id                 = 'kctm_momo_manual';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __( 'Mobile Money (MoMo) — Manual', 'kevincho-tailoring-manager' );
        $this->method_description = __( 'Accept MTN Mobile Money or Orange Money payments. Customers send money to your number and enter the financial transaction ID. You verify and confirm.', 'kevincho-tailoring-manager' );

        // Load settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define properties from settings.
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->momo_number  = $this->get_option( 'momo_number' );
        $this->momo_name    = $this->get_option( 'momo_name' );
        $this->instructions = $this->get_option( 'instructions' );

        // Save admin settings.
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // Thank-you page instructions.
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

        // Email instructions.
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }

    /**
     * Gateway settings fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled'      => array(
                'title'   => __( 'Enable/Disable', 'kevincho-tailoring-manager' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Mobile Money (MoMo) manual payments', 'kevincho-tailoring-manager' ),
                'default' => 'yes',
            ),
            'title'        => array(
                'title'       => __( 'Title', 'kevincho-tailoring-manager' ),
                'type'        => 'text',
                'description' => __( 'Payment method title shown to the customer at checkout.', 'kevincho-tailoring-manager' ),
                'default'     => __( 'Mobile Money (MoMo)', 'kevincho-tailoring-manager' ),
                'desc_tip'    => true,
            ),
            'description'  => array(
                'title'       => __( 'Description', 'kevincho-tailoring-manager' ),
                'type'        => 'textarea',
                'description' => __( 'Description shown at checkout below the title.', 'kevincho-tailoring-manager' ),
                'default'     => __( 'Pay via MTN Mobile Money or Orange Money. Send payment to the number shown below, then enter your financial transaction ID.', 'kevincho-tailoring-manager' ),
                'desc_tip'    => true,
            ),
            'momo_number'  => array(
                'title'       => __( 'MoMo Phone Number', 'kevincho-tailoring-manager' ),
                'type'        => 'text',
                'description' => __( 'The phone number customers should send money to.', 'kevincho-tailoring-manager' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => __( 'e.g. 6XX XXX XXX', 'kevincho-tailoring-manager' ),
            ),
            'momo_name'    => array(
                'title'       => __( 'Account Holder Name', 'kevincho-tailoring-manager' ),
                'type'        => 'text',
                'description' => __( 'The name registered on the MoMo account.', 'kevincho-tailoring-manager' ),
                'default'     => __( 'Kevin Cho Tailoring', 'kevincho-tailoring-manager' ),
                'desc_tip'    => true,
            ),
            'instructions' => array(
                'title'       => __( 'Instructions', 'kevincho-tailoring-manager' ),
                'type'        => 'textarea',
                'description' => __( 'Shown on the thank-you page and in order emails.', 'kevincho-tailoring-manager' ),
                'default'     => __( 'Thank you for your order. Please send the exact amount via MoMo to the number above. Your order will be confirmed once we verify the payment.', 'kevincho-tailoring-manager' ),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Display payment fields on the checkout page.
     */
    public function payment_fields() {
        // Show description.
        if ( $this->description ) {
            echo wpautop( wptexturize( wp_kses_post( $this->description ) ) );
        }

        // Get order total.
        $total    = WC()->cart ? WC()->cart->get_total( 'edit' ) : 0;
        $currency = get_woocommerce_currency_symbol();

        ?>
        <div id="kctm-momo-payment-box" style="background:#fef9e7;border:2px solid #c9a96e;border-radius:10px;padding:18px 20px;margin:12px 0 16px;font-size:14px;line-height:1.6;">
            <div style="font-weight:700;font-size:15px;color:#402417;margin-bottom:10px;border-bottom:1px solid #e8d5a3;padding-bottom:8px;">
                <?php esc_html_e( 'Payment Details / Informations de paiement', 'kevincho-tailoring-manager' ); ?>
            </div>
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="padding:4px 0;color:#666;width:45%;"><?php esc_html_e( 'Send to Number:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:700;color:#402417;font-size:16px;"><?php echo esc_html( $this->momo_number ); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#666;"><?php esc_html_e( 'Account Name:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:600;color:#402417;"><?php echo esc_html( $this->momo_name ); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#666;"><?php esc_html_e( 'Amount to Pay:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:700;color:#402417;font-size:16px;">
                        <?php echo wp_kses_post( wc_price( $total ) ); ?>
                    </td>
                </tr>
            </table>

            <!-- Bilingual instructions -->
            <div style="margin-top:12px;padding:10px;background:#fff;border-radius:6px;border:1px solid #e8d5a3;">
                <p style="margin:0 0 8px;color:#402417;font-size:13px;line-height:1.5;">
                    <strong>FR:</strong> Envoyez le paiement de <?php echo wp_kses_post( wc_price( $total ) ); ?> au num&eacute;ro <strong><?php echo esc_html( $this->momo_number ); ?></strong> (<?php echo esc_html( $this->momo_name ); ?>). Apr&egrave;s l&rsquo;envoi, copiez l&rsquo;ID de transaction financi&egrave;re de votre message de confirmation MoMo et collez-le ci-dessous.
                </p>
                <p style="margin:0;color:#402417;font-size:13px;line-height:1.5;">
                    <strong>EN:</strong> Send <?php echo wp_kses_post( wc_price( $total ) ); ?> to <strong><?php echo esc_html( $this->momo_number ); ?></strong> (<?php echo esc_html( $this->momo_name ); ?>). After sending, copy the Financial Transaction ID from your MoMo confirmation message and paste it below.
                </p>
            </div>
        </div>

        <!-- Financial Transaction ID field (required) -->
        <p class="form-row form-row-wide" id="kctm_momo_ref_field">
            <label for="kctm_momo_ref" style="font-weight:600;color:#402417;">
                <?php esc_html_e( 'Financial Transaction ID / ID de transaction', 'kevincho-tailoring-manager' ); ?>
                <abbr class="required" title="required">*</abbr>
            </label>
            <input type="text" class="input-text" name="kctm_momo_ref" id="kctm_momo_ref"
                   placeholder="<?php esc_attr_e( 'e.g. 1234567890 / ex. 1234567890', 'kevincho-tailoring-manager' ); ?>"
                   style="margin-top:4px;" required />
            <span style="font-size:12px;color:#888;margin-top:4px;display:block;">
                <?php esc_html_e( 'You will find this in your MoMo confirmation SMS. / Vous le trouverez dans votre SMS de confirmation MoMo.', 'kevincho-tailoring-manager' ); ?>
            </span>
        </p>

        <!-- Screenshot upload field (optional) -->
        <p class="form-row form-row-wide" id="kctm_momo_screenshot_field">
            <label for="kctm_momo_screenshot" style="font-weight:600;color:#402417;">
                <?php esc_html_e( 'Payment Screenshot (optional) / Capture du paiement (facultatif)', 'kevincho-tailoring-manager' ); ?>
            </label>
            <input type="file" name="kctm_momo_screenshot" id="kctm_momo_screenshot"
                   accept="image/*"
                   style="margin-top:4px;padding:8px;border:1px solid #ccc;border-radius:4px;width:100%;box-sizing:border-box;" />
            <span style="font-size:12px;color:#888;margin-top:4px;display:block;">
                <?php esc_html_e( 'Upload a screenshot of your MoMo confirmation message. / Téléchargez une capture de votre message de confirmation MoMo.', 'kevincho-tailoring-manager' ); ?>
            </span>
        </p>
        <?php
    }

    /**
     * Validate payment fields.
     *
     * @return bool
     */
    public function validate_fields() {
        $ref = isset( $_POST['kctm_momo_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_momo_ref'] ) ) : '';

        if ( empty( $ref ) ) {
            wc_add_notice( __( 'Please enter your MoMo Financial Transaction ID. / Veuillez entrer votre ID de transaction MoMo.', 'kevincho-tailoring-manager' ), 'error' );
            return false;
        }

        if ( strlen( $ref ) < 3 ) {
            wc_add_notice( __( 'The Financial Transaction ID seems too short. Please check and try again. / L\'ID de transaction semble trop court.', 'kevincho-tailoring-manager' ), 'error' );
            return false;
        }

        // Validate screenshot file if uploaded.
        if ( ! empty( $_FILES['kctm_momo_screenshot']['name'] ) ) {
            $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
            $file_type     = $_FILES['kctm_momo_screenshot']['type'];

            if ( ! in_array( $file_type, $allowed_types, true ) ) {
                wc_add_notice( __( 'Screenshot must be an image file (JPEG, PNG, GIF, or WebP). / La capture doit être une image (JPEG, PNG, GIF ou WebP).', 'kevincho-tailoring-manager' ), 'error' );
                return false;
            }

            // Max 5MB.
            if ( $_FILES['kctm_momo_screenshot']['size'] > 5 * 1024 * 1024 ) {
                wc_add_notice( __( 'Screenshot file is too large. Maximum 5MB. / Le fichier est trop volumineux. Maximum 5 Mo.', 'kevincho-tailoring-manager' ), 'error' );
                return false;
            }
        }

        return true;
    }

    /**
     * Process payment.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wc_add_notice( __( 'Order not found. Please try again.', 'kevincho-tailoring-manager' ), 'error' );
            return array( 'result' => 'failure' );
        }

        $ref = isset( $_POST['kctm_momo_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['kctm_momo_ref'] ) ) : '';

        // Save the financial transaction ID as order meta.
        $order->update_meta_data( '_kctm_momo_ref', $ref );

        // Handle screenshot upload.
        $screenshot_id = 0;
        if ( ! empty( $_FILES['kctm_momo_screenshot']['name'] ) && empty( $_FILES['kctm_momo_screenshot']['error'] ) ) {
            // WordPress media upload handler.
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            $upload_overrides = array(
                'test_form' => false,
                'mimes'     => array(
                    'jpg|jpeg' => 'image/jpeg',
                    'png'      => 'image/png',
                    'gif'      => 'image/gif',
                    'webp'     => 'image/webp',
                ),
            );

            $uploaded_file = wp_handle_upload( $_FILES['kctm_momo_screenshot'], $upload_overrides );

            if ( ! empty( $uploaded_file['file'] ) && empty( $uploaded_file['error'] ) ) {
                $file_path = $uploaded_file['file'];
                $file_url  = $uploaded_file['url'];
                $file_type = $uploaded_file['type'];

                $attachment = array(
                    'post_mime_type' => $file_type,
                    'post_title'     => sprintf( 'MoMo Screenshot - Order #%d', $order_id ),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                );

                $attach_id = wp_insert_attachment( $attachment, $file_path, $order_id );

                if ( ! is_wp_error( $attach_id ) ) {
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    $screenshot_id = $attach_id;
                }
            }
        }

        if ( $screenshot_id ) {
            $order->update_meta_data( '_kctm_momo_screenshot_id', $screenshot_id );
        }

        $order->save();

        // Build the order note.
        $note = sprintf(
            /* translators: %s: MoMo financial transaction ID */
            __( 'MoMo payment submitted. Transaction ID: %s. Awaiting confirmation.', 'kevincho-tailoring-manager' ),
            $ref
        );

        if ( $screenshot_id ) {
            $note .= ' ' . __( 'Payment screenshot uploaded.', 'kevincho-tailoring-manager' );
        }

        // Set order status to on-hold.
        $order->update_status( 'on-hold', $note );

        // Reduce stock levels.
        wc_reduce_stock_levels( $order_id );

        // Remove cart.
        WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    /**
     * Output instructions on the thank-you page.
     *
     * @param int $order_id Order ID.
     */
    public function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $ref = $order->get_meta( '_kctm_momo_ref' );
        ?>
        <div style="background:#fef9e7;border:2px solid #c9a96e;border-radius:10px;padding:20px;margin:20px 0;font-size:14px;line-height:1.7;">
            <h3 style="color:#402417;margin:0 0 12px;font-size:16px;">
                <?php esc_html_e( 'MoMo Payment Verification / Vérification du paiement MoMo', 'kevincho-tailoring-manager' ); ?>
            </h3>

            <?php if ( $ref ) : ?>
                <div style="background:#fff;border:1px solid #e8d5a3;border-radius:6px;padding:12px;margin-bottom:12px;">
                    <p style="margin:0 0 6px;font-size:14px;color:#402417;">
                        <strong><?php esc_html_e( 'EN:', 'kevincho-tailoring-manager' ); ?></strong>
                        <?php
                        printf(
                            /* translators: %s: transaction ID */
                            esc_html__( 'Your payment is being verified. Transaction ID: %s', 'kevincho-tailoring-manager' ),
                            '<strong>' . esc_html( $ref ) . '</strong>'
                        );
                        ?>
                    </p>
                    <p style="margin:0;font-size:14px;color:#402417;">
                        <strong><?php esc_html_e( 'FR:', 'kevincho-tailoring-manager' ); ?></strong>
                        <?php
                        printf(
                            esc_html__( 'Votre paiement est en cours de vérification. ID de transaction : %s', 'kevincho-tailoring-manager' ),
                            '<strong>' . esc_html( $ref ) . '</strong>'
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="padding:4px 0;color:#666;width:45%;"><?php esc_html_e( 'Pay to Number:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:700;color:#402417;"><?php echo esc_html( $this->momo_number ); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#666;"><?php esc_html_e( 'Account Name:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:600;color:#402417;"><?php echo esc_html( $this->momo_name ); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#666;"><?php esc_html_e( 'Amount:', 'kevincho-tailoring-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:700;color:#402417;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                </tr>
            </table>
            <?php if ( $this->instructions ) : ?>
                <p style="margin:12px 0 0;color:#555;border-top:1px solid #e8d5a3;padding-top:10px;">
                    <?php echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Add instructions to order emails.
     *
     * @param WC_Order $order         Order object.
     * @param bool     $sent_to_admin Whether email is sent to admin.
     * @param bool     $plain_text    Whether email is plain text.
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        if ( $order->get_payment_method() !== $this->id ) {
            return;
        }

        if ( ! $order->has_status( 'on-hold' ) ) {
            return;
        }

        $ref = $order->get_meta( '_kctm_momo_ref' );

        if ( $plain_text ) {
            echo "\n" . esc_html__( 'MOMO PAYMENT DETAILS', 'kevincho-tailoring-manager' ) . "\n";
            echo str_repeat( '=', 30 ) . "\n";
            /* translators: %s: MoMo phone number */
            echo sprintf( esc_html__( 'Pay to: %s', 'kevincho-tailoring-manager' ), $this->momo_number ) . "\n";
            /* translators: %s: account holder name */
            echo sprintf( esc_html__( 'Name: %s', 'kevincho-tailoring-manager' ), $this->momo_name ) . "\n";
            /* translators: %s: order total */
            echo sprintf( esc_html__( 'Amount: %s', 'kevincho-tailoring-manager' ), $order->get_formatted_order_total() ) . "\n";
            if ( $ref ) {
                /* translators: %s: transaction reference */
                echo sprintf( esc_html__( 'Transaction ID: %s', 'kevincho-tailoring-manager' ), $ref ) . "\n";
                echo esc_html__( 'Your payment is being verified. / Votre paiement est en cours de vérification.', 'kevincho-tailoring-manager' ) . "\n";
            }
            if ( $this->instructions ) {
                echo "\n" . wp_strip_all_tags( $this->instructions ) . "\n";
            }
            echo "\n";
        } else {
            ?>
            <div style="background:#fef9e7;border:2px solid #c9a96e;border-radius:8px;padding:16px;margin:16px 0;font-size:14px;line-height:1.6;">
                <h3 style="color:#402417;margin:0 0 10px;font-size:15px;">
                    <?php esc_html_e( 'MoMo Payment Details', 'kevincho-tailoring-manager' ); ?>
                </h3>
                <?php if ( $ref ) : ?>
                <div style="background:#fff;border:1px solid #e8d5a3;border-radius:4px;padding:10px;margin-bottom:10px;">
                    <p style="margin:0 0 4px;font-size:13px;color:#402417;">
                        <?php
                        printf(
                            esc_html__( 'Your payment is being verified. Transaction ID: %s', 'kevincho-tailoring-manager' ),
                            '<strong>' . esc_html( $ref ) . '</strong>'
                        );
                        ?>
                    </p>
                    <p style="margin:0;font-size:13px;color:#402417;">
                        <?php
                        printf(
                            esc_html__( 'Votre paiement est en cours de vérification. ID de transaction : %s', 'kevincho-tailoring-manager' ),
                            '<strong>' . esc_html( $ref ) . '</strong>'
                        );
                        ?>
                    </p>
                </div>
                <?php endif; ?>
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:3px 0;color:#666;"><?php esc_html_e( 'Pay to:', 'kevincho-tailoring-manager' ); ?></td>
                        <td style="padding:3px 0;font-weight:700;color:#402417;"><?php echo esc_html( $this->momo_number ); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:3px 0;color:#666;"><?php esc_html_e( 'Name:', 'kevincho-tailoring-manager' ); ?></td>
                        <td style="padding:3px 0;font-weight:600;color:#402417;"><?php echo esc_html( $this->momo_name ); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:3px 0;color:#666;"><?php esc_html_e( 'Amount:', 'kevincho-tailoring-manager' ); ?></td>
                        <td style="padding:3px 0;font-weight:700;color:#402417;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                    </tr>
                </table>
                <?php if ( $this->instructions ) : ?>
                    <p style="margin:10px 0 0;color:#555;border-top:1px solid #e8d5a3;padding-top:8px;">
                        <?php echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ); ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php
        }
    }
}
