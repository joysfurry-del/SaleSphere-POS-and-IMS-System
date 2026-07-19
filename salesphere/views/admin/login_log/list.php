<?php
$logs = $logs ?? [];
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
?>
<div style="max-width:1100px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Login Log</h2>
      <span style="font-size:0.8rem;color:var(--text-secondary);"><?= count($logs) ?> entries</span>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Timestamp</th>
          <th>User</th>
          <th>Action</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <!-- show each login attempt with success or fail badge -->
        <?php $i = (($page - 1) * 20) + 1; foreach ($logs as $log): ?>
          <?php $isSuccess = stripos($log['ActionDescription'], 'Successful') !== false; ?>
          <tr>
            <td style="color:var(--text-secondary);"><?= $i++ ?></td>
            <td style="white-space:nowrap;"><?= date('d/m/Y H:i:s', strtotime($log['CreatedAt'])) ?></td>
            <td>
              <?php if ($log['FullName']): ?>
                <strong><?= escape($log['FullName']) ?></strong>
                <span style="font-size:0.75rem;color:var(--text-secondary);">(@<?= escape($log['Username']) ?>)</span>
              <?php elseif ($log['Username']): ?>
                <?= escape($log['Username']) ?>
              <?php else: ?>
                <span style="color:var(--text-secondary);">Unknown</span>
              <?php endif; ?>
            </td>
            <td><?= escape($log['ActionDescription']) ?></td>
            <td>
              <span class="role-badge" style="background:<?= $isSuccess ? 'var(--success)' : 'var(--danger)' ?>;color:#fff">
                <?= $isSuccess ? 'Success' : 'Failed' ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
          <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-secondary);">No login records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <!-- pagination links for login log -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:0.5rem;padding:1rem;">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="btn-secondary-sm" style="text-decoration:none;">Previous</a>
      <?php endif; ?>
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?= $p ?>" class="btn-secondary-sm" style="text-decoration:none;<?= $p === $page ? 'background:var(--primary);color:#fff;border-color:var(--primary);' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="btn-secondary-sm" style="text-decoration:none;">Next</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
