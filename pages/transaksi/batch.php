<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'transaksi';
$pageTitle = 'Input Massal Transaksi';
$pageDescription = 'Input beberapa transaksi ZIS sekaligus menggunakan formulir multi-baris';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item"><a href="' . url('pages/transaksi/index.php') . '">Transaksi</a></li><li class="breadcrumb-item active">Input Massal</li>';
$conn = db();

// Dropdowns data
$muzakiList = get_muzaki_list();
$upzList = get_upz_list();
$jenisList = get_jenis_pembayaran_list();
$metodeList = get_metode_pembayaran_list();
$kategoriList = get_kategori_dana_list();
$sumberList = get_sumber_rekening_list();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = "CSRF Token tidak valid.";
    } else {
        $transactions = $_POST['transactions'] ?? [];
        if (empty($transactions)) {
            $errors[] = "Tidak ada data transaksi yang dikirim.";
        } else {
            $conn->begin_transaction();
            try {
                $insertedCount = 0;
                foreach ($transactions as $idx => $t) {
                    $rowNum = $idx + 1;
                    
                    $tgl = $t['tanggal_transaksi'] ?? '';
                    $id_muzaki_raw = trim($t['id_muzaki'] ?? '');
                    $id_upz_raw = trim($t['id_upz'] ?? '');
                    $id_jenis = (int)($t['id_jenis_pembayaran'] ?? 0);
                    $id_sub = (int)($t['id_sub_jenis_pembayaran'] ?? 0) ?: null;
                    $id_metode = (int)($t['id_metode_pembayaran'] ?? 0);
                    $kategori_arr = $t['id_kategori_dana'] ?? [];
                    $id_sumber = (int)($t['id_sumber_rekening'] ?? 0);
                    $jumlahStr = $t['jumlah_pembayaran'] ?? '0';
                    $jumlah = (float)str_replace(['.', ','], ['', '.'], $jumlahStr);
                    $ket = trim($t['keterangan'] ?? '');
                    
                    $rowErrors = [];
                    
                    // --- Resolve Muzaki (Auto Create if Tag Name is typed) ---
                    $id_muzaki = null;
                    if (!empty($id_muzaki_raw)) {
                        if (is_numeric($id_muzaki_raw)) {
                            $id_muzaki = (int)$id_muzaki_raw;
                        } else {
                            $stmtMuzCheck = $conn->prepare("SELECT id FROM muzaki WHERE LOWER(nama_muzaki) = LOWER(?)");
                            $stmtMuzCheck->bind_param('s', $id_muzaki_raw);
                            $stmtMuzCheck->execute();
                            $resCheck = $stmtMuzCheck->get_result()->fetch_assoc();
                            $stmtMuzCheck->close();
                            
                            if ($resCheck) {
                                $id_muzaki = (int)$resCheck['id'];
                            } else {
                                $stmtMuzInsert = $conn->prepare("INSERT INTO muzaki (nama_muzaki, created_at) VALUES (?, NOW())");
                                $stmtMuzInsert->bind_param('s', $id_muzaki_raw);
                                if ($stmtMuzInsert->execute()) {
                                    $id_muzaki = $conn->insert_id;
                                    log_audit('CREATE_MUZAKI_AUTO', 'muzaki', $id_muzaki, "Auto created via batch: " . $id_muzaki_raw);
                                } else {
                                    $rowErrors[] = "Gagal mendaftarkan Muzaki baru '$id_muzaki_raw': " . safe_db_error($conn->error, 'Kesalahan database.');
                                }
                                $stmtMuzInsert->close();
                            }
                        }
                    }
                    
                    // --- Resolve UPZ (Auto Create if Tag Name is typed) ---
                    $id_upz = null;
                    if (!empty($id_upz_raw)) {
                        if (is_numeric($id_upz_raw)) {
                            $id_upz = (int)$id_upz_raw;
                        } else {
                            $stmtUpzCheck = $conn->prepare("SELECT id FROM upz WHERE LOWER(nama_upz) = LOWER(?)");
                            $stmtUpzCheck->bind_param('s', $id_upz_raw);
                            $stmtUpzCheck->execute();
                            $resCheck = $stmtUpzCheck->get_result()->fetch_assoc();
                            $stmtUpzCheck->close();
                            
                            if ($resCheck) {
                                $id_upz = (int)$resCheck['id'];
                            } else {
                                $stmtUpzInsert = $conn->prepare("INSERT INTO upz (nama_upz) VALUES (?)");
                                $stmtUpzInsert->bind_param('s', $id_upz_raw);
                                if ($stmtUpzInsert->execute()) {
                                    $id_upz = $conn->insert_id;
                                    log_audit('CREATE_UPZ_AUTO', 'upz', $id_upz, "Auto created via batch: " . $id_upz_raw);
                                } else {
                                    $rowErrors[] = "Gagal mendaftarkan UPZ baru '$id_upz_raw': " . safe_db_error($conn->error, 'Kesalahan database.');
                                }
                                $stmtUpzInsert->close();
                            }
                        }
                    }
                    
                    // Validation
                    if (!$tgl) $rowErrors[] = "Tanggal wajib diisi.";
                    if (!$id_jenis) $rowErrors[] = "Jenis pembayaran wajib diisi.";
                    if (!$id_metode) $rowErrors[] = "Metode pembayaran wajib diisi.";
                    if (empty($kategori_arr)) $rowErrors[] = "Kategori dana wajib diisi.";
                    if (!$id_sumber) $rowErrors[] = "Sumber rekening wajib diisi.";
                    if ($jumlah <= 0) $rowErrors[] = "Jumlah harus lebih dari 0.";
                    
                    if (!empty($rowErrors)) {
                        $errors[] = "Baris #$rowNum: " . implode(', ', $rowErrors);
                    } else {
                        $id_kategori_json = json_encode(array_map('intval', $kategori_arr));
                        
                        $stmt = $conn->prepare('INSERT INTO transaksi (tanggal_transaksi, id_muzaki, id_upz, id_jenis_pembayaran, id_sub_jenis_pembayaran, id_metode_pembayaran, id_kategori_dana, id_sumber_rekening, jumlah_pembayaran, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->bind_param('siiiiisids', $tgl, $id_muzaki, $id_upz, $id_jenis, $id_sub, $id_metode, $id_kategori_json, $id_sumber, $jumlah, $ket);
                        
                        if ($stmt->execute()) {
                            $insertedCount++;
                            log_audit('CREATE_TRANSAKSI_BATCH', 'transaksi', $conn->insert_id, "Batch insert row $rowNum. Jumlah: $jumlah");
                        } else {
                            $errors[] = "Baris #$rowNum: " . safe_db_error($conn->error, 'Gagal menyimpan transaksi ke database.');
                        }
                        $stmt->close();
                    }
                }
                
                if (empty($errors)) {
                    $conn->commit();
                    $_SESSION['success_message'] = "Berhasil menyimpan $insertedCount data transaksi ZIS secara massal!";
                    header("Location: " . url('pages/transaksi/index.php'));
                    exit;
                } else {
                    $conn->rollback();
                }
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Exception error: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible shadow-none border-danger">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-ban"></i> Gagal Menyimpan!</h5>
            <ul class="mb-0 pl-3">
                <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Draft Recovery Notification Bar -->
        <div id="draftAlert" class="alert alert-warning alert-dismissible shadow-none border-warning py-2 mb-3" style="display: none;">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <span>
                    <i class="fas fa-history mr-2 text-warning"></i>
                    <strong>Terdeteksi draf inputan sebelumnya yang belum disimpan.</strong> Pulihkan untuk melanjutkan pekerjaan Anda.
                </span>
                <div class="mt-2 mt-md-0">
                    <button type="button" class="btn btn-outline-dark btn-xs mr-2 font-weight-bold" id="btnDiscardDraft">
                        <i class="fas fa-trash-alt mr-1"></i>Hapus Draf
                    </button>
                    <button type="button" class="btn btn-dark btn-xs font-weight-bold" id="btnRestoreDraft">
                        <i class="fas fa-check mr-1"></i>Pulihkan Draf
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold m-0"><i class="fas fa-layer-group mr-2 text-success"></i>Formulir Input Massal Transaksi</h3>
                <div class="card-tools d-inline-block text-right ml-auto" id="draftStatus">
                    <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Menunggu inputan...</span>
                </div>
            </div>
            <form action="batch.php" method="POST" id="batchForm">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="batchTable" style="min-width: 1400px; font-size: 0.85rem;">
                            <thead class="bg-gray-light text-center">
                                <tr>
                                    <th width="11%">Tanggal *</th>
                                    <th width="14%">Muzaki</th>
                                    <th width="11%">UPZ</th>
                                    <th width="18%">Jenis / Sub-Jenis *</th>
                                    <th width="9%">Metode *</th>
                                    <th width="12%">Kategori Dana *</th>
                                    <th width="10%">Rekening *</th>
                                    <th width="11%">Jumlah *</th>
                                    <th width="11%">Keterangan</th>
                                    <th width="3%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="batch-row" data-index="0">
                                    <td>
                                        <input type="date" class="form-control" name="transactions[0][tanggal_transaksi]" required value="<?= date('Y-m-d') ?>">
                                    </td>
                                    <td>
                                        <select class="form-control select2-row select2-muzaki" name="transactions[0][id_muzaki]">
                                            <option value="">Pilih Muzaki</option>
                                            <?php foreach ($muzakiList as $m): ?>
                                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_muzaki'] . ($m['no_ktp'] ? ' (' . $m['no_ktp'] . ')' : '')) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control select2-row select2-upz" name="transactions[0][id_upz]">
                                            <option value="">Pilih UPZ</option>
                                            <?php foreach ($upzList as $u): ?>
                                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_upz']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control select-jenis mb-1" name="transactions[0][id_jenis_pembayaran]" required>
                                            <option value="">Pilih Jenis</option>
                                            <?php foreach ($jenisList as $j): ?>
                                            <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nama_jenis']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select class="form-control select-sub-jenis" name="transactions[0][id_sub_jenis_pembayaran]">
                                            <option value="">Pilih Sub-Jenis (opsional)</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" name="transactions[0][id_metode_pembayaran]" required>
                                            <option value="">Metode</option>
                                            <?php foreach ($metodeList as $mt): ?>
                                            <option value="<?= $mt['id'] ?>"><?= htmlspecialchars($mt['nama_metode']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control select2-row select2-kategori" name="transactions[0][id_kategori_dana][]" multiple="multiple" required>
                                            <?php foreach ($kategoriList as $k): ?>
                                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" name="transactions[0][id_sumber_rekening]" required>
                                            <option value="">Sumber</option>
                                            <?php foreach ($sumberList as $sr): ?>
                                            <option value="<?= $sr['id'] ?>"><?= htmlspecialchars($sr['nama_sumber']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                            <input type="text" class="form-control currency-input text-right" name="transactions[0][jumlah_pembayaran]" required placeholder="0">
                                        </div>
                                    </td>
                                    <td>
                                        <textarea class="form-control" name="transactions[0][keterangan]" rows="2" placeholder="Ket"></textarea>
                                    </td>
                                    <td class="text-center" style="vertical-align: middle;">
                                        <button type="button" class="btn btn-outline-danger btn-xs btn-delete-row" title="Hapus baris ini">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-light">
                                <tr>
                                    <td colspan="7" class="text-right font-weight-bold" style="font-size: 0.95rem; vertical-align: middle;">TOTAL INPUTAN BATCH:</td>
                                    <td class="text-right font-weight-bold text-success" id="totalInputDisplay" style="font-size: 1.15rem; vertical-align: middle; background-color: #fcfcfc;">Rp 0</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <button type="button" class="btn btn-outline-success btn-sm mt-3" id="btnAddRow">
                        <i class="fas fa-plus mr-1"></i>Tambah Baris Baru
                    </button>
                </div>
                <div class="card-footer text-right bg-white border-top">
                    <a href="index.php" class="btn btn-secondary px-4 mr-2"><i class="fas fa-times mr-1"></i>Batal</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-1"></i>Simpan Semua Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Row template string to prevent duplicate cloning Select2 bugs
const rowTemplate = `
<tr class="batch-row" data-index="INDEX">
    <td>
        <input type="date" class="form-control" name="transactions[INDEX][tanggal_transaksi]" required value="<?= date('Y-m-d') ?>">
    </td>
    <td>
        <select class="form-control select2-row select2-muzaki" name="transactions[INDEX][id_muzaki]">
            <option value="">Pilih Muzaki</option>
            <?php foreach ($muzakiList as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_muzaki'] . ($m['no_ktp'] ? ' (' . $m['no_ktp'] . ')' : '')) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-control select2-row select2-upz" name="transactions[INDEX][id_upz]">
            <option value="">Pilih UPZ</option>
            <?php foreach ($upzList as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_upz']) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-control select-jenis mb-1" name="transactions[INDEX][id_jenis_pembayaran]" required>
            <option value="">Pilih Jenis</option>
            <?php foreach ($jenisList as $j): ?>
            <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nama_jenis']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control select-sub-jenis" name="transactions[INDEX][id_sub_jenis_pembayaran]">
            <option value="">Pilih Sub-Jenis (opsional)</option>
        </select>
    </td>
    <td>
        <select class="form-control" name="transactions[INDEX][id_metode_pembayaran]" required>
            <option value="">Metode</option>
            <?php foreach ($metodeList as $mt): ?>
            <option value="<?= $mt['id'] ?>"><?= htmlspecialchars($mt['nama_metode']) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-control select2-row select2-kategori" name="transactions[INDEX][id_kategori_dana][]" multiple="multiple" required>
            <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-control" name="transactions[INDEX][id_sumber_rekening]" required>
            <option value="">Sumber</option>
            <?php foreach ($sumberList as $sr): ?>
            <option value="<?= $sr['id'] ?>"><?= htmlspecialchars($sr['nama_sumber']) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
            <input type="text" class="form-control currency-input text-right" name="transactions[INDEX][jumlah_pembayaran]" required placeholder="0">
        </div>
    </td>
    <td>
        <textarea class="form-control" name="transactions[INDEX][keterangan]" rows="2" placeholder="Ket"></textarea>
    </td>
    <td class="text-center" style="vertical-align: middle;">
        <button type="button" class="btn btn-outline-danger btn-xs btn-delete-row" title="Hapus baris ini">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
`;

$(function() {
    let rowIndex = 0;
    let draftTimer;
    
    // Bind plugins on the initial first row
    initRowPlugins($('.batch-row[data-index="0"]'));

    function initRowPlugins(row) {
        // Tag-based initialization for Muzaki (allows quick adding of new names)
        row.find('.select2-muzaki').select2({
            theme: 'bootstrap4',
            placeholder: 'Cari Muzaki',
            tags: true,
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') return null;
                return {
                    id: term,
                    text: term + ' (Muzaki Baru)',
                    newTag: true
                };
            }
        });
        
        // Tag-based initialization for UPZ (allows quick adding of new UPZ)
        row.find('.select2-upz').select2({
            theme: 'bootstrap4',
            placeholder: 'Cari UPZ',
            tags: true,
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') return null;
                return {
                    id: term,
                    text: term + ' (UPZ Baru)',
                    newTag: true
                };
            }
        });
        
        initSelect2(row.find('.select2-kategori'), 'Pilih Kategori');
        initCurrencyInput(row.find('.currency-input'));
    }

    // Live Total Counter
    function calculateTotal() {
        let total = 0;
        $('.currency-input').each(function() {
            let val = $(this).val().replace(/[^0-9]/g, '');
            let num = parseInt(val) || 0;
            total += num;
        });
        $('#totalInputDisplay').text('Rp ' + total.toLocaleString('id-ID'));
    }

    $(document).on('input', '.currency-input', calculateTotal);

    // Add Row
    $('#btnAddRow').on('click', function() {
        // Auto-copy date from previous row
        const lastRowDate = $('#batchTable tbody tr:last-child input[type="date"]').val();
        
        rowIndex++;
        const newRowHtml = rowTemplate.replace(/INDEX/g, rowIndex);
        const newRow = $(newRowHtml);
        
        if (lastRowDate) {
            newRow.find('input[type="date"]').val(lastRowDate);
        }
        
        $('#batchTable tbody').append(newRow);
        initRowPlugins(newRow);
        
        triggerSaveDraft();
    });

    // Delete Row
    $(document).on('click', '.btn-delete-row', function() {
        if ($('.batch-row').length <= 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Harus menyisakan minimal satu baris transaksi.'
            });
            return;
        }
        $(this).closest('tr').remove();
        calculateTotal();
        triggerSaveDraft();
    });

    // Keyboard Shortcuts: Tab on Keterangan of last row appends new row
    $(document).on('keydown', '#batchTable tbody tr:last-child textarea[name*="[keterangan]"]', function(e) {
        if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            $('#btnAddRow').click();
            setTimeout(function() {
                $('#batchTable tbody tr:last-child input[type="date"]').focus().select();
            }, 100);
        }
    });

    // Keyboard Shortcuts: Ctrl + Enter anywhere saves form
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            $('#batchForm').submit();
        }
    });

    // Cascading Dropdown sub_jenis
    $(document).on('change', '.select-jenis', function() {
        const row = $(this).closest('tr');
        const jenisId = $(this).val();
        const subSelect = row.find('.select-sub-jenis');
        
        if (!jenisId) {
            subSelect.html('<option value="">Pilih Sub-Jenis (opsional)</option>');
            return;
        }
        
        subSelect.html('<option value="">Memuat...</option>');
        
        $.getJSON('index.php?ajax=sub_jenis&jenis_id=' + jenisId, function(data) {
            let html = '<option value="">Pilih Sub-Jenis (opsional)</option>';
            data.forEach(function(item) {
                html += '<option value="' + item.id + '">' + item.nama_sub_jenis + '</option>';
            });
            subSelect.html(html);
            const pendingSubVal = subSelect.data('pending-val');
            if (pendingSubVal) {
                subSelect.val(pendingSubVal).removeData('pending-val');
            }
            triggerSaveDraft();
        }).fail(function() {
            subSelect.html('<option value="">Gagal memuat sub-jenis</option>');
        });
    });

    $(document).on('change', '.select-sub-jenis', function() {
        triggerSaveDraft();
    });

    // --- localDraft (Auto-save) Implementation ---
    function showDraftStatus(text, type = 'success') {
        if (type === 'success') {
            $('#draftStatus').html('<span class="text-success small font-weight-bold"><i class="fas fa-check-circle mr-1"></i> ' + text + '</span>');
        } else {
            $('#draftStatus').html('<span class="text-muted small"><i class="fas fa-sync-alt fa-spin mr-1"></i> ' + text + '</span>');
        }
    }

    function saveDraft() {
        const data = [];
        $('.batch-row').each(function() {
            const row = $(this);
            const idx = row.attr('data-index');
            
            const tanggal = row.find('input[type="date"]').val();
            const id_muzaki = row.find('.select2-muzaki').val();
            const id_upz = row.find('.select2-upz').val();
            const id_jenis = row.find('.select-jenis').val();
            const id_sub_jenis = row.find('.select-sub-jenis').val();
            
            const sub_jenis_options = [];
            row.find('.select-sub-jenis option').each(function() {
                const val = $(this).val();
                if (val) {
                    sub_jenis_options.push({ value: val, text: $(this).text() });
                }
            });
            
            const id_metode = row.find('select[name*="id_metode_pembayaran"]').val();
            const id_kategori = row.find('.select2-kategori').val() || [];
            const id_sumber = row.find('select[name*="id_sumber_rekening"]').val();
            const jumlah = row.find('.currency-input').val();
            const keterangan = row.find('textarea[name*="keterangan"]').val();
            
            if (tanggal || id_muzaki || id_upz || id_jenis || id_metode || id_sumber || jumlah || keterangan) {
                data.push({
                    tanggal, id_muzaki, id_upz, id_jenis, id_sub_jenis, sub_jenis_options,
                    id_metode, id_kategori, id_sumber, jumlah, keterangan
                });
            }
        });
        
        if (data.length > 0) {
            localStorage.setItem('baznas_batch_draft', JSON.stringify(data));
            showDraftStatus('Draft tersimpan otomatis di browser');
        } else {
            localStorage.removeItem('baznas_batch_draft');
            showDraftStatus('<i class="fas fa-info-circle mr-1"></i>Menunggu inputan...');
        }
    }

    function triggerSaveDraft() {
        showDraftStatus('Menyimpan draf...', 'saving');
        clearTimeout(draftTimer);
        draftTimer = setTimeout(saveDraft, 1000);
    }

    $(document).on('input change', '#batchForm input, #batchForm textarea, #batchForm select:not(.select2-hidden-accessible)', function(e) {
        if ($(this).hasClass('select-sub-jenis') && $(this).html() === '<option value="">Memuat...</option>') return;
        triggerSaveDraft();
    });

    $(document).on('change', '.select2-muzaki, .select2-upz, .select2-kategori', function() {
        triggerSaveDraft();
    });

    // Discard Draft
    $('#btnDiscardDraft').on('click', function() {
        Swal.fire({
            title: 'Hapus Draf?',
            text: "Draf yang belum disimpan akan dibersihkan dari penyimpanan browser.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                localStorage.removeItem('baznas_batch_draft');
                $('#draftAlert').slideUp();
                showDraftStatus('<i class="fas fa-info-circle mr-1"></i>Menunggu inputan...');
            }
        });
    });

    // Restore Draft
    $('#btnRestoreDraft').on('click', function() {
        const savedDraft = localStorage.getItem('baznas_batch_draft');
        if (!savedDraft) return;
        
        const data = JSON.parse(savedDraft);
        if (!data || data.length === 0) return;
        
        $('#batchTable tbody').empty();
        rowIndex = -1;
        
        data.forEach(function(item) {
            rowIndex++;
            const newRowHtml = rowTemplate.replace(/INDEX/g, rowIndex);
            const newRow = $(newRowHtml);
            $('#batchTable tbody').append(newRow);
            
            // Fill values
            newRow.find('input[type="date"]').val(item.tanggal);
            newRow.find('.select-jenis').val(item.id_jenis);
            
            if (item.sub_jenis_options && item.sub_jenis_options.length > 0) {
                let html = '<option value="">Pilih Sub-Jenis (opsional)</option>';
                item.sub_jenis_options.forEach(function(opt) {
                    html += '<option value="' + opt.value + '"' + (opt.value == item.id_sub_jenis ? ' selected' : '') + '>' + opt.text + '</option>';
                });
                newRow.find('.select-sub-jenis').html(html);
            }
            
            newRow.find('select[name*="id_metode_pembayaran"]').val(item.id_metode);
            newRow.find('select[name*="id_sumber_rekening"]').val(item.id_sumber);
            newRow.find('.currency-input').val(item.jumlah);
            newRow.find('textarea[name*="keterangan"]').val(item.keterangan);
            
            // Initialize row plugins
            initRowPlugins(newRow);
            
            // Re-initialize select2 tags for newly created Muzaki
            if (item.id_muzaki && isNaN(item.id_muzaki)) {
                const newOption = new Option(item.id_muzaki + ' (Muzaki Baru)', item.id_muzaki, true, true);
                newRow.find('.select2-muzaki').append(newOption).trigger('change');
            } else {
                newRow.find('.select2-muzaki').val(item.id_muzaki).trigger('change.select2');
            }

            // Re-initialize select2 tags for newly created UPZ
            if (item.id_upz && isNaN(item.id_upz)) {
                const newOption = new Option(item.id_upz + ' (UPZ Baru)', item.id_upz, true, true);
                newRow.find('.select2-upz').append(newOption).trigger('change');
            } else {
                newRow.find('.select2-upz').val(item.id_upz).trigger('change.select2');
            }

            newRow.find('.select2-kategori').val(item.id_kategori).trigger('change.select2');
        });
        
        calculateTotal();
        $('#draftAlert').slideUp();
        showDraftStatus('Draft tersimpan otomatis di browser');
        
        Swal.fire({
            icon: 'success',
            title: 'Draf Dipulihkan!',
            text: 'Data inputan transaksi sebelumnya berhasil dimuat kembali.',
            timer: 2000,
            showConfirmButton: false
        });
    });

    const existingDraft = localStorage.getItem('baznas_batch_draft');
    if (existingDraft) {
        try {
            const data = JSON.parse(existingDraft);
            if (data && data.length > 0) {
                $('#draftAlert').slideDown();
            }
        } catch (e) {
            localStorage.removeItem('baznas_batch_draft');
        }
    }

    $('#batchForm').on('submit', function() {
        localStorage.removeItem('baznas_batch_draft');
    });
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
