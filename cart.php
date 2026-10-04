<?php
/**
 * Shopping Cart Page
 */
$pageTitle = 'Shopping Cart - NovaStore';
require_once __DIR__ . '/config/config.php';

$summary = calculate_cart_summary();
$items = $summary['items'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-slate-100/60 border-b border-slate-200/80 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Shopping Bag</h1>
        <p class="text-slate-500 text-sm mt-1">Review your selected items and proceed to secure checkout</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if (empty($items)): ?>
        <!-- Empty Cart State -->
        <div class="bg-white rounded-3xl p-12 sm:p-16 text-center border border-slate-200/80 shadow-sm max-w-xl mx-auto">
            <div class="w-20 h-20 rounded-3xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto text-3xl mb-6">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Your shopping bag is empty</h2>
            <p class="text-slate-500 text-sm mt-2 max-w-sm mx-auto">Looks like you haven't added anything to your cart yet. Explore our latest products and exclusive deals!</p>
            <div class="mt-8">
                <a href="<?= BASE_URL ?>/products.php" class="px-8 py-4 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-xl shadow-brand-600/20 inline-flex items-center gap-2 transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Start Shopping</span>
                </a>
            </div>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Cart Items List (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Free Shipping Progress Bar -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div class="flex-1 text-sm text-slate-700" id="free-shipping-progress-banner">
                        <?php if ($summary['is_free_shipping']): ?>
                            <span class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check mr-1"></i> You unlocked Free Express Shipping!</span>
                        <?php else: ?>
                            Add <span class="font-bold text-slate-900"><?= format_price($summary['free_shipping_needed']) ?></span> more to your cart to qualify for <strong>Free Shipping</strong>!
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Items Container -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-bold text-slate-900 text-lg">Cart Items (<?= $summary['total_quantity'] ?>)</h2>
                        <button type="button" onclick="if(confirm('Are you sure you want to clear your cart?')) { fetch('<?= BASE_URL ?>/api/cart.php', { method: 'POST', body: new URLSearchParams({action: 'clear'}) }).then(() => location.reload()); }" class="text-xs font-semibold text-slate-400 hover:text-rose-600 transition-colors">
                            Clear Entire Cart
                        </button>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <div id="cart-row-<?= $item['cart_id'] ?>" class="p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 transition-opacity">
                                
                                <!-- Product Media & Details -->
                                <div class="flex items-center gap-4 flex-1">
                                    <img src="<?= e(get_image_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" class="w-20 h-20 object-cover rounded-2xl bg-slate-100 border border-slate-100 shrink-0">
                                    <div>
                                        <span class="text-xs font-semibold text-brand-600"><?= e($item['category_name']) ?></span>
                                        <h3 class="font-bold text-slate-900 text-sm sm:text-base hover:text-brand-600 transition-colors">
                                            <a href="<?= BASE_URL ?>/product.php?id=<?= $item['product_id'] ?>"><?= e($item['name']) ?></a>
                                        </h3>
                                        <div class="text-xs text-slate-400 mt-1">
                                            Unit Price: <span class="text-slate-700 font-semibold"><?= format_price($item['effective_price']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quantity Controls & Line Total -->
                                <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto">
                                    <!-- Stepper -->
                                    <div class="flex items-center bg-slate-100 rounded-xl border border-slate-200 p-0.5">
                                        <button type="button" class="qty-btn-minus w-8 h-8 rounded-lg bg-white hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition-colors shadow-xs">
                                            <i class="fa-solid fa-minus text-[10px]"></i>
                                        </button>
                                        <input type="number" data-cart-id="<?= $item['cart_id'] ?>" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>" class="cart-item-qty-input w-12 text-center font-bold text-slate-900 bg-transparent border-none focus:ring-0 text-sm">
                                        <button type="button" class="qty-btn-plus w-8 h-8 rounded-lg bg-white hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition-colors shadow-xs">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                        </button>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="text-right min-w-[90px]">
                                        <span id="item-subtotal-<?= $item['cart_id'] ?>" class="text-base sm:text-lg font-black text-slate-900">
                                            <?= format_price($item['subtotal']) ?>
                                        </span>
                                    </div>

                                    <!-- Delete Button -->
                                    <button type="button" data-cart-id="<?= $item['cart_id'] ?>" class="btn-remove-cart-item text-slate-400 hover:text-rose-600 p-2 transition-colors" title="Remove item">
                                        <i class="fa-regular fa-trash-can text-base"></i>
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                        <a href="<?= BASE_URL ?>/products.php" class="text-sm font-bold text-brand-600 hover:text-brand-700 flex items-center gap-2">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>Continue Shopping</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-6">
                    <h2 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-4">Order Summary</h2>

                    <!-- Summary Line Items -->
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span id="cart-subtotal-val" class="font-bold text-slate-900"><?= format_price($summary['subtotal']) ?></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Estimated Shipping</span>
                            <span id="cart-shipping-val" class="font-bold text-slate-900">
                                <?= $summary['shipping'] == 0 ? '<span class="text-emerald-600 font-bold">FREE</span>' : format_price($summary['shipping']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Sales Tax (<?= (TAX_RATE * 100) ?>%)</span>
                            <span id="cart-tax-val" class="font-bold text-slate-900"><?= format_price($summary['tax']) ?></span>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-between items-baseline">
                            <span class="text-base font-bold text-slate-900">Grand Total</span>
                            <span id="cart-grand-total-val" class="text-2xl font-black text-brand-600"><?= format_price($summary['grand_total']) ?></span>
                        </div>
                    </div>

                    <!-- Promo Code Simulated Box -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Promo Code</label>
                        <div class="flex gap-2">
                            <input type="text" placeholder="e.g. NOVA10" class="flex-1 px-3 py-2 text-sm rounded-xl border border-slate-200 uppercase outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            <button type="button" onclick="showToast('Promo code applied! 10% discount added to checkout.', 'success')" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition-colors">
                                Apply
                            </button>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <a href="<?= BASE_URL ?>/checkout.php" class="w-full py-4 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-center block shadow-xl shadow-brand-600/20 hover:scale-[1.02] transition-all">
                        Proceed to Checkout <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                    </a>

                    <!-- Security badges -->
                    <div class="pt-4 border-t border-slate-100 text-center space-y-2">
                        <div class="flex items-center justify-center gap-2 text-xs text-slate-400 font-medium">
                            <i class="fa-solid fa-lock text-emerald-500"></i>
                            <span>256-Bit SSL Encrypted Checkout</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
