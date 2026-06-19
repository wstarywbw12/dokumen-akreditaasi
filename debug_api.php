<?php
// debug_api.php
// Run this file to see the structure of API response

$api_url = 'http://192.168.10.20:9090/getdata';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error: HTTP Code " . $httpCode);
}

$data = json_decode($response, true);

echo "=== API RESPONSE STRUCTURE ===\n\n";

// Check if data exists
if (isset($data['data'])) {
    echo "Total BAB: " . count($data['data']) . "\n\n";
    
    foreach ($data['data'] as $index => $bab) {
        echo "BAB " . ($index + 1) . ":\n";
        echo "  - judul: " . ($bab['judul'] ?? 'N/A') . "\n";
        echo "  - elemen: " . ($bab['elemen'] ?? 'N/A') . "\n";
        echo "  - progress: " . ($bab['progress'] ?? 'N/A') . "\n";
        echo "  - nilai: " . ($bab['nilai'] ?? 'N/A') . "\n";
        echo "  - nomor: " . ($bab['nomor'] ?? 'N/A') . "\n";
        
        if (isset($bab['data']) && is_array($bab['data'])) {
            echo "  - Total Standart: " . count($bab['data']) . "\n";
            
            foreach ($bab['data'] as $sIndex => $standart) {
                echo "    Standart " . ($sIndex + 1) . ":\n";
                echo "      - nama: " . ($standart['nama'] ?? 'N/A') . "\n";
                echo "      - judul: " . (isset($standart['judul']) ? substr($standart['judul'], 0, 50) . '...' : 'N/A') . "\n";
                echo "      - desc: " . (isset($standart['desc']) ? substr($standart['desc'], 0, 50) . '...' : 'N/A') . "\n";
                
                if (isset($standart['data']) && is_array($standart['data'])) {
                    echo "      - Total Elemen: " . count($standart['data']) . "\n";
                    
                    $firstElemen = reset($standart['data']);
                    if ($firstElemen) {
                        echo "      - Contoh Elemen:\n";
                        echo "        - element: " . (isset($firstElemen['element']) ? substr($firstElemen['element'], 0, 80) . '...' : 'N/A') . "\n";
                        echo "        - skor: " . ($firstElemen['skor'] ?? 'N/A') . "\n";
                        
                        if (isset($firstElemen['bukti'])) {
                            $bukti = $firstElemen['bukti'];
                            if (is_array($bukti)) {
                                if (isset($bukti[0])) {
                                    echo "        - bukti: Array of " . count($bukti) . " documents\n";
                                    if (count($bukti) > 0) {
                                        echo "          - First doc: " . ($bukti[0]['judul'] ?? 'N/A') . "\n";
                                    }
                                } else if (isset($bukti['judul'])) {
                                    echo "        - bukti: Single document\n";
                                    echo "          - judul: " . ($bukti['judul'] ?? 'N/A') . "\n";
                                    echo "          - url: " . (isset($bukti['url']) ? substr($bukti['url'], 0, 60) . '...' : 'N/A') . "\n";
                                }
                            }
                        }
                    }
                }
                echo "\n";
            }
        }
        echo "\n";
    }
} else {
    echo "No 'data' key found in response\n";
    echo "Response keys: " . print_r(array_keys($data), true) . "\n";
}

// Show full structure of first BAB
echo "\n=== FULL STRUCTURE OF FIRST BAB ===\n";
if (isset($data['data'][0])) {
    echo print_r($data['data'][0], true);
}
?>