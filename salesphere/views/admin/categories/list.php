<?php
$categories = $categories ?? [];
$csrf = csrf_token();
?>
<div style="max-width:800px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Category Management</h2>
      <button class="btn-primary" onclick="openModal('catModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Category
      </button>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:500px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Category Name</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show categories with edit and delete buttons -->
        <?php foreach ($categories as $c): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $c['CategoryID'] ?></td>
            <td><strong><?= escape($c['CategoryName']) ?></strong></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="editCat(<?= $c['CategoryID'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $c['CategoryID'] ?>" data-field="category_id" data-csrf="<?= $csrf ?>" data-action="categories.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
          <tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--text-secondary)">No categories found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="catModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:420px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('catModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="catModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Category</h3>
    <form class="ajax-form" action="categories.php" method="POST" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="catAction" value="create">
      <input type="hidden" name="category_id" id="catIdField" value="0">
      <div class="form-group">
        <label for="cat_name">Category Name</label>
        <input type="text" id="cat_name" name="name" class="input-field" required>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('catModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var catsData = <?= json_encode(array_map(function($c) {
    return ['CategoryID' => $c['CategoryID'], 'CategoryName' => $c['CategoryName']];
}, $categories)) ?>;

// fill category form with data for editing
function editCat(id) {
    var c = catsData.find(function(x) { return x.CategoryID == id; });
    if (!c) return;
    document.getElementById('catAction').value = 'update';
    document.getElementById('catIdField').value = c.CategoryID;
    document.getElementById('cat_name').value = c.CategoryName;
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    openModal('catModal');
}

// reset modal to add mode when closed
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('catModal');
    if (el) {
        var obs = new MutationObserver(function() {
            if (el.classList.contains('hidden')) {
                document.getElementById('catAction').value = 'create';
                document.getElementById('catIdField').value = '0';
                document.getElementById('cat_name').value = '';
                document.getElementById('catModalTitle').textContent = 'Add Category';
            }
        });
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>
