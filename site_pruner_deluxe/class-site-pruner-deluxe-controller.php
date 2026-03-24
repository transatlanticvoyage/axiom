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
        // Security check
        check_ajax_referer('site_pruner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $pages_list = sanitize_textarea_field($_POST['pages_list']);
        $pages_array = array_filter(array_map('trim', explode("\n", $pages_list)));
        
        if (empty($pages_array)) {
            wp_send_json_error('No pages provided for validation');
        }
        
        $results = $this->validate_page_list($pages_array);
        
        wp_send_json_success($results);
    }
    
    /**
     * Validate a list of page URLs/slugs
     */
    private function validate_page_list($pages_array) {
        global $wpdb;
        
        $found_pages = array();
        $not_found_pages = array();
        $home_url = home_url();
        $site_url = site_url();
        
        foreach ($pages_array as $page_input) {
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
            
            // Look for the page in database (case insensitive)
            $post = $wpdb->get_row($wpdb->prepare("
                SELECT ID, post_title, post_name, post_type, post_status, post_parent
                FROM {$wpdb->posts} 
                WHERE LOWER(post_name) = LOWER(%s) 
                AND post_status IN ('publish', 'draft', 'private', 'pending')
                ORDER BY post_status = 'publish' DESC
                LIMIT 1
            ", $slug));
            
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
            } else {
                $not_found_pages[] = array(
                    'input' => $page_input,
                    'reason' => 'Page not found in database'
                );
            }
        }
        
        // Generate report
        $report = "=== PAGE VALIDATION RESULTS ===\n\n";
        $report .= "📊 SUMMARY:\n";
        $report .= "- Total URLs submitted: " . count($pages_array) . "\n";
        $report .= "- Pages found: " . count($found_pages) . "\n";
        $report .= "- Pages not found: " . count($not_found_pages) . "\n\n";
        
        if (!empty($found_pages)) {
            $report .= "✅ FOUND PAGES:\n";
            $report .= str_repeat("-", 80) . "\n";
            $report .= sprintf("%-30s %-8s %-20s %-12s %-10s %s\n", 
                "INPUT", "POST_ID", "POST_TYPE", "POST_STATUS", "PARENT", "TITLE");
            $report .= str_repeat("-", 80) . "\n";
            
            foreach ($found_pages as $page) {
                $report .= sprintf("%-30s %-8s %-20s %-12s %-10s %s\n",
                    substr($page['input'], 0, 29),
                    $page['post_id'],
                    $page['post_type'],
                    $page['post_status'],
                    $page['post_parent'] ?: 'None',
                    substr($page['post_title'], 0, 30)
                );
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
        
        $report .= "⚠️  PROTECTED PAGES (will never be touched):\n";
        $report .= "- Homepage\n";
        $report .= "- Pages with slugs: about, about-us, contact, contact-us, blog, aboutus, contactus\n";
        $report .= "- Parent pages required for preserved child page URLs\n\n";
        
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
            if (!isset($parsed['path'])) return '';
            $input = $parsed['path'];
        }
        
        // Remove leading and trailing slashes
        $input = trim($input, '/');
        
        // If it contains multiple path segments, take the last one
        if (strpos($input, '/') !== false) {
            $parts = explode('/', $input);
            $input = end($parts);
        }
        
        // Clean the slug
        $input = sanitize_title($input);
        
        return $input;
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
        
        // Step 6: Execute the action
        $processed_count = 0;
        $errors = array();
        
        foreach ($posts_to_process as $post) {
            if ($action === 'trash') {
                $result = wp_trash_post($post->ID);
            } else { // draft
                $result = wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft'
                ));
            }
            
            if ($result) {
                $processed_count++;
            } else {
                $errors[] = "Failed to process post ID {$post->ID} ({$post->post_title})";
            }
        }
        
        // Generate report
        return $this->generate_pruning_report($preserve_post_ids, $posts_to_process, $processed_count, $errors, $action);
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
}