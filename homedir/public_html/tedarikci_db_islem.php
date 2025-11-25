<?php

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);

require_once "include/db.php";

#tedarikçi ekle
if(isset($_POST['tedarikci_ekle']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $firma_adi              = $_POST['firma_adi'];
    $email                  = $_POST['email'];
    $tedarikci_unvani       = $_POST['tedarikci_unvani'];
    $tedarikci_adresi       = $_POST['tedarikci_adresi'];
    $tedarikci_telefonu     = $_POST['tedarikci_telefonu'];
    $tedarikci_vergi_no     = $_POST['tedarikci_vergi_no'];
    $tedarikci_vd           = $_POST['tedarikci_vd'];
    $tedarikci_aciklama     = $_POST['tedarikci_aciklama'];
    $fason                  = $_POST['fason'];
    $departman_idler        = isset($_POST['departman_idler']) ? 
                            json_encode(array_map('intval',$_POST['departman_idler'])) : json_encode([]);

    $stok_kalem_idler       = isset($_POST['stok_kalem_idler']) ? 
                            json_encode(array_map('intval',$_POST['stok_kalem_idler'])) : json_encode([]);
    

    $sql = "INSERT INTO tedarikciler(firma_id, firma_adi, email, tedarikci_unvani, tedarikci_adresi, tedarikci_telefonu, tedarikci_vergi_no, 
        tedarikci_vd, fason, tedarikci_aciklama,departman_idler, stok_kalem_idler ) 
        VALUES(:firma_id, :firma_adi, :email, :tedarikci_unvani, :tedarikci_adresi, :tedarikci_telefonu, :tedarikci_vergi_no, 
        :tedarikci_vd, :fason, :tedarikci_aciklama, :departman_idler, :stok_kalem_idler);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("firma_adi", $firma_adi);
    $sth->bindParam("email", $email);
    $sth->bindParam("tedarikci_unvani", $tedarikci_unvani);
    $sth->bindParam("tedarikci_adresi", $tedarikci_adresi);
    $sth->bindParam("tedarikci_telefonu", $tedarikci_telefonu);
    $sth->bindParam("tedarikci_vergi_no", $tedarikci_vergi_no);
    $sth->bindParam("tedarikci_vd", $tedarikci_vd);
    $sth->bindParam("fason", $fason);
    $sth->bindParam("tedarikci_aciklama", $tedarikci_aciklama);
    $sth->bindParam("departman_idler", $departman_idler);
    $sth->bindParam("stok_kalem_idler", $stok_kalem_idler);

    $durum = $sth->execute();

    if($durum == true)
    {
        #echo "<h2>Ekleme başarılı</h2>";
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
        header('Location: /index.php?url=tedarikci');
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
        header('Location: /index.php?url=tedarikci_ekle');
    }
    die();
}

#tedarikçi guncelle
if(isset($_POST['tedarikci_guncelle']))
{
    $id                     = $_POST['id'];
    $firma_adi              = $_POST['firma_adi'];
    $email                  = $_POST['email'];
    $tedarikci_unvani       = $_POST['tedarikci_unvani'];
    $tedarikci_adresi       = $_POST['tedarikci_adresi'];
    $tedarikci_telefonu     = $_POST['tedarikci_telefonu'];
    $tedarikci_vergi_no     = $_POST['tedarikci_vergi_no'];
    $tedarikci_vd           = $_POST['tedarikci_vd'];
    $fason                  = $_POST['fason'];
    $tedarikci_aciklama     = $_POST['tedarikci_aciklama'];
    $departman_idler        = isset($_POST['departman_idler']) ? 
                                json_encode(array_map('intval',$_POST['departman_idler'])) : json_encode([]);
                                
    $stok_kalem_idler       = isset($_POST['stok_kalem_idler']) ? 
                            json_encode(array_map('intval',$_POST['stok_kalem_idler'])) : json_encode([]);


    /*
    $sql = "UPDATE kullanicilar SET adi = '$adi', email = '$email'  WHERE id = $id;";
    $sth = $conn->prepare($sql);
    $durum = $sth->execute();
    */

    $sql = "UPDATE tedarikciler SET firma_adi = :firma_adi, email = :email, 
    tedarikci_unvani = :tedarikci_unvani, tedarikci_adresi = :tedarikci_adresi, tedarikci_telefonu = :tedarikci_telefonu, 
    tedarikci_vergi_no = :tedarikci_vergi_no, tedarikci_vd = :tedarikci_vd, fason = :fason, 
    tedarikci_aciklama = :tedarikci_aciklama, departman_idler = :departman_idler, stok_kalem_idler = :stok_kalem_idler
    WHERE id = :id AND firma_id = :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_adi", $firma_adi);
    $sth->bindParam("email", $email);
    $sth->bindParam("tedarikci_unvani", $tedarikci_unvani);
    $sth->bindParam("tedarikci_adresi", $tedarikci_adresi);
    $sth->bindParam("tedarikci_telefonu", $tedarikci_telefonu);
    $sth->bindParam("tedarikci_vergi_no", $tedarikci_vergi_no);
    $sth->bindParam("tedarikci_vd", $tedarikci_vd);
    $sth->bindParam("fason", $fason);
    $sth->bindParam("tedarikci_aciklama", $tedarikci_aciklama);
    $sth->bindParam("departman_idler", $departman_idler);
    $sth->bindParam("stok_kalem_idler", $stok_kalem_idler);
    $sth->bindParam("id", $id);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);

    $durum = $sth->execute();

    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header('Location: /index.php?url=tedarikci');
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
        header('Location: /index.php?url=tedarikci_guncelle&id=.$id');
    }
    die();
}
#tedarikçi sil
if(isset($_GET['islem']) && $_GET['islem'] == 'tedarikci_sil')
{
    $id = $_GET['id'];

    $sql = "DELETE FROM tedarikciler WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    
    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
        header('Location: /index.php?url=tedarikci');
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
        header('Location: /index.php?url=tedarikci');
    }
    die();
}



