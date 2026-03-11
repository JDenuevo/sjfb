/**
 * product_process.js
 * Requires: cart_process.js already loaded (provides showToast, refreshCartFromServer).
 *
 * Handles:
 *   - Category / price filter via AJAX (no full page reload, slug-based)
 *   - Search autocomplete + performSearch
 *   - Variant selection + quantity buttons on product cards
 *   - Add-to-cart form submit
 *   - Share buttons
 */

// ─────────────────────────────────────────────────────────────────────────────
// SHARED FILTER STATE — reads slugs from URL on init
// ─────────────────────────────────────────────────────────────────────────────
var _activeFilters = (function () {
    var p = new URLSearchParams(window.location.search);
    return {
        category: p.get('category') || '',   // comma-separated slugs e.g. "freshwater-fish"
        price:    p.get('price')    || '',
        search:   p.get('search')   || '',
    };
})();

// ─────────────────────────────────────────────────────────────────────────────
// AJAX PRODUCT FETCHER
// ─────────────────────────────────────────────────────────────────────────────
async function fetchFilteredProducts(pushState) {
    var productsContent     = document.getElementById('productsContent');
    var productsLoading     = document.getElementById('productsLoading');
    var autocompleteResults = document.getElementById('autocompleteResults');

    if (productsContent) productsContent.style.opacity = '.4';
    if (productsLoading) productsLoading.classList.remove('hidden');

    var params = new URLSearchParams();
    if (_activeFilters.category) params.set('category', _activeFilters.category);
    if (_activeFilters.price)    params.set('price',    _activeFilters.price);
    if (_activeFilters.search)   params.set('search',   _activeFilters.search);

    // Sync browser URL bar without reload
    if (pushState !== false) {
        var url = new URL(window.location);
        ['category', 'price', 'search'].forEach(function (k) { url.searchParams.delete(k); });
        params.forEach(function (v, k) { url.searchParams.set(k, v); });
        window.history.pushState({}, '', url);
    }

    var base     = (window.CART_BASE !== undefined ? window.CART_BASE : '');
    var fetchUrl = base + '/functions/fetch_products.php' + (params.toString() ? '?' + params.toString() : '');

    try {
        var res = await fetch(fetchUrl);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        var html = await res.text();
        if (productsContent) productsContent.innerHTML = html;
        initializeProductFunctionality();
        if (window.innerWidth < 768) {
            var container = document.getElementById('productsContainer');
            if (container) container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (err) {
        if (productsContent) {
            productsContent.innerHTML =
                '<div style="text-align:center;padding:3rem;color:#dc2626">Error loading products. ' +
                '<button onclick="location.reload()" style="margin-left:.5rem;padding:.5rem 1rem;background:#2563eb;color:#fff;border-radius:.5rem;cursor:pointer">Reload</button></div>';
        }
    } finally {
        if (productsContent) productsContent.style.opacity = '';
        if (productsLoading) productsLoading.classList.add('hidden');
        if (autocompleteResults) autocompleteResults.classList.add('hidden');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CATEGORY FILTER — reads data-category-slug, passes slugs not IDs
// ─────────────────────────────────────────────────────────────────────────────
function handleCategoryChange(checkbox) {
    var selected = [];

    document.querySelectorAll('.category-filter:checked').forEach(function (cb) {
        if (cb.value !== 'all') {
            var slug = cb.dataset.categorySlug || '';
            if (slug) selected.push(slug);
        }
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

    if (selected.length === 0 && allCb) allCb.checked = true;

    _activeFilters.category = selected.join(',');
    _syncOffcanvasFromDesktop();
    fetchFilteredProducts();
}

function clearAllFilters() {
    _activeFilters.category = '';
    _activeFilters.price    = '';
    _activeFilters.search   = '';

    document.querySelectorAll('.category-filter').forEach(function (cb) { cb.checked = cb.value === 'all'; });
    document.querySelectorAll('.price-filter').forEach(function (r)     { r.checked = false; });
    document.querySelectorAll('.fc-cat').forEach(function (cb)          { cb.checked = cb.value === 'all'; });
    document.querySelectorAll('.fc-price').forEach(function (r)         { r.checked = false; });

    var si = document.getElementById('searchInput');
    if (si) si.value = '';
    var cs = document.getElementById('clearSearch');
    if (cs) cs.classList.add('hidden');

    fetchFilteredProducts();
}

function _syncOffcanvasFromDesktop() {
    var desktopSlugs = [];
    document.querySelectorAll('.category-filter:checked').forEach(function (cb) {
        if (cb.value !== 'all') desktopSlugs.push(cb.dataset.categorySlug || '');
    });
    document.querySelectorAll('.fc-cat').forEach(function (cb) {
        var slug = cb.dataset.categorySlug || '';
        cb.checked = cb.value === 'all'
            ? desktopSlugs.length === 0
            : desktopSlugs.includes(slug);
    });
}

function _syncCheckboxesFromFilters() {
    var slugs = _activeFilters.category ? _activeFilters.category.split(',') : [];
    document.querySelectorAll('.category-filter').forEach(function (cb) {
        var slug = cb.dataset.categorySlug || '';
        cb.checked = cb.value === 'all' ? slugs.length === 0 : slugs.includes(slug);
    });
    document.querySelectorAll('.price-filter').forEach(function (r) {
        r.checked = r.value === _activeFilters.price;
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// DOM READY
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.price-filter').forEach(function (radio) {
        radio.addEventListener('change', function () {
            _activeFilters.price = this.value;
            document.querySelectorAll('.fc-price').forEach(function (r) { r.checked = r.value === _activeFilters.price; });
            fetchFilteredProducts();
        });
    });

    var urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.get('category')) {
        var allCb = document.querySelector('.category-filter[value="all"]');
        if (allCb) allCb.checked = true;
    }

    initializeProductFunctionality();
});

// ─────────────────────────────────────────────────────────────────────────────
// PRODUCT CARD INIT
// ─────────────────────────────────────────────────────────────────────────────
function initializeProductFunctionality() {

    // ── Variant selection ──────────────────────────────────────────────────────
    document.querySelectorAll('.variant-button:not([data-bound])').forEach(function (button) {
        button.setAttribute('data-bound', '1');
        button.addEventListener('click', function () {
            if (button.disabled) return;

            var productId     = button.dataset.productId;
            var variantPrice  = parseFloat(button.dataset.variantPrice)  || 0;
            var discountPrice = parseFloat(button.dataset.discountPrice) || 0;
            var unitType      = button.dataset.unitType;
            var minimumOrder  = parseFloat(button.dataset.minimumOrder)  || 1;
            var orderIncr     = parseFloat(button.dataset.orderIncrement)|| 1;

            var form = document.querySelector('.add-to-cart-form[data-product-id="' + productId + '"]');
            if (!form) return;

            // Deselect all siblings, select this one
            form.querySelectorAll('.variant-button').forEach(function (b) {
                b.classList.remove('selected-variant', 'border-amber-400', 'bg-amber-400', 'text-white');
                b.classList.add('border-gray-300');
            });
            button.classList.add('selected-variant', 'border-amber-400', 'bg-amber-400', 'text-white');
            button.classList.remove('border-gray-300');

            form.querySelector('[name="variant_id"]').value      = button.dataset.variantId;
            form.querySelector('[name="variant_name"]').value    = button.dataset.variantName;
            form.querySelector('[name="price"]').value           = discountPrice > 0 ? discountPrice : variantPrice;
            form.querySelector('[name="unit_type"]').value       = unitType;
            form.querySelector('[name="minimum_order"]').value   = minimumOrder;
            form.querySelector('[name="order_increment"]').value = orderIncr;

            var qtyInput = form.querySelector('.quantity');
            if (qtyInput) {
                qtyInput.min   = minimumOrder;
                qtyInput.step  = orderIncr;
                qtyInput.value = unitType === 'piece' ? Math.round(minimumOrder) : minimumOrder;
                var hidQty = form.querySelector('[name="quantity"]');
                if (hidQty) hidQty.value = minimumOrder;
            }

            var unitDisp = form.querySelector('.unit-display');
            if (unitDisp) unitDisp.textContent = unitType === 'piece' ? 'pcs' : unitType;

            var minText = form.querySelector('.minimum-order-text');
            if (minText) minText.textContent = 'Minimum: ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);

            _updateCardPriceDisplay(form, variantPrice, discountPrice, minimumOrder);

            var submitBtn = form.querySelector('[name="add_to_cart"]');
            if (submitBtn) submitBtn.disabled = false;

            form.querySelectorAll('.variant-message, .minimum-error-message').forEach(function (el) { el.classList.add('hidden'); });
        });
    });

    // ── Qty − ─────────────────────────────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form .decrease-quantity:not([data-bound])').forEach(function (btn) {
        btn.setAttribute('data-bound', '1');
        btn.addEventListener('click', function () {
            var form      = btn.closest('.add-to-cart-form');
            var qtyInput  = form.querySelector('.quantity');
            var minOrder  = parseFloat(form.querySelector('[name="minimum_order"]').value) || 1;
            var orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value) || 1;
            var cur       = parseFloat(qtyInput.value) || minOrder;
            var newQty    = Math.max(minOrder, cur - orderIncr);
            qtyInput.min = minOrder; qtyInput.step = orderIncr;
            qtyInput.value = _fmtQty(newQty);
            var hidQty = form.querySelector('[name="quantity"]');
            if (hidQty) hidQty.value = newQty;
            _updateCardTotalPrice(form);
        });
    });

    // ── Qty + ─────────────────────────────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form .increase-quantity:not([data-bound])').forEach(function (btn) {
        btn.setAttribute('data-bound', '1');
        btn.addEventListener('click', function () {
            var form      = btn.closest('.add-to-cart-form');
            var qtyInput  = form.querySelector('.quantity');
            var minOrder  = parseFloat(form.querySelector('[name="minimum_order"]').value) || 1;
            var orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value) || 1;
            var cur       = parseFloat(qtyInput.value) || minOrder;
            var newQty    = cur + orderIncr;
            qtyInput.min = minOrder; qtyInput.step = orderIncr;
            qtyInput.value = _fmtQty(newQty);
            var hidQty = form.querySelector('[name="quantity"]');
            if (hidQty) hidQty.value = newQty;
            _updateCardTotalPrice(form);
        });
    });

    // ── Typed qty ─────────────────────────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form .quantity:not([data-bound])').forEach(function (input) {
        input.setAttribute('data-bound', '1');
        input.addEventListener('input', function () {
            var form = input.closest('.add-to-cart-form');
            if (!form) return;
            var hidQty = form.querySelector('[name="quantity"]');
            if (hidQty) hidQty.value = parseFloat(input.value) || 0;
            _updateCardTotalPrice(form);
        });
        input.addEventListener('change', function () {
            var form      = input.closest('.add-to-cart-form');
            var minOrder  = parseFloat(form.querySelector('[name="minimum_order"]').value) || 1;
            var orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value) || 1;
            var val       = parseFloat(input.value);
            if (isNaN(val) || val < minOrder) val = minOrder;
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

    // ── Add-to-cart submit ────────────────────────────────────────────────────
    document.querySelectorAll('.add-to-cart-form:not([data-submit-bound])').forEach(function (form) {
        form.setAttribute('data-submit-bound', '1');
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var variantId    = form.querySelector('[name="variant_id"]').value;
            var quantity     = parseFloat(form.querySelector('[name="quantity"]').value);
            var minimumOrder = parseFloat(form.querySelector('[name="minimum_order"]').value);
            var unitType     = form.querySelector('[name="unit_type"]').value;
            var errMsg       = form.querySelector('.minimum-error-message');

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
                var base = (window.CART_BASE !== undefined ? window.CART_BASE : '');
                var res  = await fetch(base + '/functions/add_to_cart.php', { method: 'POST', body: new FormData(form) });
                var data = await res.json();
                if (data.status === 'success') {
                    showToast('Product added to cart!', 'success');
                    await refreshCartFromServer();
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

// ── Price display helpers ─────────────────────────────────────────────────────
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
        el.innerHTML =
            '<span style="text-decoration:line-through;color:#9ca3af;font-size:.875rem">₱' + (variantPrice * quantity).toFixed(2) + '</span>' +
            '<span style="color:#dc2626;font-weight:700;margin-left:.5rem">₱' + total.toFixed(2) + '</span>';
    } else {
        el.innerHTML = '<span style="color:#1f2937;font-weight:700">₱' + total.toFixed(2) + '</span>';
    }
}

function _updateCardTotalPrice(form) {
    var qtyInput      = form.querySelector('.quantity');
    var qty           = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
    var selectedBtn   = form.querySelector('.variant-button.selected-variant');
    if (!selectedBtn) return;
    var variantPrice  = parseFloat(selectedBtn.dataset.variantPrice)  || 0;
    var discountPrice = parseFloat(selectedBtn.dataset.discountPrice) || 0;
    _updateCardPriceDisplay(form, variantPrice, discountPrice, qty);
}

// ─────────────────────────────────────────────────────────────────────────────
// SEARCH AUTOCOMPLETE + performSearch
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    var searchInput         = document.getElementById('searchInput');
    var autocompleteResults = document.getElementById('autocompleteResults');
    var clearSearchBtn      = document.getElementById('clearSearch');
    var searchTimeout;
    var currentSearchTerm   = searchInput ? searchInput.value.trim() : '';

    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        clearSearchBtn && (this.value.trim().length > 0
            ? clearSearchBtn.classList.remove('hidden')
            : clearSearchBtn.classList.add('hidden'));
        if (!this.value.trim()) autocompleteResults && autocompleteResults.classList.add('hidden');
    });

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearSearchBtn.classList.add('hidden');
            if (autocompleteResults) autocompleteResults.classList.add('hidden');
            _activeFilters.search = '';
            fetchFilteredProducts();
        });
    }

    searchInput.addEventListener('input', function (e) {
        currentSearchTerm = e.target.value.trim();
        clearTimeout(searchTimeout);
        if (currentSearchTerm.length < 1) { if (autocompleteResults) autocompleteResults.classList.add('hidden'); return; }
        if (autocompleteResults) {
            autocompleteResults.innerHTML = '<div style="padding:1rem;text-align:center;color:#9ca3af">Loading...</div>';
            autocompleteResults.classList.remove('hidden');
        }
        searchTimeout = setTimeout(function () { fetchAutocompleteResults(currentSearchTerm); }, 200);
    });

    searchInput.addEventListener('focus', function () { if (!this.value.trim()) showPopularSearches(); });

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
            var res = await fetch((window.CART_BASE||'') + '/functions/auto_complete.php?query=' + encodeURIComponent(query) + '&limit=8');
            displayAutocompleteResults(await res.json());
        } catch (err) {
            if (autocompleteResults) autocompleteResults.innerHTML = '<div style="padding:1rem;color:#dc2626;text-align:center">Error loading results</div>';
        }
    }

    function highlightText(text, query) {
        if (!query || !text) return text;
        return text.replace(new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'),
            '<mark style="background:#fef08a;font-weight:600">$1</mark>');
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
            item.className = 'autocomplete-item'; item.tabIndex = 0;
            item.style.cssText = 'padding:.75rem 1rem;cursor:pointer;border-bottom:1px solid #f3f4f6;transition:background .15s';
            item.onmouseenter = function () { this.style.background = '#f9fafb'; };
            item.onmouseleave = function () { this.style.background = ''; };
            var tagsHtml = product.tags && product.tags.length
                ? '<div style="margin-top:.375rem">' + product.tags.map(function (t) {
                    return '<span style="display:inline-block;padding:.125rem .5rem;background:#fff7ed;color:#c2410c;border-radius:9999px;font-size:.75rem;margin-right:.25rem">' + highlightText(t, currentSearchTerm) + '</span>';
                  }).join('') + '</div>' : '';
            item.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem"><div style="min-width:0">' +
                '<div style="font-size:.875rem;font-weight:600;color:#111827">' + highlightText(product.name, currentSearchTerm) + '</div>' +
                (product.variant ? '<div style="font-size:.75rem;color:#4b5563;margin-top:.125rem">' + highlightText(product.variant, currentSearchTerm) + '</div>' : '') +
                tagsHtml + '</div>' +
                '<span style="font-size:.75rem;background:#f3f4f6;color:#374151;padding:.25rem .625rem;border-radius:9999px;white-space:nowrap;flex-shrink:0">' +
                (product.category || 'General') + '</span></div>';
            item.addEventListener('click', function () { searchInput.value = product.name; performSearch(product.name); autocompleteResults.classList.add('hidden'); });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter')     { e.preventDefault(); searchInput.value = product.name; performSearch(product.name); autocompleteResults.classList.add('hidden'); }
                else if (e.key === 'ArrowDown') { e.preventDefault(); var n = item.nextElementSibling; if (n && n.classList.contains('autocomplete-item')) n.focus(); }
                else if (e.key === 'ArrowUp')   { e.preventDefault(); var p = item.previousElementSibling; if (p && p.classList.contains('autocomplete-item')) p.focus(); else searchInput.focus(); }
                else if (e.key === 'Escape')    { autocompleteResults.classList.add('hidden'); searchInput.focus(); }
            });
            autocompleteResults.appendChild(item);
        });
        autocompleteResults.classList.remove('hidden');
    }

    function showPopularSearches() {
        if (!autocompleteResults) return;
        var popular = ['Bangus', 'Tilapia', 'Lapu-Lapu', 'Crab', 'Salmon', 'Tuna'];
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

    function performSearch(query) {
        _activeFilters.search = query.trim();
        var si = document.getElementById('searchInput');
        if (si) si.value = _activeFilters.search;
        var cs = document.getElementById('clearSearch');
        if (cs) { _activeFilters.search ? cs.classList.remove('hidden') : cs.classList.add('hidden'); }
        fetchFilteredProducts();
    }

    window.addEventListener('popstate', function () {
        var p = new URLSearchParams(window.location.search);
        _activeFilters.category = p.get('category') || '';
        _activeFilters.price    = p.get('price')    || '';
        _activeFilters.search   = p.get('search')   || '';
        searchInput.value = _activeFilters.search;
        _syncCheckboxesFromFilters();
        fetchFilteredProducts(false);
    });

    if (currentSearchTerm) performSearch(currentSearchTerm);
});

