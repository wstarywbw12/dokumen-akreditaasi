<?php
require_once 'includes/session.php';
requireLogin();

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Akreditasi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #111927;
            --bg-card: #1a2332;
            --text-primary: #e8edf5;
            --text-secondary: #a0b4c8;
            --accent: #14b8a6;
            --border: #2a3a4a;
            --accent-teal: #0d9488;
            --accent-teal-dark: #0f766e;
            --accent-teal-light: #14b8a6;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .btn-biru {
            background: rgba(13, 148, 136, 0.15);
            color: var(--accent-teal-light);
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(13, 148, 136, 0.2);
            transition: all 0.3s ease;
        }

        .btn-biru:hover {
            background: rgba(13, 148, 136, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.2);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: auto;
            padding: 40px 20px;
        }
        
        .menu-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 25px;
            text-align: center;
            cursor: pointer;
            transition: .3s;
            height: 100%;
            text-decoration: none;
            color: var(--text-primary);
            display: block;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            color: var(--text-primary);
            box-shadow: 0 10px 25px rgba(20, 184, 166, .2);
        }

        .menu-icon {
            font-size: 60px;
            color: var(--accent);
            margin-bottom: 20px;
        }

        .menu-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .menu-desc {
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
    </style>
</head>

<body>

    <div class="dashboard-container">

        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="header-icon">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div>
                            <h1 class="header-title">Dokumen Akreditasi 2026 RSUD Jombang</h1>
                            <p class="header-subtitle"> Selamat datang,<?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?> </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-md-end gap-3 flex-wrap">
                        <div class="user-menu">

                            <a href="logout.php" class="btn-logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">

            <div class="col-md-6">
                <div class="menu-card">
                    <div class="menu-icon">
                        <i class="bi bi-folder2-open"></i>
                    </div>

                    <div class="menu-title">
                        Dokumen
                    </div>

                    <div class="menu-desc">
                        Pencarian dan pengelolaan dokumen akreditasi
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <a href="dokument.php" target="_blank" class="btn btn-biru w-100">
                                Lihat Dokumen
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="menu-card">
                    <div class="menu-icon">
                        <i class="bi bi-mortarboard"></i>
                    </div>

                    <div class="menu-title">
                        Edukasi
                    </div>

                    <div class="menu-desc">
                        Materi edukasi dan pembelajaran akreditasi
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-6">
                            <a href="pdf/Materi-Edukasi-Kolaboratif-2026.pdf" target="_blank" class="btn btn-biru w-100">
                                Materi Edukasi Kolaboratif
                            </a>
                        </div>

                        <div class="col-6">
                            <a href="pdf/Materi-Edukasi-2026.pdf" target="_blank" class="btn btn-biru w-100">
                                Materi Edukasi 2026
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</body>

</html>