<?php
ini_set('display_errors', 1);

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);

require_once "include/db.php";
include_once "include/oturum_kontrol.php";
require_once "include/SimpleXLSXGen.php";


#print_r($_SESSION);

if(isset($_GET['islem']) && $_GET['islem'] == 'musteri_excel')
{
    // $sql = "SELECT * FROM musteri  WHERE firma_id = :firma_id";
    // if ( $_SESSION['yetki_id'] != ADMIN_YETKI_ID){
    //     $sql .= " AND  musteri.musteri_temsilcisi_id = :musteri_temsilcisi_id";
    // }
    // $sth = $conn->prepare($sql);
    // $sth->bindParam('firma_id', $_SESSION['firma_id']);
    // if ( $_SESSION['yetki_id'] != ADMIN_YETKI_ID){
    //     $sth->bindParam('musteri_temsilcisi_id', $_SESSION['personel_id']);
    // }
    // $sth->execute();
    // $musteriler = $sth->fetchAll(PDO::FETCH_ASSOC);
    // $excel_data = [
    //     ['SIRA','MARKA', 'FİRMA UNVANI','EMAIL', 'ADRES','TELEFON', 'SABİT HAT', 'YETKİLİ AD SOYAD', 'YETKİLİ CEP', 'YETKİLİ EMAİL', 'VERGI DAİRESİ']
    // ];

    // foreach ($musteriler as $key=> $musteri) {
    //     $excel_data[] = [
    //         $key+1, $musteri['marka'], $musteri['firma_unvani'], 
    //         $musteri['e_mail'], $musteri['adresi'], $musteri['cep_tel'], $musteri['sabit_hat'], 
    //         $musteri['yetkili_adi'], $musteri['yetkili_cep'],$musteri['yetkili_mail'],$musteri['vergi_dairesi']
    //     ]; 
    // }

    // //print_r($excel_data);exit;

    // $xlsx = Shuchkin\SimpleXLSXGen::fromArray( $excel_data );
    // $tarih = date('dmY');
    // $xlsx->downloadAs("musteriler-{$tarih}.xlsx"); // or downloadAs('books.xlsx') or $xlsx_content = (string) $xlsx 

    // exit;

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

        $sql = 'SELECT  
                mst.marka as musteri, 
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
            LEFT JOIN personeller prs on prs.id = a.onaylayan_personel_id
            LEFT JOIN odeme_tipleri otp on otp.id = a.odeme_sekli_id
            LEFT JOIN personeller tmc on tmc.id = a.musteri_temsilcisi_id and tmc.firma_id = a.firma_id 
            WHERE a.firma_id = :firma_id';

        if ( $_SESSION['yetki_id'] != ADMIN_YETKI_ID){
            $sql .= " AND  a.musteri_temsilcisi_id = :musteri_temsilcisi_id";
        }

        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']); 
        if ( $_SESSION['yetki_id'] != ADMIN_YETKI_ID){
             $sth->bindParam('musteri_temsilcisi_id', $_SESSION['personel_id']);
        }
        $sth->execute();
        $siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sutunlar = [];
        $sutunHaritasi = [];

        $columnCount = $sth->columnCount();
        for ($i = 0; $i < $columnCount; $i++) {
            $meta = $sth->getColumnMeta($i);
            $columnName = strtoupper($meta['name']);
            $columnName = str_replace('.', '_', $columnName);

            if ($columnName !== 'VERILER') {
                $sutunlar[] = $columnName;
            }
        }

        $sutunHaritasi = $sutunlar;

        foreach ($siparisler as $key=> $siparis) {
            $veriler = json_decode($siparis['veriler']);

            $type = gettype($veriler);

            if($type === "array"){
                foreach($veriler as $veri){
                    if (isset($veri->form) && is_object($veri->form)) {
                        $ozellikler = get_object_vars($veri->form);
            
                        foreach ($ozellikler as $anahtar => $deger) { 
                            if (!in_array($anahtar, $sutunlar)) {
                                $sutunlar[] = $anahtar;
                            }
                        } 
                    }
            
                    if (isset($veri) && is_object($veri)) {
                        $ozellikler = get_object_vars($veri);
            
                        foreach ($ozellikler as $anahtar => $deger) { 
                            if($anahtar != 'form')
                            {
                                if (!in_array($anahtar, $sutunlar)) {
                                    $sutunlar[] = $anahtar;
                                }
                            }
                        } 
                    }
                }
            }else{
                if (isset($veriler->form) && is_object($veriler->form)) {
                    $ozellikler = get_object_vars($veriler->form);

                    foreach ($ozellikler as $anahtar => $deger) { 
                        if (!in_array($anahtar, $sutunlar)) {
                            $sutunlar[] = $anahtar;
                        }
                    } 
                }

                if (isset($veriler) && is_object($veriler)) {
                    $ozellikler = get_object_vars($veriler);

                    foreach ($ozellikler as $anahtar => $deger) { 
                        if($anahtar != 'form')
                        {
                            if (!in_array($anahtar, $sutunlar)) {
                                $sutunlar[] = $anahtar;
                            }
                        }
                    } 
                }
            } 
        }

        $sonuclar = [];

        foreach ($siparisler as $siparis) { 
            $veriler = json_decode($siparis['veriler']);
            $type = gettype($veriler);

            if($type === "array"){
                foreach($veriler as $veri){
                    $satir = [];

                    foreach ($sutunlar as $sutun) {    
                        if (in_array($sutun, $sutunHaritasi)) {
                            $satir[] = $siparis[strtolower($sutun)] ?? '';
                        } else {  
                            if (isset($veri->form) && is_object($veri->form) && property_exists($veri->form, $sutun)) {
                                $satir[] = $veri->form->$sutun;
                            }
                            else if (isset($veri) && is_object($veri) && property_exists($veri, $sutun)) {
                                $satir[] = $veri->$sutun;
                            } else {
                                $satir[] = '';  
                            }
                        }
                    } 

                    $sonuclar[] = $satir;
                }
            }else{
                $satir = [];

                foreach ($sutunlar as $sutun) {    
                    if (in_array($sutun, $sutunHaritasi)) {
                        $satir[] = $siparis[strtolower($sutun)] ?? '';
                    } else { 
                        $veriler = json_decode($siparis['veriler']);
                        if (isset($veriler->form) && is_object($veriler->form) && property_exists($veriler->form, $sutun)) {
                            $satir[] = $veriler->form->$sutun;
                        }
                        else if (isset($veriler) && is_object($veriler) && property_exists($veriler, $sutun)) {
                            $satir[] = $veriler->$sutun;
                        } else {
                            $satir[] = '';  
                        }
                    }
                } 

                $sonuclar[] = $satir;
            } 
        }
 
        $excel_data[] = $sutunlar;
        array_push($excel_data, ...$sonuclar);
  
        $birim_id_index = 0;
        $tur_index = 0;
        $numune_index = 0;

        $renk = '#FFFF00'; 
        foreach ($excel_data[0] as $index => $baslik) {  
            if($baslik == 'birim_id'){
                $birim_id_index = $index;
            }elseif($baslik == 'tur'){
                $tur_index = $index;
            }elseif($baslik == 'numune'){
                $numune_index = $index;
            }
            $excel_data[0][$index] = "<style bgcolor=\"{$renk}\">{$baslik}</style>";
        }

        foreach ($excel_data as $index => &$data) {
            if($index == 0) continue;
            
            $birim_id  = $data[$birim_id_index];
            $tur_id    = $data[$tur_index];
            $numune_id = $data[$numune_index]; 

            if(isset($birim_id)){  
                $birim = array_filter($birimler, function($birim) use ($birim_id) {
                    return (int)$birim['id'] === (int)$birim_id; 
                });
                
                if (!empty($birim)) {
                    $brm_txt = current($birim)['ad']; 
                    $data[$birim_id_index] = $brm_txt;
                }   
            }
            if(isset($tur_id)){
                $tur = array_filter($turler, function($tur) use ($tur_id) {
                    return (int)$tur['id'] === (int)$tur_id;
                });
                
                if (!empty($tur)) {
                    $tur_txt = current($tur)['tur']; 
                    $data[$tur_index] = $tur_txt;
                }       
            }

            if(isset($numune_id)){
                switch ($numune_id) {
                    case 0:
                        $data[$numune_index] = "Yok";
                        break;
                    case 1:
                        $data[$numune_index] = "Var";
                        break;
                    default:
                        $data[$numune_index] = "";
                }    
            }
        }
        unset($data);

        $xlsx = Shuchkin\SimpleXLSXGen::fromArray( $excel_data );
        $tarih = date('dmY');
        $xlsx->downloadAs("siparisler-{$tarih}.xlsx"); // or downloadAs('books.xlsx') or $xlsx_content = (string) $xlsx 

        exit;
}

