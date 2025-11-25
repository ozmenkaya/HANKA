<?php 

require_once "include/oturum_kontrol.php";

//echo "<pre>"; print_r($_POST); exit;
//Makina Arıza Log Ekleme
if(isset($_POST['islem']) && $_POST['islem'] == 'makina_ariza_log'){
    //echo 21; exit;
    $uretim_bakim_log_id    = intval($_POST['uretim_bakim_log_id']);
    $makina_id              = intval($_POST['makina_ariza_id']);
    $makina_durum           = $_POST['makina_ariza_durum'];
    $konu                   = trim($_POST['ariza_konu']);

    //makina bakim log
    $sql = "INSERT INTO makina_bakim_log(makina_id,durum, konu) VALUES(:makina_id,:durum, :konu);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("durum", $makina_durum);
    $sth->bindParam("konu", $konu);
    $durum = $sth->execute();

    //MAKİNA durumunu değiştirme
    $sql = "UPDATE makinalar SET durumu = :durumu  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('durumu', $makina_durum);
    $sth->bindParam('id', $makina_id);
    $durum = $sth->execute();

    //uretim_bakim_log
    $sql = "UPDATE uretim_bakim_log SET bitis_tarihi = :bitis_tarihi, durum = 'bakıldı'  WHERE id = :id;";
    $sth = $conn->prepare($sql);
    $sth->bindValue('bitis_tarihi', date('Y-m-d H:i:s'));
    $sth->bindParam('id', $uretim_bakim_log_id);
    $durum = $sth->execute();
    header("Location: /index.php?url=makina_bakim"); exit;

}

//Makina Bakım Logları Getirme
if(isset($_GET['islem']) && $_GET['islem'] == 'makina_log_getir'){
    $makina_id = intval($_GET['makina_id']);
    $sql = "SELECT durum, konu, DATE_FORMAT(tarih, '%d-%m-%Y %H:%i:%s') AS tarih FROM `makina_bakim_log` WHERE makina_id = :makina_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('makina_id', $makina_id);
    $sth->execute();
    $makina_bakim_log = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode(['makina_bakim_loglar'=>$makina_bakim_log]); exit;
}

//Makina Bakım Log Ekleme
if(isset($_POST['makina_bakim_log']))
{
    $makina_id              = intval($_POST['makina_id']);
    $makina_durum           = $_POST['makina_durum'];
    $konu                   = trim($_POST['konu']);

    //makina bakim log
    $sql = "INSERT INTO makina_bakim_log(makina_id,durum, konu) VALUES(:makina_id,:durum, :konu);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("makina_id", $makina_id);
    $sth->bindParam("durum", $makina_durum);
    $sth->bindParam("konu", $konu);
    $durum = $sth->execute();

    //makinanın bilgisi
    $sql = 'SELECT makina_bakim_suresi,makina_son_bakim_tarih FROM makinalar WHERE id = :id';
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $makina_id);
    $sth->execute();
    $makina = $sth->fetch(PDO::FETCH_ASSOC);

    //makina bilgilerini güncelleme
    if($durum == 'aktif'){
        $sql = "UPDATE makinalar SET durumu = :durumu ,makina_son_bakim_tarih =  :makina_son_bakim_tarih  
                WHERE id = :id;";
    }else{
        $sql = "UPDATE makinalar SET durumu = :durumu  WHERE id = :id;";
    }
    $sth = $conn->prepare($sql);
    $sth->bindParam('durumu', $makina_durum);
    if($durum == 'aktif'){
        $sth->bindValue('makina_son_bakim_tarih', date('Y-m-d', strtotime("+{$makina['makina_bakim_suresi']} months", strtotime($makina['makina_son_bakim_tarih']))));
    }
    $sth->bindParam('id', $makina_id);
    $durum = $sth->execute();

    header("Location: /index.php?url=makina_bakim"); exit;
}