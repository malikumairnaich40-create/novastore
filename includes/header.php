<?php
/**
 * Frontend Global Header
 */
require_once __DIR__ . '/../config/config.php';

// Fetch active categories for navigation
try {
    $navCategories = db()->query("SELECT id, name, slug, icon FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    $navCategories = [];
}

$currentUser = current_user();
$cartCount = get_cart_count();
$pageTitle = $pageTitle ?? APP_NAME . ' - ' . APP_TAGLINE;
$currentQuery = $_GET['q'] ?? '';
$activeCategorySlug = $_GET['category'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Tailwind CSS CDN with Plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
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
                            950: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    
    <script>
        window.APP_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body class="flex flex-col min-h-full bg-slate-50 text-slate-800">

    <!-- Top Announcement Bar -->
    <div class="bg-slate-900 text-slate-300 text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-brand-500 text-white uppercase tracking-wider">Offer</span>
                <span>Free shipping on all orders over <?= format_price(FREE_SHIPPING_THRESHOLD) ?>!</span>
            </div>
            <div class="flex items-center gap-6 text-slate-400">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-headset text-brand-400"></i> Support: +1 (800) 555-NOVA</span>
                <span class="hidden md:inline">|</span>
                <span class="hidden md:flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-emerald-400"></i> 100% Secure Checkout</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">
                
                <!-- Logo -->
                <div class="flex items-center gap-8">
                    <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-cube text-xl"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xl font-black tracking-tight text-slate-900 leading-none group-hover:text-brand-600 transition-colors"><?= APP_NAME ?></span>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mt-0.5">E-Commerce</span>
                        </div>
                    </a>

                    <!-- Desktop Nav Links -->
                    <nav class="hidden lg:flex items-center gap-6 text-sm font-semibold text-slate-700">
                        <a href="<?= BASE_URL ?>/index.php" class="hover:text-brand-600 transition-colors">Home</a>
                        <a href="<?= BASE_URL ?>/products.php" class="hover:text-brand-600 transition-colors">All Products</a>
                        
                        <!-- Categories Dropdown -->
                        <div class="relative group">
                            <button type="button" class="flex items-center gap-1.5 hover:text-brand-600 transition-colors py-2">
                                <span>Categories</span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></i>
                            </button>
                            <div class="absolute left-0 top-full hidden group-hover:block w-64 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-50 animate-fadeIn">
                                <?php foreach ($navCategories as $cat): ?>
                                    <a href="<?= BASE_URL ?>/products.php?category=<?= e($cat['slug']) ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 text-slate-700 hover:text-brand-600 transition-colors text-sm">
                                        <i class="fa-solid <?= e($cat['icon'] ?: 'fa-tag') ?> text-brand-500 w-5 text-center"></i>
                                        <span><?= e($cat['name']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <a href="<?= BASE_URL ?>/products.php?featured=1" class="hover:text-brand-600 transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-bolt text-amber-500"></i> Deals
                        </a>
                    </nav>
                </div>

                <!-- Search Bar -->
                <div class="hidden md:flex flex-1 max-w-md mx-4">
                    <form action="<?= BASE_URL ?>/products.php" method="GET" class="w-full relative">
                        <input type="text" name="q" value="<?= e($currentQuery) ?>" placeholder="Search 1,000+ products, electronics, apparel..." class="w-full pl-11 pr-4 py-2.5 rounded-full bg-slate-100 hover:bg-slate-100/80 focus:bg-white text-sm text-slate-800 placeholder-slate-400 border border-transparent focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all outline-none">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <?php if (!empty($activeCategorySlug)): ?>
                            <input type="hidden" name="category" value="<?= e($activeCategorySlug) ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Right Action Buttons (Cart, Account, Mobile Toggle) -->
                <div class="flex items-center gap-3">
                    
                    <!-- Cart Button -->
                    <a href="<?= BASE_URL ?>/cart.php" class="relative flex items-center justify-center w-11 h-11 rounded-xl bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-600 transition-colors group">
                        <i class="fa-solid fa-bag-shopping text-lg"></i>
                        <span class="cart-badge-count <?= $cartCount > 0 ? 'inline-flex' : 'hidden' ?> absolute -top-1.5 -right-1.5 items-center justify-center min-w-[20px] h-5 px-1 rounded-full text-[11px] font-bold bg-brand-600 text-white shadow-sm ring-2 ring-white">
                            <?= $cartCount ?>
                        </span>
                    </a>

                    <!-- User Account / Auth Dropdown -->
                    <div class="relative">
                        <?php if ($currentUser): ?>
                            <button type="button" data-dropdown-toggle="user-dropdown" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-slate-800 transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                                </div>
                                <span class="hidden sm:inline font-semibold text-sm max-w-[100px] truncate"><?= e($currentUser['name']) ?></span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-500"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="user-dropdown" class="dropdown-menu hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 p-2 z-50">
                                <div class="px-3.5 py-2.5 border-b border-slate-100 mb-1">
                                    <p class="text-xs text-slate-400">Signed in as</p>
                                    <p class="text-sm font-bold text-slate-900 truncate"><?= e($currentUser['email']) ?></p>
                                    <?php if ($currentUser['role'] === 'admin'): ?>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 uppercase">Administrator</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($currentUser['role'] === 'admin'): ?>
                                    <a href="<?= BASE_URL ?>/admin/index.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 transition-colors mb-1">
                                        <i class="fa-solid fa-gauge-high text-purple-600"></i>
                                        <span>Admin Control Panel</span>
                                    </a>
                                <?php endif; ?>

                                <a href="<?= BASE_URL ?>/account.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-slate-700 hover:bg-slate-50 hover:text-brand-600 transition-colors">
                                    <i class="fa-solid fa-user text-slate-400"></i>
                                    <span>My Account & Orders</span>
                                </a>
                                <a href="<?= BASE_URL ?>/account.php?tab=orders" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-slate-700 hover:bg-slate-50 hover:text-brand-600 transition-colors">
                                    <i class="fa-solid fa-box text-slate-400"></i>
                                    <span>Order History</span>
                                </a>
                                <div class="my-1 border-t border-slate-100"></div>
                                <a href="<?= BASE_URL ?>/logout.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-rose-600 hover:bg-rose-50 transition-colors">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                    <span>Sign Out</span>
                                </a>
                            </div>

                        <?php else: ?>
                            <div class="flex items-center gap-2">
                                <a href="<?= BASE_URL ?>/login.php" class="text-sm font-semibold text-slate-700 hover:text-brand-600 px-3 py-2 transition-colors">Sign In</a>
                                <a href="<?= BASE_URL ?>/register.php" class="hidden sm:inline-flex items-center justify-center px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm shadow-brand-500/20 transition-all hover:scale-[1.02]">
                                    Sign Up
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile Menu Hamburger -->
                    <button type="button" id="mobile-menu-btn" class="lg:hidden flex items-center justify-center w-11 h-11 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Search Bar -->
            <div class="md:hidden pb-4">
                <form action="<?= BASE_URL ?>/products.php" method="GET" class="relative">
                    <input type="text" name="q" value="<?= e($currentQuery) ?>" placeholder="Search products..." class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-100 text-sm text-slate-800 placeholder-slate-400 border-none focus:ring-2 focus:ring-brand-500 outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                </form>
            </div>
        </div>

        <!-- Mobile Drawer Navigation -->
        <div id="mobile-menu-drawer" class="hidden lg:hidden border-t border-slate-200 bg-white px-4 py-6 space-y-4">
            <div class="flex flex-col space-y-2 font-medium">
                <a href="<?= BASE_URL ?>/index.php" class="px-3 py-2 rounded-lg hover:bg-slate-100">Home</a>
                <a href="<?= BASE_URL ?>/products.php" class="px-3 py-2 rounded-lg hover:bg-slate-100">All Products</a>
                <a href="<?= BASE_URL ?>/products.php?featured=1" class="px-3 py-2 rounded-lg hover:bg-slate-100 text-amber-600 font-semibold">Special Deals</a>
            </div>
            <div class="pt-4 border-t border-slate-100">
                <p class="px-3 text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Categories</p>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($navCategories as $cat): ?>
                        <a href="<?= BASE_URL ?>/products.php?category=<?= e($cat['slug']) ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-50 text-sm text-slate-700">
                            <i class="fa-solid <?= e($cat['icon'] ?: 'fa-tag') ?> text-brand-500 text-xs"></i>
                            <span class="truncate"><?= e($cat['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (!$currentUser): ?>
                <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">
                    <a href="<?= BASE_URL ?>/login.php" class="w-full text-center py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700">Sign In</a>
                    <a href="<?= BASE_URL ?>/register.php" class="w-full text-center py-2.5 rounded-xl bg-brand-600 text-white text-sm font-semibold">Create Account</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Global Flash Toast Message Container -->
    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?= addslashes(e($flash['message'])) ?>", "<?= e($flash['type']) ?>");
            });
        </script>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main class="flex-1">
