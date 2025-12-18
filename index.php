<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoValue Jogja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="topnav">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="brand">
                <i class="fa-solid fa-map-location-dot"></i>
                <span>LAHANJOGJA</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Beranda</a></li>
                <li><a href="list.php">Database</a></li>
                <li><a href="map.php">Peta</a></li>
            </ul>
        </div>
    </nav>

    <main class="container py-5">
        <section class="hero">
            
            <h1 class="mb-4 display-4 fw-bold text-dark">
                Pemetaan Nilai Lahan <br>
                <span style="background: var(--grad-rgb); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Yogyakarta Interaktif
                </span>
            </h1>
            
            <p class="text-muted mx-auto fs-5 mb-5" style="max-width: 650px;">
                Pilih dan hitung lahan kosong yang ingin anda beli di sini. Lokasi jelas dan akurat serta terpercaya.
            </p>
            
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="map.php" class="btn btn-blue btn-lg px-5 py-3 rounded-pill btn-action text-decoration-none">
                    <i class="fa-solid fa-map me-2"></i>Mulai Analisis
                </a>
                <a href="list.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill fw-bold border shadow-sm">
                    <i class="fa-solid fa-database me-2"></i>Data Lahan
                </a>
            </div>
        </section>
    </main>
</body>
</html>