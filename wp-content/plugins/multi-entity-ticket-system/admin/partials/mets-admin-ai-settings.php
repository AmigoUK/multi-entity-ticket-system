<?php
/**
 * AI Settings Page
 *
 * @package METS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current settings
$api_key = get_option( 'mets_openrouter_api_key', '' );
$model = get_option( 'mets_openrouter_model', 'openai/gpt-3.5-turbo' );
$max_tokens = get_option( 'mets_openrouter_max_tokens', 5000 );
$ai_features_enabled = get_option( 'mets_ai_features_enabled', array() );
$master_prompt = get_option( 'mets_ai_master_prompt', '' );
$knowledge_base = get_option( 'mets_ai_knowledge_base', '' );

// Get available models from OpenRouter
$ai_service = METS_AI_Service::get_instance();
$available_models = array();
$models_error = null;

if ( ! empty( $api_key ) ) {
    $models_result = $ai_service->get_available_models();
    if ( is_wp_error( $models_result ) ) {
        $models_error = $models_result->get_error_message();
        // Fallback to default models
        $available_models = array(
            'openai/gpt-3.5-turbo' => array(
                'name' => 'GPT-3.5 Turbo',
                'context_length' => '4K',
                'cost_per_1k' => '$0.0010',
                'description' => 'Fast and economical'
            ),
            'openai/gpt-4-turbo-preview' => array(
                'name' => 'GPT-4 Turbo',
                'context_length' => '128K',
                'cost_per_1k' => '$0.0300',
                'description' => 'Most capable model'
            )
        );
    } else {
        $available_models = $models_result;
    }
} else {
    // Show placeholder when no API key
    $available_models = array(
        '' => array(
            'name' => __( 'Enter API key to load available models', 'multi-entity-ticket-system' ),
            'context_length' => '',
            'cost_per_1k' => '',
            'description' => ''
        )
    );
}

// Available AI features
$ai_features = array(
    'auto_categorize' => __( 'Automatic Ticket Categorization', 'multi-entity-ticket-system' ),
    'priority_prediction' => __( 'Priority Level Prediction', 'multi-entity-ticket-system' ),
    'sentiment_analysis' => __( 'Customer Sentiment Analysis', 'multi-entity-ticket-system' ),
    'suggested_responses' => __( 'AI-Suggested Responses', 'multi-entity-ticket-system' ),
    'smart_routing' => __( 'Intelligent Ticket Routing', 'multi-entity-ticket-system' ),
    'auto_tagging' => __( 'Automatic Tag Generation', 'multi-entity-ticket-system' )
);

// Test connection if requested
$test_result = null;
if ( isset( $_POST['test_connection'] ) && check_admin_referer( 'mets_ai_settings' ) ) {
    $ai_service = METS_AI_Service::get_instance();
    $test = $ai_service->test_connection();
    if ( is_wp_error( $test ) ) {
        $test_result = array( 'success' => false, 'message' => $test->get_error_message() );
    } else {
        $test_result = array( 'success' => true, 'message' => __( 'Connection successful! AI service is ready.', 'multi-entity-ticket-system' ) );
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php if ( $test_result ) : ?>
        <div class="notice notice-<?php echo $test_result['success'] ? 'success' : 'error'; ?> is-dismissible">
            <p><?php echo esc_html( $test_result['message'] ); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php">
        <?php settings_fields( 'mets_ai_settings_group' ); ?>
        
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="mets_openrouter_api_key"><?php _e( 'OpenRouter API Key', 'multi-entity-ticket-system' ); ?></label>
                </th>
                <td>
                    <input type="password" 
                           id="mets_openrouter_api_key" 
                           name="mets_openrouter_api_key" 
                           value="<?php echo esc_attr( $api_key ); ?>" 
                           class="regular-text" 
                           placeholder="sk-or-v1-..." />
                    <p class="description">
                        <?php _e( 'Get your API key from', 'multi-entity-ticket-system' ); ?> 
                        <a href="https://openrouter.ai/keys" target="_blank">OpenRouter.ai</a>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="mets_openrouter_model"><?php _e( 'AI Model', 'multi-entity-ticket-system' ); ?></label>
                </th>
                <td>
                    <?php if ( $models_error ) : ?>
                        <div class="notice notice-warning inline">
                            <p><?php echo esc_html( $models_error ); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <select id="mets_openrouter_model" name="mets_openrouter_model" style="width: 100%; max-width: 600px;">
                        <?php foreach ( $available_models as $model_id => $model_info ) : ?>
                            <option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $model, $model_id ); ?>>
                                <?php 
                                if ( is_array( $model_info ) ) {
                                    echo esc_html( $model_info['name'] );
                                    if ( ! empty( $model_info['context_length'] ) ) {
                                        echo ' - ' . esc_html( $model_info['context_length'] ) . ' context';
                                    }
                                    if ( ! empty( $model_info['cost_per_1k'] ) ) {
                                        echo ' - ' . esc_html( $model_info['cost_per_1k'] ) . '/1K tokens';
                                    }
                                } else {
                                    echo esc_html( $model_info );
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if ( ! empty( $api_key ) && empty( $models_error ) ) : ?>
                        <button type="button" id="refresh-models" class="button button-small" style="margin-left: 10px;">
                            <?php _e( 'Refresh Models', 'multi-entity-ticket-system' ); ?>
                        </button>
                    <?php endif; ?>
                    
                    <p class="description">
                        <?php _e( 'Models are fetched directly from OpenRouter. Choose based on your needs for speed, quality, and cost.', 'multi-entity-ticket-system' ); ?>
                        <?php if ( empty( $api_key ) ) : ?>
                            <br><strong><?php _e( 'Save your API key first to see available models.', 'multi-entity-ticket-system' ); ?></strong>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="mets_openrouter_max_tokens"><?php _e( 'Max Response Length', 'multi-entity-ticket-system' ); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="mets_openrouter_max_tokens" 
                           name="mets_openrouter_max_tokens" 
                           value="<?php echo esc_attr( $max_tokens ); ?>" 
                           min="50" 
                           max="4000" 
                           class="small-text" />
                    <span><?php _e( 'tokens', 'multi-entity-ticket-system' ); ?></span>
                    <p class="description">
                        <?php _e( 'Maximum tokens for AI responses (1 token ≈ 4 characters).', 'multi-entity-ticket-system' ); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="mets_ai_master_prompt"><?php _e( 'Master Prompt', 'multi-entity-ticket-system' ); ?></label>
                </th>
                <td>
                    <textarea id="mets_ai_master_prompt" 
                              name="mets_ai_master_prompt" 
                              rows="8" 
                              cols="80" 
                              class="large-text code"
                              placeholder="You are a professional customer support assistant for [Your Company Name]. You help customers with questions about our products and services.

Be helpful, empathetic, and solution-focused in all interactions. Always maintain a friendly but professional tone. 

When you cannot resolve an issue directly:
- Acknowledge the customer's concern
- Explain what steps you're taking to help
- Provide clear next steps or escalation information

Never make promises about refunds, account changes, or technical modifications without proper authorization."><?php echo esc_textarea( $master_prompt ); ?></textarea>
                    <p class="description">
                        <?php _e( 'Global instructions that will be prepended to all AI requests. Define the AI\'s role, tone, and behavior here.', 'multi-entity-ticket-system' ); ?>
                        <br><strong><?php _e( 'Example:', 'multi-entity-ticket-system' ); ?></strong> 
                        <?php _e( '"You are a professional customer support assistant for [Company Name]. Always be helpful, empathetic, and solution-focused. Provide clear, concise responses."', 'multi-entity-ticket-system' ); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="mets_ai_knowledge_base"><?php _e( 'Knowledge Base', 'multi-entity-ticket-system' ); ?></label>
                </th>
                <td>
                    <textarea id="mets_ai_knowledge_base" 
                              name="mets_ai_knowledge_base" 
                              rows="12" 
                              cols="80" 
                              class="large-text code"
                              placeholder="COMPANY INFORMATION:
- Business Name: [Your Company]
- Website: [your-website.com]
- Support Hours: Monday-Friday 9AM-5PM EST
- Support Email: support@yourcompany.com

PRODUCTS & SERVICES:
- [Product 1]: Description, pricing, key features
- [Product 2]: Description, pricing, key features

COMMON ISSUES & SOLUTIONS:
- Login Problems: Check email/password, try password reset
- Payment Issues: Verify card details, check for expired cards
- Technical Support: Clear browser cache, try different browser

POLICIES:
- Return Policy: 30-day returns for unused items
- Refund Policy: Processed within 5-7 business days
- Shipping: Free shipping on orders over $50

ESCALATION:
- Technical issues requiring developer: Escalate to Tech Support
- Billing disputes over $100: Escalate to Manager
- Legal issues: Forward to Legal Department"><?php echo esc_textarea( $knowledge_base ); ?></textarea>
                    <p class="description">
                        <?php _e( 'Contextual information about your business, products, policies, and procedures. This helps AI provide more accurate and relevant responses.', 'multi-entity-ticket-system' ); ?>
                        <br><strong><?php _e( 'Include:', 'multi-entity-ticket-system' ); ?></strong> 
                        <?php _e( 'Company policies, product details, common solutions, escalation procedures, contact information, etc.', 'multi-entity-ticket-system' ); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e( 'AI Features', 'multi-entity-ticket-system' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e( 'AI Features', 'multi-entity-ticket-system' ); ?></span>
                        </legend>
                        <?php foreach ( $ai_features as $feature_id => $feature_name ) : ?>
                            <label>
                                <input type="checkbox" 
                                       name="mets_ai_features_enabled[]" 
                                       value="<?php echo esc_attr( $feature_id ); ?>"
                                       <?php checked( in_array( $feature_id, $ai_features_enabled ) ); ?> />
                                <?php echo esc_html( $feature_name ); ?>
                            </label><br />
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description">
                        <?php _e( 'Select which AI-powered features to enable.', 'multi-entity-ticket-system' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <?php if ( ! empty( $api_key ) ) : ?>
        <hr />
        <h2><?php _e( 'Test Connection', 'multi-entity-ticket-system' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'mets_ai_settings' ); ?>
            <p>
                <button type="submit" name="test_connection" class="button button-secondary">
                    <?php _e( 'Test AI Connection', 'multi-entity-ticket-system' ); ?>
                </button>
            </p>
        </form>
    <?php endif; ?>
    
    <?php if ( ! empty( $api_key ) ) : ?>
        <hr />
        <h2><?php _e( 'Context Status', 'multi-entity-ticket-system' ); ?></h2>
        <?php 
        $context = $ai_service->get_system_context();
        ?>
        <div class="mets-context-status">
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php _e( 'Master Prompt:', 'multi-entity-ticket-system' ); ?></strong></td>
                        <td>
                            <?php if ( $context['has_master_prompt'] ) : ?>
                                <span style="color: #00a32a;">✓ <?php echo sprintf( __( 'Configured (%d characters)', 'multi-entity-ticket-system' ), $context['master_prompt_length'] ); ?></span>
                            <?php else : ?>
                                <span style="color: #d63638;">✗ <?php _e( 'Not configured - using default instructions', 'multi-entity-ticket-system' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e( 'Knowledge Base:', 'multi-entity-ticket-system' ); ?></strong></td>
                        <td>
                            <?php if ( $context['has_knowledge_base'] ) : ?>
                                <span style="color: #00a32a;">✓ <?php echo sprintf( __( 'Configured (%d characters)', 'multi-entity-ticket-system' ), $context['knowledge_base_length'] ); ?></span>
                            <?php else : ?>
                                <span style="color: #f0b849;">⚠ <?php _e( 'Not configured - AI will use general knowledge only', 'multi-entity-ticket-system' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e( 'Total Context Size:', 'multi-entity-ticket-system' ); ?></strong></td>
                        <td>
                            <?php 
                            $total_length = $context['total_context_length'];
                            $estimated_tokens = round( $total_length / 4 ); // Rough estimate: 1 token ≈ 4 characters
                            echo sprintf( __( '%d characters (~%d tokens)', 'multi-entity-ticket-system' ), $total_length, $estimated_tokens );
                            
                            if ( $estimated_tokens > 2000 ) {
                                echo ' <span style="color: #d63638;">⚠ ' . __( 'Very large context - may increase costs', 'multi-entity-ticket-system' ) . '</span>';
                            } elseif ( $estimated_tokens > 1000 ) {
                                echo ' <span style="color: #f0b849;">⚠ ' . __( 'Large context - monitor costs', 'multi-entity-ticket-system' ) . '</span>';
                            } else {
                                echo ' <span style="color: #00a32a;">✓ ' . __( 'Reasonable size', 'multi-entity-ticket-system' ) . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <hr />
    <h2><?php _e( 'Usage Guide', 'multi-entity-ticket-system' ); ?></h2>
    <div class="mets-ai-guide">
        <h3><?php _e( 'Getting Started', 'multi-entity-ticket-system' ); ?></h3>
        <ol>
            <li><?php _e( 'Sign up at OpenRouter.ai', 'multi-entity-ticket-system' ); ?></li>
            <li><?php _e( 'Generate an API key', 'multi-entity-ticket-system' ); ?></li>
            <li><?php _e( 'Add credits to your account', 'multi-entity-ticket-system' ); ?></li>
            <li><?php _e( 'Enter your API key above', 'multi-entity-ticket-system' ); ?></li>
            <li><?php _e( 'Configure Master Prompt and Knowledge Base (optional but recommended)', 'multi-entity-ticket-system' ); ?></li>
            <li><?php _e( 'Select desired AI features', 'multi-entity-ticket-system' ); ?></li>
            <li><?php _e( 'Save settings and test connection', 'multi-entity-ticket-system' ); ?></li>
        </ol>
        
        <h3><?php _e( 'Master Prompt Best Practices', 'multi-entity-ticket-system' ); ?></h3>
        <ul>
            <li><strong><?php _e( 'Define AI Role:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'Specify that the AI is a customer support assistant for your company', 'multi-entity-ticket-system' ); ?></li>
            <li><strong><?php _e( 'Set Tone:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'Specify whether to be formal, friendly, professional, empathetic, etc.', 'multi-entity-ticket-system' ); ?></li>
            <li><strong><?php _e( 'Response Style:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'Request concise, detailed, or structured responses as needed', 'multi-entity-ticket-system' ); ?></li>
            <li><strong><?php _e( 'Limitations:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'Specify what the AI should not do (e.g., make refunds, access accounts)', 'multi-entity-ticket-system' ); ?></li>
        </ul>
        
        <h3><?php _e( 'Knowledge Base Content Ideas', 'multi-entity-ticket-system' ); ?></h3>
        <ul>
            <li><strong><?php _e( 'Company Info:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'Business hours, contact information, location, team structure', 'multi-entity-ticket-system' ); ?></li>
            <li><strong><?php _e( 'Products/Services:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'Feature descriptions, pricing, availability, technical specifications', 'multi-entity-ticket-system' ); ?></li>
            <li><strong><?php _e( 'Policies:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'Return policy, terms of service, privacy policy, warranty information', 'multi-entity-ticket-system' ); ?></li>
            <li><strong><?php _e( 'Common Solutions:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'FAQ answers, troubleshooting steps, known issues and fixes', 'multi-entity-ticket-system' ); ?></li>
            <li><strong><?php _e( 'Escalation Rules:', 'multi-entity-ticket-system' ); ?></strong> <?php _e( 'When to escalate tickets, who to escalate to, contact procedures', 'multi-entity-ticket-system' ); ?></li>
        </ul>
        
        <h3><?php _e( 'Model Recommendations', 'multi-entity-ticket-system' ); ?></h3>
        <div id="model-recommendations">
            <?php 
            $selected_model_info = isset( $available_models[$model] ) ? $available_models[$model] : null;
            if ( $selected_model_info && is_array( $selected_model_info ) ) : 
            ?>
                <p><strong><?php _e( 'Currently Selected:', 'multi-entity-ticket-system' ); ?></strong> <?php echo esc_html( $selected_model_info['name'] ); ?></p>
                <?php if ( ! empty( $selected_model_info['description'] ) ) : ?>
                    <p><?php echo esc_html( $selected_model_info['description'] ); ?></p>
                <?php endif; ?>
                <ul>
                    <li><?php _e( 'Context Length:', 'multi-entity-ticket-system' ); ?> <?php echo esc_html( $selected_model_info['context_length'] ); ?></li>
                    <li><?php _e( 'Price per 1K tokens:', 'multi-entity-ticket-system' ); ?> <?php echo esc_html( $selected_model_info['cost_per_1k'] ); ?></li>
                </ul>
            <?php else : ?>
                <p><?php _e( 'Select a model above to see recommendations.', 'multi-entity-ticket-system' ); ?></p>
            <?php endif; ?>
        </div>
        
        <h3><?php _e( 'Cost Estimates', 'multi-entity-ticket-system' ); ?></h3>
        <div id="cost-estimates">
            <?php if ( $selected_model_info && is_array( $selected_model_info ) && ! empty( $selected_model_info['cost_per_1k'] ) ) : 
                // Extract numeric value from cost string
                $cost_match = preg_match( '/\$?([\d.]+)/', $selected_model_info['cost_per_1k'], $matches );
                $cost_per_1k = $cost_match ? floatval( $matches[1] ) : 0;
                
                if ( $cost_per_1k > 0 ) :
                    // Calculate estimates (assuming avg 500 tokens per ticket interaction)
                    $tokens_per_ticket = 500;
                    $cost_per_ticket = ( $tokens_per_ticket / 1000 ) * $cost_per_1k;
                    
                    $cost_100_tickets = $cost_per_ticket * 100;
                    $cost_1000_tickets = $cost_per_ticket * 1000;
                    $cost_10000_tickets = $cost_per_ticket * 10000;
            ?>
                <p><?php _e( 'Estimated costs for', 'multi-entity-ticket-system' ); ?> <strong><?php echo esc_html( $selected_model_info['name'] ); ?></strong>:</p>
                <ul>
                    <li><?php _e( '100 tickets/month:', 'multi-entity-ticket-system' ); ?> ~$<?php echo number_format( $cost_100_tickets, 2 ); ?></li>
                    <li><?php _e( '1,000 tickets/month:', 'multi-entity-ticket-system' ); ?> ~$<?php echo number_format( $cost_1000_tickets, 2 ); ?></li>
                    <li><?php _e( '10,000 tickets/month:', 'multi-entity-ticket-system' ); ?> ~$<?php echo number_format( $cost_10000_tickets, 2 ); ?></li>
                </ul>
                <p class="description">
                    <?php _e( '* Estimates based on ~500 tokens per ticket (question + response). Actual costs may vary.', 'multi-entity-ticket-system' ); ?>
                </p>
            <?php else : ?>
                <p><?php _e( 'This model appears to be free or has custom pricing.', 'multi-entity-ticket-system' ); ?></p>
            <?php endif; ?>
            <?php else : ?>
                <p><?php _e( 'Select a model above to see cost estimates.', 'multi-entity-ticket-system' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .mets-ai-guide {
            background: #f0f0f1;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .mets-ai-guide h3 {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .mets-ai-guide ul, .mets-ai-guide ol {
            margin-left: 20px;
        }
        .mets-ai-guide li {
            margin-bottom: 5px;
        }
        .notice.inline {
            margin: 0 0 10px 0;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Store model data for dynamic updates
        var modelData = <?php echo json_encode( $available_models ); ?>;
        
        // Update recommendations and cost estimates when model changes
        $('#mets_openrouter_model').on('change', function() {
            var selectedModel = $(this).val();
            var modelInfo = modelData[selectedModel];
            
            if (modelInfo && typeof modelInfo === 'object') {
                // Update recommendations
                var recHtml = '<p><strong><?php _e( 'Currently Selected:', 'multi-entity-ticket-system' ); ?></strong> ' + modelInfo.name + '</p>';
                if (modelInfo.description) {
                    recHtml += '<p>' + modelInfo.description + '</p>';
                }
                recHtml += '<ul>';
                recHtml += '<li><?php _e( 'Context Length:', 'multi-entity-ticket-system' ); ?> ' + modelInfo.context_length + '</li>';
                recHtml += '<li><?php _e( 'Price per 1K tokens:', 'multi-entity-ticket-system' ); ?> ' + modelInfo.cost_per_1k + '</li>';
                recHtml += '</ul>';
                $('#model-recommendations').html(recHtml);
                
                // Update cost estimates
                var costMatch = modelInfo.cost_per_1k.match(/\$?([\d.]+)/);
                if (costMatch) {
                    var costPer1k = parseFloat(costMatch[1]);
                    if (costPer1k > 0) {
                        var tokensPerTicket = 500;
                        var costPerTicket = (tokensPerTicket / 1000) * costPer1k;
                        
                        var costHtml = '<p><?php _e( 'Estimated costs for', 'multi-entity-ticket-system' ); ?> <strong>' + modelInfo.name + '</strong>:</p>';
                        costHtml += '<ul>';
                        costHtml += '<li><?php _e( '100 tickets/month:', 'multi-entity-ticket-system' ); ?> ~$' + (costPerTicket * 100).toFixed(2) + '</li>';
                        costHtml += '<li><?php _e( '1,000 tickets/month:', 'multi-entity-ticket-system' ); ?> ~$' + (costPerTicket * 1000).toFixed(2) + '</li>';
                        costHtml += '<li><?php _e( '10,000 tickets/month:', 'multi-entity-ticket-system' ); ?> ~$' + (costPerTicket * 10000).toFixed(2) + '</li>';
                        costHtml += '</ul>';
                        costHtml += '<p class="description"><?php _e( '* Estimates based on ~500 tokens per ticket (question + response). Actual costs may vary.', 'multi-entity-ticket-system' ); ?></p>';
                        $('#cost-estimates').html(costHtml);
                    } else {
                        $('#cost-estimates').html('<p><?php _e( 'This model appears to be free or has custom pricing.', 'multi-entity-ticket-system' ); ?></p>');
                    }
                } else {
                    $('#cost-estimates').html('<p><?php _e( 'Unable to calculate cost estimates for this model.', 'multi-entity-ticket-system' ); ?></p>');
                }
            }
        });
        
        // Refresh models button
        $('#refresh-models').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            
            button.prop('disabled', true).text('<?php _e( 'Loading...', 'multi-entity-ticket-system' ); ?>');
            
            // Clear cache and reload page
            $.post(ajaxurl, {
                action: 'mets_clear_models_cache',
                nonce: '<?php echo wp_create_nonce( 'mets_clear_models_cache' ); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    button.prop('disabled', false).text(originalText);
                    alert('<?php _e( 'Failed to refresh models. Please try again.', 'multi-entity-ticket-system' ); ?>');
                }
            });
        });
    });
    </script>
</div>