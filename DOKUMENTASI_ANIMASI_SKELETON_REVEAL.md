# 📘 DOKUMENTASI & CETAK BIRU: UNIVERSAL SKELETON LOADER & REVEAL REMIX (`p14` MOTION ENGINE)
**Standar Animasi Antarmuka Sistem Informasi BAZNAS Kabupaten Sumbawa**

Dokumen ini merupakan cetak biru (*blueprint*) siap pakai untuk menerapkan animasi **"Skeleton loader and reveal remix" (`p14`)** secara universal ke sistem informasi BAZNAS lainnya (seperti SIM Penyaluran, SIM UPZ, Presensi, Geografis, dll.) tanpa merusak tata letak kartu, tabel DataTables, grafik ApexCharts, maupun dropdown Select2.

> 💡 **Cara Cepat Menggunakan Dokumen Ini di Sistem Lain:**
> Saat membuka project lain bersama AI Assistant, cukup salin atau referensikan berkas ini dan berikan instruksi:
> *"Terapkan animasi Universal Skeleton Loader & Reveal Remix sesuai cetak biru pada `DOKUMENTASI_ANIMASI_SKELETON_REVEAL.md` ke seluruh halaman sistem ini."*

---

## 🎯 1. PRINSIP ARSITEKTUR UTAMA

1. **Preservasi Motion Murni (`p14`)**:
   - **Pulse (Denyut Skeleton)**: `--p14-pulse-dur: 1000ms`, `ease-in-out`, `--p14-pulse-min: 0.5`.
   - **Reveal (Transisi Konten)**: `--p14-reveal-dur: 400ms`, `--p14-reveal-ease: ease-in-out`, transisi dari `opacity: 0; filter: blur(2px)` menuju `opacity: 1; filter: blur(0)`.
   - **Aksesibilitas**: Mendukung penuh `@media (prefers-reduced-motion: reduce)`.
2. **Teknik *Zero-DOM-Reparenting* (Anti-Rusak Layout)**:
   - Jangan membungkus ulang (*re-parent*) isi `.small-box`, `.info-box`, atau `.card` ke dalam `<div class="p14-content">` baru karena akan memutus selektor CSS anak langsung (seperti `.small-box > .inner` atau `.small-box.bg-success > .icon i`).
   - Sebagai gantinya, sisipkan `<div class="p14-skeleton" aria-hidden="true">` sebagai anak pertama (*overlay* absolut), lalu kenakan transisi *blur-to-crisp reveal* langsung pada `.p14-surface-host > *:not(.p14-skeleton)`.
   - Jangan memberikan `overflow: hidden` pada `.card` utama yang memuat filter Select2 agar menu dropdown tidak terpotong; cukup berikan `overflow: hidden` pada elemen `.p14-skeleton` di dalamnya.
3. **Bentuk Skeleton Adaptif**:
   - Otomatis mendeteksi isi kartu dan membentuk kerangka skeleton yang sesuai: **Kotak KPI/Metrik**, **Grafik Batang (Charts)**, **Baris Tabel**, **Kolom Formulir Filter**, atau **Header Halaman**.

---

## 🎨 2. KODE CSS UNIVERSAL (Tambahkan di `assets/css/custom.css`)

Salin blok CSS berikut ke bagian paling bawah stylesheet utama sistem:

