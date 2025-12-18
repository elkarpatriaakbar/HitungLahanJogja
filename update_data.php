<?php
include 'koneksi.php';
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['id'])) {
    header('Content-Type: application/json');
    $id = mysqli_real_escape_string($conn, $input['id']);
    $nama = mysqli_real_escape_string($conn, $input['nama']);
    $luas = $input['luas_lahan'];
    $geojson = mysqli_real_escape_string($conn, $input['geojson_data']);

    // Update Nama, Luas, dan Geometri sekaligus
    $query = "UPDATE poligon SET 
              nama = '$nama', 
              luas_lahan = '$luas', 
              geojson_data = '$geojson' 
              WHERE id = '$id'";

    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}
?>