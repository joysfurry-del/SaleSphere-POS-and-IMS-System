<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/pdf_reports.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireRole([1, 3, 4]);

$db = \Database::getConnection();
$userId = (int)($_SESSION['user']['UserID'] ?? 0);
$branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
$userRole = (int)($_SESSION['user']['RoleID'] ?? 0);
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');
$daterange = 'custom';
if ($from === date('Y-m-d') && $to === date('Y-m-d')) $daterange = 'today';
elseif ($from === date('Y-m-d', strtotime('-7 days')) && $to === date('Y-m-d')) $daterange = 'week';
elseif ($from === date('Y-m-01') && $to === date('Y-m-d')) $daterange = 'month';

// Get orders for this branch (admin sees all, others see their branch)
if ($userRole === 1) {
    $stmt = $db->prepare(
        "SELECT o.*, u.Username AS CashierName, br.BranchName
         FROM `order` o
         JOIN user u ON o.CashierID = u.UserID
         JOIN branch br ON o.BranchID = br.BranchID
         WHERE DATE(o.CreatedAt) BETWEEN ? AND ?
         ORDER BY o.CreatedAt DESC"
    );
    $stmt->execute([$from, $to]);
} else {
    $stmt = $db->prepare(
        "SELECT o.*, u.Username AS CashierName, br.BranchName
         FROM `order` o
         JOIN user u ON o.CashierID = u.UserID
         JOIN branch br ON o.BranchID = br.BranchID
         WHERE o.BranchID = ? AND DATE(o.CreatedAt) BETWEEN ? AND ?
         ORDER BY o.CreatedAt DESC"
    );
    $stmt->execute([$branchId, $from, $to]);
}
$orders = $stmt->fetchAll();

// Get refunds for this period (only approved/given refunds affect revenue)
if ($userRole === 1) {
    $refStmt = $db->prepare(
        "SELECT pr.*, br.BranchName
         FROM product_return pr
         JOIN branch br ON pr.BranchID = br.BranchID
         WHERE pr.Status IN ('Approved', 'Refunded')
           AND DATE(pr.RequestedAt) BETWEEN ? AND ?
         ORDER BY pr.RequestedAt DESC"
    );
    $refStmt->execute([$from, $to]);
} else {
    $refStmt = $db->prepare(
        "SELECT pr.*, br.BranchName
         FROM product_return pr
         JOIN branch br ON pr.BranchID = br.BranchID
         WHERE pr.BranchID = ?
           AND pr.Status IN ('Approved', 'Refunded')
           AND DATE(pr.RequestedAt) BETWEEN ? AND ?
         ORDER BY pr.RequestedAt DESC"
    );
    $refStmt->execute([$branchId, $from, $to]);
}
$refunds = $refStmt->fetchAll();
$totalRefunds = 0;
foreach ($refunds as $r) {
    $totalRefunds += (float)($r['TotalRefund'] ?? 0);
}

// Aggregates
$totalSales = 0;
$totalOrders = count($orders);
$totalItems = 0;
$totalTax = 0;
$totalPaid = 0;
$totalChange = 0;
$methodBreakdown = [];
$orderItemCache = [];

foreach ($orders as $i => $o) {
    $totalSales += (float)$o['Total'];
    $totalTax += (float)$o['TaxAmount'];
    $totalPaid += (float)$o['AmountPaid'];
    $totalChange += (float)$o['Change'];
    $method = $o['PaymentMethod'] ?? 'Cash';
    if (!isset($methodBreakdown[$method])) $methodBreakdown[$method] = 0;
    $methodBreakdown[$method] += (float)$o['Total'];
    $items = \App\Models\OrderManager::getItems($o['OrderID']);
    $itemQty = $items ? array_sum(array_column($items, 'Quantity')) : 0;
    $totalItems += $itemQty;
    $orderItemCache[$o['OrderID']] = $itemQty;
    $o['_itemCount'] = $itemQty;
    $orders[$i] = $o;
}

// PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $filename = 'shift_report_' . date('Ymd') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo generateShiftReportPDF($orders, $from, $to, $totalSales, $totalOrders, $totalItems, $totalTax, $totalPaid, $totalChange, $methodBreakdown, $refunds, $totalRefunds);
    exit;
}

