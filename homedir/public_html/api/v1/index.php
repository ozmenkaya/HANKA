<?php
/**
 * HANKA CRM API v1
 * Dış sistemlerle entegrasyon için RESTful API
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once("../../include/db.php");

// API Key kontrolü
function validateApiKey($key) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, firma_id, name, permissions FROM api_keys WHERE api_key = ? AND is_active = 1");
    $stmt->execute([$key]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Response helper
function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Error helper
function sendError($message, $code = 400) {
    sendResponse([
        "success" => false,
        "error" => $message,
        "timestamp" => date("Y-m-d H:i:s")
    ], $code);
}

// API Key kontrol
$headers = getallheaders();
$apiKey = $headers["X-API-Key"] ?? $_GET["api_key"] ?? null;

if (!$apiKey) {
    sendError("API key gerekli", 401);
}

$auth = validateApiKey($apiKey);
if (!$auth) {
    sendError("Geçersiz API key", 403);
}

$firma_id = $auth["firma_id"];
$permissions = json_decode($auth["permissions"], true);

// Route parsing
$request_uri = $_SERVER["REQUEST_URI"];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace("/api/v1/", "", $path);
$parts = explode("/", trim($path, "/"));

$resource = $parts[0] ?? null;
$id = $parts[1] ?? null;
$method = $_SERVER["REQUEST_METHOD"];

// Input data
$input = json_decode(file_get_contents("php://input"), true);

// Router
try {
    switch($resource) {
        case "customers":
        case "musteriler":
            include("endpoints/customers.php");
            break;
            
        case "orders":
        case "siparisler":
            include("endpoints/orders.php");
            break;
            
        case "status":
            sendResponse([
                "success" => true,
                "version" => "1.0",
                "timestamp" => date("Y-m-d H:i:s"),
                "authenticated_as" => $auth["name"],
                "firma_id" => $firma_id
            ]);
            break;
            
        default:
            sendError("Geçersiz endpoint: " . $resource, 404);
    }
} catch (Exception $e) {
    sendError("Sunucu hatası: " . $e->getMessage(), 500);
}
