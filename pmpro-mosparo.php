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
require_once( dirname( __FILE__ ) . '/includes/class.pmpro-mosparo.php' ); // Adjust to a new mosparo-specific class if needed
require_once( dirname( __FILE__ ) . '/includes/checkout.php' ); // Update this file to use mosparo instead of Akismet

/**
 * Admin notice to show a warning that required plugins are inactive or misconfigured.
 * 
 * @since 1.0
 */
function pmpro_mosparo_pmpro_required() {

    // Only show notices on PMPro page.
    if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
        return;
    }

    // The required plugins for this Add On to work.
    $required_plugins = array(
        'paid-memberships-pro' => __( 'Paid Memberships Pro', 'pmpro-mosparo' ),
        'mosparo-integration' => __( 'mosparo Integration', 'pmpro-mosparo' )
    );

    // Check if the required plugins are installed.
    $missing_plugins = array();
    foreach ( $required_plugins as $plugin => $name ) {
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
            $missing_plugins[$plugin] = $name;
        }
    }

    // If there are missing plugins, show a notice.
    if ( ! empty( $missing_plugins ) ) {
        // Build install links here.
        $install_plugins = array();
        foreach( $missing_plugins as $path => $name ) {
            $install_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $path ), 'install-plugin_' . $path ) ), esc_html( $name ) );
        }

        // Show notice with install_plugin links.
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-mosparo' ),
                esc_html__( 'Paid Memberships Pro - mosparo Integration', 'pmpro-mosparo' ),
                implode( ', ', $install_plugins ) // $install_plugins was escaped when built.
            )
        );

        return; // Bail here, so we only show one notice at a time.
    }

    // Check if the required plugins are active and show a notice with activation links if they are not
    $inactive_plugins = array();
    foreach ( $required_plugins as $plugin => $name ) {
        $full_path = $plugin . '/' . $plugin . '.php';
        if ( ! is_plugin_active( $full_path ) ) {
            $inactive_plugins[$plugin] = $name;
        }
    }

    // If there are inactive plugins, show a notice.
    if ( ! empty( $inactive_plugins ) ) {
        // Build activate links here.
        $activate_plugins = array();
        foreach( $inactive_plugins as $path => $name ) {
            $full_path = $path . '/' . $path . '.php';
            $activate_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . $full_path ), 'activate-plugin_' . $full_path ) ), esc_html( $name ) );
        }

        // Show notice with activate_plugin links.
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-mosparo' ),
                esc_html__( 'Paid Memberships Pro - mosparo Integration', 'pmpro-mosparo' ),
                implode( ', ', $activate_plugins ) // $activate_plugins was escaped when built.
            )
        );

        return; // Bail here, so we only show one notice at a time.
    }

    // Check if mosparo is properly configured (e.g., connection to mosparo instance).
    $configHelper = ConfigHelper::getInstance();
    if ( ! $configHelper->hasConnection() ) { // Assuming ConfigHelper has a method like this; adjust as per mosparo's API.
        echo '<div class="error"><p>' . esc_html__( 'The Paid Memberships Pro - mosparo Integration requires a valid connection to a mosparo instance. Please configure mosparo Integration to enable anti-spam functionality.', 'pmpro-mosparo' ) . '</p></div>';
        return;
    }

}
add_action( 'admin_notices', 'pmpro_mosparo_pmpro_required' );

/**
 * Load the plugin text domain for translation.
 * 
 * @since 1.0
 */
function pmpro_mosparo_load_textdomain() {
    load_plugin_textdomain( 'pmpro-mosparo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pmpro_mosparo_load_textdomain' );
