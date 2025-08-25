<?php
/**
 * AI Chat Widget Settings Admin Page
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['mets_ai_chat_widget_nonce'], 'mets_ai_chat_widget_settings')) {
    $settings = array(
        'enabled' => isset($_POST['enabled']) ? 1 : 0,
        'position' => sanitize_text_field($_POST['position']),
        'theme' => sanitize_text_field($_POST['theme']),
        'welcome_message' => sanitize_textarea_field($_POST['welcome_message']),
        'placeholder_text' => sanitize_text_field($_POST['placeholder_text']),
        'button_text' => sanitize_text_field($_POST['button_text']),
        'create_ticket_prompt' => isset($_POST['create_ticket_prompt']) ? 1 : 0,
        'max_messages' => intval($_POST['max_messages']),
        'session_timeout' => intval($_POST['session_timeout']),
        'allowed_pages' => array_map('sanitize_text_field', (array)$_POST['allowed_pages']),
        'excluded_pages' => array_filter(array_map('sanitize_text_field', explode("\n", $_POST['excluded_pages']))),
        'offline_message' => sanitize_textarea_field($_POST['offline_message']),
        'auto_open' => isset($_POST['auto_open']) ? 1 : 0,
        'auto_open_delay' => intval($_POST['auto_open_delay']),
        'show_typing_indicator' => isset($_POST['show_typing_indicator']) ? 1 : 0,
        'default_entity' => intval($_POST['default_entity']),
        'enable_file_upload' => isset($_POST['enable_file_upload']) ? 1 : 0,
        'require_contact_info' => isset($_POST['require_contact_info']) ? 1 : 0,
        'force_language' => sanitize_text_field($_POST['force_language']),
        'ignore_language_detection' => isset($_POST['ignore_language_detection']) ? 1 : 0
    );
    
    update_option('mets_ai_chat_widget_settings', $settings);
    
    // Save the default entity setting separately
    if (isset($settings['default_entity']) && $settings['default_entity'] > 0) {
        update_option('mets_ai_chat_default_entity', $settings['default_entity']);
    }
    
    echo '<div class="notice notice-success"><p>' . __('AI Chat Widget settings saved successfully!', 'multi-entity-ticket-system') . '</p></div>';
}

// Get current settings
$default_settings = array(
    'enabled' => true,
    'position' => 'bottom-right',
    'theme' => 'blue',
    'welcome_message' => __('Hi! How can I help you today?', 'multi-entity-ticket-system'),
    'placeholder_text' => __('Type your message...', 'multi-entity-ticket-system'),
    'button_text' => __('Chat with AI', 'multi-entity-ticket-system'),
    'create_ticket_prompt' => true,
    'max_messages' => 10,
    'session_timeout' => 1800,
    'allowed_pages' => array('all'),
    'excluded_pages' => array(),
    'offline_message' => __('Our support team is currently offline. Please leave a message and we\'ll get back to you.', 'multi-entity-ticket-system'),
    'auto_open' => false,
    'auto_open_delay' => 20,
    'show_typing_indicator' => true,
    'enable_file_upload' => false,
    'require_contact_info' => true,
    'force_language' => 'en_US',
    'ignore_language_detection' => true,
    'default_entity' => 0
);

$settings = get_option('mets_ai_chat_widget_settings', $default_settings);
$settings = wp_parse_args($settings, $default_settings);

// Check if AI service is configured
$ai_service = METS_AI_Service::get_instance();
$ai_configured = $ai_service->is_configured();
?>

<div class="wrap">
    <h1><?php _e('AI Chat Widget Settings', 'multi-entity-ticket-system'); ?></h1>
    
    <?php if (!$ai_configured) : ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('AI Service Not Configured', 'multi-entity-ticket-system'); ?></strong><br>
            <?php printf(
                __('The AI Chat Widget requires AI service configuration. Please configure your AI settings %shere%s first.', 'multi-entity-ticket-system'),
                '<a href="' . admin_url('admin.php?page=mets-ai-settings') . '">',
                '</a>'
            ); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="mets-admin-container">
        <div class="mets-admin-main">
            <form method="post" action="">
                <?php wp_nonce_field('mets_ai_chat_widget_settings', 'mets_ai_chat_widget_nonce'); ?>
                
                <!-- General Settings -->
                <div class="mets-admin-section">
                    <h2><?php _e('General Settings', 'multi-entity-ticket-system'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Widget', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled'], 1); ?>>
                                    <?php _e('Enable AI Chat Widget on frontend', 'multi-entity-ticket-system'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, the chat widget will appear on pages according to your display rules.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Position', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <select name="position">
                                    <option value="bottom-right" <?php selected($settings['position'], 'bottom-right'); ?>><?php _e('Bottom Right', 'multi-entity-ticket-system'); ?></option>
                                    <option value="bottom-left" <?php selected($settings['position'], 'bottom-left'); ?>><?php _e('Bottom Left', 'multi-entity-ticket-system'); ?></option>
                                    <option value="top-right" <?php selected($settings['position'], 'top-right'); ?>><?php _e('Top Right', 'multi-entity-ticket-system'); ?></option>
                                    <option value="top-left" <?php selected($settings['position'], 'top-left'); ?>><?php _e('Top Left', 'multi-entity-ticket-system'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Choose where the chat widget button appears on the page.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Theme', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <select name="theme">
                                    <option value="blue" <?php selected($settings['theme'], 'blue'); ?>><?php _e('Blue', 'multi-entity-ticket-system'); ?></option>
                                    <option value="green" <?php selected($settings['theme'], 'green'); ?>><?php _e('Green', 'multi-entity-ticket-system'); ?></option>
                                    <option value="purple" <?php selected($settings['theme'], 'purple'); ?>><?php _e('Purple', 'multi-entity-ticket-system'); ?></option>
                                    <option value="dark" <?php selected($settings['theme'], 'dark'); ?>><?php _e('Dark', 'multi-entity-ticket-system'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Select the color theme for the chat widget.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Auto Open', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_open" value="1" <?php checked($settings['auto_open'], 1); ?>>
                                    <?php _e('Automatically open chat widget after page load', 'multi-entity-ticket-system'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('If enabled, the chat window will open automatically after the specified delay.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Auto Open Delay', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <input type="number" name="auto_open_delay" value="<?php echo esc_attr($settings['auto_open_delay']); ?>" min="1" max="300" class="small-text">
                                <span><?php _e('seconds', 'multi-entity-ticket-system'); ?></span>
                                <p class="description">
                                    <?php _e('Time to wait before automatically opening the chat window (1-300 seconds). Default is 20 seconds.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Default Entity for Tickets', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <select name="default_entity" class="regular-text">
                                    <option value="0"><?php _e('Use first active entity', 'multi-entity-ticket-system'); ?></option>
                                    <?php
                                    // Get available entities
                                    global $wpdb;
                                    $entities_table = $wpdb->prefix . 'mets_entities';
                                    $entities = $wpdb->get_results("SELECT id, name FROM $entities_table WHERE status = 'active' ORDER BY name ASC");
                                    
                                    $current_default = get_option('mets_ai_chat_default_entity', 0);
                                    
                                    foreach ($entities as $entity) {
                                        $selected = selected($current_default, $entity->id, false);
                                        echo "<option value=\"{$entity->id}\" {$selected}>{$entity->name}</option>";
                                    }
                                    ?>
                                </select>
                                <p class="description">
                                    <?php _e('Select which entity tickets created through AI chat will be assigned to. This affects which staff members can see and manage these tickets.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Messages & Text -->
                <div class="mets-admin-section">
                    <h2><?php _e('Messages & Text', 'multi-entity-ticket-system'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Button Text', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <input type="text" name="button_text" value="<?php echo esc_attr($settings['button_text']); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Text shown on the chat widget button when expanded.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Welcome Message', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <textarea name="welcome_message" rows="3" class="large-text"><?php echo esc_textarea($settings['welcome_message']); ?></textarea>
                                <p class="description">
                                    <?php _e('First message shown when users open the chat widget.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Input Placeholder', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <input type="text" name="placeholder_text" value="<?php echo esc_attr($settings['placeholder_text']); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Placeholder text shown in the message input field.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Offline Message', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <textarea name="offline_message" rows="3" class="large-text"><?php echo esc_textarea($settings['offline_message']); ?></textarea>
                                <p class="description">
                                    <?php _e('Message shown when AI service is unavailable (not currently used).', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Behavior Settings -->
                <div class="mets-admin-section">
                    <h2><?php _e('Behavior Settings', 'multi-entity-ticket-system'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Show Typing Indicator', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="show_typing_indicator" value="1" <?php checked($settings['show_typing_indicator'], 1); ?>>
                                    <?php _e('Show typing indicator while AI is generating response', 'multi-entity-ticket-system'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Create Ticket Prompt', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="create_ticket_prompt" value="1" <?php checked($settings['create_ticket_prompt'], 1); ?>>
                                    <?php _e('Show create ticket button in chat interface', 'multi-entity-ticket-system'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Allows users to convert their chat conversation into a support ticket.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Require Contact Info', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="require_contact_info" value="1" <?php checked($settings['require_contact_info'], 1); ?>>
                                    <?php _e('Require name and email before starting chat', 'multi-entity-ticket-system'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, users must provide their name and email before they can send messages.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Enable File Upload', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_file_upload" value="1" <?php checked($settings['enable_file_upload'], 1); ?>>
                                    <?php _e('Allow users to upload files in chat', 'multi-entity-ticket-system'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Currently displays files in chat but does not process them on server (feature in development).', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Max Messages', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <input type="number" name="max_messages" value="<?php echo esc_attr($settings['max_messages']); ?>" min="5" max="50" class="small-text">
                                <p class="description">
                                    <?php _e('Maximum number of messages to keep in chat history (affects AI context).', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Session Timeout', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <input type="number" name="session_timeout" value="<?php echo esc_attr($settings['session_timeout']); ?>" min="300" max="7200" class="small-text">
                                <span><?php _e('seconds', 'multi-entity-ticket-system'); ?></span>
                                <p class="description">
                                    <?php _e('How long to keep chat sessions active (300 = 5 minutes, 1800 = 30 minutes).', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Language Settings -->
                <div class="mets-admin-section">
                    <h2><?php _e('Language Settings', 'multi-entity-ticket-system'); ?></h2>
                    <p class="description"><?php _e('Control how the AI Chat Widget handles different languages and responds to users.', 'multi-entity-ticket-system'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Response Language', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <select name="force_language" class="regular-text">
                                    <option value="auto" <?php selected($settings['force_language'], 'auto'); ?>><?php _e('Auto-detect user language', 'multi-entity-ticket-system'); ?></option>
                                    <option value="en_US" <?php selected($settings['force_language'], 'en_US'); ?>><?php _e('English (US)', 'multi-entity-ticket-system'); ?></option>
                                    <option value="en_GB" <?php selected($settings['force_language'], 'en_GB'); ?>><?php _e('English (UK)', 'multi-entity-ticket-system'); ?></option>
                                    <option value="he_IL" <?php selected($settings['force_language'], 'he_IL'); ?>><?php _e('Hebrew', 'multi-entity-ticket-system'); ?></option>
                                    <option value="es_ES" <?php selected($settings['force_language'], 'es_ES'); ?>><?php _e('Spanish', 'multi-entity-ticket-system'); ?></option>
                                    <option value="fr_FR" <?php selected($settings['force_language'], 'fr_FR'); ?>><?php _e('French', 'multi-entity-ticket-system'); ?></option>
                                    <option value="de_DE" <?php selected($settings['force_language'], 'de_DE'); ?>><?php _e('German', 'multi-entity-ticket-system'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Choose how the AI should respond to users. When set to a specific language, the AI will always respond in that language regardless of user input language.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Ignore Language Detection', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ignore_language_detection" value="1" <?php checked($settings['ignore_language_detection'], 1); ?>>
                                    <?php _e('Always use the master prompt language settings', 'multi-entity-ticket-system'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, the AI will follow the language instructions in your master prompt instead of auto-detecting user language. This prevents language override issues.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Display Rules -->
                <div class="mets-admin-section">
                    <h2><?php _e('Display Rules', 'multi-entity-ticket-system'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Show On Pages', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php _e('Show On Pages', 'multi-entity-ticket-system'); ?></legend>
                                    
                                    <label>
                                        <input type="checkbox" name="allowed_pages[]" value="all" <?php checked(in_array('all', $settings['allowed_pages']), true); ?>>
                                        <?php _e('All Pages', 'multi-entity-ticket-system'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="allowed_pages[]" value="home" <?php checked(in_array('home', $settings['allowed_pages']), true); ?>>
                                        <?php _e('Homepage Only', 'multi-entity-ticket-system'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="allowed_pages[]" value="pages" <?php checked(in_array('pages', $settings['allowed_pages']), true); ?>>
                                        <?php _e('All Pages (not posts)', 'multi-entity-ticket-system'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="allowed_pages[]" value="posts" <?php checked(in_array('posts', $settings['allowed_pages']), true); ?>>
                                        <?php _e('All Blog Posts', 'multi-entity-ticket-system'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="allowed_pages[]" value="contact" <?php checked(in_array('contact', $settings['allowed_pages']), true); ?>>
                                        <?php _e('Contact/Support Pages', 'multi-entity-ticket-system'); ?>
                                    </label><br>
                                    
                                    <?php if (class_exists('WooCommerce')) : ?>
                                    <label>
                                        <input type="checkbox" name="allowed_pages[]" value="shop" <?php checked(in_array('shop', $settings['allowed_pages']), true); ?>>
                                        <?php _e('WooCommerce Shop Pages', 'multi-entity-ticket-system'); ?>
                                    </label><br>
                                    <?php endif; ?>
                                </fieldset>
                                <p class="description">
                                    <?php _e('Choose which pages should display the chat widget. "All Pages" overrides other selections.', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Exclude Pages', 'multi-entity-ticket-system'); ?></th>
                            <td>
                                <textarea name="excluded_pages" rows="4" class="large-text" placeholder="<?php esc_attr_e("Enter page IDs, slugs, or URLs (one per line)\nExample:\n123\ncontact-form\n/checkout/", 'multi-entity-ticket-system'); ?>"><?php echo esc_textarea(implode("\n", $settings['excluded_pages'])); ?></textarea>
                                <p class="description">
                                    <?php _e('Pages where the widget should NOT appear. Enter page IDs, slugs, or URLs (one per line).', 'multi-entity-ticket-system'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- AI Integration Status -->
                <div class="mets-admin-section">
                    <h2><?php _e('AI Integration Status', 'multi-entity-ticket-system'); ?></h2>
                    
                    <div class="mets-status-grid">
                        <div class="mets-status-item">
                            <div class="mets-status-icon <?php echo $ai_configured ? 'success' : 'error'; ?>">
                                <?php echo $ai_configured ? '✓' : '✗'; ?>
                            </div>
                            <div class="mets-status-content">
                                <h4><?php _e('AI Service', 'multi-entity-ticket-system'); ?></h4>
                                <p><?php echo $ai_configured ? __('Configured and ready', 'multi-entity-ticket-system') : __('Not configured', 'multi-entity-ticket-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="mets-status-item">
                            <div class="mets-status-icon <?php echo !empty(get_option('mets_ai_settings')['master_prompt']) ? 'success' : 'warning'; ?>">
                                <?php echo !empty(get_option('mets_ai_settings')['master_prompt']) ? '✓' : '!'; ?>
                            </div>
                            <div class="mets-status-content">
                                <h4><?php _e('Master Prompt', 'multi-entity-ticket-system'); ?></h4>
                                <p><?php echo !empty(get_option('mets_ai_settings')['master_prompt']) ? __('Configured', 'multi-entity-ticket-system') : __('Using default', 'multi-entity-ticket-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="mets-status-item">
                            <div class="mets-status-icon <?php echo !empty(get_option('mets_ai_settings')['knowledge_base']) ? 'success' : 'warning'; ?>">
                                <?php echo !empty(get_option('mets_ai_settings')['knowledge_base']) ? '✓' : '!'; ?>
                            </div>
                            <div class="mets-status-content">
                                <h4><?php _e('Knowledge Base', 'multi-entity-ticket-system'); ?></h4>
                                <p><?php echo !empty(get_option('mets_ai_settings')['knowledge_base']) ? __('Configured', 'multi-entity-ticket-system') : __('Using default', 'multi-entity-ticket-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="mets-status-item">
                            <div class="mets-status-icon <?php echo class_exists('METS_Language_Manager') ? 'success' : 'warning'; ?>">
                                <?php echo class_exists('METS_Language_Manager') ? '✓' : '!'; ?>
                            </div>
                            <div class="mets-status-content">
                                <h4><?php _e('Language Support', 'multi-entity-ticket-system'); ?></h4>
                                <p><?php echo class_exists('METS_Language_Manager') ? __('Available', 'multi-entity-ticket-system') : __('Limited', 'multi-entity-ticket-system'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php submit_button(__('Save Settings', 'multi-entity-ticket-system')); ?>
            </form>
        </div>
        
        <!-- Sidebar -->
        <div class="mets-admin-sidebar">
            <div class="mets-admin-widget">
                <h3><?php _e('Preview', 'multi-entity-ticket-system'); ?></h3>
                <p><?php _e('The chat widget will appear on your frontend based on the settings configured here.', 'multi-entity-ticket-system'); ?></p>
                <div class="mets-widget-preview">
                    <div class="mets-preview-widget mets-preview-<?php echo esc_attr($settings['position']); ?> mets-preview-<?php echo esc_attr($settings['theme']); ?>">
                        <div class="mets-preview-button">
                            💬
                            <span class="mets-preview-text"><?php echo esc_html($settings['button_text']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mets-admin-widget">
                <h3><?php _e('Usage Tips', 'multi-entity-ticket-system'); ?></h3>
                <ul>
                    <li><?php _e('Configure AI settings first for best results', 'multi-entity-ticket-system'); ?></li>
                    <li><?php _e('Test the widget on different devices', 'multi-entity-ticket-system'); ?></li>
                    <li><?php _e('Use page exclusions for forms/checkout', 'multi-entity-ticket-system'); ?></li>
                    <li><?php _e('Monitor chat sessions for improvements', 'multi-entity-ticket-system'); ?></li>
                </ul>
            </div>
            
            <div class="mets-admin-widget">
                <h3><?php _e('Performance', 'multi-entity-ticket-system'); ?></h3>
                <p><?php _e('Chat sessions are stored temporarily in WordPress transients and automatically expire based on your timeout setting.', 'multi-entity-ticket-system'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.mets-admin-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.mets-admin-main {
    flex: 1;
}

.mets-admin-sidebar {
    width: 300px;
}

.mets-admin-section {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
}

.mets-admin-section h2 {
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
    font-size: 16px;
}

.mets-admin-section .form-table {
    margin: 0;
}

.mets-admin-section .form-table th,
.mets-admin-section .form-table td {
    padding: 15px 20px;
}

.mets-admin-widget {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
}

.mets-admin-widget h3 {
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
    font-size: 14px;
    font-weight: 600;
}

.mets-admin-widget p,
.mets-admin-widget ul {
    padding: 15px 20px;
    margin: 0;
}

.mets-admin-widget ul {
    list-style: disc;
    padding-left: 40px;
}

.mets-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 20px;
}

.mets-status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.mets-status-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}

.mets-status-icon.success {
    background: #10b981;
}

.mets-status-icon.warning {
    background: #f59e0b;
}

.mets-status-icon.error {
    background: #ef4444;
}

.mets-status-content h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.mets-status-content p {
    margin: 4px 0 0 0;
    font-size: 12px;
    color: #666;
}

.mets-widget-preview {
    padding: 20px;
    background: #f0f0f1;
    position: relative;
    height: 120px;
    border-radius: 6px;
}

.mets-preview-widget {
    position: absolute;
}

.mets-preview-bottom-right {
    bottom: 10px;
    right: 10px;
}

.mets-preview-bottom-left {
    bottom: 10px;
    left: 10px;
}

.mets-preview-top-right {
    top: 10px;
    right: 10px;
}

.mets-preview-top-left {
    top: 10px;
    left: 10px;
}

.mets-preview-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    border-radius: 25px;
    color: white;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.mets-preview-blue .mets-preview-button {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.mets-preview-green .mets-preview-button {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.mets-preview-purple .mets-preview-button {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.mets-preview-dark .mets-preview-button {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
}

.mets-preview-text {
    white-space: nowrap;
}

@media (max-width: 782px) {
    .mets-admin-container {
        flex-direction: column;
    }
    
    .mets-admin-sidebar {
        width: 100%;
    }
    
    .mets-status-grid {
        grid-template-columns: 1fr;
    }
}
</style>