```css
/* ==============================================================
   UNIVERSAL SKELETON LOADER & REVEAL REMIX (p14 Motion Engine)
   Adapted to Paper White & Paper Dark Design System
   ============================================================== */
:root {
    /* Mapped --stage-* tokens to Paper White tokens */
    --stage-bg: #f4f6f9;
    --stage-fg: #333333;
    --stage-muted: #5e6073;
    --stage-border: #b8b8b8;
    --stage-surface: #ffffff;
    --stage-accent: var(--baznas-green, #0d9f4f);
    --stage-on-accent: #ffffff;

    /* Core p14 component variables & motion tokens */
    --text: var(--stage-fg);
    --animate-bg: rgba(243, 243, 243, 0.9);
    --animate-bg-tint: #bfc0cb;
    --animate-bg-hover: #eae9e9;
    --animate-text: #17181c;
    --muted: var(--stage-muted);
    --material-bg: var(--stage-surface);
    --font-sans: var(--font-primary, "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif);
    --duration-slow: 400ms;
    --ease-in-out: ease-in-out;
    --blur-small: 2px;
    --p14-pulse-dur: 1000ms;
    --p14-pulse-count: 1;
    --p14-pulse-min: 0.5;
    --p14-reveal-dur: var(--duration-slow);
    --p14-reveal-blur: var(--blur-small);
    --p14-reveal-ease: var(--ease-in-out);
    --p14-skeleton-color: #eeeeee;
}

html.dark-mode,
body.dark-mode,
html[data-theme="dark"] {
    /* Mapped --stage-* tokens to Paper Dark tokens */
    --stage-bg: #14171a;
    --stage-fg: #e5e7eb;
    --stage-muted: #9ca3af;
    --stage-border: #374151;
    --stage-surface: #1f2328;
    --stage-accent: #0d9f4f;
    --stage-on-accent: #ffffff;

    --text: var(--stage-fg);
    --animate-bg: rgba(255, 255, 255, 0.04);
    --animate-bg-tint: transparent;
    --animate-bg-hover: rgba(255, 255, 255, 0.06);
    --animate-bg-pressed: rgba(255, 255, 255, 0.08);
    --animate-text: #e3e3e3;
    --animate-text-disabled: rgba(227, 227, 227, 0.5);
    --muted: var(--stage-muted);
    --material-bg: var(--stage-surface);
    --p14-skeleton-color: rgba(255, 255, 255, 0.08);
}

/* 1. Standalone p14-card & Explicit [data-proto-skeleton] Component */
.p14-card {
    position: relative;
    width: 256px;
    max-width: 100%;
    height: 56px;
    border-radius: 4px;
    background: var(--material-bg);
    border: 1px solid var(--stage-border);
    box-shadow: none !important;
    overflow: hidden;
}

.p14-card .p14-skeleton,
.p14-card .p14-content {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
}

.p14-skeleton {
    gap: 6px;
    z-index: 3;
    opacity: 1;
    filter: blur(0);
    pointer-events: none;
    transition: opacity var(--p14-reveal-dur) var(--p14-reveal-ease),
                filter  var(--p14-reveal-dur) var(--p14-reveal-ease);
    transition-delay: 0ms;
}

.p14-content {
    z-index: 2;
    opacity: 0;
    filter: blur(var(--p14-reveal-blur));
    transition: opacity var(--p14-reveal-dur) var(--p14-reveal-ease),
                filter  var(--p14-reveal-dur) var(--p14-reveal-ease);
    transition-delay: 0ms;
}

.p14-skeleton .p14-sk-avatar,
.p14-skeleton .p14-sk-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--p14-skeleton-color);
    flex: none;
}

.p14-skeleton .p14-sk-lines {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-left: 4px;
    flex: 1;
}

.p14-skeleton .p14-sk-name,
.p14-skeleton .p14-sk-email,
.p14-skeleton .p14-sk-bar,
.p14-skeleton .p14-sk-block {
    height: 12px;
    border-radius: 4px;
    background: var(--p14-skeleton-color);
}

.p14-skeleton .p14-sk-name { width: 100px; }
.p14-skeleton .p14-sk-email { width: 131px; }

.p14-skeleton.is-pulsing .p14-sk-avatar,
.p14-skeleton.is-pulsing .p14-sk-circle,
.p14-skeleton.is-pulsing .p14-sk-name,
.p14-skeleton.is-pulsing .p14-sk-email,
.p14-skeleton.is-pulsing .p14-sk-bar,
.p14-skeleton.is-pulsing .p14-sk-block {
    animation: p14-pulse var(--p14-pulse-dur) ease-in-out var(--p14-pulse-count);
}

[data-proto-skeleton].is-resetting > .p14-skeleton,
[data-proto-skeleton].is-resetting > .p14-content,
.p14-card.is-resetting .p14-skeleton,
.p14-card.is-resetting .p14-content {
    transition: none !important;
}

[data-proto-skeleton].is-revealed > .p14-skeleton,
.p14-card.is-revealed .p14-skeleton {
    opacity: 0;
    filter: blur(var(--p14-reveal-blur));
    pointer-events: none;
}

[data-proto-skeleton].is-revealed > .p14-content,
.p14-card.is-revealed .p14-content {
    opacity: 1;
    filter: blur(0);
}

/* 2. Universal Surface Adaptation (Zero-DOM-Reparenting) */
.p14-surface-host {
    position: relative !important;
}

.small-box.p14-surface-host,
.info-box.p14-surface-host,
.dana-compare-box.p14-surface-host,
.exec-kpi-card.p14-surface-host,
.insight-pill.p14-surface-host,
.metric-item.p14-surface-host,
.metric-box.p14-surface-host {
    overflow: hidden !important;
}

.p14-surface-host > .p14-skeleton {
    position: absolute;
    inset: 0;
    background: var(--stage-surface);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 16px 18px;
    gap: 10px;
    z-index: 15;
    border-radius: inherit;
    overflow: hidden;
    pointer-events: none;
}

.card.p14-surface-host > .p14-skeleton,
.flat-card.p14-surface-host > .p14-skeleton,
.doctor-card.p14-surface-host > .p14-skeleton {
    justify-content: flex-start;
    padding: 18px 20px;
    gap: 14px;
}

.p14-surface-host > *:not(.p14-skeleton) {
    opacity: 0;
    filter: blur(var(--p14-reveal-blur));
    transition: opacity var(--p14-reveal-dur) var(--p14-reveal-ease),
                filter  var(--p14-reveal-dur) var(--p14-reveal-ease);
    transition-delay: 0ms;
}

.p14-surface-host.is-resetting > *:not(.p14-skeleton),
.p14-surface-host.is-resetting > .p14-skeleton {
    transition: none !important;
}

.p14-surface-host.is-revealed > *:not(.p14-skeleton) {
    opacity: 1;
    filter: blur(0);
}

/* 3. Universal Modal & Tab Pane Reveal Remix */
.modal.fade .modal-dialog .modal-content {
    opacity: 0;
    filter: blur(var(--p14-reveal-blur));
    transition: opacity var(--p14-reveal-dur) var(--p14-reveal-ease),
                filter  var(--p14-reveal-dur) var(--p14-reveal-ease);
}

.modal.show .modal-dialog .modal-content {
    opacity: 1;
    filter: blur(0);
}

.tab-content > .tab-pane.active {
    animation: p14-direct-reveal var(--p14-reveal-dur) var(--p14-reveal-ease) forwards;
}

@keyframes p14-direct-reveal {
    0%   { opacity: 0; filter: blur(var(--p14-reveal-blur)); }
    100% { opacity: 1; filter: blur(0); }
}

@media (prefers-reduced-motion: reduce) {
    .p14-skeleton,
    .p14-content,
    [data-proto-skeleton] > .p14-skeleton,
    [data-proto-skeleton] > .p14-content,
    .p14-surface-host > *:not(.p14-skeleton) {
        transform: none !important;
        filter: none !important;
        transition: none !important;
    }
    .p14-skeleton.is-pulsing .p14-sk-avatar,
    .p14-skeleton.is-pulsing .p14-sk-circle,
    .p14-skeleton.is-pulsing .p14-sk-name,
    .p14-skeleton.is-pulsing .p14-sk-email,
    .p14-skeleton.is-pulsing .p14-sk-bar,
    .p14-skeleton.is-pulsing .p14-sk-block {
        animation: none !important;
    }
}

@keyframes p14-pulse {
    0%, 100% { opacity: 1; }
    50%      { opacity: var(--p14-pulse-min); }
}
```

