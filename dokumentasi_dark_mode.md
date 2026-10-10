# Panduan Implementasi Sistem Mode Gelap & Terang (Dark / Light Mode Engine)
**Universal Guide for Web Applications, AdminLTE v3, & Custom Dashboards**

Dokumen ini berisi panduan lengkap, arsitektur, dan kode siap pakai untuk menerapkan fitur **Mode Gelap dan Terang (Dark/Light Mode)** yang mulus, bebas kedipan (*anti-flicker*), tanpa dependensi eksternal (*zero-dependency*), dan mempertahankan estetika minimalis modern (*Paper Dark*).

---

## 1. Arsitektur & Filosofi Desain

Fitur ini dirancang dengan prinsip:
1. **Zero External Dependency**: Hanya menggunakan CSS murni (*vanilla CSS*) dan JavaScript bawaan (*vanilla JS*).
2. **Anti-Flicker (Bebas Kedipan Putih)**: Membaca status tema dari `localStorage` atau preferensi OS (`prefers-color-scheme`) secara sinkron di dalam tag `<head>` sebelum perenderan DOM dimulai.
3. **Flat "Paper Dark" Palette**:
   - **Latar Belakang Dasar**: `#14171a` (Background body utama)
   - **Latar Belakang Konten / Wrapper**: `#181b1f` (Content wrapper, navbar, footer, sidebar)
   - **Latar Belakang Card / Panel / Modal**: `#1f2328` (Card body, modal content, panel)
   - **Latar Belakang Input / Tombol Hover**: `#262b32` (Form control, select, pagination)
   - **Garis Batas Tegas (Borders)**: `#374151` (Border card, divider, batas tabel)
   - **Warna Teks Primer**: `#f3f4f6` / `#ffffff`
   - **Warna Teks Sekunder**: `#9ca3af` / `#d1d5db`
   - **Warna Aksen Brand**: Tetap mempertahankan warna brand utama (misal: Hijau BAZNAS `#0d9f4f`).

---

## 2. Langkah-Langkah Integrasi pada Sistem Web

### Langkah 1: Script Anti-Flicker pada `<head>`
Tambahkan blok script ini di dalam file template header (misal: `inc/header.php` atau `header.html`), tepat di dalam tag `<head>` sebelum resource CSS selesai dimuat:

```html
<head>
    <!-- Meta tags & Title -->
    ...

    <!-- ANTI-FLICKER: Deteksi tema instan sebelum layar dirender -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="path/to/bootstrap-or-adminlte.min.css">
    <link rel="stylesheet" href="path/to/custom.css">
</head>
```

---

### Langkah 2: Sinkronisasi Awal pada Tag `<body>`
Tepat setelah tag pembuka `<body>`, tambahkan sinkronisasi kelas ke elemen body:

```html
<body class="hold-transition sidebar-mini layout-fixed">
<script>
    if (document.documentElement.classList.contains('dark-mode')) {
        document.body.classList.add('dark-mode');
    }
</script>
```

---

### Langkah 3: Tombol Beralih Mode pada Navbar / Topbar
Tambahkan ikon tombol ganti mode (Bulan/Matahari) di dalam navbar (misal: `inc/topbar.php`):

```html
<ul class="navbar-nav ml-auto">
    <!-- Tombol Mode Gelap / Terang -->
    <li class="nav-item mr-1">
        <a class="nav-link" href="javascript:void(0)" id="btn-theme-toggle" onclick="toggleDarkMode()" title="Ganti Mode Gelap / Terang" style="cursor: pointer;">
            <i class="fas fa-moon" id="theme-toggle-icon" style="font-size: 1.1rem;"></i>
        </a>
    </li>
    
    <!-- Menu profil pengguna lainnya -->
    ...
</ul>
```

*(Opsional: Untuk halaman Login yang tidak memiliki navbar, tombol dapat ditempatkan dengan posisi `fixed` di pojok kanan atas):*
```html
<div style="position: fixed; top: 15px; right: 20px; z-index: 1000;">
    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-theme-toggle" onclick="toggleDarkMode()" title="Ganti Mode Gelap / Terang" style="border-radius: 50%; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-moon" id="theme-toggle-icon"></i>
    </button>
</div>
```

---

### Langkah 4: Controller JavaScript pada Footer
Tambahkan fungsi controller berikut pada file template footer (misal: `inc/footer.php`) sebelum tag penutup `</body>`:

