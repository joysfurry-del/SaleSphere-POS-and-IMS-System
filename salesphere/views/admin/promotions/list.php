<?php
$promotions = $promotions ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Promotion Management</h2>
      <button class="btn-primary" onclick="openModal('promoModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Promotion
      </button>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:1000px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Code</th>
          <th>Name</th>
          <th>Type</th>
          <th>Value</th>
          <th>Scope</th>
          <th>Period</th>
          <th style="width:80px">Usage</th>
          <th style="width:70px">Active</th>
          <th style="width:70px">Public</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show promotions with type, value, dates, active/public badges -->
        <?php foreach ($promotions as $p): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $p['PromotionID'] ?></td>
            <td><strong><?= escape($p['Code']) ?></strong></td>
            <td><?= escape($p['Name']) ?></td>
            <td><span class="role-badge" style="white-space:nowrap;"><?= escape($p['Type']) ?></span></td>
            <td style="white-space:nowrap;"><?= $p['Type'] === 'Percentage' ? $p['Value'] . '%' : 'Rs ' . number_format((float)$p['Value'], 2) ?></td>
            <td style="color:var(--text-secondary)"><?= escape($p['Scope']) ?></td>
            <td style="color:var(--text-secondary);font-size:0.8rem;white-space:nowrap;">
              <?= date('d/m/Y', strtotime($p['StartDate'])) ?> - <?= date('d/m/Y', strtotime($p['EndDate'])) ?>
            </td>
            <td style="white-space:nowrap;"><?= (int)$p['UsageCount'] ?><?= $p['UsageLimit'] ? '/' . (int)$p['UsageLimit'] : '' ?></td>
            <td style="white-space:nowrap;">
              <span class="role-badge" style="background:<?= $p['IsActive'] ? 'var(--success)' : 'var(--danger)' ?>;color:#fff">
                <?= $p['IsActive'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td style="white-space:nowrap;">
              <span class="role-badge" style="background:<?= $p['IsPublic'] ? 'var(--secondary)' : 'var(--text-secondary)' ?>;color:#fff">
                <?= $p['IsPublic'] ? 'Public' : 'Private' ?>
              </span>
            </td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="editPromo(<?= $p['PromotionID'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $p['PromotionID'] ?>" data-field="promotion_id" data-csrf="<?= $csrf ?>" data-action="promotions.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($promotions)): ?>
          <tr><td colspan="11" style="text-align:center;padding:2rem;color:var(--text-secondary)">No promotions found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="promoModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:650px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('promoModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="promoModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Promotion</h3>
    <form id="promoForm" class="ajax-form" action="promotions.php" method="POST" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="promoAction" value="create">
      <input type="hidden" name="promotion_id" id="promoIdField" value="0">
      <div class="form-grid">
        <div class="form-group">
          <label for="p_code">Code *</label>
          <input type="text" id="p_code" name="code" class="input-field" required placeholder="SUMMER20">
        </div>
        <div class="form-group">
          <label for="p_name">Name *</label>
          <input type="text" id="p_name" name="name" class="input-field" required placeholder="Summer Sale 20%">
        </div>
        <div class="form-group">
          <label for="p_type">Type *</label>
          <select id="p_type" name="type" class="select-field" onchange="togglePromoFields()">
            <option value="Percentage">Percentage</option>
            <option value="FixedAmount">Fixed Amount</option>
            <option value="BuyXGetY">Buy X Get Y</option>
            <option value="FreeShipping">Free Shipping</option>
            <option value="Bundle">Bundle</option>
          </select>
        </div>
        <div class="form-group">
          <label for="p_scope">Scope *</label>
          <select id="p_scope" name="scope" class="select-field">
            <option value="Order">Order</option>
            <option value="Product">Product</option>
            <option value="Category">Category</option>
            <option value="Customer">Customer</option>
            <option value="Cart">Cart</option>
          </select>
        </div>
        <div class="form-group" id="valueField">
          <label for="p_value">Value *</label>
          <input type="number" id="p_value" name="value" class="input-field" step="0.01" value="0">
        </div>
        <div class="form-group">
          <label for="p_priority">Priority</label>
          <input type="number" id="p_priority" name="priority" class="input-field" value="0">
        </div>
        <div class="form-group" id="minOrderField">
          <label for="p_min_order">Min Order Amount</label>
          <input type="number" id="p_min_order" name="min_order_amount" class="input-field" step="0.01" value="0">
        </div>
        <div class="form-group" id="maxDiscountField">
          <label for="p_max_discount">Max Discount</label>
          <input type="number" id="p_max_discount" name="max_discount_amount" class="input-field" step="0.01" placeholder="Unlimited if empty">
        </div>
        <div class="form-group" id="buyQtyField" style="display:none">
          <label for="p_buy_qty">Buy Qty</label>
          <input type="number" id="p_buy_qty" name="buy_qty" class="input-field" value="1">
        </div>
        <div class="form-group" id="getQtyField" style="display:none">
          <label for="p_get_qty">Get Qty</label>
          <input type="number" id="p_get_qty" name="get_qty" class="input-field" value="1">
        </div>
        <div class="form-group" id="getDiscField" style="display:none">
          <label for="p_get_disc">Get Discount %</label>
          <input type="number" id="p_get_disc" name="get_discount_percent" class="input-field" step="0.01" value="0">
        </div>
        <div class="form-group">
          <label for="p_start">Start Date *</label>
          <input type="datetime-local" id="p_start" name="start_date" class="input-field" required>
        </div>
        <div class="form-group">
          <label for="p_end">End Date *</label>
          <input type="datetime-local" id="p_end" name="end_date" class="input-field" required>
        </div>
        <div class="form-group">
          <label for="p_usage_limit">Usage Limit</label>
          <input type="number" id="p_usage_limit" name="usage_limit" class="input-field" placeholder="Unlimited if empty">
        </div>
        <div class="form-group">
          <label for="p_per_customer">Per Customer Limit</label>
          <input type="number" id="p_per_customer" name="per_customer_limit" class="input-field" value="1">
        </div>
        <div class="form-group" style="display:flex;gap:1rem;align-items:center;padding-top:0.5rem;">
          <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;">
            <input type="checkbox" name="is_active" checked> Active
          </label>
          <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;">
            <input type="checkbox" name="is_public" checked> Public
          </label>
        </div>
        <div class="form-group full">
          <label for="p_desc">Description</label>
          <textarea id="p_desc" name="description" class="input-field" rows="2" placeholder="Promotion description..."></textarea>
        </div>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('promoModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var promosData = <?= json_encode(array_map(function($p) {
    return [
        'PromotionID' => $p['PromotionID'],
        'Code' => $p['Code'], 'Name' => $p['Name'], 'Description' => $p['Description'],
        'Type' => $p['Type'], 'Scope' => $p['Scope'], 'Value' => $p['Value'],
        'MinOrderAmount' => $p['MinOrderAmount'], 'MaxDiscountAmount' => $p['MaxDiscountAmount'],
        'BuyQuantity' => $p['BuyQuantity'], 'GetQuantity' => $p['GetQuantity'],
        'GetDiscountPercent' => $p['GetDiscountPercent'],
        'StartDate' => $p['StartDate'], 'EndDate' => $p['EndDate'],
        'UsageLimit' => $p['UsageLimit'], 'PerCustomerLimit' => $p['PerCustomerLimit'],
        'IsActive' => $p['IsActive'], 'IsPublic' => $p['IsPublic'], 'Priority' => $p['Priority'],
    ];
}, $promotions)) ?>;

