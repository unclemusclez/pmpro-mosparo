<?php
/**
 * All checkout/registration functionality for Mosparo Integration.
 */

use MosparoIntegration\Helper\VerificationHelper;

/**
 * Run spam check during checkout process
 * 
 * @since 1.0
 */
function pmpro_mosparo_registration_checks( $continue ) {
    global $pmpro_mosparo_extra_nonce;

    // Bail if another check already failed.
    if ( ! $continue ) {
        return $continue;
    }
    
    // If the user is logged in already during checkout, just bail. Let's assume they're ok.
    if ( is_user_logged_in() ) {
        return $continue;
    }

    // Check if Mosparo Integration is active; bail if not.
    if ( ! class_exists( 'MosparoIntegration\Helper\VerificationHelper' ) ) {
        return $continue;
    }

    // Check if Mosparo has a valid connection.
    if ( ! PMPro_Mosparo::has_valid_connection() ) {
        return $continue;
    }

    // Mosparo submits a token via a hidden field (e.g., 'mosparo_token').
    if ( empty( $_REQUEST['mosparo_token'] ) ) {
        $continue = false;
        pmpro_setMessage( esc_html__( 'Spam protection failed. Please try again.', 'pmpro-mosparo' ), 'pmpro_error' );
        return $continue;
    }

    // Validate the Mosparo token.
    $verificationHelper = VerificationHelper::getInstance();
    $result = $verificationHelper->verifySubmission( sanitize_text_field( $_REQUEST['mosparo_token'] ) );

    // Adjust threshold logic based on level (free vs. paid).
    $level = pmpro_getLevelAtCheckout();
    if ( pmpro_isLevelFree( $level ) ) {
        $threshold = 1; // Stricter for free levels.
    } else {
        $threshold = 2;
    }

    // If an extra nonce was passed in, raise the threshold.
    if ( ! empty( $_REQUEST['pmpro_mosparo_extra_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['pmpro_mosparo_extra_nonce'] ), 'pmpro_mosparo_extra_nonce' ) ) {
        $threshold = 2;
        $pmpro_mosparo_extra_nonce = wp_create_nonce( 'pmpro_mosparo_extra_nonce' ); // Update nonce for resubmission.
    }

    /**
     * Filter the threshold for spam detection.
     * @param int $threshold The threshold to determine if the user is spam or not.
     * @param object $level The level the user is signing up for.
     * @param mixed $result The Mosparo verification result.
     */
    $threshold = apply_filters( 'pmpro_mosparo_threshold', $threshold, $level, $result );

    // Check Mosparo verification result.
    $is_spam = PMPro_Mosparo::is_spam( $result ); // Delegate to class method.

    if ( ! $is_spam ) {
        $continue = true;
    } else {
        // Track spam activity if PMPro supports it.
        if ( function_exists( 'pmpro_track_spam_activity' ) ) {
            pmpro_track_spam_activity();
        }
        
        // Stop checkout if above the threshold.
        if ( (int)$is_spam >= (int)$threshold ) {    
            $continue = false;
            pmpro_setMessage( esc_html__( 'Your submission has been flagged as suspicious. Please double-check all fields and try again.', 'pmpro-mosparo' ), 'pmpro_error' );
            $pmpro_mosparo_extra_nonce = wp_create_nonce( 'pmpro_mosparo_extra_nonce' );
        }
    }

    return $continue;
}
add_filter( 'pmpro_registration_checks', 'pmpro_mosparo_registration_checks', 10, 1 );

/**
 * Add the Mosparo script and hidden token field to the checkout form.
 */
function pmpro_mosparo_add_frontend_script() {
    global $pmpro_mosparo_extra_nonce;

    if ( ! PMPro_Mosparo::has_valid_connection() ) {
        return;
    }

    // Add Mosparo JavaScript (assumes Mosparo Integration provides this).
    $configHelper = \MosparoIntegration\Helper\ConfigHelper::getInstance();
    $connection = $configHelper->getConnection();
    if ( $connection ) {
        $host = $connection->getHost();
        $uuid = $connection->getUuid();
        $publicKey = $connection->getPublicKey();
        ?>
        <script type="text/javascript" src="<?php echo esc_url( $host . '/mosparo.js' ); ?>" async></script>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                new mosparo('Mosparo-box', '<?php echo esc_js( $uuid ); ?>', '<?php echo esc_js( $publicKey ); ?>', {
                    loadCssResource: true,
                    onSuccess: function(token) {
                        document.getElementById('mosparo_token').value = token;
                    }
                });
            });
        </script>
        <div id="mosparo-box"></div>
        <input type="hidden" id="mosparo_token" name="mosparo_token" value="" />
        <?php
    }

    // Add extra nonce if needed.
    if ( ! empty( $pmpro_mosparo_extra_nonce ) ) {
        ?>
        <input type="hidden" name="pmpro_mosparo_extra_nonce" value="<?php echo esc_attr( $pmpro_mosparo_extra_nonce ); ?>" />
        <?php
    }
}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_mosparo_add_frontend_script' );

/**
 * Show Mosparo privacy notice below the submit button.
 * 
 * @since 1.0
 */
function pmpro_mosparo_show_privacy_notice() {
    global $pmpro_mosparo_extra_nonce;

    // Check if privacy notice should be displayed (custom filter for flexibility).
    if ( 'display' !== apply_filters( 'pmpro_mosparo_checkout_privacy_notice', 'display' ) ) {
        return;
    }

    // Show a message about Mosparo spam protection.
    ?>
    <p class="pmpro_mosparo_privacy_notice">
        <?php esc_html_e( 'This site uses mosparo to reduce spam.', 'pmpro-mosparo' ); ?>
        <a href="<?php echo esc_url( 'https://mosparo.io/about-mosparo/' ); ?>" target="_blank" rel="nofollow noopener"><?php esc_html_e( 'Learn how your data is processed', 'pmpro-mosparo' ); ?></a>.
    </p>
    <?php
}
add_action( 'pmpro_checkout_before_submit_button', 'pmpro_mosparo_show_privacy_notice' );