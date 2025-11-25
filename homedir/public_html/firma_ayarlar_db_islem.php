<?php 
include "include/db.php";
include "include/oturum_kontrol.php";
require_once "include/helper.php";

//echo "<pre>"; print_r($_POST);
//echo "<pre>"; print_r($_FILES); exit;
//Ayarları Kaydet
if(isset($_POST['ayar_kaydet'])){

    $storage = new DreamHostStorage($conn);

    $siparis_no_baslangic_kodu          = trim($_POST['siparis_no_baslangic_kodu']);
    $static_ip_varmi                    = $_POST['static_ip_varmi'];
    $makina_ekran_ipler                 = trim($_POST['makina_ekran_ipler']);
    $eksik_uretimde_onay_isteme_durumu  = $_POST['eksik_uretimde_onay_isteme_durumu'];
    $arsiv_getirme                      = $_POST['arsiv_getirme'];
    $stoga_geri_gonderme_durumu         = isset($_POST['stoga_geri_gonderme_durumu']) ? 'evet' : 'hayır';
    
    // Dosya depolama ayarları
    $dosya_depolama_tipi                = $_POST['dosya_depolama_tipi'] ?? 'local';
    $s3_endpoint                        = trim($_POST['s3_endpoint'] ?? '');
    $s3_region                          = trim($_POST['s3_region'] ?? '');
    $s3_access_key                      = trim($_POST['s3_access_key'] ?? '');
    $s3_secret_key                      = trim($_POST['s3_secret_key'] ?? '');
    $s3_bucket                          = trim($_POST['s3_bucket'] ?? '');

    $sql = "UPDATE firmalar SET siparis_no_baslangic_kodu = :siparis_no_baslangic_kodu, 
            makina_ekran_ipler = :makina_ekran_ipler, 
            eksik_uretimde_onay_isteme_durumu = :eksik_uretimde_onay_isteme_durumu, 
            static_ip_varmi = :static_ip_varmi, arsiv_getirme = :arsiv_getirme,
            stoga_geri_gonderme_durumu = :stoga_geri_gonderme_durumu,
            dosya_depolama_tipi = :dosya_depolama_tipi,
            s3_endpoint = :s3_endpoint,
            s3_region = :s3_region,
            s3_access_key = :s3_access_key,
            s3_secret_key = :s3_secret_key,
            s3_bucket = :s3_bucket
            WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("siparis_no_baslangic_kodu", $siparis_no_baslangic_kodu);
    $sth->bindParam("makina_ekran_ipler", $makina_ekran_ipler);
    $sth->bindParam("eksik_uretimde_onay_isteme_durumu", $eksik_uretimde_onay_isteme_durumu);
    $sth->bindParam("static_ip_varmi", $static_ip_varmi);
    $sth->bindParam("arsiv_getirme", $arsiv_getirme);
    $sth->bindParam("stoga_geri_gonderme_durumu", $stoga_geri_gonderme_durumu);
    $sth->bindParam("dosya_depolama_tipi", $dosya_depolama_tipi);
    $sth->bindParam("s3_endpoint", $s3_endpoint);
    $sth->bindParam("s3_region", $s3_region);
    $sth->bindParam("s3_access_key", $s3_access_key);
    $sth->bindParam("s3_secret_key", $s3_secret_key);
    $sth->bindParam("s3_bucket", $s3_bucket);
    $sth->bindParam("id", $_SESSION['firma_id']);
    $durum = $sth->execute();

    if(isset($_FILES['logo']) && !empty($_FILES['logo']['name']))
    {
        //$hedef_klasor   = "dosyalar/logo/";
        $dosya_adi      = pathinfo($_FILES['logo']["name"], PATHINFO_FILENAME)."-".random_int(1000, 99999);
        $dosya_uzanti   = pathinfo($_FILES['logo']["name"], PATHINFO_EXTENSION);
        $dosya_adi      = preg_replace("/\s+/","-", $dosya_adi);
        $logo = "{$dosya_adi}.{$dosya_uzanti}";

        $result = $storage->uploadFileToS3('logo', $_FILES['logo']["tmp_name"], $logo);

        //move_uploaded_file($_FILES["logo"]["tmp_name"], $hedef_klasor.$logo);

        if($result){
            $sql = "UPDATE firmalar SET logo = :logo  WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('logo', $logo);
            $sth->bindParam('id', $_SESSION['firma_id']);
            $durum = $sth->execute();
            $_SESSION['logo'] = $logo;
        } 
    }

    if(isset($_FILES['etiketLogo']) && !empty($_FILES['etiketLogo']['name']))
    {
       //$hedef_klasor   = "dosyalar/logo/";
        $dosya_adi      = pathinfo($_FILES['etiketLogo']["name"], PATHINFO_FILENAME)."-".random_int(1000, 99999);
        $dosya_uzanti   = pathinfo($_FILES['etiketLogo']["name"], PATHINFO_EXTENSION);
        $dosya_adi      = preg_replace("/\s+/","-", $dosya_adi);
        $logo = "{$dosya_adi}.{$dosya_uzanti}";

        //move_uploaded_file($_FILES["etiketLogo"]["tmp_name"], $hedef_klasor.$logo);

        $result = $storage->uploadFileToS3('logo', $_FILES["etiketLogo"]["tmp_name"], $logo);

        if($result){
            $sql = "UPDATE firmalar SET etiket_logo = :etiket_logo  WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('etiket_logo', $logo);
            $sth->bindParam('id', $_SESSION['firma_id']);
            $durum = $sth->execute();
            $_SESSION['logo'] = $logo;
        } 
    }

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

    header("Location: /index.php?url=firma_ayarlar");
    exit;
}