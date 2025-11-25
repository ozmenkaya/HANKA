<?php
/**
 * Ortak Fonksiyon Kütüphanesi
 * Tüm PHP dosyalarında kullanılan tekrarlayan kodlar
 */

// JSON Response Helper
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Input Sanitization
function sanitizeInput($data, $type = 'string') {
    switch($type) {
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) ?: 0;
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT) ?: 0.0;
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL) ?: '';
        case 'string':
        default:
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Tarih Formatlama
function formatDate($date, $format = 'd.m.Y H:i') {
    if(empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($date));
}

// Sayı Formatlama
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

// Para Formatlama
function formatMoney($amount, $currency = '₺') {
    return formatNumber($amount, 2) . ' ' . $currency;
}

// Veritabanı Hata Yakalama
function dbError($e, $operation = 'işlem') {
    error_log("DB Error: " . $e->getMessage());
    jsonResponse(false, ucfirst($operation) . ' sırasında bir hata oluştu.');
}

// Dosya Upload Helper
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if(!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Dosya yüklenemedi.'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Geçersiz dosya tipi.'];
    }
    
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $fileName;
    
    if(!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    if(move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $targetPath];
    }
    
    return ['success' => false, 'message' => 'Dosya taşınamadı.'];
}

// Pagination Helper
function paginate($total, $perPage = 20, $currentPage = 1) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($totalPages, $currentPage));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Yetki Kontrolü
function checkPermission($required_yetki_id, $user_yetki_id) {
    $allowed_yetkiler = [$required_yetki_id];
    // SUPER_ADMIN her şeyi yapabilir
    if(defined('SUPER_ADMIN_YETKI_ID')) {
        $allowed_yetkiler[] = SUPER_ADMIN_YETKI_ID;
    }
    if(defined('ADMIN_YETKI_ID')) {
        $allowed_yetkiler[] = ADMIN_YETKI_ID;
    }
    return in_array($user_yetki_id, $allowed_yetkiler);
}

// Log Kaydı
function logActivity($db, $user_id, $action, $table_name, $record_id = null, $details = '') {
    try {
        $sql = "INSERT INTO sistem_log (user_id, action, table_name, record_id, details, created_at) 
                VALUES (:user_id, :action, :table_name, :record_id, :details, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'action' => $action,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'details' => $details
        ]);
    } catch(Exception $e) {
        error_log("Log Error: " . $e->getMessage());
    }
}

// Durum Badge HTML
function statusBadge($status, $labels = []) {
    $default_labels = [
        'aktif' => ['class' => 'success', 'text' => 'Aktif'],
        'pasif' => ['class' => 'secondary', 'text' => 'Pasif'],
        'beklemede' => ['class' => 'warning', 'text' => 'Beklemede'],
        'tamamlandi' => ['class' => 'info', 'text' => 'Tamamlandı'],
        'iptal' => ['class' => 'danger', 'text' => 'İptal']
    ];
    
    $labels = array_merge($default_labels, $labels);
    $config = $labels[$status] ?? ['class' => 'secondary', 'text' => ucfirst($status)];
    
    return sprintf('<span class="badge bg-%s">%s</span>', $config['class'], $config['text']);
}

// Ajax Request Check
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// CSRF Token (Basit)
function generateCSRFToken() {
    if(session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if(session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>

/**
 * Bildirim oluşturma helper fonksiyonu
 * 
 * @param int $firma_id Firma ID
 * @param string $baslik Bildirim başlığı
 * @param string $mesaj Bildirim mesajı
 * @param string $tur Bildirim türü: basari, bilgi, uyari, hata
 * @param int|null $kullanici_id Hedef kullanıcı (null ise tüm firma)
 * @param string|null $link İlgili sayfa linki
 * @return bool Başarılı mı
 */
function bildirimOlustur($firma_id, $baslik, $mesaj, $tur = 'bilgi', $kullanici_id = null, $link = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO bildirimler 
            (firma_id, kullanici_id, baslik, mesaj, tur, link) 
            VALUES 
            (:firma_id, :kullanici_id, :baslik, :mesaj, :tur, :link)
        ");
        
        $stmt->execute([
            'firma_id' => $firma_id,
            'kullanici_id' => $kullanici_id,
            'baslik' => $baslik,
            'mesaj' => $mesaj,
            'tur' => $tur,
            'link' => $link
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Bildirim oluşturma hatası: ' . $e->getMessage());
        return false;
    }
}
