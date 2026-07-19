<?php
$branches = $branches ?? [];
$csrf = csrf_token();
?>
<div style="max-width:800px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Branch Management</h2>
      <button class="btn-primary" onclick="openModal('branchModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Branch
      </button>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:600px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Branch Name</th>
          <th>Location</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show branches with name, location, edit/delete -->
        <?php foreach ($branches as $b): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $b['BranchID'] ?></td>
            <td><strong><?= escape($b['BranchName']) ?></strong></td>
            <td style="color:var(--text-secondary)"><?= escape($b['Location'] ?? '-') ?></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="editBranch(<?= $b['BranchID'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $b['BranchID'] ?>" data-field="branch_id" data-csrf="<?= $csrf ?>" data-action="branches.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($branches)): ?>
          <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-secondary)">No branches found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="branchModal" style="position:fixed;inset:0;z-index:100;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:440px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('branchModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="branchModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Branch</h3>
    <form id="branchForm" class="ajax-form" action="branches.php" method="POST" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="branchAction" value="create">
      <input type="hidden" name="branch_id" id="branchIdField" value="0">
      <div class="form-group">
        <label for="b_name">Branch Name</label>
        <input type="text" id="b_name" name="name" class="input-field" required>
      </div>
      <div class="form-group">
        <label for="b_location">Location</label>
        <input type="text" id="b_location" name="location" class="input-field" placeholder="e.g. 123 Main Street">
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('branchModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var branchData = <?= json_encode($branches) ?>;

// fill branch form with data for editing
function editBranch(id) {
    var d = branchData.find(function(x) { return x.BranchID == id; });
    if (!d) return;
    document.getElementById('branchAction').value = 'update';
    document.getElementById('branchIdField').value = d.BranchID;
    document.getElementById('b_name').value = d.BranchName;
    document.getElementById('b_location').value = d.Location || '';
    document.getElementById('branchModalTitle').textContent = 'Edit Branch';
    openModal('branchModal');
}

// reset modal to add mode when closed
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('branchModal');
    if (el) {
        var obs = new MutationObserver(function() {
            if (el.classList.contains('hidden')) {
                document.getElementById('branchForm').reset();
                document.getElementById('branchAction').value = 'create';
                document.getElementById('branchIdField').value = '0';
                document.getElementById('branchModalTitle').textContent = 'Add Branch';
            }
        });
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>
