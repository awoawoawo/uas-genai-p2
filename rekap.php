<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// === DATA HARI INI (Eks-Dashboard) ===
$stmt_today = $pdo->prepare("SELECT COUNT(*) as jumlah_transaksi, SUM(total_jual) as total_rupiah, SUM(total_jual - total_modal) as total_keuntungan FROM transaksi WHERE DATE(tanggal) = CURDATE()");
$stmt_today->execute();
$today_stats = $stmt_today->fetch();

$jumlah_transaksi = $today_stats['jumlah_transaksi'] ?: 0;
$total_rupiah_hari_ini = $today_stats['total_rupiah'] ?: 0;
$total_keuntungan_hari_ini = $today_stats['total_keuntungan'] ?: 0;


// === DATA REKAPITULASI ===
// Filter Tanggal
$filter_jenis = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$tanggal_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Bangun query berdasarkan filter
$query_where = "";
$params = [];

if ($filter_jenis == 'harian' && isset($_GET['tgl'])) {
    $tgl = $_GET['tgl'];
    $query_where = "WHERE DATE(tanggal) = ?";
    $params[] = $tgl;
} elseif ($filter_jenis == 'periode') {
    $query_where = "WHERE DATE(tanggal) BETWEEN ? AND ?";
    $params[] = $tanggal_awal;
    $params[] = $tanggal_akhir;
}

$stmt = $pdo->prepare("SELECT * FROM transaksi $query_where ORDER BY tanggal DESC");
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll();

// Hitung Ringkasan sesuai filter
$total_penjualan_filter = 0;
$total_modal_filter = 0;
foreach ($transaksi_list as $t) {
    $total_penjualan_filter += $t['total_jual'];
    $total_modal_filter += $t['total_modal'];
}
$total_keuntungan_filter = $total_penjualan_filter - $total_modal_filter;

// Data untuk Grafik
$stmt_chart = $pdo->prepare("SELECT DATE(tanggal) as tgl, SUM(total_jual) as omset, SUM(total_jual - total_modal) as untung FROM transaksi $query_where GROUP BY DATE(tanggal) ORDER BY tgl ASC");
$stmt_chart->execute($params);
$chart_data = $stmt_chart->fetchAll();

$chart_labels = [];
$chart_omset = [];
$chart_untung = [];
foreach($chart_data as $row) {
    $chart_labels[] = date('d/m/Y', strtotime($row['tgl']));
    $chart_omset[] = $row['omset'];
    $chart_untung[] = $row['untung'];
}

