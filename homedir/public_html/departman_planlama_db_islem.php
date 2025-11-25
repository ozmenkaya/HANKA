<?php 

include "include/oturum_kontrol.php";
//echo "<pre>";print_r($_POST); exit;


// departman plan ekle
if(isset($_POST['departman_plan_ekle']))
{
    $departman_id               = $_POST['departman_id'];
    $stok                       = json_encode(array_map('intval',$_POST['stok']));
    $genislik                   = intval($_POST['genislik']);
    $yukseklik                  = intval($_POST['yukseklik']);
    $etiket_varmi               = $_POST['etiket_varmi'];
    $etiket_tasarim             = $_POST['etiket_tasarim'];
    $birim_id                   = $_POST['birim_id'];
    $makina_is_button_idler     = $_POST['makina_is_button_idler'];

    $sql = "INSERT INTO departman_planlama(firma_id,departman_id, stok,birim_id, 
            etiket_varmi, genislik, yukseklik, etiket_tasarim) 
        VALUES(:firma_id,:departman_id, :stok,:birim_id, 
            :etiket_varmi, :genislik, :yukseklik, :etiket_tasarim);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("stok", $stok);
    $sth->bindParam("birim_id", $birim_id);
    $sth->bindParam("etiket_varmi", $etiket_varmi);
    $sth->bindParam("genislik", $genislik);
    $sth->bindParam("yukseklik", $yukseklik);
    $sth->bindParam("etiket_tasarim", $etiket_tasarim);
    $durum = $sth->execute();

    if($durum)
    {
        foreach ($makina_is_button_idler as $makina_is_button_id => $makina_is_button_durum) {
            $sql = "INSERT INTO makina_is_buttonlar_firma_ayarlar(makina_is_button_id, firma_id, departman_id, durum) 
            VALUES(:makina_is_button_id, :firma_id, :departman_id, :durum)";
            $sth = $conn->prepare($sql);
            $sth->bindParam("makina_is_button_id", $makina_is_button_id);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("departman_id", $departman_id);
            $sth->bindParam("durum", $makina_is_button_durum);
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

    header("Location: /index.php?url=departman_planlama&id={$departman_id}");
    exit;

}


//departman plan güncelle

if(isset($_POST['departman_plan_guncelle']))
{
    $id                         = intval($_POST['id']);
    $departman_id               = intval($_POST['departman_id']);
    $genislik                   = intval($_POST['genislik']);
    $yukseklik                  = intval($_POST['yukseklik']);
    $etiket_varmi               = $_POST['etiket_varmi'];
    $etiket_tasarim             = $_POST['etiket_tasarim'];
    $stok                       = json_encode(array_map('intval',$_POST['stok']));
    $birim_id                   = intval($_POST['birim_id']);
    $makina_is_button_idler     = array_map('intval',$_POST['makina_is_button_idler']);

    foreach ($makina_is_button_idler as $makina_is_button_id => $makina_is_button_durum) {
        $sql = "SELECT * FROM makina_is_buttonlar_firma_ayarlar 
        WHERE firma_id = :firma_id AND makina_is_button_id = :makina_is_button_id AND departman_id = :departman_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->bindParam("makina_is_button_id", $makina_is_button_id);
        $sth->bindParam("departman_id", $departman_id);
        $sth->execute();
        $varmi = $sth->fetch(PDO::FETCH_ASSOC);

        if(empty($varmi))
        {
            $sql = "INSERT INTO makina_is_buttonlar_firma_ayarlar(makina_is_button_id, firma_id, departman_id, durum) 
            VALUES(:makina_is_button_id, :firma_id, :departman_id, :durum)";
            $sth = $conn->prepare($sql);
            $sth->bindParam("makina_is_button_id", $makina_is_button_id);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("departman_id", $departman_id);
            $sth->bindParam("durum", $makina_is_button_durum);
            $durum = $sth->execute();
        }
        else 
        {
            $sql = "UPDATE makina_is_buttonlar_firma_ayarlar SET durum = :durum  
            WHERE firma_id = :firma_id AND makina_is_button_id = :makina_is_button_id AND departman_id = :departman_id";
            $sth = $conn->prepare($sql);
            $sth->bindParam("durum", $makina_is_button_durum);
            $sth->bindParam("makina_is_button_id", $makina_is_button_id);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("departman_id", $departman_id);
            $durum = $sth->execute();
        }

        
    }


    $sql = "UPDATE departman_planlama 
    SET stok = :stok, birim_id = :birim_id, etiket_varmi = :etiket_varmi, 
    genislik = :genislik, yukseklik = :yukseklik, etiket_tasarim = :etiket_tasarim 
    WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('stok', $stok);
    $sth->bindParam('birim_id', $birim_id);
    $sth->bindParam('etiket_varmi', $etiket_varmi);
    $sth->bindParam('genislik', $genislik);
    $sth->bindParam('yukseklik', $yukseklik);
    $sth->bindParam('etiket_tasarim', $etiket_tasarim);
    $sth->bindParam('id', $id);
    $durum = $sth->execute();

    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
    }

    header("Location: /index.php?url=departman_planlama&id={$departman_id}");
    exit;
}