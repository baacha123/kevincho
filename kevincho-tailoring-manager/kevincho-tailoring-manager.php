<?php
/**
 * Plugin Name:       KevinCho Tailoring Manager
 * Plugin URI:        https://kevincho.com
 * Description:       Custom tailoring management for KevinCho — measurements, order workflow, garment personalization, WhatsApp + SMS notifications, and in-store order support.
 * Version:           1.3.1
 * Author:            KevinCho
 * Author URI:        https://kevincho.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kevincho-tailoring-manager
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   9.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Constants ────────────────────────────────────────────── */
define( 'KCTM_VERSION',     '1.3.1' );
define( 'KCTM_PLUGIN_FILE', __FILE__ );
define( 'KCTM_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'KCTM_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'KCTM_PLUGIN_BASE', plugin_basename( __FILE__ ) );

/* ── HPOS compatibility ──────────────────────────────────── */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/* ── Activation / Deactivation ───────────────────────────── */
register_activation_hook( __FILE__, function () {
    require_once KCTM_PLUGIN_DIR . 'includes/class-kctm-activator.php';
    KCTM_Activator::activate();
} );

register_deactivation_hook( __FILE__, function () {
    require_once KCTM_PLUGIN_DIR . 'includes/class-kctm-deactivator.php';
    KCTM_Deactivator::deactivate();
} );

/* ── WooCommerce dependency check ────────────────────────── */
add_action( 'admin_init', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'KevinCho Tailoring Manager requires WooCommerce to be installed and active.', 'kevincho-tailoring-manager' );
            echo '</p></div>';
        } );
        deactivate_plugins( KCTM_PLUGIN_BASE );
    }
} );

/* ── Upgrade hook (runs on existing installs) ───────────── */
add_action( 'plugins_loaded', function () {
    $installed = get_option( 'kctm_db_version', '0' );
    if ( version_compare( $installed, KCTM_VERSION, '<' ) ) {
        require_once KCTM_PLUGIN_DIR . 'includes/class-kctm-activator.php';
        KCTM_Activator::activate();
    }
}, 5 );

/* ── Bootstrap ───────────────────────────────────────────── */
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    load_plugin_textdomain( 'kevincho-tailoring-manager', false, dirname( KCTM_PLUGIN_BASE ) . '/languages' );

    require_once KCTM_PLUGIN_DIR . 'includes/class-kctm-loader.php';
    $loader = new KCTM_Loader();
    $loader->run();
} );

/* ── Block Checkout — must register early ───────────────── */
add_action( 'woocommerce_blocks_loaded', function () {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // CamPay blocks.
    require_once KCTM_PLUGIN_DIR . 'includes/payments/class-kctm-campay-gateway.php';
    require_once KCTM_PLUGIN_DIR . 'includes/payments/class-kctm-campay-blocks.php';

    // MoMo Manual blocks.
    require_once KCTM_PLUGIN_DIR . 'includes/payments/class-kctm-momo-manual-gateway.php';
    require_once KCTM_PLUGIN_DIR . 'includes/payments/class-kctm-momo-manual-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ( $registry ) {
            $registry->register( new KCTM_CamPay_Blocks() );
            $registry->register( new KCTM_MoMo_Manual_Blocks() );
        }
    );
} );
