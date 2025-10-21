/**
 * Jade Salvador Admin Panel - Theme Manager & UI Enhancements
 * Handles theme switching, animations, and interactive features
 */

(function() {
    'use strict';

    // Theme Manager
    const ThemeManager = {
        themes: ['light', 'dark', 'pink'],
        currentTheme: 'light',
        storageKey: 'adminTheme',

        init() {
            this.loadTheme();
            this.setupEventListeners();
            this.updateActiveButton();
        },

        loadTheme() {
            const savedTheme = localStorage.getItem(this.storageKey);
            if (savedTheme && this.themes.includes(savedTheme)) {
                this.currentTheme = savedTheme;
            }
            this.applyTheme(this.currentTheme);
        },

        applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            this.currentTheme = theme;
            localStorage.setItem(this.storageKey, theme);
            
            // Dispatch custom event for theme change
            window.dispatchEvent(new CustomEvent('themeChanged', { 
                detail: { theme } 
            }));
        },

        setupEventListeners() {
            const themeButtons = document.querySelectorAll('.theme-btn, [data-theme]');
            themeButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const theme = e.currentTarget.getAttribute('data-theme');
                    if (theme && this.themes.includes(theme)) {
                        this.applyTheme(theme);
                        this.updateActiveButton();
                        this.showThemeToast(theme);
                    }
                });
            });
        },

        updateActiveButton() {
            const themeButtons = document.querySelectorAll('.theme-btn, [data-theme]');
            themeButtons.forEach(btn => {
                const btnTheme = btn.getAttribute('data-theme');
                if (btnTheme === this.currentTheme) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        },

         
    };

    // UI Enhancements
    const UIEnhancements = {
        init() {
            this.setupAnimations();
            this.setupTooltips();
            this.setupConfirmations();
            this.setupImagePreviews();
            this.setupTableSearch();
            this.setupFormValidation();
        },

        setupAnimations() {
            // Add fade-in animation to cards on page load
            const cards = document.querySelectorAll('.card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.animation = `fadeIn 0.5s ease forwards ${index * 0.1}s`;
            });

            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(4px)';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        },

        setupTooltips() {
            // Initialize Bootstrap tooltips if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(
                    document.querySelectorAll('[data-bs-toggle="tooltip"]')
                );
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        },

        setupConfirmations() {
            // Add confirmation dialogs to delete buttons
            const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm-delete') || 
                                  'Are you sure you want to delete this item?';
                    
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        },

        setupImagePreviews() {
            // File input image preview
            const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            // Create or update preview
                            let preview = input.parentElement.querySelector('.image-preview');
                            if (!preview) {
                                preview = document.createElement('div');
                                preview.className = 'image-preview mt-3';
                                input.parentElement.appendChild(preview);
                            }
                            preview.innerHTML = `
                                <img src="${e.target.result}" 
                                     class="img-thumbnail" 
                                     style="max-width: 200px; max-height: 200px; border-radius: 12px;">
                                <p class="mt-2 mb-0 text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>Preview of selected image
                                </p>
                            `;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        },

        setupTableSearch() {
            // Add search functionality to tables
            const searchInputs = document.querySelectorAll('[data-table-search]');
            searchInputs.forEach(input => {
                const tableId = input.getAttribute('data-table-search');
                const table = document.getElementById(tableId);
                
                if (table) {
                    input.addEventListener('keyup', function() {
                        const searchTerm = this.value.toLowerCase();
                        const rows = table.querySelectorAll('tbody tr');
                        
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    });
                }
            });
        },

        setupFormValidation() {
            // Add real-time form validation
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });

                // Real-time validation for inputs
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        if (this.checkValidity()) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                        }
                    });
                });
            });
        }
    };

    // Utility Functions
    const Utils = {
        // Format numbers with commas
        formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        // Copy text to clipboard
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showNotification('Copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        },

        // Show notification
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} notification-toast`;
            notification.innerHTML = `
                <i class="bi bi-check-circle-fill me-2"></i>
                ${message}
            `;
            notification.style.cssText = `
                position: fixed;
                top: 90px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideInRight 0.3s ease;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },

        // Debounce function for search
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Auto-save feature for forms
    const AutoSave = {
        init() {
            const forms = document.querySelectorAll('[data-autosave]');
            forms.forEach(form => {
                const formId = form.id || 'form_' + Date.now();
                const inputs = form.querySelectorAll('input, textarea, select');
                
                // Load saved data
                this.loadFormData(formId, inputs);
                
                // Save on input
                inputs.forEach(input => {
                    input.addEventListener('input', Utils.debounce(() => {
                        this.saveFormData(formId, inputs);
                    }, 500));
                });
                
                // Clear on submit
                form.addEventListener('submit', () => {
                    this.clearFormData(formId);
                });
            });
        },

        saveFormData(formId, inputs) {
            const data = {};
            inputs.forEach(input => {
                if (input.name && input.type !== 'file' && input.type !== 'password') {
                    data[input.name] = input.value;
                }
            });
            localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
        },

        loadFormData(formId, inputs) {
            const savedData = localStorage.getItem(`autosave_${formId}`);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    inputs.forEach(input => {
                        if (data[input.name] && input.type !== 'file' && input.type !== 'password') {
                            input.value = data[input.name];
                        }
                    });
                } catch (e) {
                    console.error('Error loading autosave data:', e);
                }
            }
        },

        clearFormData(formId) {
            localStorage.removeItem(`autosave_${formId}`);
        }
    };

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

    // Initialize everything when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ThemeManager.init();
            UIEnhancements.init();
            AutoSave.init();
        });
    } else {
        ThemeManager.init();
        UIEnhancements.init();
        AutoSave.init();
    }

    // Export utilities to window for global access
    window.AdminUtils = Utils;
    window.AdminTheme = ThemeManager;

})();