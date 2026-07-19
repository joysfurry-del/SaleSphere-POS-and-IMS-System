<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireRole([2]);
$db = \Database::getConnection();
$monthlyRevenue = $db->query(
    "SELECT (SELECT COALESCE(SUM(Total), 0) FROM `order` WHERE MONTH(CreatedAt) = MONTH(CURDATE()) AND YEAR(CreatedAt) = YEAR(CURDATE())) +
            (SELECT COALESCE(SUM(Total), 0) FROM online_order WHERE MONTH(CreatedAt) = MONTH(CURDATE()) AND YEAR(CreatedAt) = YEAR(CURDATE()) AND Status != 'Cancelled') -
            (SELECT COALESCE(SUM(TotalRefund), 0) FROM product_return WHERE Status IN ('Approved', 'Refunded') AND MONTH(RequestedAt) = MONTH(CURDATE()) AND YEAR(RequestedAt) = YEAR(CURDATE()))"
)->fetchColumn();
$pendingInvoices = $db->query("SELECT COUNT(*) FROM bill WHERE Status IN ('Draft','Partial')")->fetchColumn();
$pageTitle = 'Accountant Dashboard';
$basePath = '../';
$user = $_SESSION['user'];
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1200px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;"><?= $greeting ?>, <?= escape($user['Username']) ?>!</h1>
  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2rem;">Financial reports and transactions.</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:0.5rem;">Rs <?= number_format((float)$monthlyRevenue, 2) ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Monthly Revenue</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--secondary);margin-bottom:0.5rem;"><?= (int)$pendingInvoices ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Pending Invoices</p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
