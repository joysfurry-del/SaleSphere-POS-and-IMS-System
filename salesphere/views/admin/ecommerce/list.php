<?php
$products = $products ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1100px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>E-Commerce Catalog</h2>
      <p style="font-size:0.8rem;color:var(--text-secondary);">Toggle which products appear on the public E-Commerce site.</p>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:50px">ID</th>
          <th>Product</th>
          <th>Category</th>
          <th>Price (Rs)</th>
          <th style="width:120px">E-Commerce</th>
          <th style="width:90px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- show products with e-commerce toggle switch -->
        <?php foreach ($products as $p): ?>
          <tr>
            <td style="color:var(--text-secondary)">#<?= $p['ProductID'] ?></td>
            <td><strong><?= escape($p['ProductName']) ?></strong></td>
            <td><span style="font-size:0.78rem;color:var(--text-secondary);border:1px solid var(--border);padding:0.15rem 0.5rem;border-radius:4px;"><?= escape($p['CategoryName']) ?></span></td>
            <td style="font-weight:600">Rs <?= number_format($p['Price'], 2) ?></td>
            <td>
              <label class="toggle-switch">
                <input type="checkbox" value="<?= $p['ProductID'] ?>" data-action="ecommerce.php" data-csrf="<?= $csrf ?>" <?= !empty($p['IsEcommerce']) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
            </td>
            <td>
              <button class="btn-cyan" onclick="editProduct(<?= $p['ProductID'] ?>)">Edit</button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary)">No products found. Create products first.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
var ecomProducts = <?= json_encode(array_map(function($p) {
    return ['ProductID' => $p['ProductID'], 'ProductName' => $p['ProductName'], 'CategoryID' => $p['CategoryID'], 'Price' => $p['Price'], 'Description' => $p['Description']];
}, $products)) ?>;

function editProduct(id) {
    var p = ecomProducts.find(function(x) { return x.ProductID == id; });
    if (!p) {
        window.location.href = 'products.php';
        return;
    }
    window.location.href = 'products.php?edit=' + id;
}
</script>
