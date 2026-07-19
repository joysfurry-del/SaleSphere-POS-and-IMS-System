<?php
$csrf = csrf_token();
$branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
$cashierName = escape($_SESSION['user']['Username'] ?? 'Cashier');
?>
<div style="height:calc(100vh - 64px - 3rem);display:flex;gap:1rem;margin:-1.5rem;padding:1.5rem;">
  <div style="flex:1;display:flex;flex-direction:column;gap:0.75rem;min-width:0;">
    <div style="display:flex;gap:0.5rem;align-items:center;">
      <div style="flex:1;position:relative;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--text-secondary);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="posSearch" class="input-field" style="padding-left:2.2rem;font-size:0.9rem;" placeholder="Search products by name or category..." autofocus>
      </div>
      <span id="posCount" style="font-size:0.8rem;color:var(--text-secondary);white-space:nowrap;">0 items</span>
    </div>
    <!-- product grid - filled by js after fetching products -->
    <div id="posGrid" style="flex:1;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:0.5rem;align-content:start;">
      <div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-secondary);font-size:0.9rem;">Select a branch to see products</div>
    </div>
  </div>

  <!-- right panel: cart, totals, payment, place order -->
  <div class="card" style="width:360px;flex-shrink:0;display:flex;flex-direction:column;padding:1rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
      <h3 style="font-size:1rem;font-weight:700;">Current Order</h3>
      <span id="cartCount" style="font-size:0.8rem;color:var(--text-secondary);">0 items</span>
    </div>
    <div id="cartItems" style="flex:1;overflow-y:auto;margin:0 -0.25rem;padding:0 0.25rem;">
      <div style="text-align:center;padding:2rem 0;color:var(--text-secondary);font-size:0.85rem;">Cart is empty. Click products to add.</div>
    </div>

    <div style="border-top:1px solid var(--border);padding-top:0.75rem;margin-top:0.75rem;">
      <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--text-secondary);margin-bottom:0.25rem;">
        <span>Subtotal</span><span id="posSubtotal">Rs 0.00</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--text-secondary);margin-bottom:0.25rem;">
        <span>Tax (<span id="posTaxRate">0</span>%)</span><span id="posTax">Rs 0.00</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:1.3rem;font-weight:900;color:var(--primary);margin-bottom:0.5rem;padding:0.5rem;background:var(--bg-hover);border-radius:8px;">
        <span>Total Due</span><span id="posTotal">Rs 0.00</span>
      </div>

      <div class="form-group" style="margin-bottom:0.5rem;">
        <label for="posCustomer" style="font-size:0.75rem;">Customer Name (optional)</label>
        <input type="text" id="posCustomer" class="input-field" style="padding:0.4rem 0.7rem;font-size:0.8rem;" placeholder="Walk-in">
      </div>

      <div style="display:flex;gap:0.4rem;margin-bottom:0.5rem;">
        <button class="pay-method active" data-method="Cash" style="flex:1;padding:0.4rem;font-size:0.75rem;font-weight:600;border:2px solid var(--primary);background:var(--primary);color:#fff;border-radius:6px;cursor:pointer;">Cash</button>
        <button class="pay-method" data-method="Card" style="flex:1;padding:0.4rem;font-size:0.75rem;font-weight:600;border:2px solid var(--border);background:transparent;color:var(--text-secondary);border-radius:6px;cursor:pointer;">Card</button>
        <button class="pay-method" data-method="QR Pay" style="flex:1;padding:0.4rem;font-size:0.75rem;font-weight:600;border:2px solid var(--border);background:transparent;color:var(--text-secondary);border-radius:6px;cursor:pointer;">QR Pay</button>
      </div>

      <div id="paidRow" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:0.25rem;">
        <label for="posPaid" style="font-size:0.75rem;color:var(--text-secondary);white-space:nowrap;">Amount Received</label>
        <input type="number" id="posPaid" class="input-field" style="padding:0.4rem 0.7rem;font-size:0.9rem;font-weight:700;width:100%;" step="0.01" min="0" placeholder="0.00" oninput="calcChange()">
      </div>
      <div id="changeRow" style="display:flex;justify-content:space-between;font-size:0.9rem;font-weight:600;margin-bottom:0.75rem;padding:0.3rem 0.5rem;border-radius:6px;display:none;">
        <span>Change Due</span><span id="posChange">Rs 0.00</span>
      </div>

      <button id="placeOrderBtn" class="btn-primary" style="width:100%;justify-content:center;padding:0.7rem;font-size:0.95rem;" disabled>
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        Place Order
      </button>
    </div>
  </div>
