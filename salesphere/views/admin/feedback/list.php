<?php
$feedback = $feedback ?? [];
$csrf = csrf_token();
?>
<div style="max-width:1200px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <h2>Customer Feedback</h2>
      <button class="btn-primary" onclick="openModal('feedbackModal')" style="font-size:0.8rem;padding:0.45rem 1rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Feedback
      </button>
    </div>
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
      <select id="refTypeFilter" class="select-field" style="width:auto;min-width:180px;">
        <option value="">All Types</option>
        <option value="Product">Product</option>
        <option value="Branch">Branch</option>
        <option value="Order">Order</option>
        <option value="OnlineOrder">Online Order</option>
      </select>
      <input type="text" id="searchInput" class="input-field" placeholder="Search title, comment, customer..." style="flex:1;max-width:300px;">
    </div>
    <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:950px;">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Customer</th>
          <th>Ref</th>
          <th style="width:70px">Rating</th>
          <th>Title</th>
          <th style="width:80px">Approved</th>
          <th style="width:80px">Featured</th>
          <th style="width:90px">Date</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- list feedback with rating stars, approved/featured toggles, view/delete -->
        <?php foreach ($feedback as $f): ?>
          <tr>
            <td style="color:var(--text-secondary);white-space:nowrap;">#<?= $f['FeedbackID'] ?></td>
            <td><?= escape($f['CustomerName'] ?? $f['UserName'] ?? 'Anonymous') ?></td>
            <td><span class="role-badge" style="font-size:0.7rem;white-space:nowrap;"><?= escape($f['ReferenceType']) ?> #<?= $f['ReferenceID'] ?></span></td>
            <td style="white-space:nowrap;">
              <span style="color:var(--warning);font-weight:600;">
                <?php for ($i = 0; $i < (int)$f['Rating']; $i++): ?>&#9733;<?php endfor; ?>
              </span>
            </td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= escape($f['Title'] ?? '(no title)') ?>
            </td>
            <td>
              <label class="toggle-switch">
                <input type="checkbox" <?= $f['IsApproved'] ? 'checked' : '' ?> data-id="<?= $f['FeedbackID'] ?>" data-csrf="<?= $csrf ?>" data-action="feedback.php" data-field="feedback_id" data-toggle="approved">
                <span class="toggle-slider"></span>
              </label>
            </td>
            <td>
              <label class="toggle-switch">
                <input type="checkbox" <?= $f['IsFeatured'] ? 'checked' : '' ?> data-id="<?= $f['FeedbackID'] ?>" data-csrf="<?= $csrf ?>" data-action="feedback.php" data-field="feedback_id" data-toggle="featured">
                <span class="toggle-slider"></span>
              </label>
            </td>
            <td style="color:var(--text-secondary);font-size:0.8rem;white-space:nowrap;"><?= date('d/m/Y', strtotime($f['CreatedAt'])) ?></td>
            <td style="white-space:nowrap;">
              <div style="display:flex;gap:0.4rem;align-items:center;">
                <button class="btn-cyan" onclick="viewFeedback(<?= $f['FeedbackID'] ?>)">View</button>
                <button class="delete-btn btn-danger-sm" data-id="<?= $f['FeedbackID'] ?>" data-field="feedback_id" data-csrf="<?= $csrf ?>" data-action="feedback.php">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($feedback)): ?>
          <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-secondary)">No feedback found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-wrap hidden" id="feedbackModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:600px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('feedbackModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;">Add Feedback</h3>
    <!-- add feedback form with type, rating, title, comment, pros/cons -->
    <form id="feedbackForm" class="ajax-form" action="feedback.php" method="POST" data-btn-text="Submit">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="create">
      <div class="form-grid">
        <div class="form-group">
          <label for="fb_ref_type">Reference Type *</label>
          <select id="fb_ref_type" name="reference_type" class="select-field" required>
            <option value="">Select...</option>
            <option value="Product">Product</option>
            <option value="Branch">Branch</option>
            <option value="Order">Order</option>
            <option value="OnlineOrder">Online Order</option>
          </select>
        </div>
        <div class="form-group">
          <label for="fb_ref_id">Reference ID *</label>
          <input type="number" id="fb_ref_id" name="reference_id" class="input-field" required value="0">
        </div>
        <div class="form-group">
          <label for="fb_rating">Rating *</label>
          <select id="fb_rating" name="rating" class="select-field" required>
            <option value="">Select...</option>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Good</option>
            <option value="3">3 - Average</option>
            <option value="2">2 - Poor</option>
            <option value="1">1 - Terrible</option>
          </select>
        </div>
        <div class="form-group">
          <label for="fb_title">Title</label>
          <input type="text" id="fb_title" name="title" class="input-field" placeholder="Summary of feedback">
        </div>
        <div class="form-group full">
          <label for="fb_comment">Comment</label>
          <textarea id="fb_comment" name="comment" class="input-field" rows="3" placeholder="Detailed feedback..."></textarea>
        </div>
        <div class="form-group">
          <label for="fb_pros">Pros</label>
          <textarea id="fb_pros" name="pros" class="input-field" rows="2" placeholder="What did you like?"></textarea>
        </div>
        <div class="form-group">
          <label for="fb_cons">Cons</label>
          <textarea id="fb_cons" name="cons" class="input-field" rows="2" placeholder="What could be improved?"></textarea>
        </div>
      </div>
      <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
        <button type="submit" class="btn-primary" style="font-size:0.85rem;">Submit</button>
        <button type="button" class="btn-secondary" onclick="closeModal('feedbackModal')" style="font-size:0.85rem;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-wrap hidden" id="viewFeedbackModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
  <div style="position:relative;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:100%;max-width:600px;margin:1rem;padding:2rem;max-height:90vh;overflow-y:auto;">
    <button type="button" onclick="closeModal('viewFeedbackModal')" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:1.3rem;">&times;</button>
    <div id="feedbackDetailContent"></div>
    <form id="respondForm" class="ajax-form" action="feedback.php" method="POST" data-btn-text="Send" style="margin-top:1rem;">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="respond">
      <input type="hidden" name="feedback_id" id="respondFeedbackId" value="0">
      <div class="form-group">
        <label for="fb_response">Admin Response</label>
        <textarea id="fb_response" name="admin_response" class="input-field" rows="3" placeholder="Write your response..."></textarea>
      </div>
      <button type="submit" class="btn-primary" style="font-size:0.85rem;">Send Response</button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var refFilter = document.getElementById('refTypeFilter');
    var searchInput = document.getElementById('searchInput');
    if (refFilter) refFilter.addEventListener('change', filterTable);
    if (searchInput) searchInput.addEventListener('input', filterTable);

    document.addEventListener('change', function(e) {
        var toggle = e.target.closest('.toggle-switch')?.querySelector('input[data-toggle]');
        if (!toggle) return;
        var action = toggle.dataset.toggle === 'approved' ? 'toggle_approved' : 'toggle_featured';
        var formData = new FormData();
        formData.append('csrf_token', toggle.dataset.csrf);
        formData.append('feedback_id', toggle.dataset.id);
        formData.append('action', action);
        fetch('feedback.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(d) { showToast(d.success ? 'success' : 'error', d.message); })
            .catch(function() { showToast('error', 'Toggle failed.'); });
    });
});

