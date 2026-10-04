<?php
/**
 * Admin Panel - Customer Accounts & Lifetime Value
 */
$adminTitle = 'Customer Directory - NovaStore';
$activePage = 'customers';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$search = trim($_GET['q'] ?? '');

$where = ["u.role = 'customer'"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT 
        u.*, 
        COUNT(o.id) as total_orders, 
        COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'paid'
    WHERE {$whereSql}
    GROUP BY u.id 
    ORDER BY total_spent DESC, u.created_at DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="space-y-8">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">Customer Directory</h2>
            <p class="text-slate-400 text-sm mt-1">Review registered customer accounts and their lifetime purchasing value</p>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between gap-4">
        <form action="<?= BASE_URL ?>/admin/customers.php" method="GET" class="flex-1 flex items-center gap-3">
            <div class="relative flex-1">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search customers by name, email, or phone..." class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:border-brand-500 outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition-colors">Search</button>
            <?php if (!empty($search)): ?>
                <a href="<?= BASE_URL ?>/admin/customers.php" class="text-xs text-rose-400 hover:underline">Clear</a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-slate-500 font-semibold shrink-0"><?= count($customers) ?> Customer Accounts</span>
    </div>

    <!-- Customers Table -->
    <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Contact Phone</th>
                        <th class="px-6 py-4">Location</th>
                        <th class="px-6 py-4">Total Orders</th>
                        <th class="px-6 py-4">Lifetime Spent</th>
                        <th class="px-6 py-4 text-right">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No customers registered yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $cust): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                                            <?= strtoupper(substr($cust['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-white"><?= e($cust['name']) ?></h4>
                                            <p class="text-xs text-slate-400"><?= e($cust['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-300">
                                    <?= e($cust['phone'] ?: 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400">
                                    <?= e($cust['city'] ?: 'Unknown') ?><?= !empty($cust['state']) ? ', ' . e($cust['state']) : '' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-900 text-slate-200 border border-slate-800">
                                        <?= $cust['total_orders'] ?> orders
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-black text-emerald-400">
                                    <?= format_price($cust['total_spent']) ?>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-slate-500">
                                    <?= date('M d, Y', strtotime($cust['created_at'])) ?>
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
