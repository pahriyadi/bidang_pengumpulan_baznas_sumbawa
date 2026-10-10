<?php
/**
 * Halaman Asisten AI Penuh (Full Page View)
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 */

require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'ai_asisten';
$pageTitle = 'Asisten AI Eksekutif';
$pageDescription = 'Asisten Virtual Intelijen Pengumpulan ZIS BAZNAS Kabupaten Sumbawa';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Asisten AI</li>';
$conn = db();
$currentUser = current_user();

include __DIR__ . '/../../inc/header.php';
?>

<!-- Style Khusus Halaman Full Page Chat -->
<style>
/* Sembunyikan floating widget ketika berada di halaman chat penuh */
#ai-copilot-toggle-btn,
#ai-copilot-window {
    display: none !important;
}

.ai-page-chat-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 165px);
    min-height: 600px;
    background: #ffffff;
    border: 1px solid #b8b8b8;
    border-radius: 6px;
    overflow: hidden;
}

.ai-page-chat-header {
    padding: 12px 18px;
    background-color: #ffffff;
    border-bottom: 1px solid #b8b8b8;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-page-chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background-color: #fcfcfc;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.ai-page-bubble {
    padding: 12px 16px;
    font-size: 0.88rem;
    line-height: 1.6;
    word-break: break-word;
    border-radius: 5px;
    max-width: 96%;
}

.ai-page-bubble.bot {
    background-color: #ffffff;
    color: #1f2937;
    border: 1px solid #b8b8b8;
    border-left: 4px solid #0d9f4f;
    align-self: flex-start;
    width: 100%;
}

.ai-page-bubble.user {
    background-color: #f0f7f3;
    color: #065f2b;
    border: 1px solid #b8b8b8;
    border-right: 4px solid #0d9f4f;
    align-self: flex-end;
    font-weight: 600;
}

.ai-page-bubble strong {
    font-weight: 700 !important;
}
.ai-page-bubble.bot strong {
    color: #111827;
}
.ai-page-bubble.user strong {
    color: #065f2b;
}

.ai-page-chips-wrap {
    padding: 10px 16px;
    background-color: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 8px;
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: thin;
}

.ai-page-chip {
    background: #ffffff;
    border: 1px solid #b8b8b8;
    color: #374151;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.ai-page-chip:hover {
    background-color: #f0f7f3;
    border-color: #0d9f4f;
    color: #0d9f4f;
}

.ai-page-footer {
    padding: 14px 18px;
    background-color: #ffffff;
    border-top: 1px solid #b8b8b8;
}

.ai-page-input-wrap {
    display: flex;
    gap: 8px;
    align-items: center;
}

.ai-page-input-field {
    flex: 1;
    border: 1px solid #b8b8b8;
    border-radius: 4px;
    padding: 10px 16px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    background-color: #ffffff;
    color: #111827;
}
.ai-page-input-field:focus {
    border-color: #0d9f4f !important;
    box-shadow: 0 0 0 3px rgba(13, 159, 79, 0.15);
}

