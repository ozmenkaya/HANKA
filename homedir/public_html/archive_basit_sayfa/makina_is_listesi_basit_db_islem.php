<?php
require_once "include/oturum_kontrol.php";

// İşi Başlat
if(isset($_POST['islem']) && $_POST['islem'] == 'is_baslat') {
    $planlama_id = intval($_POST['planlama_id']);
    $makina_id = intval($_POST['makina_id']);
    $durum = $_POST['durum'];
    
    try {
        // Planlama bilgilerini çek
        $sql = "SELECT id, siparis_id, durum, mevcut_asama, departmanlar, tekil_kod, grup_kodu 
                FROM planlama 
                WHERE id = :id AND firma_id = :firma_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $planlama_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $planlama = $sth->fetch(PDO::FETCH_ASSOC);
        
        if(empty($planlama)) {
            ob_clean();
        echo json_encode(['success' => false, 'message' => 'Planlama bulunamadı!']);
            exit;
        }
        
        $departmanlar = json_decode($planlama['departmanlar'], true);
        $departman_id = $departmanlar[$planlama['mevcut_asama']];
        
        // Bu makinada başka bir iş çalışıyor mu kontrol et
        $sql = "SELECT planlama.id, planlama.makinalar, planlama.mevcut_asama 
                FROM planlama 
                WHERE planlama.firma_id = :firma_id AND planlama.durum = 'basladi' AND planlama.id != :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('id', $planlama_id);
        $sth->execute();
        $calisan_isler = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        // Bu makinada çalışan iş var mı kontrol et
        foreach ($calisan_isler as $calisan_is) {
            $calisan_makinalar = json_decode($calisan_is['makinalar'], true);
            if(isset($calisan_makinalar[$calisan_is['mevcut_asama']]) && 
               $calisan_makinalar[$calisan_is['mevcut_asama']] == $makina_id) {
                ob_clean();
        echo json_encode(['success' => false, 'message' => 'Bu makinada başka bir iş çalışıyor! Önce onu durdurun.']);
                exit;
            }
        }
        
        // İlk aşamada ise siparişi "işlemde" yap
        if($planlama['mevcut_asama'] == 0 && $planlama['durum'] == 'baslamadi') {
            $sql = "UPDATE siparisler SET islem = 'islemde' WHERE id = :id";
            $sth = $conn->prepare($sql);
            $sth->bindParam('id', $planlama['siparis_id']);
            $sth->execute();
        }
        
        // Planlamayı başlat (veya devam ettir)
        $sql = "UPDATE planlama SET durum = 'basladi' WHERE id = :id AND firma_id = :firma_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $planlama_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        
        // Açık kayıt var mı kontrol et (bitmemiş işler)
        $sql = "SELECT id FROM uretim_islem_tarihler 
                WHERE planlama_id = :planlama_id 
                AND makina_id = :makina_id 
                AND bitirme_tarihi IS NULL
                ORDER BY baslatma_tarih DESC LIMIT 1";
        $sth = $conn->prepare($sql);
        $sth->bindParam('planlama_id', $planlama_id);
        $sth->bindParam('makina_id', $makina_id);
        $sth->execute();
        $acik_kayit = $sth->fetch(PDO::FETCH_ASSOC);
        
        // Eğer zaten açık bir kayıt varsa hata ver
        if($acik_kayit) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Bu iş için zaten açık bir kayıt var!']);
            exit;
        }
        
        // Yeni üretim kaydı oluştur (ilk başlatma veya devam)
        $baslatma_tarih = date('Y-m-d H:i:s');
        $sql = "INSERT INTO uretim_islem_tarihler(planlama_id, makina_id, departman_id, personel_id, mevcut_asama, baslatma_tarih)
                VALUES(:planlama_id, :makina_id, :departman_id, :personel_id, :mevcut_asama, :baslatma_tarih)";
        $sth = $conn->prepare($sql);
        $sth->bindParam('planlama_id', $planlama_id);
        $sth->bindParam('makina_id', $makina_id);
        $sth->bindParam('departman_id', $departman_id);
        $sth->bindParam('personel_id', $_SESSION['personel_id']);
        $sth->bindParam('mevcut_asama', $planlama['mevcut_asama']);
        $sth->bindParam('baslatma_tarih', $baslatma_tarih);
        $sth->execute();
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'İş başlatıldı!']);
        
    } catch(Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}

// İşi Duraklat
if(isset($_POST["islem"]) && $_POST["islem"] == "is_duraklat") {
    $planlama_id = intval($_POST["planlama_id"]);
    $makina_id = intval($_POST["makina_id"]);
    
    try {
        $sql = "UPDATE planlama SET durum = 'beklemede' WHERE id = :id AND firma_id = :firma_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("id", $planlama_id);
        $sth->bindParam("firma_id", $_SESSION["firma_id"]);
        $sth->execute();
        
        $bitirme_tarihi = date("Y-m-d H:i:s");
        $sql = "UPDATE uretim_islem_tarihler 
                SET bitirme_tarihi = :bitirme_tarihi 
                WHERE planlama_id = :planlama_id AND bitirme_tarihi IS NULL
                ORDER BY id DESC LIMIT 1";
        $sth = $conn->prepare($sql);
        $sth->bindParam("bitirme_tarihi", $bitirme_tarihi);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->execute();
        
        ob_clean();
        echo json_encode(["success" => true, "message" => "İş duraklatıldı!"]);
        
    } catch(Exception $e) {
        ob_clean();
        echo json_encode(["success" => false, "message" => "Hata: " . $e->getMessage()]);
    }
    exit;
}

// İşi Bitir
if(isset($_POST["islem"]) && $_POST["islem"] == "is_bitir") {
    $planlama_id = intval($_POST["planlama_id"]);
    $makina_id = intval($_POST["makina_id"]);
    $uretilen_adet = intval($_POST["uretilen_adet"]);
    $fire_adet = isset($_POST["fire_adet"]) ? intval($_POST["fire_adet"]) : 0;
    $aciklama = isset($_POST["aciklama"]) ? $_POST["aciklama"] : "";
    
    try {
        $conn->beginTransaction();
        
        // Planlama durumunu güncelle
        $sql = "UPDATE planlama SET durum = 'baslamadi' WHERE id = :id AND firma_id = :firma_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("id", $planlama_id);
        $sth->bindParam("firma_id", $_SESSION["firma_id"]);
        $sth->execute();
        
        // Açık üretim kaydını kapat
        $bitirme_tarihi = date("Y-m-d H:i:s");
        $sql = "UPDATE uretim_islem_tarihler 
                SET bitirme_tarihi = :bitirme_tarihi
                WHERE planlama_id = :planlama_id 
                AND makina_id = :makina_id 
                AND bitirme_tarihi IS NULL
                ORDER BY baslatma_tarih DESC
                LIMIT 1";
        $sth = $conn->prepare($sql);
        $sth->bindParam("bitirme_tarihi", $bitirme_tarihi);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("makina_id", $makina_id);
        $sth->execute();
        
        $conn->commit();
        ob_clean();
        echo json_encode(["success" => true, "message" => "İş başarıyla tamamlandı"]);
        
    } catch(Exception $e) {
        $conn->rollBack();
        ob_clean();
        echo json_encode(["success" => false, "message" => "Hata: " . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz işlem!']);
?>
