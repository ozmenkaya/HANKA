<?php
require_once "include/oturum_kontrol.php";

if (isset($_GET['islem']) && $_GET['islem'] == 'realtime-makina-sayilari') {
    try {
            
            // Kullanıcının makinalarını al
            $sql = "SELECT makinalar.id, makinalar.makina_adi
                    FROM `makinalar` 
                    JOIN makina_personeller ON makina_personeller.makina_id = makinalar.id 
                    WHERE makinalar.firma_id = :firma_id 
                    AND makina_personeller.personel_id = :personel_id 
                    AND makinalar.durumu = 'aktif'";
            
            $sth = $conn->prepare($sql);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->bindParam('personel_id', $_SESSION['personel_id']);
            $sth->execute();
            $makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            // Tüm planlamaları al
            $sql = "SELECT id, mevcut_asama, makinalar, departmanlar, onay_durum, durum 
                    FROM `planlama` 
                    WHERE firma_id = :firma_id 
                    AND aktar_durum = 'orijinal'
                    AND onay_durum = 'evet'";
            
            $sth = $conn->prepare($sql);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            // Her makina için iş sayısını hesapla
            $makina_is_sayilari = [];
            
            foreach ($makinalar as $makina) {
                $is_sayisi = 0;
                $aktif_is_varmi = false;
                
                // Bu makina için kaç iş var?
                foreach ($planlamalar as $planlama) {
                    $planla_makinalar = json_decode($planlama['makinalar'], true);
                    $departmanlar = json_decode($planlama['departmanlar'], true);
                    
                    // Makina departman bilgisini al
                    $sql_dept = "SELECT departman_id FROM makinalar WHERE id = :makina_id";
                    $sth_dept = $conn->prepare($sql_dept);
                    $sth_dept->bindParam('makina_id', $makina['id']);
                    $sth_dept->execute();
                    $makina_dept = $sth_dept->fetch(PDO::FETCH_ASSOC);
                    
                    if (isset($planla_makinalar[$planlama['mevcut_asama']]) && 
                        isset($departmanlar[$planlama['mevcut_asama']]) && 
                        $planla_makinalar[$planlama['mevcut_asama']] == $makina['id'] && 
                        $departmanlar[$planlama['mevcut_asama']] == $makina_dept['departman_id']) {
                        
                        $is_sayisi++;
                        
                        if ($planlama['durum'] == 'basladi') {
                            $aktif_is_varmi = true;
                        }
                    }
                }
                
                $makina_is_sayilari[] = [
                    'makina_id' => $makina['id'],
                    'is_sayisi' => $is_sayisi,
                    'aktif_is_varmi' => $aktif_is_varmi
                ];
            }
            
        // JSON Response
        ob_clean();
        echo json_encode([
            'success' => true,
            'makinalar' => $makina_is_sayilari,
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
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        exit;
    }
}
