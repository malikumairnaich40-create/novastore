<?php
/**
 * Order Success & Confirmation Page
 */
$pageTitle = 'Order Confirmed - NovaStore';
require_once __DIR__ . '/config/config.php';

$orderNumber = trim($_GET['order'] ?? $_SESSION['last_order_number'] ?? '');

if (empty($orderNumber)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Fetch order items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order['id']]);
$orderItems = $itemStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    
    <!-- Success Banner -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-12 shadow-sm text-center mb-8">
        <div class="w-20 h-20 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-3xl mb-6 ring-8 ring-emerald-50/50">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        
        <span class="text-xs font-bold text-emerald-600 uppercase tracking-widest">Order Confirmed</span>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mt-1">Thank You For Your Order!</h1>
        <p class="text-slate-500 text-sm sm:text-base mt-2 max-w-md mx-auto">
            A confirmation receipt and tracking updates will be sent to <span class="font-bold text-slate-800"><?= e($order['shipping_email']) ?></span>.
        </p>

        <div class="mt-6 inline-flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-100 text-slate-800 text-sm font-semibold">
            <span class="text-slate-500">Order Reference:</span>
            <span class="font-mono font-bold text-brand-600"><?= e($order['order_number']) ?></span>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-4 no-print">
            <button type="button" onclick="window.print()" class="px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-print"></i>
                <span>Print Receipt</span>
            </button>
            <a href="<?= BASE_URL ?>/account.php?tab=orders" class="px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition-colors">
                View My Orders
            </a>
            <a href="<?= BASE_URL ?>/products.php" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors">
                Continue Shopping
            </a>
        </div>
    </div>

    <!-- Receipt Card Details (Printable Area) -->
    <div id="invoice-print-area" class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-10 shadow-sm space-y-8">
        
        <div class="flex flex-col sm:flex-row justify-between sm:items-center pb-6 border-b border-slate-100 gap-4">
            <div>
                <h3 class="text-xl font-bold text-slate-900">Receipt Details</h3>
                <p class="text-xs text-slate-400 mt-0.5">Placed on <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></p>
            </div>
            <div class="flex items-center gap-2">
                <?= render_order_status_badge($order['order_status']) ?>
                <?= render_payment_status_badge($order['payment_status']) ?>
            </div>
        </div>

        <!-- Addresses & Payment Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Shipping Information</h4>
                <div class="space-y-1 text-slate-700 font-medium">
                    <p class="font-bold text-slate-900 text-base"><?= e($order['shipping_name']) ?></p>
                    <p><?= e($order['shipping_address']) ?></p>
                    <p><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> <?= e($order['shipping_zip']) ?></p>
                    <p class="text-slate-500"><i class="fa-solid fa-phone text-xs mr-1"></i> <?= e($order['shipping_phone']) ?></p>
                    <p class="text-slate-500"><i class="fa-solid fa-envelope text-xs mr-1"></i> <?= e($order['shipping_email']) ?></p>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Payment Method</h4>
                <div class="space-y-2 text-slate-700 font-medium">
                    <p class="font-bold text-slate-900 capitalize flex items-center gap-2">
                        <?php if ($order['payment_method'] === 'cod'): ?>
                            <i class="fa-solid fa-money-bill-wave text-emerald-600"></i> Cash on Delivery (COD)
                        <?php else: ?>
                            <i class="fa-solid fa-credit-card text-brand-600"></i> Credit / Debit Card
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-slate-500">
                        Status: <span class="font-bold capitalize text-slate-800"><?= e($order['payment_status']) ?></span>
                    </p>
                    <?php if (!empty($order['order_notes'])): ?>
                        <div class="mt-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <span class="font-bold text-slate-600">Delivery Notes:</span>
                            <p class="text-slate-500 mt-0.5"><?= e($order['order_notes']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ordered Items Table -->
        <div class="pt-4 border-t border-slate-100">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Purchased Items</h4>
            <div class="divide-y divide-slate-100">
                <?php foreach ($orderItems as $item): ?>
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <?php if ($item['product_image']): ?>
                                <img src="<?= e(get_image_url($item['product_image'])) ?>" alt="" class="w-12 h-12 object-cover rounded-xl bg-slate-100 border border-slate-200/60 shrink-0">
                            <?php endif; ?>
                            <div>
                                <h5 class="text-sm font-bold text-slate-900"><?= e($item['product_name']) ?></h5>
                                <p class="text-xs text-slate-400">Qty: <?= $item['quantity'] ?> × <?= format_price($item['price']) ?></p>
                            </div>
                        </div>
                        <span class="text-sm font-black text-slate-900"><?= format_price($item['total_price']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pricing Totals Breakdown -->
        <div class="pt-6 border-t border-slate-100 flex justify-end">
            <div class="w-full sm:w-72 space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal</span>
                    <span class="font-bold text-slate-900"><?= format_price($order['subtotal']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Shipping</span>
                    <span class="font-bold text-slate-900"><?= $order['shipping_fee'] == 0 ? 'FREE' : format_price($order['shipping_fee']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Tax</span>
                    <span class="font-bold text-slate-900"><?= format_price($order['tax_amount']) ?></span>
                </div>
                <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                    <span class="text-base font-bold text-slate-900">Total Paid/Due</span>
                    <span class="text-2xl font-black text-brand-600"><?= format_price($order['total_amount']) ?></span>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
