<?php
$bills = $bills ?? [];
$branches = $branches ?? [];
$customers = $customers ?? [];
$products = $products ?? [];
$orders = $orders ?? [];
$onlineOrders = $onlineOrders ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Bills &amp; Invoices</h2>
      <button class="btn-primary" onclick="openModal('billModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create Invoice
      </button>
    </div>
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
      <select id="branchFilter" class="select-field" style="width:auto;min-width:200px;">
        <option value="">All Branches</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" id="searchInput" class="input-field" placeholder="Search bill number or customer..." style="flex:1;max-width:300px;">
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:1050px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Bill #</th>
          <th>Customer</th>
          <th>Branch</th>
          <th style="width:100px;text-align:right">Total</th>
          <th style="width:100px;text-align:right">Paid</th>
          <th style="width:100px;text-align:right">Balance</th>
          <th style="width:85px">Status</th>
          <th style="width:100px">Date</th>
          <th style="width:270px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show bills with balance, status badge, view/download/delete -->
        <?php foreach ($bills as $b): ?>
          <?php $balance = (float)$b['Total'] - (float)$b['AmountPaid']; ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $b['BillID'] ?></td>
            <td><strong><?= escape($b['BillNumber']) ?></strong></td>
            <td><?= escape($b['CustomerName'] ?? '-') ?></td>
            <td style="color:var(--text-secondary)"><?= escape($b['BranchName'] ?? '-') ?></td>
            <td style="text-align:right;white-space:nowrap;">Rs <?= number_format((float)$b['Total'], 2) ?></td>
            <td style="color:var(--success);text-align:right;white-space:nowrap;">Rs <?= number_format((float)$b['AmountPaid'], 2) ?></td>
            <td style="color:<?= $balance > 0 ? 'var(--danger)' : 'var(--text-secondary)' ?>;text-align:right;white-space:nowrap;">Rs <?= number_format($balance, 2) ?></td>
            <td>
              <span class="role-badge" style="font-size:0.7rem;background:<?php
                $s = $b['Status'];
                echo $s === 'Paid' ? 'var(--success)' : ($s === 'Draft' ? 'var(--text-secondary)' : ($s === 'Overdue' ? 'var(--danger)' : 'var(--warning)'));
              ?>;color:#fff;white-space:nowrap;"><?= escape($s) ?></span>
            </td>
            <td style="color:var(--text-secondary);font-size:0.8rem;white-space:nowrap;"><?= date('d/m/Y', strtotime($b['CreatedAt'])) ?></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="viewBill(<?= $b['BillID'] ?>)">View</button>
                <button class="btn-primary-sm" onclick="window.open('bills.php?action=download_pdf&id=<?= $b['BillID'] ?>','_blank')">Download PDF</button>
                <button class="btn-print-sm" onclick="window.open('bills.php?action=download&id=<?= $b['BillID'] ?>','_blank')">Print</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $b['BillID'] ?>" data-field="bill_id" data-csrf="<?= $csrf ?>" data-action="bills.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($bills)): ?>
          <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-secondary)">No bills found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="billModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:750px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('billModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Record Stock Purchase</h3>
    <form id="billForm" class="ajax-form" action="bills.php" method="POST" data-btn-text="Record Purchase">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="bill_type" value="Invoice">
      <input type="hidden" name="reference_type" value="Manual">
      <div class="form-grid">
        <div class="form-group">
          <label for="b_branch">Branch *</label>
          <select id="b_branch" name="branch_id" class="select-field" required>
            <option value="">Select branch...</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="b_payment_method">Payment Method</label>
          <select id="b_payment_method" name="payment_method" class="select-field">
            <option value="Cash">Cash</option>
            <option value="Card">Card</option>
            <option value="Bank Transfer">Bank Transfer</option>
          </select>
        </div>
        <div class="form-group">
          <label for="b_paid">Amount Paid (Rs)</label>
          <input type="number" id="b_paid" name="amount_paid" class="input-field" step="0.01" value="0" oninput="calcBillTotals()">
        </div>
        <div class="form-group full">
          <label for="b_notes">Notes</label>
          <textarea id="b_notes" name="notes" class="input-field" rows="2" placeholder="Record details about this stock purchase..."></textarea>
        </div>
      </div>

      <h4 style="font-size:0.95rem;font-weight:600;color:var(--text-primary);margin:1rem 0 0.5rem;">Items Purchased</h4>
      <p style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:0.5rem;">Items added here will be recorded as a bill <strong>and</strong> added to inventory.</p>
      <div style="overflow-x:auto;">
        <table class="data-table" style="margin:0;">
          <thead>
            <tr>
              <th style="width:35%">Product</th>
              <th style="width:70px">Qty</th>
              <th style="width:90px">Cost Price (Rs)</th>
              <th style="width:80px">Tax (Rs)</th>
              <th style="width:90px">Line Total</th>
              <th style="width:30px"></th>
            </tr>
          </thead>
          <tbody id="billItemsBody">
            <tr>
              <td><input type="text" name="item_description[]" class="input-field" style="width:100%;" placeholder="Product name" list="productList"></td>
              <td><input type="number" name="item_qty[]" class="input-field" style="width:100%;" value="1" min="1" oninput="calcBillTotals()"></td>
              <td><input type="number" name="item_price[]" class="input-field" style="width:100%;" step="0.01" value="0" min="0" oninput="calcBillTotals()"></td>
              <td><input type="number" name="item_tax[]" class="input-field" style="width:100%;" step="0.01" value="0" oninput="calcBillTotals()"></td>
              <td><input type="number" name="item_line_total[]" class="input-field" style="width:100%;" step="0.01" value="0" readonly></td>
              <td><button type="button" onclick="this.closest('tr').remove();calcBillTotals();" style="background:none;border:none;color:var(--danger);cursor:pointer;">&times;</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <button type="button" class="btn-secondary" onclick="addBillRow()" style="font-size:0.8rem;margin-top:0.5rem;">+ Add Item</button>

      <datalist id="productList">
        <?php foreach ($products as $p): ?>
          <option value="<?= escape($p['ProductName']) ?>" data-price="<?= $p['Price'] ?>">
        <?php endforeach; ?>
      </datalist>

      <div style="display:flex;gap:1.5rem;justify-content:flex-end;margin-top:1rem;font-size:0.9rem;">
        <div><strong>Subtotal:</strong> Rs <span id="billSubtotal">0.00</span></div>
        <div><strong>Tax:</strong> Rs <span id="billTax">0.00</span></div>
        <div><strong>Total Paid:</strong> Rs <span id="billTotal">0.00</span></div>
      </div>
      <input type="hidden" name="subtotal" id="b_subtotal_hidden" value="0">
      <input type="hidden" name="tax_amount" id="b_tax_hidden" value="0">
      <input type="hidden" name="discount_amount" id="b_discount_hidden" value="0">
      <input type="hidden" name="total" id="b_total_hidden" value="0">
      <input type="hidden" name="is_stock_purchase" value="1">

      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Record Purchase &amp; Add to Stock</button>
        <button type="button" class="btn-secondary" onclick="closeModal('billModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-wrap hidden" id="viewBillModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:650px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('viewBillModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <div id="billDetailContent"></div>
  </div>
</div>

<script>
var productsData = <?= json_encode($products) ?>;

document.addEventListener('DOMContentLoaded', function() {
    var branchFilter = document.getElementById('branchFilter');
    var searchInput = document.getElementById('searchInput');
    if (branchFilter) branchFilter.addEventListener('change', filterTable);
    if (searchInput) searchInput.addEventListener('input', filterTable);
});

// filter bills by branch and search text
function filterTable() {
    var branchId = document.getElementById('branchFilter').value;
    var search = document.getElementById('searchInput').value.toLowerCase();
    var rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 10) return;
        var matchBranch = !branchId || (cells[3].textContent.trim() === document.querySelector('#branchFilter option[value="'+branchId+'"]')?.textContent);
        var matchSearch = !search || cells[1].textContent.toLowerCase().includes(search);
        row.style.display = (matchBranch && matchSearch) ? '' : 'none';
    });
}

