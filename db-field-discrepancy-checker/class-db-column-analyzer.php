<?php
/**
 * Database Column Analyzer for DB Field Discrepancy Checker
 * Analyzes actual database columns and compares with definitions
 * 
 * @package Axiom
 * @subpackage DbFieldDiscrepancyChecker
 */

if (!defined('ABSPATH')) {
    exit;
}

class Axiom_DB_Column_Analyzer {
    
    /**
     * Get actual columns from a database table
     * 
     * @param string $table_name Full table name with prefix
     * @return array Array of column definitions
     */
    public static function get_actual_db_columns($table_name) {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            return array();
        }
        
        // Get column information
        $columns = $wpdb->get_results("SHOW FULL COLUMNS FROM `{$table_name}`");
        
        $column_definitions = array();
        foreach ($columns as $column) {
            $definition = "`{$column->Field}` {$column->Type}";
            
            // Add NULL/NOT NULL
            if ($column->Null === 'NO') {
                $definition .= ' NOT NULL';
            } else {
                $definition .= ' DEFAULT NULL';
            }
            
            // Add default value if exists and not NULL
            if ($column->Default !== null && $column->Default !== 'NULL') {
                if ($column->Default === 'CURRENT_TIMESTAMP') {
                    $definition = str_replace(' DEFAULT NULL', '', $definition);
                    $definition .= ' DEFAULT CURRENT_TIMESTAMP';
                } elseif (is_numeric($column->Default)) {
                    $definition = str_replace(' DEFAULT NULL', '', $definition);
                    $definition .= ' DEFAULT ' . $column->Default;
                } else {
                    $definition = str_replace(' DEFAULT NULL', '', $definition);
                    $definition .= " DEFAULT '" . $column->Default . "'";
                }
            }
            
            // Add extra info (like auto_increment, on update)
            if (!empty($column->Extra)) {
                if (stripos($column->Extra, 'auto_increment') !== false) {
                    $definition .= ' AUTO_INCREMENT';
                }
                if (stripos($column->Extra, 'on update CURRENT_TIMESTAMP') !== false) {
                    $definition .= ' ON UPDATE CURRENT_TIMESTAMP';
                }
            }
            
            // Add key information
            if ($column->Key === 'PRI') {
                $definition .= ' PRIMARY KEY';
            } elseif ($column->Key === 'UNI') {
                $definition .= ' UNIQUE';
            } elseif ($column->Key === 'MUL') {
                $definition .= ' KEY';
            }
            
            $column_definitions[$column->Field] = $definition;
        }
        
        return $column_definitions;
    }
    
    /**
     * Get column definitions from CREATE TABLE SQL
     * 
     * @param string $create_sql CREATE TABLE SQL statement
     * @return array Array of column definitions
     */
    public static function get_columns_from_sql($create_sql) {
        $columns = array();
        
        // Extract the content between parentheses
        if (preg_match('/CREATE\s+TABLE[^(]+\((.*)\)\s*[^;]*;?$/is', $create_sql, $matches)) {
            $table_content = $matches[1];
            
            // Split by commas, but not commas within parentheses
            $lines = preg_split('/,(?![^()]*\))/', $table_content);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip KEY, INDEX, PRIMARY KEY, UNIQUE KEY definitions
                if (preg_match('/^(KEY|INDEX|PRIMARY\s+KEY|UNIQUE\s+KEY|CONSTRAINT|FOREIGN\s+KEY)/i', $line)) {
                    continue;
                }
                
                // Extract column name and definition
                if (preg_match('/^`?(\w+)`?\s+(.+)$/i', $line, $col_match)) {
                    $col_name = $col_match[1];
                    $col_def = '`' . $col_name . '` ' . $col_match[2];
                    $columns[$col_name] = $col_def;
                }
            }
        }
        
        return $columns;
    }
    
    /**
     * Compare source columns with actual database columns
     * 
     * @param array $source_columns Columns from plugin definition
     * @param array $actual_columns Columns from actual database
     * @return array Missing columns that need to be added
     */
    public static function get_missing_columns($source_columns, $actual_columns) {
        $missing = array();
        
        foreach ($source_columns as $col_name => $col_def) {
            if (!isset($actual_columns[$col_name])) {
                $missing[$col_name] = $col_def;
            }
        }
        
        return $missing;
    }
    
    /**
     * Generate ALTER TABLE statements for missing columns
     * 
     * @param string $table_name Full table name with prefix
     * @param array $missing_columns Array of missing column definitions
     * @return string SQL statements to add missing columns
     */
    public static function generate_alter_statements($table_name, $missing_columns) {
        if (empty($missing_columns)) {
            return '-- No missing columns found. Table structure matches plugin definition.';
        }
        
        $statements = array();
        foreach ($missing_columns as $col_name => $col_def) {
            // Clean up the column definition for ALTER TABLE
            $col_def = preg_replace('/^`?\w+`?\s+/', '', $col_def);
            $col_def = preg_replace('/\s+(PRIMARY\s+KEY|UNIQUE|KEY)$/i', '', $col_def);
            
            $statements[] = "ALTER TABLE `{$table_name}` ADD COLUMN `{$col_name}` {$col_def};";
        }
        
        return implode("\n", $statements);
    }
    
    /**
     * Format columns for display
     * 
     * @param array $columns Array of column definitions
     * @return string Formatted column list
     */
    public static function format_columns_display($columns) {
        if (empty($columns)) {
            return '-- No columns found';
        }
        
        $formatted = array();
        foreach ($columns as $col_name => $col_def) {
            $formatted[] = $col_def;
        }
        
        return implode(",\n", $formatted);
    }
}