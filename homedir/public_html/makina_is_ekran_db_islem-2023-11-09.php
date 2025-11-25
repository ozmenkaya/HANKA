<?php 
require_once "include/db.php";
require_once "include/oturum_kontrol.php";


//1- Makina Ayar Bitir 
if(isset($_GET['islem']) && $_GET['islem'] == 'makina-ayar-bitir'){

    $makina_id                  = intval($_GET['makina_id']);
    $mevcut_asama               = intval($_GET['mevcut_asama']);
    $planlama_id                = intval($_GET['planlama_id']);
    $departman_id               = intval($_GET['departman_id']);
    $makina_ayar_baslatma_tarih = $_GET['makina_ayar_suresi_varmi'] == 'yok' ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($_GET['makina_ayar_baslatma_tarih']));

    $sql = "INSERT INTO uretim_makina_ayar_log(planlama_id, makina_id, departman_id, personel_id, mevcut_asama, baslatma_tarih) 
        VALUES(:planlama_id, :makina_id, :departman_id, :personel_id, :mevcut_asama, :baslatma_tarih);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("baslatma_tarih", $makina_ayar_baslatma_tarih);
    $durum = $sth->execute();

    ob_clean();
    echo json_encode([
        'durum' => $durum
    ]);
    exit;
};

//2- Formlari Getir
if(isset($_GET['islem']) && $_GET['islem'] == 'formalari_getir')
{
    $departman_id   = intval($_GET['departman_id']);
    $planlama_id    = intval($_GET['planlama_id']);

    $sql = "SELECT durum FROM `planlama` WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->execute();
    $planlama = $sth->fetch(PDO::FETCH_ASSOC);

    if($planlama['durum'] == 'baslamadi'){
        $sql = "SELECT departman_formlar.* FROM `departman_formlar` 
                WHERE departman_formlar.firma_id = :firma_id AND departman_formlar.departman_id = :departman_id 
                AND departman_formlar.gosterme_asamasi  IN ( 'baslatta','her_durumda') ";
    }else if(in_array($planlama['durum'], ['basladi','beklemede'])){
        $sql = "SELECT departman_formlar.* FROM `departman_formlar` 
        WHERE departman_formlar.firma_id = :firma_id AND departman_formlar.departman_id = :departman_id 
        AND departman_formlar.gosterme_asamasi  IN ('her_durumda') ";
    }else{
        $sql = "SELECT departman_formlar.* FROM `departman_formlar` 
        WHERE departman_formlar.firma_id = :firma_id AND departman_formlar.departman_id = :departman_id 
        AND departman_formlar.gosterme_asamasi  IN ('bitirde','her_durumda') ";
    }
    
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $departman_id);
    $sth->execute();
    $formlar = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    ob_clean();
    echo json_encode(['formlar' => $formlar]);
}

//3- İşi başlatma
if(isset($_POST['isi_baslat']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $siparis_id     = intval($_POST['siparis_id']);
    $planlama_id    = intval($_POST['planlama_id']);
    $makina_id      = intval($_POST['makina_id']);
    $departman_id   = intval($_POST['departman_id']);
    $mevcut_asama   = intval($_POST['mevcut_asama']);
    $tekil_kod      = $_POST['tekil_kod'];
    $grup_kodu      = $_POST['grup_kodu'];


    $birim_idler            = isset($_POST['birim_id'])             ? $_POST['birim_id']            : [];
    $stok_idler             = isset($_POST['stok_id'])              ? $_POST['stok_id']             : [];
    $stok_alt_kalem_idler   = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id']   : [];
    $stok_alt_depo_idler    = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id']    : [];
    $tuketim_miktarilari    = isset($_POST['tuketim_miktari'])      ? $_POST['tuketim_miktari']     : [];
    $fire_miktarilari       = isset($_POST['fire_miktari'])         ? $_POST['fire_miktari']        : [];

    $sql = "SELECT id,durum,arsiv_altlar FROM `planlama` WHERE id = :id AND firma_id = :firma_id /*AND durum != 'baslamadi'*/";
    $sth = $conn->prepare($sql);
    $sth->bindParam("id", $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlama = $sth->fetch(PDO::FETCH_ASSOC);

    $arsiv_altlar               = json_decode($planlama['arsiv_altlar'], true);
    $arsiv_altlar               = array_filter($arsiv_altlar[$mevcut_asama]);
    $arsiv_alt_idler_birlestir  = implode(',',$arsiv_altlar);

    if(!empty($arsiv_altlar)){ //üretime giden alt arşivler 
        $sql = "UPDATE arsiv_altlar SET durum = 'uretimde'  WHERE id IN({$arsiv_alt_idler_birlestir})";
        $sth = $conn->prepare($sql);
        $durum = $sth->execute();
    }


    if($mevcut_asama == 0 && $planlama['durum'] == 'baslamadi'){
        $sql = "UPDATE siparisler SET islem = 'islemde' WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $siparis_id);
        $durum2 = $sth->execute();
    }


    //$sql = "UPDATE planlama SET durum = 'basladi', tekil_kod = :tekil_kod WHERE id = :id AND firma_id = :firma_id";
    $sql = "UPDATE planlama SET durum = 'basladi' WHERE id = :id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();
 

    //stok kullanıları ekle
    foreach($stok_idler as $index => $stok_id){
        //Stok Alt Depo Kullanılanlara Ekle
        $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, planlama_id,
                personel_id, makina_id, mevcut_asama, fire_miktari, tuketim_miktari, birim_id, departman_id) 
                VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, :planlama_id,
                :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, :birim_id, :departman_id);";

        $sth = $conn->prepare($sql);
        $sth->bindParam("stok_id", $stok_id);
        $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem_idler[$index]);
        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_idler[$index]);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("fire_miktari", $fire_miktarilari[$index]);
        $sth->bindParam("tuketim_miktari", $tuketim_miktarilari[$index]);
        $sth->bindParam("birim_id", $birim_idler[$index]);
        $sth->bindParam("departman_id", $departman_id);
        $sth->execute();

        //Stok Alt Depo Kullanılan Adedi Güncelle
        $sql = "UPDATE stok_alt_depolar SET kullanilan_adet = kullanilan_adet + :kullanilan_adet  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_adet', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('id', $stok_alt_depo_idler[$index]);
        $sth->execute();

        //Üretim Reservasyondan Düşme
        $sql = "UPDATE uretim_reservasyon 
                SET kullanilan_miktar = kullanilan_miktar + :kullanilan_miktar  
                WHERE grup_kodu = :grup_kodu AND mevcut_asama = :mevcut_asama AND stok_alt_depo_id = :stok_alt_depo_id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_miktar', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('grup_kodu', $grup_kodu);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->bindParam('stok_alt_depo_id', $stok_alt_depo_idler[$index]);
        $sth->execute();
    }


    if($durum)
    {
        $sql = "INSERT INTO uretim_islem_tarihler(planlama_id,departman_id, makina_id,personel_id, mevcut_asama) 
                VALUES(:planlama_id,:departman_id, :makina_id, :personel_id, :mevcut_asama)";
        $sth = $conn->prepare($sql);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("departman_id", $departman_id);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindValue("mevcut_asama", $mevcut_asama);
        $sth->execute();

        $sth = $conn->prepare('SELECT siparis_id FROM planlama WHERE id = :id');
        $sth->bindParam('id', $planlama_id);
        $sth->execute();
        $planlama = $sth->fetch(PDO::FETCH_ASSOC);
        
    }

    //form olmayabilir
    if(isset($_POST['formlar'])){
        $sql = "INSERT INTO departman_form_degerler(firma_id,planlama_id,departman_form_id, deger, tekil_kod) 
            VALUES(:firma_id, :planlama_id, :departman_form_id, :deger, :tekil_kod);";
        foreach ($_POST['formlar'] as $departman_form_id => $deger) {
            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("planlama_id", $planlama_id);
            $sth->bindParam("departman_form_id", $departman_form_id);
            $sth->bindParam("deger", $deger);
            $sth->bindParam("tekil_kod", $tekil_kod);
            $durum = $sth->execute();
        }
    }


    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'İş Başlatıldı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'İş Başlatılamadı!!';
    }

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;
    
}

//4- Mola Bitirme
if(isset($_POST['mola-bitir'])){
    $planlama_id            = intval($_POST['planlama_id']);
    $makina_id              = intval($_POST['makina_id']);
    $departman_id           = intval($_POST['departman_id']);
    $mevcut_asama           = intval($_POST['mevcut_asama']);
    $tekil_kod              = $_POST['tekil_kod'];
    $mola_baslatma_tarih    = date('Y-m-d H:i:s', strtotime($_POST['mola_baslatma_tarih']));

    $sql = "INSERT INTO uretim_mola_log(planlama_id,makina_id, departman_id,personel_id, mevcut_asama, baslatma_tarihi, tekil_kod) 
                VALUES(:planlama_id, :makina_id,:departman_id, :personel_id, :mevcut_asama, :baslatma_tarihi, :tekil_kod)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindValue("mevcut_asama", $mevcut_asama);
    $sth->bindValue("baslatma_tarihi", $mola_baslatma_tarih);
    $sth->bindValue("tekil_kod", $tekil_kod);
    $sth->execute();

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;
}


//5- Yemek Mola Bitir
if(isset($_POST['yemek-mola-bitir']))
{
    $planlama_id    = intval($_POST['planlama_id']);
    $makina_id      = intval($_POST['makina_id']);
    $departman_id   = intval($_POST['departman_id']);
    $mevcut_asama   = intval($_POST['mevcut_asama']);
    $tekil_kod      = $_POST['tekil_kod'];
    $yemek_mola_baslatma_tarih    = date('Y-m-d H:i:s', strtotime($_POST['yemek_mola_baslatma_tarih']));

    $sql = "INSERT INTO uretim_yemek_mola_log(planlama_id, makina_id, departman_id, personel_id, mevcut_asama, baslatma_tarihi, tekil_kod) 
            VALUES(:planlama_id, :makina_id, :departman_id, :personel_id,:mevcut_asama,:baslatma_tarihi, :tekil_kod);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("baslatma_tarihi", $yemek_mola_baslatma_tarih);
    $sth->bindParam("tekil_kod", $tekil_kod);
    $durum = $sth->execute();

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;
    
}


