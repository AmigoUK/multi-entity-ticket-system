<?php
/**
 * Schema Improvements Testing Tool
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user has proper permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Load the schema manager and data cleanup
$schema_manager_path = plugin_dir_path(__FILE__) . 'database/class-mets-schema-manager.php';
$data_cleanup_path = plugin_dir_path(__FILE__) . 'database/class-mets-data-cleanup.php';

$schema_manager = null;
$data_cleanup = null;

if (file_exists($schema_manager_path)) {
    require_once($schema_manager_path);
    $schema_manager = new METS_Schema_Manager();
}

if (file_exists($data_cleanup_path)) {
    require_once($data_cleanup_path);
    $data_cleanup = new METS_Data_Cleanup();
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'status';
$results = array();

// Handle actions
if ($schema_manager) {
    switch ($action) {
        case 'test':
            $results = $schema_manager->test_schema_improvements();
            break;
        case 'apply':
            $test_mode = isset($_GET['test_mode']) ? (bool)$_GET['test_mode'] : false;
            $results = $schema_manager->apply_all_improvements($test_mode);
            break;
        case 'report':
            $results = $schema_manager->generate_comprehensive_report();
            break;
        case 'cleanup':
            if ($data_cleanup) {
                $dry_run = isset($_GET['dry_run']) ? (bool)$_GET['dry_run'] : true;
                $results = $data_cleanup->run_comprehensive_cleanup($dry_run);
            } else {
                $results = array('error' => 'Data cleanup not available');
            }
            break;
        case 'cleanup_recommendations':
            if ($data_cleanup) {
                $results = $data_cleanup->get_cleanup_recommendations();
            } else {
                $results = array('error' => 'Data cleanup not available');
            }
            break;
        case 'rollback':
            if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
                $results = $schema_manager->emergency_rollback();
            }
            break;
        default:
            $results = $schema_manager->get_status();
    }
} else {
    // Fallback results if schema manager is not available
    $results = array(
        'error' => 'Schema manager not available',
        'validator_loaded' => false,
        'foreign_keys_loaded' => false,
        'data_sync_loaded' => false,
        'auto_sync_scheduled' => false,
        'last_operation_logs' => array()
    );
}

?>
<div class="wrap">
    <style>
        .mets-schema-tool {
            max-width: 1200px;
            margin: 0;
            padding: 20px 0;
        }
        .header {
            background: #0073aa;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav a {
            padding: 10px 15px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .nav a:hover, .nav a.active {
            background: #005a87;
        }
        .panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #46b450; font-weight: bold; }
        .error { color: #dc3232; font-weight: bold; }
        .warning { color: #f0b849; font-weight: bold; }
        .info { color: #0073aa; font-weight: bold; }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
            border-left: 4px solid #0073aa;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            margin: 5px 0;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge.success { background: #d4edda; color: #155724; }
        .badge.error { background: #f8d7da; color: #721c24; }
        .badge.warning { background: #fff3cd; color: #856404; }
        .badge.info { background: #cce5ff; color: #004085; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin: 5px;
        }
        .button:hover { background: #005a87; }
        .button.danger { background: #dc3232; }
        .button.danger:hover { background: #a02622; }
        .button.warning { background: #f0b849; }
        .button.warning:hover { background: #d39e00; }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .alert.info { background: #cce5ff; color: #004085; border: 1px solid #b8d4fd; }
    </style>
    
    <div class="mets-schema-tool">
        <h1>🛠️ METS Database Schema Improvements Tool</h1>
        <p>Manage database constraints, foreign keys, and data synchronization</p>
        
        <?php if (!$schema_manager): ?>
            <div class="notice notice-error">
                <p><strong>Warning:</strong> Schema manager class is not available. Please ensure the plugin is properly installed and activated.</p>
            </div>
        <?php endif; ?>

    <div class="nav">
        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=status'); ?>" class="<?php echo $action === 'status' ? 'active' : ''; ?>">Status</a>
        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=test'); ?>" class="<?php echo $action === 'test' ? 'active' : ''; ?>">Test Schema</a>
        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=cleanup&dry_run=1'); ?>" class="<?php echo $action === 'cleanup' && isset($_GET['dry_run']) ? 'active' : ''; ?>">🧹 Preview Cleanup</a>
        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=cleanup'); ?>" class="<?php echo $action === 'cleanup' && !isset($_GET['dry_run']) ? 'active' : ''; ?>">🔧 Run Cleanup</a>
        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=apply&test_mode=1'); ?>" class="<?php echo $action === 'apply' && isset($_GET['test_mode']) ? 'active' : ''; ?>">Test Apply</a>
        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=apply'); ?>" class="<?php echo $action === 'apply' && !isset($_GET['test_mode']) ? 'active' : ''; ?>">Apply Changes</a>
        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=report'); ?>" class="<?php echo $action === 'report' ? 'active' : ''; ?>">Full Report</a>
    </div>

    <?php if ($action === 'status'): ?>
        <div class="panel">
            <h2>📊 Schema Manager Status</h2>
            
            <div class="grid">
                <div>
                    <h3>Component Status</h3>
                    <div class="status-item">
                        <span>Schema Validator</span>
                        <span class="badge <?php echo $results['validator_loaded'] ? 'success' : 'error'; ?>">
                            <?php echo $results['validator_loaded'] ? 'Loaded' : 'Error'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>Foreign Keys Manager</span>
                        <span class="badge <?php echo $results['foreign_keys_loaded'] ? 'success' : 'error'; ?>">
                            <?php echo $results['foreign_keys_loaded'] ? 'Loaded' : 'Error'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>Data Sync Manager</span>
                        <span class="badge <?php echo $results['data_sync_loaded'] ? 'success' : 'error'; ?>">
                            <?php echo $results['data_sync_loaded'] ? 'Loaded' : 'Error'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>Auto Sync Scheduled</span>
                        <span class="badge <?php echo $results['auto_sync_scheduled'] ? 'success' : 'warning'; ?>">
                            <?php echo $results['auto_sync_scheduled'] ? 'Yes' : 'No'; ?>
                        </span>
                    </div>
                </div>

                <div>
                    <h3>Recent Operations</h3>
                    <?php if (!empty($results['last_operation_logs'])): ?>
                        <?php foreach (array_slice($results['last_operation_logs'], 0, 5) as $log): ?>
                            <div class="status-item">
                                <span><?php echo esc_html($log['timestamp']); ?></span>
                                <span class="badge <?php echo $log['success'] ? 'success' : 'error'; ?>">
                                    <?php echo $log['success'] ? 'Success' : 'Failed'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="info">No operations recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert info">
                <strong>Next Steps:</strong>
                <ul>
                    <li>Run <strong>Test Schema</strong> to validate all improvements work correctly</li>
                    <li>Use <strong>Test Apply</strong> to simulate applying changes without making permanent modifications</li>
                    <li>Use <strong>Apply Changes</strong> to implement all schema improvements</li>
                    <li>Generate a <strong>Full Report</strong> to see comprehensive database status</li>
                </ul>
            </div>
        </div>

    <?php elseif ($action === 'test'): ?>
        <div class="panel">
            <h2>🧪 Schema Testing Results</h2>
            
            <div class="alert <?php echo $results['overall_result'] === 'PASSED' ? 'success' : 'error'; ?>">
                <strong>Overall Result: <?php echo esc_html($results['overall_result']); ?></strong>
                <br>Test completed at: <?php echo esc_html($results['timestamp']); ?>
            </div>

            <?php foreach ($results['tests'] as $category => $tests): ?>
                <h3><?php echo ucwords(str_replace('_', ' ', $category)); ?> Tests</h3>
                
                <?php if (is_array($tests) && isset($tests[0]['description'])): ?>
                    <?php foreach ($tests as $test): ?>
                        <div class="status-item">
                            <div>
                                <strong><?php echo esc_html($test['description']); ?></strong>
                                <?php if (isset($test['message'])): ?>
                                    <br><small><?php echo esc_html($test['message']); ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?php echo $test['passed'] ? 'success' : 'error'; ?>">
                                <?php echo $test['passed'] ? 'PASS' : 'FAIL'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="code"><?php echo esc_html(json_encode($tests, JSON_PRETTY_PRINT)); ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    <?php elseif ($action === 'apply'): ?>
        <div class="panel">
            <h2>⚙️ Schema Application Results</h2>
            
            <div class="alert <?php echo $results['success'] ? 'success' : 'error'; ?>">
                <strong>Operation <?php echo $results['success'] ? 'Completed Successfully' : 'Failed'; ?></strong>
                <br>Mode: <?php echo $results['test_mode'] ? 'Test Mode (No Changes Made)' : 'Live Application'; ?>
                <br>Timestamp: <?php echo esc_html($results['timestamp']); ?>
                <?php if (!$results['success']): ?>
                    <br><strong>Error:</strong> <?php echo esc_html($results['error']); ?>
                <?php endif; ?>
            </div>

            <?php if (isset($results['operations'])): ?>
                <?php foreach ($results['operations'] as $operation => $result): ?>
                    <h3><?php echo ucwords(str_replace('_', ' ', $operation)); ?></h3>
                    <div class="code"><?php echo esc_html(json_encode($result, JSON_PRETTY_PRINT)); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'cleanup'): ?>
        <div class="panel">
            <h2><?php echo isset($_GET['dry_run']) ? '🧹 Data Cleanup Preview' : '🔧 Data Cleanup Results'; ?></h2>
            
            <?php if (isset($results['error'])): ?>
                <div class="alert error">
                    <strong>Error:</strong> <?php echo esc_html($results['error']); ?>
                </div>
            <?php else: ?>
                <div class="alert <?php echo $results['success'] ? 'success' : 'error'; ?>">
                    <strong><?php echo $results['success'] ? 'Cleanup Completed Successfully' : 'Cleanup Failed'; ?></strong>
                    <br>Mode: <?php echo $results['dry_run'] ? 'Preview Only (No Changes Made)' : 'Live Cleanup Applied'; ?>
                    <br>Timestamp: <?php echo esc_html($results['timestamp']); ?>
                    <?php if (!$results['success']): ?>
                        <br><strong>Error:</strong> <?php echo esc_html($results['error']); ?>
                    <?php endif; ?>
                </div>

                <?php if (isset($results['summary'])): ?>
                    <div class="grid">
                        <div>
                            <h3>📊 Cleanup Summary</h3>
                            <div class="status-item">
                                <span>Emails Fixed</span>
                                <span class="badge <?php echo $results['summary']['emails_fixed'] > 0 ? 'success' : 'info'; ?>">
                                    <?php echo $results['summary']['emails_fixed']; ?>
                                </span>
                            </div>
                            <div class="status-item">
                                <span>Orphaned Records Removed</span>
                                <span class="badge <?php echo $results['summary']['orphaned_removed'] > 0 ? 'success' : 'info'; ?>">
                                    <?php echo $results['summary']['orphaned_removed']; ?>
                                </span>
                            </div>
                            <div class="status-item">
                                <span>Relationships Repaired</span>
                                <span class="badge <?php echo $results['summary']['relationships_repaired'] > 0 ? 'success' : 'info'; ?>">
                                    <?php echo $results['summary']['relationships_repaired']; ?>
                                </span>
                            </div>
                            <div class="status-item">
                                <span>Manual Review Needed</span>
                                <span class="badge <?php echo $results['summary']['manual_review_needed'] > 0 ? 'warning' : 'success'; ?>">
                                    <?php echo $results['summary']['manual_review_needed']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($results['summary']['recommendations'])): ?>
                            <div>
                                <h3>💡 Recommendations</h3>
                                <?php foreach ($results['summary']['recommendations'] as $recommendation): ?>
                                    <div class="alert info">
                                        <?php echo esc_html($recommendation); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($results['operations'])): ?>
                    <h3>🔍 Detailed Results</h3>
                    
                    <?php if (isset($results['operations']['email_cleanup'])): ?>
                        <h4>📧 Email Cleanup</h4>
                        <?php $email_cleanup = $results['operations']['email_cleanup']; ?>
                        <p><strong>Found Invalid:</strong> <?php echo $email_cleanup['found_invalid']; ?></p>
                        
                        <?php if (!empty($email_cleanup['fixes_applied'])): ?>
                            <h5>✅ Automatic Fixes Applied:</h5>
                            <table>
                                <thead>
                                    <tr><th>Ticket</th><th>Original Email</th><th>Fixed Email</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($email_cleanup['fixes_applied'] as $fix): ?>
                                        <tr>
                                            <td><?php echo esc_html($fix['ticket_number']); ?></td>
                                            <td><?php echo esc_html($fix['original']); ?></td>
                                            <td><?php echo esc_html($fix['fixed']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <?php if (!empty($email_cleanup['manual_review_needed'])): ?>
                            <h5>⚠️ Manual Review Required:</h5>
                            <table>
                                <thead>
                                    <tr><th>Ticket</th><th>Customer</th><th>Invalid Email</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($email_cleanup['manual_review_needed'] as $manual): ?>
                                        <tr>
                                            <td><?php echo esc_html($manual['ticket_number']); ?></td>
                                            <td><?php echo esc_html($manual['customer_name']); ?></td>
                                            <td><?php echo esc_html($manual['invalid_email']); ?></td>
                                            <td><?php echo esc_html($manual['suggested_action']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (isset($results['operations']['orphaned_cleanup'])): ?>
                        <h4>🗑️ Orphaned Records Cleanup</h4>
                        <?php $orphaned = $results['operations']['orphaned_cleanup']; ?>
                        <div class="grid">
                            <div class="status-item">
                                <span>Orphaned Replies</span>
                                <span class="badge info"><?php echo $orphaned['orphaned_replies']; ?></span>
                            </div>
                            <div class="status-item">
                                <span>Orphaned Attachments</span>
                                <span class="badge info"><?php echo $orphaned['orphaned_attachments']; ?></span>
                            </div>
                            <div class="status-item">
                                <span>Orphaned Ratings</span>
                                <span class="badge info"><?php echo $orphaned['orphaned_ratings']; ?></span>
                            </div>
                            <div class="status-item">
                                <span>Orphaned SLA Tracking</span>
                                <span class="badge info"><?php echo $orphaned['orphaned_sla_tracking']; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($results['dry_run']): ?>
                    <div class="alert warning">
                        <strong>Preview Mode:</strong> No actual changes were made to your database. 
                        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=cleanup'); ?>" class="button">🔧 Run Actual Cleanup</a>
                    </div>
                <?php else: ?>
                    <div class="alert success">
                        <strong>Cleanup Complete!</strong> Your database has been cleaned and is ready for schema improvements.
                        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=test'); ?>" class="button">🧪 Test Schema</a>
                        <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=apply'); ?>" class="button">⚙️ Apply Changes</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'report'): ?>
        <div class="panel">
            <h2>📋 Comprehensive Database Report</h2>
            
            <div class="grid">
                <div>
                    <h3>Database Information</h3>
                    <table>
                        <?php foreach ($results['database_info'] as $key => $value): ?>
                            <tr>
                                <td><?php echo ucwords(str_replace('_', ' ', $key)); ?></td>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div>
                    <h3>Data Integrity Status</h3>
                    <?php foreach ($results['data_integrity'] as $check => $status): ?>
                        <div class="status-item">
                            <span><?php echo ucwords(str_replace('_', ' ', $check)); ?></span>
                            <span class="badge <?php echo $status['valid'] ? 'success' : 'error'; ?>">
                                <?php echo $status['valid'] ? 'Valid' : $status['invalid_count'] . ' Issues'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h3>Table Status</h3>
            <table>
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Rows</th>
                        <th>Data Size</th>
                        <th>Index Size</th>
                        <th>Engine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['table_status'] as $table => $status): ?>
                        <tr>
                            <td><?php echo esc_html($table); ?></td>
                            <td><?php echo number_format($status['rows']); ?></td>
                            <td><?php echo esc_html($status['data_length']); ?></td>
                            <td><?php echo esc_html($status['index_length']); ?></td>
                            <td><?php echo esc_html($status['engine']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($results['recommendations'])): ?>
                <h3>Recommendations</h3>
                <?php foreach ($results['recommendations'] as $rec): ?>
                    <div class="alert <?php echo $rec['priority'] === 'high' ? 'error' : ($rec['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                        <strong><?php echo ucfirst($rec['priority']); ?> Priority:</strong>
                        <?php echo esc_html($rec['issue']); ?>
                        <br><strong>Action:</strong> <?php echo esc_html($rec['action']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'rollback'): ?>
        <?php if (!isset($_GET['confirm'])): ?>
            <div class="panel">
                <h2>⚠️ Emergency Rollback</h2>
                <div class="alert warning">
                    <strong>Warning:</strong> This will remove all custom database constraints and scheduled synchronization tasks. 
                    Foreign key relationships will be preserved to maintain data integrity.
                </div>
                <p>Are you sure you want to proceed with the rollback?</p>
                <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=rollback&confirm=yes'); ?>" class="button danger">Yes, Rollback Changes</a>
                <a href="<?php echo admin_url('tools.php?page=mets-schema-testing&action=status'); ?>" class="button">Cancel</a>
            </div>
        <?php else: ?>
            <div class="panel">
                <h2>🔄 Rollback Results</h2>
                <div class="alert <?php echo $results['success'] ? 'success' : 'error'; ?>">
                    <strong><?php echo $results['success'] ? 'Rollback Completed' : 'Rollback Failed'; ?></strong>
                    <?php if (isset($results['message'])): ?>
                        <br><?php echo esc_html($results['message']); ?>
                    <?php endif; ?>
                    <?php if (!$results['success']): ?>
                        <br><strong>Error:</strong> <?php echo esc_html($results['error']); ?>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($results['operations'])): ?>
                    <h3>Operations Performed</h3>
                    <div class="code"><?php echo esc_html(json_encode($results['operations'], JSON_PRETTY_PRINT)); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

        <div class="panel" style="text-align: center; color: #666;">
            <p>METS Database Schema Improvements Tool v1.0.0</p>
            <p>Always backup your database before applying schema changes in production.</p>
        </div>
    </div>
</div>