/**
 * cart_process.js
 * Single source of truth for ALL cart JS across every page.
 * Loaded by: checkout.php, index.php (shop), item.php, any page with cart.php component.
 *
 * PATHS: Always uses /functions/... (absolute from web root).
 *        Override by setting window.CART_BASE before loading this file.
 */

(function () {
    'use strict';

    // ── Base path ─────────────────────────────────────────────────────────────
    // Set window.CART_BASE = '/your-path' BEFORE including this file to override.
    const BASE = (window.CART_BASE || '/sjfbi-js').replace(/\/$/, '');
    window.CART_BASE = BASE; // expose so products_patch.js and item.php can use it

    // ── TOAST ─────────────────────────────────────────────────────────────────
    window.showToast = function (message, type) {
        type = type || 'success';

        var palette = {
            success: 'background:linear-gradient(135deg,#10b981,#059669)',
            error:   'background:linear-gradient(135deg,#dc2626,#991b1b)',
            remove:  'background:linear-gradient(135deg,#ef4444,#dc2626)'
        };
        var icons = {
            success: '<svg style="width:20px;height:20px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>',
            error:   '<svg style="width:20px;height:20px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>',
            remove:  '<svg style="width:20px;height:20px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>'
        };

        // Inject keyframes once
        if (!document.getElementById('_cart_toast_kf')) {
            var s = document.createElement('style');
            s.id = '_cart_toast_kf';
            s.textContent = [
                '@keyframes _toastIn{from{opacity:0;transform:translateX(60px)}to{opacity:1;transform:none}}',
                '@keyframes _toastOut{from{opacity:1;transform:none}to{opacity:0;transform:translateX(60px)}}',
                '@keyframes _cntBounce{0%,100%{transform:scale(1)}50%{transform:scale(1.45)}}'
            ].join('');
            document.head.appendChild(s);
        }

        // Ensure container
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.setAttribute('style',
                'position:fixed;bottom:5rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none');
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.setAttribute('style', [
            'display:flex;align-items:center;gap:12px',
            'padding:14px 20px;border-radius:12px',
            'box-shadow:0 4px 12px rgba(0,0,0,.2)',
            'font-size:.9rem;font-weight:600;color:#fff',
            'min-width:260px;max-width:380px',
            'pointer-events:auto',
            'animation:_toastIn .3s ease both',
            palette[type] || palette.success
        ].join(';'));
        toast.innerHTML = (icons[type] || '') + '<span style="flex:1">' + message + '</span>';
        container.appendChild(toast);

        setTimeout(function () {
            toast.style.animation = '_toastOut .3s ease both';
            setTimeout(function () { toast.remove(); }, 320);
        }, 3200);
    };

    // ── UPDATE CART COUNT BADGES ───────────────────────────────────────────────
    // Blasts count into every possible badge selector — covers nav regardless of its class/id
    function _applyCountToDOM(n) {
        var num = parseInt(n) || 0;
        ['.cart-count', '#cart-count-sidebar', '#cart-count',
         '.cart-badge', '.nav-cart-count', '[data-cart-count]'
        ].forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                el.textContent = num;
                el.style.animation = 'none';
                void el.offsetWidth;
                el.style.animation = '_cntBounce .4s ease both';
            });
        });
    }

    // Public: applies n immediately AND re-fetches from server to sync all badges
    window.updateCartCount = function (n) {
        if (n !== undefined && n !== null) _applyCountToDOM(n);
        fetch(BASE + '/functions/get_cart_count.php', { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) { _applyCountToDOM(d.cart_count); })
            .catch(function () {});
    };

    // ── RECALC TOTALS (DOM-only, zero server calls) ────────────────────────────
    window.recalcTotals = function () {
        var total = 0;

        document.querySelectorAll('#cart-items-list .cart-item').forEach(function (item) {
            var qtyEl   = item.querySelector('.quantity');
            var qty     = parseFloat(qtyEl ? qtyEl.value : 0) || 0;
            var price   = parseFloat(item.dataset.pricePerUnit) || 0;
            var line    = qty * price;
            total += line;

            // Update per-item price label
            var priceEl = item.querySelector('.item-price, .price');
            if (priceEl) {
                priceEl.textContent = '₱' + line.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            }
        });

        var fmt = total.toLocaleString('en-PH', { minimumFractionDigits: 2 });

        var sidebarTotal = document.getElementById('cart-total-sidebar');
        if (sidebarTotal) sidebarTotal.textContent = '₱' + fmt;

        var grandTotal = document.getElementById('cart-grand-total');
        if (grandTotal) grandTotal.textContent = '₱' + fmt;

        var hiddenTotal = document.getElementById('total_amount');
        if (hiddenTotal) hiddenTotal.value = total.toFixed(2);
    };

    // ── APPLY QTY (validate → snap → DOM → silent server sync) ────────────────
    window.applyQty = function (item, newQty) {
        var unitType  = item.dataset.unitType     || 'piece';
        var minOrder  = parseFloat(item.dataset.minimumOrder)   || 1;
        var orderIncr = parseFloat(item.dataset.orderIncrement) || 1;
        var qtyInput  = item.querySelector('.quantity');
        var decBtn    = item.querySelector('.decrease-quantity');

        if (newQty < minOrder) {
            showToast('Minimum order is ' + minOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType), 'error');
            newQty = minOrder;
        }

        // Snap to nearest valid increment
        var snapped = minOrder + Math.round((newQty - minOrder) / orderIncr) * orderIncr;
        var display = unitType === 'piece' ? Math.round(snapped) : snapped;

        if (qtyInput) qtyInput.value = display;

        if (decBtn) {
            var atMin = display - orderIncr < minOrder;
            decBtn.disabled = atMin;
            decBtn.classList.toggle('opacity-40', atMin);
            decBtn.classList.toggle('cursor-not-allowed', atMin);
        }

        recalcTotals();
        _syncQtyToServer(parseInt(item.dataset.cartIndex), snapped);
    };

    // ── SILENT SERVER SYNC (debounced 300 ms per cart index) ──────────────────
    var _syncTimers = {};
    function _syncQtyToServer(cartIndex, quantity) {
        clearTimeout(_syncTimers[cartIndex]);
        _syncTimers[cartIndex] = setTimeout(function () {
            fetch(BASE + '/functions/update_cart_quantity.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ cart_index: cartIndex, quantity: quantity })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.status !== 'success') showToast(d.message || 'Sync error', 'error');
            })
            .catch(function () { /* silent — session corrects on next page load */ });
        }, 300);
    }

    // ── REMOVE ITEM ────────────────────────────────────────────────────────────
    window.removeCartItem = function (item) {
        var productId = item.dataset.productId;
        var variantId = item.dataset.variantId;

        // Immediately give visual feedback so user knows it's working
        item.style.opacity = '0.4';
        item.style.pointerEvents = 'none';

        fetch(BASE + '/functions/remove_from_cart.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ product_id: productId, variant_id: variantId })
        })
        .then(function (r) {
            // Always try to parse as JSON regardless of content-type header
            return r.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error: ' + text.substring(0, 200));
                }
            });
        })
        .then(function (d) {
            if (d.status !== 'success') {
                // Restore item if removal failed
                item.style.opacity = '';
                item.style.pointerEvents = '';
                showToast(d.message || 'Failed to remove item', 'error');
                return;
            }

            // Remove from DOM with animation, then hard-delete
            var el = document.querySelector(
                '#cart-items-list .cart-item[data-product-id="' + productId + '"][data-variant-id="' + variantId + '"]'
            ) || item;

            if (el) {
                el.style.transition = 'opacity 0.2s, max-height 0.3s, padding 0.3s, margin 0.3s';
                el.style.overflow   = 'hidden';
                el.style.opacity    = '0';
                el.style.maxHeight  = el.offsetHeight + 'px';
                // Trigger reflow then collapse
                void el.offsetHeight;
                el.style.maxHeight  = '0';
                el.style.padding    = '0';
                el.style.margin     = '0';
                setTimeout(function () {
                    if (el.parentNode) el.parentNode.removeChild(el);
                }, 320);
            }

            showToast('Item removed from cart', 'remove');

            // Update totals from server data if available
            if (d.cart_total != null) {
                var fmt = parseFloat(d.cart_total).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                var st = document.getElementById('cart-total-sidebar');
                if (st) st.textContent = '\u20B1' + fmt;
                var gt = document.getElementById('cart-grand-total');
                if (gt) gt.textContent = '\u20B1' + fmt;
                var ht = document.getElementById('total_amount');
                if (ht) ht.value = parseFloat(d.cart_total).toFixed(2);
            } else {
                recalcTotals();
            }

            // Use server count as source of truth for badges
            var remaining = (d.cart_count != null) ? parseInt(d.cart_count) : null;
            if (remaining !== null) updateCartCount(remaining);

            // After animation completes, clean up empty state
            setTimeout(function () {
                var domCount = document.querySelectorAll('#cart-items-list .cart-item').length;
                var finalCount = remaining !== null ? remaining : domCount;
                if (remaining === null) updateCartCount(domCount);

                if (finalCount === 0) {
                    var list = document.getElementById('cart-items-list');
                    if (list) list.innerHTML = '<p style="text-align:center;color:#9ca3af;padding:2rem 0;font-size:.875rem">Your cart is empty.</p>';
                    var submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.className = submitBtn.className
                            .replace('bg-orange-600', 'bg-gray-200')
                            .replace('hover:bg-orange-500', '')
                            .replace('active:scale-95', 'cursor-not-allowed')
                            .replace('text-white', 'text-gray-400');
                        submitBtn.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg> Cart is empty';
                    }
                }
                _checkSubmitState();
            }, 350);
        })
        .catch(function (err) {
            // Restore item on error
            item.style.opacity = '';
            item.style.pointerEvents = '';
            showToast(err.message || 'Error removing item', 'error');
        });
    };

    // ── FULL REFRESH FROM SERVER (used after add-to-cart) ─────────────────────
    window.refreshCartFromServer = function () {
        return fetch(BASE + '/functions/fetch_cart_items.php')
            .then(function (r) {
                var ct = r.headers.get('content-type') || '';
                if (!ct.includes('application/json')) return r.text().then(function (t) { throw new Error(t); });
                return r.json();
            })
            .then(function (data) {
                if (data.status === 'error') throw new Error(data.message);

                var list = document.getElementById('cart-items-list');
                if (list && data.cart_items) list.innerHTML = data.cart_items;

                if (data.cart_total != null) {
                    var fmt = parseFloat(data.cart_total).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    var st = document.getElementById('cart-total-sidebar');
                    if (st) st.textContent = '₱' + fmt;
                    var gt = document.getElementById('cart-grand-total');
                    if (gt) gt.textContent = '₱' + fmt;
                    var ht = document.getElementById('total_amount');
                    if (ht) ht.value = parseFloat(data.cart_total).toFixed(2);
                }

                if (data.cart_count != null) updateCartCount(data.cart_count);

                _initDecreaseStates();
            })
            .catch(function (err) { showToast(err.message || 'Error refreshing cart', 'error'); });
    };

    // Alias used by older code in cart.php / item.php
    window.updateCartUI = window.refreshCartFromServer;

    // ── INTERNAL ──────────────────────────────────────────────────────────────
    function _initDecreaseStates() {
        document.querySelectorAll('#cart-items-list .cart-item').forEach(function (item) {
            var qtyInput = item.querySelector('.quantity');
            var decBtn   = item.querySelector('.decrease-quantity');
            if (!qtyInput || !decBtn) return;
            var qty   = parseFloat(qtyInput.value);
            var min   = parseFloat(item.dataset.minimumOrder) || 1;
            var incr  = parseFloat(item.dataset.orderIncrement) || 1;
            var atMin = qty - incr < min;
            decBtn.disabled = atMin;
            decBtn.classList.toggle('opacity-40', atMin);
            decBtn.classList.toggle('cursor-not-allowed', atMin);
        });
    }

    function _checkSubmitState() {
        var errorItems = document.querySelectorAll('#cart-items-list .cart-item-error');
        var btn = document.getElementById('submitBtn');
        if (!btn || errorItems.length > 0) return;
        btn.disabled = false;
        btn.className = btn.className
            .replace('bg-gray-200', 'bg-orange-600')
            .replace('text-gray-400', 'text-white')
            .replace('cursor-not-allowed', 'active:scale-95');
        btn.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg> Complete Order';
        var banner = document.getElementById('cartErrorBanner');
        if (banner) banner.remove();
    }

    // ── EVENT DELEGATION (one listener covers dynamically added rows) ──────────
    var _attached = false;
    window.initCartHandlers = function () {
        if (_attached) return;
        _attached = true;

        document.addEventListener('click', function (e) {
            // + button
            var inc = e.target.closest ? e.target.closest('.increase-quantity') : null;
            if (inc) {
                var item = inc.closest('.cart-item');
                if (!item) return;
                var qty  = parseFloat(item.querySelector('.quantity').value) || parseFloat(item.dataset.minimumOrder);
                var incr = parseFloat(item.dataset.orderIncrement);
                applyQty(item, qty + incr);
                return;
            }

            // − button
            var dec = e.target.closest ? e.target.closest('.decrease-quantity') : null;
            if (dec) {
                var item2 = dec.closest('.cart-item');
                if (!item2) return;
                var qty2  = parseFloat(item2.querySelector('.quantity').value) || parseFloat(item2.dataset.minimumOrder);
                var incr2 = parseFloat(item2.dataset.orderIncrement);
                applyQty(item2, qty2 - incr2);
                return;
            }

            // Remove button
            var rmv = e.target.closest ? e.target.closest('.remove') : null;
            if (rmv) {
                e.preventDefault(); // prevent any form interaction
                e.stopPropagation();
                var item3 = rmv.closest('.cart-item');
                if (item3) removeCartItem(item3);
            }
        });

        // Live price preview while typing
        document.addEventListener('input', function (e) {
            if (!e.target.classList.contains('quantity')) return;
            var item = e.target.closest ? e.target.closest('.cart-item') : null;
            if (!item) return;
            var price   = parseFloat(item.dataset.pricePerUnit) || 0;
            var qty     = parseFloat(e.target.value) || 0;
            var priceEl = item.querySelector('.item-price, .price');
            if (priceEl) priceEl.textContent = '₱' + (price * qty).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            recalcTotals();
        });

        // Commit typed value on blur
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('quantity')) return;
            var item = e.target.closest ? e.target.closest('.cart-item') : null;
            if (!item) return;
            var typed = parseFloat(e.target.value);
            if (isNaN(typed) || typed <= 0) e.target.value = item.dataset.minimumOrder;
            applyQty(item, parseFloat(e.target.value));
        });

        // Enter key commits without form-submit
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || !e.target.classList.contains('quantity')) return;
            e.preventDefault();
            e.target.blur();
        });
    };

    // Auto-init on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function () {
        initCartHandlers();
        _initDecreaseStates();
        // Sync ALL cart count badges from server on every page load
        updateCartCount();
    });

}());