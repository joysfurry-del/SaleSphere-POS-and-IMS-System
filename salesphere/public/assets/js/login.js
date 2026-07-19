(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    const username = document.getElementById('username');
    const password = document.getElementById('password');

    if (!form) return;

    form.addEventListener('submit', function (e) {
      let valid = true;

      if (!username.value.trim()) {
        username.classList.add('border-[#EF4444]');
        valid = false;
      } else {
        username.classList.remove('border-[#EF4444]');
      }

      if (!password.value) {
        password.classList.add('border-[#EF4444]');
        valid = false;
      } else {
        password.classList.remove('border-[#EF4444]');
      }

      if (!valid) {
        e.preventDefault();
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Signing in...';
      }
    });

    [username, password].forEach(function (el) {
      if (el) {
        el.addEventListener('input', function () {
          this.classList.remove('border-[#EF4444]');
        });
      }
    });
  });
})();