/* Base Styling untuk AI Cards (Paper White) */
.ai-custom-card {
    background-color: #ffffff;
    border: 1.5px solid #0d9f4f;
    border-radius: 6px;
    box-shadow: none !important;
}
.ai-ocr-card { border-color: #0d9f4f !important; }
.ai-watchdog-card { border-color: #0d9f4f !important; }
.ai-chart-card { border-color: #0d9f4f !important; }
.ai-pdf-card { border-color: #0d9f4f !important; }
.ai-confirm-card { border-color: #f59e0b !important; }
.ai-trx-card { border-color: #0d9f4f !important; }
.ai-delete-card { border-color: #ef4444 !important; }
.ai-briefing-card { border-color: #0d9f4f !important; }

.ai-watchdog-metric-box {
    background-color: #fafafa;
    border: 1px solid #b8b8b8 !important;
    border-radius: 4px;
}
.ai-watchdog-finding-warn {
    background-color: #fff7ed;
    border-left: 3.5px solid #ea580c;
    border-radius: 3px;
    font-size: 0.8rem;
    color: #7c2d12;
}
.ai-watchdog-finding-danger {
    background-color: #fef2f2;
    border-left: 3.5px solid #ef4444;
    border-radius: 3px;
    font-size: 0.8rem;
    color: #991b1b;
}
.ai-card-alert {
    background-color: #f9fafb !important;
    border-color: #b8b8b8 !important;
    color: #4b5563 !important;
}

/* Mode Gelap "Paper Dark" */
.dark-mode .ai-page-chat-container {
    background: #181b1f !important;
    border-color: #374151 !important;
}
.dark-mode .ai-page-chat-header,
.dark-mode .ai-page-footer {
    background-color: #1f2328 !important;
    border-color: #374151 !important;
}
.dark-mode .ai-page-chat-body {
    background-color: #121417 !important;
}
.dark-mode .ai-page-bubble.bot {
    background-color: #1f2328 !important;
    color: #e5e7eb !important;
    border-color: #374151 !important;
    border-left-color: #0d9f4f !important;
}
.dark-mode .ai-page-bubble.user {
    background-color: #1a2e22 !important;
    color: #86efac !important;
    border-color: #276749 !important;
    border-right-color: #0d9f4f !important;
}
.dark-mode .ai-page-bubble strong {
    color: #ffffff !important;
}
.dark-mode .ai-page-chips-wrap {
    background-color: #181b1f !important;
    border-color: #374151 !important;
}
.dark-mode .ai-page-chip {
    background: #1f2328 !important;
    border-color: #374151 !important;
    color: #9ca3af !important;
}
.dark-mode .ai-page-chip:hover {
    background-color: #262b32 !important;
    border-color: #34d399 !important;
    color: #34d399 !important;
}
.dark-mode .ai-page-input-field {
    background-color: #1f2328 !important;
    border-color: #374151 !important;
    color: #f3f4f6 !important;
}
.dark-mode .ai-page-bubble table,
.dark-mode .ai-page-bubble th,
.dark-mode .ai-page-bubble td {
    border-color: #374151 !important;
    color: #e5e7eb !important;
}
.dark-mode .ai-page-bubble thead th {
    background-color: #262b32 !important;
    color: #f3f4f6 !important;
}

/* Dark Mode Overrides untuk Card AI di Halaman Penuh */
.dark-mode .ai-custom-card,
.dark-mode .ai-ocr-card,
.dark-mode .ai-watchdog-card,
.dark-mode .ai-chart-card,
.dark-mode .ai-pdf-card,
.dark-mode .ai-confirm-card,
.dark-mode .ai-trx-card,
.dark-mode .ai-delete-card,
.dark-mode .ai-briefing-card {
    background-color: #1f2328 !important;
    color: #e5e7eb !important;
    box-shadow: none !important;
}

.dark-mode .ai-ocr-card { border-color: #0d9f4f !important; }
.dark-mode .ai-watchdog-card { border-color: #0d9f4f !important; }
.dark-mode .ai-chart-card { border-color: #0d9f4f !important; }
.dark-mode .ai-pdf-card { border-color: #0d9f4f !important; }
.dark-mode .ai-confirm-card { border-color: #f59e0b !important; }
.dark-mode .ai-trx-card { border-color: #0d9f4f !important; }
.dark-mode .ai-delete-card { border-color: #ef4444 !important; }
.dark-mode .ai-briefing-card { border-color: #0d9f4f !important; }

.dark-mode .ai-custom-card strong,
.dark-mode .ai-custom-card .ai-card-title,
.dark-mode .ai-custom-card h5,
.dark-mode .ai-custom-card h6 {
    color: #f3f4f6 !important;
}

.dark-mode .ai-custom-card p,
.dark-mode .ai-custom-card .ai-card-desc,
.dark-mode .ai-custom-card li {
    color: #d1d5db !important;
}

.dark-mode .ai-custom-card small,
.dark-mode .ai-custom-card .text-muted {
    color: #9ca3af !important;
}

.dark-mode .ai-custom-card .border-bottom,
.dark-mode .ai-custom-card .border-top,
.dark-mode .ai-custom-card .border {
    border-color: #374151 !important;
}

.dark-mode .ai-watchdog-metric-box {
    background-color: #181b1f !important;
    border-color: #374151 !important;
}
.dark-mode .ai-watchdog-metric-box strong {
    color: #f3f4f6 !important;
}

.dark-mode .ai-watchdog-finding-warn {
    background-color: #2b1d0c !important;
    border-left: 3.5px solid #f59e0b !important;
    color: #fde68a !important;
}
.dark-mode .ai-watchdog-finding-warn strong {
    color: #fef08a !important;
}
.dark-mode .ai-watchdog-finding-warn li {
    color: #fde68a !important;
}

.dark-mode .ai-watchdog-finding-danger {
    background-color: #2d1517 !important;
    border-left: 3.5px solid #ef4444 !important;
    color: #fca5a5 !important;
}
.dark-mode .ai-watchdog-finding-danger strong {
    color: #fecaca !important;
}
.dark-mode .ai-watchdog-finding-danger li {
    color: #fca5a5 !important;
}

.dark-mode .ai-card-alert,
.dark-mode .ai-custom-card .alert-light {
    background-color: #181b1f !important;
    border-color: #374151 !important;
    color: #9ca3af !important;
}

.dark-mode .ai-custom-card .table,
.dark-mode .ai-custom-card .table td,
.dark-mode .ai-custom-card .table th {
    color: #e5e7eb !important;
    border-color: #374151 !important;
}

.dark-mode .ai-custom-card .badge-light {
    background-color: #262b32 !important;
    color: #d1d5db !important;
    border-color: #374151 !important;
}

.dark-mode .ai-custom-card .btn-outline-secondary {
    background-color: #262b32 !important;
    color: #d1d5db !important;
    border-color: #4b5563 !important;
}
.dark-mode .ai-custom-card .btn-outline-secondary:hover {
    background-color: #374151 !important;
    color: #ffffff !important;
}

.dark-mode .ai-custom-card .btn-outline-success {
    background-color: #143522 !important;
    color: #34d399 !important;
    border-color: #0d9f4f !important;
}
.dark-mode .ai-custom-card .btn-outline-success:hover {
    background-color: #0d9f4f !important;
    color: #ffffff !important;
}

.dark-mode .ai-custom-card .btn-outline-primary {
    background-color: #1e3a5f !important;
    color: #93c5fd !important;
    border-color: #3b82f6 !important;
}
.dark-mode .ai-custom-card .btn-outline-primary:hover {
    background-color: #2563eb !important;
    color: #ffffff !important;
}

.dark-mode #ai-page-btn-upload,
.dark-mode #ai-page-btn-slip,
.dark-mode #ai-page-btn-voice {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #d1d5db !important;
}
.dark-mode #ai-page-btn-upload:hover,
.dark-mode #ai-page-btn-slip:hover,
.dark-mode #ai-page-btn-voice:hover {
    background-color: #374151 !important;
    color: #ffffff !important;
}

.dark-mode #ai-page-voice-status-bar {
    background-color: #2d1517 !important;
    border-color: #ef4444 !important;
    color: #fca5a5 !important;
}
</style>

<div class="content-header pt-2 pb-2">
    <div class="container-fluid">
        <div class="row mb-1 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.35rem;">
                    <i class="fas fa-robot text-success mr-2"></i>Asisten AI Intelijen BAZNAS
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right bg-transparent p-0 mb-0" style="font-size: 0.85rem;">
                    <?= $breadcrumbs ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content pb-3">
    <div class="container-fluid">
        <div class="ai-page-chat-container">
            <!-- Header Chat Penuh -->
            <div class="ai-page-chat-header">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <div class="p-2 rounded" style="background: #eef7f2; border: 1px solid #b8b8b8;">
                        <i class="fas fa-brain text-success" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <span class="font-weight-bold d-block" style="font-size: 0.95rem; color: #111827;">
                            Amil Virtual Intelijen BAZNAS Sumbawa
                        </span>
                        <small class="text-muted d-block" style="font-size: 0.76rem;">
                            <span class="badge" style="background:#f0f7f3; color:#0d9f4f; border: 1px solid #b8b8b8; font-size: 0.68rem; font-weight: normal;">
                                <i class="fas fa-check-circle mr-1"></i> Strict Read-Only Engine
                            </span>
                            Mode Halaman Penuh &bull; Menampilkan tabel lengkap tanpa batas baris
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 font-weight-bold" onclick="clearPageChatHistory()" style="border-radius: 4px; border: 1px solid #b8b8b8;">
                        <i class="fas fa-trash-alt mr-1"></i> Bersihkan Obrolan
                    </button>
                    <a href="<?= url('pages/transaksi/index.php') ?>" class="btn btn-sm btn-outline-success py-1 px-3 font-weight-bold" style="border-radius: 4px;">
                        <i class="fas fa-table mr-1"></i> Data Transaksi
                    </a>
                </div>
            </div>

            <!-- Badan Riwayat Percakapan Penuh -->
            <div class="ai-page-chat-body" id="ai-page-body">
                <div class="ai-page-bubble bot">
                    Assalamu’alaikum Warahmatullahi Wabarakatuh, <strong><?= htmlspecialchars($currentUser['full_name'] ?? 'Amil BAZNAS') ?></strong>.<br><br>
                    Selamat datang di <strong>Mode Halaman Penuh Asisten Intelijen BAZNAS Sumbawa</strong>. Pada mode layar penuh ini, Anda dapat menelusuri data dengan area yang sangat luas, serta melihat tabel rekapitulasi dan transaksi secara penuh tanpa pembatasan jumlah baris:<br>
                    &bull; <strong>Daftar Transaksi Lengkap:</strong> <em>"Tampilkan semua transaksi tahun 2026 secara lengkap"</em> atau <em>"Tampilkan transaksi UPZ Dinas Kesehatan"</em>.<br>
                    &bull; <strong>Rekapitulasi RKAT:</strong> <em>"Berapa capaian target RKAT pengumpulan tahun 2026?"</em>.<br>
                    &bull; <strong>Peringkat & Agregasi:</strong> <em>"Top 5 UPZ dengan penyetoran terbanyak tahun ini"</em>.<br>
                    &bull; <strong>Rekening Koran Bank:</strong> Unggah file PDF mutasi rekening koran untuk rekonsiliasi instan (0 Token API).<br><br>
                    <small class="text-muted"><i class="fas fa-info-circle mr-1 text-success"></i> Seluruh kueri penelusuran data dieksekusi secara aman dengan pembacaan langsung dari basis data.</small>
                </div>
            </div>

            <!-- Chips Rekomendasi Prompt Cepat -->
            <div class="ai-page-chips-wrap">
                <button type="button" class="ai-page-chip" onclick="sendPageQuickPrompt('Jalankan deteksi anomali dan audit kualitas data')">
                    🛡️ Audit Watchdog
                </button>
                <button type="button" class="ai-page-chip" onclick="sendPageQuickPrompt('Tampilkan grafik tren penerimaan ZIS tahun ini')">
                    📊 Grafik Tren ZIS
                </button>
                <button type="button" class="ai-page-chip" onclick="sendPageQuickPrompt('Buat laporan eksekutif PDF pengumpulan ZIS')">
                    📄 Laporan Eksekutif PDF
                </button>
                <button type="button" class="ai-page-chip" onclick="document.getElementById('ai-page-slip-input').click()">
                    📸 Pindai Slip OCR
                </button>
                <button type="button" class="ai-page-chip" onclick="sendPageQuickPrompt('Tampilkan semua transaksi terbaru tahun 2026 secara lengkap')">
                    📋 Transaksi 2026 Lengkap
                </button>
                <button type="button" class="ai-page-chip" onclick="sendPageQuickPrompt('Berapa capaian target pengumpulan RKAT tahun 2026?')">
                    🎯 Capaian RKAT 2026
                </button>
                <button type="button" class="ai-page-chip" onclick="sendPageQuickPrompt('Tampilkan top 5 UPZ dengan penyetoran terbanyak tahun ini')">
                    🏢 Top 5 UPZ
                </button>
            </div>

            <!-- Footer Baris Input Chat -->
            <div class="ai-page-footer">
                <div id="ai-page-voice-status-bar" style="display: none; background: #fee2e2; border: 1px solid #f87171; border-radius: 4px; padding: 6px 14px; margin-bottom: 8px; font-size: 0.84rem; color: #991b1b; justify-content: space-between; align-items: center;">
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        <span class="spinner-grow spinner-grow-sm text-danger" role="status" style="width: 10px; height: 10px;"></span>
                        <span><strong>Mendengarkan ucapan Anda...</strong> Silakan berbicara sekarang.</span>
                    </div>
                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 font-weight-bold" onclick="togglePageVoiceInput()">Selesai Bicara</button>
                </div>
                <div class="ai-page-input-wrap">
                    <input type="file" id="ai-page-pdf-input" accept="application/pdf" style="display: none;" onchange="handlePagePdfUpload(this)">
                    <input type="file" id="ai-page-slip-input" accept="image/*" style="display: none;" onchange="handlePageSlipUpload(this)">
                    <button type="button" class="btn btn-outline-secondary px-2" id="ai-page-btn-upload" onclick="document.getElementById('ai-page-pdf-input').click()" title="Unggah PDF Rekening Koran (0 Token)" style="border-radius: 4px; border: 1px solid #b8b8b8;">
                        <i class="fas fa-paperclip text-success"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-2" id="ai-page-btn-slip" onclick="document.getElementById('ai-page-slip-input').click()" title="Pindai Bukti Transfer / Kuitansi (Multimodal OCR)" style="border-radius: 4px; border: 1px solid #b8b8b8;">
                        <i class="fas fa-camera text-info"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-2" id="ai-page-btn-voice" onclick="togglePageVoiceInput()" title="Input Suara Bebas Ketik (Web Speech API Native)" style="border-radius: 4px; border: 1px solid #b8b8b8;">
                        <i class="fas fa-microphone text-primary" id="ai-page-icon-voice"></i>
                    </button>
                    <input type="text" id="ai-page-input-text" class="ai-page-input-field" placeholder="Ketik pertanyaan, bicara, atau tempel (Ctrl+V) bukti transfer..." onkeypress="if(event.key==='Enter') sendPageMessage();">
                    <button type="button" class="btn btn-outline-success font-weight-bold px-4" id="ai-page-btn-send" onclick="sendPageMessage()" style="border-radius: 4px; border: 1.5px solid #0d9f4f;">
                        <i class="fas fa-paper-plane mr-1"></i> Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const PAGE_AI_ENDPOINT = '<?= url("pages/ai_asisten/chat.php") ?>';

// Parser Markdown Aman (Bold, Italic, Code, List, Newline)
function formatAiMarkdownPage(text) {
    if (!text || typeof text !== 'string') return '';
    let formatted = text;

    // 0. Amankan tanda bullet point di awal baris
    formatted = formatted.replace(/^[\t ]*[\*\-]\s+/gm, '• ');

    // 1. Bold + Italic gabungan
    formatted = formatted.replace(/\*\*\*([\s\S]+?)\*\*\*/g, '<strong><em>$1</em></strong>');
    formatted = formatted.replace(/___([\s\S]+?)___/g, '<strong><em>$1</em></strong>');

    // 2. Bold asteris ganda
    formatted = formatted.replace(/\*\*([\s\S]+?)\*\*/g, '<strong>$1</strong>');
    formatted = formatted.replace(/__([\s\S]+?)__/g, '<strong>$1</strong>');

    // 3. Italic asteris tunggal
    formatted = formatted.replace(/(?<!\*|\w)\*([^\*\n]+?)\*(?!\*|\w)/g, '<em>$1</em>');
    formatted = formatted.replace(/(?<!_|\w)_([^_\n]+?)_(?!_|\w)/g, '<em>$1</em>');

    // 4. Inline Code
    formatted = formatted.replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.06); padding:1px 4px; border-radius:3px; font-size:0.88em;">$1</code>');

    // 5. Normalisasi Baris Baru jika belum ada tag block HTML
    if (!/<(div|table|ul|ol|li|p|blockquote)[^>]*>/i.test(formatted)) {
        formatted = formatted.replace(/\n/g, '<br>');
    }

    return formatted;
}

// Auto-Scroll ke paling bawah
function scrollPageToBottom(smooth = false) {
    const body = document.getElementById('ai-page-body');
    if (!body) return;
    const doScroll = () => {
        const lastEl = body.lastElementChild;
        if (lastEl && typeof lastEl.scrollIntoView === 'function') {
            lastEl.scrollIntoView({ block: 'end', behavior: smooth ? 'smooth' : 'auto' });
        }
        body.scrollTop = body.scrollHeight;
    };
    doScroll();
    requestAnimationFrame(doScroll);
    setTimeout(doScroll, 40);
    setTimeout(doScroll, 120);
    setTimeout(doScroll, 250);
}

const AI_PAGE_CACHE_KEY = 'baznas_ai_page_history_cache_v2';
let pageHistoryLoaded = false;

function renderAiCharts(container) {
    const root = container || document;
    const canvases = root.querySelectorAll('canvas.ai-chat-chart:not([data-rendered="1"])');
    if (!canvases || canvases.length === 0) return;

    const initAll = () => {
        const isDarkMode = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
        canvases.forEach(canvas => {
            try {
                const rawConfig = canvas.dataset.chart;
                if (!rawConfig) return;
                canvas.setAttribute('data-rendered', '1');
                const config = JSON.parse(rawConfig);

                if (isDarkMode) {
                    config.options = config.options || {};
                    if (config.options.legend && config.options.legend.labels) {
                        config.options.legend.labels.fontColor = '#e5e7eb';
                    }
                    if (config.options.title) {
                        config.options.title.fontColor = '#f3f4f6';
                    }
                    if (config.options.scales) {
                        ['xAxes', 'yAxes'].forEach(axisKey => {
                            if (config.options.scales[axisKey]) {
                                config.options.scales[axisKey].forEach(axe => {
                                    axe.ticks = axe.ticks || {};
                                    axe.ticks.fontColor = '#9ca3af';
                                    axe.gridLines = axe.gridLines || {};
                                    axe.gridLines.color = 'rgba(255, 255, 255, 0.08)';
                                    axe.gridLines.zeroLineColor = 'rgba(255, 255, 255, 0.15)';
                                });
                            }
                        });
                    }
                }

                new Chart(canvas, config);
            } catch (e) {
                console.warn('Error rendering AI chart:', e);
            }
        });
    };

    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = '<?= url("assets/adminlte/plugins/chart.js/Chart.bundle.min.js") ?>';
        script.onload = initAll;
        document.head.appendChild(script);
    } else {
        initAll();
    }
}

// Sinkronkan ulang grafik ketika tema berubah
window.addEventListener('themeChanged', function() {
    document.querySelectorAll('canvas.ai-chat-chart[data-rendered="1"]').forEach(canvas => {
        canvas.removeAttribute('data-rendered');
    });
    renderAiCharts(document);
});

function renderPageHistory(historyList) {
    const body = document.getElementById('ai-page-body');
    if (!body || !historyList || historyList.length === 0) return;

    body.innerHTML = '';
    const headerNotice = document.createElement('div');
    headerNotice.className = 'text-center my-2';
    headerNotice.innerHTML = '<span class="badge" style="background-color: #f0f7f3; color: #0d9f4f; border: 1px solid #b8b8b8; font-weight: normal; font-size: 0.74rem;"><i class="fas fa-history mr-1"></i> Riwayat obrolan (' + historyList.length + ' pesan)</span>';
    body.appendChild(headerNotice);

    historyList.forEach(item => {
        if (item.user_message) {
            const userBubble = document.createElement('div');
            userBubble.className = 'ai-page-bubble user';
            userBubble.innerHTML = formatAiMarkdownPage(item.user_message);
            body.appendChild(userBubble);
        }

        if (item.ai_reply) {
            const botBubble = document.createElement('div');
            botBubble.className = 'ai-page-bubble bot';
            let replyContent = formatAiMarkdownPage(item.ai_reply);

            if (item.quick_actions && item.quick_actions.length > 0) {
                replyContent += '<div class="mt-2 pt-2 border-top d-flex flex-wrap" style="gap: 6px;">';
                item.quick_actions.forEach(act => {
                    if (act.url) {
                        replyContent += `<a href="${act.url}" target="_blank" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</a>`;
                    } else if (act.prompt) {
                        const safePrompt = act.prompt.replace(/'/g, "\\'");
                        replyContent += `<button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 font-weight-bold" onclick="sendPageQuickPrompt('${safePrompt}')" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</button>`;
                    }
                });
                replyContent += '</div>';
            }

            botBubble.innerHTML = replyContent;
            body.appendChild(botBubble);
        }
    });

    renderAiCharts(body);
    scrollPageToBottom(false);
}

// Muat Riwayat Chat Pengguna (dengan 0 ms Stale-While-Revalidate Cache)
function loadPageChatHistory() {
    const body = document.getElementById('ai-page-body');
    if (!body) return;

    // Render instan dari cache lokal jika tersedia
    const cached = sessionStorage.getItem(AI_PAGE_CACHE_KEY);
    if (cached) {
        try {
            const parsedCache = JSON.parse(cached);
            if (Array.isArray(parsedCache) && parsedCache.length > 0 && !pageHistoryLoaded) {
                renderPageHistory(parsedCache);
                pageHistoryLoaded = true;
            }
        } catch (e) {}
    }

    const formData = new FormData();
    formData.append('action', 'get_history');

    fetch(PAGE_AI_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        try {
            return await res.json();
        } catch (e) {
            return { status: 'error', history: [] };
        }
    })
    .then(data => {
        if (data.status === 'success' && data.history && data.history.length > 0) {
            sessionStorage.setItem(AI_PAGE_CACHE_KEY, JSON.stringify(data.history));
            renderPageHistory(data.history);
            pageHistoryLoaded = true;
        }
    })
    .catch(err => {
        console.warn('Gagal memuat riwayat:', err);
    });
}

// Fitur Input Suara Bebas Ketik Halaman Penuh (Web Speech API Native)
let aiPageSpeech = null;
let isPageVoiceListening = false;
let pageVoiceInitialText = '';

function togglePageVoiceInput() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const icon = document.getElementById('ai-page-icon-voice');
    const input = document.getElementById('ai-page-input-text');
    const statusBar = document.getElementById('ai-page-voice-status-bar');

    // 1. Validasi Keamanan Protokol HTTPS / Localhost
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (!window.isSecureContext && !isLocalhost) {
        const msg = 'Fitur input suara (Speech Recognition) diwajibkan oleh peramban berjalan di atas protokol HTTPS atau localhost.';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Akses Mikrofon Memerlukan HTTPS',
                html: 'Peramban memblokir izin mikrofon pada koneksi HTTP biasa.<br><br>Silakan akses SIM BAZNAS menggunakan alamat <b>HTTPS</b> atau melalui <b>localhost</b>.',
                confirmButtonColor: '#0d9f4f'
            });
        } else {
            alert(msg);
        }
        return;
    }

    // 2. Validasi Dukungan Browser
    if (!SpeechRecognition) {
        const msg = 'Fitur Input Suara memerlukan peramban dengan dukungan Web Speech API (seperti Google Chrome atau Microsoft Edge).';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Peramban Tidak Mendukung',
                html: 'Peramban ini belum mendukung Web Speech API.<br><br>Disarankan menggunakan peramban <b>Google Chrome</b> atau <b>Microsoft Edge</b> versi terbaru.',
                confirmButtonColor: '#0d9f4f'
            });
        } else {
            alert(msg);
        }
        return;
    }

    // 3. Jika sedang mendengarkan, hentikan
    if (isPageVoiceListening && aiPageSpeech) {
        try { aiPageSpeech.stop(); } catch(e) {}
        stopPageVoiceUI();
        return;
    }

    // 4. Inisialisasi & Mulai Mendengarkan
    try {
        aiPageSpeech = new SpeechRecognition();
        aiPageSpeech.lang = 'id-ID';
        aiPageSpeech.continuous = true;
        aiPageSpeech.interimResults = true;
        aiPageSpeech.maxAlternatives = 1;

        pageVoiceInitialText = input && input.value ? input.value.trim() + ' ' : '';

        aiPageSpeech.onstart = function() {
            isPageVoiceListening = true;
            if (icon) icon.className = 'fas fa-dot-circle text-danger fa-pulse';
            if (statusBar) statusBar.style.display = 'flex';
            if (input) {
                input.placeholder = '🎙️ Sedang mendengarkan ucapan Anda...';
                input.focus();
            }
        };

        aiPageSpeech.onresult = function(event) {
            let finalAccumulated = '';
            let interimTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalAccumulated += transcript + ' ';
                } else {
                    interimTranscript += transcript;
                }
            }

            if (finalAccumulated) {
                pageVoiceInitialText += finalAccumulated;
            }

            if (input) {
                input.value = (pageVoiceInitialText + interimTranscript).trim();
            }
        };

        aiPageSpeech.onerror = function(event) {
            console.warn('AI Page Voice notice:', event.error);
            stopPageVoiceUI();

            if (event.error === 'not-allowed' || event.error === 'permission-denied') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Izin Mikrofon Diblokir',
                        html: 'Akses mikrofon tidak diizinkan oleh peramban.<br><br><b>Cara mengizinkan:</b><br>1. Klik ikon gembok / pengaturan di sebelah kiri bilah URL browser.<br>2. Cari izin <b>Mikrofon (Microphone)</b> lalu pilih <b>Izinkan (Allow)</b>.<br>3. Muat ulang (refresh) halaman.',
                        confirmButtonColor: '#0d9f4f'
                    });
                } else {
                    alert('Izin mikrofon belum diberikan. Silakan aktifkan izin mikrofon pada peramban Anda.');
                }
            } else if (event.error === 'network') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Koneksi Terputus',
                        text: 'Pengenalan suara Google membutuhkan sambungan internet aktif.',
                        confirmButtonColor: '#0d9f4f'
                    });
                }
            }
        };

        aiPageSpeech.onend = function() {
            stopPageVoiceUI();
        };

        aiPageSpeech.start();
    } catch (e) {
        console.error('Error starting speech:', e);
        stopPageVoiceUI();
    }
}

