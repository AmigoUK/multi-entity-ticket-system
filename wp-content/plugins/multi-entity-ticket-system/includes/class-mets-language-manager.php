<?php
/**
 * METS Language Manager
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core language management functionality
 */
class METS_Language_Manager {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Cache for translations
     */
    private $translation_cache = array();
    
    /**
     * Supported languages
     */
    private $supported_languages = array(
        'en_US' => 'English (US)',
        'en_GB' => 'English (UK)',
        'es_ES' => 'Español (España)',
        'es_MX' => 'Español (México)',
        'fr_FR' => 'Français',
        'fr_CA' => 'Français (Canada)',
        'de_DE' => 'Deutsch',
        'it_IT' => 'Italiano',
        'pt_BR' => 'Português (Brasil)',
        'pt_PT' => 'Português (Portugal)',
        'nl_NL' => 'Nederlands',
        'pl_PL' => 'Polski',
        'ru_RU' => 'Русский',
        'ja' => '日本語',
        'zh_CN' => '中文 (简体)',
        'ar' => 'العربية',
        'he_IL' => 'עברית'
    );
    
    /**
     * RTL languages
     */
    private $rtl_languages = array('ar', 'he_IL');
    
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
     * Initialize the language manager
     */
    public function init() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_ajax_mets_detect_language', array($this, 'ajax_detect_language'));
        add_action('wp_ajax_mets_translate_content', array($this, 'ajax_translate_content'));
        add_action('wp_ajax_mets_save_translation', array($this, 'ajax_save_translation'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'multi-entity-ticket-system',
            false,
            dirname(plugin_basename(METS_PLUGIN_FILE)) . '/languages/'
        );
    }
    
    /**
     * Get all supported languages
     */
    public function get_supported_languages() {
        return apply_filters('mets_supported_languages', $this->supported_languages);
    }
    
    /**
     * Check if language is supported
     */
    public function is_language_supported($language) {
        return array_key_exists($language, $this->get_supported_languages());
    }
    
    /**
     * Check if language is RTL
     */
    public function is_rtl_language($language) {
        return in_array($language, $this->rtl_languages);
    }
    
    /**
     * Get language name
     */
    public function get_language_name($language_code) {
        $languages = $this->get_supported_languages();
        return isset($languages[$language_code]) ? $languages[$language_code] : $language_code;
    }
    
    /**
     * Detect content language using AI
     */
    public function detect_language($content) {
        // Check cache first
        $cache_key = 'mets_lang_detect_' . md5($content);
        $cached_result = wp_cache_get($cache_key, 'mets_language');
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        // Try AI detection first
        if (class_exists('METS_AI_Service')) {
            $ai_service = METS_AI_Service::get_instance();
            if ($ai_service->is_configured()) {
                $ai_result = $this->ai_detect_language($content);
                if (!is_wp_error($ai_result)) {
                    wp_cache_set($cache_key, $ai_result, 'mets_language', 3600);
                    return $ai_result;
                }
            }
        }
        
        // Fallback to simple detection
        $fallback_result = $this->simple_language_detection($content);
        wp_cache_set($cache_key, $fallback_result, 'mets_language', 3600);
        
        return $fallback_result;
    }
    
    /**
     * AI-powered language detection
     */
    private function ai_detect_language($content) {
        $ai_service = METS_AI_Service::get_instance();
        
        $prompt = "Detect the language of this text and respond with only the language code in WordPress locale format (e.g., en_US, es_ES, fr_FR, de_DE, etc.). If uncertain, provide your best guess.\n\nText: " . substr($content, 0, 500);
        
        $response = $ai_service->make_request($prompt, 'gpt-3.5-turbo', 20);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $detected_language = trim($response);
        
        // Validate and normalize the response
        if (preg_match('/([a-z]{2}_[A-Z]{2}|[a-z]{2})/', $detected_language, $matches)) {
            $language_code = $matches[1];
            
            // Convert simple language codes to full locales
            $language_map = array(
                'en' => 'en_US',
                'es' => 'es_ES',
                'fr' => 'fr_FR',
                'de' => 'de_DE',
                'it' => 'it_IT',
                'pt' => 'pt_BR',
                'nl' => 'nl_NL',
                'pl' => 'pl_PL',
                'ru' => 'ru_RU',
                'ja' => 'ja',
                'zh' => 'zh_CN',
                'ar' => 'ar',
                'he' => 'he_IL'
            );
            
            if (strlen($language_code) === 2 && isset($language_map[$language_code])) {
                $language_code = $language_map[$language_code];
            }
            
            // Calculate confidence based on content analysis
            $confidence = $this->calculate_language_confidence($content, $language_code);
            
            return array(
                'language' => $language_code,
                'confidence' => $confidence,
                'method' => 'ai'
            );
        }
        
        return new WP_Error('invalid_response', 'Could not parse language detection response');
    }
    
