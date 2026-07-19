<?php
$items = $items ?? [];
$branches = $branches ?? [];
$products = $products ?? [];
$selectedBranch = (int)($_GET['branch_id'] ?? 0);
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Global Inventory</h2>
      <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:0.5rem;align-items:center;">
          <select name="branch_id" class="select-field" style="width:auto;min-width:160px;padding:0.45rem 0.8rem;font-size:0.8rem;" onchange="this.form.submit()">
            <option value="0">All Branches</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['BranchID'] ?>" <?= $selectedBranch === (int)$b['BranchID'] ? 'selected' : '' ?>><?= escape($b['BranchName']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <button class="btn-primary" onclick="openModal('invModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Stock
        </button>
      </div>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:700px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Product</th>
          <th>Category</th>
          <th>Branch</th>
          <th style="text-align:right;width:70px">Qty</th>
          <th>Last Updated</th>
          <th style="width:165px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show all inventory with branch, qty, edit/remove buttons -->
        <?php foreach ($items as $i): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $i['InventoryID'] ?></td>
            <td><strong><?= escape($i['ProductName']) ?></strong></td>
            <td><span style="font-size:0.75rem;color:var(--text-secondary);border:1px solid var(--border);padding:0.15rem 0.5rem;border-radius:4px;white-space:nowrap;"><?= escape($i['CategoryName'] ?? '-') ?></span></td>
            <td style="color:var(--text-secondary)"><?= escape($i['BranchName']) ?></td>
            <td style="text-align:right;font-weight:700;font-size:1rem;"><?= (int)$i['AvailableQty'] ?></td>
            <td style="font-size:0.8rem;color:var(--text-secondary);white-space:nowrap;"><?= date('d M Y, H:i', strtotime($i['LastUpdated'] ?? 'now')) ?></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.5rem;align-items:center;">
                <button class="btn-cyan" onclick="editInv(<?= $i['InventoryID'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $i['InventoryID'] ?>" data-field="inventory_id" data-csrf="<?= $csrf ?>" data-action="inventory.php">Remove</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary)">No inventory records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="invModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:480px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('invModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="invModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Stock</h3>
    <form id="invForm" class="ajax-form" action="inventory.php" method="POST" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="invAction" value="create">
      <input type="hidden" name="inventory_id" id="invIdField" value="0">
      <div class="form-group">
        <label for="i_branch">Branch</label>
        <select id="i_branch" name="branch_id" class="select-field" required>
          <option value="">Select branch...</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="i_product">Product</label>
        <select id="i_product" name="product_id" class="select-field" required>
          <option value="">Select product...</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['ProductID'] ?>"><?= escape($p['ProductName']) ?> (Rs<?= number_format($p['Price'], 2) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="i_qty">Quantity</label>
        <input type="number" id="i_qty" name="qty" class="input-field" min="0" required>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('invModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var invData = <?= json_encode(array_map(function($i) {
    return ['InventoryID' => $i['InventoryID'], 'BranchID' => $i['BranchID'], 'ProductID' => $i['ProductID'], 'AvailableQty' => $i['AvailableQty']];
}, $items)) ?>;

// fill edit form with inventory data; lock branch/product
function editInv(id) {
    var d = invData.find(function(x) { return x.InventoryID == id; });
    if (!d) return;
    document.getElementById('invAction').value = 'update';
    document.getElementById('invIdField').value = d.InventoryID;
    document.getElementById('i_branch').value = d.BranchID;
    document.getElementById('i_product').value = d.ProductID;
    document.getElementById('i_qty').value = d.AvailableQty;
    document.getElementById('i_branch').disabled = true;
    document.getElementById('i_product').disabled = true;
    document.getElementById('invModalTitle').textContent = 'Edit Stock';
    openModal('invModal');
}

// reset modal to add mode when closed
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('invModal');
    if (el) {
        var obs = new MutationObserver(function() {
            if (el.classList.contains('hidden')) {
                document.getElementById('invForm').reset();
                document.getElementById('invAction').value = 'create';
                document.getElementById('invIdField').value = '0';
                document.getElementById('i_branch').disabled = false;
                document.getElementById('i_product').disabled = false;
                document.getElementById('invModalTitle').textContent = 'Add Stock';
            }
        });
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>
