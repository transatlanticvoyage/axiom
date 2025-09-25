<?php

class Axiom_Shovel_DB {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('wp_ajax_axiom_get_tables', array($this, 'ajax_get_tables'));
        add_action('wp_ajax_axiom_get_table_data', array($this, 'ajax_get_table_data'));
        add_action('wp_ajax_axiom_get_table_structure', array($this, 'ajax_get_table_structure'));
        add_action('wp_ajax_axiom_search_table', array($this, 'ajax_search_table'));
    }
    
    public function get_all_tables() {
        $tables = $this->wpdb->get_results("SHOW TABLES", ARRAY_N);
        
        $tables_data = array();
        foreach ($tables as $table) {
            $table_name = $table[0];
            $row_count = $this->wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
            
            $tables_data[] = array(
                'name' => $table_name,
                'rows' => intval($row_count),
                'prefix' => $this->get_table_prefix($table_name)
            );
        }
        
        usort($tables_data, function($a, $b) {
            if ($a['prefix'] === $b['prefix']) {
                return strcmp($a['name'], $b['name']);
            }
            return strcmp($a['prefix'], $b['prefix']);
        });
        
        return $tables_data;
    }
    
    public function get_table_structure($table) {
        $table = $this->sanitize_table_name($table);
        if (!$table) {
            return false;
        }
        
        $structure = $this->wpdb->get_results("SHOW COLUMNS FROM `{$table}`", ARRAY_A);
        return $structure;
    }
    
    public function get_table_data($table, $page = 1, $limit = 25, $orderby = null, $order = 'ASC') {
        $table = $this->sanitize_table_name($table);
        if (!$table) {
            return false;
        }
        
        $limit = min(intval($limit), 1000);
        $page = max(intval($page), 1);
        $offset = ($page - 1) * $limit;
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        
        $order_clause = '';
        if ($orderby) {
            $orderby = $this->sanitize_column_name($orderby);
            if ($orderby) {
                $order_clause = "ORDER BY `{$orderby}` {$order}";
            }
        }
        
        $query = "SELECT * FROM `{$table}` {$order_clause} LIMIT {$limit} OFFSET {$offset}";
        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        return $results;
    }
    
    public function get_table_row_count($table) {
        $table = $this->sanitize_table_name($table);
        if (!$table) {
            return 0;
        }
        
        $count = $this->wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
        return intval($count);
    }
    
    public function search_table($table, $search_query, $page = 1, $limit = 25) {
        $table = $this->sanitize_table_name($table);
        if (!$table || empty($search_query)) {
            return false;
        }
        
        $limit = min(intval($limit), 1000);
        $page = max(intval($page), 1);
        $offset = ($page - 1) * $limit;
        
        $columns = $this->wpdb->get_results("SHOW COLUMNS FROM `{$table}`", ARRAY_A);
        
        $where_clauses = array();
        foreach ($columns as $column) {
            $col_name = $column['Field'];
            $where_clauses[] = "`{$col_name}` LIKE %s";
        }
        
        $where_sql = implode(' OR ', $where_clauses);
        $search_param = '%' . $this->wpdb->esc_like($search_query) . '%';
        $params = array_fill(0, count($where_clauses), $search_param);
        
        $query = $this->wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE {$where_sql} LIMIT {$limit} OFFSET {$offset}",
            $params
        );
        
        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        $count_query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}",
            $params
        );
        $total_results = intval($this->wpdb->get_var($count_query));
        
        return array(
            'results' => $results,
            'total' => $total_results
        );
    }
    
    private function get_table_prefix($table_name) {
        if (preg_match('/^([a-z0-9]+_)/', $table_name, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    private function sanitize_table_name($table) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        
        $all_tables = $this->wpdb->get_results("SHOW TABLES", ARRAY_N);
        $table_exists = false;
        foreach ($all_tables as $db_table) {
            if ($db_table[0] === $table) {
                $table_exists = true;
                break;
            }
        }
        
        return $table_exists ? $table : false;
    }
    
    private function sanitize_column_name($column) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }
        return $column;
    }
    
    public function ajax_get_tables() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $tables = $this->get_all_tables();
        wp_send_json_success($tables);
    }
    
    public function ajax_get_table_data() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $table = sanitize_text_field($_POST['table']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 25;
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : null;
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'ASC';
        
        $data = $this->get_table_data($table, $page, $limit, $orderby, $order);
        $total_rows = $this->get_table_row_count($table);
        
        if ($data === false) {
            wp_send_json_error('Invalid table name');
        }
        
        wp_send_json_success(array(
            'data' => $data,
            'total_rows' => $total_rows,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total_rows / $limit)
        ));
    }
    
    public function ajax_get_table_structure() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $table = sanitize_text_field($_POST['table']);
        $structure = $this->get_table_structure($table);
        
        if ($structure === false) {
            wp_send_json_error('Invalid table name');
        }
        
        wp_send_json_success($structure);
    }
    
    public function ajax_search_table() {
        check_ajax_referer('axiom_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $table = sanitize_text_field($_POST['table']);
        $search_query = sanitize_text_field($_POST['search']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 25;
        
        $results = $this->search_table($table, $search_query, $page, $limit);
        
        if ($results === false) {
            wp_send_json_error('Invalid table name or search query');
        }
        
        wp_send_json_success(array(
            'data' => $results['results'],
            'total_rows' => $results['total'],
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($results['total'] / $limit)
        ));
    }
    
    public function display_shovel_page() {
        include AXIOM_PLUGIN_PATH . 'includes/pages/shovel.php';
    }
}