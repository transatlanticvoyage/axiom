<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Schema Health Check</h1>
    
    <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
        
        <h2>Health Report</h2>
        <p><strong>Last Updated:</strong> <?php echo esc_html($health_report['timestamp']); ?></p>
        
        <h3>Plugin Status</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Installed</th>
                    <th>Active</th>
                    <th>Version</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($health_report['plugins'] as $slug => $plugin): ?>
                <tr>
                    <td><?php echo esc_html(ucfirst($slug)); ?></td>
                    <td><?php echo $plugin['installed'] ? '✅' : '❌'; ?></td>
                    <td><?php echo $plugin['active'] ? '✅' : '❌'; ?></td>
                    <td><?php echo $plugin['installed'] ? esc_html($plugin['info']['Version']) : 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Schema Status</h3>
        <ul>
            <li><strong>Shared Schema Available:</strong> <?php echo $health_report['schema']['shared_schema_available'] ? '✅ Yes' : '❌ No'; ?></li>
            <li><strong>Overall Health:</strong> 
                <span style="color: <?php echo $health_report['schema']['overall_health'] === 'good' ? '#00a32a' : '#d63638'; ?>;">
                    <?php echo ucfirst(str_replace('_', ' ', $health_report['schema']['overall_health'])); ?>
                </span>
            </li>
        </ul>
        
        <?php if (!empty($health_report['schema']['missing_core_tables'])): ?>
        <h4 style="color: #d63638;">Missing Core Tables</h4>
        <ul>
            <?php foreach ($health_report['schema']['missing_core_tables'] as $table): ?>
            <li><code><?php echo esc_html($table); ?></code></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        
        <h3>Plugin Tables</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Exists</th>
                    <th>Required By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($health_report['schema']['plugin_tables'] as $table => $info): ?>
                <tr>
                    <td><code><?php echo esc_html($table); ?></code></td>
                    <td><?php echo $info['exists'] ? '✅ Yes' : '❌ No'; ?></td>
                    <td><?php echo esc_html($info['required_by']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (!empty($health_report['inconsistencies'])): ?>
        <h3 style="color: #d63638;">Inconsistencies Found</h3>
        
        <?php if (isset($health_report['inconsistencies']['zen_plugins_oasis_missing'])): ?>
        <div style="background: #fff2cd; border: 1px solid #dba617; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; color: #8a6914;">⚠️ zen_plugins_oasis Table Missing</h4>
            <p>The zen_plugins_oasis table is missing. This table is required for plugin management features.</p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($health_report['inconsistencies']['missing_from_registry'])): ?>
        <div style="background: #fff2cd; border: 1px solid #dba617; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; color: #8a6914;">⚠️ Plugins Missing from Registry</h4>
            <p>These plugins are not registered in the zen_plugins_oasis table:</p>
            <ul>
                <?php foreach ($health_report['inconsistencies']['missing_from_registry'] as $plugin): ?>
                <li><strong><?php echo esc_html($plugin); ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <?php if (!empty($health_report['recommendations'])): ?>
        <h3>Recommendations</h3>
        <?php foreach ($health_report['recommendations'] as $rec): ?>
        <div style="background: <?php echo $rec['type'] === 'critical' ? '#fbeaea' : ($rec['type'] === 'warning' ? '#fff2cd' : '#e7f7d3'); ?>; 
                    border: 1px solid <?php echo $rec['type'] === 'critical' ? '#d63638' : ($rec['type'] === 'warning' ? '#dba617' : '#68de7c'); ?>; 
                    padding: 15px; margin: 10px 0; border-radius: 4px;">
            <p style="margin: 0;"><strong><?php echo ucfirst($rec['type']); ?>:</strong> <?php echo esc_html($rec['message']); ?></p>
            <?php if ($rec['action'] === 'refresh_registry'): ?>
            <button type="button" class="button button-primary" style="margin-top: 10px;" onclick="refreshRegistry()">
                Fix: Refresh Plugin Registry
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        
    </div>
</div>

<script>
function refreshRegistry() {
    if (confirm('This will refresh the plugin registry. Continue?')) {
        jQuery.post(axiom_ajax.ajax_url, {
            action: 'axiom_refresh_registry',
            nonce: axiom_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('Registry refreshed successfully!');
                location.reload();
            } else {
                alert('Failed to refresh registry: ' + response.data);
            }
        });
    }
}
</script>