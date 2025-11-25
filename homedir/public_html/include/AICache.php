<?php
/**
 * AI Response Cache
 * Aynı soruları tekrar sormamak için akıllı cache
 */

class AICache {
    private $conn;
    private $redis;
    private $use_redis = false;
    private $cache_duration = 3600; // 1 saat
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        // Redis bağlantısı
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                // 127.0.0.1 ve 6379 varsayılan değerlerdir
                if ($this->redis->connect('127.0.0.1', 6379, 1.0)) { // 1 sn timeout
                    $this->use_redis = true;
                    // Redis auth varsa buraya eklenebilir: $this->redis->auth('password');
                }
            } catch (Exception $e) {
                error_log("⚠️ Redis connection failed: " . $e->getMessage());
                $this->use_redis = false;
            }
        }
    }
    
    /**
     * Sorunun hash'ini oluştur (benzer sorular için)
     */
    private function generateQuestionHash($question, $firma_id) {
        // Küçük harfe çevir, noktalama işaretlerini kaldır
        $normalized = mb_strtolower($question, 'UTF-8');
        $normalized = preg_replace('/[^\w\s]/u', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);
        
        return md5($normalized . '_' . $firma_id);
    }
    
    /**
     * Cache'den cevap al
     */
    public function get($question, $firma_id) {
        $hash = $this->generateQuestionHash($question, $firma_id);
        
        // 1. REDIS KONTROLÜ (En Hızlı)
        if ($this->use_redis) {
            $redis_key = "ai_cache:{$firma_id}:{$hash}";
            $cached_data = $this->redis->get($redis_key);
            
            if ($cached_data) {
                $result = json_decode($cached_data, true);
                if ($result) {
                    // Hit sayısını artır (Async olarak DB'ye de yansıtmak lazım ama şimdilik Redis'te tutalım)
                    // $this->incrementHitCount($result['id']); // DB ID'si Redis'te olmayabilir
                    
                    error_log("⚡ REDIS HIT: {$question}");
                    $result['from_cache'] = true;
                    $result['response_time'] = 0.001; // Redis çok hızlı
                    return $result;
                }
            }
        }

        // 2. MYSQL KONTROLÜ (Persistent Cache)
        $sql = "SELECT * FROM ai_cache 
                WHERE question_hash = :hash 
                AND firma_id = :firma_id 
                AND created_at > DATE_SUB(NOW(), INTERVAL :duration SECOND)
                AND is_valid = 1
                ORDER BY hit_count DESC, created_at DESC
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'hash' => $hash,
            'firma_id' => $firma_id,
            'duration' => $this->cache_duration
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Hit sayısını artır
            $this->incrementHitCount($result['id']);
            
            error_log("✅ DB CACHE HIT: {$result['original_question']} (Hit: {$result['hit_count']})");
            
            $response = [
                'success' => true,
                'from_cache' => true,
                'answer' => $result['answer'],
                'data' => json_decode($result['data_json'], true),
                'sql' => $result['sql_query'],
                'html_table' => $result['html_table'],
                'cached_at' => $result['created_at'],
                'hit_count' => $result['hit_count'] + 1,
                'chat_id' => null, // Cache'ten gelen sorgularda chat_id yok
                'response_time' => 0.05
            ];

            // DB'den bulduysak Redis'e de atalım (Bir sonraki sefer hızlı olsun)
            if ($this->use_redis) {
                $redis_key = "ai_cache:{$firma_id}:{$hash}";
                $this->redis->setex($redis_key, $this->cache_duration, json_encode($response));
            }
            
            return $response;
        }
        
        return null; // Cache miss
    }
    
    /**
     * Cevabı cache'e kaydet (sadece başarılı sonuçları)
     */
    public function set($question, $firma_id, $answer, $data, $sql, $html_table) {
        // Başarısız sonuçları cache'leme
        if (!$this->shouldCache($answer, $data, $sql)) {
            error_log("⚠️ CACHE SKIP: Başarısız sonuç cache'lenmedi - {$question}");
            return false;
        }
        
        $hash = $this->generateQuestionHash($question, $firma_id);
        
        // 1. REDIS KAYIT
        if ($this->use_redis) {
            $redis_key = "ai_cache:{$firma_id}:{$hash}";
            $cache_data = [
                'success' => true,
                'from_cache' => true,
                'answer' => $answer,
                'data' => $data,
                'sql' => $sql,
                'html_table' => $html_table,
                'cached_at' => date('Y-m-d H:i:s'),
                'hit_count' => 1,
                'chat_id' => null,
                'response_time' => 0.001
            ];
            $this->redis->setex($redis_key, $this->cache_duration, json_encode($cache_data));
        }

        // 2. MYSQL KAYIT (Persistent)
        $sql_insert = "INSERT INTO ai_cache 
                      (firma_id, question_hash, original_question, answer, 
                       data_json, sql_query, html_table, created_at, hit_count, is_valid)
                      VALUES (:firma_id, :hash, :question, :answer, 
                              :data_json, :sql_query, :html_table, NOW(), 0, 1)
                      ON DUPLICATE KEY UPDATE
                          answer = VALUES(answer),
                          data_json = VALUES(data_json),
                          sql_query = VALUES(sql_query),
                          html_table = VALUES(html_table),
                          created_at = NOW(),
                          is_valid = 1";
        
        $stmt = $this->conn->prepare($sql_insert);
        $success = $stmt->execute([
            'firma_id' => $firma_id,
            'hash' => $hash,
            'question' => $question,
            'answer' => $answer,
            'data_json' => json_encode($data),
            'sql_query' => $sql,
            'html_table' => $html_table
        ]);
        
        if ($success) {
            error_log("💾 DB CACHE SAVED: {$question}");
        }
        
        return $success;
    }
    
    /**
     * Hit sayısını artır
     */
    private function incrementHitCount($cache_id) {
        $sql = "UPDATE ai_cache SET hit_count = hit_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $cache_id]);
    }
    
    /**
     * Sonucun cache'lenmeye uygun olup olmadığını kontrol et
     * Sadece başarılı sonuçları cache'le
     */
    private function shouldCache($answer, $data, $sql) {
        // Boş cevap
        if (empty($answer) || trim($answer) === '') {
            return false;
        }
        
        // Kritik hata mesajları (case-insensitive)
        // NOT: "bulunamadı" kelimesini çıkardık - boş sonuç da başarılı sayılır
        $error_keywords = [
            'hata',
            'error',
            'failed',
            'başarısız',
            'geçersiz',
            'invalid',
            'does not exist',
            'couldn\'t',
            'unable to',
            'timeout',
            'timed out',
            'connection',
            'syntax error',
            'sql error',
            'query error'
        ];
        
        $answer_lower = mb_strtolower($answer, 'UTF-8');
        foreach ($error_keywords as $keyword) {
            if (strpos($answer_lower, $keyword) !== false) {
                return false;
            }
        }
        
        // SQL hatası
        if (!empty($sql) && (
            stripos($sql, 'ERROR') !== false ||
            stripos($sql, 'FAILED') !== false ||
            empty(trim($sql))
        )) {
            return false;
        }
        
        // Boş data (sorgu başarılı ama sonuç yok - bu cache'lenebilir)
        // Ancak data null veya false ise hata olabilir
        if ($data === null || $data === false) {
            return false;
        }
        
        // "İsim yanlış yazılmış olabilir" gibi belirsiz sonuçlar
        if (stripos($answer, 'yanlış yazılmış') !== false ||
            stripos($answer, 'benzer isimler') !== false ||
            stripos($answer, 'did you mean') !== false ||
            stripos($answer, 'tablo bulunamadı') !== false) {
            return false;
        }
        
        // Her şey OK - cache'le
        return true;
    }
    
    /**
     * Cache'i geçersiz kıl (veri değiştiğinde)
     */
    public function invalidate($keywords, $firma_id) {
        // Belirli kelimeleri içeren cache kayıtlarını geçersiz kıl
        $keyword_conditions = [];
        $params = ['firma_id' => $firma_id];
        
        foreach ($keywords as $i => $keyword) {
            $keyword_conditions[] = "original_question LIKE :keyword$i";
            $params["keyword$i"] = "%$keyword%";
        }
        
        $where = implode(' OR ', $keyword_conditions);
        
        // Önce Redis'ten silmek için hash'leri bul
        if ($this->use_redis) {
            $sql_select = "SELECT question_hash FROM ai_cache WHERE firma_id = :firma_id AND ($where)";
            $stmt = $this->conn->prepare($sql_select);
            $stmt->execute($params);
            $hashes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($hashes as $hash) {
                $this->redis->del("ai_cache:{$firma_id}:{$hash}");
            }
        }
        
        $sql = "UPDATE ai_cache 
                SET is_valid = 0, invalidated_at = NOW()
                WHERE firma_id = :firma_id 
                AND ($where)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $affected = $stmt->rowCount();
        error_log("🗑️ Cache invalidated: $affected kayıt geçersiz kılındı");
        
        return $affected;
    }
    
    /**
     * Cache istatistikleri
     */
    public function getStats($firma_id) {
        $sql = "SELECT 
                    COUNT(*) as total_cached,
                    SUM(hit_count) as total_hits,
                    SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_cache,
                    AVG(hit_count) as avg_hit_per_query
                FROM ai_cache
                WHERE firma_id = :firma_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['firma_id' => $firma_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Eski cache kayıtlarını temizle
     */
    public function cleanup($days = 7) {
        $sql = "DELETE FROM ai_cache 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                OR (is_valid = 0 AND invalidated_at < DATE_SUB(NOW(), INTERVAL 1 DAY))";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['days' => $days]);
        
        $deleted = $stmt->rowCount();
        error_log("🧹 Cache cleanup: $deleted eski kayıt silindi");
        
        return $deleted;
    }
}

