<?php
$orders = $orders ?? [];
$branches = $branches ?? [];
$customers = $customers ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Online Orders</h2>
      <button class="btn-primary" onclick="openModal('orderModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Order
      </button>
    </div>
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
      <select id="branchFilter" class="select-field" style="width:auto;min-width:200px;">
        <option value="">All Branches</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="statusFilter" class="select-field" style="width:auto;min-width:160px;">
        <option value="">All Statuses</option>
        <option value="Pending">Pending</option>
        <option value="Confirmed">Confirmed</option>
        <option value="Processing">Processing</option>
        <option value="Shipped">Shipped</option>
        <option value="Delivered">Delivered</option>
        <option value="Cancelled">Cancelled</option>
        <option value="Returned">Returned</option>
      </select>
      <input type="text" id="searchInput" class="input-field" placeholder="Search order # or customer..." style="flex:1;max-width:300px;">
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:50px">ID</th>
          <th>Order #</th>
          <th>Customer</th>
          <th>Branch</th>
          <th style="width:100px">Status</th>
          <th>Payment</th>
          <th style="width:90px">Total</th>
          <th style="width:120px">Date</th>
          <th style="width:70px">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td style="color:var(--text-secondary)">#<?= $o['OnlineOrderID'] ?></td>
            <td><strong><?= escape($o['OrderNumber']) ?></strong></td>
            <td><?= escape($o['CustomerName'] ?? '-') ?></td>
            <td style="color:var(--text-secondary)"><?= escape($o['BranchName'] ?? '-') ?></td>
            <td>
              <span class="role-badge" style="font-size:0.7rem;background:<?php $__s = $o['Status']; echo $__s === 'Pending' ? 'var(--warning)' : ($__s === 'Confirmed' ? 'var(--info)' : ($__s === 'Processing' ? 'var(--info)' : ($__s === 'Shipped' ? 'var(--primary)' : ($__s === 'Delivered' ? 'var(--success)' : ($__s === 'Cancelled' || $__s === 'Returned' ? 'var(--danger)' : 'var(--text-secondary)'))))); ?>;color:#fff;">
                <?= escape($o['Status']) ?>
              </span>
            </td>
            <td style="color:var(--text-secondary);font-size:0.8rem;"><?= escape($o['PaymentStatus']) ?></td>
            <td style="font-weight:600;text-align:right;">Rs <?= number_format((float)$o['Total'], 2) ?></td>
            <td style="font-size:0.78rem;color:var(--text-secondary)"><?= date('d/m/Y H:i', strtotime($o['CreatedAt'])) ?></td>
            <td>
              <button class="btn-sm" onclick="viewOrder(<?= $o['OnlineOrderID'] ?>)" style="font-size:0.75rem;padding:0.25rem 0.6rem;">View</button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
          <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-secondary)">No online orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create Order Modal -->
