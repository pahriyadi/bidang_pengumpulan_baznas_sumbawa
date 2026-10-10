/**
 * App.js - Shared JS Utilities for SIM Pengumpulan ZIS
 */

// SweetAlert2 Toast preset
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

// Success toast
function showSuccess(message) {
    Toast.fire({ icon: 'success', title: message });
}

// Error toast
function showError(message) {
    Toast.fire({ icon: 'error', title: message });
}

// Confirm delete with SweetAlert2
function confirmDelete(callback) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: 'Data yang dihapus tidak dapat dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash mr-1"></i>Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

// Format number to Indonesian currency
function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID');
}

// Parse currency string back to number
function parseCurrency(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/[^0-9,-]+/g, '').replace(',', '.')) || 0;
}

// Initialize currency input formatting
function initCurrencyInput(selector) {
    $(selector).on('input', function () {
        let val = this.value.replace(/[^0-9]/g, '');
        if (val === '') {
            this.value = '';
            return;
        }
        this.value = parseInt(val).toLocaleString('id-ID');
    });
}

// Get raw currency value from formatted input
function getCurrencyValue(selector) {
    return parseInt($(selector).val().replace(/[^0-9]/g, '')) || 0;
}

// Initialize Select2 for dropdowns
// dropdownParent: wajib untuk select di dalam modal Bootstrap agar kotak pencarian bisa difokuskan
function initSelect2(selector, placeholder, dropdownParent) {
    var opts = {
        theme: 'bootstrap4',
        placeholder: placeholder || 'Pilih...',
        allowClear: true,
        width: '100%'
    };
    if (dropdownParent) {
        opts.dropdownParent = $(dropdownParent);
    }
    $(selector).select2(opts);
}

// DataTable default configuration (Indonesian)
$.extend($.fn.dataTable.defaults, {
    language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(difilter dari _MAX_ total data)",
        zeroRecords: "Tidak ada data yang cocok",
        emptyTable: "Tidak ada data tersedia",
        paginate: {
            first: '<i class="fas fa-angle-double-left"></i>',
            previous: '<i class="fas fa-angle-left"></i>',
            next: '<i class="fas fa-angle-right"></i>',
            last: '<i class="fas fa-angle-double-right"></i>'
        }
    },
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
});

// Document ready
$(function () {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);
});
