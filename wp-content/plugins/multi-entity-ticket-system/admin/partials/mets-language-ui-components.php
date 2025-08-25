<?php
/**
 * METS Language UI Components
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Language UI components for forms and interfaces
 */
class METS_Language_UI_Components {
    
    /**
     * Language manager instance
     */
    private $language_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->language_manager = METS_Language_Manager::get_instance();
    }
    
    /**
     * Render language selector dropdown
     */
    public function render_language_selector($args = array()) {
        $defaults = array(
            'name' => 'language',
            'id' => 'language-selector',
            'class' => 'mets-language-selector',
            'selected' => $this->language_manager->get_user_language(),
            'show_flags' => true,
            'show_native_names' => true,
            'include_auto_detect' => false,
            'required' => false,
            'onchange' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        $languages = $this->language_manager->get_supported_languages();
        
        ?>
        <select name="<?php echo esc_attr($args['name']); ?>" 
                id="<?php echo esc_attr($args['id']); ?>" 
                class="<?php echo esc_attr($args['class']); ?>"
                <?php echo $args['required'] ? 'required' : ''; ?>
                <?php echo !empty($args['onchange']) ? 'onchange="' . esc_attr($args['onchange']) . '"' : ''; ?>>
            
            <?php if ($args['include_auto_detect']) : ?>
                <option value="auto" <?php selected($args['selected'], 'auto'); ?>>
                    <?php _e('Auto-detect', 'multi-entity-ticket-system'); ?>
                </option>
            <?php endif; ?>
            
            <?php foreach ($languages as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" 
                        <?php selected($args['selected'], $code); ?>
                        data-rtl="<?php echo $this->language_manager->is_rtl_language($code) ? '1' : '0'; ?>">
                    <?php 
                    if ($args['show_flags']) {
                        echo $this->get_flag_emoji($code) . ' ';
                    }
                    echo esc_html($name);
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <style>
            .mets-language-selector {
                min-width: 200px;
            }
            .mets-language-selector option {
                padding: 5px;
            }
        </style>
        <?php
    }
    
    /**
     * Render language switcher widget
     */
    public function render_language_switcher($args = array()) {
        $defaults = array(
            'style' => 'dropdown', // dropdown, buttons, flags
            'show_current_only' => false,
            'redirect_current' => true,
            'class' => 'mets-language-switcher'
        );
        
        $args = wp_parse_args($args, $defaults);
        $current_language = $this->language_manager->get_user_language();
        $languages = $this->language_manager->get_supported_languages();
        
        ?>
        <div class="<?php echo esc_attr($args['class']); ?>" data-style="<?php echo esc_attr($args['style']); ?>">
            <?php if ($args['style'] === 'dropdown') : ?>
                <?php $this->render_switcher_dropdown($languages, $current_language, $args); ?>
            <?php elseif ($args['style'] === 'buttons') : ?>
                <?php $this->render_switcher_buttons($languages, $current_language, $args); ?>
            <?php elseif ($args['style'] === 'flags') : ?>
                <?php $this->render_switcher_flags($languages, $current_language, $args); ?>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.mets-language-switcher').on('change', 'select', function() {
                var selectedLang = $(this).val();
                metsLanguageSwitcher.switchLanguage(selectedLang);
            });
            
            $('.mets-language-switcher').on('click', '.lang-button, .lang-flag', function(e) {
                e.preventDefault();
                var selectedLang = $(this).data('language');
                metsLanguageSwitcher.switchLanguage(selectedLang);
            });
        });
        
        var metsLanguageSwitcher = {
            switchLanguage: function(language) {
                $.post(ajaxurl, {
                    action: 'mets_switch_language',
                    language: language,
                    nonce: '<?php echo wp_create_nonce('mets_switch_language'); ?>',
                    redirect_url: <?php echo json_encode($_SERVER['REQUEST_URI']); ?>
                }, function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        console.error('Language switch failed:', response.data);
                    }
                });
            }
        };
        </script>
        
        <style>
            .mets-language-switcher[data-style="buttons"] .lang-button {
                display: inline-block;
                padding: 5px 10px;
                margin: 2px;
                border: 1px solid #ddd;
                border-radius: 3px;
                text-decoration: none;
                color: #333;
                background: #f9f9f9;
                transition: all 0.3s;
            }
            
            .mets-language-switcher[data-style="buttons"] .lang-button:hover,
            .mets-language-switcher[data-style="buttons"] .lang-button.current {
                background: #0073aa;
                color: white;
                border-color: #0073aa;
            }
            
            .mets-language-switcher[data-style="flags"] .lang-flag {
                display: inline-block;
                font-size: 24px;
                margin: 0 5px;
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.3s;
                text-decoration: none;
            }
            
            .mets-language-switcher[data-style="flags"] .lang-flag:hover,
            .mets-language-switcher[data-style="flags"] .lang-flag.current {
                opacity: 1;
            }
        </style>
        <?php
    }
    
    /**
     * Render dropdown style switcher
     */
    private function render_switcher_dropdown($languages, $current_language, $args) {
        ?>
        <select onchange="this.form.submit()">
            <?php foreach ($languages as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($current_language, $code); ?>>
                    <?php echo $this->get_flag_emoji($code) . ' ' . esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render button style switcher
     */
    private function render_switcher_buttons($languages, $current_language, $args) {
        foreach ($languages as $code => $name) : ?>
            <a href="#" class="lang-button <?php echo $code === $current_language ? 'current' : ''; ?>" 
               data-language="<?php echo esc_attr($code); ?>">
                <?php echo $this->get_flag_emoji($code) . ' ' . esc_html($name); ?>
            </a>
        <?php endforeach;
    }
    
    /**
     * Render flag style switcher
     */
    private function render_switcher_flags($languages, $current_language, $args) {
        foreach ($languages as $code => $name) : ?>
            <a href="#" class="lang-flag <?php echo $code === $current_language ? 'current' : ''; ?>" 
               data-language="<?php echo esc_attr($code); ?>" 
               title="<?php echo esc_attr($name); ?>">
                <?php echo $this->get_flag_emoji($code); ?>
            </a>
        <?php endforeach;
    }
    
    /**
     * Render language indicator
     */
    public function render_language_indicator($language, $args = array()) {
        $defaults = array(
            'show_flag' => true,
            'show_name' => true,
            'show_code' => false,
            'format' => 'inline', // inline, badge, pill
            'class' => 'mets-language-indicator'
        );
        
        $args = wp_parse_args($args, $defaults);
        $language_name = $this->language_manager->get_language_name($language);
        $is_rtl = $this->language_manager->is_rtl_language($language);
        
        $classes = array($args['class'], 'format-' . $args['format']);
        if ($is_rtl) {
            $classes[] = 'rtl-language';
        }
        
        ?>
        <span class="<?php echo esc_attr(implode(' ', $classes)); ?>" 
              data-language="<?php echo esc_attr($language); ?>"
              <?php echo $is_rtl ? 'dir="rtl"' : ''; ?>>
            <?php if ($args['show_flag']) : ?>
                <span class="flag"><?php echo $this->get_flag_emoji($language); ?></span>
            <?php endif; ?>
            
            <?php if ($args['show_name']) : ?>
                <span class="name"><?php echo esc_html($language_name); ?></span>
            <?php endif; ?>
            
            <?php if ($args['show_code']) : ?>
                <span class="code"><?php echo esc_html($language); ?></span>
            <?php endif; ?>
        </span>
        
        <style>
            .mets-language-indicator {
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .mets-language-indicator.format-badge {
                background: #f0f0f1;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 500;
            }
            
            .mets-language-indicator.format-pill {
                background: #0073aa;
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
            }
            
            .mets-language-indicator .flag {
                font-size: 14px;
            }
            
            .mets-language-indicator .code {
                opacity: 0.7;
                font-size: 10px;
                text-transform: uppercase;
            }
        </style>
        <?php
    }
    
    /**
     * Render translation interface
     */
    public function render_translation_interface($object_type, $object_id, $field_name, $original_value) {
        $languages = $this->language_manager->get_supported_languages();
        $current_language = $this->language_manager->get_user_language();
        
        ?>
        <div class="mets-translation-interface" 
             data-object-type="<?php echo esc_attr($object_type); ?>"
             data-object-id="<?php echo esc_attr($object_id); ?>"
             data-field-name="<?php echo esc_attr($field_name); ?>">
            
            <div class="translation-tabs">
                <?php foreach ($languages as $code => $name) : ?>
                    <?php if ($code === $current_language) continue; ?>
                    <button type="button" class="translation-tab" 
                            data-language="<?php echo esc_attr($code); ?>">
                        <?php echo $this->get_flag_emoji($code) . ' ' . esc_html($name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="translation-panels">
                <div class="original-content">
                    <h4><?php _e('Original', 'multi-entity-ticket-system'); ?> (<?php echo $this->get_flag_emoji($current_language) . ' ' . $this->language_manager->get_language_name($current_language); ?>)</h4>
                    <div class="original-text"><?php echo esc_html($original_value); ?></div>
                </div>
                
                <?php foreach ($languages as $code => $name) : ?>
                    <?php if ($code === $current_language) continue; ?>
                    
                    <?php 
                    $existing_translation = $this->language_manager->get_translation($object_type, $object_id, $field_name, $code);
                    ?>
                    
                    <div class="translation-panel" data-language="<?php echo esc_attr($code); ?>" style="display: none;">
                        <h4><?php echo $this->get_flag_emoji($code) . ' ' . esc_html($name); ?></h4>
                        <textarea class="translation-input" 
                                  rows="4" 
                                  placeholder="<?php esc_attr_e('Enter translation...', 'multi-entity-ticket-system'); ?>"><?php echo esc_textarea($existing_translation); ?></textarea>
                        <div class="translation-actions">
                            <button type="button" class="button button-secondary auto-translate-btn">
                                <?php _e('Auto-translate', 'multi-entity-ticket-system'); ?>
                            </button>
                            <button type="button" class="button button-primary save-translation-btn">
                                <?php _e('Save Translation', 'multi-entity-ticket-system'); ?>
                            </button>
                            <span class="translation-status"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.translation-tab').on('click', function() {
                var language = $(this).data('language');
                var $interface = $(this).closest('.mets-translation-interface');
                
                // Update tabs
                $('.translation-tab', $interface).removeClass('active');
                $(this).addClass('active');
                
                // Show panel
                $('.translation-panel', $interface).hide();
                $('.translation-panel[data-language="' + language + '"]', $interface).show();
            });
            
            $('.auto-translate-btn').on('click', function() {
                var $panel = $(this).closest('.translation-panel');
                var $interface = $panel.closest('.mets-translation-interface');
                var language = $panel.data('language');
                var originalText = $('.original-text', $interface).text();
                var $textarea = $('.translation-input', $panel);
                var $status = $('.translation-status', $panel);
                
                $(this).prop('disabled', true).text('<?php _e('Translating...', 'multi-entity-ticket-system'); ?>');
                $status.html('<span style="color: #666;"><?php _e('Translating...', 'multi-entity-ticket-system'); ?></span>');
                
                $.post(ajaxurl, {
                    action: 'mets_translate_content',
                    content: originalText,
                    target_language: language,
                    nonce: '<?php echo wp_create_nonce('mets_translate_content'); ?>'
                }, function(response) {
                    if (response.success) {
                        $textarea.val(response.data.translation);
                        $status.html('<span style="color: #46b450;"><?php _e('Translation generated', 'multi-entity-ticket-system'); ?></span>');
                    } else {
                        $status.html('<span style="color: #dc3232;"><?php _e('Translation failed', 'multi-entity-ticket-system'); ?>: ' + response.data + '</span>');
                    }
                }).always(function() {
                    $('.auto-translate-btn', $panel).prop('disabled', false).text('<?php _e('Auto-translate', 'multi-entity-ticket-system'); ?>');
                });
            });
            
            $('.save-translation-btn').on('click', function() {
                var $panel = $(this).closest('.translation-panel');
                var $interface = $panel.closest('.mets-translation-interface');
                var language = $panel.data('language');
                var translation = $('.translation-input', $panel).val();
                var $status = $('.translation-status', $panel);
                
                if (!translation.trim()) {
                    $status.html('<span style="color: #dc3232;"><?php _e('Translation cannot be empty', 'multi-entity-ticket-system'); ?></span>');
                    return;
                }
                
                $(this).prop('disabled', true).text('<?php _e('Saving...', 'multi-entity-ticket-system'); ?>');
                
                $.post(ajaxurl, {
                    action: 'mets_save_translation',
                    object_type: $interface.data('object-type'),
                    object_id: $interface.data('object-id'),
                    field_name: $interface.data('field-name'),
                    language: language,
                    translation: translation,
                    nonce: '<?php echo wp_create_nonce('mets_save_translation'); ?>'
                }, function(response) {
                    if (response.success) {
                        $status.html('<span style="color: #46b450;"><?php _e('Translation saved', 'multi-entity-ticket-system'); ?></span>');
                    } else {
                        $status.html('<span style="color: #dc3232;"><?php _e('Save failed', 'multi-entity-ticket-system'); ?>: ' + response.data + '</span>');
                    }
                }).always(function() {
                    $('.save-translation-btn', $panel).prop('disabled', false).text('<?php _e('Save Translation', 'multi-entity-ticket-system'); ?>');
                });
            });
        });
        </script>
        
        <style>
            .mets-translation-interface {
                border: 1px solid #ddd;
                border-radius: 5px;
                margin: 10px 0;
            }
            
            .translation-tabs {
                display: flex;
                flex-wrap: wrap;
                border-bottom: 1px solid #ddd;
                background: #f9f9f9;
            }
            
            .translation-tab {
                padding: 8px 12px;
                border: none;
                background: none;
                cursor: pointer;
                border-bottom: 2px solid transparent;
            }
            
            .translation-tab:hover {
                background: #f0f0f1;
            }
            
            .translation-tab.active {
                background: white;
                border-bottom-color: #0073aa;
            }
            
            .translation-panels {
                padding: 15px;
            }
            
            .original-content {
                margin-bottom: 20px;
                padding: 10px;
                background: #f8f9fa;
                border-left: 3px solid #0073aa;
            }
            
            .translation-panel {
                margin-bottom: 15px;
            }
            
            .translation-input {
                width: 100%;
                margin: 10px 0;
            }
            
            .translation-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .translation-status {
                font-size: 12px;
            }
        </style>
        <?php
    }
    
    /**
     * Get flag emoji for language
     */
    private function get_flag_emoji($language_code) {
        $flags = array(
            'en_US' => '🇺🇸',
            'en_GB' => '🇬🇧',
            'es_ES' => '🇪🇸',
            'es_MX' => '🇲🇽',
            'fr_FR' => '🇫🇷',
            'fr_CA' => '🇨🇦',
            'de_DE' => '🇩🇪',
            'it_IT' => '🇮🇹',
            'pt_BR' => '🇧🇷',
            'pt_PT' => '🇵🇹',
            'nl_NL' => '🇳🇱',
            'pl_PL' => '🇵🇱',
            'ru_RU' => '🇷🇺',
            'ja' => '🇯🇵',
            'zh_CN' => '🇨🇳',
            'ar' => '🇸🇦',
            'he_IL' => '🇮🇱'
        );
        
        return isset($flags[$language_code]) ? $flags[$language_code] : '🌐';
    }
    
    /**
     * Render language statistics widget
     */
    public function render_language_stats($object_type = null) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        $translations_table = $wpdb->prefix . 'mets_translations';
        
        // Get translation statistics
        $stats_query = "
            SELECT language, COUNT(*) as count 
            FROM $translations_table 
            " . ($object_type ? $wpdb->prepare("WHERE object_type = %s", $object_type) : "") . "
            GROUP BY language 
            ORDER BY count DESC
        ";
        
        $stats = $wpdb->get_results($stats_query);
        $languages = $this->language_manager->get_supported_languages();
        
        ?>
        <div class="mets-language-stats">
            <h3><?php _e('Translation Statistics', 'multi-entity-ticket-system'); ?></h3>
            
            <?php if (empty($stats)) : ?>
                <p><?php _e('No translations found.', 'multi-entity-ticket-system'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Language', 'multi-entity-ticket-system'); ?></th>
                            <th><?php _e('Translations', 'multi-entity-ticket-system'); ?></th>
                            <th><?php _e('Actions', 'multi-entity-ticket-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $stat) : ?>
                            <tr>
                                <td>
                                    <?php echo $this->get_flag_emoji($stat->language); ?>
                                    <?php echo esc_html($languages[$stat->language] ?? $stat->language); ?>
                                    <code><?php echo esc_html($stat->language); ?></code>
                                </td>
                                <td>
                                    <strong><?php echo number_format($stat->count); ?></strong>
                                </td>
                                <td>
                                    <a href="#" class="export-language-btn" data-language="<?php echo esc_attr($stat->language); ?>">
                                        <?php _e('Export', 'multi-entity-ticket-system'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <style>
            .mets-language-stats {
                margin: 20px 0;
            }
            
            .mets-language-stats table {
                margin-top: 10px;
            }
            
            .mets-language-stats code {
                font-size: 10px;
                color: #666;
                margin-left: 5px;
            }
        </style>
        <?php
    }
}