<?php
/**
 * AI Chat Widget for Frontend
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend AI Chat Widget functionality
 */
class METS_AI_Chat_Widget {
    
    /**
     * AI Service instance
     */
    private $ai_service;
    
    /**
     * Language Manager instance
     */
    private $language_manager;
    
    /**
     * Widget settings
     */
    private $widget_settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_service = METS_AI_Service::get_instance();
        $this->language_manager = METS_Language_Manager::get_instance();
        $this->load_widget_settings();
        
        add_action('wp_footer', array($this, 'render_chat_widget'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_mets_ai_chat_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_nopriv_mets_ai_chat_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_mets_ai_chat_create_ticket', array($this, 'handle_create_ticket'));
        add_action('wp_ajax_nopriv_mets_ai_chat_create_ticket', array($this, 'handle_create_ticket'));
        add_action('wp_ajax_mets_ai_chat_email_conversation', array($this, 'handle_email_conversation'));
        add_action('wp_ajax_nopriv_mets_ai_chat_email_conversation', array($this, 'handle_email_conversation'));
    }
    
    /**
     * Load widget settings
     */
    private function load_widget_settings() {
        $default_settings = array(
            'enabled' => true,
            'position' => 'bottom-right',
            'theme' => 'blue',
            'welcome_message' => __('Hi! How can I help you today?', 'multi-entity-ticket-system'),
            'placeholder_text' => __('Type your message...', 'multi-entity-ticket-system'),
            'button_text' => __('Chat with AI', 'multi-entity-ticket-system'),
            'create_ticket_prompt' => true,
            'max_messages' => 25, // Increased from 10 to 25 to utilize expanded context
            'session_timeout' => 1800, // 30 minutes
            'allowed_pages' => array('all'),
            'excluded_pages' => array(),
            'offline_message' => __('Our support team is currently offline. Please leave a message and we\'ll get back to you.', 'multi-entity-ticket-system'),
            'auto_open' => false,
            'auto_open_delay' => 20,
            'show_typing_indicator' => true,
            'enable_file_upload' => false,
            'require_contact_info' => true,
            'force_language' => 'en_US', // Force specific language (en_US, he_IL, etc.) or 'auto' for detection
            'ignore_language_detection' => true // Ignore automatic language detection
        );
        
        $this->widget_settings = get_option('mets_ai_chat_widget_settings', $default_settings);
        $this->widget_settings = wp_parse_args($this->widget_settings, $default_settings);
    }
    
    /**
     * Check if widget should be displayed on current page
     */
    private function should_display_widget() {
        if (!$this->widget_settings['enabled']) {
            error_log('[METS AI Chat] Widget is disabled');
            return false;
        }
        
        if (is_admin()) {
            error_log('[METS AI Chat] Currently in admin area');
            return false;
        }
        
        // Check AI service availability
        if (!$this->ai_service->is_configured()) {
            error_log('[METS AI Chat] AI service is not configured');
            return false;
        }
        
        $current_page = get_queried_object();
        $allowed_pages = $this->widget_settings['allowed_pages'];
        $excluded_pages = $this->widget_settings['excluded_pages'];
        
        // Check if current page is excluded
        if (!empty($excluded_pages)) {
            foreach ($excluded_pages as $excluded) {
                if ($this->matches_page_rule($excluded, $current_page)) {
                    error_log('[METS AI Chat] Current page is excluded');
                    return false;
                }
            }
        }
        
        // Check if current page is allowed
        if (in_array('all', $allowed_pages)) {
            error_log('[METS AI Chat] Widget should be displayed on all pages');
            return true;
        }
        
        foreach ($allowed_pages as $allowed) {
            if ($this->matches_page_rule($allowed, $current_page)) {
                error_log('[METS AI Chat] Widget matches allowed page rule');
                return true;
            }
        }
        
        error_log('[METS AI Chat] Widget not displayed - no matching page rules');
        return false;
    }
    
    /**
     * Check if current page matches a rule
     */
    private function matches_page_rule($rule, $current_page) {
        switch ($rule) {
            case 'all':
                return true;
            case 'home':
                return is_front_page();
            case 'pages':
                return is_page();
            case 'posts':
                return is_single() && get_post_type() === 'post';
            case 'shop':
                return function_exists('is_shop') && is_shop();
            case 'contact':
                return is_page() && isset($current_page->post_name) && 
                       in_array($current_page->post_name, array('contact', 'contact-us', 'support'));
            default:
                if (is_numeric($rule)) {
                    return is_page($rule) || is_single($rule);
                }
                return false;
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        error_log('[METS AI Chat] enqueue_scripts called');
        // More permissive check for enqueuing - only check basic requirements
        if (!$this->widget_settings['enabled'] || is_admin() || !$this->ai_service->is_configured()) {
            error_log('[METS AI Chat] Skipping enqueue - widget disabled, in admin, or AI not configured');
            return;
        }
        
        error_log('[METS AI Chat] Enqueuing scripts and styles');
        
        wp_enqueue_script(
            'mets-ai-chat-widget',
            METS_PLUGIN_URL . 'public/js/ai-chat-widget.js',
            array('jquery'),
            METS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'mets-ai-chat-widget',
            METS_PLUGIN_URL . 'public/css/ai-chat-widget.css',
            array(),
            METS_VERSION
        );
        
        // Localize script
        wp_localize_script('mets-ai-chat-widget', 'metsAiChat', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mets_ai_chat'),
            'settings' => array(
                'welcome_message' => $this->widget_settings['welcome_message'],
                'placeholder_text' => $this->widget_settings['placeholder_text'],
                'max_messages' => $this->widget_settings['max_messages'],
                'show_typing_indicator' => $this->widget_settings['show_typing_indicator'],
                'enable_file_upload' => $this->widget_settings['enable_file_upload'],
                'require_contact_info' => $this->widget_settings['require_contact_info'],
                'auto_open' => $this->widget_settings['auto_open'],
                'auto_open_delay' => $this->widget_settings['auto_open_delay'],
                'session_timeout' => $this->widget_settings['session_timeout']
            ),
            'strings' => array(
                'send' => __('Send', 'multi-entity-ticket-system'),
                'typing' => __('AI is typing...', 'multi-entity-ticket-system'),
                'error' => __('Sorry, something went wrong. Please try again.', 'multi-entity-ticket-system'),
                'offline' => $this->widget_settings['offline_message'],
                'create_ticket' => __('Create Support Ticket', 'multi-entity-ticket-system'),
                'name_required' => __('Please enter your name', 'multi-entity-ticket-system'),
                'email_required' => __('Please enter your email', 'multi-entity-ticket-system'),
                'entity_required' => __('Please select a department', 'multi-entity-ticket-system'),
            )
        ));
    }
    
    /**
     * Render the chat widget HTML
     */
    public function render_chat_widget() {
        error_log('[METS AI Chat] render_chat_widget called');
        if (!$this->should_display_widget()) {
            error_log('[METS AI Chat] should_display_widget returned false');
            return;
        }
        
        error_log('[METS AI Chat] Rendering chat widget');
        
        $position_class = 'mets-chat-' . str_replace('_', '-', $this->widget_settings['position']);
        $theme_class = 'mets-chat-theme-' . $this->widget_settings['theme'];
        
        ?>
        <div id="mets-ai-chat-widget" class="mets-ai-chat-widget <?php echo esc_attr($position_class); ?> <?php echo esc_attr($theme_class); ?>" style="display: none;">
            
            <!-- Chat Toggle Button -->
            <div class="mets-chat-toggle" id="mets-chat-toggle">
                <div class="mets-chat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3.04 1.05 4.39L1 22l5.61-2.05C8.96 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.4 0-2.76-.35-3.95-1.01L7 19.5l.5-1.05C6.35 17.26 6 15.9 6 14.5 6 9.26 9.26 6 12 6s6 3.26 6 6-3.26 6-6 6z"/>
                        <circle cx="9" cy="12" r="1"/>
                        <circle cx="12" cy="12" r="1"/>
                        <circle cx="15" cy="12" r="1"/>
                    </svg>
                </div>
                <div class="mets-chat-badge" id="mets-chat-badge" style="display: none;">1</div>
                <span class="mets-chat-button-text"><?php echo esc_html($this->widget_settings['button_text']); ?></span>
            </div>
            
            <!-- Chat Window -->
            <div class="mets-chat-window" id="mets-chat-window" style="display: none;">
                
                <!-- Chat Header -->
                <div class="mets-chat-header">
                    <div class="mets-chat-title">
                        <div class="mets-chat-avatar">
                            <div class="mets-ai-indicator">AI</div>
                        </div>
                        <div class="mets-chat-info">
                            <h4><?php _e('AI Support Assistant', 'multi-entity-ticket-system'); ?></h4>
                            <span class="mets-chat-status online"><?php _e('Online', 'multi-entity-ticket-system'); ?></span>
                        </div>
                    </div>
                    <div class="mets-chat-controls">
                        <button class="mets-chat-minimize" id="mets-chat-minimize" title="<?php esc_attr_e('Minimize', 'multi-entity-ticket-system'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 13H5v-2h14v2z"/>
                            </svg>
                        </button>
                        <button class="mets-chat-close" id="mets-chat-close" title="<?php esc_attr_e('Close', 'multi-entity-ticket-system'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Chat Messages -->
                <div class="mets-chat-messages" id="mets-chat-messages">
                    <div class="mets-chat-message mets-message-ai">
                        <div class="mets-message-avatar">
                            <div class="mets-ai-indicator">AI</div>
                        </div>
                        <div class="mets-message-content">
                            <div class="mets-message-bubble">
                                <?php echo esc_html($this->widget_settings['welcome_message']); ?>
                            </div>
                            <div class="mets-message-time"><?php echo current_time('H:i'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Typing Indicator -->
                <div class="mets-typing-indicator" id="mets-typing-indicator" style="display: none;">
                    <div class="mets-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span class="mets-typing-text"><?php _e('AI is typing...', 'multi-entity-ticket-system'); ?></span>
                </div>
                
                <!-- Chat Input -->
                <div class="mets-chat-input-wrapper">
                    <?php if ($this->widget_settings['require_contact_info']) : ?>
                    <div class="mets-contact-form" id="mets-contact-form">
                        <div class="mets-input-group">
                            <input type="text" id="mets-user-name" placeholder="<?php esc_attr_e('Your name', 'multi-entity-ticket-system'); ?>" required>
                        </div>
                        <div class="mets-input-group">
                            <input type="email" id="mets-user-email" placeholder="<?php esc_attr_e('Your email', 'multi-entity-ticket-system'); ?>" required>
                        </div>
                        <div class="mets-input-group">
                            <select id="mets-user-entity" required>
                                <option value=""><?php _e('Select department', 'multi-entity-ticket-system'); ?></option>
                                <?php
                                // Get available entities
                                global $wpdb;
                                $entities_table = $wpdb->prefix . 'mets_entities';
                                $entities = $wpdb->get_results("SELECT id, name FROM $entities_table WHERE status = 'active' ORDER BY name ASC");
                                
                                foreach ($entities as $entity) {
                                    echo '<option value="' . esc_attr($entity->id) . '">' . esc_html($entity->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="button" class="mets-contact-submit" id="mets-contact-submit">
                            <?php _e('Start Chat', 'multi-entity-ticket-system'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mets-chat-input" id="mets-chat-input" <?php echo $this->widget_settings['require_contact_info'] ? 'style="display: none;"' : ''; ?>>
                        <div class="mets-input-container">
                            <textarea 
                                id="mets-message-input" 
                                placeholder="<?php echo esc_attr($this->widget_settings['placeholder_text']); ?>"
                                rows="1"
                                maxlength="1000"
                            ></textarea>
                            
                            <?php if ($this->widget_settings['enable_file_upload']) : ?>
                            <button type="button" class="mets-file-upload-btn" id="mets-file-upload-btn" title="<?php esc_attr_e('Attach file', 'multi-entity-ticket-system'); ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/>
                                </svg>
                            </button>
                            <input type="file" id="mets-file-input" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                            <?php endif; ?>
                            
                            <button type="submit" class="mets-send-btn" id="mets-send-btn" disabled>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mets-chat-actions">
                            <button type="button" class="mets-action-btn mets-create-ticket-btn" id="mets-create-ticket-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                                <?php _e('Create Ticket', 'multi-entity-ticket-system'); ?>
                            </button>
                            
                            <button type="button" class="mets-action-btn mets-email-conversation-btn" id="mets-email-conversation-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/>
                                </svg>
                                <?php _e('Email Conversation', 'multi-entity-ticket-system'); ?>
                            </button>
                            
                            <button type="button" class="mets-action-btn mets-clear-chat-btn" id="mets-clear-chat-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/>
                                </svg>
                                <?php _e('Clear Chat', 'multi-entity-ticket-system'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Create Ticket Modal -->
            <div class="mets-modal" id="mets-create-ticket-modal" style="display: none;">
                <div class="mets-modal-content">
                    <div class="mets-modal-header">
                        <h3><?php _e('Create Support Ticket', 'multi-entity-ticket-system'); ?></h3>
                        <button class="mets-modal-close" id="mets-modal-close">&times;</button>
                    </div>
                    <div class="mets-modal-body">
                        <form id="mets-ticket-form">
                            <div class="mets-form-group">
                                <label for="mets-ticket-name"><?php _e('Your Name', 'multi-entity-ticket-system'); ?> *</label>
                                <input type="text" id="mets-ticket-name" name="customer_name" required>
                            </div>
                            
                            <div class="mets-form-group">
                                <label for="mets-ticket-email"><?php _e('Your Email', 'multi-entity-ticket-system'); ?> *</label>
                                <input type="email" id="mets-ticket-email" name="customer_email" required>
                            </div>
                            
                            <div class="mets-form-group">
                                <label for="mets-ticket-subject"><?php _e('Subject', 'multi-entity-ticket-system'); ?> *</label>
                                <input type="text" id="mets-ticket-subject" name="subject" required>
                            </div>
                            
                            <div class="mets-form-group">
                                <label for="mets-ticket-priority"><?php _e('Priority', 'multi-entity-ticket-system'); ?></label>
                                <select id="mets-ticket-priority" name="priority">
                                    <option value="low"><?php _e('Low', 'multi-entity-ticket-system'); ?></option>
                                    <option value="medium" selected><?php _e('Medium', 'multi-entity-ticket-system'); ?></option>
                                    <option value="high"><?php _e('High', 'multi-entity-ticket-system'); ?></option>
                                </select>
                            </div>
                            
                            <div class="mets-form-group">
                                <label for="mets-ticket-description"><?php _e('Description', 'multi-entity-ticket-system'); ?> *</label>
                                <textarea id="mets-ticket-description" name="description" rows="4" required></textarea>
                                <div class="mets-form-help">
                                    <?php _e('The chat conversation will be automatically included with your ticket.', 'multi-entity-ticket-system'); ?>
                                </div>
                            </div>
                            
                            <div class="mets-form-actions">
                                <button type="button" class="mets-btn mets-btn-secondary" id="mets-cancel-ticket">
                                    <?php _e('Cancel', 'multi-entity-ticket-system'); ?>
                                </button>
                                <button type="submit" class="mets-btn mets-btn-primary">
                                    <?php _e('Create Ticket', 'multi-entity-ticket-system'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Handle chat message AJAX request
     */
    public function handle_chat_message() {
        check_ajax_referer('mets_ai_chat', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message']);
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $user_name = sanitize_text_field($_POST['user_name'] ?? '');
        $user_email = sanitize_email($_POST['user_email'] ?? '');
        $user_entity_id = intval($_POST['user_entity_id'] ?? 0);
        
        if (empty($message)) {
            wp_send_json_error('Message is required');
        }
        
        // Generate session ID if not provided
        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }
        
        // Get or create session
        $session_data = $this->get_chat_session($session_id);
        if (!$session_data) {
            $session_data = $this->create_chat_session($session_id, $user_name, $user_email, $user_entity_id);
        }
        
        // Add user message to session
        $this->add_message_to_session($session_id, 'user', $message, $user_name);
        
        // Handle language detection based on widget settings
        if (!empty($this->widget_settings['ignore_language_detection']) || 
            (!empty($this->widget_settings['force_language']) && $this->widget_settings['force_language'] !== 'auto')) {
            // Use forced language or default to English
            $user_language = $this->widget_settings['force_language'] ?? 'en_US';
        } else {
            // Detect message language only if auto-detection is enabled
            $language_info = $this->language_manager->detect_language($message);
            $user_language = $language_info['language'] ?? 'en_US';
        }
        
        // Generate AI response
        $ai_response = $this->generate_ai_response($message, $session_data, $user_language);
        
        if (is_wp_error($ai_response)) {
            wp_send_json_error($ai_response->get_error_message());
        }
        
        // Add AI response to session
        $this->add_message_to_session($session_id, 'ai', $ai_response);
        
        // Update session timestamp
        $this->update_session_timestamp($session_id);
        
        wp_send_json_success(array(
            'response' => $ai_response,
            'session_id' => $session_id,
            'detected_language' => $user_language,
            'timestamp' => current_time('H:i')
        ));
    }
    
    /**
     * Generate AI response using the configured AI service and settings
     */
    private function generate_ai_response($user_message, $session_data, $language = 'en_US') {
        // Get AI settings
        $ai_settings = get_option('mets_ai_settings', array());
        $master_prompt = $ai_settings['master_prompt'] ?? '';
        $knowledge_base = $ai_settings['knowledge_base'] ?? '';
        
        // Get language-specific settings if available
        global $wpdb;
        $lang_settings_table = $wpdb->prefix . 'mets_ai_language_settings';
        
        $lang_settings = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $lang_settings_table WHERE language = %s",
            $language
        ));
        
        if ($lang_settings) {
            $master_prompt = $lang_settings->master_prompt ?: $master_prompt;
            $knowledge_base = $lang_settings->knowledge_base ?: $knowledge_base;
        }
        
        // Build context from session history (increased from 6 to 20 messages with 5k tokens)
        $context = '';
        if (!empty($session_data['messages'])) {
            $messages = json_decode($session_data['messages'], true);
            if (is_array($messages)) {
                $recent_messages = array_slice($messages, -20); // Last 20 messages for expanded context
                foreach ($recent_messages as $msg) {
                    $role = $msg['role'] === 'user' ? 'Customer' : 'Assistant';
                    $context .= "$role: " . $msg['content'] . "\n";
                }
            }
        }
        
        // Search Knowledge Base for relevant articles
        $kb_context = $this->search_knowledge_base_for_context($user_message, $language);
        if (!empty($kb_context)) {
            $context .= "\nRELEVANT KNOWLEDGE BASE ARTICLES:\n" . $kb_context . "\n";
        }
        
        // Build AI prompt
        $prompt = "You are a helpful customer support AI assistant.\n\n";
        
        if (!empty($master_prompt)) {
            $prompt .= "INSTRUCTIONS:\n" . $master_prompt . "\n\n";
        }
        
        if (!empty($knowledge_base)) {
            $prompt .= "KNOWLEDGE BASE:\n" . $knowledge_base . "\n\n";
        }
        
        if (!empty($context)) {
            $prompt .= "CONVERSATION HISTORY:\n" . $context . "\n";
        }
        
        $prompt .= "CURRENT MESSAGE:\nCustomer: " . $user_message . "\n\n";
        
        // Only add language instruction if master prompt doesn't already specify language AND auto-detection is enabled
        $master_prompt_lower = strtolower($master_prompt);
        $has_language_instruction = (strpos($master_prompt_lower, 'english') !== false || 
                                   strpos($master_prompt_lower, 'hebrew') !== false ||
                                   strpos($master_prompt_lower, 'language') !== false ||
                                   strpos($master_prompt_lower, 'respond in') !== false);
        
        if (!$has_language_instruction && 
            (empty($this->widget_settings['ignore_language_detection']) && 
             ($this->widget_settings['force_language'] === 'auto' || empty($this->widget_settings['force_language'])))) {
            // Only add dynamic language instruction if master prompt doesn't specify language and auto-detection is enabled
            $prompt .= "Please provide a helpful, professional response in " . $this->language_manager->get_language_name($language) . ". ";
        }
        
        $prompt .= "Keep responses helpful and informative (under 300 words). Reference relevant knowledge base articles when available with proper links. Offer to create a support ticket if the issue requires further assistance.";
        
        // Get AI model from settings
        $ai_model = $ai_settings['ai_model'] ?? 'openai/gpt-3.5-turbo';
        
        return $this->ai_service->make_request($prompt, $ai_model, 2000);
    }
    
    /**
     * Get chat session data
     */
    private function get_chat_session($session_id) {
        $transient_key = 'mets_chat_session_' . $session_id;
        return get_transient($transient_key);
    }
    
    /**
     * Create new chat session
     */
    private function create_chat_session($session_id, $user_name = '', $user_email = '', $user_entity_id = 0) {
        $session_data = array(
            'session_id' => $session_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_entity_id' => $user_entity_id,
            'created_at' => current_time('timestamp'),
            'last_activity' => current_time('timestamp'),
            'messages' => json_encode(array()),
            'user_ip' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $transient_key = 'mets_chat_session_' . $session_id;
        set_transient($transient_key, $session_data, $this->widget_settings['session_timeout']);
        
        return $session_data;
    }
    
    /**
     * Add message to session
     */
    private function add_message_to_session($session_id, $role, $content, $user_name = '') {
        $session_data = $this->get_chat_session($session_id);
        if (!$session_data) {
            return false;
        }
        
        $messages = json_decode($session_data['messages'], true) ?: array();
        
        $message = array(
            'role' => $role,
            'content' => $content,
            'timestamp' => current_time('timestamp'),
            'user_name' => $user_name
        );
        
        $messages[] = $message;
        
        // Limit message history (expanded for better context with 5k tokens)
        if (count($messages) > $this->widget_settings['max_messages'] * 2) {
            $messages = array_slice($messages, -($this->widget_settings['max_messages'] * 2));
        }
        
        $session_data['messages'] = json_encode($messages);
        
        $transient_key = 'mets_chat_session_' . $session_id;
        set_transient($transient_key, $session_data, $this->widget_settings['session_timeout']);
        
        return true;
    }
    
    /**
     * Update session timestamp
     */
    private function update_session_timestamp($session_id) {
        $session_data = $this->get_chat_session($session_id);
        if ($session_data) {
            $session_data['last_activity'] = current_time('timestamp');
            $transient_key = 'mets_chat_session_' . $session_id;
            set_transient($transient_key, $session_data, $this->widget_settings['session_timeout']);
        }
    }
    
    /**
     * Handle create ticket AJAX request
     */
    public function handle_create_ticket() {
        check_ajax_referer('mets_ai_chat', 'nonce');
        
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $subject = sanitize_text_field($_POST['subject']);
        $description = sanitize_textarea_field($_POST['description']);
        $priority = sanitize_text_field($_POST['priority']);
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $user_entity_id = intval($_POST['entity_id'] ?? 0);
        
        // Validate required fields
        if (empty($customer_name) || empty($customer_email) || empty($subject) || empty($description)) {
            wp_send_json_error('All required fields must be filled');
        }
        
        if (!is_email($customer_email)) {
            wp_send_json_error('Invalid email address');
        }
        
        // Get chat history
        $chat_history = '';
        if (!empty($session_id)) {
            $session_data = $this->get_chat_session($session_id);
            if ($session_data && !empty($session_data['messages'])) {
                $messages = json_decode($session_data['messages'], true);
                if (is_array($messages)) {
                    $chat_history = "\n\n--- Chat Conversation ---\n";
                    foreach ($messages as $msg) {
                        $role = $msg['role'] === 'user' ? $customer_name : 'AI Assistant';
                        $time = date('Y-m-d H:i:s', $msg['timestamp']);
                        $chat_history .= "[$time] $role: " . $msg['content'] . "\n";
                    }
                    $chat_history .= "--- End Chat ---\n";
                }
            }
        }
        
        // Try to get entity from session data as fallback
        $session_entity_id = 0;
        if (!empty($session_id)) {
            $session_data = $this->get_chat_session($session_id);
            if ($session_data && !empty($session_data['user_entity_id'])) {
                $session_entity_id = intval($session_data['user_entity_id']);
            }
        }
        
        // Use entity from form, or session, or default
        $selected_entity_id = $user_entity_id > 0 ? $user_entity_id : $session_entity_id;
        
        error_log("[METS AI Chat] Entity selection debug - form_entity_id: $user_entity_id, session_entity_id: $session_entity_id, selected: $selected_entity_id");
        
        if ($selected_entity_id > 0) {
            // Verify the entity exists and is active
            global $wpdb;
            $entities_table = $wpdb->prefix . 'mets_entities';
            $entity_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $entities_table WHERE id = %d AND status = 'active'",
                $selected_entity_id
            ));
            
            error_log("[METS AI Chat] Entity validation - entity_id: $selected_entity_id, exists: $entity_exists");
            
            if ($entity_exists) {
                $entity_id = $selected_entity_id;
                error_log("[METS AI Chat] Using user-selected entity: $entity_id");
            } else {
                $entity_id = $this->get_default_entity_for_chat();
                error_log("[METS AI Chat] User entity invalid, using default: $entity_id");
            }
        } else {
            $entity_id = $this->get_default_entity_for_chat();
            error_log("[METS AI Chat] No user entity provided, using default: $entity_id");
        }
        
        if (!$entity_id) {
            wp_send_json_error('No active entity available for ticket creation');
        }
        
        // Use proper METS ticket model
        if (!class_exists('METS_Ticket_Model')) {
            require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
        }
        
        $ticket_model = new METS_Ticket_Model();
        
        $ticket_data = array(
            'entity_id' => $entity_id,
            'subject' => $subject,
            'description' => $description . $chat_history,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'priority' => $priority,
            'status' => 'open',
            'created_by' => null, // Customer-created ticket
            'meta_data' => array(
                'source' => 'ai_chat',
                'language' => $this->language_manager->get_user_language(),
                'session_id' => $session_id
            )
        );
        
        error_log("[METS AI Chat] Creating ticket with entity_id: " . $ticket_data['entity_id']);
        
        $result = $ticket_model->create($ticket_data);
        
        if (is_wp_error($result)) {
            error_log("[METS AI Chat] Ticket creation failed: " . $result->get_error_message());
            wp_send_json_error('Failed to create ticket: ' . $result->get_error_message());
        }
        
        $ticket_id = $result;
        
        // Get the created ticket to retrieve the proper ticket number
        $ticket = $ticket_model->get($ticket_id);
        $ticket_number = $ticket ? $ticket->ticket_number : $ticket_id;
        
        error_log("[METS AI Chat] Ticket created successfully - ID: $ticket_id, Number: $ticket_number, Entity: " . ($ticket ? $ticket->entity_id : 'unknown'));
        
        // Clear chat session
        if (!empty($session_id)) {
            $transient_key = 'mets_chat_session_' . $session_id;
            delete_transient($transient_key);
        }
        
        wp_send_json_success(array(
            'ticket_id' => $ticket_id,
            'ticket_number' => $ticket_number,
            'message' => sprintf(
                __('Your support ticket #%s has been created successfully. We will respond as soon as possible.', 'multi-entity-ticket-system'),
                $ticket_number
            )
        ));
    }
    
    /**
     * Search Knowledge Base for relevant articles to include in AI context
     *
     * @param string $user_message The user's message
     * @param string $language User's language
     * @return string Formatted KB context
     */
    private function search_knowledge_base_for_context($user_message, $language = 'en_US') {
        if (!class_exists('METS_KB_Article_Model')) {
            return '';
        }
        
        $kb_model = new METS_KB_Article_Model();
        
        // Search for relevant articles (limit to 3 most relevant)
        $search_args = array(
            'search' => $user_message,
            'status' => array('published'),
            'visibility' => array('customer', 'staff'), // Include public articles
            'per_page' => 3,
            'page' => 1,
            'orderby' => 'relevance',
            'include_parent' => true
        );
        
        $search_results = $kb_model->get_articles_with_inheritance($search_args);
        
        if (empty($search_results['articles'])) {
            return '';
        }
        
        $kb_context = '';
        foreach ($search_results['articles'] as $article) {
            // Create article URL for referencing
            $article_url = home_url('/kb/' . $article->slug);
            if ($article->entity_name) {
                $article_url = home_url('/kb/' . strtolower($article->entity_name) . '/' . $article->slug);
            }
            
            // Format article for AI context
            $kb_context .= "- Title: {$article->title}\n";
            $kb_context .= "  URL: {$article_url}\n";
            
            // Include excerpt or first 200 chars of content
            $excerpt = !empty($article->excerpt) ? $article->excerpt : wp_trim_words(strip_tags($article->content), 30);
            $kb_context .= "  Summary: {$excerpt}\n\n";
        }
        
        return $kb_context;
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Get default entity for chat-created tickets
     */
    private function get_default_entity_for_chat() {
        // Try to get configured default entity for AI chat
        $default_entity_id = get_option('mets_ai_chat_default_entity');
        
        if ($default_entity_id) {
            // Verify the entity exists and is active
            if (!class_exists('METS_Entity_Model')) {
                require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
            }
            
            $entity_model = new METS_Entity_Model();
            $entity = $entity_model->get($default_entity_id);
            
            if ($entity && $entity->status === 'active') {
                return $default_entity_id;
            }
        }
        
        // Fallback: get first active entity
        global $wpdb;
        $entities_table = $wpdb->prefix . 'mets_entities';
        
        $entity_id = $wpdb->get_var(
            "SELECT id FROM $entities_table WHERE status = 'active' ORDER BY id ASC LIMIT 1"
        );
        
        return $entity_id ? intval($entity_id) : null;
    }
    
    /**
     * Handle email conversation AJAX request
     */
    public function handle_email_conversation() {
        check_ajax_referer('mets_ai_chat', 'nonce');
        
        $user_name = sanitize_text_field($_POST['user_name'] ?? '');
        $user_email = sanitize_email($_POST['user_email'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $chat_history = $_POST['chat_history'] ?? array();
        
        // Validate required fields
        if (empty($user_email) || !is_email($user_email)) {
            wp_send_json_error('Valid email address is required');
        }
        
        if (empty($chat_history) || !is_array($chat_history)) {
            wp_send_json_error('No conversation history found');
        }
        
        // Sanitize chat history
        $sanitized_history = array();
        foreach ($chat_history as $message) {
            if (isset($message['role']) && isset($message['content']) && isset($message['time'])) {
                $sanitized_history[] = array(
                    'role' => sanitize_text_field($message['role']),
                    'content' => sanitize_textarea_field($message['content']),
                    'time' => sanitize_text_field($message['time'])
                );
            }
        }
        
        if (empty($sanitized_history)) {
            wp_send_json_error('No valid conversation history found');
        }
        
        // Format email content
        $subject = sprintf(__('Your AI Chat Conversation - %s', 'multi-entity-ticket-system'), get_bloginfo('name'));
        
        $message = sprintf(__('Hello %s,', 'multi-entity-ticket-system'), $user_name ?: __('there', 'multi-entity-ticket-system')) . "\n\n";
        $message .= __('Here is a copy of your recent chat conversation with our AI support assistant:', 'multi-entity-ticket-system') . "\n\n";
        $message .= "===========================================\n";
        $message .= sprintf(__('Chat Conversation - %s', 'multi-entity-ticket-system'), current_time('Y-m-d H:i:s')) . "\n";
        $message .= "===========================================\n\n";
        
        foreach ($sanitized_history as $msg) {
            $message .= sprintf("[%s] %s: %s\n\n", $msg['time'], $msg['role'], $msg['content']);
        }
        
        $message .= "===========================================\n\n";
        $message .= __('If you need further assistance, please feel free to contact our support team or create a new chat session.', 'multi-entity-ticket-system') . "\n\n";
        $message .= sprintf(__('Best regards,\n%s Support Team', 'multi-entity-ticket-system'), get_bloginfo('name'));
        
        // Send email
        $sent = wp_mail($user_email, $subject, $message);
        
        if ($sent) {
            wp_send_json_success(array(
                'message' => sprintf(__('Conversation sent successfully to %s', 'multi-entity-ticket-system'), $user_email)
            ));
        } else {
            wp_send_json_error('Failed to send email. Please check your email configuration.');
        }
    }
}

// Initialize the widget after WordPress has loaded
if ( ! function_exists( 'mets_initialize_ai_chat_widget' ) ) {
    function mets_initialize_ai_chat_widget() {
        // Only initialize if required classes exist
        if ( class_exists( 'METS_AI_Service' ) && class_exists( 'METS_Language_Manager' ) ) {
            new METS_AI_Chat_Widget();
        }
    }
    // Hook into init to ensure all dependencies are loaded
    add_action( 'init', 'mets_initialize_ai_chat_widget', 20 );
}