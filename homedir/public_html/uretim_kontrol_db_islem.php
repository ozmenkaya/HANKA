<?php 
require_once "include/oturum_kontrol.php";

//Mesajları Getir
if(isset($_GET['islem']) && $_GET['islem'] == 'mesajlari-cek'){
    //echo 21; exit;
    $planlama_id = isset($_POST['planlama_id']) ? intval($_POST['planlama_id']) : 0;

    //echo $planlama_id; exit;

    $sql = "SELECT uretim_mesaj_log.mesaj, DATE_FORMAT(uretim_mesaj_log.tarih, '%d-%m-%Y %H:%i:%s') AS tarih,
        personeller.ad, personeller.soyad, 
        departmanlar.departman,
        makinalar.makina_adi,makinalar.makina_modeli
        FROM `uretim_mesaj_log` 
        JOIN personeller ON personeller.id = uretim_mesaj_log.personel_id 
        JOIN departmanlar ON departmanlar.id = uretim_mesaj_log.departman_id
        JOIN makinalar ON makinalar.id = uretim_mesaj_log.makina_id
        WHERE uretim_mesaj_log.planlama_id = :planlama_id
        ORDER BY uretim_mesaj_log.id DESC
        ";

    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->execute();
    $mesajlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode(['mesajlar'=>$mesajlar]); exit;
}

//İş Değiştir Logları Getir
if(isset($_GET['islem']) && $_GET['islem'] == 'is-degistir-log-cek'){
    $planlama_id    = intval($_POST['planlama_id']);
    $mevcut_asama   = intval($_POST['mevcut_asama']);

    $sql = "SELECT degistirme_sebebi,sorun_bildirisin_mi,DATE_FORMAT(tarih, '%d-%m-%Y %H:%i:%s') AS tarih
            FROM `uretim_degistir_loglar` 
            WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->execute();
    $is_degistir_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode(['is_degistir_loglar'=>$is_degistir_loglar]); exit;
}

