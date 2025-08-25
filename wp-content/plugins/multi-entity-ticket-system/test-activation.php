<?php
/**
 * Test Plugin Activation - Verify Tables Creation
 * 
 * This script tests that all required tables are created during plugin activation
 * Access via: http://yoursite.com/wp-content/plugins/multi-entity-ticket-system/test-activation.php
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

// Test activation
$message = '';
$message_type = '';

if (isset($_POST['test_activation']) && wp_verify_nonce($_POST['nonce'], 'mets_test_activation')) {
    try {
        require_once plugin_dir_path(__FILE__) . 'includes/class-mets-activator.php';
        METS_Activator::activate();
        $message = 'Plugin activation completed successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error during activation: ' . $e->getMessage();
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
    'mets_ticket_ratings' => 'Ticket Ratings', // Critical for dashboard
    'mets_sla_tracking' => 'SLA Tracking'      // Critical for dashboard
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>METS Plugin Activation Test</title>
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
        .critical { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 METS Plugin Activation Test</h1>
        
        <?php if ($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Database Tables Status</h2>
            
            <?php if (empty($missing_tables)): ?>
                <div class="success">
                    ✅ All required database tables exist! Plugin is properly activated.
                </div>
            <?php else: ?>
                <div class="error">
                    ❌ Missing <?php echo count($missing_tables); ?> required table(s). Plugin needs to be activated.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Critical For</th>
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
            <div class="critical">
                <h3>⚠️ Critical Tables Missing</h3>
                <p>The following tables are required for dashboard functionality:</p>
                <ul>
                    <?php foreach ($missing_tables as $table): ?>
                        <?php if (in_array($table, ['mets_ticket_ratings', 'mets_sla_tracking'])): ?>
                            <li><code><?php echo esc_html($wpdb->prefix . $table); ?></code> - <strong>Critical for Dashboard</strong></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>Test Plugin Activation</h2>
            <p>Click below to run the plugin activation process and create missing tables:</p>
            
            <form method="post">
                <?php wp_nonce_field('mets_test_activation', 'nonce'); ?>
                <button type="submit" name="test_activation" class="button">
                    🔧 Run Plugin Activation
                </button>
            </form>
        </div>

        <div class="section">
            <h2>Next Steps</h2>
            <?php if (empty($missing_tables)): ?>
                <div class="success">
                    <p><strong>✅ Plugin is ready!</strong></p>
                    <ul>
                        <li>All required tables exist</li>
                        <li>Dashboard should work properly</li>
                        <li>You can now test the Team Performance Dashboard</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="warning">
                    <p><strong>🔧 Action Required:</strong></p>
                    <ol>
                        <li>Click "Run Plugin Activation" above</li>
                        <li>Refresh this page to verify tables were created</li>
                        <li>Test the dashboard functionality</li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>