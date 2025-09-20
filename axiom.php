<?php
/**
 * Plugin Name: Axiom
 * Plugin URI: https://github.com/transatlanticvoyage/axiom
 * Description: Axiom - Central database schema management and synchronization tool for the Shenzi plugin ecosystem
 * Version: 1.0.0
 * Author: Shenzi Team
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AXIOM_PLUGIN_VERSION', '1.0.0');
define('AXIOM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AXIOM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once AXIOM_PLUGIN_PATH . 'includes/class-axiom-admin.php';
require_once AXIOM_PLUGIN_PATH . 'includes/class-axiom-schema-manager.php';

/**
 * Main Axiom Plugin Class
 */
class Axiom {
    
    private $admin;
    private $schema_manager;
    
    public function __construct() {
        // Initialize on plugins loaded to ensure all plugins are available
        add_action('plugins_loaded', array($this, 'init'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'on_activation'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize admin interface
        if (is_admin()) {
            $this->admin = new Axiom_Admin();
        }
        
        // Initialize schema manager
        $this->schema_manager = new Axiom_Schema_Manager();
        
        // Add admin bar menu for quick access
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }
    
    /**
     * Plugin activation
     */
    public function on_activation() {
        // Create axiom tables for tracking schema management
        $this->create_axiom_tables();
        
        // Perform initial schema scan
        if (class_exists('Axiom_Schema_Manager')) {
            $schema_manager = new Axiom_Schema_Manager();
            $schema_manager->perform_initial_scan();
        }
    }
    
    /**
     * Create axiom-specific tables
     */
    private function create_axiom_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table to track schema operations
        $operations_table = $wpdb->prefix . 'axiom_operations';
        $sql_operations = "CREATE TABLE $operations_table (
            operation_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            operation_type VARCHAR(50) NOT NULL,
            target_plugin VARCHAR(100),
            target_table VARCHAR(100),
            operation_data LONGTEXT,
            status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
            error_message TEXT,
            user_id BIGINT(20) UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            PRIMARY KEY (operation_id),
            KEY idx_status (status),
            KEY idx_plugin (target_plugin),
            KEY idx_created (created_at)
        ) $charset_collate;";
        
        // Table to track plugin schema versions
        $versions_table = $wpdb->prefix . 'axiom_plugin_versions';
        $sql_versions = "CREATE TABLE $versions_table (
            version_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            plugin_slug VARCHAR(100) NOT NULL,
            schema_version VARCHAR(20),
            axiom_version VARCHAR(20),
            last_scan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            schema_status ENUM('current', 'outdated', 'unknown', 'error') DEFAULT 'unknown',
            notes TEXT,
            PRIMARY KEY (version_id),
            UNIQUE KEY unique_plugin (plugin_slug),
            KEY idx_status (schema_status),
            KEY idx_scan (last_scan)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_operations);
        dbDelta($sql_versions);
    }
    
    /**
     * Add axiom menu to admin bar
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'axiom-schema',
            'title' => '⚙️ Axiom Schema',
            'href' => admin_url('admin.php?page=axiom-dashboard'),
            'meta' => array(
                'title' => 'Axiom Schema Management'
            )
        ));
        
        // Add submenu items
        $wp_admin_bar->add_node(array(
            'parent' => 'axiom-schema',
            'id' => 'axiom-health-check',
            'title' => 'Health Check',
            'href' => admin_url('admin.php?page=axiom-dashboard&tab=health'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'axiom-schema',
            'id' => 'axiom-sync-plugins',
            'title' => 'Sync Plugins',
            'href' => admin_url('admin.php?page=axiom-dashboard&tab=sync'),
        ));
    }
}

// Initialize the plugin
new Axiom();