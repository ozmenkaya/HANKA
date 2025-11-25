<?php

require_once "include/oturum_kontrol.php";
require_once "include/helper.php";
require_once 'vendor/autoload.php';

use Mpdf\Mpdf;

// Firma ayarlarını çek
try {
    $sql_firma = "SELECT * FROM `firmalar` WHERE id = :firma_id";
    $sth_firma = $conn->prepare($sql_firma);
    $sth_firma->bindParam("firma_id", $_SESSION['firma_id']);
    $sth_firma->execute();
    $firma_ayarlar = $sth_firma->fetch(PDO::FETCH_ASSOC);
    
    if(empty($firma_ayarlar)){
        $firma_ayarlar = ['dosya_depolama_tipi' => 'local'];
    }
} catch(Exception $e) {
    $firma_ayarlar = ['dosya_depolama_tipi' => 'local'];
}

$storage = new DreamHostStorage($conn);

function array2DStringToInt($arr){
    $sonuc = [];
    foreach ($arr as $deger) {
        $sonuc[] = array_map('intval', $deger);
    }
    return $sonuc;
}

#planlama ekleme veya kaydet
if(isset($_POST['planlama_ekle_kaydet']))
{
    //echo "<pre>"; print_r($_POST); exit;

    $alt_urun_sayisi    = intval($_POST['alt_urun_sayisi']);
    $islem              = $_POST['planlama_ekle_kaydet'];
    $siparis_id         = intval($_POST['siparis_id']);

    for($i = 1; $i <= $alt_urun_sayisi; $i++){

        $sql = "SELECT veriler,tip_id FROM `siparisler` WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("id",$siparis_id);
        $sth->execute();
        $siparis = $sth->fetch(PDO::FETCH_ASSOC);

        $asama_sayisi           =   isset($_POST["alt_urun_{$i}"]['departman']) ? count($_POST["alt_urun_{$i}"]['departman']) : 0;

        $departmanlar           =   isset($_POST["alt_urun_{$i}"]['departman']) ?
                                    array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['departman'])) : [];

        $adetler                =   isset($_POST["alt_urun_{$i}"]['adet']) ?
                                    array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['adet'])) : [];

        $sureler                =   isset($_POST["alt_urun_{$i}"]['sure']) ?
                                    array_reverse(array_map('floatval',$_POST["alt_urun_{$i}"]['sure'])) : [];

        $detaylar               =   isset($_POST["alt_urun_{$i}"]['detay']) ? array_reverse($_POST["alt_urun_{$i}"]['detay']) : [];

        $makinalar              =   isset($_POST["alt_urun_{$i}"]['makina']) ?
                                    array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['makina'])) : [];

        $alt_arsiv              =   isset($_POST["alt_urun_{$i}"]['alt_arsiv']) ? $_POST["alt_urun_{$i}"]['alt_arsiv'] :[];
        $alt_arsiv              =   array2DStringToInt(array_reverse($alt_arsiv));


        $stok_kalemler          = isset($_POST["alt_urun_{$i}"]['stok_kalem']) ? $_POST["alt_urun_{$i}"]['stok_kalem'] : [];
        $stok_kalemler          = array2DStringToInt(array_reverse($stok_kalemler));

        $stok_alt_kalemler      = isset($_POST["alt_urun_{$i}"]['stok_alt_kalem']) ? $_POST["alt_urun_{$i}"]['stok_alt_kalem'] : [];
        $stok_alt_kalemler      = array2DStringToInt(array_reverse($stok_alt_kalemler));

        $stok_alt_depo_adetler  = isset($_POST["alt_urun_{$i}"]['stok_alt_depo_adet']) ? $_POST["alt_urun_{$i}"]['stok_alt_depo_adet'] : [];
        $stok_alt_depo_adetler  = array2DStringToInt(array_reverse($stok_alt_depo_adetler));

        $stok_alt_depolar       = isset($_POST["alt_urun_{$i}"]['stok_alt_depo']) ? $_POST["alt_urun_{$i}"]['stok_alt_depo'] : [];
        $stok_alt_depolar       = array2DStringToInt(array_reverse($stok_alt_depolar));

        $fason_durumlar         = isset($_POST["alt_urun_{$i}"]['fason_durum']) ?
                                array_reverse(array_map('intval', $_POST["alt_urun_{$i}"]['fason_durum'])):[];

        $fason_tedarikciler     = isset($_POST["alt_urun_{$i}"]['fason_tedarikci']) ?
                                array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['fason_tedarikci'])) : [];

        $uretilecek_adet        = preg_replace("/[^0-9]/",'',$_POST["alt_urun_{$i}"]['uretilecek_adet']);
        $isim                   = trim($_POST["alt_urun_{$i}"]['isim']);

        $grup_kodu              = uniqid();
        $eksik_stok_varmi       = false;
        //Rezervasyonlara ekle
        foreach ($stok_alt_depolar as $asama_index => $stok_alt_depolar_idler) {
            foreach ($stok_alt_depolar_idler as $stok_alt_depo_id_index => $stok_alt_depo_id) {
                //0 seçilmemiş
                //-1 eksik stok
                if(!in_array($stok_alt_depo_id, [-1, 0])){
                    $sql = "INSERT INTO uretim_reservasyon(firma_id, grup_kodu, mevcut_asama, stok_alt_depo_id, miktar)
                            VALUES(:firma_id, :grup_kodu, :mevcut_asama, :stok_alt_depo_id, :miktar);";
                    $sth = $conn->prepare($sql);
                    $sth->bindParam("firma_id", $_SESSION['firma_id']);
                    $sth->bindParam("grup_kodu", $grup_kodu);
                    $sth->bindParam("mevcut_asama", $asama_index);
                    $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_id);
                    $sth->bindParam("miktar", $stok_alt_depo_adetler[$asama_index][$stok_alt_depo_id_index]);
                    $sth->execute();

                }else if($stok_alt_depo_id == -1) {
                    $eksik_stok_varmi = true;
                }
            }

        }

        $sql = "INSERT INTO planlama(firma_id, siparis_id, alt_urun_id, grup_kodu, sira, isim, asama_sayisi, uretilecek_adet, departmanlar,
                adetler,orijinal_adetler,
                sureler,detaylar,makinalar,arsiv_altlar,stok_kalemler, stok_alt_kalemler,stok_alt_depo_adetler,stok_alt_depolar,
                fason_durumlar, fason_tedarikciler, planlama_durum,tekil_kod, eksik_stok_varmi)
                VALUES(:firma_id, :siparis_id, :alt_urun_id, :grup_kodu, :sira, :isim, :asama_sayisi, :uretilecek_adet, :departmanlar,
                :adetler,:orijinal_adetler,
                :sureler, :detaylar, :makinalar,:arsiv_altlar,:stok_kalemler, :stok_alt_kalemler, :stok_alt_depo_adetler, :stok_alt_depolar,
                :fason_durumlar, :fason_tedarikciler, :planlama_durum, :tekil_kod, :eksik_stok_varmi)";

        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id",             $_SESSION['firma_id']);
        $sth->bindParam("siparis_id",           $siparis_id);
        $sth->bindParam("alt_urun_id",          $i);
        $sth->bindParam("grup_kodu",            $grup_kodu);
        $sth->bindValue("sira",                 99);
        $sth->bindParam("isim",                 $isim);
        $sth->bindParam("asama_sayisi",         $asama_sayisi);
        $sth->bindParam("uretilecek_adet",      $uretilecek_adet);
        $sth->bindValue("departmanlar",         json_encode($departmanlar));
        $sth->bindValue("orijinal_adetler",     json_encode($adetler));
        $sth->bindValue("adetler",              json_encode($adetler));
        $sth->bindValue("sureler",              json_encode($sureler));
        $sth->bindValue("detaylar",             json_encode($detaylar,JSON_UNESCAPED_UNICODE));
        $sth->bindValue("makinalar",            json_encode($makinalar));
        $sth->bindValue("arsiv_altlar",         json_encode($alt_arsiv));
        $sth->bindValue("stok_kalemler",        json_encode($stok_kalemler));
        $sth->bindValue("stok_alt_kalemler",    json_encode($stok_alt_kalemler));
        $sth->bindValue("stok_alt_depo_adetler",json_encode($stok_alt_depo_adetler));
        $sth->bindValue("stok_alt_depolar",     json_encode($stok_alt_depolar));
        $sth->bindValue("fason_durumlar",       json_encode($fason_durumlar));
        $sth->bindValue("fason_tedarikciler",   json_encode($fason_tedarikciler));
        $sth->bindValue("planlama_durum",       $islem == 'ekle' ?  'evet' : 'yarım_kalmıs');
        $sth->bindValue("tekil_kod",            uniqid());
        $sth->bindValue("eksik_stok_varmi",     $eksik_stok_varmi ? 'var':'yok');
        $durum = $sth->execute();

        $veriler = json_decode($siparis['veriler'], true);
        if($siparis['tip_id'] == TEK_URUN){
            $veriler['miktar']  = $uretilecek_adet;
            $veriler['isim']    = $isim;
        }else if(in_array($siparis['tip_id'], [GRUP_URUN_TEK_FIYAT,GRUP_URUN_AYRI_FIYAT])){
            $veriler[$i-1]['miktar']  = $uretilecek_adet;
            $veriler[$i-1]['isim']    = $isim;
        }

        $sql = "UPDATE siparisler SET veriler = :veriler WHERE id = :siparis_id";
        $sth = $conn->prepare($sql);
        $sth->bindValue("veriler",    json_encode($veriler));
        $sth->bindParam("siparis_id", $siparis_id);
        $sth->execute();


        //gelen makinalar kullanıldı
        $makinalar_birlestir = implode(',', $makinalar);
        if(!empty($makinalar_birlestir)){
            $sql = "UPDATE `makinalar` SET `kullanildi_mi` = 'evet' WHERE id IN({$makinalar_birlestir});";
            $sth = $conn->prepare($sql);
            $sth->execute();
        }
    }

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
        header("Location: /index.php?url=planlama");
    }else
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
        header("Location: /index.php?url=planla_siparis&siparis_id={$siparis_id}");
    }
    exit;
}

