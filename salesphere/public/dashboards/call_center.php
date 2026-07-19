<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::requireRole([7]);
$db = \Database::getConnection();
$pendingOrders = $db->query("SELECT COUNT(*) FROM online_order WHERE Status IN ('Pending','Confirmed','Processing')")->fetchColumn();
$openInquiries = $db->query("SELECT COUNT(*) FROM customer_inquiry WHERE Status IN ('Pending','Open')")->fetchColumn();
$pageTitle = 'Call Center Dashboard';
$basePath = '../';
$user = $_SESSION['user'];
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1200px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;"><?= $greeting ?>, <?= escape($user['Username']) ?>!</h1>
  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2rem;">Customer support and order management.</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:0.5rem;"><?= (int)$pendingOrders ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Pending Orders</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--warning);margin-bottom:0.5rem;"><?= (int)$openInquiries ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Open Tickets</p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
