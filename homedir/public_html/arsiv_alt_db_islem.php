<?php

//echo "<pre>"; print_r($_FILES);
//echo "<pre>"; print_r($_POST); 
//echo "<pre>"; print_r($_GET); exit;

require_once 'vendor/autoload.php';
require_once "include/oturum_kontrol.php";
require_once "include/helper.php";
use Dompdf\Dompdf; 

//qr kodu pdf yazma işlemi (login kontrol ihtiyacı yok)
if(isset($_GET['islem']) && $_GET['islem'] == 'arsiv_alt_qr'){
    
    $dompdf = new Dompdf(['chroot' => $_SERVER['DOCUMENT_ROOT']]);
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->set_option('isRemoteEnabled', true);  
    //$dompdf->getOptions()->setChroot($_SERVER['DOCUMENT_ROOT']);
    $html = "
    <!doctype html>
    <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
        </head>
        <body>
            <div style='text-align:center'>
                <img src='./dosyalar/qr-code/".$_GET['qr_kod']."' style='width:250px;height:250px'>
            </div>
        </body>
    </html>
    ";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4'); 
    $dompdf->render(); 
    $dompdf->stream(date('dmY_His').'.pdf');
    exit;
}

if(isset($_GET['islem']) && $_GET['islem'] == 'arsiv_alt_excel')
{
    $arsiv_id = intval($_GET['arsiv_id']);
    

    //https://www.codexworld.com/export-data-to-csv-file-using-php-mysql/
    $delimiter = ","; 
    $filename = "arsiv_alt_" . date('Ymd_His') . ".csv"; 
 
    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 
 
 
    $fields = ['SIRA','KOD', 'MÜŞTERİ ADI','SİPARİŞ ADI', 'EBAT','EDAT', 'DETAY', 'AÇIKLAMA']; 
    fputcsv($f, $fields, $delimiter); 
 
    $sth = $conn->prepare('SELECT arsiv_altlar.id, arsiv_id, arsiv_altlar.kod, arsiv_altlar.ebat, arsiv_altlar.adet, 
    arsiv_altlar.detay, arsiv_altlar.aciklama,
    musteri.marka,
    siparisler.isin_adi
    FROM arsiv_altlar JOIN musteri ON musteri.id = arsiv_altlar.musteri_id
    JOIN siparisler ON siparisler.id = arsiv_altlar.siparis_id
    WHERE arsiv_id = :id AND arsiv_altlar.firma_id = :firma_id');
    $sth->bindParam('id', $arsiv_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $arsiv_altlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    foreach ($arsiv_altlar as $key=> $arsiv_alt) {
        $lineData = [
            $key+1, $arsiv_alt['kod'], $arsiv_alt['marka'], $arsiv_alt['isin_adi'], 
            $arsiv_alt['ebat'], $arsiv_alt['adet'], 
            $arsiv_alt['detay'], $arsiv_alt['aciklama']
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

#arsiv alt dosya sil
if(isset($_GET['islem']) && $_GET['islem'] == 'dosyasil')
{
    $storage = new DreamHostStorage($conn);

    $arsiv_alt_dosya_id = trim(intval($_GET['arsiv_alt_dosya_id']));
    $arsiv_alt_id       = trim(intval($_GET['arsiv_alt_id']));
    $ad                 = trim($_GET['ad']);

    $result = $storage->deleteFileFromS3('arsiv', $ad);
    if (!$result) {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Dosya silinirken hata oluştu.';
        header("Location: /index.php?url=arsiv_alt_guncelle&arsiv_alt_id={$arsiv_alt_id}");
        die();
    }

    $sql = "DELETE FROM arsiv_alt_dosyalar WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $arsiv_alt_dosya_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 

    if($durum)
    {
        //unlink('dosyalar/arsivler/'.$ad);
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';

    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';

    }
    header("Location: /index.php?url=arsiv_alt_guncelle&arsiv_alt_id={$arsiv_alt_id}");
    die();
}

#arsiv_alt ekle
if(isset($_POST['arsiv_alt_ekle']))
{
    $storage = new DreamHostStorage($conn);

    try{
        $arsiv_id       = $_POST['arsiv_id'];
        $arsiv_tur_id   = $_POST['arsiv_tur_id'];
        $kod            = trim($_POST['kod']);
        $musteri_id     = intval($_POST['musteri_id']);
        $siparis_id     = $_POST['siparis_id'];
        $detay          = $_POST['detay'];

        // Varsayılan değerler (arsiv_tur_id != 1 için)
        $ebat           = NULL;
        $adet           = NULL;
        $fatura_no      = NULL;
        $maliyet        = NULL;
        $tedarikci_id   = NULL;
        $aciklama       = NULL;
        $durum          = NULL;

        if($arsiv_tur_id == 1){
            $ebat           = $_POST['ebat'];
            $adet           = $_POST['adet'];
            $fatura_no      = $_POST['fatura_no'];
            $maliyet        = $_POST['maliyet'];
            $tedarikci_id   = $_POST['tedarikci_id'];
            $aciklama       = $_POST['aciklama'];
            $durum          = $_POST['durum'];
        }
    
        $sql = "SELECT siparisler.isin_adi,musteri.marka, siparisler.arsiv_kod
                FROM siparisler 
                JOIN musteri ON musteri.id = siparisler.musteri_id
                WHERE siparisler.id = :siparis_id AND siparisler.firma_id = :firma_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('siparis_id', $siparis_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $siparis = $sth->fetch(PDO::FETCH_ASSOC);

        /*
        echo "<pre>";print_R($_POST);
        echo "<pre>";print_R($_FILES);
        echo "<pre>";print_R($siparis);
        exit;
        */
        
        $barcode = new \Com\Tecnick\Barcode\Barcode();
        $hedef_klasor = "dosyalar/qr-code/";

        $data = "→Kod                 : ".$kod;
        $data .= "\n→Müşteri Adı      : ".$siparis['marka'];
        $data .= "\n→Sipariş Adı      : ".$siparis['isin_adi'];

        $qrcodeObj = $barcode->getBarcodeObj('QRCODE,H', $data, - 16, - 16, 'black', [ - 2, - 2, - 2,- 2])->setBackgroundColor('#f5f5f5');
        $imageData = $qrcodeObj->getPngData();
        $qr_kod = time().'.png';
        file_put_contents($hedef_klasor. $qr_kod, $imageData);
        
        $sql = "INSERT INTO arsiv_altlar(firma_id, arsiv_id, arsiv_kod, kod, musteri_id, siparis_id, ebat, adet, fatura_no, maliyet,  detay, tedarikci_id, aciklama, durum, qr_kod) 
                VALUES(:firma_id, :arsiv_id, :arsiv_kod, :kod, :musteri_id, :siparis_id, :ebat, :adet, :fatura_no, :maliyet, :detay,:tedarikci_id, :aciklama, :durum, :qr_kod);";
        $sth = $conn->prepare($sql);
        $sth->bindParam("firma_id", $_SESSION['firma_id']);
        $sth->bindParam("arsiv_id", $arsiv_id);
        $sth->bindParam("arsiv_kod", $siparis['arsiv_kod']);
        $sth->bindParam("kod", $kod);
        $sth->bindParam("musteri_id", $musteri_id);
        $sth->bindParam("siparis_id", $siparis_id);
        $sth->bindParam("ebat", $ebat);
        $sth->bindParam("adet", $adet);
        $sth->bindParam("fatura_no", $fatura_no);
        $sth->bindParam("maliyet", $maliyet);
        $sth->bindParam("detay", $detay);
        $sth->bindParam("tedarikci_id", $tedarikci_id);
        $sth->bindParam("aciklama", $aciklama);
        $sth->bindParam("durum", $durum);
        $sth->bindParam("qr_kod", $qr_kod);
        
        $durum = $sth->execute();
        $arsiv_alt_id = $conn->lastInsertId();


        //echo $siparis_id;

        //$hedef_klasor = "dosyalar/arsivler/";
        if(isset($_FILES['dosya']) && !empty($_FILES['dosya']['name'][0]))
        {
            $dosyalar = $_FILES['dosya'];

            for($i = 0; $i < count($dosyalar['name']); $i++)
            {
                // Boş dosya ismini atla
                if(empty($dosyalar['name'][$i])) continue;

                $dosya_adi     = pathinfo($dosyalar['name'][$i], PATHINFO_FILENAME)."_".random_int(1000, 99999);
                $dosya_uzanti  = pathinfo($dosyalar['name'][$i], PATHINFO_EXTENSION);

                $dosya_adi = "{$dosya_adi}.{$dosya_uzanti}";

                $result = $storage->uploadFileToS3('arsiv', $dosyalar["tmp_name"][$i], $dosya_adi);

                if ($result) 
                {
                    $sql = "INSERT INTO  arsiv_alt_dosyalar(firma_id, arsiv_alt_id, ad) VALUES(:firma_id, :arsiv_alt_id, :ad)";
                    $sth = $conn->prepare($sql);
                    $sth->bindParam("firma_id", $_SESSION['firma_id']);
                    $sth->bindParam("arsiv_alt_id", $arsiv_alt_id);
                    $sth->bindParam("ad", $dosya_adi);
                    $sth->execute();
                }
            }
        }

        if($durum == true)
        {
            $_SESSION['durum'] = 'success';
            $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';

            
            header("Location: /index.php?url=arsiv_alt&arsiv_id={$arsiv_id}");
        }
        else 
        {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
            header("Location: /index.php?url=arsiv_alt_ekle&arsiv_id={$arsiv_id}");
        }
        die();
    } catch (Exception $e) {
        echo "<h3>Error: " . $e->getMessage() . "</h3>";
        echo "<pre>SQL: " . (isset($sql) ? $sql : 'N/A') . "</pre>";
        echo "<pre>POST Data: " . print_r($_POST, true) . "</pre>";
        echo "<pre>Stack Trace: " . $e->getTraceAsString() . "</pre>";
        die();
    }
}

#alt arsiv silme 
if(isset($_GET['islem']) && $_GET['islem'] == 'arsiv_alt_sil')
{
    $arsiv_id     = intval($_GET['arsiv_id']);
    $arsiv_alt_id = intval($_GET['arsiv_alt_id']);

    $sql = "DELETE FROM arsiv_altlar WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $arsiv_alt_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 

    if($durum)
    {
        $_SESSION['durum'] = 'basarili';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';   
    }

    header("Location: /index.php?url=arsiv_alt&arsiv_id={$arsiv_id}");
    die();
}

//alt arsiv güncelle
if(isset($_POST['arsiv_alt_guncelle']))
{
    try{
        $storage = new DreamHostStorage($conn);

        $kod            = trim($_POST['kod']);
        $arsiv_alt_id   = trim($_POST['arsiv_alt_id']);
        $arsiv_id       = trim($_POST['arsiv_id']);
        $arsiv_tur_id   = trim($_POST['arsiv_tur_id']);
        $musteri_id     = trim($_POST['musteri_id']);
        $siparis_id     = trim($_POST['siparis_id']); 
        $detay          = trim($_POST['detay']);   

        // Varsayılan değerler (arsiv_tur_id != 1 için)
        $ebat           = NULL;
        $adet           = NULL;
        $fatura_no      = NULL;
        $maliyet        = NULL;
        $tedarikci_id   = NULL;
        $aciklama       = NULL;
        $durum          = NULL;

        if($arsiv_tur_id == 1){
            $ebat           = $_POST['ebat'];
            $adet           = $_POST['adet'];
            $fatura_no      = $_POST['fatura_no'];
            $maliyet        = $_POST['maliyet'];
            $tedarikci_id   = $_POST['tedarikci_id'];
            $aciklama       = $_POST['aciklama'];
            $durum          = $_POST['durum'];
        }

        $sql = "SELECT siparisler.isin_adi,musteri.marka,siparisler.arsiv_kod
                FROM siparisler 
                JOIN musteri ON musteri.id = siparisler.musteri_id
                WHERE siparisler.id = :siparis_id AND siparisler.firma_id = :firma_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('siparis_id', $siparis_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $siparis = $sth->fetch(PDO::FETCH_ASSOC);

        $barcode = new \Com\Tecnick\Barcode\Barcode();
        $hedef_klasor = "dosyalar/qr-code/";


        $data = "→Kod                 : ".$kod;
        $data .= "\n→Müşteri Adı    : ".$siparis['marka'];
        $data .= "\n→Sipariş Adı     : ".$siparis['isin_adi'];

        $qrcodeObj = $barcode->getBarcodeObj('QRCODE,H', $data, - 16, - 16, 'black', [ - 2, - 2, - 2,- 2])->setBackgroundColor('#f5f5f5');
        $imageData = $qrcodeObj->getPngData();
        $qr_kod = time().'.png';
        file_put_contents($hedef_klasor. $qr_kod, $imageData);


        //$hedef_klasor = "dosyalar/arsivler/";

        if(!empty($_FILES['dosya']['name'][0]))
        {
            $dosyalar = $_FILES['dosya'];

            for($i = 0; $i < count($dosyalar['name']); $i++)
            {
                // Boş dosya ismini atla
                if(empty($dosyalar['name'][$i])) continue;

                $dosya_adi     = pathinfo($dosyalar['name'][$i], PATHINFO_FILENAME)."_".random_int(1000, 99999);
                $dosya_uzanti  = pathinfo($dosyalar['name'][$i], PATHINFO_EXTENSION);

                $dosya_adi = "{$dosya_adi}.{$dosya_uzanti}";

                $result = $storage->uploadFileToS3('arsiv', $dosyalar["tmp_name"][$i], $dosya_adi);

                // move_uploaded_file($dosyalar["tmp_name"][$i], $hedef_klasor.$dosya_adi)
                if ($result) 
                {
                    $sql = "INSERT INTO  arsiv_alt_dosyalar(firma_id, arsiv_alt_id, ad) VALUES(:firma_id, :arsiv_alt_id, :ad)";
                    $sth = $conn->prepare($sql);
                    $sth->bindParam("firma_id", $_SESSION['firma_id']);
                    $sth->bindParam("arsiv_alt_id", $arsiv_alt_id);
                    $sth->bindParam("ad", $dosya_adi);
                    $sth->execute();
                }
            }
        }
        $sql = "UPDATE arsiv_altlar SET musteri_id = :musteri_id, siparis_id = :siparis_id, ebat = :ebat, adet = :adet,
        detay = :detay, fatura_no = :fatura_no, maliyet = :maliyet , tedarikci_id = :tedarikci_id, kod = :kod, durum = :durum,
        aciklama = :aciklama, qr_kod = :qr_kod
        WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('musteri_id', $musteri_id);
        $sth->bindParam('siparis_id', $siparis_id);
        $sth->bindParam('ebat', $ebat);
        $sth->bindParam('adet', $adet);
        $sth->bindParam('detay', $detay);
        $sth->bindParam('fatura_no', $fatura_no);
        $sth->bindParam('maliyet', $maliyet);
        $sth->bindParam('tedarikci_id', $tedarikci_id);
        $sth->bindParam('kod', $kod);
        $sth->bindParam('durum', $durum);
        $sth->bindParam('aciklama', $aciklama);
        $sth->bindParam('qr_kod', $qr_kod);
        $sth->bindParam('id', $arsiv_alt_id);
        $durum = $sth->execute();

        if($durum)
        {
            header("Location: /index.php?url=arsiv_alt&arsiv_id={$arsiv_id}");
        }
        else 
        {
            header("Location: /index.php?url=arsiv_alt_guncelle&arsiv_alt_id={$arsiv_alt_id}");
        }
        die();

    } catch (Exception $e) {
        echo "<h3>Error (Update): " . $e->getMessage() . "</h3>";
        echo "<pre>SQL: " . (isset($sql) ? $sql : 'N/A') . "</pre>";
        echo "<pre>POST Data: " . print_r($_POST, true) . "</pre>";
        echo "<pre>Stack Trace: " . $e->getTraceAsString() . "</pre>";
        die();
    }
}
