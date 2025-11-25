<?php 
require_once "include/oturum_kontrol.php";
#echo "<pre>"; print_r($_POST);

//Ekle
if(isset($_POST['stok_alt_kalem_ekle']))
{
    $stok_id    = $_POST['stok_id'];
    $veri       = json_encode(array_map('trim', $_POST['alt_stok_kalem_ad']), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

    $sth = $conn->prepare('SELECT id FROM stok_alt_kalemler WHERE stok_id = :stok_id AND veri = :veri');
    $sth->bindParam("stok_id", $stok_id);
    $sth->bindParam("veri", $veri);
    $sth->execute();
    $varmi = $sth->fetch(PDO::FETCH_ASSOC);
    
    if(!empty($varmi))
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Daha Önceden Girilen Verilen Var!!';
        header("Location: /index.php?url=stok&stok_id=".$stok_id);
        die();
    }

    $sql = "INSERT INTO stok_alt_kalemler(firma_id, stok_id, veri) VALUES(:firma_id, :stok_id, :veri);";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('stok_id', $stok_id);
    $sth->bindParam('veri', $veri);
    $durum = $sth->execute();



    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme Başarısız';
    }
    header('Location: /index.php?url=stok&stok_id='.$stok_id);
    die();
}



#stok alt kalem güncelle
if(isset($_POST['stok_alt_kalem_guncelle']))
{
    //echo "<pre>"; print_R($_POST); exit;
    $id         = $_POST['id'];
    $veri       = json_encode(array_map('trim', $_POST['alt_stok_kalem_ad']),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    $stok_id    = $_POST['stok_id'];

    $sth = $conn->prepare('SELECT id FROM stok_alt_kalemler WHERE id != :id AND stok_id = :stok_id AND veri = :veri');
    $sth->bindParam("id", $id);
    $sth->bindParam("stok_id", $stok_id);
    $sth->bindParam("veri", $veri);
    $sth->execute();
    $varmi = $sth->fetch(PDO::FETCH_ASSOC);
    
    if(!empty($varmi))
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Daha Önceden Girilen Verilen Var!!';
        header("Location: /index.php?url=stok&stok_id=".$stok_id);
        die();
    }


    $sql = "UPDATE stok_alt_kalemler SET veri = :veri WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('veri', $veri);
    $sth->bindParam('id', $id);
    $durum = $sth->execute();

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header('Location: /index.php?url=stok&stok_id='.$stok_id);
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';

        header("Location: /index.php?url=stok_alt_kalem_guncelle&id={$id}&stok_id={$stok_id}");
    }
    die();
}



#stok alt kalem
if(isset($_GET['islem']) && $_GET['islem'] == 'stok_alt_kalem_sil')
{
    $id         = intval($_GET['id']);
    $stok_id    = intval($_GET['stok_id']);

    $sql = "DELETE FROM stok_alt_kalemler WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
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

    header('Location: /index.php?url=stok&stok_id='.$stok_id);
    die();
}