<div class="modal-wrap hidden" id="orderModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:700px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('orderModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Create Online Order</h3>
    <form id="orderForm" class="ajax-form" action="online_orders.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="items" id="orderItemsInput" value="[]">

      <div class="form-grid">
        <div class="form-group">
          <label for="o_customer">Customer</label>
          <select id="o_customer" name="customer_id" class="select-field" required>
            <option value="">Select customer...</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['CustomerID'] ?>"><?= escape($c['CustomerName']) ?> (<?= escape($c['Email'] ?? '') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="o_branch">Branch</label>
          <select id="o_branch" name="branch_id" class="select-field" required>
            <option value="">Select branch...</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="o_payment">Payment Method</label>
          <select id="o_payment" name="payment_method" class="select-field">
            <option value="Online">Online</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
            <option value="Credit Card">Credit Card</option>
          </select>
        </div>
        <div class="form-group">
          <label for="o_amount">Amount Paid (Rs)</label>
          <input type="number" id="o_amount" name="amount_paid" class="input-field" step="0.01" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="o_shipping">Shipping (Rs)</label>
          <input type="number" id="o_shipping" name="shipping_amount" class="input-field" step="0.01" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="o_discount">Discount (Rs)</label>
          <input type="number" id="o_discount" name="discount_amount" class="input-field" step="0.01" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="o_promo">Promo Code</label>
          <input type="text" id="o_promo" name="promo_code" class="input-field" placeholder="Optional">
        </div>
      </div>

      <div style="margin-top:1rem;">
        <label>Order Items</label>
        <div id="orderItemsContainer" style="border:1px solid var(--border);border-radius:8px;padding:0.75rem;margin-top:0.5rem;min-height:80px;">
          <div id="orderItemsList" style="display:flex;flex-direction:column;gap:0.5rem;"></div>
          <button type="button" class="btn-secondary" onclick="addOrderItem()" style="font-size:0.8rem;margin-top:0.5rem;">+ Add Item</button>
        </div>
        <div id="orderItemTotals" style="display:flex;justify-content:flex-end;gap:1.5rem;margin-top:0.75rem;font-size:0.85rem;">
          <span>Subtotal: Rs <span id="orderSubtotal">0.00</span></span>
          <span>Tax (6%): Rs <span id="orderTax">0.00</span></span>
          <span style="font-weight:700;">Total: Rs <span id="orderTotal">0.00</span></span>
        </div>
      </div>

      <div class="form-group" style="margin-top:1rem;">
        <label for="o_notes">Notes</label>
        <textarea id="o_notes" name="notes" class="input-field" rows="2" placeholder="Optional notes..."></textarea>
      </div>

      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Create Order</button>
        <button type="button" class="btn-secondary" onclick="closeModal('orderModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- View Order Modal -->
<div class="modal-wrap hidden" id="viewOrderModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:780px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('viewOrderModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <div id="viewOrderContent"></div>
  </div>
</div>

<script>
var orderProducts = [];

function addOrderItem() {
    var container = document.getElementById('orderItemsList');
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;gap:0.5rem;align-items:center;';
    row.innerHTML =
        '<select class="select-field product-select" style="flex:2;min-width:120px;" onchange="updateOrderItem(this)">' +
        '<option value="">-- Select product --</option>' +
        '</select>' +
        '<input type="number" class="input-field qty-input" placeholder="Qty" min="1" value="1" style="width:70px;" onchange="updateOrderItem(this)">' +
        '<input type="number" class="input-field price-input" placeholder="Price" step="0.01" min="0" style="width:100px;" onchange="updateOrderItem(this)">' +
        '<span style="font-size:0.8rem;min-width:80px;text-align:right;">Rs <span class="line-total">0.00</span></span>' +
        '<button type="button" class="delete-btn btn-danger-sm" onclick="this.parentElement.remove();updateOrderTotals();">X</button>';
    container.appendChild(row);
    loadProductsIntoSelect(row.querySelector('.product-select'));
}

function loadProductsIntoSelect(select) {
    if (orderProducts.length === 0) {
        fetch('products.php?ajax=1&limit=500')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    orderProducts = data.data || [];
                    populateSelect(select);
                }
            })
            .catch(function() {});
    } else {
        populateSelect(select);
    }
}

function populateSelect(select) {
    orderProducts.forEach(function(p) {
        var opt = document.createElement('option');
        opt.value = p.ProductID;
        opt.textContent = p.ProductName + ' (Rs ' + Number(p.Price).toFixed(2) + ')';
        opt.dataset.price = p.Price;
        select.appendChild(opt);
    });
}

