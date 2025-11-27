<?php

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);

require_once "include/oturum_kontrol.php";

//excel çıkarma işlemi
if(isset($_GET['islem']) && $_GET['islem'] == 'arsiv_kalem_csv')
{

    //https://www.codexworld.com/export-data-to-csv-file-using-php-mysql/
    $delimiter = ","; 
    $filename = "arsiv_kalemler_" . date('Y-m-d-His') . ".csv"; 

    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 


    $fields = ['SIRA','ARŞİV', 'DEPARTMAN']; 
    fputcsv($f, $fields, $delimiter); 

    $sth = $conn->prepare('SELECT arsiv_kalemler.*, departmanlar.departman FROM arsiv_kalemler 
    JOIN departmanlar  ON arsiv_kalemler.departman_id = departmanlar.id
    WHERE arsiv_kalemler.firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);


    foreach ($personeller as $key=> $personel) {
        $lineData = [
            $key+1, $personel['arsiv'], $personel['departman']
        ]; 
        fputcsv($f, $lineData, $delimiter); 
    }

    fseek($f, 0); 
     
    // Set headers to download file rather than displayed 
    header('Content-Type: text/csv'); 
    header('Content-Disposition: attachment; filename="' . $filename . '";'); 
     
    //output all remaining data on a file pointer 
    fpassthru($f); 

}


#arsiv kalem ekle
if(isset($_POST['arsiv_kalem_ekle']))
{
    $arsiv            = $_POST['arsiv'];
    $departman_idler  = isset($_POST['departman_idler']) ? 
    json_encode(array_map('intval',$_POST['departman_idler'])) : json_encode([]);
    $arsiv_tur_id     = $_POST['arsiv_tur_id'];
    
    $sql = "INSERT INTO arsiv_kalemler(firma_id, departman_idler, arsiv, arsiv_tur_id) VALUES(:firma_id, :departman_idler, :arsiv, :arsiv_tur_id);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("departman_idler", $departman_idler);
    $sth->bindParam("arsiv", $arsiv);
    $sth->bindParam("arsiv_tur_id", $arsiv_tur_id);
    $durum = $sth->execute();
 
    if($durum == true)
    {
        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['arşiv', 'dosya', 'belge', 'archive'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        #echo "<h2>Ekleme başarılı</h2>";
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
        header('Location: /index.php?url=arsiv_kalem');
    }
    else 
    {
        #echo "<h2>ekleme başarısız</h2>";
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
        header('Location: /index.php?url=arsiv_kalem');
    }
    die();
}


#Arsiv Kalem sil
if(isset($_GET['islem']) && $_GET['islem'] == 'arsiv_kalem_sil')
{
    $id = $_GET['id'];

    $sql = "DELETE FROM arsiv_kalemler WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    
    if($durum == true)
    {
        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['arşiv', 'dosya', 'belge', 'archive'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        #echo "<h2>Ekleme başarılı</h2>";
        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
        #echo "<h2>ekleme başarısız</h2>";
    }
    header('Location: /index.php?url=arsiv_kalem');
    die();
}


#Arsiv Kalem guncelle
if(isset($_POST['arsiv_kalem_guncelle']))
{
    $id                 = $_POST['id'];
    $arsiv              = $_POST['arsiv'];
    $departman_idler  = isset($_POST['departman_idler']) ? 
    json_encode(array_map('intval',$_POST['departman_idler'])) : json_encode([]);
    $arsiv_tur_id     = $_POST['arsiv_tur_id'];
    $arsiv_tur_id       = $_POST['arsiv_tur_id']; 
    
    
    $sql = "UPDATE arsiv_kalemler SET arsiv = :arsiv, departman_idler = :departman_idler, arsiv_tur_id = :arsiv_tur_id WHERE id = :id AND firma_id = :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam("arsiv", $arsiv);
    $sth->bindParam("departman_idler", $departman_idler);
    $sth->bindParam("id", $id);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("arsiv_tur_id", $arsiv_tur_id);

    $durum = $sth->execute();

    if($durum == true)
    {
        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['arşiv', 'dosya', 'belge', 'archive'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';

        header("Location: /index.php?url=arsiv_kalem");
    }
    else 
    {
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
        header("Location: /index.php?url=arsiv_kalem_guncelle&id={$id}");
    }
    die();
}
