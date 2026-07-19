<?php
$report = $report ?? 'sales';
$daterange = $daterange ?? 'custom';
$data = $data ?? [];
$db = \Database::getConnection();
$error = '';
$dateFrom = $_GET['from'] ?? date('Y-m-d');
$dateTo = $_GET['to'] ?? date('Y-m-d');
$customerType = $_GET['customer_type'] ?? 'all';
if ($daterange === 'today') { $dateFrom = date('Y-m-d'); $dateTo = date('Y-m-d'); }
elseif ($daterange === 'week') { $dateFrom = date('Y-m-d', strtotime('-7 days')); $dateTo = date('Y-m-d'); }
elseif ($daterange === 'month') { $dateFrom = date('Y-m-01'); $dateTo = date('Y-m-d'); }

$reportNames = [
  'sales'     => 'Sales Report',
  'revenue'   => 'Revenue by Branch',
  'inventory' => 'Inventory Report',
  'refunds'   => 'Refund / Return Report',
];
$reportLabel = $reportNames[$report] ?? ucfirst($report) . ' Report';
?>
<div class="report-page">
  <!-- report header -->
  <div class="report-header">
    <div class="report-title-row">
      <div>
        <h2 class="report-name"><?= escape($reportLabel) ?></h2>
        <p class="report-meta">
          Generated: <?= date('d M Y H:i') ?>
          <?php if ($report !== 'inventory'): ?>
            &nbsp;|&nbsp; Period: <?= date('d M Y', strtotime($dateFrom)) ?> – <?= date('d M Y', strtotime($dateTo)) ?>
          <?php endif; ?>
          <?php if ($report === 'sales' && $customerType !== 'all'): ?>
            &nbsp;|&nbsp; Customer: <?= ucfirst($customerType) ?>
          <?php endif; ?>
        </p>
      </div>
      <div class="report-actions">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" class="btn-pdf">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Download PDF
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-csv">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download CSV
        </a>
        <button class="btn-print" onclick="window.print()">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print
        </button>
      </div>
    </div>

    <!-- filter bar -->
    <div class="filter-bar">
      <div class="filter-group">
        <label>From</label>
        <input type="date" id="reportFrom" class="filter-input" value="<?= $dateFrom ?>">
      </div>
      <div class="filter-group">
        <label>To</label>
        <input type="date" id="reportTo" class="filter-input" value="<?= $dateTo ?>">
      </div>
      <?php if ($report === 'sales'): ?>
      <div class="filter-group">
        <label>Customer</label>
        <select id="customerType" class="filter-select">
          <option value="all" <?= $customerType === 'all' ? 'selected' : '' ?>>All</option>
          <option value="instore" <?= $customerType === 'instore' ? 'selected' : '' ?>>In-store (POS)</option>
          <option value="online" <?= $customerType === 'online' ? 'selected' : '' ?>>Online</option>
        </select>
      </div>
      <?php endif; ?>
      <button class="btn-generate" onclick="loadReport()">Generate</button>
      <div class="quick-ranges">
        <button class="range-btn <?= $daterange === 'today' ? 'active' : '' ?>" onclick="quickRange('today')">Today</button>
        <button class="range-btn <?= $daterange === 'week' ? 'active' : '' ?>" onclick="quickRange('week')">7 Days</button>
        <button class="range-btn <?= $daterange === 'month' ? 'active' : '' ?>" onclick="quickRange('month')">This Month</button>
      </div>
    </div>
  </div>

  <!-- table -->
  <div class="report-table-wrap">
  <table class="report-table <?= $report ?>">
    <?php $ths = 'color:#fff;background:#27272A;padding:12px 14px;font-size:14px;font-weight:700;text-transform:uppercase;border-bottom:1px solid #3F3F46;white-space:normal;word-break:break-word;line-height:1.4;letter-spacing:0.04em;'; ?>
    <thead>
      <tr>
        <?php if ($report === 'sales'): ?>
          <th class="col-date th-center" style="<?= $ths ?>text-align:center">Date</th>
          <th class="col-order th-center" style="<?= $ths ?>text-align:center">Order #</th>
          <th class="col-source th-center" style="<?= $ths ?>text-align:center">Source</th>
          <th class="col-branch" style="<?= $ths ?>text-align:left">Branch</th>
          <th class="col-customer" style="<?= $ths ?>text-align:left">Customer</th>
          <th class="col-items th-center" style="<?= $ths ?>text-align:center">Items</th>
          <th class="col-amount th-right" style="<?= $ths ?>text-align:right">Subtotal</th>
          <th class="col-amount th-right" style="<?= $ths ?>text-align:right">Tax</th>
          <th class="col-amount th-right" style="<?= $ths ?>text-align:right">Total</th>
          <th class="col-status th-center" style="<?= $ths ?>text-align:center">Status</th>
        <?php elseif ($report === 'inventory'): ?>
          <th class="col-product" style="<?= $ths ?>text-align:left">Product</th>
          <th class="col-sku" style="<?= $ths ?>text-align:left">SKU</th>
          <th class="col-branch" style="<?= $ths ?>text-align:left">Branch</th>
          <th class="col-qty th-center" style="<?= $ths ?>text-align:center">Qty</th>
          <th class="col-qty th-center" style="<?= $ths ?>text-align:center">Reorder</th>
          <th class="col-qty th-center" style="<?= $ths ?>text-align:center">Min</th>
          <th class="col-status th-center" style="<?= $ths ?>text-align:center">Status</th>
        <?php elseif ($report === 'refunds'): ?>
          <th class="col-date th-center" style="<?= $ths ?>text-align:center">Date</th>
          <th class="col-ret th-center" style="<?= $ths ?>text-align:center">Return #</th>
          <th class="col-order th-center" style="<?= $ths ?>text-align:center">Order #</th>
          <th class="col-branch" style="<?= $ths ?>text-align:left">Branch</th>
          <th class="col-reason" style="<?= $ths ?>text-align:left">Reason</th>
          <th class="col-amount th-right" style="<?= $ths ?>text-align:right">Refund</th>
          <th class="col-status th-center" style="<?= $ths ?>text-align:center">Status</th>
        <?php elseif ($report === 'revenue'): ?>
          <th class="col-branch" style="<?= $ths ?>text-align:left">Branch</th>
          <th class="col-orders th-right" style="<?= $ths ?>text-align:right">Orders</th>
          <th class="col-subtotal th-right" style="<?= $ths ?>text-align:right">Subtotal</th>
          <th class="col-tax th-right" style="<?= $ths ?>text-align:right">Tax</th>
          <th class="col-total th-right" style="<?= $ths ?>text-align:right">Total</th>
          <th class="col-refunds th-right" style="<?= $ths ?>text-align:right">Refunds</th>
          <th class="col-net th-right" style="<?= $ths ?>text-align:right">Net</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($data)): ?>
        <tr><td colspan="20" class="empty-row">No data found for the selected period.</td></tr>
      <?php else: ?>
        <?php foreach ($data as $row): ?>
          <tr>
            <?php if ($report === 'sales'): ?>
              <td class="cell-date"><?= date('d/m/Y', strtotime($row['CreatedAt'])) ?></td>
              <td class="cell-id">#<?= $row['OrderID'] ?? $row['OnlineOrderID'] ?? '' ?></td>
              <td><span class="badge <?= ($row['Source'] ?? 'In-store') === 'Online' ? 'badge-cyan' : 'badge-orange' ?>"><?= escape($row['Source'] ?? 'In-store') ?></span></td>
              <td><?= escape($row['BranchName'] ?? '-') ?></td>
              <td><?= escape($row['CustomerName'] ?? 'Walk-in') ?></td>
              <td class="cell-center"><?php $ic = (int)($row['ItemCount'] ?? 0); if ($ic === 0 && (float)($row['Subtotal'] ?? 0) > 0) $ic = 1; echo $ic; ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['Subtotal'] ?? 0), 2) ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['TaxAmount'] ?? 0), 2) ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['Total'] ?? 0), 2) ?></td>
              <td><span class="badge <?= $row['Status'] === 'Completed' ? 'badge-green' : 'badge-yellow' ?>"><?= escape($row['Status']) ?></span></td>
            <?php elseif ($report === 'inventory'): ?>
              <td><?= escape($row['ProductName'] ?? '-') ?></td>
              <td class="cell-sku"><?= escape($row['Sku'] ?? $row['Barcode'] ?? '-') ?></td>
              <td><?= escape($row['BranchName'] ?? '-') ?></td>
              <td class="cell-center"><?= (int)($row['AvailableQty'] ?? 0) ?></td>
              <td class="cell-center"><?= (int)($row['ReorderLevel'] ?? 0) ?></td>
              <td class="cell-center"><?= (int)($row['MinStockLevel'] ?? 0) ?></td>
              <td><span class="badge <?= ((int)($row['AvailableQty'] ?? 0) <= (int)($row['ReorderLevel'] ?? 0)) ? 'badge-red' : 'badge-green' ?>"><?= ((int)($row['AvailableQty'] ?? 0) <= (int)($row['ReorderLevel'] ?? 0)) ? 'Low Stock' : 'In Stock' ?></span></td>
            <?php elseif ($report === 'refunds'): ?>
              <td class="cell-date"><?= date('d/m/Y', strtotime($row['CreatedAt'])) ?></td>
              <td class="cell-id">#<?= $row['ReturnID'] ?? '' ?></td>
              <td class="cell-id">#<?= $row['OrderID'] ?? (isset($row['OnlineOrderID']) ? 'ONLINE#' . $row['OnlineOrderID'] : '-') ?></td>
              <td><?= escape($row['BranchName'] ?? '-') ?></td>
              <td class="cell-reason"><?= escape($row['Reason'] ?? '-') ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['RefundAmount'] ?? 0), 2) ?></td>
              <td><span class="badge <?= (($row['Status'] ?? '') === 'Approved') ? 'badge-green' : ((($row['Status'] ?? '') === 'Rejected') ? 'badge-red' : ((($row['Status'] ?? '') === 'Refunded') ? 'badge-cyan' : 'badge-yellow')) ?>"><?= escape($row['Status'] ?? 'Requested') ?></span></td>
            <?php elseif ($report === 'revenue'): ?>
              <td><strong><?= escape($row['BranchName'] ?? '-') ?></strong></td>
              <td class="cell-right"><?= (int)($row['OrderCount'] ?? 0) ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['Subtotal'] ?? 0), 2) ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['TaxAmount'] ?? 0), 2) ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['Total'] ?? 0), 2) ?></td>
              <td class="cell-right">Rs. <?= number_format((float)($row['RefundTotal'] ?? 0), 2) ?></td>
              <td class="cell-right cell-net">Rs. <?= number_format((float)($row['NetRevenue'] ?? (float)($row['Total'] ?? 0) - (float)($row['RefundTotal'] ?? 0)), 2) ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>

  <!-- summary cards -->
  <?php if (!empty($data)): ?>
  <div class="summary-section">
    <?php if ($report === 'sales'): ?>
      <?php $st = array_sum(array_column($data, 'Subtotal')); $tt = array_sum(array_column($data, 'Total')); $tx = array_sum(array_column($data, 'TaxAmount')); ?>
      <div class="summary-card">
        <span class="summary-label">Total Orders</span>
        <span class="summary-value"><?= count($data) ?></span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Total Subtotal</span>
        <span class="summary-value">Rs. <?= number_format($st, 2) ?></span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Total Tax</span>
        <span class="summary-value">Rs. <?= number_format($tx, 2) ?></span>
      </div>
      <div class="summary-card card-highlight">
        <span class="summary-label">Grand Total</span>
        <span class="summary-value">Rs. <?= number_format($tt, 2) ?></span>
      </div>
    <?php elseif ($report === 'inventory'): ?>
      <?php
        $totalItems = count($data);
        $lowStock = 0;
        foreach ($data as $r) {
          if ((int)($r['AvailableQty'] ?? 0) <= (int)($r['ReorderLevel'] ?? 0)) $lowStock++;
        }
        $inStock = $totalItems - $lowStock;
      ?>
      <div class="summary-card">
        <span class="summary-label">Total Products</span>
        <span class="summary-value"><?= $totalItems ?></span>
      </div>
      <div class="summary-card card-danger">
        <span class="summary-label">Low Stock Items</span>
        <span class="summary-value"><?= $lowStock ?></span>
      </div>
      <div class="summary-card card-success">
        <span class="summary-label">In Stock</span>
        <span class="summary-value"><?= $inStock ?></span>
      </div>
    <?php elseif ($report === 'refunds'): ?>
      <?php $refundTotal = array_sum(array_column($data, 'RefundAmount')); ?>
      <div class="summary-card">
        <span class="summary-label">Total Returns</span>
        <span class="summary-value"><?= count($data) ?></span>
      </div>
      <div class="summary-card card-highlight">
        <span class="summary-label">Total Refund Amount</span>
        <span class="summary-value">Rs. <?= number_format($refundTotal, 2) ?></span>
      </div>
    <?php elseif ($report === 'revenue'): ?>
      <?php
        $totOrders = array_sum(array_column($data, 'OrderCount'));
        $totSub = array_sum(array_column($data, 'Subtotal'));
        $totTax = array_sum(array_column($data, 'TaxAmount'));
        $totTotal = array_sum(array_column($data, 'Total'));
        $totRef = array_sum(array_column($data, 'RefundTotal'));
        $totNet = $totTotal - $totRef;
      ?>
      <div class="summary-card">
        <span class="summary-label">Total Branches</span>
        <span class="summary-value"><?= count($data) ?></span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Total Orders</span>
        <span class="summary-value"><?= $totOrders ?></span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Total Subtotal</span>
        <span class="summary-value">Rs. <?= number_format($totSub, 2) ?></span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Total Tax</span>
        <span class="summary-value">Rs. <?= number_format($totTax, 2) ?></span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Total Refunds</span>
        <span class="summary-value">Rs. <?= number_format($totRef, 2) ?></span>
      </div>
      <div class="summary-card card-highlight">
        <span class="summary-label">Net Revenue</span>
        <span class="summary-value">Rs. <?= number_format($totNet, 2) ?></span>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<style>