$pageTitle = 'My Shift Report';
$basePath = '../';
$user = $_SESSION['user'];
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1000px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>My Shift Report</h2>
      <div style="display:flex;gap:0.5rem;">
        <a href="?from=<?= $from ?>&to=<?= $to ?>&export=pdf" class="btn-primary-sm" style="font-size:0.8rem;padding:0.4rem 1rem;text-decoration:none;">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Download PDF
        </a>
        <button class="btn-primary-sm" onclick="window.print()" style="font-size:0.8rem;padding:0.4rem 1rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><polyline points="6 9 12 15 18 9"/></svg>
          Print
        </button>
      </div>
    </div>

    <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap;align-items:end;">
      <div>
        <label style="font-size:0.75rem;color:var(--text-secondary);">From</label>
        <input type="date" id="reportFrom" class="input-field" value="<?= $from ?>" style="width:150px;">
      </div>
      <div>
        <label style="font-size:0.75rem;color:var(--text-secondary);">To</label>
        <input type="date" id="reportTo" class="input-field" value="<?= $to ?>" style="width:150px;">
      </div>
      <button class="btn-primary" onclick="loadReport()" style="font-size:0.8rem;">Generate</button>
      <div style="display:flex;gap:0.4rem;margin-left:auto;">
        <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="btn-secondary-sm <?= $daterange === 'today' ? 'active-filter' : '' ?>" style="font-size:0.75rem;text-decoration:none;">Today</a>
        <a href="?from=<?= date('Y-m-d', strtotime('-7 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn-secondary-sm <?= $daterange === 'week' ? 'active-filter' : '' ?>" style="font-size:0.75rem;text-decoration:none;">7 Days</a>
        <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn-secondary-sm <?= $daterange === 'month' ? 'active-filter' : '' ?>" style="font-size:0.75rem;text-decoration:none;">This Month</a>
      </div>
    </div>

    <?php $netSales = $totalSales - $totalRefunds; ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:0.75rem;margin-bottom:1.5rem;">
      <div class="card" style="text-align:center;padding:1rem 0.75rem;">
        <div style="font-size:1.6rem;font-weight:700;color:var(--primary);margin-bottom:0.25rem;"><?= $totalOrders ?></div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Total Orders</p>
      </div>
      <div class="card" style="text-align:center;padding:1rem 0.75rem;">
        <div style="font-size:1.6rem;font-weight:700;color:var(--success);margin-bottom:0.25rem;"><?= $totalItems ?></div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Items Sold</p>
      </div>
      <div class="card" style="text-align:center;padding:1rem 0.75rem;">
        <div style="font-size:1.3rem;font-weight:700;color:var(--secondary);margin-bottom:0.25rem;">Rs <?= number_format($totalSales, 2) ?></div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Gross Sales</p>
      </div>
      <div class="card" style="text-align:center;padding:1rem 0.75rem;">
        <div style="font-size:1.1rem;font-weight:700;color:var(--warning);margin-bottom:0.25rem;">Rs <?= number_format($totalTax, 2) ?></div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Total Tax</p>
      </div>
      <div class="card" style="text-align:center;padding:1rem 0.75rem;">
        <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;">Rs <?= number_format($totalPaid, 2) ?></div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Total Paid</p>
      </div>
      <div class="card" style="text-align:center;padding:1rem 0.75rem;">
        <div style="font-size:1.1rem;font-weight:700;color:var(--text-secondary);margin-bottom:0.25rem;">Rs <?= number_format($totalChange, 2) ?></div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Total Change</p>
      </div>
      <?php if ($totalRefunds > 0): ?>
      <div class="card" style="text-align:center;padding:1rem 0.75rem;border-color:#ef4444;">
        <div style="font-size:1.1rem;font-weight:700;color:#ef4444;margin-bottom:0.25rem;">(Rs <?= number_format($totalRefunds, 2) ?>)</div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Total Refunds</p>
      </div>
      <div class="card" style="text-align:center;padding:1rem 0.75rem;border-color:#22c55e;">
        <div style="font-size:1.3rem;font-weight:700;color:#22c55e;margin-bottom:0.25rem;">Rs <?= number_format($netSales, 2) ?></div>
        <p style="font-size:0.75rem;color:var(--text-secondary);">Net Sales</p>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($methodBreakdown)): ?>
    <div style="margin-bottom:1.5rem;">
      <h3 style="font-size:0.9rem;font-weight:600;margin-bottom:0.5rem;">Payment Method Breakdown</h3>
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <?php foreach ($methodBreakdown as $method => $amt): ?>
          <div class="card" style="padding:0.75rem 1rem;text-align:center;min-width:120px;">
            <div style="font-size:0.75rem;color:var(--text-secondary);"><?= escape($method) ?></div>
            <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">Rs <?= number_format($amt, 2) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <table class="data-table" style="font-size:0.78rem;">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Date</th>
          <th>Cashier</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Method</th>
          <th style="text-align:right">Subtotal</th>
          <th style="text-align:right">Tax</th>
          <th style="text-align:right">Total</th>
          <th style="text-align:right">Paid</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-secondary);">No orders found for this period.</td></tr>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
            <?php
              $subtotal = (float)$o['Total'] - (float)$o['TaxAmount'];
            ?>
            <tr>
              <td><strong>#<?= $o['OrderID'] ?></strong></td>
              <td style="font-size:0.75rem;"><?= date('d/m/Y H:i', strtotime($o['CreatedAt'])) ?></td>
              <td><?= escape($o['CashierName'] ?? '-') ?></td>
              <td><?= escape($o['CustomerName'] ?? 'Walk-in') ?></td>
              <td style="text-align:center"><?= $orderItemCache[$o['OrderID']] ?? 0 ?></td>
              <td><?= escape($o['PaymentMethod']) ?></td>
              <td style="text-align:right">Rs <?= number_format($subtotal, 2) ?></td>
              <td style="text-align:right">Rs <?= number_format((float)$o['TaxAmount'], 2) ?></td>
              <td style="text-align:right;font-weight:600;">Rs <?= number_format((float)$o['Total'], 2) ?></td>
              <td style="text-align:right">Rs <?= number_format((float)$o['AmountPaid'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($orders)): ?>
      <tfoot>
        <tr style="font-weight:600;">
          <td colspan="6" style="text-align:right">Totals:</td>
          <td style="text-align:right">Rs <?= number_format($totalSales - $totalTax, 2) ?></td>
          <td style="text-align:right">Rs <?= number_format($totalTax, 2) ?></td>
          <td style="text-align:right">Rs <?= number_format($totalSales, 2) ?></td>
          <td style="text-align:right">Rs <?= number_format($totalPaid, 2) ?></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>

    <?php if (!empty($refunds)): ?>
    <h3 style="font-size:0.9rem;font-weight:600;margin:1.5rem 0 0.5rem;">Refunds / Returns</h3>
    <table class="data-table" style="font-size:0.78rem;">
      <thead>
        <tr>
          <th>Return #</th>
          <th>Date</th>
          <th>Branch</th>
          <th>Reason</th>
          <th style="text-align:right">Refund Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($refunds as $r): ?>
          <tr>
            <td><strong>#<?= $r['ReturnID'] ?></strong></td>
            <td style="font-size:0.75rem;"><?= date('d/m/Y H:i', strtotime($r['RequestedAt'] ?? $r['CreatedAt'])) ?></td>
            <td><?= escape($r['BranchName']) ?></td>
            <td><?= escape($r['Reason'] ?? '-') ?></td>
            <td style="text-align:right;color:#ef4444;">(Rs <?= number_format((float)($r['TotalRefund'] ?? 0), 2) ?>)</td>
            <td><?= escape($r['Status'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="font-weight:600;">
          <td colspan="4" style="text-align:right">Total Refunds:</td>
          <td style="text-align:right;color:#ef4444;">(Rs <?= number_format($totalRefunds, 2) ?>)</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    <?php endif; ?>
  </div>
</div>

<style>
  @media print {
    .sidebar, .sidebar-overlay, .navbar, .btn-primary, .btn-secondary-sm, .card-header div { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
  }
  .active-filter { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; }
</style>

<script>
function loadReport() {
    var from = document.getElementById('reportFrom').value;
    var to = document.getElementById('reportTo').value;
    window.location.href = 'report.php?from=' + from + '&to=' + to;
}
function quickRange(range) {
    var from, to = '<?= date('Y-m-d') ?>';
    if (range === 'today') from = '<?= date('Y-m-d') ?>';
    else if (range === 'week') from = '<?= date('Y-m-d', strtotime('-7 days')) ?>';
    else if (range === 'month') from = '<?= date('Y-m-01') ?>';
    window.location.href = 'report.php?from=' + from + '&to=' + to;
}
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
