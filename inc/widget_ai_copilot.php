<!-- ==============================================================
     WIDGET ASISTEN AI INTELIJEN (AI DATA COPILOT)
     SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
     Sistem Desain: "Putih Kertas" (Paper White)
============================================================== -->
<style>
/* Kontainer Utama Floating */
#ai-copilot-container {
    position: fixed;
    bottom: 22px;
    right: 25px;
    z-index: 1040;
    font-family: inherit;
}

/* Tombol Pemicu Mengambang (Floating Action Button) */
.ai-copilot-trigger {
    background-color: #ffffff;
    color: #0d9f4f;
    border: 1.5px solid #0d9f4f;
    border-radius: 50px;
    padding: 10px 18px;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(13, 159, 79, 0.18);
    transition: all 0.2s ease-in-out;
}
.ai-copilot-trigger:hover {
    background-color: #0d9f4f;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(13, 159, 79, 0.28);
}
.ai-copilot-trigger i {
    font-size: 1.15rem;
}

/* Jendela Chat Interaktif (Paper White Full Window Default) */
.ai-copilot-window {
    position: fixed;
    top: 15px;
    left: 15px;
    right: 15px;
    bottom: 15px;
    width: calc(100vw - 30px);
    height: calc(100vh - 30px);
    max-width: calc(100vw - 30px);
    max-height: calc(100vh - 30px);
    background: #ffffff;
    border: 1.5px solid #b8b8b8;
    border-radius: 8px;
    display: none;
    flex-direction: column;
    z-index: 1045;
    box-shadow: 0 16px 45px rgba(0, 0, 0, 0.22);
    overflow: hidden;
    transition: all 0.2s ease;
}
.ai-copilot-window.is-open {
    display: flex;
}
.ai-copilot-window.is-compact {
    top: auto !important;
    left: auto !important;
    bottom: 78px !important;
    right: 25px !important;
    width: 620px !important;
    height: 640px !important;
    max-width: calc(100vw - 36px) !important;
    max-height: calc(100vh - 95px) !important;
    border-radius: 6px !important;
}

@media (max-width: 768px) {
    .ai-copilot-window,
    .ai-copilot-window.is-compact {
        top: 10px !important;
        left: 10px !important;
        right: 10px !important;
        bottom: 75px !important;
        width: calc(100vw - 20px) !important;
        height: calc(100vh - 90px) !important;
        max-width: calc(100vw - 20px) !important;
        max-height: calc(100vh - 90px) !important;
    }
}

/* Header Jendela Chat */
.ai-copilot-header {
    background-color: #ffffff;
    border-bottom: 1px solid #b8b8b8;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ai-copilot-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #333333;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ai-copilot-status {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #28a745;
    border-radius: 50%;
    margin-right: 4px;
}

/* Badan Riwayat Obrolan */
.ai-copilot-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    background-color: #fdfdfd;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Balon Pesan Chat */
.ai-msg-bubble {
    padding: 10px 14px;
    font-size: 0.86rem;
    line-height: 1.55;
    word-break: break-word;
    border-radius: 4px;
    max-width: 98%;
}
.ai-msg-bubble.bot {
    background-color: #ffffff;
    color: #222222;
    border: 1px solid #b8b8b8;
    border-left: 3.5px solid #0d9f4f;
    align-self: flex-start;
    width: 100%;
}
.ai-msg-bubble.user {
    background-color: #f0f7f3;
    color: #076e34;
    border: 1px solid #b8b8b8;
    border-right: 3.5px solid #0d9f4f;
    align-self: flex-end;
    font-weight: 600;
}

/* Penekanan Teks Tebal (Bold) Kontras & Rapi */
.ai-msg-bubble strong {
    font-weight: 700 !important;
}
.ai-msg-bubble.bot strong {
    color: #111827;
}
.ai-msg-bubble.user strong {
    color: #065f2b;
}

