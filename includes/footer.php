    </main>

    <!-- Global Storefront Footer -->
    <footer class="bg-slate-900 text-slate-400 text-sm mt-20 border-t border-slate-800">
        
        <!-- Newsletter Section -->
        <div class="border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="bg-gradient-to-r from-brand-900/60 to-slate-800 p-8 sm:p-10 rounded-3xl border border-white/10 flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="max-w-xl text-center lg:text-left">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-500/20 text-brand-300 mb-3 border border-brand-500/30">
                            <i class="fa-solid fa-envelope-open-text mr-1.5"></i> Subscribe & Save
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Get 15% off your first order</h3>
                        <p class="text-slate-300 mt-2 text-sm sm:text-base">Join over 25,000+ insiders for weekly deals, new arrivals, and member perks.</p>
                    </div>
                    <form onsubmit="event.preventDefault(); showToast('Thank you for subscribing to NovaStore newsletter!', 'success'); this.reset();" class="w-full lg:w-auto flex flex-col sm:flex-row gap-3">
                        <input type="email" required placeholder="Enter your email address" class="px-5 py-3.5 rounded-2xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400 min-w-[280px]">
                        <button type="submit" class="px-6 py-3.5 rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold transition-all shadow-lg shadow-brand-500/30 hover:scale-[1.02] shrink-0">
                            Subscribe Now
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Footer Links Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
                
                <!-- Brand Info -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-md">
                            <i class="fa-solid fa-cube text-lg"></i>
                        </div>
                        <span class="text-2xl font-black tracking-tight text-white"><?= APP_NAME ?></span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-sm">
                        NovaStore is a modern multi-category shopping experience crafted for speed, convenience, and curated quality products across electronics, fashion, home essentials, and lifestyle.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <a href="#" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition-colors"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition-colors"><i class="fa-brands fa-x-twitter"></i></a>
                        <a href="#" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition-colors"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition-colors"><i class="fa-brands fa-github"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-bold text-sm tracking-wider uppercase mb-4">Quick Links</h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= BASE_URL ?>/index.php" class="hover:text-white transition-colors">Home Storefront</a></li>
                        <li><a href="<?= BASE_URL ?>/products.php" class="hover:text-white transition-colors">All Products</a></li>
                        <li><a href="<?= BASE_URL ?>/products.php?featured=1" class="hover:text-white transition-colors">Flash Deals</a></li>
                        <li><a href="<?= BASE_URL ?>/cart.php" class="hover:text-white transition-colors">Shopping Cart</a></li>
                        <li><a href="<?= BASE_URL ?>/account.php" class="hover:text-white transition-colors">My Account</a></li>
                    </ul>
                </div>

                <!-- Customer Care -->
                <div>
                    <h4 class="text-white font-bold text-sm tracking-wider uppercase mb-4">Customer Care</h4>
                    <ul class="space-y-2.5">
                        <li><a href="#" class="hover:text-white transition-colors">Help Center / FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Track Your Order</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Shipping & Delivery</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Returns & Refunds</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>

                <!-- Admin & Security -->
                <div>
                    <h4 class="text-white font-bold text-sm tracking-wider uppercase mb-4">Admin & Demo</h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= BASE_URL ?>/admin/index.php" class="text-brand-400 hover:text-brand-300 font-medium flex items-center gap-1.5"><i class="fa-solid fa-lock text-xs"></i> Admin Dashboard</a></li>
                        <li><a href="<?= BASE_URL ?>/login.php" class="hover:text-white transition-colors">Customer Login</a></li>
                        <li><a href="<?= BASE_URL ?>/register.php" class="hover:text-white transition-colors">Create Account</a></li>
                    </ul>
                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <p class="text-xs text-slate-500 font-semibold uppercase mb-2">Accepted Payments</p>
                        <div class="flex items-center gap-2 text-slate-400 text-xl">
                            <i class="fa-brands fa-cc-visa hover:text-white"></i>
                            <i class="fa-brands fa-cc-mastercard hover:text-white"></i>
                            <i class="fa-brands fa-cc-paypal hover:text-white"></i>
                            <i class="fa-brands fa-cc-apple-pay hover:text-white"></i>
                            <i class="fa-solid fa-money-bill-wave hover:text-white text-base" title="Cash on Delivery"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Copyright Bar -->
            <div class="mt-12 pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> Inc. Built with PHP 8, PDO, Tailwind CSS & MySQL.</p>
                <div class="flex items-center gap-6">
                    <span>Clean OOP Architecture</span>
                    <span>•</span>
                    <span>Prepared Statements</span>
                    <span>•</span>
                    <span>AJAX Cart</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Frontend Core Scripts -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