//planlamayı onayla
if(isset($_POST['planlama_onay_guncelle'])){
    //echo "<pre>"; print_r($_POST);
    $alt_urun_sayisi    = $_POST['alt_urun_sayisi'];
    $islem              = $_POST['planlama_onay_guncelle'];
    $siparis_id         = $_POST['siparis_id'];

    $sql = "SELECT veriler,tip_id FROM `siparisler` WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("id",$siparis_id);
    $sth->execute();
    $siparis = $sth->fetch(PDO::FETCH_ASSOC);

    for($i = 1; $i <= $alt_urun_sayisi; $i++){
        $onay_durum  = $planlama_durum = 'evet';

        if($islem == 'yarım_kalmıs'){
            $onay_durum     = 'hayır';
            $planlama_durum = 'yarım_kalmıs';
        }else if($islem == 'guncelle'){
            $onay_durum     = 'hayır';
            $planlama_durum = 'evet';
        }

        if($_POST["alt_urun_{$i}"]['planlama_id'] != 0){ //güncelle

            $grup_kodu              = $_POST["alt_urun_{$i}"]['grup_kodu'];

            $departmanlar           = isset($_POST["alt_urun_{$i}"]['departman']) ?
                                    array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['departman'])) : [];

            $adetler                = isset($_POST["alt_urun_{$i}"]['adet']) ?
                                    array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['adet'])) : [];

            $asama_sayisi           = isset($_POST["alt_urun_{$i}"]['departman']) ?
                                    count($_POST["alt_urun_{$i}"]['departman']) : 0;

            $sureler                = isset($_POST["alt_urun_{$i}"]['sure']) ?
                                    array_reverse(array_map('floatval',$_POST["alt_urun_{$i}"]['sure'])) : [];

            $detaylar               = isset($_POST["alt_urun_{$i}"]['detay']) ?
                                    array_reverse($_POST["alt_urun_{$i}"]['detay']) : [];

            $makinalar              = isset($_POST["alt_urun_{$i}"]['makina']) ?
                                    array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['makina'])) : [];


            $alt_arsiv              = isset($_POST["alt_urun_{$i}"]['alt_arsiv']) ? $_POST["alt_urun_{$i}"]['alt_arsiv'] :[];
            $alt_arsiv              = array2DStringToInt(array_reverse($alt_arsiv));

            $stok_kalemler          = isset($_POST["alt_urun_{$i}"]['stok_kalem']) ? $_POST["alt_urun_{$i}"]['stok_kalem'] : [];
            $stok_kalemler          = array2DStringToInt(array_reverse($stok_kalemler));

            $stok_alt_kalemler      = isset($_POST["alt_urun_{$i}"]['stok_alt_kalem']) ? $_POST["alt_urun_{$i}"]['stok_alt_kalem'] : [];
            $stok_alt_kalemler      = array2DStringToInt(array_reverse($stok_alt_kalemler));

            $stok_alt_depo_adetler  = isset($_POST["alt_urun_{$i}"]['stok_alt_depo_adet']) ? $_POST["alt_urun_{$i}"]['stok_alt_depo_adet'] : [];
            $stok_alt_depo_adetler  = array2DStringToInt(array_reverse($stok_alt_depo_adetler));

            $stok_alt_depolar       = isset($_POST["alt_urun_{$i}"]['stok_alt_depo']) ? $_POST["alt_urun_{$i}"]['stok_alt_depo'] : [];
            $stok_alt_depolar       = array2DStringToInt(array_reverse($stok_alt_depolar));

            $fason_durumlar         = isset($_POST["alt_urun_{$i}"]['fason_durum'])  ?
                                    array_reverse(array_map('intval', $_POST["alt_urun_{$i}"]['fason_durum'])) : [];

            $fason_tedarikciler     = isset($_POST["alt_urun_{$i}"]['fason_tedarikci']) ?
                                    array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['fason_tedarikci'])) : [];

            $uretilecek_adet        = preg_replace("/[^0-9]/",'',$_POST["alt_urun_{$i}"]['uretilecek_adet']);
            $isim                   = trim($_POST["alt_urun_{$i}"]['isim']);

            $eksik_stok_varmi       = false;

            //print_r($stok_alt_depo_adetler);
            //Rezervasyonlara ekle
            foreach ($stok_alt_depolar as $asama_index => $stok_alt_depolar_idler) {
                foreach ($stok_alt_depolar_idler as $stok_alt_depo_id_index => $stok_alt_depo_id) {
                    //echo $stok_alt_depo_id."<br>";
                    // 0 seçilmemiş
                    //-1 eksik stok
                    if(!in_array($stok_alt_depo_id, [-1, 0])){
                        $sql = "SELECT id FROM `uretim_reservasyon`
                                WHERE grup_kodu = :grup_kodu AND stok_alt_depo_id = :stok_alt_depo_id AND mevcut_asama = :mevcut_asama";
                        $sth = $conn->prepare($sql);
                        $sth->bindParam("grup_kodu", $grup_kodu);
                        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_id);
                        $sth->bindParam("mevcut_asama", $asama_index);
                        $sth->execute();
                        $reservasyon_varmi = $sth->fetch(PDO::FETCH_ASSOC);

                        //var_dump($reservasyon_varmi);

                        if(empty($reservasyon_varmi)){
                            $sql = "INSERT INTO uretim_reservasyon(firma_id, grup_kodu, mevcut_asama, stok_alt_depo_id, miktar)
                                VALUES(:firma_id, :grup_kodu, :mevcut_asama, :stok_alt_depo_id, :miktar);";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam("firma_id", $_SESSION['firma_id']);
                            $sth->bindParam("grup_kodu", $grup_kodu);
                            $sth->bindParam("mevcut_asama", $asama_index);
                            $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_id);
                            $sth->bindParam("miktar", $stok_alt_depo_adetler[$asama_index][$stok_alt_depo_id_index]);
                            $sth->execute();
                        }else{
                            $sql = "UPDATE uretim_reservasyon SET miktar = :miktar
                                    WHERE firma_id = :firma_id AND stok_alt_depo_id = :stok_alt_depo_id AND mevcut_asama = :mevcut_asama";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam("miktar", $stok_alt_depo_adetler[$asama_index][$stok_alt_depo_id_index]);
                            $sth->bindParam("firma_id", $_SESSION['firma_id']);
                            $sth->bindParam("mevcut_asama", $asama_index);
                            $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_id);
                            $sth->execute();
                        }
                    }else if($stok_alt_depo_id == -1){
                        $eksik_stok_varmi = true;
                        $sql = "DELETE FROM uretim_reservasyon
                                WHERE grup_kodu = :grup_kodu AND stok_alt_depo_id = :stok_alt_depo_id AND mevcut_asama = :mevcut_asama";
                        $sth = $conn->prepare($sql);
                        $sth->bindParam("grup_kodu", $grup_kodu);
                        $sth->bindParam("mevcut_asama", $asama_index);
                        $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_id);
                        $sth->execute();
                    }
                }

            }

            $sql = "UPDATE planlama SET alt_urun_id = :alt_urun_id, isim = :isim, asama_sayisi = :asama_sayisi,
                uretilecek_adet = :uretilecek_adet,departmanlar = :departmanlar, orijinal_adetler = :orijinal_adetler, adetler = :adetler,
                sureler = :sureler, detaylar = :detaylar, makinalar = :makinalar, arsiv_altlar = :arsiv_altlar,stok_kalemler = :stok_kalemler,
                stok_alt_kalemler = :stok_alt_kalemler, stok_alt_depo_adetler = :stok_alt_depo_adetler,
                stok_alt_depolar = :stok_alt_depolar, fason_durumlar = :fason_durumlar, fason_tedarikciler = :fason_tedarikciler,
                onay_durum = :onay_durum, planlama_durum = :planlama_durum, eksik_stok_varmi = :eksik_stok_varmi
                WHERE id = :id AND siparis_id = :siparis_id AND firma_id = :firma_id;";

            $sth = $conn->prepare($sql);
            $sth->bindParam('alt_urun_id',          $i);
            $sth->bindParam('isim',                 $isim);
            $sth->bindParam('asama_sayisi',         $asama_sayisi);
            $sth->bindParam('uretilecek_adet',      $uretilecek_adet);
            $sth->bindValue("departmanlar",         json_encode($departmanlar));
            $sth->bindValue("orijinal_adetler",     json_encode($adetler));
            $sth->bindValue("adetler",              json_encode($adetler));
            $sth->bindValue("sureler",              json_encode($sureler));
            $sth->bindValue("detaylar",             json_encode($detaylar,JSON_UNESCAPED_UNICODE));
            $sth->bindValue("makinalar",            json_encode($makinalar));
            $sth->bindValue("arsiv_altlar",         json_encode($alt_arsiv));
            $sth->bindValue("stok_kalemler",        json_encode($stok_kalemler));
            $sth->bindValue("stok_alt_kalemler",    json_encode($stok_alt_kalemler));
            $sth->bindValue("stok_alt_depo_adetler",json_encode($stok_alt_depo_adetler));
            $sth->bindValue("stok_alt_depolar",     json_encode($stok_alt_depolar));
            $sth->bindValue("fason_durumlar",       json_encode($fason_durumlar));
            $sth->bindValue("fason_tedarikciler",   json_encode($fason_tedarikciler));
            $sth->bindValue("onay_durum",           $onay_durum);
            $sth->bindValue("planlama_durum",       $planlama_durum);
            $sth->bindParam('id',                   $_POST["alt_urun_{$i}"]['planlama_id']);
            $sth->bindParam('siparis_id',           $siparis_id);
            $sth->bindParam('firma_id',             $_SESSION['firma_id']);
            $sth->bindValue("eksik_stok_varmi",     $eksik_stok_varmi ? 'var':'yok');
            $durum = $sth->execute();

            $veriler = json_decode($siparis['veriler'], true);
            if($siparis['tip_id'] == TEK_URUN){
                $veriler['miktar']  = $uretilecek_adet;
                $veriler['isim']    = $isim;
            }else if(in_array($siparis['tip_id'], [GRUP_URUN_TEK_FIYAT,GRUP_URUN_AYRI_FIYAT])){
                $veriler[$i-1]['miktar']  = $uretilecek_adet;
                $veriler[$i-1]['isim']    = $isim;
            }

            $sql = "UPDATE siparisler SET veriler = :veriler WHERE id = :siparis_id";
            $sth = $conn->prepare($sql);
            $sth->bindValue("veriler",    json_encode($veriler));
            $sth->bindParam("siparis_id", $siparis_id);
            $sth->execute();

        }
        else{ // ekle yeni alt ürün
            $sql = "INSERT INTO planlama(firma_id, siparis_id, alt_urun_id, isim, asama_sayisi, uretilecek_adet, departmanlar,
            adetler, sureler, detaylar, makinalar, arsiv_altlar, stok_alt_kalemler,
            stok_alt_depo_adetler, stok_alt_depolar, fason_durumlar, fason_tedarikciler, onay_durum, planlama_durum, tekil_kod)
            VALUES(:firma_id, :siparis_id, :alt_urun_id, :isim, :asama_sayisi, :uretilecek_adet, :departmanlar,
            :adetler, :sureler, :detaylar, :makinalar, :arsiv_altlar, :stok_alt_kalemler,
            :stok_alt_depo_adetler, :stok_alt_depolar, :fason_durumlar, :fason_tedarikciler, :onay_durum, :planlama_durum, :tekil_kod)";

            $asama_sayisi           = count($_POST["alt_urun_{$i}"]['departman']);
            $departmanlar           = array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['departman']));
            $adetler                = array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['adet']));

            $sureler                = array_reverse(array_map('floatval',$_POST["alt_urun_{$i}"]['sure']));
            $detaylar               = array_reverse($_POST["alt_urun_{$i}"]['detay']);
            $makinalar              = array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['makina']));
            $alt_arsiv              = array2DStringToInt(array_reverse($_POST["alt_urun_{$i}"]['alt_arsiv']));
            $stok_alt_kalemler      = array2DStringToInt(array_reverse($_POST["alt_urun_{$i}"]['stok_alt_kalem']));
            $stok_alt_depo_adetler  = array2DStringToInt(array_reverse($_POST["alt_urun_{$i}"]['stok_alt_depo_adet']));
            $stok_alt_depolar       = array2DStringToInt(array_reverse($_POST["alt_urun_{$i}"]['stok_alt_depo']));
            $fason_durumlar         = array_reverse(array_map('intval', $_POST["alt_urun_{$i}"]['fason_durum']));
            $fason_tedarikciler     = array_reverse(array_map('intval',$_POST["alt_urun_{$i}"]['fason_tedarikci']));

            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id",             $_SESSION['firma_id']);
            $sth->bindParam("siparis_id",           $siparis_id);
            $sth->bindParam("alt_urun_id",          $i);
            $sth->bindParam("isim",                 $_POST["alt_urun_{$i}"]['isim']);
            $sth->bindParam("asama_sayisi",         $asama_sayisi);
            $sth->bindValue('uretilecek_adet',      preg_replace("/[^0-9]/",'',$_POST["alt_urun_{$i}"]['uretilecek_adet']));
            $sth->bindValue("departmanlar",         json_encode($departmanlar));
            $sth->bindValue("adetler",              json_encode($adetler));
            $sth->bindValue("sureler",              json_encode($sureler));
            $sth->bindValue("detaylar",             json_encode($detaylar,JSON_UNESCAPED_UNICODE));
            $sth->bindValue("makinalar",            json_encode($makinalar));
            $sth->bindValue("arsiv_altlar",         json_encode($alt_arsiv));
            $sth->bindValue("stok_alt_kalemler",    json_encode($stok_alt_kalemler));
            $sth->bindValue("stok_alt_depo_adetler",json_encode($stok_alt_depo_adetler));
            $sth->bindValue("stok_alt_depolar",     json_encode($stok_alt_depolar));
            $sth->bindValue("fason_durumlar",       json_encode($fason_durumlar));
            $sth->bindValue("fason_tedarikciler",   json_encode($fason_tedarikciler));
            $sth->bindValue("onay_durum",           $onay_durum);
            $sth->bindValue("planlama_durum",       $planlama_durum);
            $sth->bindValue("tekil_kod",            uniqid());
            $durum = $sth->execute();
        }
    }

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'İşlem Başarılı';
        header("Location: /index.php?url=planlama");
    }
    else
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'İşlem Başarısız';
        header("Location: /index.php?url=planla_siparis_duzenle&siparis_id={$siparis_id}");
    }
    exit;
}

