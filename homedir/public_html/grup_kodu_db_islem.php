<?php 
require_once "include/oturum_kontrol.php";

try {

#Grup Kodu ekle
if(isset($_POST['grup_kodu_ekle']))
{ 
    $aciklama = isset($_POST['grup_kodu_tanimi']) ? trim($_POST['grup_kodu_tanimi']) : ''; 

    $firma_id    = $_SESSION['firma_id'];
    $personel_id = $_SESSION['personel_id'];
    $date        = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM grup_kodu WHERE firma_id = ? AND aciklama = ?");
    $stmt->execute([$firma_id, $aciklama]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Grup Kodu daha önce oluşturulmuş !';

        header('Location: /index.php?url=grup_kodlari');
        die();
    }
    
    $sql = "INSERT INTO grup_kodu(firma_id, aciklama, olusturan_id, olusturma_tarihi) VALUES(?, ?, ?, ?);";
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
    header('Location: /index.php?url=grup_kodlari');
    die();
}

#Grup Kodu sil
if(isset($_GET['islem']) && $_GET['islem'] == 'grup_kodu_sil')
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM grup_kodu WHERE id = :id AND firma_id = :firma_id";
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
   
    header('Location: /index.php?url=grup_kodlari');
    die();
}

$islem = isset($_GET['islem']) ? $_GET['islem'] : '';

if ($islem === 'get_grup_kodu' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grup verilerini çek
    $firma_id = $_SESSION['firma_id'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
        exit;
    }

    // Grup bilgilerini çek
    $stmt = $conn->prepare("SELECT id, aciklama FROM grup_kodu WHERE id = ? AND firma_id = ?");
    $stmt->execute([$id, $firma_id]);
    $grup = $stmt->fetch();

    if (!$grup) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Grup bulunamadı.']);
        exit;
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'grup' => $grup,
    ]);
    exit;
}

if ($islem === 'grup_kodu_guncelle' && isset($_POST['grup_kodu_guncelle'])) {
    ob_clean();
    
    $firma_id    = $_SESSION['firma_id'];
    $personel_id = $_SESSION['personel_id'];
    $date        = date('Y-m-d H:i:s');

    // Güncelleme işlemi
    $id       = isset($_POST['grup_kodu_id']) ? (int)$_POST['grup_kodu_id'] : 0;
    $aciklama = isset($_POST['grup_kodu_tanimi']) ? trim($_POST['grup_kodu_tanimi']) : '';

    // Validasyon
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
        exit;
    }
    if (empty($aciklama)) {
        echo json_encode(['success' => false, 'message' => 'Grup tanımı zorunludur.']);
        exit;
    } 

    // Depo kodunun başka bir depo ile çakışıp çakışmadığını kontrol et
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM grup_kodu WHERE aciklama = ? AND id != ? AND firma_id = ?");
    $stmt->execute([$aciklama, $id, $_SESSION['firma_id']]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu grup kodu başka bir grup için zaten kayıtlı.']);
        exit;
    }

    // Güncelleme tarihi ve kullanıcı
    $guncelleme_tarihi = date('Y-m-d H:i:s');
    $guncelleyen_id = $_SESSION['personel_id']; // Oturumdan kullanıcı ID'sini al

    // Grup Kodu güncelle
    $stmt = $conn->prepare("UPDATE grup_kodu SET aciklama = ?, guncelleme_tarihi = ?, guncelleyen_id = ? WHERE id = ? AND firma_id = ?");
    $stmt->execute([$aciklama, $guncelleme_tarihi, $guncelleyen_id, $id, $firma_id]);
 
    $_SESSION['durum'] = 'success';
    $_SESSION['mesaj'] = 'Başarıyla güncellendi.';  
    header('Location: /index.php?url=grup_kodlari');
    die();
}

http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
} catch (PDOException $e) { 
    $_SESSION['durum'] = 'error';
    $_SESSION['mesaj'] = 'Veritabanı hatası: ' . $e->getMessage();  
    header('Location: /index.php?url=grup_kodlari');
    die();
}
?>
