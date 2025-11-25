<?php 
include "include/oturum_kontrol.php";


//
if(isset($_POST['siparis_form_tip_guncelle'])){
    //echo "<pre>"; print_r($_POST); exit;
    foreach ($_POST['siparis_form_tip_deger_idler'] as $index => $siparis_form_tip_deger_id) {
        $siparis_form_tip_id    = $_POST['siparis_form_tip_idler'][$index];
        $deger                  = $_POST['siparis_form_tip_degerler'][$index];

        if($siparis_form_tip_deger_id == 0){ //ekle
            $sql = "INSERT INTO siparis_form_tip_degerler(firma_id, siparis_form_tip_id, deger) 
                    VALUES(:firma_id, :siparis_form_tip_id, :deger);";
            $sth = $conn->prepare($sql);
            $sth->bindParam("firma_id", $_SESSION['firma_id']);
            $sth->bindParam("siparis_form_tip_id", $siparis_form_tip_id);
            $sth->bindParam("deger", $deger);
            $durum = $sth->execute();
        }else{ //güncelle
            $sql = "UPDATE siparis_form_tip_degerler SET deger = :deger  WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('deger', $deger);
            $sth->bindParam('id', $siparis_form_tip_deger_id);
            $durum = $sth->execute();
        }
    }


    $_SESSION['durum'] = 'success';
    $_SESSION['mesaj'] = 'İşlem Başarılı';
    header('Location: /index.php?url=siparis_form_tipleri');
    die();
}

?>