function updateOrderItem(el) {
    var row = el.closest ? el.closest('div') : el.parentElement;
    var select = row.querySelector('.product-select');
    var qty = parseInt(row.querySelector('.qty-input').value) || 1;
    var price = parseFloat(row.querySelector('.price-input').value) || 0;

    if (select.selectedIndex > 0) {
        var opt = select.options[select.selectedIndex];
        if (!row.querySelector('.price-input').value) {
            row.querySelector('.price-input').value = opt.dataset.price;
            price = parseFloat(opt.dataset.price);
        }
    }

    var total = qty * price;
    row.querySelector('.line-total').textContent = total.toFixed(2);
    updateOrderTotals();
}

function updateOrderTotals() {
    var rows = document.querySelectorAll('#orderItemsList > div');
    var subtotal = 0;
    rows.forEach(function(row) {
        subtotal += parseFloat(row.querySelector('.line-total').textContent) || 0;
    });
    var tax = subtotal * 0.06;
    var shipping = parseFloat(document.getElementById('o_shipping').value) || 0;
    var discount = parseFloat(document.getElementById('o_discount').value) || 0;
    var total = subtotal + tax + shipping - discount;
    if (total < 0) total = 0;

    document.getElementById('orderSubtotal').textContent = subtotal.toFixed(2);
    document.getElementById('orderTax').textContent = tax.toFixed(2);
    document.getElementById('orderTotal').textContent = total.toFixed(2);
}

document.getElementById('orderForm').addEventListener('submit', function(e) {
    var rows = document.querySelectorAll('#orderItemsList > div');
    var items = [];
    var valid = true;
    rows.forEach(function(row) {
        var select = row.querySelector('.product-select');
        var qty = parseInt(row.querySelector('.qty-input').value);
        var price = parseFloat(row.querySelector('.price-input').value);
        if (select.selectedIndex > 0 && qty > 0 && price > 0) {
            var opt = select.options[select.selectedIndex];
            items.push({
                ProductID: parseInt(opt.value),
                ProductName: opt.textContent.split(' (Rs')[0],
                Price: price,
                Quantity: qty,
            });
        } else if (select.selectedIndex > 0) {
            valid = false;
        }
    });
    if (!valid || items.length === 0) {
        e.preventDefault();
        showToast('error', 'Please add at least one valid item.');
        return;
    }
    document.getElementById('orderItemsInput').value = JSON.stringify(items);
});

