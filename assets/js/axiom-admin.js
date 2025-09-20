/**
 * Axiom Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Global message handler
    window.showAxiomMessage = function(message, type) {
        const alertClass = type === 'success' ? 'notice-success' : 'notice-error';
        const html = '<div class="notice ' + alertClass + ' is-dismissible"><p>' + message + '</p></div>';
        
        let $container = $('#axiom-messages');
        if ($container.length === 0) {
            $container = $('<div id="axiom-messages"></div>').insertAfter('.wrap h1');
        }
        
        $container.html(html);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $container.find('.notice').fadeOut();
        }, 5000);
    };
    
    // AJAX error handler
    $(document).ajaxError(function(event, xhr, settings) {
        if (settings.url.indexOf('admin-ajax.php') !== -1 && settings.data.indexOf('axiom_') !== -1) {
            showAxiomMessage('AJAX request failed. Please try again.', 'error');
        }
    });
    
    // Confirm dangerous actions
    $('.danger-action').on('click', function(e) {
        const action = $(this).data('action') || 'this action';
        if (!confirm('Are you sure you want to ' + action + '? This cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-refresh health status every 5 minutes
    if (window.location.href.indexOf('axiom') !== -1) {
        setInterval(function() {
            // Only refresh if user is active (moved mouse recently)
            if (typeof window.lastActivity !== 'undefined' && Date.now() - window.lastActivity < 300000) {
                // Silent health check without UI updates
                $.post(axiom_ajax.ajax_url, {
                    action: 'axiom_health_check',
                    nonce: axiom_ajax.nonce,
                    silent: true
                });
            }
        }, 300000); // 5 minutes
        
        // Track user activity
        $(document).on('mousemove', function() {
            window.lastActivity = Date.now();
        });
    }
    
});