</div>

<div id="receiptModal" class="modal-wrap hidden" style="position:fixed;inset:0;z-index:100;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.6);"></div>
  <div style="position:relative;background:#fff;color:#111;border-radius:12px;width:320px;margin:2rem auto;padding:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
    <div id="receiptContent" style="padding:1.5rem 1.25rem 0.5rem;"></div>
    <div style="display:flex;gap:0.5rem;padding:0.75rem 1.25rem 1.25rem;border-top:1px solid #ddd;">
      <button onclick="printReceipt()" class="btn-primary" style="flex:1;justify-content:center;font-size:0.8rem;background:#FF5722;color:#fff;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        Print Receipt
      </button>
      <button onclick="closeModal('receiptModal')" class="btn-secondary" style="flex:1;justify-content:center;font-size:0.8rem;">Close</button>
    </div>
  </div>
</div>

<script>
var cart = [];
var selectedMethod = 'Cash';
var branchId = <?= $branchId ?>;
var lastOrderData = null;

// fetch products from server and render grid
function loadProducts(search) {
    if (!branchId) return;
    var url = 'index.php?action=products&search=' + encodeURIComponent(search || '');
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var grid = document.getElementById('posGrid');
            grid.innerHTML = '';
            document.getElementById('posCount').textContent = data.length + ' items';
            if (data.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-secondary);font-size:0.9rem;">No products found.</div>';
                return;
            }
            data.forEach(function(p) {
                var card = document.createElement('button');
                card.className = 'product-card';
                card.setAttribute('type', 'button');
                card.style.cssText = 'display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.25rem;padding:0.75rem;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;cursor:pointer;transition:all 0.15s;font-family:inherit;text-align:center;';
                card.innerHTML = '<div style="font-size:0.85rem;font-weight:700;color:var(--text-primary);line-height:1.2;">' + p.ProductName + '</div>'
                    + '<div style="font-size:0.7rem;color:var(--text-secondary);">' + (p.CategoryName || '') + '</div>'
                    + '<div style="font-size:1rem;font-weight:800;color:var(--primary);">Rs ' + parseFloat(p.Price).toFixed(2) + '</div>'
                    + '<div style="font-size:0.65rem;color:var(--text-secondary);">Stock: ' + p.AvailableQty + '</div>';
                card.addEventListener('click', function() { addToCart(p); });
                card.addEventListener('mouseenter', function() { this.style.borderColor = 'var(--primary)'; this.style.background = 'var(--bg-hover)'; });
                card.addEventListener('mouseleave', function() { this.style.borderColor = 'var(--border)'; this.style.background = 'var(--bg-card)'; });
                grid.appendChild(card);
            });
        });
}

// add product to cart; increase qty if already in cart
function addToCart(p) {
    var existing = cart.find(function(i) { return i.ProductID == p.ProductID; });
    if (existing) {
        if (existing.Quantity >= p.AvailableQty) { showToast('error', 'Not enough stock.'); return; }
        existing.Quantity++;
        existing.LineTotal = existing.Quantity * existing.Price;
    } else {
        cart.push({
            ProductID: p.ProductID,
            ProductName: p.ProductName,
            Price: parseFloat(p.Price),
            Quantity: 1,
            LineTotal: parseFloat(p.Price),
            AvailableQty: p.AvailableQty
        });
    }
    renderCart();
}

