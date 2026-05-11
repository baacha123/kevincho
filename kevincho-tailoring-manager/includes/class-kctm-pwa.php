<?php
/**
 * Progressive Web App support — manifest, service worker, meta tags.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_PWA {

    public static function init() {
        // Meta tags and manifest link in <head>.
        add_action( 'wp_head', array( __CLASS__, 'render_head_meta' ), 1 );

        // Service worker registration in footer.
        add_action( 'wp_footer', array( __CLASS__, 'register_service_worker' ), 999 );

        // Rewrite rule so /sw.js serves the service worker with correct headers.
        add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
        add_action( 'template_redirect', array( __CLASS__, 'serve_service_worker' ) );
    }

    /**
     * Output PWA meta tags and manifest link in <head>.
     */
    public static function render_head_meta() {
        $manifest_url = KCTM_PLUGIN_URL . 'assets/manifest.json';
        $icon_url     = '/wp-content/uploads/pwa-icon-192.png';
        ?>
        <link rel="manifest" href="<?php echo esc_url( $manifest_url ); ?>">
        <meta name="theme-color" content="#402417">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="apple-touch-icon" href="<?php echo esc_url( $icon_url ); ?>">
        <?php
    }

    /**
     * Register the service worker via inline JS.
     */
    public static function register_service_worker() {
        if ( is_admin() ) {
            return;
        }
        ?>
        <script id="kctm-pwa-sw">
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function(reg) {
                        // Registration successful.
                    })
                    .catch(function(err) {
                        // SW registration failed — non-critical.
                    });
            });
        }
        </script>
        <?php
    }

    /**
     * Add rewrite rule: /sw.js -> ?kctm_sw=1
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule( '^sw\.js$', 'index.php?kctm_sw=1', 'top' );
    }

    /**
     * Register the kctm_sw query var.
     */
    public static function add_query_vars( $vars ) {
        $vars[] = 'kctm_sw';
        return $vars;
    }

    /**
     * Serve the service worker JS file with the correct headers.
     */
    public static function serve_service_worker() {
        if ( ! get_query_var( 'kctm_sw' ) ) {
            return;
        }

        $sw_file = KCTM_PLUGIN_DIR . 'assets/sw.js';

        if ( ! file_exists( $sw_file ) ) {
            status_header( 404 );
            exit;
        }

        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'Service-Worker-Allowed: /' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'X-Robots-Tag: none' );

        readfile( $sw_file );
        exit;
    }
}
