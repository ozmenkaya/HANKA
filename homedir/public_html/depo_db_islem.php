<?php 
require_once "include/oturum_kontrol.php";

try {
//gönderimi bitmiş siparişler log
if(isset($_POST['islem']) && $_POST['islem'] == 'gonderimi_bitmis_siparis_log'){
    $siparis_id = $_POST['siparis_id'];
    $sql = "SELECT teslim_edilenler.teslim_adedi AS adet,teslim_edilenler.tarih, 'teslim' AS durum,
            planlama.isim,siparisler.isin_adi
            FROM `teslim_edilenler` 
            JOIN planlama ON planlama.siparis_id = teslim_edilenler.siparis_id
            JOIN siparisler ON siparisler.id = teslim_edilenler.siparis_id
            WHERE teslim_edilenler.siparis_id = :siparis_id";

    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->execute();
    $teslim_edilen_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
     
    $sql = "SELECT uretilen_adetler.uretilen_adet AS adet,uretilen_adetler.bitis_tarihi AS tarih, 'uretim' AS durum,
            planlama.isim,siparisler.isin_adi
            FROM `uretilen_adetler` 
            JOIN planlama ON planlama.id = uretilen_adetler.planlama_id
            JOIN siparisler ON siparisler.id = planlama.siparis_id
            WHERE siparisler.id = :siparis_id";

    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->execute();
    $uretilen_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $loglar = array_merge($uretilen_loglar, $teslim_edilen_loglar);
    array_multisort( array_column($loglar, "tarih"), SORT_ASC, $loglar );

    ob_clean();
    echo json_encode([
        'loglar' => $loglar
    ]);
    exit;

}
#echo json_encode($_POST);
if(isset($_POST['islem']) && $_POST['islem'] == 'siparisler_ve_log')
{
    $siparis_id = $_POST['siparis_id'];
    $sql = "SELECT planlama.id, planlama.isim, planlama.biten_urun_adedi, planlama.teslim_edilen_urun_adedi, 
            birimler.ad AS birim_ad
            FROM `planlama` 
            JOIN `siparisler` ON `siparisler`.id = planlama.siparis_id
            JOIN birimler ON birimler.id = siparisler.birim_id
            WHERE planlama.siparis_id = :siparis_id AND planlama.firma_id = :firma_id";

    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);


    $sql = "SELECT teslim_edilenler.teslim_adedi AS adet,teslim_edilenler.tarih, 'teslim' AS durum,
            planlama.isim,siparisler.isin_adi
            FROM `teslim_edilenler` 
            JOIN planlama ON planlama.siparis_id = teslim_edilenler.siparis_id
            JOIN siparisler ON siparisler.id = teslim_edilenler.siparis_id
            WHERE teslim_edilenler.siparis_id = :siparis_id";

    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->execute();
    $teslim_edilen_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
 
    
    $sql = "SELECT uretilen_adetler.uretilen_adet AS adet,uretilen_adetler.bitis_tarihi AS tarih, 'uretim' AS durum,
            planlama.isim,siparisler.isin_adi
            FROM `uretilen_adetler` 
            JOIN planlama ON planlama.id = uretilen_adetler.planlama_id
            JOIN siparisler ON siparisler.id = planlama.siparis_id
            WHERE siparisler.id = :siparis_id";

    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->execute();
    $uretilen_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    $loglar = array_merge($uretilen_loglar, $teslim_edilen_loglar);
    array_multisort( array_column($loglar, "tarih"), SORT_ASC, $loglar );

    ob_clean();
    echo json_encode([
        'planlamalar'   => $planlamalar,
        'loglar'        => $loglar
    ]);
    exit;
}