//6- Toplantı Bitir
if(isset($_POST['toplanti-bitir']) )
{
    $planlama_id    = intval($_POST['planlama_id']);
    $makina_id      = intval($_POST['makina_id']);
    $departman_id   = intval($_POST['departman_id']);
    $mevcut_asama   = intval($_POST['mevcut_asama']);
    $tekil_kod      = $_POST['tekil_kod'];
    $toplanti_baslatma_tarih    = date('Y-m-d H:i:s', strtotime($_POST['toplanti_baslatma_tarih']));

    $sql = "INSERT INTO uretim_toplanti_log(planlama_id,makina_id,  departman_id, personel_id,mevcut_asama, baslatma_tarihi, tekil_kod) 
            VALUES(:planlama_id, :makina_id, :departman_id, :personel_id,:mevcut_asama, :baslatma_tarihi, :tekil_kod);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("baslatma_tarihi", $toplanti_baslatma_tarih);
    $sth->bindParam("tekil_kod", $tekil_kod);
    $durum = $sth->execute();

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;
}


//6- Paydos işlemi
if(isset($_POST['paydos']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $planlama_id                            = intval($_POST['planlama_id']);
    $grup_kodu                              = $_POST['grup_kodu'];
    $mevcut_asama                           = intval($_POST['mevcut_asama']);
    $asama_sayisi                           = intval($_POST['asama_sayisi']);
    $departman_id                           = intval($_POST['departman_id']);
    $makina_id                              = intval($_POST['makina_id']);
    $uretim_islem_tarih_id                  = $_POST['uretim_islem_tarih_id'];
    $uretim_islem_tarih_baslatma_tarih      = $_POST['uretim_islem_tarih_baslatma_tarih'];
    $uretilen_adet                          = intval($_POST['uretilen_adet']);
    $uretirken_verilen_fire_adet            = intval($_POST['uretirken_verilen_fire_adet']);

    $birim_idler                            = isset($_POST['birim_id'])             ? $_POST['birim_id'] : [];
    $stok_idler                             = isset($_POST['stok_id'])              ? $_POST['stok_id'] : [];
    $stok_alt_kalem_idler                   = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id'] : [];
    $stok_alt_depo_idler                    = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id'] : [];
    $tuketim_miktarilari                    = isset($_POST['tuketim_miktari'])      ? $_POST['tuketim_miktari'] : [];
    $fire_miktarilari                       = isset($_POST['fire_miktari'])         ? $_POST['fire_miktari'] : [];

    //paydos log
    $sql = 'INSERT INTO uretim_paydos_loglar(firma_id, personel_id, makina_id, planlama_id, mevcut_asama)
            VALUES(:firma_id, :personel_id, :makina_id, :planlama_id, :mevcut_asama)';
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('personel_id', $_SESSION['personel_id']);
    $sth->bindParam('makina_id', $makina_id);
    $sth->bindParam('planlama_id', $planlama_id);
    $sth->bindParam('mevcut_asama', $mevcut_asama);
    $durum = $sth->execute();


    //uretim işlem tarihlerde bitirme tarihini güncelle
    $sql = "UPDATE uretim_islem_tarihler SET bitirme_tarihi = :bitirme_tarihi  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindValue('bitirme_tarihi', date('Y-m-d H:i:s'));
    $sth->bindParam('id', $uretim_islem_tarih_id);
    $durum = $sth->execute();

    //uretilen_adetler tablosuna ekle
    $sql = 'INSERT INTO uretilen_adetler(firma_id,planlama_id, grup_kodu, uretilen_adet, uretirken_verilen_fire_adet, mevcut_asama, 
            asama_sayisi, personel_id, departman_id, makina_id, baslangic_tarihi) 
            VALUES(:firma_id,:planlama_id,:grup_kodu, :uretilen_adet, :uretirken_verilen_fire_adet, :mevcut_asama, 
            :asama_sayisi, :personel_id, :departman_id, :makina_id, :baslangic_tarihi);';
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
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("baslangic_tarihi", $uretim_islem_tarih_baslatma_tarih);
    $durum = $sth->execute();


    //planlamada durumu beklemeye al
    $sql = "UPDATE planlama SET durum = 'beklemede',tekil_kod = :tekil_kod WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindValue('tekil_kod', uniqid());
    $durum = $sth->execute();

    //stok kullanıları ekle
    foreach($stok_idler as $index => $stok_id){
        $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, planlama_id,
                personel_id, makina_id, mevcut_asama, fire_miktari, tuketim_miktari, birim_id, departman_id) 
                VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, :planlama_id,
                :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, :birim_id, :departman_id);";

        $sth = $conn->prepare($sql);
        $sth->bindParam("stok_id", $stok_id);
        $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem_idler[$index]);
        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_idler[$index]);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("fire_miktari", $fire_miktarilari[$index]);
        $sth->bindParam("tuketim_miktari", $tuketim_miktarilari[$index]);
        $sth->bindParam("birim_id", $birim_idler[$index]);
        $sth->bindParam("departman_id", $departman_id);
        $durum = $sth->execute();

        //Stok Alt Depo Kullanılan Adedi Güncelle
        $sql = "UPDATE stok_alt_depolar SET kullanilan_adet = kullanilan_adet + :kullanilan_adet  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_adet', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('id', $stok_alt_depo_idler[$index]);
        $durum = $sth->execute();

        //Üretim Reservasyondan Düşme
        $sql = "UPDATE uretim_reservasyon 
                SET kullanilan_miktar = kullanilan_miktar + :kullanilan_miktar  
                WHERE grup_kodu = :grup_kodu AND mevcut_asama = :mevcut_asama AND stok_alt_depo_id = :stok_alt_depo_id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_miktar', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('grup_kodu', $grup_kodu);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->bindParam('stok_alt_depo_id', $stok_alt_depo_idler[$index]);
        $sth->execute();

    }

    //son adım ise planlama tablosunda biten_urun_adedi kolonuna ekle (uretilen adetleri)
    if($mevcut_asama + 1 == $asama_sayisi){
        $sql = "UPDATE planlama SET biten_urun_adedi = biten_urun_adedi + :biten_urun_adedi  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('biten_urun_adedi', $uretilen_adet);
        $sth->bindParam('id', $planlama_id);
        $durum = $sth->execute();
    }

    header("Location: makina_is_listesi.php?makina-id={$makina_id }");
    exit;
}

//8- Devret
//işi başka makinaya devretme
if(isset($_POST['devret']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $planlama_id                            = intval($_POST['planlama_id']);
    $grup_kodu                              = $_POST['grup_kodu'];
    $mevcut_asama                           = intval($_POST['mevcut_asama']);
    $asama_sayisi                           = intval($_POST['asama_sayisi']);
    $departman_id                           = intval($_POST['departman_id']);
    $makina_id                              = intval($_POST['makina_id']);
    $uretim_islem_tarih_id                  = $_POST['uretim_islem_tarih_id'];
    $uretim_islem_tarih_baslatma_tarih      = $_POST['uretim_islem_tarih_baslatma_tarih'];
    $uretilen_adet                          = intval($_POST['uretilen_adet']);
    $uretirken_verilen_fire_adet            = intval($_POST['uretirken_verilen_fire_adet']);

    $birim_idler                            = isset($_POST['birim_id'])             ? $_POST['birim_id'] : [];
    $stok_idler                             = isset($_POST['stok_id'])              ? $_POST['stok_id'] : [];
    $stok_alt_kalem_idler                   = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id'] : [];
    $stok_alt_depo_idler                    = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id'] : [];
    $tuketim_miktarilari                    = isset($_POST['tuketim_miktari'])      ? $_POST['tuketim_miktari'] : [];
    $fire_miktarilari                       = isset($_POST['fire_miktari'])         ? $_POST['fire_miktari'] : [];


    $devredilen_makina_id                    = $_POST['devredilen_makina_id'];
    $devretme_sebebi                        = trim($_POST['devretme_sebebi']);

    //devretme sebebi
    $sql = "INSERT INTO uretim_makina_devretme_sebebi_loglar(planlama_id, hangi_makinadan, hangi_makinaya,mevcut_asama,
            personel_id, departman_id, devretme_sebebi)
            VALUES(:planlama_id, :hangi_makinadan, :hangi_makinaya, :mevcut_asama, :personel_id, :departman_id, :devretme_sebebi)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("hangi_makinadan", $makina_id);
    $sth->bindParam("hangi_makinaya", $devredilen_makina_id);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("devretme_sebebi", $devretme_sebebi);
    $durum = $sth->execute();


    //uretim işlem tarihlerde bitirme tarihini güncelle
    $sql = "UPDATE uretim_islem_tarihler SET bitirme_tarihi = :bitirme_tarihi  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindValue('bitirme_tarihi', date('Y-m-d H:i:s'));
    $sth->bindParam('id', $uretim_islem_tarih_id);
    $durum = $sth->execute();

    //uretilen_adetler tablosuna ekle
    $sql = 'INSERT INTO uretilen_adetler(firma_id,planlama_id, grup_kodu, uretilen_adet, uretirken_verilen_fire_adet, mevcut_asama, 
            asama_sayisi, personel_id, departman_id, makina_id, baslangic_tarihi) 
            VALUES(:firma_id,:planlama_id, :grup_kodu, :uretilen_adet, :uretirken_verilen_fire_adet, :mevcut_asama, 
            :asama_sayisi, :personel_id, :departman_id, :makina_id, :baslangic_tarihi);';
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
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("baslangic_tarihi", $uretim_islem_tarih_baslatma_tarih);
    $durum = $sth->execute();

    $sql = "SELECT  makinalar FROM `planlama`  WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->execute();
    $mevcut_planlama = $sth->fetch(PDO::FETCH_ASSOC);

    $makinalar = json_decode($mevcut_planlama['makinalar'], true);
    $makinalar[$mevcut_asama] = (int)$devredilen_makina_id;
    $makinalar = json_encode($makinalar);



    //planlamada durumu beklemeye al
    $sql = "UPDATE planlama SET durum = 'beklemede', makinalar = :makinalar, tekil_kod = :tekil_kod WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('makinalar', $makinalar);
    $sth->bindValue('tekil_kod', uniqid());
    $durum = $sth->execute();

    //stok kullanıları ekle
    foreach($stok_idler as $index => $stok_id){
        $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, planlama_id,
                personel_id, makina_id, mevcut_asama, fire_miktari, tuketim_miktari, birim_id, departman_id) 
                VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, :planlama_id,
                :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, :birim_id, :departman_id);";

        $sth = $conn->prepare($sql);
        $sth->bindParam("stok_id", $stok_id);
        $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem_idler[$index]);
        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_idler[$index]);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("fire_miktari", $fire_miktarilari[$index]);
        $sth->bindParam("tuketim_miktari", $tuketim_miktarilari[$index]);
        $sth->bindParam("birim_id", $birim_idler[$index]);
        $sth->bindParam("departman_id", $departman_id);
        $durum = $sth->execute();

        //Stok Alt Depo Kullanılan Adedi Güncelle
        $sql = "UPDATE stok_alt_depolar SET kullanilan_adet = kullanilan_adet + :kullanilan_adet  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_adet', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('id', $stok_alt_depo_idler[$index]);
        $durum = $sth->execute();

        //Üretim Reservasyondan Düşme
        $sql = "UPDATE uretim_reservasyon 
                SET kullanilan_miktar = kullanilan_miktar + :kullanilan_miktar  
                WHERE grup_kodu = :grup_kodu AND mevcut_asama = :mevcut_asama AND stok_alt_depo_id = :stok_alt_depo_id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_miktar', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('grup_kodu', $grup_kodu);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->bindParam('stok_alt_depo_id', $stok_alt_depo_idler[$index]);
        $sth->execute();
    }

    //son adım ise planlama tablosunda biten_urun_adedi kolonuna ekle (uretilen adetleri)
    if($mevcut_asama + 1 == $asama_sayisi){
        $sql = "UPDATE planlama SET biten_urun_adedi = biten_urun_adedi + :biten_urun_adedi  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('biten_urun_adedi', $uretilen_adet);
        $sth->bindParam('id', $planlama_id);
        $durum = $sth->execute();
    }

    header("Location: makina_is_listesi.php?makina-id={$makina_id}");
    exit;

}

