<?php
// dokumen.php - Single Table Version (Tanpa Modal) - Bisa Filter Per Bab
require_once 'config/database.php';
require_once 'includes/session.php';

// Require login to access this page
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Default BAB yang akan ditampilkan
$defaultBab = 'Akses dan Kesinambungan Pelayanan (AKP)';

// Get all BAB list for filter (SEMUA BAB, tapi tanpa opsi "Semua BAB")
$babQuery = "SELECT DISTINCT 
    nama_bab,
    COUNT(*) AS total_dokumen
FROM dokuments 
WHERE nama_bab IS NOT NULL 
    AND nama_bab != ''
GROUP BY nama_bab
ORDER BY nama_bab";

$babStmt = $db->prepare($babQuery);
$babStmt->execute();
$allBabList = $babStmt->fetchAll();

// Jika tidak ada data BAB, gunakan default
if (empty($allBabList)) {
    $allBabList = [['nama_bab' => 'Akses dan Kesinambungan Pelayanan (AKP)', 'total_dokumen' => 0]];
}

// Get documents - default ke Akses dan Kesinambungan Pelayanan (AKP) menggunakan LIKE
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
WHERE nama_bab LIKE :bab
ORDER BY id DESC";

$stmt = $db->prepare($query);
$stmt->bindValue(':bab', '%' . $defaultBab . '%');
$stmt->execute();
$documents = $stmt->fetchAll();

$currentUser = getCurrentUser();