/* === report page layout === */
.report-page {
  max-width: 1200px;
  margin: 0 auto;
}

/* === header area === */
.report-header {
  margin-bottom: 1.25rem;
}
.report-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}
.report-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
}
.report-meta {
  font-size: 0.78rem;
  color: var(--text-secondary);
  margin: 0;
}
.report-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.btn-pdf, .btn-csv, .btn-print {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  border: 1px solid var(--border);
  transition: all 0.15s ease;
}
.btn-pdf { background: var(--danger); color: #fff; border-color: var(--danger); }
.btn-pdf:hover { opacity: 0.88; }
.btn-csv { background: var(--secondary); color: #000; border-color: var(--secondary); }
.btn-csv:hover { opacity: 0.88; }
.btn-print { background: var(--bg-card); color: var(--text-primary); border-color: var(--border); }
.btn-print:hover { background: var(--bg-hover); }

/* === filter bar === */
.filter-bar {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  align-items: end;
  padding: 0.75rem 1rem;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 8px;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.filter-group label {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.filter-input, .filter-select {
  padding: 0.5rem 0.8rem;
  background: var(--bg-primary);
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--text-primary);
  font-size: 0.85rem;
  font-family: 'Inter', sans-serif;
  outline: none;
  width: 170px;
}
.filter-input:focus, .filter-select:focus {
  border-color: var(--primary);
}
.btn-generate {
  padding: 0.45rem 1rem;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}
.btn-generate:hover { background: var(--primary-hover); }
.quick-ranges {
  display: flex;
  gap: 0.3rem;
  margin-left: auto;
}
.range-btn {
  padding: 0.35rem 0.7rem;
  background: transparent;
  color: var(--text-secondary);
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}
.range-btn:hover { background: var(--bg-hover); color: var(--text-primary); }
.range-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }

/* === table === */
.report-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--bg-card);
  min-width: 100%;
}
.report-table-wrap table {
  min-width: 820px;
}
.report-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}
.report-table thead th {
  padding: 12px 14px;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #fff;
  background: #27272A;
  border-bottom: 1px solid #3F3F46;
  text-align: left;
  white-space: normal;
  word-break: break-word;
  line-height: 1.4;
}
.report-table tbody td {
  padding: 0.6rem 0.8rem;
  font-size: 0.8rem;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
  word-break: break-word;
  overflow-wrap: break-word;
}
.report-table tbody tr:last-child td {
  border-bottom: none;
}
.report-table tbody tr:hover td {
  background: var(--bg-hover);
}
.report-table tbody tr:nth-child(odd) td {
  background: #ffffff;
}
.report-table tbody tr:nth-child(even) td {
  background: #F4F4F5;
}
html.dark .report-table tbody tr:nth-child(odd) td {
  background: #27272A;
}
html.dark .report-table tbody tr:nth-child(even) td {
  background: #1f1f23;
}
.report-table tbody tr:hover td {
  background: var(--bg-hover) !important;
}

