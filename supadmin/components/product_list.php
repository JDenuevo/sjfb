<?php
/**
 * supadmin/components/product_list.php
 *
 * Stock level thresholds (tweak as needed):
 *   High     > 30
 *   Moderate 11 – 30
 *   Low       1 – 10
 *   Out       0
 */

// ── Stock helper ──────────────────────────────────────────────────────────────
function stockLevel(int $qty): array {
    if ($qty > 30) return ['label' => 'High',     'dot' => '#22c55e', 'bg' => '#f0fdf4', 'text' => '#15803d', 'ring' => '#bbf7d0'];
    if ($qty > 10) return ['label' => 'Moderate', 'dot' => '#f59e0b', 'bg' => '#fffbeb', 'text' => '#b45309', 'ring' => '#fde68a'];
    if ($qty > 0)  return ['label' => 'Low',      'dot' => '#f97316', 'bg' => '#fff7ed', 'text' => '#c2410c', 'ring' => '#fed7aa'];
    return             ['label' => 'Out',      'dot' => '#ef4444', 'bg' => '#fef2f2', 'text' => '#b91c1c', 'ring' => '#fecaca'];
}

// Unit display labels
function unitLabel(string $u): string {
    return match ($u) {
        'kg'      => 'kg',
        'gram'    => 'g',
        'piece'   => 'pc',
        'pack'    => 'pk',
        'box'     => 'bx',
        'banyera' => 'bny',
        'sack'    => 'sk',
        'tray'    => 'tr',
        default   => $u,
    };
}

// Overall stock summary for a product (worst level among variants)
function worstLevel(array $quantities): array {
    $min = PHP_INT_MAX;
    foreach ($quantities as $q) {
        $v = (int)$q;
        if ($v < $min) $min = $v;
    }
    return stockLevel($min === PHP_INT_MAX ? 0 : $min);
}
?>

