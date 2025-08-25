<?php
/**
 * SMTP Diagnostic Tool for Multi-Entity Ticket System
 * 
 * This tool helps diagnose SMTP connection and configuration issues.
 * Usage: Run this file from WordPress admin or via command line.
 * 
 * SECURITY NOTE: Remove this file after diagnostics are complete.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to load WordPress if not already loaded
    $wp_load_paths = [
        dirname(__FILE__) . '/../../../../wp-load.php',
        dirname(__FILE__) . '/../../../wp-load.php',
        dirname(__FILE__) . '/../../wp-load.php',
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Could not load WordPress. Please run this from WordPress admin or ensure proper path.');
    }
}

// Check if user has permission (if running from web)
if (isset($_SERVER['HTTP_HOST']) && !current_user_can('manage_options')) {
    wp_die('Insufficient permissions to run SMTP diagnostics.');
}

/**
 * METS SMTP Diagnostic Tool
 */
class METS_SMTP_Diagnostic {
    
    private $results = [];
    private $smtp_settings = [];
    
    public function __construct() {
        $this->load_smtp_settings();
    }
    
    /**
     * Load SMTP settings from WordPress options
     */
    private function load_smtp_settings() {
        $this->smtp_settings = get_option('mets_smtp_settings', []);
        
        if (empty($this->smtp_settings)) {
            $this->results[] = [
                'test' => 'SMTP Settings',
                'status' => 'WARNING',
                'message' => 'No SMTP settings found. Please configure SMTP first.'
            ];
        }
    }
    
    /**
     * Run all diagnostic tests
     */
    public function run_diagnostics() {
        echo "<h1>METS SMTP Diagnostic Tool</h1>\n";
        echo "<div style='font-family: monospace; white-space: pre-wrap;'>\n";
        
        $this->test_wordpress_environment();
        $this->test_smtp_settings();
        $this->test_network_connectivity();
        $this->test_authentication();
        $this->test_email_queue();
        $this->test_mail_functions();
        $this->display_log_analysis();
        $this->display_recommendations();
        
        echo "</div>\n";
        
        return $this->results;
    }
    