/* === column widths per report (each group totals exactly 100%) === */
/* sales (10 cols): date 8 + order 8 + source 8 + branch 11 + customer 18 + items 5 + amount*3 33 + status 9 = 100 */
.sales .col-date { width: 8%; }
.sales .col-order { width: 8%; }
.sales .col-source { width: 8%; }
.sales .col-branch { width: 11%; }
.sales .col-customer { width: 18%; }
.sales .col-items { width: 5%; }
.sales .col-amount { width: 11%; }
.sales .col-status { width: 9%; }

/* inventory (7 cols): product 28 + sku 16 + branch 14 + qty*3 30 + status 12 = 100 */
.inventory .col-product { width: 28%; }
.inventory .col-sku { width: 16%; }
.inventory .col-branch { width: 14%; }
.inventory .col-qty { width: 10%; }
.inventory .col-status { width: 12%; }

/* refunds (7 cols): date 9 + order 11 + ret 11 + branch 15 + reason 28 + amount 13 + status 13 = 100 */
.refunds .col-date { width: 9%; }
.refunds .col-order { width: 11%; }
.refunds .col-ret { width: 11%; }
.refunds .col-branch { width: 15%; }
.refunds .col-reason { width: 28%; }
.refunds .col-amount { width: 13%; }
.refunds .col-status { width: 13%; }

