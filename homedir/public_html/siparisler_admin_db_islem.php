<?php 
require_once "include/oturum_kontrol.php";
require_once "include/helper.php";

#siparis onay
if(isset($_POST['siparis_onay']))
{
    $storage = new DreamHostStorage($conn);
    
    try {
        //echo "<pre>"; print_r($_POST);
        //echo "<pre>"; print_r($_FILES); exit;

        $siparis_id = strip_tags($_POST['siparis_id']);
        $sth = $conn->prepare('SELECT id,tip_id, musteri_id,veriler, isin_adi, tur_id, adet, birim_id, 
        teslimat_adresi, ulke_id, sehir_id, ilce_id, termin, uretim, vade, fiyat, para_cinsi, 
        odeme_sekli_id, numune, aciklama, musteri_temsilcisi_id, paketleme,nakliye, islem, durum
        FROM siparisler WHERE id=:id AND firma_id = :firma_id');
        $sth->bindParam('id', $siparis_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $siparis = $sth->fetch(PDO::FETCH_ASSOC);
        
        $siparis_id             = $_POST['siparis_id'];

        $musteri_id             = $_POST['musteri_id'];
        $firma_id               = $_SESSION['firma_id'];
        $veriler                = [];
        $tip_id                 = $siparis['tip_id']; //$_POST['tip'];
        $isin_adi               = $_POST['isin_adi'];
        $tur_id                 = $siparis['tur_id']; //$_POST['tur_id'];
        $sip_islem              = $siparis['islem']; // Mevcut değeri koru (formdan gelmiyor)
        $sip_durum              = isset($_POST['durum']) && $_POST['durum'] === 'on' ? 1 : 0;

        if ( $_POST['teslimat_adresi'] == 0 ) { //Eski siparişler için adres_id yok ise
            $teslimat_adresi        = $_POST['teslimat_adresi_text'];
        } else {
            $teslimat_adresi = $_POST['teslimat_adresi'];
        }

        $ulke_id                = $_POST['ulke_id'];
        $sehir_id               = $_POST['sehir_id'];
        $ilce_id                = $_POST['ilce_id'];
        $termin                 = $_POST['termin'];
        $uretim                 = $_POST['uretim'];
        $vade                   = $_POST['vade'];
        $odeme_sekli_id         = $_POST['odeme_sekli_id'];
        $odeme_sekli_id         = $_POST['odeme_sekli_id'];
        $musteri_temsilcisi_id  = $_POST['musteri_temsilcisi_id'];
        $paketleme              = $_POST['paketleme'];
        $nakliye                = $_POST['nakliye'];
        $alt_urun_sayisi        = $_POST['alt_urun_sayisi'];
        $onaylayan_personel_id  = $_SESSION['personel_id'];

        
        if($tip_id == GRUP_URUN_AYRI_FIYAT){  
            $fiyat      = 0;
            $adet       = intval($_POST['grup_ayri_fiyat_adet']);
            $birim_id   = $_POST['grup_ayri_fiyat_birim_id'];
            $para_cinsi = $_POST['grup_ayri_fiyat_para_cinsi'];

            for($i = 1; $i <= $alt_urun_sayisi; $i++){
                $veriler[] = $_POST["grup_ayri_fiyat_alt_urun_{$i}"];
                $fiyat      += intval($_POST["grup_ayri_fiyat_alt_urun_{$i}"]['miktar']) * floatval($_POST["grup_ayri_fiyat_alt_urun_{$i}"]['birim_fiyat']);
            }

            //$tur_id = $_POST['tur_id'];
        }
        else if($tip_id == GRUP_URUN_TEK_FIYAT){
            $adet       = intval($_POST['grup_tek_fiyat_adet']);
            $birim_id   = $_POST['grup_tek_fiyat_birim_id'];
            $para_cinsi = $_POST['grup_tek_fiyat_para_cinsi'];
            $fiyat      = intval($_POST['grup_tek_fiyat_adet'])*floatval($_POST['grup_tek_fiyat_birim_fiyat']);
            for($i = 1; $i <= $alt_urun_sayisi; $i++){
                $_POST["grup_tek_fiyat_alt_urun_{$i}"]['kdv']           = $_POST['grup_tek_fiyat_kdv'];
                $_POST["grup_tek_fiyat_alt_urun_{$i}"]['birim_fiyat']   = floatval($_POST['grup_tek_fiyat_birim_fiyat']);
                $veriler[]                                              = $_POST["grup_tek_fiyat_alt_urun_{$i}"];
            }
        }else if($tip_id == TEK_URUN){

            $tur_id = $_POST['tek_fiyat_tur_id'];

            $adet       = intval($_POST['tek_fiyat_adet']);
            $birim_id   = $_POST['tek_fiyat_birim_id'];
            $para_cinsi = $_POST['tek_fiyat_para_cinsi'];
            $fiyat      = intval($_POST['tek_fiyat_adet'])*floatval($_POST['tek_fiyat_birim_fiyat']);
            $veriler = [
                "kdv"           => $_POST['tek_fiyat_kdv'],
                "tur"           => $_POST['tek_fiyat_tur_id'],
                "form"          => isset($_POST['tek_fiyat_form']) ? $_POST['tek_fiyat_form'] : null,
                'isim'          => $_POST['isin_adi'],
                'miktar'        => $adet,
                'numune'        => $_POST['tek_fiyat_numune'],
                'aciklama'      => $_POST['tek_fiyat_aciklama'],
                'birim_id'      => $birim_id,
                'birim_fiyat'   => $_POST['tek_fiyat_birim_fiyat']
            ];
        }

        $sql = "UPDATE siparisler SET veriler = :veriler, tip_id = :tip_id, isin_adi = :isin_adi,tur_id = :tur_id,
                adet = :adet, birim_id = :birim_id, teslimat_adresi = :teslimat_adresi,ulke_id = :ulke_id, sehir_id = :sehir_id,
                ilce_id = :ilce_id,termin = :termin, uretim = :uretim, vade = :vade, fiyat = :fiyat,
                nakliye = :nakliye, paketleme = :paketleme, para_cinsi = :para_cinsi,  odeme_sekli_id = :odeme_sekli_id,numune = :numune,
                musteri_temsilcisi_id = :musteri_temsilcisi_id,  onaylayan_personel_id = :onaylayan_personel_id, onay_baslangic_durum = 'evet', islem = :islem, durum = :durum
                WHERE id = :id AND firma_id = :firma_id;";

$numune = 1;

        $sth = $conn->prepare($sql);
        $sth->bindValue("veriler", json_encode($veriler));
        $sth->bindValue("tip_id", $tip_id);
        $sth->bindParam('isin_adi', $isin_adi);
        $sth->bindParam('tur_id', $tur_id);
        $sth->bindParam("adet", $adet);
        $sth->bindParam("birim_id", $birim_id);
        $sth->bindParam("teslimat_adresi", $teslimat_adresi);
        $sth->bindParam("ulke_id", $ulke_id);
        $sth->bindParam("sehir_id", $sehir_id);
        $sth->bindParam("ilce_id", $ilce_id);
        $sth->bindParam("termin", $termin);
        $sth->bindParam("uretim", $uretim);
        $sth->bindParam("vade", $vade);
        $sth->bindParam("fiyat", $fiyat);
        $sth->bindParam("para_cinsi", $para_cinsi);
        $sth->bindParam("odeme_sekli_id", $odeme_sekli_id);
        $sth->bindParam("numune", $numune);
        $sth->bindParam("musteri_temsilcisi_id", $musteri_temsilcisi_id);
        $sth->bindParam('nakliye', $nakliye);
        $sth->bindParam('paketleme', $paketleme);
        $sth->bindParam('onaylayan_personel_id', $onaylayan_personel_id);
        $sth->bindParam('islem', $sip_islem);
        $sth->bindParam('durum', $sip_durum);
        $sth->bindParam('id', $siparis_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);

        $durum = $sth->execute();

        //Planlaması varsa Üretilecek Adeti Güncelle (Sipariş Tekrarda Sıktı Oluyor)
        if($tip_id == TEK_URUN){
            $adet   = intval($_POST['tek_fiyat_adet']);
            $sql    = "UPDATE planlama SET uretilecek_adet = :uretilecek_adet WHERE siparis_id = :siparis_id";
            $sth    = $conn->prepare($sql);
            $sth->bindParam('uretilecek_adet', $adet);
            $sth->bindParam('siparis_id', $siparis_id);
            $sth->execute();
        }else if(in_array($tip_id, [GRUP_URUN_TEK_FIYAT, GRUP_URUN_AYRI_FIYAT])){
            for($alt_urun_index = 1; $alt_urun_index <= $alt_urun_sayisi; $alt_urun_index++){
                $adet   = $tip_id ==  GRUP_URUN_TEK_FIYAT ? 
                        intval($_POST["grup_tek_fiyat_alt_urun_{$alt_urun_index}"]['miktar']) :
                        intval($_POST["grup_ayri_fiyat_alt_urun_{$alt_urun_index}"]['miktar']);
                $sql    = "UPDATE planlama SET uretilecek_adet = :uretilecek_adet WHERE siparis_id = :siparis_id AND alt_urun_id = :alt_urun_id";
                $sth    = $conn->prepare($sql);
                $sth->bindParam('uretilecek_adet', $adet);
                $sth->bindParam('siparis_id', $siparis_id);
                $sth->bindParam('alt_urun_id', $alt_urun_index);
                $sth->execute();
            }
        }

        if($durum)
        {
            $sql = "SELECT * FROM siparisler WHERE id = :id";
            $sth = $conn->prepare($sql);
            $sth->bindParam('id', $siparis_id);
            $sth->execute();
            $siparis_eski_hali = $sth->fetch(PDO::FETCH_ASSOC);

            $sql = "INSERT INTO siparis_log(siparis_id, personel_id, islem, onceki_degerler, sonraki_degerler) 
                VALUES(:siparis_id, :personel_id, :islem, :onceki_degerler, :sonraki_degerler);";
            $sth = $conn->prepare($sql);
            $sth->bindParam("siparis_id", $siparis_id);
            $sth->bindParam("personel_id", $_SESSION['personel_id']);
            $sth->bindValue('islem', 'Güncelleme');
            $sth->bindValue('onceki_degerler', json_encode($siparis_eski_hali));
            $sth->bindValue('sonraki_degerler', json_encode($_POST));
            $durum = $sth->execute();

            $hedef_klasor = "dosyalar/siparisler/";
            if(in_array($tip_id, [GRUP_URUN_AYRI_FIYAT, GRUP_URUN_TEK_FIYAT]) ){
                for($alt_urun_index = 1; $alt_urun_index <= $alt_urun_sayisi; $alt_urun_index++){
                    
                    $dosyalar = $tip_id == GRUP_URUN_AYRI_FIYAT ? 
                                $_FILES["grup_ayri_fiyat_alt_urun_{$alt_urun_index}"] :
                                $_FILES["grup_tek_fiyat_alt_urun_{$alt_urun_index}"];
                    
                        
                    for($i = 0; $i < count($dosyalar['name']); $i++)
                    {
                        $dosya_adi     = pathinfo($dosyalar['name'][$i], PATHINFO_FILENAME)."_".random_int(1000, 99999);
                        $dosya_uzanti  = pathinfo($dosyalar['name'][$i], PATHINFO_EXTENSION);
        
                        $dosya_adi = "{$dosya_adi}.{$dosya_uzanti}";

                        $result = $storage->uploadFileToS3('siparisler', $dosyalar["tmp_name"][$i], $dosya_adi);
        
                        if ($result) 
                        {
                            $sql = "INSERT INTO  siparis_dosyalar(siparis_id, alt_urun_index, ad) 
                                    VALUES(:siparis_id, :alt_urun_index, :ad)";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam("siparis_id", $siparis_id);
                            $sth->bindValue("alt_urun_index", $alt_urun_index-1);
                            $sth->bindParam("ad", $dosya_adi);
                            $durum = $sth->execute();
                        }
                    }
        
                }
            } else if($tip_id == TEK_URUN){
                if(isset($_FILES['tek_fiyat_dosya'])){
                    $dosyalar = $_FILES['tek_fiyat_dosya'];
                    for($i = 0; $i < count($dosyalar['name']); $i++)
                    {
                        $dosya_adi      = pathinfo($dosyalar['name'][$i], PATHINFO_FILENAME)."_".random_int(1000, 99999);
                        $dosya_uzanti  = pathinfo($dosyalar['name'][$i], PATHINFO_EXTENSION);
        
                        $dosya_adi = "{$dosya_adi}.{$dosya_uzanti}";

                        $result = $storage->uploadFileToS3('siparisler', $dosyalar["tmp_name"][$i], $dosya_adi);
        
                        if ($result) 
                        {
                            $sql = "INSERT INTO  siparis_dosyalar(siparis_id, alt_urun_index, ad) 
                                    VALUES(:siparis_id, :alt_urun_index, :ad)";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam("siparis_id", $siparis_id);
                            $sth->bindValue("alt_urun_index", 0);
                            $sth->bindParam("ad", $dosya_adi);
                            $durum = $sth->execute();
                        }
                    }
                }
            }
        }

        if($durum == true)
        {
            $_SESSION['durum'] = 'success';
            $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
            header("Location: /index.php?url=siparisler_onay"); 
        }
        else 
        {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
            header("Location: /index.php?url=siparisler_admin_kontrol&siparis_id={$siparis_id}");
            
        }
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}



#siparis iptal
if(isset($_GET['islem']) && $_GET['islem'] == 'iptal')
{
    $onaylayan_personel_id  = $_SESSION['personel_id'];
    $siparis_id             = intval($_GET['siparis_id']);

    $sql = "UPDATE siparisler SET islem = 'iptal', onaylayan_personel_id = :onaylayan_personel_id  WHERE id = :id AND firma_id =:firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('onaylayan_personel_id', $onaylayan_personel_id);
    $sth->bindParam('id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();

    if($durum)
    {
        $sql = "INSERT INTO siparis_log(personel_id, siparis_id, islem) VALUES(:personel_id, :siparis_id, 'Sipariş İptal İşlemi')";
        $sth = $conn->prepare($sql);
        $sth->bindParam("personel_id", $onaylayan_personel_id);
        $sth->bindParam("siparis_id", $siparis_id);
        $durum = $sth->execute();
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'İptal İşlemi Başarılı';  
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'İptal İşlemi Başarısız';
    }
    header("Location: /index.php?url=siparisler_onay");
    die();
}