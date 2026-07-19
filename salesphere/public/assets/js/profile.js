(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatarInput');
    const avatarImg = document.getElementById('avatarImg');
    const avatarInitials = document.getElementById('avatarInitials');
    const avatarForm = document.getElementById('avatarForm');

    if (avatarInput && avatarForm) {
      avatarInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
          if (avatarImg) {
            avatarImg.src = e.target.result;
            avatarImg.style.display = 'block';
          }
          if (avatarInitials) avatarInitials.style.display = 'none';
        };
        reader.readAsDataURL(file);

        const formData = new FormData(avatarForm);
        const btn = avatarForm.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Uploading...';
        }

        fetch(avatarForm.getAttribute('action'), {
          method: 'POST',
          body: formData,
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.success) {
              showToast('success', data.message);
              if (avatarImg && data.url) avatarImg.src = data.url;
              updateNavbarAvatar(data.url);
            } else {
              showToast('error', data.message);
              if (avatarImg) avatarImg.src = avatarImg.dataset.original;
            }
          })
          .catch(function () {
            showToast('error', 'Upload failed. Please try again.');
          })
          .finally(function () {
            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Change Photo';
            }
          });
      });
    }

    const infoForm = document.getElementById('infoForm');
    if (infoForm) {
      infoForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(infoForm);
        const btn = infoForm.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Saving...';
        }

        fetch(infoForm.getAttribute('action'), {
          method: 'POST',
          body: formData,
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            showToast(data.success ? 'success' : 'error', data.message);
          })
          .catch(function () {
            showToast('error', 'Something went wrong.');
          })
          .finally(function () {
            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Save Changes';
            }
          });
      });
    }

    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
      passwordForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const newPass = document.getElementById('new_password');
        const confirmPass = document.getElementById('confirm_password');

        if (newPass && confirmPass && newPass.value !== confirmPass.value) {
          showToast('error', 'New passwords do not match.');
          return;
        }

        if (newPass && newPass.value.length < 6) {
          showToast('error', 'Password must be at least 6 characters.');
          return;
        }

        const formData = new FormData(passwordForm);
        const btn = passwordForm.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Changing...';
        }

        fetch(passwordForm.getAttribute('action'), {
          method: 'POST',
          body: formData,
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            showToast(data.success ? 'success' : 'error', data.message);
            if (data.success) passwordForm.reset();
          })
          .catch(function () {
            showToast('error', 'Something went wrong.');
          })
          .finally(function () {
            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Change Password';
            }
          });
      });
    }

    const editToggle = document.getElementById('editToggle');
    const viewMode = document.getElementById('viewMode');
    const editMode = document.getElementById('editMode');

    if (editToggle && viewMode && editMode) {
      editToggle.addEventListener('click', function () {
        viewMode.classList.add('hidden');
        editMode.classList.remove('hidden');
      });

      const cancelEdit = document.getElementById('cancelEdit');
      if (cancelEdit) {
        cancelEdit.addEventListener('click', function () {
          editMode.classList.add('hidden');
          viewMode.classList.remove('hidden');
        });
      }
    }
  });

  function updateNavbarAvatar(url) {
    const navAvatar = document.getElementById('navAvatar');
    const navInitials = document.getElementById('navInitials');
    if (navAvatar) {
      navAvatar.src = url;
      navAvatar.style.display = 'block';
    }
    if (navInitials) navInitials.style.display = 'none';
  }

  function showToast(type, message) {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.className = 'toast fixed top-4 right-4 z-[60] px-5 py-3 rounded-lg text-sm font-medium shadow-lg transition-all duration-300 translate-y-0 opacity-0';
    toast.style.backgroundColor = type === 'success' ? '#22C55E' : '#EF4444';
    toast.style.color = '#fff';
    toast.style.border = '1px solid ' + (type === 'success' ? '#16A34A' : '#DC2626');
    toast.textContent = message;

    document.body.appendChild(toast);
    requestAnimationFrame(function () {
      toast.classList.remove('opacity-0');
      toast.style.opacity = '1';
    });

    setTimeout(function () {
      toast.style.opacity = '0';
      setTimeout(function () { toast.remove(); }, 300);
    }, 3500);
  }
  window.showToast = showToast;
})();
