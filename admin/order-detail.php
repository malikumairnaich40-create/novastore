<?php
/**
 * Admin Panel - Order Detail View & Status Updater
 */
$adminTitle = 'Order Details - NovaStore';
$activePage = 'order-detail';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId) {
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token()) {
        $orderStatus = trim($_POST['order_status'] ?? '');
        $paymentStatus = trim($_POST['payment_status'] ?? '');
        $notes = trim($_POST['order_notes'] ?? '');

        $upd = $pdo->prepare("
            UPDATE orders 
            SET order_status = ?, payment_status = ?, order_notes = ? 
            WHERE id = ?
        ");
        $upd->execute([$orderStatus, $paymentStatus, $notes, $orderId]);

        set_flash('success', 'Order status & payment records updated.');
        header('Location: ' . BASE_URL . '/admin/order-detail.php?id=' . $orderId);
        exit;
    }
}

// Fetch Order
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_account_name, u.email as customer_account_email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? 
    LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// Fetch Order Items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();
?>

<div class="space-y-8">
    
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="<?= BASE_URL ?>/admin/orders.php" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Orders
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-black text-white tracking-tight">Order <?= e($order['order_number']) ?></h2>
                <?= render_order_status_badge($order['order_status']) ?>
                <?= render_payment_status_badge($order['payment_status']) ?>
            </div>
            <p class="text-slate-400 text-xs mt-1">Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/admin/order-invoice.php?id=<?= $order['id'] ?>" target="_blank" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-print"></i>
                <span>Print Invoice</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Order Items & Delivery Info (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Items Table Card -->
            <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h3 class="font-bold text-white text-base">Ordered Line Items (<?= count($orderItems) ?>)</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="px-6 py-3.5">Product</th>
                                <th class="px-6 py-3.5">Price</th>
                                <th class="px-6 py-3.5">Quantity</th>
                                <th class="px-6 py-3.5 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ($item['product_image']): ?>
                                                <img src="<?= e(get_image_url($item['product_image'])) ?>" alt="" class="w-12 h-12 object-cover rounded-xl bg-slate-900 border border-slate-800 shrink-0">
                                            <?php endif; ?>
                                            <div>
                                                <h4 class="font-bold text-white"><?= e($item['product_name']) ?></h4>
                                                <?php if ($item['product_id']): ?>
                                                    <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $item['product_id'] ?>" class="text-[11px] text-brand-400 hover:underline">View in Catalog</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-300"><?= format_price($item['price']) ?></td>
                                    <td class="px-6 py-4 font-bold text-white"><?= $item['quantity'] ?></td>
                                    <td class="px-6 py-4 text-right font-black text-white"><?= format_price($item['total_price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Breakdown -->
                <div class="p-6 bg-slate-900/40 border-t border-slate-800 flex justify-end">
                    <div class="w-72 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-400">
                            <span>Subtotal</span>
                            <span class="font-bold text-white"><?= format_price($order['subtotal']) ?></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>Shipping</span>
                            <span class="font-bold text-white"><?= $order['shipping_fee'] == 0 ? 'FREE' : format_price($order['shipping_fee']) ?></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>Estimated Tax</span>
                            <span class="font-bold text-white"><?= format_price($order['tax_amount']) ?></span>
                        </div>
                        <div class="pt-3 border-t border-slate-800 flex justify-between items-baseline">
                            <span class="font-bold text-white">Grand Total</span>
                            <span class="text-xl font-black text-brand-400"><?= format_price($order['total_amount']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer & Delivery Address Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-3">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Customer Contact</h4>
                    <div class="space-y-1 text-sm">
                        <p class="font-bold text-white text-base"><?= e($order['shipping_name']) ?></p>
                        <p class="text-slate-300"><i class="fa-solid fa-envelope text-slate-500 mr-1.5 text-xs"></i> <?= e($order['shipping_email']) ?></p>
                        <p class="text-slate-300"><i class="fa-solid fa-phone text-slate-500 mr-1.5 text-xs"></i> <?= e($order['shipping_phone']) ?></p>
                    </div>
                </div>

                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-3">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Shipping Address</h4>
                    <div class="space-y-1 text-sm text-slate-300">
                        <p><?= e($order['shipping_address']) ?></p>
                        <p><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> <?= e($order['shipping_zip']) ?></p>
                        <p class="text-slate-500 text-xs font-semibold">United States</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right: Status Management Sidebar (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-sm space-y-6">
                <h3 class="font-bold text-white text-base border-b border-slate-800 pb-4">Manage Status & Notes</h3>

                <form action="<?= BASE_URL ?>/admin/order-detail.php?id=<?= $orderId ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Order Fulfillment Status</label>
                        <select name="order_status" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                            <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending Fulfillment</option>
                            <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Processing / Packing</option>
                            <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped / In Transit</option>
                            <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered to Customer</option>
                            <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled / Refunded</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Payment Status</label>
                        <select name="payment_status" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending Payment</option>
                            <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid Successfully</option>
                            <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed / Declined</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Internal Delivery / Order Notes</label>
                        <textarea name="order_notes" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs focus:border-brand-500 outline-none"><?= e($order['order_notes']) ?></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-md transition-all">
                        Update Order
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
