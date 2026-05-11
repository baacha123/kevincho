/**
 * Kevin Cho — Store Manager Portal SPA
 *
 * Hash-based router with section renderers.
 * Uses KCTM_Portal global (localized from PHP).
 */
(function () {
    'use strict';

    /* ── Globals ──────────────────────────────────────── */
    var C = window.KCTM_Portal || {};
    var $main = document.getElementById('portal-content');
    var currentSection = '';

    /* ── API helper ───────────────────────────────────── */
    function api(action, params, method) {
        method = method || 'POST';
        var fd = new FormData();
        fd.append('action', 'kctm_portal_' + action);
        fd.append('nonce', C.nonce);
        if (params) {
            Object.keys(params).forEach(function (k) {
                var v = params[k];
                if (v !== null && v !== undefined) {
                    if (typeof v === 'object' && !(v instanceof File)) {
                        Object.keys(v).forEach(function (sk) {
                            fd.append(k + '[' + sk + ']', v[sk]);
                        });
                    } else {
                        fd.append(k, v);
                    }
                }
            });
        }
        return fetch(C.ajax_url, { method: method, credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); });
    }

    /* ── Toast ────────────────────────────────────────── */
    function toast(msg, type) {
        type = type || 'success';
        var el = document.createElement('div');
        el.className = 'portal-toast portal-toast-' + type;
        el.textContent = msg;
        document.getElementById('portal-toasts').appendChild(el);
        setTimeout(function () { el.remove(); }, 4000);
    }

    /* ── Modal ────────────────────────────────────────── */
    function openModal(html) {
        document.getElementById('portal-modal-content').innerHTML = html;
        document.getElementById('portal-modal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('portal-modal').style.display = 'none';
        document.getElementById('portal-modal-content').innerHTML = '';
    }
    document.getElementById('portal-modal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    /* ── Badge helper ─────────────────────────────────── */
    function badge(status) {
        var s = (status || '').replace('wc-', '');
        return '<span class="portal-badge portal-badge-' + esc(s) + '">' + esc(statusLabel(s)) + '</span>';
    }
    function statusLabel(s) {
        var map = {
            'pending': 'Pending', 'processing': 'Processing', 'on-hold': 'On Hold',
            'completed': 'Completed', 'cancelled': 'Cancelled', 'refunded': 'Refunded', 'failed': 'Failed',
            'kctm-confirmed': 'Confirmed', 'kctm-in-progress': 'In Progress',
            'kctm-ready-pickup': 'Ready for Pickup', 'kctm-with-driver': 'With Driver', 'kctm-delivered': 'Delivered',
            'confirmed': 'Confirmed', 'paid': 'Paid', 'walkin': 'Walk-in', 'regular': 'Regular'
        };
        return map[s] || s;
    }

    /* ── Escape HTML ──────────────────────────────────── */
    function esc(str) {
        if (str === null || str === undefined) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    /* ── AI Rewrite helper ────────────────────────────── */
    function aiRewriteBtn(id, label) {
        var lbl = label || 'AI Rewrite';
        return '<button type="button" class="portal-btn portal-btn-outline portal-btn-sm ai-rewrite-btn" data-target="' + id + '" style="border-color:#c9a96e;color:#c9a96e;margin-top:0.35rem;font-size:0.75rem;">' + lbl + '</button>';
    }
    function bindAiRewrites(context, extraFn) {
        var ctx = context || 'email';
        document.querySelectorAll('.ai-rewrite-btn').forEach(function (btn) {
            if (btn._bound) return;
            btn._bound = true;
            btn.addEventListener('click', function () {
                var target = document.getElementById(this.getAttribute('data-target'));
                if (!target) return;
                var text = target.value.trim();
                /* If textarea is empty, try to get hint from extraFn (e.g. template name) */
                var extra = extraFn ? extraFn() : '';
                if (!text && !extra) { toast('Type something first, then click AI Write.', 'error'); return; }
                if (!text && extra) text = extra;
                else if (extra) text = extra + ': ' + text;
                var b = this;
                var origLabel = b.textContent;
                b.disabled = true;
                b.textContent = 'Writing...';
                api('ai_rewrite', { text: text, context: ctx }).then(function (r) {
                    b.disabled = false;
                    b.textContent = origLabel;
                    if (r.success) { target.value = r.data.rewritten; toast('Done!'); }
                    else { toast(r.data.message, 'error'); }
                });
            });
        });
    }

    /* ── Format money ─────────────────────────────────── */
    function money(val) {
        var num = parseFloat(val) || 0;
        return C.currency + num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    /* ── Format date ──────────────────────────────────── */
    function fmtDate(d) {
        if (!d) return '-';
        var dt = new Date(d);
        return dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    /* ── Loading ──────────────────────────────────────── */
    function showLoading() {
        $main.innerHTML = '<div class="portal-loading"><div class="portal-spinner"></div><p>Loading...</p></div>';
    }

    /* ── Status select options ────────────────────────── */
    function statusOptions() {
        return [
            { value: 'pending', label: 'Pending' },
            { value: 'kctm-confirmed', label: 'Confirmed' },
            { value: 'processing', label: 'Processing' },
            { value: 'kctm-in-progress', label: 'In Progress' },
            { value: 'kctm-ready-pickup', label: 'Ready for Pickup' },
            { value: 'kctm-with-driver', label: 'With Driver' },
            { value: 'kctm-delivered', label: 'Delivered' },
            { value: 'completed', label: 'Completed' },
            { value: 'cancelled', label: 'Cancelled' },
        ];
    }

    /* ═══════════════════════════════════════════════════
       ROUTER
       ═══════════════════════════════════════════════════ */
    var routes = {
        dashboard: renderDashboard,
        analytics: renderAnalytics,
        orders: renderOrders,
        production: renderProduction,
        customers: renderCustomers,
        staff: renderStaff,
        drivers: renderDrivers,
        consultations: renderConsultations,
        products: renderProducts,
        coupons: renderCoupons,
        fabrics: renderFabrics,
        email: renderEmail,
        notifications: renderNotifications,
        invoices: renderInvoices,
        expenses: renderExpenses,
        settings: renderSettings,
    };

    function navigate() {
        var hash = (location.hash || '#dashboard').replace('#', '').split('/');
        var section = hash[0];
        var param = hash[1] || null;

        /* Update active nav */
        document.querySelectorAll('.portal-nav a').forEach(function (a) {
            a.classList.toggle('active', a.getAttribute('data-section') === section);
        });

        /* Close mobile sidebar */
        document.getElementById('portal-sidebar').classList.remove('open');
        document.getElementById('portal-overlay').classList.remove('active');

        currentSection = section;

        if (routes[section]) {
            routes[section](param);
        } else {
            $main.innerHTML = '<div class="portal-empty"><p>Section not found.</p></div>';
        }
    }

    window.addEventListener('hashchange', navigate);

    /* ── Mobile sidebar toggle ────────────────────────── */
    document.getElementById('portal-hamburger').addEventListener('click', function () {
        document.getElementById('portal-sidebar').classList.toggle('open');
        document.getElementById('portal-overlay').classList.toggle('active');
    });
    document.getElementById('portal-overlay').addEventListener('click', function () {
        document.getElementById('portal-sidebar').classList.remove('open');
        this.classList.remove('active');
    });

    /* ═══════════════════════════════════════════════════
       DASHBOARD
       ═══════════════════════════════════════════════════ */
    function renderDashboard() {
        showLoading();
        api('dashboard').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            html += '<div class="portal-section-header"><h1 class="portal-section-title">Dashboard</h1></div>';

            /* Quick actions */
            html += '<div class="portal-quick-actions">';
            html += '<a href="#orders" class="portal-btn portal-btn-primary">View Orders</a>';
            html += '<button class="portal-btn portal-btn-secondary" onclick="location.hash=\'#customers\'">Customers</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="location.hash=\'#email\'">Send Email</button>';
            html += '</div>';

            /* Stats — clickable with filters */
            html += '<div class="portal-stats">';
            html += '<div class="portal-stat-card blue" style="cursor:pointer;" onclick="location.hash=\'#customers\'"><div class="portal-stat-label">Customers</div><div class="portal-stat-value">' + d.customers + '</div></div>';
            html += '<div class="portal-stat-card gold" style="cursor:pointer;" id="dash-orders-today"><div class="portal-stat-label">Orders Today</div><div class="portal-stat-value">' + d.orders_today + '</div></div>';
            html += '<div class="portal-stat-card orange" style="cursor:pointer;" id="dash-pending"><div class="portal-stat-label">Pending Orders</div><div class="portal-stat-value">' + d.pending_orders + '</div></div>';
            html += '<div class="portal-stat-card green" style="cursor:pointer;" onclick="location.hash=\'#analytics\'"><div class="portal-stat-label">Revenue This Month</div><div class="portal-stat-value">' + money(d.revenue_month) + '</div></div>';
            html += '</div>';

            /* Two-column grid */
            html += '<div class="portal-dashboard-grid">';

            /* Recent orders */
            html += '<div class="portal-card"><div class="portal-card-title">Recent Orders</div>';
            if (d.recent_orders.length === 0) {
                html += '<div class="portal-empty"><p>No orders yet.</p></div>';
            } else {
                html += '<div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead><tbody>';
                d.recent_orders.forEach(function (o) {
                    html += '<tr onclick="location.hash=\'#orders/' + o.id + '\'">';
                    html += '<td>' + esc(o.number) + '</td>';
                    html += '<td>' + esc(o.customer) + '</td>';
                    html += '<td>' + money(o.total) + '</td>';
                    html += '<td>' + badge(o.status) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
            }
            html += '</div>';

            /* Upcoming consultations */
            html += '<div class="portal-card"><div class="portal-card-title">Upcoming Consultations</div>';
            if (d.upcoming_consultations.length === 0) {
                html += '<div class="portal-empty"><p>No upcoming consultations.</p></div>';
            } else {
                html += '<div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Customer</th><th>Date</th><th>Time</th></tr></thead><tbody>';
                d.upcoming_consultations.forEach(function (c) {
                    html += '<tr>';
                    html += '<td>' + esc(c.name) + '</td>';
                    html += '<td>' + fmtDate(c.date) + '</td>';
                    html += '<td>' + esc(c.time) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
            }
            html += '</div>';

            html += '</div>'; /* end grid */
            $main.innerHTML = html;

            /* Dashboard card click handlers */
            var ot = document.getElementById('dash-orders-today');
            if (ot) ot.addEventListener('click', function () { ordersState.status = 'any'; ordersState.date_filter = 'today'; ordersState.page = 1; location.hash = '#orders'; });
            var pp = document.getElementById('dash-pending');
            if (pp) pp.addEventListener('click', function () { ordersState.status = 'on-hold'; ordersState.date_filter = ''; ordersState.page = 1; location.hash = '#orders'; });
        });
    }

    /* ═══════════════════════════════════════════════════
       ORDERS
       ═══════════════════════════════════════════════════ */
    var ordersState = { page: 1, status: 'any', search: '', expanded: null, date_filter: '' };

    function renderOrders(orderId) {
        if (orderId === 'abandoned') { renderAbandonedCarts(); return; }
        if (orderId) {
            renderOrderDetail(orderId);
            return;
        }
        showLoading();
        api('orders', { page: ordersState.page, status: ordersState.status, search: ordersState.search, date_filter: ordersState.date_filter || '' }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            html += '<div class="portal-section-header"><h1 class="portal-section-title">Orders</h1>';
            html += '<div style="display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm" onclick="location.hash=\'#orders/abandoned\'">Abandoned Carts</button>';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm portal-export-btn" id="btn-export-orders"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> CSV</button>';
            html += '</div></div>';

            /* Tabs */
            var tabs = [
                { value: 'any', label: 'All' },
                { value: 'on-hold', label: 'On Hold' },
                { value: 'processing', label: 'Processing' },
                { value: 'kctm-confirmed', label: 'Confirmed' },
                { value: 'kctm-in-progress', label: 'In Progress' },
                { value: 'kctm-ready-pickup', label: 'Ready' },
                { value: 'kctm-with-driver', label: 'With Driver' },
                { value: 'kctm-delivered', label: 'Delivered' },
                { value: 'completed', label: 'Completed' },
            ];
            html += '<div class="portal-tabs">';
            tabs.forEach(function (t) {
                html += '<button class="portal-tab' + (ordersState.status === t.value ? ' active' : '') + '" data-status="' + t.value + '">' + t.label + '</button>';
            });
            html += '</div>';

            /* Search + date filter indicator */
            html += '<div class="portal-search" style="display:flex;gap:0.5rem;align-items:center;"><input type="text" placeholder="Search by order # or customer..." value="' + esc(ordersState.search) + '" id="orders-search" style="flex:1;">';
            if (ordersState.date_filter) {
                html += '<span style="background:#fef9e7;border:1px solid #c9a96e;padding:4px 10px;border-radius:6px;font-size:0.85rem;color:#402417;white-space:nowrap;">' + esc(ordersState.date_filter === 'today' ? 'Today' : ordersState.date_filter) + ' <span style="cursor:pointer;margin-left:4px;" onclick="ordersState.date_filter=\'\';renderOrders();">&times;</span></span>';
            }
            html += '</div>';

            /* Table */
            if (d.orders.length === 0) {
                html += '<div class="portal-empty"><p>No orders found.</p></div>';
            } else {
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>#</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Action</th>';
                html += '</tr></thead><tbody>';
                d.orders.forEach(function (o) {
                    html += '<tr data-order-id="' + o.id + '">';
                    html += '<td>' + esc(o.number) + '</td>';
                    html += '<td>' + esc(o.customer) + '</td>';
                    html += '<td>' + fmtDate(o.date) + '</td>';
                    html += '<td>' + money(o.total) + '</td>';
                    html += '<td>' + badge(o.status) + '</td>';
                    html += '<td><select class="portal-status-select" data-oid="' + o.id + '">';
                    statusOptions().forEach(function (opt) {
                        html += '<option value="' + opt.value + '"' + (o.status === opt.value ? ' selected' : '') + '>' + opt.label + '</option>';
                    });
                    html += '</select></td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }

            /* Pagination */
            if (d.pages > 1) {
                html += '<div class="portal-pagination">';
                html += '<button ' + (ordersState.page <= 1 ? 'disabled' : '') + ' data-page="' + (ordersState.page - 1) + '">Prev</button>';
                html += '<span class="portal-page-info">Page ' + ordersState.page + ' of ' + d.pages + '</span>';
                html += '<button ' + (ordersState.page >= d.pages ? 'disabled' : '') + ' data-page="' + (ordersState.page + 1) + '">Next</button>';
                html += '</div>';
            }

            $main.innerHTML = html;

            /* Event: tabs */
            $main.querySelectorAll('.portal-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    ordersState.status = this.getAttribute('data-status');
                    ordersState.date_filter = '';
                    ordersState.page = 1;
                    renderOrders();
                });
            });

            /* Event: search */
            var searchTimer;
            var searchInput = document.getElementById('orders-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimer);
                    var val = this.value;
                    searchTimer = setTimeout(function () {
                        ordersState.search = val;
                        ordersState.page = 1;
                        renderOrders();
                    }, 500);
                });
            }

            /* Event: status change */
            $main.querySelectorAll('.portal-status-select').forEach(function (sel) {
                sel.addEventListener('change', function (e) {
                    e.stopPropagation();
                    var oid = this.getAttribute('data-oid');
                    var newStatus = this.value;
                    api('update_order_status', { order_id: oid, status: newStatus }).then(function (res) {
                        if (res.success) {
                            toast('Order #' + oid + ' updated to ' + res.data.status_label);
                            renderOrders();
                        } else {
                            toast(res.data.message, 'error');
                        }
                    });
                });
            });

            /* Event: row click → detail */
            $main.querySelectorAll('tr[data-order-id]').forEach(function (tr) {
                tr.addEventListener('click', function (e) {
                    if (e.target.tagName === 'SELECT' || e.target.tagName === 'OPTION') return;
                    location.hash = '#orders/' + this.getAttribute('data-order-id');
                });
            });

            /* Event: pagination */
            $main.querySelectorAll('.portal-pagination button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    ordersState.page = parseInt(this.getAttribute('data-page'));
                    renderOrders();
                });
            });

            /* Event: export */
            var exportOrders = document.getElementById('btn-export-orders');
            if (exportOrders) exportOrders.addEventListener('click', function () { exportCSV('orders'); });
        });
    }

    function showEditItemsModal(order) {
        var html = '<div class="portal-modal-title">Edit Order #' + esc(order.number) + '</div>';
        html += '<p style="font-size:0.85rem;color:#666;margin-bottom:0.75rem;">Update items, quantities, or prices. Customer will be notified by email.</p>';
        html += '<div id="edit-items-list"></div>';
        html += '<button class="portal-btn portal-btn-outline portal-btn-sm" id="edit-add-item" style="margin-bottom:1rem;">+ Add Item</button>';
        html += '<div style="border-top:1px solid #e0d6cc;padding-top:0.75rem;">';
        html += '<label style="font-size:0.9rem;"><input type="checkbox" id="edit-notify" checked> Notify customer by email</label>';
        html += '</div>';
        html += '<div class="portal-form-actions"><button class="portal-btn portal-btn-outline" onclick="closeModal()">Cancel</button>';
        html += '<button class="portal-btn portal-btn-primary" id="edit-save">Save Changes</button></div>';
        openModal(html);

        var rowCount = 0;

        function addRow(item) {
            item = item || { item_id: 0, name: '', qty: 1, price: '' };
            var idx = rowCount++;
            var row = document.createElement('div');
            row.className = 'portal-form-row edit-item-row';
            row.id = 'edit-row-' + idx;
            row.setAttribute('data-item-id', item.item_id || 0);
            row.style.cssText = 'gap:0.5rem;margin-bottom:0.5rem;align-items:flex-end;';
            row.innerHTML = '<div class="portal-form-group" style="flex:2;"><label>Item Name</label><input type="text" class="portal-form-input edit-name" value="' + esc(item.name || '') + '"></div>' +
                '<div class="portal-form-group" style="flex:1;"><label>Price (FCFA)</label><input type="number" class="portal-form-input edit-price" value="' + esc(item.price || '') + '" step="1"></div>' +
                '<div class="portal-form-group" style="flex:0.5;"><label>Qty</label><input type="number" class="portal-form-input edit-qty" value="' + (item.qty || 1) + '" min="1"></div>' +
                '<button class="portal-btn portal-btn-sm" style="background:#e74c3c;color:#fff;margin-bottom:0.25rem;" onclick="document.getElementById(\'edit-row-' + idx + '\').remove()">X</button>';
            document.getElementById('edit-items-list').appendChild(row);
        }

        /* Pre-fill with existing items */
        (order.items || []).forEach(function (item) { addRow(item); });

        document.getElementById('edit-add-item').addEventListener('click', function () { addRow(); });

        document.getElementById('edit-save').addEventListener('click', function () {
            var items = [];
            document.querySelectorAll('.edit-item-row').forEach(function (row) {
                var name = row.querySelector('.edit-name').value.trim();
                var price = row.querySelector('.edit-price').value;
                var qty = parseInt(row.querySelector('.edit-qty').value) || 1;
                if (name) {
                    items.push({
                        item_id: parseInt(row.getAttribute('data-item-id')) || 0,
                        name: name,
                        price: parseFloat(price) || 0,
                        qty: qty
                    });
                }
            });

            if (items.length === 0) { toast('Add at least one item.', 'error'); return; }

            var notify = document.getElementById('edit-notify').checked ? 'yes' : 'no';

            toast('Saving changes...');
            api('edit_order_items', {
                order_id: order.id,
                items_json: JSON.stringify(items),
                notify: notify
            }).then(function (r) {
                if (r.success) {
                    var msg = 'Order updated.';
                    if (r.data.sent_via) msg += ' Customer notified via ' + r.data.sent_via + '.';
                    toast(msg);
                    closeModal();
                    renderOrderDetail(order.id);
                } else {
                    toast(r.data.message, 'error');
                }
            });
        });
    }

    function renderOrderDetail(orderId) {
        showLoading();
        api('orders', { search: orderId }).then(function (res) {
            if (!res.success || res.data.orders.length === 0) {
                toast('Order not found.', 'error');
                location.hash = '#orders';
                return;
            }
            var o = res.data.orders[0];
            var html = '';

            html += '<div class="portal-back-link" onclick="location.hash=\'#orders\'">&#8592; Back to Orders</div>';
            html += '<div class="portal-section-header"><h1 class="portal-section-title">Order #' + esc(o.number) + '</h1>';
            html += '<div class="portal-section-actions">' + badge(o.status) + '</div></div>';

            html += '<div class="portal-card">';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Customer</span><span class="portal-detail-value">' + esc(o.customer) + '</span></div>';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Email</span><span class="portal-detail-value">' + esc(o.email) + '</span></div>';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Phone</span><span class="portal-detail-value">' + esc(o.phone) + '</span></div>';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Date</span><span class="portal-detail-value">' + fmtDate(o.date) + '</span></div>';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Total</span><span class="portal-detail-value">' + money(o.total) + '</span></div>';
            if (o.city) {
                html += '<div class="portal-detail-row"><span class="portal-detail-label">City</span><span class="portal-detail-value">' + esc(o.city) + '</span></div>';
            }

            /* Driver info if assigned */
            if (o.driver_name) {
                html += '<div class="portal-detail-row"><span class="portal-detail-label">Driver</span><span class="portal-detail-value">' + esc(o.driver_name) + ' (' + esc(o.driver_phone) + ')</span></div>';
            }

            /* MoMo payment info */
            if (o.momo_ref) {
                html += '<div class="portal-detail-row"><span class="portal-detail-label">MoMo Transaction ID</span><span class="portal-detail-value" style="font-family:monospace;font-weight:600;">' + esc(o.momo_ref) + '</span></div>';
            }
            if (o.momo_screenshot_url) {
                html += '<div class="portal-detail-row"><span class="portal-detail-label">MoMo Screenshot</span><span class="portal-detail-value">';
                html += '<a href="' + esc(o.momo_screenshot_url) + '" target="_blank" rel="noopener">';
                html += '<img src="' + esc(o.momo_screenshot_url) + '" alt="MoMo Screenshot" style="max-width:120px;max-height:120px;border-radius:6px;border:1px solid #ddd;cursor:pointer;">';
                html += '</a></span></div>';
            }

            /* Confirm Payment button for on-hold MoMo orders */
            if (o.status === 'on-hold' && (o.momo_ref || o.payment_method === 'kctm_momo_manual')) {
                html += '<div class="portal-detail-row"><span class="portal-detail-label">Payment</span><span class="portal-detail-value">';
                html += '<button class="portal-btn portal-btn-sm" id="order-confirm-payment" style="background:#27ae60;color:#fff;border:none;">Confirm Payment</button>';
                html += '</span></div>';
            }

            /* Status update */
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Update Status</span><span>';
            html += '<select class="portal-status-select" id="order-detail-status">';
            statusOptions().forEach(function (opt) {
                html += '<option value="' + opt.value + '"' + (o.status === opt.value ? ' selected' : '') + '>' + opt.label + '</option>';
            });
            html += '</select> <button class="portal-btn portal-btn-sm portal-btn-primary" id="order-detail-save-status">Update</button>';
            html += '</span></div>';

            /* Show driver info if already shipped */
            if (o.driver_name) {
                html += '<div class="portal-detail-row"><span class="portal-detail-label">Driver</span><span class="portal-detail-value" style="font-weight:600;">' + esc(o.driver_name) + ' — ' + esc(o.driver_phone) + '</span></div>';
            }
            html += '</div>';

            /* Items */
            html += '<div class="portal-card"><div class="portal-card-title">Items <button class="portal-btn portal-btn-sm portal-btn-outline" id="btn-edit-items" style="float:right;">Edit Items</button></div>';
            html += '<div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead><tbody>';
            o.items.forEach(function (item) {
                html += '<tr><td>' + esc(item.name) + '</td><td>' + item.qty + '</td><td>' + money(item.total) + '</td></tr>';
            });
            html += '</tbody></table></div></div>';

            /* Measurements */
            if (o.measurements && o.measurements.fields) {
                html += '<div class="portal-card"><div class="portal-card-title">Measurements Snapshot</div>';
                html += '<div class="portal-measurements-grid">';
                var fields = o.measurements.fields;
                Object.keys(fields).forEach(function (key) {
                    if (fields[key]) {
                        html += '<div class="portal-measurement-item"><div class="portal-measurement-label">' + esc(key.replace(/_/g, ' ')) + '</div>';
                        html += '<div class="portal-measurement-value">' + esc(fields[key]) + '</div></div>';
                    }
                });
                html += '</div></div>';
            }

            /* Invoice download */
            html += '<div class="portal-card"><div class="portal-card-title">Invoice</div>';
            html += '<button class="portal-btn portal-btn-outline" id="order-download-invoice" data-oid="' + o.id + '">Download Invoice PDF</button>';
            html += '</div>';

            $main.innerHTML = html;

            /* Events */
            document.getElementById('order-detail-save-status').addEventListener('click', function () {
                var ns = document.getElementById('order-detail-status').value;

                /* If changing to "With Driver", prompt for driver details */
                if (ns === 'kctm-with-driver') {
                    var driverName = prompt('Enter driver name / Nom du chauffeur:');
                    if (!driverName) return;
                    var driverPhone = prompt('Enter driver phone number / Numéro du chauffeur:');
                    if (!driverPhone) return;

                    api('assign_driver', { order_id: o.id, driver_name: driverName, driver_phone: driverPhone, notify: '1' }).then(function (r) {
                        if (r.success) {
                            var msg = 'Order shipped! Customer notified.';
                            if (r.data.sent_via) msg = 'Order shipped! Customer notified via ' + r.data.sent_via + '.';
                            toast(msg);
                            renderOrderDetail(orderId);
                        } else {
                            toast(r.data.message, 'error');
                        }
                    });
                    return;
                }

                api('update_order_status', { order_id: o.id, status: ns }).then(function (r) {
                    if (r.success) { toast('Status updated.'); renderOrderDetail(orderId); }
                    else toast(r.data.message, 'error');
                });
            });

            document.getElementById('order-download-invoice').addEventListener('click', function () {
                api('invoice_url', { order_id: o.id }).then(function (r) {
                    if (r.success && r.data.url) window.open(r.data.url, '_blank');
                    else toast('Invoice not available.', 'error');
                });
            });

            /* Edit order items */
            var editItemsBtn = document.getElementById('btn-edit-items');
            if (editItemsBtn) {
                editItemsBtn.addEventListener('click', function () {
                    showEditItemsModal(o);
                });
            }

            /* Confirm MoMo payment */
            var confirmBtn = document.getElementById('order-confirm-payment');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function () {
                    if (!confirm('Confirm that payment has been received for order #' + o.number + '?')) return;
                    api('update_order_status', { order_id: o.id, status: 'processing' }).then(function (r) {
                        if (r.success) { toast('Payment confirmed. Order moved to Processing.'); renderOrderDetail(orderId); }
                        else toast(r.data.message, 'error');
                    });
                });
            }

            /* (Ship order is now handled by status dropdown → With Driver) */
        });
    }

    /* ═══════════════════════════════════════════════════
       CUSTOMERS
       ═══════════════════════════════════════════════════ */
    var custState = { page: 1, search: '' };

    function renderCustomers(customerId) {
        if (customerId) {
            renderCustomerDetail(customerId);
            return;
        }
        showLoading();
        api('customers', { page: custState.page, search: custState.search }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            html += '<div class="portal-section-header"><h1 class="portal-section-title">Customers</h1>';
            html += '<div class="portal-section-actions"><button class="portal-btn portal-btn-primary" id="btn-add-walkin">+ Add Walk-in</button></div></div>';

            /* Search */
            html += '<div class="portal-search"><input type="text" placeholder="Search by name, email, or phone..." value="' + esc(custState.search) + '" id="customers-search"></div>';

            /* Customer cards */
            if (d.customers.length === 0) {
                html += '<div class="portal-empty"><p>No customers found.</p></div>';
            } else {
                html += '<div class="portal-customer-cards">';
                d.customers.forEach(function (c) {
                    var initials = (c.name || 'U').split(' ').map(function (w) { return w[0]; }).join('').toUpperCase().substring(0, 2);
                    html += '<div class="portal-customer-card" data-cid="' + c.id + '">';
                    html += '<div class="portal-customer-card-header">';
                    html += '<div class="portal-customer-avatar">' + esc(initials) + '</div>';
                    html += '<div><div class="portal-customer-name">' + esc(c.name) + '</div>';
                    html += '<div style="display:flex;gap:0.4rem;">' + badge(c.type);
                    if (c.has_measurements) html += ' <span class="portal-badge portal-badge-confirmed">Measured</span>';
                    html += '</div></div>';
                    html += '</div>';
                    html += '<div class="portal-customer-meta">';
                    if (c.phone) html += '<span>Phone: ' + esc(c.phone) + '</span>';
                    if (c.email && c.email.indexOf('@kevincho.local') === -1) html += '<span>Email: ' + esc(c.email) + '</span>';
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
            }

            /* Pagination */
            if (d.pages > 1) {
                html += '<div class="portal-pagination">';
                html += '<button ' + (custState.page <= 1 ? 'disabled' : '') + ' data-page="' + (custState.page - 1) + '">Prev</button>';
                html += '<span class="portal-page-info">Page ' + custState.page + ' of ' + d.pages + '</span>';
                html += '<button ' + (custState.page >= d.pages ? 'disabled' : '') + ' data-page="' + (custState.page + 1) + '">Next</button>';
                html += '</div>';
            }

            $main.innerHTML = html;

            /* Event: search */
            var searchTimer;
            document.getElementById('customers-search').addEventListener('input', function () {
                clearTimeout(searchTimer);
                var val = this.value;
                searchTimer = setTimeout(function () {
                    custState.search = val;
                    custState.page = 1;
                    renderCustomers();
                }, 500);
            });

            /* Event: card click */
            $main.querySelectorAll('.portal-customer-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    location.hash = '#customers/' + this.getAttribute('data-cid');
                });
            });

            /* Event: add walk-in */
            document.getElementById('btn-add-walkin').addEventListener('click', showWalkinModal);

            /* Event: pagination */
            $main.querySelectorAll('.portal-pagination button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    custState.page = parseInt(this.getAttribute('data-page'));
                    renderCustomers();
                });
            });
        });
    }

    function showWalkinModal() {
        var html = '<div class="portal-modal-title">Add Walk-in Customer</div>';
        html += '<div class="portal-form-row"><div class="portal-form-group"><label>First Name <span class="required">*</span></label><input class="portal-form-input" id="wi-first" required></div>';
        html += '<div class="portal-form-group"><label>Last Name <span class="required">*</span></label><input class="portal-form-input" id="wi-last" required></div></div>';
        html += '<div class="portal-form-row"><div class="portal-form-group"><label>Phone <span class="required">*</span></label><input class="portal-form-input" id="wi-phone" required></div>';
        html += '<div class="portal-form-group"><label>Email</label><input class="portal-form-input" id="wi-email" type="email"></div></div>';
        html += '<div class="portal-form-row"><div class="portal-form-group"><label>Gender</label><select class="portal-form-select" id="wi-gender"><option value="">-- Select --</option><option value="male">Male</option><option value="female">Female</option><option value="child">Child</option></select></div>';
        html += '<div class="portal-form-group"><label>Height (cm)</label><input class="portal-form-input" id="wi-height" type="number" placeholder="e.g. 175"></div></div>';
        html += '<div class="portal-form-group"><label>Shoe Size</label><input class="portal-form-input" id="wi-shoe" type="text" placeholder="e.g. 42"></div>';
        html += '<div class="portal-form-actions"><button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
        html += '<button class="portal-btn portal-btn-primary" id="wi-submit">Create Customer</button></div>';
        openModal(html);

        document.getElementById('wi-submit').addEventListener('click', function () {
            var data = {
                first_name: document.getElementById('wi-first').value,
                last_name: document.getElementById('wi-last').value,
                phone: document.getElementById('wi-phone').value,
                email: document.getElementById('wi-email').value,
                gender: document.getElementById('wi-gender').value,
                height: document.getElementById('wi-height').value,
                shoe_size: document.getElementById('wi-shoe').value,
            };
            if (!data.first_name || !data.last_name || !data.phone) {
                toast('Please fill in all required fields.', 'error');
                return;
            }
            api('create_walkin', data).then(function (r) {
                if (r.success) {
                    toast('Customer created!');
                    closeModal();
                    location.hash = '#customers/' + r.data.customer_id;
                } else {
                    toast(r.data.message, 'error');
                }
            });
        });
    }

    function showInstoreOrderModal(customer) {
        var html = '<div class="portal-modal-title">Create In-Store Order for ' + esc(customer.name) + '</div>';
        html += '<div id="instore-items"></div>';
        html += '<div style="display:flex;gap:0.5rem;margin-bottom:1rem;">';
        html += '<button class="portal-btn portal-btn-outline portal-btn-sm" id="instore-add-product">+ From Catalog</button>';
        html += '<button class="portal-btn portal-btn-outline portal-btn-sm" id="instore-add-custom">+ Custom Item</button>';
        html += '</div>';
        html += '<div style="border-top:1px solid #e0d6cc;padding-top:0.75rem;margin-top:0.5rem;">';
        html += '<label style="font-size:0.9rem;"><input type="checkbox" id="instore-send-invoice" checked> Send invoice email to customer</label>';
        html += '</div>';
        html += '<div class="portal-form-actions"><button class="portal-btn portal-btn-outline" onclick="closeModal()">Cancel</button>';
        html += '<button class="portal-btn portal-btn-primary" id="instore-submit">Create Order</button></div>';
        openModal(html);

        var itemCount = 0;
        var cachedProducts = null;

        function addProductRow() {
            var idx = itemCount++;
            var row = document.createElement('div');
            row.className = 'portal-form-row instore-item-row';
            row.id = 'instore-row-' + idx;
            row.setAttribute('data-type', 'product');
            row.style.cssText = 'gap:0.5rem;margin-bottom:0.5rem;align-items:flex-end;';
            row.innerHTML = '<div class="portal-form-group" style="flex:3;"><label>Product</label><select class="portal-form-select instore-product" data-idx="' + idx + '"><option value="">Loading...</option></select></div>' +
                '<div class="portal-form-group" style="flex:1;"><label>Qty</label><input type="number" class="portal-form-input instore-qty" value="1" min="1" data-idx="' + idx + '"></div>' +
                '<button class="portal-btn portal-btn-sm" style="background:#e74c3c;color:#fff;margin-bottom:0.25rem;" onclick="document.getElementById(\'instore-row-' + idx + '\').remove()">X</button>';
            document.getElementById('instore-items').appendChild(row);

            if (cachedProducts) {
                populateSelect(row.querySelector('.instore-product'), cachedProducts);
            } else {
                api('products', { per_page: 100 }).then(function (r) {
                    if (!r.success) return;
                    cachedProducts = r.data.products;
                    populateSelect(row.querySelector('.instore-product'), cachedProducts);
                });
            }
        }

        function populateSelect(sel, products) {
            sel.innerHTML = '<option value="">-- Select Product --</option>';
            products.forEach(function (p) {
                var price = p.price ? ' (' + money(p.price) + ')' : '';
                sel.innerHTML += '<option value="' + p.id + '">' + esc(p.name) + price + '</option>';
            });
        }

        function addCustomRow() {
            var idx = itemCount++;
            var row = document.createElement('div');
            row.className = 'portal-form-row instore-item-row';
            row.id = 'instore-row-' + idx;
            row.setAttribute('data-type', 'custom');
            row.style.cssText = 'gap:0.5rem;margin-bottom:0.5rem;align-items:flex-end;';
            row.innerHTML = '<div class="portal-form-group" style="flex:2;"><label>Item Description</label><input type="text" class="portal-form-input instore-custom-name" data-idx="' + idx + '" placeholder="e.g. Custom 3-piece suit"></div>' +
                '<div class="portal-form-group" style="flex:1;"><label>Price (FCFA)</label><input type="number" class="portal-form-input instore-custom-price" data-idx="' + idx + '" placeholder="e.g. 50000" step="1"></div>' +
                '<div class="portal-form-group" style="flex:0.5;"><label>Qty</label><input type="number" class="portal-form-input instore-qty" value="1" min="1" data-idx="' + idx + '"></div>' +
                '<button class="portal-btn portal-btn-sm" style="background:#e74c3c;color:#fff;margin-bottom:0.25rem;" onclick="document.getElementById(\'instore-row-' + idx + '\').remove()">X</button>';
            document.getElementById('instore-items').appendChild(row);
        }

        document.getElementById('instore-add-product').addEventListener('click', addProductRow);
        document.getElementById('instore-add-custom').addEventListener('click', addCustomRow);

        document.getElementById('instore-submit').addEventListener('click', function () {
            var items = [];
            document.querySelectorAll('.instore-item-row').forEach(function (row) {
                var type = row.getAttribute('data-type');
                var idx = row.id.replace('instore-row-', '');
                var qtyEl = row.querySelector('.instore-qty');
                var qty = qtyEl ? parseInt(qtyEl.value) || 1 : 1;

                if (type === 'product') {
                    var sel = row.querySelector('.instore-product');
                    if (sel && sel.value) {
                        items.push({ type: 'product', product_id: parseInt(sel.value), qty: qty });
                    }
                } else if (type === 'custom') {
                    var nameEl = row.querySelector('.instore-custom-name');
                    var priceEl = row.querySelector('.instore-custom-price');
                    if (nameEl && nameEl.value && priceEl && priceEl.value) {
                        items.push({ type: 'custom', name: nameEl.value, price: parseInt(priceEl.value), qty: qty });
                    }
                }
            });

            if (items.length === 0) { toast('Add at least one item.', 'error'); return; }

            var sendInvoice = document.getElementById('instore-send-invoice').checked ? 'yes' : 'no';

            toast('Creating order...');
            api('create_instore_order', {
                customer_id: customer.id,
                items_json: JSON.stringify(items),
                send_invoice: sendInvoice
            }).then(function (r) {
                if (r.success) {
                    var msg = r.data.message;
                    if (r.data.sent_invoice) msg += ' Invoice sent to customer.';
                    toast(msg);
                    closeModal();
                    renderCustomerDetail(customer.id);
                } else {
                    toast(r.data.message, 'error');
                }
            });
        });
    }

    function renderCustomerDetail(cid) {
        showLoading();
        api('customer_detail', { customer_id: cid }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); location.hash = '#customers'; return; }
            var c = res.data;
            var html = '';

            html += '<div class="portal-back-link" onclick="location.hash=\'#customers\'">&#8592; Back to Customers</div>';
            html += '<div class="portal-section-header"><h1 class="portal-section-title">' + esc(c.name) + '</h1>';
            html += '<div class="portal-section-actions">' + badge(c.type) + ' <button class="portal-btn portal-btn-sm portal-btn-primary" id="btn-create-order">+ Create In-Store Order</button></div></div>';

            /* Info card */
            html += '<div class="portal-card">';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Email</span><span class="portal-detail-value">' + esc(c.email) + '</span></div>';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Phone</span><span class="portal-detail-value">' + esc(c.phone) + '</span></div>';
            html += '<div class="portal-detail-row"><span class="portal-detail-label">Gender</span><span class="portal-detail-value">' + esc(c.gender || 'Not set') + '</span></div>';
            html += '</div>';

            /* Measurements */
            html += '<div class="portal-card"><div class="portal-card-title">Measurements <button class="portal-btn portal-btn-sm portal-btn-outline" id="btn-edit-measurements" style="float:right;">Edit</button></div>';
            var hasMeasurements = false;
            html += '<div class="portal-measurements-grid" id="measurements-display">';
            if (c.fields && c.measurements) {
                c.fields.forEach(function (f) {
                    var val = c.measurements[f.key];
                    if (val) {
                        hasMeasurements = true;
                        html += '<div class="portal-measurement-item"><div class="portal-measurement-label">' + esc(f.label) + '</div>';
                        html += '<div class="portal-measurement-value">' + esc(val);
                        if (f.unit) html += ' <span class="portal-measurement-unit">' + esc(f.unit) + '</span>';
                        html += '</div></div>';
                    }
                });
            }
            if (!hasMeasurements) {
                html += '<div class="portal-empty" style="padding:1rem;"><p>No measurements recorded yet.</p></div>';
            }
            html += '</div></div>';

            /* Notes */
            html += '<div class="portal-card"><div class="portal-card-title">Notes / Remarques</div>';
            html += '<textarea class="portal-textarea" id="customer-notes" rows="3" placeholder="Add notes about this customer... / Ajoutez des remarques sur ce client..." style="width:100%;margin-bottom:0.5rem;">' + esc(c.notes || '') + '</textarea>';
            html += '<button class="portal-btn portal-btn-sm portal-btn-primary" id="btn-save-notes">Save Notes</button>';
            html += '</div>';

            /* Order history */
            html += '<div class="portal-card"><div class="portal-card-title">Order History</div>';
            if (c.orders.length === 0) {
                html += '<div class="portal-empty"><p>No orders yet.</p></div>';
            } else {
                html += '<div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>#</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>';
                c.orders.forEach(function (o) {
                    html += '<tr onclick="location.hash=\'#orders/' + o.id + '\'"><td>' + esc(o.number) + '</td><td>' + fmtDate(o.date) + '</td><td>' + money(o.total) + '</td><td>' + badge(o.status) + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
            html += '</div>';

            $main.innerHTML = html;

            /* Edit measurements button */
            document.getElementById('btn-edit-measurements').addEventListener('click', function () {
                showMeasurementsModal(c);
            });

            /* Save notes */
            document.getElementById('btn-save-notes').addEventListener('click', function () {
                var notes = document.getElementById('customer-notes').value;
                api('save_customer_notes', { customer_id: c.id, notes: notes }).then(function (r) {
                    if (r.success) toast('Notes saved.');
                    else toast(r.data.message, 'error');
                });
            });

            /* Create in-store order */
            document.getElementById('btn-create-order').addEventListener('click', function () {
                showInstoreOrderModal(c);
            });
        });
    }

    function showMeasurementsModal(customer) {
        var html = '<div class="portal-modal-title">Edit Measurements — ' + esc(customer.name) + '</div>';
        html += '<div id="meas-autosave-status" style="text-align:right;font-size:0.8rem;color:#8b7355;min-height:1.2em;margin-bottom:0.5rem;"></div>';
        html += '<div class="portal-form-group"><label>Gender</label><select class="portal-form-select" id="meas-gender" data-mkey="gender">';
        ['male', 'female', 'child'].forEach(function (g) {
            html += '<option value="' + g + '"' + (customer.gender === g ? ' selected' : '') + '>' + g.charAt(0).toUpperCase() + g.slice(1) + '</option>';
        });
        html += '</select></div>';

        if (customer.fields) {
            html += '<div class="portal-form-row" style="grid-template-columns:1fr 1fr 1fr;">';
            customer.fields.forEach(function (f) {
                if (f.key === 'gender') return;
                var val = customer.measurements[f.key] || '';
                html += '<div class="portal-form-group"><label>' + esc(f.label);
                if (f.unit) html += ' <small>(' + esc(f.unit) + ')</small>';
                html += '</label>';
                if (f.type === 'select' && f.options) {
                    html += '<select class="portal-form-select" data-mkey="' + f.key + '">';
                    html += '<option value="">-- Select --</option>';
                    var opts = f.options;
                    Object.keys(opts).forEach(function (ok) {
                        html += '<option value="' + esc(ok) + '"' + (val === ok ? ' selected' : '') + '>' + esc(opts[ok]) + '</option>';
                    });
                    html += '</select>';
                } else {
                    html += '<input class="portal-form-input" type="text" data-mkey="' + f.key + '" value="' + esc(val) + '">';
                }
                html += '</div>';
            });
            html += '</div>';
        }

        html += '<div class="portal-form-actions"><button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
        html += '<button class="portal-btn portal-btn-primary" id="meas-save">Save All & Close</button></div>';
        openModal(html);

        var modal = document.getElementById('portal-modal-content');
        var statusEl = document.getElementById('meas-autosave-status');

        /* Progressive auto-save: save each field as soon as the tailor leaves it */
        function autoSaveField(el) {
            var key = el.getAttribute('data-mkey');
            var val = el.value.trim();
            if (!key) return;
            var data = {};
            data[key] = val;
            statusEl.textContent = 'Saving...';
            api('save_measurements', { customer_id: customer.id, measurements_json: JSON.stringify(data) }).then(function (r) {
                if (r.success) {
                    statusEl.textContent = 'Saved';
                    setTimeout(function () { if (statusEl.textContent === 'Saved') statusEl.textContent = ''; }, 2000);
                } else {
                    statusEl.textContent = 'Save failed';
                }
            });
        }

        modal.querySelectorAll('[data-mkey]').forEach(function (el) {
            if (el.tagName === 'SELECT') {
                el.addEventListener('change', function () { autoSaveField(el); });
            } else {
                el.addEventListener('blur', function () { autoSaveField(el); });
            }
        });

        /* Save All & Close — saves everything at once then closes */
        document.getElementById('meas-save').addEventListener('click', function () {
            var measurements = {};
            modal.querySelectorAll('[data-mkey]').forEach(function (el) {
                var v = el.value.trim();
                measurements[el.getAttribute('data-mkey')] = v;
            });

            api('save_measurements', { customer_id: customer.id, measurements_json: JSON.stringify(measurements) }).then(function (r) {
                if (r.success) {
                    toast('All measurements saved!');
                    closeModal();
                    renderCustomerDetail(customer.id);
                } else {
                    toast(r.data.message, 'error');
                }
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       CONSULTATIONS
       ═══════════════════════════════════════════════════ */
    var consState = { page: 1, status: 'all' };

    function renderConsultations(sub) {
        if (sub === 'calendar') { renderCalendar(); return; }
        showLoading();
        api('consultations', { page: consState.page, status: consState.status }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            html += '<div class="portal-section-header"><h1 class="portal-section-title">Consultations</h1>';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm" onclick="location.hash=\'#consultations/calendar\'">Calendar View</button>';
            html += '</div>';

            /* Tabs */
            var tabs = [
                { value: 'all', label: 'All' },
                { value: 'pending', label: 'Pending' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
            ];
            html += '<div class="portal-tabs">';
            tabs.forEach(function (t) {
                html += '<button class="portal-tab' + (consState.status === t.value ? ' active' : '') + '" data-status="' + t.value + '">' + t.label + '</button>';
            });
            html += '</div>';

            /* Table */
            if (d.consultations.length === 0) {
                html += '<div class="portal-empty"><p>No consultations found.</p></div>';
            } else {
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>Customer</th><th>Phone</th><th>Date</th><th>Time</th><th>Status</th><th>Payment</th><th>Actions</th>';
                html += '</tr></thead><tbody>';
                d.consultations.forEach(function (c) {
                    html += '<tr>';
                    html += '<td>' + esc(c.name) + '</td>';
                    html += '<td>' + esc(c.phone) + '</td>';
                    html += '<td>' + fmtDate(c.date) + '</td>';
                    html += '<td>' + esc(c.time) + '</td>';
                    html += '<td>' + badge(c.status) + '</td>';
                    html += '<td>' + badge(c.payment_status) + '</td>';
                    html += '<td class="portal-consultation-actions">';
                    if (c.status === 'confirmed' || c.status === 'pending') {
                        html += '<button class="portal-btn portal-btn-sm portal-btn-success" data-bid="' + c.id + '" data-action="complete">Complete</button> ';
                        html += '<button class="portal-btn portal-btn-sm portal-btn-danger" data-bid="' + c.id + '" data-action="cancel">Cancel</button> ';
                    }
                    html += '<button class="portal-btn portal-btn-sm portal-btn-outline" data-bid="' + c.id + '" data-action="resend">Resend</button>';
                    html += '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }

            /* Pagination */
            if (d.pages > 1) {
                html += '<div class="portal-pagination">';
                html += '<button ' + (consState.page <= 1 ? 'disabled' : '') + ' data-page="' + (consState.page - 1) + '">Prev</button>';
                html += '<span class="portal-page-info">Page ' + consState.page + ' of ' + d.pages + '</span>';
                html += '<button ' + (consState.page >= d.pages ? 'disabled' : '') + ' data-page="' + (consState.page + 1) + '">Next</button>';
                html += '</div>';
            }

            $main.innerHTML = html;

            /* Event: tabs */
            $main.querySelectorAll('.portal-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    consState.status = this.getAttribute('data-status');
                    consState.page = 1;
                    renderConsultations();
                });
            });

            /* Event: actions */
            $main.querySelectorAll('[data-bid]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var bid = this.getAttribute('data-bid');
                    var action = this.getAttribute('data-action');
                    api('update_consultation', { booking_id: bid, consultation_action: action }).then(function (r) {
                        if (r.success) { toast(r.data.message); renderConsultations(); }
                        else toast(r.data.message, 'error');
                    });
                });
            });

            /* Event: pagination */
            $main.querySelectorAll('.portal-pagination button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    consState.page = parseInt(this.getAttribute('data-page'));
                    renderConsultations();
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       SEND EMAIL
       ═══════════════════════════════════════════════════ */
    function renderEmail() {
        showLoading();
        api('customers', { page: 1 }).then(function (custRes) {
            var customers = (custRes.success && custRes.data.customers) ? custRes.data.customers : [];

            var html = '';
            html += '<div class="portal-section-header"><h1 class="portal-section-title">Send Email</h1></div>';

            html += '<div class="portal-card" style="padding:1.5rem;">';

            /* Recipient selector */
            html += '<div class="portal-form-group"><label>Send To</label>';
            html += '<select class="portal-form-select" id="email-recipients">';
            html += '<option value="all">All Customers (' + customers.length + ')</option>';
            html += '<option value="" disabled>── Select a Customer ──</option>';
            customers.forEach(function (c) {
                var label = esc(c.name || 'Guest');
                if (c.email) label += ' — ' + esc(c.email);
                html += '<option value="' + c.id + '">' + label + '</option>';
            });
            html += '</select></div>';

            /* Search filter */
            html += '<div class="portal-form-group"><label>Or search by name/email</label>';
            html += '<input type="text" class="portal-form-input" id="email-search" placeholder="Start typing to filter the customer list...">';
            html += '</div>';

            /* Subject */
            html += '<div class="portal-form-group"><label>Subject <span style="color:#c62828;">*</span></label>';
            html += '<input type="text" class="portal-form-input" id="email-subject" placeholder="e.g. Your order is ready for pickup"></div>';

            /* Body */
            html += '<div class="portal-form-group"><label>Message <span style="color:#c62828;">*</span></label>';
            html += '<textarea class="portal-form-textarea" id="email-body" rows="8" placeholder="Write your email message here...\n\nYou can use:\n{customer_name} — replaced with customer name\n{store_name} — Kevin Cho"></textarea>';
            html += aiRewriteBtn('email-body');
            html += '</div>';

            /* Action buttons */
            html += '<div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">';
            html += '<button class="portal-btn portal-btn-primary" id="email-send">Send Email</button>';
            html += '<span id="email-status" style="font-size:0.85rem;color:#8b7355;"></span>';
            html += '</div>';

            html += '</div>';
            $main.innerHTML = html;

            /* Search filter — filters the dropdown options */
            var selectEl = document.getElementById('email-recipients');
            var allOptions = [];
            for (var i = 0; i < selectEl.options.length; i++) {
                allOptions.push({ value: selectEl.options[i].value, text: selectEl.options[i].text, disabled: selectEl.options[i].disabled });
            }

            document.getElementById('email-search').addEventListener('input', function () {
                var q = this.value.toLowerCase().trim();
                selectEl.innerHTML = '';
                allOptions.forEach(function (opt) {
                    if (!q || opt.value === 'all' || opt.disabled || opt.text.toLowerCase().indexOf(q) > -1) {
                        var o = document.createElement('option');
                        o.value = opt.value;
                        o.textContent = opt.text;
                        o.disabled = opt.disabled;
                        selectEl.appendChild(o);
                    }
                });
                /* Auto-select the first matching customer if searching */
                if (q && selectEl.options.length > 2) {
                    selectEl.selectedIndex = 2; /* skip "All" and separator */
                }
            });

            bindAiRewrites('email');

            /* Send handler */
            document.getElementById('email-send').addEventListener('click', function () {
                var subject = document.getElementById('email-subject').value.trim();
                var body = document.getElementById('email-body').value.trim();
                var recipients = selectEl.value;

                if (!subject || !body) {
                    toast('Subject and message are required.', 'error');
                    return;
                }

                var statusEl = document.getElementById('email-status');
                this.disabled = true;
                this.textContent = 'Sending...';
                statusEl.textContent = '';
                var btn = this;

                api('send_email', { subject: subject, body: body, recipients: recipients }).then(function (r) {
                    btn.disabled = false;
                    btn.textContent = 'Send Email';
                    if (r.success) {
                        toast(r.data.message);
                        statusEl.textContent = r.data.message;
                        document.getElementById('email-subject').value = '';
                        document.getElementById('email-body').value = '';
                    } else {
                        toast(r.data.message, 'error');
                        statusEl.textContent = '';
                    }
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       INVOICES
       ═══════════════════════════════════════════════════ */
    function renderInvoices() {
        showLoading();
        api('orders', { page: 1, status: 'any' }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            html += '<div class="portal-section-header"><h1 class="portal-section-title">Invoices</h1></div>';

            html += '<div class="portal-card">';
            if (d.orders.length === 0) {
                html += '<div class="portal-empty"><p>No orders found.</p></div>';
            } else {
                d.orders.forEach(function (o) {
                    html += '<div class="portal-invoice-row">';
                    html += '<div class="portal-invoice-info"><div class="portal-invoice-order">Order #' + esc(o.number) + ' — ' + esc(o.customer) + '</div>';
                    html += '<div class="portal-invoice-date">' + fmtDate(o.date) + ' &middot; ' + money(o.total) + ' &middot; ' + badge(o.status) + '</div></div>';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-outline" data-invoice-oid="' + o.id + '">Download PDF</button>';
                    html += '</div>';
                });
            }
            html += '</div>';

            $main.innerHTML = html;

            /* Event: download */
            $main.querySelectorAll('[data-invoice-oid]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var oid = this.getAttribute('data-invoice-oid');
                    api('invoice_url', { order_id: oid }).then(function (r) {
                        if (r.success && r.data.url) window.open(r.data.url, '_blank');
                        else toast('Invoice not available for this order.', 'error');
                    });
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       NOTIFICATIONS LOG
       ═══════════════════════════════════════════════════ */
    var notifState = { page: 1 };

    function renderNotifications() {
        showLoading();
        api('notifications', { page: notifState.page }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            html += '<div class="portal-section-header"><h1 class="portal-section-title">WhatsApp Notifications</h1></div>';

            if (d.notifications.length === 0) {
                html += '<div class="portal-empty"><p>No notifications sent yet.</p></div>';
            } else {
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>Date</th><th>Order</th><th>Phone</th><th>Status</th><th>Response</th>';
                html += '</tr></thead><tbody>';
                d.notifications.forEach(function (n) {
                    html += '<tr>';
                    html += '<td>' + fmtDate(n.sent_at) + '</td>';
                    html += '<td>#' + (n.order_id || '-') + '</td>';
                    html += '<td>' + esc(n.phone) + '</td>';
                    html += '<td>' + esc(n.status) + '</td>';
                    html += '<td>' + (n.response_code === 200 || n.response_code === 201 ? '<span class="portal-badge portal-badge-confirmed">Sent</span>' : '<span class="portal-badge portal-badge-failed">Code ' + n.response_code + '</span>') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }

            /* Pagination */
            if (d.pages > 1) {
                html += '<div class="portal-pagination">';
                html += '<button ' + (notifState.page <= 1 ? 'disabled' : '') + ' data-page="' + (notifState.page - 1) + '">Prev</button>';
                html += '<span class="portal-page-info">Page ' + notifState.page + ' of ' + d.pages + '</span>';
                html += '<button ' + (notifState.page >= d.pages ? 'disabled' : '') + ' data-page="' + (notifState.page + 1) + '">Next</button>';
                html += '</div>';
            }

            $main.innerHTML = html;

            /* Event: pagination */
            $main.querySelectorAll('.portal-pagination button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    notifState.page = parseInt(this.getAttribute('data-page'));
                    renderNotifications();
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       PRODUCTS
       ═══════════════════════════════════════════════════ */
    var prodState = { page: 1, search: '', category: '', status: '', sort: 'date', view: 'grid', selected: [] };

    function renderProducts(productId) {
        if (productId === 'new') { renderProductDetail(0); return; }
        if (productId === 'analytics') { renderProductAnalytics(); return; }
        if (productId === 'reviews') { renderReviews(); return; }
        if (productId) { renderProductDetail(productId); return; }

        showLoading();
        api('products', {
            page: prodState.page,
            search: prodState.search,
            category: prodState.category,
            status: prodState.status,
            orderby: prodState.sort
        }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            /* Header */
            html += '<div class="portal-section-header"><h2>Products</h2>';
            html += '<div class="portal-section-actions">';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm" onclick="location.hash=\'#products/reviews\'">Reviews</button>';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm" id="btn-product-analytics">Analytics</button>';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm" id="btn-quick-add">Quick Add</button>';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" onclick="location.hash=\'#products/new\'">+ Add Product</button>';
            html += '</div></div>';

            /* Filter bar */
            html += '<div class="portal-filter-bar">';
            html += '<input type="text" placeholder="Search products..." value="' + esc(prodState.search) + '" id="prod-search" class="portal-input">';
            html += '<select id="prod-cat-filter" class="portal-select">';
            html += '<option value="">All Categories</option>';
            (d.categories || []).forEach(function (c) {
                html += '<option value="' + c.id + '"' + (prodState.category == c.id ? ' selected' : '') + '>' + esc(c.name) + '</option>';
            });
            html += '</select>';
            html += '<select id="prod-status-filter" class="portal-select">';
            html += '<option value="">All Status</option>';
            html += '<option value="publish"' + (prodState.status === 'publish' ? ' selected' : '') + '>Published</option>';
            html += '<option value="draft"' + (prodState.status === 'draft' ? ' selected' : '') + '>Draft</option>';
            html += '</select>';
            html += '<div class="portal-view-toggle">';
            html += '<button class="' + (prodState.view === 'grid' ? 'active' : '') + '" data-view="grid" title="Grid view">&#9638;</button>';
            html += '<button class="' + (prodState.view === 'table' ? 'active' : '') + '" data-view="table" title="Table view">&#9776;</button>';
            html += '</div>';
            html += '</div>';

            /* Bulk action bar (hidden until selection) */
            html += '<div class="portal-bulk-bar" id="prod-bulk-bar" style="display:none;">';
            html += '<span id="prod-bulk-count">0 selected</span>';
            html += '<select id="prod-bulk-action" class="portal-select">';
            html += '<option value="">Bulk Actions</option>';
            html += '<option value="publish">Publish</option>';
            html += '<option value="draft">Move to Draft</option>';
            html += '<option value="delete">Delete</option>';
            html += '</select>';
            html += '<button class="portal-btn portal-btn-sm portal-btn-primary" id="prod-bulk-apply">Apply</button>';
            html += '</div>';

            /* Product list */
            if (d.products.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-icon">&#128722;</div>';
                html += '<div class="portal-empty-state-text">No products found.</div>';
                html += '<button class="portal-btn portal-btn-primary" onclick="location.hash=\'#products/new\'">Add Your First Product</button></div>';
            } else if (prodState.view === 'grid') {
                html += '<div class="portal-product-cards">';
                d.products.forEach(function (p) {
                    var sel = prodState.selected.indexOf(p.id) > -1;
                    html += '<div class="portal-product-card' + (sel ? ' selected' : '') + '" data-pid="' + p.id + '">';
                    html += '<div class="portal-product-card-select"><input type="checkbox" data-sel="' + p.id + '"' + (sel ? ' checked' : '') + '></div>';
                    html += '<div class="portal-product-card-image">';
                    if (p.image) {
                        html += '<img src="' + esc(p.image) + '" alt="' + esc(p.name) + '">';
                    } else {
                        html += '<div class="portal-product-card-placeholder">&#128085;</div>';
                    }
                    html += '</div>';
                    html += '<div class="portal-product-card-body">';
                    html += '<div class="portal-product-card-name">' + esc(p.name) + '</div>';
                    html += '<div class="portal-product-card-price">';
                    if (p.sale_price) {
                        html += '<span class="sale-price">' + money(p.sale_price) + '</span>';
                        html += '<span class="original-price">' + money(p.regular_price) + '</span>';
                    } else {
                        html += money(p.regular_price || p.price);
                    }
                    html += '</div>';
                    html += '<div class="portal-product-card-meta">';
                    html += '<span class="portal-product-badge portal-product-badge-' + esc(p.status) + '">' + esc(p.status === 'publish' ? 'Published' : 'Draft') + '</span>';
                    html += '<span class="portal-product-badge portal-product-badge-' + esc(p.stock_status) + '">' + esc(p.stock_status === 'instock' ? 'In Stock' : 'Out of Stock') + '</span>';
                    html += '</div>';
                    if (p.categories) html += '<div class="portal-product-card-cats">' + esc(p.categories) + '</div>';
                    html += '</div></div>';
                });
                html += '</div>';
            } else {
                /* Table view */
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th><input type="checkbox" id="prod-select-all"></th><th></th><th>Name</th><th>SKU</th><th>Price</th><th>Category</th><th>Stock</th><th>Status</th>';
                html += '</tr></thead><tbody>';
                d.products.forEach(function (p) {
                    var sel = prodState.selected.indexOf(p.id) > -1;
                    html += '<tr data-pid="' + p.id + '"' + (sel ? ' class="selected"' : '') + '>';
                    html += '<td><input type="checkbox" data-sel="' + p.id + '"' + (sel ? ' checked' : '') + '></td>';
                    html += '<td>';
                    if (p.image) html += '<img src="' + esc(p.image) + '" class="portal-product-thumb-sm">';
                    html += '</td>';
                    html += '<td><strong>' + esc(p.name) + '</strong></td>';
                    html += '<td>' + esc(p.sku || '-') + '</td>';
                    html += '<td>';
                    if (p.sale_price) {
                        html += '<span style="color:#e74c3c;">' + money(p.sale_price) + '</span> <s style="color:#8b7355;font-size:0.8em;">' + money(p.regular_price) + '</s>';
                    } else {
                        html += money(p.regular_price || p.price);
                    }
                    html += '</td>';
                    html += '<td>' + esc(p.categories || '-') + '</td>';
                    html += '<td><span class="portal-product-badge portal-product-badge-' + esc(p.stock_status) + '">' + esc(p.stock_status === 'instock' ? 'In Stock' : 'Out of Stock') + '</span></td>';
                    html += '<td><span class="portal-product-badge portal-product-badge-' + esc(p.status) + '">' + esc(p.status === 'publish' ? 'Published' : 'Draft') + '</span></td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }

            /* Pagination */
            if (d.pages > 1) {
                html += '<div class="portal-pagination">';
                html += '<button ' + (prodState.page <= 1 ? 'disabled' : '') + ' data-page="' + (prodState.page - 1) + '">Prev</button>';
                html += '<span class="portal-page-info">Page ' + prodState.page + ' of ' + d.pages + '</span>';
                html += '<button ' + (prodState.page >= d.pages ? 'disabled' : '') + ' data-page="' + (prodState.page + 1) + '">Next</button>';
                html += '</div>';
            }

            $main.innerHTML = html;

            /* Events: search */
            var searchTimer;
            var si = document.getElementById('prod-search');
            if (si) si.addEventListener('input', function () {
                clearTimeout(searchTimer);
                var v = this.value;
                searchTimer = setTimeout(function () {
                    prodState.search = v; prodState.page = 1; renderProducts();
                }, 500);
            });

            /* Events: filters */
            var cf = document.getElementById('prod-cat-filter');
            if (cf) cf.addEventListener('change', function () { prodState.category = this.value; prodState.page = 1; renderProducts(); });
            var sf = document.getElementById('prod-status-filter');
            if (sf) sf.addEventListener('change', function () { prodState.status = this.value; prodState.page = 1; renderProducts(); });

            /* Events: view toggle */
            $main.querySelectorAll('.portal-view-toggle button').forEach(function (btn) {
                btn.addEventListener('click', function () { prodState.view = this.getAttribute('data-view'); renderProducts(); });
            });

            /* Events: product click → detail */
            $main.querySelectorAll('[data-pid]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    if (e.target.tagName === 'INPUT') return;
                    location.hash = '#products/' + this.getAttribute('data-pid');
                });
            });

            /* Events: selection checkboxes */
            $main.querySelectorAll('input[data-sel]').forEach(function (cb) {
                cb.addEventListener('change', function (e) {
                    e.stopPropagation();
                    var pid = parseInt(this.getAttribute('data-sel'));
                    var idx = prodState.selected.indexOf(pid);
                    if (this.checked && idx === -1) prodState.selected.push(pid);
                    if (!this.checked && idx > -1) prodState.selected.splice(idx, 1);
                    updateBulkBar();
                    var card = this.closest('[data-pid]');
                    if (card) card.classList.toggle('selected', this.checked);
                });
            });

            /* Events: select all (table view) */
            var sa = document.getElementById('prod-select-all');
            if (sa) sa.addEventListener('change', function () {
                var checked = this.checked;
                prodState.selected = [];
                $main.querySelectorAll('input[data-sel]').forEach(function (cb) {
                    cb.checked = checked;
                    if (checked) prodState.selected.push(parseInt(cb.getAttribute('data-sel')));
                });
                updateBulkBar();
            });

            /* Events: bulk apply */
            var ba = document.getElementById('prod-bulk-apply');
            if (ba) ba.addEventListener('click', function () {
                var action = document.getElementById('prod-bulk-action').value;
                if (!action || prodState.selected.length === 0) { toast('Select products and an action.', 'error'); return; }
                if (action === 'delete' && !confirm('Delete ' + prodState.selected.length + ' product(s)?')) return;
                api('bulk_products', { product_ids: prodState.selected.join(','), bulk_action: action }).then(function (r) {
                    if (r.success) { toast(r.data.message); prodState.selected = []; renderProducts(); }
                    else toast(r.data.message, 'error');
                });
            });

            /* Events: pagination */
            $main.querySelectorAll('.portal-pagination button').forEach(function (btn) {
                btn.addEventListener('click', function () { prodState.page = parseInt(this.getAttribute('data-page')); renderProducts(); });
            });

            /* Events: quick add */
            var qa = document.getElementById('btn-quick-add');
            if (qa) qa.addEventListener('click', showQuickAddModal);

            /* Events: analytics */
            var an = document.getElementById('btn-product-analytics');
            if (an) an.addEventListener('click', function () { location.hash = '#products/analytics'; });

            function updateBulkBar() {
                var bar = document.getElementById('prod-bulk-bar');
                var cnt = document.getElementById('prod-bulk-count');
                if (bar) bar.style.display = prodState.selected.length > 0 ? 'flex' : 'none';
                if (cnt) cnt.textContent = prodState.selected.length + ' selected';
            }
            updateBulkBar();
        });
    }

    /* ── Product Detail / Edit ────────────────────────── */
    function renderProductDetail(productId) {
        showLoading();

        if (productId === 0 || productId === '0') {
            api('product_terms', {}).then(function (res) {
                buildProductForm({
                    id: 0, name: '', status: 'draft', type: 'simple', virtual: false, personalizable: false,
                    regular_price: '', sale_price: '', date_on_sale_from: '', date_on_sale_to: '',
                    description: '', short_description: '', sku: '', manage_stock: false,
                    stock_quantity: '', stock_status: 'instock', weight: '',
                    images: [], categories: [], tags: [],
                    attributes: [], variations: [],
                    all_categories: res.success ? res.data.all_categories : [],
                    all_tags: res.success ? res.data.all_tags : []
                });
            });
            return;
        }

        api('product_detail', { product_id: productId }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); location.hash = '#products'; return; }
            buildProductForm(res.data);
        });
    }

    /* ── Variable Product Helpers ──────────────────────── */

    function buildAttrRow(a, idx) {
        var options = (a.options || []).join(', ');
        var h = '<div class="portal-attr-row" data-attr-idx="' + idx + '" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:0.5rem;flex-wrap:wrap;">';
        h += '<input type="text" class="portal-input attr-name" placeholder="Attribute name (e.g. Color)" value="' + esc(a.name || '') + '" style="flex:1;min-width:120px;">';
        h += '<input type="text" class="portal-input attr-options" placeholder="Values (comma separated, e.g. Red, Blue, Green)" value="' + esc(options) + '" style="flex:2;min-width:200px;">';
        h += '<label style="white-space:nowrap;font-size:0.85rem;"><input type="checkbox" class="attr-variation"' + (a.variation !== false ? ' checked' : '') + '> For variations</label>';
        h += '<label style="white-space:nowrap;font-size:0.85rem;"><input type="checkbox" class="attr-visible"' + (a.visible !== false ? ' checked' : '') + '> Visible</label>';
        h += '<button class="portal-btn portal-btn-sm attr-remove" style="background:#e74c3c;color:#fff;">×</button>';
        h += '</div>';
        return h;
    }

    function buildVarRow(v, attrs) {
        var label = '';
        if (v.attributes) {
            var parts = [];
            for (var k in v.attributes) {
                if (v.attributes.hasOwnProperty(k)) parts.push(v.attributes[k]);
            }
            label = parts.join(' / ') || 'Variation #' + v.id;
        }
        var h = '<div class="portal-var-row" data-var-id="' + (v.id || 0) + '" style="border:1px solid #e0d6cc;border-radius:8px;margin-bottom:0.5rem;overflow:hidden;">';
        h += '<div class="portal-var-header" style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1rem;background:#faf7f3;cursor:pointer;">';
        h += '<strong>' + esc(label) + '</strong>';
        h += '<span style="display:flex;gap:0.5rem;align-items:center;">';
        h += '<span class="portal-text-muted" style="font-size:0.85rem;">' + esc(v.regular_price ? 'FCFA ' + v.regular_price : 'No price') + '</span>';
        h += '<button class="portal-btn portal-btn-sm var-remove" style="background:#e74c3c;color:#fff;padding:2px 8px;">×</button>';
        h += '</span></div>';
        h += '<div class="portal-var-body" style="display:none;padding:1rem;">';

        /* Attribute dropdowns */
        if (attrs && attrs.length) {
            h += '<div class="portal-form-row" style="margin-bottom:0.75rem;">';
            attrs.forEach(function (a) {
                if (!a.variation) return;
                var attrKey = a.name.toLowerCase().replace(/\s+/g, '-');
                var currentVal = (v.attributes && v.attributes[attrKey]) || '';
                h += '<div class="portal-form-group"><label class="portal-label">' + esc(a.name) + '</label>';
                h += '<select class="portal-select var-attr" data-attr-key="' + esc(attrKey) + '">';
                h += '<option value="">Any...</option>';
                (a.options || []).forEach(function (opt) {
                    var optStr = typeof opt === 'string' ? opt : String(opt);
                    h += '<option value="' + esc(optStr) + '"' + (currentVal === optStr ? ' selected' : '') + '>' + esc(optStr) + '</option>';
                });
                h += '</select></div>';
            });
            h += '</div>';
        }

        h += '<div class="portal-form-row">';
        h += '<div class="portal-form-group"><label class="portal-label">Regular Price (FCFA)</label><input type="number" step="1" class="portal-input var-price" value="' + esc(v.regular_price || '') + '"></div>';
        h += '<div class="portal-form-group"><label class="portal-label">Sale Price (FCFA)</label><input type="number" step="1" class="portal-input var-sale-price" value="' + esc(v.sale_price || '') + '"></div>';
        h += '</div>';
        h += '<div class="portal-form-row">';
        h += '<div class="portal-form-group"><label class="portal-label">SKU</label><input type="text" class="portal-input var-sku" value="' + esc(v.sku || '') + '"></div>';
        h += '<div class="portal-form-group"><label class="portal-label">Stock Quantity</label><input type="number" class="portal-input var-stock" value="' + esc(v.stock_quantity || '') + '"></div>';
        h += '</div>';
        if (v.image_url) {
            h += '<div style="margin-top:0.5rem;"><img src="' + esc(v.image_url) + '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;"></div>';
        }
        h += '</div></div>';
        return h;
    }

    function collectAttrsFromForm() {
        var rows = document.querySelectorAll('.portal-attr-row');
        var attrs = [];
        rows.forEach(function (row) {
            var name = row.querySelector('.attr-name').value.trim();
            var optStr = row.querySelector('.attr-options').value.trim();
            if (!name || !optStr) return;
            attrs.push({
                name: name,
                options: optStr.split(',').map(function (s) { return s.trim(); }).filter(Boolean),
                variation: row.querySelector('.attr-variation').checked,
                visible: row.querySelector('.attr-visible').checked
            });
        });
        return attrs;
    }

    function collectVarsFromForm() {
        var rows = document.querySelectorAll('.portal-var-row');
        var vars = [];
        rows.forEach(function (row) {
            var v = { id: parseInt(row.getAttribute('data-var-id')) || 0, attributes: {} };
            row.querySelectorAll('.var-attr').forEach(function (sel) {
                v.attributes[sel.getAttribute('data-attr-key')] = sel.value;
            });
            v.regular_price = row.querySelector('.var-price') ? row.querySelector('.var-price').value : '';
            v.sale_price = row.querySelector('.var-sale-price') ? row.querySelector('.var-sale-price').value : '';
            v.sku = row.querySelector('.var-sku') ? row.querySelector('.var-sku').value : '';
            v.stock_quantity = row.querySelector('.var-stock') ? row.querySelector('.var-stock').value : '';
            v.manage_stock = v.stock_quantity !== '';
            v.stock_status = 'instock';
            v.enabled = true;
            vars.push(v);
        });
        return vars;
    }

    function buildProductForm(p) {
        var isNew = !p.id;
        var html = '';

        html += '<button class="portal-back-link" onclick="location.hash=\'#products\'">&#8592; Back to Products</button>';
        html += '<div class="portal-section-header"><h2>' + (isNew ? 'Add New Product' : 'Edit: ' + esc(p.name)) + '</h2>';
        if (!isNew) {
            html += '<div class="portal-section-actions">';
            if (p.permalink) html += '<a href="' + esc(p.permalink) + '" target="_blank" class="portal-btn portal-btn-outline portal-btn-sm">View on Site</a>';
            html += '<button class="portal-btn portal-btn-sm" style="background:#e74c3c;color:#fff;" id="btn-delete-product">Delete</button>';
            html += '</div>';
        }
        html += '</div>';

        var isVariable = (p.type === 'variable');

        /* Tabs — dynamic based on product type */
        var tabs = ['General', 'Pricing', 'Description', 'Images', 'Inventory', 'Categories & Tags'];
        if (isVariable) {
            tabs = ['General', 'Attributes', 'Variations', 'Description', 'Images', 'Inventory', 'Categories & Tags'];
        }
        html += '<div class="portal-detail-tabs">';
        tabs.forEach(function (t, i) {
            html += '<button class="portal-detail-tab' + (i === 0 ? ' active' : '') + '" data-tab="' + i + '">' + t + '</button>';
        });
        html += '</div>';

        html += '<div class="portal-card" style="padding:1.5rem;">';

        /* Tab: General */
        html += '<div class="portal-detail-panel active" data-panel="0">';
        html += '<div class="portal-form-row">';
        html += '<div class="portal-form-group"><label class="portal-label">Product Name</label><input type="text" class="portal-input" id="pf-name" value="' + esc(p.name) + '"></div>';
        html += '<div class="portal-form-group"><label class="portal-label">Status</label><select class="portal-select" id="pf-status"><option value="publish"' + (p.status === 'publish' ? ' selected' : '') + '>Published</option><option value="draft"' + (p.status === 'draft' ? ' selected' : '') + '>Draft</option></select></div>';
        html += '</div>';
        html += '<div class="portal-form-row">';
        html += '<div class="portal-form-group"><label class="portal-label">Product Type</label><select class="portal-select" id="pf-product-type"><option value="simple"' + (!isVariable ? ' selected' : '') + '>Simple Product</option><option value="variable"' + (isVariable ? ' selected' : '') + '>Variable Product</option></select></div>';
        html += '<div class="portal-form-group"><label class="portal-label"><input type="checkbox" id="pf-virtual"' + (p.virtual ? ' checked' : '') + '> Virtual Product (no shipping)</label><br><label class="portal-label"><input type="checkbox" id="pf-personalizable"' + (p.personalizable ? ' checked' : '') + '> Enable Personalization</label></div>';
        html += '</div>';
        html += '</div>';

        var tabIdx = 1;

        if (isVariable) {
            /* Tab: Attributes */
            var attrs = p.attributes || [];
            html += '<div class="portal-detail-panel" data-panel="' + tabIdx++ + '">';
            html += '<p class="portal-text-muted" style="margin-bottom:1rem;">Add attributes like Color, Size, etc. Then use "Generate Variations" to create all combinations.</p>';
            html += '<div id="pf-attr-list">';
            attrs.forEach(function (a, ai) {
                html += buildAttrRow(a, ai);
            });
            html += '</div>';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm" id="pf-add-attr" style="margin-top:0.5rem;">+ Add Attribute</button>';
            html += '</div>';

            /* Tab: Variations */
            var variations = p.variations || [];
            html += '<div class="portal-detail-panel" data-panel="' + tabIdx++ + '">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">';
            html += '<span>' + variations.length + ' variation(s)</span>';
            html += '<button class="portal-btn portal-btn-sm portal-btn-primary" id="pf-gen-variations">Generate Variations from Attributes</button>';
            html += '</div>';
            html += '<div id="pf-var-list">';
            variations.forEach(function (v) {
                html += buildVarRow(v, attrs);
            });
            if (!variations.length) {
                html += '<p class="portal-text-muted">No variations yet. Add attributes first, then click "Generate Variations".</p>';
            }
            html += '</div>';
            html += '<button class="portal-btn portal-btn-primary" id="pf-save-vars" style="margin-top:1rem;">Save All Variations</button>';
            html += '</div>';
        }

        /* Tab: Pricing (only for simple products — variable skips this entirely) */
        if (!isVariable) {
            html += '<div class="portal-detail-panel" data-panel="' + tabIdx++ + '">';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Regular Price (FCFA)</label><input type="number" step="1" class="portal-input" id="pf-regular-price" value="' + esc(p.regular_price) + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Sale Price (FCFA)</label><input type="number" step="1" class="portal-input" id="pf-sale-price" value="' + esc(p.sale_price) + '"></div>';
            html += '</div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Sale Start Date</label><input type="date" class="portal-input" id="pf-sale-from" value="' + esc(p.date_on_sale_from) + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Sale End Date</label><input type="date" class="portal-input" id="pf-sale-to" value="' + esc(p.date_on_sale_to) + '"></div>';
            html += '</div>';
            html += '</div>';
        }

        /* Tab: Description */
        html += '<div class="portal-detail-panel" data-panel="' + tabIdx++ + '">';
        html += '<div class="portal-form-group"><label class="portal-label">Full Description</label><textarea class="portal-textarea" id="pf-description" rows="8">' + esc(p.description) + '</textarea>' + aiRewriteBtn('pf-description') + '</div>';
        html += '<div class="portal-form-group"><label class="portal-label">Short Description</label><textarea class="portal-textarea" id="pf-short-desc" rows="4">' + esc(p.short_description) + '</textarea>' + aiRewriteBtn('pf-short-desc') + '</div>';
        html += '</div>';

        /* Tab: Images */
        html += '<div class="portal-detail-panel" data-panel="' + tabIdx++ + '">';
        html += '<div class="portal-dropzone" id="pf-dropzone">';
        html += '<p>Drag & drop images here or <button type="button" class="portal-btn portal-btn-outline portal-btn-sm" id="pf-browse-btn">Browse</button></p>';
        html += '<input type="file" id="pf-file-input" multiple accept="image/*" style="display:none;">';
        html += '</div>';
        html += '<div class="portal-image-gallery" id="pf-gallery">';
        (p.images || []).forEach(function (img) {
            html += productImageThumb(img);
        });
        html += '</div>';
        html += '</div>';

        /* Tab: Inventory */
        html += '<div class="portal-detail-panel" data-panel="' + tabIdx++ + '">';
        html += '<div class="portal-form-row">';
        html += '<div class="portal-form-group"><label class="portal-label">SKU</label><input type="text" class="portal-input" id="pf-sku" value="' + esc(p.sku) + '"></div>';
        html += '<div class="portal-form-group"><label class="portal-label">Weight (kg)</label><input type="number" step="0.01" class="portal-input" id="pf-weight" value="' + esc(p.weight) + '"></div>';
        html += '</div>';
        html += '<div class="portal-form-row">';
        html += '<div class="portal-form-group"><label class="portal-label"><input type="checkbox" id="pf-manage-stock"' + (p.manage_stock ? ' checked' : '') + '> Manage Stock</label></div>';
        html += '<div class="portal-form-group" id="pf-stock-fields"' + (!p.manage_stock ? ' style="display:none;"' : '') + '>';
        html += '<label class="portal-label">Stock Quantity</label><input type="number" class="portal-input" id="pf-stock-qty" value="' + esc(p.stock_quantity || '') + '">';
        html += '</div>';
        html += '</div>';
        html += '<div class="portal-form-group"><label class="portal-label">Stock Status</label>';
        html += '<select class="portal-select" id="pf-stock-status">';
        html += '<option value="instock"' + (p.stock_status === 'instock' ? ' selected' : '') + '>In Stock</option>';
        html += '<option value="outofstock"' + (p.stock_status === 'outofstock' ? ' selected' : '') + '>Out of Stock</option>';
        html += '<option value="onbackorder"' + (p.stock_status === 'onbackorder' ? ' selected' : '') + '>On Backorder</option>';
        html += '</select></div>';
        html += '</div>';

        /* Tab: Categories & Tags */
        html += '<div class="portal-detail-panel" data-panel="' + tabIdx++ + '">';
        html += '<div class="portal-form-row">';

        /* Categories — rendered with parent/child hierarchy */
        html += '<div class="portal-form-group"><label class="portal-label">Categories</label>';
        html += '<div class="portal-category-list" id="pf-cat-list">';
        var pCatIds = (p.categories || []).map(function (c) { return c.id; });
        var allCats = p.all_categories || [];
        /* Build parent groups */
        var parentCats = allCats.filter(function (c) { return !c.parent || c.parent === 0; });
        var childCats = allCats.filter(function (c) { return c.parent && c.parent > 0; });
        parentCats.forEach(function (parent) {
            var children = childCats.filter(function (c) { return c.parent === parent.id; });
            if (children.length > 0) {
                html += '<div class="portal-cat-group" style="margin-bottom:0.5rem;">';
                html += '<label class="portal-category-item" style="font-weight:600;"><input type="checkbox" value="' + parent.id + '"' + (pCatIds.indexOf(parent.id) > -1 ? ' checked' : '') + '> ' + esc(parent.name) + '</label>';
                children.forEach(function (child) {
                    html += '<label class="portal-category-item" style="padding-left:1.5rem;"><input type="checkbox" value="' + child.id + '"' + (pCatIds.indexOf(child.id) > -1 ? ' checked' : '') + '> ' + esc(child.name) + '</label>';
                });
                html += '</div>';
            } else {
                html += '<label class="portal-category-item"><input type="checkbox" value="' + parent.id + '"' + (pCatIds.indexOf(parent.id) > -1 ? ' checked' : '') + '> ' + esc(parent.name) + '</label>';
            }
        });
        html += '</div>';
        html += '<div style="margin-top:0.5rem;display:flex;gap:0.4rem;">';
        html += '<input type="text" class="portal-input" id="pf-new-cat" placeholder="New category...">';
        html += '<button class="portal-btn portal-btn-sm portal-btn-outline" id="pf-add-cat">Add</button>';
        html += '</div></div>';

        /* Tags */
        html += '<div class="portal-form-group"><label class="portal-label">Tags</label>';
        html += '<div class="portal-tag-chips" id="pf-tag-chips">';
        (p.tags || []).forEach(function (t) {
            html += '<span class="portal-tag-chip" data-tid="' + t.id + '">' + esc(t.name) + ' <span class="portal-tag-chip-remove" data-tid="' + t.id + '">&times;</span></span>';
        });
        html += '</div>';
        html += '<div style="display:flex;gap:0.4rem;">';
        html += '<input type="text" class="portal-input" id="pf-new-tag" placeholder="Add tag..." list="pf-tag-datalist">';
        html += '<datalist id="pf-tag-datalist">';
        (p.all_tags || []).forEach(function (t) {
            html += '<option value="' + esc(t.name) + '" data-tid="' + t.id + '">';
        });
        html += '</datalist>';
        html += '<button class="portal-btn portal-btn-sm portal-btn-outline" id="pf-add-tag">Add</button>';
        html += '</div></div>';

        html += '</div>'; /* end form-row */
        html += '</div>'; /* end panel 5 */

        html += '</div>'; /* end card */

        /* Save button */
        html += '<div style="margin-top:1.25rem;display:flex;gap:0.75rem;">';
        html += '<button class="portal-btn portal-btn-primary" id="btn-save-product">Save Product</button>';
        html += '</div>';

        $main.innerHTML = html;

        /* Store current images for reorder/delete tracking */
        var currentImages = (p.images || []).slice();
        var currentTags = (p.tags || []).slice();

        /* Tab switching */
        $main.querySelectorAll('.portal-detail-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                $main.querySelectorAll('.portal-detail-tab').forEach(function (t) { t.classList.remove('active'); });
                $main.querySelectorAll('.portal-detail-panel').forEach(function (pnl) { pnl.classList.remove('active'); });
                this.classList.add('active');
                var panel = $main.querySelector('[data-panel="' + this.getAttribute('data-tab') + '"]');
                if (panel) panel.classList.add('active');
            });
        });

        /* Manage stock toggle */
        var ms = document.getElementById('pf-manage-stock');
        if (ms) ms.addEventListener('change', function () {
            document.getElementById('pf-stock-fields').style.display = this.checked ? '' : 'none';
        });

        /* Image drag-drop */
        var dz = document.getElementById('pf-dropzone');
        var fi = document.getElementById('pf-file-input');
        var bb = document.getElementById('pf-browse-btn');
        if (bb) bb.addEventListener('click', function () { fi.click(); });
        if (fi) fi.addEventListener('change', function () { uploadImages(this.files); this.value = ''; });
        if (dz) {
            dz.addEventListener('dragover', function (e) { e.preventDefault(); this.classList.add('dragover'); });
            dz.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
            dz.addEventListener('drop', function (e) { e.preventDefault(); this.classList.remove('dragover'); uploadImages(e.dataTransfer.files); });
        }

        function uploadImages(files) {
            if (!files || files.length === 0) return;
            var fd = new FormData();
            fd.append('action', 'kctm_portal_upload_product_image');
            fd.append('nonce', C.nonce);
            for (var i = 0; i < files.length; i++) {
                fd.append('product_image[]', files[i]);
            }

            /* Show progress bar */
            var dzEl = document.getElementById('pf-dropzone');
            var progressWrap = document.createElement('div');
            progressWrap.id = 'pf-upload-progress';
            progressWrap.style.cssText = 'margin-top:0.75rem;';
            progressWrap.innerHTML = '<div style="display:flex;align-items:center;gap:0.75rem;">' +
                '<div style="flex:1;background:#e0d6cc;border-radius:6px;height:8px;overflow:hidden;">' +
                '<div id="pf-progress-bar" style="width:0%;height:100%;background:#c9a96e;border-radius:6px;transition:width 0.2s;"></div></div>' +
                '<span id="pf-progress-text" style="font-size:0.85rem;color:#6b5a4e;min-width:80px;">0%</span></div>' +
                '<p id="pf-progress-label" style="font-size:0.8rem;color:#6b5a4e;margin-top:0.25rem;">Uploading ' + files.length + ' image(s)...</p>';
            var oldProgress = document.getElementById('pf-upload-progress');
            if (oldProgress) oldProgress.remove();
            dzEl.parentNode.insertBefore(progressWrap, dzEl.nextSibling);

            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    var pct = Math.round((e.loaded / e.total) * 100);
                    var bar = document.getElementById('pf-progress-bar');
                    var txt = document.getElementById('pf-progress-text');
                    var lbl = document.getElementById('pf-progress-label');
                    if (bar) bar.style.width = pct + '%';
                    if (txt) txt.textContent = pct + '%';
                    if (lbl && pct >= 100) lbl.textContent = 'Processing images on server...';
                }
            });
            xhr.addEventListener('load', function () {
                var pw = document.getElementById('pf-upload-progress');
                if (pw) pw.remove();
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (!res.success) { toast(res.data.message, 'error'); return; }
                    var gal = document.getElementById('pf-gallery');
                    res.data.images.forEach(function (img) {
                        img.is_featured = currentImages.length === 0;
                        currentImages.push(img);
                        gal.insertAdjacentHTML('beforeend', productImageThumb(img));
                    });
                    bindGalleryEvents();
                    toast(res.data.message);
                } catch (e) {
                    toast('Upload failed. Please try again.', 'error');
                }
            });
            xhr.addEventListener('error', function () {
                var pw = document.getElementById('pf-upload-progress');
                if (pw) pw.remove();
                toast('Upload failed. Check your connection.', 'error');
            });
            xhr.open('POST', C.ajax_url);
            xhr.withCredentials = true;
            xhr.send(fd);
        }

        /* Gallery events */
        function bindGalleryEvents() {
            $main.querySelectorAll('.portal-image-thumb-star').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var imgId = parseInt(this.getAttribute('data-imgid'));
                    currentImages.forEach(function (im) { im.is_featured = (im.id === imgId); });
                    refreshGallery();
                });
            });
            $main.querySelectorAll('.portal-image-thumb-del').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var imgId = parseInt(this.getAttribute('data-imgid'));
                    currentImages = currentImages.filter(function (im) { return im.id !== imgId; });
                    if (currentImages.length > 0 && !currentImages.some(function (im) { return im.is_featured; })) {
                        currentImages[0].is_featured = true;
                    }
                    refreshGallery();
                });
            });
        }

        function refreshGallery() {
            var gal = document.getElementById('pf-gallery');
            if (!gal) return;
            gal.innerHTML = '';
            currentImages.forEach(function (img) {
                gal.insertAdjacentHTML('beforeend', productImageThumb(img));
            });
            bindGalleryEvents();
        }
        bindGalleryEvents();

        /* Add category */
        var addCatBtn = document.getElementById('pf-add-cat');
        if (addCatBtn) addCatBtn.addEventListener('click', function () {
            var input = document.getElementById('pf-new-cat');
            var name = input.value.trim();
            if (!name) return;
            api('create_category', { category_name: name }).then(function (r) {
                if (!r.success) { toast(r.data.message, 'error'); return; }
                var list = document.getElementById('pf-cat-list');
                list.insertAdjacentHTML('beforeend', '<label class="portal-category-item"><input type="checkbox" value="' + r.data.category_id + '" checked> ' + esc(r.data.name) + '</label>');
                input.value = '';
                toast('Category created.');
            });
        });

        /* Add tag */
        var addTagBtn = document.getElementById('pf-add-tag');
        if (addTagBtn) addTagBtn.addEventListener('click', function () {
            var input = document.getElementById('pf-new-tag');
            var name = input.value.trim();
            if (!name) return;
            /* Check if tag already exists in datalist */
            var existing = null;
            $main.querySelectorAll('#pf-tag-datalist option').forEach(function (opt) {
                if (opt.value === name) existing = { id: parseInt(opt.getAttribute('data-tid')), name: name };
            });
            if (existing) {
                if (!currentTags.some(function (t) { return t.id === existing.id; })) {
                    currentTags.push(existing);
                    refreshTags();
                }
                input.value = '';
            } else {
                /* Create new tag via product_cat taxonomy? No — use product_tag. We'll just add it as text and let save handle it. */
                currentTags.push({ id: 0, name: name });
                refreshTags();
                input.value = '';
            }
        });

        function refreshTags() {
            var tc = document.getElementById('pf-tag-chips');
            tc.innerHTML = '';
            currentTags.forEach(function (t) {
                tc.insertAdjacentHTML('beforeend', '<span class="portal-tag-chip" data-tid="' + t.id + '">' + esc(t.name) + ' <span class="portal-tag-chip-remove" data-tid="' + t.id + '" data-tname="' + esc(t.name) + '">&times;</span></span>');
            });
            tc.querySelectorAll('.portal-tag-chip-remove').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tid = parseInt(this.getAttribute('data-tid'));
                    var tname = this.getAttribute('data-tname');
                    currentTags = currentTags.filter(function (t) {
                        return tid ? t.id !== tid : t.name !== tname;
                    });
                    refreshTags();
                });
            });
        }

        /* Tag remove initial */
        $main.querySelectorAll('.portal-tag-chip-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tid = parseInt(this.getAttribute('data-tid'));
                currentTags = currentTags.filter(function (t) { return t.id !== tid; });
                refreshTags();
            });
        });

        /* Delete product */
        var del = document.getElementById('btn-delete-product');
        if (del) del.addEventListener('click', function () {
            if (!confirm('Delete this product?')) return;
            api('delete_product', { product_ids: String(p.id), force: 'no' }).then(function (r) {
                if (r.success) { toast('Product deleted.'); location.hash = '#products'; }
                else toast(r.data.message, 'error');
            });
        });

        bindAiRewrites('product');

        /* ── Variable product event handlers ── */
        var ptypeEl = document.getElementById('pf-product-type');
        if (ptypeEl) {
            ptypeEl.addEventListener('change', function () {
                p.type = this.value;
                /* Rebuild form preserving entered values */
                var rebuiltP = Object.assign({}, p, {
                    name: document.getElementById('pf-name').value,
                    status: document.getElementById('pf-status').value,
                    virtual: document.getElementById('pf-virtual').checked,
                    personalizable: document.getElementById('pf-personalizable').checked,
                    description: document.getElementById('pf-description') ? document.getElementById('pf-description').value : p.description,
                    short_description: document.getElementById('pf-short-desc') ? document.getElementById('pf-short-desc').value : p.short_description,
                    images: currentImages,
                    attributes: collectAttrsFromForm().length ? collectAttrsFromForm() : (p.attributes || []),
                    variations: p.variations || []
                });
                buildProductForm(rebuiltP);
            });
        }

        /* Add attribute row */
        var addAttrBtn = document.getElementById('pf-add-attr');
        if (addAttrBtn) {
            addAttrBtn.addEventListener('click', function () {
                var list = document.getElementById('pf-attr-list');
                var idx = list.querySelectorAll('.portal-attr-row').length;
                list.insertAdjacentHTML('beforeend', buildAttrRow({ name: '', options: [], variation: true, visible: true }, idx));
            });
        }

        /* Remove attribute row */
        $main.addEventListener('click', function (e) {
            if (e.target.classList.contains('attr-remove')) {
                e.target.closest('.portal-attr-row').remove();
            }
        });

        /* Variation accordion toggle */
        $main.addEventListener('click', function (e) {
            var header = e.target.closest('.portal-var-header');
            if (header && !e.target.classList.contains('var-remove')) {
                var body = header.nextElementSibling;
                if (body) body.style.display = body.style.display === 'none' ? '' : 'none';
            }
        });

        /* Remove variation */
        $main.addEventListener('click', function (e) {
            if (e.target.classList.contains('var-remove')) {
                e.preventDefault();
                var row = e.target.closest('.portal-var-row');
                var varId = parseInt(row.getAttribute('data-var-id'));
                if (varId && confirm('Delete this variation?')) {
                    api('delete_variation', { variation_id: varId }).then(function (r) {
                        if (r.success) { row.remove(); toast('Variation deleted.'); }
                        else toast(r.data.message, 'error');
                    });
                } else if (!varId) {
                    row.remove();
                }
            }
        });

        /* Generate variations */
        var genVarBtn = document.getElementById('pf-gen-variations');
        if (genVarBtn) {
            genVarBtn.addEventListener('click', function () {
                if (!p.id) { toast('Save the product first before generating variations.', 'error'); return; }
                var attrs = collectAttrsFromForm();
                var varAttrs = attrs.filter(function (a) { return a.variation; });
                if (!varAttrs.length) { toast('Add attributes marked "For variations" first.', 'error'); return; }

                /* First save attributes, then generate variations */
                toast('Saving attributes and generating variations...');
                api('save_product', {
                    product_id: p.id, name: document.getElementById('pf-name').value,
                    status: document.getElementById('pf-status').value, product_type: 'variable',
                    attributes_json: JSON.stringify(attrs)
                }).then(function () {
                    /* Generate Cartesian product of attribute values */
                    var combos = [{}];
                    varAttrs.forEach(function (a) {
                        var key = a.name.toLowerCase().replace(/\s+/g, '-');
                        var newCombos = [];
                        a.options.forEach(function (opt) {
                            combos.forEach(function (c) {
                                var nc = Object.assign({}, c);
                                nc[key] = opt;
                                newCombos.push(nc);
                            });
                        });
                        combos = newCombos;
                    });

                    /* Create variations */
                    var newVars = combos.map(function (combo) {
                        return { id: 0, attributes: combo, regular_price: '', enabled: true };
                    });

                    api('save_variations', {
                        product_id: p.id, variations_json: JSON.stringify(newVars)
                    }).then(function (r) {
                        if (r.success) {
                            toast(r.data.message);
                            renderProductDetail(p.id); /* Reload the form */
                        } else {
                            toast(r.data.message, 'error');
                        }
                    });
                });
            });
        }

        /* Save all variations */
        var saveVarsBtn = document.getElementById('pf-save-vars');
        if (saveVarsBtn) {
            saveVarsBtn.addEventListener('click', function () {
                var vars = collectVarsFromForm();
                if (!vars.length) { toast('No variations to save.', 'error'); return; }
                toast('Saving variations...');
                api('save_variations', {
                    product_id: p.id, variations_json: JSON.stringify(vars)
                }).then(function (r) {
                    if (r.success) { toast(r.data.message); renderProductDetail(p.id); }
                    else toast(r.data.message, 'error');
                });
            });
        }

        /* Save product */
        document.getElementById('btn-save-product').addEventListener('click', function () {
            var featured = currentImages.find(function (im) { return im.is_featured; });
            var gallery = currentImages.filter(function (im) { return !im.is_featured; }).map(function (im) { return im.id; });

            /* Collect category IDs */
            var catIds = [];
            $main.querySelectorAll('#pf-cat-list input[type="checkbox"]:checked').forEach(function (cb) {
                catIds.push(cb.value);
            });

            /* Collect tag IDs */
            var tagIds = currentTags.filter(function (t) { return t.id > 0; }).map(function (t) { return t.id; });

            var productType = document.getElementById('pf-product-type').value;
            var rpEl = document.getElementById('pf-regular-price');
            var spEl = document.getElementById('pf-sale-price');
            var sfEl = document.getElementById('pf-sale-from');
            var stEl = document.getElementById('pf-sale-to');

            var params = {
                product_id: p.id || 0,
                product_type: productType,
                name: document.getElementById('pf-name').value,
                status: document.getElementById('pf-status').value,
                virtual: document.getElementById('pf-virtual').checked ? 'yes' : 'no',
                personalizable: document.getElementById('pf-personalizable').checked ? 'yes' : 'no',
                regular_price: rpEl ? rpEl.value : '',
                sale_price: spEl ? spEl.value : '',
                date_on_sale_from: sfEl ? sfEl.value : '',
                date_on_sale_to: stEl ? stEl.value : '',
                description: document.getElementById('pf-description').value,
                short_description: document.getElementById('pf-short-desc').value,
                sku: document.getElementById('pf-sku').value,
                manage_stock: document.getElementById('pf-manage-stock').checked ? 'yes' : 'no',
                stock_quantity: document.getElementById('pf-stock-qty').value,
                stock_status: document.getElementById('pf-stock-status').value,
                weight: document.getElementById('pf-weight').value,
                featured_image_id: featured ? featured.id : 0,
                gallery_image_ids: gallery.join(','),
                'category_ids': catIds,
                'tag_ids': tagIds,
                'attributes_json': JSON.stringify(collectAttrsFromForm())
            };

            api('save_product', params).then(function (r) {
                if (r.success) {
                    toast(r.data.message);
                    if (!p.id && r.data.product_id) {
                        location.hash = '#products/' + r.data.product_id;
                    } else {
                        renderProductDetail(r.data.product_id);
                    }
                } else {
                    toast(r.data.message, 'error');
                }
            });
        });
    }

    function productImageThumb(img) {
        var html = '<div class="portal-image-thumb' + (img.is_featured ? ' featured' : '') + '">';
        html += '<img src="' + esc(img.thumbnail || img.url) + '">';
        html += '<div class="portal-image-thumb-actions">';
        html += '<button class="portal-image-thumb-star" data-imgid="' + img.id + '" title="Set as featured">&#9733;</button>';
        html += '<button class="portal-image-thumb-del" data-imgid="' + img.id + '" title="Remove">&times;</button>';
        html += '</div></div>';
        return html;
    }

    /* ── Quick Add Modal ─────────────────────────────── */
    function showQuickAddModal() {
        var html = '<h3 style="margin:0 0 1rem;">Quick Add Product</h3>';
        html += '<div class="portal-form-group"><label class="portal-label">Name</label><input type="text" class="portal-input" id="qa-name"></div>';
        html += '<div class="portal-form-group"><label class="portal-label">Price (' + esc(C.currency) + ')</label><input type="number" step="0.01" class="portal-input" id="qa-price"></div>';
        html += '<div class="portal-form-group"><label class="portal-label">Status</label><select class="portal-select" id="qa-status"><option value="publish">Published</option><option value="draft">Draft</option></select></div>';
        html += '<div class="portal-form-group"><label class="portal-label">Image (optional)</label><input type="file" id="qa-image" accept="image/*"></div>';
        html += '<div style="margin-top:1rem;display:flex;gap:0.5rem;">';
        html += '<button class="portal-btn portal-btn-primary" id="qa-save">Create</button>';
        html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
        html += '</div>';
        openModal(html);

        document.getElementById('qa-save').addEventListener('click', function () {
            var name = document.getElementById('qa-name').value.trim();
            var price = document.getElementById('qa-price').value;
            var status = document.getElementById('qa-status').value;
            if (!name) { toast('Product name is required.', 'error'); return; }

            var fileInput = document.getElementById('qa-image');
            var file = fileInput.files[0];

            function createProduct(imageId) {
                api('save_product', {
                    name: name, regular_price: price, status: status,
                    featured_image_id: imageId || 0
                }).then(function (r) {
                    if (r.success) { toast('Product created!'); closeModal(); renderProducts(); }
                    else toast(r.data.message, 'error');
                });
            }

            if (file) {
                var fd = new FormData();
                fd.append('action', 'kctm_portal_upload_product_image');
                fd.append('nonce', C.nonce);
                fd.append('product_image', file);
                fetch(C.ajax_url, { method: 'POST', credentials: 'same-origin', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success && res.data.images.length) {
                            createProduct(res.data.images[0].id);
                        } else {
                            createProduct(0);
                        }
                    });
            } else {
                createProduct(0);
            }
        });
    }

    /* ── Product Analytics ────────────────────────────── */
    function renderProductAnalytics() {
        showLoading();
        api('product_analytics', { period: '30' }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '';

            html += '<button class="portal-back-link" onclick="location.hash=\'#products\'">&#8592; Back to Products</button>';
            html += '<div class="portal-section-header"><h2>Product Analytics</h2></div>';

            /* Period selector */
            html += '<div style="margin-bottom:1rem;display:flex;gap:0.5rem;">';
            ['7', '30', '90'].forEach(function (p) {
                html += '<button class="portal-btn portal-btn-sm' + (String(d.period) === p ? ' portal-btn-primary' : ' portal-btn-outline') + '" data-period="' + p + '">' + p + ' days</button>';
            });
            html += '</div>';

            if (d.top_products.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No product sales data for this period.</div></div>';
            } else {
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>#</th><th></th><th>Product</th><th>Units Sold</th><th>Orders</th><th>Revenue</th>';
                html += '</tr></thead><tbody>';
                d.top_products.forEach(function (p, i) {
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td>';
                    if (p.image) html += '<img src="' + esc(p.image) + '" class="portal-product-thumb-sm">';
                    html += '</td>';
                    html += '<td><strong>' + esc(p.name) + '</strong></td>';
                    html += '<td>' + p.quantity + '</td>';
                    html += '<td>' + p.order_count + '</td>';
                    html += '<td>' + money(p.revenue) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }

            $main.innerHTML = html;

            /* Period buttons */
            $main.querySelectorAll('[data-period]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var period = this.getAttribute('data-period');
                    showLoading();
                    api('product_analytics', { period: period }).then(function (res2) {
                        if (res2.success) {
                            d = res2.data;
                            renderProductAnalytics();
                        }
                    });
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       ANALYTICS (Feature 2)
       ═══════════════════════════════════════════════════ */
    var analyticsState = { period: 30, charts: {} };

    function renderAnalytics(sub) {
        showLoading();
        api('analytics', { days: analyticsState.period }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<div class="portal-section-header"><h2>Analytics</h2>';
            html += '<div class="portal-period-selector">';
            [7, 30, 90, 365].forEach(function (p) {
                html += '<button class="portal-period-btn' + (analyticsState.period === p ? ' active' : '') + '" data-days="' + p + '">' + (p === 365 ? '1Y' : p + 'D') + '</button>';
            });
            html += '</div></div>';

            /* KPI cards */
            html += '<div class="portal-stats">';
            html += '<div class="portal-stat-card" style="cursor:pointer;" id="analytics-revenue"><div class="portal-stat-label">Revenue</div><div class="portal-stat-value">' + money(d.total_revenue) + '</div></div>';
            html += '<div class="portal-stat-card" style="cursor:pointer;" id="analytics-orders"><div class="portal-stat-label">Orders</div><div class="portal-stat-value">' + d.total_orders + '</div></div>';
            html += '<div class="portal-stat-card"><div class="portal-stat-label">Avg Order Value</div><div class="portal-stat-value">' + money(d.average_order_value) + '</div></div>';
            html += '<div class="portal-stat-card" style="cursor:pointer;" onclick="location.hash=\'#customers\'"><div class="portal-stat-label">Top Customers</div><div class="portal-stat-value">' + (d.top_customers ? d.top_customers.length : 0) + '</div></div>';
            html += '</div>';

            /* Charts */
            html += '<div class="portal-analytics-grid">';
            html += '<div class="portal-chart-card"><div class="portal-chart-title">Revenue Over Time</div><canvas id="chart-revenue"></canvas></div>';
            html += '<div class="portal-chart-card"><div class="portal-chart-title">Orders Over Time</div><canvas id="chart-orders"></canvas></div>';
            html += '<div class="portal-chart-card"><div class="portal-chart-title">Sales by Category</div><canvas id="chart-categories"></canvas></div>';
            html += '<div class="portal-chart-card"><div class="portal-chart-title">Revenue by Currency</div><canvas id="chart-currencies"></canvas></div>';
            html += '</div>';

            /* Export */
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm portal-export-btn" id="btn-export-analytics"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export CSV</button>';

            $main.innerHTML = html;

            /* Draw charts */
            if (typeof Chart !== 'undefined') {
                Object.values(analyticsState.charts).forEach(function (c) { if (c) c.destroy(); });
                var chartOpts = { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } } };

                if (d.revenue_by_day && d.revenue_by_day.length) {
                    analyticsState.charts.revenue = new Chart(document.getElementById('chart-revenue'), {
                        type: 'line', data: { labels: d.revenue_by_day.map(function (r) { return r.date; }), datasets: [{ data: d.revenue_by_day.map(function (r) { return r.revenue; }), borderColor: '#c9a96e', backgroundColor: 'rgba(201,169,110,0.1)', fill: true, tension: 0.3 }] }, options: chartOpts
                    });
                    analyticsState.charts.orders = new Chart(document.getElementById('chart-orders'), {
                        type: 'bar', data: { labels: d.revenue_by_day.map(function (r) { return r.date; }), datasets: [{ data: d.revenue_by_day.map(function (r) { return r.orders; }), backgroundColor: '#402417', borderRadius: 4 }] }, options: chartOpts
                    });
                }
                if (d.revenue_by_category && d.revenue_by_category.length) {
                    var catColors = ['#c9a96e', '#402417', '#8b7355', '#e8ddd0', '#d4c5b0', '#f5f0eb'];
                    analyticsState.charts.categories = new Chart(document.getElementById('chart-categories'), {
                        type: 'bar', data: { labels: d.revenue_by_category.map(function (c) { return c.category; }), datasets: [{ data: d.revenue_by_category.map(function (c) { return c.revenue; }), backgroundColor: catColors }] }, options: chartOpts
                    });
                }
                if (d.revenue_by_currency && d.revenue_by_currency.length) {
                    var curColors = ['#c9a96e', '#402417', '#8b7355', '#e8ddd0'];
                    analyticsState.charts.currencies = new Chart(document.getElementById('chart-currencies'), {
                        type: 'doughnut', data: { labels: d.revenue_by_currency.map(function (c) { return c.currency; }), datasets: [{ data: d.revenue_by_currency.map(function (c) { return c.revenue; }), backgroundColor: curColors }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                    });
                }
            }

            /* Period buttons */
            $main.querySelectorAll('.portal-period-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    analyticsState.period = parseInt(this.getAttribute('data-days'));
                    renderAnalytics();
                });
            });

            /* Export */
            var expBtn = document.getElementById('btn-export-analytics');
            if (expBtn) expBtn.addEventListener('click', function () { exportCSV('analytics'); });

            /* Analytics card click handlers */
            var ar = document.getElementById('analytics-revenue');
            if (ar) ar.addEventListener('click', function () { ordersState.status = 'any'; ordersState.date_filter = 'this_month'; ordersState.page = 1; location.hash = '#orders'; });
            var ao = document.getElementById('analytics-orders');
            if (ao) ao.addEventListener('click', function () { ordersState.status = 'any'; ordersState.date_filter = 'this_month'; ordersState.page = 1; location.hash = '#orders'; });
        });
    }

    /* ═══════════════════════════════════════════════════
       PRODUCTION KANBAN (Feature 3)
       ═══════════════════════════════════════════════════ */
    var stages = [
        { key: 'fabric_cutting', label: 'Fabric Cutting' },
        { key: 'stitching', label: 'Stitching' },
        { key: 'finishing', label: 'Finishing' },
        { key: 'quality_check', label: 'Quality Check' },
        { key: 'ready_pickup', label: 'Ready for Pickup' }
    ];

    function renderProduction() {
        showLoading();
        api('production_board').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<div class="portal-section-header"><h2>Production Board</h2></div>';
            html += '<div class="portal-kanban">';

            var board = d.board || {};
            stages.forEach(function (stage) {
                var cards = board[stage.key] || [];
                html += '<div class="portal-kanban-column" data-stage="' + stage.key + '">';
                html += '<div class="portal-kanban-header">' + esc(stage.label) + '<span class="portal-kanban-count">' + cards.length + '</span></div>';
                html += '<div class="portal-kanban-cards" data-stage="' + stage.key + '">';
                cards.forEach(function (o) {
                    html += '<div class="portal-kanban-card" draggable="true" data-order-id="' + o.id + '">';
                    html += '<div class="portal-kanban-card-top">';
                    html += '<div class="portal-kanban-card-order">#' + (o.number || o.id) + '</div>';
                    html += '<button class="portal-kanban-card-detail-btn" data-order-id="' + o.id + '" title="View details">&#9776;</button>';
                    html += '</div>';
                    html += '<div class="portal-kanban-card-customer">' + esc(o.customer) + '</div>';
                    html += '<div class="portal-kanban-card-items">' + esc(o.items_summary || '') + '</div>';
                    html += '<div class="portal-kanban-card-footer">';
                    var tailorName = o.assigned_tailor ? o.assigned_tailor.name : '';
                    if (tailorName) html += '<span class="portal-kanban-card-tailor">' + esc(tailorName) + '</span>';
                    else html += '<button class="portal-btn portal-btn-sm portal-btn-outline assign-tailor-btn" data-order-id="' + o.id + '">Assign</button>';
                    html += '</div></div>';
                });
                html += '</div></div>';
            });
            html += '</div>';
            $main.innerHTML = html;

            /* Drag and drop */
            var draggedCard = null;
            $main.querySelectorAll('.portal-kanban-card').forEach(function (card) {
                card.addEventListener('dragstart', function (e) {
                    draggedCard = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });
                card.addEventListener('dragend', function () {
                    this.classList.remove('dragging');
                    $main.querySelectorAll('.portal-kanban-column').forEach(function (col) { col.classList.remove('drag-over'); });
                });
            });

            $main.querySelectorAll('.portal-kanban-cards').forEach(function (zone) {
                zone.addEventListener('dragover', function (e) { e.preventDefault(); this.closest('.portal-kanban-column').classList.add('drag-over'); });
                zone.addEventListener('dragleave', function (e) {
                    if (!this.contains(e.relatedTarget)) this.closest('.portal-kanban-column').classList.remove('drag-over');
                });
                zone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    this.closest('.portal-kanban-column').classList.remove('drag-over');
                    if (!draggedCard) return;
                    var orderId = draggedCard.getAttribute('data-order-id');
                    var newStage = this.getAttribute('data-stage');
                    this.appendChild(draggedCard);
                    /* Update counts */
                    $main.querySelectorAll('.portal-kanban-column').forEach(function (col) {
                        var cnt = col.querySelectorAll('.portal-kanban-card').length;
                        col.querySelector('.portal-kanban-count').textContent = cnt;
                    });
                    api('update_production_stage', { order_id: orderId, stage: newStage }).then(function (r) {
                        if (r.success) toast('Stage updated');
                        else { toast(r.data.message, 'error'); renderProduction(); }
                    });
                });
            });

            /* Assign tailor */
            $main.querySelectorAll('.assign-tailor-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var orderId = this.getAttribute('data-order-id');
                    showAssignTailorModal(orderId);
                });
            });

            /* Order detail panel */
            $main.querySelectorAll('.portal-kanban-card-detail-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    showOrderDetailModal(this.getAttribute('data-order-id'));
                });
            });
        });
    }

    /* ── Order Detail Modal with Notes ────────────────── */
    function showOrderDetailModal(orderId) {
        openModal('<div style="text-align:center;padding:2rem;"><div class="portal-spinner"></div><p>Loading order details...</p></div>');
        api('order_notes', { order_id: orderId }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); closeModal(); return; }
            var d = res.data;
            var o = d.order || {};
            var notes = d.notes || [];
            var tailorName = (o.assigned_tailor && o.assigned_tailor.name) ? o.assigned_tailor.name : '';
            var tailorId = (o.assigned_tailor && o.assigned_tailor.id) ? o.assigned_tailor.id : 0;

            var stageLabel = (o.stage || 'pending').replace(/_/g, ' ');
            stageLabel = stageLabel.charAt(0).toUpperCase() + stageLabel.slice(1);

            var html = '<div class="portal-order-detail">';
            html += '<h3 style="margin:0 0 0.5rem;">Order #' + esc(o.number) + '</h3>';
            html += '<div class="portal-order-detail-meta">';
            html += '<div>' + esc(o.customer) + '</div>';
            html += '<div>' + badge(o.status) + ' &middot; Stage: <strong>' + esc(stageLabel) + '</strong></div>';
            html += '<div>' + money(o.total) + ' &middot; ' + fmtDate(o.date) + '</div>';
            html += '</div>';

            /* Assignment section */
            html += '<div class="portal-order-detail-assign">';
            if (tailorName) {
                html += '<div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">';
                html += '<span>Assigned to: <strong>' + esc(tailorName) + '</strong></span>';
                if (C.user_role !== 'tailor') {
                    html += '<button class="portal-btn portal-btn-sm portal-btn-outline" id="btn-reassign">Reassign</button>';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-danger" id="btn-unassign">Unassign</button>';
                }
                html += '</div>';
            } else {
                html += '<div>No tailor assigned. ';
                if (C.user_role !== 'tailor') html += '<button class="portal-btn portal-btn-sm portal-btn-primary" id="btn-assign-from-detail">Assign Tailor</button>';
                html += '</div>';
            }
            html += '</div>';

            /* Add note form */
            html += '<div class="portal-order-detail-note-form">';
            html += '<textarea class="portal-textarea" id="order-note-input" rows="2" placeholder="Add a note (delays, special instructions, status update...)"></textarea>';
            html += '<div style="display:flex;gap:0.5rem;margin-top:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" id="btn-add-note">Add Note</button>';
            html += aiRewriteBtn('order-note-input');
            html += '</div>';
            html += '</div>';

            /* Notes timeline */
            html += '<div class="portal-order-notes">';
            html += '<h4 style="margin:0 0 0.5rem;">Notes & Activity (' + notes.length + ')</h4>';
            if (notes.length === 0) {
                html += '<div style="color:#999;font-size:0.9rem;">No notes yet.</div>';
            } else {
                notes.forEach(function (n) {
                    html += '<div class="portal-order-note">';
                    html += '<div class="portal-order-note-header"><strong>' + esc(n.author) + '</strong> <span style="color:#999;">' + fmtDate(n.date) + '</span></div>';
                    html += '<div class="portal-order-note-content">' + esc(n.content) + '</div>';
                    html += '</div>';
                });
            }
            html += '</div>';

            html += '<div style="margin-top:1rem;text-align:right;">';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Close</button>';
            html += '</div></div>';
            openModal(html);

            bindAiRewrites('note');

            /* Add note handler */
            document.getElementById('btn-add-note').addEventListener('click', function () {
                var noteText = document.getElementById('order-note-input').value.trim();
                if (!noteText) { toast('Enter a note', 'error'); return; }
                api('add_order_note', { order_id: orderId, note: noteText }).then(function (r) {
                    if (r.success) { toast('Note added!'); showOrderDetailModal(orderId); }
                    else toast(r.data.message, 'error');
                });
            });

            /* Assign from detail */
            var assignBtn = document.getElementById('btn-assign-from-detail');
            if (assignBtn) assignBtn.addEventListener('click', function () {
                closeModal();
                showAssignTailorModal(orderId);
            });

            /* Reassign */
            var reassignBtn = document.getElementById('btn-reassign');
            if (reassignBtn) reassignBtn.addEventListener('click', function () {
                closeModal();
                showAssignTailorModal(orderId);
            });

            /* Unassign */
            var unassignBtn = document.getElementById('btn-unassign');
            if (unassignBtn) unassignBtn.addEventListener('click', function () {
                var reason = prompt('Reason for unassigning (optional):');
                if (reason === null) return; /* cancelled */
                api('unassign_tailor', { order_id: orderId, note: reason }).then(function (r) {
                    if (r.success) { toast('Tailor unassigned'); closeModal(); renderProduction(); }
                    else toast(r.data.message, 'error');
                });
            });
        });
    }

    function showAssignTailorModal(orderId) {
        api('staff', { role: 'tailor' }).then(function (res) {
            if (!res.success) return;
            var html = '<h3 style="margin:0 0 1rem;">Assign Tailor</h3>';
            html += '<div class="portal-form-group"><label class="portal-label">Tailor</label>';
            html += '<select class="portal-select" id="assign-tailor-select">';
            html += '<option value="">Select tailor...</option>';
            (res.data.staff || []).forEach(function (s) {
                html += '<option value="' + s.id + '">' + esc(s.name) + (s.specialization ? ' (' + esc(s.specialization) + ')' : '') + '</option>';
            });
            html += '</select></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Note (optional)</label>';
            html += '<input type="text" class="portal-input" id="assign-note" placeholder="e.g. Rush order, priority client"></div>';
            html += '<div style="margin-top:1rem;display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary" id="btn-confirm-assign">Assign</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
            html += '</div>';
            openModal(html);
            document.getElementById('btn-confirm-assign').addEventListener('click', function () {
                var tailorId = document.getElementById('assign-tailor-select').value;
                var note = document.getElementById('assign-note').value;
                if (!tailorId) { toast('Select a tailor', 'error'); return; }
                api('assign_tailor', { order_id: orderId, staff_id: tailorId, note: note }).then(function (r) {
                    if (r.success) { toast(r.data.message || 'Tailor assigned'); closeModal(); renderProduction(); }
                    else toast(r.data.message, 'error');
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       COUPONS (Feature 1)
       ═══════════════════════════════════════════════════ */
    var couponsState = { page: 1 };

    function renderCoupons(sub) {
        if (sub === 'new' || (sub && sub !== 'new')) { renderCouponForm(sub === 'new' ? 0 : sub); return; }
        showLoading();
        api('coupons', { page: couponsState.page }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<div class="portal-section-header"><h2>Coupons</h2>';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" id="btn-add-coupon">+ New Coupon</button></div>';

            if (!d.coupons || d.coupons.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No coupons yet. Create your first coupon!</div></div>';
            } else {
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>Code</th><th>Type</th><th>Amount</th><th>Usage</th><th>Expiry</th><th></th>';
                html += '</tr></thead><tbody>';
                d.coupons.forEach(function (c) {
                    html += '<tr>';
                    html += '<td><span class="portal-coupon-code">' + esc(c.code) + '</span></td>';
                    html += '<td>' + badge(c.discount_type) + '</td>';
                    html += '<td>' + (c.discount_type === 'percent' ? c.amount + '%' : money(c.amount)) + '</td>';
                    html += '<td>' + c.usage_count + (c.usage_limit ? '/' + c.usage_limit : '') + '</td>';
                    html += '<td>' + (c.expiry ? fmtDate(c.expiry) : 'No expiry') + '</td>';
                    html += '<td><button class="portal-btn portal-btn-sm portal-btn-outline edit-coupon" data-id="' + c.id + '">Edit</button> ';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-danger del-coupon" data-id="' + c.id + '">Delete</button></td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }
            $main.innerHTML = html;

            document.getElementById('btn-add-coupon').addEventListener('click', function () { location.hash = '#coupons/new'; });
            $main.querySelectorAll('.edit-coupon').forEach(function (btn) {
                btn.addEventListener('click', function () { location.hash = '#coupons/' + this.getAttribute('data-id'); });
            });
            $main.querySelectorAll('.del-coupon').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('Delete this coupon?')) return;
                    api('delete_coupon', { coupon_id: this.getAttribute('data-id') }).then(function (r) {
                        if (r.success) { toast('Coupon deleted'); renderCoupons(); }
                        else toast(r.data.message, 'error');
                    });
                });
            });
        });
    }

    function renderCouponForm(couponId) {
        showLoading();
        var loadData = couponId && couponId !== 0 ? api('coupons', { coupon_id: couponId }) : Promise.resolve({ success: true, data: { coupons: [{}] } });
        loadData.then(function (res) {
            var c = (res.data.coupons && res.data.coupons[0]) || {};
            var html = '<button class="portal-back-link" onclick="location.hash=\'#coupons\'">&#8592; Back to Coupons</button>';
            html += '<div class="portal-section-header"><h2>' + (c.id ? 'Edit Coupon' : 'New Coupon') + '</h2></div>';
            html += '<div class="portal-card" style="padding:1.5rem;">';
            html += '<div class="portal-form-group"><label class="portal-label">Coupon Code</label><input type="text" class="portal-input" id="cf-code" value="' + esc(c.code || '') + '" style="text-transform:uppercase;"></div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Discount Type</label><select class="portal-select" id="cf-type">';
            ['percent', 'fixed_cart', 'fixed_product'].forEach(function (t) {
                html += '<option value="' + t + '"' + (c.discount_type === t ? ' selected' : '') + '>' + (t === 'percent' ? 'Percentage' : t === 'fixed_cart' ? 'Fixed Cart' : 'Fixed Product') + '</option>';
            });
            html += '</select></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Amount</label><input type="number" step="0.01" class="portal-input" id="cf-amount" value="' + esc(c.amount || '') + '"></div>';
            html += '</div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Usage Limit</label><input type="number" class="portal-input" id="cf-limit" value="' + esc(c.usage_limit || '') + '" placeholder="Unlimited"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Per-User Limit</label><input type="number" class="portal-input" id="cf-per-user" value="' + esc(c.usage_limit_per_user || '') + '" placeholder="Unlimited"></div>';
            html += '</div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Min Order Amount</label><input type="number" step="0.01" class="portal-input" id="cf-min" value="' + esc(c.minimum_amount || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Expiry Date</label><input type="date" class="portal-input" id="cf-expiry" value="' + esc(c.expiry || '') + '"></div>';
            html += '</div>';
            html += '<div class="portal-form-group"><label class="portal-label"><input type="checkbox" id="cf-free-shipping"' + (c.free_shipping ? ' checked' : '') + '> Allow Free Shipping</label></div>';
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-coupon">Save Coupon</button>';
            html += '</div>';
            $main.innerHTML = html;

            document.getElementById('btn-save-coupon').addEventListener('click', function () {
                api('save_coupon', {
                    coupon_id: c.id || 0,
                    code: document.getElementById('cf-code').value,
                    discount_type: document.getElementById('cf-type').value,
                    amount: document.getElementById('cf-amount').value,
                    usage_limit: document.getElementById('cf-limit').value,
                    usage_limit_per_user: document.getElementById('cf-per-user').value,
                    minimum_amount: document.getElementById('cf-min').value,
                    expiry_date: document.getElementById('cf-expiry').value,
                    free_shipping: document.getElementById('cf-free-shipping').checked ? 'yes' : 'no'
                }).then(function (r) {
                    if (r.success) { toast('Coupon saved!'); location.hash = '#coupons'; }
                    else toast(r.data.message, 'error');
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       REVIEWS (Feature 5 — sub-route of Products)
       ═══════════════════════════════════════════════════ */
    function renderReviews() {
        showLoading();
        api('reviews').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<button class="portal-back-link" onclick="location.hash=\'#products\'">&#8592; Back to Products</button>';
            html += '<div class="portal-section-header"><h2>Reviews & Ratings</h2></div>';

            if (d.stats) {
                html += '<div class="portal-stats">';
                html += '<div class="portal-stat-card"><div class="portal-stat-label">Average Rating</div><div class="portal-stat-value">' + renderStars(d.stats.average) + '</div><div style="font-size:0.85rem;color:#8b7355;margin-top:0.25rem;">' + parseFloat(d.stats.average).toFixed(1) + ' / 5</div></div>';
                html += '<div class="portal-stat-card"><div class="portal-stat-label">Total Reviews</div><div class="portal-stat-value">' + d.stats.total + '</div></div>';
                html += '<div class="portal-stat-card"><div class="portal-stat-label">Pending</div><div class="portal-stat-value">' + (d.stats.pending || 0) + '</div></div>';
                html += '</div>';
            }

            if (!d.reviews || d.reviews.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No reviews yet.</div></div>';
            } else {
                d.reviews.forEach(function (r) {
                    html += '<div class="portal-card" style="padding:1rem;margin-bottom:0.75rem;">';
                    html += '<div style="display:flex;justify-content:space-between;align-items:start;">';
                    html += '<div><strong>' + esc(r.author) + '</strong> on <em>' + esc(r.product_name) + '</em>';
                    html += '<div class="portal-stars" style="margin:0.25rem 0;">' + renderStars(r.rating) + '</div>';
                    html += '<p style="margin:0.5rem 0;color:#402417;">' + esc(r.content) + '</p>';
                    html += '<small style="color:#8b7355;">' + fmtDate(r.date) + '</small></div>';
                    html += '<div style="display:flex;gap:0.3rem;">';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-outline review-action" data-id="' + r.id + '" data-action="' + (r.approved ? 'unapprove' : 'approve') + '">' + (r.approved ? 'Unapprove' : 'Approve') + '</button>';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-outline review-action" data-id="' + r.id + '" data-action="reply">Reply</button>';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-danger review-action" data-id="' + r.id + '" data-action="delete">Delete</button>';
                    html += '</div></div>';
                    if (r.reply) html += '<div class="portal-review-reply"><strong>Store reply:</strong> ' + esc(r.reply) + '</div>';
                    html += '</div>';
                });
            }
            $main.innerHTML = html;

            $main.querySelectorAll('.review-action').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var reviewId = this.getAttribute('data-id');
                    var action = this.getAttribute('data-action');
                    if (action === 'delete') {
                        if (!confirm('Delete this review?')) return;
                        api('update_review', { review_id: reviewId, action: 'delete' }).then(function (r) {
                            if (r.success) { toast('Review deleted'); renderReviews(); }
                        });
                    } else if (action === 'reply') {
                        var reply = prompt('Enter your reply:');
                        if (reply) {
                            api('update_review', { review_id: reviewId, action: 'reply', reply: reply }).then(function (r) {
                                if (r.success) { toast('Reply added'); renderReviews(); }
                            });
                        }
                    } else {
                        api('update_review', { review_id: reviewId, action: action }).then(function (r) {
                            if (r.success) { toast('Review ' + action + 'd'); renderReviews(); }
                        });
                    }
                });
            });
        });
    }

    function renderStars(rating) {
        var r = Math.round(parseFloat(rating) || 0);
        var html = '';
        for (var i = 1; i <= 5; i++) {
            html += '<span class="' + (i <= r ? 'portal-stars' : 'portal-stars-empty') + '">&#9733;</span>';
        }
        return html;
    }

    /* ═══════════════════════════════════════════════════
       FABRICS (Feature 6)
       ═══════════════════════════════════════════════════ */
    function renderFabrics(sub) {
        showLoading();
        api('fabrics_list').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<div class="portal-section-header"><h2>Fabric Inventory</h2>';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" id="btn-add-fabric">+ Add Fabric</button></div>';

            if (!d.fabrics || d.fabrics.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No fabrics in inventory. Add your first fabric!</div></div>';
            } else {
                /* Low stock alert */
                var lowStock = d.fabrics.filter(function (f) { return f.stock_quantity !== null && f.low_stock_threshold && parseFloat(f.stock_quantity) <= parseFloat(f.low_stock_threshold); });
                if (lowStock.length > 0) {
                    html += '<div class="portal-card" style="background:#fef0ef;border:2px solid #e74c3c;padding:1rem;margin-bottom:1rem;">';
                    html += '<strong style="color:#e74c3c;">Low Stock Alert:</strong> ' + lowStock.map(function (f) { return esc(f.name); }).join(', ');
                    html += '</div>';
                }

                html += '<div class="portal-fabric-cards">';
                d.fabrics.forEach(function (f) {
                    html += '<div class="portal-fabric-card" data-id="' + f.id + '">';
                    if (f.swatch_url) html += '<img class="portal-fabric-swatch" src="' + esc(f.swatch_url) + '" alt="' + esc(f.name) + '">';
                    else if (f.color_hex) html += '<div class="portal-fabric-swatch" style="background-color:' + esc(f.color_hex) + ';"></div>';
                    else html += '<div class="portal-fabric-swatch" style="background:#f0e8dd;display:flex;align-items:center;justify-content:center;color:#8b7355;font-size:0.8rem;">No swatch</div>';
                    html += '<div class="portal-fabric-name">' + esc(f.name) + '</div>';
                    html += '<div class="portal-fabric-pattern">' + esc(f.pattern_type || '') + '</div>';
                    html += '<div class="portal-fabric-stock">';
                    if (f.stock_quantity !== null) {
                        html += '<span class="portal-fabric-stock-qty">' + f.stock_quantity + ' ' + esc(f.stock_unit || 'yards') + '</span>';
                        if (f.low_stock_threshold && parseFloat(f.stock_quantity) <= parseFloat(f.low_stock_threshold)) {
                            html += '<span class="portal-fabric-low-stock">LOW</span>';
                        }
                    } else {
                        html += '<span style="color:#8b7355;font-size:0.8rem;">Not tracked</span>';
                    }
                    html += '</div>';
                    if (f.price_modifier) html += '<div style="font-size:0.8rem;color:#8b7355;margin-top:0.3rem;">+' + money(f.price_modifier) + '</div>';
                    html += '</div>';
                });
                html += '</div>';
            }
            $main.innerHTML = html;

            document.getElementById('btn-add-fabric').addEventListener('click', function () { showFabricModal(); });
            $main.querySelectorAll('.portal-fabric-card').forEach(function (card) {
                card.addEventListener('click', function () { showFabricModal(this.getAttribute('data-id')); });
            });
        });
    }

    function showFabricModal(fabricId) {
        var loadData = fabricId ? api('fabrics_list', { fabric_id: fabricId }) : Promise.resolve({ success: true, data: { fabrics: [{}] } });
        loadData.then(function (res) {
            var f = (res.data.fabrics && res.data.fabrics[0]) || {};
            var html = '<h3 style="margin:0 0 1rem;">' + (f.id ? 'Edit Fabric' : 'Add Fabric') + '</h3>';
            html += '<div class="portal-form-group"><label class="portal-label">Name</label><input type="text" class="portal-input" id="ff-name" value="' + esc(f.name || '') + '"></div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Pattern Type</label><select class="portal-select" id="ff-pattern">';
            ['solid', 'striped', 'checkered', 'plaid', 'herringbone', 'paisley', 'floral', 'other'].forEach(function (p) {
                html += '<option value="' + p + '"' + ((f.pattern_type || 'solid') === p ? ' selected' : '') + '>' + p.charAt(0).toUpperCase() + p.slice(1) + '</option>';
            });
            html += '</select></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Color</label><input type="color" class="portal-input" id="ff-color" value="' + esc(f.color_hex || '#c9a96e') + '" style="height:42px;"></div>';
            html += '</div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Price Modifier</label><input type="number" step="0.01" class="portal-input" id="ff-price" value="' + esc(f.price_modifier || '0') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Supplier</label><input type="text" class="portal-input" id="ff-supplier" value="' + esc(f.supplier || '') + '"></div>';
            html += '</div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Stock Qty</label><input type="number" step="0.01" class="portal-input" id="ff-stock" value="' + esc(f.stock_quantity || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Unit</label><select class="portal-select" id="ff-unit">';
            ['yards', 'meters', 'rolls', 'pieces'].forEach(function (u) {
                html += '<option value="' + u + '"' + ((f.stock_unit || 'yards') === u ? ' selected' : '') + '>' + u + '</option>';
            });
            html += '</select></div>';
            html += '</div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Low Stock Alert</label><input type="number" step="0.01" class="portal-input" id="ff-threshold" value="' + esc(f.low_stock_threshold || '5') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Supplier</label><input type="text" class="portal-input" id="ff-supplier" value="' + esc(f.supplier || '') + '"></div>';
            html += '</div>';
            html += '<div style="margin-top:1rem;display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-fabric">Save</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
            html += '</div>';
            openModal(html);

            document.getElementById('btn-save-fabric').addEventListener('click', function () {
                api('save_fabric', {
                    fabric_id: f.id || 0,
                    name: document.getElementById('ff-name').value,
                    pattern_type: document.getElementById('ff-pattern').value,
                    color_hex: document.getElementById('ff-color').value,
                    price_modifier: document.getElementById('ff-price').value,
                    stock_quantity: document.getElementById('ff-stock').value,
                    stock_unit: document.getElementById('ff-unit').value,
                    low_stock_threshold: document.getElementById('ff-threshold').value,
                    supplier: document.getElementById('ff-supplier').value
                }).then(function (r) {
                    if (r.success) { toast('Fabric saved!'); closeModal(); renderFabrics(); }
                    else toast(r.data.message, 'error');
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       EXPENSES (Feature 7)
       ═══════════════════════════════════════════════════ */
    var expensesState = { page: 1, category: '' };

    function renderExpenses() {
        showLoading();
        Promise.all([
            api('expenses', { page: expensesState.page, category: expensesState.category }),
            api('expense_summary')
        ]).then(function (results) {
            var res = results[0];
            var summaryRes = results[1];
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            d.summary = summaryRes.success ? summaryRes.data : null;
            var html = '<div class="portal-section-header"><h2>Expenses</h2>';
            html += '<div style="display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" id="btn-add-expense">+ Add Expense</button>';
            html += '<button class="portal-btn portal-btn-outline portal-btn-sm portal-export-btn" id="btn-export-expenses"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> CSV</button>';
            html += '</div></div>';

            /* Summary */
            if (d.summary) {
                html += '<div class="portal-stats">';
                html += '<div class="portal-stat-card"><div class="portal-stat-label">Total Expenses</div><div class="portal-stat-value">' + money(d.summary.total_expenses) + '</div></div>';
                html += '<div class="portal-stat-card"><div class="portal-stat-label">Revenue</div><div class="portal-stat-value">' + money(d.summary.total_revenue) + '</div></div>';
                var profit = parseFloat(d.summary.profit || 0);
                html += '<div class="portal-stat-card"><div class="portal-stat-label">Profit</div><div class="portal-stat-value ' + (profit >= 0 ? 'portal-profit-positive' : 'portal-profit-negative') + '">' + money(profit) + '</div></div>';
                html += '</div>';
            }

            /* Filter */
            var cats = ['', 'fabric', 'rent', 'utilities', 'shipping', 'salary', 'marketing', 'other'];
            html += '<div class="portal-filter-bar" style="margin-bottom:1rem;display:flex;gap:0.3rem;">';
            cats.forEach(function (cat) {
                html += '<button class="portal-btn portal-btn-sm' + (expensesState.category === cat ? ' portal-btn-primary' : ' portal-btn-outline') + ' expense-cat-filter" data-cat="' + cat + '">' + (cat || 'All') + '</button>';
            });
            html += '</div>';

            var expenses = d.expenses || d.items || [];
            if (expenses.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No expenses recorded.</div></div>';
            } else {
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th></th>';
                html += '</tr></thead><tbody>';
                expenses.forEach(function (e) {
                    html += '<tr>';
                    html += '<td>' + fmtDate(e.expense_date) + '</td>';
                    html += '<td>' + badge(e.category) + '</td>';
                    html += '<td>' + esc(e.description) + '</td>';
                    html += '<td><strong>' + money(e.amount) + '</strong></td>';
                    html += '<td><button class="portal-btn portal-btn-sm portal-btn-outline edit-expense" data-id="' + e.id + '">Edit</button> ';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-danger del-expense" data-id="' + e.id + '">Delete</button></td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }
            $main.innerHTML = html;

            document.getElementById('btn-add-expense').addEventListener('click', function () { showExpenseModal(); });
            $main.querySelectorAll('.expense-cat-filter').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    expensesState.category = this.getAttribute('data-cat');
                    renderExpenses();
                });
            });
            $main.querySelectorAll('.edit-expense').forEach(function (btn) {
                btn.addEventListener('click', function () { showExpenseModal(this.getAttribute('data-id')); });
            });
            $main.querySelectorAll('.del-expense').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('Delete this expense?')) return;
                    api('delete_expense', { expense_id: this.getAttribute('data-id') }).then(function (r) {
                        if (r.success) { toast('Expense deleted'); renderExpenses(); }
                        else toast(r.data.message, 'error');
                    });
                });
            });
            var expExport = document.getElementById('btn-export-expenses');
            if (expExport) expExport.addEventListener('click', function () { exportCSV('expenses'); });
        });
    }

    function showExpenseModal(expenseId) {
        var loadData = expenseId ? api('expenses', { expense_id: expenseId }) : Promise.resolve({ success: true, data: { expenses: [{}] } });
        loadData.then(function (res) {
            var e = (res.data.expenses && res.data.expenses[0]) || {};
            var html = '<h3 style="margin:0 0 1rem;">' + (e.id ? 'Edit Expense' : 'Add Expense') + '</h3>';
            html += '<div class="portal-form-group"><label class="portal-label">Date</label><input type="date" class="portal-input" id="ef-date" value="' + esc(e.expense_date || new Date().toISOString().split('T')[0]) + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Category</label><select class="portal-select" id="ef-category">';
            ['fabric', 'rent', 'utilities', 'shipping', 'salary', 'marketing', 'other'].forEach(function (cat) {
                html += '<option value="' + cat + '"' + (e.category === cat ? ' selected' : '') + '>' + cat.charAt(0).toUpperCase() + cat.slice(1) + '</option>';
            });
            html += '</select></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Description</label><input type="text" class="portal-input" id="ef-desc" value="' + esc(e.description || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Amount</label><input type="number" step="0.01" class="portal-input" id="ef-amount" value="' + esc(e.amount || '') + '"></div>';
            html += '<div style="margin-top:1rem;display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-expense">Save</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
            html += '</div>';
            openModal(html);

            document.getElementById('btn-save-expense').addEventListener('click', function () {
                api('save_expense', {
                    expense_id: e.id || 0,
                    expense_date: document.getElementById('ef-date').value,
                    category: document.getElementById('ef-category').value,
                    description: document.getElementById('ef-desc').value,
                    amount: document.getElementById('ef-amount').value
                }).then(function (r) {
                    if (r.success) { toast('Expense saved!'); closeModal(); renderExpenses(); }
                    else toast(r.data.message, 'error');
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       STAFF (Feature 11)
       ═══════════════════════════════════════════════════ */
    function renderStaff() {
        showLoading();
        api('staff').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<div class="portal-section-header"><h2>Staff & Tailors</h2>';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" id="btn-add-staff">+ Add Staff</button></div>';

            if (!d.staff || d.staff.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No staff members added yet.</div></div>';
            } else {
                html += '<div class="portal-staff-cards">';
                d.staff.forEach(function (s) {
                    html += '<div class="portal-staff-card" data-id="' + s.id + '">';
                    html += '<div class="portal-staff-card-header">';
                    html += '<div class="portal-staff-avatar">' + esc((s.name || '?').charAt(0).toUpperCase()) + '</div>';
                    html += '<div><div class="portal-staff-name">' + esc(s.name) + '</div>';
                    html += '<div>' + badge(s.role) + ' ' + badge(s.is_active ? 'active' : 'inactive') + '</div></div>';
                    html += '</div>';
                    html += '<div class="portal-staff-meta">';
                    if (s.phone) html += '<div>' + esc(s.phone) + '</div>';
                    if (s.email) html += '<div>' + esc(s.email) + '</div>';
                    if (s.specialization) html += '<div><em>' + esc(s.specialization) + '</em></div>';
                    html += '</div>';
                    if (s.order_count !== undefined) {
                        html += '<div class="portal-staff-workload"><small style="color:#8b7355;">' + s.order_count + ' assigned orders</small></div>';
                    }
                    if (s.user_id && parseInt(s.user_id) > 0) {
                        html += '<div style="margin-top:0.25rem;"><small style="color:#2e7d32;">&#x2713; Portal account</small></div>';
                    }
                    html += '</div>';
                });
                html += '</div>';
            }
            $main.innerHTML = html;

            document.getElementById('btn-add-staff').addEventListener('click', function () { showStaffModal(); });
            $main.querySelectorAll('.portal-staff-card').forEach(function (card) {
                card.addEventListener('click', function () { showStaffModal(this.getAttribute('data-id')); });
            });
        });
    }

    function showStaffModal(staffId) {
        var loadData = staffId ? api('staff', { staff_id: staffId }) : Promise.resolve({ success: true, data: { staff: [{}] } });
        loadData.then(function (res) {
            var s = (res.data.staff && res.data.staff[0]) || {};
            var html = '<h3 style="margin:0 0 1rem;">' + (s.id ? 'Edit Staff' : 'Add Staff') + '</h3>';
            html += '<div class="portal-form-group"><label class="portal-label">Name</label><input type="text" class="portal-input" id="sf-name" value="' + esc(s.name || '') + '"></div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Phone</label><input type="text" class="portal-input" id="sf-phone" value="' + esc(s.phone || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Email</label><input type="email" class="portal-input" id="sf-email" value="' + esc(s.email || '') + '"></div>';
            html += '</div>';
            html += '<div class="portal-form-row">';
            html += '<div class="portal-form-group"><label class="portal-label">Role</label><select class="portal-select" id="sf-role">';
            ['tailor', 'manager', 'assistant'].forEach(function (r) {
                html += '<option value="' + r + '"' + (s.role === r ? ' selected' : '') + '>' + r.charAt(0).toUpperCase() + r.slice(1) + '</option>';
            });
            html += '</select></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Specialization</label><input type="text" class="portal-input" id="sf-spec" value="' + esc(s.specialization || '') + '" placeholder="e.g. Suits, Shirts"></div>';
            html += '</div>';
            html += '<div class="portal-form-group"><label class="portal-label"><input type="checkbox" id="sf-active"' + (s.is_active !== false && s.is_active !== '0' ? ' checked' : '') + '> Active</label></div>';
            if (!s.id || !s.user_id) {
                html += '<div class="portal-form-group" style="background:#f8f6f3;padding:0.75rem;border-radius:8px;">';
                html += '<label class="portal-label"><input type="checkbox" id="sf-create-account"> Create Portal Login Account</label>';
                html += '<small style="display:block;color:#8b7355;margin-top:0.25rem;">Creates a WordPress user with tailor role. Login credentials will be emailed.</small>';
                html += '</div>';
            } else if (s.user_id) {
                html += '<div style="background:#e8f5e9;padding:0.5rem 0.75rem;border-radius:8px;font-size:0.85rem;color:#2e7d32;margin-bottom:0.5rem;">Portal account linked (User #' + s.user_id + ')</div>';
            }
            html += '<div style="margin-top:1rem;display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-staff">Save</button>';
            if (s.id) html += '<button class="portal-btn portal-btn-danger" id="btn-del-staff">Delete</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
            html += '</div>';
            openModal(html);

            document.getElementById('btn-save-staff').addEventListener('click', function () {
                var createAccEl = document.getElementById('sf-create-account');
                api('save_staff', {
                    staff_id: s.id || 0,
                    name: document.getElementById('sf-name').value,
                    phone: document.getElementById('sf-phone').value,
                    email: document.getElementById('sf-email').value,
                    role: document.getElementById('sf-role').value,
                    specialization: document.getElementById('sf-spec').value,
                    is_active: document.getElementById('sf-active').checked ? '1' : '0',
                    create_account: createAccEl && createAccEl.checked ? '1' : '0'
                }).then(function (r) {
                    if (r.success) { toast('Staff saved!'); closeModal(); renderStaff(); }
                    else toast(r.data.message, 'error');
                });
            });

            var delBtn = document.getElementById('btn-del-staff');
            if (delBtn) delBtn.addEventListener('click', function () {
                if (!confirm('Delete this staff member?')) return;
                api('delete_staff', { staff_id: s.id }).then(function (r) {
                    if (r.success) { toast('Staff deleted'); closeModal(); renderStaff(); }
                    else toast(r.data.message, 'error');
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       DRIVERS
       ═══════════════════════════════════════════════════ */
    function renderDrivers() {
        showLoading();
        api('drivers').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<div class="portal-section-header"><h2>Drivers</h2>';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" id="btn-add-driver">+ Add Driver</button></div>';

            if (!d.drivers || d.drivers.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No drivers added yet.</div></div>';
            } else {
                html += '<div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>Name</th><th>Phone</th><th>Cities</th><th>Status</th><th>Actions</th>';
                html += '</tr></thead><tbody>';
                d.drivers.forEach(function (drv) {
                    html += '<tr>';
                    html += '<td>' + esc(drv.name) + '</td>';
                    html += '<td>' + esc(drv.phone) + '</td>';
                    html += '<td>' + esc(drv.cities) + '</td>';
                    html += '<td>' + badge(drv.active === '1' || drv.active === 1 ? 'active' : 'inactive') + '</td>';
                    html += '<td>';
                    html += '<button class="portal-btn portal-btn-outline portal-btn-sm btn-edit-driver" data-id="' + drv.id + '">Edit</button> ';
                    html += '<button class="portal-btn portal-btn-danger portal-btn-sm btn-del-driver" data-id="' + drv.id + '">Delete</button>';
                    html += '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
            }
            $main.innerHTML = html;

            document.getElementById('btn-add-driver').addEventListener('click', function () { showDriverModal(); });
            $main.querySelectorAll('.btn-edit-driver').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    showDriverModal(this.getAttribute('data-id'));
                });
            });
            $main.querySelectorAll('.btn-del-driver').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var did = this.getAttribute('data-id');
                    if (!confirm('Delete this driver?')) return;
                    api('delete_driver', { driver_id: did }).then(function (r) {
                        if (r.success) { toast('Driver deleted.'); renderDrivers(); }
                        else toast(r.data.message, 'error');
                    });
                });
            });
        });
    }

    function showDriverModal(driverId) {
        var loadData = driverId ? api('drivers', { driver_id: driverId }) : Promise.resolve({ success: true, data: { drivers: [{}] } });
        loadData.then(function (res) {
            var drivers = res.data.drivers || [];
            var drv = {};
            if (driverId) {
                for (var i = 0; i < drivers.length; i++) {
                    if (String(drivers[i].id) === String(driverId)) { drv = drivers[i]; break; }
                }
            }
            var html = '<h3 style="margin:0 0 1rem;">' + (drv.id ? 'Edit Driver' : 'Add Driver') + '</h3>';
            html += '<div class="portal-form-group"><label class="portal-label">Name</label><input type="text" class="portal-input" id="drv-name" value="' + esc(drv.name || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Phone</label><input type="text" class="portal-input" id="drv-phone" value="' + esc(drv.phone || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Cities (comma-separated)</label><textarea class="portal-input" id="drv-cities" rows="3" placeholder="e.g. Douala, Yaounde, Bamenda">' + esc(drv.cities || '') + '</textarea></div>';
            html += '<div class="portal-form-group"><label class="portal-label"><input type="checkbox" id="drv-active"' + (drv.active !== '0' && drv.active !== 0 ? ' checked' : '') + '> Active</label></div>';
            html += '<div style="margin-top:1rem;display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-driver">Save</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
            html += '</div>';
            openModal(html);

            document.getElementById('btn-save-driver').addEventListener('click', function () {
                api('save_driver', {
                    driver_id: drv.id || 0,
                    name: document.getElementById('drv-name').value,
                    phone: document.getElementById('drv-phone').value,
                    cities: document.getElementById('drv-cities').value,
                    active: document.getElementById('drv-active').checked ? '1' : '0'
                }).then(function (r) {
                    if (r.success) { toast('Driver saved!'); closeModal(); renderDrivers(); }
                    else toast(r.data.message, 'error');
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       SETTINGS (Feature 10 + WhatsApp Templates F14)
       ═══════════════════════════════════════════════════ */
    function renderSettings() {
        showLoading();
        Promise.all([api('get_settings'), api('whatsapp_templates')]).then(function (results) {
            var settings = results[0].success ? results[0].data : {};
            var templates = results[1].success ? (results[1].data.templates || []) : [];

            var html = '<div class="portal-section-header"><h2>Settings</h2></div>';

            /* Tabs */
            html += '<div class="portal-settings-tabs">';
            html += '<button class="portal-settings-tab active" data-tab="store">Store Info</button>';
            html += '<button class="portal-settings-tab" data-tab="whatsapp">WhatsApp</button>';
            html += '<button class="portal-settings-tab" data-tab="templates">Templates</button>';
            html += '<button class="portal-settings-tab" data-tab="notifications">Notifications</button>';
            html += '</div>';

            /* Store Info Panel */
            html += '<div class="portal-settings-panel active" data-panel="store">';
            html += '<div class="portal-card" style="padding:1.5rem;">';
            html += '<div class="portal-form-group"><label class="portal-label">Store Name</label><input type="text" class="portal-input" id="set-store-name" value="' + esc(settings.store_name || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Store Phone</label><input type="text" class="portal-input" id="set-store-phone" value="' + esc(settings.store_phone || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Store Email</label><input type="email" class="portal-input" id="set-store-email" value="' + esc(settings.store_email || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Store Address</label><textarea class="portal-textarea" id="set-store-address" rows="3">' + esc(settings.store_address || '') + '</textarea></div>';
            html += '<hr style="border:none;border-top:1px solid #e8ddd0;margin:1.5rem 0;">';
            html += '<div class="portal-form-group"><label class="portal-label">OpenAI API Key <span style="font-size:0.75rem;color:#8b7355;">(<a href="https://platform.openai.com/api-keys" target="_blank" style="color:#c9a96e;">platform.openai.com</a>)</span></label><input type="password" class="portal-input" id="set-openai-key" value="' + esc(settings.openai_api_key || '') + '" placeholder="sk-..."></div>';
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-store">Save Store Info</button>';
            html += '</div></div>';

            /* WhatsApp Panel */
            html += '<div class="portal-settings-panel" data-panel="whatsapp">';
            html += '<div class="portal-card" style="padding:1.5rem;">';
            html += '<div class="portal-form-group"><label class="portal-label">WhatsApp API URL</label><input type="text" class="portal-input" id="set-wa-url" value="' + esc(settings.whatsapp_api_url || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">API Token</label><input type="password" class="portal-input" id="set-wa-token" value="' + esc(settings.whatsapp_token || '') + '"></div>';
            html += '<div class="portal-form-group"><label class="portal-label">Phone Number ID</label><input type="text" class="portal-input" id="set-wa-phone" value="' + esc(settings.whatsapp_phone_id || '') + '"></div>';
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-wa">Save WhatsApp Settings</button>';
            html += '</div></div>';

            /* Templates Panel */
            html += '<div class="portal-settings-panel" data-panel="templates">';
            html += '<div style="margin-bottom:1rem;display:flex;gap:0.5rem;">';
            html += '<button class="portal-btn portal-btn-primary portal-btn-sm" id="btn-add-template">+ New Template</button>';
            html += '<span style="font-size:0.8rem;color:#8b7355;align-self:center;">Placeholders: {customer_name}, {order_number}, {amount}, {date}</span>';
            html += '</div>';
            if (templates.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No templates yet.</div></div>';
            } else {
                templates.forEach(function (t) {
                    html += '<div class="portal-template-card">';
                    html += '<div><div class="portal-template-name">' + esc(t.name) + '</div>';
                    html += '<div class="portal-template-preview">' + esc(t.message) + '</div></div>';
                    html += '<div class="portal-template-actions">';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-outline edit-tpl" data-id="' + t.id + '">Edit</button>';
                    html += '<button class="portal-btn portal-btn-sm portal-btn-danger del-tpl" data-id="' + t.id + '">Delete</button>';
                    html += '</div></div>';
                });
            }
            html += '</div>';

            /* Notifications Panel */
            html += '<div class="portal-settings-panel" data-panel="notifications">';
            html += '<div class="portal-card" style="padding:1.5rem;">';
            var statuses = ['kctm-confirmed', 'processing', 'kctm-in-progress', 'kctm-ready-pickup', 'kctm-with-driver', 'kctm-delivered', 'completed'];
            statuses.forEach(function (s) {
                var checked = settings.notification_statuses && settings.notification_statuses.indexOf(s) > -1;
                html += '<div class="portal-form-group"><label class="portal-label"><input type="checkbox" class="notif-status-cb" value="' + s + '"' + (checked ? ' checked' : '') + '> Send notification on: ' + statusLabel(s) + '</label></div>';
            });
            html += '<button class="portal-btn portal-btn-primary" id="btn-save-notif">Save Notification Settings</button>';
            html += '</div></div>';

            $main.innerHTML = html;

            /* Tab switching */
            $main.querySelectorAll('.portal-settings-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    $main.querySelectorAll('.portal-settings-tab').forEach(function (t) { t.classList.remove('active'); });
                    $main.querySelectorAll('.portal-settings-panel').forEach(function (p) { p.classList.remove('active'); });
                    this.classList.add('active');
                    var panel = $main.querySelector('[data-panel="' + this.getAttribute('data-tab') + '"]');
                    if (panel) panel.classList.add('active');
                });
            });

            /* Save store */
            document.getElementById('btn-save-store').addEventListener('click', function () {
                api('save_settings', {
                    settings_group: 'store',
                    store_name: document.getElementById('set-store-name').value,
                    store_phone: document.getElementById('set-store-phone').value,
                    store_email: document.getElementById('set-store-email').value,
                    store_address: document.getElementById('set-store-address').value,
                    openai_api_key: document.getElementById('set-openai-key').value
                }).then(function (r) {
                    if (r.success) toast('Store settings saved!');
                    else toast(r.data.message, 'error');
                });
            });

            /* Save WhatsApp */
            document.getElementById('btn-save-wa').addEventListener('click', function () {
                api('save_settings', {
                    settings_group: 'whatsapp',
                    whatsapp_api_url: document.getElementById('set-wa-url').value,
                    whatsapp_token: document.getElementById('set-wa-token').value,
                    whatsapp_phone_id: document.getElementById('set-wa-phone').value
                }).then(function (r) {
                    if (r.success) toast('WhatsApp settings saved!');
                    else toast(r.data.message, 'error');
                });
            });

            /* Save notifications */
            document.getElementById('btn-save-notif').addEventListener('click', function () {
                var selected = [];
                $main.querySelectorAll('.notif-status-cb:checked').forEach(function (cb) { selected.push(cb.value); });
                api('save_settings', { settings_group: 'notifications', notification_statuses: selected }).then(function (r) {
                    if (r.success) toast('Notification settings saved!');
                    else toast(r.data.message, 'error');
                });
            });

            /* Templates CRUD */
            document.getElementById('btn-add-template').addEventListener('click', function () { showTemplateModal(); });
            $main.querySelectorAll('.edit-tpl').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tpl = templates.find(function (t) { return String(t.id) === String(btn.getAttribute('data-id')); });
                    if (tpl) showTemplateModal(tpl);
                });
            });
            $main.querySelectorAll('.del-tpl').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('Delete this template?')) return;
                    api('delete_whatsapp_template', { template_id: this.getAttribute('data-id') }).then(function (r) {
                        if (r.success) { toast('Template deleted'); renderSettings(); }
                    });
                });
            });
        });
    }

    function showTemplateModal(tpl) {
        tpl = tpl || {};
        var html = '<h3 style="margin:0 0 1rem;">' + (tpl.id ? 'Edit Template' : 'New Template') + '</h3>';
        html += '<div class="portal-form-group"><label class="portal-label">Template Name</label><input type="text" class="portal-input" id="tpl-name" value="' + esc(tpl.name || '') + '"></div>';
        html += '<div class="portal-form-group"><label class="portal-label">Message</label><textarea class="portal-textarea" id="tpl-msg" rows="5">' + esc(tpl.message || '') + '</textarea>' + aiRewriteBtn('tpl-msg', 'AI Write') + '</div>';
        html += '<div style="margin-bottom:0.5rem;display:flex;gap:0.3rem;flex-wrap:wrap;">';
        ['{customer_name}', '{order_number}', '{amount}', '{date}'].forEach(function (p) {
            html += '<button class="portal-placeholder-btn" data-ph="' + p + '">' + p + '</button>';
        });
        html += '</div>';
        html += '<div style="margin-top:1rem;display:flex;gap:0.5rem;">';
        html += '<button class="portal-btn portal-btn-primary" id="btn-save-tpl">Save</button>';
        html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
        html += '</div>';
        openModal(html);

        /* Insert placeholder */
        document.querySelectorAll('.portal-placeholder-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var ta = document.getElementById('tpl-msg');
                var ph = this.getAttribute('data-ph');
                ta.value = ta.value.substring(0, ta.selectionStart) + ph + ta.value.substring(ta.selectionEnd);
                ta.focus();
            });
        });

        bindAiRewrites('whatsapp', function () {
            var nameEl = document.getElementById('tpl-name');
            return nameEl ? nameEl.value.trim() : '';
        });

        document.getElementById('btn-save-tpl').addEventListener('click', function () {
            api('save_whatsapp_template', {
                template_id: tpl.id || 0,
                name: document.getElementById('tpl-name').value,
                message: document.getElementById('tpl-msg').value
            }).then(function (r) {
                if (r.success) { toast('Template saved!'); closeModal(); renderSettings(); }
                else toast(r.data.message, 'error');
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       ABANDONED CARTS (Feature 4 — sub-route of Orders)
       ═══════════════════════════════════════════════════ */
    function renderAbandonedCarts() {
        showLoading();
        api('abandoned_carts').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var html = '<button class="portal-back-link" onclick="location.hash=\'#orders\'">&#8592; Back to Orders</button>';
            html += '<div class="portal-section-header"><h2>Abandoned Carts</h2></div>';

            if (!d.carts || d.carts.length === 0) {
                html += '<div class="portal-empty-state"><div class="portal-empty-state-text">No abandoned carts found.</div></div>';
            } else {
                html += '<div class="portal-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>Customer</th><th>Email</th><th>Cart Total</th><th>Status</th><th>Date</th><th></th>';
                html += '</tr></thead><tbody>';
                d.carts.forEach(function (c) {
                    html += '<tr>';
                    html += '<td>' + esc(c.customer_name || 'Guest') + '</td>';
                    html += '<td>' + esc(c.customer_email || '-') + '</td>';
                    html += '<td>' + money(c.cart_total) + '</td>';
                    html += '<td>' + badge(c.status) + '</td>';
                    html += '<td>' + fmtDate(c.created_at) + '</td>';
                    html += '<td>';
                    if (c.status === 'abandoned' && c.customer_email) {
                        html += '<button class="portal-btn portal-btn-sm portal-btn-outline send-cart-reminder" data-id="' + c.id + '">Send Reminder</button> ';
                    }
                    html += '<button class="portal-btn portal-btn-sm portal-btn-danger del-cart" data-id="' + c.id + '">Delete</button>';
                    html += '</td></tr>';
                });
                html += '</tbody></table></div></div>';
            }
            $main.innerHTML = html;

            $main.querySelectorAll('.send-cart-reminder').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    api('send_cart_reminder', { cart_id: this.getAttribute('data-id') }).then(function (r) {
                        if (r.success) toast('Reminder sent!');
                        else toast(r.data.message, 'error');
                    });
                });
            });
            $main.querySelectorAll('.del-cart').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('Delete this abandoned cart record?')) return;
                    api('delete_abandoned_cart', { cart_id: this.getAttribute('data-id') }).then(function (r) {
                        if (r.success) { toast('Deleted'); renderAbandonedCarts(); }
                    });
                });
            });
        });
    }

    /* ═══════════════════════════════════════════════════
       CALENDAR VIEW (Feature 12 — sub-route of Consultations)
       ═══════════════════════════════════════════════════ */
    var calendarState = { year: new Date().getFullYear(), month: new Date().getMonth() };

    function renderCalendar() {
        showLoading();
        var y = calendarState.year, m = calendarState.month + 1;
        api('calendar_data', { year: y, month: m }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

            var html = '<button class="portal-back-link" onclick="location.hash=\'#consultations\'">&#8592; Back to Consultations</button>';
            html += '<div class="portal-section-header" style="margin-bottom:1rem;"><h2>Calendar</h2>';
            html += '<small style="color:#8b7355;">Click any date to block/unblock availability</small></div>';
            html += '<div class="portal-calendar">';
            html += '<div class="portal-calendar-nav">';
            html += '<button id="cal-prev">&larr;</button>';
            html += '<div class="portal-calendar-title">' + monthNames[calendarState.month] + ' ' + y + '</div>';
            html += '<button id="cal-next">&rarr;</button>';
            html += '</div>';

            /* Legend */
            html += '<div class="portal-calendar-legend">';
            html += '<span class="portal-calendar-legend-item"><span class="portal-calendar-legend-dot" style="background:#e8f5e9;"></span> Available</span>';
            html += '<span class="portal-calendar-legend-item"><span class="portal-calendar-legend-dot" style="background:#ffebee;"></span> Blocked</span>';
            html += '<span class="portal-calendar-legend-item"><span class="portal-calendar-legend-dot" style="background:#e3f2fd;"></span> Has Bookings</span>';
            html += '</div>';

            /* Grid */
            html += '<div class="portal-calendar-grid">';
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(function (d) {
                html += '<div class="portal-calendar-day-header">' + d + '</div>';
            });

            var firstDay = new Date(y, calendarState.month, 1).getDay();
            var daysInMonth = new Date(y, calendarState.month + 1, 0).getDate();
            var today = new Date();
            var todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

            /* Previous month padding */
            var prevDays = new Date(y, calendarState.month, 0).getDate();
            for (var i = firstDay - 1; i >= 0; i--) {
                html += '<div class="portal-calendar-day other-month"><div class="portal-calendar-day-num">' + (prevDays - i) + '</div></div>';
            }

            /* Build blocked dates lookup with reasons */
            var bookingMap = {};
            var blockedMap = {};
            (d.blocked_dates || []).forEach(function (bd) {
                var bDate = bd.date || bd;
                var bReason = bd.reason || '';
                blockedMap[bDate] = bReason;
            });
            (d.bookings || []).forEach(function (b) {
                var dateKey = b.date || b.booking_date;
                if (!bookingMap[dateKey]) bookingMap[dateKey] = [];
                bookingMap[dateKey].push(b);
            });

            for (var day = 1; day <= daysInMonth; day++) {
                var dateStr = y + '-' + String(m).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var isToday = dateStr === todayStr;
                var isBlocked = blockedMap.hasOwnProperty(dateStr);
                var blockReason = isBlocked ? blockedMap[dateStr] : '';
                var dayBookings = bookingMap[dateStr] || [];

                html += '<div class="portal-calendar-day clickable' + (isToday ? ' today' : '') + (isBlocked ? ' blocked' : '') + '" data-date="' + dateStr + '"' + (blockReason ? ' title="' + esc(blockReason) + '"' : '') + '>';
                html += '<div class="portal-calendar-day-num">' + day + '</div>';
                if (isBlocked) {
                    html += '<div class="portal-calendar-blocked-label">Blocked</div>';
                    if (blockReason) html += '<div class="portal-calendar-block-reason">' + esc(blockReason) + '</div>';
                }
                if (dayBookings.length > 0) {
                    html += '<div class="portal-calendar-day-count">' + dayBookings.length + ' booking' + (dayBookings.length > 1 ? 's' : '') + '</div>';
                    html += '<div class="portal-calendar-dots">';
                    dayBookings.slice(0, 4).forEach(function (b) {
                        html += '<div class="portal-calendar-dot ' + esc(b.status || 'pending') + '"></div>';
                    });
                    html += '</div>';
                }
                html += '</div>';
            }

            /* Next month padding */
            var totalCells = firstDay + daysInMonth;
            var remaining = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
            for (var j = 1; j <= remaining; j++) {
                html += '<div class="portal-calendar-day other-month"><div class="portal-calendar-day-num">' + j + '</div></div>';
            }

            html += '</div></div>';
            $main.innerHTML = html;

            /* Month navigation */
            document.getElementById('cal-prev').addEventListener('click', function () {
                calendarState.month--;
                if (calendarState.month < 0) { calendarState.month = 11; calendarState.year--; }
                renderCalendar();
            });
            document.getElementById('cal-next').addEventListener('click', function () {
                calendarState.month++;
                if (calendarState.month > 11) { calendarState.month = 0; calendarState.year++; }
                renderCalendar();
            });

            /* Click day to block/unblock */
            $main.querySelectorAll('.portal-calendar-day.clickable').forEach(function (dayEl) {
                dayEl.addEventListener('click', function () {
                    var clickedDate = this.getAttribute('data-date');
                    var isCurrentlyBlocked = this.classList.contains('blocked');
                    showBlockDateModal(clickedDate, isCurrentlyBlocked, bookingMap[clickedDate] || []);
                });
            });
        });
    }

    function showBlockDateModal(dateStr, isBlocked, bookings) {
        var niceDate = fmtDate(dateStr);
        var html = '<h3 style="margin:0 0 1rem;">' + esc(niceDate) + '</h3>';

        if (bookings.length > 0) {
            html += '<div style="margin-bottom:1rem;padding:0.75rem;background:#e3f2fd;border-radius:8px;font-size:0.9rem;">';
            html += '<strong>' + bookings.length + ' booking(s) on this date:</strong>';
            bookings.forEach(function (b) {
                html += '<div style="margin-top:0.25rem;">' + esc(b.name) + ' at ' + esc(b.time) + ' — ' + badge(b.status) + '</div>';
            });
            html += '</div>';
        }

        if (isBlocked) {
            html += '<p style="color:#c62828;">This date is currently <strong>blocked</strong>. Customers cannot book consultations.</p>';
            html += '<div style="display:flex;gap:0.5rem;margin-top:1rem;">';
            html += '<button class="portal-btn portal-btn-primary" id="btn-unblock-date">Unblock This Date</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Close</button>';
            html += '</div>';
        } else {
            html += '<p>Block this date to prevent new consultation bookings.</p>';
            html += '<div class="portal-form-group"><label class="portal-label">Reason (optional)</label>';
            html += '<input type="text" class="portal-input" id="block-reason" placeholder="e.g. CEO travel, Holiday, Renovation"></div>';
            html += '<div style="display:flex;gap:0.5rem;margin-top:1rem;">';
            html += '<button class="portal-btn portal-btn-danger" id="btn-block-date">Block This Date</button>';
            html += '<button class="portal-btn portal-btn-outline" onclick="document.getElementById(\'portal-modal\').style.display=\'none\'">Cancel</button>';
            html += '</div>';
        }
        openModal(html);

        if (isBlocked) {
            document.getElementById('btn-unblock-date').addEventListener('click', function () {
                api('unblock_date', { date: dateStr }).then(function (r) {
                    if (r.success) { toast('Date unblocked!'); closeModal(); renderCalendar(); }
                    else toast(r.data.message, 'error');
                });
            });
        } else {
            document.getElementById('btn-block-date').addEventListener('click', function () {
                var reason = document.getElementById('block-reason').value;
                api('block_date', { date: dateStr, reason: reason }).then(function (r) {
                    if (r.success) { toast('Date blocked!'); closeModal(); renderCalendar(); }
                    else toast(r.data.message, 'error');
                });
            });
        }
    }

    /* ═══════════════════════════════════════════════════
       CSV EXPORT (Feature 13)
       ═══════════════════════════════════════════════════ */
    function exportCSV(type) {
        api('export_data', { type: type }).then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var csv = res.data.csv;
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = type + '_export_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            toast('CSV downloaded!');
        });
    }

    /* ═══════════════════════════════════════════════════
       TAILOR DASHBOARD (for kctm_tailor role)
       ═══════════════════════════════════════════════════ */
    function renderTailorDashboard() {
        showLoading();
        api('tailor_dashboard').then(function (res) {
            if (!res.success) { toast(res.data.message, 'error'); return; }
            var d = res.data;
            var p = d.profile || {};
            var html = '';

            html += '<div class="portal-section-header"><h1 class="portal-section-title">My Dashboard</h1></div>';

            /* Profile card */
            html += '<div class="portal-card" style="padding:1.5rem;margin-bottom:1.5rem;">';
            html += '<div style="display:flex;align-items:center;gap:1rem;">';
            html += '<div class="portal-staff-avatar" style="width:50px;height:50px;font-size:1.5rem;">' + esc((p.name || '?').charAt(0).toUpperCase()) + '</div>';
            html += '<div><h3 style="margin:0;">' + esc(p.name) + '</h3>';
            html += '<div style="color:#8b7355;">' + esc(p.role || 'Tailor');
            if (p.specialization) html += ' &mdash; ' + esc(p.specialization);
            html += '</div></div></div></div>';

            /* Stats */
            html += '<div class="portal-kpi-row">';
            html += '<div class="portal-kpi"><div class="portal-kpi-val">' + (d.total_orders || 0) + '</div><div class="portal-kpi-label">Assigned Orders</div></div>';
            var stages = d.stages || {};
            html += '<div class="portal-kpi"><div class="portal-kpi-val">' + (stages.fabric_cutting || 0) + '</div><div class="portal-kpi-label">Cutting</div></div>';
            html += '<div class="portal-kpi"><div class="portal-kpi-val">' + (stages.stitching || 0) + '</div><div class="portal-kpi-label">Stitching</div></div>';
            html += '<div class="portal-kpi"><div class="portal-kpi-val">' + (stages.finishing || 0) + '</div><div class="portal-kpi-label">Finishing</div></div>';
            html += '<div class="portal-kpi"><div class="portal-kpi-val">' + (stages.quality_check || 0) + '</div><div class="portal-kpi-label">QC</div></div>';
            html += '<div class="portal-kpi"><div class="portal-kpi-val">' + (stages.ready_pickup || 0) + '</div><div class="portal-kpi-label">Ready</div></div>';
            html += '</div>';

            /* Quick actions */
            html += '<div class="portal-quick-actions" style="margin:1.5rem 0;">';
            html += '<a href="#production" class="portal-btn portal-btn-primary">View My Orders Board</a>';
            html += '</div>';

            /* Recent orders table */
            var orders = d.recent_orders || [];
            if (orders.length) {
                html += '<div class="portal-card"><h3 style="padding:1rem 1.5rem 0;">Recent Assigned Orders</h3>';
                html += '<div class="portal-table-wrap"><table class="portal-table"><thead><tr>';
                html += '<th>Order</th><th>Customer</th><th>Stage</th><th>Total</th><th>Date</th>';
                html += '</tr></thead><tbody>';
                orders.forEach(function (o) {
                    var stageLabel = (o.stage || 'pending').replace(/_/g, ' ');
                    stageLabel = stageLabel.charAt(0).toUpperCase() + stageLabel.slice(1);
                    html += '<tr>';
                    html += '<td>#' + esc(o.number) + '</td>';
                    html += '<td>' + esc(o.customer) + '</td>';
                    html += '<td>' + badge(o.stage) + '</td>';
                    html += '<td>' + money(o.total) + '</td>';
                    html += '<td>' + fmtDate(o.date) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div></div>';
            }

            $main.innerHTML = html;
        });
    }

    /* ═══════════════════════════════════════════════════
       BOOT
       ═══════════════════════════════════════════════════ */

    /* Role-based nav filtering for tailors. */
    if (C.user_role === 'tailor') {
        /* Replace dashboard route with tailor dashboard. */
        routes.dashboard = renderTailorDashboard;

        /* Hide nav items tailors shouldn't see. */
        var tailorSections = { dashboard: true, production: true, staff: true };
        document.querySelectorAll('.portal-nav a[data-section]').forEach(function (a) {
            if (!tailorSections[a.getAttribute('data-section')]) {
                a.parentElement.style.display = 'none';
            }
        });
        /* Also hide nav group labels that have no visible items after them. */
        document.querySelectorAll('.portal-nav-label').forEach(function (lbl) {
            var next = lbl.nextElementSibling;
            var hasVisible = false;
            while (next && !next.classList.contains('portal-nav-label')) {
                if (next.style.display !== 'none') hasVisible = true;
                next = next.nextElementSibling;
            }
            if (!hasVisible) lbl.style.display = 'none';
        });
    }

    navigate();

})();