// Mesaj gönder
if(isset($_POST['mesaj-gonder']))
{
    $personel_id    = intval($_SESSION['personel_id']);
    $departman_id   = intval($_POST['departman_id']);
    $planlama_id    = intval($_POST['planlama_id']);
    $makina_id      = intval($_POST['makina_id']);
    $mevcut_asama   = intval($_POST['mevcut_asama']);
    $mesaj          = trim($_POST['mesaj']);
    $grup_kodu      = $_POST['grup_kodu'];

    $sql = "INSERT INTO uretim_mesaj_log(planlama_id, makina_id, personel_id, departman_id, grup_kodu, mevcut_asama, mesaj) 
            VALUES(:planlama_id,:makina_id, :personel_id, :departman_id,:grup_kodu, :mevcut_asama, :mesaj);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("personel_id", $personel_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("grup_kodu", $grup_kodu);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("mesaj", $mesaj);
    $durum = $sth->execute();

    $uretim_mesaj_log_id = $conn->lastInsertId();

    $sql = "INSERT INTO uretim_mesaj_log_gorunum_durumu(uretim_mesaj_log_id, grup_kodu, personel_id) 
            VALUES(:uretim_mesaj_log_id, :grup_kodu, :personel_id)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("uretim_mesaj_log_id", $uretim_mesaj_log_id);
    $sth->bindParam("grup_kodu", $grup_kodu);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->execute();


    if($durum)
    {
        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['üretim', 'planlama', 'mesaj', 'onay'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Mesaj Gönderme Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Mesaj Gönderme Başarısız';
    }

    header("Location: /index.php?url=uretim_kontrol");
    exit;
}

//Eksik Adet Üretime Onay Ver
if(isset($_GET['islem']) && $_GET['islem'] == 'eksik-adet-onay'){
    $planlama_id = intval($_GET['planlama_id']);

    $sql = 'SELECT id FROM uretim_eksik_uretilen_loglar 
            WHERE planlama_id = :planlama_id ORDER BY id DESC LIMIT 1';
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id', $planlama_id);
    $sth->execute();
    $uretim_eksik_uretilen_log = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = "UPDATE uretim_eksik_uretilen_loglar SET onay_veren_personel_id = :onay_veren_personel_id  
            WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('onay_veren_personel_id', $_SESSION['personel_id']);
    $sth->bindParam('id', $uretim_eksik_uretilen_log['id']);
    $durum = $sth->execute();

    $sql = 'SELECT siparis_id,grup_kodu, asama_sayisi, mevcut_asama FROM planlama WHERE id = :id';
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->execute();
    $planlama = $sth->fetch(PDO::FETCH_ASSOC);

    //Mevcut Aşamadaki Tüm Reservasyonları Sil
    $sql = "DELETE FROM uretim_reservasyon WHERE grup_kodu= :grup_kodu AND mevcut_asama = :mevcut_asama";
    $sth = $conn->prepare($sql);
    $sth->bindParam('grup_kodu', $planlama['grup_kodu']);
    $sth->bindParam('mevcut_asama', $planlama['mevcut_asama']);
    $sth->execute();   


    if($planlama['mevcut_asama'] + 1 == $planlama['asama_sayisi']){
        $sql = "UPDATE planlama SET durum = 'bitti'
            WHERE id = :id AND firma_id = :firma_id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $planlama_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $durum = $sth->execute();

        //İş bitmiş ise siparisler iş tamamlandi olarak güncelleme
        $sql = "UPDATE siparisler SET islem = 'tamamlandi' WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $planlama['siparis_id']);
        $durum = $sth->execute();
    }


    //planlama güncelle(MEVCUT PLANLAMA) artık işimize yaramıyor(işi bitirde aktarmıştık)
    $sql = "UPDATE planlama SET asamada_eksik_adet_varmi = 'yok', mevcut_asama = mevcut_asama + 1,
            eksik_adet = 0, aktar_durum = 'eklendi'
            WHERE id = :id AND firma_id = :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();
  
    if($durum)
    {
        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['üretim', 'planlama', 'mesaj', 'onay'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Onay Verilme Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Onay Verilme Başarısız';
    }

    header("Location: /index.php?url=uretim_kontrol");
    exit;


}

//Eksik Adet Üretime Red Ver
if(isset($_GET['islem']) && $_GET['islem'] == 'eksik-adet-iptal'){
    $planlama_id = intval($_GET['planlama_id']);

    //exit;
    $sql = "UPDATE planlama SET durum = 'basladi', asamada_eksik_adet_varmi = 'uret', eksik_adet = 0 
            WHERE id = :id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();

    $sql = 'SELECT departman_id, makina_id,personel_id, mevcut_asama FROM uretim_islem_tarihler 
            WHERE planlama_id = :planlama_id ORDER BY id DESC LIMIT 1';
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id', $planlama_id);
    $sth->execute();
    $uretim_islem_tarih = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = "INSERT INTO uretim_islem_tarihler(planlama_id,departman_id, makina_id,personel_id, mevcut_asama) 
            VALUES(:planlama_id,:departman_id, :makina_id, :personel_id, :mevcut_asama)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("departman_id", $uretim_islem_tarih['departman_id']);
    $sth->bindParam("makina_id", $uretim_islem_tarih['makina_id']);
    $sth->bindParam("personel_id", $uretim_islem_tarih['personel_id']);
    $sth->bindValue("mevcut_asama", $uretim_islem_tarih['mevcut_asama']);
    $durum = $sth->execute();

    if($durum)
    {
        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['üretim', 'planlama', 'mesaj', 'onay'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Onay Verilme Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Onay Verilme Başarısız';
    }

    header("Location: /index.php?url=uretim_kontrol");
    exit;
}

//Sipariş Detay
if(isset($_GET['islem']) && $_GET['islem'] == 'siparis-detay'){
    $planlama_id = intval($_POST['planlama_id']);

    $sql = "SELECT `planlama`.`uretilecek_adet`,`planlama`.`alt_urun_id`,
            `siparisler`.`veriler`,`siparisler`.`tip_id`,siparisler.para_cinsi,
            `siparisler`.`teslimat_adresi`, DATE_FORMAT(`siparisler`.`termin`, '%d-%m-%Y') AS termin,siparisler.fiyat,
            DATE_FORMAT(`siparisler`.`uretim`, '%d-%m-%Y') AS uretim,DATE_FORMAT(`siparisler`.`vade`, '%d-%m-%Y') AS vade,
            `siparisler`.`id` AS siparis_id,
            turler.tur,
            `ulkeler`.`baslik` AS ulke_adi,
            `sehirler`.`baslik` AS sehir_adi,
            `ilceler`.`baslik` AS ilce_adi,
            personeller.ad, personeller.soyad,
            musteri.marka, musteri.firma_unvani,
            odeme_tipleri.odeme_sekli
            FROM `planlama` 
            JOIN siparisler ON siparisler.id = planlama.siparis_id
            JOIN turler ON turler.id = `siparisler`.`tur_id`
            JOIN ulkeler ON ulkeler.id = `siparisler`.`ulke_id`
            JOIN sehirler ON sehirler.id = `siparisler`.`sehir_id`
            JOIN ilceler ON ilceler.id = `siparisler`.`ilce_id`
            JOIN personeller ON personeller.id = `siparisler`.`musteri_temsilcisi_id`
            JOIN musteri ON musteri.id = `siparisler`.`musteri_id`
            JOIN odeme_tipleri ON odeme_tipleri.id = `siparisler`.`odeme_sekli_id`
            WHERE planlama.id = :planlama_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id', $planlama_id);
    $sth->execute();
    $planlama = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT ad FROM `siparis_dosyalar` 
    WHERE `siparis_id` = :siparis_id AND `alt_urun_index` = :alt_urun_index";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $planlama['siparis_id']);
    $sth->bindValue('alt_urun_index', $planlama['alt_urun_id']-1);
    $sth->execute();
    $siparis_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);
     
    ob_clean();
    echo json_encode(['planlama'=>$planlama, 'siparis_dosyalar'=>$siparis_dosyalar]); exit;
}

// Anlık veri güncelleme endpoint'i
if(isset($_GET['islem']) && $_GET['islem'] == 'realtime-uretim-kontrol'){
    try {
        // Session kontrolü
        if(!isset($_SESSION['firma_id'])) {
            throw new Exception('Session bilgileri eksik');
        }

        // Üretimdeki işler sayısı
        $sql = "SELECT COUNT(*) as sayi FROM planlama 
                WHERE firma_id = :firma_id 
                AND durum IN('basladi', 'beklemede','fasonda') 
                AND aktar_durum = 'orijinal'";
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->execute();
        $uretimdeki_isler_sayi = $sth->fetch(PDO::FETCH_ASSOC)['sayi'];

        // Üretime henüz girmeyen işler sayısı
        $sql = "SELECT COUNT(*) as sayi FROM planlama 
                WHERE firma_id = :firma_id 
                AND durum = 'baslamadi'";
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->execute();
        $henuz_baslamis_isler_sayi = $sth->fetch(PDO::FETCH_ASSOC)['sayi'];

        // Eksik üretimde onay bekleyenler sayısı
        $sql = "SELECT COUNT(*) as sayi FROM planlama 
                WHERE firma_id = :firma_id 
                AND asamada_eksik_adet_varmi = 'var'";
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->execute();
        $eksik_onay_bekleyen_sayi = $sth->fetch(PDO::FETCH_ASSOC)['sayi'];

        // JSON Response
        ob_clean();
        echo json_encode([
            'success' => true,
            'uretimdeki_isler_sayi' => intval($uretimdeki_isler_sayi),
            'henuz_baslamis_isler_sayi' => intval($henuz_baslamis_isler_sayi),
            'eksik_onay_bekleyen_sayi' => intval($eksik_onay_bekleyen_sayi),
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