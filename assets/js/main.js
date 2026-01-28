/**
 * ============================================
 * ACADEMIX - Main JavaScript
 * University Management System
 * Version: 2.0
 * ============================================
 */

/* ============================================
   DOM READY EVENT HANDLERS
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {
    // expose to window for the onclick handlers in HTML (legacy support)
    // Sidebar Toggles
    const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');

    function toggleSidebar() {
        const isOpen = document.body.classList.toggle('sidebar-open');
        if (sidebarBackdrop) sidebarBackdrop.classList.toggle('hidden', !isOpen);
    }

    if (sidebarToggles) {
        sidebarToggles.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleSidebar();
            });
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', toggleSidebar);
    }

    // Notification Toggle
    const notifBtn = document.getElementById('notif-btn');
    const notifDropdown = document.getElementById('notif-dropdown');
    const userMenu = document.getElementById('user-menu');

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            // Close user menu if open
            if (userMenu && !userMenu.classList.contains('hidden')) {
                userMenu.classList.add('hidden');
            }
        });
    }

    // User Menu Toggle (Existing user-menu-button logic might be missing? Adding it for completeness)
    const userMenuBtn = document.getElementById('user-menu-button');
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
            // Close notif if open
            if (notifDropdown && !notifDropdown.classList.contains('hidden')) {
                notifDropdown.classList.add('hidden');
            }
        });
    }

    // Close clicks (Outside Click)
    document.addEventListener('click', function (e) {
        // Close User Menu
        if (userMenu && !userMenu.classList.contains('hidden') && !userMenu.contains(e.target) && e.target !== userMenuBtn) {
            userMenu.classList.add('hidden');
        }
        // Close Notifications
        if (notifDropdown && !notifDropdown.classList.contains('hidden') && !notifDropdown.contains(e.target) && e.target !== notifBtn) {
            notifDropdown.classList.add('hidden');
        }
    });


    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('[role="alert"]');
    flashMessages.forEach(function (message) {
        setTimeout(function () {
            message.style.opacity = '0';
            setTimeout(function () {
                message.remove();
            }, 300);
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // Stop immediate navigation/submit

            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
            const href = this.getAttribute('href');
            const form = this.closest('form');

            showConfirmModal(message, function () {
                if (href && href !== '#') {
                    window.location.href = href;
                } else if (form) {
                    form.submit();
                } else if (button.type === 'submit') {
                    // If it's a submit button outside a form (rare) or inside but prevented
                    // If inside form, closest('form') would have caught it.
                    // If button has specific onclick implementation, we might need to trigger it manually?
                    // data-confirm-delete usually implies simple link or form submit.
                }
            });
        });
    });

    // Confirm delete actions - Intercept and use generic confirm or custom if available
    // For now, we continue using native confirm but styled via separate component if implemented.
    // However, the prompt says "Refactor JS assets to use Global Modal".
    // I will use a simple workaround: If we want strict brutalist, we need a custom confirm.
    // I will replace `confirm()` with a custom implementation in a future step or just style the validation alert.
    // Let's first Replace the validation alert at line 73.
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(function (field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Use Global Modal
                if (window.showGlobalModal) {
                    showGlobalModal('VALIDATION ERROR', 'Some required fields are missing. Please check the form.', 'error');
                } else {
                    console.error('Validation failed: Global Modal not loaded.');
                }
            }
        });
    });

    // Handle Forms with data-confirm-delete (replaces onsubmit="return confirm(...)")
    const confirmForms = document.querySelectorAll('form[data-confirm-delete]');
    confirmForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to proceed?';

            showConfirmModal(message, function () {
                form.submit(); // Programmatic submit skips the submit event listener
            });
        });
    });

    // Table search
    const tableSearch = document.getElementById('table-search');
    if (tableSearch) {
        tableSearch.addEventListener('keyup', function () {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');

            tableRows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Select all checkbox
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="selected[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }

    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const preview = document.getElementById(this.id + '-preview');
                if (preview) {
                    preview.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                }
            }
        });
    });

});

/* ============================================
   UTILITY FUNCTIONS
   ============================================ */

/**
 * Format file size in human-readable format
 * @param {number} bytes - File size in bytes
 * @returns {string} Formatted file size (e.g., "1.5 MB")
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Show loading spinner overlay
 * Creates and displays a full-screen loading indicator
 */
function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'loading-spinner';
    loader.className = 'fixed top-0 left-0 w-full h-full flex items-center justify-center bg-black bg-opacity-50 z-50';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loader);
}

/**
 * Hide loading spinner overlay
 * Removes the loading indicator from the page
 */
function hideLoading() {
    const loader = document.getElementById('loading-spinner');
    if (loader) {
        loader.remove();
    }
}

/**
 * Show modal by ID
 * @param {string} modalId - The ID of the modal element to show
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        // Add ARIA attribute for accessibility
        modal.setAttribute('aria-hidden', 'false');
    }
}

/**
 * Hide modal by ID
 * @param {string} modalId - The ID of the modal element to hide
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        // Add ARIA attribute for accessibility
        modal.setAttribute('aria-hidden', 'true');
    }
}

/**
 * AJAX helper function
 * @param {string} url - The URL to send the request to
 * @param {string} method - HTTP method (GET, POST, etc.)
 * @param {Object} data - Data to send with the request
 * @param {Function} callback - Callback function(error, response)
 */
function ajax(url, method, data, callback) {
    showLoading();

    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);

    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    xhr.onload = function () {
        hideLoading();
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                callback(null, response);
            } catch (e) {
                callback(null, xhr.responseText);
            }
        } else {
            callback(new Error('Request failed with status: ' + xhr.status), null);
        }
    };

    xhr.onerror = function () {
        hideLoading();
        callback(new Error('Network error occurred'), null);
    };

    if (method === 'POST' && data) {
        const formData = new URLSearchParams(data).toString();
        xhr.send(formData);
    } else {
        xhr.send();
    }
}

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type of toast (success, error, warning, info)
 */
function showToast(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };

    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 fade-in`;
    toast.textContent = message;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');

    document.body.appendChild(toast);

    setTimeout(function () {
        toast.style.opacity = '0';
        setTimeout(function () {
            toast.remove();
        }, 300);
    }, 3000);
}

/**
 * Confirm action with callback
 * @param {string} message - Confirmation message
 * @param {Function} callback - Function to execute if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Print current page
 * Triggers the browser's print dialog
 */
function printPage() {
    window.print();
}

/**
 * Export table to CSV file
 * @param {string} tableId - ID of the table element to export
 * @param {string} filename - Name for the downloaded CSV file (default: 'export.csv')
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(function (row) {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];

        cols.forEach(function (col) {
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });

        csv.push(rowData.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