function viewOrder(id) {
    var modal = document.getElementById('viewOrderModal');
    var content = document.getElementById('viewOrderContent');
    content.innerHTML = '<div class="spinner" style="margin:2rem auto;"></div>';
    openModal('viewOrderModal');

    fetch('ajax/order_detail.php?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                content.innerHTML = '<p style="color:var(--danger);">' + (data.message || 'Order not found.') + '</p>';
                return;
            }
            var o = data.order;
            var items = data.items || [];

            var html = '';

            // Header with order number + status badge
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">';
            html += '<h3 style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin:0;">Order ' + o.OrderNumber + '</h3>';
            html += '<span class="role-badge" style="background:' + statusColor(o.Status) + ';color:#fff;font-size:0.8rem;padding:0.3rem 0.75rem;">' + o.Status + '</span>';
            html += '</div>';

            // Two-column layout: Order Info | Customer Info
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">';

            // Left: Order Info
            html += '<div style="border:1px solid var(--border);border-radius:8px;padding:1rem;">';
            html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.75rem;">Order Info</h4>';
            html += '<div style="display:grid;gap:0.4rem;font-size:0.85rem;">';
            html += '<div><span style="color:var(--text-secondary);">Branch:</span> ' + escapeHtml(o.BranchName || '-') + '</div>';
            html += '<div><span style="color:var(--text-secondary);">Payment:</span> ' + escapeHtml(o.PaymentMethod) + ' (' + o.PaymentStatus + ')</div>';
            html += '<div><span style="color:var(--text-secondary);">Date:</span> ' + o.CreatedAt + '</div>';
            if (o.AdminNotes) html += '<div><span style="color:var(--text-secondary);">Admin Notes:</span> ' + escapeHtml(o.AdminNotes) + '</div>';
            if (o.Notes) html += '<div><span style="color:var(--text-secondary);">Customer Notes:</span> ' + escapeHtml(o.Notes) + '</div>';
            html += '</div></div>';

            // Right: Customer Info
            html += '<div style="border:1px solid var(--border);border-radius:8px;padding:1rem;">';
            html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.75rem;">Customer</h4>';
            html += '<div style="display:grid;gap:0.4rem;font-size:0.85rem;">';
            html += '<div><strong>' + escapeHtml(o.CustomerName || '-') + '</strong></div>';
            if (o.Email) html += '<div>' + escapeHtml(o.Email) + '</div>';
            if (o.Phone) html += '<div>' + escapeHtml(o.Phone) + '</div>';
            html += '</div></div>';

            html += '</div>';

            // Shipping Info (if present)
            if (o.ShippingName || o.ShippingAddress || o.ShippingCity) {
                html += '<div style="border:1px solid var(--border);border-radius:8px;padding:1rem;margin-bottom:1.5rem;">';
                html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.75rem;">Shipping Address</h4>';
                html += '<div style="display:grid;gap:0.3rem;font-size:0.85rem;">';
                if (o.ShippingName) html += '<div><strong>' + escapeHtml(o.ShippingName) + '</strong></div>';
                if (o.ShippingAddress) html += '<div>' + escapeHtml(o.ShippingAddress) + '</div>';
                if (o.ShippingCity) {
                    var shipCity = o.ShippingCity;
                    if (o.ShippingPostalCode) shipCity += ' ' + o.ShippingPostalCode;
                    if (o.ShippingState) shipCity += ', ' + o.ShippingState;
                    html += '<div>' + escapeHtml(shipCity) + '</div>';
                }
                html += '</div></div>';
            }

            // Order Items table
            if (items.length > 0) {
                html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.5rem;">Items</h4>';
                html += '<table class="data-table" style="margin-bottom:1rem;"><thead><tr><th>Product</th><th style="text-align:right;">Price</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Total</th></tr></thead><tbody>';
                items.forEach(function(item) {
                    html += '<tr><td>' + escapeHtml(item.ProductName) + '</td><td style="text-align:right;">Rs ' + Number(item.Price).toFixed(2) + '</td><td style="text-align:center;">' + item.Quantity + '</td><td style="text-align:right;">Rs ' + Number(item.LineTotal).toFixed(2) + '</td></tr>';
                });
                html += '</tbody></table>';
            }

            // Totals
            html += '<div style="display:flex;justify-content:flex-end;gap:1rem;font-size:0.85rem;border-top:1px solid var(--border);padding-top:0.75rem;margin-bottom:1.5rem;">';
            html += '<span>Subtotal: Rs ' + Number(o.Subtotal).toFixed(2) + '</span>';
            html += '<span>Tax: Rs ' + Number(o.TaxAmount).toFixed(2) + '</span>';
            if (Number(o.ShippingAmount || 0) > 0) html += '<span>Shipping: Rs ' + Number(o.ShippingAmount).toFixed(2) + '</span>';
            if (Number(o.DiscountAmount || 0) > 0) html += '<span>Discount: -Rs ' + Number(o.DiscountAmount).toFixed(2) + '</span>';
            html += '<span style="font-weight:700;">Total: Rs ' + Number(o.Total).toFixed(2) + '</span>';
            html += '</div>';

            // Status action buttons
            html += '<div style="border-top:1px solid var(--border);padding-top:1rem;">';
            html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.75rem;">Update Status</h4>';
            html += '<div style="display:flex;gap:0.5rem;flex-wrap:wrap;">';

            // Show relevant buttons based on current status
            var actions = [];
            if (o.Status === 'Pending') {
                actions.push({ status: 'Confirmed', label: 'Accept', cls: 'btn-cyan' });
                actions.push({ status: 'Cancelled', label: 'Decline', cls: 'btn-danger-sm' });
            } else if (o.Status === 'Confirmed') {
                actions.push({ status: 'Processing', label: 'Process', cls: 'btn-cyan' });
                actions.push({ status: 'Cancelled', label: 'Cancel', cls: 'btn-danger-sm' });
            } else if (o.Status === 'Processing') {
                actions.push({ status: 'Shipped', label: 'Ship', cls: 'btn-primary' });
            } else if (o.Status === 'Shipped') {
                actions.push({ status: 'Delivered', label: 'Mark Delivered', cls: 'btn-success-sm' });
            }

            // Always show these
            if (o.Status !== 'Cancelled') actions.push({ status: 'Cancelled', label: 'Cancel Order', cls: 'btn-danger-sm' });

            actions.forEach(function(a) {
                html += '<button class="modal-status-btn ' + a.cls + '" data-id="' + o.OnlineOrderID + '" data-status="' + a.status + '" style="font-size:0.8rem;padding:0.35rem 0.8rem;">' + a.label + '</button>';
            });

            html += '</div></div>';

            content.innerHTML = html;
        })
        .catch(function(err) {
            console.error('viewOrder fetch error:', err);
            content.innerHTML = '<p style="color:var(--danger);">Error loading order. Check console (F12) for details.</p>';
        });
}