/* revenue (7 cols): branch 18 + orders 12 + subtotal 14 + tax 14 + total 14 + refunds 14 + net 14 = 100 */
.revenue .col-branch { width: 18%; }
.revenue .col-orders { width: 12%; }
.revenue .col-subtotal { width: 14%; }
.revenue .col-tax { width: 14%; }
.revenue .col-total { width: 14%; }
.revenue .col-refunds { width: 14%; }
.revenue .col-net { width: 14%; }

/* header alignment */
.report-table thead th.th-center { text-align: center !important; }
.report-table thead th.th-right { text-align: right !important; }

/* cell alignment */
.cell-center { text-align: center; }
.cell-right { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.cell-date { font-size: 0.75rem; }
.cell-id { font-weight: 600; font-size: 0.8rem; }
.cell-sku { font-size: 0.75rem; color: var(--text-secondary); }
.cell-reason { word-break: break-word; }
.cell-net { font-weight: 700; color: var(--success); }
.empty-row {
  text-align: center;
  padding: 2.5rem !important;
  color: var(--text-secondary);
  font-size: 0.85rem;
}

/* === badges === */
.badge {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 600;
  white-space: nowrap;
}
.badge-orange { background: var(--primary); color: #fff; }
.badge-cyan { background: var(--secondary); color: #000; }
.badge-green { background: var(--success); color: #fff; }
.badge-yellow { background: var(--warning); color: #fff; }
.badge-red { background: var(--danger); color: #fff; }

/* === summary cards (CSS grid — no overlap, proper wrapping) === */
.summary-section {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-top: 1.25rem;
}
.summary-card {
  padding: 1.1rem 1.25rem;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 8px;
  text-align: center;
  box-sizing: border-box;
}
.summary-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-secondary);
  margin-bottom: 0.4rem;
  overflow-wrap: break-word;
  word-break: break-word;
}
.summary-value {
  display: block;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  overflow-wrap: break-word;
  word-break: break-word;
}
.card-highlight {
  border-color: var(--primary);
  background: rgba(255, 87, 34, 0.05);
}
.card-highlight .summary-value { color: var(--primary); }
.card-danger {
  border-color: var(--danger);
  background: rgba(239, 68, 68, 0.05);
}
.card-danger .summary-value { color: var(--danger); }
.card-success {
  border-color: var(--success);
  background: rgba(34, 197, 94, 0.05);
}
.card-success .summary-value { color: var(--success); }

/* === print styles === */
@media print {
  body { background: #fff; }
  .sidebar, .sidebar-overlay, .navbar, .sidebar-nav, .sidebar-footer,
  .report-actions, .filter-bar, .quick-ranges, .btn-generate,
  .btn-pdf, .btn-csv, .btn-print { display: none !important; }
  .main-content { margin-left: 0 !important; padding: 0.5in !important; margin-top: 0 !important; }
  .report-table-wrap { border: 1px solid #ccc; border-radius: 0; }
  .report-table thead { display: table-header-group; }
  .report-table tbody tr { page-break-inside: avoid; }
  .summary-card { page-break-inside: avoid; border: 1px solid #ccc; }
  .card-highlight { border: 2px solid var(--primary); }
  .report-table thead th { background: #333 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .report-table tbody tr:nth-child(odd) td { background: #fff !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .report-table tbody tr:nth-child(even) td { background: #f5f5f5 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .report-meta { color: #666; }
}

@media (max-width: 768px) {
  .report-title-row { flex-direction: column; }
  .report-actions { width: 100%; }
  .filter-bar { flex-direction: column; align-items: stretch; }
  .filter-input, .filter-select { width: 100%; }
  .quick-ranges { margin-left: 0; }
  .summary-section { grid-template-columns: 1fr; }
  .report-table { font-size: 0.75rem; }
}
</style>

<script>
function loadReport() {
    var from = document.getElementById('reportFrom').value;
    var to = document.getElementById('reportTo').value;
    var report = '<?= $report ?>';
    var ct = document.getElementById('customerType')?.value || 'all';
    window.location.href = 'reports.php?report=' + report + '&customer_type=' + ct + '&from=' + from + '&to=' + to;
}
function quickRange(range) {
    var from, to = '<?= date('Y-m-d') ?>';
    if (range === 'today') from = '<?= date('Y-m-d') ?>';
    else if (range === 'week') from = '<?= date('Y-m-d', strtotime('-7 days')) ?>';
    else if (range === 'month') from = '<?= date('Y-m-01') ?>';
    var report = '<?= $report ?>';
    var ct = document.getElementById('customerType')?.value || 'all';
    window.location.href = 'reports.php?report=' + report + '&customer_type=' + ct + '&from=' + from + '&to=' + to;
}
</script>
