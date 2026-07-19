<?php
$items = $items ?? [];
$products = $products ?? [];
$branchName = $_SESSION['user']['BranchName'] ?? 'My Branch';
$csrf = csrf_token();
?>
<div style="max-width:1100px;margin:0 auto;">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1.5rem;">
    <div>
      <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;">Branch Inventory</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);"><?= escape($branchName) ?></p>
    </div>
    <div style="display:flex;gap:0.6rem;">
      <button class="btn-primary" onclick="openModal('transferModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
        Request Transfer
      </button>
      <button class="btn-secondary" onclick="window.open('report.php', '_blank')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        Print Report
      </button>
    </div>
  </div>

  <div class="card">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px">ID</th>
          <th>Product</th>
          <th>Category</th>
          <th>Price (Rs)</th>
          <th style="text-align:right">Available Qty</th>
          <th>Last Updated</th>
        </tr>
      </thead>
      <tbody>
        <!-- show branch inventory with qty; highlight red if 5 or less -->
        <?php foreach ($items as $i): ?>
          <tr>
            <td style="color:var(--text-secondary)">#<?= $i['InventoryID'] ?></td>
            <td><strong><?= escape($i['ProductName']) ?></strong></td>
            <td><span style="font-size:0.75rem;color:var(--text-secondary);border:1px solid var(--border);padding:0.15rem 0.5rem;border-radius:4px;"><?= escape($i['CategoryName'] ?? '-') ?></span></td>
            <td style="font-weight:600">Rs <?= number_format($i['Price'], 2) ?></td>
            <td style="text-align:right;font-weight:700;font-size:1rem;<?= (int)$i['AvailableQty'] <= 5 ? 'color:var(--danger);' : '' ?>"><?= (int)$i['AvailableQty'] ?></td>
            <td style="font-size:0.8rem;color:var(--text-secondary)"><?= date('d M Y, H:i', strtotime($i['LastUpdated'] ?? 'now')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary)">No inventory records for this branch.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-wrap hidden" id="transferModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:420px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('transferModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:0.5rem;">Request Stock Transfer</h3>
    <p style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:1.5rem;">Submit a request to Admin for stock replenishment.</p>
    <!-- transfer request form - pick product and qty to send to admin -->
    <form class="ajax-form" action="transfers.php" method="POST" data-btn-text="Submit Request">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="form-group">
        <label for="t_product">Product</label>
        <select id="t_product" name="product_id" class="select-field" required>
          <option value="">Select product...</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['ProductID'] ?>"><?= escape($p['ProductName']) ?> (Rs<?= number_format($p['Price'], 2) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="t_qty">Quantity Requested</label>
        <input type="number" id="t_qty" name="qty" class="input-field" min="1" required placeholder="e.g. 20">
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Submit Request</button>
        <button type="button" class="btn-secondary" onclick="closeModal('transferModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>