// Get total documents count
$totalDocuments = count($documents);
$totalBab = count($allBabList);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Akreditasi - Live Search</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        /* Custom Dark Theme with Teal Blue Accent */
        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #111927;
            --bg-card: #1a2332;
            --bg-input: #1a2332;
            --text-primary: #e8edf5;
            --text-secondary: #a0b4c8;
            --accent-teal: #0d9488;
            --accent-teal-dark: #0f766e;
            --accent-teal-light: #14b8a6;
            --border-color: #2a3a4a;
            --shadow-color: rgba(13, 148, 136, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header-section {
            background: var(--bg-secondary);
            border-radius: 20px;
            padding: 25px 35px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-teal), var(--accent-teal-light));
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--accent-teal-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .header-icon {
            font-size: 28px;
            color: var(--accent-teal);
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(13, 148, 136, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--accent-teal-light);
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .btn-logout {
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: rgba(220, 53, 69, 0.25);
            color: #ff6b6b;
            border-color: rgba(220, 53, 69, 0.5);
            transform: translateY(-1px);
        }

        /* Stats Badge - Tanpa Modal */
        .stats-badge {
            background: rgba(13, 148, 136, 0.15);
            color: var(--accent-teal-light);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(13, 148, 136, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: default;
        }

        /* Filter Section - Baris Tunggal */
        .filter-section {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .filter-section .filter-label {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 4px;
            display: block;
        }

        .filter-section select,
        .filter-section .search-wrapper input {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .filter-section select:focus,
        .filter-section .search-wrapper input:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
            outline: none;
        }

        .filter-section select option {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        .filter-bab-wrapper {
            flex: 1;
            min-width: 200px;
        }

        .filter-search-wrapper {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-wrapper {
            position: relative;
            width: 100%;
        }

        .search-wrapper input {
            width: 100%;
            padding-right: 44px !important;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-wrapper input:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
            outline: none;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 16px;
            pointer-events: none;
        }

        .search-wrapper input {
            padding-left: 40px !important;
        }

        /* Button Clear (X) - di dalam search box */
        .btn-clear-search {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 20px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: none;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .btn-clear-search:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-clear-search.visible {
            display: flex;
        }

        /* Select2 Custom */
        .select2-container--bootstrap-5 .select2-selection {
            background: var(--bg-input) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px !important;
            min-height: 44px !important;
            color: var(--text-primary) !important;
        }

        .select2-container--bootstrap-5 .select2-selection__rendered {
            color: var(--text-primary) !important;
            padding: 8px 14px !important;
        }

        .select2-container--bootstrap-5 .select2-selection__placeholder {
            color: var(--text-secondary) !important;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px !important;
        }

        .select2-container--bootstrap-5 .select2-results__option {
            color: var(--text-primary) !important;
            padding: 8px 14px !important;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background: rgba(13, 148, 136, 0.2) !important;
            color: var(--accent-teal-light) !important;
        }

        .select2-container--bootstrap-5 .select2-selection__clear {
            color: var(--text-secondary) !important;
        }

        /* Document Grid */
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }

        .document-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.4s ease forwards;
            opacity: 0;
        }

        .document-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .document-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .document-card:nth-child(3) {
            animation-delay: 0.15s;
        }

        .document-card:nth-child(4) {
            animation-delay: 0.2s;
        }

        .document-card:nth-child(5) {
            animation-delay: 0.25s;
        }

        .document-card:nth-child(6) {
            animation-delay: 0.3s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .document-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-teal), var(--accent-teal-light));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .document-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent-teal);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        .document-card:hover::before {
            opacity: 1;
        }

        .doc-icon {
            width: 48px;
            height: 48px;
            background: rgba(13, 148, 136, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--accent-teal-light);
            margin-bottom: 16px;
            flex-shrink: 0;
        }

        .doc-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .doc-elemen {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            background: rgba(13, 148, 136, 0.05);
            padding: 8px 12px;
            border-radius: 8px;
            border-left: 3px solid var(--accent-teal);
        }

        .doc-standart {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .doc-standart strong {
            color: var(--text-primary);
        }

        .doc-bab {
            font-size: 12px;
            color: var(--text-secondary);
            opacity: 0.7;
            margin-bottom: 12px;
        }

        .doc-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
        }

        .doc-url {
            color: var(--accent-teal-light);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .doc-url:hover {
            color: var(--accent-teal);
            gap: 10px;
        }

        .doc-id {
            color: var(--text-secondary);
            font-size: 11px;
            opacity: 0.6;
        }

        .highlight {
            background: rgba(13, 148, 136, 0.3);
            color: var(--accent-teal-light);
            padding: 0 4px;
            border-radius: 3px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            animation: fadeInUp 0.4s ease;
            grid-column: 1/-1;
        }

        .empty-state i {
            font-size: 64px;
            color: var(--text-secondary);
            opacity: 0.3;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .search-loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--text-secondary);
            font-size: 14px;
            padding: 30px;
            grid-column: 1/-1;
        }

        .search-loading.active {
            display: flex;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid var(--border-color);
            border-top: 3px solid var(--accent-teal-light);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .doc-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-primary);
    line-height: 1.5;
    word-break: break-word;
    overflow-wrap: break-word;
}

.doc-elemen {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 10px;

    background: rgba(13, 148, 136, 0.05);
    padding: 8px 12px;
    border-radius: 8px;
    border-left: 3px solid var(--accent-teal);

    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.highlight {
    background: rgba(13, 148, 136, 0.35);
    color: #2dd4bf;
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 600;
}

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .header-section {
                padding: 20px;
            }

            .header-title {
                font-size: 20px;
            }

            .filter-section {
                padding: 16px;
                flex-direction: column;
                align-items: stretch;
            }

            .filter-bab-wrapper {
                flex: 1 1 auto;
                min-width: unset;
            }

            .filter-search-wrapper {
                flex: 1 1 auto;
                min-width: unset;
            }

            .document-grid {
                grid-template-columns: 1fr;
            }

            .user-menu {
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .user-info {
                text-align: left;
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .main-container {
                padding: 10px;
            }

            .header-title {
                font-size: 18px;
            }

            .document-card {
                padding: 18px;
            }

            .header-section {
                padding: 16px;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-teal);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-teal-light);
        }
    </style>
</head>

<body>

    <div class="main-container">
        <!-- Header -->
        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="header-icon">
                            <i class="bi bi-folder2-open"></i>
                        </div>
                        <div>
                            <h1 class="header-title">Dokumen Akreditasi</h1>
                            <p class="header-subtitle">Cari dan filter dokumen akreditasi per BAB</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-md-end gap-3 flex-wrap">
                        <!-- Stats Badge - Tanpa Modal -->
                        <span class="stats-badge">
                            <i class="bi bi-file-earmark-text"></i>
                            <span id="totalDocuments"><?= $totalDocuments ?></span> Dokumen
                            <span style="opacity:0.5; margin-left:4px;">|</span>
                            <span style="font-size:12px; opacity:0.7;"><?= $totalBab ?> BAB</span>
                        </span>
                        <div class="user-menu">
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></div>
                                <div class="user-role">
                                    <i class="bi bi-shield-check"></i> <?= htmlspecialchars($currentUser['role'] ?? 'user') ?>
                                </div>
                            </div>
                            <div class="user-avatar">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <a href="logout.php" class="btn-logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section - Baris Tunggal -->
        <div class="filter-section">
            <!-- Filter BAB -->
           <div class="row">
             <div class="col-md-6 mb-3 mb-md-0">
                <label class="filter-label">
                    <i class="bi bi-book me-1"></i> Filter BAB
                </label>
                <select id="babFilter" class="form-select">
                    <?php foreach ($allBabList as $bab): ?>
                        <option value="<?= htmlspecialchars($bab['nama_bab']) ?>"
                            <?= $bab['nama_bab'] == $defaultBab ? 'selected' : '' ?>>
                            <?= htmlspecialchars($bab['nama_bab']) ?> (<?= $bab['total_dokumen'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cari Dokumen dengan tombol X -->
            <div class="col-md-6 mb-3 mb-md-0">
                <label class="filter-label">
                    <i class="bi bi-search me-1"></i> Cari Dokumen
                </label>
                <div class="search-wrapper">
                    <i class="bi bi-search search-icon"></i>
                    <input
                        type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Cari berdasarkan judul dokumen..."
                        autocomplete="off">
                    <button id="clearSearchBtn" class="btn-clear-search" title="Hapus pencarian">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
           </div>
        </div>

        <!-- Document Grid -->
        <div id="documentContainer">
            <div class="document-grid" id="documentGrid">
                <?php if (count($documents) > 0): ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-card" data-id="<?= $doc['id'] ?>">
                            <div class="doc-icon">
                                <i class="bi bi-file-pdf"></i>
                            </div>
                            <h3 class="doc-title"><?= htmlspecialchars($doc['judul']) ?></h3>

                            <?php if (!empty($doc['nama_element'])): ?>
                                <div class="doc-elemen">
                                    <i class="bi bi-list-check me-1"></i>
                                    <?= htmlspecialchars(substr($doc['nama_element'], 0, 83)) . (strlen($doc['nama_element']) > 83 ? '...' : '') ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($doc['nama_standart'])): ?>
                                <div class="doc-standart">
                                    <strong><i class="bi bi-tag me-1"></i> <?= htmlspecialchars($doc['nama_standart']) ?></strong>
                                </div>
                            <?php endif; ?>

                            <div class="doc-bab">
                                <i class="bi bi-book me-1"></i>
                                <?= htmlspecialchars($doc['nama_bab'] ?? 'N/A') ?>
                            </div>

                            <div class="doc-meta">
                                <a href="view_document.php?id=<?= $doc['id'] ?>" class="doc-url" target="_blank">
                                    <i class="bi bi-eye"></i> Lihat Dokumen
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                                <span class="doc-id">#<?= $doc['id'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>Tidak ada dokumen</h4>
                        <p>Silakan pilih BAB lain atau jalankan pengambilan data</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Loading Indicator -->
            <div class="search-loading" id="searchLoading">
                <div class="spinner"></div>
                <span>Mencari dokumen...</span>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#babFilter').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih BAB',
                allowClear: false,
                width: '100%'
            });

            let debounceTimer = null;
            let currentSearch = '';
            let currentBab = '';

            // Function to highlight text
            function escapeHtml(text) {
    return $('<div>').text(text).html();
}

function highlightText(text, search) {

    if (!text) return '';

    text = escapeHtml(text);

    if (!search) return text;

    const escapedSearch = search.replace(
        /[.*+?^${}()|[\]\\]/g,
        '\\$&'
    );

    const regex = new RegExp(
        '(' + escapedSearch + ')',
        'gi'
    );

    return text.replace(
        regex,
        '<span class="highlight">$1</span>'
    );
}

            // Function to toggle clear button visibility
            function toggleClearButton() {
                const searchVal = $('#searchInput').val();
                if (searchVal && searchVal.trim().length > 0) {
                    $('#clearSearchBtn').addClass('visible');
                } else {
                    $('#clearSearchBtn').removeClass('visible');
                }
            }

            // Function to update documents
           function updateDocuments(data) {

    const grid = $('#documentGrid');
    const stats = $('#totalDocuments');

    stats.text(data.total);

    if (data.data.length === 0) {

        grid.html(`
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>Tidak ada dokumen ditemukan</h4>
                ${
                    data.search
                    ? `<p>Tidak ada dokumen yang cocok dengan "<strong>${data.search}</strong>"</p>`
                    : `<p>Belum ada dokumen pada BAB ini</p>`
                }
            </div>
        `);

        return;
    }

    let html = '';

    data.data.forEach((doc, index) => {

        const title = highlightText(
            doc.judul || '',
            data.search
        );

        const standart = highlightText(
            doc.nama_standart || '',
            data.search
        );

        const bab = highlightText(
            doc.nama_bab || '',
            data.search
        );

        let elemenText = doc.nama_element || '';

if (elemenText.length > 83) {
    elemenText = elemenText.slice(0, 83) + '...';
}

const elemen = highlightText(
    elemenText,
    data.search
);

        html += `
            <div
                class="document-card"
                data-id="${doc.id}"
                style="animation-delay:${(index + 1) * 0.05}s"
            >

                <div class="doc-icon">
                    <i class="bi bi-file-pdf"></i>
                </div>

                <h3 class="doc-title">
                    ${title}
                </h3>

                ${
                    elemen
                    ? `
                    <div class="doc-elemen">
                        <i class="bi bi-list-check me-1"></i>
                        ${elemen}
                    </div>
                    `
                    : ''
                }

                ${
                    standart
                    ? `
                    <div class="doc-standart">
                        <strong>
                            <i class="bi bi-tag me-1"></i>
                            ${standart}
                        </strong>
                    </div>
                    `
                    : ''
                }

                <div class="doc-bab">
                    <i class="bi bi-book me-1"></i>
                    ${bab}
                </div>

                <div class="doc-meta">
                    <a
                        href="view_document.php?id=${doc.id}"
                        class="doc-url"
                        target="_blank"
                    >
                        <i class="bi bi-eye"></i>
                        Lihat Dokumen
                        <i class="bi bi-arrow-right"></i>
                    </a>

                    <span class="doc-id">
                        #${doc.id}
                    </span>
                </div>

            </div>
        `;
    });

    grid.html(html);
}

            // Function to perform search
            function performSearch() {
                const searchValue = $('#searchInput').val().trim();
                const babValue = $('#babFilter').val() || '';

                currentSearch = searchValue;
                currentBab = babValue;

                // Toggle clear button
                toggleClearButton();

                // Show loading
                $('#searchLoading').addClass('active');

                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }

                debounceTimer = setTimeout(() => {
                    $.ajax({
                        url: 'api/search_dokuments.php',
                        method: 'GET',
                        data: {
                            search: searchValue,
                            bab: babValue
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                updateDocuments(response);
                            }
                            $('#searchLoading').removeClass('active');
                        },
                        error: function() {
                            console.error('Error fetching data');
                            $('#searchLoading').removeClass('active');
                        }
                    });
                }, 400);
            }

            // Event listeners
            $('#searchInput').on('input', function() {
                performSearch();
            });

            // Clear search button
            $('#clearSearchBtn').on('click', function() {
                $('#searchInput').val('');
                toggleClearButton();
                performSearch();
                $('#searchInput').focus();
            });

            $('#babFilter').on('change', function() {
                performSearch();
            });

            // Keyboard shortcut: Escape to clear search
            $('#searchInput').on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $(this).val('');
                    toggleClearButton();
                    performSearch();
                    $(this).blur();
                }
            });

            // Initial load animation
            $('.document-card').each(function(index) {
                $(this).css('animation-delay', (index + 1) * 0.05 + 's');
            });

            // Focus input on page load
            $('#searchInput').focus();

            // Initial check for clear button
            toggleClearButton();
        });
    </script>

</body>

</html>