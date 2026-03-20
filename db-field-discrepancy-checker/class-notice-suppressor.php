<?php
/**
 * Notice Suppressor for DB Field Discrepancy Checker
 * Aggressively suppresses all WordPress admin notices on this page
 * 
 * @package Axiom
 * @subpackage DbFieldDiscrepancyChecker
 */

if (!defined('ABSPATH')) {
    exit;
}

class Axiom_DB_Field_Discrepancy_Notice_Suppressor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Start suppression early
        $this->init_suppression();
    }
    
    /**
     * Initialize notice suppression
     */
    private function init_suppression() {
        // Remove all action hooks that might add notices
        add_action('admin_init', array($this, 'remove_notice_actions'), 1);
        add_action('admin_head', array($this, 'hide_notices_with_css'), 1);
        add_action('admin_notices', array($this, 'start_notice_capture'), -9999);
        add_action('admin_notices', array($this, 'end_notice_capture'), 9999);
        add_action('network_admin_notices', array($this, 'start_notice_capture'), -9999);
        add_action('network_admin_notices', array($this, 'end_notice_capture'), 9999);
        add_action('user_admin_notices', array($this, 'start_notice_capture'), -9999);
        add_action('user_admin_notices', array($this, 'end_notice_capture'), 9999);
        add_action('all_admin_notices', array($this, 'start_notice_capture'), -9999);
        add_action('all_admin_notices', array($this, 'end_notice_capture'), 9999);
    }
    
    /**
     * Main method to suppress all notices
     */
    public function suppress_all_notices() {
        // Multiple approaches to ensure complete suppression
        
        // Approach 1: Remove common notice actions
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('network_admin_notices');
        remove_all_actions('user_admin_notices');
        
        // Approach 2: Re-add our capture functions
        add_action('admin_notices', array($this, 'start_notice_capture'), -9999);
        add_action('admin_notices', array($this, 'end_notice_capture'), 9999);
        add_action('all_admin_notices', array($this, 'start_notice_capture'), -9999);
        add_action('all_admin_notices', array($this, 'end_notice_capture'), 9999);
        
        // Approach 3: Add CSS to hide any notices that slip through
        add_action('admin_head', array($this, 'hide_notices_with_css'), 9999);
        
        // Approach 4: JavaScript backup
        add_action('admin_footer', array($this, 'hide_notices_with_js'), 9999);
    }
    
    /**
     * Remove specific notice actions from other plugins
     */
    public function remove_notice_actions() {
        // Get all registered actions for notice hooks
        global $wp_filter;
        
        $notice_hooks = array(
            'admin_notices',
            'all_admin_notices',
            'network_admin_notices',
            'user_admin_notices'
        );
        
        foreach ($notice_hooks as $hook) {
            if (isset($wp_filter[$hook])) {
                // Store our callbacks
                $our_callbacks = array();
                
                // Find our callbacks
                if (isset($wp_filter[$hook]->callbacks)) {
                    foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                        foreach ($callbacks as $idx => $callback) {
                            if (is_array($callback['function']) && 
                                is_object($callback['function'][0]) && 
                                get_class($callback['function'][0]) === get_class($this)) {
                                $our_callbacks[] = array(
                                    'priority' => $priority,
                                    'idx' => $idx,
                                    'callback' => $callback
                                );
                            }
                        }
                    }
                }
                
                // Remove all callbacks
                remove_all_actions($hook);
                
                // Re-add only our callbacks
                foreach ($our_callbacks as $cb) {
                    add_action($hook, $cb['callback']['function'], $cb['priority'], $cb['callback']['accepted_args']);
                }
            }
        }
    }
    
    /**
     * Start output buffering to capture notices
     */
    public function start_notice_capture() {
        ob_start();
    }
    
    /**
     * End output buffering and discard notices
     */
    public function end_notice_capture() {
        ob_end_clean();
    }
    
    /**
     * Hide notices with CSS
     */
    public function hide_notices_with_css() {
        ?>
        <style type="text/css">
            /* Aggressively hide all WordPress admin notices on this page */
            .notice,
            .notice-success,
            .notice-error,
            .notice-warning,
            .notice-info,
            .updated,
            .update-nag,
            .error,
            .warning,
            #message,
            .update-message,
            div.updated,
            div.error,
            div.notice,
            .wrap .notice,
            .wrap .error,
            .wrap .updated,
            .wrap .update-nag,
            .wp-core-ui .notice,
            .admin-notices,
            #wpbody-content > .notice,
            #wpbody-content > .error,
            #wpbody-content > .updated,
            #wpbody-content > .update-nag,
            #adminmenu .awaiting-mod,
            #adminmenu .update-plugins,
            .plugin-update-tr,
            .theme-update-message,
            .update-message,
            #setting-error-settings_updated,
            .notice-dismiss,
            .update-plugins,
            .plugins .update-message,
            .theme-browser .theme .update-message,
            .theme-info .notice,
            body.wp-admin .notice:not(.axiom-allowed-notice),
            body.admin-color-fresh .notice:not(.axiom-allowed-notice) {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
            
            /* Also hide any notification bubbles */
            .awaiting-mod,
            .update-count,
            .plugin-count,
            .theme-count,
            .update-plugins .update-count,
            #adminmenu .update-plugins,
            #adminmenu .awaiting-mod {
                display: none !important;
            }
            
            /* Ensure our content area is clean */
            #wpcontent {
                padding-left: 20px !important;
            }
            
            #wpbody-content {
                padding-bottom: 0 !important;
            }
            
            /* Remove any empty space where notices would be */
            #wpbody-content > div:empty,
            #wpbody-content > p:empty {
                display: none !important;
            }
            
            /* Specific overrides for persistent notices */
            .wrap > div:not(.wrap):not([class*="axiom"]),
            #wpbody-content > div:not(.wrap):not([class*="axiom"]) {
                display: none !important;
            }
        </style>
        <?php
    }
    
    /**
     * Hide notices with JavaScript as a backup
     */
    public function hide_notices_with_js() {
        ?>
        <script type="text/javascript">
        (function() {
            // Aggressive notice removal
            function removeNotices() {
                var selectors = [
                    '.notice',
                    '.notice-success',
                    '.notice-error',
                    '.notice-warning',
                    '.notice-info',
                    '.updated',
                    '.update-nag',
                    '.error',
                    '.warning',
                    '#message',
                    '.update-message',
                    'div.updated',
                    'div.error',
                    'div.notice',
                    '.admin-notices',
                    '#setting-error-settings_updated',
                    '.plugin-update-tr',
                    '.theme-update-message'
                ];
                
                selectors.forEach(function(selector) {
                    var elements = document.querySelectorAll(selector);
                    elements.forEach(function(element) {
                        // Check if it's not our allowed notice
                        if (!element.classList.contains('axiom-allowed-notice')) {
                            element.style.display = 'none';
                            element.remove();
                        }
                    });
                });
            }
            
            // Run immediately
            removeNotices();
            
            // Run on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', removeNotices);
            }
            
            // Run periodically to catch late-loading notices
            var noticeInterval = setInterval(removeNotices, 100);
            
            // Stop after 5 seconds
            setTimeout(function() {
                clearInterval(noticeInterval);
                // One final cleanup
                removeNotices();
            }, 5000);
            
            // Also watch for new nodes
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                var classNames = ['notice', 'updated', 'error', 'update-nag', 'warning'];
                                classNames.forEach(function(className) {
                                    if (node.classList && node.classList.contains(className)) {
                                        if (!node.classList.contains('axiom-allowed-notice')) {
                                            node.style.display = 'none';
                                            node.remove();
                                        }
                                    }
                                });
                            }
                        });
                    });
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                // Disconnect after 10 seconds
                setTimeout(function() {
                    observer.disconnect();
                }, 10000);
            }
        })();
        </script>
        <?php
    }
}