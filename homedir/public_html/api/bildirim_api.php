<?php
// CLI için REQUEST_URI kontrolü
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/api/bildirim_api.php';
}

require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'liste';
$firma_id = $_SESSION['firma_id'] ?? null;
$kullanici_id = $_SESSION['kullanici_id'] ?? null;

if(!$firma_id) {
    jsonResponse(false, 'Oturum bulunamadı');
    exit;
}

try {
    switch($action) {
        case 'liste':
            // Son 50 bildirim
            $stmt = $conn->prepare("
                SELECT * FROM bildirimler 
                WHERE firma_id = :firma_id 
                AND (kullanici_id IS NULL OR kullanici_id = :kullanici_id)
                ORDER BY olusturma_tarihi DESC 
                LIMIT 50
            ");
            $stmt->execute([
                'firma_id' => $firma_id,
                'kullanici_id' => $kullanici_id
            ]);
            $bildirimler = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Bildirimler', $bildirimler);
            break;
            
        case 'okunmamis':
            // Okunmamış bildirim sayısı ve son 10 bildirim
            $stmt = $conn->prepare("
                SELECT COUNT(*) as sayi FROM bildirimler 
                WHERE firma_id = :firma_id 
                AND (kullanici_id IS NULL OR kullanici_id = :kullanici_id)
                AND okundu = 'hayir'
            ");
            $stmt->execute([
                'firma_id' => $firma_id,
                'kullanici_id' => $kullanici_id
            ]);
            $sayi = $stmt->fetch(PDO::FETCH_ASSOC)['sayi'];
            
            $stmt = $conn->prepare("
                SELECT * FROM bildirimler 
                WHERE firma_id = :firma_id 
                AND (kullanici_id IS NULL OR kullanici_id = :kullanici_id)
                AND okundu = 'hayir'
                ORDER BY olusturma_tarihi DESC 
                LIMIT 10
            ");
            $stmt->execute([
                'firma_id' => $firma_id,
                'kullanici_id' => $kullanici_id
            ]);
            $bildirimler = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Okunmamış bildirimler', [
                'sayi' => (int)$sayi,
                'bildirimler' => $bildirimler
            ]);
            break;
            
        case 'okundu_isaretle':
            $id = $_POST['id'] ?? null;
            if(!$id) {
                jsonResponse(false, 'Bildirim ID gerekli');
                exit;
            }
            
            $stmt = $conn->prepare("
                UPDATE bildirimler 
                SET okundu = 'evet', okunma_tarihi = NOW() 
                WHERE id = :id 
                AND firma_id = :firma_id
            ");
            $stmt->execute([
                'id' => $id,
                'firma_id' => $firma_id
            ]);
            
            jsonResponse(true, 'Bildirim okundu olarak işaretlendi');
            break;
            
        case 'tumunu_okundu_isaretle':
            $stmt = $conn->prepare("
                UPDATE bildirimler 
                SET okundu = 'evet', okunma_tarihi = NOW() 
                WHERE firma_id = :firma_id 
                AND (kullanici_id IS NULL OR kullanici_id = :kullanici_id)
                AND okundu = 'hayir'
            ");
            $stmt->execute([
                'firma_id' => $firma_id,
                'kullanici_id' => $kullanici_id
            ]);
            
            jsonResponse(true, 'Tüm bildirimler okundu');
            break;
            
        case 'ekle':
            // Yeni bildirim ekleme (yetkili kullanıcılar için)
            $baslik = $_POST['baslik'] ?? '';
            $mesaj = $_POST['mesaj'] ?? '';
            $tur = $_POST['tur'] ?? 'bilgi';
            $link = $_POST['link'] ?? null;
            $hedef_kullanici = $_POST['kullanici_id'] ?? null;
            
            if(empty($baslik) || empty($mesaj)) {
                jsonResponse(false, 'Başlık ve mesaj gerekli');
                exit;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO bildirimler 
                (firma_id, kullanici_id, baslik, mesaj, tur, link) 
                VALUES 
                (:firma_id, :kullanici_id, :baslik, :mesaj, :tur, :link)
            ");
            $stmt->execute([
                'firma_id' => $firma_id,
                'kullanici_id' => $hedef_kullanici,
                'baslik' => $baslik,
                'mesaj' => $mesaj,
                'tur' => $tur,
                'link' => $link
            ]);
            
            jsonResponse(true, 'Bildirim oluşturuldu', ['id' => $conn->lastInsertId()]);
            break;
            
        default:
            jsonResponse(false, 'Geçersiz işlem');
    }
    
} catch (PDOException $e) {
    error_log('Bildirim API Error: ' . $e->getMessage());
    jsonResponse(false, 'Bir hata oluştu');
}