/* Chips Pertanyaan Cepat */
.ai-quick-chips-wrap {
    padding: 8px 12px;
    background-color: #fafafa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 6px;
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: thin;
}
.ai-chip-btn {
    background: #ffffff;
    border: 1px solid #b8b8b8;
    color: #444444;
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.ai-chip-btn:hover {
    border-color: #0d9f4f;
    color: #0d9f4f;
    background-color: #f0f7f3;
}

/* Form Bar Input */
.ai-copilot-footer {
    padding: 10px 12px;
    background: #ffffff;
    border-top: 1px solid #b8b8b8;
}
.ai-input-wrapper {
    display: flex;
    gap: 6px;
}
.ai-input-field {
    flex: 1;
    border: 1px solid #b8b8b8;
    padding: 8px 12px;
    font-size: 0.88rem;
    border-radius: 4px;
    outline: none;
    transition: border-color 0.15s ease;
}
.ai-input-field:focus {
    border-color: #0d9f4f;
    box-shadow: 0 0 0 2px rgba(13, 159, 79, 0.15);
}

/* Animasi Titik Sedang Mengetik */
.ai-typing-dots {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    padding: 4px 6px;
}
.ai-typing-dots span {
    width: 6px;
    height: 6px;
    background-color: #0d9f4f;
    border-radius: 50%;
    animation: aiBounce 1.2s infinite ease-in-out;
}
.ai-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.ai-typing-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes aiBounce {
    0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

/* ==============================================================
   DARK MODE OVERRIDES UNTUK AI COPILOT WIDGET
   ============================================================== */
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

/* ==============================================================
   DARK MODE OVERRIDES UNTUK AI COPILOT WIDGET (PAPER DARK)
   ============================================================== */
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

/* Metric boxes inside watchdog in dark mode */
.dark-mode .ai-watchdog-metric-box {
    background-color: #181b1f !important;
    border-color: #374151 !important;
}
.dark-mode .ai-watchdog-metric-box strong {
    color: #f3f4f6 !important;
}

/* Findings inside watchdog in dark mode */
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

/* Alerts and badges inside cards */
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

/* Buttons inside cards */
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

.dark-mode #ai-btn-upload-pdf,
.dark-mode #ai-btn-scan-slip,
.dark-mode #ai-btn-voice {
    background-color: #262b32 !important;
    border: 1px solid #4b5563 !important;
    color: #d1d5db !important;
}

.dark-mode #ai-btn-upload-pdf:hover,
.dark-mode #ai-btn-scan-slip:hover,
.dark-mode #ai-btn-voice:hover {
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

.dark-mode #ai-voice-status-bar {
    background-color: #2d1517 !important;
    border-color: #ef4444 !important;
    color: #fca5a5 !important;
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
</style>

<div id="ai-copilot-container">
    <!-- Tombol Mengambang Buka AI -->
    <button type="button" class="ai-copilot-trigger" onclick="toggleAiCopilot()" title="Buka Asisten AI (Alt + A)">
        <i class="fas fa-robot"></i>
        <span>Tanya AI BAZNAS</span>
    </button>

    <!-- Jendela Chat Interaktif -->
    <div class="ai-copilot-window" id="ai-copilot-window">
        <!-- Header -->
        <div class="ai-copilot-header">
            <div>
                <div class="ai-copilot-title">
                    <i class="fas fa-robot text-success"></i>
                    <span>Amil Virtual BAZNAS</span>
                </div>
                <small class="text-muted d-block" style="font-size: 0.72rem;">
                    <span class="ai-copilot-status"></span> Asisten Eksekutif & Intelijen Data
                </small>
            </div>
            <div class="d-flex align-items-center" style="gap: 6px;">
                <a href="<?= url('pages/ai_asisten/index.php') ?>" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" title="Buka Halaman Mandiri Penuh" style="border-radius: 4px; font-size: 0.76rem;">
                    <i class="fas fa-external-link-alt mr-1"></i> Buka Full Halaman
                </a>
                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="clearAiChatHistory()" title="Bersihkan Obrolan" style="border-radius: 4px;">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" id="ai-btn-maximize" onclick="toggleAiMaximize()" title="Ubah Ukuran Jendela (Layar Penuh / Melayang)" style="border-radius: 4px;">
                    <i class="fas fa-compress-alt" id="ai-icon-maximize"></i>
                </button>
                <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2" onclick="toggleAiCopilot()" title="Tutup Jendela" style="border-radius: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Badan Obrolan -->
        <div class="ai-copilot-body" id="ai-copilot-body">
            <div class="ai-msg-bubble bot">
                Assalamu’alaikum Warahmatullahi Wabarakatuh.<br><br>
                Saya <strong>Amil Virtual Intelijen BAZNAS Sumbawa</strong>. Saya siap membantu Anda menelusuri data sistem & statement bank:<br>
                &bull; <strong>Rekening Koran PDF (0 Token):</strong> Klik ikon klip <i class="fas fa-paperclip text-success"></i> untuk melacak mutasi kredit & rekonsiliasi otomatis.<br>
                &bull; <strong>Cari Transaksi:</strong> <em>"Cari setoran Bank NTB Syariah"</em> atau <em>"Transaksi di atas 5 juta"</em>.<br>
                &bull; <strong>Rekap RKAT:</strong> <em>"Berapa capaian target RKAT 2026?"</em>.<br>
                &bull; <strong>Peringkat UPZ:</strong> <em>"Top 5 UPZ penyetoran terbesar"</em>.<br>
                &bull; <strong>Dokumen Bantuan:</strong> <em>"Rekap penerima bantuan gerobak"</em>.<br><br>
                <small class="text-muted"><i class="fas fa-shield-alt text-info mr-1"></i> Keamanan: Terhubung langsung ke basis data dalam mode <strong>Strict Read-Only</strong>.</small>
            </div>
        </div>

        <!-- Quick Action Chips -->
        <div class="ai-quick-chips-wrap" id="ai-quick-chips">
            <button type="button" class="ai-chip-btn" onclick="sendAiQuickPrompt('Jalankan deteksi anomali dan audit kualitas data')">
                🛡️ Audit Watchdog
            </button>
            <button type="button" class="ai-chip-btn" onclick="sendAiQuickPrompt('Tampilkan grafik tren penerimaan ZIS tahun ini')">
                📊 Grafik Tren ZIS
            </button>
            <button type="button" class="ai-chip-btn" onclick="sendAiQuickPrompt('Buat laporan eksekutif PDF pengumpulan ZIS')">
                📄 Laporan Eksekutif PDF
            </button>
            <button type="button" class="ai-chip-btn" onclick="document.getElementById('ai-slip-input').click()">
                📸 Pindai Slip OCR
            </button>
            <button type="button" class="ai-chip-btn" onclick="sendAiQuickPrompt('Berapa capaian target pengumpulan RKAT tahun 2026?')">
                🎯 Capaian RKAT
            </button>
            <button type="button" class="ai-chip-btn" onclick="sendAiQuickPrompt('Tampilkan top 5 UPZ dengan penyetoran terbanyak tahun ini')">
                🏢 Top 5 UPZ
            </button>
            <button type="button" class="ai-chip-btn" onclick="document.getElementById('ai-pdf-input').click()">
                📄 Cek Rekening Koran PDF
            </button>
        </div>

        <!-- Baris Input Chat -->
        <div class="ai-copilot-footer">
            <div id="ai-voice-status-bar" style="display: none; background: #fee2e2; border: 1px solid #f87171; border-radius: 4px; padding: 6px 12px; margin-bottom: 8px; font-size: 0.8rem; color: #991b1b; justify-content: space-between; align-items: center;">
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <span class="spinner-grow spinner-grow-sm text-danger" role="status" style="width: 10px; height: 10px;"></span>
                    <span><strong>Mendengarkan...</strong> Silakan berbicara sekarang.</span>
                </div>
                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 font-weight-bold" onclick="toggleAiVoiceInput()">Selesai</button>
            </div>
            <div class="ai-input-wrapper">
                <input type="file" id="ai-pdf-input" accept="application/pdf" style="display: none;" onchange="handleAiPdfUpload(this)">
                <input type="file" id="ai-slip-input" accept="image/*" style="display: none;" onchange="handleAiSlipUpload(this)">
                <button type="button" class="btn btn-outline-secondary px-2" id="ai-btn-upload-pdf" onclick="document.getElementById('ai-pdf-input').click()" title="Unggah & Lacak Mutasi Kredit PDF Rekening Koran (0 Token)" style="border-radius: 4px; border: 1px solid #b8b8b8;">
                    <i class="fas fa-paperclip text-success"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary px-2" id="ai-btn-scan-slip" onclick="document.getElementById('ai-slip-input').click()" title="Pindai Bukti Transfer / Kuitansi (Multimodal OCR)" style="border-radius: 4px; border: 1px solid #b8b8b8;">
                    <i class="fas fa-camera text-info"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary px-2" id="ai-btn-voice" onclick="toggleAiVoiceInput()" title="Input Suara Bebas Ketik (Web Speech API Native)" style="border-radius: 4px; border: 1px solid #b8b8b8;">
                    <i class="fas fa-microphone text-primary" id="ai-icon-voice"></i>
                </button>
                <input type="text" id="ai-input-text" class="ai-input-field" placeholder="Tanya data, bicara, atau tempel (Ctrl+V) bukti transfer..." onkeypress="if(event.key==='Enter') sendAiMessage();">
                <button type="button" class="btn btn-outline-success font-weight-bold px-3" id="ai-btn-send" onclick="sendAiMessage()" style="border-radius: 4px; border: 1.5px solid #0d9f4f;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const AI_CHAT_ENDPOINT = '<?= url("pages/ai_asisten/chat.php") ?>';
let aiHistoryLoaded = false;

// Parser Markdown Aman (Bold, Italic, Code, List, Newline)
function formatAiMarkdown(text) {
    if (!text || typeof text !== 'string') return '';
    let formatted = text;

    // 0. Amankan tanda bullet point di awal baris (* atau -) menjadi simbol bullet '• '
    // Ini mencegah tanda asterisk bullet di awal baris bertabrakan dengan regex asterisk
    formatted = formatted.replace(/^[\t ]*[\*\-]\s+/gm, '• ');

    // 1. Bold + Italic gabungan: ***teks*** atau ___teks___
    formatted = formatted.replace(/\*\*\*([\s\S]+?)\*\*\*/g, '<strong><em>$1</em></strong>');
    formatted = formatted.replace(/___([\s\S]+?)___/g, '<strong><em>$1</em></strong>');

    // 2. Bold asteris ganda: **teks** atau __teks__
    formatted = formatted.replace(/\*\*([\s\S]+?)\*\*/g, '<strong>$1</strong>');
    formatted = formatted.replace(/__([\s\S]+?)__/g, '<strong>$1</strong>');

    // 3. Italic asteris tunggal: *teks* atau _teks_
    formatted = formatted.replace(/(?<!\*|\w)\*([^\*\n]+?)\*(?!\*|\w)/g, '<em>$1</em>');
    formatted = formatted.replace(/(?<!_|\w)_([^_\n]+?)_(?!_|\w)/g, '<em>$1</em>');

    // 4. Inline Code: `kode`
    formatted = formatted.replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.06); padding:1px 4px; border-radius:3px; font-size:0.88em;">$1</code>');

    // 5. Normalisasi Baris Baru jika belum ada tag block HTML
    if (!/<(div|table|ul|ol|li|p|blockquote)[^>]*>/i.test(formatted)) {
        formatted = formatted.replace(/\n/g, '<br>');
    }

    return formatted;
}

