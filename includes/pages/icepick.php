<?php
/**
 * Axiom Icepick Editor
 * A clean interface with aggressive admin notice suppression
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

// Add inline styles to hide any notices that might slip through
add_action('admin_head', function() {
    echo <<<'STYLES'
<style>
        /* Aggressive notice hiding for Axiom Icepick */
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
        
        /* Custom styling for Icepick interface */
        .axiom-icepick-wrap {
            margin: 20px 20px 40px 0;
            max-width: 1200px;
        }
        
        .axiom-icepick-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 32px;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .axiom-icepick-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 40%;
            height: 200%;
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(35deg);
        }
        
        .axiom-icepick-header h1 {
            color: white;
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            z-index: 1;
        }
        
        .axiom-icepick-header h1 span {
            background: rgba(255, 255, 255, 0.2);
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 32px;
        }
        
        .axiom-icepick-header p {
            margin: 12px 0 0;
            opacity: 0.9;
            font-size: 17px;
            position: relative;
            z-index: 1;
        }
        
        .axiom-icepick-container {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .axiom-icepick-status {
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            border: 1px solid #e2e8f0;
            padding: 20px 24px;
            margin-bottom: 28px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        
        .axiom-icepick-status > div:first-child {
            display: flex;
            gap: 20px;
            align-items: center;
            font-size: 14px;
            color: #475569;
        }
        
        .axiom-icepick-status strong {
            color: #1e293b;
            font-weight: 600;
        }
        
        .prefix-badge {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 10px 20px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            animation: subtlePulse 3s ease-in-out infinite;
        }
        
        @keyframes subtlePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .prefix-badge .prefix-label {
            opacity: 0.85;
            font-weight: 500;
            font-size: 13px;
        }
        
        #site-prefix {
            font-family: "SF Mono", Monaco, "Courier New", monospace;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }
        
        .icepick-form {
            max-width: 720px;
        }
        
        .form-group {
            margin-bottom: 32px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1e293b;
            font-size: 14px;
            letter-spacing: -0.025em;
        }
        
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: "SF Mono", Monaco, "Courier New", monospace;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        
        .form-group input[type="text"]:hover,
        .form-group textarea:hover {
            border-color: #cbd5e1;
        }
        
        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1), 0 1px 2px rgba(0, 0, 0, 0.05);
            background: #ffffff;
        }
        
        .form-group textarea {
            min-height: 140px;
            resize: vertical;
            font-family: "SF Mono", Monaco, "Courier New", monospace;
            line-height: 1.5;
        }
        
        .form-group .help-text {
            margin-top: 8px;
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }
        
        .validate-button {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
        }
        
        .validate-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .validate-button:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .validate-button:disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .feedback-message {
            margin-top: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            display: none;
            font-size: 14px;
            line-height: 1.5;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feedback-message.success {
            background: linear-gradient(to right, #f0fdf4, #dcfce7);
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .feedback-message.error {
            background: linear-gradient(to right, #fef2f2, #fee2e2);
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .feedback-message.info {
            background: linear-gradient(to right, #eff6ff, #dbeafe);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 2px solid #f1f5f9;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
        }
        
        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #4338ca, #6d28d9);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .btn-primary:disabled {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            cursor: not-allowed;
            opacity: 1;
            box-shadow: none;
            color: #94a3b8;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
        }
        
        .btn-secondary:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .btn-secondary:disabled {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            cursor: not-allowed;
            opacity: 1;
            box-shadow: none;
            color: #94a3b8;
        }
        
        .output-section {
            margin-top: 40px;
            padding-top: 32px;
            border-top: 2px solid #f1f5f9;
        }
        
        .output-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .output-section h3::before {
            content: "📋";
            font-size: 18px;
        }
        
        .output-box {
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            font-family: "SF Mono", Monaco, "Courier New", monospace;
            font-size: 14px;
            line-height: 1.6;
            min-height: 120px;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #334155;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .spinner {
            display: none;
            border: 3px solid #e2e8f0;
            border-top: 3px solid #4f46e5;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 0.8s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .axiom-icepick-header {
                padding: 30px 24px;
            }
            
            .axiom-icepick-header h1 {
                font-size: 28px;
            }
            
            .axiom-icepick-container {
                padding: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
            }
            
            .axiom-icepick-status {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
        }
</style>
STYLES;
});
?>

<div class="wrap axiom-icepick-wrap">
    <div class="axiom-icepick-header">
        <h1>
            <span>🧊</span>
            Axiom Icepick Editor
        </h1>
        <p>A clean, distraction-free editing interface</p>
    </div>
    
    <div class="axiom-icepick-status">
        <div>
            <strong>System Status:</strong> Ready | 
            <strong>Database:</strong> <?php echo esc_html(DB_NAME); ?> | 
            <strong>User:</strong> <?php echo esc_html(wp_get_current_user()->user_login); ?>
        </div>
        <div class="prefix-badge">
            <span class="prefix-label">Table Prefix:</span>
            <span id="site-prefix"><?php global $wpdb; echo esc_html($wpdb->prefix); ?></span>
        </div>
    </div>
    
    <div class="axiom-icepick-container">
        <form id="icepick-form" class="icepick-form">
            <?php wp_nonce_field('axiom_icepick_nonce', 'icepick_nonce'); ?>
            
            <div class="form-group">
                <label for="db-table">DB Table:</label>
                <input type="text" id="db-table" name="db_table" placeholder="e.g., wp_options or just options">
                <div class="help-text">Enter the table name. You can use wp_ prefix and it will be automatically converted to the correct prefix.</div>
            </div>
            
            <div class="form-group">
                <label for="row-identifier">Row Identifier: (Primary Key Value)</label>
                <input type="text" id="row-identifier" name="row_identifier" placeholder="e.g., 123 or unique_key_value">
                <div class="help-text">Enter the primary key value to identify the specific row.</div>
            </div>
            
            <div class="form-group">
                <label for="column-identifier">Column Identifier: (Column Name)</label>
                <input type="text" id="column-identifier" name="column_identifier" placeholder="e.g., option_value">
                <div class="help-text">Enter the column name you want to read or update.</div>
                <button type="button" id="validate-btn" class="validate-button" disabled>Validate Table/Row/Column</button>
                <div id="validate-feedback" class="feedback-message"></div>
            </div>
            
            <div class="form-group">
                <label for="new-value">New Value to Update:</label>
                <textarea id="new-value" name="new_value" placeholder="Enter the new value here..."></textarea>
                <div class="help-text">Enter the new value for the specified column. Leave empty if you only want to read the current value.</div>
            </div>
            
            <div class="action-buttons">
                <button type="button" id="update-btn" class="btn-primary" disabled>
                    Update DB Value For The Specified Table/Row/Column
                </button>
                <button type="button" id="read-btn" class="btn-secondary" disabled>
                    Read Value Only
                </button>
            </div>
            
            <div class="output-section">
                <h3>Output:</h3>
                <div id="output-spinner" class="spinner"></div>
                <div id="output-box" class="output-box">Ready for operations...</div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Additional client-side notice removal as a final safeguard
    setTimeout(function() {
        $('.notice, .notice-error, .notice-warning, .notice-success, .notice-info, .update-nag, .updated, .error').remove();
    }, 100);
    
    // Monitor for any dynamically added notices and remove them
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                $(mutation.addedNodes).each(function() {
                    if ($(this).hasClass('notice') || 
                        $(this).hasClass('updated') || 
                        $(this).hasClass('error') || 
                        $(this).hasClass('update-nag')) {
                        $(this).remove();
                    }
                });
            }
        });
    });
    
    // Start observing the body for added notices
    observer.observe(document.getElementById('wpbody-content'), {
        childList: true,
        subtree: true
    });
    
    console.log('Axiom Icepick Editor initialized with aggressive notice suppression');
    
    // Get the site prefix for conversion
    var sitePrefix = $('#site-prefix').text();
    
    // Function to convert table name with prefix
    function convertTableName(tableName) {
        tableName = tableName.trim();
        
        // If it starts with wp_ and site prefix is different, replace it
        if (tableName.startsWith('wp_') && sitePrefix !== 'wp_') {
            return tableName.replace(/^wp_/, sitePrefix);
        }
        
        // If it doesn't have any prefix, add the site prefix
        if (!tableName.includes('_') || (!tableName.startsWith(sitePrefix) && !tableName.startsWith('wp_'))) {
            return sitePrefix + tableName;
        }
        
        return tableName;
    }
    
    // Check if first 3 fields are filled
    function checkFieldsAndEnableButtons() {
        var table = $('#db-table').val().trim();
        var row = $('#row-identifier').val().trim();
        var column = $('#column-identifier').val().trim();
        
        if (table && row && column) {
            $('#validate-btn').prop('disabled', false);
            $('#read-btn').prop('disabled', false);
            $('#update-btn').prop('disabled', false);
        } else {
            $('#validate-btn').prop('disabled', true);
            $('#read-btn').prop('disabled', true);
            $('#update-btn').prop('disabled', true);
        }
    }
    
    // Monitor input fields
    $('#db-table, #row-identifier, #column-identifier').on('input keyup', checkFieldsAndEnableButtons);
    
    // Validate button click
    $('#validate-btn').click(function() {
        var $btn = $(this);
        var $feedback = $('#validate-feedback');
        
        $btn.prop('disabled', true);
        $feedback.removeClass('success error info').hide();
        
        // Show spinner
        $feedback.html('<div class="spinner" style="display:block;"></div>').show();
        
        var tableName = convertTableName($('#db-table').val());
        var rowId = $('#row-identifier').val();
        var columnName = $('#column-identifier').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'axiom_icepick_validate',
                nonce: $('#icepick_nonce').val(),
                table: tableName,
                row_id: rowId,
                column: columnName
            },
            success: function(response) {
                $btn.prop('disabled', false);
                
                if (response.success) {
                    $feedback.removeClass('error info').addClass('success');
                    $feedback.html('✅ ' + response.data.message).show();
                } else {
                    $feedback.removeClass('success info').addClass('error');
                    $feedback.html('❌ ' + response.data).show();
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                $feedback.removeClass('success info').addClass('error');
                $feedback.html('❌ Ajax error occurred').show();
            }
        });
    });
    
    // Read button click
    $('#read-btn').click(function() {
        var $btn = $(this);
        var $output = $('#output-box');
        var $spinner = $('#output-spinner');
        
        $btn.prop('disabled', true);
        $output.hide();
        $spinner.show();
        
        var tableName = convertTableName($('#db-table').val());
        var rowId = $('#row-identifier').val();
        var columnName = $('#column-identifier').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'axiom_icepick_read',
                nonce: $('#icepick_nonce').val(),
                table: tableName,
                row_id: rowId,
                column: columnName
            },
            success: function(response) {
                $spinner.hide();
                $output.show();
                $btn.prop('disabled', false);
                
                if (response.success) {
                    var output = '=== READ OPERATION SUCCESSFUL ===\n\n';
                    output += 'Table: ' + response.data.table + '\n';
                    output += 'Row ID: ' + response.data.row_id + '\n';
                    output += 'Column: ' + response.data.column + '\n';
                    output += 'Primary Key: ' + response.data.primary_key + '\n\n';
                    output += '--- CURRENT VALUE ---\n';
                    output += response.data.value;
                    
                    $output.text(output);
                } else {
                    $output.text('❌ Error: ' + response.data);
                }
            },
            error: function() {
                $spinner.hide();
                $output.show();
                $btn.prop('disabled', false);
                $output.text('❌ Ajax error occurred');
            }
        });
    });
    
    // Update button click
    $('#update-btn').click(function() {
        var newValue = $('#new-value').val();
        
        if (!newValue) {
            alert('Please enter a new value to update');
            return;
        }
        
        if (!confirm('Are you sure you want to update this database value? This action cannot be easily undone.')) {
            return;
        }
        
        var $btn = $(this);
        var $output = $('#output-box');
        var $spinner = $('#output-spinner');
        
        $btn.prop('disabled', true);
        $output.hide();
        $spinner.show();
        
        var tableName = convertTableName($('#db-table').val());
        var rowId = $('#row-identifier').val();
        var columnName = $('#column-identifier').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'axiom_icepick_update',
                nonce: $('#icepick_nonce').val(),
                table: tableName,
                row_id: rowId,
                column: columnName,
                new_value: newValue
            },
            success: function(response) {
                $spinner.hide();
                $output.show();
                $btn.prop('disabled', false);
                
                if (response.success) {
                    var output = '=== UPDATE OPERATION SUCCESSFUL ===\n\n';
                    output += 'Table: ' + response.data.table + '\n';
                    output += 'Row ID: ' + response.data.row_id + '\n';
                    output += 'Column: ' + response.data.column + '\n';
                    output += 'Primary Key: ' + response.data.primary_key + '\n\n';
                    output += '--- PREVIOUS VALUE ---\n';
                    output += response.data.old_value + '\n\n';
                    output += '--- NEW VALUE ---\n';
                    output += response.data.new_value + '\n\n';
                    output += '✅ Update completed successfully!';
                    
                    $output.text(output);
                    
                    // Clear the new value field after successful update
                    $('#new-value').val('');
                } else {
                    $output.text('❌ Error: ' + response.data);
                }
            },
            error: function() {
                $spinner.hide();
                $output.show();
                $btn.prop('disabled', false);
                $output.text('❌ Ajax error occurred');
            }
        });
    });
});
</script>