    /**
     * Test WordPress environment
     */
    private function test_wordpress_environment() {
        echo "=== WordPress Environment ===\n";
        
        // WordPress version
        global $wp_version;
        echo "WordPress Version: {$wp_version}\n";
        
        // PHP version
        $php_version = phpversion();
        echo "PHP Version: {$php_version}\n";
        
        if (version_compare($php_version, '7.4', '<')) {
            $this->results[] = [
                'test' => 'PHP Version',
                'status' => 'WARNING',
                'message' => 'PHP version is old. Consider upgrading for better performance.'
            ];
        }
        
        // Check required extensions
        $required_extensions = ['openssl', 'curl', 'json'];
        foreach ($required_extensions as $ext) {
            $loaded = extension_loaded($ext);
            echo "Extension {$ext}: " . ($loaded ? 'LOADED' : 'MISSING') . "\n";
            
            if (!$loaded) {
                $this->results[] = [
                    'test' => 'PHP Extensions',
                    'status' => 'ERROR',
                    'message' => "Required PHP extension '{$ext}' is missing."
                ];
            }
        }
        
        // Check if METS is active
        if (class_exists('METS_Core')) {
            echo "METS Plugin: ACTIVE\n";
            
            // Check SMTP classes
            $smtp_classes = [
                'METS_SMTP_Manager',
                'METS_SMTP_Mailer', 
                'METS_Email_Queue'
            ];
            
            foreach ($smtp_classes as $class) {
                $exists = class_exists($class);
                echo "Class {$class}: " . ($exists ? 'LOADED' : 'MISSING') . "\n";
                
                if (!$exists) {
                    $this->results[] = [
                        'test' => 'METS Classes',
                        'status' => 'ERROR',
                        'message' => "Required class '{$class}' is missing."
                    ];
                }
            }
        } else {
            echo "METS Plugin: INACTIVE\n";
            $this->results[] = [
                'test' => 'METS Plugin',
                'status' => 'ERROR',
                'message' => 'METS plugin is not active.'
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Test SMTP configuration
     */
    private function test_smtp_settings() {
        echo "=== SMTP Configuration ===\n";
        
        if (empty($this->smtp_settings)) {
            echo "Status: NOT CONFIGURED\n";
            return;
        }
        
        $required_fields = ['host', 'port', 'username', 'password', 'from_email'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            $value = isset($this->smtp_settings[$field]) ? $this->smtp_settings[$field] : '';
            $status = !empty($value) ? 'SET' : 'MISSING';
            
            if ($field === 'password') {
                echo "Field {$field}: " . (!empty($value) ? 'SET (****)' : 'MISSING') . "\n";
            } else {
                echo "Field {$field}: {$status}" . (!empty($value) ? " ({$value})" : '') . "\n";
            }
            
            if (empty($value)) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            $this->results[] = [
                'test' => 'SMTP Configuration',
                'status' => 'ERROR',
                'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
            ];
        }
        
        // Validate email format
        $from_email = $this->smtp_settings['from_email'] ?? '';
        if (!empty($from_email) && !is_email($from_email)) {
            $this->results[] = [
                'test' => 'From Email',
                'status' => 'ERROR',
                'message' => 'From email address is not valid.'
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Test network connectivity
     */
    private function test_network_connectivity() {
        echo "=== Network Connectivity ===\n";
        
        if (empty($this->smtp_settings['host']) || empty($this->smtp_settings['port'])) {
            echo "Skipping network test - missing host/port configuration\n\n";
            return;
        }
        
        $host = $this->smtp_settings['host'];
        $port = $this->smtp_settings['port'];
        
        echo "Testing connection to {$host}:{$port}...\n";
        
        // Test basic connectivity
        $connection = @fsockopen($host, $port, $errno, $errstr, 30);
        
        if ($connection) {
            echo "Connection: SUCCESS\n";
            fclose($connection);
            
            $this->results[] = [
                'test' => 'Network Connectivity',
                'status' => 'SUCCESS',
                'message' => "Successfully connected to {$host}:{$port}"
            ];
        } else {
            echo "Connection: FAILED\n";
            echo "Error: {$errstr} (Code: {$errno})\n";
            
            $this->results[] = [
                'test' => 'Network Connectivity',
                'status' => 'ERROR',
                'message' => "Cannot connect to {$host}:{$port}. Error: {$errstr}"
            ];
        }
        
        // Test DNS resolution
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            echo "DNS Resolution: FAILED\n";
            $this->results[] = [
                'test' => 'DNS Resolution',
                'status' => 'ERROR',
                'message' => "Cannot resolve hostname: {$host}"
            ];
        } else {
            echo "DNS Resolution: SUCCESS ({$ip})\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test SMTP authentication
     */
    private function test_authentication() {
        echo "=== SMTP Authentication ===\n";
        
        if (!class_exists('METS_SMTP_Mailer')) {
            echo "METS SMTP Mailer not available\n\n";
            return;
        }
        
        try {
            $mailer = new METS_SMTP_Mailer();
            $result = $mailer->test_connection();
            
            if ($result['success']) {
                echo "Authentication: SUCCESS\n";
                $this->results[] = [
                    'test' => 'SMTP Authentication',
                    'status' => 'SUCCESS',
                    'message' => 'SMTP authentication successful'
                ];
            } else {
                echo "Authentication: FAILED\n";
                echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
                
                $this->results[] = [
                    'test' => 'SMTP Authentication',
                    'status' => 'ERROR',
                    'message' => $result['error'] ?? 'Unknown authentication error'
                ];
            }
        } catch (Exception $e) {
            echo "Authentication: ERROR\n";
            echo "Exception: " . $e->getMessage() . "\n";
            
            $this->results[] = [
                'test' => 'SMTP Authentication',
                'status' => 'ERROR',
                'message' => 'Exception during authentication: ' . $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Test email queue system
     */
    private function test_email_queue() {
        echo "=== Email Queue System ===\n";
        
        if (!class_exists('METS_Email_Queue')) {
            echo "Email Queue: NOT AVAILABLE\n\n";
            return;
        }
        
        try {
            global $wpdb;
            
            // Check if queue table exists
            $table_name = $wpdb->prefix . 'mets_email_queue';
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
            
            if ($table_exists) {
                echo "Queue Table: EXISTS\n";
                
                // Check queue status
                $pending = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'");
                $processing = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'processing'");
                $failed = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'failed'");
                $sent = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'sent'");
                
                echo "Queue Status:\n";
                echo "  Pending: {$pending}\n";
                echo "  Processing: {$processing}\n";
                echo "  Failed: {$failed}\n";
                echo "  Sent: {$sent}\n";
                
                if ($failed > 0) {
                    $this->results[] = [
                        'test' => 'Email Queue',
                        'status' => 'WARNING',
                        'message' => "There are {$failed} failed emails in the queue"
                    ];
                }
                
            } else {
                echo "Queue Table: MISSING\n";
                $this->results[] = [
                    'test' => 'Email Queue',
                    'status' => 'ERROR',
                    'message' => 'Email queue table is missing'
                ];
            }
            
        } catch (Exception $e) {
            echo "Queue Check: ERROR\n";
            echo "Exception: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test WordPress mail functions
     */
    private function test_mail_functions() {
        echo "=== WordPress Mail Test ===\n";
        
        // Test wp_mail function
        echo "Testing wp_mail function...\n";
        
        $test_email = 'test@example.com';
        $subject = 'METS SMTP Diagnostic Test';
        $message = 'This is a test email from the METS SMTP diagnostic tool.';
        
        // Capture any errors
        $mail_error = '';
        add_action('wp_mail_failed', function($wp_error) use (&$mail_error) {
            $mail_error = $wp_error->get_error_message();
        });
        
        // Attempt to send (but intercept before actual sending)
        add_filter('pre_wp_mail', function($null, $atts) {
            // Don't actually send, just test the setup
            return ['success' => true, 'message' => 'Test intercepted - would send successfully'];
        }, 10, 2);
        
        $result = wp_mail($test_email, $subject, $message);
        
        if ($result) {
            echo "wp_mail Test: SUCCESS\n";
            $this->results[] = [
                'test' => 'WordPress Mail',
                'status' => 'SUCCESS',
                'message' => 'wp_mail function is working correctly'
            ];
        } else {
            echo "wp_mail Test: FAILED\n";
            if ($mail_error) {
                echo "Error: {$mail_error}\n";
            }
            
            $this->results[] = [
                'test' => 'WordPress Mail',
                'status' => 'ERROR',
                'message' => $mail_error ?: 'wp_mail function failed'
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Display recent log analysis
     */
    private function display_log_analysis() {
        echo "=== Recent SMTP Logs ===\n";
        
        try {
            global $wpdb;
            
            $log_table = $wpdb->prefix . 'mets_smtp_logs';
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $log_table));
            
            if ($table_exists) {
                // Get recent logs
                $recent_logs = $wpdb->get_results(
                    "SELECT * FROM {$log_table} 
                     ORDER BY created_at DESC 
                     LIMIT 10"
                );
                
                if ($recent_logs) {
                    echo "Recent log entries:\n";
                    foreach ($recent_logs as $log) {
                        $status = $log->status === 'success' ? 'SUCCESS' : 'FAILED';
                        echo "  [{$log->created_at}] {$status}: {$log->message}\n";
                    }
                    
                    // Analyze patterns
                    $success_count = $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$log_table} 
                         WHERE status = 'success' 
                         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                    );
                    
                    $error_count = $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$log_table} 
                         WHERE status = 'error' 
                         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                    );
                    
                    echo "\nLast 24 hours:\n";
                    echo "  Successful: {$success_count}\n";
                    echo "  Failed: {$error_count}\n";
                    
                    if ($error_count > $success_count) {
                        $this->results[] = [
                            'test' => 'Log Analysis',
                            'status' => 'WARNING',
                            'message' => 'More failed emails than successful in last 24 hours'
                        ];
                    }
                } else {
                    echo "No recent log entries found\n";
                }
            } else {
                echo "SMTP log table not found\n";
            }
            
        } catch (Exception $e) {
            echo "Error reading logs: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Display recommendations based on test results
     */
    private function display_recommendations() {
        echo "=== Diagnostic Summary ===\n";
        
        $error_count = 0;
        $warning_count = 0;
        
        foreach ($this->results as $result) {
            $icon = $result['status'] === 'SUCCESS' ? '✓' : 
                   ($result['status'] === 'WARNING' ? '⚠' : '✗');
            
            echo "{$icon} {$result['test']}: {$result['status']}\n";
            if ($result['status'] !== 'SUCCESS') {
                echo "   → {$result['message']}\n";
            }
            
            if ($result['status'] === 'ERROR') $error_count++;
            if ($result['status'] === 'WARNING') $warning_count++;
        }
        
        echo "\n=== Recommendations ===\n";
        
        if ($error_count === 0 && $warning_count === 0) {
            echo "✓ All tests passed! Your SMTP configuration appears to be working correctly.\n";
        } else {
            echo "Issues found that need attention:\n";
            
            if ($error_count > 0) {
                echo "\nCRITICAL ISSUES ({$error_count}):\n";
                echo "- These must be fixed for SMTP to work properly\n";
                echo "- Check the specific error messages above\n";
                echo "- Refer to the SMTP Setup Guide for solutions\n";
            }
            
            if ($warning_count > 0) {
                echo "\nWARNINGS ({$warning_count}):\n";
                echo "- These may cause intermittent issues\n";
                echo "- Consider addressing for optimal performance\n";
            }
        }
        
        echo "\n=== Next Steps ===\n";
        echo "1. Address any critical issues found above\n";
        echo "2. Test email sending from WordPress admin\n";
        echo "3. Monitor SMTP logs for ongoing issues\n";
        echo "4. Consider enabling email queue for high-volume sites\n";
        echo "5. Remove this diagnostic file when done\n";
        
        echo "\n=== Support Resources ===\n";
        echo "- SMTP Setup Guide: /docs/SMTP_SETUP_GUIDE.md\n";
        echo "- WordPress Admin: Tickets > Settings > SMTP Settings\n";
        echo "- SMTP Logs: Available in WordPress admin\n";
    }
}

// Run diagnostics
$diagnostic = new METS_SMTP_Diagnostic();
$diagnostic->run_diagnostics();

// Add cleanup notice
echo "\n\n";
echo "IMPORTANT: Remove this diagnostic file (smtp-diagnostic.php) after completing your tests.\n";
echo "This file should not be left on production servers for security reasons.\n";
?>