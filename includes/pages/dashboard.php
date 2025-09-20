<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 style="display: flex; align-items: center; gap: 14px; margin: 0 0 20px 0;">
        <span style="font-size: 32px;">⚙️</span>
        Axiom Schema Dashboard
    </h1>
    
    <!-- Status Overview Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <!-- Overall Health Card -->
        <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">
                <?php echo $schema_status['overall_health'] === 'good' ? '✅' : '⚠️'; ?>
                Schema Health
            </h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: <?php echo $schema_status['overall_health'] === 'good' ? '#00a32a' : '#d63638'; ?>;">
                <?php echo ucfirst(str_replace('_', ' ', $schema_status['overall_health'])); ?>
            </p>
            <?php if (!empty($schema_status['missing_core_tables'])): ?>
                <p style="margin: 10px 0 0 0; color: #d63638; font-size: 14px;">
                    Missing: <?php echo implode(', ', $schema_status['missing_core_tables']); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Shenzi Plugins Card -->
        <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">🔌 Shenzi Plugins</h3>
            <?php 
            $installed_count = count(array_filter($shenzi_plugins, function($p) { return $p['installed']; }));
            $active_count = count(array_filter($shenzi_plugins, function($p) { return $p['active']; }));
            ?>
            <p style="font-size: 18px; margin: 0;">
                <strong><?php echo $active_count; ?></strong> active / 
                <strong><?php echo $installed_count; ?></strong> installed / 
                <strong><?php echo count($shenzi_plugins); ?></strong> total
            </p>
        </div>
        
        <!-- Shared Schema Card -->
        <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">📋 Shared Schema</h3>
            <p style="font-size: 18px; margin: 0; color: <?php echo $schema_status['shared_schema_available'] ? '#00a32a' : '#d63638'; ?>;">
                <?php echo $schema_status['shared_schema_available'] ? '✅ Available' : '❌ Not Found'; ?>
            </p>
        </div>
        
        <!-- Quick Actions Card -->
        <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 15px 0; color: #23282d;">🚀 Quick Actions</h3>
            <button type="button" id="health-check-btn" class="button button-primary" style="margin-bottom: 8px; width: 100%;">
                Run Health Check
            </button>
            <button type="button" id="refresh-registry-btn" class="button button-secondary" style="width: 100%;">
                Refresh Plugin Registry
            </button>
        </div>
        
    </div>
    
    <!-- Shenzi Plugins Table -->
    <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <h2 style="margin: 0 0 20px 0;">Shenzi Plugins Status</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Status</th>
                    <th>Version</th>
                    <th>Path</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shenzi_plugins as $slug => $plugin): ?>
                <tr>
                    <td><strong><?php echo esc_html(ucfirst($slug)); ?></strong></td>
                    <td>
                        <?php if ($plugin['active']): ?>
                            <span style="color: #00a32a;">✅ Active</span>
                        <?php elseif ($plugin['installed']): ?>
                            <span style="color: #dba617;">⚪ Installed</span>
                        <?php else: ?>
                            <span style="color: #d63638;">❌ Not Installed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $plugin['installed'] ? esc_html($plugin['info']['Version']) : 'N/A'; ?>
                    </td>
                    <td>
                        <code><?php echo esc_html($plugin['path']); ?></code>
                    </td>
                    <td>
                        <?php if ($plugin['installed']): ?>
                            <button type="button" class="button button-small sync-plugin-btn" data-plugin="<?php echo esc_attr($slug); ?>">
                                Sync Schema
                            </button>
                        <?php else: ?>
                            <span style="color: #8c8f94;">N/A</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Results/Messages Area -->
    <div id="axiom-messages" style="margin-top: 20px;"></div>
    
</div>

<script>
jQuery(document).ready(function($) {
    
    // Health Check Button
    $('#health-check-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Running...');
        
        $.post(axiom_ajax.ajax_url, {
            action: 'axiom_health_check',
            nonce: axiom_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                showMessage('Health check completed successfully!', 'success');
                // Could update UI with health data
            } else {
                showMessage('Health check failed: ' + response.data, 'error');
            }
        })
        .fail(function() {
            showMessage('Health check request failed', 'error');
        })
        .always(function() {
            $btn.prop('disabled', false).text('Run Health Check');
        });
    });
    
    // Refresh Registry Button
    $('#refresh-registry-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Refreshing...');
        
        $.post(axiom_ajax.ajax_url, {
            action: 'axiom_refresh_registry',
            nonce: axiom_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                showMessage(response.data, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage('Registry refresh failed: ' + response.data, 'error');
            }
        })
        .fail(function() {
            showMessage('Registry refresh request failed', 'error');
        })
        .always(function() {
            $btn.prop('disabled', false).text('Refresh Plugin Registry');
        });
    });
    
    // Sync Plugin Buttons
    $('.sync-plugin-btn').on('click', function() {
        const $btn = $(this);
        const plugin = $btn.data('plugin');
        
        $btn.prop('disabled', true).text('Syncing...');
        
        $.post(axiom_ajax.ajax_url, {
            action: 'axiom_sync_plugin',
            plugin: plugin,
            nonce: axiom_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                showMessage('Plugin ' + plugin + ' synced successfully!', 'success');
            } else {
                showMessage('Sync failed for ' + plugin + ': ' + response.data, 'error');
            }
        })
        .fail(function() {
            showMessage('Sync request failed for ' + plugin, 'error');
        })
        .always(function() {
            $btn.prop('disabled', false).text('Sync Schema');
        });
    });
    
    // Helper function to show messages
    function showMessage(message, type) {
        const alertClass = type === 'success' ? 'notice-success' : 'notice-error';
        const html = '<div class="notice ' + alertClass + ' is-dismissible"><p>' + message + '</p></div>';
        $('#axiom-messages').html(html);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('#axiom-messages .notice').fadeOut();
        }, 5000);
    }
});
</script>