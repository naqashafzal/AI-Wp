<?php
if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook( AICB_PLUGIN_FILE, 'aicb_activate' );
function aicb_activate() {
    if (function_exists('aicb_verify_database_tables_on_load')) {
        aicb_verify_database_tables_on_load();
    }
}