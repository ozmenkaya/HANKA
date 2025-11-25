<?php
/**
 * HANKA Analytics Agent
 * Veri analizi, raporlama, trend tespiti, anomali analizi
 */

require_once __DIR__ . '/../AIChatEngine.php';

class AnalyticsAgent {
    private $conn;
    private $firma_id;
    private $ai_engine;
    
    public function __construct($conn, $firma_id) {
        $this->conn = $conn;
        $this->firma_id = $firma_id;
        $this->ai_engine = new AIChatEngine($conn, $firma_id, 0); // Kullanıcı ID 0 = system
    }
    
    /**
     * Ana analiz metodu
     */
    public function analyze($params) {
        $analysis_type = $params['type'] ?? 'general';
        
        switch ($analysis_type) {
            case 'daily':
                return $this->dailyReport($params);
            case 'weekly':
                return $this->weeklyReport($params);
            case 'monthly':
                return $this->monthlyReport($params);
            case 'trend':
                return $this->trendAnalysis($params);
            case 'anomaly':
                return $this->anomalyDetection($params);
            case 'comparison':
                return $this->comparativeAnalysis($params);
            default:
                return $this->generalAnalysis($params);
        }
    }
    
    /**
     * Günlük rapor
     */
    public function dailyReport($params) {
        $date = $params['date'] ?? date('Y-m-d');
        
        error_log("📊 Günlük rapor oluşturuluyor: $date");
        
        // AI'ya sor
        $question = "Bugünkü ({$date}) genel durumu özetle. Sipariş, üretim, satış, stok durumu.";
        $ai_response = $this->ai_engine->chat($question);
        
        // Database'den rakamsal data
        $stats = $this->getDailyStats($date);
        
        return [
            'success' => true,
            'type' => 'daily_report',
            'date' => $date,
            'data' => $stats,
            'summary' => $ai_response['answer'] ?? 'Rapor oluşturulamadı',
            'ai_analysis' => $ai_response
        ];
    }
    
    /**
     * Haftalık rapor
     */
    public function weeklyReport($params) {
        $end_date = $params['end_date'] ?? date('Y-m-d');
        $start_date = $params['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        
        error_log("📊 Haftalık rapor: $start_date - $end_date");
        
        $question = "Son 7 günün ($start_date ile $end_date arası) performansını analiz et. " .
                   "Trend, karşılaştırma, öne çıkanlar.";
        
        $ai_response = $this->ai_engine->chat($question);
        
        $stats = $this->getWeeklyStats($start_date, $end_date);
        
        return [
            'success' => true,
            'type' => 'weekly_report',
            'period' => "$start_date - $end_date",
            'data' => $stats,
            'summary' => $ai_response['answer'] ?? 'Rapor oluşturulamadı',
            'ai_analysis' => $ai_response
        ];
    }
    
    /**
     * Aylık rapor
     */
    public function monthlyReport($params) {
        $month = $params['month'] ?? date('Y-m');
        
        error_log("📊 Aylık rapor: $month");
        
        $question = "$month ayının genel performansını özetle. " .
                   "En iyi/en kötü günler, müşteriler, ürünler.";
        
        $ai_response = $this->ai_engine->chat($question);
        
        $stats = $this->getMonthlyStats($month);
        
        return [
            'success' => true,
            'type' => 'monthly_report',
            'month' => $month,
            'data' => $stats,
            'summary' => $ai_response['answer'] ?? 'Rapor oluşturulamadı',
            'ai_analysis' => $ai_response
        ];
    }
    
    /**
     * Trend analizi
     */
    public function trendAnalysis($params) {
        $metric = $params['metric'] ?? 'sales';
        $period = $params['period'] ?? '30days';
        
        error_log("📈 Trend analizi: $metric ($period)");
        
        $question = "Son {$period} için {$metric} metriğindeki trendi analiz et. " .
                   "Artış/azalış var mı? Sebepleri neler olabilir?";
        
        $ai_response = $this->ai_engine->chat($question);
        
        // Trend datası
        $trend_data = $this->getTrendData($metric, $period);
        
        // Basit trend hesaplama
        $trend_direction = $this->calculateTrendDirection($trend_data);
        
        return [
            'success' => true,
            'type' => 'trend_analysis',
            'metric' => $metric,
            'period' => $period,
            'data' => $trend_data,
            'trend_direction' => $trend_direction, // up, down, stable
            'summary' => $ai_response['answer'] ?? 'Trend analizi yapılamadı',
            'ai_analysis' => $ai_response
        ];
    }
    
    /**
     * Anomali tespiti
     */
    public function anomalyDetection($params) {
        error_log("🔍 Anomali tespiti yapılıyor");
        
        $anomalies = [];
        
        // 1. Olağandışı sipariş miktarları
        $sql = "SELECT s.id, s.siparis_no, s.miktar, m.unvan, 
                       AVG(s2.miktar) as avg_miktar
                FROM siparisler s
                LEFT JOIN musteriler m ON s.musteri_id = m.id
                LEFT JOIN siparisler s2 ON s.musteri_id = s2.musteri_id 
                    AND s2.id != s.id
                    AND s2.tarih >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                WHERE s.firma_id = {$this->firma_id}
                  AND s.tarih >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY s.id
                HAVING s.miktar > (avg_miktar * 3) OR s.miktar < (avg_miktar * 0.3)
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $anomalies[] = [
                    'type' => 'unusual_order_quantity',
                    'severity' => 'medium',
                    'description' => "Sipariş {$row['siparis_no']}: " .
                                   "Miktar {$row['miktar']}, ortalama {$row['avg_miktar']}",
                    'data' => $row
                ];
            }
        }
        
