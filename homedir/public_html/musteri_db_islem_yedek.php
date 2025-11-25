<?php

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);
require_once "include/SimpleXLSXGen.php";

#print_r($_SESSION);

if(isset($_GET['islem']) && $_GET['islem'] == 'musteri_excel')
{
    $sql = "SELECT * FROM musteri  WHERE firma_id = :firma_id";
    if ( $_SESSION['yetki_id'] != ADMIN_YETKI_ID){
        $sql .= " AND  musteri.musteri_temsilcisi_id = :musteri_temsilcisi_id";
    }
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    if ( $_SESSION['yetki_id'] != ADMIN_YETKI_ID){
        $sth->bindParam('musteri_temsilcisi_id', $_SESSION['personel_id']);
    }
    $sth->execute();
    $musteriler = $sth->fetchAll(PDO::FETCH_ASSOC);
    $excel_data = [
        ['SIRA','MARKA', 'FİRMA UNVANI','EMAIL', 'ADRES','TELEFON', 'SABİT HAT', 'YETKİLİ AD SOYAD', 'YETKİLİ CEP', 'YETKİLİ EMAİL', 'VERGI DAİRESİ']
    ];

    foreach ($musteriler as $key=> $musteri) {
        $excel_data[] = [
            $key+1, $musteri['marka'], $musteri['firma_unvani'], 
            $musteri['e_mail'], $musteri['adresi'], $musteri['cep_tel'], $musteri['sabit_hat'], 
            $musteri['yetkili_adi'], $musteri['yetkili_cep'],$musteri['yetkili_mail'],$musteri['vergi_dairesi']
        ]; 
    }

    //print_r($excel_data);exit;

    $xlsx = Shuchkin\SimpleXLSXGen::fromArray( $excel_data );
    $tarih = date('dmY');
    $xlsx->downloadAs("musteriler-{$tarih}.xlsx"); // or downloadAs('books.xlsx') or $xlsx_content = (string) $xlsx 

    exit;
}

