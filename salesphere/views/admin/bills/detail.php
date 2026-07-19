<?php
$b = $b ?? [];
$items = $items ?? [];
$payments = $payments ?? [];
?>
<div style="margin-bottom:1rem;">
  <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:1rem;">
    <div>
      <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);"><?= escape($b['BillNumber'] ?? '') ?></h3>
      <p style="font-size:0.8rem;color:var(--text-secondary);">
        <?= escape($b['BillType'] ?? 'Invoice') ?> &middot;
        Ref: <?= escape($b['ReferenceType'] ?? 'Manual') ?> #<?= (int)($b['ReferenceID'] ?? 0) ?>
      </p>
    </div>
    <div style="text-align:right;display:flex;flex-direction:column;align-items:end;gap:0.4rem;">
      <span class="role-badge" style="background:<?php
        $s = $b['Status'] ?? 'Draft';
        echo $s === 'Paid' ? 'var(--success)' : ($s === 'Draft' ? 'var(--text-secondary)' : ($s === 'Overdue' ? 'var(--danger)' : 'var(--warning)'));
      ?>;color:#fff"><?= escape($s) ?></span>
      <button onclick="window.open('bills.php?action=download&id=<?= (int)($b['BillID'] ?? 0) ?>','_blank')" class="btn-primary-sm" style="font-size:0.75rem;padding:0.3rem 0.7rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        Print
      </button>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;font-size:0.85rem;margin-bottom:1rem;">
    <div><strong>Customer:</strong> <?= escape($b['CustomerName'] ?? 'Walk-in') ?></div>
    <div><strong>Branch:</strong> <?= escape($b['BranchName'] ?? '-') ?></div>
    <div><strong>Cashier:</strong> <?= escape($b['CashierName'] ?? '-') ?></div>
    <div><strong>Payment:</strong> <?= escape($b['PaymentMethod'] ?? '-') ?></div>
    <div><strong>Created:</strong> <?= $b['CreatedAt'] ? date('d/m/Y H:i', strtotime($b['CreatedAt'])) : '-' ?></div>
    <div><strong>Due:</strong> <?= $b['DueDate'] ? date('d/m/Y', strtotime($b['DueDate'])) : '-' ?></div>
  </div>

  <!-- bill detail: header, items table, totals, payments, notes -->
  <table class="data-table" style="margin-bottom:1rem;">
    <thead>
      <tr>
        <th>Item</th>
        <th style="width:60px">Qty</th>
        <th style="width:90px">Price</th>
        <th style="width:70px">Tax</th>
        <th style="width:90px">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= escape($item['Description']) ?></td>
          <td><?= (float)$item['Quantity'] ?></td>
          <td>Rs <?= number_format((float)$item['UnitPrice'], 2) ?></td>
          <td>Rs <?= number_format((float)$item['TaxAmount'], 2) ?></td>
          <td>Rs <?= number_format((float)$item['LineTotal'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="text-align:right;font-size:0.9rem;">
    <p>Subtotal: Rs <?= number_format((float)$b['Subtotal'], 2) ?></p>
    <p>Tax: Rs <?= number_format((float)$b['TaxAmount'], 2) ?></p>
    <?php if ((float)$b['DiscountAmount'] > 0): ?>
      <p>Discount: -Rs <?= number_format((float)$b['DiscountAmount'], 2) ?></p>
    <?php endif; ?>
    <p><strong>Total: Rs <?= number_format((float)$b['Total'], 2) ?></strong></p>
    <p style="color:var(--success);">Paid: Rs <?= number_format((float)$b['AmountPaid'], 2) ?></p>
    <?php $balance = (float)$b['Total'] - (float)$b['AmountPaid']; ?>
    <?php if ($balance > 0): ?>
      <p style="color:var(--danger);">Balance: Rs <?= number_format($balance, 2) ?></p>
    <?php endif; ?>
  </div>

  <?php if (!empty($payments)): ?>
    <h4 style="font-size:0.9rem;font-weight:600;margin-top:1rem;">Payments</h4>
    <table class="data-table">
      <thead><tr><th>Method</th><th>Amount</th><th>Reference</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $pmt): ?>
          <tr>
            <td><?= escape($pmt['PaymentMethod']) ?></td>
            <td>Rs <?= number_format((float)$pmt['Amount'], 2) ?></td>
            <td><?= escape($pmt['Reference'] ?? '-') ?></td>
            <td><?= date('d/m/Y H:i', strtotime($pmt['ReceivedAt'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($b['Notes']): ?>
    <p style="margin-top:0.75rem;font-size:0.85rem;color:var(--text-secondary);"><?= escape($b['Notes']) ?></p>
  <?php endif; ?>
</div>
