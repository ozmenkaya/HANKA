<?php
/**
 * HANKA Alert Agent
 * Stok, ödeme, kritik durum takibi ve otomatik bildirimler
 */

class AlertAgent {
    private $conn;
    private $firma_id;
    
    // Alert seviyeleri
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_CRITICAL = 'critical';
    
    // Alert tipleri
    const TYPE_STOCK = 'stock';
    const TYPE_PAYMENT = 'payment';
    const TYPE_ORDER = 'order';
    const TYPE_SYSTEM = 'system';
    
    public function __construct($conn, $firma_id) {
        $this->conn = $conn;
        $this->firma_id = $firma_id;
    }
    
    /**
     * Ana kontrol metodu
     */
    public function check($params) {
        $check_type = $params['type'] ?? 'all';
        
        $alerts = [];
        
        switch ($check_type) {
            case 'stock':
                $alerts = array_merge($alerts, $this->checkStock());
                break;
            case 'payment':
                $alerts = array_merge($alerts, $this->checkPayments());
                break;
            case 'order':
                $alerts = array_merge($alerts, $this->checkOrders());
                break;
            case 'all':
            default:
                $alerts = array_merge(
                    $this->checkStock(),
                    $this->checkPayments(),
                    $this->checkOrders(),
                    $this->checkSystem()
                );
                break;
        }
        
        // Alert seviyesine göre sırala (critical önce)
        usort($alerts, function($a, $b) {
            $levels = [
                self::LEVEL_CRITICAL => 3,
                self::LEVEL_WARNING => 2,
                self::LEVEL_INFO => 1
            ];
            return ($levels[$b['level']] ?? 0) - ($levels[$a['level']] ?? 0);
        });
        
        $summary = $this->generateAlertSummary($alerts);
        
        // Alert'leri database'e kaydet
        $this->logAlerts($alerts);
        
        return [
            'success' => true,
            'type' => 'alert_check',
            'alert_count' => count($alerts),
            'alerts' => $alerts,
            'summary' => $summary
        ];
    }
    
