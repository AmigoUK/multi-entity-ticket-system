<?php
/**
 * METS AI Integration
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class METS_AI_Integration {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * AI Service instance
     */
    private $ai_service;
    
    /**
     * Enabled features
     */
    private $enabled_features;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Load AI service
        if ( ! class_exists( 'METS_AI_Service' ) ) {
            require_once METS_PLUGIN_PATH . 'includes/class-mets-ai-service.php';
        }
        
        // Load AI routing engine
        if ( ! class_exists( 'METS_AI_Routing' ) ) {
            require_once METS_PLUGIN_PATH . 'includes/class-mets-ai-routing.php';
        }
        
        // Load AI tagging engine
        if ( ! class_exists( 'METS_AI_Tagging' ) ) {
            require_once METS_PLUGIN_PATH . 'includes/class-mets-ai-tagging.php';
        }
        
        $this->ai_service = METS_AI_Service::get_instance();
        $this->enabled_features = get_option( 'mets_ai_features_enabled', array() );
        
        // Only initialize if AI is configured
        if ( $this->ai_service->is_configured() && ! empty( $this->enabled_features ) ) {
            $this->init_hooks();
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Ticket creation hooks
        add_action( 'mets_ticket_created', array( $this, 'process_new_ticket' ), 10, 2 );
        
        // Add AI fields to ticket edit page
        add_action( 'mets_ticket_edit_form_after_description', array( $this, 'add_ai_analysis_fields' ) );
        
        // Add suggested response button
        add_action( 'mets_ticket_reply_form_before_submit', array( $this, 'add_suggest_response_button' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_mets_ai_suggest_response', array( $this, 'ajax_suggest_response' ) );
        add_action( 'wp_ajax_mets_ai_recategorize', array( $this, 'ajax_recategorize_ticket' ) );
        add_action( 'wp_ajax_mets_ai_approve_tag', array( $this, 'ajax_approve_tag' ) );
        add_action( 'wp_ajax_mets_ai_reject_tag', array( $this, 'ajax_reject_tag' ) );
        add_action( 'wp_ajax_mets_ai_analyze_routing', array( $this, 'ajax_analyze_routing' ) );
    }
    
    /**
     * Process new ticket with AI
     */
    public function process_new_ticket( $ticket_id, $ticket_data ) {
        global $wpdb;
        
        // Auto categorization
        if ( in_array( 'auto_categorize', $this->enabled_features ) && empty( $ticket_data['category'] ) ) {
            $categories = $this->get_available_categories( $ticket_data['entity_id'] );
            if ( ! empty( $categories ) ) {
                $category = $this->ai_service->categorize_ticket( 
                    $ticket_data['subject'], 
                    $ticket_data['description'], 
                    array_keys( $categories ) 
                );
                
                if ( ! is_wp_error( $category ) && ! empty( $category ) ) {
                    $wpdb->update(
                        $wpdb->prefix . 'mets_tickets',
                        array( 'category' => $category ),
                        array( 'id' => $ticket_id ),
                        array( '%s' ),
                        array( '%d' )
                    );
                    
                    // Log AI action
                    $this->log_ai_action( $ticket_id, 'categorize', $category );
                }
            }
        }
        
        // Priority prediction
        if ( in_array( 'priority_prediction', $this->enabled_features ) ) {
            $priority = $this->ai_service->predict_priority( 
                $ticket_data['subject'], 
                $ticket_data['description'] 
            );
            
            if ( ! is_wp_error( $priority ) && ! empty( $priority ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'mets_tickets',
                    array( 
                        'priority' => $priority,
                        'meta_data' => maybe_serialize( array_merge(
                            maybe_unserialize( $ticket_data['meta_data'] ) ?: array(),
                            array( 'ai_predicted_priority' => $priority )
                        ) )
                    ),
                    array( 'id' => $ticket_id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
                
                // Log AI action
                $this->log_ai_action( $ticket_id, 'priority', $priority );
            }
        }
        
        // Sentiment analysis
        if ( in_array( 'sentiment_analysis', $this->enabled_features ) ) {
            $sentiment = $this->ai_service->analyze_sentiment( $ticket_data['description'] );
            
            if ( ! is_wp_error( $sentiment ) ) {
                $meta_data = maybe_unserialize( $ticket_data['meta_data'] ) ?: array();
                $meta_data['ai_sentiment'] = $sentiment;
                
                $wpdb->update(
                    $wpdb->prefix . 'mets_tickets',
                    array( 'meta_data' => maybe_serialize( $meta_data ) ),
                    array( 'id' => $ticket_id ),
                    array( '%s' ),
                    array( '%d' )
                );
                
                // Log AI action
                $this->log_ai_action( $ticket_id, 'sentiment', $sentiment );
            }
        }
        
        // Smart routing
        if ( in_array( 'smart_routing', $this->enabled_features ) && empty( $ticket_data['assigned_to'] ) ) {
            $routing_engine = METS_AI_Routing::get_instance();
            $routing_analysis = $routing_engine->analyze_ticket_for_routing( $ticket_data );
            
            if ( ! is_wp_error( $routing_analysis ) && ! empty( $routing_analysis['recommended_agent'] ) ) {
                $recommended_agent = $routing_analysis['recommended_agent']['agent']['user_id'];
                
                $wpdb->update(
                    $wpdb->prefix . 'mets_tickets',
                    array( 'assigned_to' => $recommended_agent ),
                    array( 'id' => $ticket_id ),
                    array( '%d' ),
                    array( '%d' )
                );
                
                // Log routing decision
                $routing_engine->log_routing_decision( $ticket_id, $recommended_agent, array(
                    'reasoning' => $routing_analysis['recommended_agent']['reasoning'],
                    'confidence' => $routing_analysis['confidence'],
                    'ai_analysis' => $routing_analysis['ai_analysis'],
                    'fallback_used' => 0,
                    'manual_override' => 0
                ) );
                
                // Log AI action
                $this->log_ai_action( $ticket_id, 'smart_routing', $recommended_agent );
            }
        }
        
        // Auto tagging
        if ( in_array( 'auto_tagging', $this->enabled_features ) ) {
            $tagging_engine = METS_AI_Tagging::get_instance();
            $generated_tags = $tagging_engine->generate_tags( 
                $ticket_data['subject'], 
                $ticket_data['description'], 
                $ticket_data['category'] ?? '' 
            );
            
            if ( ! is_wp_error( $generated_tags ) && ! empty( $generated_tags ) ) {
                $tagging_results = $tagging_engine->apply_tags( $ticket_id, $generated_tags );
                
                // Log tagging results
                if ( ! empty( $tagging_results['applied'] ) ) {
                    $this->log_ai_action( $ticket_id, 'auto_tagging', implode( ', ', $tagging_results['applied'] ) );
                }
            }
        }
    }
    
    /**
     * Add AI analysis fields to ticket edit
     */
    public function add_ai_analysis_fields( $ticket ) {
        if ( ! current_user_can( 'manage_tickets' ) ) {
            return;
        }
        
        $meta_data = maybe_unserialize( $ticket->meta_data ) ?: array();
        ?>
        <div class="mets-ai-analysis">
            <h3><?php _e( 'AI Analysis', 'multi-entity-ticket-system' ); ?></h3>
            
            <?php if ( ! empty( $meta_data['ai_sentiment'] ) ) : ?>
                <p>
                    <strong><?php _e( 'Sentiment:', 'multi-entity-ticket-system' ); ?></strong>
                    <span class="mets-sentiment mets-sentiment-<?php echo esc_attr( $meta_data['ai_sentiment'] ); ?>">
                        <?php echo esc_html( ucfirst( $meta_data['ai_sentiment'] ) ); ?>
                    </span>
                </p>
            <?php endif; ?>
            
            <?php if ( ! empty( $meta_data['ai_predicted_priority'] ) ) : ?>
                <p>
                    <strong><?php _e( 'AI Predicted Priority:', 'multi-entity-ticket-system' ); ?></strong>
                    <?php echo esc_html( ucfirst( $meta_data['ai_predicted_priority'] ) ); ?>
                </p>
            <?php endif; ?>
            
            <?php if ( in_array( 'auto_categorize', $this->enabled_features ) ) : ?>
                <p>
                    <button type="button" class="button button-secondary" id="mets-ai-recategorize" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>">
                        <?php _e( 'Re-analyze Category', 'multi-entity-ticket-system' ); ?>
                    </button>
                </p>
            <?php endif; ?>
            
            <?php if ( in_array( 'smart_routing', $this->enabled_features ) ) : ?>
                <p>
                    <button type="button" class="button button-secondary" id="mets-ai-analyze-routing" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>">
                        <?php _e( 'AI Routing Analysis', 'multi-entity-ticket-system' ); ?>
                    </button>
                </p>
            <?php endif; ?>
            
            <?php if ( in_array( 'auto_tagging', $this->enabled_features ) ) : ?>
                <?php $this->display_tag_suggestions( $ticket->id ); ?>
            <?php endif; ?>
        </div>
        
        <style>
            .mets-ai-analysis {
                background: #f0f0f1;
                padding: 15px;
                margin: 20px 0;
                border-radius: 5px;
            }
            .mets-sentiment {
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
            }
            .mets-sentiment-positive { background: #d4f4dd; color: #0a4f2a; }
            .mets-sentiment-negative { background: #fdd4d4; color: #8a0000; }
            .mets-sentiment-neutral { background: #e0e0e0; color: #555; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#mets-ai-recategorize').on('click', function() {
                var button = $(this);
                var ticketId = button.data('ticket-id');
                
                button.prop('disabled', true).text('<?php _e( 'Analyzing...', 'multi-entity-ticket-system' ); ?>');
                
                $.post(ajaxurl, {
                    action: 'mets_ai_recategorize',
                    ticket_id: ticketId,
                    nonce: '<?php echo wp_create_nonce( 'mets_ai_recategorize' ); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data || '<?php _e( 'Failed to analyze category', 'multi-entity-ticket-system' ); ?>');
                        button.prop('disabled', false).text('<?php _e( 'Re-analyze Category', 'multi-entity-ticket-system' ); ?>');
                    }
                });
            });
            
            // AI Routing Analysis
            $('#mets-ai-analyze-routing').on('click', function() {
                var button = $(this);
                var ticketId = button.data('ticket-id');
                
                button.prop('disabled', true).text('<?php _e( 'Analyzing...', 'multi-entity-ticket-system' ); ?>');
                
                $.post(ajaxurl, {
                    action: 'mets_ai_analyze_routing',
                    ticket_id: ticketId,
                    nonce: '<?php echo wp_create_nonce( 'mets_ai_routing_analysis' ); ?>'
                }, function(response) {
                    button.prop('disabled', false).text('<?php _e( 'AI Routing Analysis', 'multi-entity-ticket-system' ); ?>');
                    
                    if (response.success) {
                        var analysis = response.data.analysis;
                        var message = 'Routing Analysis Results:\n\n';
                        message += 'Complexity: ' + analysis.ai_analysis.complexity + '\n';
                        message += 'Technical Level: ' + analysis.ai_analysis.technical_level + '\n';
                        message += 'Customer Emotion: ' + analysis.ai_analysis.customer_emotion + '\n';
                        
                        if (analysis.recommended_agent) {
                            message += '\nRecommended Agent: ' + analysis.recommended_agent.agent.display_name + '\n';
                            message += 'Confidence: ' + Math.round(analysis.confidence * 100) + '%\n';
                            message += 'Reasoning: ' + analysis.recommended_agent.reasoning;
                        } else {
                            message += '\nNo suitable agent found for routing.';
                        }
                        
                        alert(message);
                    } else {
                        alert(response.data || '<?php _e( 'Failed to analyze routing', 'multi-entity-ticket-system' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add suggest response button
     */
    public function add_suggest_response_button() {
        if ( ! in_array( 'suggested_responses', $this->enabled_features ) ) {
            return;
        }
        ?>
        <div class="mets-ai-response-controls">
            <button type="button" class="button" id="mets-ai-suggest-response">
                <?php _e( 'AI Suggest Response', 'multi-entity-ticket-system' ); ?>
            </button>
            
            <div id="mets-ai-prompt-adjustments" style="display: none; margin-top: 10px;">
                <label for="mets-ai-custom-prompt" style="display: block; margin-bottom: 5px;">
                    <strong><?php _e( 'Additional Instructions (Optional):', 'multi-entity-ticket-system' ); ?></strong>
                </label>
                <textarea id="mets-ai-custom-prompt" 
                          rows="3" 
                          style="width: 100%; max-width: 600px;" 
                          placeholder="<?php _e( 'e.g., Respond in Spanish, Include shipping policy details, Use formal tone, Reference order #12345, etc.', 'multi-entity-ticket-system' ); ?>"></textarea>
                <div style="margin-top: 5px;">
                    <button type="button" class="button button-primary" id="mets-ai-generate-with-prompt">
                        <?php _e( 'Generate Response', 'multi-entity-ticket-system' ); ?>
                    </button>
                    <button type="button" class="button" id="mets-ai-cancel-prompt">
                        <?php _e( 'Cancel', 'multi-entity-ticket-system' ); ?>
                    </button>
                </div>
                <p class="description" style="margin-top: 5px;">
                    <?php _e( 'Add any specific instructions to customize the AI response. Examples:', 'multi-entity-ticket-system' ); ?>
                    <br>• <?php _e( 'Language: "Respond in French" or "Reply in Spanish"', 'multi-entity-ticket-system' ); ?>
                    <br>• <?php _e( 'Tone: "Use casual tone" or "Be very formal"', 'multi-entity-ticket-system' ); ?>
                    <br>• <?php _e( 'Facts: "Customer is VIP member" or "Mention 30-day return policy"', 'multi-entity-ticket-system' ); ?>
                    <br>• <?php _e( 'Context: "Customer previously had issue X" or "This is their 3rd complaint"', 'multi-entity-ticket-system' ); ?>
                </p>
            </div>
        </div>
        
        <style>
            .mets-ai-response-controls {
                margin: 10px 0;
            }
            #mets-ai-prompt-adjustments {
                background: #f0f0f1;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #c3c4c7;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Show prompt adjustment box
            $('#mets-ai-suggest-response').on('click', function() {
                $('#mets-ai-prompt-adjustments').slideDown();
                $(this).prop('disabled', true);
            });
            
            // Cancel prompt adjustment
            $('#mets-ai-cancel-prompt').on('click', function() {
                $('#mets-ai-prompt-adjustments').slideUp();
                $('#mets-ai-custom-prompt').val('');
                $('#mets-ai-suggest-response').prop('disabled', false);
            });
            
            // Generate with custom prompt
            $('#mets-ai-generate-with-prompt').on('click', function() {
                var button = $(this);
                var originalText = button.text();
                var ticketId = $('#ticket_id').val();
                var customPrompt = $('#mets-ai-custom-prompt').val();
                
                button.prop('disabled', true).text('<?php _e( 'Generating...', 'multi-entity-ticket-system' ); ?>');
                $('#mets-ai-cancel-prompt').prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'mets_ai_suggest_response',
                    ticket_id: ticketId,
                    custom_prompt: customPrompt,
                    nonce: '<?php echo wp_create_nonce( 'mets_ai_suggest_response' ); ?>'
                }, function(response) {
                    button.prop('disabled', false).text(originalText);
                    $('#mets-ai-cancel-prompt').prop('disabled', false);
                    
                    if (response.success) {
                        // Insert suggestion into reply field
                        var replyField = $('#reply_content');
                        if (replyField.length) {
                            replyField.val(response.data.suggestion);
                        }
                        
                        // Hide the prompt adjustment box
                        $('#mets-ai-prompt-adjustments').slideUp();
                        $('#mets-ai-custom-prompt').val('');
                        $('#mets-ai-suggest-response').prop('disabled', false);
                    } else {
                        alert(response.data || '<?php _e( 'Failed to generate suggestion', 'multi-entity-ticket-system' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for suggesting response
     */
    public function ajax_suggest_response() {
        check_ajax_referer( 'mets_ai_suggest_response', 'nonce' );
        
        if ( ! current_user_can( 'manage_tickets' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'multi-entity-ticket-system' ) );
        }
        
        $ticket_id = intval( $_POST['ticket_id'] );
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            wp_send_json_error( __( 'Ticket not found', 'multi-entity-ticket-system' ) );
        }
        
        // Get full conversation history
        $conversation_history = $this->get_ticket_conversation_history( $ticket_id );
        
        // Get custom prompt if provided
        $custom_prompt = isset( $_POST['custom_prompt'] ) ? sanitize_textarea_field( $_POST['custom_prompt'] ) : '';
        
        $suggestion = $this->ai_service->suggest_response( array(
            'title' => $ticket->subject,
            'content' => $ticket->description,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'conversation_history' => $conversation_history,
            'custom_prompt' => $custom_prompt
        ) );
        
        if ( is_wp_error( $suggestion ) ) {
            wp_send_json_error( $suggestion->get_error_message() );
        }
        
        // Log AI action
        $this->log_ai_action( $ticket_id, 'suggest_response', 'generated' );
        
        wp_send_json_success( array( 'suggestion' => $suggestion ) );
    }
    
    /**
     * AJAX handler for recategorizing ticket
     */
    public function ajax_recategorize_ticket() {
        check_ajax_referer( 'mets_ai_recategorize', 'nonce' );
        
        if ( ! current_user_can( 'manage_tickets' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'multi-entity-ticket-system' ) );
        }
        
        $ticket_id = intval( $_POST['ticket_id'] );
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            wp_send_json_error( __( 'Ticket not found', 'multi-entity-ticket-system' ) );
        }
        
        $categories = $this->get_available_categories( $ticket->entity_id );
        $category = $this->ai_service->categorize_ticket( 
            $ticket->subject, 
            $ticket->description, 
            array_keys( $categories ) 
        );
        
        if ( is_wp_error( $category ) ) {
            wp_send_json_error( $category->get_error_message() );
        }
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'mets_tickets',
            array( 'category' => $category ),
            array( 'id' => $ticket_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        // Log AI action
        $this->log_ai_action( $ticket_id, 'recategorize', $category );
        
        wp_send_json_success( array( 
            'message' => sprintf( __( 'Category updated to: %s', 'multi-entity-ticket-system' ), $category ),
            'category' => $category 
        ) );
    }
    
    /**
     * Get ticket by ID
     */
    private function get_ticket( $ticket_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mets_tickets WHERE id = %d",
            $ticket_id
        ) );
    }
    
    /**
     * Get full conversation history for a ticket
     */
    private function get_ticket_conversation_history( $ticket_id ) {
        // Load reply model if not available
        if ( ! class_exists( 'METS_Ticket_Reply_Model' ) ) {
            require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
        }
        
        $reply_model = new METS_Ticket_Reply_Model();
        $replies = $reply_model->get_by_ticket( $ticket_id, false ); // false = exclude internal notes
        
        $conversation = array();
        
        // Add each reply to conversation history
        foreach ( $replies as $reply ) {
            $role = ( $reply->user_type === 'customer' ) ? 'Customer' : 'Agent';
            $conversation[] = array(
                'role' => $role,
                'content' => $reply->content,
                'timestamp' => $reply->created_at,
                'user_name' => $reply->user_name ?? ( $role . ' #' . $reply->user_id )
            );
        }
        
        return $conversation;
    }
    
    /**
     * Get available categories for entity
     */
    private function get_available_categories( $entity_id ) {
        $categories = get_option( 'mets_ticket_categories', array() );
        
        // Filter by entity if needed
        // For now, return all categories
        return $categories;
    }
    
    /**
     * Log AI action
     */
    private function log_ai_action( $ticket_id, $action, $result ) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mets_audit_trail',
            array(
                'ticket_id' => $ticket_id,
                'user_id' => 0, // AI user
                'action' => 'ai_' . $action,
                'details' => json_encode( array(
                    'action' => $action,
                    'result' => $result,
                    'model' => get_option( 'mets_openrouter_model', 'unknown' )
                ) ),
                'created_at' => current_time( 'mysql' )
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );
    }
    
    /**
     * Display tag suggestions for ticket
     */
    private function display_tag_suggestions( $ticket_id ) {
        $tagging_engine = METS_AI_Tagging::get_instance();
        $suggestions = $tagging_engine->get_tag_suggestions( $ticket_id );
        
        if ( empty( $suggestions ) ) {
            return;
        }
        ?>
        <div class="mets-tag-suggestions">
            <h4><?php _e( 'AI Tag Suggestions', 'multi-entity-ticket-system' ); ?></h4>
            <?php foreach ( $suggestions as $suggestion ) : ?>
                <div class="tag-suggestion" data-ticket-id="<?php echo esc_attr( $ticket_id ); ?>" data-tag="<?php echo esc_attr( $suggestion['tag_name'] ); ?>">
                    <span class="tag-name"><?php echo esc_html( $suggestion['tag_name'] ); ?></span>
                    <span class="confidence"><?php echo esc_html( round( $suggestion['confidence_score'] * 100 ) ); ?>%</span>
                    <?php if ( ! empty( $suggestion['ai_reasoning'] ) ) : ?>
                        <span class="reasoning" title="<?php echo esc_attr( $suggestion['ai_reasoning'] ); ?>">?</span>
                    <?php endif; ?>
                    <button type="button" class="button button-small approve-tag">✓</button>
                    <button type="button" class="button button-small reject-tag">✗</button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
            .mets-tag-suggestions {
                margin: 15px 0;
            }
            .tag-suggestion {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 5px 0;
                padding: 5px;
                background: #f9f9f9;
                border-radius: 3px;
            }
            .tag-name {
                font-weight: bold;
                color: #2271b1;
            }
            .confidence {
                font-size: 11px;
                color: #666;
            }
            .reasoning {
                cursor: help;
                color: #0073aa;
                font-weight: bold;
            }
            .approve-tag {
                background: #00a32a;
                color: white;
                border: none;
            }
            .reject-tag {
                background: #d63638;
                color: white;
                border: none;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.approve-tag').on('click', function() {
                var button = $(this);
                var container = button.closest('.tag-suggestion');
                var ticketId = container.data('ticket-id');
                var tagName = container.data('tag');
                
                $.post(ajaxurl, {
                    action: 'mets_ai_approve_tag',
                    ticket_id: ticketId,
                    tag_name: tagName,
                    nonce: '<?php echo wp_create_nonce( 'mets_ai_tag_action' ); ?>'
                }, function(response) {
                    if (response.success) {
                        container.fadeOut();
                    } else {
                        alert(response.data || '<?php _e( 'Failed to approve tag', 'multi-entity-ticket-system' ); ?>');
                    }
                });
            });
            
            $('.reject-tag').on('click', function() {
                var button = $(this);
                var container = button.closest('.tag-suggestion');
                var ticketId = container.data('ticket-id');
                var tagName = container.data('tag');
                
                $.post(ajaxurl, {
                    action: 'mets_ai_reject_tag',
                    ticket_id: ticketId,
                    tag_name: tagName,
                    nonce: '<?php echo wp_create_nonce( 'mets_ai_tag_action' ); ?>'
                }, function(response) {
                    if (response.success) {
                        container.fadeOut();
                    } else {
                        alert(response.data || '<?php _e( 'Failed to reject tag', 'multi-entity-ticket-system' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for approving tags
     */
    public function ajax_approve_tag() {
        check_ajax_referer( 'mets_ai_tag_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_tickets' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'multi-entity-ticket-system' ) );
        }
        
        $ticket_id = intval( $_POST['ticket_id'] );
        $tag_name = sanitize_text_field( $_POST['tag_name'] );
        
        $tagging_engine = METS_AI_Tagging::get_instance();
        $result = $tagging_engine->approve_tag( $ticket_id, $tag_name, get_current_user_id() );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Tag approved', 'multi-entity-ticket-system' ) ) );
        } else {
            wp_send_json_error( __( 'Failed to approve tag', 'multi-entity-ticket-system' ) );
        }
    }
    
    /**
     * AJAX handler for rejecting tags
     */
    public function ajax_reject_tag() {
        check_ajax_referer( 'mets_ai_tag_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_tickets' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'multi-entity-ticket-system' ) );
        }
        
        $ticket_id = intval( $_POST['ticket_id'] );
        $tag_name = sanitize_text_field( $_POST['tag_name'] );
        
        $tagging_engine = METS_AI_Tagging::get_instance();
        $result = $tagging_engine->reject_tag( $ticket_id, $tag_name, get_current_user_id() );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Tag rejected', 'multi-entity-ticket-system' ) ) );
        } else {
            wp_send_json_error( __( 'Failed to reject tag', 'multi-entity-ticket-system' ) );
        }
    }
    
    /**
     * AJAX handler for routing analysis
     */
    public function ajax_analyze_routing() {
        check_ajax_referer( 'mets_ai_routing_analysis', 'nonce' );
        
        if ( ! current_user_can( 'manage_tickets' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'multi-entity-ticket-system' ) );
        }
        
        $ticket_id = intval( $_POST['ticket_id'] );
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            wp_send_json_error( __( 'Ticket not found', 'multi-entity-ticket-system' ) );
        }
        
        $routing_engine = METS_AI_Routing::get_instance();
        $analysis = $routing_engine->analyze_ticket_for_routing( array(
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'entity_id' => $ticket->entity_id
        ) );
        
        if ( is_wp_error( $analysis ) ) {
            wp_send_json_error( $analysis->get_error_message() );
        }
        
        wp_send_json_success( array(
            'analysis' => $analysis,
            'message' => __( 'Routing analysis completed', 'multi-entity-ticket-system' )
        ) );
    }
}