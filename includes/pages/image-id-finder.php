<?php
/**
 * Axiom Image ID Finder
 * Custom media viewer page with ID copy functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Aggressive warning/notice suppression
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'axiom_image_id_finder') {
        // Remove all admin notices
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');
        
        // Add our custom action to suppress notices
        add_action('admin_notices', function() {
            remove_all_actions('admin_notices');
        }, -9999);
        
        add_action('all_admin_notices', function() {
            remove_all_actions('all_admin_notices');
        }, -9999);
        
        // Also suppress any admin notice hooks that might be added later
        add_action('admin_head', function() {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        });
    }
}, -9999);

// Get all media items from the database
global $wpdb;
$media_items = $wpdb->get_results("
    SELECT 
        ID as post_id,
        post_title,
        post_name,
        post_mime_type,
        post_date,
        post_modified,
        guid,
        post_status,
        post_author
    FROM {$wpdb->posts} 
    WHERE post_type = 'attachment'
    ORDER BY ID DESC
");

// Get author names
$authors = array();
foreach ($media_items as $item) {
    if (!isset($authors[$item->post_author])) {
        $user = get_user_by('id', $item->post_author);
        $authors[$item->post_author] = $user ? $user->display_name : 'Unknown';
    }
}
?>

<div class="wrap">
    <h1>Axiom Image ID Finder</h1>
    <p>Click on any ID to copy it to clipboard</p>
    
    <style>
        /* Hide any notices that slip through */
        .notice, .notice-error, .notice-warning, .notice-success, .notice-info,
        .updated, .error, .update-nag, #message,
        .wrap > .notice, .wrap > .error, .wrap > .updated {
            display: none !important;
        }
        
        .axiom-media-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .axiom-media-table th {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            text-transform: lowercase;
        }
        
        .axiom-media-table td {
            border: 1px solid #ddd;
            padding: 8px;
            max-height: 100px;
            overflow: hidden;
            vertical-align: middle;
        }
        
        .axiom-media-table tr {
            max-height: 100px;
        }
        
        .axiom-media-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .media-thumbnail {
            max-width: 80px;
            max-height: 80px;
            display: block;
            object-fit: contain;
        }
        
        .id-copy-input {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 5px 8px;
            font-family: monospace;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        .id-copy-input:hover {
            background: #e0e0e0;
            border-color: #999;
        }
        
        .id-copy-input.copied {
            background: #4caf50;
            color: white;
            border-color: #45a049;
        }
        
        .truncate {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        
        .mime-type {
            font-size: 0.9em;
            color: #666;
        }
        
        .no-image {
            width: 80px;
            height: 80px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 12px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .axiom-media-table {
                font-size: 12px;
            }
            
            .truncate {
                max-width: 100px;
            }
        }
    </style>
    
    <table class="axiom-media-table">
        <thead>
            <tr>
                <th>post_id</th>
                <th>image</th>
                <th>post_title</th>
                <th>post_name</th>
                <th>post_mime_type</th>
                <th>post_date</th>
                <th>post_modified</th>
                <th>post_author</th>
                <th>post_status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($media_items as $item): 
                $thumbnail_url = '';
                
                // Get thumbnail for images
                if (strpos($item->post_mime_type, 'image/') === 0) {
                    $thumbnail_url = wp_get_attachment_image_url($item->post_id, 'thumbnail');
                    if (!$thumbnail_url) {
                        $thumbnail_url = wp_get_attachment_image_url($item->post_id, 'full');
                    }
                }
            ?>
            <tr>
                <td>
                    <input type="text" 
                           class="id-copy-input" 
                           value="<?php echo esc_attr($item->post_id); ?>" 
                           readonly
                           onclick="copyToClipboard(this)"
                           title="Click to copy ID">
                </td>
                <td>
                    <?php if ($thumbnail_url): ?>
                        <img src="<?php echo esc_url($thumbnail_url); ?>" 
                             alt="<?php echo esc_attr($item->post_title); ?>" 
                             class="media-thumbnail">
                    <?php else: ?>
                        <div class="no-image">
                            <?php 
                            $ext = pathinfo($item->guid, PATHINFO_EXTENSION);
                            echo strtoupper($ext ?: 'FILE'); 
                            ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="truncate" title="<?php echo esc_attr($item->post_title); ?>">
                        <?php echo esc_html($item->post_title ?: '(no title)'); ?>
                    </span>
                </td>
                <td>
                    <span class="truncate" title="<?php echo esc_attr($item->post_name); ?>">
                        <?php echo esc_html($item->post_name); ?>
                    </span>
                </td>
                <td>
                    <span class="mime-type">
                        <?php echo esc_html($item->post_mime_type); ?>
                    </span>
                </td>
                <td>
                    <?php echo date('Y-m-d', strtotime($item->post_date)); ?>
                </td>
                <td>
                    <?php echo date('Y-m-d', strtotime($item->post_modified)); ?>
                </td>
                <td>
                    <?php echo esc_html($authors[$item->post_author]); ?>
                </td>
                <td>
                    <?php echo esc_html($item->post_status); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($media_items)): ?>
        <p>No media items found in the database.</p>
    <?php else: ?>
        <p>Found <?php echo count($media_items); ?> media items.</p>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(element) {
    // Select the text
    element.select();
    element.setSelectionRange(0, 99999); // For mobile devices
    
    // Copy to clipboard
    try {
        document.execCommand('copy');
        
        // Visual feedback
        element.classList.add('copied');
        const originalValue = element.value;
        element.value = 'Copied!';
        
        // Reset after 1 second
        setTimeout(function() {
            element.classList.remove('copied');
            element.value = originalValue;
        }, 1000);
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
    
    // Deselect
    window.getSelection().removeAllRanges();
}
</script>