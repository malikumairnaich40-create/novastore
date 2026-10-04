<?php
/**
 * Admin Panel - Product Management List
 */
$adminTitle = 'Product Inventory - NovaStore';
$activePage = 'products';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$search = trim($_GET['q'] ?? '');
$filterCategory = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$stockFilter = $_GET['stock'] ?? '';

// Build Query
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($filterCategory) {
    $where[] = "p.category_id = ?";
    $params[] = $filterCategory;
}

if ($stockFilter === 'low') {
    $where[] = "p.stock_quantity <= 5 AND p.stock_quantity > 0";
} elseif ($stockFilter === 'out') {
    $where[] = "p.stock_quantity = 0";
}

$whereSql = implode(" AND ", $where);

// Fetch categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Fetch products
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE {$whereSql} 
    ORDER BY p.id DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="space-y-8">
    
    <!-- Top Bar with Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">Product Inventory</h2>
            <p class="text-slate-400 text-sm mt-1">Manage catalog listings, pricing, stock levels, and media</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/product-add.php" class="px-5 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add New Product</span>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form action="<?= BASE_URL ?>/admin/products.php" method="GET" class="w-full md:w-auto flex-1 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name or SKU..." class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:border-brand-500 outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            </div>

            <select name="category_id" onchange="this.form.submit()" class="py-2 pl-3 pr-8 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-xs focus:border-brand-500 outline-none">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="stock" onchange="this.form.submit()" class="py-2 pl-3 pr-8 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-xs focus:border-brand-500 outline-none">
                <option value="">All Stock Levels</option>
                <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Stock (<= 5)</option>
                <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of Stock (0)</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition-colors">Filter</button>
            <?php if (!empty($search) || $filterCategory || !empty($stockFilter)): ?>
                <a href="<?= BASE_URL ?>/admin/products.php" class="text-xs text-rose-400 hover:underline">Reset</a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-slate-500 font-semibold shrink-0">Showing <?= count($products) ?> items</span>
    </div>

    <!-- Products Table -->
    <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Product</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Price</th>
                        <th class="px-6 py-4">Stock</th>
                        <th class="px-6 py-4">Featured</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">No products found matching your filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= e(get_image_url($p['image'])) ?>" alt="" class="w-12 h-12 object-cover rounded-xl bg-slate-900 border border-slate-800 shrink-0">
                                        <div>
                                            <h4 class="font-bold text-white line-clamp-1"><?= e($p['name']) ?></h4>
                                            <span class="text-[11px] font-mono text-slate-500"><?= e($p['sku'] ?: 'No SKU') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-brand-400"><?= e($p['category_name']) ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                        <div class="font-black text-white"><?= format_price($p['sale_price']) ?></div>
                                        <div class="text-[10px] text-slate-500 line-through"><?= format_price($p['price']) ?></div>
                                    <?php else: ?>
                                        <div class="font-black text-white"><?= format_price($p['price']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($p['stock_quantity'] > 5): ?>
                                        <span class="text-xs font-bold text-emerald-400"><?= $p['stock_quantity'] ?> units</span>
                                    <?php elseif ($p['stock_quantity'] > 0): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                            <?= $p['stock_quantity'] ?> Low
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($p['featured']): ?>
                                        <span class="text-amber-400 font-bold text-xs"><i class="fa-solid fa-star mr-1"></i> Yes</span>
                                    <?php else: ?>
                                        <span class="text-slate-600 text-xs">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold border <?= $p['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700' ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= BASE_URL ?>/product.php?id=<?= $p['id'] ?>" target="_blank" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors" title="View Live">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $p['id'] ?>" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors" title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>
                                        <form action="<?= BASE_URL ?>/admin/product-delete.php" method="POST" onsubmit="return confirm('Delete product <?= addslashes(e($p['name'])) ?>?');" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors" title="Delete">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
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