//9- Kontrol
//başlangıç ve bitiş kontrol formaları getirme 
if(isset($_GET['islem']) && $_GET['islem'] == 'kontrol_formalari_getir')
{
    $departman_id   = intval($_GET['departman_id']);
    $planlama_id    = intval($_GET['planlama_id']);
    $tekil_kod      = $_GET['tekil_kod'];
    $sql = "SELECT departman_formlar.*, departman_form_degerler.deger FROM `departman_formlar`  
                JOIN departman_form_degerler 
                ON departman_form_degerler.departman_form_id = departman_formlar.id
                WHERE departman_form_degerler.planlama_id = :planlama_id 
                AND departman_formlar.firma_id = :firma_id 
                AND departman_formlar.departman_id = :departman_id 
                AND departman_form_degerler.tekil_kod = :tekil_kod
                AND departman_formlar.gosterme_asamasi  IN ( 'baslatta','her_durumda') ";
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $departman_id);
    $sth->bindParam('tekil_kod', $tekil_kod);
    $sth->execute();
    $baslangic_formlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT departman_formlar.* FROM `departman_formlar` 
                WHERE  departman_formlar.firma_id = :firma_id AND departman_formlar.departman_id = :departman_id 
                AND departman_formlar.gosterme_asamasi  IN ( 'bitirde','her_durumda') ";
    //departman_form_degerler.planlama_id = :planlama_id AND
    $sth = $conn->prepare($sql);
    //$sth->bindParam('planlama_id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $departman_id);
    $sth->execute();
    $bitisteki_formlar = $sth->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode(['baslangic_formlar' => $baslangic_formlar, 'bitisteki_formlar'=>$bitisteki_formlar]); exit;
}


//10- İş Değiştir
if(isset($_POST['degistir']))
{

    //echo "<pre>"; print_r($_POST); exit;
    $planlama_id                            = intval($_POST['planlama_id']);
    $grup_kodu                              = $_POST['grup_kodu'];
    $mevcut_asama                           = intval($_POST['mevcut_asama']);
    $asama_sayisi                           = intval($_POST['asama_sayisi']);
    $departman_id                           = intval($_POST['departman_id']);
    $makina_id                              = intval($_POST['makina_id']);
    $uretim_islem_tarih_id                  = $_POST['uretim_islem_tarih_id'];
    $uretim_islem_tarih_baslatma_tarih      = $_POST['uretim_islem_tarih_baslatma_tarih'];
    $uretilen_adet                          = intval($_POST['uretilen_adet']);
    $uretirken_verilen_fire_adet            = intval($_POST['uretirken_verilen_fire_adet']);

    $birim_idler                            = isset($_POST['birim_id'])             ? $_POST['birim_id'] : [];
    $stok_idler                             = isset($_POST['stok_id'])              ? $_POST['stok_id'] : [];
    $stok_alt_kalem_idler                   = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id'] : [];
    $stok_alt_depo_idler                    = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id'] : [];
    $tuketim_miktarilari                    = isset($_POST['tuketim_miktari'])      ? $_POST['tuketim_miktari'] : [];
    $fire_miktarilari                       = isset($_POST['fire_miktari'])         ? $_POST['fire_miktari'] : [];

    $sorun_bildirisin_mi                    = isset($_POST['sorun_bildirisin_mi']) ? $_POST['sorun_bildirisin_mi'] : 0;
    $degistirme_sebebi                      = trim($_POST['degistirme_sebebi']);

    //devretme sebebi
    $sql = "INSERT INTO uretim_degistir_loglar(planlama_id, makina_id, mevcut_asama, personel_id, departman_id, 
            degistirme_sebebi, sorun_bildirisin_mi)
            VALUES(:planlama_id, :makina_id, :mevcut_asama, :personel_id, :departman_id, 
            :degistirme_sebebi, :sorun_bildirisin_mi)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("degistirme_sebebi", $degistirme_sebebi);
    $sth->bindParam("sorun_bildirisin_mi", $sorun_bildirisin_mi);
    $durum = $sth->execute();

    //uretim işlem tarihlerde bitirme tarihini güncelle
    $sql = "UPDATE uretim_islem_tarihler SET bitirme_tarihi = :bitirme_tarihi  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindValue('bitirme_tarihi', date('Y-m-d H:i:s'));
    $sth->bindParam('id', $uretim_islem_tarih_id);
    $durum = $sth->execute();


    //uretilen_adetler tablosuna ekle
    $sql = 'INSERT INTO uretilen_adetler(firma_id,planlama_id, grup_kodu, uretilen_adet, uretirken_verilen_fire_adet, mevcut_asama, 
            asama_sayisi, personel_id, departman_id, makina_id, baslangic_tarihi) 
            VALUES(:firma_id,:planlama_id, :grup_kodu, :uretilen_adet, :uretirken_verilen_fire_adet, :mevcut_asama, 
            :asama_sayisi, :personel_id, :departman_id, :makina_id, :baslangic_tarihi);';
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
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("baslangic_tarihi", $uretim_islem_tarih_baslatma_tarih);
    $durum = $sth->execute();


    //planlamada durumu beklemeye al
    $sql = "UPDATE planlama SET durum = 'beklemede', tekil_kod = :tekil_kod WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindValue('tekil_kod', uniqid());
    $durum = $sth->execute();

    //stok kullanıları ekle
    foreach($stok_idler as $index => $stok_id){
        //Stok Alt Depo Kullanılanlara Ekle
        $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, planlama_id,
                personel_id, makina_id, mevcut_asama, fire_miktari, tuketim_miktari, birim_id, departman_id) 
                VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, :planlama_id,
                :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, :birim_id, :departman_id);";

        $sth = $conn->prepare($sql);
        $sth->bindParam("stok_id", $stok_id);
        $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem_idler[$index]);
        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_idler[$index]);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("fire_miktari", $fire_miktarilari[$index]);
        $sth->bindParam("tuketim_miktari", $tuketim_miktarilari[$index]);
        $sth->bindParam("birim_id", $birim_idler[$index]);
        $sth->bindParam("departman_id", $departman_id);
        $durum = $sth->execute();

        //Stok Alt Depo Kullanılan Adedi Güncelle
        $sql = "UPDATE stok_alt_depolar SET kullanilan_adet = kullanilan_adet + :kullanilan_adet  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_adet', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('id', $stok_alt_depo_idler[$index]);
        $durum = $sth->execute();

        //Üretim Reservasyondan Düşme
        $sql = "UPDATE uretim_reservasyon 
                SET kullanilan_miktar = kullanilan_miktar + :kullanilan_miktar  
                WHERE grup_kodu = :grup_kodu AND mevcut_asama = :mevcut_asama AND stok_alt_depo_id = :stok_alt_depo_id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_miktar', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('grup_kodu', $grup_kodu);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->bindParam('stok_alt_depo_id', $stok_alt_depo_idler[$index]);
        $sth->execute();
    }

    //son adım ise planlama tablosunda biten_urun_adedi kolonuna ekle (uretilen adetleri)
    if($mevcut_asama + 1 == $asama_sayisi){
        $sql = "UPDATE planlama SET biten_urun_adedi = biten_urun_adedi + :biten_urun_adedi  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('biten_urun_adedi', $uretilen_adet);
        $sth->bindParam('id', $planlama_id);
        $durum = $sth->execute();
    }

    header("Location: makina_is_listesi.php?makina-id={$makina_id}");
    exit;
}


