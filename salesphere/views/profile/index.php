<?php
$user = $_SESSION['user'] ?? [];
$avatarUrl = avatarUrl($user['Avatar'] ?? null, $user['UserID']);
?>
<div style="max-width:800px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.5rem;">Profile</h1>
  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2rem;">Manage your account information and settings</p>

  <!-- show user profile with avatar, name, email, role, branch -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
      <div style="position:relative;flex-shrink:0;">
        <?php if ($avatarUrl): ?>
          <img id="avatarImg" src="<?= $avatarUrl ?>" alt="Avatar" class="avatar-lg" data-original="<?= $avatarUrl ?>">
          <div id="avatarInitials" class="avatar-initials-lg" style="display:none"><?= getInitials($user['Username'] ?? '') ?></div>
        <?php else: ?>
          <div id="avatarInitials" class="avatar-initials-lg" style="display:flex"><?= getInitials($user['Username'] ?? '') ?></div>
          <img id="avatarImg" src="" alt="" style="display:none">
        <?php endif; ?>
      </div>
      <div style="flex:1;min-width:200px;">
        <h2 style="font-size:1.2rem;font-weight:700;color:var(--text-primary)"><?= escape($user['Username'] ?? '') ?></h2>
        <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.25rem;"><?= escape($user['Email'] ?? '') ?></p>
        <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
          <span class="role-badge"><?= escape($user['RoleName'] ?? '') ?></span>
          <?php if (!empty($user['BranchName'])): ?>
            <span style="font-size:0.75rem;color:var(--text-secondary);border:1px solid var(--border);padding:0.25rem 0.6rem;border-radius:6px;"><?= escape($user['BranchName']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div style="flex-shrink:0;">
        <form id="avatarForm" action="update_avatar.php" method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:0.5rem;">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <label for="avatarInput" class="btn-secondary" style="cursor:pointer;text-align:center;font-size:0.8rem;">
            Change Photo
          </label>
          <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none">
        </form>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h3 style="font-size:1rem;font-weight:600;color:var(--text-primary)">Personal Information</h3>
      <button id="editToggle" class="btn-secondary" style="font-size:0.8rem;padding:0.4rem 1rem;">Edit</button>
    </div>

    <div id="viewMode">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div>
          <p style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.25rem;">Name</p>
          <p style="color:var(--text-primary);font-size:0.9rem;"><?= escape($user['Username'] ?? '') ?></p>
        </div>
        <div>
          <p style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.25rem;">Email</p>
          <p style="color:var(--text-primary);font-size:0.9rem;"><?= escape($user['Email'] ?? '') ?></p>
        </div>
        <div>
          <p style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.25rem;">Mobile</p>
          <p style="color:var(--text-primary);font-size:0.9rem;"><?= escape($user['Tele'] ?? 'Not set') ?></p>
        </div>
        <div>
          <p style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.25rem;">User ID</p>
          <p style="color:var(--text-primary);font-size:0.9rem;">#<?= $user['UserID'] ?? '' ?></p>
        </div>
      </div>
    </div>

    <div id="editMode" class="hidden">
      <form id="infoForm" action="update_info.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">
          <div>
            <label for="edit_name" style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.4rem;">Name</label>
            <input type="text" id="edit_name" name="name" class="input-field" value="<?= escape($user['Username'] ?? '') ?>" required>
          </div>
          <div>
            <label for="edit_email" style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.4rem;">Email</label>
            <input type="email" id="edit_email" name="email" class="input-field" value="<?= escape($user['Email'] ?? '') ?>" required>
          </div>
          <div>
            <label for="edit_tele" style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.4rem;">Mobile</label>
            <input type="text" id="edit_tele" name="tele" class="input-field" value="<?= escape($user['Tele'] ?? '') ?>" placeholder="+60 xx-xxxxxxx">
          </div>
        </div>
        <div style="display:flex;gap:0.75rem;">
          <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save Changes</button>
          <button type="button" id="cancelEdit" class="btn-secondary" style="font-size:0.85rem;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- form to change password with current, new, confirm fields -->
  <div class="card">
    <h3 style="font-size:1rem;font-weight:600;color:var(--text-primary);margin-bottom:1.25rem;">Change Password</h3>
    <form id="passwordForm" action="change_password.php" method="POST" style="max-width:400px;">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div style="margin-bottom:1rem;">
        <label for="current_password" style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.4rem;">Current Password</label>
        <input type="password" id="current_password" name="current_password" class="input-field" placeholder="Enter current password" required>
      </div>
      <div style="margin-bottom:1rem;">
        <label for="new_password" style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.4rem;">New Password</label>
        <input type="password" id="new_password" name="new_password" class="input-field" placeholder="Min. 6 characters" required minlength="6">
      </div>
      <div style="margin-bottom:1.25rem;">
        <label for="confirm_password" style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.4rem;">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" class="input-field" placeholder="Re-enter new password" required minlength="6">
      </div>
      <button type="submit" class="btn-primary" style="font-size:0.85rem;">Change Password</button>
    </form>
  </div>
</div>
