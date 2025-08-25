<?php
/**
 * Debug KB Article Editing Issues
 * 
 * This script helps diagnose why KB article updates aren't persisting
 * Access via: http://yoursite.com/wp-content/plugins/multi-entity-ticket-system/debug-kb-edit.php
 */

// Load WordPress
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Security check - only allow admins
if (!current_user_can('administrator')) {
    wp_die('Access denied. Administrator privileges required.');
}

// Get article ID from URL or default to 6
$article_id = isset($_GET['article_id']) ? intval($_GET['article_id']) : 6;

// Test update if form submitted
$test_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_update'])) {
    global $wpdb;
    
    $test_title = 'Test Update - ' . date('Y-m-d H:i:s');
    $test_content = 'Test content updated at ' . date('Y-m-d H:i:s');
    
    // Direct database update
    $result = $wpdb->update(
        $wpdb->prefix . 'mets_kb_articles',
        array(
            'title' => $test_title,
            'content' => $test_content,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $article_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        $test_result = '<div class="notice notice-success"><p>✅ Direct database update successful! Rows affected: ' . $result . '</p></div>';
    } else {
        $test_result = '<div class="notice notice-error"><p>❌ Database update failed! Error: ' . $wpdb->last_error . '</p></div>';
    }
}

// Check various configurations
global $wpdb;

// Check if enhanced mode is enabled
$enhanced_mode = get_option('mets_kb_enhanced_mode', 'not_set');

// Check if article exists
$article = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}mets_kb_articles WHERE id = %d",
    $article_id
));

// Check if enhanced KB files exist
$integration_file = METS_PLUGIN_PATH . 'admin/kb/class-mets-kb-admin-integration.php';
$manager_file = METS_PLUGIN_PATH . 'admin/kb/class-mets-kb-article-manager.php';
$model_file = METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';

// Check if classes are loaded
$integration_loaded = class_exists('METS_KB_Admin_Integration');
$manager_loaded = class_exists('METS_KB_Article_Manager');
$model_loaded = class_exists('METS_KB_Article_Model');

// Check registered actions
$admin_post_action = has_action('admin_post_mets_kb_save_article');
$admin_init_action = has_action('admin_init', array('METS_Admin', 'handle_kb_article_form_submission'));

// Check filters
$display_filter = has_filter('mets_kb_display_article_form');

