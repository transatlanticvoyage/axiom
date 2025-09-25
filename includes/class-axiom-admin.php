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
        $auto_convert = isset($_POST['auto_convert']) && $_POST['auto_convert'] === 'true';
        
        if (empty($sql)) {
            wp_send_json_error('No SQL provided');
        }
        
        // Auto-convert wp_ prefix if requested
        if ($auto_convert) {
            global $wpdb;
            $actual_prefix = $wpdb->prefix;
            
            // Pattern to match table names with wp_ prefix
            // This covers: ALTER TABLE wp_table, FROM wp_table, JOIN wp_table, etc.
            $patterns = array(
                '/\b(ALTER\s+TABLE\s+)wp_(\w+)/i',
                '/\b(FROM\s+)wp_(\w+)/i', 
                '/\b(JOIN\s+)wp_(\w+)/i',
                '/\b(INTO\s+)wp_(\w+)/i',
                '/\b(UPDATE\s+)wp_(\w+)/i',
                '/\b(INDEX\s+\w+\s+ON\s+)wp_(\w+)/i',
                '/\b(SHOW\s+COLUMNS\s+FROM\s+)wp_(\w+)/i',
                '/\b(DESCRIBE\s+)wp_(\w+)/i',
                '/\b(DESC\s+)wp_(\w+)/i'
            );
            
            foreach ($patterns as $pattern) {
                $sql = preg_replace($pattern, '${1}' . $actual_prefix . '${2}', $sql);
            }
        }
        
        // Security check - only allow certain operations
        $allowed_patterns = array(
            '/^ALTER\s+TABLE\s+\w+\s+ADD\s+COLUMN/i',
            '/^ALTER\s+TABLE\s+\w+\s+ADD\s+INDEX/i', 
            '/^ALTER\s+TABLE\s+\w+\s+ADD\s+KEY/i',
            '/^ALTER\s+TABLE\s+\w+\s+DROP\s+COLUMN/i',
            '/^CREATE\s+TABLE\s+\w+/i',
            '/^CREATE\s+INDEX/i',
            '/^INSERT\s+INTO\s+\w+/i',
            '/^REPLACE\s+INTO\s+\w+/i',
            '/^UPDATE\s+[\w_]+\s+SET/i',
            '/^DELETE\s+FROM\s+\w+/i',
            '/^SHOW\s+COLUMNS/i',
            '/^SHOW\s+TABLES/i',
            '/^DESCRIBE\s+\w+/i',
            '/^DESC\s+\w+/i',
            '/^SELECT\s+/i'
        );
        
        $is_allowed = false;
        foreach ($allowed_patterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                $is_allowed = true;
                break;
            }
        }
        
        if (!$is_allowed) {
            wp_send_json_error('SQL operation not allowed. Permitted commands: ALTER TABLE, CREATE TABLE, CREATE INDEX, INSERT, REPLACE, UPDATE, DELETE, SELECT, SHOW, DESCRIBE.');
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
        
        // Split SQL into multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)), function($stmt) {
            return !empty($stmt);
        });
        
        $total_statements = count($statements);
        $executed_statements = 0;
        $successful_statements = 0;
        $failed_statements = 0;
        $all_results = '';
        $summary_messages = array();
        $overall_success = true;
        
        foreach ($statements as $index => $statement) {
            $stmt_num = $index + 1;
            $all_results .= "=== STATEMENT {$stmt_num} ===\n";
            $all_results .= "SQL: {$statement}\n\n";
            
            $executed_statements++;
            $is_select_query = preg_match('/^(SHOW|DESCRIBE|DESC|SELECT)/i', trim($statement));
            
            if ($is_select_query) {
                // For SELECT-type queries, get results
                $results = $wpdb->get_results($statement, ARRAY_A);
                
                if ($results !== null && !$wpdb->last_error) {
                    $successful_statements++;
                    
                    if (!empty($results)) {
                        // Format results for display
                        $headers = array_keys($results[0]);
                        $all_results .= "RESULT: SUCCESS (" . count($results) . " rows returned)\n";
                        $all_results .= implode("\t", $headers) . "\n";
                        $all_results .= str_repeat("-", count($headers) * 15) . "\n";
                        
                        foreach ($results as $row) {
                            $all_results .= implode("\t", array_values($row)) . "\n";
                        }
                        $summary_messages[] = "Statement {$stmt_num}: SUCCESS - " . count($results) . " rows returned";
                    } else {
                        $all_results .= "RESULT: SUCCESS (No rows returned)\n";
                        $summary_messages[] = "Statement {$stmt_num}: SUCCESS - No rows returned";
                    }
                } else {
                    $failed_statements++;
                    $overall_success = false;
                    $error = $wpdb->last_error ?: 'Unknown error';
                    $all_results .= "RESULT: FAILED - {$error}\n";
                    $summary_messages[] = "Statement {$stmt_num}: FAILED - {$error}";
                }
            } else {
                // For non-SELECT queries (ALTER, CREATE, etc.)
                $result = $wpdb->query($statement);
                
                if ($result !== false && !$wpdb->last_error) {
                    $successful_statements++;
                    $all_results .= "RESULT: SUCCESS (Rows affected: {$result})\n";
                    $summary_messages[] = "Statement {$stmt_num}: SUCCESS - {$result} rows affected";
                } else {
                    $failed_statements++;
                    $overall_success = false;
                    $error = $wpdb->last_error ?: 'Unknown error';
                    $all_results .= "RESULT: FAILED - {$error}\n";
                    $summary_messages[] = "Statement {$stmt_num}: FAILED - {$error}";
                }
            }
            
            $all_results .= "\n" . str_repeat("=", 50) . "\n\n";
        }
        
        // Update operation status based on overall result
        $final_status = $overall_success ? 'completed' : 'failed';
        $error_message = $overall_success ? null : 'Some statements failed - see details';
        
        $wpdb->update(
            $wpdb->prefix . 'axiom_operations',
            array(
                'status' => $final_status,
                'error_message' => $error_message,
                'completed_at' => current_time('mysql')
            ),
            array('operation_id' => $operation_id)
        );
        
        // Prepare final response
        $summary = "Executed {$executed_statements} statements: {$successful_statements} successful, {$failed_statements} failed";
        
        if ($overall_success) {
            wp_send_json_success(array(
                'message' => "✅ ALL STATEMENTS SUCCESSFUL - {$summary}",
                'results' => $all_results,
                'details' => $summary_messages
            ));
        } else {
            // Send as success but with error message, so we can include results data
            wp_send_json_success(array(
                'message' => "❌ SOME STATEMENTS FAILED - {$summary}",
                'results' => $all_results,
                'details' => $summary_messages,
                'has_errors' => true
            ));
        }
    }
}