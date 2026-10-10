<?php
/**
 * Bank Statement PDF Parser & Smart Reconciler
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 * 
 * Menggunakan library open-source pure-PHP (Smalot\PdfParser)
 * Mengekstrak mutasi kredit, saldo, periode, dan nomor rekening tanpa biaya token API (0 Token).
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Ekstraksi teks mentah dari berkas PDF
 * 
 * @param string $filePath Path absolut ke berkas PDF
 * @return array ['success' => bool, 'text' => string, 'page_count' => int, 'error' => string|null]
 */
function pdf_extract_text($filePath) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'text' => '', 'page_count' => 0, 'error' => 'Berkas PDF tidak ditemukan.'];
    }

    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        $pages = count($pdf->getPages());

        return [
            'success' => true,
            'text' => $text,
            'page_count' => $pages,
            'error' => null
        ];
    } catch (\Throwable $e) {
        return [
            'success' => false,
            'text' => '',
            'page_count' => 0,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Analisis mendalam rekening koran bank dari teks PDF
 * Mendukung format: Bank NTB Syariah, Bank Mandiri, BRI, BNI, BSI, dll.
 * 
 * @param string $filePath Path absolut ke berkas PDF
 * @param string $originalFilename Nama asli berkas yang diunggah
 * @return array Data terstruktur hasil ekstraksi & rekonsiliasi database
 */
function analyze_bank_statement_pdf($filePath, $originalFilename = '') {
    $extraction = pdf_extract_text($filePath);

    if (!$extraction['success']) {
        return [
            'success' => false,
            'error' => 'Gagal membaca berkas PDF: ' . $extraction['error'],
            'is_scanned' => false,
            'html_card' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal membaca berkas PDF: ' . htmlspecialchars($extraction['error']) . '</div>'
        ];
    }

    $text = $extraction['text'];
    $textLength = strlen(trim($text));

    // Deteksi apakah berkas PDF berupa hasil scan/foto (tanpa layer teks digital)
    if ($textLength < 30) {
        $htmlScanned = '
        <div class="card mb-2" style="border: 1.5px solid #e0a800; border-radius: 6px; box-shadow: none !important; background: #fffdf5;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge mr-2" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; font-size:0.82rem;">
                        <i class="fas fa-camera mr-1"></i> Scan Gambar Terdeteksi
                    </span>
                    <strong style="color: #856404;">Dokumen Memerlukan OCR</strong>
                </div>
                <p class="text-muted small mb-2">
                    File PDF <code>' . htmlspecialchars($originalFilename ?: basename($filePath)) . '</code> ini berupa <strong>scan gambar/foto</strong> tanpa lapisan teks digital (teks tidak dapat diekstrak langsung).
                </p>
                <div class="p-2 bg-white rounded border" style="font-size:0.8rem; border-color:#ffeeba !important;">
                    <i class="fas fa-lightbulb text-warning mr-1"></i> <strong>Saran Solusi Efisien:</strong><br>
                    Unggah file PDF asli hasil unduh langsung (ekspor digital) dari layanan <em>Internet Banking / CMS Corp Bank</em> (misal: CMS Bank NTB Syariah, Livin Mandiri, BRImo, atau Q-Ris portal) agar seluruh data mutasi kredit dapat dipindai 100% instan & 0 Token.
                </div>
            </div>
        </div>';

        return [
            'success' => false,
            'is_scanned' => true,
            'text_length' => $textLength,
            'error' => 'Dokumen PDF berupa hasil scan gambar tanpa teks digital.',
            'html_card' => $htmlScanned
        ];
    }

    // Ekstraksi atribut metadata perbankan
    $bank = null;
    $nomorRekening = null;
    $namaPemilik = null;
    $periodeAwal = null;
    $periodeAkhir = null;
    $bulan = null; // Format YYYY-MM
    $saldoAwal = null;
    $mutasiDebet = null;
    $mutasiKredit = null;
    $saldoAkhir = null;

    // 1. Deteksi Bank
    if (preg_match('/BANK\s+NTB\s+SYARIAH/i', $text)) {
        $bank = 'Bank NTB Syariah';
    } elseif (preg_match('/BANK\s+MANDIRI|MANDIRI/i', $text)) {
        $bank = 'Bank Mandiri';
    } elseif (preg_match('/BANK\s+RAKYAT\s+INDONESIA|BRI/i', $text)) {
        $bank = 'Bank BRI';
    } elseif (preg_match('/BANK\s+SYARIAH\s+INDONESIA|BSI/i', $text)) {
        $bank = 'Bank BSI';
    } elseif (preg_match('/BANK\s+NEGARA\s+INDONESIA|BNI/i', $text)) {
        $bank = 'Bank BNI';
    } else {
        $bank = 'Perbankan (Umum)';
    }

    // 2. Deteksi Nomor Rekening & Nama Pemilik
    if (preg_match('/(\d{10,20})\s*[-–:]\s*([^\r\n]+)/i', $text, $m)) {
        $nomorRekening = trim($m[1]);
        $namaPemilik = trim($m[2]);
    } elseif (preg_match('/(?:No\.?\s*Rekening|Rekening|Account\s*No\.?)[\s:]*([0-9\-]{9,25})/i', $text, $m)) {
        $nomorRekening = str_replace(['-', ' '], '', trim($m[1]));
    }

    // 3. Deteksi Periode
    if (preg_match('/Periode\s*:\s*(\d{2}[-\/]\d{2}[-\/]\d{4})\s*(?:s\/d|-)\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/i', $text, $m)) {
        $periodeAwal = $m[1];
        $periodeAkhir = $m[2];
        $parts = preg_split('/[-\/]/', $m[1]);
        if (count($parts) === 3) {
            $bulan = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        }
    } elseif (preg_match('/(\d{2}[-\/]\d{2}[-\/]\d{4})\s*(?:s\/d|-|to)\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/i', $text, $m)) {
        $periodeAwal = $m[1];
        $periodeAkhir = $m[2];
        $parts = preg_split('/[-\/]/', $m[1]);
        if (count($parts) === 3) {
            $bulan = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        }
    }

    // 4. Deteksi Summary Numbers (Mutasi Kredit, Mutasi Debet, Saldo Awal, Saldo Akhir)
    // Mutasi Kredit (Utama)
    if (preg_match('/(?:Mutasi|Total)\s+Kredit\s*[:\t ]+\s*([0-9.,]+)/i', $text, $m)) {
        $clean = str_replace(',', '', $m[1]);
        $mutasiKredit = (float)$clean;
    } elseif (preg_match('/Total\s+CR\s*[:\t ]+\s*([0-9.,]+)/i', $text, $m)) {
        $clean = str_replace(',', '', $m[1]);
        $mutasiKredit = (float)$clean;
    }

    // Mutasi Debet
    if (preg_match('/(?:Mutasi|Total)\s+Debet\s*[:\t ]+\s*([0-9.,]+)/i', $text, $m)) {
        $clean = str_replace(',', '', $m[1]);
        $mutasiDebet = (float)$clean;
    } elseif (preg_match('/Total\s+DB\s*[:\t ]+\s*([0-9.,]+)/i', $text, $m)) {
        $clean = str_replace(',', '', $m[1]);
        $mutasiDebet = (float)$clean;
    }

    // Saldo Awal
    if (preg_match('/Saldo\s+Awal\s*[:\t ]+\s*([0-9.,]+)/i', $text, $m)) {
        $clean = str_replace(',', '', $m[1]);
        $saldoAwal = (float)$clean;
    }

    // Saldo Akhir
    if (preg_match('/Saldo\s+Akhir\s*[:\t ]+\s*([0-9.,]+)/i', $text, $m)) {
        $clean = str_replace(',', '', $m[1]);
        $saldoAkhir = (float)$clean;
    }

    // 5. Pencocokan ke Database SIM BAZNAS
    $conn = db();
    $matchedAccount = null;
    $idSumberRekening = 0;
    $namaSumberRekening = 'Belum Terpetakan';

    // Cari rekening di tabel sumber_rekening
    $resSumber = $conn->query("SELECT id, nama_sumber FROM sumber_rekening");
    $allAccounts = [];
    while ($r = $resSumber->fetch_assoc()) {
        $allAccounts[] = $r;
        // Cek apakah nomor rekening terkandung dalam nama_sumber
        if ($nomorRekening && strpos(str_replace(' ', '', $r['nama_sumber']), $nomorRekening) !== false) {
            $matchedAccount = $r;
        }
    }

    // Fallback jika belum cocok via nomor rekening, cek via nama bank
    if (!$matchedAccount && $bank) {
        foreach ($allAccounts as $acc) {
            if (stripos($acc['nama_sumber'], $bank) !== false) {
                // Jangan langsung pilih jika ada banyak pilihan, simpan sebagai kandidat
                $matchedAccount = $acc;
                break;
            }
        }
    }

    if ($matchedAccount) {
        $idSumberRekening = (int)$matchedAccount['id'];
        $namaSumberRekening = $matchedAccount['nama_sumber'];
    }

    // 6. Hitung Total Transaksi Sistem di Database untuk Rekonsiliasi
    $totalSistem = 0;
    $jumlahTransaksiSistem = 0;
    $sudahTersimpanDiRekeningKoran = false;
    $idRekeningKoran = null;

    if ($idSumberRekening > 0 && $bulan) {
        // Ambil transaksi sistem
        $stmt = $conn->prepare("SELECT COUNT(*) as jml_tx, COALESCE(SUM(jumlah_pembayaran), 0) as total_nominal FROM transaksi WHERE id_sumber_rekening = ? AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?");
        $stmt->bind_param('is', $idSumberRekening, $bulan);
        $stmt->execute();
        $resTx = $stmt->get_result()->fetch_assoc();
        $totalSistem = (float)($resTx['total_nominal'] ?? 0);
        $jumlahTransaksiSistem = (int)($resTx['jml_tx'] ?? 0);
        $stmt->close();

        // Cek apakah sudah pernah disimpan di rekening_koran
        $stmtRk = $conn->prepare("SELECT id, jumlah_kredit FROM rekening_koran WHERE id_sumber_rekening = ? AND bulan = ? LIMIT 1");
        $stmtRk->bind_param('is', $idSumberRekening, $bulan);
        $stmtRk->execute();
        $resRk = $stmtRk->get_result()->fetch_assoc();
        if ($resRk) {
            $sudahTersimpanDiRekeningKoran = true;
            $idRekeningKoran = (int)$resRk['id'];
        }
        $stmtRk->close();
    }

    // Perhitungan Selisih
    $selisih = 0;
    $statusKlop = false;
    if ($mutasiKredit !== null) {
        $selisih = $mutasiKredit - $totalSistem;
        // Toleransi selisih pecahan cent/pembulatan di bawah 1 rupiah
        if (abs($selisih) < 1.0) {
            $statusKlop = true;
            $selisih = 0;
        }
    }

    // Nama Bulan Indonesia
    $bulanIndo = $bulan ? date('F Y', strtotime($bulan . '-01')) : 'Periode Tidak Terdeteksi';
    $namaBulanArr = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $bulanIndoStr = strtr($bulanIndo, $namaBulanArr);

    // 7. Format Paper-White HTML Card yang Cantik & Elegan
    $badgeStatus = '';
    if ($mutasiKredit !== null) {
        if ($statusKlop) {
            $badgeStatus = '<span class="badge" style="background:#eef7f2; color:#0d9f4f; border:1.5px solid #0d9f4f; font-weight:700; font-size:0.8rem; padding:4px 8px;"><i class="fas fa-check-circle mr-1"></i> KLOP / REKONSILIASI 100%</span>';
        } else {
            $badgeColor = $selisih > 0 ? '#b27500' : '#d9534f';
            $bgBadge = $selisih > 0 ? '#fff9e6' : '#fff0f0';
            $badgeBorder = $selisih > 0 ? '#f0ad4e' : '#d9534f';
            $tanda = $selisih > 0 ? '+ ' : '- ';
            $badgeStatus = '<span class="badge" style="background:' . $bgBadge . '; color:' . $badgeColor . '; border:1px solid ' . $badgeBorder . '; font-weight:700; font-size:0.8rem; padding:4px 8px;"><i class="fas fa-exclamation-circle mr-1"></i> SELISIH: ' . $tanda . format_currency(abs($selisih)) . '</span>';
        }
    } else {
        $badgeStatus = '<span class="badge badge-secondary" style="border:1px solid #b8b8b8;">Ringkasan Mutasi Tidak Lengkap</span>';
    }

    $cardHtml = '
    <div class="card mb-2" style="border: 1.5px solid #b8b8b8; border-radius: 6px; box-shadow: none !important; background: #ffffff;">
        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background: #fdfdfd; border-bottom: 1px solid #dee2e6;">
            <div>
                <strong style="color: #222; font-size: 0.95rem;"><i class="fas fa-file-invoice text-success mr-1"></i> Hasil Analisis Rekening Koran</strong>
                <span class="badge ml-2" style="background:#f0f7f3; color:#0d9f4f; border:1px solid #0d9f4f; font-size:0.75rem;">0 Token (Ekstraksi Lokal)</span>
            </div>
            <div>' . $badgeStatus . '</div>
        </div>
        <div class="card-body p-3">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <small class="text-muted d-block" style="font-size:0.75rem;">BANK & REKENING:</small>
                    <strong style="font-size:0.88rem; color:#333;">' . htmlspecialchars($bank) . '</strong>
                    <div style="font-size:0.82rem; color:#555;">No. Rek: <code>' . htmlspecialchars($nomorRekening ?: '-') . '</code> ' . ($namaPemilik ? ' (' . htmlspecialchars($namaPemilik) . ')' : '') . '</div>
                    <div class="mt-1" style="font-size:0.8rem; color:#0d9f4f;"><i class="fas fa-link mr-1"></i> Mapping Sumber: <strong>' . htmlspecialchars($namaSumberRekening) . '</strong></div>
                </div>
                <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
                    <small class="text-muted d-block" style="font-size:0.75rem;">PERIODE STATEMENT:</small>
                    <strong style="font-size:0.92rem; color:#0d9f4f;">' . htmlspecialchars($bulanIndoStr) . '</strong>
                    <div class="text-muted small">' . htmlspecialchars($periodeAwal ?: '') . ' s/d ' . htmlspecialchars($periodeAkhir ?: '') . '</div>
                    <div class="text-muted small"><i class="fas fa-file-alt mr-1"></i> ' . $extraction['page_count'] . ' Halaman Dokumen</div>
                </div>
            </div>

            <!-- Tabel Komparasi Mutasi Kredit vs Sistem -->
            <div class="table-responsive my-2">
                <table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem; border-color: #b8b8b8;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th>Indikator Finansial</th>
                            <th class="text-right" width="35%">Total Rekening Koran</th>
                            <th class="text-right" width="35%">Tercatat di SIM BAZNAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background: #f0f7f3;">
                            <td><strong class="text-success"><i class="fas fa-arrow-down mr-1"></i> Total Mutasi Kredit (Setoran)</strong></td>
                            <td class="text-right font-weight-bold text-success" style="font-size:0.95rem;">' . ($mutasiKredit !== null ? format_currency($mutasiKredit) : '<span class="text-muted">-</span>') . '</td>
                            <td class="text-right font-weight-bold" style="font-size:0.95rem;">' . format_currency($totalSistem) . ' <small class="text-muted font-weight-normal">(' . $jumlahTransaksiSistem . ' tx)</small></td>
                        </tr>
                        <tr>
                            <td><span class="text-danger"><i class="fas fa-arrow-up mr-1"></i> Total Mutasi Debet (Penarikan)</span></td>
                            <td class="text-right">' . ($mutasiDebet !== null ? format_currency($mutasiDebet) : '<span class="text-muted">-</span>') . '</td>
                            <td class="text-right text-muted"><small><em>(Khusus Pendistribusian)</em></small></td>
                        </tr>
                        <tr>
                            <td><span class="text-muted">Saldo Awal Periode</span></td>
                            <td class="text-right">' . ($saldoAwal !== null ? format_currency($saldoAwal) : '<span class="text-muted">-</span>') . '</td>
                            <td class="text-right text-muted">-</td>
                        </tr>
                        <tr>
                            <td><strong>Saldo Akhir Rekening</strong></td>
                            <td class="text-right font-weight-bold">' . ($saldoAkhir !== null ? format_currency($saldoAkhir) : '<span class="text-muted">-</span>') . '</td>
                            <td class="text-right text-muted">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>';

    if ($mutasiKredit !== null) {
        if ($statusKlop) {
            $cardHtml .= '
            <div class="alert alert-success py-2 px-3 my-2" style="background:#eef7f2; border:1px solid #0d9f4f; color:#0d9f4f; font-size:0.82rem; border-radius:4px;">
                <i class="fas fa-check-circle mr-1"></i> <strong>Sempurna!</strong> Seluruh nominal mutasi kredit bank sebesar <strong>' . format_currency($mutasiKredit) . '</strong> telah cocok 100% dengan total transaksi terinput di sistem.
            </div>';
        } else {
            $cardHtml .= '
            <div class="alert alert-warning py-2 px-3 my-2" style="background:#fffdf0; border:1px solid #f0ad4e; color:#856404; font-size:0.82rem; border-radius:4px;">
                <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Terdapat Selisih ' . format_currency(abs($selisih)) . '</strong> antara mutasi kredit bank dengan data sistem. Periksa apakah ada setoran donatur yang belum diinput atau ada kesalahan nominal input.
            </div>';
        }
    }

    // Action Buttons
    $cardHtml .= '
            <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center justify-content-between" style="gap: 6px;">
                <div class="d-flex flex-wrap" style="gap: 6px;">
                    <button type="button" class="btn btn-xs btn-outline-success font-weight-bold py-1 px-2" onclick="sendAiQuickPrompt(\'Tampilkan rincian transaksi ' . addslashes($namaSumberRekening) . ' bulan ' . addslashes($bulanIndoStr) . '\')" style="border-radius:4px; font-size:0.75rem;">
                        <i class="fas fa-list mr-1"></i> Rincian Transaksi Sistem
                    </button>
                    <a href="index.php?page=rekening_koran" class="btn btn-xs btn-outline-secondary font-weight-bold py-1 px-2" style="border-radius:4px; font-size:0.75rem;">
                        <i class="fas fa-external-link-alt mr-1"></i> Buka Modul Rekening Koran
                    </a>
                </div>';

    if ($idSumberRekening > 0 && $bulan && $mutasiKredit !== null && !$sudahTersimpanDiRekeningKoran) {
        $cardHtml .= '
                <div>
                    <button type="button" class="btn btn-xs btn-success font-weight-bold py-1 px-2" onclick="saveBankStatementToDb(' . $idSumberRekening . ', \'' . $bulan . '\', ' . $mutasiKredit . ', \'' . addslashes($originalFilename ?: basename($filePath)) . '\')" style="border-radius:4px; font-size:0.75rem;">
                        <i class="fas fa-save mr-1"></i> Simpan ke Rekening Koran
                    </button>
                </div>';
    } elseif ($sudahTersimpanDiRekeningKoran) {
        $cardHtml .= '
                <div>
                    <span class="badge" style="background:#f8f9fa; color:#6c757d; border:1px solid #dee2e6; font-size:0.74rem;">
                        <i class="fas fa-check mr-1"></i> Sudah Tercatat di Modul Rekening Koran
                    </span>
                </div>';
    }

    $cardHtml .= '
            </div>
        </div>
    </div>';

    return [
        'success' => true,
        'is_scanned' => false,
        'bank' => $bank,
        'nomor_rekening' => $nomorRekening,
        'nama_pemilik' => $namaPemilik,
        'periode_awal' => $periodeAwal,
        'periode_akhir' => $periodeAkhir,
        'bulan' => $bulan,
        'bulan_nama' => $bulanIndoStr,
        'saldo_awal' => $saldoAwal,
        'mutasi_debet' => $mutasiDebet,
        'mutasi_kredit' => $mutasiKredit,
        'saldo_akhir' => $saldoAkhir,
        'id_sumber_rekening' => $idSumberRekening,
        'nama_sumber_rekening' => $namaSumberRekening,
        'total_sistem' => $totalSistem,
        'jumlah_tx_sistem' => $jumlahTransaksiSistem,
        'selisih' => $selisih,
        'status_klop' => $statusKlop,
        'sudah_tersimpan' => $sudahTersimpanDiRekeningKoran,
        'page_count' => $extraction['page_count'],
        'html_card' => $cardHtml
    ];
}
