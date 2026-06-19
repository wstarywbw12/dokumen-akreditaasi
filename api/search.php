<?php
// api/search.php
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
$bab_id = isset($_GET['bab_id']) ? (int)$_GET['bab_id'] : 0;

// Build query with joins to get complete data
$query = "SELECT 
    d.id AS dokumen_id,
    d.judul AS dokumen_judul,
    d.url AS dokumen_url,
    d.elm_id,
    d.dok_id,
    e.elemen AS elemen_text,
    e.elemen_id,
    s.nama AS standart_nama,
    s.judul AS standart_judul,
    s.standart_id,
    b.id AS bab_id,
    b.judul AS bab_judul,
    b.kode AS bab_kode,
    b.tahun AS bab_tahun
FROM dokumen d
LEFT JOIN elemen e ON d.elm_id = e.elemen_id
LEFT JOIN standart s ON e.std_id = s.standart_id
LEFT JOIN bab b ON s.bab_id = b.kode
WHERE 1=1";

$params = [];

// Filter by BAB
if ($bab_id > 0) {
    $query .= " AND b.id = :bab_id";
    $params[':bab_id'] = $bab_id;
}

// Search by multiple fields
if (!empty($search)) {
    $query .= " AND (
        d.judul LIKE :search 
        OR d.url LIKE :search 
        OR e.elemen LIKE :search 
        OR s.nama LIKE :search 
        OR s.judul LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY b.id, s.id, d.id DESC";

// Execute query
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$documents = $stmt->fetchAll();

// Format response
$formattedData = [];
foreach ($documents as $doc) {
    $formattedData[] = [
        'id' => $doc['dokumen_id'],
        'judul' => $doc['dokumen_judul'],
        'url' => $doc['dokumen_url'],
        'elm_id' => $doc['elm_id'],
        'dok_id' => $doc['dok_id'],
        'elemen' => $doc['elemen_text'],
        'elemen_id' => $doc['elemen_id'],
        'standart_nama' => $doc['standart_nama'],
        'standart_judul' => $doc['standart_judul'],
        'standart_id' => $doc['standart_id'],
        'bab_id' => $doc['bab_id'],
        'bab_judul' => $doc['bab_judul'],
        'bab_kode' => $doc['bab_kode'],
        'bab_tahun' => $doc['bab_tahun']
    ];
}

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => $formattedData,
    'total' => count($formattedData),
    'search' => $search,
    'bab_id' => $bab_id
]);
?>