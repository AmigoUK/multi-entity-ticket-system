<?php
/**
 * METS AI Service - OpenRouter Integration
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class METS_AI_Service {
    
    /**
     * OpenRouter API endpoint
     */
    const API_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';
    
    /**
     * OpenRouter models endpoint
     */
    const MODELS_ENDPOINT = 'https://openrouter.ai/api/v1/models';
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Default model
     */
    private $model;
    
    /**
     * Max tokens
     */
    private $max_tokens;
    
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
        $this->api_key = defined( 'METS_OPENROUTER_API_KEY' ) ? METS_OPENROUTER_API_KEY : get_option( 'mets_openrouter_api_key' );
        $this->model = defined( 'METS_OPENROUTER_MODEL' ) ? METS_OPENROUTER_MODEL : get_option( 'mets_openrouter_model', 'openai/gpt-3.5-turbo' );
        $this->max_tokens = defined( 'METS_OPENROUTER_MAX_TOKENS' ) ? METS_OPENROUTER_MAX_TOKENS : get_option( 'mets_openrouter_max_tokens', 5000 );
    }
    
    /**
     * Check if AI service is configured
     */
    public function is_configured() {
        return ! empty( $this->api_key );
    }
    
    /**
     * Get translated error message for AI not configured
     * Avoids early translation loading by checking current_action
     */
    private function get_ai_not_configured_error() {
        // Avoid translation until init is complete
        if ( ! did_action( 'init' ) ) {
            return new WP_Error( 'ai_not_configured', 'AI service not configured' );
        }
        return new WP_Error( 'ai_not_configured', __( 'AI service not configured', 'multi-entity-ticket-system' ) );
    }
    
    /**
     * Categorize ticket content
     */
    public function categorize_ticket( $title, $content, $categories = array() ) {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        $prompt = $this->build_categorization_prompt( $title, $content, $categories );
        
        $response = $this->make_request( $prompt, 'gpt-3.5-turbo', 150 );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        return $this->parse_categorization_response( $response );
    }
    
    /**
     * Analyze ticket sentiment
     */
    public function analyze_sentiment( $content ) {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        $prompt = "Analyze the sentiment of this customer message and respond with only one word: positive, negative, or neutral.\n\nMessage: " . $content;
        
        $response = $this->make_request( $prompt, 'gpt-3.5-turbo', 10 );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $sentiment = strtolower( trim( $response ) );
        return in_array( $sentiment, array( 'positive', 'negative', 'neutral' ) ) ? $sentiment : 'neutral';
    }
    
    /**
     * Predict ticket priority
     */
    public function predict_priority( $title, $content ) {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        $prompt = "Based on this support ticket, determine the priority level. Respond with only one word: low, medium, high, or critical.\n\nTitle: $title\nContent: $content";
        
        $response = $this->make_request( $prompt, 'gpt-3.5-turbo', 10 );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $priority = strtolower( trim( $response ) );
        return in_array( $priority, array( 'low', 'medium', 'high', 'critical' ) ) ? $priority : 'medium';
    }
    
    /**
     * Generate suggested response
     */
    public function suggest_response( $ticket_data ) {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        $prompt = $this->build_response_prompt( $ticket_data );
        
        $response = $this->make_request( $prompt, $this->model, 500 );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Make API request
     */
    public function make_request( $prompt, $model = null, $max_tokens = null ) {
        $model = $model ?: $this->model;
        $max_tokens = $max_tokens ?: $this->max_tokens;
        
        // Build messages with master prompt and knowledge base
        $messages = $this->build_messages_with_context( $prompt );
        
        $body = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => 0.7
        );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => get_site_url(),
                'X-Title' => get_bloginfo( 'name' )
            ),
            'body' => json_encode( $body ),
            'timeout' => 30,
            'method' => 'POST'
        );
        
        $response = wp_remote_post( self::API_ENDPOINT, $args );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['error'] ) ) {
            return new WP_Error( 'openrouter_error', $data['error']['message'] ?? 'Unknown error' );
        }
        
        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'invalid_response', 'Invalid response from AI service' );
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    /**
     * Build categorization prompt
     */
    private function build_categorization_prompt( $title, $content, $categories ) {
        $cat_list = empty( $categories ) ? 'General, Technical, Billing, Account, Other' : implode( ', ', $categories );
        
        return "Categorize this support ticket into one of these categories: $cat_list\n\n" .
               "Title: $title\n" .
               "Content: $content\n\n" .
               "Respond with only the category name.";
    }
    
    /**
     * Build response prompt
     */
    private function build_response_prompt( $ticket_data ) {
        $prompt = "Generate a professional customer service response for this support ticket:\n\n";
        
        $prompt .= "TICKET DETAILS:\n";
        $prompt .= "Title: {$ticket_data['title']}\n";
        $prompt .= "Category: {$ticket_data['category']}\n";
        $prompt .= "Priority: {$ticket_data['priority']}\n";
        $prompt .= "Original Issue: {$ticket_data['content']}\n\n";
        
        // Add conversation history if available
        if ( ! empty( $ticket_data['conversation_history'] ) && is_array( $ticket_data['conversation_history'] ) ) {
            $prompt .= "CONVERSATION HISTORY:\n";
            foreach ( $ticket_data['conversation_history'] as $entry ) {
                $prompt .= "[{$entry['role']}] {$entry['user_name']} ({$entry['timestamp']}):\n";
                $prompt .= "{$entry['content']}\n\n";
            }
        }
        
        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "- Review the entire conversation history above\n";
        $prompt .= "- Provide a helpful, empathetic response that addresses the customer's most recent concern\n";
        $prompt .= "- Reference previous conversation points when relevant\n";
        $prompt .= "- Be professional, solution-focused, and concise\n";
        $prompt .= "- If this is a follow-up, acknowledge previous interactions appropriately\n";
        
        // Add custom prompt instructions if provided
        if ( ! empty( $ticket_data['custom_prompt'] ) ) {
            $prompt .= "\nADDITIONAL INSTRUCTIONS FROM AGENT:\n";
            $prompt .= "- " . str_replace( "\n", "\n- ", trim( $ticket_data['custom_prompt'] ) ) . "\n";
            $prompt .= "\nIMPORTANT: Follow the additional instructions above while maintaining professionalism and accuracy.";
        }
        
        return $prompt;
    }
    
    /**
     * Parse categorization response
     */
    private function parse_categorization_response( $response ) {
        $response = trim( $response );
        
        // Common category mappings
        $mappings = array(
            'technical' => 'technical_support',
            'billing' => 'billing_inquiry',
            'account' => 'account_management',
            'general' => 'general_inquiry',
            'other' => 'other'
        );
        
        $lower = strtolower( $response );
        
        foreach ( $mappings as $key => $value ) {
            if ( strpos( $lower, $key ) !== false ) {
                return $value;
            }
        }
        
        return 'general_inquiry';
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        $response = $this->make_request( 'Hello, please respond with "Connection successful"', 'gpt-3.5-turbo', 20 );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        return true;
    }
    
    /**
     * Get available models from OpenRouter
     */
    public function get_available_models() {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        // Check cache first
        $cached_models = get_transient( 'mets_openrouter_models' );
        if ( $cached_models !== false ) {
            return $cached_models;
        }
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'HTTP-Referer' => get_site_url(),
                'X-Title' => get_bloginfo( 'name' )
            ),
            'timeout' => 15,
            'method' => 'GET'
        );
        
        $response = wp_remote_get( self::MODELS_ENDPOINT, $args );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
            return new WP_Error( 'invalid_response', 'Invalid response from OpenRouter models API' );
        }
        
        // Process models into a more usable format
        $models = array();
        foreach ( $data['data'] as $model ) {
            if ( isset( $model['id'] ) && isset( $model['name'] ) ) {
                // Calculate approximate cost per 1K tokens
                $input_cost = isset( $model['pricing']['prompt'] ) ? floatval( $model['pricing']['prompt'] ) * 1000 : 0;
                $output_cost = isset( $model['pricing']['completion'] ) ? floatval( $model['pricing']['completion'] ) * 1000 : 0;
                $avg_cost = ( $input_cost + $output_cost ) / 2;
                
                $models[ $model['id'] ] = array(
                    'name' => $model['name'],
                    'context_length' => isset( $model['context_length'] ) ? $model['context_length'] : 'Unknown',
                    'cost_per_1k' => $avg_cost > 0 ? sprintf( '$%.4f', $avg_cost ) : 'Free',
                    'description' => isset( $model['description'] ) ? $model['description'] : '',
                    'top_provider' => isset( $model['top_provider'] ) ? $model['top_provider'] : ''
                );
            }
        }
        
        // Sort models by name
        uasort( $models, function( $a, $b ) {
            return strcasecmp( $a['name'], $b['name'] );
        } );
        
        // Cache for 24 hours
        set_transient( 'mets_openrouter_models', $models, 86400 );
        
        return $models;
    }
    
    /**
     * Clear models cache
     */
    public function clear_models_cache() {
        delete_transient( 'mets_openrouter_models' );
    }
    
    /**
     * Analyze ticket content for intelligent routing
     *
     * @param string $title Ticket title
     * @param string $content Ticket content
     * @param string $category Ticket category
     * @param string $priority Ticket priority
     * @return array|WP_Error AI analysis for routing
     */
    public function analyze_for_routing( $title, $content, $category = '', $priority = 'medium' ) {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        $prompt = $this->build_routing_analysis_prompt( $title, $content, $category, $priority );
        
        $response = $this->make_request( $prompt, 'gpt-3.5-turbo', 300 );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        return $this->parse_routing_analysis( $response );
    }
    
    /**
     * Extract relevant tags from ticket content
     *
     * @param string $title Ticket title
     * @param string $content Ticket content
     * @param string $category Ticket category
     * @return array|WP_Error Extracted tags
     */
    public function extract_tags( $title, $content, $category = '' ) {
        if ( ! $this->is_configured() ) {
            return $this->get_ai_not_configured_error();
        }
        
        $prompt = $this->build_tag_extraction_prompt( $title, $content, $category );
        
        $response = $this->make_request( $prompt, 'gpt-3.5-turbo', 200 );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        return $this->parse_tag_extraction( $response );
    }
    
    /**
     * Build routing analysis prompt
     */
    private function build_routing_analysis_prompt( $title, $content, $category, $priority ) {
        return "Analyze this support ticket for intelligent agent routing. Provide a JSON response with the following structure:

{
  \"complexity\": \"low|medium|high\",
  \"technical_level\": \"basic|intermediate|advanced\",
  \"urgency_indicators\": [\"list of urgency indicators found\"],
  \"required_skills\": [\"list of skills needed\"],
  \"estimated_effort\": \"low|medium|high\",
  \"customer_emotion\": \"calm|frustrated|angry|urgent\",
  \"related_categories\": {\"category_name\": similarity_score},
  \"special_requirements\": [\"any special handling needs\"]
}

Ticket Details:
Title: $title
Category: $category
Priority: $priority
Content: $content

Analyze the technical complexity, required expertise, customer sentiment, and any special handling requirements.";
    }
    
    /**
     * Build tag extraction prompt
     */
    private function build_tag_extraction_prompt( $title, $content, $category ) {
        return "Extract relevant tags from this support ticket content. Return a JSON array of tag names only (no descriptions).

Rules:
- Maximum 8 tags
- Tags should be 2-4 words maximum
- Focus on technical terms, product features, issue types, and key concepts
- Avoid generic words like 'help', 'issue', 'problem'
- Use lowercase with hyphens instead of spaces
- Tags should be specific and actionable

Ticket Details:
Title: $title
Category: $category
Content: $content

Return format: [\"tag1\", \"tag2\", \"tag3\"]";
    }
    
    /**
     * Parse routing analysis response
     */
    private function parse_routing_analysis( $response ) {
        $decoded = json_decode( trim( $response ), true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            // Fallback parsing if JSON fails
            return array(
                'complexity' => 'medium',
                'technical_level' => 'intermediate',
                'urgency_indicators' => array(),
                'required_skills' => array(),
                'estimated_effort' => 'medium',
                'customer_emotion' => 'calm',
                'related_categories' => array(),
                'special_requirements' => array()
            );
        }
        
        // Validate and normalize the response
        $analysis = array_merge( array(
            'complexity' => 'medium',
            'technical_level' => 'intermediate',
            'urgency_indicators' => array(),
            'required_skills' => array(),
            'estimated_effort' => 'medium',
            'customer_emotion' => 'calm',
            'related_categories' => array(),
            'special_requirements' => array()
        ), $decoded );
        
        // Validate enum values
        $valid_complexity = array( 'low', 'medium', 'high' );
        if ( ! in_array( $analysis['complexity'], $valid_complexity ) ) {
            $analysis['complexity'] = 'medium';
        }
        
        $valid_technical = array( 'basic', 'intermediate', 'advanced' );
        if ( ! in_array( $analysis['technical_level'], $valid_technical ) ) {
            $analysis['technical_level'] = 'intermediate';
        }
        
        $valid_effort = array( 'low', 'medium', 'high' );
        if ( ! in_array( $analysis['estimated_effort'], $valid_effort ) ) {
            $analysis['estimated_effort'] = 'medium';
        }
        
        $valid_emotion = array( 'calm', 'frustrated', 'angry', 'urgent' );
        if ( ! in_array( $analysis['customer_emotion'], $valid_emotion ) ) {
            $analysis['customer_emotion'] = 'calm';
        }
        
        return $analysis;
    }
    
    /**
     * Parse tag extraction response
     */
    private function parse_tag_extraction( $response ) {
        $decoded = json_decode( trim( $response ), true );
        
        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
            // Fallback: extract words from response
            $words = str_word_count( strtolower( $response ), 1 );
            $filtered_words = array_filter( $words, function( $word ) {
                return strlen( $word ) > 3 && ! in_array( $word, array( 'help', 'issue', 'problem', 'please', 'need', 'want' ) );
            } );
            return array_slice( array_unique( $filtered_words ), 0, 8 );
        }
        
        // Validate and clean tags
        $cleaned_tags = array();
        foreach ( $decoded as $tag ) {
            if ( is_string( $tag ) && strlen( $tag ) > 1 && strlen( $tag ) <= 50 ) {
                $cleaned_tags[] = strtolower( trim( $tag ) );
            }
        }
        
        return array_slice( array_unique( $cleaned_tags ), 0, 8 );
    }
    
    /**
     * Build messages array with master prompt and knowledge base context
     *
     * @param string $user_prompt The actual user prompt
     * @return array Messages array for OpenRouter API
     */
    private function build_messages_with_context( $user_prompt ) {
        $messages = array();
        
        // Get master prompt and knowledge base
        $master_prompt = get_option( 'mets_ai_master_prompt', '' );
        $knowledge_base = get_option( 'mets_ai_knowledge_base', '' );
        
        // Build system message with context
        $system_content = '';
        
        // Add master prompt if configured
        if ( ! empty( $master_prompt ) ) {
            $system_content .= $master_prompt . "\n\n";
        }
        
        // Add knowledge base if configured
        if ( ! empty( $knowledge_base ) ) {
            $system_content .= "KNOWLEDGE BASE:\n" . $knowledge_base . "\n\n";
        }
        
        // Add default instructions if no master prompt
        if ( empty( $master_prompt ) ) {
            $system_content .= "You are a professional customer support AI assistant. Be helpful, accurate, and concise in your responses.\n\n";
        }
        
        $system_content .= "Current task instructions:\n";
        
        // Add system message if we have context
        if ( ! empty( trim( $system_content ) ) ) {
            $messages[] = array(
                'role' => 'system',
                'content' => trim( $system_content )
            );
        }
        
        // Add user message
        $messages[] = array(
            'role' => 'user',
            'content' => $user_prompt
        );
        
        return $messages;
    }
    
    /**
     * Get effective system context for display/debugging
     *
     * @return array Context information
     */
    public function get_system_context() {
        $master_prompt = get_option( 'mets_ai_master_prompt', '' );
        $knowledge_base = get_option( 'mets_ai_knowledge_base', '' );
        
        return array(
            'has_master_prompt' => ! empty( $master_prompt ),
            'master_prompt_length' => strlen( $master_prompt ),
            'has_knowledge_base' => ! empty( $knowledge_base ),
            'knowledge_base_length' => strlen( $knowledge_base ),
            'total_context_length' => strlen( $master_prompt . $knowledge_base )
        );
    }

}