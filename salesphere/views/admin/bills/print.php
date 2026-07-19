<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireRole([1, 3, 4]);

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { echo 'Invalid bill ID'; exit; }

$b = \App\Models\BillManager::getById($id);
if (!$b) { echo 'Bill not found'; exit; }
$items = \App\Models\BillManager::getItems($id);
$payments = \App\Models\BillManager::getPayments($id);

$balance = (float)$b['Total'] - (float)$b['AmountPaid'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice - <?= escape($b['BillNumber'] ?? '') ?></title>
<style>
  @page { margin: 10mm; }
  body { font-family: 'Inter', Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 15px; line-height: 1.5; }
  .header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #FF5722; }
  .header-left h1 { font-size: 26px; font-weight: 800; margin: 0; color: #FF5722; letter-spacing: 1px; }
  .header-left p { margin: 3px 0; color: #666; font-size: 11px; }
  .header-right { text-align: right; }
  .header-right h2 { font-size: 18px; margin: 0 0 6px; color: #111; }
  .header-right .status { display: inline-block; padding: 3px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
  .details { display: flex; justify-content: space-between; margin-bottom: 25px; padding: 12px 15px; background: #f9f9f9; border-radius: 6px; }
  .details div { font-size: 11px; }
  .details div p { margin: 3px 0; }
  .details div p strong { color: #333; }
  .section-title { font-size: 13px; font-weight: 700; color: #FF5722; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th { background: #FF5722; color: #fff; text-align: left; padding: 9px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
  th:first-child { border-radius: 4px 0 0 0; }
  th:last-child { border-radius: 0 4px 0 0; }
  td { padding: 7px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
  tbody tr:nth-child(even) { background: #fafafa; }
  .totals { width: 300px; margin-left: auto; }
  .totals div { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; }
  .totals .grand { font-weight: 800; font-size: 16px; border-top: 2px solid #333; padding-top: 8px; margin-top: 4px; color: #111; }
  .totals .paid { color: #16a34a; font-weight: 600; }
  .totals .balance-due { color: #dc2626; font-weight: 700; }
  .footer { margin-top: 35px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #999; text-align: center; }
  .print-btn { display: block; margin: 20px auto; padding: 10px 30px; background: #FF5722; color: #fff; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
  @media print { .print-btn { display: none; } body { padding: 0; } .details { background: none; padding: 12px 0; } th { border-radius: 0; } }
</style>
</head>
<body>
  <button class="print-btn" onclick="window.print()">Print / Save PDF</button>

  <div class="header">
    <div class="header-left">
      <h1>Salesphere</h1>
      <p>123 Main Street, Colombo</p>
      <p>+94 11-2345678 &middot; info@salesphere.com</p>
    </div>
    <div class="header-right">
      <h2><?= escape($b['BillNumber'] ?? '') ?></h2>
      <p><?= escape($b['BillType'] ?? 'Invoice') ?></p>
      <span class="status" style="background:<?= $b['Status'] === 'Paid' ? '#16a34a' : ($b['Status'] === 'Overdue' ? '#dc2626' : '#f59e0b') ?>;color:#fff;"><?= escape($b['Status']) ?></span>
    </div>
  </div>

  <div class="details">
    <div>
      <p><strong>Bill To:</strong></p>
      <p><?= escape($b['CustomerName'] ?? 'Walk-in Customer') ?></p>
    </div>
    <div>
      <p><strong>Invoice Info:</strong></p>
      <p>Date: <?= $b['CreatedAt'] ? date('d/m/Y', strtotime($b['CreatedAt'])) : '-' ?></p>
      <p>Due: <?= $b['DueDate'] ? date('d/m/Y', strtotime($b['DueDate'])) : '-' ?></p>
      <p>Branch: <?= escape($b['BranchName'] ?? '-') ?></p>
    </div>
  </div>

  <div class="section-title">Order Items</div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Item</th>
        <th style="text-align:center">Qty</th>
        <th style="text-align:right">Price</th>
        <th style="text-align:right">Tax</th>
        <th style="text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($items as $item): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= escape($item['Description']) ?></td>
          <td style="text-align:center"><?= (float)$item['Quantity'] ?></td>
          <td style="text-align:right">Rs <?= number_format((float)$item['UnitPrice'], 2) ?></td>
          <td style="text-align:right">Rs <?= number_format((float)$item['TaxAmount'], 2) ?></td>
          <td style="text-align:right">Rs <?= number_format((float)$item['LineTotal'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <div><span>Subtotal</span><span>Rs <?= number_format((float)$b['Subtotal'], 2) ?></span></div>
    <div><span>Tax</span><span>Rs <?= number_format((float)$b['TaxAmount'], 2) ?></span></div>
    <?php if ((float)$b['DiscountAmount'] > 0): ?>
    <div><span>Discount</span><span>-Rs <?= number_format((float)$b['DiscountAmount'], 2) ?></span></div>
    <?php endif; ?>
    <div class="grand"><span>Total</span><span>Rs <?= number_format((float)$b['Total'], 2) ?></span></div>
    <div class="paid"><span>Paid</span><span>Rs <?= number_format((float)$b['AmountPaid'], 2) ?></span></div>
    <?php if ($balance > 0): ?>
    <div class="balance-due"><span>Balance Due</span><span>Rs <?= number_format($balance, 2) ?></span></div>
    <?php endif; ?>
  </div>

  <?php if (!empty($payments)): ?>
    <div class="section-title" style="margin-top:25px;">Payment History</div>
    <table>
      <thead><tr><th>Method</th><th style="text-align:right">Amount</th><th>Reference</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $pmt): ?>
          <tr>
            <td><?= escape($pmt['PaymentMethod']) ?></td>
            <td style="text-align:right">Rs <?= number_format((float)$pmt['Amount'], 2) ?></td>
            <td><?= escape($pmt['Reference'] ?? '-') ?></td>
            <td><?= date('d/m/Y H:i', strtotime($pmt['ReceivedAt'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($b['Notes']): ?>
    <p style="margin-top:15px;font-size:12px;color:#666;"><?= nl2br(escape($b['Notes'])) ?></p>
  <?php endif; ?>

  <div class="footer">
    <p>Thank you for your business!</p>
    <p>Salesphere Management System &middot; Generated on <?= date('d/m/Y H:i') ?></p>
  </div>

  <script>window.print();</script>
</body>
</html>
