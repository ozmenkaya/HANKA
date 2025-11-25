<?php 

#echo "<pre>"; print_r($_POST);
#Sipari Form Değer ekle
if(isset($_POST['deger_ekle']))
{
    $deger          = mb_strtoupper(trim($_POST['deger']));
    $firma_id       = intval($_SESSION['firma_id']);
    $tur_idler      = json_encode(array_map('intval',$_POST['tur_id']));

    $options_raw    = trim($_POST['options'] ?? ''); // Boş gelirse null
    if (!empty($options_raw)) {
        $options_array = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $options_raw)));
        $options_json  = !empty($options_array) ? json_encode(array_values($options_array), JSON_UNESCAPED_UNICODE) : null;
    } else {
        $options_json = null;
    }

    $sql            = "SELECT * FROM `siparis_form`  WHERE  firma_id = :firma_id AND deger = :deger  LIMIT 1";
    $sth            = $conn->prepare($sql);
    $sth->bindParam('firma_id', $firma_id);
    $sth->bindParam('deger', $deger);
    $sth->execute();
    $kontrol        = $sth->fetch(PDO::FETCH_ASSOC);
    
    if(!empty($kontrol)){
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Daha Önceden Mevcut';
        header('Location: /index.php?url=siparis_form');
        exit;
    }

    
    $sql = "INSERT INTO siparis_form(firma_id, deger, tur_idler, options) 
            VALUES(:firma_id, :deger, :tur_idler, :options);";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $firma_id);
    $sth->bindParam('deger', $deger);
    $sth->bindParam('tur_idler', $tur_idler);
    $sth->bindParam('options', $options_json, PDO::PARAM_NULL | PDO::PARAM_STR); // Eğer NULL ise NULL, değilse STRING

    $durum = $sth->execute();

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
    header('Location: /index.php?url=siparis_form');
    die();
}

#Sipariş Form sil
if(isset($_GET['islem']) && $_GET['islem'] == 'siparis_form_sil')
{
    $id = $_GET['id'];

    $sql = "DELETE FROM siparis_form WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    
    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
        header('Location: /index.php?url=siparis_form');
    }
    else 
    {

        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
        header('Location: /index.php?url=siparis_form');
    }
    die();
}


#Sipariş Form Değer guncelle
if(isset($_POST['siparis_form_guncelle']))
{
    $id         = $_POST['id'];
    $deger      = mb_strtoupper(trim($_POST['deger']));
    $tur_idler  = json_encode(array_map('intval',$_POST['tur_id']));

    $options_raw    = trim($_POST['options'] ?? ''); // Boş gelirse null
    if (!empty($options_raw)) {
        $options_array = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $options_raw)));
        $options_json  = !empty($options_array) ? json_encode(array_values($options_array), JSON_UNESCAPED_UNICODE) : null;
    } else {
        $options_json = null;
    }
    
    $sql = "UPDATE siparis_form SET deger = :deger, tur_idler = :tur_idler, options = :options WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("deger", $deger);
    $sth->bindParam("tur_idler", $tur_idler);
    $sth->bindParam('options', $options_json, PDO::PARAM_NULL | PDO::PARAM_STR); // Eğer NULL ise NULL, değilse STRING

    $sth->bindParam("id", $id);

    $durum = $sth->execute();

    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header("Location: /index.php?url=siparis_form");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
        header("Location: /index.php?url=siparis_form_guncelle&id={$id}");
    }
    die();
}
