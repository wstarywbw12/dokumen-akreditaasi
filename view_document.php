<?php

require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

$config = require __DIR__ . '/config/survei.php';

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    die('ID dokumen tidak valid');
}

try {

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT id, judul, url
        FROM dokumen
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die('Dokumen tidak ditemukan');
    }

    $url = trim($doc['url']);

    if (!$url) {
        die('URL dokumen kosong');
    }

    $currentUser = getCurrentUser();

    $cookieDir = __DIR__ . '/cookies';

    if (!is_dir($cookieDir)) {
        mkdir($cookieDir, 0755, true);
    }

    /**
     * Cookie per user
     */
    $cookieFile = $cookieDir . '/user_' . ($currentUser['id'] ?? session_id()) . '.cookie';

    /**
     * Login ulang jika:
     * - cookie belum ada
     * - cookie expired
     */
    $needLogin = false;

    if (!file_exists($cookieFile)) {

        $needLogin = true;

    } else {

        $cookieAge = time() - filemtime($cookieFile);

        if ($cookieAge > $config['cookie_lifetime']) {
            $needLogin = true;
        }
    }

    /**
     * LOGIN
     */
    if ($needLogin) {

        if (file_exists($cookieFile)) {
            unlink($cookieFile);
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $config['login_url'],
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => $config['email'],
                'password' => $config['password']
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $loginResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Login gagal: ' . curl_error($ch));
        }

        $loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($loginHttpCode != 200) {

            throw new Exception(
                'Login ke survei-v2 gagal. HTTP Code: ' .
                $loginHttpCode
            );
        }
    }

    /**
     * AMBIL PDF
     */
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_TIMEOUT => 60
    ]);

    $fileContent = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(
            'Download gagal: ' . curl_error($ch)
        );
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    curl_close($ch);

    /**
     * Jika cookie ternyata sudah invalid,
     * login ulang sekali lalu retry.
     */
    if (
        $httpCode == 302 ||
        stripos($fileContent, 'login') !== false
    ) {

        if (file_exists($cookieFile)) {
            unlink($cookieFile);
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $config['login_url'],
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => $config['email'],
                'password' => $config['password']
            ])
        ]);

        curl_exec($ch);
        curl_close($ch);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $fileContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);
    }

    /**
     * Validasi hasil
     */
    if ($httpCode != 200) {

        throw new Exception(
            'Gagal mengambil dokumen. HTTP Code: ' .
            $httpCode
        );
    }

    /**
     * PDF
     */
    if (
        strpos($fileContent, '%PDF') === 0 ||
        stripos($contentType, 'pdf') !== false
    ) {

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename(parse_url($url, PHP_URL_PATH)) . '"');
        header('Content-Length: ' . strlen($fileContent));

        echo $fileContent;
        exit;
    }

    /**
     * File lain
     */
    header(
        'Content-Type: ' .
        ($contentType ?: 'application/octet-stream')
    );

    echo $fileContent;

} catch (Exception $e) {

    http_response_code(500);

    echo '<pre>';
    echo 'ERROR:' . PHP_EOL;
    echo $e->getMessage();
    echo '</pre>';
}