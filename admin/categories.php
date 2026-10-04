<?php
/**
 * Admin Panel - Category Management (Full CRUD)
 */
$adminTitle = 'Category Management - NovaStore';
$activePage = 'categories';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$errors = [];

// Handle Category Actions (Add, Edit, Delete, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid CSRF security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $catId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fa-box');
            $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';
            $imageUrl = trim($_POST['image_url'] ?? '');

            // Handle file upload if provided
            if (!empty($_FILES['image_file']['name'])) {
                $uploadRes = upload_image($_FILES['image_file'], 'categories');
                if ($uploadRes['success']) {
                    $imageUrl = $uploadRes['filename'];
                } else {
                    $errors[] = $uploadRes['error'];
                }
            }

            if (empty($name)) {
                $errors[] = 'Category name is required.';
            }

            // Check slug uniqueness
            if (empty($errors)) {
                if ($action === 'create') {
                    $slugCheck = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                    $slugCheck->execute([$slug]);
                    if ($slugCheck->fetch()) {
                        $slug .= '-' . time();
                    }

                    $insert = $pdo->prepare("
                        INSERT INTO categories (name, slug, description, image, icon, status) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insert->execute([$name, $slug, $description, $imageUrl, $icon, $status]);
                    set_flash('success', "Category \"{$name}\" created successfully.");
                } else {
                    $slugCheck = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                    $slugCheck->execute([$slug, $catId]);
                    if ($slugCheck->fetch()) {
                        $slug .= '-' . time();
                    }

                    $update = $pdo->prepare("
                        UPDATE categories 
                        SET name = ?, slug = ?, description = ?, image = COALESCE(NULLIF(?, ''), image), icon = ?, status = ? 
                        WHERE id = ?
                    ");
                    $update->execute([$name, $slug, $description, $imageUrl, $icon, $status, $catId]);
                    set_flash('success', "Category \"{$name}\" updated successfully.");
                }

                header('Location: ' . BASE_URL . '/admin/categories.php');
                exit;
            }
        } elseif ($action === 'delete') {
            $catId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
            if ($catId) {
                // Check if category has products
                $pCount = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $pCount->execute([$catId]);
                $count = (int)$pCount->fetchColumn();

                if ($count > 0) {
                    set_flash('error', "Cannot delete category: {$count} product(s) are currently assigned to it. Reassign or delete products first.");
                } else {
                    $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $del->execute([$catId]);
                    set_flash('success', 'Category deleted successfully.');
                }
                header('Location: ' . BASE_URL . '/admin/categories.php');
                exit;
            }
        } elseif ($action === 'toggle_status') {
            $catId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
            if ($catId) {
                $toggle = $pdo->prepare("UPDATE categories SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
                $toggle->execute([$catId]);
                set_flash('success', 'Category status updated.');
                header('Location: ' . BASE_URL . '/admin/categories.php');
                exit;
            }
        }
    }
}

// Fetch all categories with product counts
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
")->fetchAll();
?>

<div class="space-y-8">
    
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">Category Management</h2>
            <p class="text-slate-400 text-sm mt-1">Organize your store catalog into searchable departments</p>
        </div>
        <button type="button" onclick="openCategoryModal('create')" class="px-5 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add New Category</span>
        </button>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
            <?php foreach ($errors as $err): ?>
                <p class="flex items-center gap-2"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Categories Grid / Table -->
    <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Department</th>
                        <th class="px-6 py-4">Slug</th>
                        <th class="px-6 py-4">Icon</th>
                        <th class="px-6 py-4">Assigned Products</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No categories created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= e(get_image_url($cat['image'], 'category')) ?>" alt="" class="w-12 h-12 object-cover rounded-xl bg-slate-900 border border-slate-800 shrink-0">
                                        <div>
                                            <h4 class="font-bold text-white"><?= e($cat['name']) ?></h4>
                                            <p class="text-xs text-slate-400 line-clamp-1 max-w-xs"><?= e($cat['description'] ?: 'No description provided') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-400"><?= e($cat['slug']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="w-8 h-8 rounded-lg bg-slate-800 text-brand-400 flex items-center justify-center">
                                        <i class="fa-solid <?= e($cat['icon'] ?: 'fa-tag') ?>"></i>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-white"><?= $cat['product_count'] ?> items</td>
                                <td class="px-6 py-4">
                                    <form action="<?= BASE_URL ?>/admin/categories.php" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold border transition-colors <?= $cat['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:bg-slate-700' ?>">
                                            <?= ucfirst($cat['status']) ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" onclick="editCategory(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8') ?>)" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors" title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        
                                        <form action="<?= BASE_URL ?>/admin/categories.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this category?');" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
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

<!-- Add / Edit Category Modal -->
<div id="categoryModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <h3 id="modalTitle" class="text-lg font-bold text-white">Create New Category</h3>
            <button type="button" onclick="closeCategoryModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="<?= BASE_URL ?>/admin/categories.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="modalAction" value="create">
            <input type="hidden" name="category_id" id="modalCatId" value="">

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Category Name *</label>
                <input type="text" name="name" id="modalName" required placeholder="e.g. Smart Watches" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-white text-sm focus:border-brand-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Slug (URL)</label>
                <input type="text" name="slug" id="modalSlug" placeholder="smart-watches" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-white text-sm focus:border-brand-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">FontAwesome Icon</label>
                    <input type="text" name="icon" id="modalIcon" value="fa-box" placeholder="fa-laptop" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-white text-sm focus:border-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                    <select name="status" id="modalStatus" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-white text-sm focus:border-brand-500 outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Description</label>
                <textarea name="description" id="modalDescription" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-800 bg-slate-950 text-white text-sm focus:border-brand-500 outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Image URL or Upload</label>
                <input type="text" name="image_url" id="modalImageUrl" placeholder="https://images.unsplash.com/..." class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-white text-sm focus:border-brand-500 outline-none mb-2">
                <input type="file" name="image_file" accept="image/*" class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-700">
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                <button type="button" onclick="closeCategoryModal()" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs uppercase tracking-wider">Cancel</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-md">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCategoryModal(mode) {
    document.getElementById('categoryModal').classList.remove('hidden');
    if (mode === 'create') {
        document.getElementById('modalTitle').textContent = 'Create New Category';
        document.getElementById('modalAction').value = 'create';
        document.getElementById('modalCatId').value = '';
        document.getElementById('modalName').value = '';
        document.getElementById('modalSlug').value = '';
        document.getElementById('modalDescription').value = '';
        document.getElementById('modalIcon').value = 'fa-box';
        document.getElementById('modalStatus').value = 'active';
        document.getElementById('modalImageUrl').value = '';
    }
}

function editCategory(cat) {
    document.getElementById('categoryModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Edit Category: ' + cat.name;
    document.getElementById('modalAction').value = 'update';
    document.getElementById('modalCatId').value = cat.id;
    document.getElementById('modalName').value = cat.name;
    document.getElementById('modalSlug').value = cat.slug;
    document.getElementById('modalDescription').value = cat.description || '';
    document.getElementById('modalIcon').value = cat.icon || 'fa-box';
    document.getElementById('modalStatus').value = cat.status;
    document.getElementById('modalImageUrl').value = cat.image || '';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
