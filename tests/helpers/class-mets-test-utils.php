<?php
/**
 * Test utility functions
 *
 * @package METS_Tests
 */

class METS_Test_Utils {

    /**
     * Array to store sent emails during testing
     *
     * @var array
     */
    private static $sent_emails = [];

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into wp_mail to capture sent emails during testing
        add_filter( 'wp_mail', [ $this, 'capture_sent_email' ] );
    }

    /**
     * Capture sent emails for testing
     *
     * @param array $args wp_mail arguments
     * @return array
     */
    public function capture_sent_email( $args ) {
        self::$sent_emails[] = $args;
        return $args;
    }

    /**
     * Get captured sent emails
     *
     * @return array
     */
    public function get_sent_emails() {
        return self::$sent_emails;
    }

    /**
     * Clear sent emails
     */
    public function clear_sent_emails() {
        self::$sent_emails = [];
    }

    /**
     * Delete a directory and all its contents
     *
     * @param string $dir Directory path
     * @return bool
     */
    public function delete_directory( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return false;
        }
        
        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        
        foreach ( $files as $file ) {
            $path = $dir . '/' . $file;
            if ( is_dir( $path ) ) {
                $this->delete_directory( $path );
            } else {
                unlink( $path );
            }
        }
        
        return rmdir( $dir );
    }

    /**
     * Create a temporary uploaded file
     *
     * @param string $filename File name
     * @param string $content File content
     * @return string File path
     */
    public function create_temp_file( $filename, $content = 'Test file content' ) {
        $upload_dir = wp_upload_dir();
        $test_dir = $upload_dir['basedir'] . '/mets-tests';
        
        if ( ! is_dir( $test_dir ) ) {
            wp_mkdir_p( $test_dir );
        }
        
        $file_path = $test_dir . '/' . $filename;
        file_put_contents( $file_path, $content );
        
        return $file_path;
    }

    /**
     * Simulate a file upload
     *
     * @param string $filename File name
     * @param string $content File content
     * @param string $mime_type MIME type
     * @return array $_FILES array format
     */
    public function simulate_file_upload( $filename, $content = 'Test file', $mime_type = 'text/plain' ) {
        $file_path = $this->create_temp_file( $filename, $content );
        
        return [
            'name' => $filename,
            'type' => $mime_type,
            'tmp_name' => $file_path,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen( $content )
        ];
    }

    /**
     * Get current METS settings
     *
     * @return array
     */
    public function get_mets_settings() {
        return get_option( 'mets_settings', [] );
    }

    /**
     * Set METS settings
     *
     * @param array $settings Settings to merge
     */
    public function set_mets_settings( $settings ) {
        $current = $this->get_mets_settings();
        $new_settings = array_merge( $current, $settings );
        update_option( 'mets_settings', $new_settings );
    }

    /**
     * Simulate a POST request
     *
     * @param array $data POST data
     */
    public function simulate_post_request( $data ) {
        $_POST = array_merge( $_POST, $data );
        $_REQUEST = array_merge( $_REQUEST, $data );
    }

    /**
     * Simulate a GET request
     *
     * @param array $data GET data
     */
    public function simulate_get_request( $data ) {
        $_GET = array_merge( $_GET, $data );
        $_REQUEST = array_merge( $_REQUEST, $data );
    }

    /**
     * Clear request data
     */
    public function clear_request_data() {
        $_POST = [];
        $_GET = [];
        $_REQUEST = [];
    }

    /**
     * Assert that a database table exists
     *
     * @param string $table_name Table name (without prefix)
     * @return bool
     */
    public function table_exists( $table_name ) {
        global $wpdb;
        
        $full_table_name = $wpdb->prefix . $table_name;
        $result = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $full_table_name
        ));
        
        return $result === $full_table_name;
    }

    /**
     * Get table row count
     *
     * @param string $table_name Table name (without prefix)
     * @return int
     */
    public function get_table_row_count( $table_name ) {
        global $wpdb;
        
        $full_table_name = $wpdb->prefix . $table_name;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $full_table_name" );
    }

    /**
     * Assert that a specific option exists
     *
     * @param string $option_name Option name
     * @return bool
     */
    public function option_exists( $option_name ) {
        $value = get_option( $option_name, '__mets_test_not_found__' );
        return $value !== '__mets_test_not_found__';
    }

    /**
     * Mock external API response
     *
     * @param string $url URL to mock
     * @param array $response Response data
     */
    public function mock_http_response( $url, $response ) {
        add_filter( 'pre_http_request', function( $false, $args, $requested_url ) use ( $url, $response ) {
            if ( $requested_url === $url ) {
                return $response;
            }
            return $false;
        }, 10, 3 );
    }

    /**
     * Generate random string
     *
     * @param int $length String length
     * @return string
     */
    public function generate_random_string( $length = 10 ) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        
        for ( $i = 0; $i < $length; $i++ ) {
            $string .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
        }
        
        return $string;
    }

    /**
     * Generate random email
     *
     * @return string
     */
    public function generate_random_email() {
        return 'test' . $this->generate_random_string( 8 ) . '@example.com';
    }
}