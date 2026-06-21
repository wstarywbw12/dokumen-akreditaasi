<?php
// api/search_dokuments.php - Search Hanya Berdasarkan Judul
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

// Get parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$bab = isset($_GET['bab']) ? trim($_GET['bab']) : '';

// Jika tidak ada filter BAB, default ke 'Akses dan Kesinambungan Pelayanan (AKP)'
if (empty($bab)) {
    $bab = 'Akses dan Kesinambungan Pelayanan (AKP)';
}

// Log untuk debugging
error_log("Search: '$search', BAB: '$bab'");

// Build query - menggunakan LIKE untuk lebih fleksibel
$query = "SELECT 
    id,
    judul,
    url,
    nama_bab,
    nama_standart,
    nama_element,
    created_at,
    updated_at
FROM dokuments 
WHERE 1=1";

$params = [];

// Filter by BAB - menggunakan LIKE untuk partial match (lebih aman)
$query .= " AND nama_bab LIKE :bab";
$params[':bab'] = '%' . $bab . '%';
error_log("Filtering by BAB: $bab");

// SEARCH HANYA BERDASARKAN JUDUL SAJA
if (!empty($search)) {
    $query .= " AND judul LIKE :search";
    $params[':search'] = '%' . $search . '%';
    error_log("Searching for: $search (hanya di judul)");
}

$query .= " ORDER BY id DESC";

// Execute query
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$documents = $stmt->fetchAll();

// Log hasil
error_log("Found " . count($documents) . " documents");

// Format response
$formattedData = [];
foreach ($documents as $doc) {
    $formattedData[] = [
        'id' => $doc['id'],
        'judul' => $doc['judul'],
        'url' => $doc['url'],
        'nama_bab' => $doc['nama_bab'],
        'nama_standart' => $doc['nama_standart'],
        'nama_element' => $doc['nama_element'],
        'created_at' => $doc['created_at'],
        'updated_at' => $doc['updated_at']
    ];
}

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => $formattedData,
    'total' => count($formattedData),
    'search' => $search,
    'bab' => $bab,
    'search_field' => 'judul'
]);
