/**
 * JavaScript principal
 * BestBigData
 */

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Initialize DataTables with Spanish language
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 25,
            responsive: true
        });
    }

    // Confirm delete actions
    $('[data-confirm]').on('click', function(e) {
        if (!confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });

    // Format phone number input
    $('input[name="phone"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        $(this).val(value);
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        let input = $($(this).data('target'));
        let icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Form validation feedback
    $('form').on('submit', function() {
        let btn = $(this).find('button[type="submit"]');
        let originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Procesando...');
        btn.prop('disabled', true);
        
        // Re-enable after 10 seconds in case of error
        setTimeout(function() {
            btn.html(originalText);
            btn.prop('disabled', false);
        }, 10000);
    });

    // Sidebar active state
    let currentPath = window.location.pathname;
    $('.sidebar .nav-link').each(function() {
        let href = $(this).attr('href');
        if (currentPath.includes(href) && href !== '#') {
            $(this).addClass('active');
        }
    });

    // Tooltips
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Copy to clipboard
    $('.copy-btn').on('click', function() {
        let text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(function() {
            // Show feedback
            let btn = $(this);
            let originalHtml = btn.html();
            btn.html('<i class="bi bi-check"></i>');
            setTimeout(function() {
                btn.html(originalHtml);
            }, 2000);
        }.bind(this));
    });
});

// Utility functions
function formatMoney(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(dateString) {
    let date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