#tedarikçileri getir
if(isset($_GET['islem']) && $_GET['islem'] == 'tedarikcileri_getir'){
    $sth = $conn->prepare('SELECT * FROM tedarikciler WHERE firma_id = :firma_id AND fason = "evet"');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $tedarikciler = $sth->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');

    ob_clean();
    echo json_encode(['tedarikciler' => $tedarikciler]);exit;
}

#departmana özel makinaları getir
if(isset($_GET['islem']) && $_GET['islem'] == 'departmanin_makinalari'){

    $sth = $conn->prepare('SELECT * FROM makinalar
        WHERE firma_id = :firma_id AND departman_id = :departman_id AND durumu = "aktif" AND planlamada_goster = "evet"');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $_GET['departman_id']);
    $sth->execute();
    $makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');

    ob_clean();
    echo json_encode(['makinalar' => $makinalar]);exit;
}

if(isset($_GET['islem']) && $_GET['islem'] == 'departmanin_birimini_getir'){
    $sql = "SELECT birimler.ad FROM `departman_planlama` JOIN birimler ON birimler.id =  departman_planlama.birim_id
    WHERE departman_planlama.firma_id = :firma_id AND departman_planlama.departman_id = :departman_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $_GET['departman_id']);
    $sth->execute();
    $birim = $sth->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');

    ob_clean();
    echo json_encode(['birim' => $birim]);exit;
}


