<?php 

require_once "include/oturum_kontrol.php";
require_once 'vendor/autoload.php';

use Dompdf\Dompdf; 
#qr kodu pdf yazdırma login kontrol gerek yok
if(isset($_GET['islem']) && $_GET['islem'] == 'stok_alt_depo_qr')
{
    $dompdf = new Dompdf();
    $dompdf->getOptions()->setChroot($_SERVER['DOCUMENT_ROOT']);
    $html = "
    <div style='text-align:center'>
        <img src='./dosyalar/qr-code/".$_GET['qr_kod']."' style='width:250px;height:250px'>
    </div>
    ";

    //echo $html; exit;
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4'); 
    $dompdf->render(); 
    $dompdf->stream(date('dmY_His').'.pdf');
    exit;
}



//stok alt depo ekleme
if(isset($_POST['stok_alt_depo_ekle']))
{
    $stok_id                = $_POST['stok_id'];
    $stok_alt_kalem_id      = $_POST['stok_alt_kalem_id'];
    $adet                   = $_POST['adet'];
    $para_cinsi             = $_POST['para_cinsi'];
    $birim_id               = $_POST['birim_id'];
    $maliyet                = $_POST['maliyet'];
    $fatura_no              = $_POST['fatura_no'];
    $tedarikci_id           = $_POST['tedarikci_id'];
    $stok_alt_depo_kod      = $_POST['stok_alt_depo_kod'] ? $_POST['stok_alt_depo_kod'] :  NULL;
    $stok_kodu              = $_POST['stok_kodu']."-".random_int(100_000,999_999);

    if($stok_alt_depo_kod){
        $sth = $conn->prepare('SELECT siparis_no FROM `siparisler` WHERE stok_alt_depo_kod  = :stok_alt_depo_kod;');
        $sth->bindParam('stok_alt_depo_kod', $stok_alt_depo_kod );
        $sth->execute();
        $siparis = $sth->fetch(PDO::FETCH_ASSOC);
    }
    
    $barcode = new \Com\Tecnick\Barcode\Barcode();
    $hedef_klasor = "dosyalar/qr-code/";

    $sth = $conn->prepare('SELECT veri FROM `stok_alt_kalemler` WHERE id = :id');
    $sth->bindParam('id', $stok_alt_kalem_id);
    $sth->execute();
    $stok_alt_kalem = $sth->fetch(PDO::FETCH_ASSOC);
    $veriler = json_decode($stok_alt_kalem['veri'], true);

    $data = "";
    foreach ($veriler as $stok_alt_kalem_adi => $veri) { 
        $data .= sprintf("%-10s  : %-10s\n",$stok_alt_kalem_adi, $veri);
        //$data .= $stok_alt_kalem_adi."\t : ".$veri;
        //$data .= "\n".str_repeat("-",25)."\n";
    }

    $sth = $conn->prepare('SELECT firma_adi FROM `tedarikciler` WHERE id = :id');
    $sth->bindParam('id', $tedarikci_id);
    $sth->execute();
    $tedarikci = $sth->fetch(PDO::FETCH_ASSOC);

    $data .= "\nTedarikçi    : ".$tedarikci['firma_adi'];
    $data .= "\nSipariş No  : ".($stok_alt_depo_kod ? $siparis['siparis_no']: 'Siparişe Özel Değil');
    $data .= "\n\nStok Kodu  : ".$stok_kodu;

    $qrcodeObj = $barcode->getBarcodeObj('QRCODE,H', $data, - 16, - 16, 'black', [ - 2, - 2, - 2,- 2])->setBackgroundColor('#f5f5f5');
    $imageData = $qrcodeObj->getPngData();
    $qr_kod = $stok_kodu.'.png';
    file_put_contents($hedef_klasor. $qr_kod, $imageData);

    $sql = "INSERT INTO stok_alt_depolar(firma_id, stok_alt_kalem_id,siparis_no, stok_alt_depo_kod, adet, para_cinsi, birim_id, stok_kodu, maliyet, tedarikci_id, fatura_no, qr_kod) 
    VALUES(:firma_id, :stok_alt_kalem_id,:siparis_no, :stok_alt_depo_kod, :adet,:para_cinsi, :birim_id, :stok_kodu, :maliyet, :tedarikci_id, :fatura_no, :qr_kod);";
    
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem_id);
    $sth->bindValue("siparis_no", $stok_alt_depo_kod ? $siparis['siparis_no'] : NULL);
    $sth->bindParam("stok_alt_depo_kod", $stok_alt_depo_kod);
    $sth->bindParam("adet", $adet);
    $sth->bindParam("para_cinsi", $para_cinsi);
    $sth->bindParam("birim_id", $birim_id);
    $sth->bindParam("stok_kodu", $stok_kodu);
    $sth->bindParam("maliyet", $maliyet);
    $sth->bindParam("tedarikci_id", $tedarikci_id);
    $sth->bindParam("fatura_no", $fatura_no);
    $sth->bindParam("qr_kod", $qr_kod);
    $durum = $sth->execute();

    if($durum)
    {
        $sql = "UPDATE stok_alt_kalemler SET toplam_stok = toplam_stok  + {$adet}, birim_id = :birim_id WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $stok_alt_kalem_id);
        $sth->bindParam('birim_id', $birim_id);
        $durum = $sth->execute();
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }
    header("Location: /index.php?url=stok_alt_depolar&stok_alt_kalem_id={$stok_alt_kalem_id}&stok_id={$stok_id}");
    die();
}

