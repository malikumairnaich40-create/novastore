<?php
/**
 * AJAX Cart API Controller
 * 
 * Handles add, update, remove, and get operations with JSON response.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo = db();
$userId = $_SESSION['user_id'] ?? null;
$sessionId = get_session_id();

try {
    switch ($action) {
        case 'add':
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

            if (!$productId || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
                exit;
            }

            // Verify product exists, active and check stock
            $stmt = $pdo->prepare("SELECT id, name, price, sale_price, stock_quantity, status FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product || $product['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Product is currently unavailable']);
                exit;
            }

            if ($product['stock_quantity'] < $quantity) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Only {$product['stock_quantity']} units available in stock"
                ]);
                exit;
            }

            // Check if item already exists in cart
            if ($userId) {
                $check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $check->execute([$userId, $productId]);
            } else {
                $check = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND user_id IS NULL AND product_id = ?");
                $check->execute([$sessionId, $productId]);
            }

            $existing = $check->fetch();

            if ($existing) {
                $newQuantity = $existing['quantity'] + $quantity;
                if ($newQuantity > $product['stock_quantity']) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Cannot add more. You already have {$existing['quantity']} in cart (Stock limit: {$product['stock_quantity']})"
                    ]);
                    exit;
                }

                $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update->execute([$newQuantity, $existing['id']]);
            } else {
                if ($userId) {
                    $insert = $pdo->prepare("INSERT INTO cart (user_id, session_id, product_id, quantity) VALUES (?, ?, ?, ?)");
                    $insert->execute([$userId, $sessionId, $productId, $quantity]);
                } else {
                    $insert = $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
                    $insert->execute([$sessionId, $productId, $quantity]);
                }
            }

            $summary = calculate_cart_summary();
            $cartCount = get_cart_count();

            echo json_encode([
                'success' => true,
                'message' => "Added \"{$product['name']}\" to your cart!",
                'cart_count' => $cartCount,
                'summary' => [
                    'subtotal' => $summary['subtotal'],
                    'formatted_subtotal' => format_price($summary['subtotal']),
                    'shipping' => $summary['shipping'],
                    'formatted_shipping' => format_price($summary['shipping']),
                    'tax' => $summary['tax'],
                    'formatted_tax' => format_price($summary['tax']),
                    'grand_total' => $summary['grand_total'],
                    'formatted_grand_total' => format_price($summary['grand_total']),
                    'is_free_shipping' => $summary['is_free_shipping'],
                    'free_shipping_needed' => $summary['free_shipping_needed']
                ]
            ]);
            exit;

        case 'update':
            $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

            if (!$cartId || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
                exit;
            }

            // Verify item ownership
            if ($userId) {
                $stmt = $pdo->prepare("SELECT c.id, c.product_id, p.stock_quantity, p.price, p.sale_price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
                $stmt->execute([$cartId, $userId]);
            } else {
                $stmt = $pdo->prepare("SELECT c.id, c.product_id, p.stock_quantity, p.price, p.sale_price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.session_id = ? AND c.user_id IS NULL");
                $stmt->execute([$cartId, $sessionId]);
            }

            $cartItem = $stmt->fetch();

            if (!$cartItem) {
                echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                exit;
            }

            if ($quantity > $cartItem['stock_quantity']) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Stock limit exceeded. Only {$cartItem['stock_quantity']} available."
                ]);
                exit;
            }

            $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update->execute([$quantity, $cartId]);

            $effectivePrice = ($cartItem['sale_price'] !== null && $cartItem['sale_price'] > 0) ? (float)$cartItem['sale_price'] : (float)$cartItem['price'];
            $itemSubtotal = $effectivePrice * $quantity;

            $summary = calculate_cart_summary();
            $cartCount = get_cart_count();

            echo json_encode([
                'success' => true,
                'message' => 'Cart quantity updated',
                'cart_count' => $cartCount,
                'item_subtotal' => format_price($itemSubtotal),
                'summary' => [
                    'subtotal' => $summary['subtotal'],
                    'formatted_subtotal' => format_price($summary['subtotal']),
                    'shipping' => $summary['shipping'],
                    'formatted_shipping' => format_price($summary['shipping']),
                    'tax' => $summary['tax'],
                    'formatted_tax' => format_price($summary['tax']),
                    'grand_total' => $summary['grand_total'],
                    'formatted_grand_total' => format_price($summary['grand_total']),
                    'is_free_shipping' => $summary['is_free_shipping'],
                    'free_shipping_needed' => $summary['free_shipping_needed']
                ]
            ]);
            exit;

        case 'remove':
            $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);

            if (!$cartId) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item ID']);
                exit;
            }

            if ($userId) {
                $delete = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $delete->execute([$cartId, $userId]);
            } else {
                $delete = $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ? AND user_id IS NULL");
                $delete->execute([$cartId, $sessionId]);
            }

            $summary = calculate_cart_summary();
            $cartCount = get_cart_count();

            echo json_encode([
                'success' => true,
                'message' => 'Item removed from your cart',
                'cart_count' => $cartCount,
                'summary' => [
                    'subtotal' => $summary['subtotal'],
                    'formatted_subtotal' => format_price($summary['subtotal']),
                    'shipping' => $summary['shipping'],
                    'formatted_shipping' => format_price($summary['shipping']),
                    'tax' => $summary['tax'],
                    'formatted_tax' => format_price($summary['tax']),
                    'grand_total' => $summary['grand_total'],
                    'formatted_grand_total' => format_price($summary['grand_total']),
                    'is_free_shipping' => $summary['is_free_shipping'],
                    'free_shipping_needed' => $summary['free_shipping_needed']
                ]
            ]);
            exit;

        case 'clear':
            if ($userId) {
                $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
            } else {
                $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL")->execute([$sessionId]);
            }

            echo json_encode(['success' => true, 'message' => 'Cart emptied successfully', 'cart_count' => 0]);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown cart action']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