//departmanin ve siparişin alt arşivlerini getirme
if(isset($_GET['islem']) && $_GET['islem'] == 'siparis_alt_arsiv'){
    $sql = "SELECT * FROM `arsiv_kalemler` WHERE firma_id = :firma_id AND departman_id = :departman_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $_GET['departman_id']);
    $sth->execute();
    $arsivler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT arsiv_getirme FROM `firmalar` WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $_SESSION['firma_id']);
    $sth->execute();
    $firma_ayar = $sth->fetch(PDO::FETCH_ASSOC);



    foreach ($arsivler as $index=>$arsiv) {

        $sql = "SELECT * FROM `arsiv_altlar` WHERE arsiv_id = :arsiv_id ";
        if($firma_ayar['arsiv_getirme'] == 'siparise_ozel'){
            $sql .= " AND siparis_id = :siparis_id";
        }

        $sth = $conn->prepare($sql);
        $sth->bindParam('arsiv_id', $arsiv['id']);

        if($firma_ayar['arsiv_getirme'] == 'siparise_ozel'){
            $sth->bindParam('siparis_id', $_GET['siparis_id']);
        }
        $sth->execute();
        $alt_arsivler = $sth->fetchAll(PDO::FETCH_ASSOC);
        if(empty($alt_arsivler)){
            unset($arsivler[$index]);
        }
        else{
            $arsivler[$index]['alt_arsivler'] = $alt_arsivler;
        }
    }

    header('Content-Type: application/json; charset=utf-8');

    ob_clean();
    echo json_encode(['arsivler' => $arsivler]);exit;
}

