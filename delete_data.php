<?php
include 'koneksi.php';
$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['id'])) {
    header('Content-Type: application/json');
    $id = mysqli_real_escape_string($conn, $input['id']);
    $query = "DELETE FROM poligon WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}
?>