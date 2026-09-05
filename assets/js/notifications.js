/**
 * assets/js/notifications.js - Zvonce + panel obaveštenja (ceo sajt)
 *
 * - Polje "Obaveštenja" u desktop navbar-u i mobilnom search redu
 * - Panel je offcanvas desno (radi na desktopu i mobilnom)
 * - Poll-uje unread count na 30s (samo kad je tab vidljiv)
 * - Klik na stavku: mark-read + navigacija na link
 * - "Označi sve kao pročitano" i brisanje stavki
 *
 * Backend: api/notifications/{list,unread-count,mark-read,delete}.php
 */
(function () {
    'use strict';

    var POLL_MS = 30000;
    var panelEl = null;
    var pollTimer = null;
    var lastUnread = null;
    var isOpen = false;

    function loggedIn() {
        return typeof SITE_CONFIG !== 'undefined' && SITE_CONFIG.isLoggedIn === true
            || (typeof SITE_CONFIG !== 'undefined' && SITE_CONFIG.userId && SITE_CONFIG.userId !== '0' && SITE_CONFIG.userId !== 0);
    }

    function api(path, opts) {
        return fetch(SITE_CONFIG.url + path, Object.assign({
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' }
        }, opts || {})).then(function (r) { return r.json(); });
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    // ============================================
    // Badges na zvonce
    // ============================================
    function setBadge(count) {
        document.querySelectorAll('.notif-badge').forEach(function (b) {
            if (count > 0) {
                b.textContent = count > 99 ? '99+' : String(count);
                b.classList.remove('d-none');
            } else {
                b.classList.add('d-none');
            }
        });
    }

    function refreshCount(announce) {
        if (!loggedIn()) return;
        api('/api/notifications/unread-count.php')
            .then(function (res) {
                if (!res || !res.success) return;
                var count = res.unread_count || 0;
                if (announce && lastUnread !== null && count > lastUnread && res.latest) {
                    showToast('Novo obaveštenje', res.latest.title, res.latest.message);
                }
                if (!isOpen) {
                    setBadge(count);
                }
                lastUnread = count;
            })
            .catch(function () { /* mrezna greska - ignorisi */ });
    }

    function showToast(title, heading, body) {
        if (typeof bootstrap === 'undefined') return;
        var container = document.getElementById('notif-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notif-toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1080';
            document.body.appendChild(container);
        }
        var el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-primary border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML =
            '<div class="d-flex"><div class="toast-body">' +
            '<strong><i class="far fa-bell me-2"></i>' + esc(title) + '</strong><br>' +
            '<span class="small">' + esc(heading || '') + '</span>' +
            (body ? '<br><span class="small opacity-75">' + esc(body) + '</span>' : '') +
            '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        container.appendChild(el);
        var toast = new bootstrap.Toast(el, { delay: 6000 });
        toast.show();
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    }

    // ============================================
    // Panel (offcanvas)
    // ============================================
    function ensurePanel() {
        if (panelEl) return panelEl;
        var html =
            '<div class="offcanvas offcanvas-end" tabindex="-1" id="notifPanel" style="width:380px;max-width:92vw;">' +
            '  <div class="offcanvas-header border-bottom">' +
            '    <h5 class="offcanvas-title mb-0"><i class="far fa-bell me-2 text-primary"></i>Obaveštenja</h5>' +
            '    <div>' +
            '      <button type="button" class="btn btn-sm btn-link text-primary me-1" id="notifMarkAll" title="Označi sve kao pročitano"><i class="fas fa-check-double"></i></button>' +
            '      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Zatvori"></button>' +
            '    </div>' +
            '  </div>' +
            '  <div class="offcanvas-body p-0" id="notifListBody" style="overflow-y:auto;">' +
            '    <div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p>Učitavanje...</p></div>' +
            '  </div>' +
            '  <div class="border-top p-2 text-center bg-light">' +
            '    <button class="btn btn-sm btn-outline-danger w-100" id="notifClearRead"><i class="fas fa-broom me-2"></i>Ukloni pročitana</button>' +
            '  </div>' +
            '</div>';
        document.body.insertAdjacentHTML('beforeend', html);
        panelEl = document.getElementById('notifPanel');

        panelEl.addEventListener('shown.bs.offcanvas', function () {
            isOpen = true;
            loadList();
        });
        panelEl.addEventListener('hidden.bs.offcanvas', function () {
            isOpen = false;
            refreshCount(false);
        });

        document.getElementById('notifMarkAll').addEventListener('click', function () {
            api('/api/notifications/mark-read.php', { method: 'POST', body: JSON.stringify({ all: true }) })
                .then(function () {
                    setBadge(0);
                    lastUnread = 0;
                    loadList();
                });
        });
        document.getElementById('notifClearRead').addEventListener('click', function () {
            api('/api/notifications/delete.php', { method: 'POST', body: JSON.stringify({ all_read: true }) })
                .then(loadList);
        });

        return panelEl;
    }

    function openPanel() {
        if (!loggedIn()) {
            window.location.href = SITE_CONFIG.url + '/login';
            return;
        }
        ensurePanel();
        var oc = bootstrap.Offcanvas.getOrCreateInstance(panelEl);
        oc.show();
    }

    function loadList() {
        var body = document.getElementById('notifListBody');
        if (!body) return;
        api('/api/notifications/list.php?limit=30')
            .then(function (res) {
                if (!res.success) throw new Error('load');
                setBadge(res.unread_count || 0);
                lastUnread = res.unread_count || 0;

                var items = res.notifications || [];
                if (items.length === 0) {
                    body.innerHTML =
                        '<div class="text-center text-muted py-5">' +
                        '<i class="far fa-bell fa-3x mb-3"></i>' +
                        '<p class="mb-0">Nema obaveštenja</p></div>';
                    return;
                }

                var html = '<div class="list-group list-group-flush">';
                items.forEach(function (n) {
                    var unreadCls = n.is_read ? '' : 'fw-bold notif-unread';
                    var link = n.link ? esc(n.link) : '';
                    html +=
                        '<div class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-3 px-3' + (n.is_read ? '' : ' active-notif') + '" ' +
                        'data-notif-id="' + n.id + '" data-read="' + (n.is_read ? 1 : 0) + '"' +
                        (link ? ' data-link="' + link + '"' : '') + ' role="button" style="cursor:pointer;">' +
                        '  <div class="text-primary mt-1"><i class="fas fa-' + esc(n.icon) + '"></i></div>' +
                        '  <div class="flex-grow-1" data-act="open">' +
                        '    <div class="d-flex justify-content-between">' +
                        '      <h6 class="mb-1 ' + unreadCls + '">' + esc(n.title) + '</h6>' +
                        '      <small class="text-muted text-nowrap ms-2">' + esc(n.time_ago) + '</small>' +
                        '    </div>' +
                        '    <p class="mb-0 small ' + (n.is_read ? 'text-muted' : '') + '">' + esc(n.message) + '</p>' +
                        '    ' + (n.is_read ? '' : '<span class="badge bg-primary rounded-pill" style="font-size:.6rem">Novo</span>') +
                        '  </div>' +
                        '  <button class="btn btn-sm btn-link text-danger p-1" data-act="del" title="Obriši" aria-label="Obriši obaveštenje"><i class="fas fa-times"></i></button>' +
                        '</div>';
                });
                html += '</div>';
                if (res.total > items.length) {
                    html += '<div class="p-3 text-center"><a href="' + SITE_CONFIG.url + '/notifications/" class="small">Pogledaj sva obaveštenja →</a></div>';
                }
                body.innerHTML = html;
            })
            .catch(function () {
                body.innerHTML = '<div class="text-center text-danger py-5">Greška pri učitavanju</div>';
            });
    }

    // Delegirani klikovi u listi
    document.addEventListener('click', function (e) {
        var item = e.target.closest('#notifListBody [data-notif-id]');
        if (!item) return;
        var id = parseInt(item.getAttribute('data-notif-id'), 10);
        var act = e.target.closest('[data-act]');
        var action = act ? act.getAttribute('data-act') : 'open';

        if (action === 'del') {
            e.preventDefault();
            api('/api/notifications/delete.php', { method: 'POST', body: JSON.stringify({ id: id }) })
                .then(function () {
                    item.remove();
                    refreshCount(false);
                });
            return;
        }

        // open: mark read + idi na link
        var wasUnread = item.getAttribute('data-read') === '0';
        if (wasUnread) {
            api('/api/notifications/mark-read.php', { method: 'POST', body: JSON.stringify({ ids: [id] }) })
                .then(function () { refreshCount(false); });
        }
        var link = item.getAttribute('data-link');
        if (link) {
            window.location.href = link;
        }
    });

    // ============================================
    // Init
    // ============================================
    function init() {
        if (!loggedIn()) return;

        ['#notif-bell-desktop', '#notif-bell-mobile'].forEach(function (sel) {
            var bell = document.querySelector(sel);
            if (!bell) return;
            if (bell.tagName === 'A') {
                bell.addEventListener('click', function (e) { e.preventDefault(); openPanel(); });
            } else {
                bell.addEventListener('click', openPanel);
            }
        });

        refreshCount(false);
        pollTimer = setInterval(function () {
            if (document.visibilityState === 'visible' && !isOpen) {
                refreshCount(true);
            }
        }, POLL_MS);

        // Odmah osvezi kad se tab vrati u fokus
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') refreshCount(true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
