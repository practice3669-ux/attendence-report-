/**
 * Main JavaScript File
 * Common utilities and functionality
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initDropdowns();
    initModals();
    initToasts();
    initForms();
});

// Sidebar Toggle
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (sidebar && !sidebar.contains(e.target) && 
                !mobileMenuToggle.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        }
    });
}

// Dropdown Menus
function initDropdowns() {
    const notificationBtn = document.getElementById('notificationBtn');
    const userBtn = document.getElementById('userBtn');
    const notificationsMenu = document.getElementById('notificationsMenu');
    const userMenu = document.getElementById('userMenu');
    
    if (notificationBtn && notificationsMenu) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsMenu.classList.toggle('active');
            if (userMenu) userMenu.classList.remove('active');
        });
    }
    
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('active');
            if (notificationsMenu) notificationsMenu.classList.remove('active');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        if (notificationsMenu) notificationsMenu.classList.remove('active');
        if (userMenu) userMenu.classList.remove('active');
    });
}

// Modal Functions
function initModals() {
    window.openModal = function(modalId, content) {
        const modalContainer = document.getElementById('modalContainer');
        if (!modalContainer) return;
        
        modalContainer.innerHTML = `
            <div class="modal" id="${modalId}">
                <div class="modal-header">
                    <h3>${content.title || 'Modal'}</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    ${content.body || ''}
                </div>
                ${content.footer ? `<div class="modal-footer">${content.footer}</div>` : ''}
            </div>
        `;
        
        modalContainer.classList.add('active');
        
        // Close on background click
        modalContainer.addEventListener('click', function(e) {
            if (e.target === modalContainer) {
                closeModal();
            }
        });
    };
    
    window.closeModal = function() {
        const modalContainer = document.getElementById('modalContainer');
        if (modalContainer) {
            modalContainer.classList.remove('active');
            setTimeout(() => {
                modalContainer.innerHTML = '';
            }, 300);
        }
    };
}

// Toast Notifications
function initToasts() {
    window.showToast = function(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        toast.innerHTML = `
            <span style="font-weight: bold; font-size: 18px;">${icons[type] || icons.info}</span>
            <span>${message}</span>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, duration);
    };
}

// Add slideOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Form Validation
function initForms() {
    // Auto-validate forms on submit
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
    
    // Real-time validation
    const inputs = document.querySelectorAll('input[data-validate], select[data-validate], textarea[data-validate]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('[required], [data-validate]');
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Required validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address';
    }
    
    // Phone validation
    if (field.type === 'tel' && value && !isValidPhone(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid phone number';
    }
    
    // Min length
    const minLength = field.getAttribute('data-min-length');
    if (minLength && value.length < parseInt(minLength)) {
        isValid = false;
        errorMessage = `Minimum length is ${minLength} characters`;
    }
    
    // Max length
    const maxLength = field.getAttribute('data-max-length');
    if (maxLength && value.length > parseInt(maxLength)) {
        isValid = false;
        errorMessage = `Maximum length is ${maxLength} characters`;
    }
    
    // Show/hide error
    showFieldError(field, isValid, errorMessage);
    
    return isValid;
}

function showFieldError(field, isValid, message) {
    // Remove existing error
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    field.classList.remove('error');
    
    if (!isValid) {
        field.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        field.parentElement.appendChild(errorDiv);
    }
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[6-9]\d{9}$/.test(phone.replace(/[^0-9]/g, ''));
}

// AJAX Helper Functions
const APP_BASE_URL = (typeof window.APP_URL !== 'undefined') ? window.APP_URL : '';

// Normalize relative URLs to absolute API endpoints to avoid path issues on nested pages
function resolveUrl(url) {
    if (!url) return url;
    // Already absolute
    if (/^https?:\/\//i.test(url)) return url;
    // Strip leading ./ or ../ and prefix with base
    const cleaned = url.replace(/^\.?\//, '').replace(/^\.\.\//, '');
    return APP_BASE_URL ? `${APP_BASE_URL}/${cleaned}` : url;
}

window.ajaxRequest = function(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: null
    };
    
    const config = { ...defaults, ...options };
    const targetUrl = resolveUrl(url);
    
    if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
        config.body = JSON.stringify(config.body);
    }
    
    // If sending FormData, let the browser set headers
    if (config.body instanceof FormData) {
        delete config.headers['Content-Type'];
    }
    
    return fetch(targetUrl, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            showToast('An error occurred. Please try again.', 'error');
            throw error;
        });
};

// Loading State
window.setLoading = function(element, isLoading) {
    if (isLoading) {
        element.disabled = true;
        element.dataset.originalText = element.textContent;
        element.innerHTML = '<span class="spinner"></span> Loading...';
    } else {
        element.disabled = false;
        element.textContent = element.dataset.originalText || element.textContent;
    }
};

// Confirm Dialog
window.confirmAction = function(message, callback) {
    if (confirm(message)) {
        callback();
    }
};

// Format Currency
window.formatCurrency = function(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Format Date
window.formatDate = function(dateString, format = 'DD MMM YYYY') {
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    return format
        .replace('DD', String(date.getDate()).padStart(2, '0'))
        .replace('MMM', months[date.getMonth()])
        .replace('YYYY', date.getFullYear());
};

// Local Storage Helpers
window.saveToLocalStorage = function(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
    } catch (e) {
        console.error('LocalStorage error:', e);
    }
};

window.getFromLocalStorage = function(key) {
    try {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch (e) {
        console.error('LocalStorage error:', e);
        return null;
    }
};

window.removeFromLocalStorage = function(key) {
    try {
        localStorage.removeItem(key);
    } catch (e) {
        console.error('LocalStorage error:', e);
    }
};

// Auto-save form data
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-autosave]');
    forms.forEach(form => {
        const formId = form.id || 'form_' + Date.now();
        
        // Load saved data
        const savedData = getFromLocalStorage('form_' + formId);
        if (savedData) {
            Object.keys(savedData).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (!field) return;
                // Never restore file inputs
                if (field.type && field.type.toLowerCase() === 'file') return;
                field.value = savedData[key];
            });
        }
        
        // Save on input
        form.addEventListener('input', function() {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                // Skip files when autosaving
                const input = form.querySelector(`[name="${key}"]`);
                if (input && input.type && input.type.toLowerCase() === 'file') {
                    continue;
                }
                data[key] = value;
            }
            saveToLocalStorage('form_' + formId, data);
        });
        
        // Clear on submit
        form.addEventListener('submit', function() {
            removeFromLocalStorage('form_' + formId);
        });
    });
});

