<?php
require_once "include/oturum_kontrol.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $firma_id = isset($_GET['firma_id']) ? intval($_GET['firma_id']) : $_SESSION['firma_id'];
    $last_hash = isset($_GET['hash']) ? $_GET['hash'] : '';
    
    try {
        // Sipariş durumlarının checksum'ını hesapla
        // onay_baslangic_durum, islem ve aktif kolonlarını kontrol et
        $check_sql = "SELECT 
                        MD5(GROUP_CONCAT(
                            CONCAT(id, '|', onay_baslangic_durum, '|', islem, '|', COALESCE(aktif, 1))
                            ORDER BY id
                        )) as current_hash,
                        COUNT(*) as toplam
                      FROM siparisler 
                      WHERE firma_id = :firma_id 
                        AND islem != 'silindi'";
        
        $sth = $conn->prepare($check_sql);
        $sth->bindParam(':firma_id', $firma_id, PDO::PARAM_INT);
        $sth->execute();
        
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $current_hash = $result['current_hash'];
        
        // Hash değişmişse değişiklik var demektir
        $has_changes = ($last_hash !== '' && $last_hash !== $current_hash);
        
        echo json_encode([
            'success' => true,
            'has_changes' => $has_changes,
            'hash' => $current_hash,
            'toplam' => intval($result['toplam']),
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
