<?php
/**
 * Customer Account Dashboard & Order History
 */
$pageTitle = 'My Account - NovaStore';
require_once __DIR__ . '/config/config.php';

require_login();
$currentUser = current_user();
$pdo = db();
$tab = $_GET['tab'] ?? 'orders';
$errors = [];
$successMsg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid security token.';
    } else {
        if ($_POST['action'] === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $zipCode = trim($_POST['zip_code'] ?? '');

            if (empty($name)) {
                $errors[] = 'Name cannot be empty.';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $phone, $address, $city, $state, $zipCode, $currentUser['id']]);
                $_SESSION['user_name'] = $name;
                $currentUser = current_user(); // Refresh
                set_flash('success', 'Profile details updated successfully.');
                header('Location: ' . BASE_URL . '/account.php?tab=profile');
                exit;
            }
        } elseif ($_POST['action'] === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            // Fetch current password hash
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $dbHash = $stmt->fetchColumn();

            if (!password_verify($currentPass, $dbHash)) {
                $errors[] = 'Incorrect current password.';
            } elseif (strlen($newPass) < 6) {
                $errors[] = 'New password must be at least 6 characters long.';
            } elseif ($newPass !== $confirmPass) {
                $errors[] = 'New passwords do not match.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update->execute([$newHash, $currentUser['id']]);
                set_flash('success', 'Your password has been changed successfully.');
                header('Location: ' . BASE_URL . '/account.php?tab=password');
                exit;
            }
        }
    }
}

