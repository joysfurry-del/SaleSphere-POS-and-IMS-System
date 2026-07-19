<?php
$customers = $customers ?? [];
$branches = $branches ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Customer Management</h2>
      <button class="btn-primary" onclick="openModal('customerModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Customer
      </button>
    </div>
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
      <select id="branchFilter" class="select-field" style="width:auto;min-width:200px;">
        <option value="">All Branches</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" id="searchInput" class="input-field" placeholder="Search name, email, phone..." style="flex:1;max-width:300px;">
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:50px">ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Branch</th>
          <th>Type</th>
          <th>Orders</th>
          <th>Total Spent</th>
          <th style="width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show customer list with order count, total spent, edit/delete -->
        <?php foreach ($customers as $c): ?>
          <tr>
            <td style="color:var(--text-secondary)">#<?= $c['CustomerID'] ?></td>
            <td><strong><?= escape($c['CustomerName']) ?></strong></td>
            <td style="color:var(--text-secondary)"><?= escape($c['Email'] ?? '-') ?></td>
            <td style="color:var(--text-secondary)"><?= escape($c['Phone'] ?? '-') ?></td>
            <td style="color:var(--text-secondary)"><?= escape($c['BranchName'] ?? '-') ?></td>
            <td><span class="role-badge" style="font-size:0.7rem"><?= escape($c['CustomerType'] ?? 'Walk-in') ?></span></td>
            <td style="color:var(--text-secondary)"><?= (int)($c['OrderCount'] ?? 0) ?></td>
            <td style="color:var(--text-secondary)">Rs <?= number_format((float)($c['TotalSpent'] ?? 0), 2) ?></td>
            <td>
              <div style="display:flex;gap:0.4rem;">
                <button class="btn-cyan" onclick="editCustomer(<?= $c['CustomerID'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $c['CustomerID'] ?>" data-field="customer_id" data-csrf="<?= $csrf ?>" data-action="customers.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
          <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-secondary)">No customers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-wrap hidden" id="customerModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:600px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('customerModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="customerModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Customer</h3>
    <form id="customerForm" class="ajax-form" action="customers.php" method="POST" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="customerAction" value="create">
      <input type="hidden" name="customer_id" id="customerIdField" value="0">
      <div class="form-grid">
        <div class="form-group">
          <label for="c_name">Customer Name</label>
          <input type="text" id="c_name" name="name" class="input-field" required>
        </div>
        <div class="form-group">
          <label for="c_branch">Branch</label>
          <select id="c_branch" name="branch_id" class="select-field" required>
            <option value="">Select branch...</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="c_email">Email</label>
          <input type="email" id="c_email" name="email" class="input-field" placeholder="customer@example.com">
        </div>
        <div class="form-group">
          <label for="c_phone">Phone</label>
          <input type="text" id="c_phone" name="phone" class="input-field" placeholder="+60 xx-xxxxxxx">
        </div>
        <div class="form-group full">
          <label for="c_address">Address</label>
          <input type="text" id="c_address" name="address" class="input-field" placeholder="Street address">
        </div>
        <div class="form-group">
          <label for="c_city">City</label>
          <input type="text" id="c_city" name="city" class="input-field">
        </div>
        <div class="form-group">
          <label for="c_state">State</label>
          <input type="text" id="c_state" name="state" class="input-field">
        </div>
        <div class="form-group">
          <label for="c_postcode">Postcode</label>
          <input type="text" id="c_postcode" name="postcode" class="input-field" placeholder="50000">
        </div>
        <div class="form-group">
          <label for="c_country">Country</label>
          <input type="text" id="c_country" name="country" class="input-field" value="Malaysia">
        </div>
        <div class="form-group full">
          <label for="c_notes">Notes</label>
          <textarea id="c_notes" name="notes" class="input-field" rows="3" placeholder="Additional notes..."></textarea>
        </div>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('customerModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var customersData = <?= json_encode(array_map(function($c) {
    return [
        'CustomerID' => $c['CustomerID'],
        'CustomerName' => $c['CustomerName'],
        'Email' => $c['Email'],
        'Phone' => $c['Phone'],
        'BranchID' => $c['BranchID'],
        'BranchName' => $c['BranchName'],
        'Address' => $c['Address'],
        'City' => $c['City'],
        'State' => $c['State'],
        'Postcode' => $c['Postcode'],
        'Country' => $c['Country'],
        'Notes' => $c['Notes'],
    ];
}, $customers)) ?>;

document.addEventListener('DOMContentLoaded', function() {
    var branchFilter = document.getElementById('branchFilter');
    var searchInput = document.getElementById('searchInput');
    if (branchFilter) branchFilter.addEventListener('change', filterTable);
    if (searchInput) searchInput.addEventListener('input', filterTable);
});

// filter table by branch and search name/email/phone
function filterTable() {
    var branchId = document.getElementById('branchFilter').value;
    var search = document.getElementById('searchInput').value.toLowerCase();
    var rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 9) return;
        var matchBranch = !branchId || cells[4].textContent.trim() === document.querySelector('#branchFilter option[value="'+branchId+'"]').textContent;
        var matchSearch = !search ||
            cells[1].textContent.toLowerCase().includes(search) ||
            cells[2].textContent.toLowerCase().includes(search) ||
            cells[3].textContent.toLowerCase().includes(search);
        row.style.display = (matchBranch && matchSearch) ? '' : 'none';
    });
}

// fill customer form with data for editing
function editCustomer(id) {
    var c = customersData.find(function(x) { return x.CustomerID == id; });
    if (!c) return;
    document.getElementById('customerAction').value = 'update';
    document.getElementById('customerIdField').value = c.CustomerID;
    document.getElementById('c_name').value = c.CustomerName;
    document.getElementById('c_branch').value = c.BranchID || '';
    document.getElementById('c_email').value = c.Email || '';
    document.getElementById('c_phone').value = c.Phone || '';
    document.getElementById('c_address').value = c.Address || '';
    document.getElementById('c_city').value = c.City || '';
    document.getElementById('c_state').value = c.State || '';
    document.getElementById('c_postcode').value = c.Postcode || '';
    document.getElementById('c_country').value = c.Country || 'Malaysia';
    document.getElementById('c_notes').value = c.Notes || '';
    document.getElementById('customerModalTitle').textContent = 'Edit Customer';
    openModal('customerModal');
}

// reset modal to add mode when closed
var resetCustomerModal = document.getElementById('customerModal');
if (resetCustomerModal) {
    var obs = new MutationObserver(function() {
        if (resetCustomerModal.classList.contains('hidden')) {
            document.getElementById('customerForm').reset();
            document.getElementById('customerAction').value = 'create';
            document.getElementById('customerIdField').value = '0';
            document.getElementById('customerModalTitle').textContent = 'Add Customer';
        }
    });
    obs.observe(resetCustomerModal, { attributes: true, attributeFilter: ['class'] });
}
</script>