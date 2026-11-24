/**
 * App.js - Shared JS Utilities for SIM Pengumpulan ZIS
 */

// SweetAlert2 Toast preset
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

// Success toast
function showSuccess(message) {
    Toast.fire({ icon: 'success', title: message });
}

// Error toast
function showError(message) {
    Toast.fire({ icon: 'error', title: message });
}

// Confirm delete with SweetAlert2
function confirmDelete(callback) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: 'Data yang dihapus tidak dapat dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash mr-1"></i>Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

// Format number to Indonesian currency
function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID');
}

// Parse currency string back to number
function parseCurrency(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/[^0-9,-]+/g, '').replace(',', '.')) || 0;
}

// Initialize currency input formatting
function initCurrencyInput(selector) {
    $(selector).on('input', function () {
        let val = this.value.replace(/[^0-9]/g, '');
        if (val === '') {
            this.value = '';
            return;
        }
        this.value = parseInt(val).toLocaleString('id-ID');
    });
}

// Get raw currency value from formatted input
function getCurrencyValue(selector) {
    return parseInt($(selector).val().replace(/[^0-9]/g, '')) || 0;
}

// Initialize Select2 for dropdowns
// dropdownParent: wajib untuk select di dalam modal Bootstrap agar kotak pencarian bisa difokuskan
function initSelect2(selector, placeholder, dropdownParent) {
    var opts = {
        theme: 'bootstrap4',
        placeholder: placeholder || 'Pilih...',
        allowClear: true,
        width: '100%'
    };
    if (dropdownParent) {
        opts.dropdownParent = $(dropdownParent);
    }
    $(selector).select2(opts);
}

// DataTable default configuration (Indonesian)
if (window.jQuery && $.fn && $.fn.dataTable) {
    $.extend($.fn.dataTable.defaults, {
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang cocok",
            emptyTable: "Tidak ada data tersedia",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                previous: '<i class="fas fa-angle-left"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                last: '<i class="fas fa-angle-double-right"></i>'
            }
        },
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });
}

// Document ready
$(function () {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);
});

/**
 * ============================================================================
 * UNIVERSAL SKELETON LOADER & REVEAL REMIX (p14 Engine)
 * Preserves exact pulse durations (1000ms), reveal blur (2px), easing (400ms ease-in-out),
 * and prefers-reduced-motion accessibility fallback.
 * ============================================================================
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
        // 1. Metric & KPI Boxes
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

        // 2. Info Boxes, Insight Pills & Alerts
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

        // 3. Content Header (Page Title & Breadcrumb)
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

        // 4. Full Cards (.card / .flat-card / .doctor-card) — adaptive to Chart, Table, Filter Form, or General Card
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
            // Skip outer card if it only wraps inner cards that already have skeleton animations (e.g. comparison wrapper card)
            if (
                (el.classList.contains("card") || el.classList.contains("flat-card")) &&
                el.querySelector(".dana-compare-box, .exec-kpi-card, .small-box, .metric-item")
            ) {
                return;
            }

            el.setAttribute("data-proto-skeleton", "auto");
            el.classList.add("p14-surface-host");

            // Prepend .p14-skeleton overlay without moving or reparenting existing children
            // so all direct-child selectors (.small-box > .inner, .small-box.bg-* > .icon i) remain 100% intact.
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

            // Snap back to the pre-reveal state without animating.
            p14Card.classList.add("is-resetting");
            p14Card.classList.remove("is-revealed");
            skeleton.classList.remove("is-pulsing");
            // Force reflow
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
                // Global trigger if button[data-proto="14"] is outside a single card
                if (proto === "14") {
                    window.replayAllSkeletons();
                }
                return;
            }
            switch (proto) {
                case "14": {
                    var p14Card = card.querySelector("[data-proto-skeleton]");
                    if (p14Card && p14Card._p14Replay) p14Card._p14Replay(1);
                    break;
                }
            }
            return;
        }
    });

    function initP14() {
        upgradeUniversalSurfaces();
        var cards = document.querySelectorAll("[data-proto-skeleton]");
        if (!cards.length) return;
        cards.forEach(function (p14Card, idx) {
            bindSkeletonCard(p14Card);
            var isAutoSurface = p14Card.getAttribute("data-proto-skeleton") === "auto";
            // Universal auto-surfaces play a fast, snappy 450ms pulse then 400ms blur-to-crisp reveal;
            // explicit [data-proto-skeleton] cards play the full 1000ms pulse as defined by --p14-pulse-dur.
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

    // Hook into DataTables processing / draw events for universal table reveal feel
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

