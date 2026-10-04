<?php
/**
 * Admin Panel - Order Management List
 */
$adminTitle = 'Order Management - NovaStore';
$activePage = 'orders';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

// Quick inline status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_update_status') {
    if (verify_csrf_token()) {
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $newStatus = trim($_POST['order_status'] ?? '');
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if ($orderId && in_array($newStatus, $allowed)) {
            $upd = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $upd->execute([$newStatus, $orderId]);
            set_flash('success', "Order status updated to " . ucfirst($newStatus) . ".");
            header('Location: ' . BASE_URL . '/admin/orders.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
            exit;
        }
    }
}

// Build query
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $where[] = "o.order_status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE {$whereSql} 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Counts for filter pills
$countsStmt = $pdo->query("SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status");
$statusCounts = ['all' => 0, 'pending' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0];
while ($row = $countsStmt->fetch()) {
    $st = strtolower($row['order_status']);
    if (isset($statusCounts[$st])) {
        $statusCounts[$st] = (int)$row['cnt'];
    }
    $statusCounts['all'] += (int)$row['cnt'];
}
?>

<div class="space-y-8">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">Order Management</h2>
            <p class="text-slate-400 text-sm mt-1">Track fulfillment status, inspect invoices, and manage deliveries</p>
        </div>
    </div>

    <!-- Status Tabs / Filter Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 text-xs font-bold">
        <a href="<?= BASE_URL ?>/admin/orders.php" class="px-4 py-2 rounded-xl border transition-all whitespace-nowrap <?= empty($statusFilter) ? 'bg-brand-600 text-white border-brand-500 shadow-md shadow-brand-600/30' : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-white' ?>">
            All Orders (<?= $statusCounts['all'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/orders.php?status=pending" class="px-4 py-2 rounded-xl border transition-all whitespace-nowrap <?= $statusFilter === 'pending' ? 'bg-amber-600 text-white border-amber-500 shadow-md' : 'bg-slate-950 text-amber-400 border-slate-800 hover:bg-slate-900' ?>">
            Pending (<?= $statusCounts['pending'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/orders.php?status=processing" class="px-4 py-2 rounded-xl border transition-all whitespace-nowrap <?= $statusFilter === 'processing' ? 'bg-blue-600 text-white border-blue-500 shadow-md' : 'bg-slate-950 text-blue-400 border-slate-800 hover:bg-slate-900' ?>">
            Processing (<?= $statusCounts['processing'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/orders.php?status=shipped" class="px-4 py-2 rounded-xl border transition-all whitespace-nowrap <?= $statusFilter === 'shipped' ? 'bg-purple-600 text-white border-purple-500 shadow-md' : 'bg-slate-950 text-purple-400 border-slate-800 hover:bg-slate-900' ?>">
            Shipped (<?= $statusCounts['shipped'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/orders.php?status=delivered" class="px-4 py-2 rounded-xl border transition-all whitespace-nowrap <?= $statusFilter === 'delivered' ? 'bg-emerald-600 text-white border-emerald-500 shadow-md' : 'bg-slate-950 text-emerald-400 border-slate-800 hover:bg-slate-900' ?>">
            Delivered (<?= $statusCounts['delivered'] ?>)
        </a>
    </div>

    <!-- Search Bar -->
    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm">
        <form action="<?= BASE_URL ?>/admin/orders.php" method="GET" class="flex items-center gap-3">
            <?php if (!empty($statusFilter)): ?>
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <div class="relative flex-1">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by order #, customer name, or email..." class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:border-brand-500 outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition-colors">Search</button>
            <?php if (!empty($search)): ?>
                <a href="<?= BASE_URL ?>/admin/orders.php<?= !empty($statusFilter) ? '?status=' . $statusFilter : '' ?>" class="text-xs text-rose-400 hover:underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Order Number</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Total</th>
                        <th class="px-6 py-4">Payment</th>
                        <th class="px-6 py-4">Fulfillment Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-bold text-white"><?= e($ord['order_number']) ?></span>
                                    <span class="text-[11px] text-slate-500 block"><?= $ord['item_count'] ?> item(s)</span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-white"><?= e($ord['shipping_name']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($ord['shipping_email']) ?></p>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400">
                                    <?= date('M d, Y', strtotime($ord['created_at'])) ?><br>
                                    <span class="text-[10px] text-slate-500"><?= date('g:i A', strtotime($ord['created_at'])) ?></span>
                                </td>
                                <td class="px-6 py-4 font-black text-white"><?= format_price($ord['total_amount']) ?></td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <?= render_payment_status_badge($ord['payment_status']) ?>
                                        <span class="text-[10px] text-slate-500 uppercase block font-semibold"><?= e($ord['payment_method']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <form action="<?= BASE_URL ?>/admin/orders.php" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="quick_update_status">
                                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                        <select name="order_status" onchange="this.form.submit()" class="text-xs font-semibold py-1 pl-2 pr-6 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 focus:border-brand-500 outline-none">
                                            <option value="pending" <?= $ord['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $ord['order_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="shipped" <?= $ord['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="delivered" <?= $ord['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="cancelled" <?= $ord['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= BASE_URL ?>/admin/order-detail.php?id=<?= $ord['id'] ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-brand-600 text-slate-300 hover:text-white text-xs font-bold transition-colors">
                                            Details
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/order-invoice.php?id=<?= $ord['id'] ?>" target="_blank" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors" title="Print Invoice">
                                            <i class="fa-solid fa-print text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
