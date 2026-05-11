<?php
/**
 * CamPay Block Checkout Integration.
 *
 * Registers the CamPay payment method with WooCommerce Block Checkout
 * so it works with the new block-based checkout page.
 *
 * @package KevinCho_Tailoring_Manager
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KCTM_CamPay_Blocks
 */
final class KCTM_CamPay_Blocks extends AbstractPaymentMethodType {

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'kctm_campay';

    /**
     * Gateway instance.
     *
     * @var KCTM_CamPay_Gateway|null
     */
    private $gateway = null;

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_kctm_campay_settings', array() );

        // Get the gateway instance.
        $gateways = WC()->payment_gateways()->payment_gateways();
        if ( isset( $gateways['kctm_campay'] ) ) {
            $this->gateway = $gateways['kctm_campay'];
        }
    }

    /**
     * Returns whether this payment method is active.
     *
     * @return bool
     */
    public function is_active() {
        return ! empty( $this->settings['enabled'] ) && $this->settings['enabled'] === 'yes';
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return string[]
     */
    public function get_payment_method_script_handles() {
        $handle = 'kctm-campay-blocks';

        if ( ! wp_script_is( $handle, 'registered' ) ) {
            wp_register_script(
                $handle,
                KCTM_PLUGIN_URL . 'assets/js/kctm-campay-blocks.js',
                array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n' ),
                KCTM_VERSION,
                true
            );

            wp_localize_script( $handle, 'kctmCampayData', $this->get_payment_method_data() );
        }

        return array( $handle );
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'       => isset( $this->settings['title'] ) ? $this->settings['title'] : 'Mobile Money (MTN / Orange)',
            'description' => isset( $this->settings['description'] ) ? $this->settings['description'] : '',
            'supports'    => array( 'products' ),
            'phone_field' => array(
                'label'       => __( 'Mobile Money Phone Number', 'kevincho-tailoring-manager' ),
                'placeholder' => __( 'e.g. 6XXXXXXXX', 'kevincho-tailoring-manager' ),
                'help'        => __( 'Enter your 9-digit Cameroon phone number (without country code).', 'kevincho-tailoring-manager' ),
            ),
        );
    }
}