/**
 * KULLANIM:
 * 
 * $cache = new AICache($conn);
 * 
 * // 1. Önce cache'den kontrol et
 * $cached_result = $cache->get($user_question, $firma_id);
 * 
 * if ($cached_result) {
 *     // ✅ Cache'den cevap ver (OpenAI'ye gitme!)
 *     echo "💰 Maliyet tasarrufu: $0.002\n";
 *     return $cached_result;
 * }
 * 
 * // 2. Cache'de yok - OpenAI'ye sor
 * $ai_result = $aiEngine->chat($user_question);
 * 
 * // 3. Cevabı cache'e kaydet
 * $cache->set(
 *     $user_question,
 *     $firma_id,
 *     $ai_result['answer'],
 *     $ai_result['data'],
 *     $ai_result['sql'],
 *     $ai_result['html_table']
 * );
 * 
 * return $ai_result;
 * 
 * 
 * // VERİ DEĞİŞTİĞİNDE CACHE'İ GEÇERSİZ KIL:
 * // Örnek: Yeni sipariş eklendiğinde
 * $cache->invalidate(['sipariş', 'siparis', 'order'], $firma_id);
 * 
 * // Örnek: Müşteri bilgileri değiştiğinde
 * $cache->invalidate(['müşteri', 'musteri', 'firma_unvani'], $firma_id);
 * 
 * 
 * // İSTATİSTİKLER:
 * $stats = $cache->getStats($firma_id);
 * echo "Toplam cache: {$stats['total_cached']}\n";
 * echo "Toplam hit: {$stats['total_hits']}\n";
 * echo "Hit rate: " . round(($stats['total_hits'] / $stats['total_cached']) * 100, 2) . "%\n";
 * 
 * // MALİYET TASARRUFU HESABI:
 * // Ortalama OpenAI isteği: $0.002
 * // Cache hit sayısı: 1000
 * // Tasarruf: 1000 × $0.002 = $2.00
 */

// Veritabanı tablosu:
/*
CREATE TABLE ai_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_id INT NOT NULL,
    question_hash CHAR(32) NOT NULL COMMENT 'MD5 hash of normalized question',
    original_question TEXT NOT NULL,
    answer TEXT,
    data_json LONGTEXT COMMENT 'Query result as JSON',
    sql_query TEXT,
    html_table LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    invalidated_at DATETIME NULL,
    hit_count INT DEFAULT 0,
    is_valid TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_cache (firma_id, question_hash),
    INDEX idx_firma (firma_id),
    INDEX idx_valid (is_valid, created_at),
    INDEX idx_hits (hit_count DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/
