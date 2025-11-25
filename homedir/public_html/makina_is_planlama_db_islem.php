<?php 
require_once "include/oturum_kontrol.php";

//makina iş sıralama
if(isset($_POST['islem']) && $_POST['islem'] == 'planlama_siralama'){
    $planlama_idler = $_POST['planlama_idler'];
    foreach ($planlama_idler as $index => $planlama_id) {
        $sql = "UPDATE planlama SET sira = :sira  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('sira', intval($index)+1);
        $sth->bindParam('id', $planlama_id);
        $durum = $sth->execute();
    }
    ob_clean();
    echo json_encode(['durum'=>true]); exit;
}

// Anlık veri güncelleme endpoint'i
if(isset($_GET['islem']) && $_GET['islem'] == 'realtime-planlama'){
    try {
        // Session kontrolü
        if(!isset($_SESSION['firma_id'])) {
            throw new Exception('Session bilgileri eksik');
        }

        // Sadece mevcut iş bilgilerini al (performans için)
        $sql = "SELECT planlama.id, planlama.mevcut_asama, planlama.departmanlar, planlama.makinalar,
                planlama.durum, planlama.isim, siparisler.isin_adi
                FROM planlama
                JOIN siparisler ON planlama.siparis_id = siparisler.id
                WHERE planlama.onay_durum = 'evet' 
                AND planlama.firma_id = :firma_id 
                AND planlama.durum = 'basladi'
                AND planlama.aktar_durum = 'orijinal'";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $aktif_isler = $sth->fetchAll(PDO::FETCH_ASSOC);

        // Her makina için mevcut işi map'le
        $makina_mevcut_isler = [];
        foreach ($aktif_isler as $is) {
            $is_asama = $is['mevcut_asama'];
            $is_departmanlar = json_decode($is['departmanlar'], true);
            $is_makinalar = json_decode($is['makinalar'], true);
            
            if(isset($is_departmanlar[$is_asama]) && isset($is_makinalar[$is_asama])) {
                $departman_id = $is_departmanlar[$is_asama];
                $makina_id = $is_makinalar[$is_asama];
                $key = $departman_id . '_' . $makina_id;
                $makina_mevcut_isler[$key] = $is['isin_adi'] . '/' . $is['isim'];
            }
        }

        // JSON Response
        ob_clean();
        echo json_encode([
            'success' => true,
            'makina_mevcut_isler' => $makina_mevcut_isler,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
        
    } catch(PDOException $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database Error: ' . $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);
        exit;
    } catch(Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        exit;
    }
}