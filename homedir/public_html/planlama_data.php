<?php
require_once "include/oturum_kontrol.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $firma_id = isset($_GET['firma_id']) ? intval($_GET['firma_id']) : $_SESSION['firma_id'];
    $last_hash = isset($_GET['hash']) ? $_GET['hash'] : '';
    
    try {
        // Basit ve stabil sayaç yaklaşımı
        $check_sql = "SELECT 
                        COUNT(DISTINCT siparisler.id) as aktif_siparis,
                        COUNT(DISTINCT planlama.id) as toplam_planlama,
                        MAX(siparisler.id) as max_siparis_id,
                        MAX(COALESCE(planlama.id, 0)) as max_planlama_id
                      FROM siparisler 
                      LEFT JOIN planlama ON planlama.siparis_id = siparisler.id 
                        AND planlama.firma_id = siparisler.firma_id
                      WHERE siparisler.firma_id = :firma_id 
                        AND siparisler.onay_baslangic_durum = 'evet'
                        AND siparisler.islem != 'iptal'
                        AND (siparisler.aktif = 1 OR siparisler.aktif IS NULL)";
        
        $sth = $conn->prepare($check_sql);
        $sth->bindParam(':firma_id', $firma_id, PDO::PARAM_INT);
        $sth->execute();
        
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        
        // Basit ve stabil hash
        $current_hash = md5(
            intval($result['aktif_siparis']) . '|' .
            intval($result['toplam_planlama']) . '|' .
            intval($result['max_siparis_id']) . '|' .
            intval($result['max_planlama_id'])
        );
        
        // Hash değişmişse değişiklik var
        $has_changes = ($last_hash !== '' && $last_hash !== $current_hash);
        
        echo json_encode([
            'success' => true,
            'has_changes' => $has_changes,
            'hash' => $current_hash,
            'last_hash' => $last_hash,
            'aktif_siparis' => intval($result['aktif_siparis']),
            'toplam_planlama' => intval($result['toplam_planlama']),
            'max_siparis_id' => intval($result['max_siparis_id']),
            'max_planlama_id' => intval($result['max_planlama_id']),
            'timestamp' => time()
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage(),
            'has_changes' => false
        ]);
        exit;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek',
        'has_changes' => false
    ]);
    exit;
}