    /**
     * Stok kontrolleri
     * NOT: Gerçek stok tablosu yoksa örnek data döner
     */
    private function checkStock() {
        $alerts = [];
        
        // Stok kalemleri tablosundan basit kontrol
        $sql = "SELECT COUNT(*) as count FROM stok_kalemleri WHERE firma_id = {$this->firma_id}";
        
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) {
                // Örnek alert - gerçek stok kontrolü için özel tablo yapısı gerekli
                $alerts[] = [
                    'type' => self::TYPE_STOCK,
                    'level' => self::LEVEL_INFO,
                    'title' => "Stok Sistemi Aktif",
                    'message' => "{$row['count']} adet stok kalemi kayıtlı. Detaylı stok takibi için tablo yapısı güncellenmeli.",
                    'data' => $row,
                    'action_required' => false,
                    'suggested_action' => 'Stok modülü yapılandırması tamamlanmalı'
                ];
            }
        }
        
        // NOT: Gerçek urunler tablosu olmadığı için önceki sorguları devre dışı bıraktık
        // Eğer ürün/stok tablosu eklenirse aşağıdaki kodları aktif edin:
        /*
        $sql = "SELECT u.id, u.urun_adi, u.stok_miktar, u.min_stok_miktar,
                       (u.min_stok_miktar - u.stok_miktar) as eksik_miktar
                FROM urunler u
                WHERE u.firma_id = {$this->firma_id}
                  AND u.stok_miktar < u.min_stok_miktar
                  AND u.aktif = 1
                ORDER BY (u.min_stok_miktar - u.stok_miktar) DESC
                LIMIT 20";
        */
        
        return $alerts;
    }
    
    /**
     * Ödeme kontrolleri
     */
    private function checkPayments() {
        $alerts = [];
        
        // Ödeme sistemi basit kontrol
        $alerts[] = [
            'type' => self::TYPE_PAYMENT,
            'level' => self::LEVEL_INFO,
            'title' => "Ödeme Sistemi",
            'message' => "Ödeme takip modülü yapılandırılabilir. Fatura ve vade takibi özelleştirilebilir.",
            'data' => [],
            'action_required' => false,
            'suggested_action' => 'Ödeme modülü özelleştirilebilir'
        ];
        
        return $alerts;
    }
    
    /**
     * Sipariş kontrolleri
     */
    private function checkOrders() {
        $alerts = [];
        
        // Sipariş tablosu kontrolü - basit sayım
        $sql = "SELECT COUNT(*) as count FROM siparisler WHERE firma_id = {$this->firma_id}";
        
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) {
                $alerts[] = [
                    'type' => self::TYPE_ORDER,
                    'level' => self::LEVEL_INFO,
                    'title' => "Sipariş Sistemi Aktif",
                    'message' => "{$row['count']} adet sipariş kaydı mevcut. Detaylı sipariş takibi yapılandırılabilir.",
                    'data' => $row,
                    'action_required' => false,
                    'suggested_action' => 'Sipariş modülü özelleştirilebilir'
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Sistem kontrolleri
     */
    private function checkSystem() {
        $alerts = [];
        
        // AI Cache performansı kontrolü
        $sql = "SELECT COUNT(*) as total_queries,
                       SUM(CASE WHEN hit_count > 0 THEN 1 ELSE 0 END) as cached_queries
                FROM ai_cache
                WHERE firma_id = {$this->firma_id}
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $cache_hit_rate = $row['total_queries'] > 0 
                ? ($row['cached_queries'] / $row['total_queries']) * 100 
                : 0;
            
            $alerts[] = [
                'type' => self::TYPE_SYSTEM,
                'level' => self::LEVEL_INFO,
                'title' => "AI Cache Durumu",
                'message' => "Son 7 günde {$row['total_queries']} sorgu, cache hit rate: %" . round($cache_hit_rate, 1),
                'data' => $row,
                'action_required' => false,
                'suggested_action' => $cache_hit_rate < 30 ? 'Cache stratejisi geliştirilebilir' : 'Cache performansı iyi'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Alert özeti oluştur
     */
    private function generateAlertSummary($alerts) {
        if (empty($alerts)) {
            return "✅ Tüm sistemler normal çalışıyor. Alert bulunmuyor.";
        }
        
        $critical_count = count(array_filter($alerts, fn($a) => $a['level'] === self::LEVEL_CRITICAL));
        $warning_count = count(array_filter($alerts, fn($a) => $a['level'] === self::LEVEL_WARNING));
        $info_count = count(array_filter($alerts, fn($a) => $a['level'] === self::LEVEL_INFO));
        
        $summary = "🚨 " . count($alerts) . " alert tespit edildi:\n";
        
        if ($critical_count > 0) {
            $summary .= "❗ {$critical_count} KRİTİK\n";
        }
        if ($warning_count > 0) {
            $summary .= "⚠️  {$warning_count} UYARI\n";
        }
        if ($info_count > 0) {
            $summary .= "ℹ️  {$info_count} BİLGİ\n";
        }
        
        // En kritik alert'leri listele
        $critical_alerts = array_filter($alerts, fn($a) => $a['level'] === self::LEVEL_CRITICAL);
        if (!empty($critical_alerts)) {
            $summary .= "\n🔴 Kritik Durumlar:\n";
            foreach (array_slice($critical_alerts, 0, 3) as $alert) {
                $summary .= "• {$alert['title']}\n";
            }
        }
        
        return trim($summary);
    }
    
    /**
     * Alert'leri kaydet
     */
    private function logAlerts($alerts) {
        if (empty($alerts)) return;
        
        foreach ($alerts as $alert) {
            $type = $this->conn->real_escape_string($alert['type']);
            $level = $this->conn->real_escape_string($alert['level']);
            $title = $this->conn->real_escape_string($alert['title']);
            $message = $this->conn->real_escape_string($alert['message']);
            $data_json = $this->conn->real_escape_string(json_encode($alert['data'], JSON_UNESCAPED_UNICODE));
            
            $sql = "INSERT INTO agent_alerts (firma_id, alert_type, alert_level, title, message, data, created_at)
                    VALUES ({$this->firma_id}, '$type', '$level', '$title', '$message', '$data_json', NOW())";
            
            $this->conn->query($sql);
        }
    }
    
    public function getStatus() {
        return [
            'active' => true,
            'name' => 'AlertAgent',
            'capabilities' => [
                'stock_monitoring',
                'payment_tracking',
                'order_tracking',
                'system_monitoring'
            ]
        ];
    }
}
