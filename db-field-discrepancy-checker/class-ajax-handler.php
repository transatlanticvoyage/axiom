<?php
/**
 * AJAX Handler for DB Field Discrepancy Checker
 * 
 * @package Axiom
 * @subpackage DbFieldDiscrepancyChecker
 */

if (!defined('ABSPATH')) {
    exit;
}

class Axiom_DB_Discrepancy_Ajax_Handler {
    
    public function __construct() {
        add_action('wp_ajax_axiom_run_alter_statements', array($this, 'handle_run_alter_statements'));
    }
    
    /**
     * Handle AJAX request to run ALTER TABLE statements
     */
    public function handle_run_alter_statements() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'axiom_db_discrepancy_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get the SQL statements
        $sql_statements = isset($_POST['sql']) ? stripslashes($_POST['sql']) : '';
        $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
        
        if (empty($sql_statements)) {
            wp_send_json_error('No SQL statements provided');
        }
        
        // Check if it's the "no missing columns" message
        if (strpos($sql_statements, 'No missing columns found') !== false) {
            wp_send_json_success(array(
                'message' => 'No columns need to be added. Table structure already matches plugin definition.',
                'statements_run' => 0,
                'successes' => 0,
                'errors' => 0,
                'details' => array()
            ));
        }
        
        global $wpdb;
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_statements)));
        
        $results = array(
            'total_statements' => count($statements),
            'successes' => 0,
            'errors' => 0,
            'details' => array()
        );
        
        // Track start time
        $start_time = microtime(true);

        // Remember whether wpdb was showing errors before we start suppressing them, so it can
        // be put back exactly as it was. Calling show_errors() with no argument forces display
        // ON regardless of WP_DEBUG, which would let any later failed query in this request
        // echo raw SQL into the JSON response and corrupt the payload.
        $previous_show_errors = $wpdb->show_errors;

        foreach ($statements as $index => $statement) {
            if (empty($statement)) {
                continue;
            }
            
            // Add semicolon back
            $statement = $statement . ';';
            
            // Validate that it's an ALTER TABLE ADD COLUMN statement for safety
            if (!preg_match('/^\s*ALTER\s+TABLE\s+[`\w]+\s+ADD\s+COLUMN\s+/i', $statement)) {
                $results['details'][] = array(
                    'statement' => $statement,
                    'status' => 'error',
                    'message' => 'Invalid statement: Only ALTER TABLE ADD COLUMN statements are allowed',
                    'statement_num' => $index + 1
                );
                $results['errors']++;
                continue;
            }
            
            // Execute the statement
            $wpdb->hide_errors(); // We'll handle errors ourselves
            $result = $wpdb->query($statement);
            
            if ($result === false) {
                $error = $wpdb->last_error;
                
                // Check if it's a "column already exists" error
                if (stripos($error, 'duplicate column') !== false || 
                    stripos($error, 'column.*already exists') !== false ||
                    stripos($error, 'Duplicate column name') !== false) {
                    
                    // Extract column name from statement
                    preg_match('/ADD\s+COLUMN\s+`?(\w+)`?/i', $statement, $matches);
                    $column_name = isset($matches[1]) ? $matches[1] : 'unknown';
                    
                    $results['details'][] = array(
                        'statement' => $statement,
                        'status' => 'skipped',
                        'message' => "Column '{$column_name}' already exists - skipped",
                        'statement_num' => $index + 1
                    );
                } else {
                    $results['details'][] = array(
                        'statement' => $statement,
                        'status' => 'error',
                        'message' => $error ?: 'Unknown error occurred',
                        'statement_num' => $index + 1
                    );
                    $results['errors']++;
                }
            } else {
                // Extract column name for success message
                preg_match('/ADD\s+COLUMN\s+`?(\w+)`?/i', $statement, $matches);
                $column_name = isset($matches[1]) ? $matches[1] : 'unknown';
                
                $results['details'][] = array(
                    'statement' => $statement,
                    'status' => 'success',
                    'message' => "Column '{$column_name}' added successfully",
                    'statement_num' => $index + 1
                );
                $results['successes']++;
            }
        }
        
        $wpdb->show_errors($previous_show_errors); // Restore the prior setting, don't force it on
        
        // Calculate execution time
        $execution_time = round(microtime(true) - $start_time, 3);
        
        // Prepare summary message
        $summary = "Execution completed in {$execution_time} seconds.\n";
        $summary .= "Total statements: {$results['total_statements']}\n";
        $summary .= "Successful: {$results['successes']}\n";
        $summary .= "Errors: {$results['errors']}\n";
        $summary .= "Skipped: " . ($results['total_statements'] - $results['successes'] - $results['errors']);
        
        // Determine overall status
        if ($results['errors'] === 0 && $results['successes'] > 0) {
            $status = 'success';
            $message = "✅ All columns added successfully!";
        } elseif ($results['errors'] === 0 && $results['successes'] === 0) {
            $status = 'info';
            $message = "ℹ️ No columns were added (may already exist)";
        } elseif ($results['errors'] > 0 && $results['successes'] > 0) {
            $status = 'partial';
            $message = "⚠️ Partially completed with some errors";
        } else {
            $status = 'error';
            $message = "❌ Failed to add columns";
        }
        
        wp_send_json_success(array(
            'status' => $status,
            'message' => $message,
            'summary' => $summary,
            'table_name' => $table_name,
            'execution_time' => $execution_time,
            'statements_run' => $results['total_statements'],
            'successes' => $results['successes'],
            'errors' => $results['errors'],
            'details' => $results['details']
        ));
    }
}

// Initialize the AJAX handler
new Axiom_DB_Discrepancy_Ajax_Handler();