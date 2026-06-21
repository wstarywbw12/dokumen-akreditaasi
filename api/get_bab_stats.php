<?php
// api/get_bab_stats.php - Single Table Version
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

// Get BAB statistics from single table
$query = "SELECT 
    nama_bab,
    COUNT(*) AS total_dokumen,
    MAX(id) AS last_id,
    MIN(id) AS first_id
FROM dokuments 
WHERE nama_bab IS NOT NULL AND nama_bab != ''
GROUP BY nama_bab
ORDER BY nama_bab";

$stmt = $db->prepare($query);
$stmt->execute();
$babStats = $stmt->fetchAll();

// Calculate summary
$totalDokumen = 0;
foreach ($babStats as $bab) {
    $totalDokumen += $bab['total_dokumen'];
}

$totalBab = count($babStats);
$avgDokumen = $totalBab > 0 ? round($totalDokumen / $totalBab, 2) : 0;

echo json_encode([
    'success' => true,
    'data' => $babStats,
    'summary' => [
        'total_bab' => $totalBab,
        'total_dokumen' => $totalDokumen,
        'avg_dokumen_per_bab' => $avgDokumen
    ]
]);
?>