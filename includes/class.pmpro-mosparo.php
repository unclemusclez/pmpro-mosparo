<?php
namespace PMPro_Mosparo;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;

class PMPro_Mosparo {
    
    /**
     * Check if mosparo Integration is active.
     * 
     * @since 1.0
     * @return bool True if mosparo Integration is active, false otherwise.
     */
    public static function is_active() {
        return class_exists( 'MosparoIntegration\Helper\VerificationHelper' );
    }

    /**
     * Check if mosparo has a valid connection.
     *
     * @since 1.0
     * @return bool True if mosparo has a valid connection, false otherwise.
     */
    public static function has_valid_connection() {
        if ( ! self::is_active() ) {
            return false;
        }
        $configHelper = ConfigHelper::getInstance();
        return $configHelper->hasConnection(); // Assumes this method exists in mosparo.
    }

    /**
     * Check if the submission is spam based on mosparo verification result.
     * 
     * @since 1.0
     * @param mixed $result The verification result from mosparo.
     * @return int 2 for blatant spam, 1 for likely spam, 0 for not spam.
     */
    public static function is_spam( $result ) {
        if ( ! self::is_active() || ! self::has_valid_connection() ) {
            return 0; // Not spam if mosparo isn't configured.
        }

        if ( ! $result || ! isset( $result['valid'] ) ) {
            return 2; // Treat invalid/no result as blatant spam.
        }

        if ( $result['valid'] ) {
            return 0; // Valid submission, not spam.
        } else {
            // Check if it's blatant spam (e.g., all required fields failed).
            if ( empty( $result['verifiedFields'] ) ) {
                return 2; // No fields verified, blatant spam.
            }
            return 1; // Likely spam if some fields failed.
        }
    }
}