<?php
/**
 * Product Catalog & Advanced Search / Filtering
 */
$pageTitle = 'Products Catalog - NovaStore';
require_once __DIR__ . '/config/config.php';

$pdo = db();

// Input Filters
$search = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$minPrice = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$maxPrice = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$inStockOnly = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';
$featuredOnly = isset($_GET['featured']) && $_GET['featured'] === '1';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Base query builder
$whereClauses = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$selectedCategory = null;
if (!empty($categorySlug)) {
    $catStmt = $pdo->prepare("SELECT id, name, slug FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
    $catStmt->execute([$categorySlug]);
    $selectedCategory = $catStmt->fetch();
    if ($selectedCategory) {
        $whereClauses[] = "p.category_id = ?";
        $params[] = $selectedCategory['id'];
    }
}

if ($minPrice !== false && $minPrice !== null && $minPrice >= 0) {
    $whereClauses[] = "COALESCE(p.sale_price, p.price) >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== false && $maxPrice !== null && $maxPrice > 0) {
    $whereClauses[] = "COALESCE(p.sale_price, p.price) <= ?";
    $params[] = $maxPrice;
}

if ($inStockOnly) {
    $whereClauses[] = "p.stock_quantity > 0";
}

if ($featuredOnly) {
    $whereClauses[] = "p.featured = 1";
}

// Sorting logic
$orderBy = "p.created_at DESC";
switch ($sort) {
    case 'price_asc':
        $orderBy = "COALESCE(p.sale_price, p.price) ASC";
        break;
    case 'price_desc':
        $orderBy = "COALESCE(p.sale_price, p.price) DESC";
        break;
    case 'name_asc':
        $orderBy = "p.name ASC";
        break;
    case 'name_desc':
        $orderBy = "p.name DESC";
        break;
    case 'popular':
        $orderBy = "p.featured DESC, p.created_at DESC";
        break;
    default:
        $orderBy = "p.created_at DESC";
        break;
}

$whereSql = implode(" AND ", $whereClauses);

// Count total matching items
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE {$whereSql}");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Fetch paginated products
$productQuery = "
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE {$whereSql} 
    ORDER BY {$orderBy} 
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($productQuery);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch all active categories for sidebar
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.status = 'active'
    GROUP BY c.id 
    ORDER BY c.name ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-slate-100/60 border-b border-slate-200/80 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex text-xs font-semibold text-slate-500 mb-3 space-x-2">
            <a href="<?= BASE_URL ?>/index.php" class="hover:text-brand-600">Home</a>
            <span>/</span>
            <span class="text-slate-900 font-bold"><?= $selectedCategory ? e($selectedCategory['name']) : 'All Products' ?></span>
        </nav>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">
            <?= $selectedCategory ? e($selectedCategory['name']) : 'Explore Our Catalog' ?>
        </h1>
        <p class="text-slate-500 text-sm mt-1">Showing <?= $totalProducts ?> curated products available for instant order</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Sidebar Filter Column -->
        <aside class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-6">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-brand-600"></i>
                        <span>Filters</span>
                    </h3>
                    <?php if (!empty($search) || !empty($categorySlug) || $minPrice || $maxPrice || $inStockOnly || $featuredOnly): ?>
                        <a href="<?= BASE_URL ?>/products.php" class="text-xs font-semibold text-rose-600 hover:underline">Reset All</a>
                    <?php endif; ?>
                </div>

                <form action="<?= BASE_URL ?>/products.php" method="GET" class="space-y-6">
                    
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="q" value="<?= e($search) ?>">
                    <?php endif; ?>

                    <!-- Categories Filter -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Category</label>
                        <div class="space-y-1.5">
                            <label class="flex items-center justify-between text-sm text-slate-700 hover:text-brand-600 cursor-pointer p-1.5 rounded-lg hover:bg-slate-50">
                                <span class="flex items-center gap-2">
                                    <input type="radio" name="category" value="" <?= empty($categorySlug) ? 'checked' : '' ?> onchange="this.form.submit()" class="text-brand-600 focus:ring-brand-500">
                                    <span class="<?= empty($categorySlug) ? 'font-bold text-brand-600' : '' ?>">All Categories</span>
                                </span>
                                <span class="text-xs text-slate-400 font-medium"><?= array_sum(array_column($categories, 'product_count')) ?></span>
                            </label>
                            
                            <?php foreach ($categories as $cat): ?>
                                <label class="flex items-center justify-between text-sm text-slate-700 hover:text-brand-600 cursor-pointer p-1.5 rounded-lg hover:bg-slate-50">
                                    <span class="flex items-center gap-2">
                                        <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'checked' : '' ?> onchange="this.form.submit()" class="text-brand-600 focus:ring-brand-500">
                                        <span class="<?= $categorySlug === $cat['slug'] ? 'font-bold text-brand-600' : '' ?>"><?= e($cat['name']) ?></span>
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium"><?= $cat['product_count'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Price Range ($)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" value="<?= e($minPrice ?? '') ?>" placeholder="Min" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                            <input type="number" name="max_price" value="<?= e($maxPrice ?? '') ?>" placeholder="Max" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="pt-4 border-t border-slate-100 space-y-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500">
                            <span>In Stock Only</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="featured" value="1" <?= $featuredOnly ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500">
                            <span>Featured & Deals Only</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-brand-600 text-white font-bold text-sm transition-colors shadow-sm">
                        Apply Filters
                    </button>
                </form>
            </div>
        </aside>

        <!-- Product Grid Column -->
        <main class="lg:col-span-3 space-y-6">
            
            <!-- Top Controls (Sorting & Results Count) -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="text-sm text-slate-600">
                    Showing <span class="font-bold text-slate-900"><?= $totalProducts > 0 ? $offset + 1 : 0 ?> - <?= min($offset + $perPage, $totalProducts) ?></span> of <span class="font-bold text-slate-900"><?= $totalProducts ?></span> items
                    <?php if (!empty($search)): ?>
                        for query "<span class="font-semibold text-brand-600"><?= e($search) ?></span>"
                    <?php endif; ?>
                </div>

                <!-- Sorting Dropdown -->
                <form action="<?= BASE_URL ?>/products.php" method="GET" class="flex items-center gap-2">
                    <?php if (!empty($search)): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
                    <?php if (!empty($categorySlug)): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
                    <?php if ($minPrice): ?><input type="hidden" name="min_price" value="<?= e($minPrice) ?>"><?php endif; ?>
                    <?php if ($maxPrice): ?><input type="hidden" name="max_price" value="<?= e($maxPrice) ?>"><?php endif; ?>
                    <?php if ($inStockOnly): ?><input type="hidden" name="in_stock" value="1"><?php endif; ?>
                    <?php if ($featuredOnly): ?><input type="hidden" name="featured" value="1"><?php endif; ?>

                    <label for="sort" class="text-xs font-semibold text-slate-500 shrink-0">Sort By:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()" class="text-sm rounded-xl border-slate-200 py-1.5 pl-3 pr-8 font-medium text-slate-700 focus:border-brand-500 focus:ring-brand-500">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Popularity</option>
                    </select>
                </form>
            </div>

            <!-- Products List -->
            <?php if (empty($products)): ?>
                <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 text-2xl mb-4">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">No products found</h3>
                    <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">Try broadening your search criteria or resetting filters to view all available inventory.</p>
                    <a href="<?= BASE_URL ?>/products.php" class="inline-flex items-center gap-2 mt-6 px-6 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-sm hover:bg-brand-700 transition-colors">
                        <span>Reset Filters</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card bg-white rounded-3xl border border-slate-200/80 overflow-hidden flex flex-col group shadow-sm hover:shadow-xl transition-all">
                            
                            <!-- Product Image & Badges -->
                            <div class="relative h-60 bg-slate-100 overflow-hidden">
                                <img src="<?= e(get_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                
                                <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-20">
                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-rose-500 text-white shadow-sm">
                                            -<?= round((($product['price'] - $product['sale_price'])/$product['price'])*100) ?>% OFF
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <a href="<?= BASE_URL ?>/product.php?id=<?= $product['id'] ?>" class="absolute inset-0 z-10"></a>
                            </div>

                            <!-- Product Info -->
                            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                                <div>
                                    <span class="text-xs font-semibold text-brand-600"><?= e($product['category_name']) ?></span>
                                    <h3 class="text-base font-bold text-slate-900 mt-1 line-clamp-1 group-hover:text-brand-600 transition-colors">
                                        <a href="<?= BASE_URL ?>/product.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2"><?= e($product['short_description']) ?></p>
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <div>
                                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                            <div class="text-lg font-black text-slate-900"><?= format_price($product['sale_price']) ?></div>
                                            <div class="text-xs text-slate-400 line-through"><?= format_price($product['price']) ?></div>
                                        <?php else: ?>
                                            <div class="text-lg font-black text-slate-900"><?= format_price($product['price']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button type="button" data-product-id="<?= $product['id'] ?>" class="btn-add-to-cart relative z-20 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-brand-600 text-white text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                            <span>Add</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs font-bold">Sold Out</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Bar -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-center gap-2 pt-8">
                        <?php 
                        $queryParams = $_GET;
                        $prevPage = $page - 1;
                        $nextPage = $page + 1;
                        ?>

                        <?php if ($page > 1): ?>
                            <?php $queryParams['page'] = $prevPage; ?>
                            <a href="<?= BASE_URL ?>/products.php?<?= http_build_query($queryParams) ?>" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-bold text-slate-700 hover:bg-brand-50 hover:text-brand-600 transition-colors shadow-sm">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php $queryParams['page'] = $i; ?>
                            <a href="<?= BASE_URL ?>/products.php?<?= http_build_query($queryParams) ?>" class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-all <?= $i === $page ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <?php $queryParams['page'] = $nextPage; ?>
                            <a href="<?= BASE_URL ?>/products.php?<?= http_build_query($queryParams) ?>" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-bold text-slate-700 hover:bg-brand-50 hover:text-brand-600 transition-colors shadow-sm">
                                Next <i class="fa-solid fa-chevron-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </main>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
