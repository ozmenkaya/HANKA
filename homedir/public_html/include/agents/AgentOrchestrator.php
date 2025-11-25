<?php
/**
 * HANKA Multi-Agent System - Orchestrator
 * Tüm agent'ları yönetir, task'leri dağıtır ve sonuçları toplar
 */

require_once __DIR__ . '/AnalyticsAgent.php';
require_once __DIR__ . '/AlertAgent.php';
require_once __DIR__ . '/ActionAgent.php';

class AgentOrchestrator {
    private $conn;
    private $firma_id;
    private $agents = [];
    private $conversation_history = [];
    
    // Agent tipleri
    const AGENT_ANALYTICS = 'analytics';
    const AGENT_ALERT = 'alert';
    const AGENT_ACTION = 'action';
    const AGENT_SCHEDULER = 'scheduler';
    
    // Task priority
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_CRITICAL = 4;
    
    public function __construct($conn, $firma_id) {
        $this->conn = $conn;
        $this->firma_id = $firma_id;
        $this->initializeAgents();
    }
    
    /**
     * Agent'ları başlat
     */
    private function initializeAgents() {
        $this->agents[self::AGENT_ANALYTICS] = new AnalyticsAgent($this->conn, $this->firma_id);
        $this->agents[self::AGENT_ALERT] = new AlertAgent($this->conn, $this->firma_id);
        $this->agents[self::AGENT_ACTION] = new ActionAgent($this->conn, $this->firma_id);
        
        error_log("🤖 Multi-Agent System başlatıldı (Firma: {$this->firma_id})");
    }
    
    /**
     * Ana task çalıştırıcı - Hangi agent'in yapacağına karar verir
     */
    public function executeTask($task_description, $params = [], $priority = self::PRIORITY_NORMAL) {
        $start_time = microtime(true);
        
        error_log("🎯 Task başlatıldı: $task_description (Priority: $priority)");
        
        try {
            // 1. Task'i analiz et - Hangi agent yapmalı?
            $agent_plan = $this->planTask($task_description, $params);
            
            // 2. Agent'leri sırayla veya paralel çalıştır
            $results = [];
            
            if ($agent_plan['mode'] === 'sequential') {
                // Sıralı çalıştırma
                foreach ($agent_plan['agents'] as $agent_task) {
                    $agent_type = $agent_task['agent'];
                    $agent_action = $agent_task['action'];
                    $agent_params = array_merge($params, $agent_task['params'] ?? []);
                    
                    error_log("  ➜ {$agent_type} agent çalışıyor: {$agent_action}");
                    
                    $result = $this->runAgent($agent_type, $agent_action, $agent_params);
                    $results[$agent_type] = $result;
                    
                    // Önceki sonuçları sonraki agent'e aktar
                    if (isset($result['data'])) {
                        $params['previous_result'] = $result['data'];
                    }
                }
            } else {
                // Paralel çalıştırma (async simulation)
                foreach ($agent_plan['agents'] as $agent_task) {
                    $agent_type = $agent_task['agent'];
                    $agent_action = $agent_task['action'];
                    $agent_params = array_merge($params, $agent_task['params'] ?? []);
                    
                    error_log("  ⚡ {$agent_type} agent (parallel): {$agent_action}");
                    
                    $result = $this->runAgent($agent_type, $agent_action, $agent_params);
                    $results[$agent_type] = $result;
                }
            }
            
            // 3. Sonuçları birleştir
            $final_result = $this->aggregateResults($results, $agent_plan);
            
            // 4. Conversation history'e kaydet
            $this->logConversation($task_description, $final_result);
            
            $execution_time = round(microtime(true) - $start_time, 3);
            error_log("✅ Task tamamlandı ({$execution_time}s)");
            
            return [
                'success' => true,
                'task' => $task_description,
                'execution_time' => $execution_time,
                'agents_used' => array_keys($results),
                'results' => $results,
                'final_result' => $final_result
            ];
            
        } catch (Exception $e) {
            error_log("❌ Task hatası: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'task' => $task_description
            ];
        }
    }
    
