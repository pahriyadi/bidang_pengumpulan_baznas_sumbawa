<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/kinerja_db.php';
require_auth();

$activeMenu = 'off_balance';
$pageTitle = 'Input Massal Off Balance Sheet';
$pageDescription = 'Rekapitulasi multi-baris pengumpulan & penyaluran ZIS mandiri oleh UPZ, Masjid, Desa, dan Lembaga';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item"><a href="' . url('pages/off_balance/index.php') . '">Off Balance Sheet</a></li><li class="breadcrumb-item active">Input Massal</li>';

$conn = db();
ensure_kinerja_tables($conn);

$upzList = get_upz_list();
$jenisList = get_jenis_pembayaran_list();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = "CSRF Token tidak valid.";
    } else {
        $items = $_POST['items'] ?? [];
        if (empty($items)) {
            $errors[] = "Tidak ada data Off Balance Sheet yang dikirim.";
        } else {
            $conn->begin_transaction();
            try {
                $insertedCount = 0;
                $user_id = (int)($_SESSION['user']['id'] ?? 0) ?: null;

                foreach ($items as $idx => $item) {
                    $rowNum = $idx + 1;
                    $tgl = trim($item['tanggal_transaksi'] ?? '');
                    $id_upz_raw = trim($item['id_upz'] ?? '');
                    $nama_pelapor = trim($item['nama_pelapor'] ?? '');
                    $id_jenis = (int)($item['id_jenis_pembayaran'] ?? 0);
                    $id_sub = (int)($item['id_sub_jenis_pembayaran'] ?? 0) ?: null;
                    $jumlah_muzaki = max(0, (int)str_replace(['.', ','], '', $item['jumlah_muzaki'] ?? '0'));
                    $jumlah_mustahik = max(0, (int)str_replace(['.', ','], '', $item['jumlah_mustahik'] ?? '0'));
                    $nominal = (float)str_replace(['.', ','], ['', '.'], $item['nominal'] ?? '0');
                    $no_ba = trim($item['no_berita_acara'] ?? '');
                    $ket = trim($item['keterangan'] ?? '');

                    $rowErrors = [];

                    // Resolve UPZ (Auto Create if Tag Name is typed)
                    $id_upz = null;
                    if ($id_upz_raw !== '') {
                        if (is_numeric($id_upz_raw)) {
                            $id_upz = (int)$id_upz_raw;
                        } else {
                            $stmtUpzCheck = $conn->prepare("SELECT id FROM upz WHERE LOWER(nama_upz) = LOWER(?) LIMIT 1");
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
                                    $id_upz = (int)$conn->insert_id;
                                    log_audit('CREATE_UPZ_AUTO', 'upz', $id_upz, "Auto created via batch Off Balance: " . $id_upz_raw);
                                } else {
                                    $rowErrors[] = "Gagal mendaftarkan UPZ baru '$id_upz_raw'.";
                                }
                                $stmtUpzInsert->close();
                            }
                        }
                    }

                    if (!$tgl) $rowErrors[] = "Tanggal wajib diisi.";
                    if (!$id_upz && $nama_pelapor === '') $rowErrors[] = "UPZ atau Nama Lembaga/Pelapor wajib diisi.";
                    if (!$id_jenis) $rowErrors[] = "Jenis pembayaran wajib dipilih.";
                    if ($nominal <= 0) $rowErrors[] = "Nominal harus lebih dari 0.";

                    if (!empty($rowErrors)) {
                        $errors[] = "Baris #$rowNum: " . implode(' ', $rowErrors);
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO transaksi_off_balance
                            (tanggal_transaksi, id_upz, nama_pelapor, id_jenis_pembayaran, id_sub_jenis_pembayaran, jumlah_muzaki, jumlah_mustahik, nominal, no_berita_acara, keterangan, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param('sisiiiidssi', $tgl, $id_upz, $nama_pelapor, $id_jenis, $id_sub, $jumlah_muzaki, $jumlah_mustahik, $nominal, $no_ba, $ket, $user_id);
                        if ($stmt->execute()) {
                            $insertedCount++;
                            log_audit('CREATE_OFF_BALANCE_BATCH', 'transaksi_off_balance', $conn->insert_id, "Batch Off Balance row $rowNum: Rp $nominal");
                        } else {
                            $errors[] = "Baris #$rowNum: " . safe_db_error($conn->error, 'Gagal menyimpan ke database.');
                        }
                        $stmt->close();
                    }
                }

                if (empty($errors)) {
                    $conn->commit();
                    $_SESSION['success_message'] = "Berhasil menyimpan $insertedCount data Off Balance Sheet secara massal!";
                    header("Location: " . url('pages/off_balance/index.php'));
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
                    <strong>Terdeteksi draf inputan Off Balance Sheet yang belum disimpan.</strong> Pulihkan untuk melanjutkan pekerjaan Anda.
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

        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold m-0"><i class="fas fa-layer-group mr-2 text-success"></i>Formulir Input Massal Off Balance Sheet</h3>
                <div class="card-tools d-inline-block text-right ml-auto" id="draftStatus">
                    <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Pintasan: Tab di kolom Keterangan terakhir = Tambah Baris | Ctrl+Enter = Simpan</span>
                </div>
            </div>
            <form action="batch.php" method="POST" id="batchForm">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="batchTable" style="min-width: 1350px; font-size: 0.85rem;">
                            <thead class="bg-gray-light text-center">
                                <tr>
                                    <th width="10%">Tanggal *</th>
                                    <th width="16%">UPZ (Pilih / Ketik Baru)</th>
                                    <th width="14%">Nama Pelapor / Masjid / Desa</th>
                                    <th width="17%">Jenis / Sub-Jenis ZIS *</th>
                                    <th width="10%">No. BA / Laporan</th>
                                    <th width="7%">Muzaki (Jiwa)</th>
                                    <th width="7%">Mustahik (Jiwa)</th>
                                    <th width="13%">Nominal (Rp) *</th>
                                    <th width="13%">Keterangan</th>
                                    <th width="3%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="batch-row" data-index="0">
                                    <td>
                                        <input type="date" class="form-control" name="items[0][tanggal_transaksi]" required value="<?= date('Y-m-d') ?>">
                                    </td>
                                    <td>
                                        <select class="form-control select2-row select2-upz" name="items[0][id_upz]">
                                            <option value="">Pilih / Ketik UPZ</option>
                                            <?php foreach ($upzList as $u): ?>
                                            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nama_upz']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="items[0][nama_pelapor]" placeholder="Masjid / Desa / Pelapor">
                                    </td>
                                    <td>
                                        <select class="form-control select-jenis mb-1" name="items[0][id_jenis_pembayaran]" required>
                                            <option value="">Pilih Jenis</option>
                                            <?php foreach ($jenisList as $j): ?>
                                            <option value="<?= (int)$j['id'] ?>"><?= htmlspecialchars($j['nama_jenis']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select class="form-control select-sub-jenis" name="items[0][id_sub_jenis_pembayaran]">
                                            <option value="">Pilih Sub-Jenis (opsional)</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="items[0][no_berita_acara]" placeholder="No. BA">
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control text-center input-muzaki" name="items[0][jumlah_muzaki]" value="0">
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control text-center input-mustahik" name="items[0][jumlah_mustahik]" value="0">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                            <input type="text" class="form-control currency-input text-right" name="items[0][nominal]" required placeholder="0">
                                        </div>
                                    </td>
                                    <td>
                                        <textarea class="form-control" name="items[0][keterangan]" rows="2" placeholder="Ket. Penyaluran"></textarea>
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
                                    <td colspan="5" class="text-right font-weight-bold" style="font-size: 0.92rem; vertical-align: middle;">TOTAL INPUTAN BATCH OFF BALANCE:</td>
                                    <td class="text-center font-weight-bold" id="totalMuzakiDisplay" style="vertical-align: middle;">0</td>
                                    <td class="text-center font-weight-bold" id="totalMustahikDisplay" style="vertical-align: middle;">0</td>
                                    <td class="text-right font-weight-bold text-success" id="totalInputDisplay" style="font-size: 1.1rem; vertical-align: middle;">Rp 0</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-success btn-sm mt-3" id="btnAddRow">
                        <i class="fas fa-plus mr-1"></i>Tambah Baris Baru
                    </button>
                </div>
                <div class="card-footer text-right" style="border-top: 1px solid #b8b8b8;">
                    <a href="index.php" class="btn btn-outline-secondary px-4 mr-2"><i class="fas fa-times mr-1"></i>Batal</a>
                    <button type="submit" class="btn btn-outline-primary px-4"><i class="fas fa-save mr-1"></i>Simpan Semua Off Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Pure HTML template string to prevent Select2 DOM cloning bugs
const rowTemplate = `
<tr class="batch-row" data-index="INDEX">
    <td>
        <input type="date" class="form-control" name="items[INDEX][tanggal_transaksi]" required value="<?= date('Y-m-d') ?>">
    </td>
    <td>
        <select class="form-control select2-row select2-upz" name="items[INDEX][id_upz]">
            <option value="">Pilih / Ketik UPZ</option>
            <?php foreach ($upzList as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nama_upz']) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <input type="text" class="form-control" name="items[INDEX][nama_pelapor]" placeholder="Masjid / Desa / Pelapor">
    </td>
    <td>
        <select class="form-control select-jenis mb-1" name="items[INDEX][id_jenis_pembayaran]" required>
            <option value="">Pilih Jenis</option>
            <?php foreach ($jenisList as $j): ?>
            <option value="<?= (int)$j['id'] ?>"><?= htmlspecialchars($j['nama_jenis']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control select-sub-jenis" name="items[INDEX][id_sub_jenis_pembayaran]">
            <option value="">Pilih Sub-Jenis (opsional)</option>
        </select>
    </td>
    <td>
        <input type="text" class="form-control" name="items[INDEX][no_berita_acara]" placeholder="No. BA">
    </td>
    <td>
        <input type="number" min="0" class="form-control text-center input-muzaki" name="items[INDEX][jumlah_muzaki]" value="0">
    </td>
    <td>
        <input type="number" min="0" class="form-control text-center input-mustahik" name="items[INDEX][jumlah_mustahik]" value="0">
    </td>
    <td>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
            <input type="text" class="form-control currency-input text-right" name="items[INDEX][nominal]" required placeholder="0">
        </div>
    </td>
    <td>
        <textarea class="form-control" name="items[INDEX][keterangan]" rows="2" placeholder="Ket. Penyaluran"></textarea>
    </td>
    <td class="text-center" style="vertical-align: middle;">
        <button type="button" class="btn btn-outline-danger btn-xs btn-delete-row" title="Hapus baris ini">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
`;

$(function () {
    const DRAFT_KEY = 'baznas_off_balance_batch_draft';
    let rowIndex = 0;
    let draftTimer;

    initRowPlugins($('.batch-row[data-index="0"]'));

    function initRowPlugins(row) {
        row.find('.select2-upz').select2({
            theme: 'bootstrap4',
            placeholder: 'Pilih / Ketik UPZ',
            allowClear: true,
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
        initCurrencyInput(row.find('.currency-input'));
    }

    function calculateTotal() {
        let totalNom = 0;
        let totalMuz = 0;
        let totalMus = 0;
        $('.currency-input').each(function () {
            let val = $(this).val().replace(/[^0-9]/g, '');
            totalNom += (parseInt(val, 10) || 0);
        });
        $('.input-muzaki').each(function () {
            totalMuz += (parseInt($(this).val(), 10) || 0);
        });
        $('.input-mustahik').each(function () {
            totalMus += (parseInt($(this).val(), 10) || 0);
        });
        $('#totalInputDisplay').text('Rp ' + totalNom.toLocaleString('id-ID'));
        $('#totalMuzakiDisplay').text(totalMuz.toLocaleString('id-ID'));
        $('#totalMustahikDisplay').text(totalMus.toLocaleString('id-ID'));
    }

    $(document).on('input', '.currency-input, .input-muzaki, .input-mustahik', calculateTotal);

    $('#btnAddRow').on('click', function () {
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

    $(document).on('click', '.btn-delete-row', function () {
        if ($('.batch-row').length <= 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Harus menyisakan minimal satu baris inputan.'
            });
            return;
        }
        $(this).closest('tr').remove();
        calculateTotal();
        triggerSaveDraft();
    });

    // Tab on last row keterangan adds new row & focuses date
    $(document).on('keydown', '#batchTable tbody tr:last-child textarea[name*="[keterangan]"]', function (e) {
        if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            $('#btnAddRow').click();
            setTimeout(function () {
                $('#batchTable tbody tr:last-child input[type="date"]').focus().select();
            }, 100);
        }
    });

    // Ctrl + Enter submits form
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            $('#batchForm').submit();
        }
    });

    // Cascading sub_jenis
    $(document).on('change', '.select-jenis', function () {
        const row = $(this).closest('tr');
        const jenisId = $(this).val();
        const subSelect = row.find('.select-sub-jenis');

        if (!jenisId) {
            subSelect.html('<option value="">Pilih Sub-Jenis (opsional)</option>');
            return;
        }
        subSelect.html('<option value="">Memuat...</option>');
        $.getJSON('index.php?ajax=sub_jenis&jenis_id=' + jenisId, function (data) {
            let html = '<option value="">Pilih Sub-Jenis (opsional)</option>';
            data.forEach(function (item) {
                html += '<option value="' + item.id + '">' + item.nama_sub_jenis + '</option>';
            });
            subSelect.html(html);
            triggerSaveDraft();
        }).fail(function () {
            subSelect.html('<option value="">Gagal memuat sub-jenis</option>');
        });
    });

    function showDraftStatus(text, type = 'success') {
        if (type === 'success') {
            $('#draftStatus').html('<span class="text-success small font-weight-bold"><i class="fas fa-check-circle mr-1"></i> ' + text + '</span>');
        } else {
            $('#draftStatus').html('<span class="text-muted small"><i class="fas fa-sync-alt fa-spin mr-1"></i> ' + text + '</span>');
        }
    }

    function saveDraft() {
        const data = [];
        $('.batch-row').each(function () {
            const row = $(this);
            const tanggal = row.find('input[type="date"]').val();
            const id_upz = row.find('.select2-upz').val();
            const nama_pelapor = row.find('input[name*="[nama_pelapor]"]').val();
            const id_jenis = row.find('.select-jenis').val();
            const id_sub_jenis = row.find('.select-sub-jenis').val();
            const sub_jenis_options = [];
            row.find('.select-sub-jenis option').each(function () {
                const val = $(this).val();
                if (val) sub_jenis_options.push({ value: val, text: $(this).text() });
            });
            const no_ba = row.find('input[name*="[no_berita_acara]"]').val();
            const jumlah_muzaki = row.find('.input-muzaki').val();
            const jumlah_mustahik = row.find('.input-mustahik').val();
            const nominal = row.find('.currency-input').val();
            const keterangan = row.find('textarea[name*="[keterangan]"]').val();

            if (id_upz || nama_pelapor || id_jenis || no_ba || nominal || keterangan) {
                data.push({
                    tanggal, id_upz, nama_pelapor, id_jenis, id_sub_jenis, sub_jenis_options,
                    no_ba, jumlah_muzaki, jumlah_mustahik, nominal, keterangan
                });
            }
        });

        if (data.length > 0) {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
            showDraftStatus('Draft tersimpan otomatis di browser');
        } else {
            localStorage.removeItem(DRAFT_KEY);
            showDraftStatus('Menunggu inputan...');
        }
    }

    function triggerSaveDraft() {
        showDraftStatus('Menyimpan draf...', 'saving');
        clearTimeout(draftTimer);
        draftTimer = setTimeout(saveDraft, 900);
    }

    $(document).on('input change', '#batchForm input, #batchForm textarea, #batchForm select:not(.select2-hidden-accessible)', function () {
        if ($(this).hasClass('select-sub-jenis') && $(this).html() === '<option value="">Memuat...</option>') return;
        triggerSaveDraft();
    });

    $(document).on('change', '.select2-upz', triggerSaveDraft);

    $('#btnDiscardDraft').on('click', function () {
        localStorage.removeItem(DRAFT_KEY);
        $('#draftAlert').slideUp();
        showDraftStatus('Draf dihapus');
    });

    $('#btnRestoreDraft').on('click', function () {
        const savedDraft = localStorage.getItem(DRAFT_KEY);
        if (!savedDraft) return;
        const data = JSON.parse(savedDraft);
        if (!data || data.length === 0) return;

        $('#batchTable tbody').empty();
        rowIndex = -1;

        data.forEach(function (item) {
            rowIndex++;
            const newRowHtml = rowTemplate.replace(/INDEX/g, rowIndex);
            const newRow = $(newRowHtml);
            $('#batchTable tbody').append(newRow);

            newRow.find('input[type="date"]').val(item.tanggal);
            newRow.find('input[name*="[nama_pelapor]"]').val(item.nama_pelapor || '');
            newRow.find('.select-jenis').val(item.id_jenis);
            if (item.sub_jenis_options && item.sub_jenis_options.length > 0) {
                let html = '<option value="">Pilih Sub-Jenis (opsional)</option>';
                item.sub_jenis_options.forEach(function (opt) {
                    html += '<option value="' + opt.value + '"' + (opt.value == item.id_sub_jenis ? ' selected' : '') + '>' + opt.text + '</option>';
                });
                newRow.find('.select-sub-jenis').html(html);
            }
            newRow.find('input[name*="[no_berita_acara]"]').val(item.no_ba || '');
            newRow.find('.input-muzaki').val(item.jumlah_muzaki || 0);
            newRow.find('.input-mustahik').val(item.jumlah_mustahik || 0);
            newRow.find('.currency-input').val(item.nominal || '');
            newRow.find('textarea[name*="[keterangan]"]').val(item.keterangan || '');

            initRowPlugins(newRow);

            if (item.id_upz && isNaN(item.id_upz)) {
                const newOption = new Option(item.id_upz + ' (UPZ Baru)', item.id_upz, true, true);
                newRow.find('.select2-upz').append(newOption).trigger('change');
            } else {
                newRow.find('.select2-upz').val(item.id_upz).trigger('change.select2');
            }
        });

        calculateTotal();
        $('#draftAlert').slideUp();
        showDraftStatus('Draft berhasil dipulihkan');
    });

    const existingDraft = localStorage.getItem(DRAFT_KEY);
    if (existingDraft) {
        try {
            const parsed = JSON.parse(existingDraft);
            if (parsed && parsed.length > 0) {
                $('#draftAlert').slideDown();
            }
        } catch (e) {
            localStorage.removeItem(DRAFT_KEY);
        }
    }

    $('#batchForm').on('submit', function () {
        localStorage.removeItem(DRAFT_KEY);
    });
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
