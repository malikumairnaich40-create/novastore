<?php
/**
 * Admin Panel - Add New Product
 */
$adminTitle = 'Add New Product - NovaStore';
$activePage = 'product-add';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$errors = [];

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid security token. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $sku = trim($_POST['sku'] ?? '') ?: ('SKU-' . strtoupper(bin2hex(random_bytes(3))));
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $salePrice = filter_input(INPUT_POST, 'sale_price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $shortDesc = trim($_POST['short_description'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    // Validations
    if (empty($name)) $errors[] = 'Product name is required.';
    if (!$categoryId) $errors[] = 'Please select a category.';
    if ($price === false || $price < 0) $errors[] = 'Please provide a valid regular price.';
    if ($stock === false || $stock < 0) $stock = 0;
    if ($salePrice !== false && $salePrice >= $price) {
        $errors[] = 'Sale price must be lower than the regular price.';
    }

    // Handle Image Upload
    if (!empty($_FILES['image_file']['name'])) {
        $uploadResult = upload_image($_FILES['image_file'], 'products');
        if ($uploadResult['success']) {
            $imageUrl = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }

    if (empty($imageUrl)) {
        $errors[] = 'Please upload a product image or provide an image URL.';
    }

    // Check slug uniqueness
    if (empty($errors)) {
        $slugCheck = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
        $slugCheck->execute([$slug]);
        if ($slugCheck->fetch()) {
            $slug .= '-' . time();
        }

        $insert = $pdo->prepare("
            INSERT INTO products (
                category_id, name, slug, sku, short_description, description, 
                price, sale_price, stock_quantity, featured, image, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?
            )
        ");

        $insert->execute([
            $categoryId,
            $name,
            $slug,
            $sku,
            $shortDesc,
            $desc,
            $price,
            $salePrice ?: null,
            $stock,
            $featured,
            $imageUrl,
            $status
        ]);

        set_flash('success', "Product \"{$name}\" was created successfully!");
        header('Location: ' . BASE_URL . '/admin/products.php');
        exit;
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <a href="<?= BASE_URL ?>/admin/products.php" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Products
            </a>
            <h2 class="text-2xl font-black text-white tracking-tight">Create New Product</h2>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
            <?php foreach ($errors as $err): ?>
                <p class="flex items-center gap-2"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/admin/product-add.php" method="POST" enctype="multipart/form-data" class="space-y-8">
        <?= csrf_field() ?>

        <!-- Basic Information Card -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6">
            <h3 class="font-bold text-white text-base border-b border-slate-800 pb-4">General Information</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Product Title *</label>
                    <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="e.g. Sony WH-1000XM5 Wireless Headphones" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Category *</label>
                    <select name="category_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">SKU Code (Auto if empty)</label>
                    <input type="text" name="sku" value="<?= e($_POST['sku'] ?? '') ?>" placeholder="e.g. SONY-WH-001" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Short Summary</label>
                    <input type="text" name="short_description" value="<?= e($_POST['short_description'] ?? '') ?>" placeholder="One-line summary for cards and search snippets" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Detailed Description & Specifications</label>
                    <textarea name="description" rows="5" placeholder="Full product specifications, dimensions, features, materials, and warranty information..." class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Pricing & Inventory Card -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6">
            <h3 class="font-bold text-white text-base border-b border-slate-800 pb-4">Pricing & Inventory</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Regular Price ($) *</label>
                    <input type="number" step="0.01" name="price" value="<?= e($_POST['price'] ?? '') ?>" required placeholder="249.99" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sale Price ($) (Optional)</label>
                    <input type="number" step="0.01" name="sale_price" value="<?= e($_POST['sale_price'] ?? '') ?>" placeholder="199.99" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Stock Quantity *</label>
                    <input type="number" name="stock_quantity" value="<?= e($_POST['stock_quantity'] ?? '10') ?>" required min="0" placeholder="10" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex flex-wrap items-center gap-8">
                <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                    <input type="checkbox" name="featured" value="1" <?= isset($_POST['featured']) ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-800 text-brand-600 focus:ring-brand-500">
                    <span>Mark as <strong>Featured Deal</strong></span>
                </label>

                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status:</label>
                    <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs">
                        <option value="active" <?= ($_POST['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active / Published</option>
                        <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Draft / Hidden</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Product Media Card -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6">
            <h3 class="font-bold text-white text-base border-b border-slate-800 pb-4">Product Image</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Upload Image File (JPG, PNG, WEBP max 5MB)</label>
                    <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-600 file:text-white hover:file:bg-brand-500 cursor-pointer">
                </div>

                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t border-slate-800"></div>
                    <span class="flex-shrink mx-4 text-xs font-bold text-slate-500 uppercase">Or provide URL</span>
                    <div class="flex-grow border-t border-slate-800"></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Image URL (Unsplash, CDN, etc.)</label>
                    <input type="text" name="image_url" value="<?= e($_POST['image_url'] ?? '') ?>" placeholder="https://images.unsplash.com/photo-..." class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white text-sm focus:border-brand-500 outline-none">
                </div>
            </div>
        </div>

        <!-- Submission Buttons -->
        <div class="flex items-center justify-end gap-4">
            <a href="<?= BASE_URL ?>/admin/products.php" class="px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs uppercase tracking-wider transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
                Publish Product
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
