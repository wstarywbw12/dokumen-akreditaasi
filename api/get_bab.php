<?php
// api/get_bab.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get all BAB
$query = "SELECT id, judul, kode, tahun, elemen, progress, nilai FROM bab ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$babList = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => $babList
]);
?>