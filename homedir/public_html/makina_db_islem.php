<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/php_errors.log');

require_once "include/oturum_kontrol.php";

#Makina ekle
if(isset($_POST['makina_ekle']))
{
    //echo "<pre>"; print_r($_POST); exit;
    $makina_adi                 = trim($_POST['makina_adi']);
    $makina_modeli              = trim($_POST['makina_modeli']);
    $makina_seri_no             = trim($_POST['makina_seri_no']);
    $departman_id               = trim($_POST['departman_id']);
    $makina_bakim_suresi        = trim($_POST['makina_bakim_suresi']);
    $makina_son_bakim_tarih     = trim($_POST['makina_son_bakim_tarih']);
    $durumu                     = $_POST['durumu'];
    $makina_ayar_suresi_varmi   = $_POST['makina_ayar_suresi_varmi'];
    $planlamada_goster          = $_POST['planlamada_goster'];
    $stoga_geri_gonderme_durumu = isset($_POST['stoga_geri_gonderme_durumu']) ? 'evet':'hayır';
    $aciklama                   = trim($_POST['aciklama']);
    $makina_personel_idler      = isset($_POST['makina_personel_idler']) ? $_POST['makina_personel_idler'] : [];
    $makina_bakim_personel_idler = isset($_POST['makina_bakim_personel_idler']) ? $_POST['makina_bakim_personel_idler'] : [];

    $sql = "INSERT INTO makinalar(firma_id, makina_adi, makina_modeli, makina_seri_no, departman_id, makina_bakim_suresi, 
            makina_son_bakim_tarih, durumu, makina_ayar_suresi_varmi, planlamada_goster, stoga_geri_gonderme_durumu, aciklama) 
            VALUES(:firma_id, :makina_adi, :makina_modeli, :makina_seri_no, :departman_id, :makina_bakim_suresi, 
            :makina_son_bakim_tarih, :durumu, :makina_ayar_suresi_varmi, :planlamada_goster, :stoga_geri_gonderme_durumu, :aciklama);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("makina_adi", $makina_adi);
    $sth->bindParam("makina_modeli", $makina_modeli);
    $sth->bindParam("makina_seri_no", $makina_seri_no);
    $sth->bindParam("departman_id", $departman_id);
    $sth->bindParam("makina_bakim_suresi", $makina_bakim_suresi);
    $sth->bindParam("makina_son_bakim_tarih", $makina_son_bakim_tarih);
    $sth->bindParam("durumu", $durumu);
    $sth->bindParam("makina_ayar_suresi_varmi", $makina_ayar_suresi_varmi);
    $sth->bindParam("planlamada_goster", $planlamada_goster);
    $sth->bindParam("stoga_geri_gonderme_durumu", $stoga_geri_gonderme_durumu);
    $sth->bindParam("aciklama", $aciklama);
    $durum = $sth->execute();

    if($durum == true)
    {
        $makina_id = $conn->lastInsertId();
        foreach ($makina_personel_idler  as $makina_personel_id ) {
            $sql = "INSERT INTO makina_personeller(firma_id,makina_id, personel_id) VALUES(:firma_id,:makina_id, :personel_id)";
            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("makina_id", $makina_id);
            $sth->bindParam("personel_id", $makina_personel_id);
            $durum = $sth->execute();
        }

        foreach ($makina_bakim_personel_idler as $makina_bakim_personel_id) {
            $sql = "INSERT INTO makina_bakim_personeller(firma_id, makina_id, personel_id) VALUES(:firma_id,:makina_id, :personel_id)";
            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("makina_id", $makina_id);
            $sth->bindParam("personel_id", $makina_bakim_personel_id);
            $durum = $sth->execute();
        }

        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['makina', 'makine', 'machine', 'arıza', 'bakım'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
        header("Location: /index.php?url=makina");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Bşarısız';
        header("Location: /index.php?url=makina_ekle");
    }
    die();
}