//teslim edilecekleri kaydet
//echo "<pre>"; print_r($_POST);
if(isset($_POST['teslim_et']))
{
    $teslim_edilecekler = $_POST['teslim_edilecekler'];
    $planlanma_idler    = $_POST['planlanma_idler'];
    $siparis_id         = $_POST['siparis_id'];
    foreach ($teslim_edilecekler as $index => $teslim_edilecek_adet) {
        //teslim_edilenler tablosuna ekle
        $sql = "INSERT INTO teslim_edilenler(siparis_id, planlama_id, personel_id, teslim_adedi) 
        VALUES(:siparis_id, :planlama_id, :personel_id, :teslim_adedi);";
        $sth = $conn->prepare($sql);
        $sth->bindParam("siparis_id", $siparis_id);
        $sth->bindParam("planlama_id", $planlanma_idler[$index]);
        $sth->bindParam("personel_id", $_SESSION['personel_id']);
        $sth->bindParam("teslim_adedi", $teslim_edilecek_adet);
        $durum = $sth->execute();

        if($durum){
            //planlama tablosuna teslim edilen adetleri ekle
            $sql = "UPDATE planlama SET teslim_edilen_urun_adedi = teslim_edilen_urun_adedi + :teslim_edilen_urun_adedi  
            WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('teslim_edilen_urun_adedi', $teslim_edilecek_adet);
            $sth->bindParam('id', $planlanma_idler[$index]);
            $durum = $sth->execute();
        }
    }

    foreach ($planlanma_idler as $planlanma_id) {
        $sth = $conn->prepare('SELECT planlama.teslim_edilen_urun_adedi,siparisler.adet
        FROM planlama 
        JOIN siparisler ON siparisler.id = planlama.siparis_id
        WHERE planlama.id =:id');
        $sth->bindParam('id', $planlanma_id);
        $sth->execute();
        $planlama = $sth->fetch(PDO::FETCH_ASSOC);

        //tümü teslim edildi mi?
        if($planlama['teslim_edilen_urun_adedi'] >= $planlama['adet']){
            $sql = "UPDATE planlama SET teslim_durumu = 'bitti'  WHERE id = :id;";
            $sth = $conn->prepare($sql);
            $sth->bindParam('id', $planlanma_id);
            $durum = $sth->execute();
        }
    }


    //alt ürünlerin hepsi iade edilmi mi?
    $sth = $conn->prepare('SELECT teslim_durumu FROM planlama WHERE siparis_id=:siparis_id');
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->execute();
    $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $tum_teslim_edildi_mi = true;
    foreach ($planlamalar as $planlama) {
        if($planlama['teslim_durumu'] == 'bitmedi'){
            $tum_teslim_edildi_mi = false;
            break;
        }
    }

    if($tum_teslim_edildi_mi){
        $sql = "UPDATE siparisler SET islem = 'teslim_edildi'  WHERE id = :id;";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $siparis_id);
        $durum = $sth->execute();
    }

    header("Location: /index.php?url=depo"); exit;
}

#Depo ekle
if(isset($_POST['depo_ekle']))
{
    $depo_kodu   = isset($_POST['depo_kodu']) ? trim($_POST['depo_kodu']) : '';
    $depo_tanimi = isset($_POST['depo_tanimi']) ? trim($_POST['depo_tanimi']) : '';
    $alanlar     = isset($_POST['alanlar']) && is_array($_POST['alanlar']) ? $_POST['alanlar'] : [];

    $firma_id    = $_SESSION['firma_id'];
    $personel_id = $_SESSION['personel_id'];
    $date        = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM depolar WHERE firma_id = ? AND depo_kodu = ?");
    $stmt->execute([$firma_id, $depo_kodu]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Depo Kodu daha önce oluşturulmuş !';

        header('Location: /index.php?url=depo_islemleri');
        die();
    }
    
    $sql = "INSERT INTO depolar(firma_id, depo_kodu, depo_tanimi, olusturan_id, olusturma_tarihi) VALUES(?, ?, ?, ?, ?);";
    $sth = $conn->prepare($sql);
    $durum = $sth->execute([$firma_id, $depo_kodu, $depo_tanimi, $personel_id, $date]);

    $depo_id = $conn->lastInsertId();
    if($durum && count($alanlar) > 0)
    {
        foreach ($alanlar as $alan) {
            $alan_kodu   = trim($alan['alan_kodu']);
            $alan_tanimi = trim($alan['alan_tanimi']);
            if (empty($alan_kodu) || empty($alan_tanimi)) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Depo kodu veya tanimi boş olamaz !';

                header('Location: /index.php?url=depo_islemleri');
                die();
            }

            // Alan kodunun bu depo için var olup olmadığını kontrol et
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM depo_alanlari WHERE firma_id = ? AND depo_id = ? AND alan_kodu = ?");
            $stmt->execute([$firma_id, $depo_id, $alan_kodu]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = "Alan kodu '$alan_kodu' bu depo için zaten kayıtlı.";

                header('Location: /index.php?url=depo_islemleri');
                die(); 
            }

            // Alan ekle
            $stmt = $conn->prepare("INSERT INTO depo_alanlari (firma_id, depo_id, alan_kodu, alan_tanimi, olusturan_id, olusturma_tarihi) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firma_id, $depo_id, $alan_kodu, $alan_tanimi, $personel_id, $date]);
        }
 
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }else if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }
    header('Location: /index.php?url=depo_islemleri');
    die();
}

#Depo sil
if(isset($_GET['islem']) && $_GET['islem'] == 'depo_sil')
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM depo_alanlari WHERE depo_id = :depo_id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('depo_id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
 
    if($durum == true)
    {
        $sql = "DELETE FROM depolar WHERE id=:id AND firma_id = :firma_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $durum = $sth->execute(); 

        if($durum == true)
        {
            $_SESSION['durum'] = 'success';
            $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
        }else{
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
        } 
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Alt alan silme işlemi başarısız';  
    }
    header('Location: /index.php?url=depo_islemleri');
    die();
}