function renderCart() {
    var container = document.getElementById('cartItems');
    if (cart.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:2rem 0;color:var(--text-secondary);font-size:0.85rem;">Cart is empty. Click products to add.</div>';
        document.getElementById('cartCount').textContent = '0 items';
        document.getElementById('placeOrderBtn').disabled = true;
        updateTotals();
        return;
    }
    document.getElementById('cartCount').textContent = cart.length + ' items';
    document.getElementById('placeOrderBtn').disabled = false;

    var html = '';
    cart.forEach(function(item, idx) {
        html += '<div style="display:flex;align-items:center;gap:0.4rem;padding:0.5rem 0.25rem;border-bottom:1px solid var(--border);">'
            + '<div style="flex:1;min-width:0;">'
            + '<div style="font-size:0.8rem;font-weight:600;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + item.ProductName + '</div>'
            + '<div style="font-size:0.7rem;color:var(--text-secondary);">Rs ' + item.Price.toFixed(2) + ' ea</div>'
            + '</div>'
            + '<div style="display:flex;align-items:center;gap:0.25rem;">'
            + '<button class="qty-btn" onclick="changeQty(' + idx + ',-1)" style="width:24px;height:24px;border:1px solid var(--border);border-radius:4px;background:transparent;color:var(--text-primary);cursor:pointer;font-weight:700;font-size:0.9rem;">-</button>'
            + '<span style="width:28px;text-align:center;font-weight:700;font-size:0.85rem;">' + item.Quantity + '</span>'
            + '<button class="qty-btn" onclick="changeQty(' + idx + ',1)" style="width:24px;height:24px;border:1px solid var(--border);border-radius:4px;background:transparent;color:var(--text-primary);cursor:pointer;font-weight:700;font-size:0.9rem;">+</button>'
            + '</div>'
            + '<div style="width:60px;text-align:right;font-weight:700;font-size:0.85rem;">Rs ' + item.LineTotal.toFixed(2) + '</div>'
            + '<button onclick="removeFromCart(' + idx + ')" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:1rem;padding:0.15rem;">&times;</button>'
            + '</div>';
    });
    container.innerHTML = html;
    updateTotals();
}

function changeQty(idx, delta) {
    var item = cart[idx];
    if (!item) return;
    var newQty = item.Quantity + delta;
    if (newQty <= 0) { removeFromCart(idx); return; }
    if (newQty > item.AvailableQty) { showToast('error', 'Not enough stock.'); return; }
    item.Quantity = newQty;
    item.LineTotal = newQty * item.Price;
    renderCart();
}

function removeFromCart(idx) {
    cart.splice(idx, 1);
    renderCart();
}

function updateTotals() {
    var subtotal = cart.reduce(function(sum, i) { return sum + i.LineTotal; }, 0);
    document.getElementById('posSubtotal').textContent = 'Rs ' + subtotal.toFixed(2);
    var taxRateEl = document.getElementById('posTaxRate');
    loadTaxRate(function(rate) {
        taxRateEl.textContent = rate;
        var tax = subtotal * rate / 100;
        var total = subtotal + tax;
        document.getElementById('posTax').textContent = 'Rs ' + tax.toFixed(2);
        document.getElementById('posTotal').textContent = 'Rs ' + total.toFixed(2);
        calcChange();
    });
}

function calcChange() {
    var total = parseFloat(document.getElementById('posTotal').textContent.replace('Rs ', '')) || 0;
    var received = parseFloat(document.getElementById('posPaid').value) || 0;
    var change = received - total;
    var changeRow = document.getElementById('changeRow');
    if (received > 0) {
        changeRow.style.display = 'flex';
        document.getElementById('posChange').textContent = 'Rs ' + (change >= 0 ? change : 0).toFixed(2);
        changeRow.style.color = change >= 0 ? 'var(--success)' : 'var(--danger)';
    } else {
        changeRow.style.display = 'none';
    }
}