?>
<!DOCTYPE html>
<html>
<head>
    <title>KB Article Edit Debug</title>
    <link rel="stylesheet" href="<?php echo admin_url('load-styles.php?c=1&dir=ltr&load=dashicons,admin-bar,common,forms,admin-menu,dashboard,list-tables,edit,revisions,media,themes,about,nav-menus,widgets,site-icon,l10n,buttons,wp-auth-check'); ?>">
    <style>
        body { background: #f1f1f1; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-box { background: #f8f9fa; border: 1px solid #e9ecef; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .test-form { background: #e3f2fd; padding: 20px; border-radius: 4px; margin: 20px 0; }
        .debug-section { margin: 30px 0; }
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>🔍 KB Article Edit Debug Tool</h1>
        
        <?php echo $test_result; ?>
        
        <div class="status-box">
            <h2>Article Information</h2>
            <table>
                <tr>
                    <td><strong>Article ID:</strong></td>
                    <td><?php echo $article_id; ?></td>
                </tr>
                <tr>
                    <td><strong>Article Exists:</strong></td>
                    <td>
                        <?php if ($article): ?>
                            <span class="status-ok">✅ YES</span>
                        <?php else: ?>
                            <span class="status-error">❌ NO</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($article): ?>
                <tr>
                    <td><strong>Current Title:</strong></td>
                    <td><?php echo esc_html($article->title); ?></td>
                </tr>
                <tr>
                    <td><strong>Last Updated:</strong></td>
                    <td><?php echo $article->updated_at; ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="status-box">
            <h2>System Configuration</h2>
            <table>
                <tr>
                    <td><strong>Enhanced KB Mode:</strong></td>
                    <td>
                        <?php if ($enhanced_mode === true || $enhanced_mode === '1'): ?>
                            <span class="status-ok">✅ ENABLED</span>
                        <?php elseif ($enhanced_mode === 'not_set'): ?>
                            <span class="status-warning">⚠️ NOT SET (defaults to true)</span>
                        <?php else: ?>
                            <span class="status-error">❌ DISABLED</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Integration File:</strong></td>
                    <td>
                        <?php if (file_exists($integration_file)): ?>
                            <span class="status-ok">✅ EXISTS</span>
                        <?php else: ?>
                            <span class="status-error">❌ MISSING</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Manager File:</strong></td>
                    <td>
                        <?php if (file_exists($manager_file)): ?>
                            <span class="status-ok">✅ EXISTS</span>
                        <?php else: ?>
                            <span class="status-error">❌ MISSING</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Model File:</strong></td>
                    <td>
                        <?php if (file_exists($model_file)): ?>
                            <span class="status-ok">✅ EXISTS</span>
                        <?php else: ?>
                            <span class="status-error">❌ MISSING</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="status-box">
            <h2>Class Loading Status</h2>
            <table>
                <tr>
                    <td><strong>METS_KB_Admin_Integration:</strong></td>
                    <td>
                        <?php if ($integration_loaded): ?>
                            <span class="status-ok">✅ LOADED</span>
                        <?php else: ?>
                            <span class="status-error">❌ NOT LOADED</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>METS_KB_Article_Manager:</strong></td>
                    <td>
                        <?php if ($manager_loaded): ?>
                            <span class="status-ok">✅ LOADED</span>
                        <?php else: ?>
                            <span class="status-error">❌ NOT LOADED</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>METS_KB_Article_Model:</strong></td>
                    <td>
                        <?php if ($model_loaded): ?>
                            <span class="status-ok">✅ LOADED</span>
                        <?php else: ?>
                            <span class="status-error">❌ NOT LOADED</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="status-box">
            <h2>Hook Registration</h2>
            <table>
                <tr>
                    <td><strong>admin_post_mets_kb_save_article:</strong></td>
                    <td>
                        <?php if ($admin_post_action): ?>
                            <span class="status-ok">✅ REGISTERED (Priority: <?php echo $admin_post_action; ?>)</span>
                        <?php else: ?>
                            <span class="status-error">❌ NOT REGISTERED</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>admin_init handler:</strong></td>
                    <td>
                        <?php if ($admin_init_action): ?>
                            <span class="status-ok">✅ REGISTERED</span>
                        <?php else: ?>
                            <span class="status-error">❌ NOT REGISTERED</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>mets_kb_display_article_form filter:</strong></td>
                    <td>
                        <?php if ($display_filter): ?>
                            <span class="status-ok">✅ REGISTERED (Priority: <?php echo $display_filter; ?>)</span>
                        <?php else: ?>
                            <span class="status-error">❌ NOT REGISTERED</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="test-form">
            <h2>Test Direct Database Update</h2>
            <p>This will directly update the article in the database to test if updates work at the database level.</p>
            <form method="post">
                <input type="hidden" name="test_update" value="1">
                <button type="submit" class="button button-primary">Test Database Update</button>
            </form>
        </div>

        <div class="debug-section">
            <h2>Edit Form URLs</h2>
            <p>Test these different edit URLs to see which works:</p>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=mets-kb-articles&action=edit&article_id=' . $article_id); ?>" target="_blank">
                    Standard Edit URL (KB Articles Page)
                </a></li>
                <li><a href="<?php echo admin_url('admin.php?page=mets-kb-add-article&article_id=' . $article_id); ?>" target="_blank">
                    Add Article Page with ID (Alternative)
                </a></li>
            </ul>
        </div>

        <div class="debug-section">
            <h2>Diagnostic Summary</h2>
            <?php
            $issues = array();
            
            if (!$article) {
                $issues[] = "Article ID $article_id does not exist in database";
            }
            
            if ($enhanced_mode === false || $enhanced_mode === '0') {
                $issues[] = "Enhanced KB mode is disabled - this may cause form handling issues";
            }
            
            if (!$integration_loaded) {
                $issues[] = "KB Admin Integration class not loaded - enhanced forms won't work";
            }
            
            if (!$admin_post_action) {
                $issues[] = "admin_post handler not registered - enhanced form submissions will fail";
            }
            
            if (!$display_filter) {
                $issues[] = "Display filter not registered - edit form won't use enhanced version";
            }
            
            if (empty($issues)): ?>
                <div class="notice notice-success">
                    <p><strong>✅ All systems operational!</strong> The KB editing system should be working correctly.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-error">
                    <p><strong>❌ Issues detected:</strong></p>
                    <ul>
                        <?php foreach ($issues as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="debug-section">
            <h2>Recommendations</h2>
            <ol>
                <li>Ensure enhanced KB mode is enabled</li>
                <li>Check that all KB files are present in the plugin directory</li>
                <li>Verify the article exists in the database</li>
                <li>Test both edit URLs to see which works</li>
                <li>Check browser console for JavaScript errors</li>
                <li>Enable WP_DEBUG to see any PHP errors</li>
            </ol>
        </div>
    </div>
</body>
</html>