```html
<script>
// ==========================================
// DARK / LIGHT MODE CONTROLLER ENGINE
// ==========================================
function toggleDarkMode() {
    var isDark = document.body.classList.toggle('dark-mode');
    document.documentElement.classList.toggle('dark-mode', isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeUI(isDark);
    
    // Dispatch event agar komponen dinamis (grafik/chart) dapat merespons jika perlu
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { isDark: isDark } }));
}

function updateThemeUI(isDark) {
    // 1. Perbarui Ikon Toggle (Matahari / Bulan)
    var icon = document.getElementById('theme-toggle-icon');
    if (icon) {
        if (isDark) {
            icon.className = 'fas fa-sun text-warning';
        } else {
            icon.className = 'fas fa-moon';
        }
    }
    
    // 2. Sinkronkan Navbar AdminLTE (jika menggunakan AdminLTE)
    var nav = document.querySelector('.main-header.navbar');
    if (nav) {
        if (isDark) {
            nav.classList.remove('navbar-white', 'navbar-light');
            nav.classList.add('navbar-dark');
        } else {
            nav.classList.remove('navbar-dark');
            nav.classList.add('navbar-white', 'navbar-light');
        }
    }
    
    // 3. Sinkronkan Sidebar AdminLTE (jika menggunakan AdminLTE)
    var sidebar = document.querySelector('.main-sidebar');
    if (sidebar) {
        if (isDark) {
            sidebar.classList.remove('sidebar-light-success', 'sidebar-light-primary');
            sidebar.classList.add('sidebar-dark-success', 'sidebar-dark-primary');
        } else {
            sidebar.classList.remove('sidebar-dark-success', 'sidebar-dark-primary');
            sidebar.classList.add('sidebar-light-success', 'sidebar-light-primary');
        }
    }
}

// Sinkronisasi status UI saat DOM selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
    if (isDark && !document.body.classList.contains('dark-mode')) {
        document.body.classList.add('dark-mode');
    }
    updateThemeUI(isDark);
});
</script>
```

---

## 3. Bundel CSS Universal Mode Gelap (`custom.css`)

Salin dan masukkan kumpulan aturan CSS berikut ke file stylesheet utama aplikasi Anda (misal: `assets/css/custom.css`):

