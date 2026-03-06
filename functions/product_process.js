/**
 * product_process.js
 * Drop-in replacement for the two <script> blocks at the bottom of products.php.
 *
 * Requires: cart_process.js already loaded on the page (provides showToast,
 *           refreshCartFromServer, initCartHandlers).
 *
 * What this file handles:
 *   - Category / price / origin filter URL rewrites
 *   - Search autocomplete + performSearch
 *   - Variant selection + quantity buttons on product cards
 *   - Add-to-cart form submit
 *   - Share buttons
 */

// ─────────────────────────────────────────────────────────────────────────────
// FILTER HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function handleCategoryChange(checkbox) {
    var url = new URL(window.location);
    var selected = [];
    document.querySelectorAll('.category-filter:checked').forEach(function (cb) {
        if (cb.value !== 'all') selected.push(cb.value);
    });
    var allCb = document.querySelector('.category-filter[value="all"]');
    if (checkbox.value === 'all') {
        if (checkbox.checked) {
            document.querySelectorAll('.category-filter:not([value="all"])').forEach(function (cb) { cb.checked = false; });
            selected = [];
        }
    } else {
        if (allCb) allCb.checked = false;
    }
    if (selected.length > 0) {
        url.searchParams.set('category', selected.join(','));
    } else {
        url.searchParams.delete('category');
        if (allCb) allCb.checked = true;
    }
    var sq = document.getElementById('searchInput');
    if (sq && sq.value) url.searchParams.set('search', sq.value);
    window.location.href = url.toString();
}

function handleOriginChange(checkbox) {
    var url = new URL(window.location);
    var selected = [];
    document.querySelectorAll('.origin-filter:checked').forEach(function (cb) { selected.push(cb.value); });
    if (selected.length > 0) url.searchParams.set('origin', selected.join(','));
    else url.searchParams.delete('origin');
    var sq = document.getElementById('searchInput');
    if (sq && sq.value) url.searchParams.set('search', sq.value);
    window.location.href = url.toString();
}

