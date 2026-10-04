<?php
/**
 * Product Details Page
 */
require_once __DIR__ . '/config/config.php';

$pdo = db();
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$slug = trim($_GET['slug'] ?? '');

if (!$productId && empty($slug)) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

if ($productId) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$productId]);
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.slug = ? AND p.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
}

$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'The requested product could not be found.');
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

$pageTitle = e($product['name']) . ' - NovaStore';

// Decode gallery images
$gallery = [];
if (!empty($product['gallery_images'])) {
    $decoded = json_decode($product['gallery_images'], true);
    if (is_array($decoded)) {
        $gallery = $decoded;
    }
}
// Add main image to gallery list as first item
array_unshift($gallery, $product['image']);
$gallery = array_unique($gallery);

// Fetch Related Products from same category
$relStmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
    LIMIT 4
");
$relStmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $relStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="bg-white border-b border-slate-200/80 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center text-xs font-semibold text-slate-500 space-x-2">
            <a href="<?= BASE_URL ?>/index.php" class="hover:text-brand-600">Home</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/products.php" class="hover:text-brand-600">Products</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/products.php?category=<?= e($product['category_slug']) ?>" class="hover:text-brand-600"><?= e($product['category_name']) ?></a>
            <span>/</span>
            <span class="text-slate-900 font-bold truncate max-w-xs"><?= e($product['name']) ?></span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        
        <!-- Product Image Gallery Column (5 cols) -->
        <div class="lg:col-span-6 space-y-4">
            <!-- Main High-Res Image -->
            <div class="relative bg-white rounded-3xl overflow-hidden border border-slate-200/80 shadow-sm aspect-w-1 aspect-h-1 flex items-center justify-center p-4">
                <img id="main-product-image" src="<?= e(get_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="w-full h-96 sm:h-[480px] object-cover rounded-2xl transition-all duration-300">
                
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="absolute top-4 left-4 px-3 py-1.5 rounded-xl text-xs font-black bg-rose-500 text-white shadow-md">
                        SALE DISCOUNT
                    </span>
                <?php endif; ?>
            </div>

            <!-- Thumbnail Strip -->
            <?php if (count($gallery) > 1): ?>
                <div class="flex items-center gap-3 overflow-x-auto pb-2">
                    <?php foreach ($gallery as $idx => $imgUrl): ?>
                        <button type="button" data-src="<?= e(get_image_url($imgUrl)) ?>" class="gallery-thumbnail w-20 h-20 rounded-2xl overflow-hidden border-2 <?= $idx === 0 ? 'border-brand-600 ring-2 ring-brand-600/30' : 'border-slate-200 hover:border-slate-300' ?> shrink-0 bg-white transition-all">
                            <img src="<?= e(get_image_url($imgUrl)) ?>" alt="Thumbnail" class="w-full h-full object-cover">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Actions & Info Column (6 cols) -->
        <div class="lg:col-span-6 space-y-6">
            
            <div>
                <a href="<?= BASE_URL ?>/products.php?category=<?= e($product['category_slug']) ?>" class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3 py-1 rounded-full mb-3">
                    <i class="fa-solid fa-tag text-[10px]"></i>
                    <span><?= e($product['category_name']) ?></span>
                </a>
                
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    <?= e($product['name']) ?>
                </h1>

                <?php if ($product['sku']): ?>
                    <p class="text-xs font-semibold text-slate-400 mt-1">SKU: <span class="text-slate-600"><?= e($product['sku']) ?></span></p>
                <?php endif; ?>
            </div>

            <!-- Pricing Box -->
            <div class="flex items-baseline gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/60">
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="text-3xl sm:text-4xl font-black text-slate-900"><?= format_price($product['sale_price']) ?></span>
                    <span class="text-lg text-slate-400 line-through font-semibold"><?= format_price($product['price']) ?></span>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-800">
                        Save <?= format_price($product['price'] - $product['sale_price']) ?> (<?= round((($product['price'] - $product['sale_price'])/$product['price'])*100) ?>%)
                    </span>
                <?php else: ?>
                    <span class="text-3xl sm:text-4xl font-black text-slate-900"><?= format_price($product['price']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Stock Availability Badge -->
            <div class="flex items-center gap-3">
                <?php if ($product['stock_quantity'] > 5): ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        In Stock (<?= $product['stock_quantity'] ?> units available)
                    </span>
                <?php elseif ($product['stock_quantity'] > 0): ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Low Stock: Only <?= $product['stock_quantity'] ?> remaining!
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-700 bg-rose-50 px-3 py-1.5 rounded-xl border border-rose-200">
                        <i class="fa-solid fa-circle-xmark"></i>
                        Currently Out of Stock
                    </span>
                <?php endif; ?>
                
                <span class="text-xs text-slate-500 flex items-center gap-1">
                    <i class="fa-solid fa-truck-fast text-brand-500"></i> Dispatches in 24 hrs
                </span>
            </div>

            <!-- Short Description -->
            <?php if (!empty($product['short_description'])): ?>
                <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                    <?= e($product['short_description']) ?>
                </p>
            <?php endif; ?>

            <!-- Quantity & Add to Cart Controls -->
            <?php if ($product['stock_quantity'] > 0): ?>
                <div class="space-y-4 pt-2 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Select Quantity</label>
                    <div class="flex flex-wrap items-center gap-4">
                        
                        <!-- Dynamic Quantity Stepper -->
                        <div class="flex items-center bg-slate-100 rounded-2xl border border-slate-200 p-1">
                            <button type="button" class="qty-btn-minus w-10 h-10 rounded-xl bg-white hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition-colors shadow-sm">
                                <i class="fa-solid fa-minus text-xs"></i>
                            </button>
                            <input type="number" id="product-detail-qty" data-product-qty="<?= $product['id'] ?>" value="1" min="1" max="<?= $product['stock_quantity'] ?>" class="w-14 text-center font-bold text-slate-900 bg-transparent border-none focus:ring-0 text-base">
                            <button type="button" class="qty-btn-plus w-10 h-10 rounded-xl bg-white hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition-colors shadow-sm">
                                <i class="fa-solid fa-plus text-xs"></i>
                            </button>
                        </div>

                        <!-- Add to Cart AJAX Button -->
                        <button type="button" data-product-id="<?= $product['id'] ?>" class="btn-add-to-cart flex-1 min-w-[200px] py-4 px-6 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm sm:text-base shadow-xl shadow-brand-600/20 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-bag-shopping"></i>
                            <span>Add to Cart</span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-4 rounded-2xl bg-slate-100 border border-slate-200 text-slate-600 text-sm font-medium">
                    This item is temporarily out of stock. Check back soon or browse similar items below.
                </div>
            <?php endif; ?>

            <!-- Trust Features List -->
            <div class="grid grid-cols-2 gap-3 pt-6 border-t border-slate-100 text-xs text-slate-600">
                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50">
                    <i class="fa-solid fa-truck text-brand-500 text-base"></i>
                    <span>Free Shipping on Orders > <?= format_price(FREE_SHIPPING_THRESHOLD) ?></span>
                </div>
                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50">
                    <i class="fa-solid fa-rotate-left text-brand-500 text-base"></i>
                    <span>30-Day Hassle-Free Returns</span>
                </div>
                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50">
                    <i class="fa-solid fa-shield-halved text-brand-500 text-base"></i>
                    <span>2-Year Authentic Warranty</span>
                </div>
                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50">
                    <i class="fa-solid fa-credit-card text-brand-500 text-base"></i>
                    <span>COD & Card Payments</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Product Description & Specifications Tabs -->
    <div class="mt-16 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-10 shadow-sm space-y-6">
        <h2 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-4">Product Details & Specifications</h2>
        <div class="prose max-w-none text-slate-600 text-sm sm:text-base leading-relaxed">
            <?= nl2br(e($product['description'] ?: $product['short_description'])) ?>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-16">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight mb-8">You Might Also Like</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($relatedProducts as $rel): ?>
                    <div class="product-card bg-white rounded-3xl border border-slate-200/80 overflow-hidden flex flex-col group shadow-sm hover:shadow-xl transition-all">
                        <div class="relative h-56 bg-slate-100 overflow-hidden">
                            <img src="<?= e(get_image_url($rel['image'])) ?>" alt="<?= e($rel['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <a href="<?= BASE_URL ?>/product.php?id=<?= $rel['id'] ?>" class="absolute inset-0 z-10"></a>
                        </div>
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <span class="text-xs font-semibold text-brand-600"><?= e($rel['category_name']) ?></span>
                                <h3 class="text-sm font-bold text-slate-900 mt-1 line-clamp-1 group-hover:text-brand-600 transition-colors">
                                    <a href="<?= BASE_URL ?>/product.php?id=<?= $rel['id'] ?>"><?= e($rel['name']) ?></a>
                                </h3>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-base font-black text-slate-900">
                                    <?= format_price($rel['sale_price'] ?: $rel['price']) ?>
                                </span>
                                <button type="button" data-product-id="<?= $rel['id'] ?>" class="btn-add-to-cart relative z-20 w-9 h-9 rounded-xl bg-slate-900 hover:bg-brand-600 text-white flex items-center justify-center text-xs transition-colors">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