function loadTaxRate(callback) {
    if (!branchId) { callback(0); return; }
    fetch('index.php?action=tax_rate')
        .then(function(r) { return r.json(); })
        .then(function(d) { callback(parseFloat(d.rate) || 0); })
        .catch(function() { callback(0); });
}

    // payment method buttons: Cash, Card, QR Pay
    var payBtns = document.querySelectorAll('.pay-method');
payBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
        payBtns.forEach(function(b) {
            b.style.borderColor = 'var(--border)';
            b.style.background = 'transparent';
            b.style.color = 'var(--text-secondary)';
        });
        this.style.borderColor = 'var(--primary)';
        this.style.background = 'var(--primary)';
        this.style.color = '#fff';
        selectedMethod = this.dataset.method;
    });
});

// send order to server, show receipt on success
document.getElementById('placeOrderBtn').addEventListener('click', function() {
    if (cart.length === 0) return;
    var total = parseFloat(document.getElementById('posTotal').textContent.replace('Rs ', ''));
    var paid = parseFloat(document.getElementById('posPaid').value || '0');
    if (paid < total) {
        showToast('error', 'Amount received (Rs ' + paid.toFixed(2) + ') is less than total due (Rs ' + total.toFixed(2) + ').');
        return;
    }

    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Processing...';

    var items = cart.map(function(i) { return { ProductID: i.ProductID, ProductName: i.ProductName, Price: i.Price, Quantity: i.Quantity, LineTotal: i.LineTotal }; });
    var fd = new FormData();
    fd.append('csrf_token', '<?= $csrf ?>');
    fd.append('items', JSON.stringify(items));
    fd.append('customer_name', document.getElementById('posCustomer').value);
    fd.append('payment_method', selectedMethod);
    fd.append('amount_paid', paid);

    fetch('index.php?action=place_order', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                lastOrderData = data;
                showReceipt(data.order, data.items);
                cart = [];
                renderCart();
                document.getElementById('posPaid').value = '';
                document.getElementById('posCustomer').value = '';
            } else {
                showToast('error', data.message);
            }
        })
        .catch(function() { showToast('error', 'Order failed.'); })
        .finally(function() {
            btn.disabled = false;
            btn.textContent = 'Place Order';
        });
});