function stopPageVoiceUI() {
    isPageVoiceListening = false;
    const icon = document.getElementById('ai-page-icon-voice');
    const input = document.getElementById('ai-page-input-text');
    const statusBar = document.getElementById('ai-page-voice-status-bar');

    if (icon) icon.className = 'fas fa-microphone text-primary';
    if (statusBar) statusBar.style.display = 'none';
    if (input) input.placeholder = 'Ketik pertanyaan, bicara, atau tempel (Ctrl+V) bukti transfer...';
}

// Fitur Pemindai Bukti Transfer & Kuitansi (Multimodal OCR)
function handlePageSlipUpload(fileInput) {
    if (!fileInput.files || fileInput.files.length === 0) return;
    const file = fileInput.files[0];
    if (!file.type.startsWith('image/')) {
        alert('Silakan pilih berkas gambar bukti transfer atau kuitansi (JPG/PNG).');
        fileInput.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        uploadPageSlip(e.target.result, file.type, file.name);
    };
    reader.readAsDataURL(file);
    fileInput.value = '';
}

function uploadPageSlip(base64Data, mimeType, filename = 'Bukti Transfer') {
    const body = document.getElementById('ai-page-body');
    if (!body) return;

    const userBubble = document.createElement('div');
    userBubble.className = 'ai-page-bubble user';
    userBubble.innerHTML = `<i class="fas fa-camera mr-1"></i> Memindai bukti transfer: <strong>${filename}</strong><br><img src="${base64Data}" style="max-height: 140px; border-radius: 4px; border: 1px solid #b8b8b8; margin-top: 6px;">`;
    body.appendChild(userBubble);

    const typingId = 'page-slip-loading-' + Date.now();
    const typingBubble = document.createElement('div');
    typingBubble.className = 'ai-page-bubble bot';
    typingBubble.id = typingId;
    typingBubble.innerHTML = '<span class="ai-typing-dots"><span></span><span></span><span></span></span> <span style="font-size: 0.82rem;" class="text-muted"><i class="fas fa-search mr-1"></i> AI Vision sedang membaca struk & mencocokkan rekening BAZNAS...</span>';
    body.appendChild(typingBubble);
    scrollPageToBottom(true);

    const formData = new FormData();
    formData.append('action', 'scan_slip');
    formData.append('image_data', base64Data);
    formData.append('mime_type', mimeType);

    fetch(PAGE_AI_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        try {
            return await res.json();
        } catch (e) {
            return { status: 'error', reply: 'Respon server tidak valid saat memindai bukti transfer.' };
        }
    })
    .then(data => {
        const typingEl = document.getElementById(typingId);
        if (typingEl) typingEl.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'ai-page-bubble bot';
        botBubble.innerHTML = data.reply || 'Hasil pemindaian selesai.';
        body.appendChild(botBubble);
        scrollPageToBottom(true);
    })
    .catch(err => {
        const typingEl = document.getElementById(typingId);
        if (typingEl) typingEl.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'ai-page-bubble bot text-danger';
        botBubble.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Gagal memproses pemindai slip OCR. Pastikan koneksi internet stabil.';
        body.appendChild(botBubble);
        scrollPageToBottom(true);
    });
}

