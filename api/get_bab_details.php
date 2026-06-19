<?php
// api/get_bab_details.php
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

// Get all BAB with statistics
$query = "SELECT 
    b.id,
    b.judul,
    b.elemen AS total_elemen,
    b.progress,
    b.nilai,
    b.kode,
    b.tahun,
    COUNT(DISTINCT s.id) AS total_standart,
    COUNT(DISTINCT d.id) AS total_dokumen,
    COUNT(DISTINCT e.id) AS total_elemen_terisi
FROM bab b
LEFT JOIN standart s ON b.kode = s.bab_id
LEFT JOIN elemen e ON s.standart_id = e.std_id
LEFT JOIN dokumen d ON e.elemen_id = d.elm_id
GROUP BY b.id, b.judul, b.elemen, b.progress, b.nilai, b.kode, b.tahun
ORDER BY b.id";

$stmt = $db->prepare($query);
$stmt->execute();
$babList = $stmt->fetchAll();

// Calculate total average
$totalNilai = 0;
$totalProgress = 0;
$count = count($babList);

foreach ($babList as $bab) {
    $totalNilai += $bab['nilai'];
    $totalProgress += $bab['progress'];
}

$avgNilai = $count > 0 ? round($totalNilai / $count, 2) : 0;
$avgProgress = $count > 0 ? round($totalProgress / $count, 2) : 0;

echo json_encode([
    'success' => true,
    'data' => $babList,
    'summary' => [
        'total_bab' => $count,
        'avg_nilai' => $avgNilai,
        'avg_progress' => $avgProgress
    ]
]);
?>