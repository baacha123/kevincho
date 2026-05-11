<?php
/**
 * Runs on plugin deactivation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KCTM_Deactivator {

    public static function deactivate() {
        wp_clear_scheduled_hook( 'kctm_send_consultation_reminders' );
        flush_rewrite_rules();
    }
}
