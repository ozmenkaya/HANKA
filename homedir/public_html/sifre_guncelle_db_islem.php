<?php 

require_once "include/db.php";
require_once "include/oturum_kontrol.php";

//Şifre Güncelle
if(isset($_POST['sifre_guncelle'])){
    $mevcut_sifre       = sha1(trim($_POST['mevcut_sifre']));
    $yeni_sifre         = trim($_POST['yeni_sifre']);
    $yeni_sifre_tekrar  = trim($_POST['yeni_sifre_tekrar']);

    $sql = 'SELECT id FROM personeller WHERE id = :id AND sifre = :sifre';
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $_SESSION['personel_id']);
    $sth->bindParam('sifre', $mevcut_sifre);
    $sth->execute();
    $personel = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($personel)){
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Mevcut Şifreni Yanlış';
        header("Location: /index.php?url=sifre_guncelle"); exit;
    }

    if($yeni_sifre != $yeni_sifre_tekrar){
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Yeni Şifreler Uyuşmuyor';
        header("Location: /index.php?url=sifre_guncelle"); exit;
    }

    $sql = "UPDATE personeller SET sifre = :sifre  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindValue('sifre', sha1($yeni_sifre));
    $sth->bindParam('id', $id);
    $durum = $sth->execute();

    if($durum){
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Şifre Değiştirme Başarılı';
        header("Location: /"); 
    }else{
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Şifre Değiştirme Başarısız';
        header("Location: /index.php?url=sifre_guncelle"); 
    }
    exit;
}
