<?php
/**
 * Vektörize Edilmiş Knowledge Base (RAG)
 * Benzer soruları semantik olarak bulur
 */

class VectorKnowledgeBase {
    private $conn;
    private $openai;
    
    public function __construct($conn, $openai) {
        $this->conn = $conn;
        $this->openai = $openai;
    }
    
    /**
     * Sorunun embedding'ini al ve en benzer sorguları bul
     */
    public function findSimilarQuestions($question, $firma_id, $limit = 5) {
        // 1. Sorunun embedding'ini OpenAI'den al
        $embedding = $this->getEmbedding($question);
        
        if (!$embedding) {
            error_log("⚠️ Embedding alınamadı: " . $question);
            return [];
        }
        
        // 2. Veritabanında cosine similarity ile ara
        // PostgreSQL + pgvector kullanıyorsanız:
        // $sql = "SELECT *, (embedding <=> :query_embedding) as distance 
        //         FROM ai_knowledge_base_vectors 
        //         ORDER BY distance LIMIT :limit";
        
        // MySQL için alternatif: JSON olarak sakla ve PHP'de hesapla
        $sql = "SELECT id, soru, sql_query, embedding_json 
                FROM ai_knowledge_base_vectors 
                WHERE firma_id = :firma_id 
                ORDER BY kullanim_sayisi DESC 
                LIMIT 50";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['firma_id' => $firma_id]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. PHP'de cosine similarity hesapla
        $results = [];
        foreach ($candidates as $candidate) {
            if ($candidate['embedding_json']) {
                $candidate_embedding = json_decode($candidate['embedding_json'], true);
                $similarity = $this->cosineSimilarity($embedding, $candidate_embedding);
                
                if ($similarity > 0.75) { // %75'ten fazla benzer
                    $results[] = [
                        'soru' => $candidate['soru'],
                        'sql_query' => $candidate['sql_query'],
                        'similarity' => $similarity
                    ];
                }
            }
        }
        
        // Benzerliğe göre sırala
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * OpenAI'den text embedding al
     */
    private function getEmbedding($text) {
        try {
            // OpenAI class'ındaki getApiKey metodunu kullan
            $api_key = $this->openai->getApiKey();
            
            if (empty($api_key)) {
                error_log("❌ OpenAI API key bulunamadı");
                return null;
            }
            
            $ch = curl_init('https://api.openai.com/v1/embeddings');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'text-embedding-3-small',
                'input' => $text
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code !== 200) {
                error_log("❌ OpenAI embedding hatası (HTTP {$http_code}): " . substr($response, 0, 200));
                return null;
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['data'][0]['embedding'])) {
                error_log("❌ Embedding response'da data yok: " . substr($response, 0, 200));
                return null;
            }
            
            return $result['data'][0]['embedding'];
            
        } catch (Exception $e) {
            error_log("❌ Embedding exception: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * İki vektör arasında cosine similarity hesapla
     */
    private function cosineSimilarity($vec1, $vec2) {
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $magnitude1 += pow($vec1[$i], 2);
            $magnitude2 += pow($vec2[$i], 2);
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Yeni soru-cevap çiftini embedding ile kaydet
     */
    public function saveWithEmbedding($soru, $sql_query, $firma_id) {
        $embedding = $this->getEmbedding($soru);
        
        if (!$embedding) {
            return false;
        }
        
        $sql = "INSERT INTO ai_knowledge_base_vectors 
                (firma_id, soru, sql_query, embedding_json, tarih) 
                VALUES (:firma_id, :soru, :sql_query, :embedding_json, NOW())
                ON DUPLICATE KEY UPDATE 
                kullanim_sayisi = kullanim_sayisi + 1,
                son_kullanim = NOW()";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'firma_id' => $firma_id,
            'soru' => $soru,
            'sql_query' => $sql_query,
            'embedding_json' => json_encode($embedding)
        ]);
    }
}

/**
 * KULLANIM:
 * 
 * $vectorKB = new VectorKnowledgeBase($conn, $openai);
 * 
 * // Benzer sorguları bul
 * $similar = $vectorKB->findSimilarQuestions("hotmelt makinasındaki işler");
 * // Sonuç: [
 * //   ['soru' => 'holtmelt işleri', 'sql_query' => '...', 'similarity' => 0.92],
 * //   ['soru' => 'hot melt makina', 'sql_query' => '...', 'similarity' => 0.88]
 * // ]
 * 
 * // Prompt'a ekle:
 * $system_prompt .= "\n\nBENZER BAŞARILI SORGULAR:\n";
 * foreach ($similar as $s) {
 *     $system_prompt .= "Soru: {$s['soru']} (Benzerlik: " . round($s['similarity']*100) . "%)\n";
 *     $system_prompt .= "SQL: {$s['sql_query']}\n\n";
 * }
 */

// Veritabanı tablosu oluşturma SQL'i:
/*
CREATE TABLE ai_knowledge_base_vectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_id INT NOT NULL,
    soru TEXT NOT NULL,
    sql_query TEXT NOT NULL,
    embedding_json LONGTEXT NOT NULL COMMENT 'OpenAI embedding vektörü (1536 boyut)',
    kullanim_sayisi INT DEFAULT 1,
    son_kullanim DATETIME,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_question (firma_id, soru(255)),
    INDEX idx_firma (firma_id),
    INDEX idx_kullanim (kullanim_sayisi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/
