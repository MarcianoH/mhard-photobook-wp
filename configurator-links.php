<?php
/**
 * Plugin Name: MHard Photobook for WordPress
 * Description: Professional photobook configurator for managing product options, client invitations, and order submissions. Shortcode: [configurator_form]
 * Version: 1.1.1
 * Author: MHard
 * Text Domain: configurator-links
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
if ( ! defined( 'CL_PLUGIN_VERSION' ) ) {
    define( 'CL_PLUGIN_VERSION', '1.1.1' );
}
if ( ! defined( 'CL_PLUGIN_FILE' ) ) {
    define( 'CL_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'CL_PLUGIN_DIR' ) ) {
    define( 'CL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CL_PLUGIN_URL' ) ) {
    define( 'CL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Autoload includes
require_once CL_PLUGIN_DIR . 'includes/helpers.php';
require_once CL_PLUGIN_DIR . 'includes/class-activator.php';
require_once CL_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once CL_PLUGIN_DIR . 'includes/class-groups.php';
require_once CL_PLUGIN_DIR . 'includes/class-options.php';
require_once CL_PLUGIN_DIR . 'includes/class-clients.php';
require_once CL_PLUGIN_DIR . 'includes/class-submissions.php';
require_once CL_PLUGIN_DIR . 'includes/class-emails.php';
require_once CL_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once CL_PLUGIN_DIR . 'includes/class-importer.php';
require_once CL_PLUGIN_DIR . 'includes/class-exporter.php';
require_once CL_PLUGIN_DIR . 'includes/class-rest.php';

// Optional WP-CLI integration if available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    // Lazy include to avoid fatal if WP_CLI not present
    if ( file_exists( CL_PLUGIN_DIR . 'includes/class-cli.php' ) ) {
        require_once CL_PLUGIN_DIR . 'includes/class-cli.php';
    }
}

// GitHub-based auto-updates
if ( file_exists( CL_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once CL_PLUGIN_DIR . 'vendor/autoload.php';
}
if ( class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
    $cl_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/MarcianoH/mhard-photobook-wp',
        __FILE__,
        'configurator-links'
    );
    // If using private repository, set authentication:
    // $cl_update_checker->setAuthentication( 'your-token-here' );

    // Optional: Set which branch to track for updates
    $cl_update_checker->setBranch( 'main' );

    // Optional: Enable release assets (if you want to use release ZIPs)
    // $cl_update_checker->getVcsApi()->enableReleaseAssets();
}

// Activation/Deactivation
register_activation_hook( __FILE__, [ 'CL_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'CL_Activator', 'deactivate' ] );

// Bootstrap
add_action( 'plugins_loaded', function () {
    // i18n
    load_plugin_textdomain( 'configurator-links', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Init services
    CL_Groups::init();
    CL_Options::init();
    CL_Clients::init();
    CL_Submissions::init();
    CL_Emails::init();
    CL_Importer::init();
    CL_Exporter::init();
    CL_REST::init();

    // Admin UI
    if ( is_admin() ) {
        CL_Admin_Menu::init();
    }

    // Shortcode
    CL_Shortcode::init();
});

// Assets
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Enqueue on our pages only where possible
    $is_cl_page = isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'configurator_links' ) === 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( $is_cl_page ) {
        wp_enqueue_style( 'cl-admin', CL_PLUGIN_URL . 'assets/admin.css', [], CL_PLUGIN_VERSION );
        wp_enqueue_media();
        wp_enqueue_script( 'cl-admin', CL_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], CL_PLUGIN_VERSION, true );
        wp_localize_script( 'cl-admin', 'CLAdmin', [
            'nonce' => wp_create_nonce( 'cl_admin' ),
            'mediaTitle' => __( 'Selecteer afbeelding', 'configurator-links' ),
            'mediaButton' => __( 'Gebruik afbeelding', 'configurator-links' ),
        ] );
    }
});

add_action( 'wp_enqueue_scripts', function() {
    if ( is_singular() ) {
        wp_enqueue_style( 'cl-public', CL_PLUGIN_URL . 'assets/public.css', [], CL_PLUGIN_VERSION );
    }
});
