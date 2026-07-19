const API_BASE = 'http://localhost/salesphere/public/api/';
const TAX_RATE = 0.06;

// get or create cart token
function getCartToken() {
    let token = localStorage.getItem('cart_token');
    if (!token) {
        token = crypto.randomUUID();
        localStorage.setItem('cart_token', token);
    }
    return token;
}

// GET request to API
async function apiGet(endpoint, params = {}) {
    const url = new URL(API_BASE + endpoint);
    Object.entries(params).forEach(([key, val]) => {
        if (val !== undefined && val !== null && val !== '') {
            url.searchParams.set(key, val);
        }
    });
    const res = await fetch(url.toString());
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

// POST request to API
async function apiPost(endpoint, body = {}) {
    const res = await fetch(API_BASE + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

// show popup notification
function showToast(message, type) {
    if (!type) type = 'success';
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function () {
        toast.classList.add('toast-hide');
        setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
    }, 3000);
}

// show loading spinner
function showSpinner(container) {
    var overlay = document.createElement('div');
    overlay.className = 'spinner-overlay';
    overlay.setAttribute('data-spinner', '');
    var spin = document.createElement('div');
    spin.className = 'spinner';
    overlay.appendChild(spin);
    container.appendChild(overlay);
}

// hide loading spinner
function hideSpinner(container) {
    var spinner = container.querySelector('[data-spinner]');
    if (spinner) spinner.remove();
}

// update cart count on navbar
async function updateCartBadge() {
    try {
        var res = await apiGet('cart.php', { cart_token: getCartToken() });
        var items = res.data && res.data.items ? res.data.items : [];
        var count = 0;
        for (var i = 0; i < items.length; i++) {
            count += items[i].Quantity || 0;
        }
        var badges = document.querySelectorAll('.cart-count');
        for (var j = 0; j < badges.length; j++) {
            badges[j].textContent = count;
            badges[j].style.display = count > 0 ? 'inline-flex' : 'none';
        }
    } catch (_) {}
}

// format price with Rs symbol
function formatPrice(n) {
    return 'Rs ' + Number(n).toFixed(2);
}

// escape HTML for safe output
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// get auth token from storage
function getAuthToken() {
    return localStorage.getItem('auth_token');
}

// check if user logged in
function isLoggedIn() {
    return !!getAuthToken();
}

// save auth data to local storage
function setAuth(data) {
    localStorage.setItem('auth_token', data.token || '');
    localStorage.setItem('customer_id', data.customer_id || '');
    localStorage.setItem('customer_name', data.name || '');
    localStorage.setItem('customer_email', data.email || '');
}

// remove auth data on logout
function clearAuth() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('customer_id');
    localStorage.removeItem('customer_name');
    localStorage.removeItem('customer_email');
}

// redirect to login if not logged in
function requireAuth() {
    if (!isLoggedIn()) {
        var redirect = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = 'auth.html?redirect=' + redirect;
    }
}

// authenticated POST request
async function apiAuthPost(endpoint, body) {
    var token = getAuthToken();
    if (!token) throw new Error('Not authenticated');
    var res = await fetch(API_BASE + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(body || {})
    });
    if (!res.ok) {
        var errMsg = 'HTTP ' + res.status;
        try { var errData = await res.json(); errMsg = errData.message || errMsg; } catch (_) {}
        var err = new Error(errMsg);
        err.status = res.status;
        throw err;
    }
    var data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

// authenticated PUT request
async function apiAuthPut(endpoint, body) {
    var token = getAuthToken();
    if (!token) throw new Error('Not authenticated');
    var res = await fetch(API_BASE + endpoint, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(body || {})
    });
    if (!res.ok) {
        var errMsg = 'HTTP ' + res.status;
        try { var errData = await res.json(); errMsg = errData.message || errMsg; } catch (_) {}
        var err = new Error(errMsg);
        err.status = res.status;
        throw err;
    }
    var data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

// authenticated GET request
async function apiAuthGet(endpoint, params) {
    var token = getAuthToken();
    if (!token) throw new Error('Not authenticated');
    var url = new URL(API_BASE + endpoint);
    if (params) {
        Object.keys(params).forEach(function (k) {
            if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
                url.searchParams.set(k, params[k]);
            }
        });
    }
    var res = await fetch(url.toString(), {
        headers: { 'Authorization': 'Bearer ' + token }
    });
    if (!res.ok) {
        var errMsg = 'HTTP ' + res.status;
        try { var errData = await res.json(); errMsg = errData.message || errMsg; } catch (_) {}
        var err = new Error(errMsg);
        err.status = res.status;
        throw err;
    }
    var data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

// show login link or user dropdown
function initAuthUI() {
    var container = document.getElementById('auth-container');
    if (!container) return;
    if (isLoggedIn()) {
        var name = localStorage.getItem('customer_name') || 'Account';
        container.innerHTML =
            '<div class="auth-dropdown">' +
            '<span class="auth-user-btn">' + escapeHtml(name) + ' <span class="arrow">&#9660;</span></span>' +
            '<div class="auth-dropdown-menu">' +
            '<a href="profile.html">My Profile</a>' +
            '<a href="order-history.html">Order History</a>' +
            '<a href="feedback.html">Submit Feedback</a>' +
            '<a href="#" class="logout-btn">Logout</a>' +
            '</div></div>';
        var logoutBtn = container.querySelector('.logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearAuth();
                window.location.reload();
            });
        }
    } else {
        container.innerHTML = '<a href="auth.html" class="btn btn-sm btn-outline">Sign In</a>';
    }
}
