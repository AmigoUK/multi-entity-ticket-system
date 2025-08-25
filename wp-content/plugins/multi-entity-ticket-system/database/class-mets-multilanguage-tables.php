<?php
/**
 * METS Multilanguage Database Tables
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle creation and management of multilanguage database tables
 */
class METS_Multilanguage_Tables {
    
    /**
     * Create all multilanguage tables
     */
    public function create_tables() {
        $this->create_translations_table();
        $this->create_ai_language_settings_table();
        $this->add_language_columns();
    }
    
    /**
     * Create translations table for dynamic content
     */
    public function create_translations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_translations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            object_type varchar(50) NOT NULL COMMENT 'category, tag, priority, status, etc.',
            object_id bigint(20) unsigned NOT NULL,
            language varchar(10) NOT NULL,
            field_name varchar(50) NOT NULL,
            translation text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY object_translation (object_type, object_id, language, field_name),
            KEY idx_lookup (object_type, object_id, language),
            KEY idx_language (language)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Create AI language-specific settings table
     */
    public function create_ai_language_settings_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_ai_language_settings';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            language varchar(10) NOT NULL,
            master_prompt longtext,
            knowledge_base longtext,
            auto_detect_enabled tinyint(1) DEFAULT 1,
            auto_translate_enabled tinyint(1) DEFAULT 0,
            preferred_ai_model varchar(100),
            custom_instructions text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY language (language)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        // Insert default settings for English
        $this->insert_default_language_settings();
    }
    
    /**
     * Add language columns to existing tables
     */
    public function add_language_columns() {
        global $wpdb;
        
        // Add language columns to tickets table
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        
        // Check if columns don't exist before adding
        $columns = $wpdb->get_results("DESCRIBE $tickets_table");
        $column_names = wp_list_pluck($columns, 'Field');
        
        if ( ! in_array('language', $column_names) ) {
            $wpdb->query("ALTER TABLE $tickets_table 
                ADD COLUMN language VARCHAR(10) DEFAULT 'en_US' AFTER customer_email");
        }
        
        if ( ! in_array('detected_language', $column_names) ) {
            $wpdb->query("ALTER TABLE $tickets_table 
                ADD COLUMN detected_language VARCHAR(10) NULL AFTER language");
        }
        
        if ( ! in_array('language_confidence', $column_names) ) {
            $wpdb->query("ALTER TABLE $tickets_table 
                ADD COLUMN language_confidence DECIMAL(3,2) NULL AFTER detected_language");
        }
        
        // Add index for language
        $wpdb->query("ALTER TABLE $tickets_table ADD INDEX idx_language (language)");
        
        // Add language columns to email templates table
        $templates_table = $wpdb->prefix . 'mets_email_templates';
        
        $template_columns = $wpdb->get_results("DESCRIBE $templates_table");
        $template_column_names = wp_list_pluck($template_columns, 'Field');
        
        if ( ! in_array('language', $template_column_names) ) {
            $wpdb->query("ALTER TABLE $templates_table 
                ADD COLUMN language VARCHAR(10) DEFAULT 'en_US' AFTER template_type");
        }
        
        // Add unique constraint for template_type + language
        $wpdb->query("ALTER TABLE $templates_table 
            ADD UNIQUE KEY template_language (template_type, language)");
        
        // Add language columns to ticket replies table
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        
        $reply_columns = $wpdb->get_results("DESCRIBE $replies_table");
        $reply_column_names = wp_list_pluck($reply_columns, 'Field');
        
        if ( ! in_array('language', $reply_column_names) ) {
            $wpdb->query("ALTER TABLE $replies_table 
                ADD COLUMN language VARCHAR(10) DEFAULT 'en_US' AFTER user_type");
        }
        
        if ( ! in_array('is_translated', $reply_column_names) ) {
            $wpdb->query("ALTER TABLE $replies_table 
                ADD COLUMN is_translated TINYINT(1) DEFAULT 0 AFTER language");
        }
        
        if ( ! in_array('original_language', $reply_column_names) ) {
            $wpdb->query("ALTER TABLE $replies_table 
                ADD COLUMN original_language VARCHAR(10) NULL AFTER is_translated");
        }
    }
    
    /**
     * Insert default language settings
     */
    private function insert_default_language_settings() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_ai_language_settings';
        
        // Check if English settings already exist
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE language = %s",
            'en_US'
        ));
        
        if ( ! $exists ) {
            $default_master_prompt = "You are a professional customer support assistant. Be helpful, empathetic, and solution-focused in all interactions. Always maintain a friendly but professional tone.

When you cannot resolve an issue directly:
- Acknowledge the customer's concern
- Explain what steps you're taking to help
- Provide clear next steps or escalation information

Never make promises about refunds, account changes, or technical modifications without proper authorization.";
            
            $default_knowledge_base = "COMPANY INFORMATION:
- Business Hours: Monday-Friday 9AM-5PM EST
- Support Response Time: Within 24 hours
- Emergency Support: Available for critical issues

COMMON SOLUTIONS:
- Login Problems: Check email/password, try password reset
- Payment Issues: Verify card details, check for expired cards
- Technical Support: Clear browser cache, try different browser

ESCALATION PROCEDURES:
- Technical issues requiring developer: Escalate to Tech Support
- Billing disputes over $100: Escalate to Manager
- Legal issues: Forward to Legal Department";
            
            $wpdb->insert(
                $table_name,
                array(
                    'language' => 'en_US',
                    'master_prompt' => $default_master_prompt,
                    'knowledge_base' => $default_knowledge_base,
                    'auto_detect_enabled' => 1,
                    'auto_translate_enabled' => 0,
                    'preferred_ai_model' => 'openai/gpt-3.5-turbo'
                ),
                array('%s', '%s', '%s', '%d', '%d', '%s')
            );
        }
    }
    
    /**
     * Migrate existing data to include language information
     */
    public function migrate_existing_data() {
        global $wpdb;
        
        // Get site language
        $site_language = get_locale();
        if ( empty($site_language) ) {
            $site_language = 'en_US';
        }
        
        // Update existing tickets with site language
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $wpdb->query($wpdb->prepare(
            "UPDATE $tickets_table SET language = %s WHERE language IS NULL OR language = ''",
            $site_language
        ));
        
        // Update existing email templates
        $templates_table = $wpdb->prefix . 'mets_email_templates';
        $wpdb->query($wpdb->prepare(
            "UPDATE $templates_table SET language = %s WHERE language IS NULL OR language = ''",
            $site_language
        ));
        
        // Update existing replies
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        $wpdb->query($wpdb->prepare(
            "UPDATE $replies_table SET language = %s WHERE language IS NULL OR language = ''",
            $site_language
        ));
    }
    
    /**
     * Create language-specific email template variants
     */
    public function create_template_variants($source_language = 'en_US') {
        global $wpdb;
        
        $templates_table = $wpdb->prefix . 'mets_email_templates';
        
        // Get all templates in source language
        $templates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $templates_table WHERE language = %s",
            $source_language
        ));
        
        // Define target languages for variants
        $target_languages = array('es_ES', 'fr_FR', 'de_DE', 'it_IT', 'pt_BR');
        
        foreach ($templates as $template) {
            foreach ($target_languages as $target_lang) {
                // Check if variant already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $templates_table 
                     WHERE template_type = %s AND language = %s",
                    $template->template_type,
                    $target_lang
                ));
                
                if ( ! $exists ) {
                    // Create variant with placeholder content
                    $variant_data = array(
                        'template_name' => $template->template_name . ' (' . $target_lang . ')',
                        'template_type' => $template->template_type,
                        'language' => $target_lang,
                        'subject_line' => '[' . strtoupper($target_lang) . '] ' . $template->subject_line,
                        'content' => $template->content, // Will be translated later
                        'plain_text_content' => $template->plain_text_content,
                        'is_active' => 0, // Inactive until translated
                        'is_default' => 0,
                        'created_by' => $template->created_by,
                        'version' => 1
                    );
                    
                    $wpdb->insert($templates_table, $variant_data);
                }
            }
        }
    }
    
    /**
     * Get database version for multilanguage features
     */
    public function get_db_version() {
        return get_option('mets_multilanguage_db_version', '0.0.0');
    }
    
    /**
     * Update database version
     */
    public function update_db_version($version) {
        update_option('mets_multilanguage_db_version', $version);
    }
    
    /**
     * Check if multilanguage tables exist
     */
    public function tables_exist() {
        global $wpdb;
        
        $translations_table = $wpdb->prefix . 'mets_translations';
        $ai_settings_table = $wpdb->prefix . 'mets_ai_language_settings';
        
        $translations_exists = $wpdb->get_var("SHOW TABLES LIKE '$translations_table'") === $translations_table;
        $ai_settings_exists = $wpdb->get_var("SHOW TABLES LIKE '$ai_settings_table'") === $ai_settings_table;
        
        return $translations_exists && $ai_settings_exists;
    }
    
    /**
     * Remove multilanguage tables (for uninstall)
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'mets_translations',
            $wpdb->prefix . 'mets_ai_language_settings'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove language columns from existing tables
        $this->remove_language_columns();
        
        // Remove options
        delete_option('mets_multilanguage_db_version');
    }
    
    /**
     * Remove language columns from existing tables
     */
    private function remove_language_columns() {
        global $wpdb;
        
        // Remove from tickets table
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $wpdb->query("ALTER TABLE $tickets_table 
            DROP COLUMN IF EXISTS language,
            DROP COLUMN IF EXISTS detected_language,
            DROP COLUMN IF EXISTS language_confidence,
            DROP INDEX IF EXISTS idx_language");
        
        // Remove from email templates table
        $templates_table = $wpdb->prefix . 'mets_email_templates';
        $wpdb->query("ALTER TABLE $templates_table 
            DROP COLUMN IF EXISTS language,
            DROP INDEX IF EXISTS template_language");
        
        // Remove from ticket replies table
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        $wpdb->query("ALTER TABLE $replies_table 
            DROP COLUMN IF EXISTS language,
            DROP COLUMN IF EXISTS is_translated,
            DROP COLUMN IF EXISTS original_language");
    }
}