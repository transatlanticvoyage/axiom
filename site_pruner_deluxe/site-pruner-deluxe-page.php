<?php
/**
 * Site Pruner Deluxe Admin Page Template
 * 
 * Clean, blank page template ready for future content
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extra notice suppression at the template level
add_action('admin_init', function() {
    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
    remove_all_actions('network_admin_notices');
    remove_all_actions('user_admin_notices');
}, 999);

// Add inline styles for the page and additional notice hiding
add_action('admin_head', function() {
    echo <<<'STYLES'
<style>
        /* Aggressive notice hiding for Site Pruner Deluxe */
        .wrap > .notice,
        .wrap > .notice-error,
        .wrap > .notice-warning,
        .wrap > .notice-success,
        .wrap > .notice-info,
        .wrap > .update-nag,
        .wrap > .updated,
        .wrap > .error,
        #wpbody-content > .notice,
        #wpbody-content > .updated,
        #wpbody-content > .error,
        #wpbody-content > .update-nag,
        .wp-header-end + .notice,
        .wp-header-end + .updated,
        .wp-header-end + .error {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Custom styling for Site Pruner Deluxe interface */
        .site-pruner-deluxe-wrap {
            margin: 20px 20px 40px 0;
            max-width: 1200px;
        }
        
        .site-pruner-deluxe-header {
            background: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .site-pruner-deluxe-header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
            color: #23282d;
        }
        
        .site-pruner-deluxe-header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .site-pruner-deluxe-content {
            background: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
        }
        
        .pruner-section {
            margin-bottom: 30px;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
        }
        
        .pruner-section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
        }
        
        .pruner-textarea {
            width: 100%;
            height: 200px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.4;
            resize: vertical;
        }
        
        .pruner-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .pruner-button:hover {
            background: #005a87;
        }
        
        .pruner-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .pruner-button.danger {
            background: #d63638;
        }
        
        .pruner-button.danger:hover {
            background: #b32d2e;
        }
        
        .pruner-results {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.6;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .radio-group {
            margin: 15px 0;
        }
        
        .radio-group label {
            display: block;
            margin: 8px 0;
            font-weight: 500;
            cursor: pointer;
        }
        
        .radio-group input[type="radio"] {
            margin-right: 8px;
        }
        
        .spinner {
            display: none;
            margin-left: 10px;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0073aa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            color: #856404;
        }
        
        .warning-box strong {
            color: #d63638;
        }
</style>
STYLES;
});

?>

<div class="site-pruner-deluxe-wrap">
    <div class="site-pruner-deluxe-header">
        <h1>🗂️ Site Pruner Deluxe</h1>
        <p>Advanced site cleanup and optimization tools for WordPress management.</p>
    </div>
    
    <div class="site-pruner-deluxe-content">
        
        <!-- PART 1: Page URL Input and Validation -->
        <div class="pruner-section">
            <h3>📋 Part 1: Pages to Preserve</h3>
            <p>Enter page URLs, slugs, or permalinks (one per line) that you want to keep:</p>
            <textarea id="pages-to-preserve" class="pruner-textarea" placeholder="Examples:
/blueberry-side-effects
raspberry
grapes/
Apple-facts
https://dogs.com/bananas-now"></textarea>
            <br><br>
            <button id="validate-pages" class="pruner-button">Validate Pages</button>
            <span id="validate-spinner" class="spinner"></span>
            
            <div id="validation-results" class="pruner-results" style="display: none;"></div>
        </div>
        
        <!-- PART 2: Pruning Action Selection -->
        <div class="pruner-section">
            <h3>⚙️ Part 2: Choose Pruning Action</h3>
            <p>Select what to do with pages/posts that are NOT on the preserve list:</p>
            <div class="radio-group">
                <label>
                    <input type="radio" name="pruning_action" value="trash" checked>
                    🗑️ Trash Posts (move to trash)
                </label>
                <label>
                    <input type="radio" name="pruning_action" value="draft">
                    📄 Update To Draft (change status to draft)
                </label>
            </div>
        </div>
        
        <!-- PART 3: Execute Pruning -->
        <div class="pruner-section">
            <h3>🚨 Part 3: Execute Pruning Function</h3>
            
            <div class="warning-box">
                <strong>⚠️ WARNING:</strong> This action will affect ALL pages/posts on your site that are not on the preserve list above. 
                The following will NEVER be touched:
                <ul>
                    <li>Homepage</li>
                    <li>Pages with slugs: about, about-us, contact, contact-us, blog, aboutus, contactus</li>
                    <li>Parent pages required to maintain URLs of preserved child pages</li>
                </ul>
            </div>
            
            <p>Click the button below to execute the pruning function based on your settings above:</p>
            
            <button id="execute-pruning" class="pruner-button danger" disabled>Execute Pruning Function</button>
            <span id="execute-spinner" class="spinner"></span>
            
            <div id="execution-results" class="pruner-results" style="display: none;"></div>
        </div>
        
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        
        // Enable execute button only after successful validation
        var validationPassed = false;
        
        // Validate Pages functionality
        $('#validate-pages').on('click', function() {
            var pagesToPreserve = $('#pages-to-preserve').val().trim();
            
            if (!pagesToPreserve) {
                alert('Please enter some pages to preserve before validating.');
                return;
            }
            
            $('#validate-spinner').show();
            $('#validate-pages').prop('disabled', true);
            $('#validation-results').hide().empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'site_pruner_validate_pages',
                    pages_list: pagesToPreserve,
                    nonce: '<?php echo wp_create_nonce("site_pruner_nonce"); ?>'
                },
                success: function(response) {
                    $('#validate-spinner').hide();
                    $('#validate-pages').prop('disabled', false);
                    $('#validation-results').show().text(response.data);
                    
                    if (response.success) {
                        validationPassed = true;
                        $('#execute-pruning').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#validate-spinner').hide();
                    $('#validate-pages').prop('disabled', false);
                    $('#validation-results').show().text('Error occurred during validation.');
                }
            });
        });
        
        // Execute Pruning functionality
        $('#execute-pruning').on('click', function() {
            if (!validationPassed) {
                alert('Please validate pages first before executing pruning.');
                return;
            }
            
            if (!confirm('Are you absolutely sure you want to execute the pruning function? This action cannot be undone easily.')) {
                return;
            }
            
            var pagesToPreserve = $('#pages-to-preserve').val().trim();
            var pruningAction = $('input[name="pruning_action"]:checked').val();
            
            $('#execute-spinner').show();
            $('#execute-pruning').prop('disabled', true);
            $('#execution-results').hide().empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'site_pruner_execute_pruning',
                    pages_list: pagesToPreserve,
                    pruning_action: pruningAction,
                    nonce: '<?php echo wp_create_nonce("site_pruner_nonce"); ?>'
                },
                success: function(response) {
                    $('#execute-spinner').hide();
                    $('#execute-pruning').prop('disabled', false);
                    $('#execution-results').show().text(response.data);
                },
                error: function() {
                    $('#execute-spinner').hide();
                    $('#execute-pruning').prop('disabled', false);
                    $('#execution-results').show().text('Error occurred during pruning execution.');
                }
            });
        });
        
        // Reset validation when pages list changes
        $('#pages-to-preserve').on('input', function() {
            validationPassed = false;
            $('#execute-pruning').prop('disabled', true);
            $('#validation-results').hide();
            $('#execution-results').hide();
        });
        
    });
    </script>
</div>