<?php
session_name("PNL");
session_start();

if (!isset($_SESSION["giris_kontrol"])) {
    http_response_code(401);
    echo json_encode(["error" => "Oturum geçersiz"]);
    exit;
}

header('Content-Type: application/json');

// OpenAI API Key
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

if (empty($api_key)) {
    echo json_encode(["error" => "OpenAI API anahtarı bulunamadı"]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        // JSONL dosyasını OpenAI'ya yükle
        $file_path = __DIR__ . "/finetune_data.jsonl";
        
        if (!file_exists($file_path)) {
            echo json_encode(["error" => "finetune_data.jsonl bulunamadı. Önce finetune_prepare.php çalıştırın."]);
            exit;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/files");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $api_key
        ]);
        
        $cfile = new CURLFile($file_path, 'application/jsonl', 'finetune_data.jsonl');
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'purpose' => 'fine-tune',
            'file' => $cfile
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            echo json_encode([
                "success" => true,
                "file_id" => $data['id'],
                "message" => "Dosya yüklendi! File ID: " . $data['id']
            ]);
        } else {
            echo json_encode(["error" => "Dosya yükleme hatası", "details" => $response]);
        }
        break;
        
    case 'create':
        // Fine-tuning işi başlat
        $file_id = $_POST['file_id'] ?? '';
        $model = $_POST['model'] ?? 'gpt-4o-mini-2024-07-18'; // veya gpt-4o-2024-08-06
        
        if (empty($file_id)) {
            echo json_encode(["error" => "file_id gerekli"]);
            exit;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/fine_tuning/jobs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $api_key,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'training_file' => $file_id,
            'model' => $model,
            'suffix' => 'hanka-crm'
        ]));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            echo json_encode([
                "success" => true,
                "job_id" => $data['id'],
                "status" => $data['status'],
                "message" => "Fine-tuning başlatıldı! Job ID: " . $data['id']
            ]);
        } else {
            echo json_encode(["error" => "Fine-tuning başlatma hatası", "details" => $response]);
        }
        break;
        
    case 'status':
        // Fine-tuning durumu kontrol et
        $job_id = $_GET['job_id'] ?? '';
        
        if (empty($job_id)) {
            echo json_encode(["error" => "job_id gerekli"]);
            exit;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/fine_tuning/jobs/" . $job_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $api_key
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            echo json_encode([
                "success" => true,
                "status" => $data['status'],
                "fine_tuned_model" => $data['fine_tuned_model'] ?? null,
                "trained_tokens" => $data['trained_tokens'] ?? null,
                "message" => "Durum: " . $data['status']
            ]);
        } else {
            echo json_encode(["error" => "Durum sorgu hatası", "details" => $response]);
        }
        break;
        
    case 'list':
        // Tüm fine-tuning işlerini listele
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/fine_tuning/jobs?limit=10");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $api_key
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo $response;
        break;
        
    default:
        echo json_encode([
            "error" => "Geçersiz action",
            "available_actions" => ["upload", "create", "status", "list"]
        ]);
}
?>
