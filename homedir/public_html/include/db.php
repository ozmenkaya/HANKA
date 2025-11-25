<?php 
include_once  "sabitler.php";
include_once  "function.php";
date_default_timezone_set('Europe/Istanbul');

// URL cookie'sini kaydet - SADECE sayfa URL'leri için, DB işlem/AJAX endpoint'leri için değil
$current_url = $_SERVER['REQUEST_URI'];
$blocked_patterns = ['_db_islem', '_ajax', 'login_kontrol', 'logout', '/ajax/', '/api/'];
$should_save_cookie = true;

foreach($blocked_patterns as $pattern){
    if(strpos($current_url, $pattern) !== false){
        $should_save_cookie = false;
        break;
    }
}

if($should_save_cookie){
    // Sadece relative URL kaydet (domain prefix olmadan)
    setcookie('url', $current_url, time() + SESSION_SURESI, "/");
}

if(BAKIM_MOD){
    header("Location:include/bakim_mod.php");
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_name("PNL");
    ini_set('session.cookie_lifetime', SESSION_SURESI);
    ini_set('session.gc_maxlifetime', SESSION_SURESI);
    session_start();
    session_regenerate_id();
}



if(isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], DEBUG_IPLER))
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

#veritabanı bağlantı
try{    
    // Local veya production ortamı tespit et
    $isLocal = ($_SERVER['SERVER_NAME'] ?? 'localhost') === 'localhost' || 
               ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1';
    
    if ($isLocal) {
        // Local ayarlar
        $conn = new PDO(
            'mysql:host=localhost;port=3306;dbname=panelhankasys_crm2;charset=utf8mb4',
            'hanka_user',
            'HankaDB2025!',
            [
                PDO::ATTR_DEFAULT_FETCH_MODE    =>  PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE               =>  PDO::ERRMODE_EXCEPTION,
            ]
        );
    } else {
        // Production ayarlar (sunucu)
        $conn = new PDO(
            'mysql:host=localhost;port=3306;dbname=panelhankasys_crm2;charset=utf8mb4',
            'hanka_user',
            'HankaDB2025!',
            [
                PDO::ATTR_DEFAULT_FETCH_MODE    =>  PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE               =>  PDO::ERRMODE_EXCEPTION,
            ]
        );
    }
}catch (PDOException $e){
    // Debug için hata göster (sadece local)
    if (($_SERVER['SERVER_NAME'] ?? 'localhost') === 'localhost') {
        die("Veritabanı Bağlantı Hatası: " . $e->getMessage());
    }
    header("Location:db_hata.php");
    exit;
}

?>