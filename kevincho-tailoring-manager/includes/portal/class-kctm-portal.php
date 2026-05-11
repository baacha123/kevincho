<?php
/**
 * Store Manager Portal — Controller
 *
 * Intercepts the /store-manager/ page, checks auth,
 * and loads the portal template (bypassing the theme).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_Portal {

    /** Page slug used for the portal. */
    const PAGE_SLUG = 'store-manager';

    /**
     * Boot.
     */
    public static function init() {
        add_filter( 'template_include', array( __CLASS__, 'load_portal_template' ), 99 );
        add_action( 'wp_ajax_kctm_portal_login', array( __CLASS__, 'handle_login' ) );
        add_action( 'wp_ajax_nopriv_kctm_portal_login', array( __CLASS__, 'handle_login' ) );
    }

    /**
     * If we are on the store-manager page, replace the theme template entirely.
     */
    public static function load_portal_template( $template ) {
        if ( ! is_page( self::PAGE_SLUG ) ) {
            return $template;
        }

        /* Not logged in → show the portal template which renders a login screen via JS. */
        if ( ! is_user_logged_in() ) {
            return KCTM_PLUGIN_DIR . 'templates/portal/portal-app.php';
        }

        /* Logged in but insufficient permissions. */
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'kctm_view_portal' ) ) {
            wp_die(
                'You do not have permission to access the Store Manager portal.',
                'Access Denied',
                array( 'response' => 403 )
            );
        }

        return KCTM_PLUGIN_DIR . 'templates/portal/portal-app.php';
    }

    /**
     * AJAX login handler for the portal login form.
     */
    public static function handle_login() {
        check_ajax_referer( 'kctm_portal_nonce', 'nonce' );

        $username = sanitize_text_field( wp_unslash( $_POST['username'] ?? '' ) );
        $password = wp_unslash( $_POST['password'] ?? '' );

        if ( empty( $username ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => 'Please enter username and password.' ) );
        }

        $user = wp_signon( array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        ), is_ssl() );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array( 'message' => 'Invalid username or password.' ) );
        }

        if ( ! user_can( $user, 'manage_woocommerce' ) && ! user_can( $user, 'kctm_view_portal' ) ) {
            wp_logout();
            wp_send_json_error( array( 'message' => 'You do not have permission to access this portal.' ) );
        }

        wp_send_json_success( array( 'message' => 'Login successful.', 'redirect' => home_url( '/' . self::PAGE_SLUG . '/' ) ) );
    }
}
