<?php
/**
 * METS Database Tables Debug Tool
 * 
 * This script helps diagnose and fix missing database tables that cause
 * the dashboard AJAX requests to fail.
 * 
 * Access via: http://yoursite.com/wp-content/plugins/multi-entity-ticket-system/debug-tables.php
 */

// Load WordPress
$wp_load_path = '';
$current_dir = dirname(__FILE__);

// Look for wp-config.php by traversing up directories
for ($i = 0; $i < 5; $i++) {
    $wp_config_path = $current_dir . '/wp-config.php';
    if (file_exists($wp_config_path)) {
        $wp_load_path = $current_dir . '/wp-load.php';
        break;
    }
    $current_dir = dirname($current_dir);
}

if (!$wp_load_path || !file_exists($wp_load_path)) {
    die('Could not locate WordPress. Please access this script from within your WordPress installation.');
}

require_once($wp_load_path);

// Security check - only allow admins
if (!current_user_can('administrator')) {
    wp_die('Access denied. Administrator privileges required.');
}

// Load plugin classes
require_once plugin_dir_path(__FILE__) . 'database/class-mets-tables.php';

// Process form submission
$message = '';
$message_type = '';

if (isset($_POST['create_tables']) && wp_verify_nonce($_POST['nonce'], 'mets_debug_tables')) {
    try {
        $tables = new METS_Tables();
        $tables->create_all_tables();
        $message = 'Database tables created successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error creating tables: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Check which tables exist
global $wpdb;

$required_tables = array(
    'mets_entities' => 'Entities',
    'mets_tickets' => 'Tickets',
    'mets_ticket_replies' => 'Ticket Replies',
    'mets_attachments' => 'Attachments',
    'mets_user_entities' => 'User Entities',
    'mets_sla_rules' => 'SLA Rules',
    'mets_business_hours' => 'Business Hours',
    'mets_email_queue' => 'Email Queue',
    'mets_automation_rules' => 'Automation Rules',
    'mets_response_metrics' => 'Response Metrics',
    'mets_workflow_rules' => 'Workflow Rules',
    'mets_ticket_ratings' => 'Ticket Ratings', // Missing table causing dashboard issues
    'mets_sla_tracking' => 'SLA Tracking'      // Missing table causing dashboard issues
);

$table_status = array();
$missing_tables = array();

foreach ($required_tables as $table_suffix => $table_label) {
    $table_name = $wpdb->prefix . $table_suffix;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    $table_status[$table_suffix] = array(
        'name' => $table_name,
        'label' => $table_label,
        'exists' => $exists
    );
    
    if (!$exists) {
        $missing_tables[] = $table_suffix;
    }
}

// Check AJAX endpoint
$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('mets_team_dashboard');

?>
<!DOCTYPE html>
<html>
<head>
    <title>METS Database Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ffeaa7; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-missing { color: #dc3545; font-weight: bold; }
        .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .button:hover { background: #005a87; }
        .button-danger { background: #dc3545; }
        .button-danger:hover { background: #c82333; }
        .info-box { background: #e7f3ff; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 14px; }
        .section { margin: 30px 0; }
        .ajax-test { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 METS Database Debug Tool</h1>
        
        <?php if ($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Database Tables Status</h2>
            
            <?php if (empty($missing_tables)): ?>
                <div class="success">
                    ✅ All required database tables exist!
                </div>
            <?php else: ?>
                <div class="error">
                    ❌ Missing <?php echo count($missing_tables); ?> required table(s). This is likely causing the dashboard AJAX failures.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Required For</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_status as $suffix => $info): ?>
                        <tr>
                            <td><code><?php echo esc_html($info['name']); ?></code></td>
                            <td><?php echo esc_html($info['label']); ?></td>
                            <td>
                                <?php if ($info['exists']): ?>
                                    <span class="status-ok">✅ EXISTS</span>
                                <?php else: ?>
                                    <span class="status-missing">❌ MISSING</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($suffix === 'mets_ticket_ratings'): ?>
                                    <strong>Dashboard Satisfaction Metrics</strong>
                                <?php elseif ($suffix === 'mets_sla_tracking'): ?>
                                    <strong>Dashboard SLA Performance</strong>
                                <?php else: ?>
                                    Core Functionality
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($missing_tables)): ?>
            <div class="section">
                <h2>⚠️ Critical Issues Detected</h2>
                <div class="warning">
                    <p><strong>The following tables are missing and causing dashboard failures:</strong></p>
                    <ul>
                        <?php foreach ($missing_tables as $table): ?>
                            <li><code><?php echo esc_html($wpdb->prefix . $table); ?></code> - <?php echo esc_html($required_tables[$table]); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p>These missing tables cause the AJAX requests to fail with database errors, resulting in the "Failed to refresh dashboard data" message.</p>
                </div>
                
                <form method="post" onsubmit="return confirm('This will create the missing database tables. Continue?');">
                    <?php wp_nonce_field('mets_debug_tables', 'nonce'); ?>
                    <button type="submit" name="create_tables" class="button">
                        🔧 Create Missing Tables
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>AJAX Configuration Test</h2>
            <div class="ajax-test">
                <p><strong>AJAX URL:</strong> <code><?php echo esc_html($ajax_url); ?></code></p>
                <p><strong>Dashboard Nonce:</strong> <code><?php echo esc_html($nonce); ?></code></p>
                <p><strong>Current User Can Manage Tickets:</strong> 
                    <?php if (current_user_can('manage_tickets') || current_user_can('manage_options')): ?>
                        <span class="status-ok">✅ YES</span>
                    <?php else: ?>
                        <span class="status-missing">❌ NO</span>
                    <?php endif; ?>
                </p>
                
                <button onclick="testAjax()" class="button">Test AJAX Request</button>
                <div id="ajax-result" style="margin-top: 10px;"></div>
            </div>
        </div>

        <div class="section">
            <h2>System Information</h2>
            <table>
                <tr>
                    <td><strong>WordPress Version:</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>MySQL Version:</strong></td>
                    <td><?php echo $wpdb->db_version(); ?></td>
                </tr>
                <tr>
                    <td><strong>Database Prefix:</strong></td>
                    <td><code><?php echo esc_html($wpdb->prefix); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Plugin Path:</strong></td>
                    <td><code><?php echo esc_html(plugin_dir_path(__FILE__)); ?></code></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Next Steps</h2>
            <div class="info-box">
                <?php if (empty($missing_tables)): ?>
                    <p><strong>✅ Database is ready!</strong> If you're still experiencing issues:</p>
                    <ol>
                        <li>Test the AJAX request using the button above</li>
                        <li>Check browser console for JavaScript errors</li>
                        <li>Enable WordPress debug logging (<code>WP_DEBUG_LOG = true</code>)</li>
                        <li>Check if user has proper capabilities to access the dashboard</li>
                    </ol>
                <?php else: ?>
                    <p><strong>🔧 Action Required:</strong></p>
                    <ol>
                        <li><strong>Click "Create Missing Tables" above</strong> to fix the database issues</li>
                        <li>After creating tables, refresh the dashboard page</li>
                        <li>If issues persist, check WordPress error logs</li>
                        <li>Consider deactivating and reactivating the plugin to ensure proper initialization</li>
                    </ol>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function testAjax() {
            const resultDiv = document.getElementById('ajax-result');
            resultDiv.innerHTML = '<em>Testing AJAX request...</em>';
            
            const data = new FormData();
            data.append('action', 'mets_refresh_dashboard');
            data.append('nonce', '<?php echo $nonce; ?>');
            data.append('period', 'today');
            
            fetch('<?php echo $ajax_url; ?>', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="success">✅ AJAX request successful! Dashboard should work properly.</div>';
                } else {
                    resultDiv.innerHTML = '<div class="error">❌ AJAX request failed: ' + (data.data || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error">❌ AJAX request failed: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html>