#musteri ekle
if(isset($_POST['musteri_ekle']))
{

    $firma_id           = $_SESSION['firma_id'];
    $marka              = ucwords(trim($_POST['marka']));
    $firma_unvani       = ucwords(trim($_POST['firma_unvani']));
    //$adresi             = trim($_POST['adresi']);
    //$ilce_id            = $_POST['ilce_id'];
    //$sehir_id           = $_POST['sehir_id'];
    //$ulke_id            = $_POST['ulke_id'];
    $sektor_id          = $_POST['sektor_id'];
    $cep_tel            = trim($_POST['cep_tel']);
    $sabit_hat          = trim($_POST['sabit_hat']);
    $e_mail             = strtolower(trim($_POST['e_mail']));
    $vergi_dairesi      = trim($_POST['vergi_dairesi']);
    $vergi_numarasi     = trim($_POST['vergi_numarasi']);
    $vade               = trim($_POST['vade']);
    $musteri_temsilcisi_id = $_POST['musteri_temsilcisi_id'];

    try {
        $conn->beginTransaction();

        // Öncelikle müşteri bilgilerini kaydedelim
        $sql = "INSERT INTO musteri(firma_id, marka, firma_unvani, sektor_id, 
            cep_tel, sabit_hat, e_mail, vergi_dairesi, vergi_numarasi, musteri_temsilcisi_id, vade) 
            VALUES(:firma_id, :marka, :firma_unvani, :sektor_id, 
            :cep_tel, :sabit_hat, :e_mail, :vergi_dairesi, :vergi_numarasi, :musteri_temsilcisi_id, :vade)";

        $sth = $conn->prepare($sql);
        $sth->execute([
            ":firma_id" => $firma_id,
            ":marka" => $marka,
            ":firma_unvani" => $firma_unvani,
            ":sektor_id" => $sektor_id,
            ":cep_tel" => $cep_tel,
            ":sabit_hat" => $sabit_hat,
            ":e_mail" => $e_mail,
            ":vergi_dairesi" => $vergi_dairesi,
            ":vergi_numarasi" => $vergi_numarasi,
            ":musteri_temsilcisi_id" => $musteri_temsilcisi_id,
            ":vade" => $vade
        ]);

        // Yeni eklenen müşterinin ID'sini alalım
        $musteri_id = $conn->lastInsertId();

        // Adresleri ekleyelim
        if( isset($_POST['adresler']) && is_array($_POST['adresler']) ) {
            $adres_sql = "INSERT INTO musteri_adresleri (musteri_id, baslik, ulke_id, sehir_id, ilce_id, adres, adres_turu, is_default) VALUES (:musteri_id, :baslik, :ulke_id, :sehir_id, :ilce_id, :adres, :adres_turu, :is_default)";
            $adres_stmt = $conn->prepare($adres_sql);

            foreach($_POST['adresler'] as $key => $adres) {
                $adres_baslik = $adres['baslik'];
                $adres_ulke_id = $adres['ulke_id'];
                $adres_sehir_id = $adres['sehir_id'];
                $adres_ilce_id = $adres['ilce_id'];
                $adres_text = trim($adres['adres']);
                $adres_turu = trim($adres['adres_turu']);
                $is_default = 0;
                if ($adres_turu == 'Merkez' && isset($_POST['default_adres_merkez']) && $_POST['default_adres_merkez'] == $key) {
                    $is_default = 1;
                    // Eğer bu adres varsayılan Merkez ise, bilgileri saklayalım
                    $default_merkez_address = [
                        'baslik' => $adres_baslik,
                        'adres_text' => $adres_text,
                        'ulke_id' => $adres_ulke_id,
                        'sehir_id' => $adres_sehir_id,
                        'ilce_id' => $adres_ilce_id
                    ];
                } else if ($adres_turu == 'Sevk' && isset($_POST['default_adres_sevk']) && $_POST['default_adres_sevk'] == $key) {
                    $is_default = 1;
                }

                if (count($_POST['adresler']) < 2) { //Sadece 1 adres geliyor ise
                    $is_default = 1;
                }

                $adres_stmt->execute([
                    ":baslik" => $adres_baslik,
                    ":musteri_id" => $musteri_id,
                    ":ulke_id" => $adres_ulke_id,
                    ":sehir_id" => $adres_sehir_id,
                    ":ilce_id" => $adres_ilce_id,
                    ":adres" => $adres_text,
                    ":adres_turu" => $adres_turu,
                    ":is_default" => $is_default
                ]);

                // Eğer bu adres "varsayılan" olarak seçilmişse, müşteri tablosunu güncelleyelim
                if (count($_POST['adresler']) > 1) {
                    if ( $is_default == 1 && $adres_turu == 'Merkez' ) {
                        $update_sql = "UPDATE musteri SET adresi = :adresi, ulke_id = :ulke_id, sehir_id = :sehir_id, ilce_id = :ilce_id WHERE id = :musteri_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->execute([
                            ":adresi" => $adres_text,
                            ":ulke_id" => $adres_ulke_id,
                            ":sehir_id" => $adres_sehir_id,
                            ":ilce_id" => $adres_ilce_id,
                            ":musteri_id" => $musteri_id
                        ]);
                    }
                } else {
                    $update_sql = "UPDATE musteri SET adresi = :adresi, ulke_id = :ulke_id, sehir_id = :sehir_id, ilce_id = :ilce_id WHERE id = :musteri_id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->execute([
                        ":adresi" => $adres_text,
                        ":ulke_id" => $adres_ulke_id,
                        ":sehir_id" => $adres_sehir_id,
                        ":ilce_id" => $adres_ilce_id,
                        ":musteri_id" => $musteri_id
                    ]);
                }
            }
        }

        // Yetkilileri ekleyelim
        if(isset($_POST['yetkililer']) && is_array($_POST['yetkililer'])) {
            $yetkili_sql = "INSERT INTO musteri_yetkilileri (musteri_id, yetkili_adi, yetkili_cep, yetkili_mail, yetkili_gorev, yetkili_aciklama, is_default) 
                            VALUES (:musteri_id, :yetkili_adi, :yetkili_cep, :yetkili_mail, :yetkili_gorev, :yetkili_aciklama, :is_default)";
            $yetkili_stmt = $conn->prepare($yetkili_sql);

            foreach($_POST['yetkililer'] as $key => $yetkili) {
                $yetkili_adi = trim($yetkili['adi']);
                $yetkili_cep = trim($yetkili['cep']);
                $yetkili_mail = strtolower(trim($yetkili['mail']));
                $yetkili_gorev = trim($yetkili['gorev']);
                $yetkili_aciklama = isset($yetkili['aciklama']) ? trim($yetkili['aciklama']) : '';
                $is_default = (isset($_POST['default_yetkili']) && $_POST['default_yetkili'] == $key) ? 1 : 0;

                $yetkili_stmt->execute([
                    ":musteri_id" => $musteri_id,
                    ":yetkili_adi" => $yetkili_adi,
                    ":yetkili_cep" => $yetkili_cep,
                    ":yetkili_mail" => $yetkili_mail,
                    ":yetkili_gorev" => $yetkili_gorev,
                    ":yetkili_aciklama" => $yetkili_aciklama,
                    ":is_default" => $is_default
                ]);

                // Eğer bu yetkili "varsayılan" olarak seçilmişse, müşteri tablosunu güncelleyelim
                if (count($_POST['yetkililer']) > 1) {
                    if($is_default) {
                        $update_sql = "UPDATE musteri SET yetkili_adi = :yetkili_adi, yetkili_cep = :yetkili_cep, 
                                       yetkili_mail = :yetkili_mail, yetkili_gorev = :yetkili_gorev, aciklama = :aciklama 
                                       WHERE id = :musteri_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->execute([
                            ":yetkili_adi" => $yetkili_adi,
                            ":yetkili_cep" => $yetkili_cep,
                            ":yetkili_mail" => $yetkili_mail,
                            ":yetkili_gorev" => $yetkili_gorev,
                            ":aciklama" => $yetkili_aciklama,
                            ":musteri_id" => $musteri_id
                        ]);
                    }
                } else {
                    $update_sql = "UPDATE musteri SET yetkili_adi = :yetkili_adi, yetkili_cep = :yetkili_cep, 
                                       yetkili_mail = :yetkili_mail, yetkili_gorev = :yetkili_gorev, aciklama = :aciklama 
                                       WHERE id = :musteri_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->execute([
                            ":yetkili_adi" => $yetkili_adi,
                            ":yetkili_cep" => $yetkili_cep,
                            ":yetkili_mail" => $yetkili_mail,
                            ":yetkili_gorev" => $yetkili_gorev,
                            ":aciklama" => $yetkili_aciklama,
                            ":musteri_id" => $musteri_id
                        ]);
                }
                
            }
        }

        // İşlemler başarılıysa commit yapalım
        $conn->commit();

        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
        header('Location: /index.php?url=musteriler');
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız: ' . $e->getMessage();
        header('Location: /index.php?url=musteri_ekle');
    }

    die();

    /*
    $firma_id           = $_SESSION['firma_id'];
    $marka              = ucwords(trim($_POST['marka']));
    $firma_unvani       = ucwords(trim($_POST['firma_unvani']));
    $adresi             = trim($_POST['adresi']);
    $ilce_id            = $_POST['ilce_id'];
    $sehir_id           = $_POST['sehir_id'];
    $ulke_id            = $_POST['ulke_id'];
    $sektor_id          = $_POST['sektor_id'];
    $cep_tel            = trim($_POST['cep_tel']);
    $sabit_hat          = trim($_POST['sabit_hat']);
    $e_mail             = strtolower(trim($_POST['e_mail']));
    $yetkili_adi        = trim($_POST['yetkili_adi']);
    $yetkili_cep        = trim($_POST['yetkili_cep']);
    $yetkili_mail       = strtolower(trim($_POST['yetkili_mail']));
    $yetkili_gorev      = trim($_POST['yetkili_gorev']);
    $aciklama           = trim($_POST['aciklama']);
    $vergi_dairesi      = trim($_POST['vergi_dairesi']);
    $vergi_numarasi     = trim($_POST['vergi_numarasi']);
    $musteri_temsilcisi_id = $_POST['musteri_temsilcisi_id'];

    $sql = "INSERT INTO musteri(firma_id, marka, firma_unvani, adresi, ilce_id, sehir_id, ulke_id, sektor_id, 
        cep_tel, sabit_hat, e_mail, yetkili_adi, yetkili_cep, yetkili_mail, yetkili_gorev, aciklama, 
        vergi_dairesi, vergi_numarasi, musteri_temsilcisi_id) 
        VALUES(:firma_id, :marka, :firma_unvani, :adresi, :ilce_id, :sehir_id, :ulke_id, :sektor_id, 
        :cep_tel, :sabit_hat, :e_mail, :yetkili_adi, :yetkili_cep, :yetkili_mail, :yetkili_gorev, 
        :aciklama, :vergi_dairesi, :vergi_numarasi, :musteri_temsilcisi_id);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("marka", $marka);
    $sth->bindParam("firma_unvani", $firma_unvani);
    $sth->bindParam("adresi", $adresi);
    $sth->bindParam("ilce_id", $sehir_id);
    $sth->bindParam("sehir_id", $sehir_id);
    $sth->bindParam("ulke_id", $ulke_id);
    $sth->bindParam("sektor_id", $sektor_id);
    $sth->bindParam("cep_tel", $cep_tel);
    $sth->bindParam("sabit_hat", $sabit_hat);
    $sth->bindParam("e_mail", $e_mail);
    $sth->bindParam("yetkili_adi", $yetkili_adi);
    $sth->bindParam("yetkili_cep", $yetkili_cep);
    $sth->bindParam("yetkili_mail", $yetkili_mail);
    $sth->bindParam("yetkili_gorev", $yetkili_gorev);
    $sth->bindParam("aciklama", $aciklama);
    $sth->bindParam("vergi_dairesi", $vergi_dairesi);
    $sth->bindParam("vergi_numarasi", $vergi_numarasi);
    $sth->bindParam("musteri_temsilcisi_id", $musteri_temsilcisi_id);
    $durum = $sth->execute();

    if($durum == true)
    {
        #echo "<h2>Ekleme başarılı</h2>";
        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
        header('Location: musteriler.php');
    }
    else 
    {
        #echo "<h2>ekleme başarısız</h2>";
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
        header('Location: musteri_ekle.php');
    }
    
    die();
    */

}

