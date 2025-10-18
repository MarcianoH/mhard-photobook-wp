<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CL_Activator {
    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        $groups = $wpdb->prefix . 'configurator_groups';
        $options = $wpdb->prefix . 'configurator_options';
        $clients = $wpdb->prefix . 'configurator_clients';
        $subs    = $wpdb->prefix . 'configurator_submissions';

        $sql_groups = "CREATE TABLE $groups (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'single',
            sort_order INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY type_active (type, active)
        ) $charset_collate;";

        $sql_options = "CREATE TABLE $options (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            image_id BIGINT UNSIGNED NULL,
            image_url VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY group_active (group_id, active),
            KEY sort (group_id, sort_order)
        ) $charset_collate;";

        $sql_clients = "CREATE TABLE $clients (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            invited_at DATETIME NULL,
            last_visited DATETIME NULL,
            last_submitted DATETIME NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            KEY email (email)
        ) $charset_collate;";

        $sql_subs = "CREATE TABLE $subs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id BIGINT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            selections LONGTEXT NOT NULL,
            submitted_at DATETIME NOT NULL,
            user_agent VARCHAR(255) NULL,
            ip VARCHAR(64) NULL,
            PRIMARY KEY  (id),
            KEY client_token (client_id, token)
        ) $charset_collate;";

        dbDelta( $sql_groups );
        dbDelta( $sql_options );
        dbDelta( $sql_clients );
        dbDelta( $sql_subs );

        if ( ! get_option( 'cl_settings' ) ) {
            update_option( 'cl_settings', CL_Helpers::get_settings() );
        }
    }

    public static function deactivate() {
        // No action on deactivate; keep data.
    }
}
