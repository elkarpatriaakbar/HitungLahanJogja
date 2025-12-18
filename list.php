<?php 
include 'koneksi.php'; 

$limit = 10; 
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($page > 1) ? ($page * $limit) - $limit : 0;

$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM poligon");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_halaman = ceil($total_data / $limit);
if ($total_halaman < 1) { $total_halaman = 1; }

$query = mysqli_query($conn, "SELECT * FROM poligon ORDER BY id DESC LIMIT $limit OFFSET $offset");
$no = $offset + 1; 
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database - GeoValue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .badge-blue { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .text-green-bold { color: #059669; font-weight: 800; }
        .pagination .page-link { border: none; margin: 0 4px; border-radius: 8px; color: #64748b; }
        .pagination .page-item.active .page-link { background: var(--grad-blue); color: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="brand"><i class="fa-solid fa-map-location-dot"></i><span>LAHANJOGJA</span></div>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="list.php" class="active">Database</a></li>
                <li><a href="map.php">Peta</a></li>
            </ul>
        </div>
    </nav>

    <main class="container mt-5 mb-5">
        <div class="table-wrap">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h2 class="fw-bold m-0 text-dark">Database Lahan</h2>
                    <p class="text-muted small mb-0">Total Records: <b><?= $total_data ?></b></p>
                </div>
                <a href="map.php" class="btn btn-blue btn-action text-decoration-none px-4 py-2 small">
                    <i class="fas fa-plus me-2"></i>Input Baru
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Nama Kawasan</th>
                            <th>Luas Area</th>
                            <th>Harga / m²</th>
                            <th>Total Valuasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query)): 
                                $harga_satuan = $row['price_per_m2'] ?? 3500000;
                                $total_harga = $row['luas_lahan'] * $harga_satuan;
                            ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $no++ ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama']) ?></td>
                                <td>
                                    <span class="badge badge-blue rounded-pill px-3 py-2">
                                        <?= number_format($row['luas_lahan'], 0, ',', '.') ?> m²
                                    </span>
                                </td>
                                <td class="text-muted">Rp <?= number_format($harga_satuan, 0, ',', '.') ?></td>
                                <td class="text-green-bold">
                                    Rp <?= number_format($total_harga, 0, ',', '.') ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan='5' class='text-center py-5 text-muted'>Data kosong.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <ul class="pagination mb-0">
                    <?php for($i=1; $i <= $total_halaman; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?halaman=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>
    </main>
</body>
</html>