<?php
/**
 * Plugin Name: Paid Memberships Pro - mosparo Integration
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-mosparo/
 * Description: Protect your membership site from checkout spam with mosparo and Paid Memberships Pro.
 * Version: 1.0
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-mosparo
 * Domain Path: /languages
 */

use MosparoIntegration\Helper\ConfigHelper;

/**
 * Includes go here.
 */
require_once( dirname( __FILE__ ) . '/includes/class.pmpro-mosparo.php' );
require_once( dirname( __FILE__ ) . '/includes/checkout.php' );

/**
 * Admin notice to check mosparo Integration only (no PMPro check).
 * 
 * @since 1.0
 */
function pmpro_mosparo_requirements_check() {
    // Only show notices on PMPro-related pages.
    if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
        return;
    }

    // Check for mosparo Integration plugin only.
    $required_plugins = array(
        'mosparo-integration' => __( 'mosparo Integration', 'pmpro-mosparo' )
    );

    // Check if mosparo Integration is installed.
    $missing_plugins = array();
    foreach ( $required_plugins as $plugin => $name ) {
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
            $missing_plugins[$plugin] = $name;
        }
    }

    // If mosparo Integration is missing, show a notice.
    if ( ! empty( $missing_plugins ) ) {
        $install_plugins = array();
        foreach ( $missing_plugins as $path => $name ) {
            $install_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $path ), 'install-plugin_' . $path ) ), esc_html( $name ) );
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-mosparo' ),
                esc_html__( 'Paid Memberships Pro - mosparo Integration', 'pmpro-mosparo' ),
                implode( ', ', $install_plugins )
            )
        );
        return;
    }

    // Check if mosparo Integration is active.
    $inactive_plugins = array();
    foreach ( $required_plugins as $plugin => $name ) {
        $full_path = $plugin . '/' . $plugin . '.php';
        if ( ! is_plugin_active( $full_path ) ) {
            $inactive_plugins[$plugin] = $name;
        }
    }

    // If mosparo Integration is inactive, show a notice.
    if ( ! empty( $inactive_plugins ) ) {
        $activate_plugins = array();
        foreach ( $inactive_plugins as $path => $name ) {
            $full_path = $path . '/' . $path . '.php';
            $activate_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . $full_path ), 'activate-plugin_' . $full_path ) ), esc_html( $name ) );
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-mosparo' ),
                esc_html__( 'Paid Memberships Pro - mosparo Integration', 'pmpro-mosparo' ),
                implode( ', ', $activate_plugins )
            )
        );
        return;
    }

    // Check if mosparo has a valid connection, passing null explicitly.
    $configHelper = ConfigHelper::getInstance();
    if ( ! $configHelper->hasConnection( null ) ) {
        echo '<div class="error"><p>' . esc_html__( 'The Paid Memberships Pro - mosparo Integration requires a valid connection to a mosparo instance. Please configure mosparo Integration to enable anti-spam functionality.', 'pmpro-mosparo' ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'pmpro_mosparo_requirements_check' );

/**
 * Load the plugin text domain for translation.
 * 
 * @since 1.0
 */
function pmpro_mosparo_load_textdomain() {
    load_plugin_textdomain( 'pmpro-mosparo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pmpro_mosparo_load_textdomain' );