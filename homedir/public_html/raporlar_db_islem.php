<?php
require_once "include/oturum_kontrol.php";

header('Content-Type: application/json');

$islem = $_POST['islem'] ?? $_GET['islem'] ?? '';

switch($islem) {
    case 'rapor-kaydet':
        try {
            $rapor_adi = $_POST['rapor_adi'] ?? '';
            $veri_kaynagi = $_POST['veri_kaynagi'] ?? '';
            $sutunlar = $_POST['sutunlar'] ?? '';
            
            if(empty($rapor_adi) || empty($veri_kaynagi) || empty($sutunlar)) {
                throw new Exception('Eksik bilgi');
            }
            
            $sql = "INSERT INTO rapor_sablonlari (firma_id, kullanici_id, rapor_adi, veri_kaynagi, sutunlar) 
                    VALUES (:firma_id, :kullanici_id, :rapor_adi, :veri_kaynagi, :sutunlar)";
            $sth = $conn->prepare($sql);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->bindParam('kullanici_id', $_SESSION['personel_id']);
            $sth->bindParam('rapor_adi', $rapor_adi);
            $sth->bindParam('veri_kaynagi', $veri_kaynagi);
            $sth->bindParam('sutunlar', $sutunlar);
            
            if($sth->execute()) {
                echo json_encode(['success' => true, 'message' => 'Rapor şablonu kaydedildi']);
            } else {
                throw new Exception('Veritabanı hatası: ' . implode(', ', $sth->errorInfo()));
            }
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'rapor-sil':
        try {
            $rapor_id = $_POST['rapor_id'] ?? 0;
            
            if(empty($rapor_id)) {
                throw new Exception('Rapor ID bulunamadı');
            }
            
            $sql = "DELETE FROM rapor_sablonlari WHERE id = :id AND firma_id = :firma_id";
            $sth = $conn->prepare($sql);
            $sth->bindParam('id', $rapor_id);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            
            if($sth->execute()) {
                echo json_encode(['success' => true, 'message' => 'Rapor silindi']);
            } else {
                throw new Exception('Silme hatası: ' . implode(', ', $sth->errorInfo()));
            }
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem: ' . $islem]);
}
?>
