<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::requireRole([4]);
$db = \Database::getConnection();
$branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
$branchSales = $db->prepare("SELECT COALESCE(SUM(Total), 0) FROM `order` WHERE BranchID = ? AND DATE(CreatedAt) = CURDATE()");
$branchSales->execute([$branchId]);
$branchSalesToday = $branchSales->fetchColumn();
$lowStock = $db->prepare("SELECT COUNT(*) FROM inventory WHERE BranchID = ? AND AvailableQty <= ReorderLevel AND ReorderLevel > 0");
$lowStock->execute([$branchId]);
$lowStockItems = $lowStock->fetchColumn();
$pendingTransfers = $db->prepare("SELECT COUNT(*) FROM stock_transfer WHERE (SourceBranchID = ? OR DestinationBranchID = ?) AND Status IN ('Pending','Approved')");
$pendingTransfers->execute([$branchId, $branchId]);
$pendingTransferCount = $pendingTransfers->fetchColumn();
$pageTitle = 'Branch Manager Dashboard';
$basePath = '../';
$user = $_SESSION['user'];
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1200px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;"><?= $greeting ?>, <?= escape($user['Username']) ?>!</h1>
  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2rem;">Branch: <?= escape($user['BranchName'] ?? 'N/A') ?></p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:0.5rem;">Rs <?= number_format((float)$branchSalesToday, 2) ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Branch Sales Today</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--warning);margin-bottom:0.5rem;"><?= (int)$lowStockItems ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Low Stock Items</p>
    </div>
    <div class="card" style="text-align:center;padding:2rem 1.5rem;">
      <div style="font-size:2rem;font-weight:700;color:var(--secondary);margin-bottom:0.5rem;"><?= (int)$pendingTransferCount ?></div>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Pending Transfers</p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
