<?php
$pending = $pending ?? [];
$products = $products ?? [];
$branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
$csrf = csrf_token();
?>
<div style="max-width:1100px;margin:0 auto;">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);">Transfer Requests</h1>
    <button class="btn-primary" onclick="openModal('reqModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">New Request</button>
  </div>

  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h2>My Requests</h2></div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px">ID</th>
          <th>Product</th>
          <th style="text-align:right">Qty</th>
          <th>Requested</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pending)): ?>
          <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-secondary)">No requests yet.</td></tr>
        <?php else: ?>
          <!-- show each transfer request with status color -->
          <?php foreach ($pending as $t): ?>
            <tr>
              <td style="color:var(--text-secondary)">#<?= $t['TransferID'] ?></td>
              <td><strong><?= escape($t['ProductName']) ?></strong></td>
              <td style="text-align:right;font-weight:700;"><?= (int)$t['RequestedQty'] ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary)"><?= date('d M Y, H:i', strtotime($t['RequestDate'])) ?></td>
              <td>
                <?php if ($t['Status'] === 'Pending'): ?>
                  <span style="color:var(--warning);font-weight:600;font-size:0.8rem;">Pending</span>
                <?php elseif ($t['Status'] === 'Approved'): ?>
                  <span style="color:var(--success);font-weight:600;font-size:0.8rem;">Approved</span>
                <?php elseif ($t['Status'] === 'Rejected'): ?>
                  <span style="color:var(--danger);font-weight:600;font-size:0.8rem;">Rejected</span>
                <?php else: ?>
                  <span style="color:var(--text-secondary);font-size:0.8rem;"><?= escape($t['Status']) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-wrap hidden" id="reqModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:420px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('reqModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Request Stock Transfer</h3>
      <!-- form to request stock transfer - select product and quantity -->
      <form class="ajax-form" action="transfers.php" method="POST" data-btn-text="Submit">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="form-group">
        <label for="rp_product">Product</label>
        <select id="rp_product" name="product_id" class="select-field" required>
          <option value="">Select product...</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['ProductID'] ?>"><?= escape($p['ProductName']) ?> (Rs<?= number_format($p['Price'], 2) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="rp_qty">Quantity</label>
        <input type="number" id="rp_qty" name="qty" class="input-field" min="1" required>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Submit</button>
        <button type="button" class="btn-secondary" onclick="closeModal('reqModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
// reset form when modal is closed
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('reqModal');
    if (el) {
        var obs = new MutationObserver(function() {
            if (el.classList.contains('hidden')) {
                document.querySelector('#reqModal form').reset();
            }
        });
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>