#musteri ekle
if(isset($_POST['musteri_ekle']))
{

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
    $vergi_dairesi      = trim($_POST['vergi_dairesi']);
    $vergi_numarasi     = trim($_POST['vergi_numarasi']);
    $musteri_temsilcisi_id = $_POST['musteri_temsilcisi_id'];

    try {
        $conn->beginTransaction();

        // Öncelikle müşteri bilgilerini kaydedelim
        $sql = "INSERT INTO musteri(firma_id, marka, firma_unvani, adresi, ilce_id, sehir_id, ulke_id, sektor_id, 
            cep_tel, sabit_hat, e_mail, vergi_dairesi, vergi_numarasi, musteri_temsilcisi_id) 
            VALUES(:firma_id, :marka, :firma_unvani, :adresi, :ilce_id, :sehir_id, :ulke_id, :sektor_id, 
            :cep_tel, :sabit_hat, :e_mail, :vergi_dairesi, :vergi_numarasi, :musteri_temsilcisi_id)";

        $sth = $conn->prepare($sql);
        $sth->execute([
            ":firma_id" => $firma_id,
            ":marka" => $marka,
            ":firma_unvani" => $firma_unvani,
            ":adresi" => $adresi,
            ":ilce_id" => $ilce_id,
            ":sehir_id" => $sehir_id,
            ":ulke_id" => $ulke_id,
            ":sektor_id" => $sektor_id,
            ":cep_tel" => $cep_tel,
            ":sabit_hat" => $sabit_hat,
            ":e_mail" => $e_mail,
            ":vergi_dairesi" => $vergi_dairesi,
            ":vergi_numarasi" => $vergi_numarasi,
            ":musteri_temsilcisi_id" => $musteri_temsilcisi_id
        ]);

        // Yeni eklenen müşterinin ID'sini alalım
        $musteri_id = $conn->lastInsertId();

        // Yetkilileri ekleyelim
        if(isset($_POST['yetkililer']) && is_array($_POST['yetkililer'])) {
            $yetkili_sql = "INSERT INTO musteri_yetkilileri (musteri_id, yetkili_adi, yetkili_cep, yetkili_mail, yetkili_gorev, yetkili_aciklama) 
                            VALUES (:musteri_id, :yetkili_adi, :yetkili_cep, :yetkili_mail, :yetkili_gorev, :yetkili_aciklama)";
            $yetkili_stmt = $conn->prepare($yetkili_sql);

            foreach($_POST['yetkililer'] as $key => $yetkili) {
                $yetkili_adi = trim($yetkili['adi']);
                $yetkili_cep = trim($yetkili['cep']);
                $yetkili_mail = strtolower(trim($yetkili['mail']));
                $yetkili_gorev = trim($yetkili['gorev']);
                $yetkili_aciklama = trim($yetkili['aciklama']);
                $yetkili_varsayilan = trim($yetkili['default_yetkili']);

                $yetkili_stmt->execute([
                    ":musteri_id" => $musteri_id,
                    ":yetkili_adi" => $yetkili_adi,
                    ":yetkili_cep" => $yetkili_cep,
                    ":yetkili_mail" => $yetkili_mail,
                    ":yetkili_gorev" => $yetkili_gorev,
                    ":yetkili_aciklama" => $yetkili_aciklama,
                    ":is_default" => $yetkili_varsayilan,
                ]);

                // Eğer bu yetkili "varsayılan" olarak seçilmişse, müşteri tablosunu güncelleyelim
                if(isset($_POST['default_yetkili']) && $_POST['default_yetkili'] == $key) {
                    $update_sql = "UPDATE musteri SET yetkili_adi = :yetkili_adi, yetkili_cep = :yetkili_cep, 
                                   yetkili_mail = :yetkili_mail, yetkili_gorev = :yetkili_gorev, aciklama = :aciklama, is_default = :is_default WHERE id = :musteri_id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->execute([
                        ":yetkili_adi" => $yetkili_adi,
                        ":yetkili_cep" => $yetkili_cep,
                        ":yetkili_mail" => $yetkili_mail,
                        ":yetkili_gorev" => $yetkili_gorev,
                        ":aciklama" => $yetkili_aciklama,
                        ":is_default" => $yetkili_varsayilan,
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
if(isset($_POST['musteri_guncelle']))
{
    $id                     = $_POST['id'];
    $marka                  = ucwords(trim($_POST['marka']));
    $firma_unvani           = ucwords(trim($_POST['firma_unvani']));
    $adresi                 = trim($_POST['adresi']);
    $ilce_id                = $_POST['ilce_id'];
    $sehir_id               = $_POST['sehir_id'];
    $ulke_id                = $_POST['ulke_id'];
    $sektor_id              = $_POST['sektor_id'];
    $cep_tel                = trim($_POST['cep_tel']);
    $sabit_hat              = trim($_POST['sabit_hat']);
    $e_mail                 = strtolower(trim($_POST['e_mail']));
    $yetkili_adi            = trim($_POST['yetkili_adi']);
    $yetkili_cep            = trim($_POST['yetkili_cep']);
    $yetkili_mail           = strtolower(trim($_POST['yetkili_mail']));
    $yetkili_gorev          = trim($_POST['yetkili_gorev']);
    $aciklama               = trim($_POST['aciklama']);
    $vergi_dairesi          = trim($_POST['vergi_dairesi']);
    $vergi_numarasi         = trim($_POST['vergi_numarasi']);
    $musteri_temsilcisi_id  = $_POST['musteri_temsilcisi_id'];

    $sql = "UPDATE musteri SET marka = :marka, firma_unvani = :firma_unvani, 
    adresi = :adresi, ilce_id = :ilce_id, sehir_id = :sehir_id, ulke_id = :ulke_id, 
    sektor_id = :sektor_id, cep_tel = :cep_tel, sabit_hat= :sabit_hat, e_mail = :e_mail, yetkili_adi = :yetkili_adi, 
    yetkili_cep = :yetkili_cep, yetkili_mail = :yetkili_mail, yetkili_gorev = :yetkili_gorev, aciklama = :aciklama, 
    vergi_dairesi = :vergi_dairesi, vergi_numarasi = :vergi_numarasi, musteri_temsilcisi_id = :musteri_temsilcisi_id  
    WHERE id = :id AND firma_id = :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("marka", $marka);
    $sth->bindParam("firma_unvani", $firma_unvani);
    $sth->bindParam("adresi", $adresi);
    $sth->bindParam("ilce_id", $ilce_id);
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
    $sth->bindParam("id", $id);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);

    $durum = $sth->execute();

    if($durum == true)
    {
        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header("Location: /index.php?url=musteriler");
    }
    else 
    {
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Güncelle İşlemi Başarısız';
        header("Location: /index.php?url=musteri_guncelle&id={$id}");
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


