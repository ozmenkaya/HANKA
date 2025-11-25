<?php
/**
 * AI Chat Debug Endpoint
 */

// Tüm hataları logla
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/ai_chat_debug.log");

// Output buffering
ob_start();

try {
    require_once "include/oturum_kontrol.php";
    require_once "include/db.php";
    require_once "include/AIChatEngine.php";
    
    $output_before_clean = ob_get_contents();
    ob_clean();
    
    header("Content-Type: application/json; charset=utf-8");
    
    $debug_info = [
        "step" => "initialized",
        "output_captured" => strlen($output_before_clean) . " bytes",
        "output_preview" => substr($output_before_clean, 0, 200),
        "firma_id" => $firma_id ?? null,
        "kullanici_id" => isset($kullanici) ? $kullanici["id"] : null,
        "request_method" => $_SERVER["REQUEST_METHOD"],
        "content_type" => $_SERVER["CONTENT_TYPE"] ?? "none"
    ];
    
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $input = json_decode(file_get_contents("php://input"), true);
        $debug_info["input"] = $input;
        $debug_info["question"] = $input["question"] ?? "none";
    }
    
    echo json_encode([
        "success" => true,
        "debug" => $debug_info,
        "message" => "Debug bilgileri"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

ob_end_flush();
