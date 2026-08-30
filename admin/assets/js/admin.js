/**
 * admin/assets/js/admin.js - Profesionalni admin JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    initAdmin();
});

function initAdmin() {
    initSidebar();
    initTooltips();
    initDeleteConfirmation();
    initDataTables();
    initDarkMode();
    initNotifications();
    initCharts();
}

/**
 * Sidebar toggle
 */
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // Close sidebar on click outside (mobile)
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 992) {
            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
    
    // Active link highlighting
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href)) {
            link.classList.add('active');
        }
    });
}

/**
 * Tooltips
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Delete confirmation
 */
function initDeleteConfirmation() {
    const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
    deleteForms.forEach(form => {
        // Already handled by inline onsubmit
    });
    
    // Add confirmation to all delete buttons
    document.querySelectorAll('.btn-outline-danger, .btn-danger').forEach(btn => {
        if (btn.closest('form')) {
            btn.closest('form').addEventListener('submit', function(e) {
                if (!confirm('Da li ste sigurni? Ova akcija je nepovratna!')) {
                    e.preventDefault();
                }
            });
        }
    });
}

/**
 * DataTables
 */
function initDataTables() {
    const tables = document.querySelectorAll('.table:not(.no-datatable)');
    if (typeof $.fn.DataTable !== 'undefined' && tables.length > 0) {
        tables.forEach(table => {
            // Only apply to tables with enough rows
            if (table.querySelectorAll('tbody tr').length > 10) {
                $(table).DataTable({
                    language: {
                        // Umesto URL-a, koristi eksplicitna podešavanja na latinici
                        "sProcessing":     "Obrada u toku...",
                        "sLengthMenu":     "Prikaži _MENU_ redova",
                        "sZeroRecords":    "Nije pronađen nijedan rezultat",
                        "sInfo":           "Prikaz _START_ do _END_ od ukupno _TOTAL_ redova",
                        "sInfoEmpty":      "Prikaz 0 do 0 od ukupno 0 redova",
                        "sInfoFiltered":   "(filtrirano od ukupno _MAX_ redova)",
                        "sSearch":         "Pretraga:",
                        "oPaginate": {
                            "sFirst":      "Prva",
                            "sPrevious":   "Prethodna",
                            "sNext":       "Sledeća",
                            "sLast":       "Poslednja"
                        }
                    },
                    pageLength: 20,
                    responsive: true,
                    order: [[0, 'desc']]
                });
            }
        });
    }
}
/**
 * Dark mode
 */
function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (!darkModeToggle) return;
    
    let darkMode = localStorage.getItem('adminDarkMode') === 'true';
    
    if (darkMode) {
        document.body.classList.add('dark-mode');
        darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
    
    darkModeToggle.addEventListener('click', function() {
        darkMode = !darkMode;
        localStorage.setItem('adminDarkMode', darkMode);
        document.body.classList.toggle('dark-mode');
        this.innerHTML = darkMode ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });
}

/**
 * Notifications dropdown
 */
function initNotifications() {
    const notificationBtn = document.getElementById('notificationBtn');
    if (!notificationBtn) return;
    
    // Create notification dropdown if not exists
    if (!document.querySelector('.notification-dropdown .dropdown-menu')) {
        const dropdown = document.createElement('div');
        dropdown.className = 'dropdown-menu dropdown-menu-end p-0';
        dropdown.style.width = '300px';
        dropdown.innerHTML = `
            <div class="p-3 border-bottom">
                <h6 class="mb-0">Notifikacije</h6>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-plus text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-muted">pre 5 minuta</small>
                            <p class="mb-0 small">Novi korisnik se registrovao</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tag text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-muted">pre 1 sat</small>
                            <p class="mb-0 small">Novi oglas je postavljen</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-2 text-center border-top">
                <a href="/admin/logs.php" class="small">Pregled svih notifikacija</a>
            </div>
        `;
        notificationBtn.parentElement.appendChild(dropdown);
        
        // Toggle dropdown
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const bsDropdown = new bootstrap.Dropdown(this.nextElementSibling);
            bsDropdown.toggle();
        });
    }
}

/**
 * Charts refresh
 */
function initCharts() {
    const refreshBtn = document.getElementById('refreshChart');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            location.reload();
        });
    }
}

/**
 * Export table to CSV
 */
window.exportToCSV = function(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const rowData = [];
        const cols = row.querySelectorAll('td, th');
        cols.forEach(col => {
            let text = col.innerText.trim().replace(/,/g, ';');
            rowData.push(text);
        });
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
};

/**
 * Print table
 */
window.printTable = function(elementId) {
    const printContent = document.getElementById(elementId);
    if (!printContent) return;
    
    const originalContent = document.body.innerHTML;
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h1>Admin Panel - Izve分taj</h1>
            <hr>
            ${printContent.outerHTML}
        </div>
    `;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
};

/**
 * Toast notification
 */
window.showToast = function(message, type = 'success') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', toastHTML);
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
};

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1100';
    document.body.appendChild(container);
    return container;
}

/**
 * Auto-hide alerts
 */
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}, 100);