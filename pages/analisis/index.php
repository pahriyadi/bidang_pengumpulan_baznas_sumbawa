<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'analisis';
$pageTitle = 'Analisis Pengumpulan Data';
$pageDescription = 'Analisis mendalam tren dan komposisi pengumpulan ZIS';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Analisis Data</li>';
$conn = db();

// --- Advanced Filtering Logic ---
$filter_type = $_GET['filter_type'] ?? 'year';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('Y-m');
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$where_sql = "";
$params = [];
$types = "";

if ($filter_type === 'month') {
    $where_sql = "WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?";
    $params[] = $month;
    $types = "s";
    $label_period = date('F Y', strtotime($month . '-01'));
} elseif ($filter_type === 'range') {
    $where_sql = "WHERE tanggal_transaksi BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types = "ss";
    $label_period = date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
} else {
    $where_sql = "WHERE YEAR(tanggal_transaksi) = ?";
    $params[] = $year;
    $types = "s";
    $label_period = "Tahun " . $year;
}

// Helper function for repetitive aggregation queries
function fetch_analytics($conn, $sql, $types, $params) {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 1. Monthly Trend
$trendDataRaw = fetch_analytics($conn, "SELECT MONTH(tanggal_transaksi) as bln, SUM(jumlah_pembayaran) as total FROM transaksi $where_sql GROUP BY MONTH(tanggal_transaksi)", $types, $params);
$monthlyTrend = array_fill(1, 12, 0);
foreach($trendDataRaw as $r) $monthlyTrend[(int)$r['bln']] = (float)$r['total'];

// 2. Composition by Jenis Pembayaran
$jenisData = fetch_analytics($conn, "SELECT jp.nama_jenis, SUM(t.jumlah_pembayaran) as total FROM transaksi t JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id $where_sql GROUP BY jp.id ORDER BY total DESC", $types, $params);

// 3. Composition by Metode Pembayaran
$metodeData = fetch_analytics($conn, "SELECT mp.nama_metode, SUM(t.jumlah_pembayaran) as total FROM transaksi t JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id $where_sql GROUP BY mp.id ORDER BY total DESC", $types, $params);

// 4. Composition by Sumber Rekening (Bank)
$bankData = fetch_analytics($conn, "SELECT sr.nama_sumber, SUM(t.jumlah_pembayaran) as total FROM transaksi t JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id $where_sql GROUP BY sr.id ORDER BY total DESC", $types, $params);

// 5. Top 10 UPZ
$topUPZ = fetch_analytics($conn, "SELECT u.nama_upz, SUM(t.jumlah_pembayaran) as total FROM transaksi t JOIN upz u ON t.id_upz = u.id $where_sql GROUP BY u.id ORDER BY total DESC LIMIT 10", $types, $params);

// 5b. Composition by Kategori UPZ
$kategoriUPZData = fetch_analytics($conn, "
    SELECT COALESCE(ku.nama_kategori, 'Belum Dikategorikan') as nama_kategori, SUM(t.jumlah_pembayaran) as total 
    FROM transaksi t 
    JOIN upz u ON t.id_upz = u.id 
    LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id 
    $where_sql 
    GROUP BY COALESCE(ku.nama_kategori, 'Belum Dikategorikan')
    ORDER BY total DESC
", $types, $params);

// 6. Kategori Dana (Asnaf)
$katDataRaw = fetch_analytics($conn, "SELECT id_kategori_dana, jumlah_pembayaran FROM transaksi $where_sql", $types, $params);
$kategoriList = get_kategori_dana_list();
$kategoriStats = [];
foreach ($kategoriList as $k) $kategoriStats[$k['id']] = ['nama' => $k['nama_kategori'], 'total' => 0, 'count' => 0];

foreach ($katDataRaw as $r) {
    $ids = parse_kategori_dana_ids($r['id_kategori_dana']);
    foreach ($ids as $id) {
        if (isset($kategoriStats[$id])) {
            $kategoriStats[$id]['total'] += (float)$r['jumlah_pembayaran'];
            $kategoriStats[$id]['count']++;
        }
    }
}
uasort($kategoriStats, function($a, $b) { return $b['total'] <=> $a['total']; });

// Grand Total for percentages
$grandTotal = array_sum(array_column($jenisData, 'total'));

include __DIR__ . '/../../inc/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-body">
                <form method="GET" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-2 col-6">
                            <label class="small font-weight-bold">Tipe Filter</label>
                            <select name="filter_type" id="filter_type" class="form-control form-control-sm" onchange="toggleFilterInputs()" style="border: 1px solid #b8b8b8;">
                                <option value="year" <?= $filter_type === 'year' ? 'selected' : '' ?>>Tahun Penuh</option>
                                <option value="month" <?= $filter_type === 'month' ? 'selected' : '' ?>>Bulan Spesifik</option>
                                <option value="range" <?= $filter_type === 'range' ? 'selected' : '' ?>>Rentang Tanggal</option>
                            </select>
                        </div>
                        
                        <!-- Year Input -->
                        <div class="col-md-2 col-6 filter-input" id="input_year" style="<?= $filter_type !== 'year' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold">Tahun</label>
                            <select name="year" class="form-control form-control-sm" style="border: 1px solid #b8b8b8;">
                                <?php for($y = date('Y'); $y >= 2022; $y--): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Month Input -->
                        <div class="col-md-2 col-6 filter-input" id="input_month" style="<?= $filter_type !== 'month' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold">Bulan</label>
                            <input type="month" name="month" class="form-control form-control-sm" value="<?= $month ?>" style="border: 1px solid #b8b8b8;">
                        </div>

                        <!-- Range Inputs -->
                        <div class="col-md-2 col-6 filter-input" id="input_start" style="<?= $filter_type !== 'range' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold">Dari</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>" style="border: 1px solid #b8b8b8;">
                        </div>
                        <div class="col-md-2 col-6 filter-input" id="input_end" style="<?= $filter_type !== 'range' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold">Sampai</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>" style="border: 1px solid #b8b8b8;">
                        </div>

                        <div class="col-md-2 col-6">
                            <button type="submit" class="btn btn-success btn-sm btn-block">
                                <i class="fas fa-sync-alt mr-1"></i>Terapkan
                            </button>
                        </div>
                        <div class="col-md-2 ml-auto text-right">
                             <span class="badge badge-secondary p-2 shadow-none border" style="border-color: #b8b8b8 !important;">
                                <i class="fas fa-info-circle mr-1 text-success"></i> <?= $label_period ?>
                             </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- TREND CHART -->
    <div class="col-lg-12">
        <div class="card shadow-none mb-4" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white py-3 d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold text-success"><i class="fas fa-chart-line mr-2"></i>Tren Pengumpulan Bulanan</h3>
                <button class="btn btn-xs btn-outline-success ml-auto" onclick="toggleDetails('trendDetails')">
                    <i class="fas fa-table mr-1"></i>Lihat Detail Angka
                </button>
            </div>
            <div class="card-body">
                <div id="trendApexChart" style="min-height: 320px;"></div>
                <!-- Detail Table -->
                <div id="trendDetails" class="mt-4" style="display: none;">
                    <hr style="border-top: 1px solid #b8b8b8;">
                    <table class="table table-sm table-hover small">
                        <thead>
                            <tr style="border-bottom: 1px solid #b8b8b8;">
                                <th>Bulan</th>
                                <th class="text-right">Total Pengumpulan</th>
                                <th class="text-right">% Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($monthlyTrend as $m => $total): ?>
                            <tr>
                                <td><?= month_name($m) ?></td>
                                <td class="text-right font-weight-bold"><?= format_currency($total) ?></td>
                                <td class="text-right"><?= $grandTotal > 0 ? round(($total/$grandTotal)*100, 1) : 0 ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- COMPOSITION JENIS -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white pt-3 d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-tags mr-2 text-success"></i>Jenis Pembayaran</h3>
                <button class="btn btn-xs btn-outline-secondary ml-auto" onclick="toggleDetails('jenisDetails')">Detail</button>
            </div>
            <div class="card-body">
                <div id="jenisApexChart" style="min-height: 250px;"></div>
                <div id="jenisDetails" class="mt-3 small" style="display:none;">
                    <hr style="border-top: 1px solid #b8b8b8;">
                    <table class="table table-sm table-borderless mb-0">
                        <?php foreach($jenisData as $j): ?>
                        <tr>
                            <td><?= clean($j['nama_jenis']) ?></td>
                            <td class="text-right font-weight-bold"><?= format_currency($j['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- COMPOSITION METODE -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white pt-3 d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-wallet mr-2 text-success"></i>Metode Pembayaran</h3>
                <button class="btn btn-xs btn-outline-secondary ml-auto" onclick="toggleDetails('metodeDetails')">Detail</button>
            </div>
            <div class="card-body">
                <div id="metodeApexChart" style="min-height: 250px;"></div>
                <div id="metodeDetails" class="mt-3 small" style="display:none;">
                    <hr style="border-top: 1px solid #b8b8b8;">
                    <table class="table table-sm table-borderless mb-0">
                        <?php foreach($metodeData as $m): ?>
                        <tr>
                            <td><?= clean($m['nama_metode']) ?></td>
                            <td class="text-right font-weight-bold"><?= format_currency($m['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- COMPOSITION ASNAF -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white pt-3 d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-heart mr-2 text-success"></i>Kategori Dana</h3>
                <button class="btn btn-xs btn-outline-secondary ml-auto" onclick="toggleDetails('katDetails')">Detail</button>
            </div>
            <div class="card-body">
                <div id="kategoriApexChart" style="min-height: 250px;"></div>
                <div id="katDetails" class="mt-3 small" style="display:none;">
                    <hr style="border-top: 1px solid #b8b8b8;">
                    <div style="max-height: 150px; overflow-y: auto;">
                        <table class="table table-sm table-borderless mb-0">
                            <?php foreach($kategoriStats as $k): if($k['total'] == 0) continue;?>
                            <tr>
                                <td><?= clean($k['nama']) ?></td>
                                <td class="text-right font-weight-bold text-nowrap"><?= format_currency($k['total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- TOP UPZ -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-none mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white pt-3" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold text-success"><i class="fas fa-award mr-2"></i>Top 10 UPZ</h3>
            </div>
            <div class="card-body">
                <div id="upzApexChart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    <!-- BANK POPULARITY -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-none mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white pt-3" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold text-success"><i class="fas fa-university mr-2"></i>Sumber Dana (Bank)</h3>
            </div>
            <div class="card-body">
                <div id="bankApexChart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- COMPOSITION BY KATEGORI UPZ -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow-none mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white py-3 d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold text-success"><i class="fas fa-chart-pie mr-2"></i>Komposisi Pengumpulan per Kategori UPZ</h3>
                <button class="btn btn-xs btn-outline-success ml-auto" onclick="toggleDetails('upzKategoriDetails')">
                    <i class="fas fa-table mr-1"></i>Lihat Detail Angka
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <div id="upzKategoriApexChart" style="min-height: 320px;"></div>
                    </div>
                    <div class="col-md-5">
                        <div id="upzKategoriDetails" class="table-responsive">
                            <table class="table table-sm table-hover small">
                                <thead>
                                    <tr style="border-bottom: 1px solid #b8b8b8;">
                                        <th>Kategori Sektoral UPZ</th>
                                        <th class="text-right">Total Pengumpulan</th>
                                        <th class="text-right">% Kontribusi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $upzKategoriTotal = array_sum(array_column($kategoriUPZData, 'total'));
                                    foreach($kategoriUPZData as $ku): 
                                    ?>
                                    <tr>
                                        <td><?= clean($ku['nama_kategori']) ?></td>
                                        <td class="text-right font-weight-bold"><?= format_currency($ku['total']) ?></td>
                                        <td class="text-right"><?= $upzKategoriTotal > 0 ? round(($ku['total']/$upzKategoriTotal)*100, 1) : 0 ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDetails(id) {
    $('#' + id).slideToggle(300);
}

function toggleFilterInputs() {
    const type = $('#filter_type').val();
    $('.filter-input').hide();
    if (type === 'year') $('#input_year').show();
    else if (type === 'month') $('#input_month').show();
    else if (type === 'range') {
        $('#input_start').show();
        $('#input_end').show();
    }
}

$(function() {
    toggleFilterInputs();

    // Palette Warna BAZNAS Putih Kertas
    var chartPalette = ['#0d9f4f', '#076e34', '#b8b8b8', '#ffc107', '#17a2b8', '#64748b', '#334155'];

    // 1. Trend Chart (Area Chart)
    var trendOptions = {
        series: [{
            name: 'Total Pengumpulan',
            data: <?= json_encode(array_values($monthlyTrend)) ?>
        }],
        chart: {
            height: 320,
            type: 'area',
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#0d9f4f'],
        stroke: {
            curve: 'smooth',
            width: 3
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.35,
                opacityTo: 0.05
            }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            axisBorder: { show: true, color: '#b8b8b8' },
            axisTicks: { show: true, color: '#b8b8b8' }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        },
        grid: {
            borderColor: '#e5e5e5',
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };
    var trendChart = new ApexCharts(document.querySelector("#trendApexChart"), trendOptions);
    trendChart.render();

    // 2. Jenis Chart (Donut Chart)
    var jenisOptions = {
        series: <?= json_encode(array_map('floatval', array_column($jenisData, 'total'))) ?>,
        chart: {
            height: 250,
            type: 'donut',
            fontFamily: 'Inter, sans-serif'
        },
        colors: chartPalette,
        labels: <?= json_encode(array_column($jenisData, 'nama_jenis')) ?>,
        plotOptions: {
            pie: {
                donut: {
                    size: '65%'
                }
            }
        },
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: false
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };
    var jenisChart = new ApexCharts(document.querySelector("#jenisApexChart"), jenisOptions);
    jenisChart.render();

    // 3. Metode Chart (Donut Chart)
    var metodeOptions = {
        series: <?= json_encode(array_map('floatval', array_column($metodeData, 'total'))) ?>,
        chart: {
            height: 250,
            type: 'donut',
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#ffc107', '#0d9f4f', '#b8b8b8', '#17a2b8'],
        labels: <?= json_encode(array_column($metodeData, 'nama_metode')) ?>,
        plotOptions: {
            pie: {
                donut: {
                    size: '65%'
                }
            }
        },
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: false
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };
    var metodeChart = new ApexCharts(document.querySelector("#metodeApexChart"), metodeOptions);
    metodeChart.render();

    // 4. Kategori Chart (Donut Chart)
    <?php
    $katLabels = array_values(array_column($kategoriStats, 'nama'));
    $katTotals = array_values(array_map('floatval', array_column($kategoriStats, 'total')));
    ?>
    var kategoriOptions = {
        series: <?= json_encode($katTotals) ?>,
        chart: {
            height: 250,
            type: 'donut',
            fontFamily: 'Inter, sans-serif'
        },
        colors: chartPalette,
        labels: <?= json_encode($katLabels) ?>,
        plotOptions: {
            pie: {
                donut: {
                    size: '65%'
                }
            }
        },
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: false
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };
    var kategoriChart = new ApexCharts(document.querySelector("#kategoriApexChart"), kategoriOptions);
    kategoriChart.render();

    // 5. UPZ Chart (Horizontal Bar Chart)
    var upzOptions = {
        series: [{
            name: 'Total Pengumpulan',
            data: <?= json_encode(array_map('floatval', array_column($topUPZ, 'total'))) ?>
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true
            }
        },
        colors: ['#0d9f4f'],
        xaxis: {
            categories: <?= json_encode(array_column($topUPZ, 'nama_upz')) ?>,
            labels: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        },
        grid: {
            borderColor: '#e5e5e5',
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };
    var upzChart = new ApexCharts(document.querySelector("#upzApexChart"), upzOptions);
    upzChart.render();

    // 6. Bank Chart (Vertical Bar Chart)
    var bankOptions = {
        series: [{
            name: 'Total Pengumpulan',
            data: <?= json_encode(array_map('floatval', array_column($bankData, 'total'))) ?>
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '50%'
            }
        },
        colors: ['#334155'],
        xaxis: {
            categories: <?= json_encode(array_column($bankData, 'nama_sumber')) ?>,
            axisBorder: { show: true, color: '#b8b8b8' }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        },
        grid: {
            borderColor: '#e5e5e5',
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };
    var bankChart = new ApexCharts(document.querySelector("#bankApexChart"), bankOptions);
    bankChart.render();

    // 7. UPZ Kategori Chart (Donut Chart)
    var upzKategoriOptions = {
        series: <?= json_encode(array_map('floatval', array_column($kategoriUPZData, 'total'))) ?>,
        chart: {
            height: 320,
            type: 'donut',
            fontFamily: 'Inter, sans-serif'
        },
        colors: chartPalette,
        labels: <?= json_encode(array_column($kategoriUPZData, 'nama_kategori')) ?>,
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Sektoral',
                            formatter: function (w) {
                                return "Rp " + (<?= json_encode(array_sum(array_column($kategoriUPZData, 'total'))) ?>).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        },
        legend: {
            position: 'right',
            offsetY: 20
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + "%";
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };
    var upzKategoriChart = new ApexCharts(document.querySelector("#upzKategoriApexChart"), upzKategoriOptions);
    upzKategoriChart.render();
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
