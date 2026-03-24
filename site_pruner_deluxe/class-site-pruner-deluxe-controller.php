<?php
/**
 * Site Pruner Deluxe Controller Class
 * 
 * Handles the Site Pruner Deluxe admin page functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Axiom_Site_Pruner_Deluxe_Controller {
    
    public function __construct() {
        // Initialize hooks when needed
    }
    
    /**
     * Initialize page hooks and functionality
     */
    public function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_site_pruner_validate_pages', array($this, 'ajax_validate_pages'));
        add_action('wp_ajax_site_pruner_execute_pruning', array($this, 'ajax_execute_pruning'));
    }
    
    /**
     * Render the Site Pruner Deluxe page
     */
    public function render() {
        // Aggressive admin notice suppression
        $this->suppress_admin_notices();
        
        // Load the page template
        require_once dirname(__FILE__) . '/site-pruner-deluxe-page.php';
    }
    
    /**
     * Aggressive admin notice suppression for clean interface
     */
    private function suppress_admin_notices() {
        // Remove all admin notices
        add_action('admin_print_styles', function() {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            remove_all_actions('network_admin_notices');
            remove_all_actions('user_admin_notices');
            
            // Remove specific plugin notices at different priorities
            remove_all_actions('admin_notices', 10);
            remove_all_actions('admin_notices', 20);
            
            // Add CSS to hide any notices that slip through
            echo '<style>
                .notice, .notice-error, .notice-warning, .notice-success, .notice-info,
                .update-nag, .updated, .error, .is-dismissible, .wp-core-ui .notice,
                .wrap > .notice, .wrap > .notice-error, .wrap > .notice-warning,
                .wrap > .notice-success, .wrap > .notice-info, .wrap > .update-nag,
                .wrap > .updated, .wrap > .error, #wpbody-content > .notice,
                #wpbody-content > .updated, #wpbody-content > .error,
                #wpbody-content > .update-nag, .wp-header-end + .notice,
                .wp-header-end + .updated, .wp-header-end + .error {
                    display: none !important;
                    visibility: hidden !important;
                    height: 0 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
            </style>';
        }, 1);
        
        // Additional suppression before page loads
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }
    
    /**
     * AJAX Handler: Validate Pages
     * Validates the list of pages/URLs provided by user
     */
    public function ajax_validate_pages() {
        try {
            // Enable error reporting for debugging
            error_reporting(E_ALL);
            ini_set('display_errors', 0); // Don't display, but log
            
            // Start collecting debug info
            $debug_info = array(
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'user_login' => wp_get_current_user()->user_login,
                'user_capabilities' => wp_get_current_user()->allcaps,
                'post_data' => $_POST,
                'php_version' => phpversion(),
                'wp_version' => get_bloginfo('version'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            );
            
            // Security check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'site_pruner_nonce')) {
                $error_msg = 'Security check failed - nonce verification failed';
                error_log('Site Pruner Deluxe: ' . $error_msg);
                $debug_info['error'] = $error_msg;
                $debug_info['nonce_provided'] = isset($_POST['nonce']) ? $_POST['nonce'] : 'not provided';
                wp_send_json_error(array(
                    'message' => $error_msg,
                    'debug' => $debug_info
                ));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                $error_msg = 'Insufficient permissions - user cannot manage_options';
                error_log('Site Pruner Deluxe: ' . $error_msg);
                $debug_info['error'] = $error_msg;
                wp_send_json_error(array(
                    'message' => $error_msg,
                    'debug' => $debug_info
                ));
                return;
            }
            
            if (!isset($_POST['pages_list'])) {
                $error_msg = 'No pages_list parameter provided';
                error_log('Site Pruner Deluxe: ' . $error_msg);
                $debug_info['error'] = $error_msg;
                wp_send_json_error(array(
                    'message' => $error_msg,
                    'debug' => $debug_info
                ));
                return;
            }
            
            $pages_list = sanitize_textarea_field($_POST['pages_list']);
            $pages_array = array_filter(array_map('trim', explode("\n", $pages_list)));
            
            $debug_info['pages_count'] = count($pages_array);
            $debug_info['pages_sample'] = array_slice($pages_array, 0, 5); // First 5 pages for debug
            
            if (empty($pages_array)) {
                $error_msg = 'No valid pages found after processing';
                error_log('Site Pruner Deluxe: ' . $error_msg);
                $debug_info['error'] = $error_msg;
                $debug_info['raw_pages_list'] = $pages_list;
                wp_send_json_error(array(
                    'message' => $error_msg,
                    'debug' => $debug_info
                ));
                return;
            }
            
            // Log the validation attempt
            error_log('Site Pruner Deluxe: Validating ' . count($pages_array) . ' pages for user ' . wp_get_current_user()->user_login);
            
            $results = $this->validate_page_list($pages_array);
            
            // Add debug info to results
            $debug_info['validation_completed'] = true;
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            $error_msg = 'Exception caught: ' . $e->getMessage();
            error_log('Site Pruner Deluxe Exception: ' . $error_msg);
            error_log('Site Pruner Deluxe Stack Trace: ' . $e->getTraceAsString());
            
            wp_send_json_error(array(
                'message' => $error_msg,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'debug' => isset($debug_info) ? $debug_info : array()
            ));
        }
    }
    
    /**
     * Validate a list of page URLs/slugs
     */
    private function validate_page_list($pages_array) {
        global $wpdb;
        
        try {
            $found_pages = array();
            $not_found_pages = array();
            $errors_encountered = array();
            $home_url = home_url();
            $site_url = site_url();
            
            // Check database connection
            if (!$wpdb->check_connection()) {
                throw new Exception('Database connection lost');
            }
            
            // Get database prefix for debugging
            $db_prefix = $wpdb->prefix;
            error_log("Site Pruner Deluxe: Using database prefix: " . $db_prefix);
            
            foreach ($pages_array as $page_input) {
                try {
                    $page_input = trim($page_input);
                    if (empty($page_input)) continue;
                    
                    // Clean up the input - remove leading/trailing slashes and extract slug
                    $slug = $this->extract_slug_from_input($page_input);
                    
                    if (empty($slug)) {
                        $not_found_pages[] = array(
                            'input' => $page_input,
                            'reason' => 'Invalid URL format'
                        );
                        continue;
                    }
                    
                    // Log the query for debugging
                    error_log("Site Pruner Deluxe: Looking for slug: " . $slug);
                    
                    // Special handling for homepage
                    if ($slug === '__homepage__') {
                        // Get the homepage
                        $front_page_id = get_option('page_on_front');
                        if ($front_page_id) {
                            $post = get_post($front_page_id);
                        } else {
                            // No static homepage set, mark as not found
                            $not_found_pages[] = array(
                                'input' => $page_input,
                                'reason' => 'Homepage (no static front page set)'
                            );
                            continue;
                        }
                    } else {
                        // First try with the hierarchical slug (with ___)
                        $search_slug = str_replace('___', '/', $slug);
                        
                        // Look for the page in database (case insensitive)
                        $query = $wpdb->prepare("
                            SELECT ID, post_title, post_name, post_type, post_status, post_parent
                            FROM {$wpdb->posts} 
                            WHERE LOWER(post_name) = LOWER(%s) 
                            AND post_status IN ('publish', 'draft', 'private', 'pending')
                            ORDER BY post_status = 'publish' DESC
                            LIMIT 1
                        ", $search_slug);
                        
                        $post = $wpdb->get_row($query);
                        
                        // If not found, try just the last segment (for non-hierarchical pages)
                        if (!$post && strpos($search_slug, '/') !== false) {
                            $parts = explode('/', $search_slug);
                            $last_segment = end($parts);
                            
                            $query = $wpdb->prepare("
                                SELECT ID, post_title, post_name, post_type, post_status, post_parent
                                FROM {$wpdb->posts} 
                                WHERE LOWER(post_name) = LOWER(%s) 
                                AND post_status IN ('publish', 'draft', 'private', 'pending')
                                ORDER BY post_status = 'publish' DESC
                                LIMIT 1
                            ", $last_segment);
                            
                            $post = $wpdb->get_row($query);
                        }
                    }
                    
                    // Check for database errors
                    if ($wpdb->last_error) {
                        error_log("Site Pruner Deluxe: Database error: " . $wpdb->last_error);
                        $errors_encountered[] = "DB Error for '{$page_input}': " . $wpdb->last_error;
                        continue;
                    }
                    
                    if ($post) {
                        $found_pages[] = array(
                            'input' => $page_input,
                            'post_id' => $post->ID,
                            'post_title' => $post->post_title,
                            'post_name' => $post->post_name,
                            'post_type' => $post->post_type,
                            'post_status' => $post->post_status,
                            'post_parent' => $post->post_parent
                        );
                        error_log("Site Pruner Deluxe: Found page - ID: {$post->ID}, Title: {$post->post_title}");
                    } else {
                        $not_found_pages[] = array(
                            'input' => $page_input,
                            'reason' => 'Page not found in database'
                        );
                        error_log("Site Pruner Deluxe: Page not found for slug: " . $slug);
                    }
                    
                } catch (Exception $e) {
                    error_log("Site Pruner Deluxe: Error processing page '{$page_input}': " . $e->getMessage());
                    $errors_encountered[] = "Error for '{$page_input}': " . $e->getMessage();
                    continue;
                }
            }
        } catch (Exception $e) {
            error_log("Site Pruner Deluxe: Critical error in validate_page_list: " . $e->getMessage());
            throw $e; // Re-throw to be caught by ajax handler
        }
        
        // Generate report
        $report = "=== PAGE VALIDATION RESULTS ===\n\n";
        $report .= "📊 SUMMARY:\n";
        $report .= "- Total URLs submitted: " . count($pages_array) . "\n";
        $report .= "- Pages found: " . count($found_pages) . "\n";
        $report .= "- Pages not found: " . count($not_found_pages) . "\n";
        if (!empty($errors_encountered)) {
            $report .= "- ⚠️ Errors encountered: " . count($errors_encountered) . "\n";
        }
        $report .= "\n";
        
        if (!empty($found_pages)) {
            $report .= "✅ FOUND PAGES:\n";
            $report .= str_repeat("-", 120) . "\n";
            
            foreach ($found_pages as $page) {
                $report .= "INPUT: " . $page['input'] . "\n";
                $report .= "  → Post ID: " . $page['post_id'] . "\n";
                $report .= "  → Title: " . $page['post_title'] . "\n";
                $report .= "  → Type: " . $page['post_type'] . "\n";
                $report .= "  → Status: " . $page['post_status'] . "\n";
                $report .= "  → Parent: " . ($page['post_parent'] ?: 'None') . "\n";
                $report .= "  → Slug: " . $page['post_name'] . "\n";
                $report .= str_repeat("-", 80) . "\n";
            }
            $report .= "\n";
        }
        
        if (!empty($not_found_pages)) {
            $report .= "❌ NOT FOUND:\n";
            foreach ($not_found_pages as $page) {
                $report .= "- " . $page['input'] . " (" . $page['reason'] . ")\n";
            }
            $report .= "\n";
        }
        
        if (!empty($errors_encountered)) {
            $report .= "⚠️ ERRORS ENCOUNTERED:\n";
            foreach ($errors_encountered as $error) {
                $report .= "- " . $error . "\n";
            }
            $report .= "\n";
        }
        
        $report .= "⚠️  PROTECTED PAGES (will never be touched):\n";
        $report .= "- Homepage\n";
        $report .= "- Pages with slugs: about, about-us, contact, contact-us, blog, aboutus, contactus\n";
        $report .= "- Parent pages required for preserved child page URLs\n\n";
        
        // Add debug information to the report
        $report .= "🔍 DEBUG INFO:\n";
        $report .= "- Database Prefix: " . $wpdb->prefix . "\n";
        $report .= "- Home URL: " . home_url() . "\n";
        $report .= "- Site URL: " . site_url() . "\n";
        $report .= "- WordPress Version: " . get_bloginfo('version') . "\n";
        $report .= "- PHP Version: " . phpversion() . "\n\n";
        
        if (count($found_pages) > 0) {
            $report .= "✅ Validation completed successfully! You can now proceed to execute pruning.";
        } else {
            $report .= "❌ No valid pages found. Please check your input and try again.";
        }
        
        return $report;
    }
    
    /**
     * Extract slug from various input formats
     */
    private function extract_slug_from_input($input) {
        $input = trim($input);
        
        // If it's a full URL, extract the path
        if (strpos($input, 'http') === 0) {
            $parsed = parse_url($input);
            
            // Check if this is the homepage (no path or just '/')
            if (!isset($parsed['path']) || $parsed['path'] === '/' || $parsed['path'] === '') {
                return '__homepage__'; // Special marker for homepage
            }
            
            $input = $parsed['path'];
        }
        
        // Remove leading and trailing slashes
        $input = trim($input, '/');
        
        // If empty after trimming (homepage), return special marker
        if (empty($input)) {
            return '__homepage__';
        }
        
        // For paths with multiple segments, we want the full path as slug
        // Don't take just the last segment - keep the full path
        // WordPress stores the full path as post_name for hierarchical pages
        
        // Clean the slug but preserve the path structure
        // Replace slashes with a unique marker to preserve hierarchy
        $slug = str_replace('/', '___', $input);
        
        return $slug;
    }
    
    /**
     * AJAX Handler: Execute Pruning
     * Executes the pruning function based on user settings
     */
    public function ajax_execute_pruning() {
        // Security check
        check_ajax_referer('site_pruner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $pages_list = sanitize_textarea_field($_POST['pages_list']);
        $pruning_action = sanitize_text_field($_POST['pruning_action']);
        
        if (!in_array($pruning_action, array('trash', 'draft'))) {
            wp_send_json_error('Invalid pruning action');
        }
        
        $pages_array = array_filter(array_map('trim', explode("\n", $pages_list)));
        
        if (empty($pages_array)) {
            wp_send_json_error('No pages provided for pruning');
        }
        
        $results = $this->execute_pruning_function($pages_array, $pruning_action);
        
        wp_send_json_success($results);
    }
    
    /**
     * Execute the main pruning function
     */
    private function execute_pruning_function($pages_array, $action) {
        global $wpdb;
        
        // Step 1: Build preserve list from user input
        $preserve_post_ids = $this->get_preserve_post_ids($pages_array);
        
        // Step 2: Add hardcoded protected pages
        $protected_post_ids = $this->get_protected_post_ids();
        $preserve_post_ids = array_merge($preserve_post_ids, $protected_post_ids);
        
        // Step 3: Add parent pages that are required for preserved child pages
        $required_parent_ids = $this->get_required_parent_post_ids($preserve_post_ids);
        $preserve_post_ids = array_merge($preserve_post_ids, $required_parent_ids);
        
        // Step 4: Remove duplicates
        $preserve_post_ids = array_unique($preserve_post_ids);
        
        // Step 5: Get all posts that should be processed (excluded preserved ones)
        $posts_to_process = $this->get_posts_to_process($preserve_post_ids);
        
        // Step 6: Execute the action with detailed tracking
        $processed_count = 0;
        $success_count = 0;
        $error_count = 0;
        $operation_results = array();
        $errors = array();
        
        foreach ($posts_to_process as $post) {
            $operation_start_time = microtime(true);
            
            if ($action === 'trash') {
                $result = wp_trash_post($post->ID);
            } else { // draft
                $result = wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft'
                ));
            }
            
            $operation_time = round((microtime(true) - $operation_start_time) * 1000, 2); // in milliseconds
            
            if ($result) {
                $processed_count++;
                $success_count++;
                $status = 'SUCCESS';
            } else {
                $processed_count++;
                $error_count++;
                $status = 'FAILED';
                $errors[] = "Failed to process post ID {$post->ID} ({$post->post_title})";
            }
            
            // Store detailed result for each post
            $operation_results[] = array(
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'post_status_before' => $post->post_status,
                'action' => $action,
                'status' => $status,
                'time_ms' => $operation_time
            );
        }
        
        // Generate detailed report
        return $this->generate_detailed_pruning_report($preserve_post_ids, $posts_to_process, $operation_results, $success_count, $error_count, $errors, $action);
    }
    
    /**
     * Get post IDs from user's preserve list
     */
    private function get_preserve_post_ids($pages_array) {
        global $wpdb;
        
        $post_ids = array();
        
        foreach ($pages_array as $page_input) {
            $slug = $this->extract_slug_from_input($page_input);
            if (empty($slug)) continue;
            
            $post = $wpdb->get_row($wpdb->prepare("
                SELECT ID
                FROM {$wpdb->posts} 
                WHERE LOWER(post_name) = LOWER(%s) 
                AND post_status IN ('publish', 'draft', 'private', 'pending')
                ORDER BY post_status = 'publish' DESC
                LIMIT 1
            ", $slug));
            
            if ($post) {
                $post_ids[] = $post->ID;
            }
        }
        
        return $post_ids;
    }
    
    /**
     * Get hardcoded protected post IDs (homepage + protected slugs)
     */
    private function get_protected_post_ids() {
        global $wpdb;
        
        $protected_ids = array();
        
        // Protect homepage
        $homepage_id = get_option('page_on_front');
        if ($homepage_id) {
            $protected_ids[] = $homepage_id;
        }
        
        // Protect specific slugs (case insensitive)
        $protected_slugs = array('about', 'about-us', 'contact', 'contact-us', 'blog', 'aboutus', 'contactus');
        
        foreach ($protected_slugs as $slug) {
            $post = $wpdb->get_row($wpdb->prepare("
                SELECT ID
                FROM {$wpdb->posts} 
                WHERE LOWER(post_name) = LOWER(%s) 
                AND post_status IN ('publish', 'draft', 'private', 'pending')
                LIMIT 1
            ", $slug));
            
            if ($post) {
                $protected_ids[] = $post->ID;
            }
        }
        
        return $protected_ids;
    }
    
    /**
     * Get parent post IDs that are required for preserved child pages
     */
    private function get_required_parent_post_ids($preserve_post_ids) {
        global $wpdb;
        
        if (empty($preserve_post_ids)) {
            return array();
        }
        
        $required_parents = array();
        
        // For each preserved post, get all its ancestors
        foreach ($preserve_post_ids as $post_id) {
            $ancestors = get_post_ancestors($post_id);
            if (!empty($ancestors)) {
                $required_parents = array_merge($required_parents, $ancestors);
            }
        }
        
        return array_unique($required_parents);
    }
    
    /**
     * Get all posts that should be processed (not preserved)
     */
    private function get_posts_to_process($preserve_post_ids) {
        global $wpdb;
        
        $exclude_list = '';
        if (!empty($preserve_post_ids)) {
            $exclude_list = " AND ID NOT IN (" . implode(',', array_map('intval', $preserve_post_ids)) . ")";
        }
        
        $posts = $wpdb->get_results("
            SELECT ID, post_title, post_name, post_type, post_status, post_parent
            FROM {$wpdb->posts} 
            WHERE post_status IN ('publish', 'draft', 'private', 'pending')
            AND post_type IN ('post', 'page')
            {$exclude_list}
            ORDER BY post_type, post_title
        ");
        
        return $posts;
    }
    
    /**
     * Generate comprehensive pruning report
     */
    private function generate_pruning_report($preserve_post_ids, $posts_to_process, $processed_count, $errors, $action) {
        global $wpdb;
        
        $report = "=== PRUNING EXECUTION RESULTS ===\n\n";
        
        $action_text = $action === 'trash' ? 'TRASHED' : 'MOVED TO DRAFT';
        $report .= "📊 SUMMARY:\n";
        $report .= "- Action: " . strtoupper($action) . "\n";
        $report .= "- Posts preserved: " . count($preserve_post_ids) . "\n";
        $report .= "- Posts processed ({$action_text}): {$processed_count}\n";
        $report .= "- Errors: " . count($errors) . "\n\n";
        
        if (count($preserve_post_ids) > 0) {
            $report .= "✅ PRESERVED POSTS/PAGES:\n";
            $report .= str_repeat("-", 80) . "\n";
            
            // Get details of preserved posts
            $preserved_posts = $wpdb->get_results("
                SELECT ID, post_title, post_name, post_type, post_status, post_parent
                FROM {$wpdb->posts} 
                WHERE ID IN (" . implode(',', array_map('intval', $preserve_post_ids)) . ")
                ORDER BY post_type, post_title
            ");
            
            $report .= sprintf("%-8s %-20s %-12s %-10s %-25s %s\n", 
                "POST_ID", "POST_TYPE", "STATUS", "PARENT", "SLUG", "TITLE");
            $report .= str_repeat("-", 80) . "\n";
            
            foreach ($preserved_posts as $post) {
                $parent_text = $post->post_parent ? $post->post_parent : 'None';
                $report .= sprintf("%-8s %-20s %-12s %-10s %-25s %s\n",
                    $post->ID,
                    $post->post_type,
                    $post->post_status,
                    $parent_text,
                    substr($post->post_name, 0, 24),
                    substr($post->post_title, 0, 30)
                );
            }
            $report .= "\n";
        }
        
        if ($processed_count > 0) {
            $report .= "🗑️ PROCESSED POSTS ({$action_text}):\n";
            $report .= "- Total processed: {$processed_count}\n";
            
            // Show first 10 processed posts as examples
            $sample_processed = array_slice($posts_to_process, 0, 10);
            if (!empty($sample_processed)) {
                $report .= "- Sample of processed posts:\n";
                foreach ($sample_processed as $post) {
                    $report .= "  • {$post->post_title} (ID: {$post->ID}, Type: {$post->post_type})\n";
                }
                if (count($posts_to_process) > 10) {
                    $remaining = count($posts_to_process) - 10;
                    $report .= "  • ... and {$remaining} more posts\n";
                }
            }
            $report .= "\n";
        }
        
        if (!empty($errors)) {
            $report .= "❌ ERRORS:\n";
            foreach ($errors as $error) {
                $report .= "- " . $error . "\n";
            }
            $report .= "\n";
        }
        
        $report .= "🛡️ PROTECTION RULES APPLIED:\n";
        $report .= "- Homepage was protected from changes\n";
        $report .= "- Protected slugs: about, about-us, contact, contact-us, blog, aboutus, contactus\n";
        $report .= "- Parent pages required for preserved child page URLs were protected\n";
        $report .= "- User-specified preserve list was honored\n\n";
        
        if ($processed_count > 0) {
            $report .= "✅ Pruning operation completed successfully!";
        } else {
            $report .= "ℹ️ No posts were processed (all posts were preserved by protection rules).";
        }
        
        return $report;
    }
    
    /**
     * Generate detailed pruning report with individual post results
     */
    private function generate_detailed_pruning_report($preserve_post_ids, $posts_to_process, $operation_results, $success_count, $error_count, $errors, $action) {
        global $wpdb;
        
        $action_text = $action === 'trash' ? 'TRASHED' : 'MOVED TO DRAFT';
        $total_processed = count($operation_results);
        $execution_start = date('Y-m-d H:i:s');
        
        // Build the detailed report
        $report = "=== SITE PRUNER DELUXE - EXECUTION REPORT ===\n";
        $report .= "Generated: " . $execution_start . "\n";
        $report .= "WordPress Site: " . site_url() . "\n";
        $report .= "=========================================\n\n";
        
        $report .= "📊 EXECUTION SUMMARY\n";
        $report .= "-------------------\n";
        $report .= "Action Type: " . strtoupper($action) . "\n";
        $report .= "Total Posts Processed: {$total_processed}\n";
        $report .= "✅ Successful: {$success_count}\n";
        $report .= "❌ Failed: {$error_count}\n";
        $report .= "🛡️ Preserved: " . count($preserve_post_ids) . "\n\n";
        
        // Detailed operation results
        if (!empty($operation_results)) {
            $report .= "📋 DETAILED OPERATION RESULTS\n";
            $report .= "==============================\n";
            $report .= sprintf("%-8s | %-10s | %-8s | %-50s | %-12s | %s\n", 
                "POST ID", "POST TYPE", "STATUS", "POST TITLE", "PREV STATUS", "TIME (ms)");
            $report .= str_repeat("-", 120) . "\n";
            
            foreach ($operation_results as $result) {
                $status_symbol = $result['status'] === 'SUCCESS' ? '✅' : '❌';
                $report .= sprintf("%-8s | %-10s | %s %-7s | %-50s | %-12s | %.2f ms\n",
                    $result['post_id'],
                    $result['post_type'],
                    $status_symbol,
                    $result['status'],
                    substr($result['post_title'], 0, 50),
                    $result['post_status_before'],
                    $result['time_ms']
                );
            }
            $report .= "\n";
        }
        
        // List of preserved posts
        if (count($preserve_post_ids) > 0) {
            $report .= "🛡️ PRESERVED POSTS (NOT MODIFIED)\n";
            $report .= "==================================\n";
            
            $preserved_posts = $wpdb->get_results("
                SELECT ID, post_title, post_name, post_type, post_status
                FROM {$wpdb->posts} 
                WHERE ID IN (" . implode(',', array_map('intval', $preserve_post_ids)) . ")
                ORDER BY post_type, post_title
                LIMIT 100
            ");
            
            if (!empty($preserved_posts)) {
                $report .= sprintf("%-8s | %-10s | %-12s | %-50s\n", 
                    "POST ID", "POST TYPE", "STATUS", "POST TITLE");
                $report .= str_repeat("-", 90) . "\n";
                
                foreach ($preserved_posts as $post) {
                    $report .= sprintf("%-8s | %-10s | %-12s | %-50s\n",
                        $post->ID,
                        $post->post_type,
                        $post->post_status,
                        substr($post->post_title, 0, 50)
                    );
                }
                
                if (count($preserve_post_ids) > 100) {
                    $report .= "... and " . (count($preserve_post_ids) - 100) . " more preserved posts\n";
                }
                $report .= "\n";
            }
        }
        
        // Error details
        if (!empty($errors)) {
            $report .= "❌ ERROR DETAILS\n";
            $report .= "================\n";
            foreach ($errors as $error) {
                $report .= "• " . $error . "\n";
            }
            $report .= "\n";
        }
        
        // Protection rules applied
        $report .= "🔒 PROTECTION RULES APPLIED\n";
        $report .= "===========================\n";
        $report .= "• Homepage was protected from changes\n";
        $report .= "• Protected slugs: about, about-us, contact, contact-us, blog, aboutus, contactus\n";
        $report .= "• Parent pages required for preserved child page URLs were protected\n";
        $report .= "• User-specified preserve list was honored\n\n";
        
        // Final status
        $report .= "📝 FINAL STATUS\n";
        $report .= "===============\n";
        if ($error_count === 0 && $total_processed > 0) {
            $report .= "✅ All operations completed successfully!\n";
            $report .= "Total of {$success_count} posts were {$action_text}.\n";
        } elseif ($error_count > 0 && $success_count > 0) {
            $report .= "⚠️ Partial success - {$success_count} succeeded, {$error_count} failed.\n";
        } elseif ($total_processed === 0) {
            $report .= "ℹ️ No posts were processed (all posts were preserved by protection rules).\n";
        } else {
            $report .= "❌ All operations failed. Please check error details above.\n";
        }
        
        $report .= "\n=== END OF REPORT ===";
        
        return $report;
    }
}