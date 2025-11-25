<?php
require_once "include/db.php";
require_once "include/oturum_kontrol_ajax.php";

header('Content-Type: application/json; charset=utf-8');

$firma_id = $_SESSION['firma_id'];
$islem = $_POST['islem'] ?? '';

try {
    switch($islem) {
        case 'ekle':
            $tablo_adi = $_POST['tablo_adi'] ?? '';
            $tablo_label = $_POST['tablo_label'] ?? '';
            
            if(empty($tablo_adi) || empty($tablo_label)) {
                echo json_encode(['durum' => 'hata', 'mesaj' => 'Tablo adı ve label boş olamaz!']);
                exit;
            }
            
            // Sıra numarasını belirle
            $sira_sql = "SELECT COALESCE(MAX(sira), 0) + 1 as yeni_sira FROM rapor_ayarlari WHERE firma_id = ?";
            $sira_stmt = $conn->prepare($sira_sql);
            $sira_stmt->execute([$firma_id]);
            $yeni_sira = $sira_stmt->fetch()['yeni_sira'];
            
            $sql = "INSERT INTO rapor_ayarlari (firma_id, tablo_adi, tablo_label, aktif, sira) 
                    VALUES (?, ?, ?, 1, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$firma_id, $tablo_adi, $tablo_label, $yeni_sira]);
            
            echo json_encode(['durum' => 'basarili', 'mesaj' => 'Tablo başarıyla eklendi!']);
            break;
            
        case 'durum_degistir':
            $id = $_POST['id'] ?? 0;
            $aktif = $_POST['aktif'] ?? 0;
            
            $sql = "UPDATE rapor_ayarlari SET aktif = ? WHERE id = ? AND firma_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$aktif, $id, $firma_id]);
            
            $mesaj = $aktif ? 'Tablo aktif edildi!' : 'Tablo pasif edildi!';
            echo json_encode(['durum' => 'basarili', 'mesaj' => $mesaj]);
            break;
            
        case 'label_guncelle':
            $id = $_POST['id'] ?? 0;
            $label = $_POST['label'] ?? '';
            
            if(empty($label)) {
                echo json_encode(['durum' => 'hata', 'mesaj' => 'Label boş olamaz!']);
                exit;
            }
            
            $sql = "UPDATE rapor_ayarlari SET tablo_label = ? WHERE id = ? AND firma_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$label, $id, $firma_id]);
            
            echo json_encode(['durum' => 'basarili', 'mesaj' => 'Label güncellendi!']);
            break;
            
        case 'sil':
            $id = $_POST['id'] ?? 0;
            
            $sql = "DELETE FROM rapor_ayarlari WHERE id = ? AND firma_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id, $firma_id]);
            
            echo json_encode(['durum' => 'basarili', 'mesaj' => 'Tablo kaldırıldı!']);
            break;
            
        default:
            echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçersiz işlem!']);
    }
} catch(Exception $e) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Hata: ' . $e->getMessage()]);
}
exit;
