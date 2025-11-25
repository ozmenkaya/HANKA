<?php 
require_once "include/oturum_kontrol.php";

try {

#Kod ekle
if(isset($_POST['kod_ekle']))
{ 
    $aciklama = isset($_POST['kod_tanimi']) ? trim($_POST['kod_tanimi']) : ''; 

    $firma_id    = $_SESSION['firma_id'];
    $personel_id = $_SESSION['personel_id'];
    $date        = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM kod4 WHERE firma_id = ? AND aciklama = ?");
    $stmt->execute([$firma_id, $aciklama]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Kod daha önce oluşturulmuş !';

        header('Location: /index.php?url=kod4');
        die();
    }
    
    $sql = "INSERT INTO kod4(firma_id, aciklama, olusturan_id, olusturma_tarihi) VALUES(?, ?, ?, ?);";
    $sth = $conn->prepare($sql);
    $durum = $sth->execute([$firma_id, $aciklama, $personel_id, $date]);
 
    if($durum)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';
    }
    header('Location: /index.php?url=kod4');
    die();
}

#Kod sil
if(isset($_GET['islem']) && $_GET['islem'] == 'kod_sil')
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM kod4 WHERE id = :id AND firma_id = :firma_id";
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
   
    header('Location: /index.php?url=kod4');
    die();
}

$islem = isset($_GET['islem']) ? $_GET['islem'] : '';

if ($islem === 'get_kod' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kod verilerini çek
    $firma_id = $_SESSION['firma_id'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
        exit;
    }

    // Kod bilgilerini çek
    $stmt = $conn->prepare("SELECT id, aciklama FROM kod4 WHERE id = ? AND firma_id = ?");
    $stmt->execute([$id, $firma_id]);
    $kod = $stmt->fetch();

    if (!$kod) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Kod bulunamadı.']);
        exit;
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'kod' => $kod,
    ]);
    exit;
}

if ($islem === 'kod_guncelle' && isset($_POST['kod_guncelle'])) {
    ob_clean();
    
    $firma_id    = $_SESSION['firma_id'];
    $personel_id = $_SESSION['personel_id'];
    $date        = date('Y-m-d H:i:s');

    // Güncelleme işlemi
    $id       = isset($_POST['kod_id']) ? (int)$_POST['kod_id'] : 0;
    $aciklama = isset($_POST['kod_tanimi']) ? trim($_POST['kod_tanimi']) : '';

    // Validasyon
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
        exit;
    }
    if (empty($aciklama)) {
        echo json_encode(['success' => false, 'message' => 'Kod tanımı zorunludur.']);
        exit;
    } 

    // Depo kodunun başka bir depo ile çakışıp çakışmadığını kontrol et
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM kod4 WHERE aciklama = ? AND id != ? AND firma_id = ?");
    $stmt->execute([$aciklama, $id, $_SESSION['firma_id']]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu kod başka bir kod kaydı için zaten kayıtlı.']);
        exit;
    }

    // Güncelleme tarihi ve kullanıcı
    $guncelleme_tarihi = date('Y-m-d H:i:s');
    $guncelleyen_id = $_SESSION['personel_id']; // Oturumdan kullanıcı ID'sini al

    // Kod güncelle
    $stmt = $conn->prepare("UPDATE kod4 SET aciklama = ?, guncelleme_tarihi = ?, guncelleyen_id = ? WHERE id = ? AND firma_id = ?");
    $stmt->execute([$aciklama, $guncelleme_tarihi, $guncelleyen_id, $id, $firma_id]);
 
    $_SESSION['durum'] = 'success';
    $_SESSION['mesaj'] = 'Başarıyla güncellendi.';  
    header('Location: /index.php?url=kod4');
    die();
}

http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
} catch (PDOException $e) { 
    $_SESSION['durum'] = 'error';
    $_SESSION['mesaj'] = 'Veritabanı hatası: ' . $e->getMessage();  
    header('Location: /index.php?url=kod4');
    die();
}
?>
