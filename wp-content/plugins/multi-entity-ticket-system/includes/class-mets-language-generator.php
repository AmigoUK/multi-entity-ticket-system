<?php
/**
 * METS Language File Generator
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generate language files and manage translations
 */
class METS_Language_Generator {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'multi-entity-ticket-system';
    
    /**
     * Generate POT template file
     */
    public function generate_pot_file() {
        $plugin_dir = METS_PLUGIN_PATH;
        $languages_dir = $plugin_dir . 'languages/';
        
        // Ensure languages directory exists
        if (!file_exists($languages_dir)) {
            wp_mkdir_p($languages_dir);
        }
        
        $pot_file = $languages_dir . self::TEXT_DOMAIN . '.pot';
        
        // Get all translatable strings
        $strings = $this->extract_translatable_strings();
        
        // Generate POT content
        $pot_content = $this->generate_pot_content($strings);
        
        // Write POT file
        $result = file_put_contents($pot_file, $pot_content);
        
        if ($result === false) {
            return new WP_Error('pot_generation_failed', 'Failed to generate POT file');
        }
        
        return $pot_file;
    }
    
    /**
     * Extract translatable strings from plugin files
     */
    private function extract_translatable_strings() {
        $plugin_dir = METS_PLUGIN_PATH;
        $strings = array();
        
        // Directories to scan
        $scan_dirs = array(
            $plugin_dir . 'includes/',
            $plugin_dir . 'admin/',
            $plugin_dir . 'public/',
            $plugin_dir
        );
        
        // Translation function patterns
        $patterns = array(
            '/(?:__|_e|_n|_x|_ex|_nx|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]' . preg_quote(self::TEXT_DOMAIN) . '[\'"]/',
            '/(?:_n|_nx)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[^,]+\s*,\s*[\'"]' . preg_quote(self::TEXT_DOMAIN) . '[\'"]/',
            '/(?:_x|_ex|_nx)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"](?:\s*,\s*[^,]+)?\s*,\s*[\'"]' . preg_quote(self::TEXT_DOMAIN) . '[\'"]/'
        );
        
        foreach ($scan_dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                
                $content = file_get_contents($file->getPathname());
                $relative_path = str_replace(METS_PLUGIN_PATH, '', $file->getPathname());
                
                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                        foreach ($matches as $match) {
                            $line_number = substr_count(substr($content, 0, $match[0][1]), "\n") + 1;
                            
                            $string_entry = array(
                                'string' => $match[1][0],
                                'file' => $relative_path,
                                'line' => $line_number
                            );
                            
                            // Handle plural forms
                            if (isset($match[2])) {
                                $string_entry['plural'] = $match[2][0];
                            }
                            
                            // Handle context
                            if (strpos($pattern, '_x') !== false && isset($match[2])) {
                                $string_entry['context'] = $match[2][0];
                            }
                            
                            $strings[] = $string_entry;
                        }
                    }
                }
            }
        }
        
        // Remove duplicates
        $unique_strings = array();
        foreach ($strings as $string) {
            $key = $string['string'] . (isset($string['context']) ? '|' . $string['context'] : '');
            if (!isset($unique_strings[$key])) {
                $unique_strings[$key] = $string;
            } else {
                // Add additional file references
                if (!is_array($unique_strings[$key]['file'])) {
                    $unique_strings[$key]['file'] = array($unique_strings[$key]['file']);
                    $unique_strings[$key]['line'] = array($unique_strings[$key]['line']);
                }
                $unique_strings[$key]['file'][] = $string['file'];
                $unique_strings[$key]['line'][] = $string['line'];
            }
        }
        
        return array_values($unique_strings);
    }
    
    /**
     * Generate POT file content
     */
    private function generate_pot_content($strings) {
        $plugin_data = get_plugin_data(METS_PLUGIN_FILE);
        
        $pot_header = sprintf('# Copyright (C) %1$s %2$s
# This file is distributed under the same license as the %2$s package.
msgid ""
msgstr ""
"Project-Id-Version: %2$s %3$s\\n"
"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/multi-entity-ticket-system\\n"
"POT-Creation-Date: %4$s\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"

',
            date('Y'),
            $plugin_data['Name'],
            $plugin_data['Version'],
            date('Y-m-d H:i:s O')
        );
        
        $pot_content = $pot_header;
        
        foreach ($strings as $string) {
            // Add file references
            $files = is_array($string['file']) ? $string['file'] : array($string['file']);
            $lines = is_array($string['line']) ? $string['line'] : array($string['line']);
            
            foreach ($files as $index => $file) {
                $line = isset($lines[$index]) ? $lines[$index] : 1;
                $pot_content .= "#: {$file}:{$line}\n";
            }
            
            // Add context if present
            if (isset($string['context'])) {
                $pot_content .= "msgctxt \"" . addslashes($string['context']) . "\"\n";
            }
            
            // Add the string
            $pot_content .= "msgid \"" . addslashes($string['string']) . "\"\n";
            
            // Add plural form if present
            if (isset($string['plural'])) {
                $pot_content .= "msgid_plural \"" . addslashes($string['plural']) . "\"\n";
                $pot_content .= "msgstr[0] \"\"\n";
                $pot_content .= "msgstr[1] \"\"\n";
            } else {
                $pot_content .= "msgstr \"\"\n";
            }
            
            $pot_content .= "\n";
        }
        
        return $pot_content;
    }
    
    /**
     * Create language pack structure
     */
    public function create_language_pack($language_code) {
        $languages_dir = METS_PLUGIN_PATH . 'languages/';
        
        // Create PO file
        $po_file = $languages_dir . self::TEXT_DOMAIN . '-' . $language_code . '.po';
        $mo_file = $languages_dir . self::TEXT_DOMAIN . '-' . $language_code . '.mo';
        
        // Copy POT to PO
        $pot_file = $languages_dir . self::TEXT_DOMAIN . '.pot';
        
        if (!file_exists($pot_file)) {
            $this->generate_pot_file();
        }
        
        $pot_content = file_get_contents($pot_file);
        
        // Update PO header with language info
        $language_manager = METS_Language_Manager::get_instance();
        $language_name = $language_manager->get_language_name($language_code);
        
        $po_content = str_replace(
            array(
                'YEAR-MO-DA HO:MI+ZONE',
                'FULL NAME <EMAIL@ADDRESS>',
                'LANGUAGE <LL@li.org>',
                'Language: \\n'
            ),
            array(
                date('Y-m-d H:i O'),
                'METS Translator <translator@example.com>',
                $language_name . ' <' . $language_code . '@li.org>',
                'Language: ' . $language_code . '\\n'
            ),
            $pot_content
        );
        
        // Write PO file
        $result = file_put_contents($po_file, $po_content);
        
        if ($result === false) {
            return new WP_Error('po_creation_failed', 'Failed to create PO file');
        }
        
        return array(
            'po_file' => $po_file,
            'mo_file' => $mo_file
        );
    }
    
    /**
     * Generate default translations for common strings
     */
    public function generate_default_translations($language_code) {
        $default_translations = array(
            'en_US' => array(),
            'es_ES' => array(
                'Ticket' => 'Ticket',
                'Tickets' => 'Tickets',
                'Subject' => 'Asunto',
                'Description' => 'Descripción',
                'Priority' => 'Prioridad',
                'Status' => 'Estado',
                'Category' => 'Categoría',
                'Customer' => 'Cliente',
                'Agent' => 'Agente',
                'Open' => 'Abierto',
                'Closed' => 'Cerrado',
                'Pending' => 'Pendiente',
                'In Progress' => 'En Progreso',
                'High' => 'Alta',
                'Medium' => 'Media',
                'Low' => 'Baja',
                'Reply' => 'Responder',
                'Save' => 'Guardar',
                'Cancel' => 'Cancelar',
                'Delete' => 'Eliminar',
                'Edit' => 'Editar',
                'Create' => 'Crear',
                'Search' => 'Buscar',
                'Filter' => 'Filtrar',
                'Export' => 'Exportar',
                'Import' => 'Importar',
                'Settings' => 'Configuración',
                'Dashboard' => 'Panel de Control',
                'Reports' => 'Informes',
                'Analytics' => 'Analytics',
                'Team' => 'Equipo',
                'Users' => 'Usuarios',
                'Roles' => 'Roles',
                'Permissions' => 'Permisos',
                'Email Templates' => 'Plantillas de Email',
                'AI Features' => 'Funciones de IA',
                'Language' => 'Idioma',
                'Translation' => 'Traducción'
            ),
            'fr_FR' => array(
                'Ticket' => 'Ticket',
                'Tickets' => 'Tickets',
                'Subject' => 'Sujet',
                'Description' => 'Description',
                'Priority' => 'Priorité',
                'Status' => 'Statut',
                'Category' => 'Catégorie',
                'Customer' => 'Client',
                'Agent' => 'Agent',
                'Open' => 'Ouvert',
                'Closed' => 'Fermé',
                'Pending' => 'En attente',
                'In Progress' => 'En cours',
                'High' => 'Haute',
                'Medium' => 'Moyenne',
                'Low' => 'Basse',
                'Reply' => 'Répondre',
                'Save' => 'Enregistrer',
                'Cancel' => 'Annuler',
                'Delete' => 'Supprimer',
                'Edit' => 'Modifier',
                'Create' => 'Créer',
                'Search' => 'Rechercher',
                'Filter' => 'Filtrer',
                'Export' => 'Exporter',
                'Import' => 'Importer',
                'Settings' => 'Paramètres',
                'Dashboard' => 'Tableau de bord',
                'Reports' => 'Rapports',
                'Analytics' => 'Analytics',
                'Team' => 'Équipe',
                'Users' => 'Utilisateurs',
                'Roles' => 'Rôles',
                'Permissions' => 'Permissions',
                'Email Templates' => 'Modèles d\'email',
                'AI Features' => 'Fonctionnalités IA',
                'Language' => 'Langue',
                'Translation' => 'Traduction'
            ),
            'de_DE' => array(
                'Ticket' => 'Ticket',
                'Tickets' => 'Tickets',
                'Subject' => 'Betreff',
                'Description' => 'Beschreibung',
                'Priority' => 'Priorität',
                'Status' => 'Status',
                'Category' => 'Kategorie',
                'Customer' => 'Kunde',
                'Agent' => 'Agent',
                'Open' => 'Offen',
                'Closed' => 'Geschlossen',
                'Pending' => 'Wartend',
                'In Progress' => 'In Bearbeitung',
                'High' => 'Hoch',
                'Medium' => 'Mittel',
                'Low' => 'Niedrig',
                'Reply' => 'Antworten',
                'Save' => 'Speichern',
                'Cancel' => 'Abbrechen',
                'Delete' => 'Löschen',
                'Edit' => 'Bearbeiten',
                'Create' => 'Erstellen',
                'Search' => 'Suchen',
                'Filter' => 'Filter',
                'Export' => 'Exportieren',
                'Import' => 'Importieren',
                'Settings' => 'Einstellungen',
                'Dashboard' => 'Dashboard',
                'Reports' => 'Berichte',
                'Analytics' => 'Analytics',
                'Team' => 'Team',
                'Users' => 'Benutzer',
                'Roles' => 'Rollen',
                'Permissions' => 'Berechtigungen',
                'Email Templates' => 'E-Mail-Vorlagen',
                'AI Features' => 'KI-Funktionen',
                'Language' => 'Sprache',
                'Translation' => 'Übersetzung'
            )
        );
        
        return isset($default_translations[$language_code]) ? $default_translations[$language_code] : array();
    }
    
    /**
     * Apply default translations to PO file
     */
    public function apply_default_translations($language_code) {
        $languages_dir = METS_PLUGIN_PATH . 'languages/';
        $po_file = $languages_dir . self::TEXT_DOMAIN . '-' . $language_code . '.po';
        
        if (!file_exists($po_file)) {
            return new WP_Error('po_not_found', 'PO file not found');
        }
        
        $default_translations = $this->generate_default_translations($language_code);
        
        if (empty($default_translations)) {
            return new WP_Error('no_translations', 'No default translations available for this language');
        }
        
        $po_content = file_get_contents($po_file);
        
        foreach ($default_translations as $english => $translation) {
            // Find and replace msgstr for this string
            $pattern = '/msgid "' . preg_quote($english, '/') . '"\s*msgstr ""/';
            $replacement = 'msgid "' . $english . '"' . "\n" . 'msgstr "' . addslashes($translation) . '"';
            $po_content = preg_replace($pattern, $replacement, $po_content);
        }
        
        // Write updated PO file
        $result = file_put_contents($po_file, $po_content);
        
        if ($result === false) {
            return new WP_Error('po_update_failed', 'Failed to update PO file');
        }
        
        return true;
    }
    
    /**
     * Compile PO to MO file
     */
    public function compile_po_to_mo($language_code) {
        $languages_dir = METS_PLUGIN_PATH . 'languages/';
        $po_file = $languages_dir . self::TEXT_DOMAIN . '-' . $language_code . '.po';
        $mo_file = $languages_dir . self::TEXT_DOMAIN . '-' . $language_code . '.mo';
        
        if (!file_exists($po_file)) {
            return new WP_Error('po_not_found', 'PO file not found');
        }
        
        // Simple PO to MO compilation
        $po_content = file_get_contents($po_file);
        $translations = $this->parse_po_file($po_content);
        
        $mo_content = $this->generate_mo_content($translations);
        
        $result = file_put_contents($mo_file, $mo_content);
        
        if ($result === false) {
            return new WP_Error('mo_compilation_failed', 'Failed to compile MO file');
        }
        
        return $mo_file;
    }
    
    /**
     * Parse PO file content
     */
    private function parse_po_file($content) {
        $translations = array();
        $lines = explode("\n", $content);
        $current_entry = array();
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            if (strpos($line, 'msgid ') === 0) {
                if (!empty($current_entry['msgid'])) {
                    $translations[] = $current_entry;
                }
                $current_entry = array('msgid' => $this->extract_string($line));
            } elseif (strpos($line, 'msgstr ') === 0) {
                $current_entry['msgstr'] = $this->extract_string($line);
            } elseif (strpos($line, '"') === 0) {
                // Continuation line
                if (isset($current_entry['msgstr'])) {
                    $current_entry['msgstr'] .= $this->extract_string($line);
                } elseif (isset($current_entry['msgid'])) {
                    $current_entry['msgid'] .= $this->extract_string($line);
                }
            }
        }
        
        if (!empty($current_entry['msgid'])) {
            $translations[] = $current_entry;
        }
        
        return $translations;
    }
    
    /**
     * Extract string from PO line
     */
    private function extract_string($line) {
        if (preg_match('/^(?:msgid|msgstr)\s+"(.*)"$/', $line, $matches)) {
            return stripcslashes($matches[1]);
        } elseif (preg_match('/^"(.*)"$/', $line, $matches)) {
            return stripcslashes($matches[1]);
        }
        return '';
    }
    
    /**
     * Generate MO file content (simplified)
     */
    private function generate_mo_content($translations) {
        // This is a simplified MO file generator
        // For production, consider using a proper gettext library
        
        $entries = array();
        foreach ($translations as $translation) {
            if (!empty($translation['msgid']) && !empty($translation['msgstr'])) {
                $entries[$translation['msgid']] = $translation['msgstr'];
            }
        }
        
        if (empty($entries)) {
            return '';
        }
        
        // MO file format is binary, this is a simplified version
        return serialize($entries);
    }
    
    /**
     * Get translation statistics
     */
    public function get_translation_stats($language_code) {
        $languages_dir = METS_PLUGIN_PATH . 'languages/';
        $po_file = $languages_dir . self::TEXT_DOMAIN . '-' . $language_code . '.po';
        
        if (!file_exists($po_file)) {
            return new WP_Error('po_not_found', 'PO file not found');
        }
        
        $po_content = file_get_contents($po_file);
        $translations = $this->parse_po_file($po_content);
        
        $total = 0;
        $translated = 0;
        $fuzzy = 0;
        
        foreach ($translations as $translation) {
            if (empty($translation['msgid'])) {
                continue; // Skip header
            }
            
            $total++;
            
            if (!empty($translation['msgstr'])) {
                $translated++;
            }
        }
        
        return array(
            'total' => $total,
            'translated' => $translated,
            'untranslated' => $total - $translated,
            'percentage' => $total > 0 ? round(($translated / $total) * 100) : 0
        );
    }
}