function clearAllFilters() {
    var url = new URL(window.location);
    ['category','price','origin','search','page'].forEach(function (k) { url.searchParams.delete(k); });
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    // Price filter
    document.querySelectorAll('.price-filter').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var url = new URL(window.location);
            url.searchParams.set('price', this.value);
            var sq = document.getElementById('searchInput');
            if (sq && sq.value) url.searchParams.set('search', sq.value);
            window.location.href = url.toString();
        });
    });
    // All-products checkbox default
    var urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.get('category')) {
        var allCb = document.querySelector('.category-filter[value="all"]');
        if (allCb) allCb.checked = true;
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// PRODUCT CARD INIT (variant selection, qty buttons, add-to-cart)
// ─────────────────────────────────────────────────────────────────────────────
function initializeProductFunctionality() {

    // ── Variant selection ──────────────────────────────────────────────────────
    document.querySelectorAll('.variant-button').forEach(function (button) {
        button.addEventListener('click', function () {
            var productId    = button.dataset.productId;
            var variantPrice = parseFloat(button.dataset.variantPrice);
            var discountPrice= parseFloat(button.dataset.discountPrice);
            var unitType     = button.dataset.unitType;
            var minimumOrder = parseFloat(button.dataset.minimumOrder);
            var orderIncr    = parseFloat(button.dataset.orderIncrement);

            var form = document.querySelector('.add-to-cart-form[data-product-id="' + productId + '"]');
            if (!form) return;

            // Deselect siblings
            form.querySelectorAll('.variant-button').forEach(function (b) { b.classList.remove('selected-variant'); });
            button.classList.add('selected-variant');

            // Update hidden fields
            form.querySelector('[name="variant_id"]').value   = button.dataset.variantId;
            form.querySelector('[name="variant_name"]').value = button.dataset.variantName;
            form.querySelector('[name="price"]').value        = discountPrice > 0 ? discountPrice : variantPrice;
            form.querySelector('[name="unit_type"]').value    = unitType;
            form.querySelector('[name="minimum_order"]').value  = minimumOrder;
            form.querySelector('[name="order_increment"]').value = orderIncr;

            // Reset qty to minimum
            var qtyInput = form.querySelector('.quantity');
            if (qtyInput) {
                // Set min/step FIRST so type="number" validation accepts the value
                qtyInput.min  = minimumOrder;
                qtyInput.step = orderIncr;
                qtyInput.value = unitType === 'piece' ? Math.round(minimumOrder) : minimumOrder;
                // Also store in hidden quantity field if present
                var hidQty = form.querySelector('[name="quantity"]');
                if (hidQty) hidQty.value = minimumOrder;
            }

            // Unit label
            var unitDisp = form.querySelector('.unit-display');
            if (unitDisp) unitDisp.textContent = unitType === 'piece' ? 'pcs' : unitType;

            // Min order text
            var minText = form.querySelector('.minimum-order-text');
            if (minText) minText.textContent = 'Minimum: ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);

            // Price display
            _updateCardPriceDisplay(form, variantPrice, discountPrice, minimumOrder);

            // Enable submit
            var submitBtn = form.querySelector('[name="add_to_cart"]');
            if (submitBtn) submitBtn.disabled = false;
            var vmsg = form.querySelector('.variant-message');
            if (vmsg) vmsg.classList.add('hidden');
            var emsg = form.querySelector('.minimum-error-message');
            if (emsg) emsg.classList.add('hidden');
        });
    });

    // ── Qty − buttons ──────────────────────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form .decrease-quantity').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form      = btn.closest('.add-to-cart-form');
            var qtyInput  = form.querySelector('.quantity');
            var minOrder  = parseFloat(form.querySelector('[name="minimum_order"]').value) || 1;
            var orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value) || 1;
            var currentQty = parseFloat(qtyInput.value) || minOrder;
            var newQty    = Math.max(minOrder, currentQty - orderIncr);
            qtyInput.min   = minOrder;
            qtyInput.step  = orderIncr;
            qtyInput.value = _fmtQty(newQty);
            var hidQty = form.querySelector('[name="quantity"]');
            if (hidQty) hidQty.value = newQty;
            _updateCardTotalPrice(form);
        });
    });

    // ── Qty + buttons ──────────────────────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form .increase-quantity').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form      = btn.closest('.add-to-cart-form');
            var qtyInput  = form.querySelector('.quantity');
            var minOrder  = parseFloat(form.querySelector('[name="minimum_order"]').value) || 1;
            var orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value) || 1;
            var currentQty = parseFloat(qtyInput.value) || minOrder;
            var newQty    = currentQty + orderIncr;
            qtyInput.min   = minOrder;
            qtyInput.step  = orderIncr;
            qtyInput.value = _fmtQty(newQty);
            var hidQty = form.querySelector('[name="quantity"]');
            if (hidQty) hidQty.value = newQty;
            _updateCardTotalPrice(form);
        });
    });

    // ── Typed qty → live price update ─────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form .quantity').forEach(function (input) {
        input.addEventListener('input', function () {
            var form  = input.closest('.add-to-cart-form');
            if (!form) return;
            var hidQty = form.querySelector('[name="quantity"]');
            if (hidQty) hidQty.value = parseFloat(input.value) || 0;
            _updateCardTotalPrice(form);
        });
        input.addEventListener('change', function () {
            var form     = input.closest('.add-to-cart-form');
            var minOrder = parseFloat(form.querySelector('[name="minimum_order"]').value) || 1;
            var orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value) || 1;
            var val      = parseFloat(input.value);
            if (isNaN(val) || val < minOrder) {
                val = minOrder;
                input.value = _fmtQty(val);
            }
            // Snap to increment
            val = minOrder + Math.round((val - minOrder) / orderIncr) * orderIncr;
            input.value = _fmtQty(val);
            var hidQty = form.querySelector('[name="quantity"]');
            if (hidQty) hidQty.value = val;
            _updateCardTotalPrice(form);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
        });
    });

    // ── Auto-select first in-stock variant ────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form').forEach(function (form) {
        var firstBtn = form.querySelector('.variant-button:not([disabled])');
        if (firstBtn) firstBtn.click();
    });

    // ── Add-to-cart submit ────────────────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var variantId   = form.querySelector('[name="variant_id"]').value;
            var quantity    = parseFloat(form.querySelector('[name="quantity"]').value);
            var minimumOrder= parseFloat(form.querySelector('[name="minimum_order"]').value);
            var unitType    = form.querySelector('[name="unit_type"]').value;
            var errMsg      = form.querySelector('.minimum-error-message');

            if (!variantId) {
                var vmsg = form.querySelector('.variant-message');
                if (vmsg) vmsg.classList.remove('hidden');
                return;
            }
            if (quantity < minimumOrder) {
                if (errMsg) {
                    errMsg.textContent = 'Minimum order is ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);
                    errMsg.classList.remove('hidden');
                }
                return;
            }

            try {
                var res  = await fetch((window.CART_BASE || '/sjfbi-js') + '/functions/add_to_cart.php', { method: 'POST', body: new FormData(form) });
                var data = await res.json();
                if (data.status === 'success') {
                    showToast('Product added to cart!', 'success');
                    await refreshCartFromServer();
                    // Reset to minimum after add
                    var firstBtn = form.querySelector('.variant-button:not([disabled])');
                    if (firstBtn) firstBtn.click();
                } else {
                    showToast(data.message || 'Failed to add product', 'error');
                }
            } catch (err) {
                showToast('An error occurred', 'error');
            }
        });
    });
}

