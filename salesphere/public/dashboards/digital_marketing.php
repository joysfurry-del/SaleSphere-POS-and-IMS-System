<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::requireRole([6]);
$db = \Database::getConnection();
$activePromos = $db->query("SELECT COUNT(*) FROM promotion WHERE IsActive = 1 AND (EndDate IS NULL OR EndDate >= CURDATE())")->fetchColumn();
$couponsUsed = $db->query("SELECT COUNT(*) FROM `order` WHERE CouponCode IS NOT NULL AND CouponCode != '' AND MONTH(CreatedAt) = MONTH(CURDATE()) AND YEAR(CreatedAt) = YEAR(CURDATE())")->fetchColumn();
$pageTitle = 'Marketing Dashboard';
$basePath = '../';
$user = $_SESSION['user'];
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1200px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;"><?= $greeting ?>, <?= escape($user['Username']) ?>!</h1>
  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2rem;">Promotions and campaigns.</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:0.5rem;"><?= (int)$activePromos ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Active Campaigns</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--secondary);margin-bottom:0.5rem;"><?= (int)$couponsUsed ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Coupons Redeemed</p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
