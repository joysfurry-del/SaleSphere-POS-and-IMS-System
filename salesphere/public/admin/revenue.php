<?php
// revenue tracking page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/pdf_reports.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireRole([1, 2, 4]);

$db = \Database::getConnection();
$view = $_GET['view'] ?? 'daily';
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$daterange = 'custom';
if ($from === date('Y-m-d', strtotime('-7 days')) && $to === date('Y-m-d')) $daterange = '7days';
elseif ($from === date('Y-m-d', strtotime('-30 days')) && $to === date('Y-m-d')) $daterange = '30days';
elseif ($from === date('Y-m-01') && $to === date('Y-m-d')) $daterange = 'month';

// Daily revenue
if ($view === 'daily') {
    $sql = "SELECT DATE(CreatedAt) AS Date,
                   COUNT(*) AS OrderCount,
                   SUM(Total) AS GrossRevenue,
                   SUM(TaxAmount) AS TaxAmount,
                   0 AS RefundAmount
            FROM `order`
            WHERE DATE(CreatedAt) BETWEEN ? AND ?
            GROUP BY DATE(CreatedAt)
            ORDER BY Date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$from, $to]);
    $dailyData = $stmt->fetchAll();

    // Add refunds per day
    $refSql = "SELECT DATE(RequestedAt) AS Date, SUM(TotalRefund) AS RefundAmount
               FROM product_return
               WHERE DATE(RequestedAt) BETWEEN ? AND ? AND Status IN ('Refunded','Approved')
               GROUP BY DATE(RequestedAt)";
    $refStmt = $db->prepare($refSql);
    $refStmt->execute([$from, $to]);
    $refunds = [];
    while ($r = $refStmt->fetch()) { $refunds[$r['Date']] = (float)$r['RefundAmount']; }

    foreach ($dailyData as &$d) {
        $d['RefundAmount'] = $refunds[$d['Date']] ?? 0;
        $d['NetRevenue'] = (float)$d['GrossRevenue'] - $d['RefundAmount'];
    }
    unset($d);

    $totalGross = array_sum(array_column($dailyData, 'GrossRevenue'));
    $totalRefunds = array_sum(array_column($dailyData, 'RefundAmount'));
    $totalNet = $totalGross - $totalRefunds;
}

// Monthly revenue
$monthlySql = "SELECT DATE_FORMAT(CreatedAt, '%Y-%m') AS Month,
                      COUNT(*) AS OrderCount,
                      SUM(Total) AS GrossRevenue,
                      SUM(TaxAmount) AS TaxAmount
               FROM `order`
               WHERE CreatedAt >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               GROUP BY DATE_FORMAT(CreatedAt, '%Y-%m')
               ORDER BY Month DESC";
$monthlyData = $db->query($monthlySql)->fetchAll();

$mRefSql = "SELECT DATE_FORMAT(RequestedAt, '%Y-%m') AS Month, SUM(TotalRefund) AS RefundAmount
            FROM product_return
            WHERE RequestedAt >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND Status IN ('Refunded','Approved')
            GROUP BY DATE_FORMAT(RequestedAt, '%Y-%m')";
$mRefStmt = $db->query($mRefSql);
$mRefunds = [];
while ($r = $mRefStmt->fetch()) { $mRefunds[$r['Month']] = (float)$r['RefundAmount']; }
foreach ($monthlyData as &$m) {
    $m['RefundAmount'] = $mRefunds[$m['Month']] ?? 0;
    $m['NetRevenue'] = (float)$m['GrossRevenue'] - $m['RefundAmount'];
}
unset($m);
$monthlyTotalGross = array_sum(array_column($monthlyData, 'GrossRevenue'));
$monthlyTotalRefunds = array_sum(array_column($monthlyData, 'RefundAmount'));
$monthlyTotalNet = $monthlyTotalGross - $monthlyTotalRefunds;

