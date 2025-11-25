<?php
// JSON çıktısı için - warning'leri log'a yaz ama ekranda gösterme
error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON bozulmasın
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/logs/ai_chat_error.log');
ob_start(); // Output buffering - temiz JSON için

session_name("PNL");
session_start();

if (!isset($_SESSION['giris_kontrol'])) {
    ob_clean();
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Oturum yok"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Session değişkenlerini al
$firma_id = $_SESSION['firma_id'] ?? null;
$personel_id = $_SESSION['personel_id'] ?? null;
$firma_adi = $_SESSION['firma_adi'] ?? null;

if (!$firma_id || !$personel_id) {
    ob_clean();
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(401);
    
    error_log("Session debug - firma_id: " . ($firma_id ?? 'null') . ", personel_id: " . ($personel_id ?? 'null'));
    error_log("All session keys: " . json_encode(array_keys($_SESSION)));
    
    echo json_encode(["success" => false, "error" => "Firma veya kullanıcı bilgisi eksik"], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once "include/db.php";
require_once "include/AIChatEngine.php";

ob_clean();
header("Content-Type: application/json; charset=utf-8");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Sadece POST");
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    $question = $input["question"] ?? "";
    
    if (empty(trim($question))) {
        throw new Exception("Soru boş");
    }
    
    $question = strip_tags($question);
    
    error_log("AI Chat - Firma: $firma_id ($firma_adi), Personel: $personel_id, Question: $question");
    
    // 🚀 PATTERN MATCHING - Müşteri marka sorguları için hızlı cevap
    $question_lower = mb_strtolower($question, 'UTF-8');
    
    // "komagene hangi firma", "migros firma", "carrefour firma bilgisi"
    if (preg_match('/^([a-zğüşıöçA-ZĞÜŞİÖÇ\s]+?)\s+(hangi|firma|kim|bilgi|bilgisi|unvan)/ui', $question_lower, $matches)) {
        $brand_name = trim($matches[1]);
        
        if (strlen($brand_name) >= 3) {
            $stmt = $conn->prepare("
                SELECT m.id, m.marka, m.firma_unvani 
                FROM musteri m 
                WHERE (m.marka LIKE :brand OR m.firma_unvani LIKE :brand) 
                  AND m.firma_id = :firma_id 
                LIMIT 1
            ");
            $stmt->execute([
                ':brand' => '%' . $brand_name . '%',
                ':firma_id' => $firma_id
            ]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                error_log("✅ PATTERN MATCH - Müşteri bulundu: " . $customer['marka']);
                
                echo json_encode([
                    "success" => true,
                    "answer" => "**" . $customer['marka'] . "** (Firma Ünvanı: " . $customer['firma_unvani'] . ")",
                    "data" => [$customer],
                    "html_table" => "<table class='table table-sm'><tr><th>Marka</th><th>Firma Ünvanı</th></tr><tr><td>" . htmlspecialchars($customer['marka']) . "</td><td>" . htmlspecialchars($customer['firma_unvani']) . "</td></tr></table>",
                    "sql" => "SELECT marka, firma_unvani FROM musteri WHERE (marka LIKE '%$brand_name%' OR firma_unvani LIKE '%$brand_name%') AND firma_id = $firma_id",
                    "response_time" => 0.05,
                    "record_count" => 1,
                    "source" => "pattern_match"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }
    
    $chat_engine = new AIChatEngine($conn, $firma_id, $personel_id);
    $result = $chat_engine->chat($question);
    
    if ($result["success"]) {
        // Cevaptaki firma ID'yi firma adıyla değiştir
        $answer = $result["answer"];
        if ($firma_adi) {
            // "firma id'si 16" -> "Helmex Firma"
            $answer = preg_replace(
                '/firma\s+id[\'"]?si\s+' . preg_quote($firma_id, '/') . '/ui',
                $firma_adi,
                $answer
            );
            // "firma_id 16" -> "Helmex Firma"
            $answer = preg_replace(
                '/firma[_\s]id\s+' . preg_quote($firma_id, '/') . '/ui',
                $firma_adi,
                $answer
            );
        }
        
        echo json_encode([
            "success" => true,
            "answer" => $answer,
            "data" => $result["data"],
            "html_table" => $result["html_table"] ?? "",
            "sql" => $result["sql"],
            "chat_id" => $result["chat_id"] ?? null,
            "response_time" => $result["response_time"] ?? 0,
            "record_count" => count($result["data"])
        ], JSON_UNESCAPED_UNICODE);
    } else {
        error_log("AI Error: " . json_encode($result));
        throw new Exception($result["error"] ?? "Bilinmeyen hata");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    error_log("Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