// ─────────────────────────────────────────────────────────────────────────────
// OFFCANVAS open / close
// ─────────────────────────────────────────────────────────────────────────────
var _fcPanel    = document.getElementById('filterCanvas');
var _fcBackdrop = document.getElementById('fcBackdrop');

function openFilterCanvas() {
    _fcPanel.style.display    = 'flex';
    _fcBackdrop.style.display = 'block';
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () { requestAnimationFrame(function () { _fcPanel.style.transform = 'translateX(0)'; }); });
}
function closeFilterCanvas() {
    _fcPanel.style.transform = 'translateX(-100%)';
    document.body.style.overflow = '';
    setTimeout(function () { _fcPanel.style.display = 'none'; _fcBackdrop.style.display = 'none'; }, 300);
}

document.getElementById('openFilterCanvas').addEventListener('click', openFilterCanvas);
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeFilterCanvas(); });

// ── Offcanvas Apply — slug-based AJAX ────────────────────────────────────────
document.getElementById('fcApply').addEventListener('click', function () {
    var checkedSlugs = [];
    document.querySelectorAll('.fc-cat:checked').forEach(function (cb) {
        if (cb.value !== 'all') {
            var slug = cb.dataset.categorySlug || '';
            if (slug) checkedSlugs.push(slug);
        }
    });
    var priceEl = document.querySelector('.fc-price:checked');

    _activeFilters.category = checkedSlugs.join(',');
    _activeFilters.price    = priceEl ? priceEl.value : '';

    // Sync desktop sidebar
    document.querySelectorAll('.category-filter').forEach(function (cb) {
        var slug = cb.dataset.categorySlug || '';
        cb.checked = cb.value === 'all' ? checkedSlugs.length === 0 : checkedSlugs.includes(slug);
    });
    document.querySelectorAll('.price-filter').forEach(function (r) { r.checked = r.value === _activeFilters.price; });

    closeFilterCanvas();
    fetchFilteredProducts();
});

// ── Offcanvas Clear ───────────────────────────────────────────────────────────
document.getElementById('fcClear').addEventListener('click', function () {
    document.querySelectorAll('.fc-cat').forEach(function (cb)  { cb.checked = cb.value === 'all'; });
    document.querySelectorAll('.fc-price').forEach(function (r) { r.checked = false; });
});

// ─────────────────────────────────────────────────────────────────────────────
// SHARE BUTTONS
// ─────────────────────────────────────────────────────────────────────────────
function shareToFacebook(url) {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank', 'width=600,height=400,noopener,noreferrer');
}
function shareProduct(title, text, url) {
    if (navigator.share) {
        navigator.share({ title: title, text: text, url: url }).catch(function (err) { if (err.name !== 'AbortError') _copyLink(url); });
    } else { _copyLink(url); }
}
function _copyLink(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function () { showToast('Link copied to clipboard!', 'success'); }).catch(function () { showToast('Failed to copy link', 'error'); });
    } else {
        var el = document.createElement('textarea');
        el.value = url; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el);
        showToast('Link copied to clipboard!', 'success');
    }
}