```css
/* ==============================================================
   DARK MODE COMPREHENSIVE OVERRIDES (Paper Dark / Minimalist Flat)
   ============================================================== */

/* 1. DASAR BODY & CONTENT WRAPPER */
body.dark-mode {
    background-color: #14171a !important;
    color: #e5e7eb !important;
}

.dark-mode .content-wrapper {
    background-color: #181b1f !important;
    color: #e5e7eb !important;
}

.dark-mode .content-header h1 {
    color: #f3f4f6 !important;
}

.dark-mode .breadcrumb-item a {
    color: #34d399 !important;
}

.dark-mode .breadcrumb-item.active {
    color: #9ca3af !important;
}

/* 2. KARTU & PANEL (CARD & MODAL) */
.dark-mode .card,
.dark-mode .flat-card {
    background-color: #1f2328 !important;
    border-color: #374151 !important;
    color: #e5e7eb !important;
    box-shadow: none !important;
}

.dark-mode .card-header,
.dark-mode .card-header.bg-white,
.dark-mode [style*="background-color: #ffffff"],
.dark-mode [style*="background-color:#ffffff"] {
    background-color: #1f2328 !important;
    border-color: #374151 !important;
    color: #f3f4f6 !important;
}

.dark-mode .card-body,
.dark-mode .card-footer {
    background-color: #1f2328 !important;
    color: #e5e7eb !important;
    border-color: #374151 !important;
}

.dark-mode .modal-content {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
    color: #e5e7eb !important;
}

.dark-mode .modal-header,
.dark-mode .modal-footer {
    background-color: #181b1f !important;
    border-color: #374151 !important;
}

/* 3. SIDEBAR & MENU NAVIGASI */
.dark-mode .main-sidebar,
.dark-mode aside.main-sidebar,
.dark-mode .main-sidebar[class*="sidebar-"],
.dark-mode .sidebar-light-success,
.dark-mode .sidebar-dark-success {
    background-color: #181b1f !important;
    border-right: 1px solid #374151 !important;
}

.dark-mode .main-sidebar .brand-link,
.dark-mode aside.main-sidebar .brand-link,
.dark-mode .brand-link,
.dark-mode .user-panel {
    background-color: #181b1f !important;
    border-bottom: 1px solid #374151 !important;
    color: #f3f4f6 !important;
}

.dark-mode .main-sidebar .brand-text,
.dark-mode .user-panel .info a {
    color: #f3f4f6 !important;
}

.dark-mode .user-panel .info small {
    color: #9ca3af !important;
}

.dark-mode .nav-sidebar .nav-item .nav-link,
.dark-mode .sidebar .nav-sidebar .nav-item > .nav-link {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
    color: #d1d5db !important;
}

.dark-mode .nav-sidebar .nav-item .nav-link i {
    color: #9ca3af !important;
}

.dark-mode .nav-sidebar .nav-item .nav-link:hover {
    background-color: #262b32 !important;
    border-color: #4b5563 !important;
    color: #ffffff !important;
}

.dark-mode .nav-sidebar .nav-item .nav-link:hover i {
    color: #ffffff !important;
}

.dark-mode .nav-sidebar .nav-item .nav-link.active,
.dark-mode .sidebar-dark-success .nav-sidebar>.nav-item>.nav-link.active,
.dark-mode .sidebar-light-success .nav-sidebar>.nav-item>.nav-link.active {
    background-color: rgba(13, 159, 79, 0.2) !important;
    border: 1px solid #0d9f4f !important;
    border-left: 4px solid #0d9f4f !important;
    color: #34d399 !important;
}

.dark-mode .sidebar-dark-success .nav-sidebar>.nav-item>.nav-link.active i,
.dark-mode .sidebar-light-success .nav-sidebar>.nav-item>.nav-link.active i,
.dark-mode .nav-sidebar .nav-item .nav-link.active i {
    color: #34d399 !important;
}

.dark-mode .nav-sidebar .nav-treeview .nav-item .nav-link {
    background-color: #181b1f !important;
    border-color: #2d333b !important;
    color: #9ca3af !important;
}

.dark-mode .nav-sidebar .nav-treeview .nav-item .nav-link:hover {
    background-color: #22272e !important;
    color: #ffffff !important;
}

.dark-mode .nav-sidebar .nav-treeview .nav-item .nav-link.active {
    background-color: rgba(13, 159, 79, 0.2) !important;
    border-color: #0d9f4f !important;
    color: #34d399 !important;
}

.dark-mode .nav-sidebar .nav-header {
    background-color: #181b1f !important;
    color: #9ca3af !important;
    border-top: 1px solid #2d333b !important;
}

/* 4. METRIC BOXES & SMALL-BOXES DASHBOARD */
.dark-mode .small-box,
.dark-mode .small-box.bg-primary,
.dark-mode .small-box.bg-success,
.dark-mode .small-box.bg-info,
.dark-mode .small-box.bg-warning,
.dark-mode .small-box.bg-danger,
.dark-mode .small-box.bg-secondary,
.dark-mode .info-box {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
    color: #e5e7eb !important;
    box-shadow: none !important;
    border-radius: 4px !important;
}

.dark-mode .small-box.bg-primary { border-top: 3px solid #3b82f6 !important; }
.dark-mode .small-box.bg-success { border-top: 3px solid #0d9f4f !important; }
.dark-mode .small-box.bg-info    { border-top: 3px solid #06b6d4 !important; }
.dark-mode .small-box.bg-warning { border-top: 3px solid #f59e0b !important; }
.dark-mode .small-box.bg-danger  { border-top: 3px solid #ef4444 !important; }

.dark-mode .small-box>.inner h3,
.dark-mode .info-box .info-box-number {
    color: #ffffff !important;
}

.dark-mode .small-box>.inner p,
.dark-mode .info-box .info-box-text {
    color: #9ca3af !important;
}

.dark-mode .small-box .icon {
    color: rgba(255, 255, 255, 0.08) !important;
}

.dark-mode .small-box:hover .icon {
    color: rgba(255, 255, 255, 0.15) !important;
}

.dark-mode .small-box .small-box-footer {
    background-color: #181b1f !important;
    border-top: 1px solid #374151 !important;
    color: #9ca3af !important;
}

.dark-mode .small-box .small-box-footer:hover {
    background-color: #262b32 !important;
    color: #34d399 !important;
}

/* 5. TABEL & DATATABLES SERVER-SIDE */
.dark-mode .table,
.dark-mode .table-excel,
.dark-mode table.dataTable {
    background-color: #1f2328 !important;
    border: 1.5px solid #374151 !important;
    color: #e5e7eb !important;
}

.dark-mode .table th,
.dark-mode .table-excel th,
.dark-mode table.dataTable thead th,
.dark-mode table.dataTable thead td,
.dark-mode .thead-dark th,
.dark-mode .thead-light th {
    background-color: #181b1f !important;
    color: #f3f4f6 !important;
    border: 1px solid #374151 !important;
}

.dark-mode .table tbody tr td,
.dark-mode .table tbody tr th,
.dark-mode table.dataTable tbody tr td,
.dark-mode table.dataTable tbody tr th {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
    color: #e5e7eb !important;
}

.dark-mode .table-striped>tbody>tr:nth-of-type(odd)>*,
.dark-mode table.dataTable tbody tr.odd,
.dark-mode table.dataTable tbody tr.even {
    background-color: #1f2328 !important;
    background: #1f2328 !important;
    --bs-table-striped-bg: #1f2328 !important;
    color: #e5e7eb !important;
}

.dark-mode table.dataTable tbody td.sorting_1 {
    background-color: #242930 !important;
}

.dark-mode .table tbody tr:hover td,
.dark-mode .table-hover tbody tr:hover,
.dark-mode table.dataTable.table-hover tbody tr:hover td {
    background-color: #143522 !important; /* Soft Green hover */
    color: #ffffff !important;
}

.dark-mode .table tfoot tr td {
    background-color: #181b1f !important;
    color: #f3f4f6 !important;
    border-top: 1.5px solid #374151 !important;
}

/* Kontrol Strip DataTables (Search, Length, Info, Pagination) */
.dark-mode .dataTables_wrapper,
.dark-mode .dataTables_wrapper .dataTables_length,
.dark-mode .dataTables_wrapper .dataTables_filter,
.dark-mode .dataTables_wrapper .dataTables_info,
.dark-mode .dataTables_wrapper .dataTables_paginate {
    background-color: #1f2328 !important;
    color: #9ca3af !important;
    border-color: #374151 !important;
}

.dark-mode .dataTables_wrapper .dataTables_length label,
.dark-mode .dataTables_wrapper .dataTables_filter label {
    color: #d1d5db !important;
}

.dark-mode .dataTables_wrapper .dataTables_filter input,
.dark-mode .dataTables_wrapper .dataTables_length select {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #ffffff !important;
    border-radius: 4px;
    padding: 4px 8px;
}

.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
    background: #262b32 !important;
    border: 1px solid #374151 !important;
    color: #d1d5db !important;
}

.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #ffffff !important;
}

.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: #0d9f4f !important;
    border-color: #0d9f4f !important;
    color: #ffffff !important;
}

/* 6. FORM INPUTS & SELECT2 */
.dark-mode .form-control,
.dark-mode .custom-select,
.dark-mode textarea.form-control,
.dark-mode .input-group-text {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #f9fafb !important;
}

.dark-mode .form-control:focus,
.dark-mode .custom-select:focus,
.dark-mode textarea.form-control:focus {
    border-color: #0d9f4f !important;
    box-shadow: 0 0 0 2px rgba(13, 159, 79, 0.3) !important;
    background-color: #262b32 !important;
    color: #ffffff !important;
}

.dark-mode .form-control::placeholder {
    color: #9ca3af !important;
}

.dark-mode .select2-container--bootstrap4 .select2-selection {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #f9fafb !important;
}

.dark-mode .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    color: #f9fafb !important;
}

.dark-mode .select2-dropdown {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
    color: #f9fafb !important;
}

.dark-mode .select2-results__option {
    color: #e5e7eb !important;
}

.dark-mode .select2-results__option--highlighted {
    background-color: #0d9f4f !important;
    color: #ffffff !important;
}

.dark-mode .select2-search--dropdown .select2-search__field {
    background-color: #262b32 !important;
    border-color: #4b5563 !important;
    color: #ffffff !important;
}

/* Kalender Native HTML5 (Datepicker Otomatis Gelap) */
.dark-mode input[type="date"].form-control,
.dark-mode input[type="datetime-local"].form-control,
.dark-mode input[type="month"].form-control {
    color-scheme: dark !important;
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #ffffff !important;
}

.dark-mode input[type="date"].form-control:focus {
    border-color: #0d9f4f !important;
    box-shadow: 0 0 0 2px rgba(13, 159, 79, 0.25) !important;
}

/* Select2 Multi-Select Tags (Filter Laporan / Kategori Banyak) */
.dark-mode .select2-container--bootstrap4 .select2-selection--multiple {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #ffffff !important;
    min-height: 38px;
}

.dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
    padding: 2px 6px !important;
}

.dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
    background-color: #1a2e22 !important;
    border: 1px solid #0d9f4f !important;
    color: #34d399 !important;
    border-radius: 3px !important;
    padding: 2px 8px !important;
    margin: 3px 4px 3px 0 !important;
    font-size: 0.82rem !important;
    font-weight: 500 !important;
}

.dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
    color: #f87171 !important;
    margin-right: 6px !important;
    font-weight: bold !important;
}

.dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ef4444 !important;
}

.dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field {
    background-color: transparent !important;
    color: #ffffff !important;
    margin-top: 5px !important;
}

.dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field::placeholder {
    color: #9ca3af !important;
}

.dark-mode .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple {
    border-color: #0d9f4f !important;
    box-shadow: 0 0 0 2px rgba(13, 159, 79, 0.25) !important;
}

.dark-mode .select2-container--bootstrap4 .select2-results__option[aria-selected="true"] {
    background-color: #1e293b !important;
    color: #93c5fd !important;
}

/* 7. MODAL FORM & DIALOGS */
.dark-mode .modal-content {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
    color: #e5e7eb !important;
}

.dark-mode .modal-header,
.dark-mode .modal-footer {
    background-color: #181b1f !important;
    border-color: #374151 !important;
}

.dark-mode .modal-header .close {
    color: #9ca3af !important;
    text-shadow: none !important;
}

.dark-mode .modal-header .close:hover {
    color: #ffffff !important;
}

.dark-mode .form-group label,
.dark-mode form label {
    color: #d1d5db !important;
    font-weight: 500;
}

.dark-mode .modal-body small.text-muted,
.dark-mode .form-text.text-muted {
    color: #9ca3af !important;
}

/* 8. TOMBOL-TOMBOL (OUTLINE BUTTONS) */
.dark-mode .btn-primary,
.dark-mode .btn-outline-primary {
    background-color: #1e293b !important;
    border: 1px solid #3b82f6 !important;
    color: #60a5fa !important;
}
.dark-mode .btn-primary:hover,
.dark-mode .btn-outline-primary:hover {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
    color: #ffffff !important;
}

.dark-mode .btn-success,
.dark-mode .btn-outline-success {
    background-color: #143522 !important;
    border: 1px solid #0d9f4f !important;
    color: #34d399 !important;
}
.dark-mode .btn-success:hover,
.dark-mode .btn-outline-success:hover {
    background-color: #0d9f4f !important;
    border-color: #0d9f4f !important;
    color: #ffffff !important;
}

.dark-mode .btn-danger,
.dark-mode .btn-outline-danger {
    background-color: #2f1b1d !important;
    border: 1px solid #ef4444 !important;
    color: #f87171 !important;
}
.dark-mode .btn-danger:hover,
.dark-mode .btn-outline-danger:hover {
    background-color: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
}

.dark-mode .btn-warning,
.dark-mode .btn-outline-warning,
.dark-mode .btn-gold {
    background-color: #332712 !important;
    border: 1px solid #f59e0b !important;
    color: #fbbf24 !important;
}
.dark-mode .btn-warning:hover,
.dark-mode .btn-outline-warning:hover,
.dark-mode .btn-gold:hover {
    background-color: #d97706 !important;
    border-color: #d97706 !important;
    color: #ffffff !important;
}

.dark-mode .btn-info,
.dark-mode .btn-outline-info {
    background-color: #162c38 !important;
    border: 1px solid #06b6d4 !important;
    color: #38bdf8 !important;
}
.dark-mode .btn-info:hover,
.dark-mode .btn-outline-info:hover {
    background-color: #0891b2 !important;
    border-color: #0891b2 !important;
    color: #ffffff !important;
}

.dark-mode .btn-secondary,
.dark-mode .btn-default,
.dark-mode .btn-light {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #d1d5db !important;
}
.dark-mode .btn-secondary:hover,
.dark-mode .btn-default:hover,
.dark-mode .btn-light:hover {
    background-color: #374151 !important;
    border-color: #6b7280 !important;
    color: #ffffff !important;
}

/* 9. BADGES, DROPDOWNS, & SWEETALERT2 */
.dark-mode .badge-primary {
    background-color: rgba(37, 99, 235, 0.2) !important;
    color: #93c5fd !important;
    border: 1px solid #2563eb !important;
}

.dark-mode .badge-success {
    background-color: rgba(13, 159, 79, 0.2) !important;
    color: #6ee7b7 !important;
    border: 1px solid #0d9f4f !important;
}

.dark-mode .badge-warning {
    background-color: rgba(245, 158, 11, 0.2) !important;
    color: #fde047 !important;
    border: 1px solid #f59e0b !important;
}

.dark-mode .badge-danger {
    background-color: rgba(239, 68, 68, 0.2) !important;
    color: #fca5a5 !important;
    border: 1px solid #ef4444 !important;
}

.dark-mode .badge-secondary {
    background-color: rgba(107, 114, 128, 0.2) !important;
    color: #d1d5db !important;
    border: 1px solid #4b5563 !important;
}

.dark-mode .dropdown-menu {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
}

.dark-mode .dropdown-item {
    background-color: #1f2328 !important;
    color: #e5e7eb !important;
    border-bottom: 1px solid #2d333b !important;
}

.dark-mode .dropdown-item:hover {
    background-color: #262b32 !important;
    color: #ffffff !important;
}

/* SweetAlert2 Modal Gelap */
.dark-mode .swal2-popup {
    background-color: #1f2328 !important;
    color: #e5e7eb !important;
    border: 1px solid #374151 !important;
}

.dark-mode .swal2-title {
    color: #ffffff !important;
}

.dark-mode .swal2-html-container {
    color: #d1d5db !important;
}

.dark-mode .swal2-input,
.dark-mode .swal2-textarea {
    background-color: #262b32 !important;
    border-color: #4b5563 !important;
    color: #ffffff !important;
}

/* 10. APEXCHARTS / GRAFIK DARK MODE OVERRIDES */
.dark-mode .apexcharts-canvas text {
    fill: #9ca3af !important;
}

.dark-mode .apexcharts-canvas .apexcharts-title-text,
.dark-mode .apexcharts-canvas .apexcharts-legend-text {
    color: #e5e7eb !important;
}

.dark-mode .apexcharts-tooltip {
    background: #1f2328 !important;
    border-color: #374151 !important;
    color: #f3f4f6 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5) !important;
}

.dark-mode .apexcharts-tooltip-title {
    background: #181b1f !important;
    border-bottom: 1px solid #374151 !important;
    color: #f3f4f6 !important;
}

.dark-mode .apexcharts-xaxistooltip,
.dark-mode .apexcharts-yaxistooltip {
    background: #1f2328 !important;
    border-color: #374151 !important;
    color: #f3f4f6 !important;
}

.dark-mode .apexcharts-gridline {
    stroke: #2d333b !important;
}

/* 11. AI COPILOT / CHATBOT FLOATING WIDGET */
.dark-mode #ai-copilot-window,
.dark-mode .ai-copilot-window {
    background-color: #181b1f !important;
    border: 1.5px solid #374151 !important;
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.6) !important;
}

.dark-mode .ai-copilot-header {
    background-color: #181b1f !important;
    border-bottom: 1px solid #374151 !important;
}

.dark-mode .ai-copilot-title,
.dark-mode .ai-copilot-title span {
    color: #f3f4f6 !important;
}

.dark-mode .ai-copilot-body {
    background-color: #121417 !important;
}

.dark-mode .ai-msg-bubble.bot {
    background-color: #1f2328 !important;
    color: #e5e7eb !important;
    border: 1px solid #374151 !important;
    border-left: 3.5px solid #0d9f4f !important;
}

.dark-mode .ai-msg-bubble.bot strong {
    color: #ffffff !important;
}

.dark-mode .ai-msg-bubble.bot table,
.dark-mode .ai-msg-bubble.bot th,
.dark-mode .ai-msg-bubble.bot td {
    border-color: #374151 !important;
    color: #e5e7eb !important;
}

.dark-mode .ai-msg-bubble.bot thead th {
    background-color: #262b32 !important;
    color: #f3f4f6 !important;
}

.dark-mode .ai-msg-bubble.bot pre,
.dark-mode .ai-msg-bubble.bot code {
    background-color: #262b32 !important;
    color: #34d399 !important;
    border: 1px solid #374151 !important;
}

.dark-mode .ai-msg-bubble.user {
    background-color: #143522 !important;
    color: #a7f3d0 !important;
    border: 1px solid #0d9f4f !important;
    border-right: 3.5px solid #0d9f4f !important;
}

.dark-mode .ai-quick-chips-wrap {
    background-color: #181b1f !important;
    border-top: 1px solid #374151 !important;
}

.dark-mode .ai-chip-btn {
    background-color: #1f2328 !important;
    border: 1px solid #374151 !important;
    color: #d1d5db !important;
}

.dark-mode .ai-chip-btn:hover {
    background-color: #262b32 !important;
    border-color: #0d9f4f !important;
    color: #34d399 !important;
}

.dark-mode .ai-copilot-footer {
    background-color: #181b1f !important;
    border-top: 1px solid #374151 !important;
}

.dark-mode .ai-input-field {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #ffffff !important;
}

.dark-mode .ai-input-field:focus {
    border-color: #0d9f4f !important;
    box-shadow: 0 0 0 2px rgba(13, 159, 79, 0.3) !important;
}

.dark-mode #ai-btn-upload-pdf {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #d1d5db !important;
}

.dark-mode #ai-btn-upload-pdf:hover {
    background-color: #374151 !important;
    color: #ffffff !important;
}

.dark-mode #ai-btn-send {
    background-color: #143522 !important;
    border: 1.5px solid #0d9f4f !important;
    color: #34d399 !important;
}

.dark-mode #ai-btn-send:hover {
    background-color: #0d9f4f !important;
    color: #ffffff !important;
}

.dark-mode .ai-copilot-trigger {
    background-color: #1f2328 !important;
    color: #34d399 !important;
    border: 1.5px solid #0d9f4f !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5) !important;
}

.dark-mode .ai-copilot-trigger:hover {
    background-color: #0d9f4f !important;
    color: #ffffff !important;
}

/* 12. SCROLLBAR MINIMALIS GELAP */
.dark-mode ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.dark-mode ::-webkit-scrollbar-track {
    background: #181b1f;
}
.dark-mode ::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 4px;
}
.dark-mode ::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
}
```

