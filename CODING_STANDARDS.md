# HANKA SYS SAAS - Kod Standartları ve Best Practices

## 📋 İçindekiler
- [Genel Prensipler](#genel-prensipler)
- [PHP Standartları](#php-standartları)
- [Veritabanı İşlemleri](#veritabanı-i̇şlemleri)
- [JavaScript & Frontend](#javascript--frontend)
- [Güvenlik](#güvenlik)
- [Hata Yönetimi](#hata-yönetimi)
- [Dosya Yapısı](#dosya-yapısı)
- [Yorum ve Dokümantasyon](#yorum-ve-dokümantasyon)

---

## Genel Prensipler

### 1. Multi-Tenant First
```php
// ✅ Her işlemde firma_id kontrolü ZORUNLU
$firma_id = $_SESSION['firma_id'];
$stmt = $conn->prepare("SELECT * FROM siparisler WHERE firma_id = :firma_id AND id = :id");

// ❌ Asla firma_id olmadan query
$stmt = $conn->prepare("SELECT * FROM siparisler WHERE id = :id");  // GÜVENSİZ!
```

### 2. Security First
- SQL Injection: **Prepared Statements** kullan
- XSS: **htmlspecialchars()** ile escape et
- CSRF: **Token** kontrolü yap
- Session: **Oturum kontrolü** her sayfada

### 3. PDO Only (MySQLi Kullanma!)
```php
// ✅ DOĞRU: PDO
$conn = new PDO("mysql:host=localhost;dbname=panelhankasys_crm2", "user", "pass");
$stmt = $conn->prepare("SELECT * FROM table WHERE id = :id");
$stmt->execute([':id' => $id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ❌ YANLIŞ: MySQLi (Kullanma!)
$conn = new mysqli("localhost", "user", "pass", "db");  // ESKİ SİSTEM
$result = $conn->query("SELECT * FROM table");  // GÜVENSİZ
```

---

## PHP Standartları

### Dosya Yapısı

```php
<?php
/**
 * Dosya Başlığı
 * 
 * @description Dosyanın amacı
 * @author Özmen Kaya
 * @date 2025-11-02
 */

// 1. Session ve güvenlik kontrolleri
ob_start();
require_once "include/db.php";
require_once "include/oturum_kontrol.php";

// 2. Değişken tanımlamaları
$firma_id = $_SESSION['firma_id'];
$page_title = "Sayfa Başlığı";

// 3. POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // İşlem kodu
}

// 4. Veri çekme
$stmt = $conn->prepare("SELECT ...");
// ...

// 5. HTML (varsa)
?>
<!DOCTYPE html>
<html>
...
</html>
```

### Naming Convention

```php
// ✅ Değişkenler: snake_case (PHP community standard)
$firma_id = 16;
$musteri_adi = "ABC Şirketi";
$siparis_listesi = [];

// ✅ Fonksiyonlar: camelCase
function getSiparisListesi($firma_id) { }
function updateMusteriAdres($musteri_id, $adres) { }

// ✅ Sınıflar: PascalCase
class AIChatEngine { }
class AlertAgent { }

// ✅ Sabitler: UPPER_CASE
define('MAX_UPLOAD_SIZE', 5242880);
const API_VERSION = '2.0';
```

### Type Hinting & Return Types (PHP 7.4+)

```php
// ✅ Tip belirtme kullan
function getMusteriById(int $id, int $firma_id): ?array {
    // ...
    return $data ?? null;
}

function saveSiparis(array $data): bool {
    try {
        // ...
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getAIResponse(string $question): string {
    // ...
    return $answer;
}
```

### Error Handling

```php
// ✅ Try-Catch kullan
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
    exit;
}

// ❌ Hataları gösterme (production'da)
// echo $e->getMessage();  // GÜVENLİK RİSKİ!
```

---

## Veritabanı İşlemleri

### CRUD Pattern (Standart)

#### CREATE
```php
// ✅ Prepared statement ile INSERT
$stmt = $conn->prepare("
    INSERT INTO siparisler 
    (firma_id, musteri_id, siparis_no, veriler, tarih) 
    VALUES (:firma_id, :musteri_id, :siparis_no, :veriler, NOW())
");

$stmt->execute([
    ':firma_id' => $_SESSION['firma_id'],
    ':musteri_id' => $musteri_id,
    ':siparis_no' => $siparis_no,
    ':veriler' => json_encode($veriler, JSON_UNESCAPED_UNICODE)
]);

$lastId = $conn->lastInsertId();
```

#### READ
```php
// ✅ Firma izolasyonu ile SELECT
$stmt = $conn->prepare("
    SELECT s.*, m.firma_unvani 
    FROM siparisler s
    INNER JOIN musteri m ON s.musteri_id = m.id
    WHERE s.firma_id = :firma_id 
      AND s.silindi = 0
    ORDER BY s.tarih DESC
    LIMIT :offset, :limit
");

$stmt->bindValue(':firma_id', $firma_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

#### UPDATE
```php
// ✅ Firma kontrolü ile UPDATE
$stmt = $conn->prepare("
    UPDATE siparisler 
    SET isin_adi = :isin_adi,
        adet = :adet,
        updated_at = NOW()
    WHERE id = :id 
      AND firma_id = :firma_id  -- ZORUNLU!
");

$stmt->execute([
    ':isin_adi' => $isin_adi,
    ':adet' => $adet,
    ':id' => $siparis_id,
    ':firma_id' => $_SESSION['firma_id']
]);

$affectedRows = $stmt->rowCount();
```

#### DELETE (Soft Delete)
```php
// ✅ Soft delete (silindi flag)
$stmt = $conn->prepare("
    UPDATE siparisler 
    SET silindi = 1, 
        silindi_tarih = NOW(),
        silindi_user_id = :user_id
    WHERE id = :id 
      AND firma_id = :firma_id
");

$stmt->execute([
    ':id' => $siparis_id,
    ':firma_id' => $_SESSION['firma_id'],
    ':user_id' => $_SESSION['personel_id']
]);

// ❌ Fiziksel silme (hard delete) - sadece gerektiğinde
// DELETE FROM siparisler WHERE id = :id;  // DİKKATLİ!
```

### Transaction Kullanımı

```php
// ✅ İlişkili işlemlerde transaction
try {
    $conn->beginTransaction();
    
    // Sipariş kaydet
    $stmt1 = $conn->prepare("INSERT INTO siparisler (...) VALUES (...)");
    $stmt1->execute($params1);
    $siparis_id = $conn->lastInsertId();
    
    // Planlama kaydet
    $stmt2 = $conn->prepare("INSERT INTO planlama (...) VALUES (...)");
    $stmt2->execute($params2);
    
    // Stok güncelle
    $stmt3 = $conn->prepare("UPDATE stok_alt_depolar SET miktar = miktar - :miktar WHERE id = :id");
    $stmt3->execute($params3);
    
    $conn->commit();
    echo json_encode(['success' => true, 'siparis_id' => $siparis_id]);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem başarısız']);
}
```

### JSON İşlemleri

```php
// ✅ JSON kaydetme
$veriler = [
    'urun_adi' => 'Plastik Kalıp',
    'ozellikler' => ['renk' => 'Beyaz', 'olcu' => '120x80'],
    'notlar' => 'Özel işlem gerekli'
];

$json = json_encode($veriler, JSON_UNESCAPED_UNICODE);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('JSON encoding failed: ' . json_last_error_msg());
}

// ✅ JSON okuma
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$veriler = json_decode($row['veriler'], true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON decode error: ' . json_last_error_msg());
    $veriler = [];  // Fallback
}

// ✅ MySQL JSON fonksiyonları
$stmt = $conn->prepare("
    SELECT id, 
           JSON_UNQUOTE(JSON_EXTRACT(veriler, '$.urun_adi')) as urun,
           JSON_EXTRACT(veriler, '$.ozellikler.renk') as renk
    FROM siparisler 
    WHERE firma_id = :firma_id
");
```

---

## JavaScript & Frontend

### AJAX Pattern (Standart)

```javascript
// ✅ Standart AJAX yapısı
function saveSiparis(data) {
    $.ajax({
        url: 'siparis_db_islem.php',
        type: 'POST',
        data: {
            action: 'kaydet',
            ...data,
            firma_id: FIRMA_ID  // Global değişken
        },
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                showSuccess(response.message || 'İşlem başarılı');
                reloadTable();
            } else {
                showError(response.message || 'İşlem başarısız');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);
            showError('Sunucu hatası. Lütfen tekrar deneyin.');
        }
    });
}
```

### DataTables Pattern

```javascript
// ✅ Standart DataTables yapılandırması
$('#siparis_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: 'siparis_listesi.php',
        type: 'POST',
        data: function(d) {
            d.firma_id = FIRMA_ID;
            d.durum_filtre = $('#durum_select').val();
        }
    },
    columns: [
        { data: 'siparis_no' },
        { data: 'musteri_adi' },
        { data: 'isin_adi' },
        { 
            data: 'termin',
            render: function(data) {
                return moment(data).format('DD.MM.YYYY');
            }
        },
        {
            data: null,
            orderable: false,
            render: function(data, type, row) {
                return `
                    <button class="btn btn-sm btn-primary" onclick="editSiparis(${row.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteSiparis(${row.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            }
        }
    ],
    language: {
        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
    },
    order: [[0, 'desc']],
    pageLength: 25
});
```

### Modal Pattern

```javascript
// ✅ Bootstrap modal açma
function editSiparis(id) {
    $.ajax({
        url: 'siparis_detay.php',
        type: 'GET',
        data: { id: id, firma_id: FIRMA_ID },
        success: function(html) {
            $('#editModal .modal-body').html(html);
            $('#editModal').modal('show');
            
            // Form submit handler
            $('#editForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                saveSiparis($(this).serialize());
            });
        }
    });
}
```

---

## Güvenlik

### 1. Session Kontrolü (Her Sayfada)

```php
// ✅ include/oturum_kontrol.php
session_start();

if (!isset($_SESSION['personel_id']) || !isset($_SESSION['firma_id'])) {
    header('Location: login.php');
    exit;
}

// Session hijacking koruması
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_destroy();
    header('Location: login.php');
    exit;
}
```

### 2. Input Validation

```php
// ✅ POST verisi kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Required field kontrolü
    $required = ['musteri_id', 'siparis_no', 'adet'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            echo json_encode(['success' => false, 'message' => "$field zorunludur"]);
            exit;
        }
    }
    
    // Tip kontrolü
    $musteri_id = filter_var($_POST['musteri_id'], FILTER_VALIDATE_INT);
    if ($musteri_id === false) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz müşteri ID']);
        exit;
    }
    
    // String sanitization
    $siparis_no = trim($_POST['siparis_no']);
    $siparis_no = preg_replace('/[^a-zA-Z0-9\-_]/', '', $siparis_no);
    
    // Email validation
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    // Float validation
    $fiyat = filter_var($_POST['fiyat'], FILTER_VALIDATE_FLOAT);
}
```

### 3. XSS Prevention

```php
// ✅ Output'ta her zaman escape
echo htmlspecialchars($firma_adi, ENT_QUOTES, 'UTF-8');

// ✅ JSON output'ta
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS);

// ❌ Asla raw output
// echo $user_input;  // GÜVENSİZ!
```

### 4. CSRF Protection

```php
// ✅ Token oluşturma
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Form'da token
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// ✅ Token kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
        exit;
    }
}
```

### 5. File Upload Security

```php
// ✅ Güvenli dosya upload
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$max_size = 5 * 1024 * 1024; // 5MB

if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    
    // Tip kontrolü
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz dosya tipi']);
        exit;
    }
    
    // Boyut kontrolü
    if ($_FILES['file']['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Dosya çok büyük']);
        exit;
    }
    
    // Güvenli dosya adı
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    
    $upload_dir = "dosyalar/siparisler/{$_SESSION['firma_id']}/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename);
}
```

---

## Hata Yönetimi

### Log Sistemi

```php
// ✅ Error logging
error_log("[" . date('Y-m-d H:i:s') . "] Error in siparis_db_islem.php: " . $e->getMessage());

// ✅ Custom log fonksiyonu
function logError($message, $context = []) {
    $log_file = __DIR__ . '/logs/errors.log';
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'user_id' => $_SESSION['personel_id'] ?? 'guest',
        'firma_id' => $_SESSION['firma_id'] ?? 0,
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    file_put_contents($log_file, json_encode($log_entry) . PHP_EOL, FILE_APPEND);
}

// Kullanım
try {
    // Kod
} catch (Exception $e) {
    logError('Database error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
```

### AJAX Response Standardı

```php
// ✅ Başarılı response
echo json_encode([
    'success' => true,
    'message' => 'İşlem başarılı',
    'data' => $result,
    'id' => $lastInsertId  // Opsiyonel
]);

// ✅ Hata response
echo json_encode([
    'success' => false,
    'message' => 'İşlem başarısız',
    'error' => 'Detaylı hata mesajı',
    'code' => 'SIPARIS_NOT_FOUND'  // Opsiyonel error code
]);
```

---

## Dosya Yapısı

### Dosya İsimlendirme

```
✅ DOĞRU:
- siparisler.php          (view sayfası)
- siparis_db_islem.php    (backend API)
- siparis_ekle.php        (form sayfası)
- siparis_modal.php       (modal content)

❌ YANLIŞ:
- Siparisler.php          (Büyük harf başlangıç)
- siparis-db-islem.php    (Tire yerine underscore)
- SiparisDbIslem.php      (CamelCase)
```

### Klasör Organizasyonu

```
/var/www/html/
├── include/              # Core sistem dosyaları
│   ├── db.php
│   ├── oturum_kontrol.php
│   ├── header.php
│   └── agents/          # Agent sınıfları
├── assets/              # 3rd party kütüphaneler
├── css/                 # Custom CSS
├── js/                  # Custom JavaScript
├── dosyalar/            # Upload dosyaları
│   └── {firma_id}/      # Firma bazlı klasörler
├── logs/                # Log dosyaları
├── mysql/               # SQL migration'lar
└── cron/                # Scheduled task'ler
```

---

## Yorum ve Dokümantasyon

### Fonksiyon Dokümantasyonu

```php
/**
 * Sipariş listesi getirir
 * 
 * @param int $firma_id Firma ID
 * @param array $filters Filtre array'i ['durum' => 'aktif', 'musteri_id' => 5]
 * @param int $limit Kayıt limiti
 * @param int $offset Sayfa offset'i
 * @return array Sipariş listesi
 * @throws PDOException Veritabanı hatası durumunda
 */
function getSiparisListesi(int $firma_id, array $filters = [], int $limit = 25, int $offset = 0): array {
    // ...
}
```

### Kod İçi Yorumlar

```php
// ✅ İyi yorum: NEDEN açıklar
// Cache'i kullan çünkü bu query çok yavaş (5+ saniye)
$cached = getCachedResult($hash);

// ✅ Önemli business logic
// Müşteri temsilcisi yoksa, default temsilciyi ata (firma ayarından)
if (!$musteri_temsilcisi_id) {
    $musteri_temsilcisi_id = getFirmaDefaultTemsilci($firma_id);
}

// ❌ Gereksiz yorum: NE yaptığını açıklar (zaten belli)
// Değişkene 5 ata
$limit = 5;
```

### TODO ve FIXME

```php
// TODO: Performans optimizasyonu gerekli - 1000+ kayıtta yavaş
// FIXME: Firma 16'da hata veriyor, kontrol edilmeli
// HACK: Geçici çözüm, refactor edilecek
// NOTE: Bu kod legacy sistemden geldi, değiştirme!
```

---

## Performance Best Practices

### 1. Query Optimizasyonu

```php
// ✅ İhtiyacın olan kolonları seç
SELECT id, siparis_no, musteri_id FROM siparisler WHERE ...

// ❌ SELECT * kullanma (gereksiz veri)
SELECT * FROM siparisler WHERE ...

// ✅ LIMIT kullan
SELECT * FROM siparisler WHERE firma_id = :firma_id LIMIT 100

// ✅ INDEX'li kolonlarda filtrele
WHERE firma_id = :firma_id AND created_at > :tarih  -- İndeksli
```

### 2. Cache Kullanımı

```php
// ✅ Sık kullanılan veriyi cache'le
$cache_key = "musteri_list_firma_{$firma_id}";
$cache_time = 3600; // 1 saat

if ($cached = getCache($cache_key)) {
    return $cached;
}

$data = fetchFromDatabase();
setCache($cache_key, $data, $cache_time);
```

### 3. Lazy Loading

```javascript
// ✅ Büyük listelerde pagination
$('#table').DataTables({
    serverSide: true,  // Server-side processing
    deferRender: true  // Lazy rendering
});
```

---

## Git Commit Messages

```bash
# ✅ İyi commit message
git commit -m "feat: Sipariş filtreleme sistemi eklendi"
git commit -m "fix: AlertAgent urunler tablosu hatası düzeltildi"
git commit -m "docs: DATABASE_SCHEMA.md güncellendi"
git commit -m "refactor: MySQLi'den PDO'ya geçiş (ai_settings.php)"

# Prefix'ler:
# feat: Yeni özellik
# fix: Bug düzeltme
# docs: Dokümantasyon
# refactor: Kod iyileştirme
# perf: Performans optimizasyonu
# test: Test ekleme
# chore: Bakım işleri
```

---

## Özet Checklist

### Yeni Kod Yazarken
- [ ] Firma izolasyonu var mı? (`firma_id` kontrolü)
- [ ] PDO Prepared Statements kullanıldı mı?
- [ ] Input validation yapıldı mı?
- [ ] XSS koruması var mı? (`htmlspecialchars`)
- [ ] Error handling var mı? (try-catch)
- [ ] Transaction gerekli mi?
- [ ] Log sistemi aktif mi?
- [ ] Fonksiyon dokümantasyonu yazıldı mı?
- [ ] Test edildi mi?

### Code Review Kriterleri
- [ ] Güvenlik açıkları var mı?
- [ ] Performans problemi var mı?
- [ ] Kod standartlarına uygun mu?
- [ ] Okunabilir mi?
- [ ] DRY (Don't Repeat Yourself) prensibi uygulanmış mı?
- [ ] Gereksiz yorum var mı?

---

**Son Güncelleme**: 2 Kasım 2025  
**Versiyon**: 2.0  
**Yazar**: Özmen Kaya