// Fetch Customer's Orders
$orderStmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$orderStmt->execute([$currentUser['id']]);
$userOrders = $orderStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-slate-100/60 border-b border-slate-200/80 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Customer Dashboard</h1>
                <p class="text-slate-500 text-sm mt-1">Manage your order history, delivery details, and account settings</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500">Logged in as:</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-white text-slate-800 border border-slate-200 shadow-xs"><?= e($currentUser['email']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Sidebar Navigation (3 cols) -->
        <aside class="lg:col-span-3 space-y-2">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-4 shadow-sm space-y-1">
                <a href="<?= BASE_URL ?>/account.php?tab=orders" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all <?= $tab === 'orders' ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-700 hover:bg-slate-50' ?>">
                    <i class="fa-solid fa-box-archive text-base"></i>
                    <span>My Orders</span>
                    <span class="ml-auto text-xs py-0.5 px-2 rounded-full <?= $tab === 'orders' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>"><?= count($userOrders) ?></span>
                </a>

                <a href="<?= BASE_URL ?>/account.php?tab=profile" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all <?= $tab === 'profile' ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-700 hover:bg-slate-50' ?>">
                    <i class="fa-solid fa-address-card text-base"></i>
                    <span>Profile & Address</span>
                </a>

                <a href="<?= BASE_URL ?>/account.php?tab=password" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all <?= $tab === 'password' ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-700 hover:bg-slate-50' ?>">
                    <i class="fa-solid fa-shield-keyhole text-base"></i>
                    <span>Security & Password</span>
                </a>

                <div class="pt-2 border-t border-slate-100">
                    <a href="<?= BASE_URL ?>/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold text-rose-600 hover:bg-rose-50 transition-colors">
                        <i class="fa-solid fa-arrow-right-from-bracket text-base"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Area (9 cols) -->
        <main class="lg:col-span-9 space-y-6">
            
            <?php if (!empty($errors)): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <p class="flex items-center gap-2"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Tab 1: Orders -->
            <?php if ($tab === 'orders'): ?>
                <div class="space-y-6">
                    <h2 class="text-xl font-black text-slate-900 tracking-tight">Order History (<?= count($userOrders) ?>)</h2>

                    <?php if (empty($userOrders)): ?>
                        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
                            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl mb-4">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">No orders placed yet</h3>
                            <p class="text-slate-500 text-sm mt-1">When you place orders, they will appear here with live tracking status.</p>
                            <a href="<?= BASE_URL ?>/products.php" class="inline-flex items-center gap-2 mt-6 px-6 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-sm hover:bg-brand-700 transition-colors">
                                Explore Store
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($userOrders as $ord): 
                                // Fetch items for this order
                                $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                                $itemsStmt->execute([$ord['id']]);
                                $orderItems = $itemsStmt->fetchAll();
                            ?>
                                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
                                    
                                    <!-- Order Header -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-4">
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-mono font-black text-slate-900 text-base"><?= e($ord['order_number']) ?></span>
                                                <?= render_order_status_badge($ord['order_status']) ?>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1">Placed on <?= date('M d, Y \a\t g:i A', strtotime($ord['created_at'])) ?></p>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <p class="text-xs text-slate-400">Total Amount</p>
                                                <p class="text-lg font-black text-slate-900"><?= format_price($ord['total_amount']) ?></p>
                                            </div>
                                            <a href="<?= BASE_URL ?>/order-success.php?order=<?= urlencode($ord['order_number']) ?>" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors shrink-0">
                                                View Receipt
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Tracking Timeline Progress Bar -->
                                    <?php
                                    $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                                    $currentStatusIndex = array_search(strtolower($ord['order_status']), $statuses);
                                    if ($currentStatusIndex === false) $currentStatusIndex = 0;
                                    $isCancelled = strtolower($ord['order_status']) === 'cancelled';
                                    ?>
                                    
                                    <?php if (!$isCancelled): ?>
                                        <div class="pt-2">
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Delivery Status</p>
                                            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                                                <div class="space-y-1">
                                                    <div class="w-7 h-7 mx-auto rounded-full flex items-center justify-center font-bold <?= $currentStatusIndex >= 0 ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                                        <i class="fa-solid fa-receipt text-[10px]"></i>
                                                    </div>
                                                    <span class="font-bold <?= $currentStatusIndex >= 0 ? 'text-brand-600' : 'text-slate-400' ?>">Placed</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="w-7 h-7 mx-auto rounded-full flex items-center justify-center font-bold <?= $currentStatusIndex >= 1 ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                                        <i class="fa-solid fa-box text-[10px]"></i>
                                                    </div>
                                                    <span class="font-bold <?= $currentStatusIndex >= 1 ? 'text-brand-600' : 'text-slate-400' ?>">Processing</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="w-7 h-7 mx-auto rounded-full flex items-center justify-center font-bold <?= $currentStatusIndex >= 2 ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                                        <i class="fa-solid fa-truck text-[10px]"></i>
                                                    </div>
                                                    <span class="font-bold <?= $currentStatusIndex >= 2 ? 'text-brand-600' : 'text-slate-400' ?>">Shipped</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="w-7 h-7 mx-auto rounded-full flex items-center justify-center font-bold <?= $currentStatusIndex >= 3 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                                        <i class="fa-solid fa-check text-[10px]"></i>
                                                    </div>
                                                    <span class="font-bold <?= $currentStatusIndex >= 3 ? 'text-emerald-600' : 'text-slate-400' ?>">Delivered</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-700">
                                            This order was cancelled.
                                        </div>
                                    <?php endif; ?>

                                    <!-- Items Mini List -->
                                    <div class="pt-4 border-t border-slate-100 divide-y divide-slate-100">
                                        <?php foreach ($orderItems as $item): ?>
                                            <div class="py-2.5 flex items-center justify-between text-sm">
                                                <div class="flex items-center gap-3">
                                                    <?php if ($item['product_image']): ?>
                                                        <img src="<?= e(get_image_url($item['product_image'])) ?>" alt="" class="w-10 h-10 object-cover rounded-xl bg-slate-100 border border-slate-200/60 shrink-0">
                                                    <?php endif; ?>
                                                    <div>
                                                        <span class="font-bold text-slate-800"><?= e($item['product_name']) ?></span>
                                                        <span class="text-xs text-slate-400 block sm:inline sm:ml-2">Qty: <?= $item['quantity'] ?></span>
                                                    </div>
                                                </div>
                                                <span class="font-bold text-slate-900"><?= format_price($item['total_price']) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <!-- Tab 2: Profile & Shipping Settings -->
            <?php elseif ($tab === 'profile'): ?>
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
                    <h2 class="text-xl font-black text-slate-900 tracking-tight border-b border-slate-100 pb-4">Personal & Shipping Details</h2>

                    <form action="<?= BASE_URL ?>/account.php?tab=profile" method="POST" class="space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Full Name</label>
                                <input type="text" name="name" value="<?= e($currentUser['name']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email Address (Read Only)</label>
                                <input type="email" readonly value="<?= e($currentUser['email']) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-400 text-sm outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Phone Number</label>
                                <input type="tel" name="phone" value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">City</label>
                                <input type="text" name="city" value="<?= e($currentUser['city'] ?? '') ?>" placeholder="Springfield" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Default Street Address</label>
                                <input type="text" name="address" value="<?= e($currentUser['address'] ?? '') ?>" placeholder="123 Main St, Apt 4" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">State / Region</label>
                                <input type="text" name="state" value="<?= e($currentUser['state'] ?? '') ?>" placeholder="Oregon" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Zip Code</label>
                                <input type="text" name="zip_code" value="<?= e($currentUser['zip_code'] ?? '') ?>" placeholder="97477" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100">
                            <button type="submit" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-md transition-all">
                                Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>

            <!-- Tab 3: Security & Password -->
            <?php elseif ($tab === 'password'): ?>
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6 max-w-lg">
                    <h2 class="text-xl font-black text-slate-900 tracking-tight border-b border-slate-100 pb-4">Change Password</h2>

                    <form action="<?= BASE_URL ?>/account.php?tab=password" method="POST" class="space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Current Password</label>
                            <input type="password" name="current_password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">New Password (min 6 chars)</label>
                            <input type="password" name="new_password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 text-sm outline-none">
                        </div>

                        <div class="pt-4 border-t border-slate-100">
                            <button type="submit" class="px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm shadow-md transition-all">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