function escapeHtml(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }

function statusColor(s) {
    var colors = { Pending: 'var(--warning)', Confirmed: 'var(--info)', Processing: 'var(--info)', Shipped: 'var(--primary)', Delivered: 'var(--success)', Cancelled: 'var(--danger)', Returned: 'var(--danger)' };
    return colors[s] || 'var(--text-secondary)';
}

document.addEventListener('DOMContentLoaded', function() {
    var branchFilter = document.getElementById('branchFilter');
    var statusFilter = document.getElementById('statusFilter');
    var searchInput = document.getElementById('searchInput');
    if (branchFilter) branchFilter.addEventListener('change', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
    if (searchInput) searchInput.addEventListener('input', filterTable);

    // Status buttons in modal (delegated)
    document.getElementById('viewOrderContent').addEventListener('click', function(e) {
        var btn = e.target.closest('.modal-status-btn');
        if (btn) {
            updateOrderStatus(btn.dataset.id, btn.dataset.status, btn);
        }
    });

    var ship = document.getElementById('o_shipping');
    var disc = document.getElementById('o_discount');
    if (ship) ship.addEventListener('input', updateOrderTotals);
    if (disc) disc.addEventListener('input', updateOrderTotals);
});

function updateOrderStatus(id, status, btn) {
    var formData = new FormData();
    formData.append('csrf_token', '<?= $csrf ?>');
    formData.append('action', 'update_status');
    formData.append('order_id', id);
    formData.append('status', status);

    btn.disabled = true;
    btn.textContent = '...';

    fetch('online_orders.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            showToast(d.success ? 'success' : 'error', d.message || 'Request failed.');
            if (d.success) {
                // Reload the order in the modal
                setTimeout(function() { viewOrder(id); }, 300);
            } else {
                btn.disabled = false;
            }
        })
        .catch(function() {
            btn.disabled = false;
            showToast('error', 'Request failed.');
        });
}

function filterTable() {
    var branchId = document.getElementById('branchFilter').value;
    var status = document.getElementById('statusFilter').value;
    var search = document.getElementById('searchInput').value.toLowerCase();
    var rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 9) return;
        var matchBranch = !branchId || cells[3].textContent.trim() === (document.querySelector('#branchFilter option[value="' + branchId + '"]')?.textContent || '');
        var matchStatus = !status || cells[4].textContent.trim() === status;
        var matchSearch = !search || cells[1].textContent.toLowerCase().includes(search) || cells[2].textContent.toLowerCase().includes(search);
        row.style.display = (matchBranch && matchStatus && matchSearch) ? '' : 'none';
    });
}
</script>