// ── Price display helpers ──────────────────────────────────────────────────────
function _fmtQty(n) {
    var s = n.toFixed(2).replace(/\.?0+$/, '');
    return s === '' ? '0' : s;
}

function _updateCardPriceDisplay(form, variantPrice, discountPrice, quantity) {
    var el = form.querySelector('.price-display');
    if (!el) return;
    var price = discountPrice > 0 ? discountPrice : variantPrice;
    var total = price * quantity;
    if (discountPrice > 0) {
        el.innerHTML = '<span style="text-decoration:line-through;color:#9ca3af;font-size:.875rem">₱' + (variantPrice * quantity).toFixed(2) + '</span>' +
                       '<span style="color:#dc2626;font-weight:700;margin-left:.5rem">₱' + total.toFixed(2) + '</span>';
    } else {
        el.innerHTML = '<span style="color:#1f2937;font-weight:700">₱' + total.toFixed(2) + '</span>';
    }
}

function _updateCardTotalPrice(form) {
    var qtyInput     = form.querySelector('.quantity');
    var qty          = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
    var selectedBtn  = form.querySelector('.variant-button.selected-variant');
    if (!selectedBtn) return;
    var variantPrice  = parseFloat(selectedBtn.dataset.variantPrice) || 0;
    var discountPrice = parseFloat(selectedBtn.dataset.discountPrice) || 0;
    _updateCardPriceDisplay(form, variantPrice, discountPrice, qty);
}

