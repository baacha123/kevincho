<?php
/**
 * Multi-Currency System — Geolocation + Exchange Rates + Price Conversion
 *
 * Detects visitor country via IP, shows prices in local currency,
 * and routes payment gateways accordingly.
 *
 * Base currency: USD (WooCommerce store default)
 * Supported display currencies: USD, EUR, GBP, XAF, CAD, NGN
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_Currency {

    /** @var string Base store currency */
    const BASE = 'XAF';

    /** @var string Transient key for cached rates */
    const RATES_TRANSIENT = 'kctm_exchange_rates';

    /** @var string Cookie name for selected currency */
    const COOKIE = 'kctm_currency';

    /** @var int Cache duration in seconds (24 hours) */
    const CACHE_TTL = 86400;

    /** @var array Country → Currency mapping */
    private static $country_currency = array(
        'US' => 'USD', 'PR' => 'USD', 'GU' => 'USD', 'VI' => 'USD',
        'GB' => 'GBP', 'IM' => 'GBP', 'JE' => 'GBP', 'GG' => 'GBP',
        'CM' => 'XAF', 'CF' => 'XAF', 'TD' => 'XAF', 'CG' => 'XAF',
        'GA' => 'XAF', 'GQ' => 'XAF',
        'NG' => 'NGN',
        'CA' => 'CAD',
        'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
        'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'PT' => 'EUR',
        'IE' => 'EUR', 'FI' => 'EUR', 'GR' => 'EUR', 'LU' => 'EUR',
        'SK' => 'EUR', 'SI' => 'EUR', 'EE' => 'EUR', 'LV' => 'EUR',
        'LT' => 'EUR', 'CY' => 'EUR', 'MT' => 'EUR', 'HR' => 'EUR',
    );

    /** @var array Supported currencies with symbols and names */
    private static $currencies = array(
        'USD' => array( 'symbol' => '$',    'name' => 'US Dollar',      'flag' => '🇺🇸' ),
        'EUR' => array( 'symbol' => '€',    'name' => 'Euro',           'flag' => '🇪🇺' ),
        'GBP' => array( 'symbol' => '£',    'name' => 'British Pound',  'flag' => '🇬🇧' ),
        'XAF' => array( 'symbol' => 'FCFA', 'name' => 'CFA Franc',     'flag' => '🇨🇲' ),
        'CAD' => array( 'symbol' => 'C$',   'name' => 'Canadian Dollar','flag' => '🇨🇦' ),
        'NGN' => array( 'symbol' => '₦',    'name' => 'Nigerian Naira', 'flag' => '🇳🇬' ),
    );

    /** @var string|null Resolved currency for this request */
    private static $active_currency = null;

    /** @var array|null Cached rates for this request */
    private static $rates = null;

    /**
     * Initialize hooks.
     */
    public static function init() {
        // Register query var so WordPress doesn't strip it.
        add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );

        // Detect currency early.
        add_action( 'init', array( __CLASS__, 'resolve_currency' ), 1 );

        // Handle manual currency switch.
        add_action( 'init', array( __CLASS__, 'handle_switch' ), 2 );

        // Filter WooCommerce currency.
        add_filter( 'woocommerce_currency', array( __CLASS__, 'filter_currency' ), 99 );

        // Filter product prices for display.
        add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'convert_price' ), 99, 2 );
        add_filter( 'woocommerce_product_get_regular_price', array( __CLASS__, 'convert_price' ), 99, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'convert_sale_price' ), 99, 2 );

        // Filter variation prices.
        add_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'convert_price' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_regular_price', array( __CLASS__, 'convert_price' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_sale_price', array( __CLASS__, 'convert_sale_price' ), 99, 2 );

        // Variable product price range.
        add_filter( 'woocommerce_variation_prices', array( __CLASS__, 'convert_variation_prices' ), 99, 3 );

        // Zero decimals for XAF and NGN.
        add_filter( 'wc_get_price_decimals', array( __CLASS__, 'filter_decimals' ), 99 );

        // Currency switcher in frontend.
        add_action( 'wp_head', array( __CLASS__, 'output_switcher_css' ) );
        add_action( 'wp_footer', array( __CLASS__, 'output_switcher_html' ) );
        add_action( 'wp_footer', array( __CLASS__, 'output_switcher_js' ) );

        // Gateway visibility.
        add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_gateways' ), 99 );

        // Force PayPal to use USD for cart/order totals.
        add_filter( 'woocommerce_paypal_args', array( __CLASS__, 'convert_paypal_args' ), 99 );

        // AJAX for rate refresh (admin).
        add_action( 'wp_ajax_kctm_refresh_rates', array( __CLASS__, 'ajax_refresh_rates' ) );

        // Cron to refresh rates daily.
        add_action( 'kctm_refresh_exchange_rates', array( __CLASS__, 'fetch_rates' ) );
        if ( ! wp_next_scheduled( 'kctm_refresh_exchange_rates' ) ) {
            wp_schedule_event( time(), 'daily', 'kctm_refresh_exchange_rates' );
        }
    }

    /* ──────────────────────────────────────────────
     * Currency Resolution
     * ────────────────────────────────────────────── */

    /**
     * Register 'currency' as a valid WordPress query variable.
     *
     * @param array $vars
     * @return array
     */
    public static function register_query_var( $vars ) {
        $vars[] = 'currency';
        return $vars;
    }

    /**
     * Resolve the active currency for this request.
     */
    public static function resolve_currency() {
        // 1. Cookie override (user manually selected or previously detected).
        if ( ! empty( $_COOKIE[ self::COOKIE ] ) ) {
            $cookie_currency = strtoupper( sanitize_text_field( $_COOKIE[ self::COOKIE ] ) );
            if ( isset( self::$currencies[ $cookie_currency ] ) ) {
                self::$active_currency = $cookie_currency;
                return;
            }
        }

        // 2. Geolocation — detect and persist in cookie so it stays consistent.
        $country = self::detect_country();
        if ( $country && isset( self::$country_currency[ $country ] ) ) {
            self::$active_currency = self::$country_currency[ $country ];
            // Set cookie so subsequent page loads don't re-detect.
            if ( ! headers_sent() ) {
                setcookie( self::COOKIE, self::$active_currency, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
                $_COOKIE[ self::COOKIE ] = self::$active_currency;
            }
            return;
        }

        // 3. Default to base.
        self::$active_currency = self::BASE;
    }

    /**
     * Handle ?currency=XXX switch.
     */
    public static function handle_switch() {
        if ( ! isset( $_GET['currency'] ) ) {
            return;
        }

        $currency = strtoupper( sanitize_text_field( $_GET['currency'] ) );
        if ( ! isset( self::$currencies[ $currency ] ) ) {
            return;
        }

        self::$active_currency = $currency;
        setcookie( self::COOKIE, $currency, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
        $_COOKIE[ self::COOKIE ] = $currency;
    }

    /**
     * Detect visitor's country code from IP.
     *
     * Uses ip-api.com (free, no key needed, 45 req/min).
     * Falls back to CloudFlare header if available.
     *
     * @return string|false Two-letter country code or false.
     */
    private static function detect_country() {
        // CloudFlare header (fastest, no external call).
        if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
            return strtoupper( sanitize_text_field( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
        }

        // Check WooCommerce geolocation if available.
        if ( class_exists( 'WC_Geolocation' ) ) {
            $geo = WC_Geolocation::geolocate_ip();
            if ( ! empty( $geo['country'] ) ) {
                return $geo['country'];
            }
        }

        // Cache per IP in transient to avoid hitting API on every page load.
        $ip = self::get_visitor_ip();
        if ( ! $ip || $ip === '127.0.0.1' ) {
            return false;
        }

        $cache_key = 'kctm_geo_' . md5( $ip );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) {
            return $cached ?: false;
        }

        $response = wp_remote_get( 'http://ip-api.com/json/' . $ip . '?fields=countryCode', array(
            'timeout' => 3,
        ) );

        if ( is_wp_error( $response ) ) {
            set_transient( $cache_key, '', 3600 ); // Cache failure for 1 hour.
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = isset( $body['countryCode'] ) ? strtoupper( $body['countryCode'] ) : '';

        set_transient( $cache_key, $code, DAY_IN_SECONDS );

        return $code ?: false;
    }

    /**
     * Get visitor IP address.
     *
     * @return string
     */
    private static function get_visitor_ip() {
        $headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );
        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( $_SERVER[ $header ] );
                // X-Forwarded-For can be comma-separated; take first.
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }
        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
    }

    /* ──────────────────────────────────────────────
     * Exchange Rates
     * ────────────────────────────────────────────── */

    /**
     * Get all exchange rates (base USD).
     *
     * @return array Currency code => rate.
     */
    public static function get_rates() {
        if ( self::$rates !== null ) {
            return self::$rates;
        }

        $cached = get_transient( self::RATES_TRANSIENT );
        if ( $cached && is_array( $cached ) ) {
            self::$rates = $cached;
            return self::$rates;
        }

        self::$rates = self::fetch_rates();
        return self::$rates;
    }

    /**
     * Fetch fresh rates from API and cache them.
     *
     * Uses exchangerate-api.com free tier (1500 req/month).
     *
     * @return array
     */
    public static function fetch_rates() {
        $response = wp_remote_get(
            'https://open.er-api.com/v6/latest/XAF',
            array( 'timeout' => 10 )
        );

        if ( is_wp_error( $response ) ) {
            return self::fallback_rates();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['rates'] ) ) {
            return self::fallback_rates();
        }

        $rates = array( 'XAF' => 1.0 );
        foreach ( array_keys( self::$currencies ) as $code ) {
            if ( isset( $body['rates'][ $code ] ) ) {
                $rates[ $code ] = floatval( $body['rates'][ $code ] );
            }
        }

        set_transient( self::RATES_TRANSIENT, $rates, self::CACHE_TTL );
        self::$rates = $rates;

        return $rates;
    }

    /**
     * Fallback rates in case API is unreachable.
     *
     * @return array
     */
    private static function fallback_rates() {
        // Rates relative to 1 XAF.
        return array(
            'XAF' => 1.0,
            'USD' => 0.00165,
            'EUR' => 0.00152,
            'GBP' => 0.00131,
            'CAD' => 0.00225,
            'NGN' => 2.56,
        );
    }

    /**
     * Get the conversion rate from base to target currency.
     *
     * @param string $target Target currency code.
     * @return float
     */
    public static function get_rate( $target = null ) {
        if ( $target === null ) {
            $target = self::get_active_currency();
        }
        if ( $target === self::BASE ) {
            return 1.0;
        }
        $rates = self::get_rates();
        return isset( $rates[ $target ] ) ? $rates[ $target ] : 1.0;
    }

    /* ──────────────────────────────────────────────
     * Price Conversion Filters
     * ────────────────────────────────────────────── */

    /**
     * Get the active display currency.
     *
     * @return string
     */
    public static function get_active_currency() {
        if ( self::$active_currency === null ) {
            self::resolve_currency();
        }
        return self::$active_currency;
    }

    /**
     * Use zero decimals for currencies that don't have subunits.
     *
     * @param int $decimals
     * @return int
     */
    public static function filter_decimals( $decimals ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $decimals;
        }
        $active = self::get_active_currency();
        if ( in_array( $active, array( 'XAF', 'NGN' ), true ) ) {
            return 0;
        }
        return $decimals;
    }

    /**
     * Filter WooCommerce currency symbol.
     *
     * @param string $currency
     * @return string
     */
    public static function filter_currency( $currency ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            /* PayPal settings page needs to see USD so it can connect */
            if ( isset( $_GET['section'] ) && strpos( $_GET['section'], 'ppcp' ) !== false ) {
                return 'USD';
            }
            return $currency;
        }

        /* During PayPal checkout/API calls, force USD (PayPal doesn't support XAF) */
        if ( self::is_paypal_context() ) {
            return 'USD';
        }

        return self::get_active_currency();
    }

    /**
     * Check if current request is a PayPal payment context.
     */
    private static function is_paypal_context() {
        /* PayPal REST API callbacks */
        if ( isset( $_GET['ppcp-listener'] ) || isset( $_GET['ppc-webhook'] ) ) {
            return true;
        }
        /* PayPal AJAX order creation */
        if ( wp_doing_ajax() ) {
            $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
            if ( strpos( $action, 'ppcp' ) !== false || strpos( $action, 'ppc-' ) !== false ) {
                return true;
            }
        }
        /* PayPal order-pay endpoint */
        if ( isset( $_POST['payment_method'] ) && strpos( $_POST['payment_method'], 'ppcp' ) !== false ) {
            return true;
        }
        return false;
    }

    /**
     * Convert a product price.
     *
     * @param string $price
     * @param object $product
     * @return string
     */
    public static function convert_price( $price, $product = null ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $price;
        }
        if ( $price === '' || $price === null ) {
            return $price;
        }
        $active = self::get_active_currency();
        if ( $active === self::BASE ) {
            return $price;
        }
        return (string) round( floatval( $price ) * self::get_rate( $active ), 2 );
    }

    /**
     * Convert sale price (only if set).
     *
     * @param string $price
     * @param object $product
     * @return string
     */
    public static function convert_sale_price( $price, $product = null ) {
        if ( $price === '' || $price === null ) {
            return $price;
        }
        return self::convert_price( $price, $product );
    }

    /**
     * Convert variation price arrays.
     *
     * @param array  $prices
     * @param object $product
     * @param bool   $for_display
     * @return array
     */
    public static function convert_variation_prices( $prices, $product, $for_display ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $prices;
        }
        $active = self::get_active_currency();
        if ( $active === self::BASE ) {
            return $prices;
        }

        $rate = self::get_rate( $active );
        foreach ( $prices as $type => &$type_prices ) {
            foreach ( $type_prices as $id => &$p ) {
                $p = (string) round( floatval( $p ) * $rate, 2 );
            }
        }
        return $prices;
    }

    /* ──────────────────────────────────────────────
     * Payment Gateway Routing
     * ────────────────────────────────────────────── */

    /**
     * Show/hide gateways based on active currency.
     *
     * XAF → show MoMo, hide PayPal card gateways (keep PayPal for flexibility).
     * Other currencies → hide MoMo, show PayPal.
     *
     * @param array $gateways
     * @return array
     */
    public static function filter_gateways( $gateways ) {
        $active = self::get_active_currency();

        if ( $active === 'XAF' ) {
            // Cameroon: show Manual MoMo + Pay on Pickup. Hide PayPal (doesn't support XAF) and CamPay (pending Go Live).
            unset( $gateways['ppcp-gateway'] );
            unset( $gateways['ppcp-credit-card-gateway'] );
            unset( $gateways['ppcp-googlepay'] );
            unset( $gateways['ppcp-applepay'] );
            unset( $gateways['kctm_campay'] );
        } else {
            // International: show PayPal only. Hide MoMo, CamPay, and Pay on Pickup.
            unset( $gateways['momo'] );
            unset( $gateways['kctm_momo_manual'] );
            unset( $gateways['campay'] );
            unset( $gateways['kctm_campay'] );
            unset( $gateways['cod'] );
        }

        return $gateways;
    }

    /* ──────────────────────────────────────────────
     * Currency Switcher (Frontend Widget)
     * ────────────────────────────────────────────── */

    /**
     * Output switcher CSS.
     */
    public static function output_switcher_css() {
        if ( is_admin() ) {
            return;
        }
        // Don't show on portal pages.
        if ( is_page( 'store-manager' ) ) {
            return;
        }
        ?>
        <style id="kctm-currency-switcher">
        .kctm-cs{position:fixed;bottom:20px;left:20px;z-index:9999;font-family:Inter,system-ui,sans-serif}
        .kctm-cs-btn{display:flex;align-items:center;gap:6px;background:#402417;color:#fff;border:none;
            padding:8px 14px;border-radius:24px;cursor:pointer;font-size:13px;font-weight:500;
            box-shadow:0 2px 12px rgba(0,0,0,.2);transition:all .2s}
        .kctm-cs-btn:hover{background:#5a3828;transform:translateY(-1px)}
        .kctm-cs-flag{font-size:18px;line-height:1}
        .kctm-cs-code{letter-spacing:.5px}
        .kctm-cs-drop{display:none;position:absolute;bottom:48px;left:0;background:#fff;
            border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.15);overflow:hidden;min-width:200px}
        .kctm-cs-drop.open{display:block}
        .kctm-cs-opt{display:flex;align-items:center;gap:10px;padding:10px 16px;cursor:pointer;
            font-size:13px;color:#333;transition:background .15s;border:none;background:none;width:100%;text-align:left}
        .kctm-cs-opt:hover{background:#fef9e7}
        .kctm-cs-opt.active{background:#fef9e7;font-weight:600;color:#402417}
        .kctm-cs-opt-flag{font-size:18px}
        .kctm-cs-opt-name{flex:1}
        .kctm-cs-opt-code{color:#999;font-size:12px;font-weight:500}
        </style>
        <?php
    }

    /**
     * Output switcher HTML.
     */
    public static function output_switcher_html() {
        if ( is_admin() ) {
            return;
        }
        if ( is_page( 'store-manager' ) ) {
            return;
        }

        $active = self::get_active_currency();
        $active_info = self::$currencies[ $active ];
        ?>
        <div class="kctm-cs" id="kctm-currency-switcher">
            <button class="kctm-cs-btn" id="kctm-cs-toggle" aria-label="Change currency">
                <span class="kctm-cs-flag"><?php echo esc_html( $active_info['flag'] ); ?></span>
                <span class="kctm-cs-code"><?php echo esc_html( $active ); ?></span>
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5l3-3 3 3"/></svg>
            </button>
            <div class="kctm-cs-drop" id="kctm-cs-dropdown">
                <?php foreach ( self::$currencies as $code => $info ) : ?>
                <button class="kctm-cs-opt<?php echo $code === $active ? ' active' : ''; ?>"
                        data-currency="<?php echo esc_attr( $code ); ?>">
                    <span class="kctm-cs-opt-flag"><?php echo esc_html( $info['flag'] ); ?></span>
                    <span class="kctm-cs-opt-name"><?php echo esc_html( $info['name'] ); ?></span>
                    <span class="kctm-cs-opt-code"><?php echo esc_html( $code ); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Output switcher JavaScript.
     */
    public static function output_switcher_js() {
        if ( is_admin() ) {
            return;
        }
        if ( is_page( 'store-manager' ) ) {
            return;
        }
        ?>
        <script id="kctm-currency-switcher-js">
        (function(){
            var toggle = document.getElementById('kctm-cs-toggle');
            var dropdown = document.getElementById('kctm-cs-dropdown');
            if (!toggle || !dropdown) return;

            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('open');
            });

            document.addEventListener('click', function() {
                dropdown.classList.remove('open');
            });

            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            var opts = dropdown.querySelectorAll('.kctm-cs-opt');
            for (var i = 0; i < opts.length; i++) {
                opts[i].addEventListener('click', function() {
                    var code = this.getAttribute('data-currency');
                    var url = new URL(window.location.href);
                    url.searchParams.set('currency', code);
                    window.location.href = url.toString();
                });
            }
        })();
        </script>
        <?php
    }

    /* ──────────────────────────────────────────────
     * Admin AJAX — Manual Rate Refresh
     * ────────────────────────────────────────────── */

    /**
     * AJAX handler to refresh exchange rates.
     */
    public static function ajax_refresh_rates() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        delete_transient( self::RATES_TRANSIENT );
        $rates = self::fetch_rates();
        wp_send_json_success( array( 'rates' => $rates ) );
    }

    /* ──────────────────────────────────────────────
     * Public Helpers
     * ────────────────────────────────────────────── */

    /**
     * Get all supported currencies.
     *
     * @return array
     */
    public static function get_supported_currencies() {
        return self::$currencies;
    }

    /**
     * Format a price in a specific currency.
     *
     * @param float  $amount Amount in base currency (USD).
     * @param string $currency Target currency code.
     * @return string Formatted price string.
     */
    public static function format_price( $amount, $currency = null ) {
        if ( $currency === null ) {
            $currency = self::get_active_currency();
        }
        $rate      = self::get_rate( $currency );
        $converted = round( $amount * $rate, 2 );
        $info      = isset( self::$currencies[ $currency ] ) ? self::$currencies[ $currency ] : self::$currencies['USD'];

        if ( $currency === 'XAF' || $currency === 'NGN' ) {
            return $info['symbol'] . ' ' . number_format( $converted, 0, '.', ',' );
        }
        return $info['symbol'] . number_format( $converted, 2, '.', ',' );
    }
}
