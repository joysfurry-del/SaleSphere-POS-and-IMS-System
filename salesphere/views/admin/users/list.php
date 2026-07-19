<?php
$users = $users ?? [];
$roles = $roles ?? [];
$branches = $branches ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>User Management</h2>
      <button class="btn-primary" onclick="openModal('userModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add User
      </button>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:900px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Username</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Tele</th>
          <th>Role</th>
          <th>Branch</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- list all users with role, branch, edit/delete buttons -->
        <?php foreach ($users as $u): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $u['UserID'] ?></td>
            <td><strong><?= escape($u['Username']) ?></strong></td>
            <td style="color:var(--text-secondary)"><?= escape($u['FullName'] ?? $u['Username']) ?></td>
            <td style="color:var(--text-secondary)"><?= escape($u['Email']) ?></td>
            <td style="color:var(--text-secondary);white-space:nowrap;"><?= escape($u['Tele'] ?? '-') ?></td>
            <td><span class="role-badge" style="font-size:0.7rem;white-space:nowrap;"><?= escape($u['RoleName']) ?></span></td>
            <td style="color:var(--text-secondary)"><?= escape($u['BranchName'] ?? '-') ?></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="editUser(<?= $u['UserID'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $u['UserID'] ?>" data-field="user_id" data-csrf="<?= $csrf ?>" data-action="users.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
          <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-secondary)">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="userModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:520px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('userModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="userModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add User</h3>
    <form id="userForm" class="ajax-form" action="users.php" method="POST" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="userAction" value="create">
      <input type="hidden" name="user_id" id="userIdField" value="0">
      <div class="form-grid">
        <div class="form-group">
          <label for="u_username">Username</label>
          <input type="text" id="u_username" name="username" class="input-field" required>
        </div>
        <div class="form-group">
          <label for="u_fullname">Full Name</label>
          <input type="text" id="u_fullname" name="full_name" class="input-field">
        </div>
        <div class="form-group">
          <label for="u_email">Email</label>
          <input type="email" id="u_email" name="email" class="input-field" required>
        </div>
        <div class="form-group">
          <label for="u_password">Password <span id="pwHint" style="font-weight:400;color:var(--text-secondary)"></span></label>
          <input type="password" id="u_password" name="password" class="input-field" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
          <label for="u_tele">Mobile</label>
          <input type="text" id="u_tele" name="tele" class="input-field" placeholder="+60 xx-xxxxxxx">
        </div>
        <div class="form-group">
          <label for="u_role">Role</label>
          <select id="u_role" name="role_id" class="select-field" required>
            <option value="">Select role...</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['RoleID'] ?>"><?= escape($r['RoleName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="u_branch">Branch</label>
          <select id="u_branch" name="branch_id" class="select-field">
            <option value="">No branch</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['BranchID'] ?>"><?= escape($b['BranchName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('userModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var usersData = <?= json_encode(array_map(function($u) {
    return ['UserID' => $u['UserID'], 'Username' => $u['Username'], 'FullName' => ($u['FullName'] ?? $u['Username']), 'Email' => $u['Email'], 'Tele' => $u['Tele'], 'RoleID' => $u['RoleID'], 'BranchID' => $u['BranchID']];
}, $users)) ?>;

// fill edit form with user data and open modal
function editUser(id) {
    var u = usersData.find(function(x) { return x.UserID == id; });
    if (!u) return;
    document.getElementById('userAction').value = 'update';
    document.getElementById('userIdField').value = u.UserID;
    document.getElementById('u_username').value = u.Username;
    document.getElementById('u_fullname').value = u.FullName || '';
    document.getElementById('u_email').value = u.Email;
    document.getElementById('u_tele').value = u.Tele || '';
    document.getElementById('u_role').value = u.RoleID;
    document.getElementById('u_branch').value = u.BranchID || '';
    document.getElementById('u_password').required = false;
    document.getElementById('u_password').placeholder = 'Leave blank to keep current';
    document.getElementById('pwHint').textContent = '(leave blank to keep)';
    document.getElementById('userModalTitle').textContent = 'Edit User';
    openModal('userModal');
}

// reset modal to add mode when closed
document.addEventListener('DOMContentLoaded', function() {
    var resetUserModal = document.getElementById('userModal');
    if (resetUserModal) {
        var obs = new MutationObserver(function() {
            if (resetUserModal.classList.contains('hidden')) {
                document.getElementById('userForm').reset();
                document.getElementById('userAction').value = 'create';
                document.getElementById('userIdField').value = '0';
                document.getElementById('u_password').required = true;
                document.getElementById('u_password').placeholder = 'Min. 6 characters';
                document.getElementById('pwHint').textContent = '';
                document.getElementById('userModalTitle').textContent = 'Add User';
            }
        });
        obs.observe(resetUserModal, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>
