<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'rekening_koran';
$pageTitle = 'Rekening Koran';
$pageDescription = 'Manajemen statement bank dan rekonsiliasi data';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Rekening Koran</li>';
$conn = db();

// --- AJAX HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        $id_sumber = (int)($_POST['id_sumber_rekening'] ?? 0);
        $bulan = $_POST['bulan'] ?? ''; // Format: YYYY-MM
        $raw_kredit = $_POST['jumlah_kredit'] ?? '0';
        $clean_kredit = preg_replace('/[^0-9,]/', '', str_replace('Rp', '', $raw_kredit));
        $jumlah_kredit = (float)str_replace(',', '.', $clean_kredit);
        
        if (!$id_sumber || !$bulan) {
            echo json_encode(['success' => false, 'message' => 'Lengkapi data dengan benar.']);
            exit;
        }

        // Handle File Upload
        $file_path = $_POST['existing_file'] ?? '';
        $original_name = $_POST['existing_name'] ?? '';
        
        if (isset($_FILES['file_rk']) && $_FILES['file_rk']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../assets/uploads/rekening_koran/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['file_rk']['name'], PATHINFO_EXTENSION);
            $new_filename = 'RK_' . $id_sumber . '_' . str_replace('-', '', $bulan) . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['file_rk']['tmp_name'], $target_file)) {
                $file_path = 'assets/uploads/rekening_koran/' . $new_filename;
                $original_name = $_FILES['file_rk']['name'];
            }
        }

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO rekening_koran (id_sumber_rekening, bulan, jumlah_kredit, file_path, nama_file_original) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('isdss', $id_sumber, $bulan, $jumlah_kredit, $file_path, $original_name);
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE rekening_koran SET id_sumber_rekening=?, bulan=?, jumlah_kredit=?, file_path=?, nama_file_original=? WHERE id=?");
            $stmt->bind_param('isdssi', $id_sumber, $bulan, $jumlah_kredit, $file_path, $original_name, $id);
        }

        if ($stmt->execute()) {
            $inserted_id = $action === 'create' ? $conn->insert_id : $id;
            log_audit(strtoupper($action) . '_REKENING_KORAN', 'rekening_koran', $inserted_id, "Bulan: $bulan, Kredit: $jumlah_kredit");
            echo json_encode(['success' => true, 'message' => 'Data Berhasil Disimpan']);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal menyimpan data rekening koran.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM rekening_koran WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_REKENING_KORAN', 'rekening_koran', $id, "Deleted entry ID: $id");
            echo json_encode(['success' => true, 'message' => 'Data Dihapus']);
        } else {
            echo json_encode(['success' => false]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'reconcile') {
        $id_sumber = (int)$_POST['id_sumber_rekening'];
        $bulan = $_POST['bulan'];
        
        // Sum System Transactions
        $sql = "SELECT SUM(jumlah_pembayaran) as total_sistem FROM transaksi WHERE id_sumber_rekening = ? AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $id_sumber, $bulan);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $total_sistem = (float)($res['total_sistem'] ?? 0);
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'total_sistem' => $total_sistem,
            'formatted_sistem' => format_currency($total_sistem)
        ]);
        exit;
    }

    if ($action === 'parse_pdf') {
        require_once __DIR__ . '/../../config/pdf_parser.php';
        if (isset($_FILES['file_rk']) && $_FILES['file_rk']['error'] === UPLOAD_ERR_OK) {
            $analysis = analyze_bank_statement_pdf($_FILES['file_rk']['tmp_name'], $_FILES['file_rk']['name']);
            echo json_encode($analysis);
        } else {
            echo json_encode(['success' => false, 'error' => 'File tidak berhasil terunggah']);
        }
        exit;
    }
}

