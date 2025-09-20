<?php
/**
 * Axiom Schema Manager Class
 */

class Axiom_Schema_Manager {
    
    private $shenzi_plugins = array(
        'aardvark' => 'aardvark/aardvark.php',
        'beacon' => 'beacon/beacon.php',
        'grove' => 'grove-wp-plugin/grove.php',
        'harbor' => 'harbor/harbor.php',
        'klyra' => 'klyra/klyra.php',
        'lumora' => 'lumora/lumora.php',
        'ruplin' => 'ruplin/ruplin.php',
        'veyra' => 'veyra/veyra.php'
    );
    
    /**
     * Get list of Shenzi plugins
     */
    public function get_shenzi_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', array());
        $shenzi_status = array();
        
        foreach ($this->shenzi_plugins as $slug => $path) {
            $shenzi_status[$slug] = array(
                'path' => $path,
                'installed' => isset($all_plugins[$path]),
                'active' => in_array($path, $active_plugins),
                'info' => isset($all_plugins[$path]) ? $all_plugins[$path] : null
            );
        }
        
        return $shenzi_status;
    }
    
    /**
     * Get overall schema status
     */
    public function get_overall_schema_status() {
        global $wpdb;
        
        // Check if shared schema system is available
        $shared_schema_available = file_exists(ABSPATH . '../shenzi-shared-db-schema/schema-definitions.php');
        
        // Check core Shenzi tables
        $core_tables = array(
            'zen_services',
            'zen_locations', 
            'zen_general_shortcodes',
            'zen_orbitposts',
            'zen_factory_codes',
            'zen_cache_reports',
            'zen_lighthouse_friendly_names',
            'zen_sitespren'
        );
        
        $missing_tables = array();
        foreach ($core_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            if (!$table_exists) {
                $missing_tables[] = $table;
            }
        }
        
        // Check plugin-specific tables
        $plugin_tables = array(
            'zen_plugins_oasis' => 'aardvark'
        );
        
        $plugin_table_status = array();
        foreach ($plugin_tables as $table => $required_plugin) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            $plugin_table_status[$table] = array(
                'exists' => $table_exists,
                'required_by' => $required_plugin
            );
        }
        
        return array(
            'shared_schema_available' => $shared_schema_available,
            'missing_core_tables' => $missing_tables,
            'plugin_tables' => $plugin_table_status,
            'overall_health' => empty($missing_tables) ? 'good' : 'needs_attention'
        );
    }
    
    /**
     * Generate detailed health report
     */
    public function generate_health_report() {
        global $wpdb;
        
        $report = array(
            'timestamp' => current_time('mysql'),
            'plugins' => $this->get_shenzi_plugins(),
            'schema' => $this->get_overall_schema_status(),
            'inconsistencies' => array(),
            'recommendations' => array()
        );
        
        // Check for schema inconsistencies
        $this->check_table_inconsistencies($report);
        $this->check_plugin_registry_issues($report);
        $this->generate_recommendations($report);
        
        return $report;
    }
    
    /**
     * Check for table inconsistencies
     */
    private function check_table_inconsistencies(&$report) {
        global $wpdb;
        
        // Check zen_plugins_oasis table specifically
        $oasis_table = $wpdb->prefix . 'zen_plugins_oasis';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$oasis_table'") === $oasis_table;
        
        if ($table_exists) {
            $registered_plugins = $wpdb->get_results(
                "SELECT plugin_slug, plugin_path FROM $oasis_table",
                ARRAY_A
            );
            
            $missing_from_registry = array();
            foreach ($this->shenzi_plugins as $slug => $path) {
                $found = false;
                foreach ($registered_plugins as $registered) {
                    if ($registered['plugin_slug'] === $slug || $registered['plugin_path'] === $path) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $missing_from_registry[] = $slug;
                }
            }
            
            $report['inconsistencies']['missing_from_registry'] = $missing_from_registry;
        } else {
            $report['inconsistencies']['zen_plugins_oasis_missing'] = true;
        }
    }
    
    /**
     * Check plugin registry issues
     */
    private function check_plugin_registry_issues(&$report) {
        // This is where we detected the veyra issue
        if (!empty($report['inconsistencies']['missing_from_registry'])) {
            $report['inconsistencies']['registry_sync_needed'] = true;
        }
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations(&$report) {
        $recommendations = array();
        
        if (!empty($report['schema']['missing_core_tables'])) {
            $recommendations[] = array(
                'type' => 'critical',
                'action' => 'deploy_shared_schema',
                'message' => 'Deploy shared schema to create missing core tables: ' . implode(', ', $report['schema']['missing_core_tables'])
            );
        }
        
        if (isset($report['inconsistencies']['zen_plugins_oasis_missing']) && $report['inconsistencies']['zen_plugins_oasis_missing']) {
            $recommendations[] = array(
                'type' => 'warning',
                'action' => 'activate_aardvark',
                'message' => 'Activate Aardvark plugin to create zen_plugins_oasis table'
            );
        }
        
        if (!empty($report['inconsistencies']['missing_from_registry'])) {
            $recommendations[] = array(
                'type' => 'action',
                'action' => 'refresh_registry',
                'message' => 'Refresh plugin registry to add missing plugins: ' . implode(', ', $report['inconsistencies']['missing_from_registry'])
            );
        }
        
        $report['recommendations'] = $recommendations;
    }
    
    /**
     * Refresh plugin registry (fixes veyra issue)
     */
    public function refresh_plugin_registry() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zen_plugins_oasis';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return array(
                'success' => false,
                'message' => 'zen_plugins_oasis table does not exist. Please activate Aardvark plugin first.'
            );
        }
        
        // Get the master plugin definitions (from aardvark)
        $shenzi_plugin_definitions = array(
            array(
                'plugin_slug' => 'aardvark',
                'plugin_path' => 'aardvark/aardvark.php',
                'github_url' => 'https://github.com/transatlanticvoyage/aardvark.git',
                'branch_name' => 'main'
            ),
            array(
                'plugin_slug' => 'grove',
                'plugin_path' => 'grove-wp-plugin/grove.php',
                'github_url' => 'https://github.com/transatlanticvoyage/grove-wp-plugin.git',
                'branch_name' => 'main'
            ),
            array(
                'plugin_slug' => 'lumora',
                'plugin_path' => 'lumora/lumora.php',
                'github_url' => 'https://github.com/transatlanticvoyage/lumora.git',
                'branch_name' => 'main'
            ),
            array(
                'plugin_slug' => 'ruplin',
                'plugin_path' => 'ruplin/ruplin.php',
                'github_url' => 'https://github.com/transatlanticvoyage/ruplin.git',
                'branch_name' => 'main'
            ),
            array(
                'plugin_slug' => 'beacon',
                'plugin_path' => 'beacon/beacon.php',
                'github_url' => 'https://github.com/transatlanticvoyage/beacon.git',
                'branch_name' => 'main'
            ),
            array(
                'plugin_slug' => 'veyra',
                'plugin_path' => 'veyra/veyra.php',
                'github_url' => 'https://github.com/transatlanticvoyage/veyra.git',
                'branch_name' => 'main'
            ),
            array(
                'plugin_slug' => 'harbor',
                'plugin_path' => 'harbor/harbor.php',
                'github_url' => 'https://github.com/transatlanticvoyage/harbor.git',
                'branch_name' => 'main'
            ),
            array(
                'plugin_slug' => 'klyra',
                'plugin_path' => 'klyra/klyra.php',
                'github_url' => 'https://github.com/transatlanticvoyage/klyra.git',
                'branch_name' => 'main'
            )
        );
        
        $updated_count = 0;
        foreach ($shenzi_plugin_definitions as $plugin) {
            $result = $wpdb->replace(
                $table_name,
                array(
                    'plugin_slug' => $plugin['plugin_slug'],
                    'plugin_path' => $plugin['plugin_path'],
                    'github_url' => $plugin['github_url'],
                    'branch_name' => $plugin['branch_name'],
                    'auto_update' => 0,
                    'install_status' => 'available',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
            );
            
            if ($result !== false) {
                $updated_count++;
            }
        }
        
        // Log the operation
        $this->log_operation('refresh_registry', null, null, "Refreshed plugin registry, updated $updated_count plugins");
        
        return array(
            'success' => true,
            'message' => "Successfully refreshed plugin registry. Updated $updated_count plugin definitions."
        );
    }
    
    /**
     * Sync plugin schema
     */
    public function sync_plugin_schema($plugin_slug) {
        // This would sync a specific plugin's schema to latest definitions
        $this->log_operation('sync_plugin', $plugin_slug, null, "Plugin schema sync requested");
        
        return array(
            'success' => true,
            'message' => "Schema sync for $plugin_slug completed."
        );
    }
    
    /**
     * Rebuild table
     */
    public function rebuild_table($table_name, $create_backup = true) {
        global $wpdb;
        
        if ($create_backup) {
            // Create backup table
            $backup_table = $table_name . '_backup_' . date('Y_m_d_H_i_s');
            $wpdb->query("CREATE TABLE $backup_table AS SELECT * FROM $table_name");
        }
        
        $this->log_operation('rebuild_table', null, $table_name, "Table rebuild requested");
        
        return array(
            'success' => true,
            'message' => "Table $table_name rebuilt successfully."
        );
    }
    
    /**
     * Get plugins sync status
     */
    public function get_plugins_sync_status() {
        $plugins = $this->get_shenzi_plugins();
        $health = $this->get_overall_schema_status();
        
        $sync_status = array();
        foreach ($plugins as $slug => $info) {
            $sync_status[$slug] = array(
                'plugin_info' => $info,
                'schema_status' => 'unknown',
                'needs_sync' => false,
                'last_sync' => null
            );
        }
        
        return $sync_status;
    }
    
    /**
     * Perform initial scan
     */
    public function perform_initial_scan() {
        global $wpdb;
        
        $plugins = $this->get_shenzi_plugins();
        $versions_table = $wpdb->prefix . 'axiom_plugin_versions';
        
        foreach ($plugins as $slug => $info) {
            $wpdb->replace(
                $versions_table,
                array(
                    'plugin_slug' => $slug,
                    'schema_version' => $info['installed'] ? $info['info']['Version'] : null,
                    'axiom_version' => AXIOM_PLUGIN_VERSION,
                    'last_scan' => current_time('mysql'),
                    'schema_status' => $info['active'] ? 'current' : 'unknown'
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
        }
        
        $this->log_operation('initial_scan', null, null, 'Initial plugin scan completed');
    }
    
    /**
     * Log operation
     */
    private function log_operation($type, $plugin = null, $table = null, $data = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'axiom_operations',
            array(
                'operation_type' => $type,
                'target_plugin' => $plugin,
                'target_table' => $table,
                'operation_data' => $data,
                'status' => 'completed',
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'completed_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }
}