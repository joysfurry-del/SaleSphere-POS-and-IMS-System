<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireRole([1]);

$db = \Database::getConnection();

// Real metrics
$todayRevenue = $db->query(
    "SELECT (SELECT COALESCE(SUM(Total), 0) FROM `order` WHERE DATE(CreatedAt) = CURDATE()) +
            (SELECT COALESCE(SUM(Total), 0) FROM online_order WHERE DATE(CreatedAt) = CURDATE() AND Status != 'Cancelled') -
            (SELECT COALESCE(SUM(TotalRefund), 0) FROM product_return WHERE Status IN ('Approved', 'Refunded') AND DATE(RequestedAt) = CURDATE())"
)->fetchColumn();
$activeOrders = $db->query("SELECT COUNT(*) FROM online_order WHERE Status NOT IN ('Delivered','Cancelled')")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM product WHERE IsActive = 1 OR IsActive IS NULL")->fetchColumn();
$lowStockItems = $db->query("SELECT COUNT(*) FROM inventory WHERE AvailableQty <= ReorderLevel AND ReorderLevel > 0")->fetchColumn();

$pageTitle = 'Admin Dashboard';
$basePath = '../';
$user = $_SESSION['user'];
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');

require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1200px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;"><?= $greeting ?>, <?= escape($user['Username']) ?>!</h1>
  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2rem;">Welcome to the Admin dashboard. You have full system access.</p>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:0.5rem;">Rs <?= number_format((float)$todayRevenue, 2) ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Total Revenue Today</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--secondary);margin-bottom:0.5rem;"><?= (int)$activeOrders ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Active Orders</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--success);margin-bottom:0.5rem;"><?= (int)$totalProducts ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Products</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--warning);margin-bottom:0.5rem;"><?= (int)$lowStockItems ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Low Stock Alerts</p>
    </div>
  </div>

  <div class="card" style="margin-top:2rem;">
    <div class="card-header">
      <h2>Recent Login Activity</h2>
      <a href="../admin/login_log.php" class="btn-primary-sm" style="text-decoration:none;font-size:0.75rem;">View All</a>
    </div>
    <?php
    $loginStmt = $db->query(
        "SELECT tl.*, u.Username, u.FullName
         FROM transaction_log tl
         LEFT JOIN user u ON tl.UserID = u.UserID
         WHERE tl.ActionDescription LIKE '%login%' OR tl.ActionDescription LIKE '%Login%'
         ORDER BY tl.CreatedAt DESC LIMIT 10"
    );
    $recentLogins = $loginStmt->fetchAll();
    ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>User</th>
          <th>Action</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentLogins as $log): ?>
          <?php $isSuccess = stripos($log['ActionDescription'], 'Successful') !== false; ?>
          <tr>
            <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($log['CreatedAt'])) ?></td>
            <td><strong><?= escape($log['FullName'] ?? $log['Username'] ?? 'Unknown') ?></strong></td>
            <td><?= escape($log['ActionDescription']) ?></td>
            <td>
              <span class="role-badge" style="background:<?= $isSuccess ? 'var(--success)' : 'var(--danger)' ?>15;color:<?= $isSuccess ? 'var(--success)' : 'var(--danger)' ?>">
                <?= $isSuccess ? 'Success' : 'Failed' ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($recentLogins)): ?>
          <tr><td colspan="4" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">No login activity recorded yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
require __DIR__ . '/../../views/layouts/footer.php';
