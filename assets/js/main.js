/**
 * NovaStore Frontend Interactive JavaScript & AJAX Cart Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    initQuantitySelectors();
    initAddToCartButtons();
    initCartQuantityUpdaters();
    initImageGallery();
    initMobileMenu();
    initDropdowns();
});

/**
 * Toast Notification System
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    const isError = type === 'error';
    const isWarning = type === 'warning';

    const bgClass = isSuccess ? 'bg-slate-900 text-white' : (isError ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white');
    const iconClass = isSuccess ? 'fa-circle-check text-emerald-400' : (isError ? 'fa-circle-xmark text-rose-200' : 'fa-triangle-exclamation text-amber-200');

    toast.className = `toast-animate pointer-events-auto flex items-center gap-3 px-4 py-3.5 rounded-xl shadow-2xl ${bgClass} text-sm font-medium border border-white/10`;
    toast.innerHTML = `
        <i class="fa-solid ${iconClass} text-lg shrink-0"></i>
        <div class="flex-1">${message}</div>
        <button type="button" class="text-white/60 hover:text-white shrink-0 ml-2" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Update Cart Badge in Navigation
 */
function updateCartBadge(count) {
    const badges = document.querySelectorAll('.cart-badge-count');
    badges.forEach(badge => {
        badge.textContent = count;
        if (count > 0) {
            badge.classList.remove('hidden');
            badge.classList.add('inline-flex');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('inline-flex');
        }
    });
}

/**
 * AJAX Add to Cart
 */
function initAddToCartButtons() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-add-to-cart');
        if (!btn) return;

        e.preventDefault();
        const productId = btn.dataset.productId;
        const qtyInput = document.querySelector(`input[data-product-qty="${productId}"]`) || document.getElementById('product-detail-qty');
        const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch((window.APP_URL || '') + '/api/cart.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                updateCartBadge(data.cart_count);
            } else {
                showToast(data.message || 'Could not add product to cart', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error while updating cart', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}

/**
 * AJAX Cart Page Live Quantity Updater & Deletion
 */
function initCartQuantityUpdaters() {
    // Quantity changes on cart page
    document.addEventListener('change', async (e) => {
        if (!e.target.classList.contains('cart-item-qty-input')) return;
        updateCartItem(e.target);
    });

    // Remove item button
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-remove-cart-item');
        if (!btn) return;

        e.preventDefault();
        const cartId = btn.dataset.cartId;
        const row = document.getElementById(`cart-row-${cartId}`);

        if (row) {
            row.style.opacity = '0.4';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('cart_id', cartId);

            const response = await fetch((window.APP_URL || '') + '/api/cart.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (row) row.remove();
                showToast(data.message, 'success');
                updateCartBadge(data.cart_count);
                refreshCartSummaryDOM(data.summary);

                if (data.cart_count === 0) {
                    location.reload(); // Reload to show empty cart banner
                }
            } else {
                if (row) row.style.opacity = '1';
                showToast(data.message || 'Error removing item', 'error');
            }
        } catch (err) {
            console.error(err);
            if (row) row.style.opacity = '1';
            showToast('Network error', 'error');
        }
    });
}

async function updateCartItem(input) {
    const cartId = input.dataset.cartId;
    const quantity = Math.max(1, parseInt(input.value) || 1);
    input.value = quantity;

    try {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('cart_id', cartId);
        formData.append('quantity', quantity);

        const response = await fetch((window.APP_URL || '') + '/api/cart.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Update line item total
            const lineSubtotal = document.getElementById(`item-subtotal-${cartId}`);
            if (lineSubtotal && data.item_subtotal) {
                lineSubtotal.textContent = data.item_subtotal;
            }

            updateCartBadge(data.cart_count);
            refreshCartSummaryDOM(data.summary);
            showToast('Cart updated', 'success');
        } else {
            showToast(data.message || 'Error updating cart', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Network error', 'error');
    }
}

function refreshCartSummaryDOM(summary) {
    if (!summary) return;
    const subtotalEl = document.getElementById('cart-subtotal-val');
    const shippingEl = document.getElementById('cart-shipping-val');
    const taxEl = document.getElementById('cart-tax-val');
    const grandTotalEl = document.getElementById('cart-grand-total-val');
    const freeShippingBanner = document.getElementById('free-shipping-progress-banner');

    if (subtotalEl) subtotalEl.textContent = summary.formatted_subtotal;
    if (shippingEl) shippingEl.textContent = summary.shipping === 0 ? 'FREE' : summary.formatted_shipping;
    if (taxEl) taxEl.textContent = summary.formatted_tax;
    if (grandTotalEl) grandTotalEl.textContent = summary.formatted_grand_total;

    if (freeShippingBanner) {
        if (summary.is_free_shipping) {
            freeShippingBanner.innerHTML = '<span class="text-emerald-600 font-semibold"><i class="fa-solid fa-truck-fast mr-1"></i> You unlocked Free Shipping!</span>';
        } else {
            freeShippingBanner.innerHTML = `Add <span class="font-bold text-slate-900">$${summary.free_shipping_needed.toFixed(2)}</span> more to qualify for Free Shipping!`;
        }
    }
}

/**
 * Plus / Minus Quantity Controls
 */
function initQuantitySelectors() {
    document.addEventListener('click', (e) => {
        const btnMinus = e.target.closest('.qty-btn-minus');
        const btnPlus = e.target.closest('.qty-btn-plus');

        if (btnMinus) {
            const input = btnMinus.parentElement.querySelector('input[type="number"]');
            if (input) {
                let val = parseInt(input.value) || 1;
                if (val > 1) {
                    input.value = val - 1;
                    input.dispatchEvent(new Event('change'));
                }
            }
        }

        if (btnPlus) {
            const input = btnPlus.parentElement.querySelector('input[type="number"]');
            if (input) {
                let max = parseInt(input.getAttribute('max')) || 999;
                let val = parseInt(input.value) || 1;
                if (val < max) {
                    input.value = val + 1;
                    input.dispatchEvent(new Event('change'));
                } else {
                    showToast(`Maximum stock limit of ${max} reached`, 'warning');
                }
            }
        }
    });
}

/**
 * Product Details Multi-Image Gallery Switcher
 */
function initImageGallery() {
    const mainImg = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.gallery-thumbnail');

    if (!mainImg || !thumbnails.length) return;

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', () => {
            thumbnails.forEach(t => t.classList.remove('border-indigo-600', 'ring-2', 'ring-indigo-600/30'));
            thumb.classList.add('border-indigo-600', 'ring-2', 'ring-indigo-600/30');

            const newSrc = thumb.dataset.src;
            if (newSrc) {
                mainImg.style.opacity = '0.5';
                mainImg.src = newSrc;
                setTimeout(() => mainImg.style.opacity = '1', 150);
            }
        });
    });
}

/**
 * Mobile Navigation Toggle
 */
function initMobileMenu() {
    const toggleBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu-drawer');
    const closeBtn = document.getElementById('mobile-menu-close');

    if (toggleBtn && mobileMenu) {
        toggleBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    if (closeBtn && mobileMenu) {
        closeBtn.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
        });
    }
}

/**
 * User & Category Dropdowns
 */
function initDropdowns() {
    const dropdownTriggers = document.querySelectorAll('[data-dropdown-toggle]');
    
    dropdownTriggers.forEach(trigger => {
        const targetId = trigger.getAttribute('data-dropdown-toggle');
        const target = document.getElementById(targetId);
        
        if (!target) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            target.classList.toggle('hidden');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    });
}
