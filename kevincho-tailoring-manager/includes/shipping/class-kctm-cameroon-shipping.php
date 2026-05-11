<?php
/**
 * Cameroon region-based shipping method for Kevin Cho Tailoring.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_Cameroon_Shipping extends WC_Shipping_Method {

    public function __construct( $instance_id = 0 ) {
        $this->id                 = 'kctm_cameroon_shipping';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Kevin Cho Delivery', 'kevincho-tailoring-manager' );
        $this->method_description = __( 'Region-based delivery rates for Cameroon.', 'kevincho-tailoring-manager' );
        $this->supports           = array( 'shipping-zones', 'instance-settings' );
        $this->enabled            = 'yes';

        $this->init();
    }

    private function init() {
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option( 'title', $this->method_title );
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {
        $this->instance_form_fields = array(
            'title' => array(
                'title'   => __( 'Method Title', 'kevincho-tailoring-manager' ),
                'type'    => 'text',
                'default' => __( 'Kevin Cho Delivery', 'kevincho-tailoring-manager' ),
            ),
        );
    }

    /**
     * Calculate shipping rates based on the customer's selected Cameroon region.
     *
     * @param array $package Shipping package.
     */
    public function calculate_shipping( $package = array() ) {
        $state = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';

        /* Standard delivery cost based on region */
        switch ( $state ) {
            case 'LT': // Littoral (Douala) — free
                $standard_cost = 0;
                break;
            case 'SW': // South West (Buea)
                $standard_cost = 1500;
                break;
            default: // All other regions
                $standard_cost = 2000;
                break;
        }

        $standard_label = __( 'Standard Delivery', 'kevincho-tailoring-manager' );
        if ( $standard_cost == 0 ) {
            $standard_label .= ' (' . __( 'Free', 'kevincho-tailoring-manager' ) . ')';
        }

        $this->add_rate( array(
            'id'    => $this->get_rate_id( 'standard' ),
            'label' => $standard_label,
            'cost'  => $standard_cost,
        ) );

        /* Express delivery — flat 5,000 FCFA */
        $this->add_rate( array(
            'id'    => $this->get_rate_id( 'express' ),
            'label' => __( 'Express Delivery', 'kevincho-tailoring-manager' ),
            'cost'  => 5000,
        ) );
    }
}