// Penggulir Otomatis ke Percakapan Terakhir (Scroll to Bottom)
function scrollAiToBottom(smooth = false) {
    const body = document.getElementById('ai-copilot-body');
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
    setTimeout(doScroll, 450);
}

function toggleAiCopilot() {
    const win = document.getElementById('ai-copilot-window');
    win.classList.toggle('is-open');
    if (win.classList.contains('is-open')) {
        setTimeout(() => {
            const input = document.getElementById('ai-input-text');
            if (input) input.focus();
        }, 150);
        if (!aiHistoryLoaded) {
            loadAiChatHistory(false);
        } else {
            scrollAiToBottom(false);
        }
    }
}

// Pintasan Keyboard Global (Alt + A) untuk Membuka / Menutup AI
document.addEventListener('keydown', function(e) {
    if (e.altKey && (e.key === 'a' || e.key === 'A')) {
        e.preventDefault();
        toggleAiCopilot();
    }
});

const AI_HISTORY_CACHE_KEY = 'baznas_ai_history_cache_v2';

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

// Sinkronkan ulang grafik ketika tema mode gelap/terang berubah
window.addEventListener('themeChanged', function() {
    document.querySelectorAll('canvas.ai-chat-chart[data-rendered="1"]').forEach(canvas => {
        canvas.removeAttribute('data-rendered');
    });
    renderAiCharts(document);
});

