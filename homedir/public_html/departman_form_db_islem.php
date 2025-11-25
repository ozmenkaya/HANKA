<?php 
require_once "include/db.php";
require_once "include/oturum_kontrol.php";

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET); exit;
//form silme
if(isset($_GET['islem']) && $_GET['islem'] == 'form_sil')
{
    $id = $_GET['id'];
    $departman_id = $_GET['departman_id'];

    $sql = "DELETE FROM departman_formlar WHERE id=:id AND firma_id = :firma_id";
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
    
    header("Location: /index.php?url=departman_form&id={$departman_id}");
    exit;
}


//form ekle
if(isset($_POST['form_ekle']))
{
    $departman_id       = $_POST['departman_id'];
    $konu               = $_POST['konu'];
    $gosterme_asamasi   = $_POST['gosterme_asamasi'];
    $zorunluluk_durumu  = $_POST['zorunluluk_durumu'];

    $sql = "INSERT INTO departman_formlar(firma_id, departman_id, konu, gosterme_asamasi, zorunluluk_durumu) 
            VALUES(:firma_id, :departman_id, :konu, :gosterme_asamasi, :zorunluluk_durumu);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("konu", $konu);
    $sth->bindParam("gosterme_asamasi", $gosterme_asamasi);
    $sth->bindParam("zorunluluk_durumu", $zorunluluk_durumu);
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
    header("Location: /index.php?url=departman_form&id={$departman_id}");
    exit;
}



if(isset($_POST['form_guncelle']))
{
    $id                 = $_POST['id'];
    $departman_id       = $_POST['departman_id'];
    $konu               = $_POST['konu'];
    $gosterme_asamasi   = $_POST['gosterme_asamasi'];
    $zorunluluk_durumu  = $_POST['zorunluluk_durumu'];

    $sql = "UPDATE departman_formlar SET departman_id = :departman_id, konu = :konu, gosterme_asamasi = :gosterme_asamasi, zorunluluk_durumu = :zorunluluk_durumu
        WHERE id = :id AND firma_id = :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('departman_id', $departman_id);
    $sth->bindParam('konu', $konu);
    $sth->bindParam('gosterme_asamasi', $gosterme_asamasi);
    $sth->bindParam('zorunluluk_durumu', $zorunluluk_durumu);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header("Location: /index.php?url=departman_form&id={$departman_id}");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
        header("Location: /index.php?url=departman_form_guncelle&id={$id}");
    }
    exit;

}