// Paste Event Listener untuk Menangkap Gambar dari Clipboard (Ctrl + V)
document.addEventListener('paste', function(e) {
    if (e.clipboardData && e.clipboardData.items) {
        for (let i = 0; i < e.clipboardData.items.length; i++) {
            const item = e.clipboardData.items[i];
            if (item.type.indexOf('image') !== -1) {
                e.preventDefault();
                const file = item.getAsFile();
                const reader = new FileReader();
                reader.onload = function(evt) {
                    uploadPageSlip(evt.target.result, file.type, 'Tangkapan Layar (Clipboard)');
                };
                reader.readAsDataURL(file);
                break;
            }
        }
    }
});

function sendPageQuickPrompt(promptText) {
    document.getElementById('ai-page-input-text').value = promptText;
    sendPageMessage();
}

function sendPageMessage() {
    const input = document.getElementById('ai-page-input-text');
    const body = document.getElementById('ai-page-body');
    const btn = document.getElementById('ai-page-btn-send');
    const msg = input.value.trim();

    if (isPageVoiceListening && aiPageSpeech) {
        try { aiPageSpeech.stop(); } catch(e) {}
        stopPageVoiceUI();
    }

    if (!msg) return;

    // 1. Tampilkan Balon Pengguna
    const userBubble = document.createElement('div');
    userBubble.className = 'ai-page-bubble user';
    userBubble.innerHTML = formatAiMarkdownPage(msg);
    body.appendChild(userBubble);
    input.value = '';
    scrollPageToBottom(true);

    // 2. Indikator Loading
    btn.disabled = true;
    const typingId = 'page-typing-' + Date.now();
    const typingBubble = document.createElement('div');
    typingBubble.className = 'ai-page-bubble bot';
    typingBubble.id = typingId;
    typingBubble.innerHTML = '<span class="ai-typing-dots"><span></span><span></span><span></span></span> <em>Menganalisis data SIM BAZNAS...</em>';
    body.appendChild(typingBubble);
    scrollPageToBottom(true);

    // 3. Kirim ke Endpoint
    const formData = new FormData();
    formData.append('message', msg);

    fetch(PAGE_AI_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const rawText = await res.text();
        let parsed = null;
        try {
            parsed = JSON.parse(rawText);
        } catch (e) {
            return {
                status: 'success',
                reply: 'Terjadi kendala saat memproses jawaban otomatis. Ada data transaksi atau laporan lain yang ingin Anda cari?',
                quick_actions: [
                    { label: '🎯 Capaian RKAT 2026', prompt: 'Berapa capaian target RKAT tahun 2026?' },
                    { label: '🏢 Top 5 UPZ', prompt: 'Tampilkan top 5 UPZ dengan penyetoran terbanyak' }
                ]
            };
        }
        return parsed;
    })
    .then(data => {
        btn.disabled = false;
        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'ai-page-bubble bot';

        let replyContent = formatAiMarkdownPage(data.reply || 'Tidak ada data.');
        if (data.quick_actions && data.quick_actions.length > 0) {
            replyContent += '<div class="mt-2 pt-2 border-top d-flex flex-wrap" style="gap: 6px;">';
            data.quick_actions.forEach(act => {
                if (act.url) {
                    replyContent += `<a href="${act.url}" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</a>`;
                } else if (act.prompt) {
                    replyContent += `<button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 font-weight-bold" onclick="sendPageQuickPrompt('${act.prompt}')" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</button>`;
                }
            });
            replyContent += '</div>';
        }

        botBubble.innerHTML = replyContent;
        body.appendChild(botBubble);
        renderAiCharts(botBubble);
        scrollPageToBottom(true);
    })
    .catch(err => {
        btn.disabled = false;
        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const errBubble = document.createElement('div');
        errBubble.className = 'ai-page-bubble bot text-danger';
        errBubble.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Terjadi kendala saat menghubungi server. Mohon segarkan halaman.';
        body.appendChild(errBubble);
        scrollPageToBottom(true);
    });
}

