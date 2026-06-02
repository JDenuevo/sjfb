(function () {
    'use strict';

    const BASE = (window.CART_BASE || '/sjfbi-js').replace(/\/$/, '');
    window.CART_BASE = BASE;

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
        if (!document.getElementById('_cart_toast_kf')) {
            var s = document.createElement('style');
            s.id = '_cart_toast_kf';
            s.textContent = '@keyframes _toastIn{from{opacity:0;transform:translateX(60px)}to{opacity:1;transform:none}}@keyframes _toastOut{from{opacity:1;transform:none}to{opacity:0;transform:translateX(60px)}}@keyframes _cntBounce{0%,100%{transform:scale(1)}50%{transform:scale(1.45)}}';
            document.head.appendChild(s);
        }
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.setAttribute('style', 'position:fixed;bottom:5rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none');
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

    // ── CART COUNT ────────────────────────────────────────────────────────────
    function _applyCountToDOM(n) {
        var num = parseInt(n) || 0;
        ['.cart-count','#cart-count-sidebar','#cart-count','.cart-badge','.nav-cart-count','[data-cart-count]'].forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                el.textContent = num;
                el.style.animation = 'none';
                void el.offsetWidth;
                el.style.animation = '_cntBounce .4s ease both';
            });
        });
    }
    window.updateCartCount = function (n) {
        if (n !== undefined && n !== null) _applyCountToDOM(n);
        fetch(BASE + '/functions/get_cart_count.php', { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) { _applyCountToDOM(d.cart_count); })
            .catch(function () {});
    };

    // ── CHECKOUT ORDER SUMMARY RELOAD ─────────────────────────────────────────
    // This is the SINGLE source of truth for all checkout totals.
    // It sets every number — subtotal, delivery, discount, grand total, hidden fields.
    // Nothing else should be recalculating totals on the checkout page.
    var _summaryDebounce = null;

    function _reloadOrderSummary() {
        var city     = (document.getElementById('City') || {}).value || '';
        var discount = parseFloat((document.getElementById('discount_amount_input') || {}).value || 0) || 0;

        var url = BASE + '/functions/fetch_order_summary.php?city=' + encodeURIComponent(city) + '&discount=' + discount;

        fetch(url)
            .then(function (r) { return r.json(); })
            // ── FIX 1: response object was named 'd' throughout but one block
            //           accidentally referenced 'data' (undefined) — causing a
            //           ReferenceError that aborted the entire update and fell
            //           through to recalcTotals() which ignored delivery fee.
            //           Now all references consistently use 'd'. ──────────────
            .then(function (d) {
                if (!d.success) return;

                // Cart items HTML
                var list = document.getElementById('cart-items-list');
                if (list && d.items_html) list.innerHTML = d.items_html;

                // Subtotal
                var st = document.getElementById('cart-subtotal');
                if (st) st.textContent = '₱' + parseFloat(d.subtotal).toLocaleString('en-PH', { minimumFractionDigits: 2 });

                // Delivery fee display
                var fd = document.getElementById('delivery-fee-display');
                if (fd) {
                    fd.textContent = d.fee_display;
                    fd.className   = d.fee_class || '';
                }

                // City hint (fuzzy match / fallback)  ← FIX: was referencing 'data' not 'd'
                var cityHint = document.getElementById('city-fee-preview');
                if (cityHint) {
                    if (d.fuzzy_match) {
                        cityHint.innerHTML =
                            '⚠️ Matched to <strong>' + d.city + '</strong> — ' +
                            '<a href="#" id="city-confirm-link" class="text-orange-500 underline">use this city?</a>';
                        var lnk = document.getElementById('city-confirm-link');
                        if (lnk) lnk.addEventListener('click', function (e) {
                            e.preventDefault();
                            var citySelect = document.getElementById('City');
                            if (citySelect) citySelect.value = d.city;
                            cityHint.textContent = '';
                            _reloadOrderSummary();
                        });
                    } else if (d.fallback) {
                        cityHint.textContent = '⚠️ City not found — default ₱250.00 fee applies.';
                    } else {
                        cityHint.textContent = '';
                    }
                }

                // Discount line
                var dl = document.getElementById('discount-line-container');
                var da = document.getElementById('discount-amount');
                if (d.discount && parseFloat(d.discount) > 0) {
                    if (dl) dl.classList.remove('hidden');
                    if (da) da.textContent = '-₱' + parseFloat(d.discount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                }

                // ── Grand total — server is authoritative, set it directly ──
                var gt = document.getElementById('cart-grand-total');
                if (gt) gt.textContent = '₱' + parseFloat(d.grand_total).toLocaleString('en-PH', { minimumFractionDigits: 2 });

                // Hidden form fields (submitted to add.php)
                var si = document.getElementById('subtotal_input');
                if (si) si.value = parseFloat(d.subtotal).toFixed(2);

                // ── FIX 2: set delivery_fee_input WITHOUT the intercepted value
                //           setter (which was one of three sources triggering a
                //           racing recalcGrandTotal). Use the raw prototype set. ──
                var fi = document.getElementById('delivery_fee_input');
                if (fi) {
                    // Write directly via prototype to avoid any overridden setter
                    Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')
                          .set.call(fi, parseFloat(d.delivery_fee).toFixed(2));
                }

                var ta = document.getElementById('total_amount');
                if (ta) ta.value = parseFloat(d.grand_total).toFixed(2);

                // Cart count badge
                if (d.cart_count != null) _applyCountToDOM(d.cart_count);

                // Re-init decrease button states after HTML replacement
                _initDecreaseStates();

                // Notify to_checkout.php stock checker
                if (typeof window._checkAllStock === 'function') window._checkAllStock();
            })
            .catch(function (err) {
                // On network error fall back to lightweight DOM recalc
                // (does NOT include delivery fee — only used as last resort)
                console.warn('Order summary reload failed:', err);
                recalcTotals();
            });
    }

    // ── RECALC TOTALS (non-checkout pages / last-resort fallback only) ────────
    // This sums up line items in the cart sidebar. It does NOT know the delivery
    // fee so it should NEVER be used as the primary calculator on checkout.php.
    window.recalcTotals = function () {
        var total = 0;
        document.querySelectorAll('#cart-items-list .cart-item').forEach(function (item) {
            var qtyEl = item.querySelector('.quantity');
            var qty   = parseFloat(qtyEl ? qtyEl.value : 0) || 0;
            var price = parseFloat(item.dataset.pricePerUnit) || 0;
            var line  = qty * price;
            total += line;
            var priceEl = item.querySelector('.item-price, .price');
            if (priceEl) priceEl.textContent = '₱' + line.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        });

        var fmt = total.toLocaleString('en-PH', { minimumFractionDigits: 2 });

        var sidebarTotal = document.getElementById('cart-total-sidebar');
        if (sidebarTotal) sidebarTotal.textContent = '₱' + fmt;

        // On checkout: do NOT touch grand-total or total_amount here.
        // Those are owned by _reloadOrderSummary which includes delivery + discount.
        var onCheckout = typeof window.updateDeliveryFee === 'function';
        if (!onCheckout) {
            var subtotalEl = document.getElementById('cart-subtotal');
            if (subtotalEl) subtotalEl.textContent = '₱' + fmt;
            var gt = document.getElementById('cart-grand-total');
            if (gt) gt.textContent = '₱' + fmt;
            var ht = document.getElementById('total_amount');
            if (ht) ht.value = total.toFixed(2);
        }
    };

    // ── APPLY QTY ─────────────────────────────────────────────────────────────
    window.applyQty = function (item, newQty) {
        var unitType  = item.dataset.unitType      || 'piece';
        var minOrder  = parseFloat(item.dataset.minimumOrder)   || 1;
        var orderIncr = parseFloat(item.dataset.orderIncrement) || 1;
        var qtyInput  = item.querySelector('.quantity');
        var decBtn    = item.querySelector('.decrease-quantity');

        if (newQty < minOrder) {
            showToast('Minimum order is ' + minOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType), 'error');
            newQty = minOrder;
        }

        var snapped = minOrder + Math.round((newQty - minOrder) / orderIncr) * orderIncr;
        var display = unitType === 'piece' ? Math.round(snapped) : parseFloat(snapped.toFixed(3));

        if (qtyInput) qtyInput.value = display;

        // Optimistic UI: update line price immediately for instant feedback
        var price   = parseFloat(item.dataset.pricePerUnit) || 0;
        var lineAmt = display * price;
        var priceEl = item.querySelector('.item-price');
        if (priceEl) priceEl.textContent = '₱' + lineAmt.toLocaleString('en-PH', { minimumFractionDigits: 2 });

        if (decBtn) {
            var atMin = display - orderIncr < minOrder;
            decBtn.disabled = atMin;
            decBtn.classList.toggle('opacity-40', atMin);
            decBtn.classList.toggle('cursor-not-allowed', atMin);
        }

        // Sync to server, then reload the full summary (checkout) or recalc (elsewhere)
        _syncQtyToServer(parseInt(item.dataset.cartIndex), snapped, function () {
            if (typeof window.updateDeliveryFee === 'function') {
                // CHECKOUT: always reload full summary from server (single source of truth)
                clearTimeout(_summaryDebounce);
                _summaryDebounce = setTimeout(_reloadOrderSummary, 150);
            } else {
                // NON-CHECKOUT: lightweight DOM recalc is sufficient
                recalcTotals();
            }
        });
    };

    // ── SERVER SYNC ───────────────────────────────────────────────────────────
    var _syncTimers = {};
    function _syncQtyToServer(cartIndex, quantity, callback) {
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
                if (typeof callback === 'function') callback();
            })
            .catch(function () {
                if (typeof callback === 'function') callback();
            });
        }, 200);
    }

    // ── REMOVE ITEM ───────────────────────────────────────────────────────────
    window.removeCartItem = function (item) {
        var productId = item.dataset.productId;
        var variantId = item.dataset.variantId;

        item.style.opacity      = '0.4';
        item.style.pointerEvents = 'none';

        fetch(BASE + '/functions/remove_from_cart.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ product_id: productId, variant_id: variantId })
        })
        .then(function (r) {
            return r.text().then(function (text) {
                try { return JSON.parse(text); }
                catch (e) { throw new Error('Server error: ' + text.substring(0, 200)); }
            });
        })
        .then(function (d) {
            if (d.status !== 'success') {
                item.style.opacity      = '';
                item.style.pointerEvents = '';
                showToast(d.message || 'Failed to remove item', 'error');
                return;
            }

            // Animate removal
            var el = document.querySelector(
                '#cart-items-list .cart-item[data-product-id="' + productId + '"][data-variant-id="' + variantId + '"]'
            ) || item;
            if (el) {
                el.style.transition = 'opacity 0.2s, max-height 0.3s, padding 0.3s, margin 0.3s';
                el.style.overflow   = 'hidden';
                el.style.opacity    = '0';
                el.style.maxHeight  = el.offsetHeight + 'px';
                void el.offsetHeight;
                el.style.maxHeight  = '0';
                el.style.padding    = '0';
                el.style.margin     = '0';
                setTimeout(function () {
                    if (el.parentNode) el.parentNode.removeChild(el);
                    if (typeof window.updateDeliveryFee === 'function') {
                        _reloadOrderSummary();
                    } else {
                        recalcTotals();
                    }
                }, 320);
            }

            showToast('Item removed from cart', 'remove');
            if (d.cart_count != null) updateCartCount(d.cart_count);

            setTimeout(function () {
                var domCount = document.querySelectorAll('#cart-items-list .cart-item').length;
                if (domCount === 0) {
                    var list = document.getElementById('cart-items-list');
                    if (list) list.innerHTML = '<p style="text-align:center;color:#9ca3af;padding:2rem 0;font-size:.875rem">Your cart is empty.</p>';
                    var submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.className = submitBtn.className
                            .replace('bg-orange-600','bg-gray-200')
                            .replace('hover:bg-orange-500','')
                            .replace('active:scale-95','cursor-not-allowed')
                            .replace('text-white','text-gray-400');
                        submitBtn.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg> Cart is empty';
                    }
                }
            }, 400);
        })
        .catch(function (err) {
            item.style.opacity      = '';
            item.style.pointerEvents = '';
            showToast(err.message || 'Error removing item', 'error');
        });
    };

    // ── REFRESH FROM SERVER (cart sidebar on non-checkout pages) ──────────────
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
                    var st  = document.getElementById('cart-total-sidebar');
                    if (st) st.textContent = '₱' + fmt;
                }

                if (data.cart_count != null) updateCartCount(data.cart_count);
                _initDecreaseStates();
            })
            .catch(function (err) { showToast(err.message || 'Error refreshing cart', 'error'); });
    };
    window.updateCartUI = window.refreshCartFromServer;

    // Expose reload for to_checkout.php
    window.reloadOrderSummary = _reloadOrderSummary;

    // ── INTERNALS ─────────────────────────────────────────────────────────────
    function _initDecreaseStates() {
        document.querySelectorAll('#cart-items-list .cart-item').forEach(function (item) {
            var qtyInput = item.querySelector('.quantity');
            var decBtn   = item.querySelector('.decrease-quantity');
            if (!qtyInput || !decBtn) return;
            var qty   = parseFloat(qtyInput.value);
            var min   = parseFloat(item.dataset.minimumOrder)   || 1;
            var incr  = parseFloat(item.dataset.orderIncrement) || 1;
            var atMin = qty - incr < min;
            decBtn.disabled = atMin;
            decBtn.classList.toggle('opacity-40', atMin);
            decBtn.classList.toggle('cursor-not-allowed', atMin);
        });
    }

    // ── EVENT DELEGATION ──────────────────────────────────────────────────────
    var _attached = false;
    window.initCartHandlers = function () {
        if (_attached) return;
        _attached = true;

        document.addEventListener('click', function (e) {
            var cartItem = e.target.closest ? e.target.closest('#cart-items-list .cart-item') : null;
            if (!cartItem) return;

            if (e.target.closest('.increase-quantity')) {
                var qty  = parseFloat(cartItem.querySelector('.quantity').value) || parseFloat(cartItem.dataset.minimumOrder);
                applyQty(cartItem, qty + parseFloat(cartItem.dataset.orderIncrement));
                return;
            }
            if (e.target.closest('.decrease-quantity')) {
                var qty2 = parseFloat(cartItem.querySelector('.quantity').value) || parseFloat(cartItem.dataset.minimumOrder);
                applyQty(cartItem, qty2 - parseFloat(cartItem.dataset.orderIncrement));
                return;
            }
            if (e.target.closest('.remove')) {
                e.preventDefault();
                e.stopPropagation();
                removeCartItem(cartItem);
            }
        });

        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('quantity')) return;
            var cartItem = e.target.closest ? e.target.closest('#cart-items-list .cart-item') : null;
            if (!cartItem) return;
            var typed = parseFloat(e.target.value);
            if (isNaN(typed) || typed <= 0) e.target.value = cartItem.dataset.minimumOrder;
            applyQty(cartItem, parseFloat(e.target.value));
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || !e.target.classList.contains('quantity')) return;
            if (!e.target.closest('#cart-items-list .cart-item')) return;
            e.preventDefault();
            e.target.blur();
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        initCartHandlers();
        _initDecreaseStates();
        updateCartCount();
    });

}());