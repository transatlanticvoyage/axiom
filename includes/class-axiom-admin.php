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
        add_action('wp_ajax_axiom_execute_sql', array($this, 'ajax_execute_sql'));
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
        
        add_submenu_page(
            'axiom-dashboard',
            'Axiom Plunger - Direct Schema Editor',
            'Axiom Plunger',
            'manage_options',
            'axiomplunger',
            array($this, 'display_plunger_page')
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
     * Display Axiom Plunger page
     */
    public function display_plunger_page() {
        include AXIOM_PLUGIN_PATH . 'includes/pages/plunger.php';
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
    
    /**
     * AJAX: Execute SQL
     */
    public function ajax_execute_sql() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $sql = stripslashes($_POST['sql']);
        $sql = trim($sql);
        
        if (empty($sql)) {
            wp_send_json_error('No SQL provided');
        }
        
        // Security check - only allow certain operations
        $allowed_patterns = array(
            '/^ALTER\s+TABLE\s+\w+\s+ADD\s+COLUMN/i',
            '/^ALTER\s+TABLE\s+\w+\s+ADD\s+INDEX/i', 
            '/^ALTER\s+TABLE\s+\w+\s+ADD\s+KEY/i',
            '/^CREATE\s+INDEX/i',
            '/^SHOW\s+COLUMNS/i',
            '/^SHOW\s+TABLES/i',
            '/^DESCRIBE\s+\w+/i',
            '/^DESC\s+\w+/i'
        );
        
        $is_allowed = false;
        foreach ($allowed_patterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                $is_allowed = true;
                break;
            }
        }
        
        if (!$is_allowed) {
            wp_send_json_error('SQL operation not allowed. Only ALTER TABLE ADD COLUMN, ADD INDEX, CREATE INDEX, and SHOW/DESCRIBE commands are permitted.');
        }
        
        global $wpdb;
        
        // Log the operation
        $wpdb->insert(
            $wpdb->prefix . 'axiom_operations',
            array(
                'operation_type' => 'direct_sql',
                'target_plugin' => 'manual',
                'target_table' => 'multiple',
                'operation_data' => $sql,
                'status' => 'running',
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );
        
        $operation_id = $wpdb->insert_id;
        
        // Execute the SQL
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            // Update operation status
            $wpdb->update(
                $wpdb->prefix . 'axiom_operations',
                array(
                    'status' => 'completed',
                    'completed_at' => current_time('mysql')
                ),
                array('operation_id' => $operation_id)
            );
            
            if ($wpdb->last_error) {
                wp_send_json_error('SQL executed but with warning: ' . $wpdb->last_error);
            } else {
                wp_send_json_success('SQL executed successfully. Rows affected: ' . $result);
            }
        } else {
            // Update operation status
            $wpdb->update(
                $wpdb->prefix . 'axiom_operations',
                array(
                    'status' => 'failed',
                    'error_message' => $wpdb->last_error,
                    'completed_at' => current_time('mysql')
                ),
                array('operation_id' => $operation_id)
            );
            
            wp_send_json_error('SQL execution failed: ' . $wpdb->last_error);
        }
    }
}