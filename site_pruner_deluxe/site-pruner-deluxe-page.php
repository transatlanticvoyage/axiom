<?php
/**
 * Site Pruner Deluxe Admin Page Template
 * 
 * Clean, blank page template ready for future content
 */

if (!defined('ABSPATH')) {
    exit;
}

// AGGRESSIVE NOTICE SUPPRESSION - Phase 1: Remove all admin notice actions
add_action('admin_init', function() {
    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
    remove_all_actions('network_admin_notices');
    remove_all_actions('user_admin_notices');
}, 999);

// AGGRESSIVE NOTICE SUPPRESSION - Phase 2: Additional cleanup on admin_print_styles
add_action('admin_print_styles', function() {
    // Remove all notice actions again (some plugins add them late)
    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
    remove_all_actions('network_admin_notices');
    
    // Remove user admin notices from global filter
    global $wp_filter;
    if (isset($wp_filter['user_admin_notices'])) {
        unset($wp_filter['user_admin_notices']);
    }
}, 0);

// AGGRESSIVE NOTICE SUPPRESSION - Phase 3: CSS-based notice hiding and custom styles
add_action('admin_head', function() {
    // Add cache-busting version comment to force browser refresh
    $version = time(); // Use timestamp for aggressive cache busting
    echo <<<STYLES
<style>
/* Site Pruner Deluxe Styles - Version: $version */
        /* ULTRA-AGGRESSIVE notice hiding for Site Pruner Deluxe */
        .notice, .notice-warning, .notice-error, .notice-success, .notice-info,
        .updated, .error, .update-nag, .admin-notice,
        div.notice, div.updated, div.error, div.update-nag,
        .wrap > .notice, .wrap > .updated, .wrap > .error,
        #wpbody-content > .notice,
        #wpbody-content > .updated,
        #wpbody-content > .error,
        #wpbody-content > .update-nag,
        #adminmenu + .notice, #adminmenu + .updated, #adminmenu + .error,
        .update-php, .php-update-nag,
        .plugin-update-tr, .theme-update-message,
        .update-message, .updating-message,
        #update-nag, #deprecation-warning,
        .wp-header-end + .notice,
        .wp-header-end + .updated,
        .wp-header-end + .error,
        /* WordPress core update notices */
        .update-core-php, .notice-alt,
        /* Plugin activation/deactivation notices */
        .activated, .deactivated,
        /* Gutenberg/Block editor notices */
        .edit-post-header .components-notice-list,
        .edit-post-layout .components-notice,
        .edit-post-sidebar .components-notice,
        .components-snackbar-list,
        .components-notice-list .components-notice,
        .interface-interface-skeleton__notices,
        .edit-post-notices,
        .block-editor-warning,
        .components-notice.is-warning {
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
            width: 500px !important;
            height: 500px !important;
            min-width: 500px !important;
            min-height: 500px !important;
            max-width: 500px !important;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            line-height: 1.5;
            resize: vertical;
            background: #f9f9f9;
            transition: border-color 0.3s;
        }
        
        .pruner-textarea:focus {
            border-color: #0073aa;
            background: #fff;
            outline: none;
        }
        
        .pruner-button {
            background: linear-gradient(135deg, #0085ba 0%, #0073aa 100%);
            color: white;
            border: none;
            padding: 14px 28px; /* Increased padding */
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px; /* Increased font size */
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 115, 170, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pruner-button:hover {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            box-shadow: 0 4px 8px rgba(0, 115, 170, 0.4);
            transform: translateY(-1px);
        }
        
        .pruner-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .pruner-button.success {
            background: linear-gradient(135deg, #46b450 0%, #389e41 100%);
        }
        
        .pruner-button.success:hover {
            background: linear-gradient(135deg, #389e41 0%, #2d7f36 100%);
            box-shadow: 0 4px 8px rgba(70, 180, 80, 0.4);
        }
        
        .pruner-button.danger {
            background: linear-gradient(135deg, #dc3232 0%, #b32d2e 100%);
        }
        
        .pruner-button.danger:hover {
            background: linear-gradient(135deg, #b32d2e 0%, #8e2424 100%);
            box-shadow: 0 4px 8px rgba(220, 50, 50, 0.4);
        }
        
        .pruner-button.small {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .pruner-results {
            background: #fff;
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-top: 15px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-wrap;
            min-height: 500px; /* Increased from max-height 400px */
            max-height: 800px;
            overflow-y: auto;
            background: #f9f9f9;
        }
        
        .section-divider {
            margin: 40px 0 30px 0;
            border: 0;
            height: 2px;
            background: linear-gradient(to right, transparent, #ddd 20%, #ddd 80%, transparent);
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
        <hr class="section-divider">
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
            <button id="validate-pages" class="pruner-button success">Validate Pages</button>
            <span id="validate-spinner" class="spinner"></span>
            
            <div id="validation-results-container" style="display: none; margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0; color: #333;">📋 Validation Results</h4>
                    <button id="copy-validation-results" class="pruner-button small" type="button">
                        📋 Copy Results
                    </button>
                </div>
                <textarea id="validation-results" class="pruner-textarea" style="width: 100% !important; height: 500px !important; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 13px; background: #f9f9f9;" readonly></textarea>
            </div>
        </div>
        
        <!-- PART 2: Pruning Action Selection -->
        <hr class="section-divider">
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
        <hr class="section-divider">
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
            
            <div id="execution-results-container" style="display: none; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0; color: #333;">📊 Execution Report</h4>
                    <button id="copy-execution-report" class="pruner-button small">
                        📋 Copy Report
                    </button>
                </div>
                <div id="execution-results" class="pruner-results"></div>
            </div>
        </div>
        
    </div>
    
    <!-- Debug Information Panel -->
    <div id="debug-panel" style="display: none; margin-top: 20px; padding: 20px; background: #fff5f5; border: 2px solid #ff0000; border-radius: 8px;">
        <h3 style="color: #ff0000; margin-top: 0;">🐛 Debug Information</h3>
        <pre id="debug-content" style="background: white; padding: 15px; border: 1px solid #ccc; max-height: 600px; overflow-y: auto; font-family: monospace; font-size: 13px; border-radius: 4px;"></pre>
        <button id="copy-debug" class="pruner-button small" style="margin-top: 15px;">📋 Copy Debug Info</button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        
        // Enable execute button only after successful validation
        var validationPassed = false;
        var lastDebugInfo = {};
        
        // Copy debug info function
        $('#copy-debug').on('click', function() {
            var debugText = $('#debug-content').text();
            navigator.clipboard.writeText(debugText).then(function() {
                alert('Debug information copied to clipboard!');
            }).catch(function(err) {
                alert('Failed to copy: ' + err);
            });
        });
        
        // Copy validation results function
        $('#copy-validation-results').on('click', function() {
            var validationText = $('#validation-results').val();
            navigator.clipboard.writeText(validationText).then(function() {
                alert('Validation results copied to clipboard!');
            }).catch(function(err) {
                alert('Failed to copy: ' + err);
            });
        });
        
        // Validate Pages functionality
        $('#validate-pages').on('click', function() {
            var pagesToPreserve = $('#pages-to-preserve').val().trim();
            
            if (!pagesToPreserve) {
                alert('Please enter some pages to preserve before validating.');
                return;
            }
            
            $('#validate-spinner').show();
            $('#validate-pages').prop('disabled', true);
            $('#validation-results-container').hide();
            $('#validation-results').val('');
            $('#debug-panel').hide();
            
            // Clear previous debug info
            lastDebugInfo = {
                timestamp: new Date().toISOString(),
                request: {
                    action: 'site_pruner_validate_pages',
                    pages_list: pagesToPreserve,
                    pages_count: pagesToPreserve.split('\n').length,
                    ajax_url: ajaxurl
                }
            };
            
            console.log('Sending validation request:', lastDebugInfo.request);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'site_pruner_validate_pages',
                    pages_list: pagesToPreserve,
                    nonce: '<?php echo wp_create_nonce("site_pruner_nonce"); ?>'
                },
                success: function(response, textStatus, jqXHR) {
                    $('#validate-spinner').hide();
                    $('#validate-pages').prop('disabled', false);
                    
                    console.log('Validation response:', response);
                    
                    // Update debug info
                    lastDebugInfo.response = response;
                    lastDebugInfo.textStatus = textStatus;
                    lastDebugInfo.httpStatus = jqXHR.status;
                    lastDebugInfo.responseHeaders = jqXHR.getAllResponseHeaders();
                    
                    if (response && response.data) {
                        $('#validation-results').val(response.data);
                        $('#validation-results-container').show();
                        
                        if (response.success) {
                            validationPassed = true;
                            $('#execute-pruning').prop('disabled', false);
                        } else {
                            // Show debug panel on failure
                            showDebugInfo('Validation failed with success=false');
                        }
                    } else {
                        $('#validation-results').val('Invalid response format from server.');
                        $('#validation-results-container').show();
                        showDebugInfo('Invalid response format');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#validate-spinner').hide();
                    $('#validate-pages').prop('disabled', false);
                    
                    console.error('AJAX Error:', textStatus, errorThrown);
                    console.error('Response:', jqXHR.responseText);
                    
                    // Collect detailed error information
                    lastDebugInfo.error = {
                        textStatus: textStatus,
                        errorThrown: errorThrown,
                        httpStatus: jqXHR.status,
                        statusText: jqXHR.statusText,
                        responseText: jqXHR.responseText,
                        responseHeaders: jqXHR.getAllResponseHeaders()
                    };
                    
                    // Try to parse response if it's JSON
                    try {
                        if (jqXHR.responseText) {
                            var jsonResponse = JSON.parse(jqXHR.responseText);
                            lastDebugInfo.parsedResponse = jsonResponse;
                        }
                    } catch(e) {
                        lastDebugInfo.parseError = e.message;
                    }
                    
                    // Check for common issues
                    var errorMessage = 'Error occurred during validation.';
                    
                    if (jqXHR.status === 0) {
                        errorMessage += ' (Network error - check connection)';
                        lastDebugInfo.diagnosis = 'Network error - possibly CORS or connection issue';
                    } else if (jqXHR.status === 404) {
                        errorMessage += ' (404 - AJAX endpoint not found)';
                        lastDebugInfo.diagnosis = 'WordPress AJAX endpoint not found - check if admin-ajax.php is accessible';
                    } else if (jqXHR.status === 403) {
                        errorMessage += ' (403 - Permission denied)';
                        lastDebugInfo.diagnosis = 'Permission denied - check user capabilities and nonce';
                    } else if (jqXHR.status === 500) {
                        errorMessage += ' (500 - Server error)';
                        lastDebugInfo.diagnosis = 'Server error - check PHP error logs';
                    } else if (jqXHR.status === 302 || jqXHR.status === 301) {
                        errorMessage += ' (Redirect detected - likely login required)';
                        lastDebugInfo.diagnosis = 'Redirect detected - user might not be logged in';
                    }
                    
                    $('#validation-results').val(
                        errorMessage + '\n\n' +
                        'See debug information below for details.'
                    );
                    $('#validation-results-container').show();
                    
                    showDebugInfo('AJAX request failed');
                }
            });
        });
        
        function showDebugInfo(message) {
            lastDebugInfo.debugMessage = message;
            lastDebugInfo.userAgent = navigator.userAgent;
            lastDebugInfo.currentUrl = window.location.href;
            lastDebugInfo.wpVersion = '<?php global $wp_version; echo $wp_version; ?>';
            lastDebugInfo.phpVersion = '<?php echo phpversion(); ?>';
            
            var debugText = '=== SITE PRUNER DELUXE DEBUG INFO ===\n\n';
            debugText += 'Message: ' + message + '\n';
            debugText += 'Timestamp: ' + lastDebugInfo.timestamp + '\n';
            debugText += 'WP Version: ' + lastDebugInfo.wpVersion + '\n';
            debugText += 'PHP Version: ' + lastDebugInfo.phpVersion + '\n';
            debugText += 'Current URL: ' + lastDebugInfo.currentUrl + '\n';
            debugText += 'User Agent: ' + lastDebugInfo.userAgent + '\n\n';
            debugText += '--- REQUEST INFO ---\n';
            debugText += JSON.stringify(lastDebugInfo.request, null, 2) + '\n\n';
            
            if (lastDebugInfo.error) {
                debugText += '--- ERROR INFO ---\n';
                debugText += JSON.stringify(lastDebugInfo.error, null, 2) + '\n\n';
            }
            
            if (lastDebugInfo.response) {
                debugText += '--- RESPONSE INFO ---\n';
                debugText += JSON.stringify(lastDebugInfo.response, null, 2) + '\n\n';
            }
            
            if (lastDebugInfo.diagnosis) {
                debugText += '--- DIAGNOSIS ---\n';
                debugText += lastDebugInfo.diagnosis + '\n\n';
            }
            
            debugText += '--- FULL DEBUG OBJECT ---\n';
            debugText += JSON.stringify(lastDebugInfo, null, 2);
            
            $('#debug-content').text(debugText);
            $('#debug-panel').show();
        }
        
        // Copy execution report functionality
        $('#copy-execution-report').on('click', function() {
            var reportText = $('#execution-results').text();
            navigator.clipboard.writeText(reportText).then(function() {
                alert('Execution report copied to clipboard!');
            }).catch(function(err) {
                alert('Failed to copy: ' + err);
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
            $('#execution-results-container').hide();
            $('#execution-results').empty();
            
            console.log('Executing pruning with action:', pruningAction);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'site_pruner_execute_pruning',
                    pages_list: pagesToPreserve,
                    pruning_action: pruningAction,
                    nonce: '<?php echo wp_create_nonce("site_pruner_nonce"); ?>'
                },
                success: function(response) {
                    $('#execute-spinner').hide();
                    $('#execute-pruning').prop('disabled', false);
                    
                    console.log('Execution response:', response);
                    
                    if (response && response.data) {
                        $('#execution-results').text(response.data);
                        $('#execution-results-container').show();
                    } else {
                        $('#execution-results').text('Invalid response format from server.');
                        $('#execution-results-container').show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#execute-spinner').hide();
                    $('#execute-pruning').prop('disabled', false);
                    
                    console.error('Execution AJAX Error:', textStatus, errorThrown);
                    console.error('Response:', jqXHR.responseText);
                    
                    var errorMessage = 'Error occurred during pruning execution.\n\n';
                    errorMessage += 'Status: ' + jqXHR.status + ' ' + jqXHR.statusText + '\n';
                    if (jqXHR.responseText) {
                        errorMessage += 'Response: ' + jqXHR.responseText;
                    }
                    
                    $('#execution-results').text(errorMessage);
                    $('#execution-results-container').show();
                }
            });
        });
        
        // Reset validation when pages list changes
        $('#pages-to-preserve').on('input', function() {
            validationPassed = false;
            $('#execute-pruning').prop('disabled', true);
            $('#validation-results-container').hide();
            $('#execution-results').hide();
        });
        
    });
    </script>
</div>