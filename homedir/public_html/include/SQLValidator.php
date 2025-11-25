<?php
/**
 * SQL Validator & Auto-Fix
 * SQL hatalarını yakayla ve otomatik düzelt
 */

class SQLValidator {
    private $conn;
    private $openai;
    private $schema;
    
    public function __construct($conn, $openai, $schema) {
        $this->conn = $conn;
        $this->openai = $openai;
        $this->schema = $schema;
    }
    
    /**
     * SQL sorgusunu doğrula ve hataları düzelt
     */
    public function validateAndFix($sql, $original_question, $max_attempts = 3) {
        $attempt = 1;
        $current_sql = $sql;
        $errors = [];
        
        while ($attempt <= $max_attempts) {
            // 1. Syntax kontrolü
            $syntax_error = $this->checkSyntax($current_sql);
            
            if (!$syntax_error) {
                // 2. Dry-run test (EXPLAIN kullan)
                $explain_error = $this->testWithExplain($current_sql);
                
                if (!$explain_error) {
                    // ✅ Başarılı!
                    return [
                        'success' => true,
                        'sql' => $current_sql,
                        'attempts' => $attempt,
                        'fixed_errors' => $errors
                    ];
                }
                
                $errors[] = "EXPLAIN hatası: $explain_error";
            } else {
                $errors[] = "Syntax hatası: $syntax_error";
            }
            
            // 3. AI ile düzelt
            error_log("🔧 SQL düzeltme denemesi $attempt/$max_attempts");
            $current_sql = $this->fixWithAI(
                $current_sql,
                $original_question,
                $syntax_error ?? $explain_error,
                $errors
            );
            
            $attempt++;
        }
        
        // ❌ Düzeltemedik
        return [
            'success' => false,
            'error' => 'SQL ' . $max_attempts . ' denemede düzeltilemedi',
            'attempts' => $attempt - 1,
            'all_errors' => $errors,
            'last_sql' => $current_sql
        ];
    }
    
    /**
     * SQL syntax kontrolü (basit parse)
     */
    private function checkSyntax($sql) {
        // Yaygın hatalar
        $common_errors = [
            '/SELECT\s+FROM/' => 'SELECT ile FROM arasında kolon yok',
            '/FROM\s+WHERE/' => 'FROM ile WHERE arasında tablo yok',
            '/JOIN\s+ON\s+WHERE/' => 'JOIN sonrası ON koşulu eksik',
            '/GROUP BY\s+ORDER/' => 'GROUP BY sonrası kolon yok'
        ];
        
        foreach ($common_errors as $pattern => $error_msg) {
            if (preg_match($pattern, $sql)) {
                return $error_msg;
            }
        }
        
        // MySQL syntax kontrolü
        try {
            $this->conn->query("EXPLAIN $sql");
            return null; // Hata yok
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
    
    /**
     * EXPLAIN ile test et (gerçek veriyi çalıştırmadan)
     */
    private function testWithExplain($sql) {
        try {
            $stmt = $this->conn->query("EXPLAIN $sql");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // SADECE KRİTİK HATALARI kontrol et
            // Performans uyarılarını (filesort, temporary) görmezden gel
            foreach ($result as $row) {
                // Kritik: Çok büyük full table scan (>50000 satır)
                if ($row['type'] === 'ALL' && $row['rows'] > 50000) {
                    error_log("⚠️ Performans uyarısı: Full table scan ({$row['rows']} satır)");
                    // Uyarı ver ama hataya dönüştürme
                }
                
                // Filesort ve temporary normal durumlar - hata değil!
                // ORDER BY ve GROUP BY kullanıldığında beklenir
            }
            
            return null; // EXPLAIN başarılı = SQL çalışabilir
        } catch (PDOException $e) {
            // Gerçek SQL hatası
            return $e->getMessage();
        }
    }
    
    /**
     * AI ile SQL'i düzelt
     */
    private function fixWithAI($broken_sql, $original_question, $error_message, $previous_errors) {
        $fix_prompt = "HATA DÜZELTME GÖREVİ

Kullanıcı sorusu: \"$original_question\"

Hatalı SQL sorgusu:
```sql
$broken_sql
```

HATA MESAJI:
$error_message

DAHA ÖNCE YAPILAN HATALAR:
" . implode("\n", $previous_errors) . "

VERİTABANI ŞEMASI:
" . json_encode($this->schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

GÖREV:
1. Hatayı analiz et
2. Şemaya uygun düzelt
3. SADECE düzeltilmiş SQL'i döndür (JSON olmadan, açıklama olmadan)
4. SELECT ile başla, ; ile bitir

DİKKAT: Aynı hatayı tekrarlama!";

        $response = $this->openai->chat([
            ['role' => 'system', 'content' => 'Sen bir SQL debugging uzmanısın. Sadece düzeltilmiş SQL kodunu döndür.'],
            ['role' => 'user', 'content' => $fix_prompt]
        ], 0.2, 1000);
        
        // Yanıtı temizle
        $fixed_sql = trim($response);
        $fixed_sql = preg_replace('/^```sql\s*/i', '', $fixed_sql);
        $fixed_sql = preg_replace('/\s*```$/i', '', $fixed_sql);
        
        error_log("🔧 Düzeltilmiş SQL: " . substr($fixed_sql, 0, 100) . "...");
        
        return $fixed_sql;
    }
    
    /**
     * SQL performans önerileri
     */
    public function getPerformanceAdvice($sql) {
        $advice = [];
        
        // 1. INDEX kullanımı kontrol
        if (preg_match('/WHERE.*=.*(?!INDEX)/', $sql)) {
            $advice[] = "💡 WHERE koşullarına INDEX ekleyin";
        }
        
        // 2. SELECT * kullanımı
        if (preg_match('/SELECT \*/', $sql)) {
            $advice[] = "⚠️ SELECT * yerine gerekli kolonları seçin";
        }
        
        // 3. Subquery çokluğu
        if (substr_count($sql, 'SELECT') > 5) {
            $advice[] = "🔍 Çok fazla subquery - JOIN ile optimize edin";
        }
        
        // 4. DISTINCT kullanımı
        if (stripos($sql, 'DISTINCT') !== false && stripos($sql, 'GROUP BY') === false) {
            $advice[] = "📊 DISTINCT yerine GROUP BY kullanmayı deneyin";
        }
        
        return $advice;
    }
}

/**
 * KULLANIM:
 * 
 * $validator = new SQLValidator($conn, $openai, $schema);
 * 
 * // SQL doğrula ve düzelt
 * $result = $validator->validateAndFix($generated_sql, $user_question);
 * 
 * if ($result['success']) {
 *     echo "✅ SQL doğrulandı!\n";
 *     echo "Deneme sayısı: {$result['attempts']}\n";
 *     
 *     if (!empty($result['fixed_errors'])) {
 *         echo "Düzeltilen hatalar:\n";
 *         foreach ($result['fixed_errors'] as $error) {
 *             echo "  - $error\n";
 *         }
 *     }
 *     
 *     // Performans tavsiyeleri al
 *     $advice = $validator->getPerformanceAdvice($result['sql']);
 *     if (!empty($advice)) {
 *         echo "\nPerformans önerileri:\n";
 *         foreach ($advice as $tip) {
 *             echo "  $tip\n";
 *         }
 *     }
 *     
 *     // Güvenle çalıştır
 *     $data = executeSQL($result['sql']);
 * } else {
 *     echo "❌ SQL düzeltilemedi: {$result['error']}\n";
 *     echo "Son deneme:\n{$result['last_sql']}\n";
 * }
 */