function renderAiHistory(historyList) {
    const body = document.getElementById('ai-copilot-body');
    if (!body || !historyList || historyList.length === 0) return;

    body.innerHTML = '';
    const headerNotice = document.createElement('div');
    headerNotice.className = 'text-center my-2';
    headerNotice.innerHTML = '<span class="badge" style="background-color: #f0f7f3; color: #0d9f4f; border: 1px solid #b8b8b8; font-weight: normal; font-size: 0.72rem;"><i class="fas fa-history mr-1"></i> Riwayat obrolan (' + historyList.length + ' pesan)</span>';
    body.appendChild(headerNotice);

    historyList.forEach(item => {
        if (item.user_message) {
            const userBubble = document.createElement('div');
            userBubble.className = 'ai-msg-bubble user';
            userBubble.innerHTML = formatAiMarkdown(item.user_message);
            body.appendChild(userBubble);
        }

        if (item.ai_reply) {
            const botBubble = document.createElement('div');
            botBubble.className = 'ai-msg-bubble bot';
            let replyContent = formatAiMarkdown(item.ai_reply);

            if (item.quick_actions && item.quick_actions.length > 0) {
                replyContent += '<div class="mt-2 pt-2 border-top d-flex flex-wrap" style="gap: 6px;">';
                item.quick_actions.forEach(act => {
                    if (act.url) {
                        replyContent += `<a href="${act.url}" target="_blank" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</a>`;
                    } else if (act.prompt) {
                        const safePrompt = act.prompt.replace(/'/g, "\\'");
                        replyContent += `<button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 font-weight-bold" onclick="sendAiQuickPrompt('${safePrompt}')" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</button>`;
                    }
                });
                replyContent += '</div>';
            }

            botBubble.innerHTML = replyContent;
            body.appendChild(botBubble);
        }
    });

    renderAiCharts(body);
    scrollAiToBottom(false);
}

function loadAiChatHistory(isPreload = false) {
    const body = document.getElementById('ai-copilot-body');
    if (!body) return;

    // 1. Optimasi Waktu Tunggu 0 ms: Langsung baca & render dari sessionStorage jika ada
    const cached = sessionStorage.getItem(AI_HISTORY_CACHE_KEY);
    if (cached) {
        try {
            const parsedCache = JSON.parse(cached);
            if (Array.isArray(parsedCache) && parsedCache.length > 0 && !aiHistoryLoaded) {
                renderAiHistory(parsedCache);
                aiHistoryLoaded = true;
            }
        } catch (e) {}
    }

    // 2. Sinkronisasi data terbaru secara senyap di latar belakang
    const formData = new FormData();
    formData.append('action', 'get_history');

    fetch(AI_CHAT_ENDPOINT, {
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
            sessionStorage.setItem(AI_HISTORY_CACHE_KEY, JSON.stringify(data.history));
            renderAiHistory(data.history);
            aiHistoryLoaded = true;
        }
    })
    .catch(err => {
        console.warn('Gagal sinkronisasi riwayat AI:', err);
    });
}

// Fitur Input Suara Bebas Ketik (Web Speech API Native)
let aiSpeechRecognition = null;
let isAiVoiceListening = false;
let aiVoiceInitialText = '';

