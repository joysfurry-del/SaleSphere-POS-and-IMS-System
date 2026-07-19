<?php
$settings = $settings ?? [];
$csrf = csrf_token();
?>
<div style="max-width:800px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>System Settings</h2>
      <button class="btn-primary" onclick="openModal('settingModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Setting
      </button>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:700px;">
      <thead>
        <tr>
          <th style="width:70px">#</th>
          <th>Setting Name</th>
          <th>Value</th>
          <th>Modified By</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show all system settings with name, value, modified by -->
        <?php foreach ($settings as $s): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;"><?= $s['SettingNumber'] ?></td>
            <td><strong><?= escape($s['SettingName']) ?></strong></td>
            <td style="color:var(--text-secondary);max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= escape($s['SettingValue']) ?></td>
            <td style="color:var(--text-secondary);font-size:0.8rem;white-space:nowrap;"><?= escape($s['Username'] ?? '-') ?></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="editSetting(<?= $s['SettingNumber'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $s['SettingNumber'] ?>" data-field="setting_id" data-csrf="<?= $csrf ?>" data-action="settings.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($settings)): ?>
          <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-secondary)">No settings found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="settingModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:480px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('settingModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="settingModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Setting</h3>
    <form class="ajax-form" action="settings.php" method="POST" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="settingAction" value="create">
      <input type="hidden" name="setting_id" id="settingIdField" value="0">
      <div class="form-group">
        <label for="s_name">Setting Name</label>
        <input type="text" id="s_name" name="setting_name" class="input-field" required>
      </div>
      <div class="form-group">
        <label for="s_value">Value</label>
        <textarea id="s_value" name="setting_value" class="input-field" rows="3" style="resize:vertical" required></textarea>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('settingModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var settingsData = <?= json_encode(array_map(function($s) {
    return ['SettingNumber' => $s['SettingNumber'], 'SettingName' => $s['SettingName'], 'SettingValue' => $s['SettingValue']];
}, $settings)) ?>;

// fill setting form with data for editing
function editSetting(id) {
    var s = settingsData.find(function(x) { return x.SettingNumber == id; });
    if (!s) return;
    document.getElementById('settingAction').value = 'update';
    document.getElementById('settingIdField').value = s.SettingNumber;
    document.getElementById('s_name').value = s.SettingName;
    document.getElementById('s_value').value = s.SettingValue;
    document.getElementById('settingModalTitle').textContent = 'Edit Setting';
    openModal('settingModal');
}

// reset modal to add mode when closed
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('settingModal');
    if (el) {
        var obs = new MutationObserver(function() {
            if (el.classList.contains('hidden')) {
                document.getElementById('settingAction').value = 'create';
                document.getElementById('settingIdField').value = '0';
                document.getElementById('s_name').value = '';
                document.getElementById('s_value').value = '';
                document.getElementById('settingModalTitle').textContent = 'Add Setting';
            }
        });
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>