        // 2. Olağandışı fiyat değişimleri
        $sql = "SELECT u.id, u.urun_adi, u.satis_fiyat,
                       LAG(u.satis_fiyat) OVER (ORDER BY u.updated_at) as prev_fiyat
                FROM urunler u
                WHERE u.firma_id = {$this->firma_id}
                  AND u.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                HAVING ABS((u.satis_fiyat - prev_fiyat) / prev_fiyat * 100) > 20
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $change_pct = round((($row['satis_fiyat'] - $row['prev_fiyat']) / $row['prev_fiyat']) * 100, 1);
                $anomalies[] = [
                    'type' => 'price_change',
                    'severity' => 'high',
                    'description' => "Ürün {$row['urun_adi']}: Fiyat %{$change_pct} değişti",
                    'data' => $row
                ];
            }
        }
        
        // 3. Stok hareketlerinde anomali
        $sql = "SELECT s.urun_id, u.urun_adi, 
                       COUNT(*) as hareket_sayisi,
                       SUM(s.miktar) as toplam_miktar
                FROM stok_hareketleri s
                LEFT JOIN urunler u ON s.urun_id = u.id
                WHERE s.firma_id = {$this->firma_id}
                  AND s.tarih >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY s.urun_id
                HAVING hareket_sayisi > 20 OR ABS(toplam_miktar) > 1000
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $anomalies[] = [
                    'type' => 'unusual_stock_movement',
                    'severity' => 'medium',
                    'description' => "Ürün {$row['urun_adi']}: " .
                                   "{$row['hareket_sayisi']} hareket, toplam {$row['toplam_miktar']}",
                    'data' => $row
                ];
            }
        }
        
        $summary = count($anomalies) > 0 
            ? "🔍 " . count($anomalies) . " adet anomali tespit edildi!"
            : "✅ Anomali tespit edilmedi.";
        
        return [
            'success' => true,
            'type' => 'anomaly_detection',
            'anomaly_count' => count($anomalies),
            'data' => $anomalies,
            'summary' => $summary
        ];
    }
    
    /**
     * Karşılaştırmalı analiz
     */
    public function comparativeAnalysis($params) {
        $compare_type = $params['compare'] ?? 'period'; // period, customer, product
        
        error_log("🔄 Karşılaştırmalı analiz: $compare_type");
        
        $question = "Bu ay ile geçen ayı karşılaştır. Farklar, değişimler, öne çıkanlar.";
        $ai_response = $this->ai_engine->chat($question);
        
        // Karşılaştırma datası
        $comparison_data = $this->getComparisonData($compare_type);
        
        return [
            'success' => true,
            'type' => 'comparative_analysis',
            'comparison_type' => $compare_type,
            'data' => $comparison_data,
            'summary' => $ai_response['answer'] ?? 'Karşılaştırma yapılamadı',
            'ai_analysis' => $ai_response
        ];
    }
    
    /**
     * Genel analiz
     */
    private function generalAnalysis($params) {
        $query = $params['query'] ?? 'Genel durumu analiz et';
        
        error_log("📊 Genel analiz: $query");
        
        $ai_response = $this->ai_engine->chat($query);
        
        return [
            'success' => true,
            'type' => 'general_analysis',
            'query' => $query,
            'summary' => $ai_response['answer'] ?? 'Analiz yapılamadı',
            'data' => $ai_response
        ];
    }
    
    // Helper methods
    
    private function getDailyStats($date) {
        $sql = "SELECT 
                    COUNT(DISTINCT s.id) as siparis_sayisi,
                    SUM(s.miktar) as toplam_miktar,
                    SUM(s.tutar) as toplam_tutar,
                    COUNT(DISTINCT s.musteri_id) as musteri_sayisi
                FROM siparisler s
                WHERE s.firma_id = {$this->firma_id}
                  AND DATE(s.tarih) = '$date'";
        
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : [];
    }
    
    private function getWeeklyStats($start_date, $end_date) {
        $sql = "SELECT 
                    COUNT(DISTINCT s.id) as siparis_sayisi,
                    SUM(s.miktar) as toplam_miktar,
                    SUM(s.tutar) as toplam_tutar,
                    COUNT(DISTINCT s.musteri_id) as musteri_sayisi,
                    DATE(s.tarih) as gun
                FROM siparisler s
                WHERE s.firma_id = {$this->firma_id}
                  AND DATE(s.tarih) BETWEEN '$start_date' AND '$end_date'
                GROUP BY DATE(s.tarih)
                ORDER BY gun";
        
        $result = $this->conn->query($sql);
        $stats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }
        return $stats;
    }
    
    private function getMonthlyStats($month) {
        $sql = "SELECT 
                    COUNT(DISTINCT s.id) as siparis_sayisi,
                    SUM(s.miktar) as toplam_miktar,
                    SUM(s.tutar) as toplam_tutar,
                    COUNT(DISTINCT s.musteri_id) as musteri_sayisi,
                    AVG(s.tutar) as ortalama_tutar
                FROM siparisler s
                WHERE s.firma_id = {$this->firma_id}
                  AND DATE_FORMAT(s.tarih, '%Y-%m') = '$month'";
        
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : [];
    }
    
    private function getTrendData($metric, $period) {
        // Basitleştirilmiş trend datası
        $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT);
        
        $sql = "SELECT DATE(tarih) as gun, 
                       COUNT(*) as siparis_sayisi,
                       SUM(tutar) as toplam_tutar
                FROM siparisler
                WHERE firma_id = {$this->firma_id}
                  AND tarih >= DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY DATE(tarih)
                ORDER BY gun";
        
        $result = $this->conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    private function calculateTrendDirection($data) {
        if (count($data) < 2) return 'insufficient_data';
        
        $values = array_column($data, 'toplam_tutar');
        $first_half = array_slice($values, 0, floor(count($values) / 2));
        $second_half = array_slice($values, floor(count($values) / 2));
        
        $avg_first = array_sum($first_half) / count($first_half);
        $avg_second = array_sum($second_half) / count($second_half);
        
        $change_pct = (($avg_second - $avg_first) / $avg_first) * 100;
        
        if ($change_pct > 10) return 'up';
        if ($change_pct < -10) return 'down';
        return 'stable';
    }
    
    private function getComparisonData($type) {
        // Basit karşılaştırma - bu ay vs geçen ay
        $sql = "SELECT 
                    DATE_FORMAT(tarih, '%Y-%m') as ay,
                    COUNT(*) as siparis_sayisi,
                    SUM(tutar) as toplam_tutar
                FROM siparisler
                WHERE firma_id = {$this->firma_id}
                  AND tarih >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
                GROUP BY DATE_FORMAT(tarih, '%Y-%m')
                ORDER BY ay DESC
                LIMIT 2";
        
        $result = $this->conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    public function getStatus() {
        return [
            'active' => true,
            'name' => 'AnalyticsAgent',
            'capabilities' => [
                'daily_report',
                'weekly_report',
                'monthly_report',
                'trend_analysis',
                'anomaly_detection',
                'comparative_analysis'
            ]
        ];
    }
}