#makina guncelle
if(isset($_POST['makina_guncelle']))
{
    $id                         = trim($_POST['id']);
    $makina_adi                 = trim($_POST['makina_adi']);
    $makina_modeli              = trim($_POST['makina_modeli']);
    $makina_seri_no             = trim($_POST['makina_seri_no']);
    $departman_id               = trim($_POST['departman_id']);
    $makina_bakim_suresi        = trim($_POST['makina_bakim_suresi']);
    $makina_son_bakim_tarih     = trim($_POST['makina_son_bakim_tarih']);
    $durumu                     = $_POST['durumu'];
    $makina_ayar_suresi_varmi   = $_POST['makina_ayar_suresi_varmi'];
    $planlamada_goster          = $_POST['planlamada_goster'];
    $stoga_geri_gonderme_durumu = isset($_POST['stoga_geri_gonderme_durumu']) ? 'evet':'hayır';
    $aciklama                   = trim($_POST['aciklama']);
    $makina_personel_idler      = isset($_POST['makina_personel_idler']) ? $_POST['makina_personel_idler'] : [];
    $makina_bakim_personel_idler = isset($_POST['makina_bakim_personel_idler']) ? $_POST['makina_bakim_personel_idler'] : [];

    $sql = "UPDATE makinalar SET makina_adi = :makina_adi, makina_modeli = :makina_modeli, makina_seri_no = :makina_seri_no,
            departman_id = :departman_id, makina_bakim_suresi = :makina_bakim_suresi, makina_son_bakim_tarih = :makina_son_bakim_tarih,
            durumu = :durumu, makina_ayar_suresi_varmi = :makina_ayar_suresi_varmi, planlamada_goster = :planlamada_goster, 
            stoga_geri_gonderme_durumu = :stoga_geri_gonderme_durumu, aciklama = :aciklama
            WHERE id = :id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('makina_adi', $makina_adi);
    $sth->bindParam('makina_modeli', $makina_modeli);
    $sth->bindParam('makina_seri_no', $makina_seri_no);
    $sth->bindParam('departman_id', $departman_id);
    $sth->bindParam('makina_bakim_suresi', $makina_bakim_suresi);
    $sth->bindParam('makina_son_bakim_tarih', $makina_son_bakim_tarih);
    $sth->bindParam('durumu', $durumu);
    $sth->bindParam('makina_ayar_suresi_varmi', $makina_ayar_suresi_varmi);
    $sth->bindParam('planlamada_goster', $planlamada_goster);
    $sth->bindParam('stoga_geri_gonderme_durumu', $stoga_geri_gonderme_durumu);
    $sth->bindParam('aciklama', $aciklama);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();

    if($durum)
    {
        $sql = "DELETE FROM makina_personeller WHERE makina_id=:makina_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('makina_id', $id);
        $sth->execute(); 

        foreach ($makina_personel_idler  as $makina_personel_id ) {
            $sql = "INSERT INTO makina_personeller(firma_id,makina_id, personel_id) VALUES(:firma_id,:makina_id, :personel_id)";
            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("makina_id", $id);
            $sth->bindParam("personel_id", $makina_personel_id);
            $durum = $sth->execute();
        }

        $sql = "DELETE FROM makina_bakim_personeller WHERE makina_id=:makina_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('makina_id', $id);
        $sth->execute(); 

        foreach ($makina_bakim_personel_idler as $makina_bakim_personel_id) {
            $sql = "INSERT INTO makina_bakim_personeller(firma_id, makina_id, personel_id) VALUES(:firma_id,:makina_id, :personel_id)";
            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("makina_id", $id);
            $sth->bindParam("personel_id", $makina_bakim_personel_id);
            $durum = $sth->execute();
        }

        // AI Cache Invalidation
        if (file_exists("include/AICache.php")) {
            require_once "include/AICache.php";
            try {
                $aiCache = new AICache($conn);
                $aiCache->invalidate(['makina', 'makine', 'machine', 'arıza', 'bakım'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';
        header('Location: /index.php?url=makina');
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';
        header('Location: /index.php?url=makina_guncelle&id='.$id);
    }
    die();
}

#makina sil
if(isset($_GET['islem']) && $_GET['islem'] == 'makina_sil')
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM makinalar WHERE id=:id AND firma_id = :firma_id";
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
                $aiCache->invalidate(['makina', 'makine', 'machine', 'arıza', 'bakım'], $_SESSION['firma_id']);
            } catch (Exception $e) {
                // Cache temizleme hatası önemsiz
            }
        }

        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
    }
    header('Location: /index.php?url=makina');
    die();
}


