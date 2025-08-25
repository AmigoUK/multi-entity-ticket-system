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
        // Only modify CSP for METS-specific pages to avoid conflicts
        add_action( 'send_headers', array( __CLASS__, 'modify_csp_headers' ) );
        add_filter( 'wp_headers', array( __CLASS__, 'filter_wp_headers' ) );
    }

    /**
     * Modify CSP headers to allow METS functionality
     *
     * @since    1.0.0
     */
    public static function modify_csp_headers() {
        // Don't apply CSP if we're not on a METS page or if page builders are active
        if ( ! self::should_apply_csp() ) {
            return;
        }

        // Check if we're in a development environment
        $is_dev_environment = defined('WP_DEBUG') && WP_DEBUG && 
                             (strpos($_SERVER['HTTP_HOST'] ?? '', 'dev') !== false || 
                              strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);

        // Base CSP directives for METS functionality
        $csp_directives = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:",
            "style-src 'self' 'unsafe-inline' https:",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "connect-src 'self'",
            "worker-src 'self' blob:"
        );

        // Add more permissive directives for development
        if ($is_dev_environment) {
            $csp_directives[1] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: http: https:";
            $csp_directives[4] = "font-src 'self' data: https: http:";
            $csp_directives[] = "worker-src 'self' blob: http: https:";
        }

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
        // Check if we're in a development environment
        $is_dev_environment = defined('WP_DEBUG') && WP_DEBUG && 
                             (strpos($_SERVER['HTTP_HOST'] ?? '', 'dev') !== false || 
                              strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);

        // Base CSP directives for METS functionality
        $csp_directives = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:",
            "style-src 'self' 'unsafe-inline' https:",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "connect-src 'self'",
            "worker-src 'self' blob:"
        );

        // Add more permissive directives for development
        if ($is_dev_environment) {
            $csp_directives[1] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: http: https:";
            $csp_directives[4] = "font-src 'self' data: https: http:";
            $csp_directives[] = "worker-src 'self' blob: http: https:";
        }

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

    /**
     * Check if CSP should be applied
     *
     * @since    1.0.0
     * @return   bool    Whether CSP should be applied
     */
    private static function should_apply_csp() {
        global $pagenow;
        
        // Never apply CSP on page/post editors to avoid breaking page builders
        $excluded_pages = array( 'post.php', 'post-new.php', 'edit.php', 'customize.php' );
        if ( in_array( $pagenow, $excluded_pages ) ) {
            return false;
        }
        
        // Don't apply CSP if popular page builders are detected
        if ( self::is_page_builder_active() ) {
            return false;
        }
        
        // Only apply on METS-specific pages
        return self::is_mets_page();
    }

    /**
     * Check if current page is METS related
     *
     * @since    1.0.0
     * @return   bool    Whether current page is METS related
     */
    private static function is_mets_page() {
        global $pagenow;
        
        // Admin pages
        if ( is_admin() ) {
            $page = isset( $_GET['page'] ) ? $_GET['page'] : '';
            return strpos( $page, 'mets' ) === 0;
        }
        
        // Frontend pages with METS shortcodes or REST API
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            return strpos( $uri, '/wp-json/mets/' ) !== false;
        }
        
        // Pages with METS content
        global $post;
        if ( $post && ( has_shortcode( $post->post_content, 'ticket_form' ) || has_shortcode( $post->post_content, 'ticket_portal' ) ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if page builders are active to avoid CSP conflicts
     *
     * @since    1.0.0
     * @return   bool    Whether page builders are detected
     */
    private static function is_page_builder_active() {
        // Check for common page builder indicators
        $page_builder_indicators = array(
            'elementor', 'divi', 'beaver-builder', 'visual-composer', 
            'gutenberg', 'block-editor', 'oxygen', 'bricks'
        );
        
        // Check URL parameters
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ( $page_builder_indicators as $indicator ) {
            if ( strpos( $request_uri, $indicator ) !== false ) {
                return true;
            }
        }
        
        // Check $_GET parameters
        foreach ( $page_builder_indicators as $indicator ) {
            if ( isset( $_GET[ $indicator ] ) ) {
                return true;
            }
        }
        
        // Check for Elementor preview mode
        if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['preview'] ) ) {
            return true;
        }
        
        return false;
    }
}