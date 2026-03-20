<?php
/**
 * Page Renderer for DB Field Discrepancy Checker
 * 
 * @package Axiom
 * @subpackage DbFieldDiscrepancyChecker
 */

if (!defined('ABSPATH')) {
    exit;
}

class Axiom_DB_Field_Discrepancy_Page_Renderer {
    
    /**
     * Render the admin page
     */
    public function render() {
        // Load SQL extractor and column analyzer
        require_once plugin_dir_path(__FILE__) . 'class-sql-extractor.php';
        require_once plugin_dir_path(__FILE__) . 'class-db-column-analyzer.php';
        ?>
        <div class="wrap">
            
            <!-- Page Header with Axiom Branding -->
            <h1 style="display: flex; align-items: center; gap: 14px; margin: 0 0 20px 0;">
                <?php echo $this->get_axiom_svg(); ?>
                DB Field Discrepancy Checker
            </h1>
            
            <!-- Main Content Container -->
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
                
                <!-- Status Bar -->
                <div style="background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="font-size: 14px; color: #50575e;">
                            <strong>Status:</strong> Ready
                        </div>
                        <div style="font-size: 14px; color: #50575e;">
                            <strong>Database:</strong> <?php echo esc_html(DB_NAME); ?>
                        </div>
                        <div style="font-size: 14px; color: #50575e;">
                            <strong>Table Prefix:</strong> <?php echo esc_html($GLOBALS['wpdb']->prefix); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Section -->
                <div style="min-height: 400px; padding: 30px;">
                    
                    <!-- Table Title -->
                    <h2 style="font-size: 20px; margin: 0 0 20px 0; color: #333;">Database Table Reference</h2>
                    
                    <!-- Database Tables Comparison -->
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc; background: white;">
                        <thead>
                            <tr style="background: #f0f0f1;">
                                <th style="border: 1px solid #ccc; padding: 12px; text-align: left; font-weight: 600;">
                                    DB Column Reference Name
                                </th>
                                <th style="border: 1px solid #ccc; padding: 12px; text-align: left; font-weight: 600;">
                                    DB Column Actual Name
                                </th>
                                <th style="border: 1px solid #ccc; padding: 12px; text-align: left; font-weight: 600;">
                                    Definition Location
                                </th>
                                <th style="border: 1px solid #ccc; padding: 12px; text-align: left; font-weight: 600;">
                                    Copy SQL to Create All Columns
                                </th>
                                <th style="border: 1px solid #ccc; padding: 12px; text-align: left; font-weight: 600;">
                                    view in splasher
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            global $wpdb;
                            $prefix = $wpdb->prefix;
                            
                            // Define the tables to check
                            $tables_to_check = array(
                                array(
                                    'reference' => 'wp_pylons',
                                    'actual' => $prefix . 'pylons',
                                    'plugin' => 'Ruplin',
                                    'file' => 'ruplin/ruplin.php',
                                    'description' => 'Table for page content blocks (pylons). Defined in the activation hook.',
                                    'function' => 'ruplin_activation()',
                                    'table_name' => 'pylons'
                                ),
                                array(
                                    'reference' => 'wp_zen_sitespren',
                                    'actual' => $prefix . 'zen_sitespren',
                                    'plugin' => 'Ruplin',
                                    'file' => 'ruplin/ruplin.php',
                                    'description' => 'Mirrors Supabase sitespren structure. Defined in the activation hook.',
                                    'function' => 'ruplin_activation()',
                                    'table_name' => 'zen_sitespren'
                                )
                            );
                            
                            foreach ($tables_to_check as $table_info):
                                // Check if table actually exists
                                $table_exists = $wpdb->get_var($wpdb->prepare(
                                    "SHOW TABLES LIKE %s",
                                    $table_info['actual']
                                ));
                                
                                $status_color = $table_exists ? '#00a32a' : '#d63638';
                                $status_text = $table_exists ? '✓' : '✗';
                            ?>
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 10px; font-family: monospace; font-size: 14px;">
                                    <strong><?php echo esc_html($table_info['reference']); ?></strong>
                                </td>
                                <td style="border: 1px solid #ccc; padding: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-family: monospace; font-size: 14px; color: #333;">
                                            <?php echo esc_html($table_info['actual']); ?>
                                        </span>
                                        <span style="color: <?php echo $status_color; ?>; font-weight: bold;" title="<?php echo $table_exists ? 'Table exists' : 'Table not found'; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="border: 1px solid #ccc; padding: 10px; font-size: 13px; color: #555;">
                                    <div style="margin-bottom: 5px;">
                                        <strong>Plugin:</strong> <?php echo esc_html($table_info['plugin']); ?>
                                    </div>
                                    <div style="margin-bottom: 5px;">
                                        <strong>File:</strong> <code style="background: #f0f0f1; padding: 2px 4px; border-radius: 3px;"><?php echo esc_html($table_info['file']); ?></code>
                                    </div>
                                    <div style="margin-bottom: 5px;">
                                        <strong>Function:</strong> <code style="background: #f0f0f1; padding: 2px 4px; border-radius: 3px;"><?php echo esc_html($table_info['function']); ?></code>
                                    </div>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        <?php echo esc_html($table_info['description']); ?>
                                    </div>
                                </td>
                                <td style="border: 1px solid #ccc; padding: 10px; text-align: center;">
                                    <?php
                                    // Get the CREATE TABLE SQL dynamically
                                    $create_sql = '';
                                    if ($table_info['table_name'] === 'pylons') {
                                        $create_sql = Axiom_DB_SQL_Extractor::get_pylons_create_sql();
                                    } elseif ($table_info['table_name'] === 'zen_sitespren') {
                                        $create_sql = Axiom_DB_SQL_Extractor::get_zen_sitespren_create_sql();
                                    }
                                    
                                    if ($create_sql):
                                    ?>
                                    <button type="button" 
                                            class="copy-sql-btn" 
                                            data-table="<?php echo esc_attr($table_info['table_name']); ?>"
                                            data-sql="<?php echo esc_attr($create_sql); ?>"
                                            style="padding: 8px 16px; background: #2271b1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                                        📋 Copy SQL
                                    </button>
                                    <div class="copy-status" id="copy-status-<?php echo esc_attr($table_info['table_name']); ?>" 
                                         style="margin-top: 5px; font-size: 12px; color: #00a32a; display: none;">✓ Copied!</div>
                                    <?php else: ?>
                                    <span style="color: #666; font-size: 13px;">SQL not available</span>
                                    <?php endif; ?>
                                </td>
                                <td style="border: 1px solid #ccc; padding: 10px; text-align: center;">
                                    <button type="button"
                                            class="view-splasher-btn"
                                            data-table="<?php echo esc_attr($table_info['table_name']); ?>"
                                            data-actual-table="<?php echo esc_attr($table_info['actual']); ?>"
                                            style="padding: 8px 16px; background: #7C3AED; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                                        👁️ View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Additional Information -->
                    <div style="margin-top: 30px; padding: 20px; background: #f6f7f7; border: 1px solid #ddd; border-radius: 4px;">
                        <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #333;">Information</h3>
                        <ul style="margin: 10px 0; padding-left: 20px; color: #666;">
                            <li>Current database prefix: <strong><?php echo esc_html($prefix); ?></strong></li>
                            <li>Both tables are defined in the <strong>Ruplin</strong> plugin during activation</li>
                            <li>The <strong>wp_pylons</strong> table stores page content blocks</li>
                            <li>The <strong>wp_zen_sitespren</strong> table mirrors the Supabase sitespren structure</li>
                            <li>Green checkmark (✓) indicates the table exists in the database</li>
                            <li>Red X (✗) indicates the table is missing from the database</li>
                        </ul>
                    </div>
                    
                    <!-- Three-Pane Splasher Interface -->
                    <div id="splasher-interface" style="margin-top: 30px; display: none;">
                        <h2 style="font-size: 20px; margin: 0 0 20px 0; color: #333;">Column Analysis Splasher</h2>
                        
                        <!-- Current Table Being Analyzed -->
                        <div style="background: #f6f7f7; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                            <strong>Currently Analyzing:</strong> <span id="current-analyzing-table" style="font-family: monospace; font-size: 14px;">-</span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                            <!-- Pane 1: Source Definition -->
                            <div style="background: white; border: 1px solid #ccc; border-radius: 4px;">
                                <div style="background: #f0f0f1; padding: 12px; border-bottom: 1px solid #ccc;">
                                    <div style="font-size: 16px; font-weight: bold;">source definition of db columns from plugin</div>
                                </div>
                                <div style="padding: 12px;">
                                    <textarea id="source-definition-box" 
                                              readonly
                                              style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; border: 1px solid #ddd; border-radius: 4px; padding: 8px; resize: vertical; background: #f9f9f9;"
                                              placeholder="Click 'view in splasher' to load source definition..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Pane 2: Current DB Columns -->
                            <div style="background: white; border: 1px solid #ccc; border-radius: 4px;">
                                <div style="background: #f0f0f1; padding: 12px; border-bottom: 1px solid #ccc;">
                                    <div style="font-size: 16px; font-weight: bold;">current db columns that exist in the db table on this specific site</div>
                                </div>
                                <div style="padding: 12px;">
                                    <textarea id="current-columns-box" 
                                              readonly
                                              style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; border: 1px solid #ddd; border-radius: 4px; padding: 8px; resize: vertical; background: #f9f9f9;"
                                              placeholder="Click 'view in splasher' to load current columns..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Pane 3: Columns to Add -->
                            <div style="background: white; border: 1px solid #ccc; border-radius: 4px;">
                                <div style="background: #f0f0f1; padding: 12px; border-bottom: 1px solid #ccc;">
                                    <div style="font-size: 16px; font-weight: bold;">db columns that must be added</div>
                                </div>
                                <div style="padding: 12px;">
                                    <textarea id="columns-to-add-box" 
                                              readonly
                                              style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; border: 1px solid #ddd; border-radius: 4px; padding: 8px; resize: vertical; background: #f9f9f9;"
                                              placeholder="Click 'view in splasher' to analyze discrepancies..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons for Splasher -->
                        <div style="margin-top: 20px; text-align: center;">
                            <button type="button" 
                                    id="copy-alter-statements" 
                                    style="padding: 10px 20px; background: #00a32a; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-right: 10px;">
                                📋 Copy ALTER Statements
                            </button>
                            <button type="button" 
                                    id="refresh-splasher" 
                                    style="padding: 10px 20px; background: #2271b1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-right: 10px;">
                                🔄 Refresh Analysis
                            </button>
                            <button type="button" 
                                    id="close-splasher" 
                                    style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                                ✖ Close Splasher
                            </button>
                        </div>
                    </div>
                    
                </div>
                
            </div>
            
            <!-- Footer Information -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <p style="color: #666; font-size: 13px;">
                    Axiom Plugin • DB Field Discrepancy Checker • Version 1.0.0
                </p>
            </div>
            
        </div>
        
        <style>
            /* Page-specific styles */
            .wrap {
                margin: 10px 20px 0 2px;
            }
            
            /* Ensure our content isn't affected by any theme styles */
            .wrap h1 {
                font-size: 23px;
                font-weight: 400;
                line-height: 1.3;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            console.log('DB Field Discrepancy Checker page loaded');
            
            // Copy SQL to clipboard functionality
            $('.copy-sql-btn').on('click', function() {
                const $button = $(this);
                const sql = $button.data('sql');
                const tableName = $button.data('table');
                const $status = $('#copy-status-' + tableName);
                
                // Create a temporary textarea to copy the SQL
                const $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(sql).select();
                
                try {
                    // Try modern clipboard API first
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(sql).then(function() {
                            showCopySuccess($button, $status);
                        }).catch(function() {
                            // Fallback to document.execCommand
                            fallbackCopy($temp, $button, $status);
                        });
                    } else {
                        // Use fallback for older browsers
                        fallbackCopy($temp, $button, $status);
                    }
                } catch(err) {
                    console.error('Copy failed:', err);
                    alert('Failed to copy SQL. Please try selecting and copying manually.');
                } finally {
                    $temp.remove();
                }
            });
            
            function fallbackCopy($temp, $button, $status) {
                try {
                    document.execCommand('copy');
                    showCopySuccess($button, $status);
                } catch(err) {
                    console.error('Fallback copy failed:', err);
                    alert('Failed to copy SQL. Please try selecting and copying manually.');
                }
            }
            
            function showCopySuccess($button, $status) {
                // Change button appearance
                const originalText = $button.html();
                const originalBg = $button.css('background');
                
                $button.html('✓ Copied!').css('background', '#00a32a');
                $status.fadeIn(200);
                
                // Reset after 2 seconds
                setTimeout(function() {
                    $button.html(originalText).css('background', originalBg);
                    $status.fadeOut(200);
                }, 2000);
            }
            
            // Add hover effect to copy buttons
            $('.copy-sql-btn').hover(
                function() {
                    $(this).css({
                        'background': '#135e96',
                        'transform': 'translateY(-1px)',
                        'box-shadow': '0 2px 4px rgba(0,0,0,0.2)'
                    });
                },
                function() {
                    if (!$(this).text().includes('Copied')) {
                        $(this).css({
                            'background': '#2271b1',
                            'transform': 'translateY(0)',
                            'box-shadow': 'none'
                        });
                    }
                }
            );
            
            // View in Splasher functionality
            let currentTableData = {};
            
            $('.view-splasher-btn').on('click', function() {
                const $button = $(this);
                const tableName = $button.data('table');
                const actualTableName = $button.data('actual-table');
                
                // Show the splasher interface
                $('#splasher-interface').slideDown();
                
                // Update current table being analyzed
                $('#current-analyzing-table').text(actualTableName);
                
                // Scroll to splasher
                $('html, body').animate({
                    scrollTop: $('#splasher-interface').offset().top - 50
                }, 500);
                
                // Get source SQL from the copy button in the same row
                const $row = $button.closest('tr');
                const $copyBtn = $row.find('.copy-sql-btn');
                const sourceSQL = $copyBtn.data('sql');
                
                // Populate source definition pane
                $('#source-definition-box').val(sourceSQL);
                
                // Load current DB columns via AJAX
                loadCurrentDBColumns(actualTableName, sourceSQL);
            });
            
            function loadCurrentDBColumns(tableName, sourceSQL) {
                // For now, we'll use PHP to generate the data
                // In a full implementation, this would be an AJAX call
                
                <?php
                // Generate JavaScript data for each table
                global $wpdb;
                $tables = array(
                    'pylons' => $wpdb->prefix . 'pylons',
                    'zen_sitespren' => $wpdb->prefix . 'zen_sitespren'
                );
                
                foreach ($tables as $key => $full_table_name):
                    $actual_columns = Axiom_DB_Column_Analyzer::get_actual_db_columns($full_table_name);
                    $formatted_actual = Axiom_DB_Column_Analyzer::format_columns_display($actual_columns);
                ?>
                if (tableName === '<?php echo esc_js($full_table_name); ?>') {
                    // Current DB columns
                    const currentColumns = <?php echo json_encode($formatted_actual); ?>;
                    $('#current-columns-box').val(currentColumns || '-- Table not found in database');
                    
                    // Calculate discrepancies
                    <?php
                    $create_sql = '';
                    if ($key === 'pylons') {
                        $create_sql = Axiom_DB_SQL_Extractor::get_pylons_create_sql();
                    } else {
                        $create_sql = Axiom_DB_SQL_Extractor::get_zen_sitespren_create_sql();
                    }
                    
                    $source_columns = Axiom_DB_Column_Analyzer::get_columns_from_sql($create_sql);
                    $missing_columns = Axiom_DB_Column_Analyzer::get_missing_columns($source_columns, $actual_columns);
                    $alter_statements = Axiom_DB_Column_Analyzer::generate_alter_statements($full_table_name, $missing_columns);
                    ?>
                    
                    const alterStatements = <?php echo json_encode($alter_statements); ?>;
                    $('#columns-to-add-box').val(alterStatements);
                    
                    // Store for copy functionality
                    currentTableData = {
                        tableName: tableName,
                        alterStatements: alterStatements
                    };
                }
                <?php endforeach; ?>
            }
            
            // Copy ALTER statements
            $('#copy-alter-statements').on('click', function() {
                const alterText = $('#columns-to-add-box').val();
                
                if (!alterText || alterText.includes('No missing columns')) {
                    alert('No ALTER statements to copy.');
                    return;
                }
                
                // Create temporary textarea and copy
                const $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(alterText).select();
                
                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(alterText).then(function() {
                            showAlterCopySuccess();
                        }).catch(function() {
                            document.execCommand('copy');
                            showAlterCopySuccess();
                        });
                    } else {
                        document.execCommand('copy');
                        showAlterCopySuccess();
                    }
                } catch(err) {
                    alert('Failed to copy ALTER statements.');
                } finally {
                    $temp.remove();
                }
            });
            
            function showAlterCopySuccess() {
                const $btn = $('#copy-alter-statements');
                const originalText = $btn.html();
                const originalBg = $btn.css('background');
                
                $btn.html('✓ Copied!').css('background', '#0f5132');
                
                setTimeout(function() {
                    $btn.html(originalText).css('background', originalBg);
                }, 2000);
            }
            
            // Refresh splasher
            $('#refresh-splasher').on('click', function() {
                const tableName = $('#current-analyzing-table').text();
                if (tableName && tableName !== '-') {
                    // Find and click the corresponding view button
                    $('.view-splasher-btn[data-actual-table="' + tableName + '"]').click();
                }
            });
            
            // Close splasher
            $('#close-splasher').on('click', function() {
                $('#splasher-interface').slideUp();
                // Clear the textboxes
                $('#source-definition-box, #current-columns-box, #columns-to-add-box').val('');
                $('#current-analyzing-table').text('-');
            });
            
            // Add hover effect to view splasher buttons
            $('.view-splasher-btn').hover(
                function() {
                    $(this).css({
                        'background': '#6D28D9',
                        'transform': 'translateY(-1px)',
                        'box-shadow': '0 2px 4px rgba(0,0,0,0.2)'
                    });
                },
                function() {
                    $(this).css({
                        'background': '#7C3AED',
                        'transform': 'translateY(0)',
                        'box-shadow': 'none'
                    });
                }
            );
        });
        </script>
        <?php
    }
    
    /**
     * Get Axiom SVG icon (lightning bolt)
     */
    private function get_axiom_svg() {
        return '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" style="flex-shrink: 0;">
            <!-- Lightning bolt -->
            <path d="M16 4L10 16H14L13 26L20 14H16L18 4H16Z" fill="#FFC107" stroke="#FF9800" stroke-width="1.5"/>
            <!-- Glow effect -->
            <circle cx="15" cy="15" r="13" stroke="#FFC107" stroke-width="0.5" opacity="0.3"/>
        </svg>';
    }
}