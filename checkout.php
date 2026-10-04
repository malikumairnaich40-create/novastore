<?php
/**
 * Checkout & Order Processing
 */
$pageTitle = 'Secure Checkout - NovaStore';
require_once __DIR__ . '/config/config.php';

$summary = calculate_cart_summary();
$items = $summary['items'];

if (empty($items)) {
    set_flash('error', 'Your shopping cart is empty.');
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$currentUser = current_user();
$pdo = db();
$errors = [];

// Handle Checkout Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid or expired CSRF security token. Please try again.';
    }

    $shippingName = trim($_POST['shipping_name'] ?? '');
    $shippingEmail = trim($_POST['shipping_email'] ?? '');
    $shippingPhone = trim($_POST['shipping_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $shippingCity = trim($_POST['shipping_city'] ?? '');
    $shippingState = trim($_POST['shipping_state'] ?? '');
    $shippingZip = trim($_POST['shipping_zip'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'cod');
    $orderNotes = trim($_POST['order_notes'] ?? '');

    // Validations
    if (empty($shippingName)) $errors[] = 'Full name is required.';
    if (empty($shippingEmail) || !filter_var($shippingEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (empty($shippingPhone)) $errors[] = 'Phone number is required.';
    if (empty($shippingAddress)) $errors[] = 'Street address is required.';
    if (empty($shippingCity)) $errors[] = 'City is required.';
    if (empty($shippingState)) $errors[] = 'State / Province is required.';
    if (empty($shippingZip)) $errors[] = 'Postal / Zip code is required.';
    if (!in_array($paymentMethod, ['cod', 'card', 'paypal'])) $paymentMethod = 'cod';

    // Mock card validation if card chosen
    if ($paymentMethod === 'card') {
        $cardNumber = trim($_POST['card_number'] ?? '');
        $cardExpiry = trim($_POST['card_expiry'] ?? '');
        $cardCvv = trim($_POST['card_cvv'] ?? '');

        if (empty($cardNumber) || empty($cardExpiry) || empty($cardCvv)) {
            $errors[] = 'Please provide valid credit card details.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Re-verify product stock within transaction
            foreach ($items as $item) {
                $stockCheck = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE id = ? FOR UPDATE");
                $stockCheck->execute([$item['product_id']]);
                $prod = $stockCheck->fetch();

                if (!$prod || $prod['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Sorry, \"{$item['name']}\" has insufficient stock (Available: " . ($prod['stock_quantity'] ?? 0) . "). Please update your cart.");
                }
            }

            // Generate unique Order Number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $paymentStatus = ($paymentMethod === 'card' || $paymentMethod === 'paypal') ? 'paid' : 'pending';
            $orderStatus = 'pending';
            $userId = $currentUser ? (int)$currentUser['id'] : null;

            // Insert into orders table
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number, subtotal, tax_amount, shipping_fee, discount_amount, 
                    total_amount, payment_method, payment_status, order_status, 
                    shipping_name, shipping_email, shipping_phone, shipping_address, 
                    shipping_city, shipping_state, shipping_zip, order_notes
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?
                )
            ");

            $orderStmt->execute([
                $userId,
                $orderNumber,
                $summary['subtotal'],
                $summary['tax'],
                $summary['shipping'],
                0.00,
                $summary['grand_total'],
                $paymentMethod,
                $paymentStatus,
                $orderStatus,
                $shippingName,
                $shippingEmail,
                $shippingPhone,
                $shippingAddress,
                $shippingCity,
                $shippingState,
                $shippingZip,
                $orderNotes
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // Insert order items & deduct stock
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, product_image, price, quantity, total_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['image'],
                    $item['effective_price'],
                    $item['quantity'],
                    $item['subtotal']
                ]);

                $stockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Clear cart
            $sessionId = get_session_id();
            if ($userId) {
                $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
            } else {
                $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL")->execute([$sessionId]);
            }

            $pdo->commit();

            // Save order ID to session for confirmation page access
            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['last_order_number'] = $orderNumber;

            header('Location: ' . BASE_URL . '/order-success.php?order=' . urlencode($orderNumber));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-slate-100/60 border-b border-slate-200/80 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Checkout</h1>
        <p class="text-slate-500 text-sm mt-1">Complete your delivery address and payment details</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if (!empty($errors)): ?>
        <div class="mb-8 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Please fix the following errors:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/checkout.php" method="POST" id="checkout-form">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            
            <!-- Left Column: Shipping & Payment Details (7 cols) -->
            <div class="lg:col-span-7 space-y-8">
                
                <!-- Section 1: Customer & Shipping Address -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 font-black flex items-center justify-center text-sm">1</div>
                            <h2 class="text-lg font-bold text-slate-900">Shipping & Contact Details</h2>
                        </div>
                        <?php if (!$currentUser): ?>
                            <a href="<?= BASE_URL ?>/login.php" class="text-xs font-semibold text-brand-600 hover:underline">Already have an account? Log in</a>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Full Name *</label>
                            <input type="text" name="shipping_name" value="<?= e($_POST['shipping_name'] ?? $currentUser['name'] ?? '') ?>" required placeholder="e.g. Alex Johnson" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email Address *</label>
                            <input type="email" name="shipping_email" value="<?= e($_POST['shipping_email'] ?? $currentUser['email'] ?? '') ?>" required placeholder="alex@example.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Phone Number *</label>
                            <input type="tel" name="shipping_phone" value="<?= e($_POST['shipping_phone'] ?? $currentUser['phone'] ?? '') ?>" required placeholder="+1 (555) 019-2834" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Street Address *</label>
                            <input type="text" name="shipping_address" value="<?= e($_POST['shipping_address'] ?? $currentUser['address'] ?? '') ?>" required placeholder="123 Main Street, Apt 4B" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">City *</label>
                            <input type="text" name="shipping_city" value="<?= e($_POST['shipping_city'] ?? $currentUser['city'] ?? '') ?>" required placeholder="New York" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">State / Region *</label>
                            <input type="text" name="shipping_state" value="<?= e($_POST['shipping_state'] ?? $currentUser['state'] ?? '') ?>" required placeholder="NY" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Postal / Zip Code *</label>
                            <input type="text" name="shipping_zip" value="<?= e($_POST['shipping_zip'] ?? $currentUser['zip_code'] ?? '') ?>" required placeholder="10001" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Country</label>
                            <input type="text" readonly value="United States" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Delivery Instructions (Optional)</label>
                            <textarea name="order_notes" rows="2" placeholder="e.g. Leave package by the porch, gate code #1234" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none"><?= e($_POST['order_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Payment Method -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 font-black flex items-center justify-center text-sm">2</div>
                        <h2 class="text-lg font-bold text-slate-900">Payment Selection</h2>
                    </div>

                    <div class="space-y-3">
                        <!-- COD Option -->
                        <label class="payment-method-option flex items-start gap-4 p-4 rounded-2xl border-2 border-brand-500 bg-brand-50/30 cursor-pointer transition-all">
                            <input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentInputs('cod')" class="mt-1 text-brand-600 focus:ring-brand-500">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900 text-sm">Cash on Delivery (COD)</span>
                                    <i class="fa-solid fa-money-bill-wave text-emerald-600 text-lg"></i>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Pay with cash or card upon receiving your package at your doorstep.</p>
                            </div>
                        </label>

                        <!-- Card Payment Option -->
                        <label class="payment-method-option flex items-start gap-4 p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 cursor-pointer transition-all">
                            <input type="radio" name="payment_method" value="card" onchange="togglePaymentInputs('card')" class="mt-1 text-brand-600 focus:ring-brand-500">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900 text-sm">Credit / Debit Card (Simulated)</span>
                                    <div class="flex items-center gap-2 text-slate-400 text-lg">
                                        <i class="fa-brands fa-cc-visa"></i>
                                        <i class="fa-brands fa-cc-mastercard"></i>
                                        <i class="fa-brands fa-cc-amex"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Instant simulated card payment processing via safe gateway.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Card Fields (Hidden by default unless card selected) -->
                    <div id="card-fields-box" class="hidden pt-4 space-y-4 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Card Number</label>
                            <input type="text" name="card_number" placeholder="4242 •••• •••• 4242" maxlength="19" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white outline-none focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Expiry Date</label>
                                <input type="text" name="card_expiry" placeholder="MM/YY" maxlength="5" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">CVC / CVV</label>
                                <input type="password" name="card_cvv" placeholder="123" maxlength="4" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Right Column: Order Summary (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6 sticky top-28">
                    <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4">Order Items (<?= $summary['total_quantity'] ?>)</h2>

                    <!-- Items List -->
                    <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 pr-1 space-y-3">
                        <?php foreach ($items as $item): ?>
                            <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <img src="<?= e(get_image_url($item['image'])) ?>" alt="" class="w-12 h-12 object-cover rounded-xl bg-slate-100 border border-slate-200/60 shrink-0">
                                        <span class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-slate-800 text-white text-[10px] font-bold flex items-center justify-center">
                                            <?= $item['quantity'] ?>
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 line-clamp-1"><?= e($item['name']) ?></h4>
                                        <p class="text-[11px] text-slate-400"><?= format_price($item['effective_price']) ?> each</p>
                                    </div>
                                </div>
                                <span class="text-xs font-black text-slate-900 shrink-0"><?= format_price($item['subtotal']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totals Breakdown -->
                    <div class="pt-4 border-t border-slate-100 space-y-2.5 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-900"><?= format_price($summary['subtotal']) ?></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Shipping</span>
                            <span class="font-bold text-slate-900">
                                <?= $summary['shipping'] == 0 ? '<span class="text-emerald-600 font-bold">FREE</span>' : format_price($summary['shipping']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Taxes</span>
                            <span class="font-bold text-slate-900"><?= format_price($summary['tax']) ?></span>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                            <span class="text-base font-bold text-slate-900">Total Due</span>
                            <span class="text-2xl font-black text-brand-600"><?= format_price($summary['grand_total']) ?></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-base shadow-xl shadow-brand-600/20 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock text-sm"></i>
                        <span>Place Your Order</span>
                    </button>

                    <p class="text-center text-xs text-slate-400">By placing this order, you agree to our Terms of Service.</p>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function togglePaymentInputs(method) {
    const cardBox = document.getElementById('card-fields-box');
    const options = document.querySelectorAll('.payment-method-option');

    options.forEach(opt => {
        opt.classList.remove('border-brand-500', 'bg-brand-50/30');
        opt.classList.add('border-slate-200');
    });

    if (method === 'card') {
        cardBox.classList.remove('hidden');
        document.querySelector('input[value="card"]').closest('.payment-method-option').classList.add('border-brand-500', 'bg-brand-50/30');
    } else {
        cardBox.classList.add('hidden');
        document.querySelector('input[value="cod"]').closest('.payment-method-option').classList.add('border-brand-500', 'bg-brand-50/30');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