function handlePagePdfUpload(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    if (!file.name.toLowerCase().endsWith('.pdf')) {
        alert('Format file tidak didukung. Mohon pilih dokumen PDF rekening koran.');
        input.value = '';
        return;
    }

    const body = document.getElementById('ai-page-body');
    const btnSend = document.getElementById('ai-page-btn-send');
    const btnUpload = document.getElementById('ai-page-btn-upload');

    const userBubble = document.createElement('div');
    userBubble.className = 'ai-page-bubble user';
    userBubble.innerHTML = '<i class="fas fa-file-pdf text-danger mr-1"></i> Mengunggah: <strong>' + file.name + '</strong> (' + (file.size / 1024).toFixed(1) + ' KB)';
    body.appendChild(userBubble);
    scrollPageToBottom(true);

    btnSend.disabled = true;
    btnUpload.disabled = true;
    const typingId = 'page-typing-' + Date.now();
    const typingBubble = document.createElement('div');
    typingBubble.className = 'ai-page-bubble bot';
    typingBubble.id = typingId;
    typingBubble.innerHTML = '<span class="ai-typing-dots"><span></span><span></span><span></span></span> <em>Mengekstrak teks & menganalisis mutasi kredit bank...</em>';
    body.appendChild(typingBubble);
    scrollPageToBottom(true);

    const formData = new FormData();
    formData.append('action', 'upload_pdf');
    formData.append('pdf_file', file);

    fetch(PAGE_AI_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        try {
            return await res.json();
        } catch (e) {
            return {
                status: 'error',
                reply: '<div class="alert alert-danger mb-0">Gagal membaca berkas.</div>'
            };
        }
    })
    .then(data => {
        btnSend.disabled = false;
        btnUpload.disabled = false;
        input.value = '';

        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'ai-page-bubble bot';
        botBubble.innerHTML = formatAiMarkdownPage(data.reply || 'Tidak ada data.');
        body.appendChild(botBubble);
        scrollPageToBottom(true);
    })
    .catch(err => {
        btnSend.disabled = false;
        btnUpload.disabled = false;
        input.value = '';
        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();
        scrollPageToBottom(true);
    });
}

function clearPageChatHistory() {
    if (!confirm('Apakah Anda yakin ingin menghapus seluruh riwayat obrolan AI Anda?')) return;

    const formData = new FormData();
    formData.append('action', 'clear_history');

    fetch(PAGE_AI_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        try {
            return await res.json();
        } catch (e) {
            return { status: 'success' };
        }
    })
    .then(data => {
        const body = document.getElementById('ai-page-body');
        body.innerHTML = `
            <div class="ai-page-bubble bot">
                <i class="fas fa-check-circle text-success mr-1"></i> Riwayat obrolan Anda telah dibersihkan dari memori AI.<br><br>
                Ada data transaksi, target RKAT, atau rekening koran lain yang ingin Anda tampilkan?
            </div>
        `;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadPageChatHistory();
    setTimeout(() => {
        const inp = document.getElementById('ai-page-input-text');
        if (inp) inp.focus();
    }, 200);
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