function addBillRow() {
    var tbody = document.getElementById('billItemsBody');
    var tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="item_description[]" class="input-field" style="width:100%;" placeholder="Product name" list="productList"></td>
        <td><input type="number" name="item_qty[]" class="input-field" style="width:100%;" value="1" min="1" oninput="calcBillTotals()"></td>
        <td><input type="number" name="item_price[]" class="input-field" style="width:100%;" step="0.01" value="0" min="0" oninput="calcBillTotals()"></td>
        <td><input type="number" name="item_tax[]" class="input-field" style="width:100%;" step="0.01" value="0" oninput="calcBillTotals()"></td>
        <td><input type="number" name="item_line_total[]" class="input-field" style="width:100%;" step="0.01" value="0" readonly></td>
        <td><button type="button" onclick="this.closest('tr').remove();calcBillTotals();" style="background:none;border:none;color:var(--danger);cursor:pointer;">&times;</button></td>
    `;
    tbody.appendChild(tr);
}

function calcBillTotals() {
    var subtotal = 0;
    var taxTotal = 0;
    var rows = document.querySelectorAll('#billItemsBody tr');
    rows.forEach(function(row) {
        var qty = parseFloat(row.querySelector('input[name="item_qty[]"]')?.value) || 0;
        var price = parseFloat(row.querySelector('input[name="item_price[]"]')?.value) || 0;
        var tax = parseFloat(row.querySelector('input[name="item_tax[]"]')?.value) || 0;
        var lineTotal = qty * price;
        var lineTotalInput = row.querySelector('input[name="item_line_total[]"]');
        if (lineTotalInput) lineTotalInput.value = lineTotal.toFixed(2);
        subtotal += lineTotal;
        taxTotal += tax;
    });
    var totalTax = taxTotal;
    var total = subtotal + totalTax;
    document.getElementById('billSubtotal').textContent = subtotal.toFixed(2);
    document.getElementById('billTax').textContent = totalTax.toFixed(2);
    document.getElementById('billTotal').textContent = total.toFixed(2);
    document.getElementById('b_subtotal_hidden').value = subtotal.toFixed(2);
    document.getElementById('b_tax_hidden').value = totalTax.toFixed(2);
    document.getElementById('b_total_hidden').value = total.toFixed(2);
}

// fetch bill details and show in modal
function viewBill(id) {
    fetch('bills.php?action=get&id=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.getElementById('billDetailContent').innerHTML = html || '<p>Bill details not available.</p>';
            openModal('viewBillModal');
        })
        .catch(function() { showToast('error', 'Failed to load bill details.'); });
}
</script>
