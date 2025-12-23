<?php
// Get data for dashboard
if(isset($_GET['auto']) && !empty($_GET['auto'])){
    if($_GET['auto'] === 'new') {
        $page_title = 'Create New Automation';
        ob_start();
        include 'views/embedded/new_automation.php';
        $content = ob_get_clean();
        include 'layout.php';
        exit;
    } elseif(is_numeric($_GET['auto'])) {
        $automation_id = intval($_GET['auto']);
        $page_title = 'Edit Automation #' . $automation_id;
        ob_start();
        include 'views/embedded/new_automation.php';
        $content = ob_get_clean();
        include 'layout.php';
        exit;
    }
}

try {
    $automations = $fun->getAutomations($_SESSION['site_domain']) ?? ['automations' => []];
} catch (Exception $e) {
    $automations = ['automations' => []];
}

$page_title = 'Automations';
ob_start();
?>
<style>
    .main-content{
        padding: 0px 16px;
        width: 100%;
        margin: 0;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .stats-grid {
        max-width: 100%;
    }
    
    .stat-card {
        background: white;
        border-radius: 6px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 24px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    table tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.2s ease;
    }

    table th, table td {
        text-align: left;
        padding: 16px 20px;
        font-size: 14px;
    }

    table th {
        background: #fff;
        color: #0a474c;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #fff;
        position: relative;
    }

    table th:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 20%;
        height: 60%;
        width: 1px;
        background: linear-gradient(to bottom, transparent, #a7f3d0, transparent);
    }

    table td {
        color: #334155;
        font-weight: 400;
        background: white;
    }

    tr:hover td {
        background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%);
    }
    
    tr:last-child td:first-child {
        border-bottom-left-radius: 12px;
    }
    
    tr:last-child td:last-child {
        border-bottom-right-radius: 12px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 40px;
        color: #64748b;
        background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%);
        border-radius: 16px;
        margin-top: 20px;
        border: 2px dashed #cbd5e1;
    }
    
    .empty-state svg {
        color: #a7f3d0;
        margin-bottom: 20px;
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05));
    }
    
    .empty-state a {
        color: #10b981;
        text-decoration: none;
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 8px;
        background: #ecfdf5;
        display: inline-block;
        margin-top: 12px;
        transition: all 0.2s ease;
        border: 1px solid #a7f3d0;
    }
    
    .empty-state a:hover {
        background: #d1fae5;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
    }
    
    /* DataTables Custom Styles */
    .dataTables_wrapper {
        margin-top: 24px;
    }
    
    .dataTables_length,
    .dataTables_filter {
        margin-bottom: 20px;
    }
    
    .dataTables_length select,
    .dataTables_filter input {
        padding: 10px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        margin: 0 8px;
        font-size: 14px;
        background: white;
        transition: all 0.2s ease;
    }
    
   
    
    .dataTables_length label,
    .dataTables_filter label {
        color: #475569;
        font-size: 14px;
        font-weight: 500;
    }
    
    .dataTables_info {
        margin-top: 20px;
        color: #64748b;
        font-size: 13px;
        background: #f8fafc;
        padding: 12px 16px;
        border-radius: 10px;
        display: inline-block;
        border: 1px solid #e2e8f0;
    }
    
    .dataTables_paginate {
        margin-top: 20px;
    }
    
    .dataTables_paginate .paginate_button {
        padding: 10px 16px;
        margin: 0 4px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        background: white;
        color: #475569;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
        min-width: 40px;
        text-align: center;
    }
    
    .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, #f0f9ff 0%, #e6f7f0 100%);
        border-color: #a7f3d0;
        color: #0f766e;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
    }
    
    .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-color: #10b981;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
    }
    
    .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #f1f5f9;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .status-badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }
    
    .status-active {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        border: 1px solid #86efac;
    }
    
    .status-active::before {
        background: #10b981;
        box-shadow: 0 0 8px #10b981;
    }
    
    .status-draft {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        border: 1px solid #fcd34d;
    }
    
    .status-draft::before {
        background: #f59e0b;
        box-shadow: 0 0 8px #f59e0b;
    }
    
    .status-paused {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: #374151;
        border: 1px solid #d1d5db;
    }
    
    .status-paused::before {
        background: #6b7280;
        box-shadow: 0 0 8px #6b7280;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn-action {
        padding: 8px 12px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .btn-edit {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
        border: 1px solid #93c5fd;
    }
    
    .btn-edit:hover {
        background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    }
    
    .btn-delete {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #dc2626;
        border: 1px solid #fca5a5;
    }
    
    .btn-delete:hover {
        background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
    }
    
    .btn-view {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: #374151;
        border: 1px solid #d1d5db;
    }
    
    .btn-view:hover {
        background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    }
    
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .table-header h3 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0f766e;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    
    .table-header h3 svg {
        filter: drop-shadow(0 2px 4px rgba(16, 185, 129, 0.2));
    }
    
    .add-new-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 12px 18px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        letter-spacing: 0.3px;
    }
    
    .add-new-btn:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }
    
    .add-new-btn svg {
        stroke-width: 2.5;
    }
    
    /* Custom scrollbar for table */
    .dataTables_scrollBody::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    
    .dataTables_scrollBody::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .dataTables_scrollBody::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
        border-radius: 4px;
    }
    
    .dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #6ee7b7 0%, #34d399 100%);
    }
    
    /* Loading state */
    .dataTables_processing {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
        border-radius: 10px !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2) !important;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .main-content {
            padding: 16px;
        }
        
        .table-header {
            flex-direction: column;
            gap: 16px;
            align-items: flex-start;
        }
        
        .add-new-btn {
            width: 100%;
            justify-content: center;
        }
        
        .action-buttons {
            flex-wrap: wrap;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            float: none;
            text-align: left;
            margin-bottom: 12px;
        }
        
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            width: 100%;
            margin: 8px 0;
        }
    }
