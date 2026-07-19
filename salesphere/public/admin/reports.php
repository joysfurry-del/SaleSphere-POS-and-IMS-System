<?php
// reports page with CSV and PDF export
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/pdf_reports.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireRole([1, 2, 4, 5, 6]);

$db = \Database::getConnection();
$report = $_GET['report'] ?? 'sales';
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$customerType = $_GET['customer_type'] ?? 'all';
$daterange = 'custom';
if ($from === date('Y-m-d') && $to === date('Y-m-d')) $daterange = 'today';
elseif ($from === date('Y-m-d', strtotime('-7 days')) && $to === date('Y-m-d')) $daterange = 'week';
elseif ($from === date('Y-m-01') && $to === date('Y-m-d')) $daterange = 'month';
$data = [];
$error = '';

try {
    if ($report === 'sales') {
        $parts = [];
        $params = [];
        if ($customerType === 'all' || $customerType === 'instore') {
            $parts[] = "(SELECT o.OrderID, o.CreatedAt, o.Subtotal, o.TaxAmount, o.Total, 'Completed' AS Status, br.BranchName, o.CustomerName, 'In-store' AS Source,
                   (SELECT COUNT(*) FROM order_item oi WHERE oi.OrderID = o.OrderID) AS ItemCount
            FROM `order` o
            LEFT JOIN branch br ON o.BranchID = br.BranchID
            WHERE DATE(o.CreatedAt) BETWEEN ? AND ?)";
            $params[] = $from; $params[] = $to;
        }
        if ($customerType === 'all' || $customerType === 'online') {
            $parts[] = "(SELECT oo.OnlineOrderID, oo.CreatedAt, oo.Subtotal, oo.TaxAmount, oo.Total, oo.Status, br.BranchName, c.CustomerName, 'Online' AS Source,
                   (SELECT COUNT(*) FROM online_order_item ooi WHERE ooi.OnlineOrderID = oo.OnlineOrderID) AS ItemCount
            FROM online_order oo
            LEFT JOIN branch br ON oo.BranchID = br.BranchID
            LEFT JOIN customer c ON oo.CustomerID = c.CustomerID
            WHERE DATE(oo.CreatedAt) BETWEEN ? AND ? AND oo.Status != 'Cancelled')";
            $params[] = $from; $params[] = $to;
        }
        $sql = implode(' UNION ALL ', $parts) . " ORDER BY CreatedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    } elseif ($report === 'inventory') {
        $sql = "SELECT p.ProductName, p.ProductSKU AS Sku, p.Barcode, i.AvailableQty, i.ReorderLevel, i.MinStockLevel, br.BranchName
                FROM inventory i
                JOIN product p ON i.ProductID = p.ProductID
                JOIN branch br ON i.BranchID = br.BranchID
                ORDER BY (i.AvailableQty <= i.ReorderLevel) DESC, p.ProductName ASC";
        $stmt = $db->query($sql);
        $data = $stmt->fetchAll();
    } elseif ($report === 'refunds') {
        $sql = "SELECT pr.ReturnID, pr.RequestedAt AS CreatedAt, 
                       CASE WHEN pr.ReferenceType = 'Order' THEN pr.ReferenceID ELSE NULL END AS OrderID,
                       CASE WHEN pr.ReferenceType = 'OnlineOrder' THEN pr.ReferenceID ELSE NULL END AS OnlineOrderID,
                       pr.Reason, pr.TotalRefund AS RefundAmount, pr.Status, br.BranchName
                FROM product_return pr
                LEFT JOIN branch br ON pr.BranchID = br.BranchID
                WHERE pr.Status IN ('Approved', 'Refunded')
                  AND DATE(pr.RequestedAt) BETWEEN ? AND ?
                ORDER BY pr.RequestedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$from, $to]);
        $data = $stmt->fetchAll();
    } elseif ($report === 'revenue') {
        $sql = "SELECT br.BranchName,
                       COUNT(DISTINCT o.OrderID) + COUNT(DISTINCT oo.OnlineOrderID) AS OrderCount,
                       COALESCE(SUM(o.Subtotal), 0) + COALESCE(SUM(oo.Subtotal), 0) AS Subtotal,
                       COALESCE(SUM(o.TaxAmount), 0) + COALESCE(SUM(oo.TaxAmount), 0) AS TaxAmount,
                       COALESCE(SUM(o.Total), 0) + COALESCE(SUM(oo.Total), 0) AS Total,
                       COALESCE((SELECT SUM(TotalRefund) FROM product_return pr WHERE pr.BranchID = br.BranchID AND pr.Status IN ('Approved', 'Refunded') AND DATE(pr.RequestedAt) BETWEEN ? AND ?), 0) AS RefundTotal
                FROM branch br
                LEFT JOIN `order` o ON o.BranchID = br.BranchID AND DATE(o.CreatedAt) BETWEEN ? AND ?
                LEFT JOIN online_order oo ON oo.BranchID = br.BranchID AND DATE(oo.CreatedAt) BETWEEN ? AND ? AND oo.Status != 'Cancelled'
                GROUP BY br.BranchID, br.BranchName
                ORDER BY Total DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$from, $to, $from, $to, $from, $to]);
        $data = $stmt->fetchAll();
    }
} catch (\Throwable $e) {
    $error = $e->getMessage();
}

// PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $filename = 'report_' . $report . '_' . date('Ymd') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    if ($report === 'sales') {
        echo generateSalesReportPDF($data, $from, $to, $customerType);
    } elseif ($report === 'inventory') {
        echo generateInventoryReportPDF($data);
    } elseif ($report === 'refunds') {
        echo generateRefundReportPDF($data, $from, $to);
    } elseif ($report === 'revenue') {
        echo generateRevenueReportPDF($data, $from, $to);
    }
    exit;
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'report_' . $report . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    if ($report === 'sales') {
        fputcsv($out, ['Date', 'Order #', 'Source', 'Branch', 'Customer', 'Items', 'Subtotal (Rs)', 'Tax (Rs)', 'Total (Rs)', 'Status']);
        foreach ($data as $r) {
            fputcsv($out, [
                date('d/m/Y', strtotime($r['CreatedAt'])),
                $r['OrderID'] ?? $r['OnlineOrderID'] ?? '',
                $r['Source'] ?? 'In-store',
                $r['BranchName'] ?? '-',
                $r['CustomerName'] ?? 'Walk-in',
                (int)($r['ItemCount'] ?? 0),
                number_format((float)($r['Subtotal'] ?? 0), 2),
                number_format((float)($r['TaxAmount'] ?? 0), 2),
                number_format((float)($r['Total'] ?? 0), 2),
                $r['Status'] ?? '',
            ]);
        }
    } elseif ($report === 'inventory') {
        fputcsv($out, ['Product', 'SKU', 'Branch', 'Qty', 'Reorder Level', 'Min Stock', 'Status']);
        foreach ($data as $r) {
            $qty = (int)($r['AvailableQty'] ?? 0);
            fputcsv($out, [
                $r['ProductName'] ?? '-',
                $r['Sku'] ?? $r['Barcode'] ?? '-',
                $r['BranchName'] ?? '-',
                $qty,
                (int)($r['ReorderLevel'] ?? 0),
                (int)($r['MinStockLevel'] ?? 0),
                $qty <= (int)($r['ReorderLevel'] ?? 0) ? 'Low Stock' : 'In Stock',
            ]);
        }
    } elseif ($report === 'refunds') {
        fputcsv($out, ['Date', 'Return #', 'Order #', 'Branch', 'Reason', 'Refund (Rs)', 'Status']);
        foreach ($data as $r) {
            fputcsv($out, [
                date('d/m/Y', strtotime($r['CreatedAt'])),
                $r['ReturnID'] ?? '',
                $r['OrderID'] ?? (isset($r['OnlineOrderID']) ? 'ONLINE#' . $r['OnlineOrderID'] : '-'),
                $r['BranchName'] ?? '-',
                $r['Reason'] ?? '-',
                number_format((float)($r['RefundAmount'] ?? 0), 2),
                $r['Status'] ?? '',
            ]);
        }
    } elseif ($report === 'revenue') {
        fputcsv($out, ['Branch', 'Orders', 'Subtotal (Rs)', 'Tax (Rs)', 'Total (Rs)', 'Refunds (Rs)', 'Net (Rs)']);
        foreach ($data as $r) {
            fputcsv($out, [
                $r['BranchName'] ?? '-',
                (int)($r['OrderCount'] ?? 0),
                number_format((float)($r['Subtotal'] ?? 0), 2),
                number_format((float)($r['TaxAmount'] ?? 0), 2),
                number_format((float)($r['Total'] ?? 0), 2),
                number_format((float)($r['RefundTotal'] ?? 0), 2),
                number_format((float)($r['NetRevenue'] ?? (float)($r['Total'] ?? 0) - (float)($r['RefundTotal'] ?? 0)), 2),
            ]);
        }
    }
    fclose($out);
    exit;
}

$pageTitle = 'Reports - ' . ucfirst($report);
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
?>
<div style="display:flex;gap:1rem;max-width:1200px;margin:0 auto;">
  <div style="width:200px;flex-shrink:0;">
    <div class="card" style="padding:0.75rem;">
      <h3 style="font-size:0.85rem;font-weight:600;color:var(--text-primary);margin-bottom:0.75rem;padding:0 0.5rem;">Report Type</h3>
      <?php
      $reports = [
        'sales'     => 'Sales Report',
        'revenue'   => 'Revenue by Branch',
        'inventory' => 'Inventory Report',
        'refunds'   => 'Refund Report',
      ];
      foreach ($reports as $key => $label):
        $active = $report === $key;
      ?>
        <a href="reports.php?report=<?= $key ?>&from=<?= $from ?>&to=<?= $to ?>&customer_type=<?= $customerType ?>" class="nav-item <?= $active ? 'active' : '' ?>" style="justify-content:flex-start;padding:0.5rem;font-size:0.85rem;border-radius:6px;margin-bottom:2px;">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div style="flex:1;min-width:0;">
    <?php require __DIR__ . '/../../views/admin/reports/list.php'; ?>
  </div>
</div>
<?php require __DIR__ . '/../../views/layouts/footer.php'; ?>
