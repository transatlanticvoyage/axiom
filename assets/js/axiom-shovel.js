jQuery(document).ready(function($) {
    
    loadTables();
    
    function loadTables() {
        $('#tables-loading').show();
        $('#tables-list').empty();
        
        $.post(axiom_ajax.ajax_url, {
            action: 'axiom_get_tables',
            nonce: axiom_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                axiomShovel.allTables = response.data;
                renderTables(response.data);
            } else {
                showError('Failed to load tables: ' + response.data);
            }
        })
        .fail(function() {
            showError('Failed to communicate with server');
        })
        .always(function() {
            $('#tables-loading').hide();
        });
    }
    
    function renderTables(tables) {
        const $list = $('#tables-list');
        $list.empty();
        
        if (tables.length === 0) {
            $list.html('<p style="padding: 20px; text-align: center; color: #666;">No tables found</p>');
            return;
        }
        
        const grouped = {};
        tables.forEach(function(table) {
            const prefix = table.prefix || 'other';
            if (!grouped[prefix]) {
                grouped[prefix] = [];
            }
            grouped[prefix].push(table);
        });
        
        const sortedPrefixes = Object.keys(grouped).sort();
        
        sortedPrefixes.forEach(function(prefix) {
            const $group = $('<div class="shovel-table-group"></div>');
            const displayPrefix = prefix || 'No Prefix';
            $group.append('<div class="shovel-table-group-header">' + escapeHtml(displayPrefix) + '</div>');
            
            grouped[prefix].forEach(function(table) {
                const $item = $('<div class="shovel-table-item"></div>');
                $item.attr('data-table', table.name);
                
                const $name = $('<span class="shovel-table-name"></span>').text(table.name);
                const $rows = $('<span class="shovel-table-rows"></span>').text(table.rows + ' rows');
                
                $item.append($name).append($rows);
                $item.on('click', function() {
                    selectTable(table.name);
                });
                
                $group.append($item);
            });
            
            $list.append($group);
        });
    }
    
    $('#table-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm === '') {
            renderTables(axiomShovel.allTables);
            return;
        }
        
        const filtered = axiomShovel.allTables.filter(function(table) {
            return table.name.toLowerCase().indexOf(searchTerm) !== -1;
        });
        
        renderTables(filtered);
    });
    
    function selectTable(tableName) {
        axiomShovel.currentTable = tableName;
        axiomShovel.currentPage = 1;
        axiomShovel.sortColumn = null;
        axiomShovel.sortOrder = 'ASC';
        axiomShovel.searchQuery = null;
        
        $('.shovel-table-item').removeClass('active');
        $('.shovel-table-item[data-table="' + tableName + '"]').addClass('active');
        
        $('#welcome-message').hide();
        $('#table-viewer').show();
        $('#current-table-name').text(tableName);
        $('#data-search').val('');
        $('#btn-clear-search').hide();
        
        loadTableData();
    }
    
    function loadTableData() {
        $('#data-loading').show();
        $('#table-data-container').hide();
        
        const data = {
            action: 'axiom_get_table_data',
            nonce: axiom_ajax.nonce,
            table: axiomShovel.currentTable,
            page: axiomShovel.currentPage,
            limit: axiomShovel.rowsPerPage
        };
        
        if (axiomShovel.sortColumn) {
            data.orderby = axiomShovel.sortColumn;
            data.order = axiomShovel.sortOrder;
        }
        
        $.post(axiom_ajax.ajax_url, data)
        .done(function(response) {
            if (response.success) {
                axiomShovel.totalPages = response.data.total_pages;
                axiomShovel.totalRows = response.data.total_rows;
                renderTableData(response.data.data);
                updatePagination();
            } else {
                showError('Failed to load table data: ' + response.data);
            }
        })
        .fail(function() {
            showError('Failed to communicate with server');
        })
        .always(function() {
            $('#data-loading').hide();
            $('#table-data-container').show();
        });
    }
    
    function renderTableData(data) {
        const $head = $('#data-table-head');
        const $body = $('#data-table-body');
        
        $head.empty();
        $body.empty();
        
        if (data.length === 0) {
            $body.html('<tr><td colspan="100" style="text-align: center; padding: 40px; color: #666;">No data found</td></tr>');
            $('#current-table-count').text('0 rows');
            return;
        }
        
        $('#current-table-count').text(axiomShovel.totalRows + ' rows');
        
        const columns = Object.keys(data[0]);
        const $headerRow = $('<tr></tr>');
        
        columns.forEach(function(column) {
            const $th = $('<th class="sortable"></th>').text(column);
            $th.attr('data-column', column);
            
            if (axiomShovel.sortColumn === column) {
                if (axiomShovel.sortOrder === 'ASC') {
                    $th.addClass('sorted-asc');
                } else {
                    $th.addClass('sorted-desc');
                }
            }
            
            $th.on('click', function() {
                const col = $(this).attr('data-column');
                
                if (axiomShovel.sortColumn === col) {
                    axiomShovel.sortOrder = axiomShovel.sortOrder === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    axiomShovel.sortColumn = col;
                    axiomShovel.sortOrder = 'ASC';
                }
                
                axiomShovel.currentPage = 1;
                loadTableData();
            });
            
            $headerRow.append($th);
        });
        
        $head.append($headerRow);
        
        data.forEach(function(row) {
            const $tr = $('<tr></tr>');
            
            columns.forEach(function(column) {
                const value = row[column];
                const $td = $('<td></td>');
                
                if (value === null) {
                    $td.text('NULL').addClass('data-type-null');
                } else if (typeof value === 'number' || !isNaN(value)) {
                    $td.text(value).addClass('data-type-number');
                } else {
                    $td.text(value).addClass('data-type-text');
                }
                
                $td.attr('title', value);
                $tr.append($td);
            });
            
            $body.append($tr);
        });
    }
    
    function updatePagination() {
        $('#pagination-info').text('Showing ' + ((axiomShovel.currentPage - 1) * axiomShovel.rowsPerPage + 1) + ' to ' + Math.min(axiomShovel.currentPage * axiomShovel.rowsPerPage, axiomShovel.totalRows) + ' of ' + axiomShovel.totalRows + ' rows');
        
        $('#page-indicator').text('Page ' + axiomShovel.currentPage + ' of ' + axiomShovel.totalPages);
        
        $('#btn-first-page, #btn-prev-page').prop('disabled', axiomShovel.currentPage === 1);
        $('#btn-next-page, #btn-last-page').prop('disabled', axiomShovel.currentPage === axiomShovel.totalPages);
    }
    
    $('#btn-first-page').on('click', function() {
        axiomShovel.currentPage = 1;
        if (axiomShovel.searchQuery) {
            searchTableData();
        } else {
            loadTableData();
        }
    });
    
    $('#btn-prev-page').on('click', function() {
        if (axiomShovel.currentPage > 1) {
            axiomShovel.currentPage--;
            if (axiomShovel.searchQuery) {
                searchTableData();
            } else {
                loadTableData();
            }
        }
    });
    
    $('#btn-next-page').on('click', function() {
        if (axiomShovel.currentPage < axiomShovel.totalPages) {
            axiomShovel.currentPage++;
            if (axiomShovel.searchQuery) {
                searchTableData();
            } else {
                loadTableData();
            }
        }
    });
    
    $('#btn-last-page').on('click', function() {
        axiomShovel.currentPage = axiomShovel.totalPages;
        if (axiomShovel.searchQuery) {
            searchTableData();
        } else {
            loadTableData();
        }
    });
    
    $('#rows-per-page').on('change', function() {
        axiomShovel.rowsPerPage = parseInt($(this).val());
        axiomShovel.currentPage = 1;
        if (axiomShovel.searchQuery) {
            searchTableData();
        } else {
            loadTableData();
        }
    });
    
    $('#btn-refresh-data').on('click', function() {
        if (axiomShovel.searchQuery) {
            searchTableData();
        } else {
            loadTableData();
        }
    });
    
    $('#btn-view-structure').on('click', function() {
        loadTableStructure();
    });
    
    function loadTableStructure() {
        $('#structure-modal-title').text('Table Structure: ' + axiomShovel.currentTable);
        $('#structure-table-body').html('<tr><td colspan="6" style="text-align: center; padding: 20px;">⏳ Loading...</td></tr>');
        $('#structure-modal').show();
        
        $.post(axiom_ajax.ajax_url, {
            action: 'axiom_get_table_structure',
            nonce: axiom_ajax.nonce,
            table: axiomShovel.currentTable
        })
        .done(function(response) {
            if (response.success) {
                renderTableStructure(response.data);
            } else {
                showError('Failed to load table structure: ' + response.data);
                $('#structure-modal').hide();
            }
        })
        .fail(function() {
            showError('Failed to communicate with server');
            $('#structure-modal').hide();
        });
    }
    
    function renderTableStructure(structure) {
        const $tbody = $('#structure-table-body');
        $tbody.empty();
        
        structure.forEach(function(column) {
            const $tr = $('<tr></tr>');
            
            $tr.append('<td>' + escapeHtml(column.Field) + '</td>');
            $tr.append('<td>' + escapeHtml(column.Type) + '</td>');
            $tr.append('<td>' + escapeHtml(column.Null) + '</td>');
            
            const $keyTd = $('<td></td>');
            if (column.Key === 'PRI') {
                $keyTd.html('<span class="key-pri">PRIMARY</span>');
            } else if (column.Key === 'UNI') {
                $keyTd.html('<span class="key-uni">UNIQUE</span>');
            } else if (column.Key === 'MUL') {
                $keyTd.html('<span class="key-mul">INDEX</span>');
            } else {
                $keyTd.text(column.Key);
            }
            $tr.append($keyTd);
            
            $tr.append('<td>' + escapeHtml(column.Default) + '</td>');
            $tr.append('<td>' + escapeHtml(column.Extra) + '</td>');
            
            $tbody.append($tr);
        });
    }
    
    $('#close-structure-modal, #close-structure-modal-btn').on('click', function() {
        $('#structure-modal').hide();
    });
    
    $('#structure-modal').on('click', function(e) {
        if ($(e.target).is('#structure-modal')) {
            $('#structure-modal').hide();
        }
    });
    
    $('#btn-search-data').on('click', function() {
        const searchQuery = $('#data-search').val().trim();
        
        if (searchQuery === '') {
            alert('Please enter a search term');
            return;
        }
        
        axiomShovel.searchQuery = searchQuery;
        axiomShovel.currentPage = 1;
        searchTableData();
    });
    
    $('#data-search').on('keypress', function(e) {
        if (e.which === 13) {
            $('#btn-search-data').click();
        }
    });
    
    $('#btn-clear-search').on('click', function() {
        $('#data-search').val('');
        axiomShovel.searchQuery = null;
        axiomShovel.currentPage = 1;
        $(this).hide();
        loadTableData();
    });
    
    function searchTableData() {
        $('#data-loading').show();
        $('#table-data-container').hide();
        $('#btn-clear-search').show();
        
        $.post(axiom_ajax.ajax_url, {
            action: 'axiom_search_table',
            nonce: axiom_ajax.nonce,
            table: axiomShovel.currentTable,
            search: axiomShovel.searchQuery,
            page: axiomShovel.currentPage,
            limit: axiomShovel.rowsPerPage
        })
        .done(function(response) {
            if (response.success) {
                axiomShovel.totalPages = response.data.total_pages;
                axiomShovel.totalRows = response.data.total_rows;
                renderTableData(response.data.data);
                updatePagination();
            } else {
                showError('Search failed: ' + response.data);
            }
        })
        .fail(function() {
            showError('Failed to communicate with server');
        })
        .always(function() {
            $('#data-loading').hide();
            $('#table-data-container').show();
        });
    }
    
    $('#btn-export-csv').on('click', function() {
        if (!axiomShovel.currentTable) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Exporting...');
        
        $.post(axiom_ajax.ajax_url, {
            action: 'axiom_get_table_data',
            nonce: axiom_ajax.nonce,
            table: axiomShovel.currentTable,
            page: 1,
            limit: 10000
        })
        .done(function(response) {
            if (response.success && response.data.data.length > 0) {
                const data = response.data.data;
                const columns = Object.keys(data[0]);
                
                let csv = columns.join(',') + '\n';
                
                data.forEach(function(row) {
                    const values = columns.map(function(col) {
                        let value = row[col];
                        if (value === null) {
                            return 'NULL';
                        }
                        value = String(value).replace(/"/g, '""');
                        if (value.indexOf(',') !== -1 || value.indexOf('"') !== -1 || value.indexOf('\n') !== -1) {
                            value = '"' + value + '"';
                        }
                        return value;
                    });
                    csv += values.join(',') + '\n';
                });
                
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = axiomShovel.currentTable + '.csv';
                a.click();
                window.URL.revokeObjectURL(url);
                
                showMessage('Exported ' + data.length + ' rows to CSV', 'success');
            } else {
                showError('No data to export');
            }
        })
        .fail(function() {
            showError('Export failed');
        })
        .always(function() {
            $btn.prop('disabled', false).text('📥 Export CSV');
        });
    });
    
    function showError(message) {
        alert('Error: ' + message);
    }
    
    function showMessage(message, type) {
        if (typeof window.showAxiomMessage === 'function') {
            window.showAxiomMessage(message, type);
        } else {
            alert(message);
        }
    }
    
    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});