//stok alt depo güncelleme
if(isset($_POST['stok_alt_depo_guncelle']))
{
    //echo "<pre>"; print_r($_POST);exit;
    $id                     = $_POST['id'];
    $stok_alt_kalem_id      = $_POST['stok_alt_kalem_id'];
    $stok_kodu              = $_POST['stok_kodu'];
    $adet                   = $_POST['adet'];
    $para_cinsi             = $_POST['para_cinsi'];
    $maliyet                = $_POST['maliyet'];
    $fatura_no              = $_POST['fatura_no'];
    $tedarikci_id           = $_POST['tedarikci_id'];
    $stok_alt_depo_kod      = $_POST['stok_alt_depo_kod'] ? $_POST['stok_alt_depo_kod'] :  NULL;
    $stok_id                = $_POST['stok_id'];

    if($stok_alt_depo_kod){
        $sth = $conn->prepare('SELECT siparis_no FROM `siparisler` WHERE stok_alt_depo_kod  = :stok_alt_depo_kod;');
        $sth->bindParam('stok_alt_depo_kod', $stok_alt_depo_kod );
        $sth->execute();
        $siparis = $sth->fetch(PDO::FETCH_ASSOC);
    }

    $barcode        = new \Com\Tecnick\Barcode\Barcode();
    $hedef_klasor   = "dosyalar/qr-code/";

    $sth = $conn->prepare('SELECT veri FROM `stok_alt_kalemler` WHERE id = :id');
    $sth->bindParam('id', $stok_alt_kalem_id);
    $sth->execute();
    $stok_alt_kalem = $sth->fetch(PDO::FETCH_ASSOC);
    $veriler = json_decode($stok_alt_kalem['veri'], true);

    $data = "";
    foreach ($veriler as $stok_alt_kalem_adi => $veri) { 
        $data .= sprintf("%-10s  : %-10s\n",$stok_alt_kalem_adi, $veri);
    }

    $sth = $conn->prepare('SELECT firma_adi FROM `tedarikciler` WHERE id = :id');
    $sth->bindParam('id', $tedarikci_id);
    $sth->execute();
    $tedarikci = $sth->fetch(PDO::FETCH_ASSOC);

    $data .= "\nTedarikçi    : ".$tedarikci['firma_adi'];
    $data .= "\nSipariş No  : ".($stok_alt_depo_kod ? $siparis['siparis_no']: 'Siparişe Özel Değil');
    $data .= "\n\nStok Kodu  : ".$stok_kodu;

    $qrcodeObj = $barcode->getBarcodeObj('QRCODE,H', $data, - 16, - 16, 'black', [ - 2, - 2, - 2,- 2])->setBackgroundColor('#f5f5f5');
    $imageData = $qrcodeObj->getPngData();
    $qr_kod = $stok_kodu.'.png';
    if(file_exists($hedef_klasor. $qr_kod)) unlink($hedef_klasor. $qr_kod);
    file_put_contents($hedef_klasor. $qr_kod, $imageData);


    $sql = "UPDATE stok_alt_depolar SET adet = :adet, para_cinsi = :para_cinsi, maliyet = :maliyet, fatura_no = :fatura_no, 
            tedarikci_id = :tedarikci_id, stok_alt_depo_kod = :stok_alt_depo_kod, siparis_no = :siparis_no, qr_kod = :qr_kod  
            WHERE id = :id AND firma_id = :firma_id;";
    $sth = $conn->prepare($sql);
    $sth->bindParam('adet', $adet);
    $sth->bindParam('para_cinsi', $para_cinsi);
    $sth->bindParam('maliyet', $maliyet);
    $sth->bindParam('fatura_no', $fatura_no);
    $sth->bindParam('tedarikci_id', $tedarikci_id);
    $sth->bindParam('stok_alt_depo_kod', $stok_alt_depo_kod);
    $sth->bindValue("siparis_no", $stok_alt_depo_kod ? $siparis['siparis_no'] : NULL);
    $sth->bindParam('qr_kod', $qr_kod);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute();

    if($durum)
    {
        
        $sth = $conn->prepare('SELECT SUM(adet) AS toplam_adet FROM stok_alt_depolar WHERE stok_alt_kalem_id = :stok_alt_kalem_id');
        $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem_id);
        $sth->execute();
        $toplam = $sth->fetch(PDO::FETCH_ASSOC);
        
        $sql = "UPDATE stok_alt_kalemler SET toplam_stok = :toplam_stok WHERE id = :id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('toplam_stok', $toplam['toplam_adet']);
        $sth->bindParam('id', $stok_alt_kalem_id);
        $durum = $sth->execute();
        
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Güncelleme İşlem Başarılı';
        header("Location: /index.php?url=stok_alt_depolar&stok_alt_kalem_id={$stok_alt_kalem_id}&stok_id={$stok_id}");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Güncelleme İşlem Başarısız';
        header('Location: /index.php?url=stok_alt_depolar_guncelle&id='.$id);
    }
    die();

}