#musteri guncelle
if( isset($_POST['musteri_guncelle']) ) {

    $id                 = $_POST['id'];
    $firma_id           = $_SESSION['firma_id'];
    $marka              = ucwords(trim($_POST['marka']));
    $firma_unvani       = ucwords(trim($_POST['firma_unvani']));
    //$adresi             = trim($_POST['adresi']);
    //$ilce_id            = $_POST['ilce_id'];
    //$sehir_id           = $_POST['sehir_id'];
    //$ulke_id            = $_POST['ulke_id'];
    $sektor_id          = $_POST['sektor_id'];
    $cep_tel            = trim($_POST['cep_tel']);
    $sabit_hat          = trim($_POST['sabit_hat']);
    $e_mail             = strtolower(trim($_POST['e_mail']));
    $vergi_dairesi      = trim($_POST['vergi_dairesi']);
    $vergi_numarasi     = trim($_POST['vergi_numarasi']);
    $vade               = trim($_POST['vade']);
    $musteri_temsilcisi_id = $_POST['musteri_temsilcisi_id'];

    try {
        $conn->beginTransaction();

        // Müşteri bilgilerini güncelleyelim
        $sql = "UPDATE musteri SET marka=:marka, firma_unvani=:firma_unvani, sektor_id=:sektor_id, cep_tel=:cep_tel, sabit_hat=:sabit_hat, 
            e_mail=:e_mail, vergi_dairesi=:vergi_dairesi, vergi_numarasi=:vergi_numarasi, 
            musteri_temsilcisi_id=:musteri_temsilcisi_id, vade=:vade WHERE id=:id AND firma_id=:firma_id";

        $sth = $conn->prepare($sql);
        $sth->execute([
            ":marka" => $marka,
            ":firma_unvani" => $firma_unvani,
            ":sektor_id" => $sektor_id,
            ":cep_tel" => $cep_tel,
            ":sabit_hat" => $sabit_hat,
            ":e_mail" => $e_mail,
            ":vergi_dairesi" => $vergi_dairesi,
            ":vergi_numarasi" => $vergi_numarasi,
            ":musteri_temsilcisi_id" => $musteri_temsilcisi_id,
            ":id" => $id,
            ":firma_id" => $firma_id,
            ":vade" => $vade
        ]);
    
        // Adresleri güncelleyelim
        if( isset($_POST['adresler']) && is_array($_POST['adresler']) ) {
            
            // Önce varsayılan adres bilgilerini sıfırlayalım
            $reset_default_sql = "UPDATE musteri_adresleri SET is_default = 0 WHERE musteri_id = :musteri_id";
            $reset_default_stmt = $conn->prepare($reset_default_sql);
            $reset_default_stmt->bindParam(':musteri_id', $id);
            $reset_default_stmt->execute();
            
            // Sadece Merkez olmayan adresleri silelim
            $delete_sql = "DELETE FROM musteri_adresleri WHERE musteri_id = :musteri_id AND adres_turu != 'Merkez'";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bindParam(':musteri_id', $id);
            $delete_stmt->execute(); 

            // Sonra yeni adresleri ekleyelim
            $adres_sql = "INSERT INTO musteri_adresleri (musteri_id, baslik, ulke_id, sehir_id, ilce_id, adres, adres_turu, is_default) VALUES (:musteri_id, :baslik, :ulke_id, :sehir_id, :ilce_id, :adres, :adres_turu, :is_default)";
            $adres_stmt = $conn->prepare($adres_sql);

            // Varsayılan merkez adresi için değişken
            $default_merkez_address = null;
            
            // Adres türüne göre varsayılan adres indeksleri
            $default_merkez_index = isset($_POST['default_adres_merkez']) ? $_POST['default_adres_merkez'] : null;
            $default_sevk_index = isset($_POST['default_adres_sevk']) ? $_POST['default_adres_sevk'] : null;
            
            // Önce tüm adresleri işleyelim
            foreach($_POST['adresler'] as $key => $adres) {

                $adres_baslik = $adres['baslik'];
                $adres_ulke_id = $adres['ulke_id'];
                $adres_sehir_id = $adres['sehir_id'];
                $adres_ilce_id = $adres['ilce_id'];
                $adres_text = trim($adres['adres']);
                $adres_turu = trim($adres['adres_turu']);
                
                // Varsayılan adres kontrolü - adres türüne göre kontrol et
                $is_default = 0;
                if ($adres_turu == 'Merkez' && $default_merkez_index == $key) {
                    $is_default = 1;
                    // Eğer bu adres varsayılan Merkez ise, bilgileri saklayalım
                    $default_merkez_address = [
                        'baslik' => $adres_baslik,
                        'adres_text' => $adres_text,
                        'ulke_id' => $adres_ulke_id,
                        'sehir_id' => $adres_sehir_id,
                        'ilce_id' => $adres_ilce_id
                    ];
                } else if ($adres_turu == 'Sevk' && $default_sevk_index == $key) {
                    $is_default = 1;
                }

                // Önce bu adres türünde mevcut bir kayıt var mı kontrol et
                $check_sql = "SELECT id FROM musteri_adresleri WHERE musteri_id = :musteri_id AND adres_turu = :adres_turu";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bindParam(':musteri_id', $id);
                $check_stmt->bindParam(':adres_turu', $adres_turu);

                $check_stmt->execute();
                $existing_adres = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_adres && $adres_turu == 'Merkez') {
                    // Merkez adresi güncelle
                    $update_sql = "UPDATE musteri_adresleri SET
                        baslik = :baslik,
                        ulke_id = :ulke_id,
                        sehir_id = :sehir_id,
                        ilce_id = :ilce_id,
                        adres = :adres,
                        is_default = :is_default
                    WHERE id = :adres_id";

                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(':baslik', $adres_baslik);
                    $update_stmt->bindParam(':ulke_id', $adres_ulke_id);
                    $update_stmt->bindParam(':sehir_id', $adres_sehir_id);
                    $update_stmt->bindParam(':ilce_id', $adres_ilce_id);
                    $update_stmt->bindParam(':adres', $adres_text);
                    $update_stmt->bindParam(':is_default', $is_default);
                    $update_stmt->bindParam(':adres_id', $existing_adres['id']);
                    $update_stmt->execute();
                } else {
                    // Yeni adres ekle
                    $insert_stmt = $adres_stmt;
                    $insert_stmt->execute([
                        ":musteri_id" => $id,
                        ":baslik" => $adres_baslik,
                        ":ulke_id" => $adres_ulke_id,
                        ":sehir_id" => $adres_sehir_id,
                        ":ilce_id" => $adres_ilce_id,
                        ":adres" => $adres_text,
                        ":adres_turu" => $adres_turu,
                        ":is_default" => $is_default
                    ]);
                }
            }
            

            // Eğer varsayılan merkez adresi varsa, müşteri tablosunu güncelleyelim
            if($default_merkez_address) {
                $update_sql = "UPDATE musteri SET adresi = :adresi, ulke_id = :ulke_id, sehir_id = :sehir_id, ilce_id = :ilce_id WHERE id = :musteri_id";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->execute([
                    ":adresi" => $default_merkez_address['adres_text'],
                    ":ulke_id" => $default_merkez_address['ulke_id'],
                    ":sehir_id" => $default_merkez_address['sehir_id'],
                    ":ilce_id" => $default_merkez_address['ilce_id'],
                    ":musteri_id" => $id
                ]);
            }

        }

        // Yetkilileri güncelleyelim
        if( isset($_POST['yetkililer']) && is_array($_POST['yetkililer']) ) {

            // Formdan gelen yetkilileri al
            $gelen_yetkililer = isset($_POST['yetkililer']) ? $_POST['yetkililer'] : [];

            // Öncelikle mevcut yetkilileri çekelim
            $mevcut_yetkililer = [];
            $query = $conn->prepare("SELECT id FROM musteri_yetkilileri WHERE musteri_id = :musteri_id");
            $query->execute([":musteri_id" => $id]);
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $mevcut_yetkililer[] = $row['id'];
            }

            // Güncellenmiş veya eklenmiş olan yetkili ID'lerini takip edelim
            $guncellenen_yetkililer = [];

            // Gelen yetkililer üzerinde işlem yap
            foreach ($gelen_yetkililer as $key => $yetkili) {
                $yetkili_adi = trim($yetkili['adi']);
                $yetkili_cep = trim($yetkili['cep']);
                $yetkili_mail = strtolower(trim($yetkili['mail']));
                $yetkili_gorev = trim($yetkili['gorev']);
                $yetkili_aciklama = isset($yetkili['aciklama']) ? trim($yetkili['aciklama']) : '';
                $is_default = (isset($_POST['default_yetkili']) && $_POST['default_yetkili'] == $key) ? 1 : 0;

                if (!empty($yetkili['id']) && in_array($yetkili['id'], $mevcut_yetkililer)) {
                    // Güncelleme işlemi
                    $update_sql = "UPDATE musteri_yetkilileri 
                                   SET yetkili_adi = :yetkili_adi, yetkili_cep = :yetkili_cep, yetkili_mail = :yetkili_mail, 
                                       yetkili_gorev = :yetkili_gorev, yetkili_aciklama = :yetkili_aciklama, is_default = :is_default 
                                   WHERE id = :yetkili_id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->execute([
                        ":yetkili_adi" => $yetkili_adi,
                        ":yetkili_cep" => $yetkili_cep,
                        ":yetkili_mail" => $yetkili_mail,
                        ":yetkili_gorev" => $yetkili_gorev,
                        ":yetkili_aciklama" => $yetkili_aciklama,
                        ":is_default" => $is_default,
                        ":yetkili_id" => $yetkili['id']
                    ]);

                    $guncellenen_yetkililer[] = $yetkili['id'];
                } else {
                    // Yeni ekleme işlemi
                    $insert_sql = "INSERT INTO musteri_yetkilileri (musteri_id, yetkili_adi, yetkili_cep, yetkili_mail, yetkili_gorev, yetkili_aciklama, is_default) 
                                   VALUES (:musteri_id, :yetkili_adi, :yetkili_cep, :yetkili_mail, :yetkili_gorev, :yetkili_aciklama, :is_default)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->execute([
                        ":musteri_id" => $id,
                        ":yetkili_adi" => $yetkili_adi,
                        ":yetkili_cep" => $yetkili_cep,
                        ":yetkili_mail" => $yetkili_mail,
                        ":yetkili_gorev" => $yetkili_gorev,
                        ":yetkili_aciklama" => $yetkili_aciklama,
                        ":is_default" => $is_default
                    ]);

                    $guncellenen_yetkililer[] = $conn->lastInsertId();
                }

                // Eğer bu yetkili "varsayılan" olarak seçilmişse, müşteri tablosunu güncelle
                if ($is_default) {
                    $update_musteri_sql = "UPDATE musteri SET yetkili_adi = :yetkili_adi, yetkili_cep = :yetkili_cep, 
                                           yetkili_mail = :yetkili_mail, yetkili_gorev = :yetkili_gorev, aciklama = :aciklama 
                                           WHERE id = :musteri_id";
                    $update_musteri_stmt = $conn->prepare($update_musteri_sql);
                    $update_musteri_stmt->execute([
                        ":yetkili_adi" => $yetkili_adi,
                        ":yetkili_cep" => $yetkili_cep,
                        ":yetkili_mail" => $yetkili_mail,
                        ":yetkili_gorev" => $yetkili_gorev,
                        ":aciklama" => $yetkili_aciklama,
                        ":musteri_id" => $id
                    ]);
                }
            }

            // **Silme işlemi:** Formda olmayanları veritabanından kaldır
            $yetkili_sil_sql = "DELETE FROM musteri_yetkilileri WHERE musteri_id = :musteri_id AND id NOT IN (" . implode(',', $guncellenen_yetkililer) . ")";
            $yetkili_sil_stmt = $conn->prepare($yetkili_sil_sql);
            $yetkili_sil_stmt->execute([":musteri_id" => $id]);

        }

        $conn->commit();

        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header('Location: /index.php?url=musteriler');

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız: ' . $e->getMessage();
        header('Location: /index.php?url=musteri_guncelle&id=' . $id);
    }

    die();
}

#musteri sil
if(isset($_GET['islem']) && $_GET['islem'] == 'musteri_sil')
{
    $id = $_GET['id'];

    $sql = "DELETE FROM musteri WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    
    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
        header('Location: /index.php?url=musteriler');
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
        header('Location: /index.php?url=musteriler');
    }
    die();
}