    /**
     * Simple language detection fallback
     */
    private function simple_language_detection($content) {
        // Common words in different languages
        $language_patterns = array(
            'es_ES' => array('el', 'la', 'de', 'que', 'y', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'está', 'hola', 'gracias', 'por favor'),
            'fr_FR' => array('le', 'de', 'et', 'à', 'un', 'il', 'être', 'et', 'en', 'avoir', 'que', 'pour', 'dans', 'ce', 'son', 'une', 'sur', 'avec', 'ne', 'se', 'pas', 'tout', 'plus', 'par', 'bonjour', 'merci', 's\'il vous plaît'),
            'de_DE' => array('der', 'die', 'und', 'in', 'den', 'von', 'zu', 'das', 'mit', 'sich', 'des', 'auf', 'für', 'ist', 'im', 'dem', 'nicht', 'ein', 'eine', 'als', 'auch', 'es', 'an', 'werden', 'aus', 'er', 'hat', 'dass', 'sie', 'hallo', 'danke', 'bitte'),
            'it_IT' => array('il', 'di', 'che', 'e', 'la', 'per', 'un', 'in', 'con', 'i', 'non', 'da', 'su', 'sono', 'le', 'si', 'a', 'una', 'come', 'ma', 'più', 'o', 'ne', 'se', 'ci', 'questo', 'mi', 'anche', 'ciao', 'grazie', 'per favore'),
            'pt_BR' => array('o', 'de', 'a', 'e', 'do', 'da', 'em', 'um', 'para', 'é', 'com', 'não', 'uma', 'os', 'no', 'se', 'na', 'por', 'mais', 'as', 'dos', 'como', 'mas', 'foi', 'ao', 'ele', 'das', 'tem', 'à', 'seu', 'sua', 'ou', 'ser', 'quando', 'muito', 'há', 'nos', 'já', 'está', 'eu', 'também', 'só', 'pelo', 'pela', 'até', 'isso', 'ela', 'entre', 'era', 'depois', 'sem', 'mesmo', 'aos', 'ter', 'seus', 'suas', 'numa', 'nem', 'suas', 'meu', 'às', 'minha', 'têm', 'numa', 'pelas', 'elas', 'estão', 'você', 'tinha', 'foram', 'essa', 'num', 'nem', 'suas', 'isso', 'olá', 'obrigado', 'por favor')
        );
        
        $content_lower = strtolower($content);
        $words = str_word_count($content_lower, 1);
        $scores = array();
        
        foreach ($language_patterns as $lang => $patterns) {
            $score = 0;
            foreach ($patterns as $pattern) {
                $score += substr_count($content_lower, ' ' . $pattern . ' ');
                $score += substr_count($content_lower, $pattern . ' ');
                $score += substr_count($content_lower, ' ' . $pattern);
            }
            $scores[$lang] = $score;
        }
        
        // Default to English if no patterns match strongly
        if (empty($scores) || max($scores) < 2) {
            return array(
                'language' => 'en_US',
                'confidence' => 0.3,
                'method' => 'fallback'
            );
        }
        
        arsort($scores);
        $detected_language = array_key_first($scores);
        $confidence = min(0.9, max(0.4, $scores[$detected_language] / 10));
        
        return array(
            'language' => $detected_language,
            'confidence' => $confidence,
            'method' => 'pattern'
        );
    }
    
    /**
     * Calculate language confidence score
     */
    private function calculate_language_confidence($content, $language) {
        $factors = array();
        
        // Length factor (longer text = higher confidence)
        $length = strlen($content);
        $factors['length'] = min(1.0, $length / 200);
        
        // Character set analysis
        if (in_array($language, array('ar', 'he_IL'))) {
            // Arabic/Hebrew specific characters
            $rtl_chars = preg_match_all('/[\x{0600}-\x{06FF}\x{0590}-\x{05FF}]/u', $content);
            $factors['charset'] = min(1.0, $rtl_chars / 10);
        } elseif ($language === 'zh_CN') {
            // Chinese characters
            $chinese_chars = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $content);
            $factors['charset'] = min(1.0, $chinese_chars / 10);
        } elseif ($language === 'ja') {
            // Japanese characters (Hiragana, Katakana, Kanji)
            $japanese_chars = preg_match_all('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4e00}-\x{9fff}]/u', $content);
            $factors['charset'] = min(1.0, $japanese_chars / 10);
        } else {
            // Latin-based languages
            $factors['charset'] = 0.8;
        }
        
