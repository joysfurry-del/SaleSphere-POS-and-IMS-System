<?php
$returns = $returns ?? [];
$branches = $branches ?? [];
$customers = $customers ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Product Returns</h2>
    </div>
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
      <select id="branchFilter" class="select-field" style="width:auto;min-width:200px;">
        <option value="">All Branches</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" id="searchInput" class="input-field" placeholder="Search return number or customer..." style="flex:1;max-width:300px;">
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:850px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Return #</th>
          <th>Customer</th>
          <th>Branch</th>
          <th>Reason</th>
          <th style="width:90px;text-align:right">Refund</th>
          <th style="width:90px">Status</th>
          <th style="width:90px">Date</th>
          <th style="width:90px">Action</th>
        </tr>
      </thead>
      <tbody>
        <!-- list returns with status badge, refund, and view button -->
        <?php foreach ($returns as $r): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $r['ReturnID'] ?></td>
            <td><strong><?= escape($r['ReturnNumber']) ?></strong></td>
            <td><?= escape($r['CustomerName'] ?? '-') ?></td>
            <td style="color:var(--text-secondary)"><?= escape($r['BranchName'] ?? '-') ?></td>
            <td><span class="role-badge" style="font-size:0.7rem;white-space:nowrap;"><?= escape($r['Reason']) ?></span></td>
            <td style="text-align:right;white-space:nowrap;">Rs <?= number_format((float)$r['TotalRefund'], 2) ?></td>
            <td style="white-space:nowrap;">
              <span class="role-badge" style="font-size:0.7rem;background:<?php
                $s = $r['Status'];
                echo $s === 'Refunded' ? 'var(--success)' : ($s === 'Rejected' || $s === 'Cancelled' ? 'var(--danger)' : ($s === 'Requested' ? 'var(--warning)' : 'var(--secondary)'));
              ?>;color:#fff"><?= escape($s) ?></span>
            </td>
            <td style="color:var(--text-secondary);font-size:0.8rem;white-space:nowrap;"><?= date('d/m/Y', strtotime($r['RequestedAt'])) ?></td>
            <td style="white-space:nowrap;">
              <button class="btn-cyan" onclick="viewReturn(<?= $r['ReturnID'] ?>)">View</button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($returns)): ?>
          <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-secondary)">No returns found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- View Return Modal -->
<div class="modal-wrap hidden" id="viewReturnModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:700px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('viewReturnModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <div id="viewReturnContent"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var branchFilter = document.getElementById('branchFilter');
    var searchInput = document.getElementById('searchInput');
    if (branchFilter) branchFilter.addEventListener('change', filterTable);
    if (searchInput) searchInput.addEventListener('input', filterTable);

    // Status buttons in modal (delegated)
    document.getElementById('viewReturnContent').addEventListener('click', function(e) {
        var btn = e.target.closest('.return-status-btn');
        if (btn) {
            updateReturnStatus(btn.dataset.id, btn.dataset.status, btn);
        }
    });
});

function escapeHtml(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }

