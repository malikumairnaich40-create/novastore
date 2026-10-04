<?php
/**
 * Admin Panel Global Header Layout
 */
require_once __DIR__ . '/auth_check.php';

$pdo = db();
$activePage = $activePage ?? 'dashboard';

// Count pending orders for badge
try {
    $pendingOrdersCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
    $lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 5 AND status = 'active'")->fetchColumn();
} catch (Exception $e) {
    $pendingOrdersCount = 0;
    $lowStockCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle ?? 'Admin Dashboard - NovaStore') ?></title>
    
    <!-- Tailwind CSS CDN with Plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">

    <script>
        window.APP_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body class="flex h-full bg-slate-900 text-slate-100">

    <!-- Admin Sidebar Navigation -->
    <aside class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col shrink-0 hidden md:flex">
        
        <!-- Brand Header -->
        <div class="h-20 flex items-center gap-3 px-6 border-b border-slate-800/80">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-purple-600 to-brand-500 flex items-center justify-center text-white shadow-md">
                <i class="fa-solid fa-cube text-base"></i>
            </div>
            <div>
                <span class="text-lg font-black text-white tracking-tight">Nova Admin</span>
                <span class="block text-[10px] font-bold text-purple-400 uppercase tracking-wider">Control Center</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            
            <p class="px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Main Navigation</p>
            
            <a href="<?= BASE_URL ?>/admin/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activePage === 'dashboard' ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <i class="fa-solid fa-gauge-high text-base w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/products.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= in_array($activePage, ['products', 'product-add', 'product-edit']) ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <i class="fa-solid fa-boxes-stacked text-base w-5 text-center"></i>
                <span>Products</span>
                <?php if ($lowStockCount > 0): ?>
                    <span class="ml-auto px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30"><?= $lowStockCount ?> Low</span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/admin/categories.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activePage === 'categories' ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <i class="fa-solid fa-layer-group text-base w-5 text-center"></i>
                <span>Categories</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/orders.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= in_array($activePage, ['orders', 'order-detail']) ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <i class="fa-solid fa-cart-flatbed text-base w-5 text-center"></i>
                <span>Orders</span>
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="ml-auto px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/admin/customers.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activePage === 'customers' ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <i class="fa-solid fa-users text-base w-5 text-center"></i>
                <span>Customers</span>
            </a>

            <div class="pt-6 border-t border-slate-800 my-4">
                <p class="px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Store Links</p>
                <a href="<?= BASE_URL ?>/index.php" target="_blank" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-900 transition-all">
                    <i class="fa-solid fa-arrow-up-right-from-square text-base w-5 text-center text-brand-400"></i>
                    <span>Live Storefront</span>
                </a>
            </div>

        </nav>

        <!-- Current Admin User Footer -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-950/60 flex items-center justify-between">
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="w-8 h-8 rounded-lg bg-purple-600 text-white font-bold flex items-center justify-center text-xs shrink-0">
                    A
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate"><?= e($currentAdmin['name']) ?></p>
                    <p class="text-[10px] text-slate-500 truncate"><?= e($currentAdmin['email']) ?></p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="text-slate-500 hover:text-rose-400 p-1.5 transition-colors" title="Sign Out">
                <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
            </a>
        </div>
    </aside>

    <!-- Main Workspace Container -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-900">
        
        <!-- Top Admin Header -->
        <header class="h-20 bg-slate-950/90 backdrop-blur-md border-b border-slate-800 flex items-center justify-between px-6 sm:px-8 z-30 shrink-0">
            <div class="flex items-center gap-4">
                <button type="button" id="admin-mobile-toggle" class="md:hidden text-slate-400 hover:text-white p-2">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h1 class="text-lg font-bold text-white"><?= e($adminTitle ?? 'Dashboard') ?></h1>
            </div>

            <div class="flex items-center gap-4">
                <a href="<?= BASE_URL ?>/admin/product-add.php" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all shadow-md">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Product</span>
                </a>
                <a href="<?= BASE_URL ?>/index.php" target="_blank" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <i class="fa-solid fa-store"></i>
                    <span class="hidden sm:inline">View Shop</span>
                </a>
            </div>
        </header>

        <!-- Flash Toast Script -->
        <?php $flash = get_flash(); ?>
        <?php if ($flash): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    showToast("<?= addslashes(e($flash['message'])) ?>", "<?= e($flash['type']) ?>");
                });
            </script>
        <?php endif; ?>

        <!-- Scrollable Page Content -->
        <main class="flex-1 overflow-y-auto p-6 sm:p-8">
