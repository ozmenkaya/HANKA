<?php
// HANKA AI - Get API Key Tool
// Veritabanından OpenAI API anahtarını çeker

$host = 'localhost';
$db   = 'panelhankasys_crm2';
$user = 'hanka_user';
$pass = 'HankaDB2025!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // İlk bulunan API key'i al (genelde firma_id=1 veya dolu olan ilk kayıt)
    $stmt = $pdo->query("SELECT openai_api_key FROM ai_agent_settings WHERE openai_api_key IS NOT NULL AND openai_api_key != '' LIMIT 1");
    $row = $stmt->fetch();
    
    if ($row) {
        echo trim($row['openai_api_key']);
    } else {
        echo "ERROR: API Key not found in database.";
        exit(1);
    }
    
} catch (\PDOException $e) {
    echo "ERROR: " . $e->getMessage();
    exit(1);
}
