<?php
// Get data for dashboard
if(isset($_GET['auto']) && is_numeric($_GET['auto'])){
    $automation_id = intval($_GET['auto']);
} else {
    $automation_id = '';
}

try {
    $automation = $fun->getAutomation($automation_id, $_SESSION['site_domain']) ?? [];
} catch (Exception $e) {
    $automation = [];
}

$page_title = $automation_id ? 'Edit Automation #' . $automation_id : 'New Automation';
?>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    table tr {
        border-bottom: 1px solid #e5e7eb;
    }

    table th, table td {
        text-align: left;
        padding: 12px 8px;
        font-size: 15px;
    }

    table th {
        background: #f3f4f6;
        color: #374151;
        font-weight: 600;
    }

    table td {
        color: #1f2937;
    }

    tr:hover td {
        background: #f9fafb;
    }

    .empty-state {
        text-align: center;
        padding: 20px;
        color: #6b7280;
    }
    
    /* Additional styles for the form */
    .automation-form {
        max-width: 800px;
        margin: 20px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 24px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        text-align: left;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 15px;
        transition: border-color 0.2s;
        text-align: left;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-row {
        display: flex;
        gap: 16px;
    }
    
    .form-col {
        flex: 1;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        font-size: 15px;
    }
    
    .btn-primary {
        background-color: #3b82f6;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #2563eb;
    }
    
    .btn-secondary {
        background-color: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    
    .variables-section {
        margin-top: 30px;
        border-top: 1px solid #e5e7eb;
        padding-top: 20px;
        text-align: left;
    }
    
    .variable-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }
    
    .variable-name {
        flex: 1;
    }
    
    .variable-value {
        flex: 2;
        position: relative;
    }
    
    .btn-add-variable {
        background: none;
        border: 1px dashed #d1d5db;
        color: #6b7280;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 10px;
    }
    
    .btn-add-variable:hover {
        background-color: #f9fafb;
        border-color: #9ca3af;
    }
    
    .btn-remove {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
    }
    
    .btn-remove:hover {
        background-color: #fef2f2;
    }
    
    /* Select2 styles */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        min-height: 42px;
        padding: 5px;
    }
    
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 4px;
        color: #1e40af;
        padding: 2px 8px;
        margin: 3px;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #1e40af;
        margin-right: 4px;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #dc2626;
        background-color: transparent;
    }
    
    .select2-dropdown {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .select2-results__option {
        padding: 8px 12px;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .select2-results__option--highlighted {
        background-color: #eff6ff;
        color: #1e40af;
    }
    
    .select2-results__option[aria-selected=true] {
        background-color: #dbeafe;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    .main-content{
        padding: 0px 16px;
        width: 100%;
        margin: 0;
        background: #f8fafc;
        min-height: 100vh;
    }
</style>

<div class="main-content">
    <div class="stats-grid">
        <div class="stat-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0f766e;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.5px;"><svg height="32px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.06152 12C5.55362 8.05369 8.92001 5 12.9996 5C17.4179 5 20.9996 8.58172 20.9996 13C20.9996 17.4183 17.4179 21 12.9996 21H8M13 13V9M11 3H15M3 15H8M5 18H10" stroke="url(#gradient)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:1"></stop>
                                <stop offset="100%" style="stop-color:#059669;stop-opacity:1"></stop>
                            </linearGradient>
                        </defs>
                    </svg> <?=$page_title;?></h3>
                
                <a href="?page=automation" class="btn btn-secondary">← Back to Automations</a>
            </div>
            
            <div class="automation-formx">
                
                <form id="automationForm">
                    <input type="hidden" id="automation_id" value="<?php echo $automation_id; ?>">
                    
                    <div class="form-group">
                        <label for="automation_name">Name *</label>
                        <input type="text" id="automation_name" class="form-control" placeholder="Enter automation name" 
                               value="<?php echo htmlspecialchars($automation['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="joonweb_event">JoonWeb Event *</label>
                        <select id="joonweb_event" class="form-control" required>
                            <option value="">Select an event</option>
                            <option value="checkouts/abandoned" <?php echo ($automation['joonweb_event'] ?? '') === 'checkouts/abandoned' ? 'selected' : ''; ?>>Abandoned Checkout</option>
                            <option value="customers/create" <?php echo ($automation['joonweb_event'] ?? '') === 'customers/create' ? 'selected' : ''; ?>>New Customer</option>
                            <option value="orders/create" <?php echo ($automation['joonweb_event'] ?? '') === 'orders/create' ? 'selected' : ''; ?>>New Order</option>
                            <option value="orders/confirmed" <?php echo ($automation['joonweb_event'] ?? '') === 'orders/confirmed' ? 'selected' : ''; ?>>Order Confirmed</option>
                            <option value="orders/paid" <?php echo ($automation['joonweb_event'] ?? '') === 'orders/paid' ? 'selected' : ''; ?>>Order Paid</option>
                            <option value="orders/shipped" <?php echo ($automation['joonweb_event'] ?? '') === 'orders/shipped' ? 'selected' : ''; ?>>Order Shipped</option>
                            <option value="orders/delivered" <?php echo ($automation['joonweb_event'] ?? '') === 'orders/delivered' ? 'selected' : ''; ?>>Order Delivered</option>
                            <option value="orders/cancelled" <?php echo ($automation['joonweb_event'] ?? '') === 'orders/cancelled' ? 'selected' : ''; ?>>Order Cancelled</option>
                            <option value="leads/create" <?php echo ($automation['joonweb_event'] ?? '') === 'leads/create' ? 'selected' : ''; ?>>Form Submitted</option>
                        </select>
                        <small style="color: #6b7280; margin-top: 4px; display: block;text-align:left;">Select the event that will trigger this automation</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="campaign">Campaign name [Aisensy]</label>
                                <input name="campaign" id="campaign" class="form-control" placeholder="Enter campaign name"
                                       value="<?php echo htmlspecialchars($automation['campaign'] ?? ''); ?>" list="campaigns-list" required>
                                <p style="text-align: left;color: #6b7280; font-size: 12px;">Note: It should match Aisensy campaign name exactly.</p>
                                    </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" class="form-control">
                                    <option value="active" <?php echo ($automation['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="draft" <?php echo ($automation['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="paused" <?php echo ($automation['status'] ?? '') === 'paused' ? 'selected' : ''; ?>>Paused</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="variables-section">
                        <label style="font-weight: 600; margin-bottom: 12px; display: block;">Variables</label>
                        <div id="variables-container">
        
                            <?php if (!empty($automation['variables'])): ?>
                                <?php foreach ($automation['variables'] as $index => $variable): ?>
                                    <div class="variable-row">
                                        <div class="variable-name">
                                            <?php echo htmlspecialchars($variable['name'] ?? '{{' . ($index + 1) . '}}'); ?>
                                        </div>
                                        <div class="variable-value">
                                            <select class="form-control variable-value-input select2-field" multiple="multiple" style="width: 100%;">
                                                <?php 
                                                $selectedValues = !empty($variable['value']) ? explode(',', $variable['value']) : [];
                                                foreach ($selectedValues as $value): ?>
                                                    <option value="<?php echo htmlspecialchars(trim($value)); ?>" selected><?php echo htmlspecialchars(trim($value)); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn-remove" title="Remove variable">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18 6L6 18M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                               <div>You can add variables to pass dynamic data from JoonWeb events to Aisensy campaigns.</div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="btn-add-variable" id="addVariable">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"></path>
                            </svg>
                            Add Variable
                        </button>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=automation'">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Automation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery and Select2 from CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Sample data for select suggestions based on selected event
    const eventVariables = {
        "customers/create": [
            { id: 'user_id', text: 'User ID' },
            { id: 'name', text: 'Customer Name' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'created_at', text: 'Customer Created At' }
        ],
        "leads/create": [
            { id: 'submission_id', text: 'Form Submission ID' },
            { id: 'form_name', text: 'Form Name'},
            { id: 'name', text: 'Customer Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'ip', text: 'Customer IP Address' },
            { id: 'created_at', text: 'Form Submitted At' }
        ],
        "orders/confirmed": [
            { id: 'invoice_id', text: 'Order ID' },
            { id: 'subtotal_price', text: 'Order Subtotal' },
            { id: 'total_price', text: 'Order Total' },
            { id: 'total_tax', text: 'Order Total Tax' },
            { id: 'total_discount', text: 'Order Total Discount' },
            { id: 'total_due', text: 'Order Due' },
            { id: 'payment_status', text: 'Order Payment Status' },
            { id: 'order_items_title', text: 'First Ordered Item Title' },
            { id: 'order_items_quantity', text: 'First Ordered Item Qty' },
            { id: 'total_items_count', text: 'Total Count' },
            { id: 'name', text: 'Customer Name' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'shipping_address', text: 'Shipping Address' },
            { id: 'created_at', text: 'Order Created At' }
        ],
        "orders/create": [
            { id: 'invoice_id', text: 'Order ID' },
            { id: 'subtotal_price', text: 'Order Subtotal' },
            { id: 'total_price', text: 'Order Total' },
            { id: 'total_tax', text: 'Order Total Tax' },
            { id: 'total_discount', text: 'Order Total Discount' },
            { id: 'total_due', text: 'Order Due' },
            { id: 'payment_status', text: 'Order Payment Status' },
            { id: 'order_items_title', text: 'First Ordered Item Title' },
            { id: 'order_items_quantity', text: 'First Ordered Item Qty' },
            { id: 'total_items_count', text: 'Total Count' },
            { id: 'name', text: 'Customer Name' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'shipping_address', text: 'Shipping Address' },
            { id: 'created_at', text: 'Order Created At' }
        ],
        "orders/paid": [
            { id: 'invoice_id', text: 'Order ID' },
            { id: 'subtotal_price', text: 'Order Subtotal' },
            { id: 'total_price', text: 'Order Total' },
            { id: 'total_tax', text: 'Order Total Tax' },
            { id: 'total_discount', text: 'Order Total Discount' },
            { id: 'total_due', text: 'Order Due' },
            { id: 'payment_status', text: 'Order Payment Status' },
            { id: 'payment_method', text: 'Order Payment Method' },
            { id: 'order_items_title', text: 'First Ordered Item Title' },
            { id: 'order_items_quantity', text: 'First Ordered Item Qty' },
            { id: 'total_items_count', text: 'Total Count' },
            { id: 'name', text: 'Customer Name' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'shipping_address', text: 'Shipping Address' },
            { id: 'created_at', text: 'Order Created At' }
        ],
        "orders/shipped": [
            { id: 'invoice_id', text: 'Order ID' },
            { id: 'subtotal_price', text: 'Order Subtotal' },
            { id: 'total_price', text: 'Order Total' },
            { id: 'total_tax', text: 'Order Total Tax' },
            { id: 'total_discount', text: 'Order Total Discount' },
            { id: 'total_due', text: 'Order Due' },
            { id: 'payment_status', text: 'Order Payment Status' },
            { id: 'payment_method', text: 'Order Payment Method' },
            { id: 'order_items_title', text: 'First Ordered Item Title' },
            { id: 'order_items_quantity', text: 'First Ordered Item Qty' },
            { id: 'total_items_count', text: 'Total Count' },
            { id: 'name', text: 'Customer Name' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'shipping_address', text: 'Shipping Address' },
            { id: 'created_at', text: 'Order Created At' }
        ],
        "orders/delivered": [
            { id: 'invoice_id', text: 'Order ID' },
            { id: 'subtotal_price', text: 'Order Subtotal' },
            { id: 'total_price', text: 'Order Total' },
            { id: 'total_tax', text: 'Order Total Tax' },
            { id: 'total_discount', text: 'Order Total Discount' },
            { id: 'total_due', text: 'Order Due' },
            { id: 'payment_status', text: 'Order Payment Status' },
            { id: 'payment_method', text: 'Order Payment Method' },
            { id: 'order_items_title', text: 'First Ordered Item Title' },
            { id: 'order_items_quantity', text: 'First Ordered Item Qty' },
            { id: 'total_items_count', text: 'Total Count' },
            { id: 'name', text: 'Customer Name' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'shipping_address', text: 'Shipping Address' },
            { id: 'created_at', text: 'Order Created At' }
        ],
        "orders/cancelled": [
            { id: 'invoice_id', text: 'Order ID' },
            { id: 'cancellation_reason', text: 'Cancellation Reason' },
            { id: 'cancellation_date', text: 'Cancellation Date' },
            { id: 'customer_email', text: 'Customer Email' },
            { id: 'subtotal_price', text: 'Order Subtotal' },
            { id: 'total_price', text: 'Order Total' },
            { id: 'total_tax', text: 'Order Total Tax' },
            { id: 'total_discount', text: 'Order Total Discount' },
            { id: 'total_due', text: 'Order Due' },
            { id: 'payment_status', text: 'Order Payment Status' },
            { id: 'payment_method', text: 'Order Payment Method' },
            { id: 'order_items_title', text: 'First Ordered Item Title' },
            { id: 'order_items_quantity', text: 'First Ordered Item Qty' },
            { id: 'total_items_count', text: 'Total Count' },
            { id: 'name', text: 'Customer Name' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'shipping_address', text: 'Shipping Address' },
            { id: 'created_at', text: 'Order Created At' }
        ],
        "checkouts/abandoned": [
            { id: 'checkout_id', text: 'Checkout ID' },
            { id: 'cart_items', text: 'Cart Items' },
            { id: 'firstName', text: 'Customer First Name' },
            { id: 'lastName', text: 'Customer Last Name' },
            { id: 'mobile', text: 'Customer Mobile' },
            { id: 'email', text: 'Customer Email' },
            { id: 'checkout_link', text: 'Checkout Recovery Link' },
            { id: 'abandonment_time', text: 'Abandonment Time' },
            { id: 'customer_email', text: 'Customer Email' }
        ]
    };

    // Store Select2 instances
    const select2Instances = [];

    // Initialize Select2 on existing variable value fields
    function initSelect2(selectElement) {
        const $select = $(selectElement);
        
        // Initialize Select2
        $select.select2({
            tags: true,
            tokenSeparators: [','],
            placeholder: "Select or type variables",
            allowClear: false,
            width: '100%',
            createTag: function (params) {
                return {
                    id: params.term,
                    text: params.term,
                    newTag: true
                };
            }
        });

        // Update suggestions when event changes
        $('#joonweb_event').on('change', function() {
            const selectedEvent = $(this).val();
            const suggestions = eventVariables[selectedEvent] || [];
            
            // Clear current options and add new ones
            $select.empty();
            
            // Add suggestions as options
            suggestions.forEach(function(item) {
                const option = new Option(item.text, item.id, false, false);
                $select.append(option);
            });
            
            // Refresh Select2
            $select.trigger('change');
        });

        // Trigger initial update
        const initialEvent = $('#joonweb_event').val();
        if (initialEvent && eventVariables[initialEvent]) {
            const suggestions = eventVariables[initialEvent];
            suggestions.forEach(function(item) {
                const option = new Option(item.text, item.id, false, false);
                $select.append(option);
            });
            $select.trigger('change');
        }

        select2Instances.push($select);
        return $select;
    }

    // Initialize existing variable fields
    $('.variable-value-input').each(function() {
        initSelect2(this);
    });

    // Add new variable row
    $('#addVariable').on('click', function() {
        const container = $('#variables-container');
        const variableCount = container.children('.variable-row').length + 1;
        
        const newRow = $(`
            <div class="variable-row">
                <div class="variable-name">
                    {{${variableCount}}}
                </div>
                <div class="variable-value">
                    <select class="form-control variable-value-input select2-field" multiple="multiple" style="width: 100%;"></select>
                </div>
                <button type="button" class="btn-remove" title="Remove variable">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `);
        
        container.append(newRow);
        
        // Initialize Select2 for the new field
        const newSelect = newRow.find('.variable-value-input');
        initSelect2(newSelect);
        
        // Add remove functionality
        newRow.find('.btn-remove').on('click', function() {
            if ($('.variable-row').length > 0) {
                // Destroy Select2 before removing
                const select2Instance = $(this).closest('.variable-row').find('.variable-value-input');
                select2Instance.select2('destroy');
                $(this).closest('.variable-row').remove();
            }
        });
    });

    // Add remove functionality to existing rows
    $('.btn-remove').on('click', function() {
        if ($('.variable-row').length > 0) {
            // Destroy Select2 before removing
            const select2Instance = $(this).closest('.variable-row').find('.variable-value-input');
            select2Instance.select2('destroy');
            $(this).closest('.variable-row').remove();
        } 
    });

    // Form submission
    // $('#automationForm').on('submit', function(e) {
    //     e.preventDefault();
        
    //     // Collect form data
    //     const formData = {
    //         name: $('#automation_name').val(),
    //         status: $('#status').val(),
    //         campaign: $('#campaign').val(),
    //         joonweb_event: $('#joonweb_event').val(),
    //         variables: []
    //     };
        
    //     // Collect variables
    //     $('.variable-row').each(function() {
    //         const nameElement = $(this).find('.variable-name');
    //         const valueSelect = $(this).find('.variable-value-input');
            
    //         const name = nameElement.text().trim();
    //         const values = valueSelect.val() || [];
            
    //         if (name && values.length > 0) {
    //             formData.variables.push({
    //                 name: name,
    //                 value: values.join(',')
    //             });
    //         }
    //     });
        
    //     // Basic validation
    //     if (!formData.name) {
    //         alert('Please enter an automation name');
    //         return;
    //     }
        
    //     if (!formData.campaign) {
    //         alert('Please select a campaign');
    //         return;
    //     }
        
    //     if (!formData.joonweb_event) {
    //         alert('Please select a JoonWeb event');
    //         return;
    //     }
        
    //     // Show loading state
    //     const submitBtn = $(this).find('button[type="submit"]');
    //     const originalText = submitBtn.text();
    //     submitBtn.text('Saving...').prop('disabled', true);
        
    //     // Send data to server
    //     $.ajax({
    //         url: 'ajax/save_automation.php',
    //         type: 'POST',
    //         data: {
    //             automation_id: $('#automation_id').val(),
    //             ...formData
    //         },
    //         success: function(response) {
    //             if (response.success) {
    //                 alert('Automation saved successfully!');
    //                 window.location.href = '?page=automation';
    //             } else {
    //                 alert('Error saving automation: ' + (response.message || 'Unknown error'));
    //             }
    //         },
    //         error: function(xhr, status, error) {
    //             alert('Error saving automation: ' + error);
    //         },
    //         complete: function() {
    //             submitBtn.text(originalText).prop('disabled', false);
    //         }
    //     });
    // });

    $('#automationForm').on('submit', function(e) {
    e.preventDefault();
    
    // Collect form data
    const formData = {
        action: 'save_automation',
        name: $('#automation_name').val(),
        status: $('#status').val(),
        campaign: $('#campaign').val(),
        joonweb_event: $('#joonweb_event').val(),
        variables: []
    };
    
    // Collect variables
    $('.variable-row').each(function() {
        const nameElement = $(this).find('.variable-name');
        const valueSelect = $(this).find('.variable-value-input');
        
        const name = nameElement.text().trim();
        const values = valueSelect.val() || [];
        
        if (name && values.length > 0) {
            formData.variables.push({
                name: name,
                value: values.join(',')
            });
        }
    });
    
    // Basic validation
    if (!formData.name) {
        alert('Please enter an automation name');
        return;
    }
    
    if (!formData.campaign) {
        alert('Please select a campaign');
        return;
    }
    
    if (!formData.joonweb_event) {
        alert('Please select a JoonWeb event');
        return;
    }
    
    // Show loading state
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.text('Saving...').prop('disabled', true);
    
    // Add automation_id if editing
    const automationId = $('#automation_id').val();
    if (automationId) {
        formData.automation_id = automationId;
    }
    
    // Send data to server
    $.ajax({
        url: 'ajax/handler.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Automation saved successfully!');
                window.location.href = '?page=automation';
            } else {
                alert('Error saving automation: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            alert('Error saving automation: ' + error);
        },
        complete: function() {
            submitBtn.text(originalText).prop('disabled', false);
        }
    });
});

    // Clean up Select2 instances when leaving page
    $(window).on('beforeunload', function() {
        select2Instances.forEach(function(instance) {
            instance.select2('destroy');
        });
    });
});
</script>