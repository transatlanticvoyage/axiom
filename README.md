# Axiom - Shenzi Schema Manager

Central database schema management and synchronization tool for the Shenzi plugin ecosystem.

## Features

- **Schema Health Monitoring** - Real-time status of all Shenzi plugin schemas
- **Plugin Registry Management** - Centralized tracking of all Shenzi plugins
- **One-Click Fixes** - Automated resolution of common schema inconsistencies
- **Backwards Compatible** - Works alongside existing plugins without interference
- **Safe Operations** - Backup creation and rollback capabilities

## Installation

1. Upload the `axiom` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin
3. Navigate to **Axiom** in the admin menu

## Key Capabilities

### Fixes the "Veyra Not Showing" Issue
Axiom can detect and fix missing plugin registry entries with the "Refresh Plugin Registry" feature.

### Schema Synchronization
Keep all Shenzi plugins in sync with the latest database schema definitions.

### Health Monitoring
Comprehensive reporting on:
- Plugin installation status
- Database table existence
- Schema version consistency
- Registry completeness

## Admin Interface

- **Dashboard** - Overview of system health and quick actions
- **Health Check** - Detailed schema analysis and recommendations
- **Plugin Sync** - Individual plugin schema synchronization
- **Operations** - History of all schema management activities

## Database Tables

Axiom creates two tracking tables:
- `wp_axiom_operations` - Schema operation history
- `wp_axiom_plugin_versions` - Plugin version and status tracking

## Compatibility

- WordPress 5.0+
- PHP 7.4+
- Works with all existing Shenzi plugins
- Safe to activate/deactivate without data loss

## Support

Part of the Shenzi plugin ecosystem. For issues, check the Axiom admin interface for automated diagnostics and fixes.