//alt arsiv resimlerini getir
if(isset($_GET['islem']) && $_GET['islem'] == 'alt_arsiv_resim_getir'){
    $sql = "SELECT * FROM `arsiv_alt_dosyalar` WHERE firma_id = :firma_id AND arsiv_alt_id = :arsiv_alt_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('arsiv_alt_id', $_GET['arsiv_alt_id']);
    $sth->execute();
    $alt_arsiv_resimler = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Depolama tipine göre dosya URL'lerini ayarla
    foreach($alt_arsiv_resimler as &$resim){
        if($firma_ayarlar['dosya_depolama_tipi'] == 's3'){
            $resim['url'] = $storage->getFileFromS3('arsiv', $resim['ad']);
        }else{
            $resim['url'] = "dosyalar/arsiv/{$resim['ad']}";
        }
    }

    header('Content-Type: application/json; charset=utf-8');

    ob_clean();
    echo json_encode(['alt_arsiv_resimler' => $alt_arsiv_resimler]);exit;
}

//stoklari getir stoklari_getir
if(isset($_GET['islem']) && $_GET['islem'] == 'stoklari_getir'){
    $sql = "SELECT departman_planlama.stok
        FROM `departman_planlama`
        WHERE firma_id = :firma_id AND `departman_id` = :departman_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $_GET['departman_id']);
    $sth->execute();
    $departman_stok             = $sth->fetch(PDO::FETCH_ASSOC);
    $departman_stoklar          = json_decode($departman_stok['stok'], true);
    $stoklar                    = [];
    $stok_alt_kalemler_sonuc    = [];

    foreach ($departman_stoklar as $key => $stok_id) {
        $sql = "SELECT id,stok_kalem FROM `stok_kalemleri`  WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $stok_id);
        $sth->execute();
        $stok_kalem = $sth->fetch(PDO::FETCH_ASSOC);
        if($stok_kalem){
            $sql = "SELECT stok_alt_kalemler.id, stok_alt_kalemler.veri,stok_alt_kalemler.toplam_stok
                    FROM `stok_alt_kalemler`
                    WHERE stok_alt_kalemler.firma_id = :firma_id
                    AND stok_alt_kalemler.stok_id = :stok_id";
            $sth = $conn->prepare($sql);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->bindParam('stok_id', $stok_id);
            $sth->execute();
            $stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
            if(!empty($stok_alt_kalemler)){
                $stoklar[] = $stok_kalem;
                $stok_alt_kalemler_sonuc[] = $stok_alt_kalemler;
            }
        }
    }

    header('Content-Type: application/json; charset=utf-8');

    ob_clean();
    echo json_encode([
        'stoklar'           => $stoklar,
        'stok_alt_kalemler' =>$stok_alt_kalemler_sonuc
    ]);
    exit;
}


// stok alt depoları getir
if(isset($_GET['islem']) && $_GET['islem'] == 'stok_alt_depo_getir'){
    $siparis_id         = intval($_POST['siparis_id']);
    $stok_alt_kalem_id  = intval($_POST['stok_alt_kalem_id']);

    $sql = "SELECT stok_alt_depo_kod  FROM `siparisler`  WHERE id = :id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT stok_alt_depolar.id, (stok_alt_depolar.adet - stok_alt_depolar.kullanilan_adet) AS kalan_adet,
            stok_alt_depolar.fatura_no,stok_alt_depolar.siparis_no,`stok_alt_depolar`.`stok_kodu`,
            `tedarikciler`.`firma_adi`
            FROM `stok_alt_depolar`
            JOIN `tedarikciler` ON `tedarikciler`.id = stok_alt_depolar.tedarikci_id
            WHERE stok_alt_depolar.stok_alt_kalem_id = :stok_alt_kalem_id
            AND stok_alt_depolar.firma_id = :firma_id
            -- AND (stok_alt_depolar.stok_alt_depo_kod  = :stok_alt_depo_kod  OR stok_alt_depolar.stok_alt_depo_kod  IS NULL)
            AND (stok_alt_depolar.adet - stok_alt_depolar.kullanilan_adet) > 0
            ORDER BY stok_alt_depolar.stok_alt_depo_kod DESC";
    $sth = $conn->prepare($sql);
    $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    #$sth->bindParam('stok_alt_depo_kod', $siparis['stok_alt_depo_kod']);
    $sth->execute();
    $stok_alt_depolar = $sth->fetchAll(PDO::FETCH_ASSOC);

    //Firmanın Reservasyonları
    $sql = "SELECT `stok_alt_depo_id`, (SUM(`miktar`) - SUM(`kullanilan_miktar`)) reservasyon_miktari
            FROM `uretim_reservasyon`
            WHERE `firma_id` = :firma_id GROUP BY `stok_alt_depo_id`; ";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $reservasyonlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stok_alt_depolar as $index => $stok_alt_depo) {
        foreach ($reservasyonlar as $reservasyon) {
            if($reservasyon['stok_alt_depo_id'] == $stok_alt_depo['id']){
                $stok_alt_depolar[$index]['kalan_adet']  = $stok_alt_depo['kalan_adet'] - $reservasyon['reservasyon_miktari'];
                break;
            }
        }
    }

    $sql = "SELECT birimler.ad FROM `stok_alt_kalemler`
            JOIN birimler ON birimler.id = stok_alt_kalemler.birim_id
            WHERE stok_alt_kalemler.id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $stok_alt_kalem_id);
    $sth->execute();
    $birim = $sth->fetch(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        'stok_alt_depolar'  =>  $stok_alt_depolar,
        'birim'             =>  $birim,
    ]); exit;
}

