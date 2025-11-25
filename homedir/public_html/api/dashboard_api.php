<?php
// CLI için REQUEST_URI kontrolü
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/api/dashboard_api.php';
}

require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/functions.php';

header('Content-Type: application/json');

try {
    $data = [];
    $firma_id = $_SESSION['firma_id'] ?? null;
    
    if(!$firma_id) {
        jsonResponse(false, 'Firma bilgisi bulunamadı');
        exit;
    }
    
    // Bugünkü üretim (firma bazlı)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(biten_urun_adedi), 0) as toplam 
        FROM planlama 
        WHERE DATE(tarih) = CURDATE() AND firma_id = :firma_id
    ");
    $stmt->execute(['firma_id' => $firma_id]);
    $data['bugun_uretim'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
    
    // Aktif işler (firma bazlı)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as toplam 
        FROM planlama 
        WHERE mevcut_asama < asama_sayisi AND firma_id = :firma_id
    ");
    $stmt->execute(['firma_id' => $firma_id]);
    $data['aktif_is'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
    
    // Çalışan makina (firma bazlı - son 30 dakikada aktif)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT m.id) as toplam
        FROM makinalar m
        INNER JOIN uretim_islem_tarihler uit ON m.id = uit.makina_id
        WHERE uit.baslatma_tarih >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        AND m.firma_id = :firma_id
    ");
    $stmt->execute(['firma_id' => $firma_id]);
    $data['calisan_makina'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
    
    // Toplam aktif makina (firma bazlı)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as toplam 
        FROM makinalar 
        WHERE firma_id = :firma_id AND durumu = 'aktif'
    ");
    $stmt->execute(['firma_id' => $firma_id]);
    $data['toplam_makina'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
    
    // Bekleyen sipariş (firma bazlı)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as toplam 
        FROM siparisler 
        WHERE durum = 'bekliyor' AND firma_id = :firma_id
    ");
    $stmt->execute(['firma_id' => $firma_id]);
    $data['bekleyen_siparis'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
    
    // Arızalı makina (firma bazlı)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as toplam 
        FROM makinalar 
        WHERE durumu = 'arizali' AND firma_id = :firma_id
    ");
    $stmt->execute(['firma_id' => $firma_id]);
    $data['arizali_makina'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
    
    jsonResponse(true, 'Dashboard verileri', $data);
    
} catch (PDOException $e) {
    error_log('Dashboard API Error: ' . $e->getMessage());
    jsonResponse(false, 'Veritabanı hatası: ' . $e->getMessage());
}