<style>
  /* ── Product list ── */
  .pl-wrap { background:#fff; border:1px solid #f0f0f0; border-radius:1.25rem; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.05); }

  /* header */
  .pl-head { padding:1.125rem 1.375rem; border-bottom:1px solid #f0f0f0; background:#fafafa; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; }
  .pl-head-title   { font-size:1.0625rem; font-weight:800; color:#111827; letter-spacing:-.02em; }
  .pl-head-sub     { font-size:.75rem; color:#9ca3af; margin-top:.1rem; }

  /* search */
  .pl-search-wrap { position:relative; }
  .pl-search-icon { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); pointer-events:none; color:#9ca3af; }
  .pl-search-input {
    padding:.4375rem .75rem .4375rem 2.125rem;
    border:1.5px solid #e5e7eb; border-radius:.625rem;
    font-size:.8125rem; font-family:inherit; color:#111827; outline:none;
    transition:border-color .15s, box-shadow .15s; width:220px;
    background:#fafafa;
  }
  .pl-search-input:focus { border-color:#f97316; box-shadow:0 0 0 3px rgba(249,115,22,.12); background:#fff; }
  .pl-search-input::placeholder { color:#9ca3af; }

  /* add button */
  .pl-add-btn {
    display:inline-flex; align-items:center; gap:.375rem;
    padding:.4375rem 1rem;
    background:#f97316; color:#fff;
    border:none; border-radius:.625rem;
    font-size:.8125rem; font-weight:700; font-family:inherit;
    cursor:pointer; transition:all .15s;
    box-shadow:0 2px 8px rgba(249,115,22,.22);
  }
  .pl-add-btn:hover { background:#ea580c; box-shadow:0 4px 14px rgba(249,115,22,.32); transform:translateY(-1px); }
  .pl-add-btn:active { transform:translateY(0); }

  /* table */
  .pl-table { min-width:100%; border-collapse:collapse; }
  .pl-table thead tr { background:#f9fafb; }
  .pl-table thead th {
    padding:.625rem 1rem; text-align:left;
    font-size:.6875rem; font-weight:700; color:#9ca3af;
    letter-spacing:.06em; text-transform:uppercase;
    border-bottom:1px solid #f0f0f0; white-space:nowrap;
  }
  .pl-table thead th:last-child { text-align:right; }
  .pl-table tbody tr {
    border-bottom:1px solid #f9fafb;
    border-left:3px solid transparent;
    transition:background .12s, border-color .12s;
  }
  .pl-table tbody tr:last-child { border-bottom:none; }
  .pl-table tbody tr:hover { background:#fff7ed33; border-left-color:#f97316; }
  .pl-table td { padding:.8125rem 1rem; vertical-align:top; }

  /* product name cell */
  .pl-prod-name { font-size:.875rem; font-weight:700; color:#111827; line-height:1.3; }
  .pl-prod-unit { font-size:.7rem; color:#9ca3af; margin-top:.2rem; }
  .pl-prod-desc { font-size:.7rem; color:#6b7280; margin-top:.25rem; line-height:1.4;
                  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; max-width:180px; }

  /* category badges */
  .pl-cat-badge {
    display:inline-flex; align-items:center; gap:.2rem;
    padding:.15rem .5rem; border-radius:9999px;
    font-size:.6875rem; font-weight:600; white-space:nowrap;
  }
  .pl-cat-primary { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
  .pl-cat-normal  { background:#f3f4f6; color:#374151; }

  /* ── Variant + stock rows ── */
  .pl-variant-list { display:flex; flex-direction:column; gap:.3125rem; min-width:220px; }
  .pl-variant-item {
    display:flex; align-items:center; gap:.5rem;
    padding:.3125rem .625rem;
    background:#fafafa; border:1px solid #f0f0f0; border-radius:.5rem;
    transition:border-color .12s;
  }
  .pl-variant-item:hover { border-color:#e5e7eb; background:#f9f9f9; }

  .pl-v-name { font-size:.75rem; font-weight:600; color:#374151; min-width:52px; }
  .pl-v-unit { font-size:.6875rem; color:#9ca3af; }

  .pl-v-qty  { font-size:.75rem; font-weight:700; color:#111827; margin-left:auto; white-space:nowrap; }

  /* stock level pill */
  .pl-stock-pill {
    display:inline-flex; align-items:center; gap:.25rem;
    padding:.1rem .4375rem; border-radius:9999px;
    font-size:.625rem; font-weight:700; white-space:nowrap;
    border:1px solid;
    flex-shrink:0;
  }
  .pl-stock-dot { width:5px; height:5px; border-radius:9999px; flex-shrink:0; }

  /* price cell */
  .pl-price-main { font-size:.8125rem; font-weight:700; color:#111827; }
  .pl-price-disc { font-size:.7rem; color:#16a34a; font-weight:600; margin-top:.1rem; }
  .pl-price-orig { font-size:.7rem; color:#9ca3af; text-decoration:line-through; }

  /* overall stock summary badge */
  .pl-stock-summary {
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.25rem .625rem; border-radius:9999px;
    font-size:.6875rem; font-weight:700; border:1px solid;
  }

  /* action buttons */
  .pl-action-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:2rem; height:2rem; border-radius:.5rem; border:none;
    cursor:pointer; transition:all .12s;
  }
  .pl-action-edit   { background:#eff6ff; color:#2563eb; }
  .pl-action-edit:hover   { background:#dbeafe; transform:scale(1.08); }
  .pl-action-delete { background:#fef2f2; color:#dc2626; }
  .pl-action-delete:hover { background:#fee2e2; transform:scale(1.08); }

  /* empty state */
  .pl-empty { padding:3.5rem 1rem; text-align:center; }
  .pl-empty-icon { width:3.5rem; height:3.5rem; background:#fff7ed; border-radius:1rem;
                   display:flex; align-items:center; justify-content:center; margin:0 auto .875rem; }

  /* pagination */
  .pl-pag { padding:.875rem 1.375rem; border-top:1px solid #f0f0f0; background:#fafafa;
            display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.625rem; }
  .pl-pag-info { font-size:.75rem; color:#9ca3af; }
  .pl-pag-info strong { color:#374151; font-weight:700; }
  .pl-pag-btn {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:2rem; height:2rem; padding:0 .5rem;
    border:1.5px solid #e5e7eb; border-radius:.5rem;
    font-size:.75rem; font-weight:600; color:#6b7280;
    background:#fff; text-decoration:none; transition:all .12s;
  }
  .pl-pag-btn:hover { background:#f9fafb; border-color:#d1d5db; color:#111827; }
  .pl-pag-btn.active { background:#f97316; border-color:#f97316; color:#fff; box-shadow:0 2px 6px rgba(249,115,22,.3); }
  .pl-pag-btn.disabled { background:#f9fafb; color:#d1d5db; border-color:#f0f0f0; pointer-events:none; }

  /* search highlight */
  mark.hl { background:#fff3cd; color:inherit; padding:0 1px; border-radius:2px; }

  @media(max-width:768px) {
    .pl-search-input { width:160px; }
    .pl-table thead th:nth-child(3),
    .pl-table td:nth-child(3) { display:none; } /* hide categories on mobile */
  }
</style>

<div class="pl-wrap">

  <!-- ── Header ── -->
  <div class="pl-head">
    <div>
      <p class="pl-head-title">Products</p>
      <p class="pl-head-sub"><?= $totalItems ?> total products</p>
    </div>
    <div style="display:flex;align-items:center;gap:.625rem;flex-wrap:wrap">
      <!-- Search -->
      <div class="pl-search-wrap">
        <svg class="pl-search-icon" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" id="plSearchInput" class="pl-search-input" placeholder="Search products…"
               oninput="plSearch(this.value)">
      </div>
      <!-- Add button -->
      <button class="pl-add-btn" data-modal-target="addProductModal">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Add Product
      </button>
    </div>
  </div>

  <!-- ── Table ── -->
  <div style="overflow-x:auto">
    <table class="pl-table" id="plTable">
      <thead>
        <tr>
          <th>Product</th>
          <th>Categories</th>
          <th>Variants &amp; Stock</th>
          <th>Prices</th>
          <th>Overall Stock</th>
          <th>Updated</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()):

          // ── Parse comma-separated variant data ──────────────────────────
          $vNames    = array_filter(array_map('trim', explode(', ', $row['variants']          ?? '')));
          $vPrices   = array_filter(array_map('trim', explode(', ', $row['prices']            ?? '')));
          $vDiscounts= array_filter(array_map('trim', explode(', ', $row['discount_prices']   ?? '')));
          $vQtys     = array_map('intval', array_filter(array_map('trim', explode(', ', $row['stock_quantities'] ?? ''))));

          // Re-fetch full variant details (name, unit, qty) for this product
          $varStmt = $conn->prepare("
            SELECT variant_id, variant_name, unit_type, stock_quantity, variant_price, discount_price
            FROM product_variants
            WHERE product_id = ? AND is_deleted = 0
            ORDER BY created_at ASC
          ");
          $varStmt->bind_param('i', $row['product_id']);
          $varStmt->execute();
          $varRows = $varStmt->get_result()->fetch_all(MYSQLI_ASSOC);

          $primary_cat_id = getPrimaryCategoryId($row['product_id']);
          $cats    = array_filter(array_map('trim', explode(', ', $row['category_names'] ?? '')));
          $cat_ids = array_filter(array_map('trim', explode(',',  $row['category_ids']   ?? '')));

          // Overall stock level (based on minimum variant qty)
          $allQtys = array_column($varRows, 'stock_quantity');
          $worst   = worstLevel($allQtys);

        ?>
        <tr class="pl-row" data-name="<?= strtolower(htmlspecialchars($row['product_name'])) ?>">

          <!-- Product -->
          <td>
            <p class="pl-prod-name"><?= htmlspecialchars($row['product_name']) ?></p>
            <?php if (!empty($row['product_unit'])): ?>
            <p class="pl-prod-unit"><?= htmlspecialchars($row['product_unit']) ?></p>
            <?php endif; ?>
            <?php if (!empty($row['product_description'])): ?>
            <p class="pl-prod-desc"><?= htmlspecialchars($row['product_description']) ?></p>
            <?php endif; ?>
          </td>

          <!-- Categories -->
          <td>
            <div style="display:flex;flex-wrap:wrap;gap:.25rem;max-width:160px">
              <?php if (!empty($cats)): foreach ($cats as $idx => $cat):
                $cid = $cat_ids[$idx] ?? 0;
                $isPrimary = ($cid == $primary_cat_id);
              ?>
              <span class="pl-cat-badge <?= $isPrimary ? 'pl-cat-primary' : 'pl-cat-normal' ?>">
                <?php if ($isPrimary): ?>
                <svg width="8" height="8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <?php endif; ?>
                <?= htmlspecialchars($cat) ?>
              </span>
              <?php endforeach; else: ?>
              <span style="font-size:.7rem;color:#9ca3af">—</span>
              <?php endif; ?>
            </div>
          </td>

          <!-- Variants & Stock -->
          <td>
            <?php if (!empty($varRows)): ?>
            <div class="pl-variant-list">
              <?php foreach ($varRows as $v):
                $qty = (int)$v['stock_quantity'];
                $lvl = stockLevel($qty);
                $uLabel = unitLabel($v['unit_type']);
              ?>
              <div class="pl-variant-item">
                <span class="pl-v-name"><?= htmlspecialchars($v['variant_name']) ?></span>
                <span class="pl-v-unit"><?= $uLabel ?></span>
                <span class="pl-v-qty"><?= number_format($qty) ?></span>
                <span class="pl-stock-pill"
                      style="background:<?= $lvl['bg'] ?>;color:<?= $lvl['text'] ?>;border-color:<?= $lvl['ring'] ?>">
                  <span class="pl-stock-dot" style="background:<?= $lvl['dot'] ?>"></span>
                  <?= $lvl['label'] ?>
                </span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <span style="font-size:.75rem;color:#9ca3af">No variants</span>
            <?php endif; ?>
          </td>

          <!-- Prices -->
          <td>
            <?php if (!empty($varRows)): ?>
            <div style="display:flex;flex-direction:column;gap:.25rem">
              <?php foreach ($varRows as $v):
                $price = (float)$v['variant_price'];
                $disc  = !empty($v['discount_price']) ? (float)$v['discount_price'] : null;
              ?>
              <div>
                <?php if ($disc && $disc < $price): ?>
                <p class="pl-price-disc">₱<?= number_format($disc, 2) ?></p>
                <p class="pl-price-orig">₱<?= number_format($price, 2) ?></p>
                <?php else: ?>
                <p class="pl-price-main">₱<?= number_format($price, 2) ?></p>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <span style="font-size:.75rem;color:#9ca3af">—</span>
            <?php endif; ?>
          </td>

          <!-- Overall Stock -->
          <td style="vertical-align:middle">
            <span class="pl-stock-summary"
                  style="background:<?= $worst['bg'] ?>;color:<?= $worst['text'] ?>;border-color:<?= $worst['ring'] ?>">
              <span class="pl-stock-dot" style="background:<?= $worst['dot'] ?>;width:7px;height:7px"></span>
              <?= $worst['label'] === 'Out' ? 'Out of Stock' : $worst['label'] ?>
            </span>
            <?php if (count($varRows) > 1):
              $totalQty = array_sum(array_column($varRows, 'stock_quantity'));
            ?>
            <p style="font-size:.6875rem;color:#9ca3af;margin-top:.3rem"><?= number_format($totalQty) ?> total units</p>
            <?php endif; ?>
          </td>

          <!-- Last Updated -->
          <td style="white-space:nowrap">
            <?php if (!empty($row['last_updated'])): ?>
            <p style="font-size:.75rem;font-weight:600;color:#374151"><?= date('M j, Y', strtotime($row['last_updated'])) ?></p>
            <p style="font-size:.6875rem;color:#9ca3af"><?= date('g:i a', strtotime($row['last_updated'])) ?></p>
            <?php else: ?>
            <span style="font-size:.75rem;color:#9ca3af">—</span>
            <?php endif; ?>
          </td>

          <!-- Actions -->
          <td style="text-align:right;vertical-align:middle">
            <div style="display:inline-flex;align-items:center;gap:.375rem">
              <button class="pl-action-btn pl-action-edit"
                      onclick="openEditModal(<?= $row['product_id'] ?>)" title="Edit product">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button class="pl-action-btn pl-action-delete"
                      onclick="openDeleteModal(<?= $row['product_id'] ?>, '<?= htmlspecialchars(addslashes($row['product_name'])) ?>')"
                      title="Delete product">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            </div>
          </td>

        </tr>
        <?php endwhile; else: ?>
        <tr>
          <td colspan="7">
            <div class="pl-empty">
              <div class="pl-empty-icon">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="1.5">
                  <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                  <line x1="3" y1="6" x2="21" y2="6"/>
                  <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
              </div>
              <p style="font-size:.875rem;font-weight:700;color:#374151">No products yet</p>
              <p style="font-size:.75rem;color:#9ca3af;margin-top:.25rem">Click "Add Product" to get started</p>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ── Pagination ── -->
  <?php if ($totalPages > 1): ?>
  <div class="pl-pag">
    <p class="pl-pag-info">
      Showing <strong><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></strong>
      of <strong><?= $totalItems ?></strong> products
    </p>
    <div style="display:flex;align-items:center;gap:.3125rem;flex-wrap:wrap">
      <!-- Prev -->
      <?php if ($page > 1): ?>
      <a href="?page=<?= $page-1 ?>" class="pl-pag-btn">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
      </a>
      <?php else: ?>
      <span class="pl-pag-btn disabled">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
      </span>
      <?php endif; ?>

      <?php
        $ps = max(1, $page-2); $pe = min($totalPages, $page+2);
        if ($ps > 1) echo '<a href="?page=1" class="pl-pag-btn">1</a>';
        if ($ps > 2) echo '<span style="color:#9ca3af;font-size:.75rem;padding:0 .1rem">…</span>';
        for ($i=$ps; $i<=$pe; $i++) {
          $cls = $i==$page ? 'pl-pag-btn active' : 'pl-pag-btn';
          echo "<a href=\"?page=$i\" class=\"$cls\">$i</a>";
        }
        if ($pe < $totalPages-1) echo '<span style="color:#9ca3af;font-size:.75rem;padding:0 .1rem">…</span>';
        if ($pe < $totalPages) echo '<a href="?page='.$totalPages.'" class="pl-pag-btn">'.$totalPages.'</a>';
      ?>

      <!-- Next -->
      <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page+1 ?>" class="pl-pag-btn">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
      </a>
      <?php else: ?>
      <span class="pl-pag-btn disabled">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
      </span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /pl-wrap -->

<script>
/* ── Live search — filters rows client-side ── */
function plSearch(q) {
  q = q.toLowerCase().trim();
  document.querySelectorAll('#plTable tbody tr.pl-row').forEach(function(tr) {
    var name = tr.dataset.name || '';
    tr.style.display = (!q || name.includes(q)) ? '' : 'none';
  });
}
</script>