<?php
/**
 * Modern Multi-Category E-Commerce Platform - Homepage
 */
$pageTitle = 'NovaStore - Modern Multi-Category Online Shopping';
require_once __DIR__ . '/config/config.php';

$pdo = db();

// Fetch Categories with product counts
$catStmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.status = 'active' 
    GROUP BY c.id 
    ORDER BY c.name ASC 
    LIMIT 6
");
$featuredCategories = $catStmt->fetchAll();

// Fetch Flash Deals (Products with sale_price)
$dealsStmt = $pdo->query("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' AND p.sale_price IS NOT NULL AND p.sale_price < p.price 
    ORDER BY p.id DESC 
    LIMIT 4
");
$flashDeals = $dealsStmt->fetchAll();

// Fetch Featured & Trending Products
$featuredStmt = $pdo->query("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' AND p.featured = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
");
$featuredProducts = $featuredStmt->fetchAll();

// Fetch Latest Arrivals
$latestStmt = $pdo->query("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.id DESC 
    LIMIT 8
");
$latestProducts = $latestStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Section -->
<section class="relative overflow-hidden bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-white py-16 lg:py-24">
    <!-- Glow Background Accents -->
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-brand-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Hero Content -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 text-xs font-semibold text-brand-300 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>New 2026 Collection Released</span>
                </div>
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-[1.1]">
                    Next-Gen Tech & <br class="hidden sm:block">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-indigo-300 to-cyan-300">Everyday Luxury</span>
                </h1>
                
                <p class="text-slate-300 text-base sm:text-lg max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Discover cutting-edge audio gear, tailored apparel, ergonomic office upgrades, and premium lifestyle essentials designed for those who demand excellence.
                </p>

                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                    <a href="<?= BASE_URL ?>/products.php" class="px-8 py-4 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-base shadow-xl shadow-brand-600/30 hover:scale-105 transition-all flex items-center gap-2">
                        <span>Explore Catalog</span>
                        <i class="fa-solid fa-arrow-right text-sm"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/products.php?featured=1" class="px-8 py-4 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-base border border-white/20 backdrop-blur-md transition-all">
                        View Flash Deals
                    </a>
                </div>

                <!-- Metrics / Badges -->
                <div class="grid grid-cols-3 gap-6 pt-6 border-t border-white/10 max-w-md mx-auto lg:mx-0">
                    <div>
                        <p class="text-2xl font-black text-white">100%</p>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Authentic Gear</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-white">2-Day</p>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Fast Dispatch</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-white">4.9 ★</p>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Customer Rating</p>
                    </div>
                </div>
            </div>

            <!-- Hero Visual Feature Card -->
            <div class="lg:col-span-5">
                <div class="relative mx-auto max-w-md lg:max-w-none">
                    <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-white/10 bg-slate-800/80 backdrop-blur-md group">
                        <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80" alt="Hero Product" class="w-full h-96 object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                        <div class="absolute bottom-0 inset-x-0 p-6 sm:p-8 space-y-2">
                            <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold bg-amber-500 text-slate-950">Special Promo</span>
                            <h3 class="text-xl sm:text-2xl font-black text-white">Aura ANC Wireless Headphones</h3>
                            <p class="text-xs text-slate-300">Spatial audio with 40-hr battery life</p>
                            <div class="flex items-center justify-between pt-2">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-white">$199.99</span>
                                    <span class="text-sm text-slate-400 line-through">$249.99</span>
                                </div>
                                <a href="<?= BASE_URL ?>/product.php?id=1" class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-white font-bold text-xs uppercase tracking-wider transition-colors">
                                    Shop Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Trust & Feature Badges Bar -->
<section class="border-b border-slate-200 bg-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="flex items-center gap-4 p-3">
                <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 text-xl">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Free Express Delivery</h4>
                    <p class="text-xs text-slate-500 mt-0.5">On all orders over $100</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-xl">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900">2-Year Warranty</h4>
                    <p class="text-xs text-slate-500 mt-0.5">100% replacement guarantee</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 text-xl">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900">30-Day Free Returns</h4>
                    <p class="text-xs text-slate-500 mt-0.5">No questions asked policy</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 text-xl">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900">24/7 Expert Support</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Dedicated live agent care</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Categories -->
<section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
        <div>
            <span class="text-xs font-bold text-brand-600 uppercase tracking-widest">Browse Departments</span>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight mt-1">Featured Categories</h2>
        </div>
        <a href="<?= BASE_URL ?>/products.php" class="inline-flex items-center gap-1.5 text-sm font-bold text-brand-600 hover:text-brand-700">
            <span>View All Categories</span>
            <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 sm:gap-6">
        <?php foreach ($featuredCategories as $cat): ?>
            <a href="<?= BASE_URL ?>/products.php?category=<?= e($cat['slug']) ?>" class="group relative rounded-2xl overflow-hidden bg-white border border-slate-200/80 p-4 flex flex-col items-center text-center hover:border-brand-500 hover:shadow-lg transition-all duration-300">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden mb-3 bg-slate-100 flex items-center justify-center">
                    <img src="<?= e(get_image_url($cat['image'], 'category')) ?>" alt="<?= e($cat['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                </div>
                <h3 class="text-sm font-bold text-slate-900 group-hover:text-brand-600 transition-colors line-clamp-1"><?= e($cat['name']) ?></h3>
                <p class="text-xs text-slate-400 mt-1"><?= $cat['product_count'] ?> Products</p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Flash Deals & Limited Offers Section -->
<?php if (!empty($flashDeals)): ?>
<section class="py-12 bg-slate-900 text-white my-8 rounded-3xl max-w-7xl mx-auto px-6 sm:px-10 lg:px-12 relative overflow-hidden">
    <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 mb-10 pb-6 border-b border-white/10">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-rose-600 text-white tracking-wider uppercase animate-pulse">
                    <i class="fa-solid fa-fire mr-1"></i> Flash Sale
                </span>
                <span class="text-xs text-slate-400">Save up to 35% this week</span>
            </div>
            <h2 class="text-3xl font-black text-white tracking-tight">Today's Hottest Deals</h2>
        </div>

        <!-- Simulated Countdown Clock -->
        <div class="flex items-center gap-2 text-center text-xs font-bold uppercase tracking-wider">
            <div class="bg-white/10 px-3 py-2 rounded-xl border border-white/10"><span class="text-lg font-black text-brand-300">14</span><br>Hours</div>
            <span class="text-brand-400 font-bold">:</span>
            <div class="bg-white/10 px-3 py-2 rounded-xl border border-white/10"><span class="text-lg font-black text-brand-300">42</span><br>Mins</div>
            <span class="text-brand-400 font-bold">:</span>
            <div class="bg-white/10 px-3 py-2 rounded-xl border border-white/10"><span class="text-lg font-black text-brand-300">18</span><br>Secs</div>
        </div>
    </div>

    <!-- Deals Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($flashDeals as $deal): 
            $discountPct = round((($deal['price'] - $deal['sale_price']) / $deal['price']) * 100);
        ?>
            <div class="product-card bg-slate-800/90 rounded-2xl border border-white/10 overflow-hidden flex flex-col group">
                <div class="relative h-56 bg-slate-900 overflow-hidden">
                    <img src="<?= e(get_image_url($deal['image'])) ?>" alt="<?= e($deal['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-xs font-extrabold bg-rose-600 text-white shadow-md">
                        -<?= $discountPct ?>% OFF
                    </span>
                    <a href="<?= BASE_URL ?>/product.php?id=<?= $deal['id'] ?>" class="absolute inset-0 z-10"></a>
                </div>

                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <span class="text-[11px] font-semibold text-brand-400 uppercase tracking-wider"><?= e($deal['category_name']) ?></span>
                        <h3 class="text-base font-bold text-white mt-1 group-hover:text-brand-400 transition-colors line-clamp-1">
                            <a href="<?= BASE_URL ?>/product.php?id=<?= $deal['id'] ?>"><?= e($deal['name']) ?></a>
                        </h3>
                    </div>

                    <div class="pt-2 border-t border-white/10 flex items-center justify-between">
                        <div>
                            <div class="text-lg font-black text-white"><?= format_price($deal['sale_price']) ?></div>
                            <div class="text-xs text-slate-400 line-through"><?= format_price($deal['price']) ?></div>
                        </div>
                        <button type="button" data-product-id="<?= $deal['id'] ?>" class="btn-add-to-cart relative z-20 w-10 h-10 rounded-xl bg-brand-500 hover:bg-brand-400 text-white flex items-center justify-center transition-all shadow-md">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Best Selling & Featured Products Grid -->
<section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
        <div>
            <span class="text-xs font-bold text-brand-600 uppercase tracking-widest">Handpicked Selections</span>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight mt-1">Featured Products</h2>
        </div>
        <a href="<?= BASE_URL ?>/products.php" class="inline-flex items-center gap-1.5 text-sm font-bold text-brand-600 hover:text-brand-700">
            <span>Explore All <?= count($latestProducts) ?>+ Products</span>
            <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card bg-white rounded-3xl border border-slate-200/80 overflow-hidden flex flex-col group shadow-sm hover:shadow-xl transition-all">
                
                <!-- Product Thumbnail & Badges -->
                <div class="relative h-64 bg-slate-100 overflow-hidden">
                    <img src="<?= e(get_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    
                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-20">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-rose-500 text-white shadow-sm">
                                SALE
                            </span>
                        <?php endif; ?>
                        <?php if ($product['featured']): ?>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-brand-600 text-white shadow-sm">
                                FEATURED
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Stock indicator -->
                    <?php if ($product['stock_quantity'] <= 5 && $product['stock_quantity'] > 0): ?>
                        <span class="absolute bottom-3 left-3 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-slate-950 z-20">
                            Only <?= $product['stock_quantity'] ?> left!
                        </span>
                    <?php elseif ($product['stock_quantity'] == 0): ?>
                        <span class="absolute bottom-3 left-3 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white z-20">
                            Out of Stock
                        </span>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/product.php?id=<?= $product['id'] ?>" class="absolute inset-0 z-10"></a>
                </div>

                <!-- Product Info -->
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <a href="<?= BASE_URL ?>/products.php?category=<?= e($product['category_slug']) ?>" class="text-xs font-semibold text-brand-600 hover:underline"><?= e($product['category_name']) ?></a>
                        <h3 class="text-base font-bold text-slate-900 mt-1 line-clamp-2 group-hover:text-brand-600 transition-colors">
                            <a href="<?= BASE_URL ?>/product.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 line-clamp-2"><?= e($product['short_description']) ?></p>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <span class="text-xl font-black text-slate-900"><?= format_price($product['sale_price']) ?></span>
                                <span class="text-xs text-slate-400 line-through ml-1.5"><?= format_price($product['price']) ?></span>
                            <?php else: ?>
                                <span class="text-xl font-black text-slate-900"><?= format_price($product['price']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($product['stock_quantity'] > 0): ?>
                            <button type="button" data-product-id="<?= $product['id'] ?>" class="btn-add-to-cart relative z-20 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-brand-600 text-white text-xs font-bold flex items-center gap-2 transition-colors shadow-sm">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <span>Add</span>
                            </button>
                        <?php else: ?>
                            <button type="button" disabled class="px-3 py-2 rounded-xl bg-slate-200 text-slate-400 text-xs font-bold cursor-not-allowed">
                                Sold Out
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Promotional Call-to-Action Split Banner -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Promo 1 -->
        <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-indigo-900 to-brand-900 text-white p-8 sm:p-10 flex flex-col justify-between min-h-[280px]">
            <div class="space-y-2 relative z-10 max-w-xs">
                <span class="text-xs font-bold uppercase tracking-widest text-brand-300">Modern Workspace</span>
                <h3 class="text-2xl sm:text-3xl font-black">Ergonomic Office Chairs & Desks</h3>
                <p class="text-slate-300 text-sm">Enhance your productivity with certified spinal posture gear.</p>
            </div>
            <div class="pt-6 relative z-10">
                <a href="<?= BASE_URL ?>/products.php?category=home-living" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-slate-900 hover:bg-slate-100 text-xs font-bold uppercase tracking-wider transition-all">
                    <span>Shop Workspace</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <i class="fa-solid fa-chair absolute -bottom-6 -right-6 text-9xl text-white/5 pointer-events-none"></i>
        </div>

        <!-- Promo 2 -->
        <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-slate-900 to-slate-800 text-white p-8 sm:p-10 flex flex-col justify-between min-h-[280px]">
            <div class="space-y-2 relative z-10 max-w-xs">
                <span class="text-xs font-bold uppercase tracking-widest text-amber-400">Smart Wearables</span>
                <h3 class="text-2xl sm:text-3xl font-black">Fitness AMOLED GPS Watches</h3>
                <p class="text-slate-300 text-sm">Real-time health telemetry with 7-day battery stamina.</p>
            </div>
            <div class="pt-6 relative z-10">
                <a href="<?= BASE_URL ?>/products.php?category=electronics-gadgets" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold uppercase tracking-wider transition-all">
                    <span>Discover Gadgets</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <i class="fa-solid fa-stopwatch absolute -bottom-6 -right-6 text-9xl text-white/5 pointer-events-none"></i>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
