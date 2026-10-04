<?php
/**
 * Admin Panel - Official Printable Order Invoice
 */
require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId) {
    die("Invalid Order ID");
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found");
}

// Fetch Order Items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= e($order['order_number']) ?> - <?= APP_NAME ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            #invoice-card { border: none !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 p-4 sm:p-8 font-sans antialiased">

    <!-- Top Action Bar (Screen Only) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="<?= BASE_URL ?>/admin/order-detail.php?id=<?= $order['id'] ?>" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Order Details
        </a>
        <div class="flex items-center gap-3">
            <button type="button" onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-sm transition-all">
                <i class="fa-solid fa-print"></i>
                <span>Print Invoice</span>
            </button>
        </div>
    </div>

    <!-- Invoice Paper Sheet -->
    <div id="invoice-card" class="max-w-4xl mx-auto bg-white rounded-3xl border border-slate-200 shadow-xl p-8 sm:p-12 space-y-8">
        
        <!-- Header: Company Info & Invoice Meta -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 pb-8">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xl font-bold">
                        <i class="fa-solid fa-cube"></i>
                    </div>
                    <span class="text-2xl font-black text-slate-900 tracking-tight"><?= APP_NAME ?> Inc.</span>
                </div>
                <p class="text-xs text-slate-500 pt-2">742 Evergreen Terrace, Springfield, OR 97477</p>
                <p class="text-xs text-slate-500">Email: billing@novastore.com | Phone: +1 (800) 555-NOVA</p>
            </div>

            <div class="text-left sm:text-right space-y-1">
                <span class="text-2xl font-black text-indigo-600 tracking-tight uppercase">INVOICE</span>
                <p class="text-xs font-mono font-bold text-slate-800">#<?= e($order['order_number']) ?></p>
                <p class="text-xs text-slate-500">Date: <?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                <div class="pt-2">
                    <span class="inline-block px-2.5 py-1 rounded-md text-xs font-extrabold uppercase border <?= $order['payment_status'] === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-amber-50 text-amber-700 border-amber-300' ?>">
                        Payment: <?= strtoupper($order['payment_status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Billed & Shipped To -->
        <div class="grid grid-cols-2 gap-8 text-sm">
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Billed & Shipped To</h4>
                <div class="space-y-1 text-slate-700">
                    <p class="font-bold text-slate-900 text-base"><?= e($order['shipping_name']) ?></p>
                    <p><?= e($order['shipping_address']) ?></p>
                    <p><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> <?= e($order['shipping_zip']) ?></p>
                    <p class="text-slate-500 text-xs">Phone: <?= e($order['shipping_phone']) ?></p>
                    <p class="text-slate-500 text-xs">Email: <?= e($order['shipping_email']) ?></p>
                </div>
            </div>

            <div class="text-right">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Payment Details</h4>
                <div class="space-y-1 text-slate-700">
                    <p><span class="text-slate-500">Method:</span> <strong class="uppercase"><?= e($order['payment_method']) ?></strong></p>
                    <p><span class="text-slate-500">Fulfillment:</span> <strong class="capitalize"><?= e($order['order_status']) ?></strong></p>
                    <p><span class="text-slate-500">Currency:</span> USD ($)</p>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase border-y border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Item & Description</th>
                        <th class="py-3 px-4 text-center">Qty</th>
                        <th class="py-3 px-4 text-right">Unit Price</th>
                        <th class="py-3 px-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-slate-900"><?= e($item['product_name']) ?></p>
                            </td>
                            <td class="py-3.5 px-4 text-center font-semibold text-slate-700"><?= $item['quantity'] ?></td>
                            <td class="py-3.5 px-4 text-right text-slate-600"><?= format_price($item['price']) ?></td>
                            <td class="py-3.5 px-4 text-right font-bold text-slate-900"><?= format_price($item['total_price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals Calculation -->
        <div class="flex justify-end pt-4 border-t border-slate-200">
            <div class="w-72 space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal</span>
                    <span class="font-bold text-slate-900"><?= format_price($order['subtotal']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Shipping Fee</span>
                    <span class="font-bold text-slate-900"><?= $order['shipping_fee'] == 0 ? 'FREE' : format_price($order['shipping_fee']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Sales Tax (<?= (TAX_RATE * 100) ?>%)</span>
                    <span class="font-bold text-slate-900"><?= format_price($order['tax_amount']) ?></span>
                </div>
                <div class="pt-3 border-t-2 border-slate-900 flex justify-between items-baseline">
                    <span class="font-black text-slate-900 text-base uppercase">Total Due</span>
                    <span class="text-2xl font-black text-indigo-600"><?= format_price($order['total_amount']) ?></span>
                </div>
            </div>
        </div>

        <!-- Footer Terms & Notes -->
        <div class="pt-8 border-t border-slate-200 text-xs text-slate-500 space-y-2">
            <p class="font-bold text-slate-700">Terms & Conditions:</p>
            <p>All returns must be in original packaging within 30 days of receipt. Thank you for shopping with <?= APP_NAME ?>!</p>
        </div>

    </div>

</body>
</html>