</style>

<div class="main-content">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="table-header">
                <h3>
                    <svg height="32px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.06152 12C5.55362 8.05369 8.92001 5 12.9996 5C17.4179 5 20.9996 8.58172 20.9996 13C20.9996 17.4183 17.4179 21 12.9996 21H8M13 13V9M11 3H15M3 15H8M5 18H10" stroke="url(#gradient)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                    Automations
                </h3>
                <a href="?page=automation&auto=new" class="add-new-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    Create New Automation
                </a>
            </div>

            <?php if (!empty($automations['automations'])): ?>
                <div class="table-responsive">
                    <table id="automationsTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Trigger Name</th>
                                <th>Campaign</th>
                                <th>JoonWeb Event</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($automations['automations'] as $automation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($automation['id'] ?? ''); ?></td>
                                    <td>
                                        <div class="automation-name">
                                            <strong><?php echo htmlspecialchars($automation['name'] ?? ''); ?></strong>
                                            <?php if (!empty($automation['description'])): ?>
                                                <small style="display: block; color: #64748b; margin-top: 4px; font-size: 12px;">
                                                    <?php echo htmlspecialchars($automation['description']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($automation['campaign'] ?? ''); ?></td>
                                    <td>
                                        <span class="event-badge">
                                            <?php echo htmlspecialchars($automation['joonweb_event'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $automation['status'] ?? 'draft';
                                        $statusClass = 'status-' . $status;
                                        $statusText = ucfirst($status);
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?page=automation&auto=<?php echo $automation['id']; ?>" class="btn-action btn-edit" title="Edit Automation">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                Edit
                                            </a>
                                            <button class="btn-action btn-delete" title="Delete Automation" onclick="deleteAutomation(<?php echo $automation['id']; ?>)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M10.3246 4.31731C10.751 2.5609 13.249 2.5609 13.6754 4.31731C13.9508 5.45193 15.2507 5.99038 16.2478 5.38285C17.7913 4.44239 19.5576 6.2087 18.6172 7.75218C18.0096 8.74925 18.5481 10.0492 19.6827 10.3246C21.4391 10.751 21.4391 13.249 19.6827 13.6754C18.5481 13.9508 18.0096 15.2507 18.6172 16.2478C19.5576 17.7913 17.7913 19.5576 16.2478 18.6172C15.2507 18.0096 13.9508 18.5481 13.6754 19.6827C13.249 21.4391 10.751 21.4391 10.3246 19.6827C10.0492 18.5481 8.74926 18.0096 7.75219 18.6172C6.2087 19.5576 4.44239 17.7913 5.38285 16.2478C5.99038 15.2507 5.45193 13.9508 4.31731 13.6754C2.5609 13.249 2.5609 10.751 4.31731 10.3246C5.45193 10.0492 5.99037 8.74926 5.38285 7.75218C4.44239 6.2087 6.2087 4.44239 7.75219 5.38285C8.74926 5.99037 10.0492 5.45193 10.3246 4.31731Z"/>
                        <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z"/>
                    </svg>
                    <h3 style="color: #0f766e; margin-bottom: 12px;">No Automations Yet</h3>
                    <p style="font-size: 16px; margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        Start automating your workflows to save time and increase efficiency.
                    </p>
                    <a href="?page=automation&auto=new">
                        Create Your First Automation
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include DataTables from CDN -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    <?php if (!empty($automations['automations'])): ?>
    // Initialize DataTable only if there are automations
    $('#automationsTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        "order": [[0, 'desc']], // Sort by ID descending by default
        "language": {
            "search": "<i class='fa fa-search'></i> Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "No entries available",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "zeroRecords": "No matching records found",
            "paginate": {
                "first": "« First",
                "last": "Last »",
                "next": "Next →",
                "previous": "← Previous"
            },
            "processing": "<div class='spinner'></div> Processing..."
        },
        "columnDefs": [
            {
                "targets": [0], // ID column
                "visible": false,
                "searchable": false
            },
            {
                "targets": [5], // Actions column
                "orderable": false,
                "searchable": false,
                "className": "text-center"
            }
        ],
        "initComplete": function() {
            // Add custom CSS class to DataTables elements
            
            
        },
        "drawCallback": function() {
            // Add hover effects to table rows
            $('#automationsTable tbody tr').hover(
                function() {
                    $(this).css('transform', 'scale(1.002)');
                },
                function() {
                    $(this).css('transform', 'scale(1)');
                }
            );
        }
    });
    <?php endif; ?>
});

// Delete automation function with modern modal
function deleteAutomation(id) {
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(4px);
    `;
    
    modal.innerHTML = `
        <div id="dialsePop" class="deletepopup" style="
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid #e2e8f0;
            text-align: center;
        ">
            <div style="
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                border: 3px solid #fca5a5;
            ">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </div>
            <h3 style="color: #1e293b; margin-bottom: 12px;">Delete Automation?</h3>
            <p style="color: #64748b; margin-bottom: 24px; line-height: 1.5;">
                Are you sure you want to delete this automation? This action cannot be undone and all associated data will be permanently removed.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="this.closest('.deletepopup').parentElement.remove()" style="
                    padding: 12px 24px;
                    border: 2px solid #e2e8f0;
                    background: white;
                    color: #64748b;
                    border-radius: 12px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s ease;
                ">
                    Cancel
                </button>
                <button onclick="confirmDelete(${id})" style="
                    padding: 12px 24px;
                    border: none;
                    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                    color: white;
                    border-radius: 12px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s ease;
                ">
                    Delete Automation
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function confirmDelete(id) {
    // Remove modal
    document.querySelector('div[style*="position: fixed"]').remove();
    
    // Show loading state
    const table = $('#automationsTable').DataTable();
    const row = table.row($(`button[onclick="deleteAutomation(${id})"]`).closest('tr'));
    
    // Add loading state to the row
    row.nodes().to$().css({
        'opacity': '0.6',
        'pointer-events': 'none'
    });
    
    // For demo purposes, show success message
    $('.deletepopup').parent().remove();
    setTimeout(() => {
        // Show success notification
        showNotification('Automation deleted successfully!', 'success');
        
        // In real implementation, you would remove the row after successful API call
        row.remove().draw();
        
        // Remove loading state
        row.nodes().to$().css({
            'opacity': '1',
            'pointer-events': 'auto'
        });

        
        // Show demo message
    }, 1000);

    // Actual API implementation:
    $.ajax({
        url: 'ajax/handler.php',
        type: 'POST',
        data: {
            action: 'delete_automation',
            automation_id: id,
            site_domain: '<?php echo $_SESSION['site_domain']; ?>'
        },
        success: function(response) {
            if (response.success) {
                row.remove().draw();
                showNotification('Automation deleted successfully!', 'success');
            } else {
                showNotification('Error: ' + (response.message || 'Failed to delete'), 'error');
                row.nodes().to$().css({
                    'opacity': '1',
                    'pointer-events': 'auto'
                });
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error: ' + error, 'error');
            row.nodes().to$().css({
                'opacity': '1',
                'pointer-events': 'auto'
            });
        }
    });
    
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    const colors = {
        success: { bg: '#10b981', border: '#059669' },
        error: { bg: '#ef4444', border: '#dc2626' },
        info: { bg: '#3b82f6', border: '#2563eb' }
    };
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, ${colors[type].bg} 0%, ${colors[type].border} 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 1001;
        transform: translateX(120%);
        transition: transform 0.3s ease;
        border: 1px solid ${colors[type].border};
        max-width: 300px;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'success' ? '<path d="M20 6L9 17l-5-5"/>' : '<path d="M18 6L6 18M6 6l12 12"/>'}
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(120%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Export functionality
function exportAutomations(format) {
    <?php if (empty($automations['automations'])): ?>
    showNotification('No automations to export', 'info');
    return;
    <?php endif; ?>
    
    showNotification('Exporting automations...', 'info');
    
    // Implementation would go here
}
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>