//11- Mesaj gönder
if(isset($_POST['mesaj-gonder']))
{
    $personel_id    = intval($_SESSION['personel_id']);
    $departman_id   = intval($_POST['departman_id']);
    $planlama_id    = intval($_POST['planlama_id']);
    $makina_id      = intval($_POST['makina_id']);
    $mevcut_asama   = intval($_POST['mevcut_asama']);
    $mesaj          = trim($_POST['mesaj']);
    $grup_kodu      = trim($_POST['grup_kodu']);

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
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Mesaj Gönderme Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Mesaj Gönderme Başarısız';
    }

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;
}


//12- iş bitime için formları alma
if(isset($_POST['islem']) && $_POST['islem'] == 'is_bitir_form_getir')
{
    $departman_id = $_POST['departman_id'];
    $sql = "SELECT * FROM `departman_formlar` 
    WHERE firma_id = :firma_id AND departman_id = :departman_id AND gosterme_asamasi IN('bitirde','her_durumda');";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $departman_id);
    $sth->execute();
    $formlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['formlar' => $formlar]);
}

//13- iş bitirme son aşama 
if(isset($_POST['is_bitir_son_asama']))
{
    //echo "<pre>"; print_r($_POST);exit;
    $eksik_uretimde_onay_isteme_durumu      = $_POST['eksik_uretimde_onay_isteme_durumu'];
    $min_uretilecek_adet                    = intval($_POST['min_uretilecek_adet']);
    $siparis_id                             = intval($_POST['siparis_id']);
    $planlama_id                            = intval($_POST['planlama_id']);
    $grup_kodu                              = $_POST['grup_kodu'];
    $mevcut_asama                           = intval($_POST['mevcut_asama']);
    $asama_sayisi                           = intval($_POST['asama_sayisi']);
    $departman_id                           = intval($_POST['departman_id']);
    $makina_id                              = intval($_POST['makina_id']);
    $uretim_islem_tarih_id                  = $_POST['uretim_islem_tarih_id'];
    $uretim_islem_tarih_baslatma_tarih      = $_POST['uretim_islem_tarih_baslatma_tarih'];
    $uretilen_adet                          = intval($_POST['uretilen_adet']);
    $uretirken_verilen_fire_adet            = intval($_POST['uretirken_verilen_fire_adet']);
    $tekil_kod                              = $_POST['tekil_kod'];

    

    $birim_idler                            = isset($_POST['birim_id'])             ? $_POST['birim_id'] : [];
    $stok_idler                             = isset($_POST['stok_id'])              ? $_POST['stok_id'] : [];
    $stok_alt_kalem_idler                   = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id'] : [];
    $stok_alt_depo_idler                    = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id'] : [];
    $tuketim_miktarilari                    = isset($_POST['tuketim_miktari'])      ? $_POST['tuketim_miktari'] : [];
    $fire_miktarilari                       = isset($_POST['fire_miktari'])         ? $_POST['fire_miktari'] : [];

    //Mevcut Planlama
    $sql = 'SELECT id, adetler,sureler,grup_kodu,arsiv_altlar FROM planlama WHERE id = :id AND firma_id = :firma_id ';
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $mevcut_planlama = $sth->fetch(PDO::FETCH_ASSOC);


    $arsiv_altlar               = json_decode($mevcut_planlama['arsiv_altlar'], true);
    $arsiv_altlar               = array_filter($arsiv_altlar[$mevcut_asama]);
    $arsiv_alt_idler_birlestir  = implode(',',$arsiv_altlar);

    if(!empty($arsiv_altlar)){ //üretime giden alt arşivler 
        $sql = "UPDATE arsiv_altlar SET durum = 'arsivde'  WHERE id IN({$arsiv_alt_idler_birlestir})";
        $sth = $conn->prepare($sql);
        $durum = $sth->execute();
    }

    //Bir Sonraki Aşamaya Geçecek İçin Aynı Aşamada Daha Önce Var mı Varsa Üstüne Ekleyecez
    $sql = 'SELECT id, adetler,sureler FROM planlama 
            WHERE grup_kodu = :grup_kodu AND firma_id = :firma_id AND mevcut_asama = :mevcut_asama';
    $sth = $conn->prepare($sql);
    $sth->bindParam('grup_kodu', $mevcut_planlama['grup_kodu']);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindValue('mevcut_asama', $mevcut_asama + 1);
    $sth->execute();
    $sonraki_asama_olan_planlama = $sth->fetch(PDO::FETCH_ASSOC);

    /*
    echo "<pre>";
    echo "session<br>"; print_r($_SESSION);
    echo "post<br>"; print_r($_POST);
    echo "mevcut_planlama<br>"; print_r($mevcut_planlama);
    echo "sonraki_asama_olan_planlama<br>"; print_r($sonraki_asama_olan_planlama);
    exit;
    */




    //uretim işlem tarihlerde bitirme tarihini güncelle(HER DURUMDA)
    $sql = "UPDATE uretim_islem_tarihler SET bitirme_tarihi = :bitirme_tarihi  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindValue('bitirme_tarihi', date('Y-m-d H:i:s'));
    $sth->bindParam('id', $uretim_islem_tarih_id);
    $durum = $sth->execute();

    //uretilen_adetler tablosuna ekle(HER DURUMDA)
    $sql = 'INSERT INTO uretilen_adetler(firma_id,planlama_id, grup_kodu, uretilen_adet, uretirken_verilen_fire_adet, mevcut_asama, 
            asama_sayisi, personel_id, departman_id, makina_id, aktarma_topla, baslangic_tarihi) 
            VALUES(:firma_id,:planlama_id, :grup_kodu, :uretilen_adet, :uretirken_verilen_fire_adet, :mevcut_asama, 
            :asama_sayisi, :personel_id, :departman_id, :makina_id,:aktarma_topla, :baslangic_tarihi);';
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("grup_kodu", $mevcut_planlama['grup_kodu']);
    $sth->bindParam("uretilen_adet", $uretilen_adet);
    $sth->bindParam("uretirken_verilen_fire_adet", $uretirken_verilen_fire_adet);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("asama_sayisi", $asama_sayisi);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindValue("aktarma_topla", 'hayır');
    $sth->bindParam("baslangic_tarihi", $uretim_islem_tarih_baslatma_tarih);
    $durum = $sth->execute();

    //stok kullanıları ekle(HER DURUMDA)
    foreach($stok_idler as $index => $stok_id){
        $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, planlama_id,
                personel_id, makina_id, mevcut_asama, fire_miktari, tuketim_miktari, birim_id, departman_id) 
                VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, :planlama_id,
                :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, :birim_id, :departman_id);";

        $sth = $conn->prepare($sql);
        $sth->bindParam("stok_id", $stok_id);
        $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem_idler[$index]);
        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_idler[$index]);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("fire_miktari", $fire_miktarilari[$index]);
        $sth->bindParam("tuketim_miktari", $tuketim_miktarilari[$index]);
        $sth->bindParam("birim_id", $birim_idler[$index]);
        $sth->bindParam("departman_id", $departman_id);
        $durum = $sth->execute();

        //Stok Alt Depo Kullanılan Adedi Güncelle
        $sql = "UPDATE stok_alt_depolar SET kullanilan_adet = kullanilan_adet + :kullanilan_adet  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_adet', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('id', $stok_alt_depo_idler[$index]);
        $durum = $sth->execute();

        //Üretim Reservasyondan Düşme
        $sql = "UPDATE uretim_reservasyon 
                SET kullanilan_miktar = kullanilan_miktar + :kullanilan_miktar  
                WHERE grup_kodu = :grup_kodu AND mevcut_asama = :mevcut_asama AND stok_alt_depo_id = :stok_alt_depo_id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_miktar', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('grup_kodu', $grup_kodu);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->bindParam('stok_alt_depo_id', $stok_alt_depo_idler[$index]);
        $sth->execute();
    }


    //form olmayabilir(HER DURUMDA)
    if(isset($_POST['bitir_formlar'])){
        $sql = "INSERT INTO departman_form_degerler(firma_id,planlama_id,departman_form_id, deger, tekil_kod) 
            VALUES(:firma_id, :planlama_id, :departman_form_id, :deger, :tekil_kod);";
        foreach ($_POST['bitir_formlar'] as $departman_form_id => $deger) {
            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("planlama_id", $planlama_id);
            $sth->bindParam("departman_form_id", $departman_form_id);
            $sth->bindParam("deger", $deger);
            $sth->bindParam("tekil_kod", $tekil_kod);
            $durum = $sth->execute();
        }
    }

    $sql = "SELECT SUM(uretilen_adet) AS uretilen_adet FROM `uretilen_adetler` 
            WHERE planlama_id = :planlama_id AND firma_id = :firma_id 
            AND mevcut_asama = :mevcut_asama AND aktarma_topla = 'evet'";
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id',$planlama_id);
    $sth->bindParam('firma_id',$_SESSION['firma_id']);
    $sth->bindParam('mevcut_asama',$mevcut_asama);
    $sth->execute();
    $daha_onceden_toplam_uretilen = $sth->fetch(PDO::FETCH_ASSOC);
    $daha_onceden_toplam_uretilen_adet = empty($daha_onceden_toplam_uretilen['uretilen_adet']) ? 0 : $daha_onceden_toplam_uretilen['uretilen_adet'];

    //son aşama değilse
    if($mevcut_asama + 1 != $asama_sayisi){
        $mevcut_planlama_adetler = json_decode($mevcut_planlama['adetler'], true);
        $aktarilan_adet = ($mevcut_planlama_adetler[$mevcut_asama+1]*($uretilen_adet + $daha_onceden_toplam_uretilen_adet))/$mevcut_planlama_adetler[$mevcut_asama];
        $sql = "INSERT INTO uretim_aktarma_loglar(planlama_id, grup_kodu, aktarilan_asama, aktarilan_adet) 
                VALUES(:planlama_id, :grup_kodu, :aktarilan_asama, :aktarilan_adet)";
        $sth = $conn->prepare($sql);
        $sth->bindParam("planlama_id",      $mevcut_planlama['id']);
        $sth->bindParam("grup_kodu",        $mevcut_planlama['grup_kodu']);
        $sth->bindValue("aktarilan_asama",  $mevcut_asama + 1);
        $sth->bindParam("aktarilan_adet",   $aktarilan_adet);
        $sth->execute();
    }
    
    //Eksik Üretimde Onay Yoksa Veya Eksik Üretim Yoksa
    if( $eksik_uretimde_onay_isteme_durumu == 'hayır' || 
        !($eksik_uretimde_onay_isteme_durumu == 'evet' && $min_uretilecek_adet > 0 && $uretilen_adet < $min_uretilecek_adet)){
        $sql = "DELETE FROM uretim_reservasyon WHERE grup_kodu= :grup_kodu AND mevcut_asama = :mevcut_asama";
        $sth = $conn->prepare($sql);
        $sth->bindParam('grup_kodu', $grup_kodu);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->execute();        
    }


    if(empty($sonraki_asama_olan_planlama)){ //sonraki aşamada daha önceden yoksa
        //eksik adet üretildi ve bir sonraki aşama için onay gereklidir
        if($eksik_uretimde_onay_isteme_durumu == 'evet' && $min_uretilecek_adet > 0 && $uretilen_adet < $min_uretilecek_adet){
            $eksik_uretilen_adet = $min_uretilecek_adet-$uretilen_adet;
            $sql = "UPDATE planlama SET asamada_eksik_adet_varmi = 'var',eksik_adet = :eksik_adet,durum = 'beklemede'
                    WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('eksik_adet', $eksik_uretilen_adet);
            $sth->bindParam('id', $planlama_id);
            $durum = $sth->execute();

            $sql = "INSERT INTO uretim_eksik_uretilen_loglar(planlama_id, mevcut_asama, personel_id, eksik_adet)
                    VALUES(:planlama_id, :mevcut_asama, :personel_id, :eksik_adet);";

            $sth = $conn->prepare($sql);
            $sth->bindParam("planlama_id", $planlama_id);
            $sth->bindParam("mevcut_asama", $mevcut_asama);
            $sth->bindParam("personel_id", $_SESSION['personel_id']);
            $sth->bindParam("eksik_adet", $eksik_uretilen_adet);
            $durum = $sth->execute();

            if($mevcut_asama + 1 == $asama_sayisi){
                $sql = "UPDATE planlama SET biten_urun_adedi = biten_urun_adedi + :biten_urun_adedi 
                    WHERE id = :id;";
                    $sth = $conn->prepare($sql);
                    $sth->bindParam('biten_urun_adedi', $uretilen_adet);
                    $sth->bindParam('id', $planlama_id);
                    $durum = $sth->execute();
            }
        }else if($mevcut_asama + 1 == $asama_sayisi){  //eksik üretilmemiş ve son adım ise
            //son adım ise planlama tablosunda biten_urun_adedi kolonuna ekle (uretilen adetleri)
            $sql = "UPDATE planlama SET biten_urun_adedi = biten_urun_adedi + :biten_urun_adedi, durum = 'bitti', 
                    tekil_kod = :tekil_kod, mevcut_asama = mevcut_asama+1   
                    WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('biten_urun_adedi', $uretilen_adet);
            $sth->bindValue('tekil_kod', uniqid());
            $sth->bindParam('id', $planlama_id);
            $durum = $sth->execute();

            //siparişler tablosunda birden fazla alt ürün olsa bile islem  'tamamlandi' olması gerekir ki depodan çıkabilsin
            $sql = "UPDATE siparisler SET islem = 'tamamlandi' WHERE id = :id";
            $sth = $conn->prepare($sql);
            $sth->bindParam('id', $siparis_id);
            $durum = $sth->execute();
        }else{ //son aşama değilse
            $sql = "UPDATE planlama SET durum = 'beklemede', tekil_kod = :tekil_kod, mevcut_asama = mevcut_asama+1   
                    WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindValue('tekil_kod', uniqid());
            $sth->bindParam('id', $planlama_id);
            $durum = $sth->execute();
        }

    } else{  //sonraki aşamada daha önceden varsa

        $eksik_uretilen_adet = $min_uretilecek_adet-$uretilen_adet;

        //eksik adet üretildi ve bir sonraki aşama için onay gereklidir
        if($eksik_uretimde_onay_isteme_durumu == 'evet' && $min_uretilecek_adet > 0 && $uretilen_adet < $min_uretilecek_adet){
            $sql = "UPDATE planlama SET asamada_eksik_adet_varmi = 'var',eksik_adet = :eksik_adet,durum = 'beklemede'
                    WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('eksik_adet', $eksik_uretilen_adet);
            $sth->bindParam('id', $planlama_id);
            $durum = $sth->execute();

            $sql = "INSERT INTO uretim_eksik_uretilen_loglar(planlama_id, mevcut_asama, personel_id, eksik_adet)
                    VALUES(:planlama_id, :mevcut_asama, :personel_id, :eksik_adet);";

            $sth = $conn->prepare($sql);
            $sth->bindParam("planlama_id", $planlama_id);
            $sth->bindParam("mevcut_asama", $mevcut_asama);
            $sth->bindParam("personel_id", $_SESSION['personel_id']);
            $sth->bindParam("eksik_adet", $eksik_uretilen_adet);
            $durum = $sth->execute();

            //Mevcut Planlama
            $sql = "UPDATE planlama SET  durum = 'beklemede' WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('id',       $mevcut_planlama['id']);
            $durum = $sth->execute();

        }else{
            //Mevcut Planlama
            $sql = "UPDATE planlama SET aktar_durum = 'eklendi', durum = 'beklemede' WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('id',       $mevcut_planlama['id']);
            $durum = $sth->execute();
        }
        

        //BİR SONRAKİ AŞAMADA VAR OLAN PLANLAMA
        $mevcut_planlama_adetler = json_decode($mevcut_planlama['adetler'], true);
        $mevcut_planlama_sureler = json_decode($mevcut_planlama['sureler'], true);

        $adet_sayisi                        = count($mevcut_planlama_adetler);
        $yeni_mevcut_planlama_adetler       = array_fill(0, $adet_sayisi,0);
        $yeni_mevcut_planlama_adetler[0]    = (int)$eksik_uretilen_adet;
        for($i = 0; $i < $adet_sayisi-1; $i++){
            $yeni_mevcut_planlama_adetler[$i+1] = ($mevcut_planlama_adetler[$i+1]*$yeni_mevcut_planlama_adetler[$i]) / $mevcut_planlama_adetler[$i];
        }

        $sql = "UPDATE planlama SET uretilecek_adet = :uretilecek_adet, adetler = :adetler, sureler = :sureler 
                WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('uretilecek_adet',  end($yeni_mevcut_planlama_adetler));
        $sth->bindValue('adetler',  json_encode($yeni_mevcut_planlama_adetler));
        $sth->bindValue('sureler',  json_encode($mevcut_planlama_sureler));
        $sth->bindParam('id',       $mevcut_planlama['id']);
        $durum = $sth->execute();



        $mevcut_planlama_aktarilacak_adetler = array_fill(0, $adet_sayisi,0);
        for($i = 0; $i < $adet_sayisi; $i++){
            $mevcut_planlama_aktarilacak_adetler[$i] = $mevcut_planlama_adetler[$i] - $yeni_mevcut_planlama_adetler[$i];
        }
        

        $sonraki_asama_olan_planlama_adetler = json_decode($sonraki_asama_olan_planlama['adetler'], true);
        $sonraki_asama_olan_planlama_sureler = json_decode($sonraki_asama_olan_planlama['sureler'], true);

        //Eski Hesaplanan adet ve sureler sonraki aşamada olan adet ve sureler toplanması
        $orijinal_adetler = array_map('array_sum', array_map(null, $sonraki_asama_olan_planlama_adetler, $mevcut_planlama_aktarilacak_adetler));
        $orijinal_sureler = array_map('array_sum', array_map(null, $mevcut_planlama_sureler, $mevcut_planlama_sureler));

        $sql = "UPDATE planlama SET uretilecek_adet = :uretilecek_adet, adetler = :adetler, sureler = :sureler 
                WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('uretilecek_adet',  end($orijinal_adetler));
        $sth->bindValue('adetler',  json_encode($orijinal_adetler));
        $sth->bindValue('sureler',  json_encode($orijinal_sureler));
        $sth->bindParam('id',       $sonraki_asama_olan_planlama['id']);
        $durum = $sth->execute();
    
    }

    //uretilen_adetler aktarma_topla 'hayır' yapılacak (TÜM DURUMLARDA)
    $sql = "UPDATE uretilen_adetler SET aktarma_topla = 'hayır' 
            WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id', $mevcut_planlama['id']);
    $sth->bindParam('mevcut_asama', $mevcut_asama);
    $durum = $sth->execute();

    header("Location: makina_is_listesi.php?makina-id={$makina_id}");
    exit;
}


