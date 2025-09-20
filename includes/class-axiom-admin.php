<?php
/**
 * Axiom Admin Class
 */

class Axiom_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_axiom_health_check', array($this, 'ajax_health_check'));
        add_action('wp_ajax_axiom_sync_plugin', array($this, 'ajax_sync_plugin'));
        add_action('wp_ajax_axiom_refresh_registry', array($this, 'ajax_refresh_registry'));
        add_action('wp_ajax_axiom_rebuild_table', array($this, 'ajax_rebuild_table'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Axiom Schema Manager',
            'Axiom',
            'manage_options',
            'axiom-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-database-view',
            3
        );
        
        // Add submenu pages
        add_submenu_page(
            'axiom-dashboard',
            'Schema Health Check',
            'Health Check',
            'manage_options',
            'axiom-health',
            array($this, 'display_health_page')
        );
        
        add_submenu_page(
            'axiom-dashboard',
            'Plugin Sync',
            'Plugin Sync',
            'manage_options',
            'axiom-sync',
            array($this, 'display_sync_page')
        );
        
        add_submenu_page(
            'axiom-dashboard',
            'Schema Operations',
            'Operations',
            'manage_options',
            'axiom-operations',
            array($this, 'display_operations_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'axiom') === false) {
            return;
        }
        
        wp_enqueue_script('axiom-admin', AXIOM_PLUGIN_URL . 'assets/js/axiom-admin.js', array('jquery'), AXIOM_PLUGIN_VERSION, true);
        wp_enqueue_style('axiom-admin', AXIOM_PLUGIN_URL . 'assets/css/axiom-admin.css', array(), AXIOM_PLUGIN_VERSION);
        
        wp_localize_script('axiom-admin', 'axiom_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('axiom_nonce')
        ));
    }
    
    /**
     * Display main dashboard
     */
    public function display_dashboard() {
        $schema_manager = new Axiom_Schema_Manager();
        $shenzi_plugins = $schema_manager->get_shenzi_plugins();
        $schema_status = $schema_manager->get_overall_schema_status();
        
        include AXIOM_PLUGIN_PATH . 'includes/pages/dashboard.php';
    }
    
    /**
     * Display health check page
     */
    public function display_health_page() {
        $schema_manager = new Axiom_Schema_Manager();
        $health_report = $schema_manager->generate_health_report();
        
        include AXIOM_PLUGIN_PATH . 'includes/pages/health-check.php';
    }
    
    /**
     * Display sync page
     */
    public function display_sync_page() {
        $schema_manager = new Axiom_Schema_Manager();
        $plugins_status = $schema_manager->get_plugins_sync_status();
        
        include AXIOM_PLUGIN_PATH . 'includes/pages/plugin-sync.php';
    }
    
    /**
     * Display operations page
     */
    public function display_operations_page() {
        global $wpdb;
        
        $operations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}axiom_operations 
             ORDER BY created_at DESC 
             LIMIT 50"
        );
        
        include AXIOM_PLUGIN_PATH . 'includes/pages/operations.php';
    }
    
    /**
     * AJAX: Health check
     */
    public function ajax_health_check() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $schema_manager = new Axiom_Schema_Manager();
        $health_report = $schema_manager->generate_health_report();
        
        wp_send_json_success($health_report);
    }
    
    /**
     * AJAX: Sync plugin
     */
    public function ajax_sync_plugin() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin = sanitize_text_field($_POST['plugin']);
        
        $schema_manager = new Axiom_Schema_Manager();
        $result = $schema_manager->sync_plugin_schema($plugin);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Refresh plugin registry
     */
    public function ajax_refresh_registry() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $schema_manager = new Axiom_Schema_Manager();
        $result = $schema_manager->refresh_plugin_registry();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Rebuild table
     */
    public function ajax_rebuild_table() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $table = sanitize_text_field($_POST['table']);
        $backup = isset($_POST['backup']) && $_POST['backup'] === 'true';
        
        $schema_manager = new Axiom_Schema_Manager();
        $result = $schema_manager->rebuild_table($table, $backup);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}