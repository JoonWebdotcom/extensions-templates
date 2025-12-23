<?php
// Get data for dashboard
try {
    $products = $api->getProducts(1, 5);
    $orders = [];
} catch (Exception $e) {
    $products = ['products' => []];
    $orders = ['orders' => []];
}

$page_title = 'Products';
ob_start();
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
</style>

<div class="dashboard">
    <div class="stats-grid">
        <div class="stat-card">
            <h3 style="text-align:left;">📦 Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Title</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($products['products'])): ?>
                    <?php foreach ($products['products'] as $key => $product): ?>
                        <tr>
                            <td><?php echo $key + 1; ?></td>
                            <td><?php echo htmlspecialchars($product['title']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" class="empty-state">No products found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.joonwebApp) {
        // Method 1: Using the Toast action directly
        const toast = window.joonwebApp.actions.Toast.create(window.joonwebApp, {
            message: 'Showing you product Page',
            duration: 1000
        });
        toast.show(); // You need to call show() method
        
        // Method 2: Using the show action directly
        // window.joonwebApp.actions.Toast.show({
        //     message: 'Showing you product Page',
        //     duration: 3000
        // });
    } else {
        console.log("JoonWeb App not found");
        
        // Fallback: Show browser notification
        if (Notification && Notification.permission === "granted") {
            new Notification('Showing you product Page');
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>