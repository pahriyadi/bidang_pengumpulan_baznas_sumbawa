            </div>
        </section>
    </div>
    <?php include __DIR__ . '/footer_content.php'; ?>
</div>
<script src="<?= asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-colreorder/js/dataTables.colReorder.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-colreorder/js/colReorder.bootstrap4.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/jszip/jszip.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/pdfmake/pdfmake.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/pdfmake/vfs_fonts.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-buttons/js/buttons.html5.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-buttons/js/buttons.print.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/datatables-buttons/js/buttons.colVis.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/select2/js/select2.full.min.js') ?>"></script>
<script src="<?= asset('adminlte/dist/js/adminlte.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>

<!-- Modal Tambah Catatan -->
<div class="modal fade" id="addNoteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border elevation-0" style="border-radius: 4px; border: 1px solid #b8b8b8 !important; box-shadow: none !important;">
            <div class="modal-header bg-white" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-plus mr-2 text-success"></i>Tambah Catatan Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addNoteForm" class="no-preloader">
                <input type="hidden" name="type" value="note">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Judul Catatan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" placeholder="Misal: Catat no telp Ketua UPZ Masjid Jami'" required style="border: 1px solid #b8b8b8 !important;">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Catatan / Keterangan</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Detail catatan..." style="border: 1px solid #b8b8b8 !important;"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-top: 1px solid #b8b8b8;">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengingat -->
<div class="modal fade" id="addReminderModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border elevation-0" style="border-radius: 4px; border: 1px solid #b8b8b8 !important; box-shadow: none !important;">
            <div class="modal-header bg-white" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-plus mr-2 text-warning"></i>Tambah Pengingat Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addReminderForm" class="no-preloader">
                <input type="hidden" name="type" value="reminder">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Judul Pengingat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" placeholder="Misal: Selesaikan setoran UPZ Masjid Agung" required style="border: 1px solid #b8b8b8 !important;">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Catatan / Keterangan</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Detail tugas atau transaksi..." style="border: 1px solid #b8b8b8 !important;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Tanggal Jatuh Tempo</label>
                        <input type="date" class="form-control" name="due_date" value="<?= date('Y-m-d') ?>" style="border: 1px solid #b8b8b8 !important;">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-top: 1px solid #b8b8b8;">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" style="color: #333; background-color: #ffc107; border-color: #ffc107;"><i class="fas fa-save mr-1"></i>Simpan Pengingat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddNoteModal() {
    $('#addNoteModal').modal('show');
}

function showAddReminderModal() {
    $('#addReminderModal').modal('show');
}

$('#addNoteForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize() + '&action=create_reminder';
    $.ajax({
        url: '',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#addNoteModal').modal('hide');
                Swal.fire({
                    title: 'Sukses',
                    text: res.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }
    });
});

$('#addReminderForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize() + '&action=create_reminder';
    $.ajax({
        url: '',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#addReminderModal').modal('hide');
                Swal.fire({
                    title: 'Sukses',
                    text: res.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }
    });
});

function toggleReminder(id, isChecked) {
    var is_completed = isChecked ? 1 : 0;
    $.ajax({
        url: '',
        method: 'POST',
        data: {
            action: 'toggle_reminder',
            id: id,
            is_completed: is_completed
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }
    });
}

function deleteReminder(id) {
    Swal.fire({
        title: 'Hapus Item?',
        text: 'Catatan/pengingat ini akan dihapus secara permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    action: 'delete_reminder',
                    id: id
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            title: 'Terhapus',
                            text: res.message,
                            icon: 'success',
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            });
        }
    });
}

// ==========================================
// DARK / LIGHT MODE CONTROLLER (BAZNAS SIM)
// ==========================================
function toggleDarkMode() {
    var isDark = document.body.classList.toggle('dark-mode');
    document.documentElement.classList.toggle('dark-mode', isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeUI(isDark);
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { isDark: isDark } }));
}

function updateThemeUI(isDark) {
    var icon = document.getElementById('theme-toggle-icon');
    if (icon) {
        if (isDark) {
            icon.className = 'fas fa-sun text-warning';
        } else {
            icon.className = 'fas fa-moon';
        }
    }
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
    var sidebar = document.querySelector('.main-sidebar');
    if (sidebar) {
        if (isDark) {
            sidebar.classList.remove('sidebar-light-success');
            sidebar.classList.add('sidebar-dark-success');
        } else {
            sidebar.classList.remove('sidebar-dark-success');
            sidebar.classList.add('sidebar-light-success');
        }
    }
}

// Initial UI synchronization on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
    if (isDark && !document.body.classList.contains('dark-mode')) {
        document.body.classList.add('dark-mode');
    }
    updateThemeUI(isDark);
});
</script>
<?php include __DIR__ . '/widget_ai_copilot.php'; ?>
</body>
</html>
<?php
if (function_exists('end_page_cache')) {
    end_page_cache();
}
?>
