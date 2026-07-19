<?php
$products = $products ?? [];
$categories = $categories ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Product Management</h2>
      <button class="btn-primary" onclick="openModal('productModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Product
      </button>
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:800px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Product Name</th>
          <th>Category</th>
          <th style="width:100px;text-align:right">Price (Rs)</th>
          <th>Description</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- list all products with category, price, edit/delete -->
        <?php foreach ($products as $p): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $p['ProductID'] ?></td>
            <td><strong><?= escape($p['ProductName']) ?></strong></td>
            <td><span style="font-size:0.78rem;color:var(--text-secondary);border:1px solid var(--border);padding:0.15rem 0.5rem;border-radius:4px;white-space:nowrap;"><?= escape($p['CategoryName']) ?></span></td>
            <td style="font-weight:600;text-align:right;white-space:nowrap;">Rs <?= number_format($p['Price'], 2) ?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-secondary)"><?= escape($p['Description'] ?? '-') ?></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="editProduct(<?= $p['ProductID'] ?>)">Edit</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $p['ProductID'] ?>" data-field="product_id" data-csrf="<?= $csrf ?>" data-action="products.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary)">No products found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="productModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:520px;margin:1rem;padding:2rem;">
    <button type="button" onclick="closeModal('productModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 id="productModalTitle" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Product</h3>
    <form id="productForm" class="ajax-form" action="products.php" method="POST" enctype="multipart/form-data" data-btn-text="Save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" id="productAction" value="create">
      <input type="hidden" name="product_id" id="productIdField" value="0">
      <div class="form-grid">
        <div class="form-group full">
          <label for="p_name">Product Name</label>
          <input type="text" id="p_name" name="product_name" class="input-field" required>
        </div>
        <div class="form-group">
          <label for="p_category">Category</label>
          <select id="p_category" name="category_id" class="select-field" required>
            <option value="">Select...</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['CategoryID'] ?>"><?= escape($c['CategoryName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="p_price">Price (Rs)</label>
          <input type="number" id="p_price" name="price" class="input-field" step="0.01" min="0.01" required>
        </div>
        <div class="form-group full">
          <label for="p_desc">Description</label>
          <textarea id="p_desc" name="description" class="input-field" rows="3" style="resize:vertical"></textarea>
        </div>
        <div class="form-group full">
          <label for="p_image">Product Image</label>
          <input type="file" id="p_image" name="product_image" class="input-field" accept="image/jpeg,image/png,image/gif,image/webp">
          <div id="imagePreview" style="margin-top:0.5rem;display:none;">
            <img id="previewImg" src="" alt="Preview" style="max-width:120px;max-height:120px;border:1px solid var(--border);border-radius:6px;">
            <button type="button" onclick="document.getElementById('p_image').value='';document.getElementById('imagePreview').style.display='none';" style="font-size:0.75rem;color:var(--danger);background:none;border:none;cursor:pointer;margin-left:0.5rem;">Remove</button>
          </div>
        </div>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Save</button>
        <button type="button" class="btn-secondary" onclick="closeModal('productModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var productsData = <?= json_encode(array_map(function($p) {
    return ['ProductID' => $p['ProductID'], 'ProductName' => $p['ProductName'], 'CategoryID' => $p['CategoryID'], 'Price' => $p['Price'], 'Description' => $p['Description'], 'ProductImage' => $p['ProductImage'] ?? ''];
}, $products)) ?>;

// fill product form with data; show image preview if exists
function editProduct(id) {
    var p = productsData.find(function(x) { return x.ProductID == id; });
    if (!p) return;
    document.getElementById('productAction').value = 'update';
    document.getElementById('productIdField').value = p.ProductID;
    document.getElementById('p_name').value = p.ProductName;
    document.getElementById('p_category').value = p.CategoryID;
    document.getElementById('p_price').value = p.Price;
    document.getElementById('p_desc').value = p.Description || '';
    document.getElementById('productModalTitle').textContent = 'Edit Product';
    var preview = document.getElementById('imagePreview');
    var img = document.getElementById('previewImg');
    if (p.ProductImage) {
        img.src = p.ProductImage;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
    openModal('productModal');
}

// show image preview when user selects a file
document.getElementById('p_image')?.addEventListener('change', function(e) {
    var preview = document.getElementById('imagePreview');
    var img = document.getElementById('previewImg');
    if (e.target.files && e.target.files[0]) {
        var reader = new FileReader();
        reader.onload = function(ev) { img.src = ev.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(e.target.files[0]);
    } else {
        preview.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('productModal');
    if (el) {
        var obs = new MutationObserver(function() {
            if (el.classList.contains('hidden')) {
                document.getElementById('productForm').reset();
                document.getElementById('productAction').value = 'create';
                document.getElementById('productIdField').value = '0';
                document.getElementById('productModalTitle').textContent = 'Add Product';
                document.getElementById('imagePreview').style.display = 'none';
            }
        });
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>