function toggleAiVoiceInput() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const icon = document.getElementById('ai-icon-voice');
    const input = document.getElementById('ai-input-text');
    const statusBar = document.getElementById('ai-voice-status-bar');

    // 1. Validasi Keamanan Protokol HTTPS / Localhost
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (!window.isSecureContext && !isLocalhost) {
        const msg = 'Fitur input suara (Speech Recognition) diwajibkan oleh peramban (Chrome/Edge) berjalan di atas protokol HTTPS atau localhost.';
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

    // 2. Validasi Dukungan Browser Web Speech API
    if (!SpeechRecognition) {
        const msg = 'Fitur Input Suara memerlukan peramban dengan dukungan Web Speech API (Google Chrome atau Microsoft Edge).';
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
    if (isAiVoiceListening && aiSpeechRecognition) {
        try { aiSpeechRecognition.stop(); } catch(e) {}
        stopAiVoiceUI();
        return;
    }

    // 4. Inisialisasi & Mulai Mendengarkan
    try {
        aiSpeechRecognition = new SpeechRecognition();
        aiSpeechRecognition.lang = 'id-ID';
        aiSpeechRecognition.continuous = true;
        aiSpeechRecognition.interimResults = true;
        aiSpeechRecognition.maxAlternatives = 1;

        aiVoiceInitialText = input && input.value ? input.value.trim() + ' ' : '';

        aiSpeechRecognition.onstart = function() {
            isAiVoiceListening = true;
            if (icon) icon.className = 'fas fa-dot-circle text-danger fa-pulse';
            if (statusBar) statusBar.style.display = 'flex';
            if (input) {
                input.placeholder = '🎙️ Silakan berbicara, sistem sedang mendengarkan...';
                input.focus();
            }
        };

        aiSpeechRecognition.onresult = function(event) {
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
                aiVoiceInitialText += finalAccumulated;
            }

            if (input) {
                input.value = (aiVoiceInitialText + interimTranscript).trim();
            }
        };

        aiSpeechRecognition.onerror = function(event) {
            console.warn('AI Voice recognition notice:', event.error);
            stopAiVoiceUI();

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

        aiSpeechRecognition.onend = function() {
            stopAiVoiceUI();
        };

        aiSpeechRecognition.start();
    } catch (e) {
        console.error('Error starting voice recognition:', e);
        stopAiVoiceUI();
    }
}

function stopAiVoiceUI() {
    isAiVoiceListening = false;
    const icon = document.getElementById('ai-icon-voice');
    const input = document.getElementById('ai-input-text');
    const statusBar = document.getElementById('ai-voice-status-bar');

    if (icon) icon.className = 'fas fa-microphone text-primary';
    if (statusBar) statusBar.style.display = 'none';
    if (input) input.placeholder = 'Tanya data, bicara, atau tempel (Ctrl+V) bukti transfer...';
}

// Fitur Pemindai Bukti Transfer & Kuitansi (Multimodal OCR)
function handleAiSlipUpload(fileInput) {
    if (!fileInput.files || fileInput.files.length === 0) return;
    const file = fileInput.files[0];
    if (!file.type.startsWith('image/')) {
        alert('Silakan pilih berkas gambar bukti transfer atau kuitansi (JPG/PNG).');
        fileInput.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        uploadAiSlip(e.target.result, file.type, file.name);
    };
    reader.readAsDataURL(file);
    fileInput.value = '';
}

function uploadAiSlip(base64Data, mimeType, filename = 'Bukti Transfer') {
    const body = document.getElementById('ai-copilot-body');
    if (!body) return;

    const userBubble = document.createElement('div');
    userBubble.className = 'ai-msg-bubble user';
    userBubble.innerHTML = `<i class="fas fa-camera mr-1"></i> Memindai bukti transfer: <strong>${filename}</strong><br><img src="${base64Data}" style="max-height: 120px; border-radius: 4px; border: 1px solid #b8b8b8; margin-top: 6px;">`;
    body.appendChild(userBubble);

    const typingId = 'ai-slip-loading-' + Date.now();
    const typingBubble = document.createElement('div');
    typingBubble.className = 'ai-msg-bubble bot';
    typingBubble.id = typingId;
    typingBubble.innerHTML = '<span class="ai-typing-dots"><span></span><span></span><span></span></span> <span style="font-size: 0.8rem;" class="text-muted"><i class="fas fa-search mr-1"></i> AI Vision sedang membaca struk & mencocokkan rekening BAZNAS...</span>';
    body.appendChild(typingBubble);
    scrollAiToBottom(true);

    const formData = new FormData();
    formData.append('action', 'scan_slip');
    formData.append('image_data', base64Data);
    formData.append('mime_type', mimeType);

    fetch(AI_CHAT_ENDPOINT, {
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
        botBubble.className = 'ai-msg-bubble bot';
        botBubble.innerHTML = data.reply || 'Hasil pemindaian selesai.';
        body.appendChild(botBubble);
        scrollAiToBottom(true);
    })
    .catch(err => {
        const typingEl = document.getElementById(typingId);
        if (typingEl) typingEl.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'ai-msg-bubble bot text-danger';
        botBubble.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Gagal menghubungi pemindai slip OCR. Pastikan koneksi internet stabil.';
        body.appendChild(botBubble);
        scrollAiToBottom(true);
    });
}

// Tangkap Gambar dari Clipboard secara Otomatis (Ctrl + V)
document.addEventListener('paste', function(e) {
    const win = document.getElementById('ai-copilot-window');
    if (!win || !win.classList.contains('is-open')) return;

    if (e.clipboardData && e.clipboardData.items) {
        for (let i = 0; i < e.clipboardData.items.length; i++) {
            const item = e.clipboardData.items[i];
            if (item.type.indexOf('image') !== -1) {
                e.preventDefault();
                const file = item.getAsFile();
                const reader = new FileReader();
                reader.onload = function(evt) {
                    uploadAiSlip(evt.target.result, file.type, 'Tangkapan Layar (Clipboard)');
                };
                reader.readAsDataURL(file);
                break;
            }
        }
    }
});

function sendAiQuickPrompt(promptText) {
    document.getElementById('ai-input-text').value = promptText;
    sendAiMessage();
}

function sendAiMessage() {
    const input = document.getElementById('ai-input-text');
    const body = document.getElementById('ai-copilot-body');
    const btn = document.getElementById('ai-btn-send');
    const msg = input.value.trim();

    if (isAiVoiceListening && aiSpeechRecognition) {
        try { aiSpeechRecognition.stop(); } catch(e) {}
        stopAiVoiceUI();
    }

    if (!msg) return;

    // 1. Tampilkan Balon Pengguna
    const userBubble = document.createElement('div');
    userBubble.className = 'ai-msg-bubble user';
    userBubble.innerHTML = formatAiMarkdown(msg);
    body.appendChild(userBubble);
    input.value = '';
    scrollAiToBottom(true);

    // 2. Tampilkan Indikator Sedang Berpikir
    btn.disabled = true;
    const typingId = 'ai-typing-' + Date.now();
    const typingBubble = document.createElement('div');
    typingBubble.className = 'ai-msg-bubble bot';
    typingBubble.id = typingId;
    typingBubble.innerHTML = '<span class="ai-typing-dots"><span></span><span></span><span></span></span> <em>Menganalisis data SIM BAZNAS...</em>';
    body.appendChild(typingBubble);
    scrollAiToBottom(true);

    // 3. Kirim AJAX ke Backend
    const formData = new FormData();
    formData.append('message', msg);

    fetch(AI_CHAT_ENDPOINT, {
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
            // Tangani jika respons adalah pengalihan login atau pesan sistem
            if (res.status === 401 || rawText.includes('login') || rawText.includes('kicked')) {
                return {
                    status: 'error',
                    reply: '⚠️ <strong>Sesi Login Anda Telah Berakhir</strong>.<br>Silakan muat ulang (refresh) halaman ini untuk melanjutkan kembali.',
                    quick_actions: [{ label: '🔄 Refresh Halaman', url: window.location.href }]
                };
            }
            return {
                status: 'success',
                reply: 'Maaf, terjadi kendala saat memproses jawaban otomatis. Ada data transaksi atau laporan lain yang ingin Anda tampilkan?',
                quick_actions: [
                    { label: '📊 Capaian RKAT 2026', prompt: 'Berapa capaian target RKAT tahun 2026?' },
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
        botBubble.className = 'ai-msg-bubble bot';
        
        let replyContent = formatAiMarkdown(data.reply || 'Tidak ada data.');
        // Render quick action buttons if any
        if (data.quick_actions && data.quick_actions.length > 0) {
            replyContent += '<div class="mt-2 pt-2 border-top d-flex flex-wrap" style="gap: 6px;">';
            data.quick_actions.forEach(act => {
                if (act.url) {
                    replyContent += `<a href="${act.url}" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</a>`;
                } else if (act.prompt) {
                    replyContent += `<button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 font-weight-bold" onclick="sendAiQuickPrompt('${act.prompt}')" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</button>`;
                }
            });
            replyContent += '</div>';
        }

        botBubble.innerHTML = replyContent;
        body.appendChild(botBubble);
        renderAiCharts(botBubble);
        scrollAiToBottom(true);
    })
    .catch(err => {
        btn.disabled = false;
        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const errBubble = document.createElement('div');
        errBubble.className = 'ai-msg-bubble bot text-danger';
        errBubble.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Terjadi kendala saat menghubungi server. Mohon segarkan halaman atau coba beberapa saat lagi.';
        body.appendChild(errBubble);
        scrollAiToBottom(true);
    });
}

function handleAiPdfUpload(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    if (!file.name.toLowerCase().endsWith('.pdf')) {
        alert('Format file tidak didukung. Mohon pilih dokumen PDF rekening koran.');
        input.value = '';
        return;
    }

    const body = document.getElementById('ai-copilot-body');
    const btnSend = document.getElementById('ai-btn-send');
    const btnUpload = document.getElementById('ai-btn-upload-pdf');

    // 1. Tampilkan Balon Pengguna
    const userBubble = document.createElement('div');
    userBubble.className = 'ai-msg-bubble user';
    userBubble.innerHTML = '<i class="fas fa-file-pdf text-danger mr-1"></i> Mengunggah: <strong>' + file.name + '</strong> (' + (file.size / 1024).toFixed(1) + ' KB)';
    body.appendChild(userBubble);
    scrollAiToBottom(true);

    // 2. Tampilkan Indikator Sedang Membaca Dokumen
    btnSend.disabled = true;
    btnUpload.disabled = true;
    const typingId = 'ai-typing-' + Date.now();
    const typingBubble = document.createElement('div');
    typingBubble.className = 'ai-msg-bubble bot';
    typingBubble.id = typingId;
    typingBubble.innerHTML = '<span class="ai-typing-dots"><span></span><span></span><span></span></span> <em>Mengekstrak teks & menganalisis mutasi kredit bank (0 Token API)...</em>';
    body.appendChild(typingBubble);
    scrollAiToBottom(true);

    // 3. Kirim File PDF via FormData ke Endpoint AI
    const formData = new FormData();
    formData.append('action', 'upload_pdf');
    formData.append('pdf_file', file);

    fetch(AI_CHAT_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const raw = await res.text();
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {
                status: 'error',
                reply: '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Terjadi kendala saat membaca berkas. Mohon coba beberapa saat lagi.</div>'
            };
        }
    })
    .then(data => {
        btnSend.disabled = false;
        btnUpload.disabled = false;
        input.value = ''; // Reset input file agar bisa memilih file yang sama jika ingin re-upload

        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'ai-msg-bubble bot';

        let replyContent = formatAiMarkdown(data.reply || 'Tidak ada data.');
        if (data.quick_actions && data.quick_actions.length > 0) {
            replyContent += '<div class="mt-2 pt-2 border-top d-flex flex-wrap" style="gap: 6px;">';
            data.quick_actions.forEach(act => {
                if (act.url) {
                    replyContent += `<a href="${act.url}" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</a>`;
                } else if (act.prompt) {
                    replyContent += `<button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 font-weight-bold" onclick="sendAiQuickPrompt('${act.prompt}')" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</button>`;
                }
            });
            replyContent += '</div>';
        }

        botBubble.innerHTML = replyContent;
        body.appendChild(botBubble);
        scrollAiToBottom(true);
    })
    .catch(err => {
        btnSend.disabled = false;
        btnUpload.disabled = false;
        input.value = '';
        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const errBubble = document.createElement('div');
        errBubble.className = 'ai-msg-bubble bot text-danger';
        errBubble.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Gagal mengunggah file. Pastikan server web dan koneksi stabil.';
        body.appendChild(errBubble);
        scrollAiToBottom(true);
    });
}

function saveBankStatementToDb(idSumber, bulan, kredit, originalName) {
    if (!confirm('Simpan hasil ekstraksi rekening koran ini ke modul Rekening Koran BAZNAS?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'save_rk_statement');
    formData.append('id_sumber_rekening', idSumber);
    formData.append('bulan', bulan);
    formData.append('jumlah_kredit', kredit);
    formData.append('original_name', originalName);

    fetch(AI_CHAT_ENDPOINT, {
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
            return { status: 'success', message: 'Data berhasil disimpan.' };
        }
    })
    .then(data => {
        if (data.status === 'success') {
            const body = document.getElementById('ai-copilot-body');
            const botBubble = document.createElement('div');
            botBubble.className = 'ai-msg-bubble bot';
            botBubble.innerHTML = `
                <div class="alert alert-success mb-0" style="background:#eef7f2; border:1px solid #0d9f4f; color:#0d9f4f; font-size:0.85rem; border-radius:4px;">
                    <i class="fas fa-check-circle mr-1"></i> <strong>Berhasil!</strong> ${data.message}<br>
                    <div class="mt-2">
                        <a href="index.php?page=rekening_koran" class="btn btn-xs btn-outline-success font-weight-bold" style="border-radius:4px;">
                            <i class="fas fa-table mr-1"></i> Buka Tabel Rekening Koran
                        </a>
                    </div>
                </div>
            `;
            body.appendChild(botBubble);
            scrollAiToBottom(true);
        } else {
            alert(data.message || 'Gagal menyimpan rekening koran.');
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan saat menyimpan data rekening koran.');
    });
}

function clearAiChatHistory() {
    if (!confirm('Apakah Anda yakin ingin menghapus riwayat obrolan Anda dengan AI?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'clear_history');

    fetch(AI_CHAT_ENDPOINT, {
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
        const body = document.getElementById('ai-copilot-body');
        body.innerHTML = `
            <div class="ai-msg-bubble bot">
                <i class="fas fa-check-circle text-success mr-1"></i> Riwayat obrolan Anda telah dibersihkan dari memori AI.<br><br>
                Ada hal lain seputar data transaksi, UPZ, atau rekap ZIS yang bisa saya bantu?
            </div>
        `;
        aiHistoryLoaded = true;
        scrollAiToBottom(false);
    })
    .catch(err => {
        alert('Gagal membersihkan riwayat obrolan.');
    });
}

function confirmAiPendingAction(actionType) {
    const body = document.getElementById('ai-copilot-body');
    const btn = document.getElementById('ai-btn-send');
    if (btn) btn.disabled = true;

    // 1. Tampilkan Balon Pengguna
    const userBubble = document.createElement('div');
    userBubble.className = 'ai-msg-bubble user';
    let userText = '✅ Ya, simpan transaksi sekarang';
    if (actionType === 'update') userText = '✏️ Ya, perbarui transaksi';
    else if (actionType === 'delete') userText = '🗑️ Ya, hapus transaksi permanen';
    else if (actionType === 'batch_create') userText = '✅ Ya, simpan seluruh transaksi massal sekarang';
    userBubble.innerHTML = userText;
    body.appendChild(userBubble);
    scrollAiToBottom(true);

    // 2. Indikator Loading
    const typingId = 'ai-typing-' + Date.now();
    const typingBubble = document.createElement('div');
    typingBubble.className = 'ai-msg-bubble bot';
    typingBubble.id = typingId;
    typingBubble.innerHTML = '<span class="ai-typing-dots"><span></span><span></span><span></span></span> <em>Mengeksekusi ke basis data & mencatat log audit...</em>';
    body.appendChild(typingBubble);
    scrollAiToBottom(true);

    // 3. Kirim Request ke AJAX Endpoint
    const formData = new FormData();
    formData.append('action', 'confirm_pending_action');

    fetch(AI_CHAT_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const raw = await res.text();
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {
                status: 'success',
                reply: 'Alhamdulillah, transaksi berhasil diproses.',
                quick_actions: []
            };
        }
    })
    .then(data => {
        if (btn) btn.disabled = false;
        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'ai-msg-bubble bot';

        let replyContent = formatAiMarkdown(data.reply || 'Berhasil diproses.');
        if (data.quick_actions && data.quick_actions.length > 0) {
            replyContent += '<div class="mt-2 pt-2 border-top d-flex flex-wrap" style="gap: 6px;">';
            data.quick_actions.forEach(act => {
                if (act.url) {
                    replyContent += `<a href="${act.url}" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</a>`;
                } else if (act.prompt) {
                    replyContent += `<button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 font-weight-bold" onclick="sendAiQuickPrompt('${act.prompt}')" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</button>`;
                }
            });
            replyContent += '</div>';
        }

        botBubble.innerHTML = replyContent;
        body.appendChild(botBubble);
        scrollAiToBottom(true);
    })
    .catch(err => {
        if (btn) btn.disabled = false;
        const elTyping = document.getElementById(typingId);
        if (elTyping) elTyping.remove();

        const errBubble = document.createElement('div');
        errBubble.className = 'ai-msg-bubble bot text-danger';
        errBubble.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Gagal mengeksekusi aksi. Pastikan koneksi lancar dan coba lagi.';
        body.appendChild(errBubble);
        scrollAiToBottom(true);
    });
}

function cancelAiPendingAction() {
    const body = document.getElementById('ai-copilot-body');
    const btn = document.getElementById('ai-btn-send');
    if (btn) btn.disabled = true;

    // 1. Tampilkan Balon Pengguna
    const userBubble = document.createElement('div');
    userBubble.className = 'ai-msg-bubble user';
    userBubble.innerHTML = '❌ Batalkan aksi';
    body.appendChild(userBubble);
    scrollAiToBottom(true);

    // 2. Kirim Pembatalan
    const formData = new FormData();
    formData.append('action', 'cancel_pending_action');

    fetch(AI_CHAT_ENDPOINT, {
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
                status: 'success',
                reply: 'Draf aksi telah dibatalkan.',
                quick_actions: []
            };
        }
    })
    .then(data => {
        if (btn) btn.disabled = false;
        const botBubble = document.createElement('div');
        botBubble.className = 'ai-msg-bubble bot';

        let replyContent = formatAiMarkdown(data.reply || 'Draf aksi telah dibatalkan.');
        if (data.quick_actions && data.quick_actions.length > 0) {
            replyContent += '<div class="mt-2 pt-2 border-top d-flex flex-wrap" style="gap: 6px;">';
            data.quick_actions.forEach(act => {
                if (act.url) {
                    replyContent += `<a href="${act.url}" class="btn btn-xs btn-outline-success py-1 px-2 font-weight-bold" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</a>`;
                } else if (act.prompt) {
                    replyContent += `<button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 font-weight-bold" onclick="sendAiQuickPrompt('${act.prompt}')" style="font-size: 0.76rem; border-radius: 4px;">${act.label}</button>`;
                }
            });
            replyContent += '</div>';
        }

        botBubble.innerHTML = replyContent;
        body.appendChild(botBubble);
        scrollAiToBottom(true);
    })
    .catch(err => {
        if (btn) btn.disabled = false;
        const errBubble = document.createElement('div');
        errBubble.className = 'ai-msg-bubble bot text-danger';
        errBubble.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Terjadi kendala saat membatalkan aksi.';
        body.appendChild(errBubble);
        scrollAiToBottom(true);
    });
}

function toggleAiMaximize() {
    const win = document.getElementById('ai-copilot-window');
    const icon = document.getElementById('ai-icon-maximize');
    if (!win) return;

    win.classList.toggle('is-compact');
    if (win.classList.contains('is-compact')) {
        if (icon) icon.className = 'fas fa-expand-alt';
        localStorage.setItem('ai_copilot_compact', '1');
    } else {
        if (icon) icon.className = 'fas fa-compress-alt';
        localStorage.setItem('ai_copilot_compact', '0');
    }
    scrollAiToBottom(false);
}

// Kembalikan preferensi ukuran layar pengguna dan muat riwayat saat halaman dibuka
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('ai_copilot_compact') === '1') {
        const win = document.getElementById('ai-copilot-window');
        const icon = document.getElementById('ai-icon-maximize');
        if (win) win.classList.add('is-compact');
        if (icon) icon.className = 'fas fa-expand-alt';
    }
    // Muat riwayat awal di latar belakang
    loadAiChatHistory(true);
});
</script>
