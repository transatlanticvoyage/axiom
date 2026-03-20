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
        
        // Axiom Icepick AJAX handlers
        add_action('wp_ajax_axiom_icepick_validate', array($this, 'ajax_icepick_validate'));
        add_action('wp_ajax_axiom_icepick_read', array($this, 'ajax_icepick_read'));
        add_action('wp_ajax_axiom_icepick_update', array($this, 'ajax_icepick_update'));
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
            3.05
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
        
        add_submenu_page(
            'axiom-dashboard',
            'Axiom Shovel - Database Browser',
            'Axiom Shovel',
            'manage_options',
            'axiom_shovel',
            array($this, 'display_shovel_page')
        );
        
        add_submenu_page(
            'axiom-dashboard',
            'Venlazar',
            'Venlazar',
            'manage_options',
            'axiomvenlazar',
            array($this, 'display_venlazar_page')
        );
        
        add_submenu_page(
            'axiom-dashboard',
            'Axiom Icepick Editor',
            'Axiom Icepick Editor',
            'manage_options',
            'axiom_icepick',
            array($this, 'display_icepick_page')
        );
        
        add_submenu_page(
            'axiom-dashboard',
            'Axiom Image ID Finder',
            'Axiom Image ID Finder',
            'manage_options',
            'axiom_image_id_finder',
            array($this, 'display_image_id_finder_page')
        );
        
        add_submenu_page(
            'axiom-dashboard',
            'DB Field Discrepancy Checker',
            'DB Field Discrepancy Checker',
            'manage_options',
            'db-field-discrepancy-checker',
            array($this, 'display_db_field_discrepancy_page')
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
        
        if ($hook === 'axiom_page_axiom_shovel') {
            wp_enqueue_script('axiom-shovel', AXIOM_PLUGIN_URL . 'assets/js/axiom-shovel.js', array('jquery'), AXIOM_PLUGIN_VERSION, true);
            wp_enqueue_style('axiom-shovel', AXIOM_PLUGIN_URL . 'assets/css/axiom-shovel.css', array(), AXIOM_PLUGIN_VERSION);
        }
        
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
     * Display Axiom Shovel page
     */
    public function display_shovel_page() {
        global $axiom_shovel_db;
        if ($axiom_shovel_db) {
            $axiom_shovel_db->display_shovel_page();
        }
    }
    
    /**
     * Display Venlazar page
     */
    public function display_venlazar_page() {
        include AXIOM_PLUGIN_PATH . 'includes/pages/venlazar.php';
    }
    
    /**
     * Display Axiom Icepick page with aggressive notice suppression
     */
    public function display_icepick_page() {
        // Aggressive admin notice suppression
        add_action('admin_print_styles', function() {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            remove_all_actions('network_admin_notices');
            remove_all_actions('user_admin_notices');
            
            // Remove specific plugin notices
            remove_all_actions('admin_notices', 10);
            remove_all_actions('admin_notices', 20);
            
            // Hide notices with CSS as backup
            echo '<style>
                .notice, .notice-error, .notice-warning, .notice-success, .notice-info,
                .update-nag, .updated, .error, .is-dismissible, .wp-core-ui .notice {
                    display: none !important;
                }
            </style>';
        }, 1);
        
        // Additional suppression before page loads
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        
        include AXIOM_PLUGIN_PATH . 'includes/pages/icepick.php';
    }
    
    /**
     * Display Image ID Finder page
     */
    public function display_image_id_finder_page() {
        // Aggressive admin notice suppression
        add_action('admin_print_styles', function() {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            remove_all_actions('network_admin_notices');
            remove_all_actions('user_admin_notices');
            
            // Remove specific plugin notices
            remove_all_actions('admin_notices', 10);
            remove_all_actions('admin_notices', 20);
            
            // Hide notices with CSS as backup
            echo '<style>
                .notice, .notice-error, .notice-warning, .notice-success, .notice-info,
                .update-nag, .updated, .error, .is-dismissible, .wp-core-ui .notice {
                    display: none !important;
                }
            </style>';
        }, 1);
        
        // Additional suppression before page loads
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        
        include AXIOM_PLUGIN_PATH . 'includes/pages/image-id-finder.php';
    }
    
    /**
     * Display DB Field Discrepancy Checker page
     */
    public function display_db_field_discrepancy_page() {
        // Load and initialize the controller
        require_once AXIOM_PLUGIN_PATH . 'db-field-discrepancy-checker/class-db-field-discrepancy-controller.php';
        
        $controller = new Axiom_DB_Field_Discrepancy_Controller();
        $controller->init_hooks();
        $controller->render();
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
        
        // Remove SQL comments
        // Remove -- style comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        // Remove /* */ style comments
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        // Trim again after removing comments
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
            '/^ALTER\s+TABLE\s+[\w`]+\s+ADD\s+COLUMN\s+/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+ADD\s+INDEX/i', 
            '/^ALTER\s+TABLE\s+[\w`]+\s+ADD\s+KEY/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+DROP\s+COLUMN/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+DROP\s+INDEX/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+DROP\s+KEY/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+DROP\s+PRIMARY/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+DROP\s+FOREIGN/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+MODIFY\s+COLUMN/i',
            '/^ALTER\s+TABLE\s+[\w`]+\s+CHANGE\s+COLUMN/i',
            '/^ALTER\s+TABLE\s+[\w`]+/i',
            '/^CREATE\s+TABLE\s+[\w`]+/i',
            '/^CREATE\s+INDEX/i',
            '/^DROP\s+TABLE(\s+IF\s+EXISTS)?\s+/i',
            '/^DROP\s+INDEX/i',
            '/^INSERT\s+INTO\s+[\w`]+/i',
            '/^REPLACE\s+INTO\s+[\w`]+/i',
            '/^UPDATE\s+[\w_`]+\s+SET/i',
            '/^DELETE\s+FROM\s+[\w`]+/i',
            '/^SHOW\s+COLUMNS/i',
            '/^SHOW\s+TABLES/i',
            '/^SHOW\s+INDEXES/i',
            '/^SHOW\s+INDEX/i',
            '/^DESCRIBE\s+[\w`]+/i',
            '/^DESC\s+[\w`]+/i',
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
            wp_send_json_error('SQL operation not allowed. Permitted commands: ALTER TABLE, CREATE TABLE, DROP TABLE, CREATE INDEX, INSERT, REPLACE, UPDATE, DELETE, SELECT, SHOW, DESCRIBE.');
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
    
    /**
     * AJAX: Icepick Validate Table/Row/Column
     */
    public function ajax_icepick_validate() {
        check_ajax_referer('axiom_icepick_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $table = sanitize_text_field($_POST['table']);
        $row_id = sanitize_text_field($_POST['row_id']);
        $column = sanitize_text_field($_POST['column']);
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table
        ));
        
        if (!$table_exists) {
            wp_send_json_error("Table '{$table}' does not exist");
        }
        
        // Get table structure to find primary key
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table}`");
        $primary_key = null;
        $column_exists = false;
        
        foreach ($columns as $col) {
            if ($col->Key === 'PRI') {
                $primary_key = $col->Field;
            }
            if ($col->Field === $column) {
                $column_exists = true;
            }
        }
        
        if (!$column_exists) {
            wp_send_json_error("Column '{$column}' does not exist in table '{$table}'");
        }
        
        if (!$primary_key) {
            // Try common primary key names
            $common_keys = array('id', 'ID', $table . '_id', str_replace($wpdb->prefix, '', $table) . '_id');
            foreach ($common_keys as $key) {
                if ($wpdb->get_var("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$key}'")) {
                    $primary_key = $key;
                    break;
                }
            }
            
            if (!$primary_key) {
                wp_send_json_error("Could not determine primary key for table '{$table}'");
            }
        }
        
        // Check if row exists
        $row_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE `{$primary_key}` = %s",
            $row_id
        ));
        
        if (!$row_exists) {
            wp_send_json_error("Row with {$primary_key}='{$row_id}' does not exist in table '{$table}'");
        }
        
        wp_send_json_success(array(
            'message' => "Validation successful! Table: {$table}, Row: {$row_id} (PK: {$primary_key}), Column: {$column} - All exist and are accessible."
        ));
    }
    
    /**
     * AJAX: Icepick Read Value
     */
    public function ajax_icepick_read() {
        check_ajax_referer('axiom_icepick_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $table = sanitize_text_field($_POST['table']);
        $row_id = sanitize_text_field($_POST['row_id']);
        $column = sanitize_text_field($_POST['column']);
        
        // Get primary key
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table}`");
        $primary_key = null;
        
        foreach ($columns as $col) {
            if ($col->Key === 'PRI') {
                $primary_key = $col->Field;
                break;
            }
        }
        
        if (!$primary_key) {
            // Try common primary key names
            $common_keys = array('id', 'ID', $table . '_id', str_replace($wpdb->prefix, '', $table) . '_id');
            foreach ($common_keys as $key) {
                if ($wpdb->get_var("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$key}'")) {
                    $primary_key = $key;
                    break;
                }
            }
        }
        
        if (!$primary_key) {
            wp_send_json_error("Could not determine primary key for table '{$table}'");
        }
        
        // Get the value
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT `{$column}` FROM `{$table}` WHERE `{$primary_key}` = %s",
            $row_id
        ));
        
        if ($value === null && $wpdb->last_error) {
            wp_send_json_error("Database error: " . $wpdb->last_error);
        }
        
        // Handle serialized data
        $display_value = $value;
        if (is_serialized($value)) {
            $unserialized = @unserialize($value);
            if ($unserialized !== false) {
                $display_value = "SERIALIZED DATA:\n" . print_r($unserialized, true);
            }
        }
        
        wp_send_json_success(array(
            'table' => $table,
            'row_id' => $row_id,
            'column' => $column,
            'primary_key' => $primary_key,
            'value' => $display_value === null ? '(NULL)' : $display_value
        ));
    }
    
    /**
     * AJAX: Icepick Update Value
     */
    public function ajax_icepick_update() {
        check_ajax_referer('axiom_icepick_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $table = sanitize_text_field($_POST['table']);
        $row_id = sanitize_text_field($_POST['row_id']);
        $column = sanitize_text_field($_POST['column']);
        $new_value = wp_unslash($_POST['new_value']); // Don't sanitize the value itself
        
        // Get primary key
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table}`");
        $primary_key = null;
        
        foreach ($columns as $col) {
            if ($col->Key === 'PRI') {
                $primary_key = $col->Field;
                break;
            }
        }
        
        if (!$primary_key) {
            // Try common primary key names
            $common_keys = array('id', 'ID', $table . '_id', str_replace($wpdb->prefix, '', $table) . '_id');
            foreach ($common_keys as $key) {
                if ($wpdb->get_var("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$key}'")) {
                    $primary_key = $key;
                    break;
                }
            }
        }
        
        if (!$primary_key) {
            wp_send_json_error("Could not determine primary key for table '{$table}'");
        }
        
        // Get old value first
        $old_value = $wpdb->get_var($wpdb->prepare(
            "SELECT `{$column}` FROM `{$table}` WHERE `{$primary_key}` = %s",
            $row_id
        ));
        
        // Handle serialized data for display
        $display_old_value = $old_value;
        if (is_serialized($old_value)) {
            $unserialized = @unserialize($old_value);
            if ($unserialized !== false) {
                $display_old_value = "SERIALIZED DATA:\n" . print_r($unserialized, true);
            }
        }
        
        // Check if new value should be serialized
        $value_to_save = $new_value;
        
        // Try to detect if it's meant to be serialized (e.g., starts with a:, s:, etc.)
        if (is_serialized($new_value)) {
            // It's already serialized, use as is
            $value_to_save = $new_value;
        } elseif (is_serialized($old_value)) {
            // Old value was serialized, so we might need to serialize the new value
            // Try to parse as JSON first
            $json_decoded = json_decode($new_value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value_to_save = serialize($json_decoded);
            } else {
                // If not JSON, just save as string (user might be intentionally changing to non-serialized)
                $value_to_save = $new_value;
            }
        }
        
        // Update the value
        $result = $wpdb->update(
            $table,
            array($column => $value_to_save),
            array($primary_key => $row_id)
        );
        
        if ($result === false) {
            wp_send_json_error("Update failed: " . $wpdb->last_error);
        }
        
        // Log the operation
        $wpdb->insert(
            $wpdb->prefix . 'axiom_operations',
            array(
                'operation_type' => 'icepick_update',
                'target_plugin' => 'manual',
                'target_table' => $table,
                'operation_data' => json_encode(array(
                    'table' => $table,
                    'primary_key' => $primary_key,
                    'row_id' => $row_id,
                    'column' => $column,
                    'old_value' => substr($old_value, 0, 1000),
                    'new_value' => substr($new_value, 0, 1000)
                )),
                'status' => 'completed',
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'completed_at' => current_time('mysql')
            )
        );
        
        wp_send_json_success(array(
            'table' => $table,
            'row_id' => $row_id,
            'column' => $column,
            'primary_key' => $primary_key,
            'old_value' => $display_old_value === null ? '(NULL)' : $display_old_value,
            'new_value' => $new_value,
            'rows_affected' => $result
        ));
    }
}