//14- Yetkili Çağırma son adım
if(isset($_POST['yetkili_cagirma']))
{
    $planlama_id        = intval($_POST['planlama_id']);
    $departman_id       = intval($_POST['departman_id']);
    $makina_id          = intval($_POST['makina_id']);
    $gelen_personel_id  = intval($_POST['gelen_personel_id']);
    $mevcut_asama       = intval($_POST['mevcut_asama']);
    $tekil_kod          = intval($_POST['tekil_kod']);


    $sql = "INSERT INTO uretim_yetkili_log(firma_id, planlama_id, departman_id, makina_id,personel_id, gelen_personel_id, tekil_kod, mevcut_asama) 
        VALUES(:firma_id,:planlama_id, :departman_id, :makina_id, :personel_id, :gelen_personel_id, :tekil_kod, :mevcut_asama);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("gelen_personel_id", $gelen_personel_id);
    $sth->bindParam("tekil_kod", $tekil_kod);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $durum = $sth->execute();

    // TODO yetkili kişiye ileti gönder!!

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Yetkili Çağırma Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Yetkili Çağırma Başarısız';
    }

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;
}


//15- Arıza
if(isset($_POST['ariza-bitir']))
{
    $planlama_id                = intval($_POST['planlama_id']);
    $makina_id                  = intval($_POST['makina_id']);
    $departman_id               = intval($_POST['departman_id']);
    $mevcut_asama               = intval($_POST['mevcut_asama']);
    $mesaj                      = trim($_POST['ariza_mesaj']);
    $ariza_baslatma_tarih       = $_POST['ariza_baslatma_tarih'];


    $sql = "INSERT INTO uretim_ariza_log(firma_id,planlama_id, makina_id, departman_id, personel_id, mevcut_asama, mesaj, baslatma_tarihi) 
    VALUES(:firma_id,:planlama_id, :makina_id, :departman_id, :personel_id, :mevcut_asama, :mesaj, :baslatma_tarihi);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("mesaj", $mesaj);
    $sth->bindParam("baslatma_tarihi", $ariza_baslatma_tarih);
    $durum = $sth->execute();

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'İşlem Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'İşlem Başarısız';
    }

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;

}

