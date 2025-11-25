<?php
/**
 * OpenAI API Helper Class
 * Rapor analizi ve özetleme için
 */
class OpenAI {
    private $api_key;
    private $api_url = "https://api.openai.com/v1/chat/completions";
    private $model = "gpt-4o-mini"; // Uygun fiyat/performans
    
    public function __construct($api_key = null) {
        // API key yoksa .env'den yükle
        if (!$api_key) {
            $env_file = __DIR__ . "/../.env";
            if (file_exists($env_file)) {
                $env = parse_ini_file($env_file);
                $this->api_key = $env["OPENAI_API_KEY"] ?? null;
                // Fine-tuned model varsa onu kullan
                $this->model = $env["OPENAI_FINETUNED_MODEL"] ?? $env["OPENAI_MODEL"] ?? $this->model;
            }
        } else {
            $this->api_key = $api_key;
        }
        
        if (!$this->api_key) {
            throw new Exception("OpenAI API key bulunamadı!");
        }
    }
    
    /**
     * API key'i döndür
     */
    public function getApiKey() {
        return $this->api_key;
    }
    
    /**
     * Rapor verilerini analiz et ve özet çıkar
     */
    public function analyzeReport($report_data, $report_name) {
        $prompt = $this->buildReportAnalysisPrompt($report_data, $report_name);
        
        return $this->chat([
            ["role" => "system", "content" => "Sen bir iş analitiği uzmanısın. Türkçe raporları analiz edip özet çıkarıyorsun. Profesyonel, özlü ve işe yarar bilgiler veriyorsun."],
            ["role" => "user", "content" => $prompt]
        ]);
    }
    
    /**
     * Rapor analizi için prompt oluştur
     */
    private function buildReportAnalysisPrompt($data, $report_name) {
        $total_records = count($data);
        
        // İlk 100 kaydı al (token limiti için)
        $sample_data = array_slice($data, 0, 100);
        
        // İstatistikleri hazırla
        $stats = $this->calculateStats($sample_data);
        
        $prompt = "Aşağıdaki \"$report_name\" raporunu analiz et:\n\n";
        $prompt .= "📊 GENEL BİLGİLER:\n";
        $prompt .= "- Toplam Kayıt: $total_records\n";
        $prompt .= "- Analiz Edilen: " . count($sample_data) . "\n\n";
        
        $prompt .= "📈 İSTATİSTİKLER:\n";
        foreach ($stats as $key => $value) {
            $prompt .= "- $key: $value\n";
        }
        
        $prompt .= "\n🎯 İSTENEN ANALİZ:\n";
        $prompt .= "1. En önemli 3-5 bulguyu özetle\n";
        $prompt .= "2. Dikkat çekici trendleri belirt\n";
        $prompt .= "3. İyileştirme önerileri sun\n";
        $prompt .= "4. Özet maksimum 200 kelime olsun\n";
        $prompt .= "5. Emoji kullan, markdown formatında yaz\n\n";
        
        $prompt .= "Lütfen profesyonel ve anlaşılır bir analiz yap.";
        
        return $prompt;
    }
    
    /**
     * Veri setinden istatistik çıkar
     */
    private function calculateStats($data) {
        if (empty($data)) {
            return ["Veri yok" => "-"];
        }
        
        $stats = [];
        $first_row = $data[0];
        
        // Sayısal alanları bul ve hesapla
        foreach ($first_row as $key => $value) {
            if (is_numeric($value)) {
                $values = array_column($data, $key);
                $values = array_filter($values, fn($v) => is_numeric($v));
                
                if (!empty($values)) {
                    $stats[ucfirst($key) . " (Toplam)"] = number_format(array_sum($values), 2);
                    $stats[ucfirst($key) . " (Ortalama)"] = number_format(array_sum($values) / count($values), 2);
                }
            }
        }
        
        // Kategorik alanları say
        foreach ($first_row as $key => $value) {
            if (!is_numeric($value) && strlen($value) < 50) {
                $values = array_column($data, $key);
                $counts = array_count_values(array_filter($values));
                arsort($counts);
                $top = array_slice($counts, 0, 3, true);
                
                if (!empty($top)) {
                    $top_str = implode(", ", array_map(fn($k, $v) => "$k ($v)", array_keys($top), $top));
                    $stats["En Çok " . ucfirst($key)] = $top_str;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * OpenAI Chat API çağrısı
     */
    public function chat($messages, $temperature = 0.7, $max_tokens = 1000) {
        $data = [
            "model" => $this->model,
            "messages" => $messages,
            "temperature" => $temperature,
            "max_tokens" => $max_tokens
        ];
        
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->api_key
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception("cURL hatası: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            throw new Exception("OpenAI API hatası: " . ($error["error"]["message"] ?? "Bilinmeyen hata"));
        }
        
        $result = json_decode($response, true);
        return $result["choices"][0]["message"]["content"] ?? "";
    }
    
    /**
     * Token tahmini (yaklaşık)
     */
    public function estimateTokens($text) {
        // Basit tahmin: ~4 karakter = 1 token
        return ceil(strlen($text) / 4);
    }
}