// load return detail via ajax and show in modal
function viewReturn(id) {
    var content = document.getElementById('viewReturnContent');
    content.innerHTML = '<div class="spinner" style="margin:2rem auto;"></div>';
    openModal('viewReturnModal');

    fetch('ajax/return_detail.php?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                content.innerHTML = '<p style="color:var(--danger);">' + (data.message || 'Return not found.') + '</p>';
                return;
            }
            var r = data.return;
            var items = data.items || [];

            var html = '';

            // Header
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">';
            html += '<h3 style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin:0;">Return ' + r.ReturnNumber + '</h3>';
            html += '<span class="role-badge" style="background:' + returnStatusColor(r.Status) + ';color:#fff;font-size:0.8rem;padding:0.3rem 0.75rem;">' + r.Status + '</span>';
            html += '</div>';

            // Return Info | Customer
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">';

            html += '<div style="border:1px solid var(--border);border-radius:8px;padding:1rem;">';
            html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.75rem;">Return Info</h4>';
            html += '<div style="display:grid;gap:0.4rem;font-size:0.85rem;">';
            html += '<div><span style="color:var(--text-secondary);">Branch:</span> ' + escapeHtml(r.BranchName || '-') + '</div>';
            html += '<div><span style="color:var(--text-secondary);">Reason:</span> ' + escapeHtml(r.Reason) + '</div>';
            if (r.ReasonDetails) html += '<div><span style="color:var(--text-secondary);">Details:</span> ' + escapeHtml(r.ReasonDetails) + '</div>';
            html += '<div><span style="color:var(--text-secondary);">Refund Method:</span> ' + escapeHtml(r.RefundMethod) + '</div>';
            html += '<div><span style="color:var(--text-secondary);">Date:</span> ' + r.RequestedAt + '</div>';
            if (r.AdminNotes) html += '<div><span style="color:var(--text-secondary);">Admin Notes:</span> ' + escapeHtml(r.AdminNotes) + '</div>';
            html += '</div></div>';

            html += '<div style="border:1px solid var(--border);border-radius:8px;padding:1rem;">';
            html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.75rem;">Customer</h4>';
            html += '<div style="display:grid;gap:0.4rem;font-size:0.85rem;">';
            html += '<div><strong>' + escapeHtml(r.CustomerName || 'Walk-in') + '</strong></div>';
            html += '</div></div>';

            html += '</div>';

            // Items
            if (items.length > 0) {
                html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.5rem;">Items</h4>';
                html += '<table class="data-table" style="margin-bottom:1rem;"><thead><tr><th>Product</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Price</th><th style="text-align:right;">Total</th></tr></thead><tbody>';
                items.forEach(function(item) {
                    html += '<tr><td>' + escapeHtml(item.ProductName) + '</td><td style="text-align:center;">' + item.Quantity + '</td><td style="text-align:right;">Rs ' + Number(item.UnitPrice).toFixed(2) + '</td><td style="text-align:right;">Rs ' + Number(item.LineTotal).toFixed(2) + '</td></tr>';
                });
                html += '</tbody></table>';
            }

            // Totals
            html += '<div style="display:flex;justify-content:flex-end;gap:1rem;font-size:0.85rem;border-top:1px solid var(--border);padding-top:0.75rem;margin-bottom:1.5rem;">';
            html += '<span>Subtotal: Rs ' + Number(r.Subtotal || 0).toFixed(2) + '</span>';
            if (Number(r.RestockFee || 0) > 0) html += '<span>Restock Fee: -Rs ' + Number(r.RestockFee).toFixed(2) + '</span>';
            if (Number(r.ShippingFee || 0) > 0) html += '<span>Shipping Fee: -Rs ' + Number(r.ShippingFee).toFixed(2) + '</span>';
            html += '<span style="font-weight:700;">Total Refund: Rs ' + Number(r.TotalRefund || 0).toFixed(2) + '</span>';
            html += '</div>';

                    // show status-specific action buttons (approve, reject, refund, etc.)
            html += '<div style="border-top:1px solid var(--border);padding-top:1rem;">';
            html += '<h4 style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;margin:0 0 0.75rem;">Actions</h4>';
            html += '<div style="display:flex;gap:0.5rem;flex-wrap:wrap;">';

            if (r.Status === 'Requested') {
                html += '<button class="return-status-btn btn-cyan" data-id="' + r.ReturnID + '" data-status="Approved" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Approve</button>';
                html += '<button class="return-status-btn btn-danger-sm" data-id="' + r.ReturnID + '" data-status="Rejected" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Reject</button>';
            } else if (r.Status === 'Approved') {
                html += '<button class="return-status-btn btn-cyan" data-id="' + r.ReturnID + '" data-status="Received" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Mark Received</button>';
            } else if (r.Status === 'Received') {
                html += '<button class="return-status-btn btn-cyan" data-id="' + r.ReturnID + '" data-status="Processed" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Mark Processed</button>';
            } else if (r.Status === 'Processed') {
                html += '<button class="return-status-btn btn-primary" data-id="' + r.ReturnID + '" data-status="Refunded" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Issue Refund</button>';
            }

            if (r.Status !== 'Refunded' && r.Status !== 'Rejected' && r.Status !== 'Cancelled') {
                html += '<button class="return-status-btn btn-danger-sm" data-id="' + r.ReturnID + '" data-status="Cancelled" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Cancel</button>';
            }

            // Delete button (always visible)
            html += '<form method="POST" action="returns.php" style="display:inline;" onsubmit="return confirm(\'Delete this return?\')">';
            html += '<input type="hidden" name="csrf_token" value="<?= $csrf ?>">';
            html += '<input type="hidden" name="action" value="delete">';
            html += '<input type="hidden" name="return_id" value="' + r.ReturnID + '">';
            html += '<button type="submit" class="btn-danger-sm" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Delete</button>';
            html += '</form>';

            html += '</div></div>';

            content.innerHTML = html;
        })
        .catch(function() {
            content.innerHTML = '<p style="color:var(--danger);">Error loading return details.</p>';
        });
}

// send ajax to update return status
function updateReturnStatus(id, status, btn) {
    var formData = new FormData();
    formData.append('csrf_token', '<?= $csrf ?>');
    formData.append('action', 'update_status');
    formData.append('return_id', id);
    formData.append('status', status);

    btn.disabled = true;
    btn.textContent = '...';

    fetch('returns.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            showToast(d.success ? 'success' : 'error', d.message || 'Request failed.');
            if (d.success) {
                setTimeout(function() { viewReturn(id); }, 300);
            } else {
                btn.disabled = false;
                btn.textContent = status;
            }
        })
        .catch(function() {
            btn.disabled = false;
            showToast('error', 'Request failed.');
        });
}

function returnStatusColor(s) {
    var colors = { Requested: 'var(--warning)', Approved: 'var(--info)', Received: 'var(--info)', Processed: 'var(--primary)', Refunded: 'var(--success)', Rejected: 'var(--danger)', Cancelled: 'var(--danger)' };
    return colors[s] || 'var(--text-secondary)';
}

// filter returns by branch and search text
function filterTable() {
    var branchId = document.getElementById('branchFilter').value;
    var search = document.getElementById('searchInput').value.toLowerCase();
    var rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 9) return;
        var matchBranch = !branchId || (cells[3].textContent.trim() === document.querySelector('#branchFilter option[value="'+branchId+'"]')?.textContent);
        var matchSearch = !search || cells[1].textContent.toLowerCase().includes(search) || cells[2].textContent.toLowerCase().includes(search);
        row.style.display = (matchBranch && matchSearch) ? '' : 'none';
    });
}
</script>