//16- Bakım personel çağırmayı başlatma
if(isset($_POST['bakim_personel_cagir']))
{
    $planlama_id        = intval($_POST['planlama_id']);
    $departman_id       = intval($_POST['departman_id']);
    $makina_id          = intval($_POST['makina_id']);
    $mevcut_asama       = intval($_POST['mevcut_asama']);
    $gelen_personel_id  = intval($_POST['gelen_personel_id']);



    // TODO bakım personele ileti gönder


    $sql = "INSERT INTO uretim_bakim_log(firma_id, planlama_id, departman_id, personel_id,mevcut_asama, gelen_personel_id,  makina_id) 
        VALUES(:firma_id, :planlama_id, :departman_id, :personel_id, :mevcut_asama, :gelen_personel_id, :makina_id);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindParam("mevcut_asama", $mevcut_asama);
    $sth->bindParam("gelen_personel_id", $gelen_personel_id);
    $sth->bindParam("makina_id", $makina_id);
    $durum = $sth->execute();

    header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    exit;
}

//16- Bakım Personel Çağırma Son Adım
if(isset($_POST['bakim_bitir'])){
    //echo "<pre>"; print_r($_POST); exit;
    $makina_id              = intval($_POST['makina_id']);
    $planlama_id            = intval($_POST['planlama_id']);
    $uretim_bakim_log_id    = intval($_POST['uretim_bakim_log_id']);
    $ariza_sebebi           = trim($_POST['ariza_sebebi']);
    $bakim_sorunu_cozuldumu = isset($_POST['bakim_sorunu_cozuldumu']) ? $_POST['bakim_sorunu_cozuldumu'] : 0;

    $sql = "UPDATE uretim_bakim_log SET bitis_tarihi = :bitis_tarihi, ariza_sebebi = :ariza_sebebi, sorun_cozuldu_mu = :sorun_cozuldu_mu
            WHERE id = :id AND firma_id = :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindValue('bitis_tarihi', date('Y-m-d H:i:s'));
    $sth->bindParam('ariza_sebebi', $ariza_sebebi);
    $sth->bindValue('sorun_cozuldu_mu', $bakim_sorunu_cozuldumu ? 'evet':'hayır');
    $sth->bindParam('id', $uretim_bakim_log_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();

    if($bakim_sorunu_cozuldumu == 0){
        $sql = "UPDATE makinalar SET durumu = 'bakimda' WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $makina_id);
        $sth->execute();
    }
    if($bakim_sorunu_cozuldumu){
        header("Location: makina_is_ekran.php?planlama-id={$planlama_id}&makina-id={$makina_id}");
    }else{
        header("Location: makina_listesi.php");
    }
    exit;
}


//16- Bakım Personeli Geldi
if(isset($_POST['islem']) && $_POST['islem'] == 'bakim_personeli_geldi'){
    $uretim_bakim_log_id = intval($_POST['uretim_bakim_log_id']);

    $sql = "UPDATE uretim_bakim_log SET personel_gelme_tarihi = :personel_gelme_tarihi WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindValue('personel_gelme_tarihi', date('Y-m-d H:i:s'));
    $sth->bindParam('id', $uretim_bakim_log_id);
    $durum = $sth->execute();

    echo json_encode(['durum'=>$durum]);
}

//İşi Aktar
if(isset($_POST['aktar'])){
    echo "<pre>"; print_r($_POST); exit;
    $siparis_id                             = intval($_POST['siparis_id']);
    $planlama_id                            = intval($_POST['planlama_id']);
    $grup_kodu                              = $_POST['grup_kodu'];
    $mevcut_asama                           = intval($_POST['mevcut_asama']);
    $asama_sayisi                           = intval($_POST['asama_sayisi']);
    $departman_id                           = intval($_POST['departman_id']);
    $makina_id                              = intval($_POST['makina_id']);
    $uretim_islem_tarih_id                  = $_POST['uretim_islem_tarih_id'];
    $uretim_islem_tarih_baslatma_tarih      = $_POST['uretim_islem_tarih_baslatma_tarih'];
    $uretilen_adet                          = intval($_POST['uretilen_adet']);
    $uretirken_verilen_fire_adet            = intval($_POST['uretirken_verilen_fire_adet']);
    //$tekil_kod                              = $_POST['tekil_kod'];

    $birim_idler                            = isset($_POST['birim_id'])             ? $_POST['birim_id']            : [];
    $stok_idler                             = isset($_POST['stok_id'])              ? $_POST['stok_id']             : [];
    $stok_alt_kalem_idler                   = isset($_POST['stok_alt_kalem_id'])    ? $_POST['stok_alt_kalem_id']   : [];
    $stok_alt_depo_idler                    = isset($_POST['stok_alt_depo_id'])     ? $_POST['stok_alt_depo_id']    : [];
    $tuketim_miktarilari                    = isset($_POST['tuketim_miktari'])      ? $_POST['tuketim_miktari']     : [];
    $fire_miktarilari                       = isset($_POST['fire_miktari'])         ? $_POST['fire_miktari']        : [];


    $sql = 'SELECT * FROM planlama WHERE id = :id AND firma_id = :firma_id ';
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $mevcut_planlama = $sth->fetch(PDO::FETCH_ASSOC);

    //Mevcut Aşamada Daha Önceden Üretilmiş Varsa Ekle
    $sql = "SELECT SUM(uretilen_adet) AS uretilen_adet FROM `uretilen_adetler` 
            WHERE planlama_id = :planlama_id AND firma_id = :firma_id 
            AND mevcut_asama = :mevcut_asama AND aktarma_topla = 'evet'";
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id',$planlama_id);
    $sth->bindParam('firma_id',$_SESSION['firma_id']);
    $sth->bindParam('mevcut_asama',$mevcut_asama);
    $sth->execute();
    $daha_onceden_toplam_uretilen = $sth->fetch(PDO::FETCH_ASSOC);
    $daha_onceden_toplam_uretilen_adet = empty($daha_onceden_toplam_uretilen['uretilen_adet']) ? 0 : $daha_onceden_toplam_uretilen['uretilen_adet'];


    //Bir Sonraki Aşamaya Geçecek İçin Aynı Aşamada Daha Önce Var mı Varsa Üstüne Ekleyecez
    $sql = 'SELECT * FROM planlama 
            WHERE grup_kodu = :grup_kodu AND firma_id = :firma_id AND mevcut_asama = :mevcut_asama';
    $sth = $conn->prepare($sql);
    $sth->bindParam('grup_kodu', $mevcut_planlama['grup_kodu']);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindValue('mevcut_asama', $mevcut_asama + 1);
    $sth->execute();
    $ayni_asama_olan_planlama = $sth->fetch(PDO::FETCH_ASSOC);

    $adetler = json_decode($mevcut_planlama['adetler'], true);
    $sureler = json_decode($mevcut_planlama['sureler'], true);

    $orijinal_adetler = $yeni_adetler   = array_fill(0, count($adetler),0);
    $orijinal_sureler = $yeni_sureler   = array_fill(0, count($sureler),0);

    $orijinal_adetler[$mevcut_asama] = $uretilen_adet + $daha_onceden_toplam_uretilen_adet;
    $yeni_adetler[$mevcut_asama]     = $adetler[$mevcut_asama]-$orijinal_adetler[$mevcut_asama] ;

    $aktarilan_adet = ($adetler[$mevcut_asama+1]*$orijinal_adetler[$mevcut_asama])/$adetler[$mevcut_asama];


    for($i = $mevcut_asama-1; $i >= 0; $i--){
        $orijinal_adetler[$i]       = floor(($adetler[$i]*$orijinal_adetler[$i+1])/$adetler[$i+1]);
        $yeni_adetler[$i]           = $adetler[$i]-$orijinal_adetler[$i];
    }

    for($i = $mevcut_asama+1 ; $i < $asama_sayisi; $i++){
        $orijinal_adetler[$i]   = floor(($adetler[$i]/$adetler[$i-1])*$orijinal_adetler[$i-1]);
        $yeni_adetler[$i]       = $adetler[$i]-$orijinal_adetler[$i];
    }


    for($i = 0; $i < $asama_sayisi; $i++){
        $orijinal_sureler[$i]   = round(($sureler[$i]*$orijinal_adetler[$i])/$adetler[$i],3);
        $yeni_sureler[$i]       = round($sureler[$i]-$orijinal_sureler[$i],3);
    }

    if(!empty($ayni_asama_olan_planlama)){
        $ayni_asama_olan_planlama_adetler = json_decode($ayni_asama_olan_planlama['adetler'], true);
        $ayni_asama_olan_planlama_sureler = json_decode($ayni_asama_olan_planlama['sureler'], true);

        //Eski Hesaplanan adet ve sureler sonraki aşamada olan adet ve sureler toplanması
        $orijinal_adetler = array_map('array_sum', array_map(null, $ayni_asama_olan_planlama_adetler, $orijinal_adetler));
        $orijinal_sureler = array_map('array_sum', array_map(null, $ayni_asama_olan_planlama_sureler, $orijinal_sureler));

    }

    

    /*
    echo "<pre>"; print_r($_POST);
    echo "aktarilan_adet".$aktarilan_adet."<br>";
    echo "gerçek<br>"; print_r($mevcut_planlama['orijinal_adetler']);
    echo "orijinal_adetler<br>"; print_r($orijinal_adetler);
    echo "yeni_adetler<br>"; print_r($yeni_adetler);

    echo "orijinal_sureler<br>";print_r($orijinal_sureler);
    echo "yeni_sureler<br>";print_r($yeni_sureler);
    echo "ayni_asama_olan_planlama<br>"; print_r($ayni_asama_olan_planlama);
    echo "mevcut_planlama<br>";print_r($mevcut_planlama);exit;
    */
    

    //HER DURUMDA
    //Aktarıldı Üretilenler
    $sql = "INSERT INTO uretim_aktarma_loglar(planlama_id, grup_kodu, aktarilan_asama, aktarilan_adet) 
            VALUES(:planlama_id, :grup_kodu, :aktarilan_asama, :aktarilan_adet)";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id",      $mevcut_planlama['id']);
    $sth->bindParam("grup_kodu",        $mevcut_planlama['grup_kodu']);
    $sth->bindValue("aktarilan_asama",  $mevcut_asama + 1);
    $sth->bindParam("aktarilan_adet",   $aktarilan_adet);
    $sth->execute();
    
    
    //HER DURUMDA
    //stok kullanıları ekle(MEVCUT PLANLAMANIN)
    foreach($stok_idler as $index => $stok_id){
        $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, planlama_id,
                personel_id, makina_id, mevcut_asama, fire_miktari, tuketim_miktari, birim_id, departman_id) 
                VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, :planlama_id,
                :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, :birim_id, :departman_id);";

        $sth = $conn->prepare($sql);
        $sth->bindParam("stok_id",              $stok_id);
        $sth->bindParam("stok_alt_kalem_id",    $stok_alt_kalem_idler[$index]);
        $sth->bindParam("stok_alt_depo_id",     $stok_alt_depo_idler[$index]);
        $sth->bindParam("planlama_id",          $planlama_id);
        $sth->bindParam("personel_id",          $_SESSION['personel_id']);
        $sth->bindParam("makina_id",            $makina_id);
        $sth->bindParam("mevcut_asama",         $mevcut_asama);
        $sth->bindParam("fire_miktari",         $fire_miktarilari[$index]);
        $sth->bindParam("tuketim_miktari",      $tuketim_miktarilari[$index]);
        $sth->bindParam("birim_id",             $birim_idler[$index]);
        $sth->bindParam("departman_id",         $departman_id);
        $durum = $sth->execute();

        //Stok Alt Depo Kullanılan Adedi Güncelle
        $sql = "UPDATE stok_alt_depolar SET kullanilan_adet = kullanilan_adet + :kullanilan_adet  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_adet', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('id', $stok_alt_depo_idler[$index]);
        $durum = $sth->execute();

        //Üretim Reservasyondan Düşme
        $sql = "UPDATE uretim_reservasyon 
                SET kullanilan_miktar = kullanilan_miktar + :kullanilan_miktar  
                WHERE grup_kodu = :grup_kodu AND mevcut_asama = :mevcut_asama AND stok_alt_depo_id = :stok_alt_depo_id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('kullanilan_miktar', $tuketim_miktarilari[$index] + $fire_miktarilari[$index]);
        $sth->bindParam('grup_kodu', $grup_kodu);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->bindParam('stok_alt_depo_id', $stok_alt_depo_idler[$index]);
        $sth->execute();
    }


    //Eskide Aynı Aşamada Yoksa
    if(empty($ayni_asama_olan_planlama)) 
    {
        //MEVCUT AŞAMADA KALAN
        //Yeni Planlama(Kalan Kısım İçin)
        $sql = "INSERT INTO planlama(firma_id, siparis_id, alt_urun_id, grup_kodu, sira, isim, asama_sayisi, mevcut_asama, uretilecek_adet, 
        departmanlar,orijinal_adetler, adetler,
        sureler,detaylar,makinalar,arsiv_altlar,stok_kalemler, stok_alt_kalemler,stok_alt_depo_adetler,stok_alt_depolar,
        fason_durumlar, fason_tedarikciler,durum, onay_durum, planlama_durum,tekil_kod)
        VALUES(:firma_id, :siparis_id, :alt_urun_id, :grup_kodu, :sira, :isim, :asama_sayisi,:mevcut_asama, :uretilecek_adet, 
        :departmanlar,:orijinal_adetler, :adetler,
        :sureler, :detaylar, :makinalar,:arsiv_altlar,:stok_kalemler, :stok_alt_kalemler, :stok_alt_depo_adetler, :stok_alt_depolar,
        :fason_durumlar, :fason_tedarikciler, :durum, :onay_durum,:planlama_durum, :tekil_kod)";

        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id",             $_SESSION['firma_id']);
        $sth->bindParam("siparis_id",           $mevcut_planlama['siparis_id']);
        $sth->bindParam("alt_urun_id",          $mevcut_planlama['alt_urun_id']);  
        $sth->bindParam("grup_kodu",            $mevcut_planlama['grup_kodu']); 
        $sth->bindParam("sira",                 $mevcut_planlama['sira']); 
        $sth->bindParam("isim",                 $mevcut_planlama['isim']); 
        $sth->bindParam("asama_sayisi",         $mevcut_planlama['asama_sayisi']); 
        $sth->bindParam("mevcut_asama",         $mevcut_planlama['mevcut_asama']); 
        $sth->bindValue("uretilecek_adet",      end($yeni_adetler)); 
        $sth->bindParam("departmanlar",         $mevcut_planlama['departmanlar']); 
        $sth->bindParam('orijinal_adetler',     $mevcut_planlama['orijinal_adetler']);
        $sth->bindValue('adetler',              json_encode($yeni_adetler));
        $sth->bindValue('sureler',              json_encode($yeni_sureler));
        $sth->bindParam('detaylar',             $mevcut_planlama['detaylar']);
        $sth->bindParam('makinalar',            $mevcut_planlama['makinalar']);
        $sth->bindParam('arsiv_altlar',         $mevcut_planlama['arsiv_altlar']);
        $sth->bindParam('stok_kalemler',        $mevcut_planlama['stok_kalemler']);
        $sth->bindParam('stok_alt_kalemler',    $mevcut_planlama['stok_alt_kalemler']);
        $sth->bindParam('stok_alt_depo_adetler',$mevcut_planlama['stok_alt_depo_adetler']);
        $sth->bindParam('stok_alt_depolar',     $mevcut_planlama['stok_alt_depolar']);
        $sth->bindParam('fason_durumlar',       $mevcut_planlama['fason_durumlar']);
        $sth->bindParam('fason_tedarikciler',   $mevcut_planlama['fason_tedarikciler']);
        $sth->bindValue('durum',                'basladi');
        $sth->bindParam('onay_durum',           $mevcut_planlama['onay_durum']);
        $sth->bindParam('planlama_durum',       $mevcut_planlama['planlama_durum']);
        $sth->bindValue('tekil_kod',            uniqid());
        $sth->execute();
        $son_eklenen_planlama_id = $conn->lastInsertId();

        //EKSİ PLANLAMA(SONRAKİ AŞAMA GEÇEN)
        //Mevcut Planlama Üretim İşlem Tarihini Bitir(ESKİ PLANMA => Aktarılan)
        $sql = "UPDATE uretim_islem_tarihler SET bitirme_tarihi = :bitirme_tarihi WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('bitirme_tarihi', date('Y-m-d H:i:s'));
        $sth->bindParam('id', $planlama_id);
        $sth->execute();


        //YENİ PLANLAMA(MEVCUT AŞAMADA KALAN)
        //Yeni Oluşuna (Kalan Kısma) Uretim İşlem Tarihi Ekle(Çünkü Yeni Planlama Id'ye Sahip)
        $sql = "INSERT INTO uretim_islem_tarihler(planlama_id, departman_id, makina_id, personel_id, mevcut_asama) 
            VALUES(:planlama_id, :departman_id, :makina_id, :personel_id, :mevcut_asama)";
        $sth = $conn->prepare($sql);
        $sth->bindParam('planlama_id', $son_eklenen_planlama_id);
        $sth->bindParam('departman_id', $departman_id);
        $sth->bindParam('makina_id', $makina_id);
        $sth->bindParam('personel_id', $_SESSION['personel_id']);
        $sth->bindParam('mevcut_asama', $mevcut_asama);
        $sth->execute();

        

        //EKSİ PLANLAMA(SONRAKİ AŞAMA GEÇEN)
        //Verileri Mevcut Aşamada Planlamaya Aktarıldı
        $sql = "UPDATE planlama SET uretilecek_adet = :uretilecek_adet, adetler = :adetler, 
                sureler = :sureler, mevcut_asama  = mevcut_asama + 1, durum = 'beklemede' 
                WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('uretilecek_adet',  end($orijinal_adetler));
        $sth->bindValue('adetler',  json_encode($orijinal_adetler));
        $sth->bindValue('sureler',  json_encode($yeni_sureler));
        $sth->bindParam('id',  $mevcut_planlama['id']);
        $durum = $sth->execute();

        //uretilen_adetler tablosuna ekle(MEVCUT PLANLAMANIN) HER DURUMDA
        $sql = 'INSERT INTO uretilen_adetler(firma_id,planlama_id, grup_kodu, uretilen_adet, uretirken_verilen_fire_adet, mevcut_asama, 
        asama_sayisi, personel_id, departman_id, makina_id, aktarma_topla, baslangic_tarihi) 
        VALUES(:firma_id,:planlama_id,:grup_kodu, :uretilen_adet, :uretirken_verilen_fire_adet, :mevcut_asama, 
        :asama_sayisi, :personel_id, :departman_id, :makina_id, :aktarma_topla, :baslangic_tarihi);';
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->bindParam("planlama_id", $son_eklenen_planlama_id);
        $sth->bindParam("grup_kodu", $grup_kodu);
        $sth->bindParam("uretilen_adet", $uretilen_adet);
        $sth->bindParam("uretirken_verilen_fire_adet", $uretirken_verilen_fire_adet);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("asama_sayisi", $asama_sayisi);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("departman_id", $departman_id);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindValue("aktarma_topla", 'hayır');
        $sth->bindParam("baslangic_tarihi", $uretim_islem_tarih_baslatma_tarih);
        $durum = $sth->execute();

        header("Location: makina_is_ekran.php?planlama-id={$son_eklenen_planlama_id}&makina-id={$makina_id}");

    }
    else //Aynı Aşamada Daha Önce Varsa
    {
        //Mevcut Aşamadaki Planı Güncelle(MEVCUT AŞAMADA KALAN)
        $sql = "UPDATE planlama SET uretilecek_adet = :uretilecek_adet, adetler = :adetler, sureler = :sureler 
                WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('uretilecek_adet',  end($yeni_adetler));
        $sth->bindValue('adetler',  json_encode($yeni_adetler));
        $sth->bindValue('sureler',  json_encode($yeni_sureler));
        $sth->bindParam('id',       $mevcut_planlama['id']);
        $durum = $sth->execute();

        //Sonraki Aşamaya Geçen Güncelle(SONRAKİ AŞAMAYA GEÇEN)
        $sql = "UPDATE planlama SET uretilecek_adet = :uretilecek_adet, adetler = :adetler, sureler = :sureler 
                WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('uretilecek_adet',  end($orijinal_adetler));
        $sth->bindValue('adetler',  json_encode($orijinal_adetler));
        $sth->bindValue('sureler',  json_encode($orijinal_sureler));
        $sth->bindParam('id',       $ayni_asama_olan_planlama['id']);
        $durum = $sth->execute();

        //uretilen_adetler tablosuna ekle(MEVCUT PLANLAMANIN) HER DURUMDA
        $sql = 'INSERT INTO uretilen_adetler(firma_id, planlama_id, grup_kodu, uretilen_adet, uretirken_verilen_fire_adet, mevcut_asama, 
        asama_sayisi, personel_id, departman_id, makina_id, aktarma_topla, baslangic_tarihi) 
        VALUES(:firma_id,:planlama_id, :grup_kodu, :uretilen_adet, :uretirken_verilen_fire_adet, :mevcut_asama, 
        :asama_sayisi, :personel_id, :departman_id, :makina_id, :aktarma_topla, :baslangic_tarihi);';
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->bindParam("planlama_id", $mevcut_planlama['id']);
        $sth->bindParam("grup_kodu", $grup_kodu);
        $sth->bindParam("uretilen_adet", $uretilen_adet);
        $sth->bindParam("uretirken_verilen_fire_adet", $uretirken_verilen_fire_adet);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("asama_sayisi", $asama_sayisi);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("departman_id", $departman_id);
        $sth->bindParam("makina_id", $makina_id);
        $sth->bindValue("aktarma_topla", 'hayır');
        $sth->bindParam("baslangic_tarihi", $uretim_islem_tarih_baslatma_tarih);
        $durum = $sth->execute();

        
        header("Location: makina_is_ekran.php?planlama-id={$mevcut_planlama['id']}&makina-id={$makina_id}");
    }

    //uretilen_adetler aktarma_topla 'hayır' yapılacak (TÜM DURUMLARDA)
    $sql = "UPDATE uretilen_adetler SET aktarma_topla = 'hayır' 
            WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
    $sth = $conn->prepare($sql);
    $sth->bindParam('planlama_id', $mevcut_planlama['id']);
    $sth->bindParam('mevcut_asama', $mevcut_asama);
    $durum = $sth->execute();
    exit;

}


