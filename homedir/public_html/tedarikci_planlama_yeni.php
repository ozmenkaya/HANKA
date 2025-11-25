<?php
require_once "include/oturum_kontrol.php";

$tedarikci_id = isset($_GET['tedarikci_id']) ? intval($_GET['tedarikci_id']) : 0;

if ($tedarikci_id == 0) {
    header("Location: /index.php?url=tedarikci");
    exit;
}

// Tedarikçi bilgilerini al
$sth = $conn->prepare('SELECT * FROM tedarikciler WHERE id = :id AND firma_id = :firma_id');
$sth->bindParam('id', $tedarikci_id);
$sth->bindParam('firma_id', $_SESSION['firma_id']);
$sth->execute();
$tedarikci = $sth->fetch(PDO::FETCH_ASSOC);

if (!$tedarikci) {
    header("Location: /index.php?url=tedarikci");
    exit;
}

// FASON durumunda olan TÜM planlama kayıtlarını getir
$sth = $conn->prepare('SELECT 
    planlama.id, 
    planlama.isim,
    planlama.mevcut_asama, 
    planlama.departmanlar, 
    planlama.arsiv_altlar,
    planlama.adetler,
    planlama.fason_durumlar, 
    planlama.fason_tedarikciler, 
    siparisler.isin_adi, 
    siparisler.siparis_no,
    musteri.marka,
    birimler.ad AS birim_ad
    FROM planlama 
    JOIN siparisler ON siparisler.id = planlama.siparis_id
    JOIN musteri ON musteri.id = siparisler.musteri_id 
    JOIN birimler ON birimler.id = siparisler.birim_id 
    WHERE planlama.firma_id = :firma_id 
    AND planlama.durum IN("baslamadi","basladi","beklemede","fasonda")');
$sth->bindParam('firma_id', $_SESSION['firma_id']);
$sth->execute();
$planlanmalar = $sth->fetchAll(PDO::FETCH_ASSOC);

// Bu tedarikçiye ait fason işleri filtrele
$fason_isler = [];
foreach ($planlanmalar as $planlama) {
    // Fason durumunu kontrol et
    $fasonlar = json_decode($planlama['fason_durumlar'], true);
    if (!is_array($fasonlar)) continue;
    
    $fason = isset($fasonlar[$planlama['mevcut_asama']]) ? $fasonlar[$planlama['mevcut_asama']] : 0;
    
    // Eğer bu aşama fasonda ise
    if ($fason == 1) {
        // Bu aşamadaki tedarikçi ID'sini kontrol et
        $tedarikciler = json_decode($planlama['fason_tedarikciler'], true);
        if (!is_array($tedarikciler)) continue;
        
        $planlama_tedarikci_id = isset($tedarikciler[$planlama['mevcut_asama']]) ? 
                                 intval($tedarikciler[$planlama['mevcut_asama']]) : 0;
        
        // Bu tedarikçi ile eşleşiyorsa listeye ekle
        if ($planlama_tedarikci_id == $tedarikci_id) {
            // Departman bilgisini ekle
            $departmanlar = json_decode($planlama['departmanlar'], true);
            $departman_id = is_array($departmanlar) && isset($departmanlar[$planlama['mevcut_asama']]) ? 
                           $departmanlar[$planlama['mevcut_asama']] : 0;
            
            if ($departman_id > 0) {
                $sth_dept = $conn->prepare('SELECT id, departman FROM departmanlar WHERE id = :id');
                $sth_dept->bindParam('id', $departman_id);
                $sth_dept->execute();
                $planlama['departman'] = $sth_dept->fetch(PDO::FETCH_ASSOC);
            } else {
                $planlama['departman'] = ['id' => 0, 'departman' => 'Tanımsız'];
            }
            
            // Adet bilgisini ekle
            $adetler = json_decode($planlama['adetler'], true);
            $planlama['adet'] = is_array($adetler) && isset($adetler[$planlama['mevcut_asama']]) ? 
                               $adetler[$planlama['mevcut_asama']] : 0;
            
            // Fason durumunu ekle
            $sth_fason = $conn->prepare('SELECT id, durum FROM uretim_fason_durum_loglar 
                WHERE planlama_id = :planlama_id 
                AND departman_id = :departman_id 
                AND mevcut_asama = :mevcut_asama 
                ORDER BY id DESC LIMIT 1');
            $sth_fason->execute([
                'planlama_id' => $planlama['id'],
                'departman_id' => $departman_id,
                'mevcut_asama' => $planlama['mevcut_asama']
            ]);
            $planlama['fason_log'] = $sth_fason->fetch(PDO::FETCH_ASSOC);
            
            $fason_isler[] = $planlama;
        }
    }
}
?>
