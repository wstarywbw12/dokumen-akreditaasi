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

        /* Filter Section */
        .filter-section {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .filter-section .filter-label {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }

        .filter-section select,
        .filter-section input {
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
        .filter-section input:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
            outline: none;
        }

        .filter-section select option {
            background: var(--bg-card);
            color: var(--text-primary);
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

        .btn-filter-reset {
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-filter-reset:hover {
            background: rgba(220, 53, 69, 0.25);
            color: #ff6b6b;
            border-color: rgba(220, 53, 69, 0.5);
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

        .document-card:nth-child(1) { animation-delay: 0.05s; }
        .document-card:nth-child(2) { animation-delay: 0.1s; }
        .document-card:nth-child(3) { animation-delay: 0.15s; }
        .document-card:nth-child(4) { animation-delay: 0.2s; }
        .document-card:nth-child(5) { animation-delay: 0.25s; }
        .document-card:nth-child(6) { animation-delay: 0.3s; }

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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .header-section {
                padding: 20px;
            }

            .header-title {
                font-size: 20px;
            }

            .filter-section {
                padding: 16px 20px;
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

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="filter-label">
                    <i class="bi bi-book me-1"></i> Pilih BAB
                </label>
                <select id="babFilter" class="form-select">
                    <!-- TANPA OPSI "Semua BAB" -->
                    <?php foreach ($allBabList as $bab): ?>
                        <option value="<?= htmlspecialchars($bab['nama_bab']) ?>" 
                                <?= $bab['nama_bab'] == $defaultBab ? 'selected' : '' ?>>
                            <?= htmlspecialchars($bab['nama_bab']) ?> (<?= $bab['total_dokumen'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="filter-label">
                    <i class="bi bi-search me-1"></i> Cari Dokumen
                </label>
                <input 
                    type="text" 
                    id="searchInput"
                    class="form-control" 
                    placeholder="Cari berdasarkan judul dokumen..." 
                    autocomplete="off"
                    style="background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 10px; padding: 10px 14px;"
                >
            </div>
            <div class="col-md-2">
                <button id="resetFilter" class="btn-filter-reset">
                    <i class="bi bi-arrow-counterclockwise me-2"></i> Reset Filter
                </button>
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
                                <?= htmlspecialchars(substr($doc['nama_element'], 0, 100)) . (strlen($doc['nama_element']) > 100 ? '...' : '') ?>
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
                            <a href="<?= htmlspecialchars($doc['url']) ?>" class="doc-url" target="_blank">
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
    // Initialize Select2 - Bisa dipilih, tapi tanpa opsi "Semua BAB"
    $('#babFilter').select2({
        theme: 'bootstrap-5',
        placeholder: 'Pilih BAB',
        allowClear: false, // Tidak bisa clear/remove selection
        width: '100%'
    });

    let debounceTimer = null;
    let currentSearch = '';
    let currentBab = '';

    // Function to highlight text
    function highlightText(text, search) {
        if (!search || !text) return text;
        const regex = new RegExp('(' + search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return String(text).replace(regex, '<span class="highlight">$1</span>');
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
                    ${data.search ? `<p>Maaf, tidak ada dokumen yang cocok dengan kata kunci "<strong>${data.search}</strong>" di BAB "<strong>${data.bab}</strong>"</p>` : `<p>Belum ada dokumen untuk BAB "<strong>${data.bab}</strong>"</p>`}
                </div>
            `);
            return;
        }

        let html = '';
        data.data.forEach((doc, index) => {
            const title = highlightText(doc.judul, data.search);
            const elemen = highlightText(doc.nama_element || '', data.search);
            const standart = highlightText(doc.nama_standart || '', data.search);
            const bab = highlightText(doc.nama_bab || '', data.search);
            
            html += `
                <div class="document-card" data-id="${doc.id}" style="animation-delay: ${(index + 1) * 0.05}s">
                    <div class="doc-icon">
                        <i class="bi bi-file-pdf"></i>
                    </div>
                    <h3 class="doc-title">${title}</h3>
                    
                    ${elemen ? `
                        <div class="doc-elemen">
                            <i class="bi bi-list-check me-1"></i>
                            ${elemen.length > 100 ? elemen.substring(0, 100) + '...' : elemen}
                        </div>
                    ` : ''}
                    
                    ${standart ? `
                        <div class="doc-standart">
                            <strong><i class="bi bi-tag me-1"></i> ${standart}</strong>
                        </div>
                    ` : ''}
                    
                    <div class="doc-bab">
                        <i class="bi bi-book me-1"></i>
                        ${bab || 'N/A'}
                    </div>
                    
                    <div class="doc-meta">
                        <a href="${doc.url}" class="doc-url" target="_blank">
                            <i class="bi bi-eye"></i> Lihat Dokumen
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <span class="doc-id">#${doc.id}</span>
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

    $('#babFilter').on('change', function() {
        performSearch();
    });

    $('#resetFilter').on('click', function() {
        $('#searchInput').val('');
        // Reset ke default BAB (Akses dan Kesinambungan Pelayanan (AKP))
        $('#babFilter').val('Akses dan Kesinambungan Pelayanan (AKP)').trigger('change');
        performSearch();
        $('#searchInput').focus();
    });

    // Keyboard shortcut: Escape to clear search
    $('#searchInput').on('keydown', function(e) {
        if (e.key === 'Escape') {
            $(this).val('');
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

    // Auto resize select2
    $(document).on('select2:open', () => {
        document.querySelector('.select2-container--bootstrap-5 .select2-selection--single').style.width = '100%';
    });
});
</script>

</body>
</html>