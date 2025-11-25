<?php
session_name("PNL");
session_start();

if (!isset($_SESSION["giris_kontrol"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Oturum yok"], JSON_UNESCAPED_UNICODE);
    exit;
}

$firma_id = $_SESSION["firma_id"] ?? null;

if (!$firma_id) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Firma bilgisi eksik"], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once "include/db.php";

header("Content-Type: application/json; charset=utf-8");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        throw new Exception("Sadece GET");
    }
    
    $query = $_GET["q"] ?? "";
    
    if (strlen($query) < 2) {
        echo json_encode(["suggestions" => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $query = strip_tags($query);
    $search_term = "%" . $query . "%";
    
    $suggestions = [];
    
    // 1. Müşteri isimleri
    $sql = "SELECT id, firma_unvani, firma_unvani as display_name, \"musteri\" as type
            FROM musteri 
            WHERE firma_id = :firma_id 
            AND firma_unvani LIKE :search 
            ORDER BY firma_unvani 
            LIMIT 5";
    $sth = $conn->prepare($sql);
    $sth->execute(["firma_id" => $firma_id, "search" => $search_term]);
    $musteriler = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($musteriler as $musteri) {
        $suggestions[] = [
            "text" => $musteri["firma_unvani"],
            "type" => "müşteri",
            "id" => $musteri["id"],
            "icon" => "👤",
            "link" => "index.php?url=musteriler&id=" . $musteri["id"]
        ];
    }
    
    // 2. Sipariş numaraları
    $sql = "SELECT siparis_id, siparis_no, siparis_no as display_name, \"siparis\" as type
            FROM siparisler 
            WHERE firma_id = :firma_id 
            AND siparis_no LIKE :search 
            ORDER BY siparis_id DESC
            LIMIT 5";
    try {
        $sth = $conn->prepare($sql);
        $sth->execute(["firma_id" => $firma_id, "search" => $search_term]);
        $siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($siparisler as $siparis) {
            $suggestions[] = [
                "text" => $siparis["siparis_no"],
                "type" => "sipariş",
                "id" => $siparis["siparis_id"],
                "icon" => "📦",
                "link" => "index.php?url=siparis_gor&siparis_id=" . $siparis["siparis_id"]
            ];
        }
    } catch (Exception $e) {
        // Table might not exist, skip silently
    }
    
    // 3. Personel isimleri
    $sql = "SELECT id, CONCAT(ad, \" \", soyad) as ad_soyad, \"personel\" as type
            FROM personeller 
            WHERE firma_id = :firma_id 
            AND (ad LIKE :search OR soyad LIKE :search OR CONCAT(ad, \" \", soyad) LIKE :search)
            ORDER BY ad 
            LIMIT 5";
    try {
        $sth = $conn->prepare($sql);
        $sth->execute(["firma_id" => $firma_id, "search" => $search_term]);
        $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($personeller as $personel) {
            $suggestions[] = [
                "text" => $personel["ad_soyad"],
                "type" => "personel",
                "id" => $personel["id"],
                "icon" => "👨‍💼",
                "link" => "index.php?url=personeller&id=" . $personel["id"]
            ];
        }
    } catch (Exception $e) {
        // Table might not exist, skip silently
    }
    
    // 4. Departman isimleri
    $sql = "SELECT id, departman, departman as display_name, \"departman\" as type
            FROM departmanlar 
            WHERE firma_id = :firma_id 
            AND departman LIKE :search 
            ORDER BY departman 
            LIMIT 3";
    try {
        $sth = $conn->prepare($sql);
        $sth->execute(["firma_id" => $firma_id, "search" => $search_term]);
        $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($departmanlar as $departman) {
            $suggestions[] = [
                "text" => $departman["departman"],
                "type" => "departman",
                "id" => $departman["id"],
                "icon" => "🏢",
                "link" => "index.php?url=departmanlar&id=" . $departman["id"]
            ];
        }
    } catch (Exception $e) {
        // Table might not exist, skip silently
    }
    
    echo json_encode(["suggestions" => $suggestions], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>