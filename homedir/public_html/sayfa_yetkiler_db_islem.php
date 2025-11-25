<?php 
 
require_once "include/oturum_kontrol.php";

//echo "<pre>"; print_R($_POST);

if(isset($_POST['sayfa-yetki-kaydet'])){
    $aktif_tab           = $_POST['aktif_tab'];
    $yetki_id           = $_POST['yetki_id'];
    $yetki_sayfalar_id  = $_POST['yetki_sayfalar_id'];
    $sayfa_idler        = json_encode(array_map('intval', $_POST['sayfa_idler']));

    if($yetki_sayfalar_id == 0){ //yoksa ekle
        $sql = "INSERT INTO yetki_sayfalar(firma_id, yetki_id, sayfa_idler) 
                VALUES(:firma_id, :yetki_id, :sayfa_idler);";
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->bindParam("yetki_id", $yetki_id);
        $sth->bindParam("sayfa_idler", $sayfa_idler);
        $durum = $sth->execute();
    }else{ //varsa güncelle
        $sql = "UPDATE yetki_sayfalar SET sayfa_idler = :sayfa_idler  WHERE id = :yetki_sayfalar_id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('sayfa_idler', $sayfa_idler);
        $sth->bindParam('yetki_sayfalar_id', $yetki_sayfalar_id);
        $durum = $sth->execute();
    }
    header("Location: /index.php?url=sayfa_yetkiler&aktif_tab={$aktif_tab}");
    exit;
}