// filter feedback by type and search
function filterTable() {
    var refType = document.getElementById('refTypeFilter').value;
    var search = document.getElementById('searchInput').value.toLowerCase();
    var rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 9) return;
        var matchRef = !refType || cells[2].textContent.trim().startsWith(refType);
        var matchSearch = !search || cells[1].textContent.toLowerCase().includes(search) || cells[4].textContent.toLowerCase().includes(search);
        row.style.display = (matchRef && matchSearch) ? '' : 'none';
    });
}

// load feedback detail and show in modal with admin response form
function viewFeedback(id) {
    fetch('feedback.php?action=get&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.error) { showToast('error', d.error); return; }
            var stars = '';
            for (var i = 0; i < d.Rating; i++) stars += '&#9733;';
            document.getElementById('feedbackDetailContent').innerHTML = `
                <div style="margin-bottom:1rem;">
                    <h3 style="font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;">${d.Title || '(No Title)'}</h3>
                    <div style="font-size:1.2rem;color:var(--warning);">${stars}</div>
                    <p style="font-size:0.8rem;color:var(--text-secondary);margin-top:0.25rem;">${d.CustomerName || d.UserName || 'Anonymous'} &middot; ${d.ReferenceType} #${d.ReferenceID} &middot; ${d.CreatedAt ? d.CreatedAt.substring(0, 10) : ''}</p>
                </div>
                ${d.Comment ? '<p style="margin-bottom:0.75rem;">' + d.Comment + '</p>' : ''}
                ${d.Pros ? '<div style="margin-bottom:0.5rem;"><strong style="color:var(--success);font-size:0.85rem;">Pros:</strong><p style="font-size:0.85rem;color:var(--text-secondary);margin-top:0.2rem;">' + d.Pros + '</p></div>' : ''}
                ${d.Cons ? '<div style="margin-bottom:0.5rem;"><strong style="color:var(--danger);font-size:0.85rem;">Cons:</strong><p style="font-size:0.85rem;color:var(--text-secondary);margin-top:0.2rem;">' + d.Cons + '</p></div>' : ''}
                ${d.AdminResponse ? '<div style="margin-top:1rem;padding:1rem;background:rgba(0,229,255,0.05);border:1px solid var(--border);border-radius:8px;"><strong style="font-size:0.85rem;color:var(--secondary);">Admin Response:</strong><p style="font-size:0.85rem;margin-top:0.25rem;">' + d.AdminResponse + '</p></div>' : ''}
                ${d.HelpfulCount !== undefined ? '<p style="font-size:0.8rem;color:var(--text-secondary);margin-top:0.5rem;">' + d.HelpfulCount + ' found helpful &middot; ' + d.ReportedCount + ' reported</p>' : ''}
            `;
            document.getElementById('respondFeedbackId').value = id;document.getElementById('respondForm').querySelector('textarea').value = '';
            openModal('viewFeedbackModal');
        })
        .catch(function() { showToast('error', 'Failed to load feedback.'); });
}
</script>
