<?php
/**
 * Handle Content Security Policy for n8n chat integration
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_CSP_Handler {

    /**
     * Initialize CSP modifications for METS features
     *
     * @since    1.0.0
     */
    public static function init() {
        // Always modify CSP for METS features (forms, n8n chat, etc.)
        add_action( 'send_headers', array( __CLASS__, 'modify_csp_headers' ) );
        add_filter( 'wp_headers', array( __CLASS__, 'filter_wp_headers' ) );
    }

    /**
     * Modify CSP headers to allow METS functionality
     *
     * @since    1.0.0
     */
    public static function modify_csp_headers() {
        // Base CSP directives for METS functionality
        $csp_directives = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https:",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "connect-src 'self'"
        );

        // Add n8n chat support if enabled
        $n8n_settings = get_option( 'mets_n8n_chat_settings', array() );
        $webhook_url = $n8n_settings['webhook_url'] ?? '';
        
        if ( ! empty( $n8n_settings['enabled'] ) && ! empty( $webhook_url ) ) {
            // Extract domain from webhook URL for CSP
            $parsed_url = parse_url( $webhook_url );
            if ( $parsed_url && isset( $parsed_url['scheme'] ) && isset( $parsed_url['host'] ) ) {
                $webhook_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                if ( isset( $parsed_url['port'] ) ) {
                    $webhook_domain .= ':' . $parsed_url['port'];
                }
                // Update connect-src to include n8n webhook domain
                $csp_directives[5] = "connect-src 'self' " . esc_attr( $webhook_domain );
            }
        }

        // Add additional METS-specific directives
        $csp_directives[] = "frame-src 'self'";
        $csp_directives[] = "object-src 'none'";
        $csp_directives[] = "base-uri 'self'";

        // Set the CSP header
        $csp_header = implode( '; ', $csp_directives );
        header( "Content-Security-Policy: " . $csp_header );
    }

    /**
     * Filter WordPress headers to modify CSP
     *
     * @since    1.0.0
     * @param    array    $headers    Existing headers
     * @return   array                Modified headers
     */
    public static function filter_wp_headers( $headers ) {
        // Base CSP directives for METS functionality
        $csp_directives = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https:",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "connect-src 'self'"
        );

        // Add n8n chat support if enabled
        $n8n_settings = get_option( 'mets_n8n_chat_settings', array() );
        $webhook_url = $n8n_settings['webhook_url'] ?? '';
        
        if ( ! empty( $n8n_settings['enabled'] ) && ! empty( $webhook_url ) ) {
            // Extract domain from webhook URL for CSP
            $parsed_url = parse_url( $webhook_url );
            if ( $parsed_url && isset( $parsed_url['scheme'] ) && isset( $parsed_url['host'] ) ) {
                $webhook_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                if ( isset( $parsed_url['port'] ) ) {
                    $webhook_domain .= ':' . $parsed_url['port'];
                }
                // Update connect-src to include n8n webhook domain
                $csp_directives[5] = "connect-src 'self' " . esc_attr( $webhook_domain );
            }
        }

        // Add additional METS-specific directives
        $csp_directives[] = "frame-src 'self'";
        $csp_directives[] = "object-src 'none'";
        $csp_directives[] = "base-uri 'self'";

        // Set the CSP header
        $headers['Content-Security-Policy'] = implode( '; ', $csp_directives );

        return $headers;
    }

    /**
     * Get current CSP settings info for debugging
     *
     * @since    1.0.0
     * @return   array    CSP information
     */
    public static function get_csp_info() {
        $n8n_settings = get_option( 'mets_n8n_chat_settings', array() );
        $webhook_url = $n8n_settings['webhook_url'] ?? '';
        
        $info = array(
            'n8n_enabled' => ! empty( $n8n_settings['enabled'] ),
            'webhook_url' => $webhook_url,
            'webhook_domain' => '',
            'csp_active' => false
        );

        if ( ! empty( $webhook_url ) ) {
            $parsed_url = parse_url( $webhook_url );
            if ( $parsed_url && isset( $parsed_url['scheme'] ) && isset( $parsed_url['host'] ) ) {
                $info['webhook_domain'] = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                if ( isset( $parsed_url['port'] ) ) {
                    $info['webhook_domain'] .= ':' . $parsed_url['port'];
                }
                $info['csp_active'] = true;
            }
        }

        return $info;
    }
}