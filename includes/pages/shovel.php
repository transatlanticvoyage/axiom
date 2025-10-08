<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap axiom-shovel-wrap">
    <h1 style="display: flex; align-items: center; gap: 14px; margin: 0 0 20px 0;">
        <span style="font-size: 32px;">🗄️</span>
        Axiom Shovel - Database Browser
    </h1>
    
    <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 12px; margin: 15px 0; border-radius: 5px;">
        <p style="color: #0c5460; margin: 0;">
            <strong>Database:</strong> <?php echo esc_html(DB_NAME); ?> | 
            <strong>Prefix:</strong> <code><?php global $wpdb; echo esc_html($wpdb->prefix); ?></code> | 
            <strong>User:</strong> <?php echo esc_html(wp_get_current_user()->user_login); ?>
        </p>
    </div>
    
    <div class="axiom-shovel-container">
        <div class="axiom-shovel-sidebar">
            <div class="shovel-sidebar-header">
                <h3>Database Tables</h3>
                <input 
                    type="text" 
                    id="table-search" 
                    placeholder="Search tables..." 
                    class="shovel-search-input"
                >
            </div>
            
            <div id="tables-loading" class="shovel-loading">
                <span>⏳ Loading tables...</span>
            </div>
            
            <div id="tables-list" class="shovel-tables-list"></div>
        </div>
        
        <div class="axiom-shovel-main">
            <div id="welcome-message" class="shovel-welcome">
                <div style="text-align: center; padding: 60px 20px; color: #666;">
                    <span style="font-size: 64px; display: block; margin-bottom: 20px;">🗄️</span>
                    <h2>Welcome to Axiom Shovel</h2>
                    <p>Select a table from the sidebar to view its contents</p>
                </div>
            </div>
            
            <div id="table-viewer" class="shovel-table-viewer" style="display: none;">
                <div class="shovel-viewer-header">
                    <div class="shovel-viewer-title">
                        <h2 id="current-table-name">Table Name</h2>
                        <span id="current-table-count" class="shovel-row-count"></span>
                    </div>
                    
                    <div class="shovel-viewer-actions">
                        <button type="button" id="btn-view-structure" class="button button-secondary">
                            📋 View Structure
                        </button>
                        <button type="button" id="btn-refresh-data" class="button button-secondary">
                            🔄 Refresh
                        </button>
                        <button type="button" id="btn-export-csv" class="button button-secondary">
                            📥 Export CSV
                        </button>
                    </div>
                </div>
                
                <div class="shovel-search-bar">
                    <input 
                        type="text" 
                        id="data-search" 
                        placeholder="Search in table data..." 
                        class="shovel-search-input"
                    >
                    <button type="button" id="btn-search-data" class="button button-primary">
                        Search
                    </button>
                    <button type="button" id="btn-clear-search" class="button button-secondary" style="display: none;">
                        Clear
                    </button>
                </div>
                
                <div id="data-loading" class="shovel-loading" style="display: none;">
                    <span>⏳ Loading data...</span>
                </div>
                
                <div id="table-data-container" class="shovel-data-container">
                    <div class="shovel-table-scroll">
                        <table id="data-table" class="shovel-data-table">
                            <thead id="data-table-head"></thead>
                            <tbody id="data-table-body"></tbody>
                        </table>
                    </div>
                </div>
                
                <div class="shovel-pagination">
                    <div class="shovel-pagination-info">
                        <span id="pagination-info"></span>
                    </div>
                    
                    <div class="shovel-pagination-controls">
                        <button type="button" id="btn-first-page" class="button button-small">⏮️ First</button>
                        <button type="button" id="btn-prev-page" class="button button-small">◀️ Prev</button>
                        <span id="page-indicator" style="margin: 0 15px; font-weight: bold;"></span>
                        <button type="button" id="btn-next-page" class="button button-small">Next ▶️</button>
                        <button type="button" id="btn-last-page" class="button button-small">Last ⏭️</button>
                    </div>
                    
                    <div class="shovel-pagination-limit">
                        <label>
                            Rows per page:
                            <select id="rows-per-page" class="shovel-select">
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="structure-modal" class="shovel-modal" style="display: none;">
    <div class="shovel-modal-content">
        <div class="shovel-modal-header">
            <h2 id="structure-modal-title">Table Structure</h2>
            <button type="button" class="shovel-modal-close" id="close-structure-modal">&times;</button>
        </div>
        <div class="shovel-modal-body">
            <div class="shovel-table-scroll">
                <table id="structure-table" class="shovel-structure-table">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody id="structure-table-body"></tbody>
                </table>
            </div>
        </div>
        <div class="shovel-modal-footer">
            <button type="button" class="button button-secondary" id="close-structure-modal-btn">Close</button>
        </div>
    </div>
</div>

<script type="text/javascript">
var axiomShovel = {
    currentTable: null,
    currentPage: 1,
    rowsPerPage: 25,
    totalPages: 1,
    totalRows: 0,
    allTables: [],
    sortColumn: null,
    sortOrder: 'ASC',
    searchQuery: null
};
</script>