//Ürün Resim Ekle
if(isset($_GET['islem']) && $_GET['islem'] == 'urun-resim-ekle'){
    if(!isset($_FILES['file'])){
        echo json_encode(['durum'=>false]); exit;
    }
    $dosya          = $_FILES['file'];
    $planlama_id    = $_POST['planlama_id'];
    $mevcut_asama   = $_POST['mevcut_asama'];

    $hedef_klasor   = "dosyalar/uretim-dosyalar/";
    $dosya_adi      = pathinfo($dosya['name'], PATHINFO_FILENAME)."_".random_int(1000, 99999);
    $dosya_uzanti   = pathinfo($dosya['name'], PATHINFO_EXTENSION);
    $dosya_adi      = "{$dosya_adi}.{$dosya_uzanti}";

    if (move_uploaded_file($dosya["tmp_name"], $hedef_klasor.$dosya_adi)) {
        $sql = "INSERT INTO uretim_dosyalar(planlama_id, mevcut_asama, dosya_adi) 
        VALUES(:planlama_id, :mevcut_asama, :dosya_adi);";
        $sth = $conn->prepare($sql);
        $sth->bindParam("planlama_id", $planlama_id);
        $sth->bindParam("mevcut_asama", $mevcut_asama);
        $sth->bindParam("dosya_adi", $dosya_adi);
        $durum = $sth->execute();
        echo json_encode(['durum'=>true]);
    }else{
        echo json_encode(['durum'=>false]);
    }
    exit;
}