// Fetch data
$sql = "SELECT rk.*, sr.nama_sumber FROM rekening_koran rk LEFT JOIN sumber_rekening sr ON rk.id_sumber_rekening = sr.id ORDER BY rk.bulan DESC";
$result = $conn->query($sql);

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-university mr-2"></i>Daftar Rekening Koran</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" onclick="showAddModal()">
                        <i class="fas fa-plus mr-1"></i>Tambah Statement
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Sumber Bank</th>
                                <th>Periode Bulan</th>
                                <th class="text-right">Kredit (Bank)</th>
                                <th class="text-center">Lampiran</th>
                                <th class="text-center" width="15%">Aksi / Rekon</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_sumber']) ?></td>
                                    <td><?= date('F Y', strtotime($row['bulan'] . '-01')) ?></td>
                                    <td class="text-right"><?= format_currency($row['jumlah_kredit']) ?></td>
                                    <td class="text-center">
                                        <?php if($row['file_path']): ?>
                                            <a href="<?= base_url($row['file_path']) ?>" target="_blank" class="btn btn-xs btn-outline-info">
                                                <i class="fas fa-file-download mr-1"></i>Lihat File
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Tdk ada file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-success" onclick="checkReconciliation(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fas fa-sync-alt mr-1"></i>Rekon
                                        </button>
                                        <button class="btn btn-xs btn-warning" onclick="editData(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-xs btn-danger" onclick="deleteData(<?= $row['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="rkForm" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="modalTitle">Tambah Rekening Koran</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="formId">
                    <input type="hidden" name="existing_file" id="formExistingFile">
                    <input type="hidden" name="existing_name" id="formExistingName">

                    <!-- Upload File dengan Auto-Parse 0 Token -->
                    <div class="form-group p-2 rounded mb-3" style="background: #f8faf9; border: 1px dashed #0d9f4f;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0 text-success font-weight-bold" style="font-size: 0.88rem;">
                                <i class="fas fa-file-pdf mr-1"></i> File Statement Rekening Koran (PDF)
                            </label>
                            <span id="pdfParseSpinner" style="display: none;" class="text-muted small">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Membaca PDF...
                            </span>
                        </div>
                        <input type="file" name="file_rk" id="input_file_rk" class="form-control-file" accept="application/pdf" onchange="autoDetectPdfStatement(this)">
                        <small class="text-muted d-block mt-1" id="fileInfo">
                            Pilih file PDF statement bank untuk mengisi Bank, Periode Bulan, & Mutasi Kredit secara otomatis (0 Token).
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Sumber / Bank <span class="text-danger">*</span></label>
                        <select name="id_sumber_rekening" id="id_sumber_rekening" class="form-control select2" style="width: 100%;" required>
                            <option value="">-- Pilih Bank --</option>
                            <?php foreach(get_sumber_rekening_list() as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_sumber']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Periode Bulan <span class="text-danger">*</span></label>
                        <input type="month" name="bulan" id="bulan" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Jumlah Kredit (Dana Masuk) <span class="text-danger">*</span></label>
                        <input type="text" name="jumlah_kredit" id="jumlah_kredit" class="form-control currency-input" placeholder="Rp 0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Rekon -->
<div class="modal fade" id="modalRekon" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold">Hasil Rekonsiliasi</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="rekonResult">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <td width="50%"><strong>Bank / Sumber</strong></td>
                            <td id="rekonBank"></td>
                        </tr>
                        <tr>
                            <td><strong>Periode</strong></td>
                            <td id="rekonBulan"></td>
                        </tr>
                        <tr class="table-info">
                            <td><strong>Total Sistem</strong></td>
                            <td id="rekonSistem" class="text-right"></td>
                        </tr>
                        <tr class="table-warning">
                            <td><strong>Kredit Rekening Koran</strong></td>
                            <td id="rekonKredit" class="text-right"></td>
                        </tr>
                        <tr id="diffRow">
                            <td><strong>Selisih</strong></td>
                            <td id="rekonDiff" class="text-right font-weight-bold"></td>
                        </tr>
                    </table>
                    <div id="rekonStatus" class="mt-3 text-center p-2 rounded">
                        
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $("#dataTable").DataTable({
        "responsive": true,
        "autoWidth": false,
        "colReorder": true
    });
    $(".select2").select2({ theme: "bootstrap4", dropdownParent: $("#modalForm") });
    
    $("#jumlah_kredit").on("keyup", function() {
        let val = $(this).val().replace(/[^\d]/g, "");
        if (val === "") val = "0";
        $(this).val(new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val));
    });
});

function showAddModal() {
    $("#modalTitle").text("Tambah Rekening Koran");
    $("#formAction").val("create");
    $("#rkForm")[0].reset();
    $("#id_sumber_rekening").val("").trigger("change");
    $("#fileInfo").text("Pilih file PDF statement bank untuk mengisi Bank, Periode Bulan, & Mutasi Kredit secara otomatis (0 Token).");
    $("#modalForm").modal("show");
}

