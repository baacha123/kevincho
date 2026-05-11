<?php
/**
 * Central hook loader — requires all modules and wires them up.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_Loader {

    public function run() {

        /* ── Measurements ─────────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/measurements/class-kctm-measurement-fields.php';
        require_once KCTM_PLUGIN_DIR . 'includes/measurements/class-kctm-measurement-storage.php';
        require_once KCTM_PLUGIN_DIR . 'includes/measurements/class-kctm-measurement-validator.php';

        /* ── Accounts ─────────────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/accounts/class-kctm-my-account-endpoints.php';
        require_once KCTM_PLUGIN_DIR . 'includes/accounts/class-kctm-registration-fields.php';

        /* ── Orders ───────────────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/orders/class-kctm-order-statuses.php';
        require_once KCTM_PLUGIN_DIR . 'includes/orders/class-kctm-order-meta.php';

        /* ── Personalization ──────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/personalization/class-kctm-personalization-options.php';
        require_once KCTM_PLUGIN_DIR . 'includes/personalization/class-kctm-personalization-frontend.php';
        require_once KCTM_PLUGIN_DIR . 'includes/personalization/class-kctm-personalization-storage.php';
        require_once KCTM_PLUGIN_DIR . 'includes/personalization/class-kctm-fabric-catalog.php';

        /* ── Notifications ────────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/notifications/class-kctm-whatsapp-api.php';
        require_once KCTM_PLUGIN_DIR . 'includes/notifications/class-kctm-sms-api.php';
        require_once KCTM_PLUGIN_DIR . 'includes/notifications/class-kctm-notification-log.php';
        require_once KCTM_PLUGIN_DIR . 'includes/notifications/class-kctm-notification-dispatcher.php';
        require_once KCTM_PLUGIN_DIR . 'includes/notifications/class-kctm-notification-hooks.php';
        require_once KCTM_PLUGIN_DIR . 'includes/notifications/class-kctm-whatsapp-notifications.php';

        /* ── Consultations ───────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/consultations/class-kctm-consultation-availability.php';
        require_once KCTM_PLUGIN_DIR . 'includes/consultations/class-kctm-consultation-product.php';
        require_once KCTM_PLUGIN_DIR . 'includes/consultations/class-kctm-consultation-booking.php';
        require_once KCTM_PLUGIN_DIR . 'includes/consultations/class-kctm-consultation-notifications.php';
        require_once KCTM_PLUGIN_DIR . 'includes/consultations/class-kctm-consultation-cron.php';

        /* ── Payments ──────────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/payments/class-kctm-momo-manual-gateway.php';
        require_once KCTM_PLUGIN_DIR . 'includes/payments/class-kctm-campay-gateway.php';

        /* ── Shipping ──────────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/shipping/class-kctm-cameroon-shipping.php';

        /* ── Multi-Currency ─────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/class-kctm-currency.php';

        /* ── Frontend Enhancements ─────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/class-kctm-frontend-enhancements.php';

        /* ── PWA Support ───────────────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/class-kctm-pwa.php';

        /* ── Store Manager Portal ───────────────────── */
        require_once KCTM_PLUGIN_DIR . 'includes/portal/class-kctm-portal.php';
        require_once KCTM_PLUGIN_DIR . 'includes/portal/class-kctm-portal-ajax.php';

        /* ── Admin (only in dashboard) ────────────────── */
        if ( is_admin() ) {
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-menu.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-customers.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-create-order.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-walkin.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-settings.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-ajax.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-consultations.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-consultation-settings.php';
            require_once KCTM_PLUGIN_DIR . 'includes/admin/class-kctm-admin-fabrics.php';
        }

        /* ── Initialize all modules ───────────────────── */
        KCTM_My_Account_Endpoints::init();
        KCTM_Registration_Fields::init();
        KCTM_Order_Statuses::init();
        KCTM_Order_Meta::init();
        KCTM_Personalization_Frontend::init();
        KCTM_Personalization_Storage::init();
        KCTM_WhatsApp_Notifications::init();
        KCTM_Notification_Hooks::init();

        /* ── Consultations ───────────────────────────── */
        KCTM_Consultation_Product::init();
        KCTM_Consultation_Booking::init();
        KCTM_Consultation_Cron::init();

        /* ── Payments ──────────────────────────────── */
        add_filter( 'woocommerce_payment_gateways', array( $this, 'register_momo_manual_gateway' ) );
        add_filter( 'woocommerce_payment_gateways', array( $this, 'register_campay_gateway' ) );
        add_action( 'woocommerce_blocks_loaded', array( $this, 'register_campay_blocks' ) );

        /* ── Multi-Currency ─────────────────────────── */
        KCTM_Currency::init();

        /* ── Frontend Enhancements ─────────────────── */
        KCTM_Frontend_Enhancements::init();

        /* ── PWA Support ───────────────────────────── */
        KCTM_PWA::init();

        /* ── Store Manager Portal ───────────────────── */
        KCTM_Portal::init();
        KCTM_Portal_Ajax::init();

        if ( is_admin() ) {
            KCTM_Admin_Menu::init();
            KCTM_Admin_Ajax::init();
            KCTM_Admin_Consultation_Settings::init();
            KCTM_Admin_Fabrics::init();
        }

        /* ── Checkout: ensure billing_city and billing_phone are required ── */
        add_filter( 'woocommerce_billing_fields', array( $this, 'require_checkout_fields' ) );

        /* ── SEO meta via Yoast filters ─────────────── */
        add_filter( 'wpseo_title', array( $this, 'filter_seo_title' ), 15 );
        add_filter( 'wpseo_metadesc', array( $this, 'filter_seo_desc' ), 15 );
        add_filter( 'wpseo_opengraph_title', array( $this, 'filter_seo_title' ), 15 );
        add_filter( 'wpseo_opengraph_desc', array( $this, 'filter_seo_desc' ), 15 );

        /* ── Allow any password — no strength meter ──── */
        add_filter( 'woocommerce_min_password_length', function () { return 1; } );
        add_action( 'wp_enqueue_scripts', function () {
            wp_dequeue_script( 'wc-password-strength-meter' );
            wp_deregister_script( 'wc-password-strength-meter' );
            /* Force cart fragments so cart badge updates after add-to-cart */
            if ( ! is_admin() ) {
                wp_enqueue_script( 'wc-cart-fragments' );
            }
        }, 999 );

        /* ── Email branding ───────────────────────────── */
        add_filter( 'wp_mail_from', function () { return 'info@kevincho.com'; } );
        add_filter( 'wp_mail_from_name', function () { return 'Kevin Cho Tailoring'; } );

        /* ── Checkout field customization ─────────────── */
        add_filter( 'woocommerce_get_country_locale', array( $this, 'customize_cameroon_locale' ) );
        add_filter( 'woocommerce_states', array( $this, 'cameroon_regions' ) );
        add_filter( 'default_checkout_billing_country', function () { return 'CM'; } );
        add_filter( 'default_checkout_shipping_country', function () { return 'CM'; } );

        /* ── Cameroon region-based shipping ──────────── */
        add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
            $methods['kctm_cameroon_shipping'] = 'KCTM_Cameroon_Shipping';
            return $methods;
        } );

        /* ── Redirect /my-account/ to /profile/ ────────── */
        add_action( 'init', function () {
            if ( isset( $_SERVER['REQUEST_URI'] ) && preg_match( '#^/my-account(/|$)#', $_SERVER['REQUEST_URI'] ) && ! is_admin() ) {
                $path = str_replace( '/my-account', '/profile', $_SERVER['REQUEST_URI'] );
                wp_safe_redirect( home_url( $path ), 301 );
                exit;
            }
        }, 1 );

        /* ── Login/Register page: show only login, link to register ── */
        add_action( 'woocommerce_after_customer_login_form', array( $this, 'login_register_link' ) );
        add_action( 'wp_head', array( $this, 'login_register_css' ) );

        /* ── Book Consultation on Custom Wear page ──── */
        add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_consultation_cta' ), 5 );
        add_action( 'wp_head', array( $this, 'hide_custom_wear_extras' ) );

        /* Auto-purge handled in portal save AJAX handler — hooks removed (caused conflicts) */

        /* ── Suit Configurator shortcode ─────────────── */
        add_shortcode( 'kctm_suit_configurator', array( $this, 'render_suit_configurator' ) );

        /* ── Frontend assets ──────────────────────────── */
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_branding' ), 999 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
        /* Payment logos removed — kept clean */
        add_action( 'wp_footer', array( $this, 'inject_navigation_menu' ) );
        add_action( 'wp_footer', array( $this, 'inject_cart_count_badge' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_branding' ) );
    }

    /**
     * Customize checkout fields for Cameroon.
     */
    /**
     * Show consultation CTA on Custom Wear product page.
     */
    public function render_consultation_cta() {
        global $product;
        if ( ! $product || $product->get_slug() !== 'custom-wear' ) {
            return;
        }
        ?>
        <div style="background:linear-gradient(135deg,#402417 0%,#5a3828 100%);border-radius:16px;padding:40px;margin:30px 0;color:#fff;">
            <h2 style="font-family:'Cormorant Garamond',Georgia,serif;font-size:28px;font-weight:400;color:#c9a96e;margin:0 0 12px;text-align:center;">
                Book a Consultation with Kevin Cho
            </h2>
            <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.85);max-width:550px;margin:0 auto 8px;font-family:'Open Sans',sans-serif;text-align:center;">
                For your custom wear, book a consultation to discuss your design, choose fabrics, and get measured for the perfect fit.
            </p>
            <p style="font-size:13px;line-height:1.6;color:rgba(255,255,255,0.6);max-width:550px;margin:0 auto 24px;font-family:'Open Sans',sans-serif;text-align:center;">
                Pour vos tenues sur mesure, prenez rendez-vous pour discuter de votre design, choisir les tissus et prendre vos mesures.
            </p>
            <div style="background:rgba(255,255,255,0.08);border-radius:12px;padding:20px;max-width:600px;margin:0 auto;">
                <?php echo do_shortcode( '[kctm_consultation_booking]' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * On the My Account login page, hide the register column and show a link instead.
     */
    public function login_register_css() {
        if ( ! is_account_page() || is_user_logged_in() ) {
            return;
        }
        $show_register = isset( $_GET['action'] ) && $_GET['action'] === 'register';
        ?>
        <style>
        <?php if ( ! $show_register ) : ?>
            .woocommerce-form--register,
            .u-column2.col-2 { display: none !important; }
            .u-column1.col-1 { width: 100% !important; max-width: 480px !important; margin: 0 auto !important; float: none !important; }
            .u-columns { display: block !important; }
        <?php else : ?>
            .woocommerce-form--login,
            .u-column1.col-1 { display: none !important; }
            .u-column2.col-2 { width: 100% !important; max-width: 560px !important; margin: 0 auto !important; float: none !important; }
            .u-columns { display: block !important; }
        <?php endif; ?>
        .kctm-auth-switch { text-align: center; margin: -1.5rem 0 1rem; font-size: 1.5rem; color: #555; }
        .kctm-auth-switch a { color: #c9a96e; font-weight: 600; text-decoration: none; }
        .kctm-auth-switch a:hover { text-decoration: underline; color: #402417; }
        </style>
        <?php
    }

    public function login_register_link() {
        if ( is_user_logged_in() ) {
            return;
        }
        $show_register = isset( $_GET['action'] ) && $_GET['action'] === 'register';
        $profile_url   = wc_get_page_permalink( 'myaccount' );
        if ( $show_register ) {
            echo '<p class="kctm-auth-switch">Already have an account? <a href="' . esc_url( $profile_url ) . '">Sign in here</a></p>';
        } else {
            echo '<p class="kctm-auth-switch">Don\'t have an account yet? <a href="' . esc_url( add_query_arg( 'action', 'register', $profile_url ) ) . '">Create one here</a></p>';
        }
    }

    /**
     * Hide extras on Custom Wear product page — no related products, no add to cart, no price.
     */
    public function hide_custom_wear_extras() {
        if ( ! is_product() ) {
            return;
        }
        global $post;
        if ( ! $post || $post->post_name !== 'custom-wear' ) {
            return;
        }
        ?>
        <style>
            /* Custom Wear page — hide price, add to cart, wishlist, share, meta, related */
            body.single-product .summary .price,
            body.single-product .summary form.cart,
            body.single-product .summary .cart,
            body.single-product .yith-add-to-wishlist-button-block,
            body.single-product .yith-wcwl-add-to-wishlist,
            body.single-product .product_meta,
            body.single-product .woocommerce-product-rating,
            body.single-product section.related,
            body.single-product .related.products,
            body.single-product .up-sells,
            body.single-product .kctm-social-share,
            body.single-product [data-product-id] .yith-add-to-wishlist-button-block { display: none !important; }
        </style>
        <?php
    }

    /**
     * Purge SiteGround cache when products change.
     */
    public function purge_sg_cache( $product_id = 0 ) {
        /* SG Optimizer plugin */
        if ( function_exists( 'sg_cachepress_purge_everything' ) ) {
            sg_cachepress_purge_everything();
        } elseif ( class_exists( 'SG_CachePress_Supercacher' ) ) {
            SG_CachePress_Supercacher::purge_cache();
        }
        /* Also clear WC transients */
        if ( function_exists( 'wc_delete_product_transients' ) && $product_id ) {
            wc_delete_product_transients( $product_id );
        }
        /* Clear 10Web cache */
        if ( class_exists( 'TenWebOptimizer\OptimizerUtils' ) ) {
            try { \TenWebOptimizer\OptimizerUtils::clear_cache(); } catch ( \Exception $e ) {}
        }
    }

    public function customize_cameroon_locale( $locales ) {
        $locales['CM'] = array(
            'postcode' => array(
                'required' => false,
                'hidden'   => true,
            ),
            'state' => array(
                'required' => true,
                'label'    => 'Region',
            ),
            'city' => array(
                'required' => true,
                'label'    => 'City / Ville',
            ),
            'address_1' => array(
                'label'       => 'Describe where you stay / Décrivez où vous habitez',
                'placeholder' => '',
            ),
            'phone' => array(
                'required' => true,
            ),
        );
        return $locales;
    }

    /**
     * Add Cameroon's 10 regions to WooCommerce states.
     *
     * @param  array $states Existing states.
     * @return array
     */
    public function cameroon_regions( $states ) {
        $states['CM'] = array(
            'AD' => 'Adamawa (Ngaoundéré)',
            'CE' => 'Centre (Yaoundé)',
            'ES' => 'East (Bertoua)',
            'EN' => 'Far North (Maroua)',
            'LT' => 'Littoral (Douala)',
            'NO' => 'North (Garoua)',
            'NW' => 'North West (Bamenda)',
            'OU' => 'West (Bafoussam)',
            'SU' => 'South (Ebolowa)',
            'SW' => 'South West (Buea)',
        );
        return $states;
    }

    /**
     * Register the manual MoMo payment gateway with WooCommerce.
     *
     * @param array $gateways Existing gateways.
     * @return array
     */
    public function register_momo_manual_gateway( $gateways ) {
        $gateways[] = 'KCTM_MoMo_Manual_Gateway';
        return $gateways;
    }

    /**
     * Register the CamPay payment gateway with WooCommerce.
     *
     * @param array $gateways Existing gateways.
     * @return array
     */
    public function register_campay_gateway( $gateways ) {
        $gateways[] = 'KCTM_CamPay_Gateway';
        return $gateways;
    }

    /**
     * Register CamPay block checkout integration.
     */
    public function register_campay_blocks() {
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            return;
        }
        require_once KCTM_PLUGIN_DIR . 'includes/payments/class-kctm-campay-blocks.php';
        $registry = WC()->payment_gateways(); // Ensure gateways are loaded.
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function ( $payment_method_registry ) {
                $payment_method_registry->register( new KCTM_CamPay_Blocks() );
            }
        );
    }

    /**
     * Frontend CSS & JS.
     */
    public function enqueue_frontend() {
        global $post;

        $is_consultation = false;
        if ( $post && ( has_shortcode( $post->post_content, 'kctm_consultation_booking' ) || is_page( 'consultation' ) ) ) {
            $is_consultation = true;
        }

        $is_configurator = false;
        if ( $post && has_shortcode( $post->post_content, 'kctm_suit_configurator' ) ) {
            $is_configurator = true;
        }

        if ( ! is_account_page() && ! is_product() && ! is_cart() && ! $is_consultation && ! $is_configurator ) {
            return;
        }

        wp_enqueue_style(
            'kctm-frontend',
            KCTM_PLUGIN_URL . 'assets/css/kctm-frontend.css',
            array(),
            KCTM_VERSION
        );

        if ( $is_consultation ) {
            wp_enqueue_style(
                'kctm-consultation-booking',
                KCTM_PLUGIN_URL . 'assets/css/kctm-consultation-booking.css',
                array(),
                KCTM_VERSION
            );
            wp_enqueue_script(
                'kctm-consultation-booking',
                KCTM_PLUGIN_URL . 'assets/js/kctm-consultation-booking.js',
                array( 'jquery' ),
                KCTM_VERSION,
                true
            );
            wp_localize_script( 'kctm-consultation-booking', 'kctm_consultation', array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'kctm_consultation_nonce' ),
                'checkout_url' => wc_get_checkout_url(),
            ) );
        }

        if ( is_account_page() ) {
            wp_enqueue_script(
                'kctm-measurement-form',
                KCTM_PLUGIN_URL . 'assets/js/kctm-measurement-form.js',
                array( 'jquery' ),
                KCTM_VERSION,
                true
            );
            wp_localize_script( 'kctm-measurement-form', 'kctm_measurements', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'kctm_save_measurements' ),
            ) );
        }

        if ( is_product() ) {
            wp_enqueue_style(
                'kctm-personalization',
                KCTM_PLUGIN_URL . 'assets/css/kctm-personalization.css',
                array(),
                KCTM_VERSION
            );
            wp_enqueue_script(
                'kctm-personalization',
                KCTM_PLUGIN_URL . 'assets/js/kctm-personalization.js',
                array( 'jquery' ),
                KCTM_VERSION,
                true
            );
        }

        if ( $is_configurator ) {
            wp_enqueue_style(
                'kctm-suit-configurator',
                KCTM_PLUGIN_URL . 'assets/css/kctm-suit-configurator.css',
                array(),
                KCTM_VERSION
            );
            wp_enqueue_script(
                'kctm-suit-configurator',
                KCTM_PLUGIN_URL . 'assets/js/kctm-suit-configurator.js',
                array( 'jquery' ),
                KCTM_VERSION,
                true
            );
            wp_localize_script( 'kctm-suit-configurator', 'kctm_configurator', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'kctm_configurator_nonce' ),
            ) );
        }
    }

    /**
     * Render the [kctm_suit_configurator] shortcode.
     *
     * @param  array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_suit_configurator( $atts ) {
        $atts = shortcode_atts( array(
            'product_id' => 0,
        ), $atts, 'kctm_suit_configurator' );

        $product_id = absint( $atts['product_id'] );

        // If no product_id attribute, try to find a personalizable product.
        if ( ! $product_id ) {
            $products = wc_get_products( array(
                'limit'  => 1,
                'status' => 'publish',
                'type'   => 'simple',
                'orderby' => 'date',
                'order'   => 'DESC',
            ) );
            if ( ! empty( $products ) ) {
                $product_id = $products[0]->get_id();
            }
        }

        $product = $product_id ? wc_get_product( $product_id ) : null;
        $base_price = $product ? floatval( $product->get_price() ) : 0;
        $currency_symbol = get_woocommerce_currency_symbol();

        // Fabrics.
        $fabrics = KCTM_Fabric_Catalog::get_fabrics( array( 'active_only' => true ) );

        // Personalization groups split into style & accents.
        $all_groups   = KCTM_Personalization_Options::get_groups_with_options();
        $style_slugs  = array( 'collar-style', 'sleeve-style', 'fit', 'pocket-style' );
        $style_groups = array();
        $accent_groups = array();

        foreach ( $all_groups as $group ) {
            if ( in_array( $group->slug, $style_slugs, true ) ) {
                $style_groups[] = $group;
            } else {
                $accent_groups[] = $group;
            }
        }

        ob_start();
        include KCTM_PLUGIN_DIR . 'templates/personalization/suit-configurator.php';
        return ob_get_clean();
    }

    /**
     * Frontend branding — loaded on ALL frontend pages.
     */
    public function enqueue_frontend_branding() {
        if ( is_admin() ) {
            return;
        }
        wp_enqueue_style(
            'kctm-wc-branding',
            KCTM_PLUGIN_URL . 'assets/css/kctm-wc-branding.css',
            array(),
            KCTM_VERSION
        );
    }

    /**
     * Inject updated navigation links into the existing side-menu.
     * Only replaces the <a> link items — preserves close button and original structure.
     */
    public function inject_navigation_menu() {
        if ( is_admin() ) {
            return;
        }
        $base = esc_url( home_url( '/product-category/' ) );
        $home = esc_url( home_url( '/' ) );
        ?>
        <script id="kctm-nav-menu">
        (function(){
            var menu = document.querySelector('.side-menu');
            if (!menu) return;

            var base = <?php echo wp_json_encode( $base ); ?>;
            var home = <?php echo wp_json_encode( $home ); ?>;

            /* Collect all existing <a> links (skip the close button) */
            var allLinks = menu.querySelectorAll('a');
            var navLinks = [];
            allLinks.forEach(function(a){
                if (!a.classList.contains('close-btn') && a.textContent.trim() !== '\u00D7') {
                    navLinks.push(a);
                }
            });
            navLinks.forEach(function(a){ a.remove(); });

            /* Helper: create a collapsible section (toggle link + hidden container) */
            function makeAccordion(label, indent) {
                var wrap = document.createElement('div');
                wrap.style.cssText = 'padding-left:' + indent + 'px;';

                var toggle = document.createElement('a');
                toggle.href = 'javascript:void(0)';
                toggle.style.cssText = 'display:flex;justify-content:space-between;align-items:center;cursor:pointer;';
                var span = document.createElement('span');
                span.textContent = label;
                var plus = document.createElement('span');
                plus.textContent = '+';
                plus.style.cssText = 'font-size:1.1em;font-weight:700;transition:transform 0.3s;';
                toggle.appendChild(span);
                toggle.appendChild(plus);

                var panel = document.createElement('div');
                panel.style.cssText = 'max-height:0;overflow:hidden;transition:max-height 0.35s ease;';
                panel.dataset.open = '0';

                /* After expand animation ends, switch to max-height:none so children can grow freely */
                panel.addEventListener('transitionend', function(){
                    if (panel.dataset.open === '1') {
                        panel.style.maxHeight = 'none';
                    }
                });

                toggle.addEventListener('click', function(e){
                    e.preventDefault();
                    if (panel.dataset.open === '1') {
                        /* Collapse: set explicit height first so transition works, then go to 0 */
                        panel.style.maxHeight = panel.scrollHeight + 'px';
                        panel.offsetHeight; /* force reflow */
                        panel.style.maxHeight = '0px';
                        panel.dataset.open = '0';
                        plus.textContent = '+';
                    } else {
                        /* Expand: animate to scrollHeight, transitionend will switch to none */
                        panel.style.maxHeight = panel.scrollHeight + 'px';
                        panel.dataset.open = '1';
                        plus.textContent = '\u2212';
                    }
                });

                wrap.appendChild(toggle);
                wrap.appendChild(panel);
                return { wrap: wrap, panel: panel };
            }

            /* ── Category data ── */
            var menCats = [
                {name:'Suits',slug:'men/suits'},
                {name:'Agbada',slug:'men/agbada'},
                {name:'Kaftan',slug:'men/kaftan'},
                {name:'Dashiki',slug:'men/dashiki'},
                {name:'Shirts',slug:'men/shirts'},
                {name:'T-Shirts',slug:'men/t-shirts'},
                {name:'Trousers',slug:'men/trousers'},
                {name:'Shoes',slug:'men/shoes'},
                {name:'Accessories',slug:'men/accessories'}
            ];
            var womenCats = [
                {name:'Dresses',slug:'women/dresses'},
                {name:'Skirts',slug:'women/skirts'},
                {name:'Blouses',slug:'women/blouses'},
                {name:'Accessories',slug:'women/accessories'}
            ];
            var kidsCats = [
                {name:'Boys',slug:'kids/boys'},
                {name:'Girls',slug:'kids/girls'}
            ];

            /* Helper: add category links into a panel */
            function fillPanel(panel, cats, indent) {
                cats.forEach(function(c){
                    var a = document.createElement('a');
                    a.href = base + c.slug + '/';
                    a.textContent = c.name;
                    a.style.cssText = 'font-size:0.9em;padding-left:' + indent + 'px;';
                    panel.appendChild(a);
                });
            }

            /* ── Build: Shop accordion (level 1) ── */
            var shop = makeAccordion('Shop', 0);

            /* Men accordion (level 2 inside Shop) */
            var men = makeAccordion('Men', 12);
            fillPanel(men.panel, menCats, 24);
            shop.panel.appendChild(men.wrap);

            /* Women accordion (level 2 inside Shop) */
            var women = makeAccordion('Women', 12);
            fillPanel(women.panel, womenCats, 24);
            shop.panel.appendChild(women.wrap);

            /* Kids accordion (level 2 inside Shop) */
            var kids = makeAccordion('Kids', 12);
            fillPanel(kids.panel, kidsCats, 24);
            shop.panel.appendChild(kids.wrap);

            /* ── Append all links to the menu ── */
            var simpleLinks = [
                {text:'Home', href: home},
            ];
            simpleLinks.forEach(function(l){
                var a = document.createElement('a');
                a.href = l.href;
                a.textContent = l.text;
                menu.appendChild(a);
            });

            menu.appendChild(shop.wrap);

            var afterShop = [
                {text:'Custom Wear', href: home + 'product/custom-wear/'},
                {text:'Consultation', href: home + 'consultation/'},
                {text:'About Us', href: home + 'about-us/'},
                {text:'Profile', href: home + 'profile/'},
                {text:'Contact Us', href: home + 'contact-us/'}
            ];
            afterShop.forEach(function(l){
                var a = document.createElement('a');
                a.href = l.href;
                a.textContent = l.text;
                menu.appendChild(a);
            });

            /* Lock body scroll when menu is open */
            var scrollY = 0;
            var observer = new MutationObserver(function() {
                var isOpen = menu.style.display !== 'none' && menu.classList.contains('active') ||
                             window.getComputedStyle(menu).display !== 'none' && menu.offsetWidth > 0;
                if (isOpen && !document.body.classList.contains('menu-open')) {
                    scrollY = window.scrollY;
                    document.body.classList.add('menu-open');
                    document.body.style.top = '-' + scrollY + 'px';
                } else if (!isOpen && document.body.classList.contains('menu-open')) {
                    document.body.classList.remove('menu-open');
                    document.body.style.top = '';
                    window.scrollTo(0, scrollY);
                }
            });
            observer.observe(menu, { attributes: true, attributeFilter: ['style', 'class'] });

            /* Also watch for close button click */
            var closeBtn = menu.querySelector('.close-btn') || menu.querySelector('[class*="close"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    document.body.classList.remove('menu-open');
                    document.body.style.top = '';
                    window.scrollTo(0, scrollY);
                });
            }

            /* Watch menu icon click too */
            document.querySelectorAll('.menu-icon, .menu-text, [class*="hamburger"]').forEach(function(el) {
                el.addEventListener('click', function() {
                    setTimeout(function() {
                        if (menu.style.display !== 'none') {
                            scrollY = window.scrollY;
                            document.body.classList.add('menu-open');
                            document.body.style.top = '-' + scrollY + 'px';
                        }
                    }, 100);
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Inject a cart count badge next to the cart icon in the header.
     * Updates dynamically via WooCommerce AJAX cart fragments.
     */
    public function inject_cart_count_badge() {
        if ( is_admin() ) {
            return;
        }
        ?>
        <style>
        .kctm-cart-badge{position:absolute;top:-6px;right:-6px;background:#c9a96e;color:#402417;font-size:11px;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1;font-family:Arial,sans-serif;pointer-events:none;z-index:10}
        .cart-user a[href*="cart"]{position:relative!important;display:inline-block!important}
        </style>
        <script id="kctm-cart-badge">
        (function(){
            var STORAGE_KEY = 'kctm_cart_count';
            var currentCount = parseInt(localStorage.getItem(STORAGE_KEY)) || 0;

            function ensureBadge() {
                var link = document.querySelector('.cart-user a[href*="cart"]');
                if (!link) return null;
                link.style.position = 'relative';
                var badge = link.querySelector('.kctm-cart-badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'kctm-cart-badge';
                    link.appendChild(badge);
                }
                badge.textContent = currentCount;
                badge.style.display = currentCount > 0 ? 'flex' : 'none';
                return badge;
            }

            function setCount(n) {
                currentCount = Math.max(0, parseInt(n) || 0);
                localStorage.setItem(STORAGE_KEY, currentCount);
                ensureBadge();
            }

            function fetchCartCount() {
                fetch('/?wc-ajax=get_refreshed_fragments', {method:'POST', credentials:'same-origin'})
                    .then(function(r){ return r.json(); })
                    .then(function(d){
                        if (d && d.fragments) {
                            for (var k in d.fragments) {
                                var m = d.fragments[k].match(/(\d+)\s*item/i);
                                if (m) { setCount(m[1]); return; }
                            }
                        }
                        /* If cart_hash is empty, cart is empty */
                        if (d && d.cart_hash === '') setCount(0);
                    }).catch(function(){});
            }

            /* Show stored count immediately, then verify with server */
            function init() {
                ensureBadge();
                fetchCartCount();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            /* Intercept add-to-cart clicks — increment immediately */
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.add_to_cart_button, .single_add_to_cart_button');
                if (btn) {
                    setCount(currentCount + 1);
                    setTimeout(fetchCartCount, 2000);
                }
            }, true);

            /* WC jQuery events */
            if (typeof jQuery !== 'undefined') {
                jQuery(function($){
                    $(document.body).on('added_to_cart', function(){
                        setCount(currentCount + 1);
                        setTimeout(fetchCartCount, 1000);
                    });
                    $(document.body).on('removed_from_cart updated_cart_totals', function(){
                        fetchCartCount();
                    });
                    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function(){
                        fetchCartCount();
                    });
                });
            }

            /* Clear count on order confirmation page */
            if (window.location.pathname.indexOf('order-received') > -1 || document.querySelector('.woocommerce-order-received')) {
                setCount(0);
            }

            /* Re-inject badge if header re-renders */
            var header = document.querySelector('.cart-user');
            if (header) {
                new MutationObserver(function(){
                    if (!document.querySelector('.kctm-cart-badge')) ensureBadge();
                }).observe(header, { childList: true, subtree: true });
            }
        })();
        </script>
        <?php
    }

    /**
     * Inject payment method logos on checkout page.
     */
    public function inject_payment_logos() {
        if ( is_admin() || ! ( is_checkout() || is_page( 'checkout' ) ) ) {
            return;
        }
        ?>
        <style>
        .kctm-pay-icons{display:inline-flex;gap:4px;margin-left:8px;vertical-align:middle}
        .kctm-pay-icons img{height:20px;width:auto;border-radius:3px}
        </style>
        <script id="kctm-payment-logos">
        (function(){
            var logos = {
                'ppcp-gateway': [
                    {src:'https://cdn.jsdelivr.net/gh/nicehash/payment-icons/SVG/paypal-logo.svg',alt:'PayPal'},
                    {src:'https://cdn.jsdelivr.net/gh/nicehash/payment-icons/SVG/visa.svg',alt:'Visa'},
                    {src:'https://cdn.jsdelivr.net/gh/nicehash/payment-icons/SVG/mastercard.svg',alt:'Mastercard'},
                    {src:'https://cdn.jsdelivr.net/gh/nicehash/payment-icons/SVG/amex.svg',alt:'Amex'}
                ],
                'kctm_momo_manual': [
                    {text:'MTN',bg:'#ffcc00',color:'#000'},
                    {text:'Orange',bg:'#ff6600',color:'#fff'}
                ],
                'kctm_campay': [
                    {text:'MTN',bg:'#ffcc00',color:'#000'},
                    {text:'Orange',bg:'#ff6600',color:'#fff'}
                ],
                'cod': [
                    {text:'Cash',bg:'#27ae60',color:'#fff'}
                ]
            };

            function addLogos() {
                document.querySelectorAll('.wc-block-components-radio-control__label, .wc-block-components-payment-method-label').forEach(function(label) {
                    if (label.querySelector('.kctm-pay-icons')) return;
                    var id = '';
                    var radio = label.closest('.wc-block-components-radio-control__option, [class*="payment-method"]');
                    if (radio) {
                        var input = radio.querySelector('input[type="radio"]');
                        if (input) id = input.value;
                    }
                    if (!id) {
                        var text = label.textContent.toLowerCase();
                        if (text.indexOf('paypal') > -1) id = 'ppcp-gateway';
                        else if (text.indexOf('momo') > -1 && text.indexOf('mtn') > -1) id = 'kctm_campay';
                        else if (text.indexOf('momo') > -1) id = 'kctm_momo_manual';
                        else if (text.indexOf('pickup') > -1) id = 'cod';
                    }
                    var iconSet = logos[id];
                    if (!iconSet) return;
                    var wrap = document.createElement('span');
                    wrap.className = 'kctm-pay-icons';
                    iconSet.forEach(function(ic) {
                        if (ic.src) {
                            var img = document.createElement('img');
                            img.src = ic.src; img.alt = ic.alt; img.loading = 'lazy';
                            wrap.appendChild(img);
                        } else if (ic.text) {
                            var sp = document.createElement('span');
                            sp.textContent = ic.text;
                            sp.style.cssText = 'background:' + ic.bg + ';color:' + ic.color + ';font-size:10px;font-weight:700;padding:2px 6px;border-radius:3px;line-height:1.4;';
                            wrap.appendChild(sp);
                        }
                    });
                    label.appendChild(wrap);
                });
            }

            /* Run on load and watch for checkout re-renders */
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function(){ setTimeout(addLogos, 1000); });
            } else {
                setTimeout(addLogos, 1000);
            }
            /* Re-run when payment section updates */
            new MutationObserver(function(){ addLogos(); }).observe(
                document.body, { childList: true, subtree: true }
            );
        })();
        </script>
        <?php
    }

    /**
     * Admin branding — loaded on ALL admin pages.
     */
    public function enqueue_admin_branding() {
        wp_enqueue_style(
            'kctm-admin-branding',
            KCTM_PLUGIN_URL . 'assets/css/kctm-admin-branding.css',
            array(),
            KCTM_VERSION
        );
    }

    /**
     * Admin CSS & JS.
     */
    public function enqueue_admin( $hook ) {
        $kctm_pages = array(
            'toplevel_page_kctm-dashboard',
            'tailoring_page_kctm-customers',
            'tailoring_page_kctm-customer-measurements',
            'tailoring_page_kctm-walkin',
            'tailoring_page_kctm-create-order',
            'tailoring_page_kctm-settings',
            'tailoring_page_kctm-notification-log',
            'tailoring_page_kctm-personalization',
            'tailoring_page_kctm-consultations',
            'tailoring_page_kctm-consultation-settings',
            'tailoring_page_kctm-fabrics',
        );

        if ( ! in_array( $hook, $kctm_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'kctm-admin',
            KCTM_PLUGIN_URL . 'assets/css/kctm-admin.css',
            array(),
            KCTM_VERSION
        );

        /* Select2 for search pickers */
        wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
        wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );

        wp_enqueue_script(
            'kctm-admin-customers',
            KCTM_PLUGIN_URL . 'assets/js/kctm-admin-customers.js',
            array( 'jquery', 'select2' ),
            KCTM_VERSION,
            true
        );

        wp_enqueue_script(
            'kctm-admin-create-order',
            KCTM_PLUGIN_URL . 'assets/js/kctm-admin-create-order.js',
            array( 'jquery', 'select2' ),
            KCTM_VERSION,
            true
        );

        wp_localize_script( 'kctm-admin-customers', 'kctm_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'kctm_admin_nonce' ),
        ) );

        if ( wp_script_is( 'kctm-admin-create-order', 'enqueued' ) ) {
            wp_localize_script( 'kctm-admin-create-order', 'kctm_order', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'kctm_admin_nonce' ),
                'currency' => get_woocommerce_currency_symbol(),
            ) );
        }

        /* Media uploader for personalization images and fabrics */
        if ( strpos( $hook, 'kctm-personalization' ) !== false || strpos( $hook, 'kctm-fabrics' ) !== false ) {
            wp_enqueue_media();
        }

        /* ── Consultation admin assets ──────────────────── */
        if ( strpos( $hook, 'kctm-consultation' ) !== false ) {
            wp_enqueue_style(
                'kctm-admin-consultations',
                KCTM_PLUGIN_URL . 'assets/css/kctm-admin-consultations.css',
                array(),
                KCTM_VERSION
            );
            wp_enqueue_script(
                'kctm-admin-consultations',
                KCTM_PLUGIN_URL . 'assets/js/kctm-admin-consultations.js',
                array( 'jquery' ),
                KCTM_VERSION,
                true
            );
        }
    }

    /**
     * Get SEO data map for pages, products, and categories.
     */
    private static function get_seo_map() {
        return array(
            // Pages.
            5660 => array( 'title' => 'Kevin Cho Tailoring — Custom Suits & African Fashion | Cameroon', 'desc' => 'Premium custom tailoring in Cameroon. Bespoke suits, Agbada, Kaftan, Dashiki and ready-to-wear African-inspired fashion. Book a consultation today.' ),
            5633 => array( 'title' => 'About Kevin Cho Tailoring — Our Story | Cameroon', 'desc' => 'Discover the story behind Kevin Cho Tailoring. Handcrafted African-inspired fashion from Cameroon — bespoke suits, traditional wear, and modern designs.' ),
            5283 => array( 'title' => 'Book a Tailoring Consultation — Kevin Cho | Cameroon', 'desc' => 'Book a personal consultation with Kevin Cho Tailoring. Get measured, choose fabrics, and design your perfect custom outfit. 15,000 FCFA.' ),
            5277 => array( 'title' => 'Contact Kevin Cho Tailoring — Get in Touch | Cameroon', 'desc' => 'Contact Kevin Cho Tailoring in Cameroon. Reach us for custom orders, consultations, or questions about our bespoke African fashion services.' ),
            14   => array( 'title' => 'My Account — Kevin Cho Tailoring', 'desc' => 'Manage your Kevin Cho Tailoring account. View orders, track measurements, and update your profile.' ),
            5599 => array( 'title' => 'Store Manager — Kevin Cho Tailoring', 'desc' => '' ),
            // Products.
            5553 => array( 'title' => 'Custom Wear — Bespoke Tailoring | Kevin Cho Cameroon', 'desc' => 'Order custom-made clothing from Kevin Cho Tailoring. Choose your fabric, style, and measurements for a perfectly fitted outfit. Starting at 25,000 FCFA.' ),
            5464 => array( 'title' => 'Red Double Breasted Tailored Suit | Kevin Cho Cameroon', 'desc' => 'Stand out in this bold red double breasted tailored suit. Custom-fitted, premium fabric. Handcrafted by Kevin Cho Tailoring in Cameroon.' ),
            5467 => array( 'title' => 'Navy Blue Kaftan with Gold Embroidery | Kevin Cho Cameroon', 'desc' => 'Elegant navy blue kaftan with gold feather embroidery. Traditional African style with modern craftsmanship by Kevin Cho Tailoring.' ),
            5473 => array( 'title' => 'Black Embroidered Dashiki with Rhinestones | Kevin Cho', 'desc' => 'Luxurious black embroidered dashiki with rhinestone detailing. Premium African fashion handcrafted by Kevin Cho Tailoring in Cameroon.' ),
            5476 => array( 'title' => 'Teal Green Agbada with Black Embroidery | Kevin Cho', 'desc' => 'Stunning teal green agbada with black embroidered detailing. Traditional African style crafted by Kevin Cho Tailoring in Cameroon.' ),
            5514 => array( 'title' => 'White Two-Tone Agbada | Kevin Cho Tailoring Cameroon', 'desc' => 'Classic white two-tone agbada. Elegant traditional African wear. Perfect for ceremonies and events. By Kevin Cho Tailoring.' ),
            5621 => array( 'title' => 'Book a Consultation — Kevin Cho Tailoring | 15,000 FCFA', 'desc' => 'Book a personal tailoring consultation with Kevin Cho. Get measured, explore fabrics, and design your custom outfit. 15,000 FCFA.' ),
        );
    }

    /**
     * Filter Yoast SEO title for our pages/products.
     */
    public function filter_seo_title( $title ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return $title;
        }
        $map = self::get_seo_map();
        if ( isset( $map[ $post_id ]['title'] ) && ! empty( $map[ $post_id ]['title'] ) ) {
            return $map[ $post_id ]['title'];
        }
        return $title;
    }

    /**
     * Filter Yoast SEO meta description for our pages/products.
     */
    public function filter_seo_desc( $desc ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return $desc;
        }
        $map = self::get_seo_map();
        if ( isset( $map[ $post_id ]['desc'] ) && ! empty( $map[ $post_id ]['desc'] ) ) {
            return $map[ $post_id ]['desc'];
        }
        return $desc;
    }

    /**
     * Ensure billing_city and billing_phone are required at checkout.
     *
     * @param  array $fields WooCommerce billing fields.
     * @return array Modified fields.
     */
    public function require_checkout_fields( $fields ) {
        if ( isset( $fields['billing_city'] ) ) {
            $fields['billing_city']['required'] = true;
        }
        if ( isset( $fields['billing_phone'] ) ) {
            $fields['billing_phone']['required'] = true;
        }
        return $fields;
    }
}
