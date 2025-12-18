<?php
include 'koneksi.php';

$query = mysqli_query($conn, "SELECT * FROM poligon");
$features = [];

while ($row = mysqli_fetch_assoc($query)) {
    $geometry = json_decode($row['geojson_data']);
    
    $features[] = [
        "type" => "Feature",
        "properties" => [
            "id" => $row['id'],
            "name" => $row['nama'],
            "luas" => $row['luas_lahan'],
            "price" => $row['price_per_m2'] ?? 3500000
        ],
        "geometry" => $geometry
    ];
}

$geojson = [
    "type" => "FeatureCollection",
    "features" => $features
];

header('Content-Type: application/json');
echo json_encode($geojson);
?>