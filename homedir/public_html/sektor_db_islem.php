<?php

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);

// require_once "include/db.php";

#Sektor ekle
if(isset($_POST['sektor_ekle']))
{
    $sektor_adi   = mb_strtoupper(trim($_POST['sektor_adi']));
    $firma_id     = intval($_SESSION['firma_id']);
    
    
    $sql = "INSERT INTO sektorler(firma_id, sektor_adi) VALUES(:firma_id, :sektor_adi);";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $firma_id);
    $sth->bindParam('sektor_adi', $sektor_adi);
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
    header('Location: /index.php?url=sektor');
    die();
}


#Sektor sil
if(isset($_GET['islem']) && $_GET['islem'] == 'sektor_sil')
{
    $id = $_GET['id'];

    $sql = "DELETE FROM sektorler WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    
    if($durum == true)
    {
        #echo "<h2>Ekleme başarılı</h2>";
        header('Location: /index.php?url=sektor&durum=basarili&mesaj=Sektor Silme İşlemi Başarılı');
    }
    else 
    {
        #echo "<h2>ekleme başarısız</h2>";
        header('Location: /index.php?url=sektor&durum=basarisiz&mesaj=Sektor Silme İşlemi Başarısız');
    }
    die();
}


#Sektor guncelle
if(isset($_POST['sektor_guncelle']))
{
    $id                 = intval($_POST['id']);
    $sektor_adi         = mb_strtoupper(trim($_POST['sektor_adi']));
    
    $sql = "UPDATE sektorler SET sektor_adi = :sektor_adi WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("sektor_adi", $sektor_adi);
    $sth->bindParam("id", $id);

    $durum = $sth->execute();

    if($durum == true)
    {
        header("Location: /index.php?url=sektor&durum=basarili&mesaj=Sektor Güncelleme İşlemi Başarılı");
    }
    else 
    {
        header("Location: /index.php?url=sekotr&durum=basarisiz&mesaj=Sektor Güncelleme İşlemi Başarısız");
    }
    die();
}