if(isset($_GET['islem']) && $_GET['islem'] == 'stok_alt_kalem_getir'){
    $sql = "SELECT * FROM `stok_alt_kalemler` WHERE stok_id = :stok_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('stok_id', $_POST['stok_id']);
    $sth->execute();
    $stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM `stok_kalemleri` WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $_POST['stok_id']);
    $sth->execute();
    $stok_kalem = $sth->fetch(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        'stok_alt_kalemler' =>$stok_alt_kalemler,
        'stok_kalem'        =>$stok_kalem
    ]);exit;
}

if (isset($_GET['islem']) && $_GET['islem'] == 'planlama-pdf' && isset($_GET['siparis_id'])) {

    // Increase execution time for PDF generation
    set_time_limit(120); // 2 minutes
    ini_set('max_execution_time', 120);

    $siparis_id = intval($_GET['siparis_id']);
    $logo = isset($_SESSION['logo']) ? htmlspecialchars($_SESSION['logo'], ENT_QUOTES, 'UTF-8') : 'default-logo.png';
    
    // Check if logo file exists, otherwise use empty string
    $logo_path = 'dosyalar/logo/' . $logo;
    if (!file_exists($logo_path)) {
        $logo_html = ''; // No logo if file doesn't exist
    } else {
        $logo_html = '<img style="width:60px;height:60px" src="' . $logo_path . '" alt="small logo" class="logo-sm">';
    }

    // Sipariş temel bilgilerini al
    $sql = 'SELECT
                mst.firma_unvani as musteri,
                a.siparis_no,
                tip.tip,
                a.isin_adi,
                tur.tur,
                a.arsiv_kod,
                a.adet,
                brm.ad as olcu_birimi,
                a.fiyat,
                a.para_cinsi as para_birimi,
                otp.odeme_sekli,
                a.teslimat_adresi,
                ulke.baslik as ulke,
                sehir.baslik as sehir,
                ilce.baslik as ilce,
                a.termin as termin_tarihi,
                a.uretim as uretim_tarihi,
                a.vade as vade_tarihi,
                a.tarih,
                CONCAT(prs.ad, " ", prs.soyad) as onaylayan_ad_soyad,
                a.numune,
                a.aciklama,
                a.onay_baslangic_durum,
                a.islem,
                a.paketleme,
                a.nakliye,
                CONCAT(tmc.ad, " ", tmc.soyad) as musteri_temsilcisi,
                a.veriler
            FROM siparisler as a
            LEFT JOIN firmalar f on a.firma_id = f.id
            LEFT JOIN siparis_form_tipleri tip on a.tip_id = tip.id
            LEFT JOIN turler AS tur on tur.firma_id = a.firma_id and tur.id = a.tur_id
            LEFT JOIN musteri AS mst on mst.firma_id = a.firma_id and mst.id = a.musteri_id
            LEFT JOIN birimler brm on brm.id = a.birim_id and brm.firma_id = a.firma_id
            LEFT JOIN ulkeler ulke on ulke.id = a.ulke_id
            LEFT JOIN sehirler sehir on sehir.id = a.sehir_id and sehir.ulke_id = a.ulke_id
            LEFT JOIN ilceler ilce on ilce.id = a.ilce_id and ilce.sehir_id = a.sehir_id
            LEFT JOIN personeller prs on prs.id = a.onaylayan_personel_id and prs.firma_id = a.firma_id
            LEFT JOIN odeme_tipleri otp on otp.id = a.odeme_sekli_id
            LEFT JOIN personeller tmc on tmc.id = a.musteri_temsilcisi_id and tmc.firma_id = a.firma_id
            WHERE a.id = :siparis_id AND a.firma_id = :firma_id';

    $sth = $conn->prepare($sql);
    $sth->bindParam(':firma_id', $_SESSION['firma_id']);
    $sth->bindParam(':siparis_id', $siparis_id);
    $sth->execute();
    $siparis = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM planlama
    WHERE siparis_id = :siparis_id AND 
          firma_id = :firma_id 
    ORDER BY alt_urun_id asc";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM departmanlar
            WHERE firma_id = :firma_id ORDER BY `departmanlar`.`departman` ASC";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    // ************ Birimler Start ************* //
    $sql_birim = 'SELECT *  FROM birimler a WHERE a.firma_id = :firma_id'; 
    $sth = $conn->prepare($sql_birim);
    $sth->bindParam('firma_id', $_SESSION['firma_id']); 
    $sth->execute();
    $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);
    // ************* # Birimler End ************* //
    // ************ Turler Start ************* //
    $sql_tur = 'SELECT *  FROM turler a WHERE a.firma_id = :firma_id'; 
    $sth = $conn->prepare($sql_tur);
    $sth->bindParam('firma_id', $_SESSION['firma_id']); 
    $sth->execute();
    $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
    // ************* # Turler End ************* //
    // ************ Makinalar Start ************* //
    $sql_tur = 'SELECT id, makina_adi FROM makinalar a WHERE a.firma_id = :firma_id AND a.planlamada_goster = "evet"'; 
    $sth = $conn->prepare($sql_tur);
    $sth->bindParam('firma_id', $_SESSION['firma_id']); 
    $sth->execute();
    $makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);
    // ************* # Makinalar End ************* //
    // ************ Tedarikciler Start ************* //
    $sql_tur = 'SELECT id, firma_adi FROM tedarikciler a WHERE a.firma_id = :firma_id'; 
    $sth = $conn->prepare($sql_tur);
    $sth->bindParam('firma_id', $_SESSION['firma_id']); 
    $sth->execute();
    $tedarikciler = $sth->fetchAll(PDO::FETCH_ASSOC);
    // ************* # Tedarikciler End ************* //
    // ************ Stok Kalemleri Start ************* //
    $sql_tur = 'SELECT id, stok_kalem FROM stok_kalemleri a WHERE a.firma_id = :firma_id'; 
    $sth = $conn->prepare($sql_tur);
    $sth->bindParam('firma_id', $_SESSION['firma_id']); 
    $sth->execute();
    $stokKalemleri = $sth->fetchAll(PDO::FETCH_ASSOC);
    // ************* # Stok Kalemleri End ************* //
    // ************ Stok Alt Kalemleri Start ************* //
    $sql_tur = 'SELECT id, stok_id, veri FROM stok_alt_kalemler a WHERE a.firma_id = :firma_id'; 
    $sth = $conn->prepare($sql_tur);
    $sth->bindParam('firma_id', $_SESSION['firma_id']); 
    $sth->execute();
    $stokAltKalemleri = $sth->fetchAll(PDO::FETCH_ASSOC);
    // ************* # Stok Alt Kalemleri End ************* //

    if (!$siparis) {
        die("Sipariş bulunamadı.");
    }

    $jsonData = json_decode($siparis['veriler']);

    $items = is_array($jsonData) ? $jsonData : [$jsonData];

    $fields    = [];
    $subFields = [];

    foreach ($items as $index => &$item) { // &$item ile tüm nesneyi güncelle
        foreach ($item as $key => &$value) {
            if ($key == 'form') { 

            }
            if (strtoupper($key) == 'BIRIM_ID') {
                $index = $value - 1;
                $value = isset($birimler[$index]) ? $birimler[$index]['ad'] : $value;
            }
            if (strtoupper($key) == 'TUR') {
                $index = $value - 1;
                $value = isset($turler[$index]['tur']) ? $turler[$index]['tur'] : $value;
            }
        }
        unset($value); 
    }
    unset($item);
 
    $arrayProps = get_object_vars($items[0]);
    foreach ($arrayProps as $key => $value) {
        if($key == 'form') {
            $formValues = get_object_vars($value);
            foreach ($formValues as $key2 => $value2) {
                if (!in_array($key2, $subFields) && $value2 != '') {
                    array_push($subFields, $key2);
                }
            }
        }else{ 
            if (!in_array($key, $fields)) {
                array_push($fields, $key);
            }
        }
    }  

    $html = '
            <!DOCTYPE html>
            <html lang="tr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Sipariş Onay Formu</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 10px; }
                    .container { max-width: 1000px; margin: 0 auto; }
                    .btn { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
                    .btn:hover { background-color: #45a049; }
                    .border{ border: 1px solid #ddd; }
                </style>
            </head>
            <body>
                <div class="container">
                <table border="0" cellpadding="5" cellspacing="0" width="100%">
                    <tr>
                        <td width="10%" style="text-align:center">'. $logo_html .'</td>
                        <td width="90%" style="text-align:center">
                            <h2>SİPARİŞ PLANLAMA FORMU</h2>
                        </td>
                    </tr>
                </table>
                <hr style="margin-top:10px;margin-bottom:10px;">

                <h4>GENEL BİLGİLER</h4>
                <table class="border" style="margin-bottom:10px" cellpadding="5" cellspacing="0">
                    <tr>
                        <td><strong>Müşteri Adı:</strong></td>
                        <td colspan="3">'. $siparis['musteri'] .'</td>
                    </tr>
                    <tr>
                        <td><strong>Sipariş No:</strong></td>
                        <td>'. $siparis['siparis_no'] .'</td>
                        <td><strong>İşin Adı:</strong></td>
                        <td>'. $siparis['isin_adi'] .'</td>
                    </tr>
                    <tr>
                        <td><strong>Adet:</strong></td>
                        <td>' . number_format($siparis['adet'], 0, ',', '.') . '</td>
                        <td><strong>Ölçü Birimi</strong></td>
                        <td>'. $siparis['olcu_birimi'] .'</td>
                    </tr>
                    <tr>
                        <td><strong>Üretim Tarihi:</strong></td>
                 <td>'. date('d.m.Y', strtotime($siparis['uretim_tarihi'])) .'</td>
                        <td><strong>Termin Tarihi:</strong></td>
                        <td>'. date('d.m.Y', strtotime($siparis['termin_tarihi'])) .'</td>
                    </tr>
                </table>';

                foreach ($items as $item) {
                    // $html .= '<table class="border" cellpadding="5" cellspacing="0"><tr>';
                    // foreach ($fields as $field) {
                    //     $html .= '<td class="border"><strong>'. strtoupper( $field ) .'</strong></td>';
                    // }
                    // $html .= '</tr><tr>';
                    // foreach ($fields as $field) {
                    //     $html .= '<td class="border">'. $item->$field .'</td>'; 
                    // }
                    // $html .= '</tr>';
                    // $html .= '</table><br>';
                    if(isset($item->form) && count($subFields) > 0) {
                        $html .= '<table class="border" style="width:100%;margin-bottom:10px" cellpadding="5" cellspacing="0">';
                        foreach ($subFields as $key => $value) {
                            if($key == 0 || $key % 3 == 0) {
                                $html .= '<tr>';
                            }
                            if($item->form->$value == '') {
                                continue;
                            }
                            $html .= '<td><strong>'. strtoupper( $value ) .'</strong>:</td><td> '. $item->form->$value .'</td>';
                            if($key == 0 || $key % 3 == 0) {
                                $html .= '</tr>';
                            }
                        } 
                        $html .= '</table>';
                        // $html .= '</tr><tr>';
                        // foreach ($subFields as $subField) {
                        //     $html .= '<td class="border">'. $item->form->$subField .'</td>';
                        // }
                        // // $html .= '</tr><td>'. $item->$field .'</td>';
                        // $html .= '</tr></table>';
                    }
                }

   $html .= '<table style="width:100%;margin-bottom:10px"><tr><td style="vertical-align:top">';
   $html .= '<h4>Planlama Bilgileri</h4><br>
                <table style="border:1px solid #ddd" cellpadding="5" cellspacing="0">
                    <tr>
                        <td><strong>Onaylayan Personel:</strong></td>
                        <td>'. $siparis['onaylayan_ad_soyad'] .'</td>
                        <td><strong>Müşteri Temsilcisi:</strong></td>
                        <td>'. $siparis['musteri_temsilcisi'] .'</td>
                    </tr>
                    <tr>
                        <td><strong>Durum:</strong></td>
                        <td>'. $siparis['islem'] .'</td>
                        <td><strong>Oluşturulma Tarihi:</strong></td>
                        <td>'. date('d.m.Y H:i:s', strtotime($siparis['tarih'])) .'</td>
                    </tr>
                </table></td>';
            
    $html  .=   '<td><h4>TESLİMAT BİLGİLERİ</h4><br>
                <table class="border" cellpadding="5" cellspacing="0">
                    <tr>
                        <td><strong>Ülke:</strong></td>
                        <td>'. $siparis['ulke'] .'</td>
                        <td><strong>Şehir:</strong></td>
                        <td>'. $siparis['sehir'] .'</td>
                        <td><strong>İlçe:</strong></td>
                        <td>'. $siparis['ilce'] .'</td>
                    </tr>
                    <tr>
                        <td><strong>Paketleme:</strong></td>
                        <td colspan="5">'. $siparis['paketleme'] .'</td>
                    </tr>
                    <tr>
                        <td><strong>Nakliye:</strong></td>
                        <td colspan="5">'. $siparis['nakliye'] .'</td>
                    </tr>
                     <tr>
                        <td><strong>Teslimat Adresi:</strong></td>
                        <td colspan="5"></td>
                    </tr>
                </table></td></tr></table>';

                foreach ($planlamalar as $planlama) {
                   $html .= '<table class="border" style="width:100%;">';
                   $html .= '<tr class="border"><td><strong>'. $planlama["alt_urun_id"].'. Alt Ürün:</strong> '.$planlama["isim"].'</td>
                                 <td style="text-align:right"><strong>Ü.Adet:</strong> ' . number_format($planlama["uretilecek_adet"], 0, ',', '.') .'</td>
                             </tr></table>';
                   $html .= '<table class="border" style="width:100%;margin-bottom:10px;border-bottom:0">'; 
                             $departmanlar_data  = json_decode($planlama['departmanlar'], true);
                             foreach ($departmanlar_data as $index => $departman) {
                               
                                $html .= '<tr>';
                                $html .= '<td style="border-bottom:1px solid #ddd">';
                                $html .= '<table>';
                                $adetler  = json_decode($planlama['adetler'], true);
  
                                $html .= '<tr><td><strong>'. ($index + 1) .'. Aşama:</strong></td></tr>';
                                $filteredDepartman = array_filter($departmanlar, function($departman1) use ($departman) {
                                    return $departman1['id'] == $departman;
                                });
                                foreach ($filteredDepartman as $filteredDep) {
                                    $html .= '<tr><td><strong>Departman:</strong> '.$filteredDep['departman'].'</td></tr>';
                                }
                                $html .= '<tr><td><strong>Adet:</strong> '.number_format($adetler[$index], 0, ',', '.') .'</td></tr>';
                                
                                $fason  = json_decode($planlama['fason_durumlar'], true);
                                if(isset($fason[$index])){
                                    $tedarikci    = json_decode($planlama['fason_tedarikciler'], true);
                                    $tedarikci_id = $tedarikci[$index];
                                    $filteredTedarikci = array_filter($tedarikciler, function($tedarikci1) use ($tedarikci_id) {
                                        return $tedarikci1['id'] == $tedarikci_id;
                                    });
                                    foreach ($filteredTedarikci as $filteredTed) {
                                        $html .= '<tr><td><strong>Fason:</strong> '.$filteredTed['firma_adi'].'</td></tr>';
                                    }
                                }else{ 
                                    $makina  = json_decode($planlama['makinalar'], true);
                                    $makina_id = $makina[$index];
                                    $filteredMakina = array_filter($makinalar, function($makina1) use ($makina_id) {
                                        return $makina1['id'] == $makina_id;
                                    });
                                    foreach ($filteredMakina as $filteredMak) {
                                        $html .= '<tr><td><strong>Fason:</strong> '.$filteredMak['makina_adi'].'</td></tr>';
                                    }
                                }
                                $stok_kalemler = json_decode($planlama['stok_kalemler'], true);
                                $kalems = $stok_kalemler[$index];

                                $stok_alt_kalemler = json_decode($planlama['stok_alt_kalemler'], true);
                                $alt_kalems = $stok_alt_kalemler[$index];

                                $stok_adetler = json_decode($planlama['stok_alt_depo_adetler'], true); 

                                foreach ($kalems as $index2 => $kalem) {
                                    $kalem_id = $kalem;
                                    $filteredStok = array_filter($stokKalemleri, function($kalem1) use ($kalem_id) {
                                        return $kalem1['id'] == $kalem_id;
                                    });
                                    foreach ($filteredStok as $filteredSt) {
                                        $html .= '<tr>';
                                        $html .= '<td><strong>'.$filteredSt['stok_kalem'].':</strong>';
                                        foreach ($alt_kalems as $alt_kalem) {
                                            $alt_kalem_id = $alt_kalem;
                                            $filteredAltStok = array_filter($stokAltKalemleri, function($kalem1) use ($alt_kalem_id,$kalem_id) {
                                                return $kalem1['id'] == $alt_kalem_id && $kalem1['stok_id'] == $kalem_id;
                                            });
                                            foreach ($filteredAltStok as $filteredAltSt) {
                                                $stokVeriler = json_decode($filteredAltSt['veri'], true);
                                                $html .= '<table>';
                                                foreach ($stokVeriler as $key => $value){
                                                    $html .= '<tr><td>'.$key.' : '.$value.'</td></tr>';
                                                }
                                                $html .= '</table>';
                                            }
                                        } 
                                        $html .= '</td></tr>';
                                    }
                                    $html .= '<tr><td><strong>Adet:</strong> '.number_format($stok_adetler[$index][$index2], 0, ',', '.').'</td></tr>';
                                }   
                                $html .= '</table>';
                                $html .= '</td>';
                                $html .= '<td style="border-bottom:1px solid #ddd;width:40%"><strong>Detay:</strong>';
                                    $detaylar = json_decode($planlama['detaylar'], true);  
                                $html .= ' '.$detaylar[$index].'<br>'; 
                                $html .= '</td>';
 
                                $html .= '</tr>';  
                                
                            } 
                    $html .= '</table>';
                }
                  
                $html .= ' 
                </div>
            </body>
            </html>';

    $mpdf = new Mpdf([
        'default_font_size' => 10,
        'format' => 'A4',
        'margin_left' => 5,
        'margin_right' => 5,
        'margin_top' => 5,
        'margin_bottom' => 5,
        'curlTimeout' => 10, // Reduce timeout for external resources
        'curlAllowUnsafeSslRequests' => false,
    ]);

    $mpdf->WriteHTML($html);
    $filename = 'Siparis_Planlama_Formu' . preg_replace('/[^A-Za-z0-9\-]/', '', $siparis['siparis_no']) . '.pdf';

    // Çıktı tamponunu temizle
    ob_clean();

    // HTTP başlıklarını ayarla
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $mpdf->Output($filename, 'I');

    exit;
}