<?php 

require_once "include/oturum_kontrol.php";

//personel ekle
if(isset($_POST['personel_ekle']))
{
    //echo "<pre>"; print_R($_POST); exit;
    $firma_id   = trim($_POST['firma_id']);
    $ad         = ucfirst(trim($_POST['ad']));
    $soyad      = ucfirst(trim($_POST['soyad']));
    $email      = trim($_POST['email']);
    $sifre      = sha1(trim($_POST['sifre']));

    $sth = $conn->prepare('SELECT id FROM personeller WHERE email =:email');
    $sth->bindParam('email', $email);
    $sth->execute();
    $personel = $sth->fetch(PDO::FETCH_ASSOC);

    if(!empty($personel))
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Aynı Emailden Daha Önceden Mevcut';
        header("Location: /index.php?url=personel_ekle");
        die();
    }

    $sql = "INSERT INTO personeller(firma_id, ad, soyad, email, sifre, yetki_id) 
            VALUES(:firma_id, :ad, :soyad, :email, :sifre, 1);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $firma_id);
    $sth->bindParam("ad", $ad);
    $sth->bindParam("soyad", $soyad);
    $sth->bindParam("email", $email);
    $sth->bindParam("sifre", $sifre);
    $durum = $sth->execute();

    if($durum)
    {
        $personel_id = $conn->lastInsertId();
        $sth = $conn->prepare('SELECT * FROM sayfalar');
        $sth->execute();
        $sayfalar = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sayfalar as $sayfa) {
            $sql = "INSERT INTO personel_sayfa_yetki(sayfa_id, personel_id, yetki_durum) 
                    VALUES(:sayfa_id, :personel_id,'1')";
            $sth = $conn->prepare($sql);
            $sth->bindParam('sayfa_id', $sayfa['id']);
            $sth->bindParam('personel_id', $personel_id);
            $sth->execute();
        }

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }

    header('Location: /index.php?url=superadmin_personel_ekle');
}