// show/hide fields based on promotion type (BuyXGetY, Percentage, etc.)
function togglePromoFields() {
    var type = document.getElementById('p_type').value;
    document.getElementById('buyQtyField').style.display = type === 'BuyXGetY' ? '' : 'none';
    document.getElementById('getQtyField').style.display = type === 'BuyXGetY' ? '' : 'none';
    document.getElementById('getDiscField').style.display = type === 'BuyXGetY' ? '' : 'none';
    var label = document.getElementById('valueField').querySelector('label');
    label.textContent = type === 'Percentage' ? 'Discount % *' : type === 'FixedAmount' ? 'Discount Amount (Rs) *' : 'Value *';
}

// fill promotion form with data for editing
function editPromo(id) {
    var p = promosData.find(function(x) { return x.PromotionID == id; });
    if (!p) return;
    document.getElementById('promoAction').value = 'update';
    document.getElementById('promoIdField').value = p.PromotionID;
    document.getElementById('p_code').value = p.Code;
    document.getElementById('p_name').value = p.Name;
    document.getElementById('p_type').value = p.Type;
    document.getElementById('p_scope').value = p.Scope;
    document.getElementById('p_value').value = p.Value;
    document.getElementById('p_priority').value = p.Priority;
    document.getElementById('p_min_order').value = p.MinOrderAmount;
    document.getElementById('p_max_discount').value = p.MaxDiscountAmount || '';
    document.getElementById('p_buy_qty').value = p.BuyQuantity || 1;
    document.getElementById('p_get_qty').value = p.GetQuantity || 1;
    document.getElementById('p_get_disc').value = p.GetDiscountPercent || 0;
    document.getElementById('p_start').value = p.StartDate ? p.StartDate.substring(0, 16) : '';
    document.getElementById('p_end').value = p.EndDate ? p.EndDate.substring(0, 16) : '';
    document.getElementById('p_usage_limit').value = p.UsageLimit || '';
    document.getElementById('p_per_customer').value = p.PerCustomerLimit || 1;
    document.querySelector('input[name="is_active"]').checked = p.IsActive == 1;
    document.querySelector('input[name="is_public"]').checked = p.IsPublic == 1;
    document.getElementById('p_desc').value = p.Description || '';
    document.getElementById('promoModalTitle').textContent = 'Edit Promotion';
    togglePromoFields();
    openModal('promoModal');
}

var resetPromoModal = document.getElementById('promoModal');
if (resetPromoModal) {
    var obs = new MutationObserver(function() {
        if (resetPromoModal.classList.contains('hidden')) {
            document.getElementById('promoForm').reset();
            document.getElementById('promoAction').value = 'create';
            document.getElementById('promoIdField').value = '0';
            document.getElementById('promoModalTitle').textContent = 'Add Promotion';
        }
    });
    obs.observe(resetPromoModal, { attributes: true, attributeFilter: ['class'] });
}
</script>