$islem = isset($_GET['islem']) ? $_GET['islem'] : '';

if ($islem === 'get_depo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Depo ve alan verilerini çek
    $firma_id = $_SESSION['firma_id'];
    $depo_id = isset($_POST['depo_id']) ? (int)$_POST['depo_id'] : 0;
    if ($depo_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz depo ID.']);
        exit;
    }

    // Depo bilgilerini çek
    $stmt = $conn->prepare("SELECT id, depo_kodu, depo_tanimi FROM depolar WHERE id = ? AND firma_id = ?");
    $stmt->execute([$depo_id, $firma_id]);
    $depo = $stmt->fetch();

    if (!$depo) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Depo bulunamadı.']);
        exit;
    }

    // Alan bilgilerini çek
    $stmt = $conn->prepare("SELECT alan_kodu, alan_tanimi FROM depo_alanlari WHERE depo_id = ? AND firma_id = ?");
    $stmt->execute([$depo_id, $firma_id]);
    $alanlar = $stmt->fetchAll();

    ob_clean();
    echo json_encode([
        'success' => true,
        'depo' => $depo,
        'alanlar' => $alanlar
    ]);
    exit;
}

if ($islem === 'depo_guncelle' && isset($_POST['depo_guncelle'])) {
    ob_clean();
    
    $firma_id    = $_SESSION['firma_id'];
    $personel_id = $_SESSION['personel_id'];
    $date        = date('Y-m-d H:i:s');

    // Güncelleme işlemi
    $depo_id     = isset($_POST['depo_id']) ? (int)$_POST['depo_id'] : 0;
    $depo_kodu   = isset($_POST['depo_kodu']) ? trim($_POST['depo_kodu']) : '';
    $depo_tanimi = isset($_POST['depo_tanimi']) ? trim($_POST['depo_tanimi']) : '';
    $alanlar     = isset($_POST['alanlar']) && is_array($_POST['alanlar']) ? $_POST['alanlar'] : [];

    // Validasyon
    if ($depo_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz depo ID.']);
        exit;
    }
    if (empty($depo_kodu)) {
        echo json_encode(['success' => false, 'message' => 'Depo kodu zorunludur.']);
        exit;
    }
    if (empty($depo_tanimi)) {
        echo json_encode(['success' => false, 'message' => 'Depo tanımı zorunludur.']);
        exit;
    } 

    // Depo kodunun başka bir depo ile çakışıp çakışmadığını kontrol et
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM depolar WHERE depo_kodu = ? AND id != ? AND firma_id = ?");
    $stmt->execute([$depo_kodu, $depo_id, $_SESSION['firma_id']]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu depo kodu başka bir depo için zaten kayıtlı.']);
        exit;
    }

    // Güncelleme tarihi ve kullanıcı
    $guncelleme_tarihi = date('Y-m-d H:i:s');
    $guncelleyen_id = $_SESSION['personel_id']; // Oturumdan kullanıcı ID'sini al

    // Depo güncelle
    $stmt = $conn->prepare("UPDATE depolar SET depo_kodu = ?, depo_tanimi = ?, guncelleme_tarihi = ?, guncelleyen_id = ? WHERE id = ? AND firma_id = ?");
    $stmt->execute([$depo_kodu, $depo_tanimi, $guncelleme_tarihi, $guncelleyen_id, $depo_id, $firma_id]);

    // Mevcut alanları sil
    $stmt = $conn->prepare("DELETE FROM depo_alanlari WHERE depo_id = ? AND firma_id = ?");
    $stmt->execute([$depo_id, $firma_id]);
 
    // Yeni alanları ekle
    $stmt = $conn->prepare("INSERT INTO depo_alanlari (firma_id, depo_id, alan_kodu, alan_tanimi, olusturan_id, olusturma_tarihi) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($alanlar as $alan) {
        $alan_kodu   = trim($alan['alan_kodu']);
        $alan_tanimi = trim($alan['alan_tanimi']);
        if (!empty($alan_kodu) && !empty($alan_tanimi)) {
            $stmt->execute([$firma_id, $depo_id, $alan_kodu, $alan_tanimi, $personel_id, $date]);
        }
    }

    $_SESSION['durum'] = 'success';
    $_SESSION['mesaj'] = 'Depo ve alanlar başarıyla güncellendi.';  
    header('Location: /index.php?url=depo_islemleri');
    die();
}

http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
} catch (PDOException $e) { 
    $_SESSION['durum'] = 'error';
    $_SESSION['mesaj'] = 'Veritabanı hatası: ' . $e->getMessage();  
    header('Location: /index.php?url=depo_islemleri');
    die();
}
?>
