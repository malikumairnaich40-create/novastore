<?php
/**
 * Global Utility Functions & Security Helpers
 */

/**
 * Quick Database Accessor
 */
function db(): PDO {
    return Database::getConnection();
}

/**
 * HTML Output Escaping (XSS Prevention)
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render Hidden CSRF Input Field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token(?string $token = null): bool {
    $token = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if a user is authenticated
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Check if the authenticated user is an administrator
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current authenticated user data
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    try {
        $stmt = db()->prepare("SELECT id, role, name, email, phone, address, city, state, zip_code, country, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Enforce Customer Login
 */
function require_login(string $redirectUrl = null): void {
    if (!is_logged_in()) {
        $redirect = $redirectUrl ?? $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/index.php');
        $_SESSION['redirect_after_login'] = $redirect;
        set_flash('error', 'Please log in to continue.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Enforce Admin Role
 */
function require_admin(): void {
    if (!is_admin()) {
        set_flash('error', 'Access denied. Administrator privileges required.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Format Price with Currency Symbol
 */
function format_price($price): string {
    return CURRENCY_SYMBOL . number_format((float)$price, 2);
}

/**
 * Generate URL-friendly slug
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'n-a');
}

/**
 * Flash Message Handler
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get or create unique session identifier for guest cart
 */
function get_session_id(): string {
    if (empty($_SESSION['guest_cart_id'])) {
        $_SESSION['guest_cart_id'] = 'guest_' . bin2hex(random_bytes(16));
    }
    return $_SESSION['guest_cart_id'];
}

/**
 * Fetch all items in current cart (DB-backed)
 */
function get_cart_items(): array {
    $pdo = db();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = get_session_id();

    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.slug, p.price, p.sale_price, p.stock_quantity, p.image, p.status, cat.name as category_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.slug, p.price, p.sale_price, p.stock_quantity, p.image, p.status, cat.name as category_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE c.session_id = ? AND c.user_id IS NULL
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$sessionId]);
    }

    $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        $effectivePrice = ($item['sale_price'] !== null && $item['sale_price'] > 0) ? (float)$item['sale_price'] : (float)$item['price'];
        $item['effective_price'] = $effectivePrice;
        $item['subtotal'] = $effectivePrice * $item['quantity'];
    }

    return $items;
}

/**
 * Get total number of items in cart
 */
function get_cart_count(): int {
    $pdo = db();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = get_session_id();

    if ($userId) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE session_id = ? AND user_id IS NULL");
        $stmt->execute([$sessionId]);
    }

    $result = $stmt->fetch();
    return (int)($result['total_qty'] ?? 0);
}

/**
 * Calculate Cart Totals
 */
function calculate_cart_summary(): array {
    $items = get_cart_items();
    $subtotal = 0;
    $totalQty = 0;

    foreach ($items as $item) {
        $subtotal += $item['subtotal'];
        $totalQty += $item['quantity'];
    }

    $shipping = ($subtotal >= FREE_SHIPPING_THRESHOLD || $subtotal == 0) ? 0.00 : FLAT_SHIPPING_FEE;
    $tax = round($subtotal * TAX_RATE, 2);
    $grandTotal = $subtotal + $shipping + $tax;

    return [
        'items' => $items,
        'item_count' => count($items),
        'total_quantity' => $totalQty,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'grand_total' => $grandTotal,
        'is_free_shipping' => ($subtotal >= FREE_SHIPPING_THRESHOLD && $subtotal > 0),
        'free_shipping_needed' => max(0, FREE_SHIPPING_THRESHOLD - $subtotal)
    ];
}

/**
 * Migrate Guest Session Cart to Authenticated User
 */
function migrate_session_cart_to_user(int $userId): void {
    $pdo = db();
    $sessionId = get_session_id();

    $stmt = $pdo->prepare("SELECT id, product_id, quantity FROM cart WHERE session_id = ? AND user_id IS NULL");
    $stmt->execute([$sessionId]);
    $guestItems = $stmt->fetchAll();

    foreach ($guestItems as $guestItem) {
        // Check if user already has this product in cart
        $check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check->execute([$userId, $guestItem['product_id']]);
        $existing = $check->fetch();

        if ($existing) {
            $newQty = $existing['quantity'] + $guestItem['quantity'];
            $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update->execute([$newQty, $existing['id']]);

            // Remove guest record
            $delete = $pdo->prepare("DELETE FROM cart WHERE id = ?");
            $delete->execute([$guestItem['id']]);
        } else {
            // Assign to user
            $update = $pdo->prepare("UPDATE cart SET user_id = ? WHERE id = ?");
            $update->execute([$userId, $guestItem['id']]);
        }
    }
}

/**
 * Product & Category Image URL Resolver
 */
function get_image_url(?string $imagePath, string $fallbackType = 'product'): string {
    if (empty($imagePath)) {
        return 'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=600&auto=format&fit=crop&q=80';
    }

    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }

    // Local file path inside assets/uploads/
    return BASE_URL . '/assets/uploads/' . ltrim($imagePath, '/');
}

/**
 * Secure File Uploader
 */
function upload_image(array $file, string $subFolder = 'products'): array {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error code: ' . ($file['error'] ?? 'unknown')];
    }

    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum limit of 5MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid image format. Only JPG, PNG, WEBP, and GIF are allowed.'];
    }

    $ext = $allowedMimes[$mime];
    $targetDir = UPLOAD_DIR . $subFolder . DIRECTORY_SEPARATOR;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = $subFolder . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destination = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file. Check folder permissions.'];
    }

    return [
        'success' => true,
        'filename' => $subFolder . '/' . $filename
    ];
}

/**
 * Render Status Badges
 */
function render_order_status_badge(string $status): string {
    $status = strtolower($status);
    $colors = [
        'pending' => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400',
        'processing' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400',
        'shipped' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400',
        'delivered' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400',
        'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400',
    ];

    $color = $colors[$status] ?? 'bg-slate-100 text-slate-800 border-slate-200';
    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ' . $color . '">' . ucfirst($status) . '</span>';
}

function render_payment_status_badge(string $status): string {
    $status = strtolower($status);
    $colors = [
        'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
        'failed' => 'bg-rose-100 text-rose-800 border-rose-200',
    ];

    $color = $colors[$status] ?? 'bg-slate-100 text-slate-800 border-slate-200';
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ' . $color . '">' . ucfirst($status) . '</span>';
}