---

## ⚡ 3. KODE JAVASCRIPT ENGINE UNIVERSAL (Tambahkan di `assets/js/app.js`)

Salin script berikut ke berkas JavaScript global sistem (`assets/js/app.js` atau `inc/footer.php`):

```javascript
/**
 * UNIVERSAL SKELETON LOADER & REVEAL REMIX (p14 Engine)
 */
(function () {
    function findCard(el) {
        return el.closest("[data-proto-card]");
    }

    function readMs(name, fallback) {
        var raw = getComputedStyle(document.documentElement)
            .getPropertyValue(name).trim();
        if (!raw) return fallback;
        if (raw.slice(-2) === "ms") return parseFloat(raw);
        if (raw.slice(-1) === "s")  return parseFloat(raw) * 1000;
        var n = parseFloat(raw);
        return isNaN(n) ? fallback : n;
    }

    function buildAutoSkeletonMarkup(el) {
        if (
            el.classList.contains("small-box") ||
            el.classList.contains("exec-kpi-card") ||
            el.classList.contains("dana-compare-box") ||
            el.classList.contains("metric-item") ||
            el.classList.contains("metric-box")
        ) {
            return (
                '<div class="d-flex justify-content-between align-items-center w-100">' +
                    '<div class="p14-sk-lines" style="margin-left:0;">' +
                        '<div class="p14-sk-bar" style="width: 62%; height: 20px; margin-bottom: 6px;"></div>' +
                        '<div class="p14-sk-bar" style="width: 44%; height: 11px;"></div>' +
                    '</div>' +
                    '<div class="p14-sk-circle" style="width: 38px; height: 38px;"></div>' +
                '</div>' +
                '<div class="p14-sk-bar" style="width: 85%; height: 10px; margin-top: 8px;"></div>'
            );
        }

        if (
            el.classList.contains("info-box") ||
            el.classList.contains("insight-pill") ||
            el.classList.contains("alert-excel") ||
            el.classList.contains("callout")
        ) {
            return (
                '<div class="d-flex align-items-center w-100" style="gap: 12px;">' +
                    '<div class="p14-sk-circle" style="width: 36px; height: 36px; border-radius: 4px;"></div>' +
                    '<div class="p14-sk-lines" style="margin-left:0;">' +
                        '<div class="p14-sk-name" style="width: 55%;"></div>' +
                        '<div class="p14-sk-email" style="width: 78%; height: 15px;"></div>' +
                    '</div>' +
                '</div>'
            );
        }

        if (el.classList.contains("content-header")) {
            return (
                '<div class="d-flex justify-content-between align-items-center w-100">' +
                    '<div class="p14-sk-lines" style="margin-left:0;">' +
                        '<div class="p14-sk-bar" style="width: 220px; height: 22px; margin-bottom: 6px;"></div>' +
                        '<div class="p14-sk-bar" style="width: 320px; height: 12px;"></div>' +
                    '</div>' +
                    '<div class="p14-sk-bar d-none d-sm-block" style="width: 140px; height: 14px;"></div>' +
                '</div>'
            );
        }

        if (el.classList.contains("card") || el.classList.contains("flat-card") || el.classList.contains("doctor-card")) {
            var hasChart = el.querySelector('[id*="Chart"], [id*="chart"], .apexcharts-canvas');
            var hasTable = el.querySelector("table");
            var hasForm = el.querySelector("form");

            var headerSkel =
                '<div class="d-flex justify-content-between align-items-center w-100 pb-2" style="border-bottom: 1px solid var(--stage-border);">' +
                    '<div class="p14-sk-bar" style="width: 38%; height: 16px;"></div>' +
                    '<div class="p14-sk-bar" style="width: 85px; height: 22px;"></div>' +
                '</div>';

            if (hasChart) {
                return (
                    headerSkel +
                    '<div class="d-flex align-items-end justify-content-between w-100 pt-3" style="height: 170px; gap: 10px;">' +
                        '<div class="p14-sk-block" style="flex:1; height: 48%;"></div>' +
                        '<div class="p14-sk-block" style="flex:1; height: 78%;"></div>' +
                        '<div class="p14-sk-block" style="flex:1; height: 62%;"></div>' +
                        '<div class="p14-sk-block" style="flex:1; height: 94%;"></div>' +
                        '<div class="p14-sk-block" style="flex:1; height: 70%;"></div>' +
                        '<div class="p14-sk-block" style="flex:1; height: 85%;"></div>' +
                    '</div>'
                );
            }

            if (hasTable) {
                return (
                    headerSkel +
                    '<div class="d-flex flex-column w-100 pt-2" style="gap: 10px;">' +
                        '<div class="p14-sk-bar" style="width: 100%; height: 24px;"></div>' +
                        '<div class="p14-sk-bar" style="width: 96%; height: 16px;"></div>' +
                        '<div class="p14-sk-bar" style="width: 92%; height: 16px;"></div>' +
                        '<div class="p14-sk-bar" style="width: 98%; height: 16px;"></div>' +
                        '<div class="p14-sk-bar" style="width: 88%; height: 16px;"></div>' +
                    '</div>'
                );
            }

            if (hasForm) {
                return (
                    headerSkel +
                    '<div class="d-flex flex-wrap w-100 pt-2" style="gap: 12px;">' +
                        '<div class="p14-sk-block" style="flex: 1 1 22%; min-width: 140px; height: 34px;"></div>' +
                        '<div class="p14-sk-block" style="flex: 1 1 22%; min-width: 140px; height: 34px;"></div>' +
                        '<div class="p14-sk-block" style="flex: 1 1 22%; min-width: 140px; height: 34px;"></div>' +
                        '<div class="p14-sk-block" style="flex: 1 1 22%; min-width: 140px; height: 34px;"></div>' +
                    '</div>'
                );
            }

            return (
                headerSkel +
                '<div class="d-flex flex-column w-100 pt-2" style="gap: 10px;">' +
                    '<div class="p14-sk-bar" style="width: 75%; height: 18px;"></div>' +
                    '<div class="p14-sk-bar" style="width: 92%; height: 13px;"></div>' +
                    '<div class="p14-sk-bar" style="width: 60%; height: 13px;"></div>' +
                '</div>'
            );
        }

        return (
            '<div class="d-flex align-items-center w-100" style="gap: 10px;">' +
                '<div class="p14-sk-avatar"></div>' +
                '<div class="p14-sk-lines">' +
                    '<div class="p14-sk-name"></div>' +
                    '<div class="p14-sk-email"></div>' +
                '</div>' +
            '</div>'
        );
    }

    function upgradeUniversalSurfaces() {
        var selectors = [
            ".content-header",
            ".small-box",
            ".info-box",
            ".dana-compare-box",
            ".exec-kpi-card",
            ".insight-pill",
            ".metric-item",
            ".metric-box",
            ".alert-excel",
            ".callout",
            ".content .card",
            ".content .flat-card",
            ".content .doctor-card",
            ".login-box .card"
        ];
        var surfaces = document.querySelectorAll(selectors.join(","));
        surfaces.forEach(function (el) {
            if (
                el.hasAttribute("data-proto-skeleton") ||
                el.hasAttribute("data-no-skeleton") ||
                el.closest(".modal")
            ) {
                return;
            }
            if (
                (el.classList.contains("card") || el.classList.contains("flat-card")) &&
                el.querySelector(".dana-compare-box, .exec-kpi-card, .small-box, .metric-item")
            ) {
                return;
            }

            el.setAttribute("data-proto-skeleton", "auto");
            el.classList.add("p14-surface-host");

            var skelWrap = document.createElement("div");
            skelWrap.className = "p14-skeleton";
            skelWrap.setAttribute("aria-hidden", "true");
            skelWrap.innerHTML = buildAutoSkeletonMarkup(el);

            el.insertBefore(skelWrap, el.firstChild);
        });
    }

    function bindSkeletonCard(p14Card) {
        if (p14Card._p14Replay) return;
        var resetTimer = null;
        var reducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        function replay(customDurationFactor) {
            if (resetTimer) {
                window.clearTimeout(resetTimer);
                resetTimer = null;
            }
            var skeleton = p14Card.querySelector(":scope > .p14-skeleton") || p14Card.querySelector(".p14-skeleton");
            if (!skeleton) return;

            if (reducedMotion) {
                p14Card.classList.remove("is-resetting");
                skeleton.classList.remove("is-pulsing");
                p14Card.classList.add("is-revealed");
                return;
            }

            p14Card.classList.add("is-resetting");
            p14Card.classList.remove("is-revealed");
            skeleton.classList.remove("is-pulsing");
            void skeleton.offsetWidth;
            p14Card.classList.remove("is-resetting");
            skeleton.classList.add("is-pulsing");

            var dur = readMs("--p14-pulse-dur", 1000);
            var count = parseFloat(
                getComputedStyle(document.documentElement)
                    .getPropertyValue("--p14-pulse-count")
            ) || 1;
            var factor = typeof customDurationFactor === "number" ? customDurationFactor : 1;
            var total = Math.max(180, dur * count * factor);

            resetTimer = window.setTimeout(function () {
                resetTimer = null;
                p14Card.classList.add("is-revealed");
            }, total);
        }

        p14Card._p14Replay = replay;
    }

    document.addEventListener("click", function (e) {
        var animateBtn = e.target.closest("button[data-proto]");
        if (animateBtn) {
            var proto = animateBtn.getAttribute("data-proto");
            var card = findCard(animateBtn);
            if (!card) {
                if (proto === "14") window.replayAllSkeletons();
                return;
            }
            if (proto === "14") {
                var p14Card = card.querySelector("[data-proto-skeleton]");
                if (p14Card && p14Card._p14Replay) p14Card._p14Replay(1);
            }
        }
    });

    function initP14() {
        upgradeUniversalSurfaces();
        var cards = document.querySelectorAll("[data-proto-skeleton]");
        if (!cards.length) return;
        cards.forEach(function (p14Card, idx) {
            bindSkeletonCard(p14Card);
            var isAutoSurface = p14Card.getAttribute("data-proto-skeleton") === "auto";
            var factor = isAutoSurface ? 0.42 : 1;
            var stagger = Math.min(idx * 35, 240);
            window.setTimeout(function () {
                if (p14Card._p14Replay) p14Card._p14Replay(factor);
            }, stagger);
        });
    }

    window.replayAllSkeletons = function (factor) {
        var cards = document.querySelectorAll("[data-proto-skeleton]");
        cards.forEach(function (p14Card, idx) {
            bindSkeletonCard(p14Card);
            window.setTimeout(function () {
                if (p14Card._p14Replay) p14Card._p14Replay(typeof factor === "number" ? factor : 0.65);
            }, Math.min(idx * 30, 210));
        });
    };

    if (window.jQuery && $.fn && $.fn.dataTable) {
        $(document).on("preXhr.dt", function (e, settings) {
            var wrapper = $(settings.nTableWrapper);
            wrapper.find("tbody").css({
                transition: "opacity 200ms ease-in-out, filter 200ms ease-in-out",
                opacity: "0.45",
                filter: "blur(2px)"
            });
        });
        $(document).on("draw.dt", function (e, settings) {
            var wrapper = $(settings.nTableWrapper);
            wrapper.find("tbody").css({
                transition: "opacity 400ms ease-in-out, filter 400ms ease-in-out",
                opacity: "1",
                filter: "blur(0)"
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initP14);
    } else {
        initP14();
    }
})();
```
