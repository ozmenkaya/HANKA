<?php
/**
 * HANKA Multi-Agent System - API Endpoint
 * Agent'lara erişim ve task çalıştırma
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON'u bozmamak için

require_once __DIR__ . "/include/connect.php";
require_once __DIR__ . "/include/agents/AgentOrchestrator.php";

// CORS headers (gerekirse)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

try {
    // 1. Authentication kontrol
    session_start();
    
    $firma_id = $_SESSION['firma_id'] ?? null;
    $kullanici_id = $_SESSION['id'] ?? null;
    
    // API key ile de giriş yapılabilir (cron jobs için)
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    
    if (!$firma_id && $api_key) {
        // API key ile firma_id bulma
        $api_key_escaped = $conn->real_escape_string($api_key);
        $sql = "SELECT firma_id FROM api_keys 
                WHERE api_key = '$api_key_escaped' AND is_active = 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $firma_id = $row['firma_id'];
            $kullanici_id = 0; // System user
        }
    }
    
    if (!$firma_id) {
        throw new Exception("Yetkisiz erişim. Lütfen giriş yapın.");
    }
    
    // 2. Request'i parse et
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? 'status';
    
    $input_data = [];
    if ($method === 'POST') {
        $raw_input = file_get_contents('php://input');
        $input_data = json_decode($raw_input, true) ?? $_POST;
    } else {
        $input_data = $_GET;
    }
    
    // 3. Agent Orchestrator'ı başlat
    $orchestrator = new AgentOrchestrator($conn, $firma_id);
    
    // 4. Action'a göre işlem yap
    switch ($action) {
        // Agent durumu
        case 'status':
            $response = [
                'success' => true,
                'firma_id' => $firma_id,
                'agents' => $orchestrator->getAgentStatus(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
        
        // Task çalıştır
        case 'execute':
        case 'run':
            $task = $input_data['task'] ?? '';
            $params = $input_data['params'] ?? [];
            $priority = $input_data['priority'] ?? AgentOrchestrator::PRIORITY_NORMAL;
            
            if (empty($task)) {
                throw new Exception("Task parametresi gerekli");
            }
            
            $response = $orchestrator->executeTask($task, $params, $priority);
            break;
        
        // Günlük rapor (kısayol)
        case 'daily_report':
            $response = $orchestrator->executeTask(
                "Bugünkü günlük raporu oluştur",
                ['type' => 'daily']
            );
            break;
        
        // Alert kontrolü (kısayol)
        case 'check_alerts':
            $response = $orchestrator->executeTask(
                "Tüm sistemleri kontrol et ve alert'leri tespit et",
                ['type' => 'all']
            );
            break;
        
        // Düşük stok bildirimi (kısayol)
        case 'notify_low_stock':
            $recipients = $input_data['recipients'] ?? ['admin@hankasys.com'];
            
            $response = $orchestrator->executeTask(
                "Düşük stoklu ürünleri tespit et ve bildir",
                [
                    'action' => 'notify_low_stock',
                    'recipients' => $recipients
                ]
            );
            break;
        
        // Ödeme hatırlatıcısı toplu gönderim
        case 'send_payment_reminders':
            $limit = $input_data['limit'] ?? 10;
            
            // Geciken faturaları al
            $sql = "SELECT id FROM faturalar 
                    WHERE firma_id = $firma_id 
                      AND odeme_durumu != 'odendi'
                      AND vade_tarihi < NOW()
                    ORDER BY vade_tarihi
                    LIMIT $limit";
            
            $result = $conn->query($sql);
            $sent_count = 0;
            
            if ($result && $result->num_rows > 0) {
                $action_agent = $orchestrator->getAgent(AgentOrchestrator::AGENT_ACTION);
                
                while ($row = $result->fetch_assoc()) {
                    $result_action = $action_agent->execute([
                        'action' => 'send_payment_reminder',
                        'fatura_id' => $row['id']
                    ]);
                    
                    if ($result_action['success']) {
                        $sent_count++;
                    }
                }
            }
            
            $response = [
                'success' => true,
                'action' => 'send_payment_reminders',
                'sent_count' => $sent_count,
                'summary' => "$sent_count ödeme hatırlatıcısı gönderildi"
            ];
            break;
        
        // Anomali tespiti
        case 'detect_anomalies':
            $analytics_agent = $orchestrator->getAgent(AgentOrchestrator::AGENT_ANALYTICS);
            $response = $analytics_agent->analyze(['type' => 'anomaly']);
            break;
        
        // Haftalık rapor
        case 'weekly_report':
            $recipients = $input_data['recipients'] ?? [];
            
            if (empty($recipients)) {
                throw new Exception("Rapor alıcıları belirtilmeli (recipients parametresi)");
            }
            
            $response = $orchestrator->executeTask(
                "Haftalık rapor oluştur ve gönder",
                [
                    'type' => 'weekly',
                    'action' => 'create_report',
                    'report_type' => 'weekly',
                    'recipients' => $recipients
                ]
            );
            break;
        
        // Conversation history
        case 'history':
            $limit = $input_data['limit'] ?? 50;
            
            $sql = "SELECT * FROM agent_conversation_log 
                    WHERE firma_id = $firma_id 
                    ORDER BY created_at DESC 
                    LIMIT $limit";
            
            $result = $conn->query($sql);
            $history = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['result_data'] = json_decode($row['result_data'], true);
                    $history[] = $row;
                }
            }
            
            $response = [
                'success' => true,
                'history' => $history,
                'count' => count($history)
            ];
            break;
        
        // Alert listesi
        case 'alerts':
            $level = $input_data['level'] ?? null;
            $is_resolved = $input_data['is_resolved'] ?? 0;
            $limit = $input_data['limit'] ?? 50;
            
            $where = "firma_id = $firma_id AND is_resolved = $is_resolved";
            if ($level) {
                $level_escaped = $conn->real_escape_string($level);
                $where .= " AND alert_level = '$level_escaped'";
            }
            
            $sql = "SELECT * FROM agent_alerts 
                    WHERE $where 
                    ORDER BY created_at DESC 
                    LIMIT $limit";
            
            $result = $conn->query($sql);
            $alerts = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['data'] = json_decode($row['data'], true);
                    $alerts[] = $row;
                }
            }
            
            $response = [
                'success' => true,
                'alerts' => $alerts,
                'count' => count($alerts)
            ];
            break;
        
        // Alert'i resolve et
        case 'resolve_alert':
            $alert_id = $input_data['alert_id'] ?? 0;
            
            if ($alert_id <= 0) {
                throw new Exception("Alert ID gerekli");
            }
            
            $sql = "UPDATE agent_alerts 
                    SET is_resolved = 1, resolved_at = NOW() 
                    WHERE id = $alert_id AND firma_id = $firma_id";
            
            $success = $conn->query($sql);
            
            $response = [
                'success' => $success,
                'alert_id' => $alert_id,
                'message' => $success ? 'Alert resolved' : 'Failed to resolve alert'
            ];
            break;
        
        // Bilinmeyen action
        default:
            throw new Exception("Geçersiz action: $action");
    }
    
    // 5. Response dön
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
