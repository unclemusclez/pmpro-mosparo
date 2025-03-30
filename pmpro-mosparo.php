<?php
/**
 * Plugin Name: Paid Memberships Pro - Mosparo Integration
 * Plugin URI: https://github.com/yourusername/pmpro-mosparo-integration
 * Description: Integrates Mosparo spam protection with Paid Memberships Pro.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: pmpro-mosparo-integration
 * Domain Path: /languages
 * License: GPL v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'PMPRO_MOSPARO_DIR', dirname( __FILE__ ) );
define( 'PMPRO_MOSPARO_BASENAME', plugin_basename( __FILE__ ) );

// Includes (adjust as needed)
require_once PMPRO_MOSPARO_DIR . '/includes/checkout.php'; // Replace with your actual includes if any
require_once PMPRO_MOSPARO_DIR . '/includes/class.pmpro-mosparo.php';

function pmpro_mosparo_load_textdomain() {
    load_plugin_textdomain( 'pmpro-mosparo-integration', false, dirname( PMPRO_MOSPARO_BASENAME ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pmpro_mosparo_load_textdomain' );

function pmpro_mosparo_requirements_check() {
    if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
        return;
    }

    if ( ! function_exists( 'is_plugin_active' ) ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    $mosparo_plugin = 'mosparo-integration/mosparo-integration.php';
    $is_mosparo_active = is_plugin_active( $mosparo_plugin );

    // Check Mosparo settings for a valid connection using the correct option key
    $mosparo_config = get_option( 'mosparo-integration-configuration', [] );
    $has_valid_connection = ! empty( $mosparo_config['connections'] ) && is_array( $mosparo_config['connections'] ) && ! empty( array_filter( $mosparo_config['connections'], function( $conn ) {
        // Handle both array and object formats due to serialization
        $host = is_object( $conn ) ? ($conn->getHost() ?? '') : ($conn['host'] ?? '');
        $uuid = is_object( $conn ) ? ($conn->getUuid() ?? '') : ($conn['uuid'] ?? '');
        return ! empty( $host ) && ! empty( $uuid );
    } ) );

    if ( ! $is_mosparo_active || ! $has_valid_connection ) {
        $message = $is_mosparo_active ?
            __( 'The Paid Memberships Pro - Mosparo Integration requires a valid connection to a Mosparo instance. Please configure the Mosparo Integration plugin under Settings > Mosparo Integration to enable anti-spam functionality.', 'pmpro-mosparo-integration' ) :
            sprintf(
                __( 'The %1$s plugin requires the "Mosparo Integration" plugin to be installed and active. <a href="%2$s">Install Mosparo Integration</a>.', 'pmpro-mosparo-integration' ),
                __( 'Paid Memberships Pro - Mosparo Integration', 'pmpro-mosparo-integration' ),
                esc_url( admin_url( 'plugin-install.php?s=mosparo-integration&tab=search&type=term' ) )
            );
        printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $message ) );
    }

    // Debug logging
    file_put_contents( '/tmp/pmpro-mosparo-debug.log', 'PMPro Mosparo: Mosparo Active: ' . ( $is_mosparo_active ? 'yes' : 'no' ) . ', Valid Connection: ' . ( $has_valid_connection ? 'yes' : 'no' ) . "\n", FILE_APPEND );
    file_put_contents( '/tmp/pmpro-mosparo-debug.log', 'PMPro Mosparo: Mosparo Config: ' . print_r( $mosparo_config, true ) . "\n", FILE_APPEND );
}

add_action( 'admin_notices', 'pmpro_mosparo_requirements_check' );

function pmpro_mosparo_init() {
    if ( function_exists( 'pmpro_getMembershipLevelForUser' ) && class_exists( 'MosparoIntegration\Module' ) ) {
        // Your initialization logic here
        file_put_contents( '/tmp/pmpro-mosparo-debug.log', 'PMPro Mosparo: Initialization successful.' . "\n", FILE_APPEND );
    }
}
add_action( 'init', 'pmpro_mosparo_init', 20 );