// build and show receipt modal after successful order
function showReceipt(order, items) {
    var dt = order.CreatedAt ? order.CreatedAt.substring(0,16).replace('T',' ') : new Date().toLocaleString();
    var html = '';

    // store header
    html += '<div style="text-align:center;margin-bottom:10px;">'
        + '<div style="font-size:20px;font-weight:900;color:#FF5722;letter-spacing:2px;">SALESPHERE</div>'
        + '<div style="font-size:12px;font-weight:700;margin-top:2px;color:#111;">' + (order.BranchName || 'Branch') + '</div>'
        + '<div style="font-size:9px;color:#777;margin-top:1px;">123 Main Street, Colombo</div>'
        + '<div style="font-size:9px;color:#777;">+94 11-2345678</div>'
        + '</div>';

    // dashed divider
    html += '<div style="border-top:1px dashed #bbb;margin:6px 0;"></div>';

    // receipt title
    html += '<div style="text-align:center;font-size:11px;font-weight:700;letter-spacing:2px;color:#555;">- - - RECEIPT - - -</div>';

    html += '<div style="border-top:1px dashed #bbb;margin:6px 0;"></div>';

    // order info
    html += '<div style="font-size:10px;color:#333;line-height:1.7;">'
        + '<div style="display:flex;justify-content:space-between;"><span>Order #</span><span style="font-weight:700;">' + order.OrderID + '</span></div>'
        + '<div style="display:flex;justify-content:space-between;"><span>Date</span><span>' + dt + '</span></div>'
        + '<div style="display:flex;justify-content:space-between;"><span>Cashier</span><span>' + (order.CashierName || '') + '</span></div>'
        + (order.CustomerName ? '<div style="display:flex;justify-content:space-between;"><span>Customer</span><span>' + order.CustomerName + '</span></div>' : '')
        + '</div>';

    // items header
    html += '<div style="border-top:1px dashed #bbb;border-bottom:1px solid #555;margin:6px 0 4px;padding:4px 0;font-size:10px;font-weight:700;color:#111;">'
        + '<div style="display:flex;"><span style="flex:1;text-align:left;">Item</span><span style="width:36px;text-align:center;">Qty</span><span style="width:65px;text-align:right;">Total</span></div>'
        + '</div>';

    // items
    items.forEach(function(i) {
        html += '<div style="display:flex;font-size:10px;padding:3px 0;border-bottom:1px dotted #eee;">'
            + '<span style="flex:1;text-align:left;color:#333;">' + i.ProductName + '</span>'
            + '<span style="width:36px;text-align:center;color:#333;">' + i.Quantity + '</span>'
            + '<span style="width:65px;text-align:right;font-weight:600;color:#111;">Rs ' + parseFloat(i.LineTotal).toFixed(2) + '</span>'
            + '</div>';
    });

    // totals
    var taxRate = parseFloat(order.TaxRate || 0).toFixed(1);
    html += '<div style="border-top:1px dashed #bbb;margin-top:6px;padding-top:6px;font-size:11px;color:#333;">'
        + '<div style="display:flex;justify-content:space-between;padding:2px 0;"><span>Subtotal</span><span>Rs ' + parseFloat(order.Subtotal).toFixed(2) + '</span></div>'
        + '<div style="display:flex;justify-content:space-between;padding:2px 0;"><span>Tax (' + taxRate + '%)</span><span>Rs ' + parseFloat(order.TaxAmount).toFixed(2) + '</span></div>'
        + '<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:900;color:#FF5722;border-top:1px solid #555;padding:5px 0 2px;margin-top:4px;"><span>TOTAL</span><span>Rs ' + parseFloat(order.Total).toFixed(2) + '</span></div>'
        + '<div style="border-top:1px dashed #bbb;margin-top:6px;padding-top:6px;font-size:10px;">'
        + '<div style="display:flex;justify-content:space-between;padding:2px 0;"><span>Paid (' + order.PaymentMethod + ')</span><span>Rs ' + parseFloat(order.AmountPaid).toFixed(2) + '</span></div>'
        + '<div style="display:flex;justify-content:space-between;padding:2px 0;font-weight:700;color:' + (parseFloat(order.Change) > 0 ? '#16a34a' : '#333') + ';"><span>Change</span><span>Rs ' + parseFloat(Math.max(0, order.Change)).toFixed(2) + '</span></div>'
        + '</div></div>';

    // footer
    html += '<div style="border-top:1px dashed #bbb;margin-top:10px;padding-top:10px;text-align:center;font-size:9px;color:#888;line-height:1.6;">'
        + 'Items are non-returnable after 7 days.<br>'
        + '<span style="font-size:10px;color:#555;font-weight:600;">Thank you for your purchase!</span><br>'
        + '<span style="font-size:8px;color:#aaa;">Powered by Salesphere POS</span>'
        + '</div>';

    document.getElementById('receiptContent').innerHTML = html;
    openModal('receiptModal');
}

// open print window for receipt (80mm thermal receipt style)
function printReceipt() {
    var content = document.getElementById('receiptContent').innerHTML;
    var win = window.open('', '', 'width=380,height=600');
    win.document.write('<html><head><title>Receipt</title><style>'
        + '*{margin:0;padding:0;box-sizing:border-box;}'
        + 'body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#111;width:280px;margin:0 auto;padding:15px 10px;background:#fff;}'
        + '@media print{@page{margin:0;}body{padding:10px;width:auto;}}'
        + '</style></head><body>'
        + content
        + '<div style="text-align:center;margin-top:12px;padding-top:8px;border-top:1px dashed #bbb;font-size:8px;color:#aaa;">Salesphere POS v1.0</div>'
        + '</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function() { win.print(); }, 300);
}

document.getElementById('posSearch').addEventListener('input', function() {
    loadProducts(this.value);
});

loadProducts('');
</script>
