<?php
/**
 * PHPStan bootstrap file for METS Plugin
 */

// Define WordPress constants for PHPStan analysis
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_CONTENT_URL')) {
    define('WP_CONTENT_URL', 'http://localhost/wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('WP_PLUGIN_URL')) {
    define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// METS Plugin constants
if (!defined('METS_VERSION')) {
    define('METS_VERSION', '1.0.0');
}

if (!defined('METS_PLUGIN_PATH')) {
    define('METS_PLUGIN_PATH', __DIR__ . '/../wp-content/plugins-backup/multi-entity-ticket-system-emergency-backup/');
}

if (!defined('METS_PLUGIN_URL')) {
    define('METS_PLUGIN_URL', 'http://localhost/wp-content/plugins/multi-entity-ticket-system/');
}

if (!defined('METS_TEXT_DOMAIN')) {
    define('METS_TEXT_DOMAIN', 'multi-entity-ticket-system');
}

// Mock WordPress globals
global $wpdb, $wp_roles, $wp_filter;

// Basic WordPress function stubs for analysis
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        exit($message);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        return null;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = "_wpnonce", $referer = true, $echo = true) {
        return '<input type="hidden" name="' . $name . '" value="1234567890" />';
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

// Mock WordPress classes
if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct($code = '', $message = '', $data = '') {}
        public function get_error_code() { return ''; }
        public function get_error_message($code = '') { return ''; }
        public function get_error_data($code = '') { return null; }
        public function add($code, $message, $data = '') {}
    }
}

if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = [];
        public $post_count = 0;
        public $found_posts = 0;
        public $max_num_pages = 0;
        public function __construct($query = '') {}
        public function query($query) { return []; }
        public function get_posts() { return []; }
        public function have_posts() { return false; }
        public function the_post() {}
    }
}

if (!class_exists('WP_User')) {
    class WP_User {
        public $ID = 0;
        public $roles = [];
        public function __construct($id = 0, $name = '', $site_id = '') {}
        public function get($key) { return ''; }
        public function has_cap($cap) { return true; }
        public function add_role($role) {}
        public function remove_role($role) {}
    }
}

if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public $last_error = '';
        public $insert_id = 0;
        public $num_rows = 0;
        
        public function prepare($query, ...$args) { return $query; }
        public function get_results($query, $output = OBJECT) { return []; }
        public function get_row($query, $output = OBJECT, $y = 0) { return null; }
        public function get_var($query, $x = 0, $y = 0) { return null; }
        public function query($query) { return false; }
        public function insert($table, $data, $format = null) { return false; }
        public function update($table, $data, $where, $format = null, $where_format = null) { return false; }
        public function delete($table, $where, $where_format = null) { return false; }
    }
}

$wpdb = new wpdb();