        // Calculate weighted average
        return array_sum($factors) / count($factors);
    }
    
    /**
     * Get user's preferred language
     */
    public function get_user_language($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return $this->get_site_language();
        }
        
        $user_language = get_user_meta($user_id, 'mets_language_preference', true);
        
        if (empty($user_language) || !$this->is_language_supported($user_language)) {
            // Try WordPress user locale
            $wp_locale = get_user_locale($user_id);
            if ($this->is_language_supported($wp_locale)) {
                return $wp_locale;
            }
            
            return $this->get_site_language();
        }
        
        return $user_language;
    }
    
    /**
     * Set user's preferred language
     */
    public function set_user_language($user_id, $language) {
        if (!$this->is_language_supported($language)) {
            return new WP_Error('unsupported_language', 'Language not supported');
        }
        
        return update_user_meta($user_id, 'mets_language_preference', $language);
    }
    
    /**
     * Get user's spoken languages
     */
    public function get_user_spoken_languages($user_id) {
        $languages = get_user_meta($user_id, 'mets_languages_spoken', true);
        
        if (empty($languages)) {
            return array($this->get_user_language($user_id));
        }
        
        return is_array($languages) ? $languages : json_decode($languages, true);
    }
    
    /**
     * Set user's spoken languages
     */
    public function set_user_spoken_languages($user_id, $languages) {
        if (!is_array($languages)) {
            return new WP_Error('invalid_format', 'Languages must be an array');
        }
        
        // Validate all languages
        foreach ($languages as $language) {
            if (!$this->is_language_supported($language)) {
                return new WP_Error('unsupported_language', "Language $language not supported");
            }
        }
        
        return update_user_meta($user_id, 'mets_languages_spoken', json_encode($languages));
    }
    
    /**
     * Get site's default language
     */
    public function get_site_language() {
        $locale = get_locale();
        return $this->is_language_supported($locale) ? $locale : 'en_US';
    }
    
    /**
     * Get language fallback chain
     */
    public function get_fallback_chain($preferred_language) {
        $chain = array($preferred_language);
        
        // Add site language if different
        $site_language = $this->get_site_language();
        if ($site_language !== $preferred_language) {
            $chain[] = $site_language;
        }
        
        // Add English as final fallback
        if (!in_array('en_US', $chain)) {
            $chain[] = 'en_US';
        }
        
        return $chain;
    }
    
    /**
     * Get translation for dynamic content
     */
    public function get_translation($object_type, $object_id, $field_name, $language = null) {
        if (!$language) {
            $language = $this->get_user_language();
        }
        
        $cache_key = "mets_translation_{$object_type}_{$object_id}_{$field_name}_{$language}";
        $cached = wp_cache_get($cache_key, 'mets_translations');
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mets_translations';
        
        $translation = $wpdb->get_var($wpdb->prepare(
            "SELECT translation FROM $table_name 
             WHERE object_type = %s AND object_id = %d AND field_name = %s AND language = %s",
            $object_type, $object_id, $field_name, $language
        ));
        
        // Cache the result (including null results)
        wp_cache_set($cache_key, $translation, 'mets_translations', 3600);
        
        return $translation;
    }
    
    /**
     * Save translation for dynamic content
     */
    public function save_translation($object_type, $object_id, $field_name, $language, $translation) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mets_translations';
        
        $result = $wpdb->replace(
            $table_name,
            array(
                'object_type' => $object_type,
                'object_id' => $object_id,
                'field_name' => $field_name,
                'language' => $language,
                'translation' => $translation
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            // Clear cache
            $cache_key = "mets_translation_{$object_type}_{$object_id}_{$field_name}_{$language}";
            wp_cache_delete($cache_key, 'mets_translations');
        }
        
        return $result;
    }
    
    /**
     * Get localized data (dates, numbers, currency)
     */
    public function format_locale_data($language, $data_type, $value = null) {
        switch ($data_type) {
            case 'date':
                return $this->format_date($value, $language);
            case 'time':
                return $this->format_time($value, $language);
            case 'currency':
                return $this->get_currency_symbol($language);
            case 'number':
                return $this->format_number($value, $language);
            default:
                return $value;
        }
    }
    
    /**
     * Format date for locale
     */
    private function format_date($timestamp, $language) {
        $original_locale = get_locale();
        
        // Temporarily switch locale
        if ($language !== $original_locale) {
            switch_to_locale($language);
        }
        
        $formatted = date_i18n(get_option('date_format'), $timestamp);
        
        // Restore original locale
        if ($language !== $original_locale) {
            restore_previous_locale();
        }
        
        return $formatted;
    }
    
    /**
     * Format time for locale
     */
    private function format_time($timestamp, $language) {
        $original_locale = get_locale();
        
        if ($language !== $original_locale) {
            switch_to_locale($language);
        }
        
        $formatted = date_i18n(get_option('time_format'), $timestamp);
        
        if ($language !== $original_locale) {
            restore_previous_locale();
        }
        
        return $formatted;
    }
    
    /**
     * Get currency symbol for language/region
     */
    private function get_currency_symbol($language) {
        $currency_map = array(
            'en_US' => '$',
            'en_GB' => '£',
            'es_ES' => '€',
            'fr_FR' => '€',
            'de_DE' => '€',
            'it_IT' => '€',
            'pt_BR' => 'R$',
            'pt_PT' => '€',
            'ja' => '¥',
            'zh_CN' => '¥'
        );
        
        return isset($currency_map[$language]) ? $currency_map[$language] : '$';
    }
    
    /**
     * Format number for locale
     */
    private function format_number($number, $language) {
        // Simplified number formatting
        $decimal_separators = array(
            'en_US' => '.',
            'es_ES' => ',',
            'fr_FR' => ',',
            'de_DE' => ',',
            'it_IT' => ','
        );
        
        $thousands_separators = array(
            'en_US' => ',',
            'es_ES' => '.',
            'fr_FR' => ' ',
            'de_DE' => '.',
            'it_IT' => '.'
        );
        
        $decimal_sep = isset($decimal_separators[$language]) ? $decimal_separators[$language] : '.';
        $thousands_sep = isset($thousands_separators[$language]) ? $thousands_separators[$language] : ',';
        
        return number_format($number, 2, $decimal_sep, $thousands_sep);
    }
    
    /**
     * AJAX handler for language detection
     */
    public function ajax_detect_language() {
        check_ajax_referer('mets_language_detect', 'nonce');
        
        $content = sanitize_textarea_field($_POST['content']);
        
        if (empty($content)) {
            wp_send_json_error('Content is required');
        }
        
        $result = $this->detect_language($content);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for content translation
     */
    public function ajax_translate_content() {
        check_ajax_referer('mets_translate_content', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $content = sanitize_textarea_field($_POST['content']);
        $target_language = sanitize_text_field($_POST['target_language']);
        
        if (empty($content) || empty($target_language)) {
            wp_send_json_error('Content and target language are required');
        }
        
        if (!$this->is_language_supported($target_language)) {
            wp_send_json_error('Target language not supported');
        }
        
        // Use AI service for translation
        if (class_exists('METS_AI_Service')) {
            $ai_service = METS_AI_Service::get_instance();
            if ($ai_service->is_configured()) {
                $translation = $this->ai_translate_content($content, $target_language);
                
                if (is_wp_error($translation)) {
                    wp_send_json_error($translation->get_error_message());
                }
                
                wp_send_json_success(array('translation' => $translation));
            }
        }
        
        wp_send_json_error('Translation service not available');
    }
    
    /**
     * AI-powered content translation
     */
    private function ai_translate_content($content, $target_language) {
        $ai_service = METS_AI_Service::get_instance();
        $language_name = $this->get_language_name($target_language);
        
        $prompt = "Translate the following text to {$language_name} ({$target_language}). Maintain the professional tone and context. Only return the translated text:\n\n{$content}";
        
        return $ai_service->make_request($prompt, 'gpt-3.5-turbo', 1000);
    }
    
    /**
     * AJAX handler for saving translations
     */
    public function ajax_save_translation() {
        check_ajax_referer('mets_save_translation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $object_type = sanitize_text_field($_POST['object_type']);
        $object_id = intval($_POST['object_id']);
        $field_name = sanitize_text_field($_POST['field_name']);
        $language = sanitize_text_field($_POST['language']);
        $translation = sanitize_textarea_field($_POST['translation']);
        
        if (empty($object_type) || empty($object_id) || empty($field_name) || empty($language) || empty($translation)) {
            wp_send_json_error('All fields are required');
        }
        
        $result = $this->save_translation($object_type, $object_id, $field_name, $language, $translation);
        
        if ($result === false) {
            wp_send_json_error('Failed to save translation');
        }
        
        wp_send_json_success('Translation saved successfully');
    }
}