// Totals
$todayRevenue = $db->query("SELECT COALESCE(SUM(Total),0) FROM `order` WHERE DATE(CreatedAt)=CURDATE()")->fetchColumn();
$thisMonthRevenue = $db->query("SELECT COALESCE(SUM(Total),0) FROM `order` WHERE MONTH(CreatedAt)=MONTH(CURDATE()) AND YEAR(CreatedAt)=YEAR(CURDATE())")->fetchColumn();
$thisYearRevenue = $db->query("SELECT COALESCE(SUM(Total),0) FROM `order` WHERE YEAR(CreatedAt)=YEAR(CURDATE())")->fetchColumn();

// PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $rows = $view === 'daily' ? $dailyData : $monthlyData;
    $totalGrossVal = $view === 'daily' ? $totalGross : $monthlyTotalGross;
    $totalRefundsVal = $view === 'daily' ? $totalRefunds : $monthlyTotalRefunds;
    $totalNetVal = $view === 'daily' ? $totalNet : $monthlyTotalNet;
    $filename = 'revenue_tracking_' . date('Ymd') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo generateRevenueTrackingPDF($rows, $from, $to, $view, $totalGrossVal, $totalRefundsVal, $totalNetVal);
    exit;
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $view === 'daily' ? $dailyData : $monthlyData;
    $filename = 'revenue_' . ($view === 'daily' ? 'daily' : 'monthly') . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
    fputcsv($out, [$view === 'daily' ? 'Date' : 'Month', 'Orders', 'Gross Revenue (Rs)', 'Tax (Rs)', 'Refunds (Rs)', 'Net Revenue (Rs)']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['Date'] ?? $r['Month'] ?? '-',
            (int)$r['OrderCount'],
            number_format((float)$r['GrossRevenue'], 2),
            number_format((float)$r['TaxAmount'], 2),
            number_format((float)$r['RefundAmount'], 2),
            number_format((float)$r['NetRevenue'], 2),
        ]);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Revenue Tracking';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="max-width:1100px;margin:0 auto;">
  <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:1.5rem;">Revenue Tracking</h1>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="card" style="text-align:center;padding:1.25rem;">
      <div style="font-size:1.6rem;font-weight:700;color:var(--primary);">Rs <?= number_format((float)$todayRevenue, 2) ?></div>
      <p style="font-size:0.8rem;color:var(--text-secondary);">Today</p>
    </div>
    <div class="card" style="text-align:center;padding:1.25rem;">
      <div style="font-size:1.6rem;font-weight:700;color:var(--secondary);">Rs <?= number_format((float)$thisMonthRevenue, 2) ?></div>
      <p style="font-size:0.8rem;color:var(--text-secondary);">This Month</p>
    </div>
    <div class="card" style="text-align:center;padding:1.25rem;">
      <div style="font-size:1.6rem;font-weight:700;color:var(--success);">Rs <?= number_format((float)$thisYearRevenue, 2) ?></div>
      <p style="font-size:0.8rem;color:var(--text-secondary);">This Year</p>
    </div>
  </div>

  <div class="card" style="margin-bottom:1rem;">
    <div class="card-header">
      <h2><?= $view === 'daily' ? 'Daily Revenue' : 'Monthly Revenue' ?></h2>
      <div style="display:flex;gap:0.5rem;">
        <a href="?view=daily" class="btn-primary-sm <?= $view === 'daily' ? 'active-filter' : '' ?>" style="text-decoration:none;">Daily</a>
        <a href="?view=monthly" class="btn-primary-sm <?= $view === 'monthly' ? 'active-filter' : '' ?>" style="text-decoration:none;">Monthly</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-primary-sm" style="text-decoration:none;font-size:0.75rem;padding:0.35rem 0.75rem;background:var(--secondary);border-color:var(--secondary);">CSV</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" class="btn-primary-sm" style="text-decoration:none;font-size:0.75rem;padding:0.35rem 0.75rem;">PDF</a>
      </div>
    </div>

    <?php if ($view === 'daily'): ?>
    <div style="display:flex;gap:0.75rem;margin-bottom:1rem;flex-wrap:wrap;align-items:end;">
      <div>
        <label style="font-size:0.75rem;color:var(--text-secondary);">From</label>
        <input type="date" id="revFrom" class="input-field" value="<?= $from ?>" style="width:150px;">
      </div>
      <div>
        <label style="font-size:0.75rem;color:var(--text-secondary);">To</label>
        <input type="date" id="revTo" class="input-field" value="<?= $to ?>" style="width:150px;">
      </div>
      <button class="btn-primary" onclick="loadRevenue()" style="font-size:0.8rem;">Filter</button>
      <div style="display:flex;gap:0.4rem;margin-left:auto;">
        <a href="?view=daily&from=<?= date('Y-m-d', strtotime('-7 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn-secondary-sm <?= $daterange === '7days' ? 'active-filter' : '' ?>" style="font-size:0.75rem;text-decoration:none;">7 Days</a>
        <a href="?view=daily&from=<?= date('Y-m-d', strtotime('-30 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn-secondary-sm <?= $daterange === '30days' ? 'active-filter' : '' ?>" style="font-size:0.75rem;text-decoration:none;">30 Days</a>
        <a href="?view=daily&from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn-secondary-sm <?= $daterange === 'month' ? 'active-filter' : '' ?>" style="font-size:0.75rem;text-decoration:none;">This Month</a>
      </div>
    </div>
    <?php endif; ?>

    <table class="data-table">
      <thead>
        <tr>
          <th><?= $view === 'daily' ? 'Date' : 'Month' ?></th>
          <th style="text-align:center">Orders</th>
          <th style="text-align:right">Gross Revenue</th>
          <th style="text-align:right">Tax</th>
          <th style="text-align:right">Refunds</th>
          <th style="text-align:right">Net Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php $rows = $view === 'daily' ? $dailyData : $monthlyData; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary);">No data found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><strong><?= $r['Date'] ?? $r['Month'] ?? '-' ?></strong></td>
              <td style="text-align:center"><?= (int)$r['OrderCount'] ?></td>
              <td style="text-align:right">Rs <?= number_format((float)$r['GrossRevenue'], 2) ?></td>
              <td style="text-align:right">Rs <?= number_format((float)$r['TaxAmount'], 2) ?></td>
              <td style="text-align:right;color:var(--danger);">Rs <?= number_format((float)$r['RefundAmount'], 2) ?></td>
              <td style="text-align:right;font-weight:600;color:var(--success);">Rs <?= number_format((float)$r['NetRevenue'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr style="font-weight:600;">
          <td>Totals</td>
          <td style="text-align:center"><?= array_sum(array_column($rows, 'OrderCount')) ?></td>
          <td style="text-align:right">Rs <?= number_format($view === 'daily' ? $totalGross : $monthlyTotalGross, 2) ?></td>
          <td style="text-align:right">Rs <?= number_format(array_sum(array_column($rows, 'TaxAmount')), 2) ?></td>
          <td style="text-align:right">Rs <?= number_format($view === 'daily' ? $totalRefunds : $monthlyTotalRefunds, 2) ?></td>
          <td style="text-align:right">Rs <?= number_format($view === 'daily' ? $totalNet : $monthlyTotalNet, 2) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<style>
  .active-filter { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; }
  @media print { .sidebar, .sidebar-overlay, .navbar { display: none !important; } .main-content { margin-left: 0 !important; padding: 0 !important; } }
</style>

<script>
function loadRevenue() {
    var from = document.getElementById('revFrom').value;
    var to = document.getElementById('revTo').value;
    window.location.href = 'revenue.php?view=daily&from=' + from + '&to=' + to;
}
function quickRev(range) {
    var to = '<?= date('Y-m-d') ?>', from;
    if (range === '7days') from = '<?= date('Y-m-d', strtotime('-7 days')) ?>';
    else if (range === '30days') from = '<?= date('Y-m-d', strtotime('-30 days')) ?>';
    else if (range === 'month') from = '<?= date('Y-m-01') ?>';
    window.location.href = 'revenue.php?view=daily&from=' + from + '&to=' + to;
}
</script>

<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
