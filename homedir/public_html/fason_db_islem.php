<?php 
// Hata ayıklama için
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/php_errors.log');

include "include/oturum_kontrol.php";

//planlama detay
if(isset($_POST['islem']) && $_POST['islem'] == 'planlama-detay')
{
    $planlama_id    = $_POST['planlama_id'];

    $sth = $conn->prepare('SELECT planlama.id, planlama.isim,planlama.mevcut_asama, planlama.departmanlar, `planlama`.`arsiv_altlar`,
    `planlama`.`adetler`,`planlama`.`fason_durumlar`, `planlama`.`fason_tedarikciler`, `planlama`.`stok_alt_kalemler`, `planlama`.`stok_alt_depo_adetler`,
    siparisler.isin_adi, siparisler.siparis_no,
    musteri.marka,
    birimler.ad  AS birim_ad
    FROM planlama 
    JOIN siparisler ON siparisler.id = planlama.siparis_id
    JOIN musteri ON musteri.id = `siparisler`.`musteri_id` 
    JOIN birimler ON birimler.id = `siparisler`.`birim_id` 
    WHERE planlama.id = :id AND planlama.firma_id = :firma_id AND  planlama.durum != "bitti" ');
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlama = $sth->fetch(PDO::FETCH_ASSOC);

    $departmanlar = json_decode($planlama['departmanlar'], true);
    $departman_id = $departmanlar[$planlama['mevcut_asama']];

    $sth = $conn->prepare('SELECT departman FROM `departmanlar` WHERE `departmanlar`.`id` = :id');
    $sth->bindParam('id', $departman_id );
    $sth->execute();
    $departman = $sth->fetch(PDO::FETCH_ASSOC);   

    $stok_kalem_adetler = json_decode($planlama['stok_alt_depo_adetler'], true);
    $stok_kalem_adetler = $stok_kalem_adetler[$planlama['mevcut_asama']];    
    $stok_kalemler      = json_decode($planlama['stok_alt_kalemler'], true);
    $stok_kalemler      = !empty($stok_kalemler[$planlama['mevcut_asama']]) ? $stok_kalemler[$planlama['mevcut_asama']] : [];
    //echo json_encode($stok_kalemler); exit;

    $stok_kalem_datalar    = [];
    foreach ($stok_kalemler as $index => $stok_kalem_id) {
        if($stok_kalem_id != 0){
            $sql = "SELECT stok_alt_kalemler.veri, `stok_kalemleri`.`stok_kalem` 
            FROM `stok_alt_kalemler` 
            JOIN stok_kalemleri ON stok_kalemleri.id = stok_alt_kalemler.stok_id 
            WHERE stok_alt_kalemler.id = :id AND stok_kalemleri.firma_id = :firma_id";

            $sth = $conn->prepare($sql);
            $sth->bindParam('id',$stok_kalem_id);
            $sth->bindParam('firma_id',$_SESSION['firma_id']);
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_ASSOC);
            $data['adet'] = $stok_kalem_adetler[$index];

            $stok_kalem_datalar[] = $data;
        }
    }

    $tedarikciler = json_decode($planlama['fason_tedarikciler'], true);
    $tedarikci_id = $tedarikciler[$planlama['mevcut_asama']];
    if($tedarikci_id != 0){
        $sth = $conn->prepare('SELECT firma_adi FROM `tedarikciler` WHERE id = :id');
        $sth->bindParam('id', $tedarikci_id);
        $sth->execute();
        $tedarikci = $sth->fetch(PDO::FETCH_ASSOC); 
    }
    else{
        $tedarikci['firma_adi'] = '-';
    }

    $adetler = json_decode($planlama['adetler']);
    $adet = $adetler[$planlama['mevcut_asama']];
 
    ob_clean();
    echo json_encode([
        'siparis_no'    => $planlama['siparis_no'],
        'marka'         => $planlama['marka'],
        'isin_adi'      => $planlama['isin_adi'],
        'isim'          => $planlama['isim'],
        'departman'     => $departman['departman'],
        'tedarikci'     => $tedarikci['firma_adi'],
        'adet'          => $adet,
        'birim_ad'      => $planlama['birim_ad'],
        'stok_kalem_datalar'   => $stok_kalem_datalar
    ]); exit;
}

