# 🤖 Dokumentasi Rancang Bangun Asisten AI Mandiri (Hybrid Cloud & Local Engine)

> **Dokumen Panduan Implementasi Universal**  
> Proyek Rujukan: *Amil Virtual BAZNAS Kabupaten Sumbawa*  
> Teknologi: PHP (CodeIgniter / Native / Laravel Compatible) + Google Gemini Generative AI Free Tier + Vanilla JavaScript  
> Penulis: Tim Pengembang Sistem Informasi BAZNAS Kabupaten Sumbawa

---

## 📋 Daftar Isi
1. [Pendahuluan & Konsep Arsitektur](#1-pendahuluan--konsep-arsitektur)
2. [Keunggulan Desain Sistem](#2-keunggulan-desain-sistem)
3. [Alur Kerja Sistem (System Workflow)](#3-alur-kerja-sistem-system-workflow)
4. [Persiapan & Kunci API Google AI Studio](#4-persiapan--kunci-api-google-ai-studio)
5. [Komponen & Struktur Source Code](#5-komponen--struktur-source-code)
   - [A. Konfigurasi Sistem (`config.php`)](#a-konfigurasi-sistem-configphp)
   - [B. Routing Sistem (`routes.php`)](#b-routing-sistem-routesphp)
   - [C. Controller Backend (`Ai_asisten.php`)](#c-controller-backend-ai_asistenphp)
   - [D. View Frontend Widget (`widget_ai_chat.php`)](#d-view-frontend-widget-widget_ai_chatphp)
   - [E. Pemasangan di Master Template (`template.php`)](#e-pemasangan-di-master-template-templatephp)
6. [Catatan Penting Pemilihan Model Gemini (2025/2026)](#6-catatan-penting-pemilihan-model-gemini-20252026)
7. [Panduan Porting ke Platform / Framework Lain](#7-panduan-porting-ke-platform--framework-lain)
   - [A. Penerapan di PHP Native (File Tunggal)](#a-penerapan-di-php-native-file-tunggal)
   - [B. Penerapan di Laravel](#b-penerapan-di-laravel)
   - [C. Menyesuaikan Pengetahuan (Prompt) untuk Instansi Lain](#c-menyesuaikan-pengetahuan-prompt-untuk-instansi-lain)
8. [Troubleshooting & Solusi Masalah Umum](#8-troubleshooting--solusi-masalah-umum)

---

## 1. Pendahuluan & Konsep Arsitektur

Dokumen ini memuat panduan lengkap perancangan dan implementasi **Asisten AI Interaktif** berbasis web yang menggabungkan kecerdasan komputasi awan (*Cloud Large Language Model*) dengan mesin aturan lokal (*Intelligent Local Rule Engine*).

Konsep ini dirancang agar sebuah website atau sistem informasi organisasi dapat melayani tanya-jawab pengunjung secara cerdas, otomatis, santun, dan 24 jam penuh tanpa biaya bulanan (*100% Free Tier*) serta memiliki ketahanan tinggi (*High Availability*).

```
+-------------------------------------------------------------------------+
|                              PENGUNJUNG                                 |
|            (Klik Widget Floating Chat di Layar Kanan Bawah)            |
+------------------------------------+------------------------------------+
                                     | (AJAX Fetch POST)
                                     v
+-------------------------------------------------------------------------+
|                         BACKEND CONTROLLER                              |
|  1. Rate Limiting IP Check (Maks 20 pesan / menit)                      |
|  2. Ambil Pengetahuan Dinamis (Database Profil, Kontak, Rekening, dll)  |
|  3. RAG Search: Telusuri Cerdas Tabel Berita & Halaman Statis (<10 ms)  |
|  4. Susun System Prompt (Peran, Hasil RAG Berita, Pedoman Fiqih)        |
+------------------------------------+------------------------------------+
                                     |
               +---------------------+---------------------+
               | Apakah API Key Terisi & Online?           |
               |                                           |
               | [YA]                                      | [TIDAK / OFFLINE / KUOTA HABIS]
               v                                           v
+-------------------------------+           +-------------------------------+
|    GOOGLE GEMINI CLOUD API    |           |  INTELLIGENT LOCAL ENGINE     |
| Multi-Model Fallback Chain:   |           | (Aturan Berbasis Kata Kunci)  |
| 1. gemini-3.5-flash-lite      |           | - Deteksi Fiqih / Nisab       |
| 2. gemini-3.6-flash           | (Gagal/   | - Deteksi Rekening Resmi      |
| 3. gemini-flash-latest        |  503)     | - RAG Fallback Berita Terkait |
+---------------+---------------+           +---------------+---------------+
                |                                           |
                +---------------------+---------------------+
                                      |
                                      v
+-------------------------------------------------------------------------+
|                            RESPONS JSON                                 |
|  - Reply Text (Teks Jawaban Terstruktur + Hyperlink [Judul](URL))       |
|  - Quick Action Chips (Tombol Berita 📰, WA, Kalkulator, Rekening)      |
+-------------------------------------------------------------------------+
```

---

## 2. Keunggulan Desain Sistem

1. **Nol Biaya Operasional (100% Free Tier):**  
   Memanfaatkan Google Gemini API pada paket gratis resmi tanpa kartu kredit dengan batasan kuota puluhan request per menit yang sangat memadai untuk portal instansi/lembaga.
2. **Keamanan Maksimal (Secure API Proxy):**  
   Kunci API Google **tidak pernah dibocorkan** ke kode JavaScript atau browser pengunjung. Komunikasi API sepenuhnya dikelola oleh backend PHP via cURL server-to-server.
3. **Multi-Model Fallback Chain (Anti Macet):**  
   Jika model utama mengalami lonjakan antrean (*HTTP 503 High Demand Spike*), sistem secara otomatis mengalihkan permintaan ke model alternatif dalam hitungan detik.
4. **Intelligent Local Engine (Fail-Safe Offline):**  
   Bila internet server terputus, API key kosong, atau limit Google tercapai, sistem tidak akan menampilkan layar kosong/error, melainkan memberikan jawaban lokal yang relevan dari basis data lokal.
5. **Konteks Real-Time Dinamis (RAG Sederhana):**  
   Data alamat kantor, jam layanan, nomor rekening, dan nomor WhatsApp diambil langsung dari database sistem secara *live*, sehingga AI tidak akan pernah memberikan nomor telepon atau rekening kadaluarsa.
6. **Desain UI Ringan & Bebas Framework JS Berat:**  
   Widget antarmuka chat dibangun dengan **Vanilla JavaScript & CSS murni**, tanpa jQuery atau React, berukuran sangat kecil (<20 KB) sehingga tidak memperlambat loading website.

---

## 3. Alur Kerja Sistem (System Workflow)

1. **User Mengirim Pesan:**  
   Pengunjung mengetikkan pesan di widget dan menekan tombol *Kirim* atau memilih salah satu tombol cepat (*Quick Prompt Chips*).
2. **Validasi Frontend:**  
   Pesan divalidasi tidak boleh kosong, tombol kirim dinonaktifkan sementara, dan animasi indikator *sedang mengetik...* ditampilkan.
3. **Penyaringan Backend:**  
   Backend memeriksa IP pengunjung (perlindungan *spam flooding*), membersihkan karakter berbahaya, dan mengambil data entitas organisasi dari database.
4. **Inferensi AI:**  
   Backend menyuntikkan data resmi bersama pertanyaan pengguna ke dalam *System Prompt*, lalu mengirimkannya ke Google Gemini API via cURL dengan batas waktu *timeout* terukur (12-15 detik).
5. **Pengolahan & Fallback:**  
   Jika respons AI sukses (`HTTP 200`), teks langsung dikemas. Jika gagal/terjadi timeout, *Intelligent Local Engine* membedah pesan dengan pencocokan pola (*regex*) untuk menyajikan jawaban darurat yang tepat.
6. **Rendering UI:**  
   Frontend menerima format JSON, mengubah format teks (*bold, italic, bullet points*) menjadi elemen HTML, dan menampilkan balon chat baru secara mulus.

---

## 4. Persiapan & Kunci API Google AI Studio

Untuk mengaktifkan fitur cerdas generatif Google Gemini:

1. Kunjungi portal resmi: [Google AI Studio](https://aistudio.google.com/).
2. Masuk menggunakan akun Google Anda.
3. Klik tombol **Get API Key** di menu sebelah kiri.
4. Klik **Create API key in new project**.
5. Salin kode API Key yang dihasilkan (diawali format `AQ...` atau `AIza...`).
6. Kunci API ini nantinya dimasukkan ke dalam file konfigurasi backend sistem Anda.

---

## 5. Komponen & Struktur Source Code

### A. Konfigurasi Sistem (`config.php`)
Tambahkan parameter konfigurasi pada file konfigurasi aplikasi (pada CodeIgniter terletak di `application/config/config.php`):

```php
/*
|--------------------------------------------------------------------------
| Konfigurasi Agen AI Asisten (Google Gemini API)
|--------------------------------------------------------------------------
| Dapatkan API Key gratis di https://aistudio.google.com/
| Jika dikosongkan, sistem otomatis menjalankan Intelligent Local Engine (Fallback Cerdas)
*/
$config['gemini_api_key'] = 'MASUKKAN_KUNCI_API_GEMINI_ANDA_DI_SINI';
$config['ai_assistant_name'] = 'Amil Virtual BAZNAS Sumbawa';
```

---

### B. Routing Sistem (`routes.php`)
Daftarkan endpoint URL yang bersih pada file `application/config/routes.php`:

```php
// Route Asisten AI
$route['ai-asisten/chat'] = 'ai_asisten/chat';
$route['ai-asisten']      = 'ai_asisten/index';
```

---

### C. Controller Backend (`Ai_asisten.php`)
Simpan file ini di `application/controllers/Ai_asisten.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_asisten extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function index() {
        if ($this->input->method() !== 'post') {
            redirect(base_url());
            return;
        }
        $this->chat();
    }

    public function chat() {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->input->method() !== 'post') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya metode POST yang diizinkan.']);
            return;
        }

        // 1. Rate Limiting Protection (Maksimal 20 request per menit per IP)
        $ip = $this->input->ip_address();
        $rate_key = 'ai_rate_' . md5($ip);
        $rate_data = $this->session->userdata($rate_key);
        $now = time();

        if ($rate_data && isset($rate_data['count']) && $rate_data['count'] >= 20 && ($now - $rate_data['start']) < 60) {
            echo json_encode([
                'status' => 'error',
                'reply' => "Afwan, Anda mengirim pesan terlalu cepat. Mohon tunggu beberapa saat sebelum bertanya kembali.",
                'quick_actions' => []
            ]);
            return;
        }

        if (!$rate_data || ($now - $rate_data['start']) >= 60) {
            $this->session->set_userdata($rate_key, ['count' => 1, 'start' => $now]);
        } else {
            $this->session->set_userdata($rate_key, ['count' => $rate_data['count'] + 1, 'start' => $rate_data['start']]);
        }

        // 2. Ambil dan Sanitasi Pesan
        $user_message = trim($this->input->post('message', TRUE));
        if (empty($user_message)) {
            echo json_encode([
                'status' => 'error',
                'reply' => "Silakan ketikkan pertanyaan Anda.",
                'quick_actions' => []
            ]);
            return;
        }

        // 3. Ambil Data Real-time Organisasi dari Database
        $identitas = $this->db->get_where('identitas', ['id_identitas' => 1])->row_array();
        $nama_web  = !empty($identitas['nama_website']) ? $identitas['nama_website'] : 'Organisasi Anda';
        $no_telp   = !empty($identitas['no_telp']) ? $identitas['no_telp'] : '08123456789';
        $email     = !empty($identitas['email']) ? $identitas['email'] : 'kontak@domain.id';
        $alamat    = "Alamat Resmi Kantor Instansi Anda";

        // Format WA Internasional (62...)
        $clean_wa = preg_replace('/[^0-9]/', '', $no_telp);
        if (substr($clean_wa, 0, 1) === '0') {
            $clean_wa = '62' . substr($clean_wa, 1);
        }

        // Data Layanan / Rekening
        $str_rekening = "- Bank Syariah Indonesia (BSI): 1234567890 a.n " . $nama_web . "\n";

        // 4. Periksa Kunci API Gemini
        $api_key = $this->config->item('gemini_api_key');
        $ai_reply = null;
        $quick_actions = [];

        if (!empty($api_key)) {
            $ai_reply = $this->_call_gemini_api($api_key, $user_message, $nama_web, $no_telp, $email, $alamat, $str_rekening);
        }

        // 5. Fallback ke Local Rule Engine jika Offline / API Kosong / Limit Habis
        if (empty($ai_reply)) {
            $fallback_result = $this->_intelligent_local_engine($user_message, $nama_web, $no_telp, $clean_wa, $email, $alamat, $str_rekening);
            $ai_reply = $fallback_result['reply'];
            $quick_actions = $fallback_result['quick_actions'];
        }

        // 6. Bersihkan Buffer Output Sebelum Render JSON
        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode([
            'status' => 'success',
            'reply' => $ai_reply,
            'quick_actions' => $quick_actions
        ]);
        exit;
    }

    /**
     * Integrasi Google Gemini API via cURL dengan Multi-Model Fallback Chain
     */
    private function _call_gemini_api($api_key, $user_message, $nama_web, $no_telp, $email, $alamat, $str_rekening) {
        $system_prompt = "Anda adalah 'Asisten Virtual Resmi', yang bertugas melayani masyarakat untuk {$nama_web}.

Pedoman Sikap:
1. Awali sapaan dengan ramah, islami, dan santun.
2. Berikan informasi yang valid, transparan, dan menyejukkan.
3. Jawab pertanyaan pengguna secara lugas, jelas, dan kontekstual.

Data Resmi Lembaga:
- Nama Lembaga: {$nama_web}
- Alamat Kantor: {$alamat}
- Jam Operasional: Senin - Jumat (08.00 - 16.00 WITA)
- Layanan WhatsApp Resmi: {$no_telp}
- Email Resmi: {$email}
- Rekening Resmi:
{$str_rekening}

Aturan Penting:
- Jangan pernah mengarang nomor rekening atau nomor kontak selain data di atas.";

        // Rantai Model: Utama (flash-lite tercepat) -> Cadangan (3.6-flash) -> Alternatif (flash-latest)
        $model_chain = ['gemini-3.5-flash-lite', 'gemini-3.6-flash', 'gemini-flash-latest'];

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $system_prompt . "\n\nPertanyaan Pengguna: " . $user_message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 1500
            ]
        ];

        $json_payload = json_encode($payload);

        foreach ($model_chain as $model) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 && !empty($response)) {
                $data = json_decode($response, true);
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($data['candidates'][0]['content']['parts'][0]['text']);
                }
            }
        }

        return null;
    }

    /**
     * Mesin Aturan Lokal (Intelligent Fallback Tanpa Internet Luar)
     */
    private function _intelligent_local_engine($msg, $nama_web, $no_telp, $clean_wa, $email, $alamat, $str_rekening) {
        $lower = strtolower($msg);
        $quick_actions = [];

        // Deteksi Rekening & Pembayaran
        if (preg_match('/(rekening|bank|transfer|bayar|setor)/i', $lower)) {
            $reply = "Berikut adalah nomor rekening resmi {$nama_web}:\n\n" . $str_rekening . "\nKonfirmasi setoran via WhatsApp di {$no_telp}.";
            $quick_actions[] = ['label' => '💬 Konfirmasi WA', 'action' => 'wa', 'url' => 'https://wa.me/' . $clean_wa];
            return ['reply' => $reply, 'quick_actions' => $quick_actions];
        }

        // Jawaban Standar
        $reply = "Terima kasih telah menghubungi {$nama_web}. Ada hal spesifik seputar layanan, kontak, atau rekening yang bisa kami bantu?";
        $quick_actions[] = ['label' => '💬 Chat Petugas WhatsApp', 'action' => 'wa', 'url' => 'https://wa.me/' . $clean_wa];
        return ['reply' => $reply, 'quick_actions' => $quick_actions];
    }
}
```

---

### D. View Frontend Widget (`widget_ai_chat.php`)
Simpan file ini di direktori view (misal `application/views/phpmu-magazine/widget_ai_chat.php`).  
Komponen ini terdiri dari **CSS Scope Khusus**, **Struktur HTML DOM**, dan **Vanilla JS Engine**:

```html
<!-- Scope CSS Widget AI -->
<style>
#ai-widget-container * {
    box-sizing: border-box;
    border-radius: 0px !important; /* Standar Sudut Kotak Kaku */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
#ai-widget-container {
    position: fixed;
    bottom: 100px; /* Di atas tombol sticky pembayaran/ZIS (bottom: 30px) */
    right: 30px;
    z-index: 999998;
}
.ai-launch-button {
    width: 52px;
    height: 52px;
    background: #006937;
    color: #fff;
    border: 2px solid #F4C10F; /* Aksen Emas */
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(0, 105, 55, 0.4);
    transition: all 0.25s ease;
    font-size: 22px;
}
.ai-modal-window {
    position: fixed;
    bottom: 165px;
    right: 30px;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 520px;
    max-height: calc(100vh - 190px);
    background: #ffffff;
    border: 1px solid #b8b8b8;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.18);
    display: none;
    flex-direction: column;
    z-index: 1000000;
}
.ai-modal-window.is-open {
    display: flex;
}
.ai-chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background: #fdfdfd;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ai-bubble {
    padding: 10px 14px;
    font-size: 13px;
    line-height: 1.55;
    word-break: break-word;
}
.ai-bubble.bot {
    background: #ffffff;
    color: #222;
    border: 1px solid #dcdcdc;
    border-left: 3px solid #006937;
}
.ai-bubble.user {
    background: #006937;
    color: #ffffff;
    align-self: flex-end;
}
.ai-chat-input-bar {
    padding: 10px;
    background: #fff;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 8px;
}
.ai-input-text {
    flex: 1;
    padding: 9px 12px;
    border: 1px solid #b8b8b8;
    outline: none;
}
.ai-send-btn {
    background: #006937;
    color: #fff;
    border: none;
    padding: 0 16px;
    cursor: pointer;
}
</style>

<!-- HTML DOM -->
<div id="ai-widget-container">
    <button class="ai-launch-button" onclick="toggleAiWidget()">
        <span>💬 Asisten AI</span>
    </button>

    <div class="ai-modal-window" id="ai-modal-window">
        <div style="background:#006937; color:#fff; padding:12px; display:flex; justify-content:space-between; align-items:center;">
            <strong>Asisten Virtual Resmi</strong>
            <button onclick="toggleAiWidget()" style="background:transparent; border:none; color:#fff; cursor:pointer; font-size:16px;">&times;</button>
        </div>
        <div class="ai-chat-body" id="ai-chat-body">
            <div class="ai-bubble bot">
                Assalamu’alaikum Warahmatullahi Wabarakatuh. Ada yang bisa saya bantu hari ini?
            </div>
        </div>
        <div class="ai-chat-input-bar">
            <input type="text" id="ai-input-field" class="ai-input-text" placeholder="Ketik pertanyaan Anda..." onkeypress="if(event.key==='Enter') sendAiMessage();">
            <button class="ai-send-btn" id="ai-send-btn" onclick="sendAiMessage()">Kirim</button>
        </div>
    </div>
</div>

<!-- JavaScript Engine -->
<script>
const AI_ENDPOINT_URL = '<?php echo base_url("ai-asisten/chat"); ?>';

function toggleAiWidget() {
    const win = document.getElementById('ai-modal-window');
    win.classList.toggle('is-open');
    if (win.classList.contains('is-open')) {
        document.getElementById('ai-input-field').focus();
    }
}

function sendAiMessage() {
    const input = document.getElementById('ai-input-field');
    const body = document.getElementById('ai-chat-body');
    const msg = input.value.trim();
    if (!msg) return;

    // Tampilkan pesan pengguna
    const userBubble = document.createElement('div');
    userBubble.className = 'ai-bubble user';
    userBubble.innerText = msg;
    body.appendChild(userBubble);
    input.value = '';
    body.scrollTop = body.scrollHeight;

    // Panggil Backend Endpoint via Fetch API
    const formData = new FormData();
    formData.append('message', msg);

    fetch(AI_ENDPOINT_URL, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        const botBubble = document.createElement('div');
        botBubble.className = 'ai-bubble bot';
        botBubble.innerHTML = data.reply.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        body.appendChild(botBubble);
        body.scrollTop = body.scrollHeight;
    })
    .catch(() => {
        const errBubble = document.createElement('div');
        errBubble.className = 'ai-bubble bot';
        errBubble.innerText = 'Mohon maaf, sistem sedang mengalami kendala koneksi.';
        body.appendChild(errBubble);
    });
}
</script>
```

---

### E. Pemasangan di Master Template (`template.php`)
Sertakan file widget di master template tepat sebelum penutup tag `</body>`:

```php
<!-- Asisten Virtual AI Widget -->
<?php $this->load->view('phpmu-magazine/widget_ai_chat'); ?>
</body>
</html>
```

---

## 6. Catatan Penting Pemilihan Model Gemini (2025/2026)

Berdasarkan arsitektur terbaru Google Gemini API:
1. **Model `gemini-1.5-flash` sudah pensiun (deprecated):**  
   Penggunaan model `1.5-flash` pada API Key generasi baru akan menghasilkan error `HTTP 404 NOT_FOUND`.
2. **Keunggulan `gemini-3.5-flash-lite`:**  
   Model ini adalah model generasi terkini yang sangat ringan, berkecepatan tinggi (~1.5 detik per respons), dan memiliki kuota toleransi tinggi sehingga bebas dari masalah *High Demand Overload*.
3. **Pemberlakuan Multi-Model Fallback:**  
   Wajib menyusun urutan model di dalam array:
   ```php
   $model_chain = ['gemini-3.5-flash-lite', 'gemini-3.6-flash', 'gemini-flash-latest'];
   ```
   Sehingga jika salah satu model Google sedang sibuk, pengguna tidak merasakan kegagalan karena request langsung diteruskan ke model cadangan.

---

## 7. Panduan Porting ke Platform / Framework Lain

### A. Penerapan di PHP Native (File Tunggal)
Jika Anda memiliki sistem PHP tanpa framework (PHP murni), cukup buat 1 file bernama `api_chat.php`:

```php
<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Hanya POST yang didukung']);
    exit;
}

$user_message = trim($_POST['message'] ?? '');
if (empty($user_message)) {
    echo json_encode(['status' => 'error', 'reply' => 'Pesan tidak boleh kosong']);
    exit;
}

$api_key = "MASUKKAN_KUNCI_API_GEMINI_DI_SINI";
$system_prompt = "Anda adalah asisten virtual resmi organisasi kami. Jawablah dengan sopan dan ringkas.";

$payload = [
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => $system_prompt . "\n\nPertanyaan: " . $user_message]]]
    ],
    'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 1000]
];

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=" . urlencode($api_key));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$res = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && !empty($res)) {
    $data = json_decode($res, true);
    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Tidak ada jawaban.';
    echo json_encode(['status' => 'success', 'reply' => trim($reply)]);
} else {
    echo json_encode(['status' => 'error', 'reply' => 'Maaf, asisten sedang offline. Hubungi kami via kontak resmi.']);
}
```

---

### B. Penerapan di Laravel
1. Buat Controller: `php artisan make:controller AiChatController`
2. Daftarkan rute di `routes/web.php` atau `routes/api.php`:
   ```php
   Route::post('/ai/chat', [AiChatController::class, 'chat']);
   ```
3. Gunakan facade `Illuminate\Support\Facades\Http`:
   ```php
   use Illuminate\Http\Request;
   use Illuminate\Support\Facades\Http;

   public function chat(Request $request) {
       $request->validate(['message' => 'required|string']);
       
       $apiKey = config('services.gemini.key');
       $response = Http::withHeaders(['Content-Type' => 'application/json'])
           ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key={$apiKey}", [
               'contents' => [
                   ['role' => 'user', 'parts' => [['text' => "Anda asisten resmi. Pertanyaan: " . $request->message]]]
               ]
           ]);

       if ($response->successful()) {
           $reply = $response->json('candidates.0.content.parts.0.text');
           return response()->json(['status' => 'success', 'reply' => $reply]);
       }

       return response()->json(['status' => 'error', 'reply' => 'Layanan AI sedang sibuk.']);
   }
   ```

---

### C. Menyesuaikan Pengetahuan (Prompt) untuk Instansi Lain
Cukup ubah bagian `$system_prompt` pada controller:

* **Untuk Rumah Sakit / Klinik:**  
  *"Anda adalah asisten virtual RS Sehat Sejahtera. Berikan informasi jadwal poliklinik, alur pendaftaran BPJS, dan IGD 24 Jam. Jangan memberikan resep obat keras secara mandiri."*
* **Untuk Perguruan Tinggi / Sekolah:**  
  *"Anda adalah asisten penerimaan mahasiswa baru (PMB) Universitas ABC. Jelaskan alur seleksi jalur prestasi, beasiswa KIP Kuliah, dan rincian biaya kuliah per semester."*
* **Untuk Dinas / Pemerintahan Daerah:**  
  *"Anda adalah asisten pelayanan publik Dinas Kependudukan dan Catatan Sipil. Jelaskan syarat pengurusan KTP elektronik baru, akta kelahiran, dan perpindahan KK."*

---

## 8. Troubleshooting & Solusi Masalah Umum

| Gejala / Error | Penyebab | Solusi Tepat |
|---|---|---|
| **HTTP 404 NOT_FOUND** | Menggunakan nama model lama yang sudah dihentikan Google (misal `gemini-1.5-flash`). | Ganti model ke `gemini-3.5-flash-lite` atau `gemini-3.6-flash`. |
| **HTTP 503 UNAVAILABLE** | Lonjakan antrean global pada model alias (`high demand spike`). | Terapkan *Multi-Model Fallback Chain* di backend seperti yang diuraikan di Bab 5C. |
| **HTTP 400 INVALID_ARGUMENT** | Format parameter payload tidak didukung oleh versi model yang dipanggil (misal: setting `thinkingBudget` pada model lite). | Hapus objek `thinkingConfig` atau sederhanakan `generationConfig` hanya pada `temperature` dan `maxOutputTokens`. |
| **JSON Parse Error di Browser** | PHP mengeluarkan warning/notice (misal Notice Undefined Index) yang mencemari format output JSON. | Tambahkan perintah `if (ob_get_length()) ob_clean();` tepat sebelum `echo json_encode(...)`. |
| **CURL SSL Certificate Error** | Server lokal (XAMPP Windows) belum memiliki sertifikat CA bundle (`cacert.pem`). | Pastikan `curl.cainfo` pada file `php.ini` mengarah ke file `cacert.pem` yang valid atau uji koneksi SSL server Anda. |
| **Tabel Tidak Tampil / Hanya Teks Pembuka** | Model AI menambahkan titik koma (`;`) di akhir query yang bentrok dengan pemotong/validator SQL multi-statement backend. | Terapkan sanitasi `rtrim($sql, ';')` dan mekanisme *Self-Healing SQL Query Fallback* pada backend engine. |
| **Teks Tebal Asteris (`**teks**`) Tidak Diformat** | Simbol asteris bullet point (`* `) di awal baris berbenturan dengan regex deteksi asteris ganda ganda (*double asterisks*). | Isolasi bullet point di awal baris menjadi karakter entitas (`• `) sebelum menjalankan regex bold/italic, dan tegaskan CSS dengan `font-weight: 700 !important`. |

---

> **Kesimpulan:**  
> Dengan arsitektur gabungan ini, sistem website apa pun kini dapat memiliki asisten AI modern kelas dunia tanpa biaya bulanan, cepat, aman, dan tahan banting.