function autoDetectPdfStatement(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    if (!file.name.toLowerCase().endsWith('.pdf')) {
        Swal.fire('Format Salah', 'Mohon pilih dokumen file PDF statement bank.', 'warning');
        return;
    }

    $("#pdfParseSpinner").show();
    let fd = new FormData();
    fd.append('action', 'parse_pdf');
    fd.append('file_rk', file);

    $.ajax({
        url: "",
        method: "POST",
        data: fd,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(res) {
            $("#pdfParseSpinner").hide();
            if (res.success) {
                if (res.id_sumber_rekening > 0) {
                    $("#id_sumber_rekening").val(res.id_sumber_rekening).trigger("change");
                }
                if (res.bulan) {
                    $("#bulan").val(res.bulan);
                }
                if (res.mutasi_kredit !== null) {
                    $("#jumlah_kredit").val(new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(res.mutasi_kredit));
                }
                $("#fileInfo").html('<span class="text-success font-weight-bold"><i class="fas fa-check mr-1"></i> Terdeteksi: ' + (res.bank || 'Bank') + ' (' + (res.bulan_nama || res.bulan) + ') - Mutasi: ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(res.mutasi_kredit || 0) + '</span>');
                Swal.fire({
                    icon: 'success',
                    title: 'Auto-Deteksi Berhasil (0 Token)',
                    html: `Bank: <strong>${res.bank || '-'}</strong><br>Periode: <strong>${res.bulan_nama || res.bulan}</strong><br>Mutasi Kredit: <strong>Rp ${new Intl.NumberFormat('id-ID').format(res.mutasi_kredit || 0)}</strong>`,
                    timer: 3000,
                    showConfirmButton: false
                });
            } else if (res.is_scanned) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Scan Gambar Terdeteksi',
                    text: 'File PDF ini berupa scan gambar/foto tanpa teks digital. Silakan gunakan PDF digital resmi dari internet banking/CMS bank, atau isi data formulir secara manual.'
                });
            } else {
                Swal.fire('Gagal Membaca', res.error || 'Dokumen tidak dapat dipindai otomatis.', 'info');
            }
        },
        error: function() {
            $("#pdfParseSpinner").hide();
            Swal.fire('Error', 'Terjadi kendala saat membaca file PDF.', 'error');
        }
    });
}

function editData(row) {
    $("#modalTitle").text("Edit Rekening Koran");
    $("#formAction").val("update");
    $("#formId").val(row.id);
    $("#id_sumber_rekening").val(row.id_sumber_rekening).trigger("change");
    $("#bulan").val(row.bulan);
    $("#jumlah_kredit").val(row.jumlah_kredit).trigger("keyup");
    $("#formExistingFile").val(row.file_path);
    $("#formExistingName").val(row.nama_file_original);
    $("#fileInfo").text(row.nama_file_original ? "File aktif: " + row.nama_file_original : "");
    $("#modalForm").modal("show");
}

$("#rkForm").on("submit", function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    $("#btnSubmit").attr("disabled", true).text("Menyimpan...");
    
    $.ajax({
        url: "",
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(res) {
            if (res.success) {
                Swal.fire("Berhasil", res.message, "success").then(() => location.reload());
            } else {
                Swal.fire("Gagal", res.message, "error");
                $("#btnSubmit").attr("disabled", false).text("Simpan Data");
            }
        },
        error: function(xhr) {
            let msg = "Terjadi kesalahan pada server.";
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            Swal.fire("Error System", msg, "error");
            $("#btnSubmit").attr("disabled", false).text("Simpan Data");
        }
    });
});

function deleteData(id) {
    Swal.fire({
        title: "Hapus data?",
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Ya, Hapus!"
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("", { action: "delete", id: id }, function(res) {
                if(res.success) location.reload();
            });
        }
    });
}

function checkReconciliation(row) {
    $.post("", { action: "reconcile", id_sumber_rekening: row.id_sumber_rekening, bulan: row.bulan }, function(res) {
        if (res.success) {
            $("#rekonBank").text(row.nama_sumber);
            $("#rekonBulan").text(row.bulan);
            $("#rekonSistem").text(res.formatted_sistem);
            $("#rekonKredit").text(new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(row.jumlah_kredit));
            
            let diff = res.total_sistem - parseFloat(row.jumlah_kredit);
            $("#rekonDiff").text(new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(diff));
            
            let status = $("#rekonStatus");
            if (Math.abs(diff) < 1) {
                status.html("<i class='fas fa-check-circle mr-1'></i> DATA COCOK (Balanced)").removeClass("bg-danger").addClass("bg-success text-white");
                $("#diffRow").removeClass("table-danger");
            } else {
                status.html("<i class='fas fa-exclamation-triangle mr-1'></i> ADA SELISIH (Imbalance)").removeClass("bg-success").addClass("bg-danger text-white");
                $("#diffRow").addClass("table-danger");
            }
            
            $("#modalRekon").modal("show");
        }
    }, "json");
}
</script>

<?php
include __DIR__ . '/../../inc/footer.php';
?>
