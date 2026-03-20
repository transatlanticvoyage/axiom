<?php
/**
 * SQL Extractor for DB Field Discrepancy Checker
 * Extracts CREATE TABLE statements from plugin files
 * 
 * @package Axiom
 * @subpackage DbFieldDiscrepancyChecker
 */

if (!defined('ABSPATH')) {
    exit;
}

class Axiom_DB_SQL_Extractor {
    
    /**
     * Extract CREATE TABLE SQL from a plugin file
     * 
     * @param string $file_path Full path to the file
     * @param string $table_name Table name to search for (without prefix)
     * @return string|false The CREATE TABLE SQL or false if not found
     */
    public static function extract_create_table_sql($file_path, $table_name) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $file_content = file_get_contents($file_path);
        if (!$file_content) {
            return false;
        }
        
        // Pattern to match CREATE TABLE statement for the specific table
        // This will match from CREATE TABLE to the closing semicolon
        $pattern = '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"\']?\$?' . preg_quote($table_name) . '[`"\']?\s*\([^;]+\);/is';
        
        // First try to find with variable reference (like $pylons_table)
        if (preg_match($pattern, $file_content, $matches)) {
            return self::clean_sql($matches[0], $table_name);
        }
        
        // Try with direct table name reference
        $pattern = '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"\']?\w+[`"\']?\.' . preg_quote($table_name) . '[`"\']?\s*\([^;]+\);/is';
        if (preg_match($pattern, $file_content, $matches)) {
            return self::clean_sql($matches[0], $table_name);
        }
        
        // Try another pattern for concatenated table names
        global $wpdb;
        $prefix = $wpdb->prefix;
        $full_table_name = $prefix . $table_name;
        
        // Look for the SQL assignment pattern
        $pattern = '/\$\w+_sql\s*=\s*["\']CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[^"\']*' . preg_quote($table_name) . '[^;]+;["\'];/is';
        if (preg_match($pattern, $file_content, $matches)) {
            // Extract just the SQL part
            $sql = preg_replace('/^\$\w+_sql\s*=\s*["\']/', '', $matches[0]);
            $sql = preg_replace('/["\'];$/', '', $sql);
            return self::clean_sql($sql, $table_name);
        }
        
        return false;
    }
    
    /**
     * Clean and standardize the SQL statement
     */
    private static function clean_sql($sql, $table_name) {
        global $wpdb;
        $prefix = $wpdb->prefix;
        
        // Remove PHP variable references and quotes
        $sql = preg_replace('/^\$\w+\s*=\s*["\']/', '', $sql);
        $sql = preg_replace('/["\'];?$/', '', $sql);
        
        // Replace table name placeholder with actual prefixed name
        $sql = preg_replace('/\$\w+_table/', $prefix . $table_name, $sql);
        $sql = preg_replace('/\$' . preg_quote($table_name) . '_table/', $prefix . $table_name, $sql);
        $sql = preg_replace('/\$\w+/', $prefix . $table_name, $sql);
        
        // Replace charset collate placeholder
        $charset_collate = $wpdb->get_charset_collate();
        $sql = str_replace('$charset_collate', $charset_collate, $sql);
        
        // Ensure the table name has the correct prefix
        $sql = preg_replace('/CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?[`"\']?\w+[`"\']?/i', 
                           'CREATE TABLE IF NOT EXISTS ' . $prefix . $table_name, $sql);
        
        // Clean up any double spaces
        $sql = preg_replace('/\s+/', ' ', $sql);
        
        // Ensure proper formatting
        $sql = str_replace(' (', ' (', $sql);
        $sql = str_replace(') ', ')', $sql);
        
        return trim($sql);
    }
    
    /**
     * Get CREATE TABLE SQL for wp_pylons table
     */
    public static function get_pylons_create_sql() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Read directly from the ruplin.php file
        $ruplin_file = WP_CONTENT_DIR . '/plugins/ruplin/ruplin.php';
        
        if (file_exists($ruplin_file)) {
            $content = file_get_contents($ruplin_file);
            
            // Extract the pylons table creation SQL
            if (preg_match('/\$pylons_sql\s*=\s*"CREATE TABLE[^"]+pylons[^;]+;"/s', $content, $matches)) {
                $sql = $matches[0];
                $sql = preg_replace('/^\$pylons_sql\s*=\s*"/', '', $sql);
                $sql = preg_replace('/"$/', '', $sql);
                $sql = str_replace('$pylons_table', $prefix . 'pylons', $sql);
                $sql = str_replace('$charset_collate', $charset_collate, $sql);
                return $sql;
            }
        }
        
        // Fallback: return the known structure
        return self::get_pylons_fallback_sql();
    }
    
    /**
     * Get CREATE TABLE SQL for wp_zen_sitespren table
     */
    public static function get_zen_sitespren_create_sql() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Read directly from the ruplin.php file
        $ruplin_file = WP_CONTENT_DIR . '/plugins/ruplin/ruplin.php';
        
        if (file_exists($ruplin_file)) {
            $content = file_get_contents($ruplin_file);
            
            // Extract the zen_sitespren table creation SQL
            if (preg_match('/\$zen_sitespren_sql\s*=\s*"CREATE TABLE[^"]+zen_sitespren[^;]+;"/s', $content, $matches)) {
                $sql = $matches[0];
                $sql = preg_replace('/^\$zen_sitespren_sql\s*=\s*"/', '', $sql);
                $sql = preg_replace('/"$/', '', $sql);
                $sql = str_replace('$zen_sitespren_table', $prefix . 'zen_sitespren', $sql);
                $sql = str_replace('$charset_collate', $charset_collate, $sql);
                return $sql;
            }
        }
        
        // Fallback: return the known structure
        return self::get_zen_sitespren_fallback_sql();
    }
    
    /**
     * Fallback SQL for pylons table
     */
    private static function get_pylons_fallback_sql() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE IF NOT EXISTS {$prefix}pylons (
            pylon_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            rel_wp_post_id BIGINT(20) UNSIGNED DEFAULT NULL,
            plasma_page_id BIGINT(20) UNSIGNED DEFAULT NULL,
            pylon_archetype TEXT DEFAULT NULL,
            hero_mainheading TEXT DEFAULT NULL,
            hero_subheading TEXT DEFAULT NULL,
            PRIMARY KEY (pylon_id),
            KEY rel_wp_post_id (rel_wp_post_id),
            KEY plasma_page_id (plasma_page_id)
        ) $charset_collate;";
    }
    
    /**
     * Fallback SQL for zen_sitespren table
     */
    private static function get_zen_sitespren_fallback_sql() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE {$prefix}zen_sitespren (
            id varchar(36) NOT NULL,
            wppma_id INT UNSIGNED AUTO_INCREMENT UNIQUE,
            wppma_db_only_created_at datetime DEFAULT CURRENT_TIMESTAMP,
            wppma_db_only_updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT NULL,
            sitespren_base text,
            true_root_domain text,
            full_subdomain text,
            PRIMARY KEY (id)
        ) $charset_collate;";
    }
}