<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory Report â€” <?= escape($_SESSION['user']['BranchName'] ?? 'Branch') ?> | Salesphere</title>
<style>
  @page { margin: 15mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', Arial, sans-serif; color: #18181B; background: #fff; font-size: 12px; }
  .report-header { text-align: center; margin-bottom: 1.5rem; border-bottom: 2px solid #FF5722; padding-bottom: 0.75rem; }
  .report-header h1 { font-size: 1.3rem; font-weight: 800; }
  .report-header p { font-size: 0.75rem; color: #52525B; }
  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  th { background: #18181B; color: #fff; padding: 0.45rem 0.6rem; text-align: left; font-weight: 600; }
  th:last-child { text-align: right; }
  td { padding: 0.4rem 0.6rem; border-bottom: 1px solid #E4E4E7; }
  td:last-child { text-align: right; font-weight: 700; }
  tr:nth-child(even) { background: #F4F4F5; }
  .total-row td { border-top: 2px solid #18181B; font-weight: 700; }
  .footer { text-align: center; margin-top: 2rem; font-size: 0.7rem; color: #A1A1AA; border-top: 1px solid #E4E4E7; padding-top: 0.75rem; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>
  <div class="no-print" style="text-align:right;margin-bottom:1rem;">
    <button onclick="window.print()" style="background:#FF5722;color:#fff;border:none;padding:0.5rem 1.2rem;border-radius:8px;cursor:pointer;font-weight:600;">Print / Save PDF</button>
  </div>

  <div class="report-header">
    <h1>Inventory Stock Report</h1>
    <p><?= escape($_SESSION['user']['BranchName'] ?? 'All Branches') ?> &mdash; Generated <?= date('d F Y, H:i') ?></p>
  </div>

  <!-- print-friendly inventory report table with totals -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Product</th>
        <th>Category</th>
        <th>Price (Rs)</th>
        <th>Available Qty</th>
      </tr>
    </thead>
    <tbody>
      <?php $total = 0; $gt = 0; ?>
      <?php foreach ($items as $i): $total += (int)$i['AvailableQty']; $gt += (float)$i['Price'] * (int)$i['AvailableQty']; ?>
        <tr>
          <td style="color:#71717A;"><?= $i['InventoryID'] ?></td>
          <td><strong><?= escape($i['ProductName']) ?></strong></td>
          <td><?= escape($i['CategoryName'] ?? '-') ?></td>
          <td style="font-weight:600">Rs <?= number_format($i['Price'], 2) ?></td>
          <td><?= (int)$i['AvailableQty'] ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
        <tr><td colspan="5" style="text-align:center;padding:1.5rem;color:#A1A1AA;">No inventory records.</td></tr>
      <?php endif; ?>
    </tbody>
    <?php if (!empty($items)): ?>
      <tfoot>
        <tr class="total-row">
          <td colspan="3"></td>
          <td>Total Items:</td>
          <td><?= $total ?></td>
        </tr>
        <tr class="total-row">
          <td colspan="3"></td>
          <td>Total Value:</td>
          <td>Rs <?= number_format($gt, 2) ?></td>
        </tr>
      </tfoot>
    <?php endif; ?>
  </table>

  <div class="footer">
    <p>Salesphere POS &amp; Inventory Management System &mdash; Confidential</p>
  </div>
</body>
</html>
