<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::requireRole([3]);
$db = \Database::getConnection();
$todaySales = $db->query("SELECT COALESCE(SUM(Total), 0) FROM `order` WHERE DATE(CreatedAt) = CURDATE()")->fetchColumn();
$todayOrders = $db->query("SELECT COUNT(*) FROM `order` WHERE DATE(CreatedAt) = CURDATE()")->fetchColumn();
$todayRefunds = $db->query("SELECT COALESCE(SUM(TotalRefund), 0) FROM product_return WHERE DATE(RequestedAt) = CURDATE() AND Status IN ('Approved', 'Refunded')")->fetchColumn();
$todayRefundCount = $db->query("SELECT COUNT(*) FROM product_return WHERE DATE(RequestedAt) = CURDATE() AND Status IN ('Approved', 'Refunded')")->fetchColumn();
$pageTitle = 'Cashier Dashboard';
$basePath = '../';
$user = $_SESSION['user'];
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1200px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;"><?= $greeting ?>, <?= escape($user['Username']) ?>!</h1>
  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2rem;">Point of Sale terminal is ready.</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;margin-bottom:2rem;">
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:0.5rem;">Rs <?= number_format((float)$todaySales, 2) ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Today's Sales</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--secondary);margin-bottom:0.5rem;"><?= (int)$todayOrders ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Orders Today</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--danger);margin-bottom:0.5rem;">Rs <?= number_format((float)$todayRefunds, 2) ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Refunds Today (<?= (int)$todayRefundCount ?>)</p>
    </div>
  </div>
  <div class="card" style="text-align:center;padding:3rem;">
    <div style="margin-bottom:1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    </div>
    <h2 style="font-size:1.1rem;font-weight:600;color:var(--text-primary);margin-bottom:0.5rem;">POS Terminal</h2>
    <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1.5rem;">Open the POS terminal to start processing sales.</p>
    <a href="../cashier/index.php" class="btn-primary">Launch POS</a>
  </div>
</div>
<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
