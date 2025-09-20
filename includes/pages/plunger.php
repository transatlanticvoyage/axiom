<?php
/**
 * Axiom Plunger - Direct Schema Editor
 * DANGER: This tool can directly modify your database schema
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>⚡ Axiom Plunger - Direct Schema Editor</h1>
    
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <h3 style="color: #856404; margin-top: 0;">⚠️ WARNING - DANGEROUS TOOL</h3>
        <p style="color: #856404; margin-bottom: 0;">
            This tool executes SQL directly on your database. <strong>Use with extreme caution.</strong><br>
            Only the following operations are allowed for safety:
        </p>
        <ul style="color: #856404;">
            <li><code>ALTER TABLE [table] ADD/DROP COLUMN [column] [type]</code> - Modify table structure</li>
            <li><code>ALTER TABLE [table] ADD INDEX/KEY [name] ([column])</code> - Add indexes</li>
            <li><code>CREATE INDEX [name] ON [table] ([column])</code> - Create indexes</li>
            <li><code>INSERT INTO [table] ([columns]) VALUES ([values])</code> - Insert data</li>
            <li><code>REPLACE INTO [table] ([columns]) VALUES ([values])</code> - Replace data</li>
            <li><code>UPDATE [table] SET [column=value] WHERE [condition]</code> - Update data</li>
            <li><code>DELETE FROM [table] WHERE [condition]</code> - Delete data</li>
            <li><code>SELECT [columns] FROM [table] WHERE [condition]</code> - Query data</li>
            <li><code>SHOW COLUMNS/TABLES, DESCRIBE [table]</code> - View structure</li>
        </ul>
    </div>

    <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
        <h2>Database Information</h2>
        <p><strong>Database Name:</strong> <?php echo DB_NAME; ?></p>
        <p><strong>Table Prefix:</strong> <?php global $wpdb; echo $wpdb->prefix; ?></p>
        <p><strong>Current User:</strong> <?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)</p>
    </div>

    <!-- Quick Commands Section -->
    <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
        <h2>Quick Commands</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <h4>View Table Structure</h4>
                <button class="button quick-sql" data-sql="SHOW COLUMNS FROM <?php echo $wpdb->prefix; ?>zen_services">Show zen_services columns</button>
                <button class="button quick-sql" data-sql="DESCRIBE <?php echo $wpdb->prefix; ?>zen_services">Describe zen_services</button>
            </div>
            <div>
                <h4>Common Operations</h4>
                <button class="button quick-sql" data-sql="SHOW TABLES LIKE '%zen%'">Show all zen tables</button>
                <button class="button quick-sql" data-sql="SHOW COLUMNS FROM <?php echo $wpdb->prefix; ?>zen_sitespren">Show zen_sitespren columns</button>
            </div>
        </div>
    </div>

    <!-- SQL Editor -->
    <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2>SQL Command Editor</h2>
        
        <form id="sql-executor-form">
            <!-- Database Name Display -->
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Connected Database:</label>
                <div style="display: flex; align-items: center;">
                    <input 
                        type="text" 
                        id="database-name" 
                        value="<?php echo esc_attr(DB_NAME); ?>" 
                        readonly 
                        style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 3px; background: #f8f9fa; font-family: monospace; font-size: 14px;"
                    >
                    <button type="button" id="copy-db-name" class="button button-secondary" style="margin-left: 8px;">Copy</button>
                </div>
            </div>
            
            <!-- Auto-prefix conversion option -->
            <div style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; font-weight: bold;">
                    <input type="checkbox" id="auto-convert-prefix" checked style="margin-right: 8px;">
                    Convert wp_ prefixes in tables in the SQL automatically
                </label>
                <p style="margin: 5px 0 0 24px; font-size: 13px; color: #666;">
                    Your site prefix: <code><?php global $wpdb; echo $wpdb->prefix; ?></code>
                </p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="sql-command-pasted" style="display: block; font-weight: bold; margin-bottom: 5px;">SQL Command (pasted):</label>
                <textarea 
                    id="sql-command-pasted" 
                    name="sql" 
                    rows="8" 
                    style="width: 100%; font-family: monospace; font-size: 14px; padding: 10px; border: 1px solid #ddd; border-radius: 3px;"
                    placeholder="Enter your SQL command here...

Example for your current issue:
ALTER TABLE wp_zen_services ADD COLUMN asn_service_page_id BIGINT(20) UNSIGNED DEFAULT NULL;
ALTER TABLE wp_zen_services ADD INDEX asn_service_page_id (asn_service_page_id);"
                ></textarea>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="sql-command-final" style="display: block; font-weight: bold; margin-bottom: 5px;">SQL Command (final):</label>
                <textarea 
                    id="sql-command-final" 
                    rows="8" 
                    style="width: 100%; font-family: monospace; font-size: 14px; padding: 10px; border: 1px solid #e6f3ff; border-radius: 3px; background: #f8f9fa; color: #495057;"
                    readonly
                    placeholder="The final SQL that will be executed will appear here..."
                ></textarea>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" id="confirm-execution" style="margin-right: 8px;">
                    I understand this will modify the database and cannot be easily undone
                </label>
            </div>
            
            <div style="margin-bottom: 15px;">
                <button type="submit" id="execute-sql" class="button button-primary" disabled style="background: #dc3545; border-color: #dc3545;">
                    🔥 Execute SQL Command
                </button>
                <button type="button" id="clear-sql" class="button button-secondary" style="margin-left: 10px;">Clear</button>
            </div>
        </form>

        <!-- Results Area -->
        <div id="sql-results" style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 3px; background: #f8f9fa; display: none;">
            <h4>Execution Results:</h4>
            <div id="sql-output" style="font-family: monospace; font-size: 13px; white-space: pre-wrap;"></div>
        </div>
        
        <!-- Loading indicator -->
        <div id="sql-loading" style="display: none; text-align: center; margin-top: 15px;">
            <span style="font-size: 18px;">⏳ Executing SQL...</span>
        </div>
    </div>

    <!-- Crunchy Box for Query Results -->
    <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">
        <div style="display: flex; align-items: center; margin-bottom: 10px;">
            <h2 style="margin: 0; margin-right: 15px;"><strong>crunchy_box</strong></h2>
            <button type="button" id="copy-crunchy-box" class="button button-secondary" style="padding: 4px 12px; font-size: 12px;">Copy</button>
        </div>
        <textarea 
            id="crunchy-box" 
            readonly 
            rows="12" 
            style="width: 100%; font-family: monospace; font-size: 13px; padding: 15px; border: 2px solid #28a745; border-radius: 5px; background: #f8fff9; color: #155724; resize: vertical;"
            placeholder="Query results (like SHOW COLUMNS, DESCRIBE, etc.) will appear here..."
        ></textarea>
    </div>

    <!-- Recent Operations -->
    <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">
        <h2>Recent SQL Operations</h2>
        <div id="recent-operations">
            <?php
            global $wpdb;
            $recent_ops = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}axiom_operations 
                 WHERE operation_type = 'direct_sql' 
                 ORDER BY created_at DESC 
                 LIMIT 10"
            );
            
            if ($recent_ops) {
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<thead><tr style="background: #f8f9fa;">';
                echo '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Time</th>';
                echo '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">SQL</th>';
                echo '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Status</th>';
                echo '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">User</th>';
                echo '</tr></thead><tbody>';
                
                foreach ($recent_ops as $op) {
                    $status_color = '';
                    switch ($op->status) {
                        case 'completed': $status_color = 'color: green;'; break;
                        case 'failed': $status_color = 'color: red;'; break;
                        case 'running': $status_color = 'color: orange;'; break;
                    }
                    
                    echo '<tr>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($op->created_at) . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; max-width: 400px; overflow: hidden; text-overflow: ellipsis;">' . esc_html(substr($op->operation_data, 0, 100)) . '...</td>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd; ' . $status_color . '">' . esc_html($op->status) . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($op->user_id) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<p style="color: #666;">No recent SQL operations found.</p>';
            }
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Enable execute button only when checkbox is checked
    $('#confirm-execution').change(function() {
        $('#execute-sql').prop('disabled', !this.checked);
        if (this.checked) {
            $('#execute-sql').css({
                'background': '#dc3545',
                'border-color': '#dc3545'
            });
        } else {
            $('#execute-sql').css({
                'background': '#6c757d',
                'border-color': '#6c757d'
            });
        }
    });
    
    // Function to convert SQL prefixes
    function convertSqlPrefixes(sql) {
        if (!$('#auto-convert-prefix').is(':checked')) {
            return sql;
        }
        
        var actualPrefix = '<?php global $wpdb; echo $wpdb->prefix; ?>';
        
        // Pattern to match table names with wp_ prefix
        var patterns = [
            /\b(ALTER\s+TABLE\s+)wp_(\w+)/gi,
            /\b(FROM\s+)wp_(\w+)/gi, 
            /\b(JOIN\s+)wp_(\w+)/gi,
            /\b(INTO\s+)wp_(\w+)/gi,
            /\b(UPDATE\s+)wp_(\w+)/gi,
            /\b(INDEX\s+\w+\s+ON\s+)wp_(\w+)/gi,
            /\b(SHOW\s+COLUMNS\s+FROM\s+)wp_(\w+)/gi,
            /\b(DESCRIBE\s+)wp_(\w+)/gi,
            /\b(DESC\s+)wp_(\w+)/gi
        ];
        
        patterns.forEach(function(pattern) {
            sql = sql.replace(pattern, '$1' + actualPrefix + '$2');
        });
        
        return sql;
    }
    
    // Function to update final SQL
    function updateFinalSql() {
        var pastedSql = $('#sql-command-pasted').val();
        var finalSql = convertSqlPrefixes(pastedSql);
        $('#sql-command-final').val(finalSql);
    }
    
    // Update final SQL when pasted SQL changes
    $('#sql-command-pasted').on('input keyup paste', function() {
        setTimeout(updateFinalSql, 10); // Small delay to ensure paste content is processed
    });
    
    // Update final SQL when prefix conversion checkbox changes
    $('#auto-convert-prefix').change(function() {
        updateFinalSql();
    });
    
    // Quick SQL buttons
    $('.quick-sql').click(function() {
        var sql = $(this).data('sql');
        $('#sql-command-pasted').val(sql);
        updateFinalSql();
    });
    
    // Copy database name button
    $('#copy-db-name').click(function() {
        var dbName = $('#database-name').val();
        
        // Create temporary input to copy text
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(dbName).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Visual feedback
        var originalText = $(this).text();
        $(this).text('✓ Copied!').css('background', '#28a745');
        setTimeout(function() {
            $('#copy-db-name').text(originalText).css('background', '');
        }, 2000);
    });
    
    // Copy crunchy box button
    $('#copy-crunchy-box').click(function() {
        var crunchyContent = $('#crunchy-box').val();
        
        if (!crunchyContent.trim()) {
            alert('Crunchy box is empty - nothing to copy!');
            return;
        }
        
        // Create temporary textarea to copy text
        var tempTextarea = $('<textarea>');
        $('body').append(tempTextarea);
        tempTextarea.val(crunchyContent).select();
        document.execCommand('copy');
        tempTextarea.remove();
        
        // Visual feedback
        var originalText = $(this).text();
        $(this).text('✓ Copied!').css('background', '#28a745');
        setTimeout(function() {
            $('#copy-crunchy-box').text(originalText).css('background', '');
        }, 2000);
    });
    
    // Clear button
    $('#clear-sql').click(function() {
        $('#sql-command-pasted').val('');
        $('#sql-command-final').val('');
        $('#sql-results').hide();
        $('#confirm-execution').prop('checked', false).trigger('change');
    });
    
    // Form submission
    $('#sql-executor-form').submit(function(e) {
        e.preventDefault();
        
        if (!$('#confirm-execution').is(':checked')) {
            alert('Please confirm that you understand the risks before executing SQL.');
            return;
        }
        
        var sql = $('#sql-command-final').val().trim();
        if (!sql) {
            alert('Please enter a SQL command.');
            return;
        }
        
        var autoConvert = $('#auto-convert-prefix').is(':checked');
        
        // Show loading
        $('#sql-loading').show();
        $('#sql-results').hide();
        $('#execute-sql').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'axiom_execute_sql',
                nonce: '<?php echo wp_create_nonce('axiom_nonce'); ?>',
                sql: sql,
                auto_convert: autoConvert
            },
            success: function(response) {
                $('#sql-loading').hide();
                $('#execute-sql').prop('disabled', false);
                
                if (response.success) {
                    var message = typeof response.data === 'string' ? response.data : response.data.message;
                    var hasErrors = response.data.has_errors || false;
                    
                    $('#sql-output').html(message);
                    
                    // Set border color based on whether there were errors
                    if (hasErrors) {
                        $('#sql-results').css('border-color', '#ffc107').show(); // Warning yellow for mixed results
                    } else {
                        $('#sql-results').css('border-color', '#28a745').show(); // Success green
                    }
                    
                    // Populate crunchy_box with detailed results
                    if (response.data.results) {
                        $('#crunchy-box').val(response.data.results);
                    } else {
                        $('#crunchy-box').val('No detailed results available (this was likely a simple modification command).');
                    }
                } else {
                    $('#sql-output').html('❌ ' + response.data);
                    $('#sql-results').css('border-color', '#dc3545').show();
                    
                    // Even on error, try to show any partial results that might be available
                    $('#crunchy-box').val('Some statements failed. Check execution results above for details.');
                }
                
                // Refresh the page after 5 seconds to update recent operations
                setTimeout(function() {
                    location.reload();
                }, 5000);
            },
            error: function() {
                $('#sql-loading').hide();
                $('#execute-sql').prop('disabled', false);
                $('#sql-output').html('❌ AJAX ERROR: Failed to communicate with server.');
                $('#sql-results').css('border-color', '#dc3545').show();
                $('#crunchy-box').val('AJAX communication error - no results available.');
            }
        });
    });
});
</script>

<style>
.quick-sql {
    display: block !important;
    width: 100%;
    margin-bottom: 5px;
    text-align: left;
}

#sql-command-pasted {
    border: 2px solid #ddd !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#sql-command-pasted:focus {
    border-color: #0073aa !important;
    box-shadow: 0 0 0 1px #0073aa;
}

#sql-command-final {
    border: 2px solid #e6f3ff !important;
    background: #f8f9fa !important;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

#sql-command-final:focus {
    border-color: #007cba !important;
    box-shadow: 0 0 0 1px #007cba;
}

#execute-sql {
    font-weight: bold;
    padding: 8px 20px;
}

.wrap h1 {
    color: #d63384;
    font-weight: bold;
}
</style>