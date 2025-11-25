<?php  
require_once "include/oturum_kontrol.php";

#Birim Ekle
if(isset($_POST['birim_ekle'])){
    #echo "<pre>"; print_r($_POST); exit;
    $birim = mb_strtoupper(trim($_POST['birim']));

    $sql = "INSERT INTO birimler(ad, firma_id) VALUES(:ad, :firma_id);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("ad", $birim);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $durum = $sth->execute();

    if($durum){ 
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }
    header('Location: /index.php?url=birimler');
    die();

}

#Birim Güncelle
if(isset($_POST['birim_guncelle'])){
    $id     = intval($_POST['id']);
    $birim  = mb_strtoupper(trim($_POST['birim']));

    $sql = "UPDATE birimler SET ad = :ad
            WHERE id = :id AND firma_id =:firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("ad", $birim);
    $sth->bindParam("id", $id);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);

    $durum = $sth->execute();

    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelle İşlemi Başarılı';
        header('Location: /index.php?url=birimler');
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelle İşlemi Başarısız';
        header("Location: /index.php?url=birim_guncelle&id={$id}");
    }
    die();
} 

if(isset($_GET['islem']) && $_GET['islem'] == 'birim_sil')
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM birimler WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
    }
    header('Location: /index.php?url=birimler');
    die();
}