//fason İptal
if(isset($_POST['fason-iptal']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $planlama_id                = $_POST['planlama_id'];
    $departman_id               = $_POST['departman_id'];
    $mevcut_asama               = $_POST['mevcut_asama'];
    $fason_id                   = $_POST['fason_id'];
    $stok_idler                 = isset($_POST['stok_id'])              ? $_POST['stok_id']             : [];
    $stok_alt_kalem_idler       = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id']   : [];
    $stok_alt_depo_idler        = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id']    : [];
    $gelen_adetler              = isset($_POST['gelen_adet'])           ? $_POST['gelen_adet']          : [];
    $birim_idler                = isset($_POST['birim_id'])             ? $_POST['birim_id']            : [];
    $arsiv_alt_idler            = isset($_POST['arsiv_alt_id'])         ?  $_POST['arsiv_alt_id']       : [];
    $arsivden_gelme_durumlari   = isset($_POST['arsivden_gelme_durumu']) ? $_POST['arsivden_gelme_durumu'] : [];
    $iptal_sebebi               = $_POST['iptal_sebebi'];

    //Arşiv Durumlarını Güncelle
    foreach ($arsiv_alt_idler as $index => $arsiv_alt_id) {
        if(isset($arsivden_gelme_durumlari[$index]) && $arsivden_gelme_durumlari[$index] == 1){
            $sql = "UPDATE arsiv_altlar SET durum = 'arsivde'  WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam("id", $arsiv_alt_id);
            $durum = $sth->execute();
        }
        
    }

    //Üretim Fason Log Güncelle
    $sql = "UPDATE uretim_fason_durum_loglar SET durum = 'iptal', iptal_sebebi = :iptal_sebebi, gelis_tarihi = :gelis_tarihi
            WHERE planlama_id = :planlama_id AND (gelis_tarihi IS NULL OR YEAR(gelis_tarihi) = 0)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("iptal_sebebi", $iptal_sebebi);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindValue("gelis_tarihi", date('Y-m-d H:i:s'));
    $durum = $sth->execute();
    
    //Gelen Stok Kullanımda Çıkar
    for($i = 0 ; $i < count($stok_idler); $i++){
        $sql = "UPDATE stok_alt_depolar_kullanilanlar 
                SET tuketim_miktari = tuketim_miktari - :tuketim_miktari, tarih = :tarih
                WHERE planlama_id = :planlama_id AND makina_id = 0 AND mevcut_asama = :mevcut_asama AND departman_id = :departman_id 
                AND stok_id = :stok_id AND stok_alt_kalem_id = :stok_alt_kalem_id AND stok_alt_depo_id = :stok_alt_depo_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("tuketim_miktari", $gelen_adetler[$i]);
        $sth->bindValue("tarih", date('Y-m-d H:i:s'));
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindValue("departman_id", $departman_id);
        $sth->bindValue("stok_id", $stok_idler[$i]);
        $sth->bindValue("stok_alt_kalem_id", $stok_alt_kalem_idler[$i]);
        $sth->bindValue("stok_alt_depo_id", $stok_alt_depo_idler[$i]);
        $durum = $sth->execute();
    }
    //Fasoncuyu Değişmek İçin Planı Çek
    $sql = "SELECT fason_tedarikciler FROM planlama WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->execute();
    $planlama = $sth->fetch(PDO::FETCH_ASSOC);

    $fason_tedarikciler = json_decode($planlama['fason_tedarikciler'], true);
    $fason_tedarikciler[$mevcut_asama] = $fason_id;


    //Planlama Güncelleme
    $sql = "UPDATE planlama SET  durum = 'beklemede', fason_tedarikciler = :fason_tedarikciler  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindValue('fason_tedarikciler', json_encode($fason_tedarikciler));
    $durum = $sth->execute();

    $_SESSION['durum'] = 'success';
    $_SESSION['mesaj'] = 'Fason İptal Edildi';
    header('Location: /index.php?url=fason'); exit;
}


//fasondan geldi
if(isset($_POST['fasondan-geldi']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $siparis_id                 = intval($_POST['siparis_id']);
    $planlama_id                = intval($_POST['planlama_id']);
    $departman_id               = intval($_POST['departman_id']);
    $mevcut_asama               = intval($_POST['mevcut_asama']);
    $asama_sayisi               = intval($_POST['asama_sayisi']);
    $grup_kodu                  = $_POST['grup_kodu'];
    $stok_idler                 = isset($_POST['stok_id'])              ? $_POST['stok_id'] : [];
    $stok_alt_kalem_idler       = isset($_POST['stok_alt_kalem_id'])    ?  $_POST['stok_alt_kalem_id'] : [];
    $stok_alt_depo_idler        = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id'] : [];
    $gelen_adetler              = isset($_POST['gelen_adet'])           ?  $_POST['gelen_adet'] : [];
    $birim_idler                = isset($_POST['birim_id'])             ? $_POST['birim_id'] : [];
    $arsiv_alt_idler            = isset($_POST['arsiv_alt_id'])         ? $_POST['arsiv_alt_id'] : [];
    $arsivden_gelme_durumlari   = isset($_POST['arsivden_gelme_durumu'])? $_POST['arsivden_gelme_durumu'] : [];
    $uretilen_adet              = $_POST['uretilen_adet'];
    $uretirken_verilen_fire_adet= $_POST['uretirken_verilen_fire_adet'];

    //Arşiv Durumlarını Güncelle
    foreach ($arsiv_alt_idler as $index => $arsiv_alt_id) {
        if(isset($arsivden_gelme_durumlari[$index]) && $arsivden_gelme_durumlari[$index] == 1){
            $sql = "UPDATE arsiv_altlar SET durum = 'arsivde'  WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam("id", $arsiv_alt_id);
            $durum = $sth->execute();
        }
        
    }

    //Üretim Fason Log Güncelle
    $sql = "UPDATE uretim_fason_durum_loglar SET durum = 'geldi', gelis_tarihi = :gelis_tarihi
            WHERE planlama_id = :planlama_id AND (gelis_tarihi IS NULL OR YEAR(gelis_tarihi) = 0)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindValue("gelis_tarihi", date('Y-m-d H:i:s'));
    $durum = $sth->execute();

    //Gelen Stok Kullanımda Çıkar
    for($i = 0 ; $i < count($stok_idler); $i++){
        $sql = "UPDATE stok_alt_depolar_kullanilanlar 
                SET tuketim_miktari = tuketim_miktari - :tuketim_miktari, tarih = :tarih
                WHERE planlama_id = :planlama_id AND makina_id = 0 AND mevcut_asama = :mevcut_asama AND departman_id = :departman_id 
                AND stok_id = :stok_id AND stok_alt_kalem_id = :stok_alt_kalem_id AND stok_alt_depo_id = :stok_alt_depo_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("tuketim_miktari", $gelen_adetler[$i]);
        $sth->bindValue("tarih", date('Y-m-d H:i:s'));
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindValue("departman_id", $departman_id);
        $sth->bindValue("stok_id", $stok_idler[$i]);
        $sth->bindValue("stok_alt_kalem_id", $stok_alt_kalem_idler[$i]);
        $sth->bindValue("stok_alt_depo_id", $stok_alt_depo_idler[$i]);
        $durum = $sth->execute();
    }

    //Üretilen Adet Ekle
    $sql = "INSERT INTO uretilen_adetler(firma_id, planlama_id, grup_kodu, uretilen_adet, uretirken_verilen_fire_adet, mevcut_asama, 
            asama_sayisi, personel_id, departman_id, makina_id, baslangic_tarihi, bitis_tarihi) 
            VALUES(:firma_id, :planlama_id, :grup_kodu, :uretilen_adet, :uretirken_verilen_fire_adet, :mevcut_asama, 
            :asama_sayisi, :personel_id, :departman_id, :makina_id, :baslangic_tarihi, :bitis_tarihi)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("grup_kodu", $grup_kodu);
    $sth->bindParam("uretilen_adet", $uretilen_adet);
    $sth->bindParam("uretirken_verilen_fire_adet", $uretirken_verilen_fire_adet);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("asama_sayisi", $asama_sayisi);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindValue("makina_id", 0);
    $sth->bindValue("baslangic_tarihi", date('Y-m-d H:i:s'));
    $sth->bindValue("bitis_tarihi", date('Y-m-d H:i:s'));
    $durum = $sth->execute();

    


    //Planlama Güncelleme
    $sql = "UPDATE planlama SET mevcut_asama = mevcut_asama + 1, durum = 'beklemede'  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $durum = $sth->execute();

    //Son aşama ise Planlama ve Sipariş Tablosunu Güncelle
    if($mevcut_asama + 1 == $asama_sayisi){
        $sql = "UPDATE planlama SET biten_urun_adedi = biten_urun_adedi + :biten_urun_adedi, durum = 'bitti'  
                WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('biten_urun_adedi', $uretilen_adet);
        $sth->bindParam('id', $planlama_id);
        $durum = $sth->execute();

        $sql = "UPDATE siparisler SET islem = 'tamamlandi' WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $siparis_id);
        $durum = $sth->execute();
    }

    $_SESSION['durum'] = 'success';
    $_SESSION['mesaj'] = 'İşlem Başarılı';
    header('Location: /index.php?url=fason'); exit;
}


//Fasona Gönder
if(isset($_POST['fasona-gonder']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $planlama_id            = intval($_POST['planlama_id']);
    $departman_id           = intval($_POST['departman_id']);
    $mevcut_asama           = intval($_POST['mevcut_asama']);
    $stok_idler             = isset($_POST['stok_id']) ? $_POST['stok_id'] : [];
    $stok_alt_kalem_idler   = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id']   : [];
    $stok_alt_depo_idler    = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id']    : [];
    $tuketim_miktarilari    = isset($_POST['tuketim_miktari'])      ? $_POST['tuketim_miktari']     : [];
    $birim_idler            = isset($_POST['birim_id'])             ? $_POST['birim_id']            : [];
    $arsiv_alt_idler        = isset($_POST['arsiv_alt_id'])         ? $_POST['arsiv_alt_id']        : [];


    //stok tüketilenlere ekle
    for($i = 0 ; $i < count($stok_idler); $i++){
        //echo $i."<br>";
        $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, planlama_id, 
                personel_id,makina_id, mevcut_asama, fire_miktari, tuketim_miktari, birim_id, departman_id) 
                VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, :planlama_id, 
                :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, :birim_id, :departman_id);";
        $sth = $conn->prepare($sql);
        $sth->bindParam("stok_id", $stok_idler[$i]);
        $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem_idler[$i]);
        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_idler[$i]);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindValue("makina_id", 0);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindValue("fire_miktari", 0);
        $sth->bindParam("tuketim_miktari", $tuketim_miktarilari[$i]);
        $sth->bindParam("birim_id", $birim_idler[$i]);
        $sth->bindParam("departman_id", $departman_id);
        $durum = $sth->execute();
    }

    foreach ($arsiv_alt_idler as $arsiv_alt_id) {
        $sql = "UPDATE arsiv_altlar SET durum = 'fasonda'  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam("id", $arsiv_alt_id);
        $durum = $sth->execute();
    }

    //fason log
    $sql = "INSERT INTO uretim_fason_durum_loglar(planlama_id, departman_id, personel_id, mevcut_asama, durum) 
            VALUES(:planlama_id, :departman_id, :personel_id, :mevcut_asama, :durum)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindValue("durum", 'gitti');
    $durum = $sth->execute();

    //Planlama Durumunu Güncelleme
    $sql = "UPDATE planlama SET `durum` = 'fasonda' WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $durum = $sth->execute();

    $_SESSION['durum'] = 'success';
    $_SESSION['mesaj'] = 'Fasona Gönderildi';
    
    header('Location: /index.php?url=fason'); exit;
}

