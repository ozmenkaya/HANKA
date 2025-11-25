<?php 
require_once "include/oturum_kontrol.php";

if(isset($_POST['bilgileri_guncelle'])){
    $id             = $_SESSION['personel_id'];
    $ad             = ucwords(trim($_POST['ad']));
    $soyad          = ucwords(trim($_POST['soyad']));
    $adres          = ucwords(trim($_POST['adres']));
    $yetki_id       = $_POST['yetki_id'];
    $cep_numarasi   = trim($_POST['cep_numarasi']);
    $sabit_hat      = trim($_POST['sabit_hat']);
    $email          = trim($_POST['email']);
    $dogum_tarihi   = $_POST['dogum_tarihi'];
    $ise_baslama    = $_POST['ise_baslama'];
    $aciklama       = ucwords(trim($_POST['aciklama']));
    $departman_idler = isset($_POST['departman_idler']) ? $_POST['departman_idler'] : [];

    $sql = "UPDATE personeller SET ad = :ad, soyad = :soyad, adres = :adres,
        yetki_id = :yetki_id, cep_numarasi = :cep_numarasi, sabit_hat = :sabit_hat, email = :email,
        dogum_tarihi = :dogum_tarihi, ise_baslama = :ise_baslama, aciklama = :aciklama 
        WHERE id = :id AND firma_id = :firma_id;";

    $sth = $conn->prepare($sql);
    $sth->bindParam("ad", $ad);
    $sth->bindParam("soyad", $soyad);
    $sth->bindParam("adres", $adres);
    $sth->bindParam("yetki_id", $yetki_id);
    $sth->bindParam("cep_numarasi", $cep_numarasi);
    $sth->bindParam("sabit_hat", $sabit_hat);
    $sth->bindParam("email", $email);
    $sth->bindParam("dogum_tarihi", $dogum_tarihi);
    $sth->bindParam("ise_baslama", $ise_baslama);
    $sth->bindParam("aciklama", $aciklama);
    $sth->bindParam("id", $id);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);

    $durum = $sth->execute();

    if($durum == true)
    {
        $sql = "DELETE FROM personel_departmanlar WHERE personel_id =  :personel_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('personel_id', $id);
        $sth->execute();

        for($i = 0; $i < count($departman_idler); $i++)
        {
            $sql = "INSERT INTO personel_departmanlar(personel_id, departman_id) VALUES(:personel_id, :departman_id)";
            $sth = $conn->prepare($sql);
            $sth->bindParam('personel_id', $id);
            $sth->bindParam('departman_id', $departman_idler[$i]);
            $durum = $sth->execute();
        
        }
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Personel Düzenleme İşlemi Başarılı';
        header("Location: /index.php?url=bilgilerim");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Personel Düzenleme İşlemi Başarısız';
        header("Location: /index.php?url=bilgilerim");
    }
    die();


}