// ─────────────────────────────────────────────────────────────────────────────
// SEARCH AUTOCOMPLETE + performSearch
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initializeProductFunctionality();

    var searchInput        = document.getElementById('searchInput');
    var autocompleteResults= document.getElementById('autocompleteResults');
    var clearSearchBtn     = document.getElementById('clearSearch');
    var productsContent    = document.getElementById('productsContent');
    var productsLoading    = document.getElementById('productsLoading');
    var searchTimeout;
    var currentSearchTerm  = searchInput ? searchInput.value.trim() : '';

    if (!searchInput) return;

    // Show/hide clear button
    searchInput.addEventListener('input', function () {
        if (this.value.trim().length > 0) clearSearchBtn && clearSearchBtn.classList.remove('hidden');
        else {
            clearSearchBtn && clearSearchBtn.classList.add('hidden');
            autocompleteResults && autocompleteResults.classList.add('hidden');
        }
    });

    // Clear button
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearSearchBtn.classList.add('hidden');
            if (autocompleteResults) autocompleteResults.classList.add('hidden');
            var url = new URL(window.location);
            url.searchParams.delete('search');
            window.history.pushState({}, '', url);
            performSearch('');
        });
    }

    // Autocomplete on typing
    searchInput.addEventListener('input', function (e) {
        currentSearchTerm = e.target.value.trim();
        clearTimeout(searchTimeout);
        if (currentSearchTerm.length < 1) {
            if (autocompleteResults) autocompleteResults.classList.add('hidden');
            return;
        }
        if (autocompleteResults) {
            autocompleteResults.innerHTML = '<div style="padding:1rem;text-align:center;color:#9ca3af">Loading...</div>';
            autocompleteResults.classList.remove('hidden');
        }
        searchTimeout = setTimeout(function () { fetchAutocompleteResults(currentSearchTerm); }, 200);
    });

    searchInput.addEventListener('focus', function () {
        if (!this.value.trim()) showPopularSearches();
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { if (autocompleteResults) autocompleteResults.classList.add('hidden'); }
        else if (e.key === 'ArrowDown') {
            var first = autocompleteResults && autocompleteResults.querySelector('.autocomplete-item');
            if (first) { e.preventDefault(); first.focus(); }
        } else if (e.key === 'Enter' && currentSearchTerm.length > 0) {
            e.preventDefault(); performSearch(currentSearchTerm);
        }
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && autocompleteResults && !autocompleteResults.contains(e.target)) {
            autocompleteResults.classList.add('hidden');
        }
    });

    async function fetchAutocompleteResults(query) {
        try {
            var res     = await fetch((window.CART_BASE || '/sjfbi-js') + '/functions/auto_complete.php?query=' + encodeURIComponent(query) + '&limit=8');
            var results = await res.json();
            displayAutocompleteResults(results);
        } catch (err) {
            if (autocompleteResults) autocompleteResults.innerHTML = '<div style="padding:1rem;color:#dc2626;text-align:center">Error loading results</div>';
        }
    }

    function highlightText(text, query) {
        if (!query || !text) return text;
        var esc   = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var regex = new RegExp('(' + esc + ')', 'gi');
        return text.replace(regex, '<mark style="background:#fef08a;font-weight:600">$1</mark>');
    }

    function displayAutocompleteResults(results) {
        if (!autocompleteResults) return;
        if (!results.length) {
            autocompleteResults.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#9ca3af">No products found.</div>';
            autocompleteResults.classList.remove('hidden');
            return;
        }
        autocompleteResults.innerHTML = '';
        var header = document.createElement('div');
        header.style.cssText = 'padding:.5rem 1rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center';
        header.innerHTML = '<span style="font-size:.875rem;font-weight:600;color:#4b5563">' + results.length + ' results</span>' +
                           '<span style="font-size:.75rem;color:#9ca3af">Press Enter to search all</span>';
        autocompleteResults.appendChild(header);

        results.forEach(function (product) {
            var item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.tabIndex  = 0;
            item.style.cssText = 'padding:.75rem 1rem;cursor:pointer;border-bottom:1px solid #f3f4f6;transition:background .15s';
            item.onmouseenter = function () { this.style.background = '#f9fafb'; };
            item.onmouseleave = function () { this.style.background = ''; };

            var tagsHtml = '';
            if (product.tags && product.tags.length) {
                tagsHtml = '<div style="margin-top:.375rem">' +
                    product.tags.map(function (t) {
                        return '<span style="display:inline-block;padding:.125rem .5rem;background:#fff7ed;color:#c2410c;border-radius:9999px;font-size:.75rem;margin-right:.25rem">' +
                               highlightText(t, currentSearchTerm) + '</span>';
                    }).join('') + '</div>';
            }

            item.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem">' +
                '<div style="min-width:0">' +
                '<div style="font-size:.875rem;font-weight:600;color:#111827">' + highlightText(product.name, currentSearchTerm) + '</div>' +
                (product.variant ? '<div style="font-size:.75rem;color:#4b5563;margin-top:.125rem">' + highlightText(product.variant, currentSearchTerm) + '</div>' : '') +
                tagsHtml + '</div>' +
                '<span style="font-size:.75rem;background:#f3f4f6;color:#374151;padding:.25rem .625rem;border-radius:9999px;white-space:nowrap;flex-shrink:0">' +
                (product.category || 'General') + '</span></div>';

            item.addEventListener('click', function () {
                searchInput.value = product.name;
                performSearch(product.name);
                autocompleteResults.classList.add('hidden');
            });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); searchInput.value = product.name; performSearch(product.name); autocompleteResults.classList.add('hidden'); }
                else if (e.key === 'ArrowDown') { e.preventDefault(); var n = item.nextElementSibling; if (n && n.classList.contains('autocomplete-item')) n.focus(); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); var p = item.previousElementSibling; if (p && p.classList.contains('autocomplete-item')) p.focus(); else searchInput.focus(); }
                else if (e.key === 'Escape') { autocompleteResults.classList.add('hidden'); searchInput.focus(); }
            });
            autocompleteResults.appendChild(item);
        });
        autocompleteResults.classList.remove('hidden');
    }

    function showPopularSearches() {
        if (!autocompleteResults) return;
        var popular = ['Bangus','Tilapia','Lapu-Lapu','Crab','Salmon','Tuna'];
        autocompleteResults.innerHTML = '';
        var h = document.createElement('div');
        h.style.cssText = 'padding:.5rem 1rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:.875rem;font-weight:600;color:#4b5563';
        h.textContent = 'Popular Searches';
        autocompleteResults.appendChild(h);
        popular.forEach(function (term) {
            var item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.style.cssText = 'padding:.75rem 1rem;cursor:pointer;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;font-weight:500;color:#374151;transition:background .15s';
            item.onmouseenter = function () { this.style.background = '#f9fafb'; };
            item.onmouseleave = function () { this.style.background = ''; };
            item.textContent = term;
            item.addEventListener('click', function () { searchInput.value = term; performSearch(term); });
            autocompleteResults.appendChild(item);
        });
        autocompleteResults.classList.remove('hidden');
    }

    async function performSearch(query) {
        var url = new URL(window.location);
        if (query.trim()) url.searchParams.set('search', query);
        else url.searchParams.delete('search');
        window.history.pushState({}, '', url);

        if (productsContent) productsContent.style.opacity = '.5';
        if (productsLoading) productsLoading.classList.remove('hidden');

        try {
            var fetchUrl = (window.CART_BASE || '/sjfbi-js') + '/functions/fetch_products.php' + (query.trim() ? '?search=' + encodeURIComponent(query) : '');
            var res      = await fetch(fetchUrl);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            var html = await res.text();
            if (productsContent) productsContent.innerHTML = html;
            initializeProductFunctionality();
        } catch (err) {
            if (productsContent) productsContent.innerHTML = '<div style="text-align:center;padding:3rem;color:#dc2626">Error loading products. <button onclick="location.reload()" style="margin-left:.5rem;padding:.5rem 1rem;background:#2563eb;color:#fff;border-radius:.5rem;cursor:pointer">Reload</button></div>';
        } finally {
            if (productsContent) productsContent.style.opacity = '';
            if (productsLoading) productsLoading.classList.add('hidden');
            if (autocompleteResults) autocompleteResults.classList.add('hidden');
            if (query.trim()) {
                var container = document.getElementById('productsContainer');
                if (container) container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    window.addEventListener('popstate', function () {
        var p = new URLSearchParams(window.location.search);
        var sq = p.get('search');
        searchInput.value = sq || '';
        performSearch(sq || '');
    });

    if (currentSearchTerm) performSearch(currentSearchTerm);
});

// ─────────────────────────────────────────────────────────────────────────────
// SHARE BUTTONS (used in both products.php and item.php)
// ─────────────────────────────────────────────────────────────────────────────
function shareToFacebook(url) {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank', 'width=600,height=400,noopener,noreferrer');
}

function shareProduct(title, text, url) {
    var data = { title: title, text: text, url: url };
    if (navigator.share) {
        navigator.share(data).catch(function (err) { if (err.name !== 'AbortError') _copyLink(url); });
    } else {
        _copyLink(url);
    }
}

function _copyLink(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url)
            .then(function ()  { showToast('Link copied to clipboard!', 'success'); })
            .catch(function () { showToast('Failed to copy link', 'error'); });
    } else {
        var el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        showToast('Link copied to clipboard!', 'success');
    }
}