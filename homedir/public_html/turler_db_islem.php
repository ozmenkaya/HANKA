<?php

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);

require_once "include/db.php";

#Tur ekle
if(isset($_POST['tur_ekle']))
{
    $tur        = mb_strtoupper(trim($_POST['tur']));
    $firma_id   = intval($_SESSION['firma_id']);
    
    
    $sql = "INSERT INTO turler(firma_id, tur) VALUES(:firma_id, :tur);";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $firma_id);
    $sth->bindParam('tur', $tur);
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
    header('Location: /index.php?url=turler');
    die();
}


#Tur sil
if(isset($_GET['islem']) && $_GET['islem'] == 'tur_sil')
{
    $id = $_GET['id'];

    $sql = "DELETE FROM turler WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    
    if($durum == true)
    {
        #echo "<h2>Ekleme başarılı</h2>";
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Tür Silme İşlemi Başarılı';
        header('Location: /index.php?url=turler');
    }
    else 
    {
        #echo "<h2>ekleme başarısız</h2>";
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Tür Silme İşlemi Başarısız';
        header('Location: /index.php?url=turler');
    }
    die();
}


#Tur guncelle
if(isset($_POST['tur_guncelle']))
{
    $id         = intval($_POST['id']);
    $tur        = mb_strtoupper(trim($_POST['tur']));
    
    $sql = "UPDATE turler SET tur = :tur WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("tur", $tur);
    $sth->bindParam("id", $id);

    $durum = $sth->execute();

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header("Location: /index.php?url=turler");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
        header("Location: /index.php?url=turler_guncelle&id={$id}");
    }
    die();
}
