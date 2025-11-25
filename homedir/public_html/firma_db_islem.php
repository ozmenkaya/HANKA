<?php 

#echo 21; exit;
require_once "include/oturum_kontrol.php";

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_FILES); exit;

//firma güncelleme işlemi
if(isset($_POST['firma-guncelle']))
{
    $id         = trim($_POST['id']);
    $domain_adi = trim($_POST['domain_adi']);
    $firma_adi  = ucwords(trim($_POST['firma_adi']));
    $email      = trim($_POST['email']);

    $sth = $conn->prepare('SELECT id FROM firmalar WHERE domain_adi = :domain_adi AND id != :id');
    $sth->bindParam('id', $id);
    $sth->bindParam('domain_adi', $domain_adi);
    $sth->execute();
    $firma = $sth->fetch(PDO::FETCH_ASSOC);

    if(!empty($firma))
    {
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Domain Daha Önceden Var Zaten!';
        header("Location: /index.php?url=firma_guncelle&id={$id}");
        die();
    }

    if(isset($_FILES['logo']) && !empty($_FILES['logo']['name']))
    {
        $hedef_klasor   = "dosyalar/logo/";
        $dosya_adi      = pathinfo($_FILES['logo']["name"], PATHINFO_FILENAME)."-".random_int(1000, 99999);
        $dosya_uzanti   = pathinfo($_FILES['logo']["name"], PATHINFO_EXTENSION);
        $dosya_adi      = preg_replace("/\s+/","-", $dosya_adi);
        $logo = "{$dosya_adi}.{$dosya_uzanti}";

        move_uploaded_file($_FILES["logo"]["tmp_name"], $hedef_klasor.$logo);

        $sql = "UPDATE firmalar SET domain_adi = :domain_adi, firma_adi = :firma_adi, email = :email, logo = :logo  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('domain_adi', $domain_adi);
        $sth->bindParam('firma_adi', $firma_adi);
        $sth->bindParam('email', $email);
        $sth->bindParam('logo', $logo);
        $sth->bindParam('id', $id);
        $durum = $sth->execute();
    }
    else 
    {
        $sql = "UPDATE firmalar SET domain_adi = :domain_adi, firma_adi = :firma_adi, email = :email  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('domain_adi', $domain_adi);
        $sth->bindParam('firma_adi', $firma_adi);
        $sth->bindParam('email', $email);
        $sth->bindParam('id', $id);
        $durum = $sth->execute();
    }

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Guncelleme İşlemi Başarılı';
        header("Location: /index.php?url=firma");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
        header("Location: /index.php?url=firma_guncelle&id={$id}");
    }

    die();

}

//firma ekleme işlemi
if(isset($_POST['firma-ekle']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $domain_adi = trim($_POST['domain_adi']);
    $firma_adi  = ucwords(trim($_POST['firma_adi']));
    $email      = trim($_POST['email']);


    $sth = $conn->prepare('SELECT id FROM firmalar WHERE domain_adi = :domain_adi');
    $sth->bindParam('domain_adi', $domain_adi);
    $sth->execute();
    $firma = $sth->fetch(PDO::FETCH_ASSOC);

    if(!empty($firma))
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Domain Daha Önceden Var Zaten!';
        header("Location: /index.php?url=firma");
        die();
    }

    $logo = "";
    if(isset($_FILES['logo']))
    {
        $hedef_klasor   = "dosyalar/logo/";
        $dosya_adi      = pathinfo($_FILES['logo']["name"], PATHINFO_FILENAME)."-".random_int(1000, 99999);
        $dosya_uzanti   = pathinfo($_FILES['logo']["name"], PATHINFO_EXTENSION);
        $dosya_adi      = preg_replace("/\s+/","-", $dosya_adi);
        $logo           = "{$dosya_adi}.{$dosya_uzanti}";

        move_uploaded_file($_FILES["logo"]["tmp_name"], $hedef_klasor.$logo);
    }
    

    $sql = "INSERT INTO firmalar(domain_adi, firma_adi, email, logo, siparis_no_baslangic_kodu) 
            VALUES(:domain_adi, :firma_adi, :email, :logo, :siparis_no_baslangic_kodu);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("domain_adi", $domain_adi);
    $sth->bindParam("firma_adi", $firma_adi);
    $sth->bindParam("email", $email);
    $sth->bindParam("logo", $logo);
    $sth->bindValue("siparis_no_baslangic_kodu", mb_substr(mb_strtoupper($firma_adi),0,3));
    $durum = $sth->execute();

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }

    header("Location: /index.php?url=firma");
    die();

}

//firma silme işlemi
if(isset($_GET['islem']) && $_GET['islem'] == 'firma-sil')
{
    $id = intval(trim($_GET['id']));

    $sql = "DELETE FROM firmalar WHERE id=:id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $durum = $sth->execute(); 


    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
    }

    header("Location: /index.php?url=firma");
    die();

}