//Stok ve Arşivleri Getir
if(isset($_POST['islem']) && $_POST['islem'] == 'planlama-stok-arsiv-getir')
{
    $planlama_id = $_POST['planlama_id'];
    $sth = $conn->prepare('SELECT planlama.arsiv_altlar, planlama.stok_kalemler, planlama.stok_alt_kalemler, planlama.stok_alt_depo_adetler, 
    planlama.mevcut_asama, `planlama`.`adetler`,planlama.asama_sayisi,
    planlama.id, planlama.isim,planlama.mevcut_asama, planlama.departmanlar,
    `planlama`.`adetler`,planlama.stok_alt_depolar,  `planlama`.`grup_kodu`,
    siparisler.isin_adi, siparisler.id AS siparis_id, 
    musteri.marka FROM planlama 
    JOIN siparisler ON siparisler.id = planlama.siparis_id
    JOIN musteri ON musteri.id = `siparisler`.`musteri_id` 
    WHERE planlama.id = :id AND planlama.firma_id = :firma_id');
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlama = $sth->fetch(PDO::FETCH_ASSOC);

    $departmanlar       = json_decode($planlama['departmanlar'], true);
    $departman_id       = $departmanlar[$planlama['mevcut_asama']];

    $arsiv_altlar       = json_decode($planlama['arsiv_altlar'], true);
    $arsiv_altlar       = $arsiv_altlar[$planlama['mevcut_asama']];
    $arsiv_altlar       = array_filter($arsiv_altlar); 
    $arsiv_alt_id_birlestir = implode(',', $arsiv_altlar);

    $arsiv_kalemler = [];
    if(!empty($arsiv_alt_id_birlestir)){
        $sql = "SELECT  `arsiv_altlar`.`id`, `arsiv_altlar`.`kod`,`arsiv_altlar`.`ebat`,
        `arsiv_altlar`.`detay`,`arsiv_kalemler`.`arsiv`
        FROM `arsiv_altlar` 
        JOIN arsiv_kalemler ON arsiv_kalemler.id = arsiv_altlar.arsiv_id WHERE arsiv_altlar.id IN(:arsiv_idler)";
        $sth = $conn->prepare($sql);
        $sth->bindParam("arsiv_idler", $arsiv_alt_id_birlestir);
        $sth->execute();
        $arsiv_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
    }
    

    $stok_kalemler      = json_decode($planlama['stok_kalemler'], true);
    $stok_kalemler      = $stok_kalemler[$planlama['mevcut_asama']];

    $stok_alt_kalemler   = json_decode($planlama['stok_alt_kalemler'], true);
    $stok_alt_kalemler   = $stok_alt_kalemler[$planlama['mevcut_asama']];

    $stok_alt_depolar   = json_decode($planlama['stok_alt_depolar'], true);
    $stok_alt_depolar   = $stok_alt_depolar[$planlama['mevcut_asama']];

    $stok_alt_depo_adetler   = json_decode($planlama['stok_alt_depo_adetler'], true);
    $stok_alt_depo_adetler   = $stok_alt_depo_adetler[$planlama['mevcut_asama']];

    $stok_veriler = [];
    foreach ($stok_alt_depolar as $index =>  $stok_alt_depo_id) {
        if($stok_alt_depo_id == 0) {continue; }

        $sql = "SELECT stok_alt_depolar.id, stok_alt_depolar.stok_kodu,
        birimler.ad AS birim_ad, birimler.id AS birim_id
        FROM `stok_alt_depolar` 
        JOIN `birimler` ON `birimler`.id = stok_alt_depolar.birim_id
        WHERE stok_alt_depolar.id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("id", $stok_alt_depo_id);
        $sth->execute();
        $stok_alt_depo = $sth->fetch(PDO::FETCH_ASSOC);
        $stok_alt_depo['adet'] = $stok_alt_depo_adetler[$index];

        $sql = "SELECT id, veri FROM `stok_alt_kalemler` WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("id", $stok_alt_kalemler[$index]);
        $sth->execute();
        $stok_alt_kalem = $sth->fetch(PDO::FETCH_ASSOC);

        $sql = "SELECT id,stok_kalem FROM `stok_kalemleri` WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("id", $stok_kalemler[$index]);
        $sth->execute();
        $stok_kalem = $sth->fetch(PDO::FETCH_ASSOC);

        $stok_veriler[] = [
            'stok_kalem'        => $stok_kalem,
            'stok_alt_kalem'    => $stok_alt_kalem,
            'stok_alt_depo'     => $stok_alt_depo,
        ];
    }

    $sql = "SELECT id,firma_adi,tedarikci_unvani FROM `tedarikciler` WHERE firma_id = :firma_id AND fason = 'evet'";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->execute();
    $tedarikciler = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    ob_clean();
    echo json_encode([
        'planlama'          => $planlama,
        'departman_id'      => $departman_id,
        'stok_veriler'      => $stok_veriler,
        'arsiv_kalemler'    => $arsiv_kalemler,
        'tedarikciler'      => $tedarikciler,
    ]);
    exit;

}