//stok alt depo silme
if(isset($_GET['islem']) && $_GET['islem'] == 'stok_alt_depo_sil')
{
    $id             = intval($_GET['id']);
    $stok_id        = intval($_GET['stok_id']);
    $stok_alt_kalem_id   = intval($_GET['stok_alt_kalem_id']);

    $sth = $conn->prepare('SELECT adet FROM stok_alt_depolar WHERE id = :id');
    $sth->bindParam('id', $id);
    $sth->execute();
    $stok_alt_depo = $sth->fetch(PDO::FETCH_ASSOC);
    
    $sql = "DELETE FROM stok_alt_depolar WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $durum = $sth->execute(); 

    if($durum)
    {
        $sql = "UPDATE stok_alt_kalemler SET toplam_stok = toplam_stok  - {$stok_alt_depo['adet']} WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $stok_alt_kalem_id);
        $durum = $sth->execute();
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
    }
    else
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
    }

    header("Location: /index.php?url=stok_alt_depolar&stok_alt_kalem_id={$stok_alt_kalem_id}&stok_id={$stok_id}");
    die();
    
}

//Stok Alt Depo Düşme(Elle)
if(isset($_POST['stok_alt_depo_dusme'])){
    //echo "<pre>"; print_R($_POST);exit;
    $stok_id            = $_POST['stok_id'];
    $stok_alt_depo_id   = $_POST['stok_alt_depo_id'];
    $birim_id           = $_POST['birim_id'];
    $adet               = $_POST['adet'];
    $aciklama           = $_POST['aciklama'];

    $sql = "SELECT id  FROM `stok_alt_kalemler` WHERE stok_id = :stok_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("stok_id", $stok_id);
    $sth->execute();
    $stok_alt_kalem = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = "INSERT INTO stok_alt_depolar_kullanilanlar(stok_id, stok_alt_kalem_id, stok_alt_depo_id, 
            planlama_id, personel_id, makina_id, mevcut_asama, fire_miktari, tuketim_miktari, 
            fason_geri_gelenmeyen_miktar, birim_id, departman_id, aciklama) 
            VALUES(:stok_id, :stok_alt_kalem_id, :stok_alt_depo_id, 
            :planlama_id, :personel_id, :makina_id, :mevcut_asama, :fire_miktari, :tuketim_miktari, 
            :fason_geri_gelenmeyen_miktar,:birim_id, :departman_id, :aciklama);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("stok_id", $stok_id);
    $sth->bindParam("stok_alt_kalem_id", $stok_alt_kalem['id']);
    $sth->bindParam("stok_alt_depo_id", $stok_alt_depo_id);
    $sth->bindValue("planlama_id", 0);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $sth->bindValue("makina_id", 99);
    $sth->bindValue("mevcut_asama", 99);
    $sth->bindValue("fire_miktari", 0);
    $sth->bindParam("tuketim_miktari", $adet);
    $sth->bindValue("fason_geri_gelenmeyen_miktar", 0);
    $sth->bindParam("birim_id", $birim_id);
    $sth->bindValue("departman_id",0);
    $sth->bindParam("aciklama",$aciklama);
    $durum = $sth->execute();

    header("Location: /index.php?url=stok_alt_depolar&stok_alt_kalem_id={$stok_alt_kalem['id']}&stok_id={$stok_id}");
    die();
}