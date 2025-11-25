<?php 
require_once "include/oturum_kontrol.php";

//Real-Time Liste Güncelleme (Her 10 saniyede bir çağrılır)
if(isset($_GET['islem']) && $_GET['islem'] == 'realtime-liste'){
    try {
        $makina_id = intval($_GET['makina_id']);
        
        // Session kontrolü
        if(!isset($_SESSION['firma_id']) || !isset($_SESSION['personel_id'])) {
            throw new Exception('Session bilgileri eksik');
        }

        // Makinadaki iş sayısını hesapla
        $sql = "SELECT planlama.id, planlama.makinalar, planlama.mevcut_asama, planlama.durum
                FROM planlama 
                WHERE planlama.firma_id = :firma_id 
                AND planlama.durum IN('baslamadi','basladi','beklemede') 
                AND onay_durum = 'evet' 
                AND aktar_durum = 'orijinal'
                ORDER BY planlama.sira";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $isler = $sth->fetchAll(PDO::FETCH_ASSOC);

        $is_sayisi = 0;
        $is_ids = [];
        foreach ($isler as $is) {
            $makinalar = json_decode($is['makinalar'], true);
            if(isset($makinalar[$is['mevcut_asama']]) && $makinalar[$is['mevcut_asama']] == $makina_id) {
                $is_sayisi++;
                $is_ids[] = $is['id'];
            }
        }

        // Liste değişikliği kontrolü (iş eklenmiş/çıkarılmış mı?)
        $mevcut_liste_hash = md5(json_encode($is_ids));
        $onceki_liste_hash = isset($_SESSION['liste_hash_' . $makina_id]) ? $_SESSION['liste_hash_' . $makina_id] : '';
        
        $liste_degisti = ($mevcut_liste_hash !== $onceki_liste_hash);
        $_SESSION['liste_hash_' . $makina_id] = $mevcut_liste_hash;

        // JSON Response
        ob_clean();
        echo json_encode([
            'success' => true,
            'is_sayisi' => $is_sayisi,
            'liste_degisti' => $liste_degisti,
            'timestamp' => time()
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
