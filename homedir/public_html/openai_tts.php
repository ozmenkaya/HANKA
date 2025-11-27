<?php
require_once "include/db.php";

header('Content-Type: application/json');

// Oturum kontrolü
if (!isset($_SESSION["giris_kontrol"])) {
    http_response_code(401);
    echo json_encode(["error" => "Oturum geçersiz", "debug" => "giris_kontrol yok"]);
    exit;
}

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? '';
$model = $input['model'] ?? 'tts-1'; // tts-1 veya tts-1-hd
$voice = $input['voice'] ?? 'alloy'; // alloy, echo, fable, onyx, nova, shimmer

if (empty($text)) {
    http_response_code(400);
    echo json_encode(["error" => "Metin boş olamaz"]);
    exit;
}

// OpenAI API Key'i veritabanından al
$api_key = null;
try {
    $stmt = $conn->prepare("SELECT openai_api_key FROM ai_agent_settings WHERE firma_id = :firma_id");
    $stmt->execute([':firma_id' => $_SESSION['firma_id']]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($settings['openai_api_key'])) {
        $api_key = $settings['openai_api_key'];
    }
} catch (Exception $e) {
    // DB hatası olursa logla ama devam et (.env kontrolü için)
    error_log("OpenAI TTS DB Error: " . $e->getMessage());
}

// Eğer DB'de yoksa .env dosyasından bak
if (empty($api_key)) {
    $env_path = __DIR__ . "/.env";
    $env = [];
    if (file_exists($env_path)) {
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }
    }
    $api_key = $env["OPENAI_API_KEY"] ?? null;
}

if (empty($api_key)) {
    http_response_code(500);
    echo json_encode(["error" => "OpenAI API anahtarı bulunamadı"]);
    exit;
}

// OpenAI TTS API isteği
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/audio/speech");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $api_key,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => $model,
    "input" => $text,
    "voice" => $voice,
    "response_format" => "mp3",
    "speed" => floatval($input['speed'] ?? 1.0) // 0.25 - 4.0
]));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => "API isteği başarısız: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

if ($http_code !== 200) {
    http_response_code($http_code);
    echo json_encode(["error" => "OpenAI API hatası", "details" => $response]);
    exit;
}

// MP3 dosyasını base64 olarak döndür
$audio_base64 = base64_encode($response);
echo json_encode([
    "success" => true,
    "audio" => $audio_base64,
    "format" => "mp3"
]);
