<?php
/**
 * Admin Panel - Delete Product Handler
 */
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Invalid security token.');
        header('Location: ' . BASE_URL . '/admin/products.php');
        exit;
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $pdo = db();

        // Fetch image to safely remove file if local
        $stmt = $pdo->prepare("SELECT image, name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            if (!empty($product['image']) && !filter_var($product['image'], FILTER_VALIDATE_URL)) {
                $filePath = UPLOAD_DIR . ltrim($product['image'], '/');
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $del->execute([$id]);

            set_flash('success', "Product \"{$product['name']}\" was deleted successfully.");
        }
    }
}

header('Location: ' . BASE_URL . '/admin/products.php');
exit;
