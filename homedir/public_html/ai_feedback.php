<?php
/**
 * AI Feedback Endpoint
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Session başlat (db.php ile aynı session name)
session_name("PNL");
session_start();

// AJAX oturum kontrolü
if (!isset($_SESSION['giris_kontrol'])) {
    ob_clean();
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "Oturum bulunamadı."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Session değişkenlerini al
$firma_id = $_SESSION['firma_id'] ?? null;
$personel_id = $_SESSION['personel_id'] ?? null;

if (!$firma_id || !$personel_id) {
    ob_clean();
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Firma veya kullanıcı bilgisi eksik"], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once "include/db.php";

ob_clean();
header("Content-Type: application/json; charset=utf-8");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Sadece POST istekleri kabul edilir");
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    
    $chat_id = $input["chat_id"] ?? 0;
    $rating = $input["rating"] ?? 0;
    $dogru_mu = $input["dogru_mu"] ?? 1;
    $duzeltme = $input["duzeltme"] ?? "";
    
    if ($chat_id <= 0) {
        throw new Exception("Geçersiz chat ID");
    }
    
    if ($rating < 1 || $rating > 5) {
        throw new Exception("Rating 1-5 arası olmalı");
    }
    
    // Feedback kaydet
    $sql = "INSERT INTO ai_feedback 
            (firma_id, chat_id, kullanici_id, rating, dogru_mu, duzeltme, tarih)
            VALUES (:firma_id, :chat_id, :kullanici_id, :rating, :dogru_mu, :duzeltme, NOW())";
    
    $sth = $conn->prepare($sql);
    $sth->execute([
        "firma_id" => $firma_id,
        "chat_id" => $chat_id,
        "kullanici_id" => $personel_id,
        "rating" => $rating,
        "dogru_mu" => $dogru_mu,
        "duzeltme" => $duzeltme
    ]);
    
    // Knowledge base güncelle
    if ($dogru_mu == 1 && $rating >= 4) {
        $chat_sql = "SELECT soru FROM ai_chat_history WHERE id = :chat_id AND firma_id = :firma_id";
        $chat_sth = $conn->prepare($chat_sql);
        $chat_sth->execute([
            "chat_id" => $chat_id,
            "firma_id" => $firma_id
        ]);
        $chat = $chat_sth->fetch(PDO::FETCH_ASSOC);
        
        if ($chat) {
            $keywords = preg_split("/[\s,?.!]+/u", mb_strtolower($chat["soru"], "UTF-8"));
            $stopwords = ["nedir", "kadar", "nekadar", "nasıl", "bir", "için"];
            $keywords = array_diff($keywords, $stopwords);
            
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword, "UTF-8") > 3) {
                    $update_sql = "UPDATE ai_knowledge_base 
                                  SET basari_orani = LEAST(basari_orani + 5, 100)
                                  WHERE firma_id = :firma_id 
                                  AND anahtar_kelime = :keyword";
                    $update_sth = $conn->prepare($update_sql);
                    $update_sth->execute([
                        "firma_id" => $firma_id,
                        "keyword" => $keyword
                    ]);
                }
            }
        }
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Geri bildiriminiz kaydedildi! 🚀"
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