// Data untuk Produk Terlaris
$query_where_top = str_replace('tanggal', 't.tanggal', $query_where);
$stmt_top = $pdo->prepare("
    SELECT p.nama, p.kategori, SUM(td.qty) as total_terjual 
    FROM transaksi_detail td 
    JOIN produk p ON td.produk_id = p.id 
    JOIN transaksi t ON td.transaksi_id = t.id 
    $query_where_top 
    GROUP BY p.id 
    ORDER BY total_terjual DESC 
    LIMIT 5
");
$stmt_top->execute($params);
$top_products = $stmt_top->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Keuntungan - Kopi Kuningan</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    
    <!-- === BAGIAN RINGKASAN HARI INI === -->
    <h2 class="mb-1" style="color: var(--text-main); border-bottom: 2px dashed var(--border-color); padding-bottom: 0.5rem; font-size: 1.5rem;">STATUS HARI INI</h2>
    <div class="dashboard-cards" style="margin-bottom: 2rem;">
        <div class="dash-card omset">
            <h4>Omset Hari Ini (<?= $jumlah_transaksi ?> Trx)</h4>
            <div class="h2">Rp <?= number_format($total_rupiah_hari_ini, 0, ',', '.') ?></div>
        </div>
        <div class="dash-card success">
            <h4>Keuntungan Hari Ini</h4>
            <div class="h2">Rp <?= number_format($total_keuntungan_hari_ini, 0, ',', '.') ?></div>
        </div>
    </div>
    
    <!-- === BAGIAN ANALISIS (GRAFIK & TOP PRODUK) === -->
    <h2 class="mb-1 mt-2" style="color: var(--text-main); border-bottom: 2px dashed var(--border-color); padding-bottom: 0.5rem; font-size: 1.5rem;">ANALISIS PENJUALAN (FILTER)</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 2rem; align-items: stretch;">
        <div style="flex: 2; min-width: 300px;">
            <div class="card card-table" style="height: 100%; padding: 1.5rem; margin-bottom: 0;">
                <h4 style="margin-bottom: 1rem; font-family: var(--font-mono); color: var(--text-main); text-transform: uppercase;">Grafik Tren Omset & Keuntungan</h4>
                <?php if(empty($chart_labels)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 3rem 0; font-family: var(--font-sans);">
                        Belum ada data untuk ditampilkan pada periode ini.
                    </div>
                <?php else: ?>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="salesChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div style="flex: 1; min-width: 280px;">
            <div class="card card-table" style="height: 100%; padding: 1.5rem; margin-bottom: 0;">
                <h4 style="margin-bottom: 1rem; font-family: var(--font-mono); color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; text-transform: uppercase;">5 Produk Terlaris</h4>
                <?php if(empty($top_products)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 2rem 0; font-family: var(--font-sans);">
                        Belum ada penjualan.
                    </div>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php $no = 1; foreach($top_products as $tp): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px dashed var(--border-color);">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <strong style="font-family: var(--font-mono);"><?= $no++ ?>.</strong>
                                <div>
                                    <div style="font-family: var(--font-mono); font-size: 0.95rem; font-weight: bold;"><?= htmlspecialchars($tp['nama']) ?></div>
                                    <span style="font-size: 0.75rem; background: var(--surface); border: 1px solid var(--border-color); padding: 1px 4px; border-radius: 2px; text-transform: uppercase;"><?= htmlspecialchars($tp['kategori']) ?></span>
                                </div>
                            </div>
                            <div style="font-weight: bold; color: var(--accent-secondary); font-size: 1.1rem;"><?= $tp['total_terjual'] ?> <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-main);">terjual</span></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- === BAGIAN RIWAYAT TRANSAKSI === -->
    <h2 id="filter-result" class="mb-1 mt-2" style="color: var(--text-main); border-bottom: 2px dashed var(--border-color); padding-bottom: 0.5rem; font-size: 1.5rem;">RIWAYAT & FILTER TRANSAKSI</h2>

    <!-- Ringkasan Angka (Sesuai Filter) -->
    <div class="dashboard-cards" style="margin-bottom: 1rem;">
        <div class="dash-card">
            <h4>Total Omset (Filter)</h4>
            <div class="h2">Rp <?= number_format($total_penjualan_filter, 0, ',', '.') ?></div>
        </div>
        <div class="dash-card" style="background-color: var(--surface);">
            <h4>Total Modal (Filter)</h4>
            <div class="h2">Rp <?= number_format($total_modal_filter, 0, ',', '.') ?></div>
        </div>
        <div class="dash-card success">
            <h4>Total Keuntungan (Filter)</h4>
            <div class="h2">Rp <?= number_format($total_keuntungan_filter, 0, ',', '.') ?></div>
        </div>
    </div>

    <!-- Filter Form -->
    <div id="riwayat" class="card card-table mb-2" style="padding: 1rem 1.5rem;">
        <form id="filterForm" method="GET" action="rekap.php" class="d-flex align-items-center gap-2" style="flex-wrap: wrap;">
            <div style="font-family: var(--font-mono); font-weight: 700;">PILIH PERIODE:</div>
            
            <select name="filter" class="form-control" style="width: auto; padding: 0.5rem;" onchange="submitFilterForm()">
                <option value="semua" <?= $filter_jenis == 'semua' ? 'selected' : '' ?>>Semua Waktu</option>
                <option value="harian" <?= $filter_jenis == 'harian' ? 'selected' : '' ?>>Harian (Pilih Tanggal)</option>
                <option value="periode" <?= $filter_jenis == 'periode' ? 'selected' : '' ?>>Periode (Dari - Sampai)</option>
            </select>

            <?php if($filter_jenis == 'harian'): ?>
                <input type="date" name="tgl" class="form-control" style="width: auto; padding: 0.5rem;" value="<?= isset($_GET['tgl']) ? $_GET['tgl'] : date('Y-m-d') ?>">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Terapkan</button>
            <?php endif; ?>

            <?php if($filter_jenis == 'periode'): ?>
                <input type="date" name="tgl_awal" class="form-control" style="width: auto; padding: 0.5rem;" value="<?= $tanggal_awal ?>">
                <span style="font-weight:bold;">s/d</span>
                <input type="date" name="tgl_akhir" class="form-control" style="width: auto; padding: 0.5rem;" value="<?= $tanggal_akhir ?>">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Terapkan</button>
            <?php endif; ?>
        </form>
        
        <script>
            function submitFilterForm() {
                document.getElementById('filterForm').dispatchEvent(new Event('submit', { cancelable: true }));
            }
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const url = new URL(window.location.origin + window.location.pathname);
                const formData = new FormData(this);
                for (const [key, value] of formData) {
                    url.searchParams.append(key, value);
                }
                url.hash = 'filter-result';
                window.location.href = url.href;
            });
        </script>
    </div>

    <!-- Tabel Riwayat Transaksi -->
    <div class="card card-table">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID TRX</th>
                        <th>WAKTU TRANSAKSI</th>
                        <th>TOTAL OMSET</th>
                        <th>TOTAL MODAL</th>
                        <th>KEUNTUNGAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $limit_display = 20;
                    $total_records = count($transaksi_list);
                    $total_pages = ceil($total_records / $limit_display);
                    
                    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    if($current_page < 1) $current_page = 1;
                    if($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
                    
                    $offset = ($current_page - 1) * $limit_display;
                    $displayed_list = array_slice($transaksi_list, $offset, $limit_display);
                    
                    foreach($displayed_list as $t): 
                        $keuntungan = $t['total_jual'] - $t['total_modal'];
                    ?>
                    <tr>
                        <td style="font-weight: bold; text-align: center;">#<?= $t['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($t['tanggal'])) ?></td>
                        <td style="font-weight: 600;">Rp <?= number_format($t['total_jual'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($t['total_modal'], 0, ',', '.') ?></td>
                        <td style="color: #638548; font-weight: bold;">+ Rp <?= number_format($keuntungan, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(empty($transaksi_list)): ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 2.5rem 0; color: #777; font-family: var(--font-sans);">
                            Belum ada transaksi tercatat untuk periode ini.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($total_pages > 1): ?>
        <div style="padding: 1rem; text-align: center; border-top: 2px solid var(--border-color); background: var(--surface);">
            <?php
            // Membangun URL dasar tanpa parameter 'page' agar filter tetap terbawa
            $query_params = $_GET;
            unset($query_params['page']);
            $base_url = "rekap.php?" . http_build_query($query_params);
            ?>
            
            <?php if($current_page > 1): ?>
                <a href="<?= $base_url ?>&page=1#filter-result" class="btn" style="padding: 0.4rem 0.8rem; margin: 0 0.2rem; font-size: 0.85rem;" title="Halaman Pertama">&laquo;&laquo; FIRST</a>
                <a href="<?= $base_url ?>&page=<?= $current_page - 1 ?>#filter-result" class="btn" style="padding: 0.4rem 0.8rem; margin: 0 0.2rem; font-size: 0.85rem;" title="Halaman Sebelumnya">&laquo; PREV</a>
            <?php endif; ?>
            
            <span style="font-family: var(--font-mono); font-weight: bold; margin: 0 1rem; color: var(--text-main);">
                HALAMAN <?= $current_page ?> / <?= $total_pages ?>
            </span>
            
            <?php if($current_page < $total_pages): ?>
                <a href="<?= $base_url ?>&page=<?= $current_page + 1 ?>#filter-result" class="btn" style="padding: 0.4rem 0.8rem; margin: 0 0.2rem; font-size: 0.85rem;" title="Halaman Selanjutnya">NEXT &raquo;</a>
                <a href="<?= $base_url ?>&page=<?= $total_pages ?>#filter-result" class="btn" style="padding: 0.4rem 0.8rem; margin: 0 0.2rem; font-size: 0.85rem;" title="Halaman Terakhir">LAST &raquo;&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($chart_labels)): ?>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Omset',
                    data: <?= json_encode($chart_omset) ?>,
                    borderColor: '#C9A227',
                    backgroundColor: '#C9A227',
                    borderWidth: 3,
                    tension: 0.3
                },
                {
                    label: 'Keuntungan',
                    data: <?= json_encode($chart_untung) ?>,
                    borderColor: '#87A96B', // hijau tema
                    backgroundColor: '#87A96B',
                    borderWidth: 3,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    },
                    grid: { color: 'rgba(42, 38, 34, 0.1)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                },
                legend: {
                    labels: {
                        font: { family: 'Courier Prime' },
                        color: '#2A2622'
                    }
                }
            }
        }
    });
</script>
<?php endif; ?>

</body>
</html>