---

## 4. Tips Mengatasi Masalah (Troubleshooting & Best Practices)

1. **Hindari Inline `style="background-color: #ffffff;"`**:
   - Jika ada elemen HTML yang memiliki hardcoded style warna putih di atribut `style`, hapus atribut tersebut atau biarkan CSS yang mengaturnya. Rule CSS `.dark-mode [style*="background-color: #ffffff"]` telah disediakan untuk menetralkannya secara otomatis, namun lebih bersih jika tidak ada hardcoded inline color.
2. **Prioritas Aturan CSS**:
   - Pastikan blok CSS Dark Mode diletakkan di bagian paling bawah (*end of file*) dari `custom.css` agar spesifisitas selektornya selalu memenangkan prioritas terhadap kelas default AdminLTE atau Bootstrap.
3. **Penyimpanan Status Tema**:
   - Status tema disimpan secara persisten di browser pengguna via `localStorage.setItem('theme', 'dark' | 'light')`. Pengguna tidak perlu menekan tombol lagi saat berpindah halaman atau membuka tab baru.
4. **Kalender Native Browser Otomatis Gelap (`color-scheme: dark`)**:
   - Untuk elemen formulir `<input type="date">`, `<input type="month">`, dan `<input type="datetime-local">`, popup kalender bawaan browser (Chrome, Edge, Firefox, Safari) biasanya tetap berwarna putih cerah jika tidak diatur. Properti `color-scheme: dark !important;` wajib disertakan agar dialog kalender bawaan browser otomatis ikut menjadi gelap.
5. **Select2 Multi-Select Tags (Pilihan Ganda)**:
   - Pada form filter dengan opsi banyak, pastikan chip tag `.select2-selection__choice` menggunakan warna lembut dengan outline tipis, serta tombol hapus tag (`.select2-selection__choice__remove`) diberi warna kontras (misal merah muda `#f87171`) agar mudah diidentifikasi pengguna.
6. **Komponen Mengambang (Floating Widget / Chatbot AI)**:
   - Untuk widget pop-up mengambang atau chatbot di pojok layar, hindari penggunaan style inline putih (`style="background: #fff"`). Gunakan pembagian kelas CSS terpisah sehingga gelembung chat (speech bubble) bot dan user otomatis menyesuaikan palet gelap `#1f2328` dan `#143522`.

