<?php 

require_once "include/db.php";
//echo "<pre>"; print_r($_POST); exit;

#Stok Alt Kalem Ekle
if(isset($_POST['stok_alt_kalem_deger_ekle']))
{

    $ad             = mb_strtoupper(trim($_POST['ad']));    
    $kolon_tipi     = $_POST['kolon_tipi'];    
    $stok_id        = intval($_POST['stok_id']);    

    $sql = 'SELECT id FROM stok_alt_kalem_degerler WHERE stok_id = :stok_id AND ad = :ad';
    $sth = $conn->prepare($sql);
    $sth->bindParam("stok_id", $stok_id);
    $sth->bindParam("ad", $ad);
    $sth->execute();
    $stok_alt_deger = $sth->fetch(PDO::FETCH_ASSOC);

    if(!empty($stok_alt_deger)){
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Daha Önceden Mevcut';
        header('Location: /index.php?url=stok_alt_kalem_deger&stok_id='.$stok_id);
        exit;
    }

    
    $sql = "INSERT INTO stok_alt_kalem_degerler(firma_id, stok_id, ad, kolon_tipi) 
            VALUES(:firma_id, :stok_id, :ad, :kolon_tipi);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("stok_id", $stok_id);
    $sth->bindParam("ad", $ad);
    $sth->bindParam("kolon_tipi", $kolon_tipi);
    
    $durum = $sth->execute();

    $sql = "SELECT id, veri FROM `stok_alt_kalemler` WHERE stok_id = :stok_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("stok_id", $stok_id);
    $sth->execute();
    $stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stok_alt_kalemler as $stok_alt_kalem) {
        $veri = json_decode($stok_alt_kalem['veri'], true);
        $veri[$ad] = '';

        $sql = "UPDATE stok_alt_kalemler SET veri = :veri  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('veri', json_encode($veri,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $sth->bindParam('id', $stok_alt_kalem['id']);
        $durum = $sth->execute();
    }

    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }
    header('Location: /index.php?url=stok_alt_kalem_deger&stok_id='.$stok_id);
    die();
}

//Stok Alt Değer Silme
if(isset($_GET['islem']) && $_GET['islem'] == 'stok_alt_kalem_deger_sil')
{
    $id         = intval($_GET['id']);
    $stok_id    = intval($_GET['stok_id']);

    $sql = "SELECT ad FROM `stok_alt_kalem_degerler` WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("id", $id);
    $sth->execute();
    $stok_alt_kalem_data = $sth->fetch(PDO::FETCH_ASSOC);


    $sql = "DELETE FROM stok_alt_kalem_degerler WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 

    $sql = "SELECT id, veri FROM `stok_alt_kalemler` WHERE stok_id = :stok_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("stok_id", $stok_id);
    $sth->execute();
    $stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stok_alt_kalemler as $stok_alt_kalem) {
        $veri = json_decode($stok_alt_kalem['veri'], true);
        unset($veri[$stok_alt_kalem_data['ad']]);

        $sql = "UPDATE stok_alt_kalemler SET veri = :veri  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindValue('veri', json_encode($veri,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $sth->bindParam('id', $stok_alt_kalem['id']);
        $durum = $sth->execute();
    }



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
    header("Location: /index.php?url=stok_alt_kalem_deger&stok_id={$stok_id}");
    die();
}


if(isset($_POST['stok_alt_kalem_deger_guncelle']))
{
    $id             = intval($_POST['id']);
    $stok_id        = intval($_POST['stok_id']);
    $ad             = mb_strtoupper(trim($_POST['ad']));
    $ad_eski        = mb_strtoupper(trim($_POST['ad_eski']));
    $kolon_tipi     = $_POST['kolon_tipi'];


    $sql = "UPDATE stok_alt_kalem_degerler SET ad = :ad, kolon_tipi = :kolon_tipi  
            WHERE id = :id AND stok_id =:stok_id AND firma_id= :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('ad', $ad);
    $sth->bindParam('kolon_tipi', $kolon_tipi);
    $sth->bindParam('id', $id);
    $sth->bindParam('stok_id', $stok_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();

    if($durum)
    {
        $sth = $conn->prepare('SELECT id, veri FROM stok_alt_kalemler WHERE stok_id = :stok_id AND firma_id = :firma_id');
        $sth->bindParam('stok_id', $stok_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($stok_alt_kalemler as $stok_alt_kalem) {
            $veriler = json_decode($stok_alt_kalem['veri'], true);
            $yeni_veriler = [];
            foreach ($veriler as $key => $deger) {
                if($key == $ad_eski)
                {
                    $yeni_veriler[$ad] = $deger;
                }
                else 
                {
                    $yeni_veriler[$key] = $deger;
                }
            }
            $sql = "UPDATE stok_alt_kalemler SET veri = :veri  WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindValue('veri', json_encode($yeni_veriler, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $sth->bindParam('id', $stok_alt_kalem['id']);
            $durum = $sth->execute();
        }

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header("Location: /index.php?url=stok_alt_kalem_deger&stok_id={$stok_id}");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
        header('Location: /index.php?url=stok_alt_kalem_deger_guncelle&id='.$id);
    }
    die();
}
