<?php
$pending = $pending ?? [];
$all = $all ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1100px;margin:0 auto;">
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
      <h2>Pending Transfer Requests</h2>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px">ID</th>
          <th>Branch</th>
          <th>Product</th>
          <th style="text-align:right">Requested</th>
          <th>Date</th>
          <th style="width:180px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show pending transfers with approve/reject buttons -->
        <?php if (empty($pending)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary)">No pending transfer requests.</td></tr>
        <?php else: ?>
          <?php foreach ($pending as $t): ?>
            <tr>
              <td style="color:var(--text-secondary)">#<?= $t['TransferID'] ?></td>
              <td><strong><?= escape($t['BranchName']) ?></strong></td>
              <td><?= escape($t['ProductName']) ?></td>
              <td style="text-align:right;font-weight:700;"><?= (int)$t['RequestedQty'] ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary)"><?= date('d M Y, H:i', strtotime($t['RequestDate'])) ?></td>
              <td>
                <div style="display:flex;gap:0.4rem;">
                  <button class="btn-primary" style="font-size:0.75rem;padding:0.35rem 0.7rem;" onclick="approveTransfer(<?= $t['TransferID'] ?>)">Approve</button>
                  <button class="btn-danger-sm" style="font-size:0.75rem;padding:0.35rem 0.7rem;" onclick="rejectTransfer(<?= $t['TransferID'] ?>)">Reject</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header">
      <h2>All Transfer History</h2>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px">ID</th>
          <th>Branch</th>
          <th>Product</th>
          <th style="text-align:right">Qty</th>
          <th>Requested</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <!-- show all transfers with color-coded status -->
        <?php if (empty($all)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary)">No transfers yet.</td></tr>
        <?php else: ?>
          <?php foreach ($all as $t): ?>
            <tr>
              <td style="color:var(--text-secondary)">#<?= $t['TransferID'] ?></td>
              <td><?= escape($t['BranchName']) ?></td>
              <td><?= escape($t['ProductName']) ?></td>
              <td style="text-align:right;font-weight:600;"><?= (int)$t['RequestedQty'] ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary)"><?= date('d M Y', strtotime($t['RequestDate'])) ?></td>
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

<script>
// send ajax to approve/reject transfer request
function approveTransfer(id) {
    if (!confirm('Approve this transfer request? Inventory will be updated.')) return;
    var fd = new FormData();
    fd.append('csrf_token', '<?= $csrf ?>');
    fd.append('action', 'approve');
    fd.append('transfer_id', id);
    fetch('transfers.php', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) { showToast(d.success ? 'success' : 'error', d.message); if (d.success) setTimeout(function() { location.reload(); }, 700); })
      .catch(function() { showToast('error', 'Request failed.'); });
}

function rejectTransfer(id) {
    if (!confirm('Reject this transfer request?')) return;
    var fd = new FormData();
    fd.append('csrf_token', '<?= $csrf ?>');
    fd.append('action', 'reject');
    fd.append('transfer_id', id);
    fetch('transfers.php', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) { showToast(d.success ? 'success' : 'error', d.message); if (d.success) setTimeout(function() { location.reload(); }, 700); })
      .catch(function() { showToast('error', 'Request failed.'); });
}
</script>
