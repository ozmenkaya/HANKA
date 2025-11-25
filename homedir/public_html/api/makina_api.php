<?php
/**
 * Makina İş Ekranı - AJAX API
 * Sayfa yenilemeden işlem yapma
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once '../include/db.php';
require_once '../include/functions.php';

// Yetki kontrol
if(!isset($_SESSION['kullanici_id'])) {
    jsonResponse(false, 'Oturum sonlandırılmış. Lütfen giriş yapın.');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch($action) {
        
        case 'getMakinaDurumlari':
            $sql = "SELECT 
                        m.id,
                        m.makina_adi,
                        m.durumu,
                        COUNT(uit.id) as aktif_is_sayisi
                    FROM makinalar m
                    LEFT JOIN uretim_islem_tarihler uit ON m.id = uit.makina_id 
                        AND uit.bitis_tarih IS NULL
                    WHERE m.planlamada_goster = 'evet'
                    GROUP BY m.id
                    ORDER BY m.makina_adi";
            
            $stmt = $db->query($sql);
            $makinalar = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Başarılı', $makinalar);
            break;
            
        case 'getIsDetaylari':
            $id = filter_input(INPUT_GET, 'planlama_id', FILTER_VALIDATE_INT);
            if(!$id) jsonResponse(false, 'Geçersiz ID');
            
            $sql = "SELECT p.*, s.siparis_no
                    FROM planlama p
                    LEFT JOIN siparisler s ON p.siparis_id = s.id
                    WHERE p.id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $is = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$is) jsonResponse(false, 'İş bulunamadı');
            jsonResponse(true, 'Başarılı', $is);
            break;
            
        case 'updateUretimAdet':
            $planlama_id = filter_input(INPUT_POST, 'planlama_id', FILTER_VALIDATE_INT);
            $adet = filter_input(INPUT_POST, 'adet', FILTER_VALIDATE_INT);
            
            if(!$planlama_id || $adet < 0) {
                jsonResponse(false, 'Geçersiz parametreler');
            }
            
            $sql = "UPDATE planlama 
                    SET biten_urun_adedi = biten_urun_adedi + :adet
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['adet' => $adet, 'id' => $planlama_id]);
            
            $sql = "SELECT biten_urun_adedi, uretilecek_adet FROM planlama WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $planlama_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Üretim adedi güncellendi', $result);
            break;
            
        default:
            jsonResponse(false, 'Geçersiz işlem');
    }
    
} catch(PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(false, 'İşlem sırasında hata oluştu');
}
?>
