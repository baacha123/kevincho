<?php
/**
 * Store Manager Portal — Full-page App Template
 *
 * Completely replaces the WordPress theme.
 * If the user is NOT logged in, renders a login screen.
 * If logged in + authorized, renders the SPA shell.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_logged_in = is_user_logged_in();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kevin Cho &mdash; Store Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php
    wp_enqueue_style(
        'kctm-portal',
        KCTM_PLUGIN_URL . 'assets/css/kctm-portal.css',
        array(),
        KCTM_VERSION
    );
    wp_print_styles( 'kctm-portal' );
    ?>
</head>
<body class="kctm-portal<?php echo $is_logged_in ? ' logged-in' : ' logged-out'; ?>">

<?php if ( ! $is_logged_in ) : ?>
    <!-- ═══════════ LOGIN SCREEN ═══════════ -->
    <div class="portal-login-wrap">
        <div class="portal-login-card">
            <img src="<?php echo esc_url( KCTM_PLUGIN_URL . 'assets/images/logo-dark.png' ); ?>" alt="Kevin Cho" class="portal-login-logo-img">
            <p class="portal-login-subtitle">Store Manager Portal</p>

            <form id="portal-login-form" autocomplete="on">
                <div class="portal-field">
                    <label for="portal-username">Username or Email</label>
                    <input type="text" id="portal-username" name="username" autocomplete="username" required>
                </div>
                <div class="portal-field">
                    <label for="portal-password">Password</label>
                    <input type="password" id="portal-password" name="password" autocomplete="current-password" required>
                </div>
                <div id="portal-login-error" class="portal-error" style="display:none;"></div>
                <button type="submit" class="portal-btn portal-btn-primary portal-btn-block" id="portal-login-btn">
                    Sign In
                </button>
                <p class="portal-login-forgot">
                    <a href="<?php echo esc_url( wp_lostpassword_url( home_url( '/store-manager/' ) ) ); ?>">
                        Forgot your password?
                    </a>
                </p>
            </form>
        </div>
    </div>

    <script>
    (function(){
        var form = document.getElementById('portal-login-form');
        var btn  = document.getElementById('portal-login-btn');
        var err  = document.getElementById('portal-login-error');

        form.addEventListener('submit', function(e){
            e.preventDefault();
            btn.disabled = true;
            btn.textContent = 'Signing in...';
            err.style.display = 'none';

            var fd = new FormData();
            fd.append('action', 'kctm_portal_login');
            fd.append('nonce', '<?php echo esc_js( wp_create_nonce( 'kctm_portal_nonce' ) ); ?>');
            fd.append('username', document.getElementById('portal-username').value);
            fd.append('password', document.getElementById('portal-password').value);

            fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.success) {
                    window.location.href = res.data.redirect;
                } else {
                    err.textContent = res.data.message;
                    err.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                }
            })
            .catch(function(){
                err.textContent = 'Connection error. Please try again.';
                err.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Sign In';
            });
        });
    })();
    </script>

<?php else : ?>
    <!-- ═══════════ APP SHELL ═══════════ -->

    <!-- Mobile header -->
    <header class="portal-mobile-header">
        <button class="portal-hamburger" id="portal-hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <img src="<?php echo esc_url( KCTM_PLUGIN_URL . 'assets/images/logo-light.png' ); ?>" alt="Kevin Cho" class="portal-mobile-logo-img">
        <div class="portal-mobile-avatar"><?php echo esc_html( mb_substr( wp_get_current_user()->display_name, 0, 1 ) ); ?></div>
    </header>

    <!-- Sidebar overlay (mobile) -->
    <div class="portal-overlay" id="portal-overlay"></div>

    <!-- Sidebar -->
    <nav class="portal-sidebar" id="portal-sidebar">
        <div class="portal-sidebar-header">
            <img src="<?php echo esc_url( KCTM_PLUGIN_URL . 'assets/images/logo-light.png' ); ?>" alt="Kevin Cho" class="portal-sidebar-logo-img">
        </div>

        <ul class="portal-nav">
            <li class="portal-nav-label">Main</li>
            <li><a href="#dashboard" data-section="dashboard" class="active">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a></li>
            <li><a href="#analytics" data-section="analytics">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Analytics
            </a></li>
            <li><a href="#orders" data-section="orders">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                Orders
            </a></li>
            <li><a href="#production" data-section="production">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                Production
            </a></li>

            <li class="portal-nav-label">People</li>
            <li><a href="#customers" data-section="customers">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                Customers
            </a></li>
            <li><a href="#staff" data-section="staff">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Staff
            </a></li>
            <li><a href="#drivers" data-section="drivers">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Drivers
            </a></li>
            <li><a href="#consultations" data-section="consultations">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Consultations
            </a></li>

            <li class="portal-nav-label">Catalog</li>
            <li><a href="#products" data-section="products">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Products
            </a></li>
            <li><a href="#coupons" data-section="coupons">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 5H3a2 2 0 00-2 2v4a2 2 0 002 2 2 2 0 000 4H3a2 2 0 00-2 2v4a2 2 0 002 2h18a2 2 0 002-2v-4a2 2 0 00-2-2 2 2 0 010-4 2 2 0 002-2V7a2 2 0 00-2-2z"/></svg>
                Coupons
            </a></li>
            <li><a href="#fabrics" data-section="fabrics">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2"/><path d="M2 12h20"/><path d="M12 2v20"/></svg>
                Fabrics
            </a></li>

            <li class="portal-nav-label">Business</li>
            <li><a href="#email" data-section="email">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Send Email
            </a></li>
            <li><a href="#notifications" data-section="notifications">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                Notifications
            </a></li>
            <li><a href="#invoices" data-section="invoices">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Invoices
            </a></li>
            <li><a href="#expenses" data-section="expenses">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                Expenses
            </a></li>
            <li><a href="#settings" data-section="settings">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                Settings
            </a></li>
        </ul>

        <div class="portal-sidebar-footer">
            <div class="portal-user-info">
                <div class="portal-avatar"><?php echo esc_html( mb_substr( wp_get_current_user()->display_name, 0, 1 ) ); ?></div>
                <div class="portal-user-name"><?php echo esc_html( wp_get_current_user()->display_name ); ?></div>
            </div>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/store-manager/' ) ) ); ?>" class="portal-logout">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="portal-main" id="portal-content">
        <div class="portal-loading" id="portal-loading">
            <div class="portal-spinner"></div>
            <p>Loading...</p>
        </div>
    </main>

    <!-- Toast container -->
    <div class="portal-toast-container" id="portal-toasts"></div>

    <!-- Modal container -->
    <div class="portal-modal-overlay" id="portal-modal" style="display:none;">
        <div class="portal-modal-content" id="portal-modal-content"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    <?php
    wp_enqueue_script(
        'kctm-portal',
        KCTM_PLUGIN_URL . 'assets/js/kctm-portal.js',
        array(),
        KCTM_VERSION,
        true
    );
    /* Determine user role for JS. */
    $kctm_user      = wp_get_current_user();
    $kctm_user_role = 'manager';
    if ( in_array( 'kctm_tailor', (array) $kctm_user->roles, true ) && ! current_user_can( 'manage_woocommerce' ) ) {
        $kctm_user_role = 'tailor';
    }
    /* Lookup staff ID for this WP user. */
    $kctm_staff_id = 0;
    if ( $kctm_user->ID ) {
        global $wpdb;
        $kctm_staff_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}kctm_staff WHERE user_id = %d",
            $kctm_user->ID
        ) );
    }
    wp_localize_script( 'kctm-portal', 'KCTM_Portal', array(
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'kctm_portal_nonce' ),
        'currency'  => get_woocommerce_currency_symbol( get_option( 'woocommerce_currency' ) ) . ' ',
        'home_url'  => home_url( '/' ),
        'user_name' => $kctm_user->display_name,
        'user_role' => $kctm_user_role,
        'staff_id'  => $kctm_staff_id,
    ) );
    wp_print_scripts( 'kctm-portal' );
    ?>

<?php endif; ?>

</body>
</html>
