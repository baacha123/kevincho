<?php
/**
 * Frontend Enhancements — search bar, social share, newsletter, Quick View.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_Frontend_Enhancements {

    public static function init() {
        /* Product search bar — try both WC hook and footer injection for Elementor pages */
        add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'render_product_search' ), 5 );
        add_action( 'wp_footer', array( __CLASS__, 'inject_search_bar_js' ) );

        /* Social share buttons on single product pages */
        add_action( 'woocommerce_share', array( __CLASS__, 'render_social_share' ) );
        add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_social_share' ), 50 );

        /* Newsletter signup in footer */
        add_action( 'wp_footer', array( __CLASS__, 'render_newsletter_popup' ) );

        /* Newsletter AJAX handler */
        add_action( 'wp_ajax_kctm_newsletter_subscribe', array( __CLASS__, 'handle_newsletter_subscribe' ) );
        add_action( 'wp_ajax_nopriv_kctm_newsletter_subscribe', array( __CLASS__, 'handle_newsletter_subscribe' ) );

        /* Enqueue frontend enhancements JS */
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 999 );
    }

    /**
     * Product search bar — renders above the shop product grid.
     */
    public static function render_product_search() {
        $search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        ?>
        <div class="kctm-product-search">
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="hidden" name="post_type" value="product">
                <div class="kctm-search-wrap">
                    <input type="text"
                           name="s"
                           placeholder="Search products..."
                           value="<?php echo esc_attr( $search_query ); ?>"
                           class="kctm-search-input"
                           autocomplete="off">
                    <button type="submit" class="kctm-search-btn" aria-label="Search">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Inject search bar via JS for Elementor-based shop pages where WC hooks don't fire.
     */
    public static function inject_search_bar_js() {
        if ( is_admin() ) {
            return;
        }
        $home = esc_url( home_url( '/' ) );
        ?>
        <script id="kctm-search-inject">
        (function(){
            /* Only inject if not already present (WC hook didn't fire) */
            if (document.querySelector('.kctm-product-search')) return;

            /* Find the shop product grid */
            var target = document.querySelector('.woocommerce-result-count')
                      || document.querySelector('.products.columns-3')
                      || document.querySelector('.products.columns-4')
                      || document.querySelector('ul.products');
            if (!target) return;

            var container = target.parentElement;
            var div = document.createElement('div');
            div.className = 'kctm-product-search';
            div.innerHTML = '<form role="search" method="get" action="<?php echo $home; ?>">'
                + '<input type="hidden" name="post_type" value="product">'
                + '<div class="kctm-search-wrap">'
                + '<input type="text" name="s" placeholder="Search products..." class="kctm-search-input" autocomplete="off">'
                + '<button type="submit" class="kctm-search-btn" aria-label="Search">'
                + '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
                + '</button></div></form>';
            container.insertBefore(div, target);
        })();
        </script>
        <?php
    }

    /**
     * Social share buttons — renders on single product pages.
     */
    public static function render_social_share() {
        if ( ! is_product() ) {
            return;
        }

        global $product;
        $url   = rawurlencode( get_permalink() );
        $title = rawurlencode( get_the_title() );
        $img   = '';
        if ( has_post_thumbnail() ) {
            $img = rawurlencode( get_the_post_thumbnail_url( get_the_ID(), 'large' ) );
        }
        ?>
        <div class="kctm-social-share">
            <span class="kctm-share-label">Share:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url; ?>"
               target="_blank" rel="noopener" class="kctm-share-btn kctm-share-fb" title="Share on Facebook">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
            </a>
            <a href="https://wa.me/?text=<?php echo $title . '%20' . $url; ?>"
               target="_blank" rel="noopener" class="kctm-share-btn kctm-share-wa" title="Share on WhatsApp">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>&text=<?php echo $title; ?>"
               target="_blank" rel="noopener" class="kctm-share-btn kctm-share-tw" title="Share on X">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="mailto:?subject=<?php echo $title; ?>&body=Check%20this%20out:%20<?php echo $url; ?>"
               class="kctm-share-btn kctm-share-email" title="Share via Email">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </a>
            <button class="kctm-share-btn kctm-share-copy" title="Copy link" data-url="<?php echo esc_attr( get_permalink() ); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
        </div>
        <?php
    }

    /**
     * Newsletter floating bar — renders in footer on all frontend pages.
     */
    public static function render_newsletter_popup() {
        if ( is_admin() ) {
            return;
        }
        ?>
        <div id="kctm-newsletter-bar" class="kctm-newsletter-bar">
            <button class="kctm-newsletter-close" aria-label="Close">&times;</button>
            <div class="kctm-newsletter-inner">
                <strong>Stay in Style</strong>
                <span class="kctm-newsletter-text">Get exclusive offers &amp; new arrivals straight to your inbox.</span>
                <form class="kctm-newsletter-form" onsubmit="return false;">
                    <input type="email" class="kctm-newsletter-email" placeholder="Your email address" required>
                    <button type="submit" class="kctm-newsletter-submit">Subscribe</button>
                </form>
                <span class="kctm-newsletter-msg"></span>
            </div>
        </div>
        <?php
    }

    /**
     * Handle newsletter subscription via AJAX.
     * Adds the subscriber to FluentCRM if available, or stores in WP options as fallback.
     */
    public static function handle_newsletter_subscribe() {
        check_ajax_referer( 'kctm_newsletter_nonce', 'nonce' );

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
        }

        /* Try FluentCRM first */
        if ( defined( 'FLUENTCRM' ) && class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
            $subscriber = \FluentCrm\App\Models\Subscriber::where( 'email', $email )->first();
            if ( $subscriber ) {
                if ( $subscriber->status === 'subscribed' ) {
                    wp_send_json_success( array( 'message' => 'You are already subscribed!' ) );
                }
                $subscriber->status = 'subscribed';
                $subscriber->save();
            } else {
                \FluentCrm\App\Models\Subscriber::create( array(
                    'email'  => $email,
                    'status' => 'subscribed',
                    'source' => 'website_newsletter',
                ) );
            }
            wp_send_json_success( array( 'message' => 'Welcome! You have been subscribed.' ) );
        }

        /* Fallback: store in WP option */
        $subscribers = get_option( 'kctm_newsletter_subscribers', array() );
        if ( in_array( $email, $subscribers, true ) ) {
            wp_send_json_success( array( 'message' => 'You are already subscribed!' ) );
        }
        $subscribers[] = $email;
        update_option( 'kctm_newsletter_subscribers', $subscribers );
        wp_send_json_success( array( 'message' => 'Welcome! You have been subscribed.' ) );
    }

    /**
     * Enqueue the enhancement assets.
     */
    public static function enqueue_assets() {
        if ( is_admin() ) {
            return;
        }

        wp_enqueue_script(
            'kctm-frontend-enhancements',
            KCTM_PLUGIN_URL . 'assets/js/kctm-frontend-enhancements.js',
            array(),
            KCTM_VERSION,
            true
        );

        wp_localize_script( 'kctm-frontend-enhancements', 'kctm_enhancements', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'kctm_newsletter_nonce' ),
        ) );
    }
}
