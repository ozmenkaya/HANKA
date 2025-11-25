<?php

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);

require_once "include/oturum_kontrol.php";;

#Departman ekle
if(isset($_POST['departman_ekle']))
{
    $departman                  = mb_strtoupper(trim($_POST['departman']),'UTF-8');
    $sorumlu_personel_idler     = isset($_POST['sorumlu_personel_idler']) ? array_map('intval',$_POST['sorumlu_personel_idler']) : [];
    $sorumlu_personel_idler     = json_encode($sorumlu_personel_idler);

    $sql = "INSERT INTO departmanlar(departman, firma_id, sorumlu_personel_idler) VALUES(:departman, :firma_id, :sorumlu_personel_idler);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("departman", $departman);
    $sth->bindParam("sorumlu_personel_idler", $sorumlu_personel_idler);
    $durum = $sth->execute();

    $departman_id = $conn->lastInsertId();
    if($durum)
    {
        $sql = "SELECT id FROM `makina_is_buttonlar`";
        $sth = $conn->prepare($sql);
        $sth->execute();
        $makina_is_buttonlar = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($makina_is_buttonlar as $makina_is_button) {
            $sql = "INSERT INTO makina_is_buttonlar_firma_ayarlar(makina_is_button_id, firma_id,departman_id,durum)
                    VALUES(:makina_is_button_id, :firma_id, :departman_id,'1')";
            $sth = $conn->prepare($sql);
            $sth->bindParam("makina_is_button_id", $makina_is_button['id']);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("departman_id", $departman_id);
            $durum = $sth->execute();
        }
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }
    header('Location: /index.php?url=departman');
    die();
}


#Departman sil
if(isset($_GET['islem']) && $_GET['islem'] == 'departman_sil')
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM departmanlar WHERE id=:id AND firma_id = :firma_id";
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
    header('Location: /index.php?url=departman');
    die();
}


#Departman guncelle
if(isset($_POST['departman_guncelle']))
{
    $id                         = intval($_POST['id']);
    $sorumlu_personel_idler     = isset($_POST['sorumlu_personel_idler']) ? array_map('intval',$_POST['sorumlu_personel_idler']) : [];
    $sorumlu_personel_idler     = json_encode($sorumlu_personel_idler);
    $departman                  = mb_strtoupper(trim($_POST['departman']),'UTF-8');
    

    
    $sql = "UPDATE departmanlar SET departman = :departman, sorumlu_personel_idler = :sorumlu_personel_idler 
            WHERE id = :id AND firma_id =:firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("departman", $departman);
    $sth->bindParam("sorumlu_personel_idler", $sorumlu_personel_idler);
    $sth->bindParam("id", $id);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);

    $durum = $sth->execute();

    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelle İşlemi Başarılı';
        header("Location: /index.php?url=departman");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelle İşlemi Başarısız';
        header("Location: /index.php?url=departman_guncelle&id={$id}");
    }
    die();
}
