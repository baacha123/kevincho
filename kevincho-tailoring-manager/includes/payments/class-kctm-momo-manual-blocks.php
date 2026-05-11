<?php
/**
 * MoMo Manual Block Checkout Integration.
 *
 * Registers the MoMo Manual payment method with WooCommerce Block Checkout
 * so it works with the new block-based checkout page.
 *
 * @package KevinCho_Tailoring_Manager
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KCTM_MoMo_Manual_Blocks
 */
final class KCTM_MoMo_Manual_Blocks extends AbstractPaymentMethodType {

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'kctm_momo_manual';

    /**
     * Gateway instance.
     *
     * @var KCTM_MoMo_Manual_Gateway|null
     */
    private $gateway = null;

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_kctm_momo_manual_settings', array() );

        // Get the gateway instance.
        $gateways = WC()->payment_gateways()->payment_gateways();
        if ( isset( $gateways['kctm_momo_manual'] ) ) {
            $this->gateway = $gateways['kctm_momo_manual'];
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
        $handle = 'kctm-momo-manual-blocks';

        if ( ! wp_script_is( $handle, 'registered' ) ) {
            wp_register_script(
                $handle,
                KCTM_PLUGIN_URL . 'assets/js/kctm-momo-manual-blocks.js',
                array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n' ),
                KCTM_VERSION,
                true
            );

            wp_localize_script( $handle, 'kctmMomoManualData', $this->get_payment_method_data() );
        }

        return array( $handle );
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        $momo_number = isset( $this->settings['momo_number'] ) ? $this->settings['momo_number'] : '';
        $momo_name   = isset( $this->settings['momo_name'] ) ? $this->settings['momo_name'] : 'Kevin Cho Tailoring';

        return array(
            'title'       => isset( $this->settings['title'] ) ? $this->settings['title'] : 'Mobile Money (MoMo)',
            'description' => isset( $this->settings['description'] ) ? $this->settings['description'] : '',
            'supports'    => array( 'products' ),
            'momo_number' => $momo_number,
            'momo_name'   => $momo_name,
        );
    }
}