//Sipariş Detay
if(isset($_GET['islem']) && $_GET['islem'] == 'siparis-detay-ajax'){
    $planlama_id = intval($_POST['planlama_id']);

    $sql = "SELECT `planlama`.`alt_urun_id`,`planlama`.`uretilecek_adet`,planlama.stok_alt_kalemler,
            `siparisler`.`isin_adi`,`siparisler`.`id` AS siparis_id,`siparisler`.`veriler`,`siparisler`.`tip_id`,
            DATE_FORMAT(`siparisler`.`termin`, '%d-%m-%Y') AS termin,`siparisler`.`paketleme`,siparisler.aciklama
            FROM `planlama` 
            JOIN siparisler ON siparisler.id = planlama.siparis_id
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


    $stok_alt_kalemler_hepsi = json_decode($planlama['stok_alt_kalemler'], true);
    $stok_alt_kalemler_sonuc_idler = [];
    foreach ($stok_alt_kalemler_hepsi as $stok_alt_kalemler) {
        $stok_alt_kalemler = array_filter($stok_alt_kalemler);
        foreach ($stok_alt_kalemler as $stok_alt_kalem) {
            $stok_alt_kalemler_sonuc_idler[] = $stok_alt_kalem;
        }
    }
    $stok_alt_kalemler_sonuc_idler_birlestir = implode(',',$stok_alt_kalemler_sonuc_idler);
    
    $sql = "SELECT stok_alt_kalemler.veri, birimler.ad,stok_kalemleri.stok_kalem FROM `stok_alt_kalemler` 
    JOIN stok_kalemleri ON stok_kalemleri.id = `stok_alt_kalemler`.`stok_id`
    LEFT JOIN birimler ON birimler.id = stok_alt_kalemler.birim_id  
    WHERE  stok_alt_kalemler.firma_id = :firma_id ";
    if(!empty($stok_alt_kalemler_sonuc_idler_birlestir)){
        $sql .= " AND stok_alt_kalemler.id IN({$stok_alt_kalemler_sonuc_idler_birlestir });";
    }
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $stok_veriler = $sth->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['planlama'=>$planlama, 'siparis_dosyalar'=>$siparis_dosyalar, 'stok_veriler'=>$stok_veriler]); exit;
}


//Tüm Mesajlar Göründü Yap
if(isset($_GET['islem']) && $_GET['islem'] == 'mesajlari-gordu'){
    $grup_kodu = $_GET['grup_kodu'];

    $sql = 'SELECT id FROM uretim_mesaj_log WHERE grup_kodu = :grup_kodu ORDER BY id DESC';
    $sth = $conn->prepare($sql);
    $sth->bindParam("grup_kodu", $grup_kodu);
    $sth->execute();
    $uretim_mesaj_log = $sth->fetch(PDO::FETCH_ASSOC);

    if(!empty($uretim_mesaj_log)){
        $sql = "INSERT INTO uretim_mesaj_log_gorunum_durumu(uretim_mesaj_log_id, grup_kodu, personel_id) 
            VALUES(:uretim_mesaj_log_id, :grup_kodu, :personel_id)";
        $sth = $conn->prepare($sql);
        $sth->bindParam("uretim_mesaj_log_id", $uretim_mesaj_log['id']);
        $sth->bindParam("grup_kodu", $grup_kodu);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->execute();
    }
    echo true;
    exit;
}