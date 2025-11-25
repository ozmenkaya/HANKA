<?php

include "include/oturum_kontrol.php";

//departmanları getirme
if(isset($_GET['islem']) && $_GET['islem'] == 'departman-getir')
{
    $sth = $conn->prepare('SELECT departmanlar.* FROM departmanlar 
    JOIN departman_planlama ON departman_planlama.departman_id = departmanlar.id
    WHERE departmanlar.firma_id = :firma_id ORDER BY `departmanlar`.`departman` ASC');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode($departmanlar);
    exit;
}

// departman id ye göre makinaları getir
if(isset($_GET['islem']) && $_GET['islem'] == 'depatmana-gore-makina-getir')
{
    $departman_id = $_GET['departman_id'];
    $sth = $conn->prepare('SELECT id, makina_adi, makina_modeli FROM makinalar 
        WHERE departman_id = :departman_id AND firma_id = :firma_id AND durumu = "aktif"');
    $sth->bindParam('departman_id', $departman_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    ($makinalar); exit;
}

//departman id ye göre arşiv getirme
if(isset($_GET['islem']) && $_GET['islem'] == 'depatmana-gore-arsiv-getir')
{
    $siparis_id     = $_GET['siparis_id'];
    $departman_id   = $_GET['departman_id'];

    $sth = $conn->prepare('SELECT arsiv_kod FROM siparisler WHERE id = :siparis_id AND firma_id = :firma_id');
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT arsiv_altlar.id, arsiv_altlar.kod, arsiv_altlar.ebat, arsiv_kalemler.arsiv FROM `arsiv_altlar` 
        JOIN  arsiv_kalemler  ON arsiv_altlar.arsiv_id  =  arsiv_kalemler.id 
        WHERE arsiv_kalemler.departman_id = :departman_id AND arsiv_altlar.arsiv_kod = :arsiv_kod";

    $sth = $conn->prepare($sql);
    $sth->bindParam('departman_id', $departman_id);
    $sth->bindParam('arsiv_kod', $siparis['arsiv_kod'] );
    $sth->execute();
    $arsiv_altlar = $sth->fetchAll(PDO::FETCH_ASSOC);
    //echo json_encode($siparis);

    ob_clean();
    echo json_encode($arsiv_altlar); exit;

}


//arsiv alt id gore resimleri getirme
if(isset($_GET['islem']) && $_GET['islem'] == 'arsiv-alt-id-gore-resimleri-getir')
{
    $arsiv_alt_id   = $_GET['arsiv_alt_id'];
    //echo $arsiv_alt_id;
    $sth = $conn->prepare('SELECT ad FROM arsiv_alt_dosyalar WHERE arsiv_alt_id = :arsiv_alt_id');
    $sth->bindParam('arsiv_alt_id', $arsiv_alt_id);
    $sth->execute();
    $arsiv_alt_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode($arsiv_alt_dosyalar);
}


//planlama için stok aşamaları getirme
if(isset($_GET['islem']) && $_GET['islem'] == 'stok-asama-getirme')
{
    $departman_id = intval($_GET['departman_id']);

    $sth = $conn->prepare('SELECT departman_planlama.stok,birimler.ad FROM departman_planlama 
            JOIN birimler ON birimler.id = departman_planlama.birim_id
            WHERE departman_planlama.firma_id = :firma_id AND  departman_planlama.departman_id = :departman_id' );
    $sth->bindParam('departman_id', $departman_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $stok_asama = $sth->fetch(PDO::FETCH_ASSOC);
    $stok_asamalar = json_decode($stok_asama['stok']);

    $data = [];
    foreach ($stok_asamalar as $stok_id) {

        $sth = $conn->prepare('SELECT stok_kalem FROM stok_kalemleri WHERE firma_id = :firma_id AND  id = :stok_id' );
        $sth->bindParam('stok_id', $stok_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $stok_kalem = $sth->fetch(PDO::FETCH_ASSOC);

        $sth = $conn->prepare('SELECT id, veri, toplam_stok FROM stok_alt_kalemler WHERE firma_id = :firma_id AND stok_id = :stok_id');
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('stok_id', $stok_id);
        $sth->execute();
        $data[] = [
            'stok_kalem'    =>  $stok_kalem['stok_kalem'],
            'veri'          =>  $sth->fetchAll(PDO::FETCH_ASSOC),
            'birim_ad'      =>  $stok_asama['ad']
        ];
    }

    ob_clean();
    echo json_encode($data);
    exit;
}


// departmana göre birim getir
if(isset($_GET['islem']) && $_GET['islem'] == 'depatmana-gore-birim-getir')
{
    $departman_id = intval($_GET['departman_id']);

    $sql = "SELECT birimler.ad FROM `departman_planlama` 
        JOIN birimler ON birimler.id = departman_planlama.birim_id   
        WHERE departman_planlama.firma_id = :firma_id AND departman_planlama.departman_id = :departman_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('departman_id', $departman_id);
    $sth->execute();
    
    ob_clean();
    echo json_encode($sth->fetch(PDO::FETCH_ASSOC));
    exit;

}

// stok kalem id göre birim getirme işlemi
if(isset($_GET['islem']) && $_GET['islem'] == 'stok-kalem-id-gore-birim-getir')
{
    $stok_alt_kalem_id = intval($_GET['stok_alt_kalem_id']);

    $sql = "SELECT birimler.ad FROM stok_alt_depolar 
        JOIN birimler ON birimler.id = stok_alt_depolar.birim_id 
        WHERE stok_alt_depolar.firma_id = :firma_id AND stok_alt_depolar.stok_alt_kalem_id = :stok_alt_kalem_id";

    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem_id);
    $sth->execute();

    ob_clean();
    echo json_encode($sth->fetch(PDO::FETCH_ASSOC));
    exit;
}

//fason olan tedarikcileri getir
if(isset($_GET['islem']) && $_GET['islem'] == 'fason-olan-tedarikci')
{
    $sql = "SELECT id, firma_adi FROM `tedarikciler` WHERE firma_id = :firma_id AND fason='evet' ";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();

    ob_clean();
    echo json_encode($sth->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

