<?php
/**
 * Store Manager Portal — AJAX Endpoints
 *
 * All data endpoints for the portal SPA.
 * Every handler checks manage_woocommerce + nonce.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_Portal_Ajax {

    /** Nonce action shared with the portal JS. */
    const NONCE_ACTION = 'kctm_portal_nonce';

    /**
     * Register AJAX actions.
     */
    public static function init() {
        $endpoints = array(
            'kctm_portal_dashboard',
            'kctm_portal_orders',
            'kctm_portal_update_order_status',
            'kctm_portal_customers',
            'kctm_portal_customer_detail',
            'kctm_portal_create_walkin',
            'kctm_portal_create_instore_order',
            'kctm_portal_edit_order_items',
            'kctm_portal_save_customer_notes',
            'kctm_portal_save_measurements',
            'kctm_portal_consultations',
            'kctm_portal_update_consultation',
            'kctm_portal_notifications',
            'kctm_portal_send_email',
            'kctm_portal_invoice_url',
            'kctm_portal_products',
            'kctm_portal_product_detail',
            'kctm_portal_save_product',
            'kctm_portal_delete_product',
            'kctm_portal_bulk_products',
            'kctm_portal_upload_product_image',
            'kctm_portal_delete_product_image',
            'kctm_portal_create_category',
            'kctm_portal_product_terms',
            'kctm_portal_save_variations',
            'kctm_portal_delete_variation',
            'kctm_portal_product_analytics',
            /* Coupons */
            'kctm_portal_coupons',
            'kctm_portal_save_coupon',
            'kctm_portal_delete_coupon',
            /* Reviews */
            'kctm_portal_reviews',
            'kctm_portal_update_review',
            'kctm_portal_review_stats',
            /* Customer Tags */
            'kctm_portal_customer_tags',
            'kctm_portal_bulk_customer_tags',
            /* Settings */
            'kctm_portal_get_settings',
            'kctm_portal_save_settings',
            /* WhatsApp Templates */
            'kctm_portal_whatsapp_templates',
            'kctm_portal_save_whatsapp_template',
            'kctm_portal_delete_whatsapp_template',
            'kctm_portal_send_whatsapp_test',
            /* Expenses */
            'kctm_portal_expenses',
            'kctm_portal_save_expense',
            'kctm_portal_delete_expense',
            'kctm_portal_expense_summary',
            /* Staff */
            'kctm_portal_staff',
            'kctm_portal_save_staff',
            'kctm_portal_delete_staff',
            'kctm_portal_staff_workload',
            /* Abandoned Carts */
            'kctm_portal_abandoned_carts',
            'kctm_portal_send_cart_reminder',
            'kctm_portal_delete_abandoned_cart',
            /* Production Board */
            'kctm_portal_production_board',
            'kctm_portal_update_production_stage',
            'kctm_portal_assign_tailor',
            /* Fabric Inventory */
            'kctm_portal_fabrics_list',
            'kctm_portal_save_fabric',
            'kctm_portal_adjust_fabric_stock',
            /* Shipping Tracker */
            'kctm_portal_save_tracking',
            'kctm_portal_send_tracking_notification',
            /* Calendar */
            'kctm_portal_calendar_data',
            'kctm_portal_block_date',
            'kctm_portal_unblock_date',
            /* Order Notes & Assignment */
            'kctm_portal_order_notes',
            'kctm_portal_add_order_note',
            'kctm_portal_unassign_tailor',
            /* Analytics */
            'kctm_portal_analytics',
            /* Export */
            'kctm_portal_export_data',
            /* Drivers */
            'kctm_portal_drivers',
            'kctm_portal_save_driver',
            'kctm_portal_delete_driver',
            'kctm_portal_assign_driver',
            /* Tailor Portal */
            'kctm_portal_tailor_dashboard',
            /* AI Rewrite */
            'kctm_portal_ai_rewrite',
        );

        foreach ( $endpoints as $action ) {
            $method = str_replace( 'kctm_portal_', '', $action );
            add_action( 'wp_ajax_' . $action, array( __CLASS__, $method ) );
        }
    }

    /* ── helpers ──────────────────────────────────────── */

    private static function check_access() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }
    }

    /**
     * Looser access check — allows tailors with kctm_view_portal cap.
     */
    private static function check_tailor_access() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'kctm_view_portal' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }
    }

    /**
     * Is current user a tailor (not a manager/admin)?
     */
    private static function is_tailor() {
        return current_user_can( 'kctm_view_portal' ) && ! current_user_can( 'manage_woocommerce' );
    }

    /**
     * Get the staff table ID for the current logged-in user.
     */
    private static function get_current_staff_id() {
        global $wpdb;
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return 0;
        }
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}kctm_staff WHERE user_id = %d",
            $user_id
        ) );
    }

    private static function get_param( $key, $default = '' ) {
        if ( isset( $_POST[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
        }
        if ( isset( $_GET[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
        }
        return $default;
    }

    private static function get_int( $key, $default = 0 ) {
        return absint( self::get_param( $key, $default ) );
    }

    /**
     * Convert a price from the visitor's active currency back to base (USD).
     * The portal displays prices in the visitor's currency, so when saving
     * we need to reverse the conversion.
     */
    private static function revert_price_to_base( $price ) {
        if ( '' === $price || null === $price ) {
            return $price;
        }
        if ( ! class_exists( 'KCTM_Currency' ) ) {
            return $price;
        }
        $active = KCTM_Currency::get_active_currency();
        if ( $active === KCTM_Currency::BASE ) {
            return $price;
        }
        $rate = KCTM_Currency::get_rate( $active );
        if ( $rate <= 0 ) {
            return $price;
        }
        return (string) round( floatval( $price ) / $rate, 2 );
    }

    /* ================================================================
     * Dashboard
     * ============================================================= */

    public static function dashboard() {
        self::check_access();

        /* Customer count */
        $customers = count_users();
        $customer_count = isset( $customers['avail_roles']['customer'] ) ? $customers['avail_roles']['customer'] : 0;

        /* Orders today */
        $today = date( 'Y-m-d' );
        $orders_today = wc_get_orders( array(
            'limit'        => -1,
            'return'       => 'ids',
            'date_created' => $today . '...' . $today . ' 23:59:59',
        ) );

        /* Pending orders */
        $pending_orders = wc_get_orders( array(
            'limit'  => -1,
            'return' => 'ids',
            'status' => array( 'pending', 'kctm-confirmed', 'kctm-in-progress' ),
        ) );

        /* Revenue this month */
        $first_of_month = date( 'Y-m-01' );
        $completed_orders = wc_get_orders( array(
            'limit'        => -1,
            'status'       => array( 'completed', 'processing', 'kctm-delivered' ),
            'date_created' => $first_of_month . '...' . $today . ' 23:59:59',
        ) );
        $revenue = 0;
        foreach ( $completed_orders as $order ) {
            $revenue += floatval( $order->get_total() );
        }

        /* Recent orders (last 10) */
        $recent = wc_get_orders( array(
            'limit'   => 10,
            'orderby' => 'date',
            'order'   => 'DESC',
        ) );
        $recent_orders = array();
        foreach ( $recent as $order ) {
            $recent_orders[] = self::format_order( $order );
        }

        /* Upcoming consultations (next 7 days) */
        $upcoming_consultations = array();
        if ( class_exists( 'KCTM_Consultation_Booking' ) ) {
            $result = KCTM_Consultation_Booking::get_bookings( array(
                'per_page'  => 10,
                'status'    => 'confirmed',
                'date_from' => $today,
                'date_to'   => date( 'Y-m-d', strtotime( '+7 days' ) ),
            ) );
            $upcoming_consultations = array_map( function( $b ) {
                return array(
                    'id'        => $b->id,
                    'name'      => $b->first_name . ' ' . $b->last_name,
                    'phone'     => $b->phone,
                    'date'      => $b->consultation_date,
                    'time'      => $b->consultation_time,
                    'status'    => $b->status,
                );
            }, $result['items'] );
        }

        wp_send_json_success( array(
            'customers'              => $customer_count,
            'orders_today'           => count( $orders_today ),
            'pending_orders'         => count( $pending_orders ),
            'revenue_month'          => $revenue,
            'currency'               => get_woocommerce_currency_symbol(),
            'recent_orders'          => $recent_orders,
            'upcoming_consultations' => $upcoming_consultations,
        ) );
    }

    /* ================================================================
     * Orders
     * ============================================================= */

    public static function orders() {
        self::check_access();

        $page        = max( 1, self::get_int( 'page', 1 ) );
        $status      = self::get_param( 'status', 'any' );
        $search      = self::get_param( 'search' );
        $date_filter = self::get_param( 'date_filter' );
        $per         = 20;

        $args = array(
            'limit'   => $per,
            'paged'   => $page,
            'orderby' => 'date',
            'order'   => 'DESC',
        );

        if ( 'any' !== $status && ! empty( $status ) ) {
            $args['status'] = $status;
        }

        /* Date filter. */
        if ( 'today' === $date_filter ) {
            $args['date_created'] = '>=' . gmdate( 'Y-m-d' ) . 'T00:00:00';
        } elseif ( 'this_month' === $date_filter ) {
            $args['date_created'] = '>=' . gmdate( 'Y-m-01' ) . 'T00:00:00';
        } elseif ( 'this_week' === $date_filter ) {
            $args['date_created'] = '>=' . gmdate( 'Y-m-d', strtotime( 'monday this week' ) ) . 'T00:00:00';
        }

        if ( ! empty( $search ) ) {
            /* Search by order ID or customer name / email */
            if ( is_numeric( $search ) ) {
                $args['post__in'] = array( absint( $search ) );
            } else {
                /* WooCommerce doesn't support customer search directly in wc_get_orders,
                   so we search customers first then filter by customer ID. */
                $users = get_users( array(
                    'search'         => '*' . $search . '*',
                    'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
                    'fields'         => 'ID',
                    'number'         => 50,
                ) );
                $name_users = get_users( array(
                    'meta_query' => array(
                        'relation' => 'OR',
                        array( 'key' => 'first_name', 'value' => $search, 'compare' => 'LIKE' ),
                        array( 'key' => 'last_name', 'value' => $search, 'compare' => 'LIKE' ),
                    ),
                    'fields' => 'ID',
                    'number' => 50,
                ) );
                $ids = array_unique( array_merge( $users, $name_users ) );
                if ( ! empty( $ids ) ) {
                    $args['customer_id'] = $ids;
                } else {
                    wp_send_json_success( array( 'orders' => array(), 'total' => 0, 'pages' => 0 ) );
                }
            }
        }

        $orders = wc_get_orders( $args );

        /* total count */
        $count_args = $args;
        $count_args['limit']  = -1;
        $count_args['return'] = 'ids';
        unset( $count_args['paged'] );
        $total = count( wc_get_orders( $count_args ) );

        $formatted = array();
        foreach ( $orders as $order ) {
            $formatted[] = self::format_order( $order );
        }

        wp_send_json_success( array(
            'orders' => $formatted,
            'total'  => $total,
            'pages'  => ceil( $total / $per ),
        ) );
    }

    private static function format_order( $order ) {
        $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        if ( empty( $customer_name ) ) {
            $customer_name = $order->get_billing_email();
        }

        $items = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $items[] = array(
                'item_id' => $item_id,
                'name'  => $item->get_name(),
                'qty'   => $item->get_quantity(),
                'total' => wc_format_decimal( $item->get_total(), 2 ),
                'price' => $item->get_quantity() > 0 ? wc_format_decimal( $item->get_total() / $item->get_quantity(), 2 ) : '0',
            );
        }

        /* Measurement snapshot attached to order */
        $measurements = $order->get_meta( '_kctm_measurements' );

        /* Driver info attached to order */
        $driver_id    = $order->get_meta( '_kctm_driver_id' );
        $driver_name  = $order->get_meta( '_kctm_driver_name' );
        $driver_phone = $order->get_meta( '_kctm_driver_phone' );

        return array(
            'id'           => $order->get_id(),
            'number'       => $order->get_order_number(),
            'customer'     => $customer_name,
            'customer_id'  => $order->get_customer_id(),
            'email'        => $order->get_billing_email(),
            'phone'        => $order->get_billing_phone(),
            'city'         => $order->get_billing_city(),
            'date'         => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '',
            'total'        => wc_format_decimal( $order->get_total(), 2 ),
            'status'       => $order->get_status(),
            'status_label' => wc_get_order_status_name( $order->get_status() ),
            'items'        => $items,
            'measurements' => $measurements ? $measurements : null,
            'driver_id'    => $driver_id ? (int) $driver_id : null,
            'driver_name'  => $driver_name ? $driver_name : null,
            'driver_phone' => $driver_phone ? $driver_phone : null,
            'momo_ref'            => $order->get_meta( '_kctm_momo_ref' ),
            'momo_screenshot_id'  => $order->get_meta( '_kctm_momo_screenshot_id' ),
            'momo_screenshot_url' => $order->get_meta( '_kctm_momo_screenshot_id' ) ? wp_get_attachment_url( $order->get_meta( '_kctm_momo_screenshot_id' ) ) : '',
            'payment_method'      => $order->get_payment_method(),
        );
    }

    private static function format_product_summary( $product ) {
        $image_id  = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

        $cats = array();
        foreach ( $product->get_category_ids() as $cid ) {
            $term = get_term( $cid, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $cats[] = array( 'id' => $term->term_id, 'name' => $term->name );
            }
        }

        return array(
            'id'             => $product->get_id(),
            'name'           => $product->get_name(),
            'sku'            => $product->get_sku(),
            'status'         => $product->get_status(),
            'regular_price'  => $product->get_regular_price(),
            'sale_price'     => $product->get_sale_price(),
            'price'          => $product->get_price(),
            'stock_status'   => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock'   => $product->get_manage_stock(),
            'image'          => $image_url,
            'categories'     => $cats,
            'date_created'   => $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d H:i' ) : '',
            'total_sales'    => $product->get_total_sales(),
            'type'           => $product->get_type(),
        );
    }

    /* ================================================================
     * Update Order Status
     * ============================================================= */

    public static function update_order_status() {
        self::check_access();

        $order_id = self::get_int( 'order_id' );
        $status   = self::get_param( 'status' );

        if ( ! $order_id || empty( $status ) ) {
            wp_send_json_error( array( 'message' => 'Order ID and status are required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $order->update_status( $status );

        wp_send_json_success( array(
            'message'      => 'Order status updated.',
            'status'       => $order->get_status(),
            'status_label' => wc_get_order_status_name( $order->get_status() ),
        ) );
    }

    /* ================================================================
     * Customers
     * ============================================================= */

    public static function customers() {
        self::check_access();

        $page   = max( 1, self::get_int( 'page', 1 ) );
        $search = self::get_param( 'search' );
        $per    = 20;

        $user_args = array(
            'role'   => 'customer',
            'number' => $per,
            'paged'  => $page,
            'orderby' => 'registered',
            'order'   => 'DESC',
        );

        if ( ! empty( $search ) ) {
            /* First try standard search */
            $user_args['search'] = '*' . $search . '*';
            $user_args['search_columns'] = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );
        }

        $query = new WP_User_Query( $user_args );
        $users = $query->get_results();
        $total = $query->get_total();

        /* Also search by phone and name meta if keyword given */
        if ( ! empty( $search ) ) {
            $meta_args = array(
                'role'   => 'customer',
                'number' => $per,
                'meta_query' => array(
                    'relation' => 'OR',
                    array( 'key' => '_kctm_phone', 'value' => $search, 'compare' => 'LIKE' ),
                    array( 'key' => 'billing_phone', 'value' => $search, 'compare' => 'LIKE' ),
                    array( 'key' => 'first_name', 'value' => $search, 'compare' => 'LIKE' ),
                    array( 'key' => 'last_name', 'value' => $search, 'compare' => 'LIKE' ),
                ),
            );
            $meta_query = new WP_User_Query( $meta_args );
            $meta_users = $meta_query->get_results();

            /* Merge + deduplicate */
            $seen = array();
            foreach ( $users as $u ) {
                $seen[ $u->ID ] = true;
            }
            foreach ( $meta_users as $u ) {
                if ( ! isset( $seen[ $u->ID ] ) ) {
                    $users[] = $u;
                    $seen[ $u->ID ] = true;
                }
            }
            $total = max( $total, count( $users ) );
        }

        $formatted = array();
        foreach ( $users as $user ) {
            $name = trim( $user->first_name . ' ' . $user->last_name );
            if ( empty( $name ) ) {
                $name = $user->display_name;
            }

            $phone = get_user_meta( $user->ID, '_kctm_phone', true );
            if ( empty( $phone ) ) {
                $phone = get_user_meta( $user->ID, 'billing_phone', true );
            }

            $type = get_user_meta( $user->ID, '_kctm_customer_type', true );
            $has_measurements = (bool) get_user_meta( $user->ID, '_kctm_measurement_gender', true );

            $formatted[] = array(
                'id'               => $user->ID,
                'name'             => $name,
                'email'            => $user->user_email,
                'phone'            => $phone,
                'type'             => $type ? $type : 'regular',
                'has_measurements' => $has_measurements,
                'registered'       => $user->user_registered,
            );
        }

        wp_send_json_success( array(
            'customers' => $formatted,
            'total'     => $total,
            'pages'     => ceil( $total / $per ),
        ) );
    }

    /* ================================================================
     * Customer Detail
     * ============================================================= */

    public static function customer_detail() {
        self::check_access();

        $customer_id = self::get_int( 'customer_id' );
        if ( ! $customer_id ) {
            wp_send_json_error( array( 'message' => 'Customer ID is required.' ) );
        }

        $user = get_userdata( $customer_id );
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => 'Customer not found.' ) );
        }

        $name = trim( $user->first_name . ' ' . $user->last_name );
        if ( empty( $name ) ) {
            $name = $user->display_name;
        }

        $phone = get_user_meta( $customer_id, '_kctm_phone', true );
        if ( empty( $phone ) ) {
            $phone = get_user_meta( $customer_id, 'billing_phone', true );
        }

        $type   = get_user_meta( $customer_id, '_kctm_customer_type', true );
        $gender = get_user_meta( $customer_id, '_kctm_measurement_gender', true );

        /* Measurements */
        $measurements = KCTM_Measurement_Storage::get_measurements( $customer_id );
        $fields       = KCTM_Measurement_Fields::get_all_fields();

        /* Order history */
        $orders = wc_get_orders( array(
            'customer_id' => $customer_id,
            'limit'       => 20,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
        $order_history = array();
        foreach ( $orders as $order ) {
            $order_history[] = array(
                'id'           => $order->get_id(),
                'number'       => $order->get_order_number(),
                'date'         => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
                'total'        => wc_format_decimal( $order->get_total(), 2 ),
                'status'       => $order->get_status(),
                'status_label' => wc_get_order_status_name( $order->get_status() ),
            );
        }

        wp_send_json_success( array(
            'id'           => $customer_id,
            'name'         => $name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'email'        => $user->user_email,
            'phone'        => $phone,
            'type'         => $type ? $type : 'regular',
            'gender'       => $gender ? $gender : '',
            'measurements' => $measurements,
            'fields'       => $fields,
            'orders'       => $order_history,
            'notes'        => get_user_meta( $customer_id, '_kctm_customer_notes', true ),
        ) );
    }

    /* ================================================================
     * Save Customer Notes
     * ============================================================= */

    public static function save_customer_notes() {
        self::check_access();

        $customer_id = self::get_int( 'customer_id' );
        $notes       = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

        if ( ! $customer_id ) {
            wp_send_json_error( array( 'message' => 'Customer ID is required.' ) );
        }

        update_user_meta( $customer_id, '_kctm_customer_notes', $notes );

        wp_send_json_success( array( 'message' => 'Notes saved.' ) );
    }

    /* ================================================================
     * Create Walk-in Customer
     * ============================================================= */

    public static function create_walkin() {
        self::check_access();

        $first_name = self::get_param( 'first_name' );
        $last_name  = self::get_param( 'last_name' );
        $phone      = self::get_param( 'phone' );
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $gender     = self::get_param( 'gender' );

        if ( empty( $first_name ) || empty( $last_name ) || empty( $phone ) ) {
            wp_send_json_error( array( 'message' => 'First name, last name, and phone are required.' ) );
        }

        if ( empty( $email ) ) {
            $clean_phone = preg_replace( '/[^0-9]/', '', $phone );
            $email = 'walkin_' . $clean_phone . '@kevincho.local';
        }

        $existing = email_exists( $email );
        if ( $existing ) {
            wp_send_json_error( array( 'message' => 'A customer with this email already exists.', 'customer_id' => $existing ) );
        }

        $username = sanitize_user( strtolower( $first_name . '.' . $last_name . '.' . substr( md5( $phone ), 0, 4 ) ) );
        $password = wp_generate_password( 12, true );

        if ( function_exists( 'wc_create_new_customer' ) ) {
            /* Set phone for any remaining validation hooks. */
            $_POST['kctm_phone'] = $phone;
            $user_id = wc_create_new_customer( $email, $username, $password );
        } else {
            $user_id = wp_insert_user( array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => $password,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'role'       => 'customer',
            ) );
        }

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        update_user_meta( $user_id, 'first_name', $first_name );
        update_user_meta( $user_id, 'last_name', $last_name );
        update_user_meta( $user_id, '_kctm_customer_type', 'walkin' );
        update_user_meta( $user_id, '_kctm_phone', $phone );
        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, 'billing_first_name', $first_name );
        update_user_meta( $user_id, 'billing_last_name', $last_name );
        update_user_meta( $user_id, 'billing_email', $email );

        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $first_name . ' ' . $last_name,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
        ) );

        $height    = self::get_param( 'height' );
        $shoe_size = self::get_param( 'shoe_size' );

        if ( ! empty( $height ) ) {
            update_user_meta( $user_id, '_kctm_measurement_height', $height );
        }
        if ( ! empty( $shoe_size ) ) {
            update_user_meta( $user_id, '_kctm_measurement_shoe_size', $shoe_size );
        }

        if ( ! empty( $gender ) && in_array( $gender, array( 'male', 'female', 'child' ), true ) ) {
            update_user_meta( $user_id, '_kctm_measurement_gender', $gender );
        }

        wp_send_json_success( array(
            'message'     => 'Walk-in customer created.',
            'customer_id' => $user_id,
        ) );
    }

    /* ================================================================
     * Create In-Store Order
     * ============================================================= */

    public static function create_instore_order() {
        self::check_access();

        $customer_id = self::get_int( 'customer_id' );
        $items_json  = isset( $_POST['items_json'] ) ? wp_unslash( $_POST['items_json'] ) : '[]';
        $items       = json_decode( $items_json, true );
        $send_invoice = self::get_param( 'send_invoice', 'no' );

        if ( ! $customer_id ) {
            wp_send_json_error( array( 'message' => 'Customer ID is required.' ) );
        }

        if ( empty( $items ) || ! is_array( $items ) ) {
            wp_send_json_error( array( 'message' => 'At least one item is required.' ) );
        }

        $customer = get_userdata( $customer_id );
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => 'Customer not found.' ) );
        }

        /* Create the order. */
        $order = wc_create_order( array( 'customer_id' => $customer_id ) );
        if ( is_wp_error( $order ) ) {
            wp_send_json_error( array( 'message' => $order->get_error_message() ) );
        }

        /* Add items — supports both catalog products and custom items. */
        foreach ( $items as $item ) {
            $qty  = isset( $item['qty'] ) ? max( 1, intval( $item['qty'] ) ) : 1;
            $type = isset( $item['type'] ) ? sanitize_text_field( $item['type'] ) : 'product';

            if ( 'custom' === $type ) {
                /* Custom line item (e.g. "Custom 3-piece suit" at 50000 FCFA). */
                $name  = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : 'Custom Item';
                $price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;

                $line = new WC_Order_Item_Product();
                $line->set_name( $name );
                $line->set_quantity( $qty );
                $line->set_subtotal( $price * $qty );
                $line->set_total( $price * $qty );
                $order->add_item( $line );
            } else {
                /* Catalog product. */
                $product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
                $product    = wc_get_product( $product_id );
                if ( $product ) {
                    $order->add_product( $product, $qty );
                }
            }
        }

        /* Set billing info from customer profile. */
        $order->set_billing_first_name( $customer->first_name );
        $order->set_billing_last_name( $customer->last_name );
        $order->set_billing_email( $customer->user_email );
        $order->set_billing_phone( get_user_meta( $customer_id, 'billing_phone', true ) );

        /* Mark as in-store order. */
        $order->update_meta_data( '_kctm_order_type', 'instore' );
        $order->set_payment_method_title( 'In-Store Payment' );

        $order->calculate_totals();
        $order->update_status( 'processing', 'In-store order created from Store Manager.' );
        $order->save();

        /* Send invoice email if requested. */
        $sent_invoice = false;
        if ( 'yes' === $send_invoice ) {
            $email = $customer->user_email;
            if ( ! empty( $email ) && strpos( $email, '@kevincho.local' ) === false ) {
                do_action( 'woocommerce_new_customer_note', array(
                    'order_id'      => $order->get_id(),
                    'customer_note' => 'Your invoice from Kevin Cho Tailoring.',
                ) );
                /* Also trigger the customer invoice email. */
                WC()->mailer()->customer_invoice( $order );
                $sent_invoice = true;
            }
        }

        wp_send_json_success( array(
            'message'      => 'In-store order #' . $order->get_order_number() . ' created.',
            'order_id'     => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'total'        => wc_format_decimal( $order->get_total(), 0 ),
            'sent_invoice' => $sent_invoice,
        ) );
    }

    /* ================================================================
     * Edit Order Items — modify items, prices, qty
     * ============================================================= */

    public static function edit_order_items() {
        self::check_access();

        $order_id  = self::get_int( 'order_id' );
        $items_raw = isset( $_POST['items_json'] ) ? wp_unslash( $_POST['items_json'] ) : '[]';
        $items     = json_decode( $items_raw, true );
        $notify    = self::get_param( 'notify', 'yes' );

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Order ID is required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $existing_ids = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $existing_ids[] = $item_id;
        }

        $kept_ids = array();

        /* Update or add items */
        if ( is_array( $items ) ) {
            foreach ( $items as $item ) {
                $item_id = isset( $item['item_id'] ) ? absint( $item['item_id'] ) : 0;
                $name    = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
                $qty     = isset( $item['qty'] ) ? max( 1, intval( $item['qty'] ) ) : 1;
                $price   = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;

                if ( empty( $name ) ) {
                    continue;
                }

                if ( $item_id && in_array( $item_id, $existing_ids, true ) ) {
                    /* Update existing line */
                    $line = $order->get_item( $item_id );
                    if ( $line ) {
                        $line->set_name( $name );
                        $line->set_quantity( $qty );
                        $line->set_subtotal( $price * $qty );
                        $line->set_total( $price * $qty );
                        $line->save();
                        $kept_ids[] = $item_id;
                    }
                } else {
                    /* Add new line */
                    $line = new WC_Order_Item_Product();
                    $line->set_name( $name );
                    $line->set_quantity( $qty );
                    $line->set_subtotal( $price * $qty );
                    $line->set_total( $price * $qty );
                    $new_id = $order->add_item( $line );
                    if ( $new_id ) {
                        $kept_ids[] = $new_id;
                    }
                }
            }
        }

        /* Remove items that were deleted */
        foreach ( $existing_ids as $eid ) {
            if ( ! in_array( $eid, $kept_ids, true ) ) {
                $order->remove_item( $eid );
            }
        }

        $order->save();

        /* Re-fetch the order so cached line items reflect the saved changes,
         * otherwise calculate_totals() reads stale in-memory line totals. */
        $order = wc_get_order( $order_id );
        $order->calculate_totals();
        $order->add_order_note( 'Order items updated by store manager.', false, true );
        $order->save();

        /* Notify customer */
        $sent_via = '';
        if ( 'yes' === $notify ) {
            $email   = $order->get_billing_email();
            $name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            $number  = $order->get_order_number();
            $total   = wp_strip_all_tags( wc_price( $order->get_total() ) );

            $items_list = '';
            foreach ( $order->get_items() as $line ) {
                $items_list .= '- ' . $line->get_name() . ' (x' . $line->get_quantity() . ') ' . wp_strip_all_tags( wc_price( $line->get_total() ) ) . "\n";
            }

            $message = 'Bonjour ' . ( ! empty( $name ) ? $name : 'Customer' ) . ",\n\n"
                . 'Your order #' . $number . " has been updated.\n"
                . 'Votre commande #' . $number . " a ete mise a jour.\n\n"
                . "Updated items:\n" . $items_list . "\n"
                . 'New total: ' . $total . "\n\n"
                . "If you have any questions, please contact us.\n"
                . "Si vous avez des questions, veuillez nous contacter.\n\n"
                . "Thank you! / Merci !\nKevin Cho Tailoring";

            /* WhatsApp */
            $phone = $order->get_billing_phone();
            if ( class_exists( 'KCTM_WhatsApp_API' ) && ! empty( $phone ) ) {
                try {
                    $wa_api = new KCTM_WhatsApp_API();
                    $result = $wa_api->send_text_message( $phone, $message );
                    if ( $result && ! is_wp_error( $result ) ) {
                        $sent_via = 'whatsapp';
                    }
                } catch ( \Exception $e ) {}
            }

            /* Email */
            if ( ! empty( $email ) ) {
                $subject = 'Your Order #' . $number . ' Has Been Updated / Votre Commande a ete Mise a Jour';
                $headers = array( 'Content-Type: text/html; charset=UTF-8' );
                wp_mail( $email, $subject, nl2br( $message ), $headers );
                $sent_via = empty( $sent_via ) ? 'email' : $sent_via . ' + email';
            }
        }

        wp_send_json_success( array(
            'message'   => 'Order updated.',
            'total'     => wc_format_decimal( $order->get_total(), 0 ),
            'sent_via'  => $sent_via,
        ) );
    }

    /* ================================================================
     * Save Measurements
     * ============================================================= */

    public static function save_measurements() {
        self::check_access();

        $customer_id = self::get_int( 'customer_id' );
        if ( ! $customer_id ) {
            wp_send_json_error( array( 'message' => 'Customer ID is required.' ) );
        }

        $user = get_userdata( $customer_id );
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => 'Customer not found.' ) );
        }

        $data = array();
        /* Accept JSON string (reliable) or FormData array (legacy). */
        if ( ! empty( $_POST['measurements_json'] ) ) {
            $raw  = wp_unslash( $_POST['measurements_json'] );
            $data = json_decode( $raw, true );
            if ( ! is_array( $data ) ) {
                $data = array();
            }
            $data = array_map( 'sanitize_text_field', $data );
        } elseif ( isset( $_POST['measurements'] ) && is_array( $_POST['measurements'] ) ) {
            $data = array_map( 'sanitize_text_field', wp_unslash( $_POST['measurements'] ) );
        }

        if ( empty( $data ) ) {
            wp_send_json_error( array( 'message' => 'No measurement data provided.' ) );
        }

        /* Save each measurement directly — skip strict validation for portal.
         * The portal allows partial measurements (fill what you have, edit later). */
        $saved = 0;
        foreach ( $data as $key => $value ) {
            $key   = sanitize_key( $key );
            $value = sanitize_text_field( $value );
            if ( '' !== $value ) {
                update_user_meta( $customer_id, '_kctm_measurement_' . $key, $value );
                $saved++;
            } else {
                /* Empty value means clear the field. */
                delete_user_meta( $customer_id, '_kctm_measurement_' . $key );
            }
        }

        wp_send_json_success( array( 'message' => $saved . ' measurement(s) saved.' ) );
    }

    /* ================================================================
     * Consultations
     * ============================================================= */

    public static function consultations() {
        self::check_access();

        $page   = max( 1, self::get_int( 'page', 1 ) );
        $status = self::get_param( 'status' );
        $search = self::get_param( 'search' );

        $args = array(
            'per_page' => 20,
            'page'     => $page,
        );

        if ( ! empty( $status ) && 'all' !== $status ) {
            $args['status'] = $status;
        }

        if ( ! empty( $search ) ) {
            $args['search'] = $search;
        }

        $result = KCTM_Consultation_Booking::get_bookings( $args );

        $formatted = array_map( function( $b ) {
            return array(
                'id'             => $b->id,
                'name'           => $b->first_name . ' ' . $b->last_name,
                'first_name'     => $b->first_name,
                'last_name'      => $b->last_name,
                'email'          => $b->email,
                'phone'          => $b->phone,
                'date'           => $b->consultation_date,
                'time'           => $b->consultation_time,
                'status'         => $b->status,
                'payment_status' => $b->payment_status,
                'notes'          => $b->notes,
                'order_id'       => $b->order_id,
            );
        }, $result['items'] );

        wp_send_json_success( array(
            'consultations' => $formatted,
            'total'         => $result['total'],
            'pages'         => $result['pages'],
        ) );
    }

    /* ================================================================
     * Update Consultation (Complete / Cancel / Resend)
     * ============================================================= */

    public static function update_consultation() {
        self::check_access();

        $booking_id = self::get_int( 'booking_id' );
        $action     = self::get_param( 'consultation_action' );

        if ( ! $booking_id || empty( $action ) ) {
            wp_send_json_error( array( 'message' => 'Booking ID and action are required.' ) );
        }

        switch ( $action ) {
            case 'complete':
                $result = KCTM_Consultation_Booking::complete_booking( $booking_id );
                $msg    = 'Consultation marked as completed.';
                break;

            case 'cancel':
                $result = KCTM_Consultation_Booking::cancel_booking( $booking_id );
                $msg    = 'Consultation cancelled.';
                break;

            case 'resend':
                $result = KCTM_Consultation_Notifications::send_confirmation( $booking_id );
                $msg    = 'Notification resent.';
                break;

            default:
                wp_send_json_error( array( 'message' => 'Unknown action.' ) );
                return;
        }

        if ( $result ) {
            wp_send_json_success( array( 'message' => $msg ) );
        } else {
            wp_send_json_error( array( 'message' => 'Action failed.' ) );
        }
    }

    /* ================================================================
     * Notifications
     * ============================================================= */

    public static function notifications() {
        self::check_access();

        $page = max( 1, self::get_int( 'page', 1 ) );

        $result = KCTM_Notification_Log::get_logs( array(
            'per_page' => 20,
            'page'     => $page,
        ) );

        $formatted = array_map( function( $log ) {
            return array(
                'id'            => $log->id,
                'order_id'      => $log->order_id,
                'customer_id'   => $log->customer_id,
                'phone'         => $log->phone,
                'status'        => $log->status,
                'message'       => $log->message,
                'response_code' => $log->response_code,
                'sent_at'       => $log->sent_at,
            );
        }, $result['items'] );

        wp_send_json_success( array(
            'notifications' => $formatted,
            'total'         => $result['total'],
            'pages'         => $result['pages'],
        ) );
    }

    /* ================================================================
     * Send Email (FluentCRM or wp_mail fallback)
     * ============================================================= */

    public static function send_email() {
        self::check_access();

        $subject    = self::get_param( 'subject' );
        $body       = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : '';
        $recipients = self::get_param( 'recipients' ); // 'all' | customer ID

        if ( empty( $subject ) || empty( $body ) ) {
            wp_send_json_error( array( 'message' => 'Subject and body are required.' ) );
        }

        /* Gather recipients: array of [ 'email' => ..., 'name' => ... ] */
        $recipient_list = array();

        if ( 'all' === $recipients ) {
            $users = get_users( array( 'role' => 'customer', 'fields' => array( 'ID', 'user_email', 'display_name' ) ) );
            foreach ( $users as $u ) {
                if ( ! empty( $u->user_email ) && strpos( $u->user_email, '@kevincho.local' ) === false ) {
                    $recipient_list[] = array( 'email' => $u->user_email, 'name' => $u->display_name );
                }
            }
        } else {
            $cid  = absint( $recipients );
            $user = get_userdata( $cid );
            if ( $user ) {
                $recipient_list[] = array( 'email' => $user->user_email, 'name' => $user->display_name );
            } else {
                /* Try as a guest — look up from recent orders. */
                $orders = wc_get_orders( array( 'customer_id' => $cid, 'limit' => 1 ) );
                if ( ! empty( $orders ) ) {
                    $o = $orders[0];
                    $email = $o->get_billing_email();
                    $name  = trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() );
                    if ( $email ) {
                        $recipient_list[] = array( 'email' => $email, 'name' => $name );
                    }
                }
            }
        }

        if ( empty( $recipient_list ) ) {
            wp_send_json_error( array( 'message' => 'No valid recipients found.' ) );
        }

        $store_name = get_option( 'kctm_store_name', get_bloginfo( 'name' ) );

        /* Try FluentCRM REST first */
        $sent_via = 'wp_mail';

        if ( defined( 'FLUENTCRM' ) && function_exists( 'FluentCrmApi' ) ) {
            try {
                $api = FluentCrmApi( 'campaigns' );
                $campaign = $api->create( array(
                    'title'        => $subject,
                    'subject'      => $subject,
                    'email_body'   => nl2br( $body ),
                    'status'       => 'draft',
                    'template_id'  => 0,
                ) );
                $sent_via = 'fluentcrm_draft';
            } catch ( \Exception $e ) {
                /* Fallback to wp_mail */
            }
        }

        if ( 'wp_mail' === $sent_via ) {
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );

            foreach ( $recipient_list as $r ) {
                /* Replace placeholders. */
                $personalized = str_replace(
                    array( '{customer_name}', '{store_name}' ),
                    array( $r['name'] ?: 'Valued Customer', $store_name ),
                    $body
                );
                $html_body = nl2br( $personalized );
                wp_mail( $r['email'], $subject, $html_body, $headers );
            }
        }

        wp_send_json_success( array(
            'message' => 'Email sent to ' . count( $recipient_list ) . ' recipient(s).',
            'count'   => count( $recipient_list ),
            'method'  => $sent_via,
        ) );
    }

    /* ================================================================
     * Invoice URL
     * ============================================================= */

    public static function invoice_url() {
        self::check_access();

        $order_id = self::get_int( 'order_id' );
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Order ID is required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        /* WooCommerce PDF Invoices & Packing Slips plugin */
        $url = '';
        if ( function_exists( 'wcpdf_get_document' ) ) {
            $document = wcpdf_get_document( 'invoice', $order );
            if ( $document && $document->exists() ) {
                $url = wp_nonce_url(
                    admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&document_type=invoice&order_ids=' . $order_id ),
                    'generate_wpo_wcpdf'
                );
            }
        }

        /* Fallback: WooCommerce built-in */
        if ( empty( $url ) ) {
            $url = $order->get_view_order_url();
        }

        wp_send_json_success( array(
            'url'      => $url,
            'order_id' => $order_id,
        ) );
    }

    /* ================================================================
     * Products — List / Search
     * ============================================================= */

    public static function products() {
        self::check_access();

        $page     = max( 1, self::get_int( 'page', 1 ) );
        $search   = self::get_param( 'search' );
        $category = self::get_int( 'category' );
        $status   = self::get_param( 'status', 'any' );
        $orderby  = self::get_param( 'orderby', 'date' );
        $order    = self::get_param( 'order', 'DESC' );
        $per      = 20;

        $args = array(
            'limit'   => $per,
            'page'    => $page,
            'orderby' => $orderby,
            'order'   => $order,
        );

        if ( 'any' !== $status && ! empty( $status ) ) {
            $args['status'] = $status;
        }

        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        if ( $category ) {
            $term = get_term( $category, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $args['category'] = array( $term->slug );
            }
        }

        $products = wc_get_products( $args );

        /* Total count */
        $count_args           = $args;
        $count_args['limit']  = -1;
        $count_args['return'] = 'ids';
        unset( $count_args['page'] );
        $total = count( wc_get_products( $count_args ) );

        $formatted = array();
        foreach ( $products as $product ) {
            $formatted[] = self::format_product_summary( $product );
        }

        /* All categories for filter dropdown */
        $all_cats  = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );
        $categories = array();
        if ( ! is_wp_error( $all_cats ) ) {
            foreach ( $all_cats as $cat ) {
                $categories[] = array( 'id' => $cat->term_id, 'name' => $cat->name );
            }
        }

        wp_send_json_success( array(
            'products'   => $formatted,
            'total'      => $total,
            'pages'      => ceil( $total / $per ),
            'categories' => $categories,
        ) );
    }

    /* ================================================================
     * Product Detail
     * ============================================================= */

    public static function product_detail() {
        self::check_access();

        $product_id = self::get_int( 'product_id' );
        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => 'Product ID is required.' ) );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( array( 'message' => 'Product not found.' ) );
        }

        /* Images */
        $images     = array();
        $featured_id = $product->get_image_id();
        if ( $featured_id ) {
            $images[] = array(
                'id'          => (int) $featured_id,
                'url'         => wp_get_attachment_image_url( $featured_id, 'medium' ),
                'thumbnail'   => wp_get_attachment_image_url( $featured_id, 'thumbnail' ),
                'is_featured' => true,
            );
        }
        foreach ( $product->get_gallery_image_ids() as $gid ) {
            $images[] = array(
                'id'          => (int) $gid,
                'url'         => wp_get_attachment_image_url( $gid, 'medium' ),
                'thumbnail'   => wp_get_attachment_image_url( $gid, 'thumbnail' ),
                'is_featured' => false,
            );
        }

        /* Categories */
        $cats = array();
        foreach ( $product->get_category_ids() as $cid ) {
            $term = get_term( $cid, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $cats[] = array( 'id' => $term->term_id, 'name' => $term->name );
            }
        }

        /* Tags */
        $tags = array();
        foreach ( $product->get_tag_ids() as $tid ) {
            $term = get_term( $tid, 'product_tag' );
            if ( $term && ! is_wp_error( $term ) ) {
                $tags[] = array( 'id' => $term->term_id, 'name' => $term->name );
            }
        }

        /* All categories & tags for form selectors */
        $all_cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
        $all_categories = array();
        if ( ! is_wp_error( $all_cats ) ) {
            foreach ( $all_cats as $cat ) {
                $all_categories[] = array(
                    'id'     => $cat->term_id,
                    'name'   => $cat->name,
                    'parent' => $cat->parent,
                    'slug'   => $cat->slug,
                );
            }
        }

        $all_tag_terms = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false ) );
        $all_tags = array();
        if ( ! is_wp_error( $all_tag_terms ) ) {
            foreach ( $all_tag_terms as $tag ) {
                $all_tags[] = array( 'id' => $tag->term_id, 'name' => $tag->name );
            }
        }

        /* Personalizable flag */
        $personalizable = get_post_meta( $product_id, '_kctm_personalizable', true ) === 'yes';

        /* Sale dates */
        $date_on_sale_from = $product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->date( 'Y-m-d' ) : '';
        $date_on_sale_to   = $product->get_date_on_sale_to() ? $product->get_date_on_sale_to()->date( 'Y-m-d' ) : '';

        /* Attributes (for variable products) */
        $attributes = array();
        foreach ( $product->get_attributes() as $attr ) {
            $attributes[] = array(
                'name'      => $attr->get_name(),
                'options'   => $attr->get_options(),
                'visible'   => $attr->get_visible(),
                'variation' => $attr->get_variation(),
            );
        }

        /* Variations (for variable products) */
        $variations = array();
        if ( $product->is_type( 'variable' ) ) {
            foreach ( $product->get_children() as $var_id ) {
                $var = wc_get_product( $var_id );
                if ( ! $var ) {
                    continue;
                }
                $var_img_id  = $var->get_image_id();
                $var_img_url = $var_img_id ? wp_get_attachment_image_url( $var_img_id, 'thumbnail' ) : '';
                $variations[] = array(
                    'id'             => $var->get_id(),
                    'attributes'     => $var->get_attributes(),
                    'regular_price'  => $var->get_regular_price(),
                    'sale_price'     => $var->get_sale_price(),
                    'price'          => $var->get_price(),
                    'sku'            => $var->get_sku(),
                    'manage_stock'   => $var->get_manage_stock(),
                    'stock_quantity' => $var->get_stock_quantity(),
                    'stock_status'   => $var->get_stock_status(),
                    'image_id'       => (int) $var_img_id,
                    'image_url'      => $var_img_url,
                    'enabled'        => $var->get_status() === 'publish',
                );
            }
        }

        wp_send_json_success( array(
            'id'                => $product->get_id(),
            'name'              => $product->get_name(),
            'slug'              => $product->get_slug(),
            'sku'               => $product->get_sku(),
            'status'            => $product->get_status(),
            'type'              => $product->get_type(),
            'virtual'           => $product->get_virtual(),
            'description'       => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'regular_price'     => $product->get_regular_price(),
            'sale_price'        => $product->get_sale_price(),
            'date_on_sale_from' => $date_on_sale_from,
            'date_on_sale_to'   => $date_on_sale_to,
            'price'             => $product->get_price(),
            'manage_stock'      => $product->get_manage_stock(),
            'stock_quantity'    => $product->get_stock_quantity(),
            'stock_status'      => $product->get_stock_status(),
            'weight'            => $product->get_weight(),
            'images'            => $images,
            'categories'        => $cats,
            'tags'              => $tags,
            'all_categories'    => $all_categories,
            'all_tags'          => $all_tags,
            'personalizable'    => $personalizable,
            'attributes'        => $attributes,
            'variations'        => $variations,
            'total_sales'       => $product->get_total_sales(),
            'permalink'         => get_permalink( $product_id ),
            'date_created'      => $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d H:i' ) : '',
        ) );
    }

    /* ================================================================
     * Save Product (Create or Update)
     * ============================================================= */

    public static function save_product() {
        self::check_access();

        $product_id    = self::get_int( 'product_id' );
        $name          = self::get_param( 'name' );

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Product name is required.' ) );
        }

        $product_type = self::get_param( 'product_type', 'simple' );

        if ( $product_id ) {
            $existing = wc_get_product( $product_id );
            if ( ! $existing ) {
                wp_send_json_error( array( 'message' => 'Product not found.' ) );
            }
            /* Handle type switching by creating correct class instance */
            $current_type = $existing->get_type();
            if ( $product_type !== $current_type && in_array( $product_type, array( 'simple', 'variable' ), true ) ) {
                /* Create new product object of the target type, copy ID */
                $classmap = array(
                    'simple'   => 'WC_Product_Simple',
                    'variable' => 'WC_Product_Variable',
                );
                $product = new $classmap[ $product_type ]( $product_id );
            } else {
                $product = $existing;
            }
        } else {
            $product = ( $product_type === 'variable' ) ? new \WC_Product_Variable() : new \WC_Product_Simple();
        }

        $product->set_name( $name );

        $status = self::get_param( 'status' );
        if ( ! empty( $status ) ) {
            $product->set_status( $status );
        }

        /* Prices — only for simple products (variable derives from variations) */
        if ( $product_type !== 'variable' ) {
            $regular_price = self::get_param( 'regular_price' );
            if ( '' !== $regular_price ) {
                $product->set_regular_price( $regular_price );
            }
            $sale_price = self::get_param( 'sale_price' );
            $product->set_sale_price( $sale_price );
        }

        $date_on_sale_from = self::get_param( 'date_on_sale_from' );
        $product->set_date_on_sale_from( ! empty( $date_on_sale_from ) ? $date_on_sale_from : null );

        $date_on_sale_to = self::get_param( 'date_on_sale_to' );
        $product->set_date_on_sale_to( ! empty( $date_on_sale_to ) ? $date_on_sale_to : null );

        $description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
        $product->set_description( $description );

        $short_description = isset( $_POST['short_description'] ) ? wp_kses_post( wp_unslash( $_POST['short_description'] ) ) : '';
        $product->set_short_description( $short_description );

        $sku = self::get_param( 'sku' );
        if ( '' !== $sku ) {
            try {
                $product->set_sku( $sku );
            } catch ( WC_Data_Exception $e ) {
                wp_send_json_error( array( 'message' => $e->getMessage() ) );
            }
        }

        $manage_stock = self::get_param( 'manage_stock' );
        $product->set_manage_stock( 'yes' === $manage_stock );

        if ( 'yes' === $manage_stock ) {
            $stock_quantity = self::get_param( 'stock_quantity' );
            if ( '' !== $stock_quantity ) {
                $product->set_stock_quantity( intval( $stock_quantity ) );
            }
        }

        $stock_status = self::get_param( 'stock_status' );
        if ( ! empty( $stock_status ) ) {
            $product->set_stock_status( $stock_status );
        }

        $weight = self::get_param( 'weight' );
        if ( '' !== $weight ) {
            $product->set_weight( $weight );
        }

        $virtual = self::get_param( 'virtual' );
        $product->set_virtual( 'yes' === $virtual );

        /* Categories */
        if ( isset( $_POST['category_ids'] ) && is_array( $_POST['category_ids'] ) ) {
            $product->set_category_ids( array_map( 'absint', $_POST['category_ids'] ) );
        }

        /* Tags */
        if ( isset( $_POST['tag_ids'] ) && is_array( $_POST['tag_ids'] ) ) {
            $product->set_tag_ids( array_map( 'absint', $_POST['tag_ids'] ) );
        }

        /* Featured image */
        $featured_image_id = self::get_int( 'featured_image_id' );
        if ( $featured_image_id ) {
            $product->set_image_id( $featured_image_id );
        }

        /* Gallery images */
        $gallery_str = self::get_param( 'gallery_image_ids' );
        if ( ! empty( $gallery_str ) ) {
            $gallery_ids = array_map( 'absint', explode( ',', $gallery_str ) );
            $product->set_gallery_image_ids( $gallery_ids );
        } else {
            $product->set_gallery_image_ids( array() );
        }

        /* Attributes (for variable products) */
        if ( isset( $_POST['attributes_json'] ) ) {
            $attrs_raw  = json_decode( wp_unslash( $_POST['attributes_json'] ), true );
            $attr_objs  = array();
            $position   = 0;
            if ( is_array( $attrs_raw ) ) {
                foreach ( $attrs_raw as $attr_data ) {
                    $attr_name = sanitize_text_field( $attr_data['name'] ?? '' );
                    $options   = array_map( 'sanitize_text_field', $attr_data['options'] ?? array() );
                    if ( empty( $attr_name ) || empty( $options ) ) {
                        continue;
                    }
                    $attr = new \WC_Product_Attribute();
                    $attr->set_name( $attr_name );
                    $attr->set_options( $options );
                    $attr->set_position( $position++ );
                    $attr->set_visible( ! empty( $attr_data['visible'] ) );
                    $attr->set_variation( ! empty( $attr_data['variation'] ) );
                    $attr_objs[] = $attr;
                }
            }
            $product->set_attributes( $attr_objs );
        }

        $product->save();

        /* Sync variable product data */
        if ( $product_type === 'variable' ) {
            \WC_Product_Variable::sync( $product->get_id() );
        }

        /* Personalizable meta */
        $personalizable = self::get_param( 'personalizable' );
        if ( ! empty( $personalizable ) ) {
            update_post_meta( $product->get_id(), '_kctm_personalizable', 'yes' === $personalizable ? 'yes' : 'no' );
        }

        /* Purge cache so new/updated products show immediately for guests */
        if ( function_exists( 'sg_cachepress_purge_everything' ) ) {
            sg_cachepress_purge_everything();
        }
        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients( $product->get_id() );
        }

        wp_send_json_success( array(
            'message'    => $product_id ? 'Product updated.' : 'Product created.',
            'product_id' => $product->get_id(),
        ) );
    }

    /* ================================================================
     * Delete Product
     * ============================================================= */

    public static function delete_product() {
        self::check_access();

        $product_ids = self::get_param( 'product_ids' );
        $force       = self::get_param( 'force' );

        if ( empty( $product_ids ) ) {
            wp_send_json_error( array( 'message' => 'Product IDs are required.' ) );
        }

        $ids   = array_map( 'absint', explode( ',', $product_ids ) );
        $count = 0;

        foreach ( $ids as $id ) {
            $product = wc_get_product( $id );
            if ( $product ) {
                $product->delete( 'yes' === $force );
                $count++;
            }
        }

        wp_send_json_success( array(
            'message' => $count . ' product(s) deleted.',
            'count'   => $count,
        ) );
    }

    /* ================================================================
     * Bulk Product Operations
     * ============================================================= */

    public static function bulk_products() {
        self::check_access();

        $product_ids = self::get_param( 'product_ids' );
        $bulk_action = self::get_param( 'bulk_action' );

        if ( empty( $product_ids ) || empty( $bulk_action ) ) {
            wp_send_json_error( array( 'message' => 'Product IDs and action are required.' ) );
        }

        $ids   = array_map( 'absint', explode( ',', $product_ids ) );
        $count = 0;

        switch ( $bulk_action ) {
            case 'publish':
                foreach ( $ids as $id ) {
                    $product = wc_get_product( $id );
                    if ( $product ) {
                        $product->set_status( 'publish' );
                        $product->save();
                        $count++;
                    }
                }
                $msg = $count . ' product(s) published.';
                break;

            case 'draft':
                foreach ( $ids as $id ) {
                    $product = wc_get_product( $id );
                    if ( $product ) {
                        $product->set_status( 'draft' );
                        $product->save();
                        $count++;
                    }
                }
                $msg = $count . ' product(s) moved to draft.';
                break;

            case 'delete':
                foreach ( $ids as $id ) {
                    $product = wc_get_product( $id );
                    if ( $product ) {
                        $product->delete( false );
                        $count++;
                    }
                }
                $msg = $count . ' product(s) deleted.';
                break;

            case 'change_category':
                $category_id = self::get_int( 'category_id' );
                if ( ! $category_id ) {
                    wp_send_json_error( array( 'message' => 'Category ID is required.' ) );
                }
                foreach ( $ids as $id ) {
                    $product = wc_get_product( $id );
                    if ( $product ) {
                        $product->set_category_ids( array( $category_id ) );
                        $product->save();
                        $count++;
                    }
                }
                $msg = $count . ' product(s) category updated.';
                break;

            case 'price_adjust':
                $price_type  = self::get_param( 'price_type' );
                $price_value = self::get_param( 'price_value' );
                $price_field = self::get_param( 'price_field', 'regular_price' );

                if ( empty( $price_type ) || '' === $price_value ) {
                    wp_send_json_error( array( 'message' => 'Price type and value are required.' ) );
                }

                foreach ( $ids as $id ) {
                    $product = wc_get_product( $id );
                    if ( ! $product ) {
                        continue;
                    }

                    $current = 'sale_price' === $price_field
                        ? floatval( $product->get_sale_price() )
                        : floatval( $product->get_regular_price() );

                    if ( 'percent' === $price_type ) {
                        $new_price = $current + ( $current * floatval( $price_value ) / 100 );
                    } else {
                        $new_price = $current + floatval( $price_value );
                    }

                    $new_price = max( 0, round( $new_price, 2 ) );

                    if ( 'sale_price' === $price_field ) {
                        $product->set_sale_price( $new_price );
                    } else {
                        $product->set_regular_price( $new_price );
                    }

                    $product->save();
                    $count++;
                }
                $msg = $count . ' product(s) price adjusted.';
                break;

            default:
                wp_send_json_error( array( 'message' => 'Unknown bulk action.' ) );
                return;
        }

        wp_send_json_success( array(
            'message' => $msg,
            'count'   => $count,
        ) );
    }

    /* ================================================================
     * Upload Product Image
     * ============================================================= */

    public static function upload_product_image() {
        self::check_access();

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        if ( empty( $_FILES['product_image'] ) ) {
            wp_send_json_error( array( 'message' => 'No image file provided.' ) );
        }

        $images   = array();
        $is_multi = is_array( $_FILES['product_image']['name'] );

        if ( $is_multi ) {
            $file_count = count( $_FILES['product_image']['name'] );
            for ( $i = 0; $i < $file_count; $i++ ) {
                $_FILES['upload_file'] = array(
                    'name'     => $_FILES['product_image']['name'][ $i ],
                    'type'     => $_FILES['product_image']['type'][ $i ],
                    'tmp_name' => $_FILES['product_image']['tmp_name'][ $i ],
                    'error'    => $_FILES['product_image']['error'][ $i ],
                    'size'     => $_FILES['product_image']['size'][ $i ],
                );

                $attachment_id = media_handle_upload( 'upload_file', 0 );
                if ( is_wp_error( $attachment_id ) ) {
                    wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
                }

                $images[] = array(
                    'id'        => $attachment_id,
                    'url'       => wp_get_attachment_image_url( $attachment_id, 'medium' ),
                    'thumbnail' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
                );
            }
        } else {
            $attachment_id = media_handle_upload( 'product_image', 0 );
            if ( is_wp_error( $attachment_id ) ) {
                wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
            }

            $images[] = array(
                'id'        => $attachment_id,
                'url'       => wp_get_attachment_image_url( $attachment_id, 'medium' ),
                'thumbnail' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
            );
        }

        wp_send_json_success( array(
            'message' => count( $images ) . ' image(s) uploaded.',
            'images'  => $images,
        ) );
    }

    /* ================================================================
     * Delete Product Image
     * ============================================================= */

    public static function delete_product_image() {
        self::check_access();

        $attachment_id = self::get_int( 'attachment_id' );
        if ( ! $attachment_id ) {
            wp_send_json_error( array( 'message' => 'Attachment ID is required.' ) );
        }

        $result = wp_delete_attachment( $attachment_id, true );
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Failed to delete attachment.' ) );
        }

        wp_send_json_success( array( 'message' => 'Image deleted.' ) );
    }

    /* ================================================================
     * Create Product Category
     * ============================================================= */

    public static function create_category() {
        self::check_access();

        $category_name = self::get_param( 'category_name' );
        $parent_id     = self::get_int( 'parent_id' );

        if ( empty( $category_name ) ) {
            wp_send_json_error( array( 'message' => 'Category name is required.' ) );
        }

        $result = wp_insert_term( $category_name, 'product_cat', array( 'parent' => $parent_id ) );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message'     => 'Category created.',
            'category_id' => $result['term_id'],
            'name'        => $category_name,
        ) );
    }

    /* ================================================================
     * Product Terms — categories & tags for form selectors
     * ============================================================= */

    public static function product_terms() {
        self::check_access();

        $all_cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
        $all_categories = array();
        if ( ! is_wp_error( $all_cats ) ) {
            foreach ( $all_cats as $cat ) {
                $all_categories[] = array(
                    'id'     => $cat->term_id,
                    'name'   => $cat->name,
                    'parent' => $cat->parent,
                    'slug'   => $cat->slug,
                );
            }
        }

        $all_tag_terms = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false ) );
        $all_tags = array();
        if ( ! is_wp_error( $all_tag_terms ) ) {
            foreach ( $all_tag_terms as $tag ) {
                $all_tags[] = array( 'id' => $tag->term_id, 'name' => $tag->name );
            }
        }

        wp_send_json_success( array(
            'all_categories' => $all_categories,
            'all_tags'       => $all_tags,
        ) );
    }

    /* ================================================================
     * Save Variations (batch save/create)
     * ============================================================= */

    public static function save_variations() {
        self::check_access();

        $product_id     = self::get_int( 'product_id' );
        $variations_raw = isset( $_POST['variations_json'] ) ? json_decode( wp_unslash( $_POST['variations_json'] ), true ) : array();

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => 'Product ID is required.' ) );
        }

        $parent = wc_get_product( $product_id );
        if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
            wp_send_json_error( array( 'message' => 'Product is not a variable product.' ) );
        }

        $saved = array();
        foreach ( $variations_raw as $vdata ) {
            $var_id = isset( $vdata['id'] ) ? absint( $vdata['id'] ) : 0;

            if ( $var_id ) {
                $variation = wc_get_product( $var_id );
                if ( ! $variation || $variation->get_parent_id() !== $product_id ) {
                    continue;
                }
            } else {
                $variation = new \WC_Product_Variation();
                $variation->set_parent_id( $product_id );
            }

            if ( isset( $vdata['attributes'] ) && is_array( $vdata['attributes'] ) ) {
                $clean_attrs = array();
                foreach ( $vdata['attributes'] as $k => $v ) {
                    $clean_attrs[ sanitize_text_field( $k ) ] = sanitize_text_field( $v );
                }
                $variation->set_attributes( $clean_attrs );
            }

            if ( isset( $vdata['regular_price'] ) ) {
                $variation->set_regular_price( sanitize_text_field( $vdata['regular_price'] ) );
            }
            if ( isset( $vdata['sale_price'] ) ) {
                $variation->set_sale_price( sanitize_text_field( $vdata['sale_price'] ) );
            }
            if ( isset( $vdata['sku'] ) ) {
                try {
                    $variation->set_sku( sanitize_text_field( $vdata['sku'] ) );
                } catch ( \WC_Data_Exception $e ) {
                    // Skip invalid SKU.
                }
            }
            if ( isset( $vdata['manage_stock'] ) ) {
                $variation->set_manage_stock( (bool) $vdata['manage_stock'] );
            }
            if ( isset( $vdata['stock_quantity'] ) ) {
                $variation->set_stock_quantity( intval( $vdata['stock_quantity'] ) );
            }
            if ( isset( $vdata['stock_status'] ) ) {
                $variation->set_stock_status( sanitize_text_field( $vdata['stock_status'] ) );
            }
            if ( isset( $vdata['image_id'] ) && absint( $vdata['image_id'] ) ) {
                $variation->set_image_id( absint( $vdata['image_id'] ) );
            }
            $variation->set_status( ! empty( $vdata['enabled'] ) ? 'publish' : 'private' );

            $variation->save();
            $saved[] = $variation->get_id();
        }

        \WC_Product_Variable::sync( $product_id );

        wp_send_json_success( array(
            'message'       => count( $saved ) . ' variation(s) saved.',
            'variation_ids' => $saved,
        ) );
    }

    /* ================================================================
     * Delete Variation
     * ============================================================= */

    public static function delete_variation() {
        self::check_access();

        $variation_id = self::get_int( 'variation_id' );
        if ( ! $variation_id ) {
            wp_send_json_error( array( 'message' => 'Variation ID is required.' ) );
        }

        $variation = wc_get_product( $variation_id );
        if ( ! $variation || $variation->get_type() !== 'variation' ) {
            wp_send_json_error( array( 'message' => 'Variation not found.' ) );
        }

        $parent_id = $variation->get_parent_id();
        $variation->delete( true );
        \WC_Product_Variable::sync( $parent_id );

        wp_send_json_success( array( 'message' => 'Variation deleted.' ) );
    }

    /* ================================================================
     * Product Analytics — Top Products by Revenue
     * ============================================================= */

    public static function product_analytics() {
        self::check_access();

        $period = self::get_param( 'period', '30' );
        $limit  = self::get_int( 'limit', 10 );

        $date_from = date( 'Y-m-d', strtotime( '-' . absint( $period ) . ' days' ) );
        $today     = date( 'Y-m-d' );

        $orders = wc_get_orders( array(
            'limit'        => -1,
            'status'       => array( 'completed', 'processing', 'kctm-delivered' ),
            'date_created' => $date_from . '...' . $today . ' 23:59:59',
        ) );

        $product_data = array();

        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                $pid = $item->get_product_id();
                if ( ! $pid ) {
                    continue;
                }

                if ( ! isset( $product_data[ $pid ] ) ) {
                    $product_data[ $pid ] = array(
                        'product_id'   => $pid,
                        'name'         => $item->get_name(),
                        'quantity'     => 0,
                        'order_count'  => 0,
                        'revenue'      => 0,
                        'orders_seen'  => array(),
                    );
                }

                $product_data[ $pid ]['quantity'] += $item->get_quantity();
                $product_data[ $pid ]['revenue']  += floatval( $item->get_total() );

                if ( ! in_array( $order->get_id(), $product_data[ $pid ]['orders_seen'], true ) ) {
                    $product_data[ $pid ]['order_count']++;
                    $product_data[ $pid ]['orders_seen'][] = $order->get_id();
                }
            }
        }

        /* Sort by revenue DESC */
        usort( $product_data, function( $a, $b ) {
            return $b['revenue'] <=> $a['revenue'];
        } );

        /* Slice to limit */
        $product_data = array_slice( $product_data, 0, $limit );

        /* Add image URL */
        foreach ( $product_data as &$item ) {
            unset( $item['orders_seen'] );
            $product = wc_get_product( $item['product_id'] );
            if ( $product ) {
                $image_id = $product->get_image_id();
                $item['image'] = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
            } else {
                $item['image'] = '';
            }
            $item['revenue'] = round( $item['revenue'], 2 );
        }
        unset( $item );

        wp_send_json_success( array(
            'top_products' => $product_data,
            'period'       => absint( $period ),
            'currency'     => get_woocommerce_currency_symbol(),
        ) );
    }

    /* ================================================================
     * Coupons — List
     * ============================================================= */

    public static function coupons() {
        self::check_access();

        $page = max( 1, self::get_int( 'page', 1 ) );
        $per  = 20;

        $posts = get_posts( array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => $per,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'any',
        ) );

        $total_query = new WP_Query( array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );
        $total = $total_query->found_posts;

        $items = array();
        foreach ( $posts as $post ) {
            $coupon = new WC_Coupon( $post->ID );

            $items[] = array(
                'id'                 => $coupon->get_id(),
                'code'               => $coupon->get_code(),
                'type'               => $coupon->get_discount_type(),
                'amount'             => $coupon->get_amount(),
                'usage_count'        => $coupon->get_usage_count(),
                'usage_limit'        => $coupon->get_usage_limit(),
                'date_expires'       => $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : '',
                'individual_use'     => $coupon->get_individual_use(),
                'minimum_amount'     => $coupon->get_minimum_amount(),
                'maximum_amount'     => $coupon->get_maximum_amount(),
                'product_ids'        => $coupon->get_product_ids(),
                'email_restrictions' => $coupon->get_email_restrictions(),
            );
        }

        wp_send_json_success( array(
            'coupons' => $items,
            'total'   => $total,
            'pages'   => ceil( $total / $per ),
        ) );
    }

    /* ================================================================
     * Coupons — Save (Create or Update)
     * ============================================================= */

    public static function save_coupon() {
        self::check_access();

        $coupon_id = self::get_int( 'coupon_id' );
        $code      = self::get_param( 'code' );

        if ( empty( $code ) ) {
            wp_send_json_error( array( 'message' => 'Coupon code is required.' ) );
        }

        if ( $coupon_id ) {
            $coupon = new WC_Coupon( $coupon_id );
            if ( ! $coupon->get_id() ) {
                wp_send_json_error( array( 'message' => 'Coupon not found.' ) );
            }
        } else {
            $coupon = new WC_Coupon();
        }

        $coupon->set_code( $code );

        $discount_type = self::get_param( 'discount_type', 'percent' );
        if ( in_array( $discount_type, array( 'percent', 'fixed_cart', 'fixed_product' ), true ) ) {
            $coupon->set_discount_type( $discount_type );
        }

        $amount = self::get_param( 'amount' );
        if ( '' !== $amount ) {
            $coupon->set_amount( floatval( $amount ) );
        }

        $individual_use = self::get_param( 'individual_use' );
        $coupon->set_individual_use( 'yes' === $individual_use );

        $usage_limit = self::get_param( 'usage_limit' );
        if ( '' !== $usage_limit ) {
            $coupon->set_usage_limit( absint( $usage_limit ) );
        }

        $usage_limit_per_user = self::get_param( 'usage_limit_per_user' );
        if ( '' !== $usage_limit_per_user ) {
            $coupon->set_usage_limit_per_user( absint( $usage_limit_per_user ) );
        }

        $date_expires = self::get_param( 'date_expires' );
        $coupon->set_date_expires( ! empty( $date_expires ) ? $date_expires : null );

        $minimum_amount = self::get_param( 'minimum_amount' );
        if ( '' !== $minimum_amount ) {
            $coupon->set_minimum_amount( floatval( $minimum_amount ) );
        }

        $maximum_amount = self::get_param( 'maximum_amount' );
        if ( '' !== $maximum_amount ) {
            $coupon->set_maximum_amount( floatval( $maximum_amount ) );
        }

        $free_shipping = self::get_param( 'free_shipping' );
        $coupon->set_free_shipping( 'yes' === $free_shipping );

        $product_ids_str = self::get_param( 'product_ids' );
        if ( ! empty( $product_ids_str ) ) {
            $coupon->set_product_ids( array_map( 'absint', explode( ',', $product_ids_str ) ) );
        } else {
            $coupon->set_product_ids( array() );
        }

        $coupon->save();

        wp_send_json_success( array(
            'message'   => $coupon_id ? 'Coupon updated.' : 'Coupon created.',
            'coupon_id' => $coupon->get_id(),
        ) );
    }

    /* ================================================================
     * Coupons — Delete
     * ============================================================= */

    public static function delete_coupon() {
        self::check_access();

        $coupon_id = self::get_int( 'coupon_id' );
        if ( ! $coupon_id ) {
            wp_send_json_error( array( 'message' => 'Coupon ID is required.' ) );
        }

        $post = get_post( $coupon_id );
        if ( ! $post || 'shop_coupon' !== $post->post_type ) {
            wp_send_json_error( array( 'message' => 'Coupon not found.' ) );
        }

        wp_delete_post( $coupon_id, true );

        wp_send_json_success( array( 'message' => 'Coupon deleted.' ) );
    }

    /* ================================================================
     * Reviews — List
     * ============================================================= */

    public static function reviews() {
        self::check_access();

        $page   = max( 1, self::get_int( 'page', 1 ) );
        $per    = 20;
        $status = self::get_param( 'status', 'all' );

        $args = array(
            'type'   => 'review',
            'number' => $per,
            'offset' => ( $page - 1 ) * $per,
            'orderby' => 'comment_date_gmt',
            'order'   => 'DESC',
        );

        if ( 'approved' === $status ) {
            $args['status'] = 'approve';
        } elseif ( 'pending' === $status ) {
            $args['status'] = 'hold';
        } else {
            $args['status'] = 'all';
        }

        $comments = get_comments( $args );

        $count_args = $args;
        $count_args['count'] = true;
        unset( $count_args['number'], $count_args['offset'] );
        $total = get_comments( $count_args );

        $items = array();
        foreach ( $comments as $comment ) {
            $product_id   = $comment->comment_post_ID;
            $product      = wc_get_product( $product_id );
            $product_name = $product ? $product->get_name() : '(Deleted product)';
            $rating       = (int) get_comment_meta( $comment->comment_ID, 'rating', true );

            $items[] = array(
                'id'           => (int) $comment->comment_ID,
                'product_name' => $product_name,
                'product_id'   => (int) $product_id,
                'author'       => $comment->comment_author,
                'author_email' => $comment->comment_author_email,
                'date'         => $comment->comment_date,
                'content'      => $comment->comment_content,
                'rating'       => $rating,
                'approved'     => '1' === $comment->comment_approved,
            );
        }

        wp_send_json_success( array(
            'reviews' => $items,
            'total'   => (int) $total,
            'pages'   => ceil( $total / $per ),
        ) );
    }

    /* ================================================================
     * Reviews — Approve / Unapprove / Delete / Reply
     * ============================================================= */

    public static function update_review() {
        self::check_access();

        $comment_id    = self::get_int( 'comment_id' );
        $review_action = self::get_param( 'review_action' );

        if ( ! $comment_id || empty( $review_action ) ) {
            wp_send_json_error( array( 'message' => 'Comment ID and action are required.' ) );
        }

        $comment = get_comment( $comment_id );
        if ( ! $comment ) {
            wp_send_json_error( array( 'message' => 'Review not found.' ) );
        }

        switch ( $review_action ) {
            case 'approve':
                wp_set_comment_status( $comment_id, 'approve' );
                $msg = 'Review approved.';
                break;

            case 'unapprove':
                wp_set_comment_status( $comment_id, 'hold' );
                $msg = 'Review unapproved.';
                break;

            case 'delete':
                wp_delete_comment( $comment_id, true );
                $msg = 'Review deleted.';
                break;

            case 'reply':
                $reply_content = isset( $_POST['reply_content'] ) ? wp_kses_post( wp_unslash( $_POST['reply_content'] ) ) : '';
                if ( empty( $reply_content ) ) {
                    wp_send_json_error( array( 'message' => 'Reply content is required.' ) );
                }

                $current_user = wp_get_current_user();
                $reply_id = wp_insert_comment( array(
                    'comment_post_ID' => $comment->comment_post_ID,
                    'comment_parent'  => $comment_id,
                    'comment_content' => $reply_content,
                    'comment_author'  => $current_user->display_name,
                    'comment_author_email' => $current_user->user_email,
                    'comment_approved'     => 1,
                    'comment_type'         => 'review',
                    'user_id'              => $current_user->ID,
                ) );

                if ( ! $reply_id ) {
                    wp_send_json_error( array( 'message' => 'Failed to post reply.' ) );
                }

                $msg = 'Reply posted.';
                break;

            default:
                wp_send_json_error( array( 'message' => 'Unknown action.' ) );
                return;
        }

        wp_send_json_success( array( 'message' => $msg ) );
    }

    /* ================================================================
     * Reviews — Rating Distribution Stats
     * ============================================================= */

    public static function review_stats() {
        self::check_access();

        global $wpdb;

        $distribution = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 );

        $results = $wpdb->get_results(
            "SELECT cm.meta_value AS rating, COUNT(*) AS cnt
             FROM {$wpdb->comments} c
             INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_ID
             WHERE c.comment_type = 'review'
               AND c.comment_approved = '1'
               AND cm.meta_key = 'rating'
             GROUP BY cm.meta_value"
        );

        $total_count  = 0;
        $rating_sum   = 0;

        foreach ( $results as $row ) {
            $star = (int) $row->rating;
            $cnt  = (int) $row->cnt;
            if ( $star >= 1 && $star <= 5 ) {
                $distribution[ $star ] = $cnt;
                $total_count += $cnt;
                $rating_sum  += $star * $cnt;
            }
        }

        $average = $total_count > 0 ? round( $rating_sum / $total_count, 2 ) : 0;

        wp_send_json_success( array(
            'distribution' => $distribution,
            'total'        => $total_count,
            'average'      => $average,
        ) );
    }

    /* ================================================================
     * Customer Tags — Set Tags
     * ============================================================= */

    public static function customer_tags() {
        self::check_access();

        $customer_id = self::get_int( 'customer_id' );
        $tags_str    = self::get_param( 'tags' );

        if ( ! $customer_id ) {
            wp_send_json_error( array( 'message' => 'Customer ID is required.' ) );
        }

        $user = get_userdata( $customer_id );
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => 'Customer not found.' ) );
        }

        $tags = array();
        if ( ! empty( $tags_str ) ) {
            $tags = array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', $tags_str ) ) );
            $tags = array_filter( $tags );
            $tags = array_values( array_unique( $tags ) );
        }

        update_user_meta( $customer_id, '_kctm_customer_tags', $tags );

        wp_send_json_success( array(
            'message' => 'Customer tags updated.',
            'tags'    => $tags,
        ) );
    }

    /* ================================================================
     * Customer Tags — Bulk Add / Remove
     * ============================================================= */

    public static function bulk_customer_tags() {
        self::check_access();

        $customer_ids_str = self::get_param( 'customer_ids' );
        $action           = self::get_param( 'action' );
        $tag              = self::get_param( 'tag' );

        if ( empty( $customer_ids_str ) || empty( $action ) || empty( $tag ) ) {
            wp_send_json_error( array( 'message' => 'Customer IDs, action, and tag are required.' ) );
        }

        if ( ! in_array( $action, array( 'add', 'remove' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Action must be add or remove.' ) );
        }

        $customer_ids = array_map( 'absint', explode( ',', $customer_ids_str ) );
        $tag          = sanitize_text_field( $tag );
        $count        = 0;

        foreach ( $customer_ids as $cid ) {
            if ( ! $cid ) {
                continue;
            }

            $existing = get_user_meta( $cid, '_kctm_customer_tags', true );
            if ( ! is_array( $existing ) ) {
                $existing = array();
            }

            if ( 'add' === $action ) {
                if ( ! in_array( $tag, $existing, true ) ) {
                    $existing[] = $tag;
                }
            } else {
                $existing = array_values( array_diff( $existing, array( $tag ) ) );
            }

            update_user_meta( $cid, '_kctm_customer_tags', $existing );
            $count++;
        }

        wp_send_json_success( array(
            'message' => 'Tags updated for ' . $count . ' customer(s).',
            'count'   => $count,
        ) );
    }

    /* ================================================================
     * Settings — Get All
     * ============================================================= */

    public static function get_settings() {
        self::check_access();

        $store_settings        = get_option( 'kctm_store_settings', array() );
        $whatsapp_settings     = get_option( 'kctm_whatsapp_settings', array() );
        $consultation_settings = get_option( 'kctm_consultation_settings', array() );

        wp_send_json_success( array(
            'store'        => $store_settings,
            'whatsapp'     => $whatsapp_settings,
            'consultation' => $consultation_settings,
        ) );
    }

    /* ================================================================
     * Settings — Save
     * ============================================================= */

    public static function save_settings() {
        self::check_access();

        $group = self::get_param( 'settings_group' );

        if ( empty( $group ) ) {
            wp_send_json_error( array( 'message' => 'Settings group is required.' ) );
        }

        switch ( $group ) {
            case 'store':
                $settings = array(
                    'store_name'       => self::get_param( 'store_name' ),
                    'store_phone'      => self::get_param( 'store_phone' ),
                    'store_email'      => self::get_param( 'store_email' ),
                    'store_address'    => self::get_param( 'store_address' ),
                    'default_currency' => self::get_param( 'default_currency' ),
                    'tax_rate'         => self::get_param( 'tax_rate' ),
                    'openai_api_key'   => sanitize_text_field( self::get_param( 'openai_api_key' ) ),
                );
                update_option( 'kctm_store_settings', $settings );
                break;

            case 'whatsapp':
                $settings = array(
                    'whatsapp_phone_id'    => self::get_param( 'whatsapp_phone_id' ),
                    'whatsapp_token'       => self::get_param( 'whatsapp_token' ),
                    'whatsapp_verify_token' => self::get_param( 'whatsapp_verify_token' ),
                    'whatsapp_business_id' => self::get_param( 'whatsapp_business_id' ),
                );
                update_option( 'kctm_whatsapp_settings', $settings );
                break;

            case 'consultation':
                $settings = array(
                    'consultation_duration' => self::get_param( 'consultation_duration' ),
                    'consultation_price'    => self::get_param( 'consultation_price' ),
                    'consultation_enabled'  => self::get_param( 'consultation_enabled' ),
                );
                update_option( 'kctm_consultation_settings', $settings );
                break;

            case 'notifications':
                $statuses = isset( $_POST['notification_statuses'] ) ? array_map( 'sanitize_text_field', (array) $_POST['notification_statuses'] ) : array();
                update_option( 'kctm_notification_statuses', $statuses );
                break;

            default:
                wp_send_json_error( array( 'message' => 'Unknown settings group.' ) );
                return;
        }

        wp_send_json_success( array( 'message' => ucfirst( $group ) . ' settings saved.' ) );
    }

    /* ================================================================
     * WhatsApp Templates — List
     * ============================================================= */

    public static function whatsapp_templates() {
        self::check_access();

        $templates = get_option( 'kctm_whatsapp_templates', array() );

        if ( empty( $templates ) ) {
            $templates = array(
                array(
                    'id'           => 'order_ready',
                    'name'         => 'Order Ready',
                    'message'      => 'Hello {customer_name}, your order #{order_id} is ready for pickup. Thank you for choosing Kevin Cho!',
                    'placeholders' => array( 'customer_name', 'order_id' ),
                ),
                array(
                    'id'           => 'payment_reminder',
                    'name'         => 'Payment Reminder',
                    'message'      => 'Hello {customer_name}, this is a friendly reminder that your payment of {amount} for order #{order_id} is pending.',
                    'placeholders' => array( 'customer_name', 'amount', 'order_id' ),
                ),
                array(
                    'id'           => 'holiday_greeting',
                    'name'         => 'Holiday Greeting',
                    'message'      => 'Happy holidays, {customer_name}! Wishing you a wonderful season from the Kevin Cho team.',
                    'placeholders' => array( 'customer_name' ),
                ),
                array(
                    'id'           => 'appointment_reminder',
                    'name'         => 'Appointment Reminder',
                    'message'      => 'Hello {customer_name}, this is a reminder of your consultation on {date} at {time}. See you soon!',
                    'placeholders' => array( 'customer_name', 'date', 'time' ),
                ),
                array(
                    'id'           => 'thank_you',
                    'name'         => 'Thank You',
                    'message'      => 'Thank you for your order, {customer_name}! We appreciate your business. Your order #{order_id} is being prepared.',
                    'placeholders' => array( 'customer_name', 'order_id' ),
                ),
            );
        }

        wp_send_json_success( array( 'templates' => $templates ) );
    }

    /* ================================================================
     * WhatsApp Templates — Save
     * ============================================================= */

    public static function save_whatsapp_template() {
        self::check_access();

        $template_id = self::get_param( 'template_id' );
        $name        = self::get_param( 'name' );
        $message     = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        if ( empty( $name ) || empty( $message ) ) {
            wp_send_json_error( array( 'message' => 'Template name and message are required.' ) );
        }

        $templates = get_option( 'kctm_whatsapp_templates', array() );

        /* Extract placeholders from message */
        preg_match_all( '/\{(\w+)\}/', $message, $matches );
        $placeholders = ! empty( $matches[1] ) ? array_values( array_unique( $matches[1] ) ) : array();

        if ( empty( $template_id ) ) {
            $template_id = sanitize_title( $name ) . '_' . time();
        }

        $found = false;
        foreach ( $templates as &$tpl ) {
            if ( $tpl['id'] === $template_id ) {
                $tpl['name']         = $name;
                $tpl['message']      = $message;
                $tpl['placeholders'] = $placeholders;
                $found = true;
                break;
            }
        }
        unset( $tpl );

        if ( ! $found ) {
            $templates[] = array(
                'id'           => $template_id,
                'name'         => $name,
                'message'      => $message,
                'placeholders' => $placeholders,
            );
        }

        update_option( 'kctm_whatsapp_templates', $templates );

        wp_send_json_success( array(
            'message'     => 'Template saved.',
            'template_id' => $template_id,
        ) );
    }

    /* ================================================================
     * WhatsApp Templates — Delete
     * ============================================================= */

    public static function delete_whatsapp_template() {
        self::check_access();

        $template_id = self::get_param( 'template_id' );

        if ( empty( $template_id ) ) {
            wp_send_json_error( array( 'message' => 'Template ID is required.' ) );
        }

        $templates = get_option( 'kctm_whatsapp_templates', array() );
        $filtered  = array();
        $removed   = false;

        foreach ( $templates as $tpl ) {
            if ( $tpl['id'] === $template_id ) {
                $removed = true;
                continue;
            }
            $filtered[] = $tpl;
        }

        if ( ! $removed ) {
            wp_send_json_error( array( 'message' => 'Template not found.' ) );
        }

        update_option( 'kctm_whatsapp_templates', $filtered );

        wp_send_json_success( array( 'message' => 'Template deleted.' ) );
    }

    /* ================================================================
     * WhatsApp Templates — Send Test Message
     * ============================================================= */

    public static function send_whatsapp_test() {
        self::check_access();

        $template_id = self::get_param( 'template_id' );
        $phone       = self::get_param( 'phone' );

        if ( empty( $template_id ) || empty( $phone ) ) {
            wp_send_json_error( array( 'message' => 'Template ID and phone number are required.' ) );
        }

        $templates = get_option( 'kctm_whatsapp_templates', array() );
        $template  = null;

        foreach ( $templates as $tpl ) {
            if ( $tpl['id'] === $template_id ) {
                $template = $tpl;
                break;
            }
        }

        if ( ! $template ) {
            wp_send_json_error( array( 'message' => 'Template not found.' ) );
        }

        /* Replace placeholders with test values */
        $test_values = array(
            'customer_name' => 'Test Customer',
            'order_id'      => '12345',
            'amount'        => get_woocommerce_currency_symbol() . '100.00',
            'date'          => date( 'Y-m-d' ),
            'time'          => '10:00 AM',
            'product_name'  => 'Sample Product',
            'tracking_url'  => site_url( '/tracking/test' ),
        );

        $message = $template['message'];
        foreach ( $test_values as $key => $value ) {
            $message = str_replace( '{' . $key . '}', $value, $message );
        }

        /* Replace any remaining placeholders with placeholder name */
        $message = preg_replace( '/\{(\w+)\}/', '[$1]', $message );

        if ( class_exists( 'KCTM_WhatsApp_API' ) ) {
            $result = KCTM_WhatsApp_API::send_text_message( $phone, $message );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            }
        } else {
            wp_send_json_error( array( 'message' => 'WhatsApp API class not available. Message preview: ' . $message ) );
        }

        wp_send_json_success( array(
            'message'  => 'Test message sent.',
            'preview'  => $message,
        ) );
    }

    /* ================================================================
     * Expenses — List
     * ============================================================= */

    public static function expenses() {
        self::check_access();

        global $wpdb;

        $page      = max( 1, self::get_int( 'page', 1 ) );
        $per       = 20;
        $category  = self::get_param( 'category' );
        $date_from = self::get_param( 'date_from' );
        $date_to   = self::get_param( 'date_to' );
        $search    = self::get_param( 'search' );
        $table     = $wpdb->prefix . 'kctm_expenses';

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $category ) ) {
            $where[]  = 'category = %s';
            $values[] = $category;
        }

        if ( ! empty( $date_from ) ) {
            $where[]  = 'expense_date >= %s';
            $values[] = $date_from;
        }

        if ( ! empty( $date_to ) ) {
            $where[]  = 'expense_date <= %s';
            $values[] = $date_to;
        }

        if ( ! empty( $search ) ) {
            $where[]  = 'description LIKE %s';
            $values[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $where_sql = implode( ' AND ', $where );

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        if ( ! empty( $values ) ) {
            $count_sql = $wpdb->prepare( $count_sql, $values );
        }
        $total = (int) $wpdb->get_var( $count_sql );

        $offset    = ( $page - 1 ) * $per;
        $query_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY expense_date DESC, id DESC LIMIT %d OFFSET %d";
        $query_values = array_merge( $values, array( $per, $offset ) );
        $items = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_values ) );

        wp_send_json_success( array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil( $total / $per ),
        ) );
    }

    /* ================================================================
     * Expenses — Save (Create or Update)
     * ============================================================= */

    public static function save_expense() {
        self::check_access();

        global $wpdb;

        $expense_id   = self::get_int( 'expense_id' );
        $expense_date = self::get_param( 'expense_date' );
        $category     = self::get_param( 'category' );
        $description  = self::get_param( 'description' );
        $amount       = self::get_param( 'amount' );
        $currency     = self::get_param( 'currency', 'KES' );
        $receipt_url  = esc_url_raw( self::get_param( 'receipt_url' ) );
        $table        = $wpdb->prefix . 'kctm_expenses';

        if ( empty( $expense_date ) || empty( $category ) || '' === $amount ) {
            wp_send_json_error( array( 'message' => 'Date, category, and amount are required.' ) );
        }

        $valid_categories = array( 'fabric', 'rent', 'utilities', 'shipping', 'salary', 'marketing', 'other' );
        if ( ! in_array( $category, $valid_categories, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid expense category.' ) );
        }

        $data = array(
            'expense_date' => $expense_date,
            'category'     => $category,
            'description'  => $description,
            'amount'       => floatval( $amount ),
            'currency'     => $currency,
            'receipt_url'  => $receipt_url,
        );

        $format = array( '%s', '%s', '%s', '%f', '%s', '%s' );

        if ( $expense_id ) {
            $result = $wpdb->update( $table, $data, array( 'id' => $expense_id ), $format, array( '%d' ) );
            if ( false === $result ) {
                wp_send_json_error( array( 'message' => 'Failed to update expense.' ) );
            }
            $msg = 'Expense updated.';
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $format[]           = '%s';
            $result = $wpdb->insert( $table, $data, $format );
            if ( false === $result ) {
                wp_send_json_error( array( 'message' => 'Failed to create expense.' ) );
            }
            $expense_id = $wpdb->insert_id;
            $msg        = 'Expense created.';
        }

        wp_send_json_success( array(
            'message'    => $msg,
            'expense_id' => $expense_id,
        ) );
    }

    /* ================================================================
     * Expenses — Delete
     * ============================================================= */

    public static function delete_expense() {
        self::check_access();

        global $wpdb;

        $expense_id = self::get_int( 'expense_id' );
        if ( ! $expense_id ) {
            wp_send_json_error( array( 'message' => 'Expense ID is required.' ) );
        }

        $table  = $wpdb->prefix . 'kctm_expenses';
        $result = $wpdb->delete( $table, array( 'id' => $expense_id ), array( '%d' ) );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Failed to delete expense.' ) );
        }

        wp_send_json_success( array( 'message' => 'Expense deleted.' ) );
    }

    /* ================================================================
     * Expenses — Summary by Category and Period
     * ============================================================= */

    public static function expense_summary() {
        self::check_access();

        global $wpdb;

        $period    = absint( self::get_param( 'period', '30' ) );
        $date_from = date( 'Y-m-d', strtotime( '-' . $period . ' days' ) );
        $today     = date( 'Y-m-d' );
        $table     = $wpdb->prefix . 'kctm_expenses';

        /* Expenses by category */
        $category_totals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT category, SUM(amount) AS total, COUNT(*) AS count
                 FROM {$table}
                 WHERE expense_date >= %s AND expense_date <= %s
                 GROUP BY category
                 ORDER BY total DESC",
                $date_from,
                $today
            )
        );

        $total_expenses = 0;
        $by_category    = array();
        foreach ( $category_totals as $row ) {
            $by_category[] = array(
                'category' => $row->category,
                'total'    => round( floatval( $row->total ), 2 ),
                'count'    => (int) $row->count,
            );
            $total_expenses += floatval( $row->total );
        }

        /* Revenue from WC orders in the same period */
        $orders = wc_get_orders( array(
            'limit'        => -1,
            'status'       => array( 'completed', 'processing', 'kctm-delivered' ),
            'date_created' => $date_from . '...' . $today . ' 23:59:59',
        ) );

        $total_revenue = 0;
        foreach ( $orders as $order ) {
            $total_revenue += floatval( $order->get_total() );
        }

        wp_send_json_success( array(
            'by_category'    => $by_category,
            'total_expenses' => round( $total_expenses, 2 ),
            'total_revenue'  => round( $total_revenue, 2 ),
            'profit'         => round( $total_revenue - $total_expenses, 2 ),
            'period'         => $period,
            'currency'       => get_woocommerce_currency_symbol(),
        ) );
    }

    /* ================================================================
     * Staff — List
     * ============================================================= */

    public static function staff() {
        self::check_tailor_access();

        global $wpdb;

        $table = $wpdb->prefix . 'kctm_staff';

        /* Tailors only see their own profile. */
        if ( self::is_tailor() ) {
            $my_staff_id = self::get_current_staff_id();
            if ( $my_staff_id ) {
                $rows = $wpdb->get_results( $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE id = %d",
                    $my_staff_id
                ) );
            } else {
                $rows = array();
            }
        } else {
            $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );
        }

        $items = array();
        foreach ( $rows as $row ) {
            /* Count orders assigned to this staff member (HPOS-compatible). */
            $assigned_orders = wc_get_orders( array(
                'meta_key'   => '_kctm_assigned_tailor',
                'meta_value' => (string) $row->id,
                'return'     => 'ids',
                'limit'      => -1,
            ) );
            $order_count = count( $assigned_orders );

            $item               = (array) $row;
            $item['order_count'] = $order_count;
            $items[]            = $item;
        }

        wp_send_json_success( array( 'staff' => $items ) );
    }

    /* ================================================================
     * Staff — Save (Create or Update)
     * ============================================================= */

    public static function save_staff() {
        self::check_access();

        global $wpdb;

        $staff_id       = self::get_int( 'staff_id' );
        $name           = self::get_param( 'name' );
        $phone          = self::get_param( 'phone' );
        $email          = self::get_param( 'email' );
        $role           = self::get_param( 'role', 'tailor' );
        $specialization = self::get_param( 'specialization' );
        $is_active      = self::get_param( 'is_active', '1' );
        $table          = $wpdb->prefix . 'kctm_staff';

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Staff name is required.' ) );
        }

        $valid_roles = array( 'tailor', 'manager', 'assistant' );
        if ( ! in_array( $role, $valid_roles, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid staff role.' ) );
        }

        $data = array(
            'name'           => $name,
            'phone'          => $phone,
            'email'          => $email,
            'role'           => $role,
            'specialization' => $specialization,
            'is_active'      => absint( $is_active ),
        );

        $format = array( '%s', '%s', '%s', '%s', '%s', '%d' );

        if ( $staff_id ) {
            $result = $wpdb->update( $table, $data, array( 'id' => $staff_id ), $format, array( '%d' ) );
            if ( false === $result ) {
                wp_send_json_error( array( 'message' => 'Failed to update staff member.' ) );
            }

            /* Sync WP user profile if linked. */
            $linked_user_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT user_id FROM {$table} WHERE id = %d",
                $staff_id
            ) );
            if ( $linked_user_id ) {
                wp_update_user( array(
                    'ID'           => $linked_user_id,
                    'display_name' => $name,
                    'user_email'   => $email,
                ) );
            }

            $msg = 'Staff member updated.';
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $format[]           = '%s';
            $result = $wpdb->insert( $table, $data, $format );
            if ( false === $result ) {
                wp_send_json_error( array( 'message' => 'Failed to create staff member.' ) );
            }
            $staff_id = $wpdb->insert_id;
            $msg      = 'Staff member created.';
        }

        /* Optionally create a WordPress user account for this staff member. */
        $create_account = self::get_param( 'create_account' );
        $account_msg    = '';
        if ( $create_account && ! empty( $email ) ) {
            /* Check if already linked. */
            $existing_user_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT user_id FROM {$table} WHERE id = %d",
                $staff_id
            ) );
            if ( ! $existing_user_id ) {
                /* Check if email is already a WP user. */
                $wp_user = get_user_by( 'email', $email );
                if ( $wp_user ) {
                    /* Link existing user and set role. */
                    $wp_user->set_role( 'kctm_tailor' );
                    $wpdb->update( $table, array( 'user_id' => $wp_user->ID ), array( 'id' => $staff_id ), array( '%d' ), array( '%d' ) );
                    $account_msg = ' Linked to existing WordPress user.';
                } else {
                    /* Create new WP user. */
                    $username = sanitize_user( strtolower( str_replace( ' ', '.', $name ) ), true );
                    if ( username_exists( $username ) ) {
                        $username .= wp_rand( 10, 99 );
                    }
                    $password   = wp_generate_password( 12 );
                    $new_user_id = wp_insert_user( array(
                        'user_login'   => $username,
                        'user_email'   => $email,
                        'user_pass'    => $password,
                        'display_name' => $name,
                        'role'         => 'kctm_tailor',
                    ) );
                    if ( ! is_wp_error( $new_user_id ) ) {
                        $wpdb->update( $table, array( 'user_id' => $new_user_id ), array( 'id' => $staff_id ), array( '%d' ), array( '%d' ) );
                        /* Send credentials via email. */
                        $portal_url = home_url( '/store-manager/' );
                        $body  = "Hello {$name},\n\n";
                        $body .= "A portal account has been created for you at Kevin Cho.\n\n";
                        $body .= "Username: {$username}\n";
                        $body .= "Password: {$password}\n";
                        $body .= "Portal: {$portal_url}\n\n";
                        $body .= "Please change your password after first login.\n\nKevin Cho Team";
                        wp_mail( $email, 'Your Kevin Cho Portal Account', $body );
                        $account_msg = ' WordPress account created — credentials sent to ' . $email . '.';
                    } else {
                        $account_msg = ' Could not create WP user: ' . $new_user_id->get_error_message();
                    }
                }
            } else {
                $account_msg = ' Already has a linked WordPress account.';
            }
        }

        wp_send_json_success( array(
            'message'  => $msg . $account_msg,
            'staff_id' => $staff_id,
        ) );
    }

    /* ================================================================
     * Staff — Delete
     * ============================================================= */

    public static function delete_staff() {
        self::check_access();

        global $wpdb;

        $staff_id = self::get_int( 'staff_id' );
        if ( ! $staff_id ) {
            wp_send_json_error( array( 'message' => 'Staff ID is required.' ) );
        }

        $table  = $wpdb->prefix . 'kctm_staff';
        $result = $wpdb->delete( $table, array( 'id' => $staff_id ), array( '%d' ) );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Failed to delete staff member.' ) );
        }

        wp_send_json_success( array( 'message' => 'Staff member deleted.' ) );
    }

    /* ================================================================
     * Staff — Workload per Tailor
     * ============================================================= */

    public static function staff_workload() {
        self::check_access();

        global $wpdb;

        $table = $wpdb->prefix . 'kctm_staff';

        /* Get active tailors */
        $tailors = $wpdb->get_results(
            "SELECT id, name FROM {$table} WHERE role = 'tailor' AND is_active = 1 ORDER BY name ASC"
        );

        $workload = array();

        foreach ( $tailors as $tailor ) {
            /* Get order IDs assigned to this tailor (HPOS-compatible). */
            $order_ids = wc_get_orders( array(
                'meta_key'   => '_kctm_assigned_tailor',
                'meta_value' => (string) $tailor->id,
                'return'     => 'ids',
                'limit'      => -1,
            ) );

            $stages = array(
                'pending'    => 0,
                'cutting'    => 0,
                'sewing'     => 0,
                'finishing'  => 0,
                'quality_check' => 0,
                'completed'  => 0,
            );

            foreach ( $order_ids as $order_id ) {
                $order = wc_get_order( $order_id );
                $stage = $order ? $order->get_meta( '_kctm_production_stage' ) : '';
                if ( ! empty( $stage ) && isset( $stages[ $stage ] ) ) {
                    $stages[ $stage ]++;
                } elseif ( ! empty( $stage ) ) {
                    $stages[ $stage ] = 1;
                } else {
                    $stages['pending']++;
                }
            }

            $workload[] = array(
                'staff_id'    => (int) $tailor->id,
                'name'        => $tailor->name,
                'total_orders' => count( $order_ids ),
                'stages'      => $stages,
            );
        }

        wp_send_json_success( array( 'workload' => $workload ) );
    }

    /* ================================================================
     * Abandoned Carts — List
     * ============================================================= */

    public static function abandoned_carts() {
        self::check_access();

        global $wpdb;

        $page   = max( 1, self::get_int( 'page', 1 ) );
        $per    = 20;
        $status = self::get_param( 'status' );
        $table  = $wpdb->prefix . 'kctm_abandoned_carts';

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $status ) ) {
            $where[]  = 'status = %s';
            $values[] = $status;
        }

        $where_sql = implode( ' AND ', $where );

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        if ( ! empty( $values ) ) {
            $count_sql = $wpdb->prepare( $count_sql, $values );
        }
        $total = (int) $wpdb->get_var( $count_sql );

        $offset       = ( $page - 1 ) * $per;
        $query_sql    = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_values = array_merge( $values, array( $per, $offset ) );
        $rows         = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_values ) );

        $items = array();
        foreach ( $rows as $row ) {
            $item                  = (array) $row;
            $item['cart_contents'] = ! empty( $row->cart_contents ) ? json_decode( $row->cart_contents, true ) : array();
            $items[]               = $item;
        }

        wp_send_json_success( array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil( $total / $per ),
        ) );
    }

    /* ================================================================
     * Abandoned Carts — Send Reminder Email
     * ============================================================= */

    public static function send_cart_reminder() {
        self::check_access();

        global $wpdb;

        $cart_id = self::get_int( 'cart_id' );
        if ( ! $cart_id ) {
            wp_send_json_error( array( 'message' => 'Cart ID is required.' ) );
        }

        $table = $wpdb->prefix . 'kctm_abandoned_carts';
        $cart  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $cart_id ) );

        if ( ! $cart ) {
            wp_send_json_error( array( 'message' => 'Cart not found.' ) );
        }

        if ( empty( $cart->email ) ) {
            wp_send_json_error( array( 'message' => 'No email address for this cart.' ) );
        }

        /* Decode cart contents for product names */
        $contents     = ! empty( $cart->cart_contents ) ? json_decode( $cart->cart_contents, true ) : array();
        $product_list = array();
        $cart_total   = 0;

        if ( is_array( $contents ) ) {
            foreach ( $contents as $item ) {
                $product_name = isset( $item['product_name'] ) ? $item['product_name'] : '';
                if ( empty( $product_name ) && ! empty( $item['product_id'] ) ) {
                    $product = wc_get_product( $item['product_id'] );
                    $product_name = $product ? $product->get_name() : 'Product #' . $item['product_id'];
                }
                $qty = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
                $product_list[] = $product_name . ' x ' . $qty;

                if ( isset( $item['line_total'] ) ) {
                    $cart_total += floatval( $item['line_total'] );
                }
            }
        }

        /* Build recovery link */
        $recovery_url = add_query_arg( array(
            'kctm_recover_cart' => $cart_id,
            'token'             => wp_hash( $cart_id . $cart->email ),
        ), wc_get_cart_url() );

        /* Compose email */
        $store_name = get_bloginfo( 'name' );
        $subject    = sprintf( __( 'You left items in your cart at %s', 'kctm' ), $store_name );

        $body  = '<p>Hello,</p>';
        $body .= '<p>It looks like you left some items in your shopping cart:</p>';
        $body .= '<ul>';
        foreach ( $product_list as $item_line ) {
            $body .= '<li>' . esc_html( $item_line ) . '</li>';
        }
        $body .= '</ul>';
        if ( $cart_total > 0 ) {
            $body .= '<p><strong>Cart total: ' . get_woocommerce_currency_symbol() . number_format( $cart_total, 2 ) . '</strong></p>';
        }
        $body .= '<p><a href="' . esc_url( $recovery_url ) . '" style="display:inline-block;padding:10px 20px;background:#000;color:#fff;text-decoration:none;">Complete Your Order</a></p>';
        $body .= '<p>Thank you,<br>' . esc_html( $store_name ) . '</p>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $sent    = wp_mail( $cart->email, $subject, $body, $headers );

        if ( ! $sent ) {
            wp_send_json_error( array( 'message' => 'Failed to send reminder email.' ) );
        }

        /* Mark reminder as sent */
        $wpdb->update(
            $table,
            array( 'reminder_sent' => 1 ),
            array( 'id' => $cart_id ),
            array( '%d' ),
            array( '%d' )
        );

        wp_send_json_success( array( 'message' => 'Reminder email sent to ' . $cart->email . '.' ) );
    }

    /* ================================================================
     * Abandoned Carts — Delete
     * ============================================================= */

    public static function delete_abandoned_cart() {
        self::check_access();

        global $wpdb;

        $cart_id = self::get_int( 'cart_id' );
        if ( ! $cart_id ) {
            wp_send_json_error( array( 'message' => 'Cart ID is required.' ) );
        }

        $table  = $wpdb->prefix . 'kctm_abandoned_carts';
        $result = $wpdb->delete( $table, array( 'id' => $cart_id ), array( '%d' ) );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Failed to delete abandoned cart.' ) );
        }

        wp_send_json_success( array( 'message' => 'Abandoned cart deleted.' ) );
    }

    /* ================================================================
     * Production Board — Kanban view of orders by production stage
     * ============================================================= */

    /**
     * Get orders grouped by production stage for the Kanban board.
     */
    public static function production_board() {
        self::check_tailor_access();
        global $wpdb;

        $valid_stages = array( 'fabric_cutting', 'stitching', 'finishing', 'quality_check', 'ready_pickup' );
        $board        = array();
        foreach ( $valid_stages as $stage ) {
            $board[ $stage ] = array();
        }

        $staff_table = $wpdb->prefix . 'kctm_staff';

        /* If tailor, only show their assigned orders. */
        $tailor_filter_id = 0;
        if ( self::is_tailor() ) {
            $tailor_filter_id = self::get_current_staff_id();
        }

        /* Fetch orders in relevant statuses. */
        $orders = wc_get_orders( array(
            'limit'  => -1,
            'status' => array( 'processing', 'kctm-confirmed', 'kctm-in-progress', 'kctm-ready-pickup' ),
        ) );

        foreach ( $orders as $order ) {
            /* If tailor, skip orders not assigned to them. */
            if ( $tailor_filter_id ) {
                $assigned = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
                if ( $assigned !== $tailor_filter_id ) {
                    continue;
                }
            }
            $stage = $order->get_meta( '_kctm_production_stage' );

            /* Orders without a stage in processing/confirmed default to fabric_cutting. */
            if ( empty( $stage ) ) {
                $status = $order->get_status();
                if ( in_array( $status, array( 'processing', 'kctm-confirmed' ), true ) ) {
                    $stage = 'fabric_cutting';
                } else {
                    continue;
                }
            }

            if ( ! in_array( $stage, $valid_stages, true ) ) {
                continue;
            }

            /* Customer name. */
            $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            if ( empty( $customer_name ) ) {
                $customer_name = $order->get_billing_email();
            }

            /* Items summary: first item name + total count. */
            $items       = $order->get_items();
            $first_item  = '';
            $items_count = 0;
            foreach ( $items as $item ) {
                if ( empty( $first_item ) ) {
                    $first_item = $item->get_name();
                }
                $items_count += $item->get_quantity();
            }
            $items_summary = $first_item;
            if ( $items_count > 1 ) {
                $items_summary .= ' +' . ( $items_count - 1 ) . ' more';
            }

            /* Assigned tailor. */
            $tailor_id   = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
            $tailor_name = '';
            if ( $tailor_id ) {
                $tailor_name = $wpdb->get_var( $wpdb->prepare(
                    "SELECT name FROM {$staff_table} WHERE id = %d",
                    $tailor_id
                ) );
                if ( ! $tailor_name ) {
                    $tailor_name = '';
                }
            }

            $stage_updated = $order->get_meta( '_kctm_stage_updated_at' );

            $board[ $stage ][] = array(
                'id'               => $order->get_id(),
                'number'           => $order->get_order_number(),
                'customer'         => $customer_name,
                'items_summary'    => $items_summary,
                'date'             => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '',
                'assigned_tailor'  => array(
                    'id'   => $tailor_id,
                    'name' => $tailor_name,
                ),
                'stage'            => $stage,
                'stage_updated_at' => $stage_updated ? $stage_updated : '',
            );
        }

        wp_send_json_success( array(
            'board'  => $board,
            'stages' => $valid_stages,
        ) );
    }

    /**
     * Move an order to a new production stage.
     */
    public static function update_production_stage() {
        self::check_tailor_access();

        $order_id = self::get_int( 'order_id' );
        $stage    = self::get_param( 'stage' );

        if ( ! $order_id || empty( $stage ) ) {
            wp_send_json_error( array( 'message' => 'Order ID and stage are required.' ) );
        }

        $valid_stages = array( 'fabric_cutting', 'stitching', 'finishing', 'quality_check', 'ready_pickup' );
        if ( ! in_array( $stage, $valid_stages, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid production stage.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        /* Tailors can only update their own assigned orders. */
        if ( self::is_tailor() ) {
            $my_staff_id = self::get_current_staff_id();
            $assigned    = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
            if ( $assigned !== $my_staff_id ) {
                wp_send_json_error( array( 'message' => 'You can only update orders assigned to you.' ) );
            }
        }

        $order->update_meta_data( '_kctm_production_stage', $stage );
        $order->update_meta_data( '_kctm_stage_updated_at', current_time( 'mysql' ) );
        $order->save();

        /* If ready for pickup, also update WC order status. */
        if ( 'ready_pickup' === $stage ) {
            $order->update_status( 'kctm-ready-pickup' );
        }

        wp_send_json_success( array(
            'message'          => 'Production stage updated.',
            'stage'            => $stage,
            'stage_updated_at' => current_time( 'mysql' ),
        ) );
    }

    /**
     * Assign a tailor (staff member) to an order.
     */
    public static function assign_tailor() {
        self::check_access();
        global $wpdb;

        $order_id = self::get_int( 'order_id' );
        $staff_id = self::get_int( 'staff_id' );

        if ( ! $order_id || ! $staff_id ) {
            wp_send_json_error( array( 'message' => 'Order ID and staff ID are required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $staff_table = $wpdb->prefix . 'kctm_staff';
        $tailor_name = $wpdb->get_var( $wpdb->prepare(
            "SELECT name FROM {$staff_table} WHERE id = %d",
            $staff_id
        ) );

        if ( ! $tailor_name ) {
            wp_send_json_error( array( 'message' => 'Staff member not found.' ) );
        }

        /* Check if reassigning (already had a tailor). */
        $old_tailor_id = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
        $old_name      = '';
        if ( $old_tailor_id && $old_tailor_id !== $staff_id ) {
            $old_name = $wpdb->get_var( $wpdb->prepare(
                "SELECT name FROM {$staff_table} WHERE id = %d",
                $old_tailor_id
            ) ) ?: 'Unknown';
        }

        $order->update_meta_data( '_kctm_assigned_tailor', $staff_id );
        $order->save();

        /* Log assignment as order note. */
        $current_user = wp_get_current_user();
        $assign_note  = self::get_param( 'note' );
        if ( $old_name ) {
            $log = '[' . $current_user->display_name . '] Reassigned from ' . $old_name . ' to ' . $tailor_name . '.';
        } else {
            $log = '[' . $current_user->display_name . '] Assigned to ' . $tailor_name . '.';
        }
        if ( ! empty( $assign_note ) ) {
            $log .= ' Note: ' . $assign_note;
        }
        $order->add_order_note( $log, 0, false );

        /* Notify the tailor — email + WhatsApp if configured. */
        $staff_row   = $wpdb->get_row( $wpdb->prepare(
            "SELECT email, phone FROM {$staff_table} WHERE id = %d",
            $staff_id
        ) );

        $notification_msg = '';
        $order_number     = $order->get_order_number();

        if ( $staff_row && ! empty( $staff_row->email ) ) {
            $customer   = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            $email_body = "Hello {$tailor_name},\n\n";
            $email_body .= "Order #{$order_number} has been assigned to you.\n";
            $email_body .= "Customer: {$customer}\n";
            $email_body .= "Total: " . $order->get_formatted_order_total() . "\n\n";
            $email_body .= "View it in your portal: " . home_url( '/store-manager/#production' ) . "\n\nKevin Cho Team";
            wp_mail( $staff_row->email, "Order #{$order_number} Assigned to You", $email_body );
            $notification_msg .= ' Email sent.';
        }

        if ( $staff_row && ! empty( $staff_row->phone ) ) {
            /* Try WhatsApp if credentials are configured. */
            $wa_url   = get_option( 'kctm_whatsapp_api_url' );
            $wa_token = get_option( 'kctm_whatsapp_token' );
            $wa_phone = get_option( 'kctm_whatsapp_phone_id' );
            if ( $wa_url && $wa_token && $wa_phone ) {
                $phone  = preg_replace( '/[^0-9]/', '', $staff_row->phone );
                $wa_msg = "Hi {$tailor_name}, Order #{$order_number} has been assigned to you. Check your portal for details.";
                $response = wp_remote_post( rtrim( $wa_url, '/' ) . '/' . $wa_phone . '/messages', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $wa_token,
                        'Content-Type'  => 'application/json',
                    ),
                    'body' => wp_json_encode( array(
                        'messaging_product' => 'whatsapp',
                        'to'   => $phone,
                        'type' => 'text',
                        'text' => array( 'body' => $wa_msg ),
                    ) ),
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) {
                    $notification_msg .= ' WhatsApp sent.';
                }
            }
        }

        wp_send_json_success( array(
            'message'      => 'Tailor assigned.' . $notification_msg,
            'staff_id'     => $staff_id,
            'tailor_name'  => $tailor_name,
        ) );
    }

    /* ================================================================
     * Fabric Inventory
     * ============================================================= */

    /**
     * List all fabrics with stock info.
     */
    public static function fabrics_list() {
        self::check_access();
        global $wpdb;

        $table   = $wpdb->prefix . 'kctm_fabrics';
        $fabrics = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC" );

        $formatted = array();
        foreach ( $fabrics as $fabric ) {
            $stock_qty       = floatval( $fabric->stock_quantity );
            $low_threshold   = floatval( $fabric->low_stock_threshold );

            $formatted[] = array(
                'id'                  => absint( $fabric->id ),
                'name'                => $fabric->name,
                'slug'                => $fabric->slug,
                'color_hex'           => $fabric->color_hex,
                'pattern_type'        => $fabric->pattern_type,
                'swatch_url'          => $fabric->swatch_url,
                'price_modifier'      => floatval( $fabric->price_modifier ),
                'is_active'           => (bool) $fabric->is_active,
                'stock_quantity'      => $stock_qty,
                'stock_unit'          => $fabric->stock_unit,
                'low_stock_threshold' => $low_threshold,
                'supplier'            => $fabric->supplier,
                'is_low_stock'        => $stock_qty <= $low_threshold,
            );
        }

        wp_send_json_success( array( 'fabrics' => $formatted ) );
    }

    /**
     * Create or update a fabric record.
     */
    public static function save_fabric() {
        self::check_access();
        global $wpdb;

        $table     = $wpdb->prefix . 'kctm_fabrics';
        $fabric_id = self::get_int( 'fabric_id' );
        $name      = self::get_param( 'name' );

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Fabric name is required.' ) );
        }

        $data = array(
            'name'                => $name,
            'color_hex'           => self::get_param( 'color_hex', '#333333' ),
            'pattern_type'        => self::get_param( 'pattern_type', 'solid' ),
            'swatch_url'          => esc_url_raw( self::get_param( 'swatch_url' ) ),
            'price_modifier'      => floatval( self::get_param( 'price_modifier', '0' ) ),
            'is_active'           => absint( self::get_param( 'is_active', '1' ) ),
            'stock_quantity'      => floatval( self::get_param( 'stock_quantity', '0' ) ),
            'stock_unit'          => self::get_param( 'stock_unit', 'yards' ),
            'low_stock_threshold' => floatval( self::get_param( 'low_stock_threshold', '5' ) ),
            'supplier'            => self::get_param( 'supplier' ),
        );

        $format = array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%s', '%f', '%s' );

        if ( $fabric_id ) {
            /* Update existing. */
            $wpdb->update( $table, $data, array( 'id' => $fabric_id ), $format, array( '%d' ) );
            $message = 'Fabric updated.';
        } else {
            /* Create new — generate slug from name. */
            $data['slug'] = sanitize_title( $name );

            /* Ensure slug uniqueness. */
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE slug = %s",
                $data['slug']
            ) );
            if ( $existing ) {
                $data['slug'] .= '-' . wp_rand( 100, 999 );
            }

            $wpdb->insert( $table, $data );
            $fabric_id = $wpdb->insert_id;
            $message   = 'Fabric created.';
        }

        wp_send_json_success( array(
            'message'   => $message,
            'fabric_id' => absint( $fabric_id ),
        ) );
    }

    /**
     * Adjust fabric stock quantity with logging.
     */
    public static function adjust_fabric_stock() {
        self::check_access();
        global $wpdb;

        $fabric_table = $wpdb->prefix . 'kctm_fabrics';
        $log_table    = $wpdb->prefix . 'kctm_fabric_stock_log';

        $fabric_id  = self::get_int( 'fabric_id' );
        $adjustment = floatval( self::get_param( 'adjustment', '0' ) );
        $reason     = self::get_param( 'reason', '' );

        if ( ! $fabric_id ) {
            wp_send_json_error( array( 'message' => 'Fabric ID is required.' ) );
        }

        if ( 0 == $adjustment ) {
            wp_send_json_error( array( 'message' => 'Adjustment amount cannot be zero.' ) );
        }

        /* Get current stock. */
        $current_qty = $wpdb->get_var( $wpdb->prepare(
            "SELECT stock_quantity FROM {$fabric_table} WHERE id = %d",
            $fabric_id
        ) );

        if ( null === $current_qty ) {
            wp_send_json_error( array( 'message' => 'Fabric not found.' ) );
        }

        $current_qty  = floatval( $current_qty );
        $new_quantity = max( 0, $current_qty + $adjustment );

        /* Update fabric stock. */
        $wpdb->update(
            $fabric_table,
            array( 'stock_quantity' => $new_quantity ),
            array( 'id' => $fabric_id ),
            array( '%f' ),
            array( '%d' )
        );

        /* Insert log record. */
        $wpdb->insert(
            $log_table,
            array(
                'fabric_id'    => $fabric_id,
                'adjustment'   => $adjustment,
                'new_quantity' => $new_quantity,
                'reason'       => $reason,
                'created_by'   => get_current_user_id(),
            ),
            array( '%d', '%f', '%f', '%s', '%d' )
        );

        wp_send_json_success( array(
            'message'      => 'Stock adjusted.',
            'new_quantity' => $new_quantity,
        ) );
    }

    /* ================================================================
     * Shipping Tracker
     * ============================================================= */

    /**
     * Save tracking info for an order.
     */
    public static function save_tracking() {
        self::check_access();

        $order_id        = self::get_int( 'order_id' );
        $tracking_number = self::get_param( 'tracking_number' );
        $carrier         = self::get_param( 'shipping_carrier' );
        $status          = self::get_param( 'shipping_status' );

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Order ID is required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $valid_statuses = array( 'shipped', 'in_transit', 'delivered' );
        if ( ! empty( $status ) && ! in_array( $status, $valid_statuses, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid shipping status.' ) );
        }

        if ( ! empty( $tracking_number ) ) {
            $order->update_meta_data( '_kctm_tracking_number', $tracking_number );
        }
        if ( ! empty( $carrier ) ) {
            $order->update_meta_data( '_kctm_shipping_carrier', $carrier );
        }
        if ( ! empty( $status ) ) {
            $order->update_meta_data( '_kctm_shipping_status', $status );
        }

        $order->save();

        /* If delivered, update WC order status. */
        if ( 'delivered' === $status ) {
            $order->update_status( 'kctm-delivered' );
        }

        wp_send_json_success( array(
            'message'          => 'Tracking info saved.',
            'tracking_number'  => $tracking_number,
            'shipping_carrier' => $carrier,
            'shipping_status'  => $status,
        ) );
    }

    /**
     * Send tracking notification via WhatsApp or email.
     */
    public static function send_tracking_notification() {
        self::check_access();

        $order_id = self::get_int( 'order_id' );
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Order ID is required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $tracking_number = $order->get_meta( '_kctm_tracking_number' );
        $carrier         = $order->get_meta( '_kctm_shipping_carrier' );

        if ( empty( $tracking_number ) ) {
            wp_send_json_error( array( 'message' => 'No tracking number set for this order.' ) );
        }

        $order_number = $order->get_order_number();
        $message      = sprintf(
            'Your order #%s has been shipped via %s. Tracking: %s',
            $order_number,
            ! empty( $carrier ) ? $carrier : 'courier',
            $tracking_number
        );

        $sent_via = 'email';
        $phone    = $order->get_billing_phone();

        /* Try WhatsApp first. */
        if ( class_exists( 'KCTM_WhatsApp_API' ) && ! empty( $phone ) ) {
            try {
                $result = KCTM_WhatsApp_API::send_message( $phone, $message );
                if ( $result ) {
                    $sent_via = 'whatsapp';
                }
            } catch ( \Exception $e ) {
                /* Fall back to email. */
            }
        }

        /* Fallback to email. */
        if ( 'email' === $sent_via ) {
            $email = $order->get_billing_email();
            if ( empty( $email ) ) {
                wp_send_json_error( array( 'message' => 'No email or phone available for this customer.' ) );
            }

            $subject = sprintf( 'Tracking Update for Order #%s', $order_number );
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            wp_mail( $email, $subject, nl2br( $message ), $headers );
        }

        wp_send_json_success( array(
            'message'  => 'Tracking notification sent.',
            'sent_via' => $sent_via,
        ) );
    }

    /* ================================================================
     * Calendar View — Consultations + Availability + Blocked Dates
     * ============================================================= */

    /**
     * Get consultation calendar data for a given month.
     */
    public static function calendar_data() {
        self::check_access();
        global $wpdb;

        $month = self::get_int( 'month', (int) date( 'n' ) );
        $year  = self::get_int( 'year', (int) date( 'Y' ) );

        /* Validate ranges. */
        $month = max( 1, min( 12, $month ) );
        $year  = max( 2020, min( 2099, $year ) );

        $date_start = sprintf( '%04d-%02d-01', $year, $month );
        $date_end   = date( 'Y-m-t', strtotime( $date_start ) );

        /* Bookings for this month. */
        $consultations_table = $wpdb->prefix . 'kctm_consultations';
        $bookings_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, first_name, last_name, consultation_date, consultation_time, status, duration
             FROM {$consultations_table}
             WHERE consultation_date BETWEEN %s AND %s
             ORDER BY consultation_date ASC, consultation_time ASC",
            $date_start,
            $date_end
        ) );

        $bookings = array();
        foreach ( $bookings_raw as $row ) {
            $bookings[] = array(
                'id'       => absint( $row->id ),
                'name'     => trim( $row->first_name . ' ' . $row->last_name ),
                'date'     => $row->consultation_date,
                'time'     => $row->consultation_time,
                'status'   => $row->status,
                'duration' => absint( $row->duration ),
            );
        }

        /* Blocked dates for this month. */
        $blocked_table = $wpdb->prefix . 'kctm_consultation_blocked_dates';
        $blocked_raw   = $wpdb->get_results( $wpdb->prepare(
            "SELECT blocked_date, reason FROM {$blocked_table}
             WHERE blocked_date BETWEEN %s AND %s
             ORDER BY blocked_date ASC",
            $date_start,
            $date_end
        ) );

        $blocked_dates = array();
        foreach ( $blocked_raw as $row ) {
            $blocked_dates[] = array(
                'date'   => $row->blocked_date,
                'reason' => $row->reason,
            );
        }

        /* Availability slots keyed by day_of_week. */
        $availability_table = $wpdb->prefix . 'kctm_consultation_availability';
        $availability_raw   = $wpdb->get_results(
            "SELECT day_of_week, time_slot, is_active
             FROM {$availability_table}
             ORDER BY day_of_week ASC, time_slot ASC"
        );

        $availability = array();
        foreach ( $availability_raw as $row ) {
            $day = absint( $row->day_of_week );
            if ( ! isset( $availability[ $day ] ) ) {
                $availability[ $day ] = array();
            }
            $availability[ $day ][] = array(
                'time_slot' => $row->time_slot,
                'is_active' => (bool) $row->is_active,
            );
        }

        wp_send_json_success( array(
            'bookings'      => $bookings,
            'blocked_dates' => $blocked_dates,
            'availability'  => $availability,
            'month'         => $month,
            'year'          => $year,
        ) );
    }

    /* ================================================================
     * Calendar — Block a Date
     * ============================================================= */

    public static function block_date() {
        self::check_access();
        global $wpdb;

        $date   = self::get_param( 'date' );
        $reason = self::get_param( 'reason' );

        if ( empty( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( array( 'message' => 'Valid date is required (YYYY-MM-DD).' ) );
        }

        $table = $wpdb->prefix . 'kctm_consultation_blocked_dates';

        /* Check if already blocked. */
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE blocked_date = %s",
            $date
        ) );

        if ( $exists ) {
            /* Update reason. */
            $wpdb->update( $table, array( 'reason' => $reason ), array( 'id' => $exists ), array( '%s' ), array( '%d' ) );
        } else {
            $wpdb->insert( $table, array(
                'blocked_date' => $date,
                'reason'       => $reason,
            ), array( '%s', '%s' ) );
        }

        wp_send_json_success( array( 'message' => 'Date blocked: ' . $date ) );
    }

    /* ================================================================
     * Calendar — Unblock a Date
     * ============================================================= */

    public static function unblock_date() {
        self::check_access();
        global $wpdb;

        $date = self::get_param( 'date' );

        if ( empty( $date ) ) {
            wp_send_json_error( array( 'message' => 'Date is required.' ) );
        }

        $table = $wpdb->prefix . 'kctm_consultation_blocked_dates';
        $wpdb->delete( $table, array( 'blocked_date' => $date ), array( '%s' ) );

        wp_send_json_success( array( 'message' => 'Date unblocked: ' . $date ) );
    }

    /* ================================================================
     * Order Notes — List notes for an order
     * ============================================================= */

    public static function order_notes() {
        self::check_tailor_access();

        $order_id = self::get_int( 'order_id' );
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Order ID is required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        /* Tailors can only see notes for their assigned orders. */
        if ( self::is_tailor() ) {
            $my_staff_id = self::get_current_staff_id();
            $assigned    = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
            if ( $assigned !== $my_staff_id ) {
                wp_send_json_error( array( 'message' => 'Access denied.' ) );
            }
        }

        $notes = wc_get_order_notes( array( 'order_id' => $order_id ) );

        $formatted = array();
        foreach ( $notes as $note ) {
            $formatted[] = array(
                'id'      => $note->id,
                'content' => $note->content,
                'date'    => $note->date_created->date( 'Y-m-d H:i' ),
                'author'  => $note->added_by === 'system' ? 'System' : $note->added_by,
                'is_customer_note' => (bool) $note->customer_note,
            );
        }

        /* Also return assignment info. */
        global $wpdb;
        $staff_table   = $wpdb->prefix . 'kctm_staff';
        $assigned_id   = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
        $assigned_name = '';
        if ( $assigned_id ) {
            $assigned_name = $wpdb->get_var( $wpdb->prepare(
                "SELECT name FROM {$staff_table} WHERE id = %d",
                $assigned_id
            ) ) ?: '';
        }

        $customer = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

        wp_send_json_success( array(
            'notes' => $formatted,
            'order' => array(
                'id'              => $order->get_id(),
                'number'          => $order->get_order_number(),
                'customer'        => $customer,
                'status'          => $order->get_status(),
                'total'           => wc_format_decimal( $order->get_total(), 2 ),
                'date'            => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '',
                'stage'           => $order->get_meta( '_kctm_production_stage' ) ?: 'pending',
                'assigned_tailor' => array( 'id' => $assigned_id, 'name' => $assigned_name ),
            ),
        ) );
    }

    /* ================================================================
     * Order Notes — Add a note to an order
     * ============================================================= */

    public static function add_order_note() {
        self::check_tailor_access();

        $order_id = self::get_int( 'order_id' );
        $note     = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

        if ( ! $order_id || empty( $note ) ) {
            wp_send_json_error( array( 'message' => 'Order ID and note text are required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        /* Tailors can only add notes to their assigned orders. */
        if ( self::is_tailor() ) {
            $my_staff_id = self::get_current_staff_id();
            $assigned    = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
            if ( $assigned !== $my_staff_id ) {
                wp_send_json_error( array( 'message' => 'You can only add notes to your assigned orders.' ) );
            }
        }

        /* Prefix note with author name. */
        $current_user = wp_get_current_user();
        $author       = $current_user->display_name ?: 'Staff';
        $full_note    = '[' . $author . '] ' . $note;

        $note_id = $order->add_order_note( $full_note, 0, false );

        wp_send_json_success( array(
            'message' => 'Note added.',
            'note_id' => $note_id,
        ) );
    }

    /* ================================================================
     * Unassign Tailor from an order
     * ============================================================= */

    public static function unassign_tailor() {
        self::check_access();
        global $wpdb;

        $order_id = self::get_int( 'order_id' );
        $note     = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Order ID is required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $old_tailor_id = absint( $order->get_meta( '_kctm_assigned_tailor' ) );
        $old_name      = '';
        if ( $old_tailor_id ) {
            $staff_table = $wpdb->prefix . 'kctm_staff';
            $old_name    = $wpdb->get_var( $wpdb->prepare(
                "SELECT name FROM {$staff_table} WHERE id = %d",
                $old_tailor_id
            ) ) ?: 'Unknown';
        }

        $order->delete_meta_data( '_kctm_assigned_tailor' );
        $order->save();

        /* Log it as a note. */
        $current_user = wp_get_current_user();
        $log = '[' . $current_user->display_name . '] Unassigned tailor: ' . $old_name . '.';
        if ( ! empty( $note ) ) {
            $log .= ' Reason: ' . $note;
        }
        $order->add_order_note( $log, 0, false );

        wp_send_json_success( array( 'message' => 'Tailor unassigned from order #' . $order->get_order_number() ) );
    }

    /* ================================================================
     * Analytics Dashboard — Revenue & Order Analytics
     * ============================================================= */

    /**
     * Revenue and order analytics with optional period comparison.
     */
    public static function analytics() {
        self::check_access();

        $period  = self::get_int( 'period', 30 );
        $compare = self::get_int( 'compare', 0 );

        /* Clamp period to valid range. */
        $allowed_periods = array( 7, 30, 90, 365 );
        if ( ! in_array( $period, $allowed_periods, true ) ) {
            $period = 30;
        }

        $date_end   = date( 'Y-m-d' );
        $date_start = date( 'Y-m-d', strtotime( '-' . $period . ' days' ) );

        $current = self::compute_analytics( $date_start, $date_end );

        $result = array(
            'period'              => $period,
            'date_start'          => $date_start,
            'date_end'            => $date_end,
            'total_revenue'       => $current['total_revenue'],
            'total_orders'        => $current['total_orders'],
            'average_order_value' => $current['average_order_value'],
            'revenue_by_day'      => $current['revenue_by_day'],
            'revenue_by_category' => $current['revenue_by_category'],
            'revenue_by_currency' => $current['revenue_by_currency'],
            'top_customers'       => $current['top_customers'],
            'currency'            => get_woocommerce_currency_symbol(),
        );

        /* Previous period comparison. */
        if ( $compare ) {
            $prev_end   = date( 'Y-m-d', strtotime( $date_start . ' -1 day' ) );
            $prev_start = date( 'Y-m-d', strtotime( '-' . $period . ' days', strtotime( $prev_end ) ) );

            $previous = self::compute_analytics( $prev_start, $prev_end );

            $result['previous_period'] = array(
                'date_start'          => $prev_start,
                'date_end'            => $prev_end,
                'total_revenue'       => $previous['total_revenue'],
                'total_orders'        => $previous['total_orders'],
                'average_order_value' => $previous['average_order_value'],
            );

            /* Percent changes. */
            $result['percent_change'] = array(
                'revenue'             => self::percent_change( $previous['total_revenue'], $current['total_revenue'] ),
                'orders'              => self::percent_change( $previous['total_orders'], $current['total_orders'] ),
                'average_order_value' => self::percent_change( $previous['average_order_value'], $current['average_order_value'] ),
            );
        }

        wp_send_json_success( $result );
    }

    /**
     * Compute analytics for a given date range.
     *
     * @param string $date_start Y-m-d.
     * @param string $date_end   Y-m-d.
     * @return array
     */
    private static function compute_analytics( $date_start, $date_end ) {
        $orders = wc_get_orders( array(
            'limit'        => -1,
            'status'       => array( 'completed', 'processing', 'kctm-delivered' ),
            'date_created' => $date_start . '...' . $date_end . ' 23:59:59',
        ) );

        $total_revenue    = 0;
        $total_orders     = count( $orders );
        $revenue_by_day   = array();
        $category_revenue = array();
        $currency_revenue = array();
        $customer_totals  = array();

        foreach ( $orders as $order ) {
            $order_total = floatval( $order->get_total() );
            $total_revenue += $order_total;

            /* Revenue by day. */
            $day = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : $date_start;
            if ( ! isset( $revenue_by_day[ $day ] ) ) {
                $revenue_by_day[ $day ] = array(
                    'date'    => $day,
                    'revenue' => 0,
                    'orders'  => 0,
                );
            }
            $revenue_by_day[ $day ]['revenue'] += $order_total;
            $revenue_by_day[ $day ]['orders']++;

            /* Revenue by category (from order items). */
            foreach ( $order->get_items() as $item ) {
                $product_id = $item->get_product_id();
                if ( ! $product_id ) {
                    continue;
                }

                $terms = get_the_terms( $product_id, 'product_cat' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $cat_name = $term->name;
                        if ( ! isset( $category_revenue[ $cat_name ] ) ) {
                            $category_revenue[ $cat_name ] = 0;
                        }
                        $category_revenue[ $cat_name ] += floatval( $item->get_total() );
                    }
                } else {
                    if ( ! isset( $category_revenue['Uncategorized'] ) ) {
                        $category_revenue['Uncategorized'] = 0;
                    }
                    $category_revenue['Uncategorized'] += floatval( $item->get_total() );
                }
            }

            /* Revenue by currency. */
            $currency = $order->get_currency();
            if ( ! isset( $currency_revenue[ $currency ] ) ) {
                $currency_revenue[ $currency ] = 0;
            }
            $currency_revenue[ $currency ] += $order_total;

            /* Top customers. */
            $customer_id = $order->get_customer_id();
            $customer_key = $customer_id ? $customer_id : 'guest_' . $order->get_billing_email();
            if ( ! isset( $customer_totals[ $customer_key ] ) ) {
                $name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
                if ( empty( $name ) ) {
                    $name = $order->get_billing_email();
                }
                $customer_totals[ $customer_key ] = array(
                    'customer_id' => $customer_id,
                    'name'        => $name,
                    'email'       => $order->get_billing_email(),
                    'total_spent' => 0,
                    'order_count' => 0,
                );
            }
            $customer_totals[ $customer_key ]['total_spent'] += $order_total;
            $customer_totals[ $customer_key ]['order_count']++;
        }

        /* Sort revenue_by_day by date. */
        ksort( $revenue_by_day );
        $revenue_by_day = array_values( $revenue_by_day );

        /* Round revenue values. */
        foreach ( $revenue_by_day as &$day_data ) {
            $day_data['revenue'] = round( $day_data['revenue'], 2 );
        }
        unset( $day_data );

        /* Format revenue_by_category. */
        $cat_array = array();
        foreach ( $category_revenue as $cat_name => $cat_rev ) {
            $cat_array[] = array(
                'category' => $cat_name,
                'revenue'  => round( $cat_rev, 2 ),
            );
        }
        usort( $cat_array, function( $a, $b ) {
            return $b['revenue'] <=> $a['revenue'];
        } );

        /* Format revenue_by_currency. */
        $currency_array = array();
        foreach ( $currency_revenue as $curr => $curr_rev ) {
            $currency_array[] = array(
                'currency' => $curr,
                'revenue'  => round( $curr_rev, 2 ),
            );
        }

        /* Top 5 customers by total spend. */
        usort( $customer_totals, function( $a, $b ) {
            return $b['total_spent'] <=> $a['total_spent'];
        } );
        $top_customers = array_slice( array_values( $customer_totals ), 0, 5 );
        foreach ( $top_customers as &$cust ) {
            $cust['total_spent'] = round( $cust['total_spent'], 2 );
        }
        unset( $cust );

        $average_order_value = $total_orders > 0 ? round( $total_revenue / $total_orders, 2 ) : 0;

        return array(
            'total_revenue'       => round( $total_revenue, 2 ),
            'total_orders'        => $total_orders,
            'average_order_value' => $average_order_value,
            'revenue_by_day'      => $revenue_by_day,
            'revenue_by_category' => $cat_array,
            'revenue_by_currency' => $currency_array,
            'top_customers'       => $top_customers,
        );
    }

    /**
     * Calculate percentage change between old and new values.
     *
     * @param float $old_value Previous period value.
     * @param float $new_value Current period value.
     * @return float
     */
    private static function percent_change( $old_value, $new_value ) {
        if ( 0 == $old_value ) {
            return $new_value > 0 ? 100.0 : 0.0;
        }
        return round( ( ( $new_value - $old_value ) / $old_value ) * 100, 2 );
    }

    /* ================================================================
     * CSV Export — Generate data for client-side CSV download
     * ============================================================= */

    /**
     * Generate CSV data arrays for client-side download.
     */
    public static function export_data() {
        self::check_access();
        global $wpdb;

        $export_type = self::get_param( 'export_type', 'orders' );
        $today       = date( 'Y-m-d' );

        $headers  = array();
        $rows     = array();
        $filename = '';

        switch ( $export_type ) {

            case 'orders':
                $filename = 'orders_export_' . $today . '.csv';
                $headers  = array( 'ID', 'Date', 'Customer', 'Email', 'Phone', 'Items', 'Total', 'Currency', 'Status' );

                $orders = wc_get_orders( array(
                    'limit'   => 500,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                ) );

                foreach ( $orders as $order ) {
                    $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

                    /* Build items summary. */
                    $items_parts = array();
                    foreach ( $order->get_items() as $item ) {
                        $items_parts[] = $item->get_name() . ' x' . $item->get_quantity();
                    }
                    $items_str = implode( '; ', $items_parts );

                    $rows[] = array(
                        $order->get_id(),
                        $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '',
                        $customer_name,
                        $order->get_billing_email(),
                        $order->get_billing_phone(),
                        $items_str,
                        wc_format_decimal( $order->get_total(), 2 ),
                        $order->get_currency(),
                        wc_get_order_status_name( $order->get_status() ),
                    );
                }
                break;

            case 'customers':
                $filename = 'customers_export_' . $today . '.csv';
                $headers  = array( 'ID', 'Name', 'Email', 'Phone', 'Type', 'Tags', 'Registered', 'Order Count', 'Total Spent' );

                $users = get_users( array(
                    'role'    => 'customer',
                    'number'  => 1000,
                    'orderby' => 'registered',
                    'order'   => 'DESC',
                ) );

                foreach ( $users as $user ) {
                    $name = trim( $user->first_name . ' ' . $user->last_name );
                    if ( empty( $name ) ) {
                        $name = $user->display_name;
                    }

                    $phone = get_user_meta( $user->ID, '_kctm_phone', true );
                    if ( empty( $phone ) ) {
                        $phone = get_user_meta( $user->ID, 'billing_phone', true );
                    }

                    $type = get_user_meta( $user->ID, '_kctm_customer_type', true );
                    if ( empty( $type ) ) {
                        $type = 'regular';
                    }

                    $tags_raw = get_user_meta( $user->ID, '_kctm_tags', true );
                    $tags_str = '';
                    if ( is_array( $tags_raw ) ) {
                        $tags_str = implode( ', ', $tags_raw );
                    } elseif ( is_string( $tags_raw ) ) {
                        $tags_str = $tags_raw;
                    }

                    /* Order count and total spent. */
                    $customer_orders = wc_get_orders( array(
                        'customer_id' => $user->ID,
                        'limit'       => -1,
                        'return'      => 'ids',
                        'status'      => array( 'completed', 'processing', 'kctm-delivered' ),
                    ) );

                    $total_spent = 0;
                    foreach ( $customer_orders as $oid ) {
                        $o = wc_get_order( $oid );
                        if ( $o ) {
                            $total_spent += floatval( $o->get_total() );
                        }
                    }

                    $rows[] = array(
                        $user->ID,
                        $name,
                        $user->user_email,
                        $phone,
                        $type,
                        $tags_str,
                        $user->user_registered,
                        count( $customer_orders ),
                        round( $total_spent, 2 ),
                    );
                }
                break;

            case 'products':
                $filename = 'products_export_' . $today . '.csv';
                $headers  = array( 'ID', 'Name', 'SKU', 'Price', 'Sale Price', 'Stock', 'Categories', 'Total Sales' );

                $products = wc_get_products( array(
                    'limit'  => 500,
                    'status' => 'any',
                ) );

                foreach ( $products as $product ) {
                    $cats = array();
                    foreach ( $product->get_category_ids() as $cid ) {
                        $term = get_term( $cid, 'product_cat' );
                        if ( $term && ! is_wp_error( $term ) ) {
                            $cats[] = $term->name;
                        }
                    }

                    $stock = $product->get_manage_stock()
                        ? ( $product->get_stock_quantity() !== null ? $product->get_stock_quantity() : 'N/A' )
                        : $product->get_stock_status();

                    $rows[] = array(
                        $product->get_id(),
                        $product->get_name(),
                        $product->get_sku(),
                        $product->get_regular_price(),
                        $product->get_sale_price(),
                        $stock,
                        implode( ', ', $cats ),
                        $product->get_total_sales(),
                    );
                }
                break;

            case 'expenses':
                $filename = 'expenses_export_' . $today . '.csv';
                $headers  = array( 'ID', 'Date', 'Category', 'Description', 'Amount', 'Currency' );

                $expenses_table = $wpdb->prefix . 'kctm_expenses';
                $expenses = $wpdb->get_results(
                    "SELECT * FROM {$expenses_table} ORDER BY expense_date DESC LIMIT 1000"
                );

                foreach ( $expenses as $expense ) {
                    $rows[] = array(
                        absint( $expense->id ),
                        $expense->expense_date,
                        $expense->category,
                        $expense->description,
                        floatval( $expense->amount ),
                        $expense->currency,
                    );
                }
                break;

            case 'analytics':
                $filename = 'analytics_export_' . $today . '.csv';
                $headers  = array( 'Date', 'Revenue', 'Orders' );

                /* Default to last 30 days. */
                $period     = self::get_int( 'period', 30 );
                $date_start = date( 'Y-m-d', strtotime( '-' . absint( $period ) . ' days' ) );
                $analytics  = self::compute_analytics( $date_start, $today );

                foreach ( $analytics['revenue_by_day'] as $day_data ) {
                    $rows[] = array(
                        $day_data['date'],
                        $day_data['revenue'],
                        $day_data['orders'],
                    );
                }
                break;

            default:
                wp_send_json_error( array( 'message' => 'Invalid export type.' ) );
                return;
        }

        wp_send_json_success( array(
            'headers'  => $headers,
            'rows'     => $rows,
            'filename' => $filename,
        ) );
    }

    /* ================================================================
     * Tailor Dashboard — personal summary for tailor-role users
     * ============================================================= */

    public static function tailor_dashboard() {
        self::check_tailor_access();

        $my_staff_id = self::get_current_staff_id();
        if ( ! $my_staff_id ) {
            wp_send_json_error( array( 'message' => 'Your account is not linked to a staff profile.' ) );
        }

        global $wpdb;
        $staff_table = $wpdb->prefix . 'kctm_staff';
        $profile     = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$staff_table} WHERE id = %d",
            $my_staff_id
        ) );

        /* Get assigned orders (HPOS-compatible). */
        $order_ids = wc_get_orders( array(
            'meta_key'   => '_kctm_assigned_tailor',
            'meta_value' => (string) $my_staff_id,
            'return'     => 'ids',
            'limit'      => -1,
        ) );

        $stages = array(
            'fabric_cutting' => 0,
            'stitching'      => 0,
            'finishing'       => 0,
            'quality_check'  => 0,
            'ready_pickup'   => 0,
        );
        $recent_orders = array();

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            $stage = $order->get_meta( '_kctm_production_stage' );
            if ( ! empty( $stage ) && isset( $stages[ $stage ] ) ) {
                $stages[ $stage ]++;
            }

            $customer = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            $recent_orders[] = array(
                'id'       => $order->get_id(),
                'number'   => $order->get_order_number(),
                'customer' => $customer,
                'status'   => $order->get_status(),
                'stage'    => $stage ?: 'pending',
                'total'    => $order->get_total(),
                'date'     => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
            );
        }

        wp_send_json_success( array(
            'profile'       => array(
                'id'             => (int) $profile->id,
                'name'           => $profile->name,
                'phone'          => $profile->phone,
                'email'          => $profile->email,
                'role'           => $profile->role,
                'specialization' => $profile->specialization,
            ),
            'total_orders'  => count( $order_ids ),
            'stages'        => $stages,
            'recent_orders' => array_slice( $recent_orders, 0, 20 ),
        ) );
    }

    /* ================================================================
     * Drivers — List
     * ============================================================= */

    public static function drivers() {
        self::check_access();

        global $wpdb;

        $table = $wpdb->prefix . 'kctm_drivers';
        $rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );

        wp_send_json_success( array( 'drivers' => $rows ? $rows : array() ) );
    }

    /* ================================================================
     * Drivers — Save (Create or Update)
     * ============================================================= */

    public static function save_driver() {
        self::check_access();

        global $wpdb;

        $driver_id = self::get_int( 'driver_id' );
        $name      = self::get_param( 'name' );
        $phone     = self::get_param( 'phone' );
        $cities    = self::get_param( 'cities' );
        $active    = self::get_param( 'active', '1' );
        $table     = $wpdb->prefix . 'kctm_drivers';

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Driver name is required.' ) );
        }

        $data   = array(
            'name'   => $name,
            'phone'  => $phone,
            'cities' => $cities,
            'active' => absint( $active ),
        );
        $format = array( '%s', '%s', '%s', '%d' );

        if ( $driver_id ) {
            $result = $wpdb->update( $table, $data, array( 'id' => $driver_id ), $format, array( '%d' ) );
            if ( false === $result ) {
                wp_send_json_error( array( 'message' => 'Failed to update driver.' ) );
            }
            $msg = 'Driver updated.';
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $format[]           = '%s';
            $result = $wpdb->insert( $table, $data, $format );
            if ( false === $result ) {
                wp_send_json_error( array( 'message' => 'Failed to create driver.' ) );
            }
            $driver_id = $wpdb->insert_id;
            $msg       = 'Driver created.';
        }

        wp_send_json_success( array(
            'message'   => $msg,
            'driver_id' => $driver_id,
        ) );
    }

    /* ================================================================
     * Drivers — Delete
     * ============================================================= */

    public static function delete_driver() {
        self::check_access();

        global $wpdb;

        $driver_id = self::get_int( 'driver_id' );
        if ( ! $driver_id ) {
            wp_send_json_error( array( 'message' => 'Driver ID is required.' ) );
        }

        $table  = $wpdb->prefix . 'kctm_drivers';
        $result = $wpdb->delete( $table, array( 'id' => $driver_id ), array( '%d' ) );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Failed to delete driver.' ) );
        }

        wp_send_json_success( array( 'message' => 'Driver deleted.' ) );
    }

    /* ================================================================
     * Drivers — Assign driver to order + optional WhatsApp notify
     * ============================================================= */

    public static function assign_driver() {
        self::check_access();

        $order_id     = self::get_int( 'order_id' );
        $driver_name  = self::get_param( 'driver_name' );
        $driver_phone = self::get_param( 'driver_phone' );
        $notify       = self::get_param( 'notify' );

        if ( ! $order_id || empty( $driver_name ) || empty( $driver_phone ) ) {
            wp_send_json_error( array( 'message' => 'Order ID, driver name and phone are required.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        /* Save driver info as order meta. */
        $order->update_meta_data( '_kctm_driver_name', $driver_name );
        $order->update_meta_data( '_kctm_driver_phone', $driver_phone );
        $order->save();

        /* Update order status to "With Driver" (shipped). */
        $order->update_status( 'kctm-with-driver', sprintf( 'Package shipped with %s (%s).', $driver_name, $driver_phone ) );

        /* Notify customer. */
        $sent_via = '';
        if ( $notify ) {
            $city         = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
            $order_number = $order->get_order_number();
            $customer     = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

            $city_display = ! empty( $city ) ? $city : 'your city';
            $message = 'Bonjour ' . ( ! empty( $customer ) ? $customer : 'Customer' ) . ",\n\n"
                . 'Your order #' . $order_number . " has been shipped!\n"
                . 'Votre commande #' . $order_number . " a ete expediee !\n\n"
                . "Your package has been handed to:\n"
                . 'Driver / Chauffeur: ' . $driver_name . "\n"
                . 'Phone / Telephone: ' . $driver_phone . "\n\n"
                . 'The driver will call you when they arrive in ' . $city_display . ".\n"
                . 'Le chauffeur vous appellera a son arrivee a ' . $city_display . ".\n\n"
                . "Please let us know when you receive your package.\n"
                . "Veuillez nous informer lorsque vous recevez votre colis.\n\n"
                . "Thank you! / Merci !\nKevin Cho Tailoring";

            $phone = $order->get_billing_phone();

            /* Try WhatsApp. */
            if ( class_exists( 'KCTM_WhatsApp_API' ) && ! empty( $phone ) ) {
                try {
                    $wa_api = new KCTM_WhatsApp_API();
                    $result = $wa_api->send_text_message( $phone, $message );
                    if ( $result && ! is_wp_error( $result ) ) {
                        $sent_via = 'whatsapp';
                    }
                } catch ( \Exception $e ) {
                    /* Continue to email. */
                }
            }

            /* Always send email too. */
            $email = $order->get_billing_email();
            if ( ! empty( $email ) ) {
                $subject = 'Your Order #' . $order_number . ' Has Been Shipped! / Votre Commande a ete Expediee !';
                $headers = array( 'Content-Type: text/html; charset=UTF-8' );
                wp_mail( $email, $subject, nl2br( $message ), $headers );
                if ( empty( $sent_via ) ) {
                    $sent_via = 'email';
                } else {
                    $sent_via .= ' + email';
                }
            }
        }

        wp_send_json_success( array(
            'message'      => 'Order shipped!',
            'driver_name'  => $driver_name,
            'driver_phone' => $driver_phone,
            'sent_via'     => $sent_via,
        ) );
    }

    /* ================================================================
     * AI Rewrite — uses Google Gemini free API
     * ============================================================= */

    public static function ai_rewrite() {
        self::check_access();

        $text    = isset( $_POST['text'] ) ? wp_kses_post( wp_unslash( $_POST['text'] ) ) : '';
        $context = sanitize_text_field( self::get_param( 'context' ) ); // email, whatsapp, note

        if ( empty( $text ) ) {
            wp_send_json_error( array( 'message' => 'No text provided.' ) );
        }

        $store_settings = get_option( 'kctm_store_settings', array() );
        $api_key        = isset( $store_settings['openai_api_key'] ) ? $store_settings['openai_api_key'] : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'OpenAI API key not configured. Go to Settings → Store Info to add it.' ) );
        }

        $store_name = isset( $store_settings['store_name'] ) && $store_settings['store_name']
            ? $store_settings['store_name']
            : get_bloginfo( 'name' );

        $system_prompts = array(
            'email'    => "You are a professional copywriter for \"{$store_name}\", a luxury custom tailoring business. Rewrite rough email messages into polished, warm, and professional emails. Keep it concise. Do not add a subject line — only the body. Preserve the core intent and any specific details (names, dates, amounts). Output ONLY the rewritten text, no extra commentary.",
            'product'  => "You are a product copywriter for \"{$store_name}\", a luxury custom tailoring business. Rewrite the rough product description into a compelling, elegant product listing description. Highlight craftsmanship, fabric quality, and style. Keep it concise and suitable for an ecommerce product page. Output ONLY the rewritten text, no extra commentary.",
            'whatsapp' => "You are a friendly customer service writer for \"{$store_name}\", a custom tailoring business. You write WhatsApp messages that are brief, warm, and professional. If the user gives you just a topic, theme, or a few words, generate a complete message. If they give you a rough draft, polish it. Keep it short (under 500 characters). Use these placeholders naturally where relevant: {customer_name} (customer's name), {order_number} (their order number), {amount} (money amount), {date} (a date). Not all placeholders need to be used — only include ones that make sense for the message. Output ONLY the message text, no extra commentary.",
            'note'     => "Rewrite this internal production note to be clear and professional. Keep it brief. Output ONLY the rewritten text.",
        );

        $system = isset( $system_prompts[ $context ] ) ? $system_prompts[ $context ] : $system_prompts['email'];

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'body'    => wp_json_encode( array(
                    'model'    => 'gpt-4o-mini',
                    'messages' => array(
                        array( 'role' => 'system', 'content' => $system ),
                        array( 'role' => 'user',   'content' => $text ),
                    ),
                    'temperature'  => 0.7,
                    'max_tokens'   => 1024,
                ) ),
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'API request failed: ' . $response->get_error_message() ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code ) {
            $err = isset( $body['error']['message'] ) ? $body['error']['message'] : 'API returned status ' . $code;
            wp_send_json_error( array( 'message' => $err ) );
        }

        $rewritten = isset( $body['choices'][0]['message']['content'] )
            ? trim( $body['choices'][0]['message']['content'] )
            : '';

        if ( empty( $rewritten ) ) {
            wp_send_json_error( array( 'message' => 'AI returned an empty response. Try again.' ) );
        }

        wp_send_json_success( array( 'rewritten' => $rewritten ) );
    }
}
