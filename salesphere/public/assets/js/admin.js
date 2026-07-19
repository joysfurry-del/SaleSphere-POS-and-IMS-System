(function () {
  document.querySelectorAll('.modal-wrap').forEach(function (el) {
    el.style.display = '';
  });

  function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('hidden');
  }

  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('hidden');
  }

  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
      e.target.closest('.modal-wrap').classList.add('hidden');
    }
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.classList.contains('ajax-form')) return;
    e.preventDefault();

    var btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving...'; }

    var formData = new FormData(form);

    fetch(form.getAttribute('action'), { method: 'POST', body: formData })
      .then(function (r) { return r.text(); })
      .then(function (text) {
        try {
          var data = JSON.parse(text);
          window.showToast(data.success ? 'success' : 'error', data.message);
          if (data.success) {
            var modal = form.closest('.modal-wrap');
            if (modal) modal.classList.add('hidden');
            setTimeout(function () { location.reload(); }, 600);
          }
        } catch (e) {
          console.error('JSON parse error:', e, 'Response:', text);
          window.showToast('error', 'Server error: ' + text.substring(0, 150));
        }
      })
      .catch(function (err) { console.error('Fetch error:', err); window.showToast('error', 'Request failed.'); })
      .finally(function () {
        if (btn) { btn.disabled = false; btn.textContent = form.dataset.btnText || 'Save'; }
      });
  });

  document.addEventListener('click', function (e) {
    var delBtn = e.target.closest('.delete-btn');
    if (!delBtn) return;
    e.preventDefault();

    if (!confirm('Are you sure you want to delete this item? This cannot be undone.')) return;

    var formData = new FormData();
    formData.append('csrf_token', delBtn.dataset.csrf);
    formData.append('action', 'delete');
    formData.append(delBtn.dataset.field || 'id', delBtn.dataset.id);

    var btn = delBtn;
    btn.disabled = true;
    btn.textContent = '...';

    fetch(delBtn.dataset.action, { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        window.showToast(data.success ? 'success' : 'error', data.message);
        if (data.success) setTimeout(function () { location.reload(); }, 600);
      })
      .catch(function (err) { console.error('Delete error:', err); window.showToast('error', 'Delete failed.'); })
      .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Delete';
      });
  });

  document.addEventListener('change', function (e) {
    if (!e.target.closest('.toggle-switch') || e.target.tagName !== 'INPUT') return;
    var toggle = e.target;
    var formData = new FormData();
    formData.append('csrf_token', toggle.dataset.csrf);
    formData.append('product_id', toggle.value);

    fetch(toggle.dataset.action, { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) window.showToast('success', data.message);
        else window.showToast('error', data.message);
      })
      .catch(function (err) { console.error('Toggle error:', err); window.showToast('error', 'Toggle failed.'); });
  });

  window.openModal = openModal;
  window.closeModal = closeModal;
})();