    /**
     * Task planlama - Hangi agent(ler) kullanılacak?
     */
    private function planTask($task_description, $params) {
        $task_lower = strtolower($task_description);
        $plan = [
            'mode' => 'sequential', // sequential veya parallel
            'agents' => []
        ];
        
        // Anahtar kelimelere göre agent seçimi
        
        // ANALYTICS AGENT
        if (preg_match('/rapor|analiz|istatistik|trend|özet|performans|karşılaştır/u', $task_lower)) {
            $plan['agents'][] = [
                'agent' => self::AGENT_ANALYTICS,
                'action' => 'analyze',
                'params' => []
            ];
        }
        
        // ALERT AGENT
        if (preg_match('/uyar|kontrol|tespit|izle|bildir|alarm/u', $task_lower)) {
            $plan['agents'][] = [
                'agent' => self::AGENT_ALERT,
                'action' => 'check',
                'params' => []
            ];
        }
        
        // ACTION AGENT
        if (preg_match('/gönder|ekle|sil|güncelle|oluştur|işle|email|whatsapp/u', $task_lower)) {
            $plan['agents'][] = [
                'agent' => self::AGENT_ACTION,
                'action' => 'execute',
                'params' => []
            ];
        }
        
        // Kombine task'ler için mode belirleme
        if (count($plan['agents']) > 1) {
            // "Analiz yap VE rapor gönder" → sequential
            if (preg_match('/ve|sonra|ardından/u', $task_lower)) {
                $plan['mode'] = 'sequential';
            }
            // "Stok kontrol et VE kritik olanları bildir" → parallel
            else {
                $plan['mode'] = 'parallel';
            }
        }
        
        // Agent bulunamadıysa default olarak Analytics
        if (empty($plan['agents'])) {
            $plan['agents'][] = [
                'agent' => self::AGENT_ANALYTICS,
                'action' => 'analyze',
                'params' => []
            ];
        }
        
        return $plan;
    }
    
    /**
     * Tek bir agent'i çalıştır
     */
    private function runAgent($agent_type, $action, $params) {
        if (!isset($this->agents[$agent_type])) {
            throw new Exception("Agent bulunamadı: $agent_type");
        }
        
        $agent = $this->agents[$agent_type];
        
        // Agent'in action metodunu çağır
        if (method_exists($agent, $action)) {
            return $agent->$action($params);
        }
        
        // Genel execute metodu
        if (method_exists($agent, 'execute')) {
            return $agent->execute($action, $params);
        }
        
        throw new Exception("Agent action bulunamadı: $agent_type::$action");
    }
    
    /**
     * Sonuçları birleştir
     */
    private function aggregateResults($results, $plan) {
        $aggregated = [
            'summary' => '',
            'details' => [],
            'actions_taken' => [],
            'alerts' => []
        ];
        
        foreach ($results as $agent_type => $result) {
            if (!$result['success']) {
                continue;
            }
            
            // Agent tipine göre sonuçları kategorize et
            switch ($agent_type) {
                case self::AGENT_ANALYTICS:
                    $aggregated['details']['analytics'] = $result['data'];
                    $aggregated['summary'] .= $result['summary'] ?? '';
                    break;
                    
                case self::AGENT_ALERT:
                    $aggregated['alerts'] = $result['alerts'] ?? [];
                    $aggregated['summary'] .= "\n\n" . ($result['summary'] ?? '');
                    break;
                    
                case self::AGENT_ACTION:
                    $aggregated['actions_taken'] = $result['actions'] ?? [];
                    $aggregated['summary'] .= "\n\n" . ($result['summary'] ?? '');
                    break;
            }
        }
        
        $aggregated['summary'] = trim($aggregated['summary']);
        
        return $aggregated;
    }
    
    /**
     * Conversation history'e kaydet
     */
    private function logConversation($task, $result) {
        $this->conversation_history[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'task' => $task,
            'result' => $result,
            'firma_id' => $this->firma_id
        ];
        
        // Database'e kaydet
        $task_escaped = $this->conn->real_escape_string($task);
        $result_json = $this->conn->real_escape_string(json_encode($result, JSON_UNESCAPED_UNICODE));
        
        $sql = "INSERT INTO agent_conversation_log (firma_id, task_description, result_data, created_at) 
                VALUES ({$this->firma_id}, '$task_escaped', '$result_json', NOW())";
        
        $this->conn->query($sql);
    }
    
    /**
     * Agent durumlarını getir
     */
    public function getAgentStatus() {
        $status = [];
        
        foreach ($this->agents as $type => $agent) {
            if (method_exists($agent, 'getStatus')) {
                $status[$type] = $agent->getStatus();
            } else {
                $status[$type] = ['active' => true, 'name' => get_class($agent)];
            }
        }
        
        return $status;
    }
    
    /**
     * Belirli bir agent'e direkt erişim
     */
    public function getAgent($agent_type) {
        return $this->agents[$agent_type] ?? null;
    }
}
