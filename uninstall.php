<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('sc_sheet_url');
delete_option('sc_logo');
delete_option('sc_footer_text');
delete_option('sc_address');
delete_option('sc_phone');
delete_option('sc_website');
delete_option('sc_enable_qr');
delete_option('sc_qr');
delete_option('sc_public_print');
delete_option('sc_excluded_events');
delete_option('sc_primary_color');
delete_option('sc_calendar_version');
delete_option('sc_calendar_last_fetch');
delete_transient('sc_calendar_events');
delete_transient('sc_calendar_parsed');