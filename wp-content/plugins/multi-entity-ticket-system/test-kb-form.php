<?php
/**
 * Test KB Article Form Processing
 * 
 * This script tests the exact KB form submission flow
 * Access via: http://yoursite.com/wp-content/plugins/multi-entity-ticket-system/test-kb-form.php
 */

// Load WordPress
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Security check - only allow admins
if (!current_user_can('administrator')) {
    wp_die('Access denied. Administrator privileges required.');
}

$article_id = isset($_GET['article_id']) ? intval($_GET['article_id']) : 6;

// Process test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_kb_form'])) {
    
    // Simulate the exact form data that should be submitted
    $_POST['title'] = 'Test Article Update - ' . date('Y-m-d H:i:s');
    $_POST['content'] = 'This is a test update performed at ' . date('Y-m-d H:i:s');
    $_POST['status'] = 'published';
    $_POST['visibility'] = 'customer';
    $_POST['entity_id'] = '';
    $_POST['meta_title'] = 'Test Meta Title';
    $_POST['meta_description'] = 'Test meta description';
    $_POST['article_id'] = $article_id;
    $_POST['mets_kb_nonce'] = wp_create_nonce('mets_kb_save_article');
    
    // Load the admin class and models
    require_once METS_PLUGIN_PATH . 'admin/class-mets-admin.php';
    require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
    require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
    
    try {
        $admin = new METS_Admin('multi-entity-ticket-system', '1.0.0');
        
        // Call the form submission handler directly
        $reflection = new ReflectionClass($admin);
        $method = $reflection->getMethod('handle_kb_article_form_submission');
        $method->setAccessible(true);
        
        echo '<h2>🧪 Testing KB Form Submission...</h2>';
        echo '<div style="background: #f0f8ff; padding: 20px; border-radius: 4px; margin: 20px 0;">';
        
        // Capture any output or redirects
        ob_start();
        
        try {
            $method->invoke($admin);
            echo '<p>✅ Form submission completed without errors!</p>';
            
            // Check if article was actually updated
            $article_model = new METS_KB_Article_Model();
            $updated_article = $article_model->get($article_id);
            
            if ($updated_article) {
                echo '<p>✅ Article was found in database</p>';
                echo '<p><strong>Current Title:</strong> ' . esc_html($updated_article->title) . '</p>';
                echo '<p><strong>Last Updated:</strong> ' . $updated_article->updated_at . '</p>';
                
                if (strpos($updated_article->title, 'Test Article Update') !== false) {
                    echo '<p style="color: green; font-weight: bold;">🎉 SUCCESS! Article was updated successfully!</p>';
                } else {
                    echo '<p style="color: orange; font-weight: bold;">⚠️ Article exists but title wasn\'t updated as expected</p>';
                }
            } else {
                echo '<p style="color: red;">❌ Article not found after update attempt</p>';
            }
            
        } catch (Exception $e) {
            echo '<p style="color: red;">❌ Exception occurred: ' . esc_html($e->getMessage()) . '</p>';
        }
        
        $output = ob_get_clean();
        echo $output;
        
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div style="background: #ffeeee; padding: 20px; border-radius: 4px; margin: 20px 0;">';
        echo '<p style="color: red;">❌ Failed to test form submission: ' . esc_html($e->getMessage()) . '</p>';
        echo '</div>';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>KB Form Test</title>
    <link rel="stylesheet" href="<?php echo admin_url('load-styles.php?c=1&dir=ltr&load=dashicons,admin-bar,common,forms,admin-menu,dashboard,list-tables,edit,revisions,media,themes,about,nav-menus,widgets,site-icon,l10n,buttons,wp-auth-check'); ?>">
    <style>
        body { background: #f1f1f1; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-form { background: #e8f5e8; padding: 20px; border-radius: 4px; margin: 20px 0; border: 1px solid #4caf50; }
        .info-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .button:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>🧪 KB Article Form Processing Test</h1>
        
        <div class="info-box">
            <p><strong>This tool will test the KB article form submission process directly.</strong></p>
            <p>Article ID: <strong><?php echo $article_id; ?></strong></p>
            <p>It will simulate a form submission and check if the article gets updated in the database.</p>
        </div>
        
        <div class="test-form">
            <h2>Test Form Submission</h2>
            <form method="post">
                <input type="hidden" name="test_kb_form" value="1">
                <button type="submit" class="button">🚀 Test KB Form Processing</button>
            </form>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>Manual Testing Links</h3>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=mets-kb-articles&action=edit&article_id=' . $article_id); ?>" target="_blank">
                    Edit Article <?php echo $article_id; ?> (Articles Page)
                </a></li>
                <li><a href="<?php echo admin_url('admin.php?page=mets-kb-add-article&article_id=' . $article_id); ?>" target="_blank">
                    Edit Article <?php echo $article_id; ?> (Add Article Page)
                </a></li>
            </ul>
        </div>
    </div>
</body>
</html>