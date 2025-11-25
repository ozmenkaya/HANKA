<?php
/**
 * HANKA AI - Self Learning System
 * Başarılı WhatsApp sorgularını otomatik training data'ya ekler
 */

require_once __DIR__ . '/include/db.php';

/**
 * Başarılı sorguları training data'ya ekle
 */
function collectTrainingData($conn, $firma_id, $min_quality_score = 80) {
    try {
        // WhatsApp mesajlarından başarılı sorguları bul
        $stmt = $conn->prepare("
            SELECT 
                wm.body as question,
                wm.response,
                wm.created_at
            FROM whatsapp_messages wm
            WHERE wm.firma_id = :firma_id
              AND wm.response NOT LIKE '%hata%'
              AND wm.response NOT LIKE '%bulunamadı%'
              AND wm.response NOT LIKE '%anlayamadım%'
              AND wm.response NOT LIKE '%yerine%deneyebilirsiniz%'
              AND LENGTH(wm.response) > 20
              AND wm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY wm.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([':firma_id' => $firma_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $training_data = [];
        
        foreach ($messages as $msg) {
            // AI cache'den SQL'i bul
            $stmt = $conn->prepare("
                SELECT sql_query, hit_count
                FROM ai_cache
                WHERE original_question = :question
                  AND firma_id = :firma_id
                  AND sql_query IS NOT NULL
                  AND sql_query != ''
                LIMIT 1
            ");
            $stmt->execute([
                ':question' => $msg['question'],
                ':firma_id' => $firma_id
            ]);
            $cache = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cache && $cache['sql_query']) {
                // Training formatına çevir
                $training_entry = [
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Sen bir SQL uzmanısın. Türkçe sorulara göre MySQL sorguları oluşturuyorsun.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $msg['question'] . " (firma_id: {$firma_id})"
                        ],
                        [
                            'role' => 'assistant',
                            'content' => json_encode([
                                'sql' => $cache['sql_query'],
                                'explanation' => 'Başarılı sorgu'
                            ], JSON_UNESCAPED_UNICODE)
                        ]
                    ],
                    'metadata' => [
                        'firma_id' => $firma_id,
                        'timestamp' => $msg['created_at'],
                        'source' => 'whatsapp',
                        'auto_generated' => true
                    ]
                ];
                
                $training_data[] = $training_entry;
            }
        }
        
        return $training_data;
        
    } catch (Exception $e) {
        error_log("collectTrainingData Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Training data'yı dosyaya kaydet
 */
function saveTrainingData($training_data, $filepath) {
    try {
        // Mevcut dosyayı oku
        $existing_data = [];
        if (file_exists($filepath)) {
            $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $existing_data[] = json_decode($line, true);
            }
        }
        
        // Duplikasyonu önle - aynı soruyu tekrar ekleme
        $existing_questions = [];
        foreach ($existing_data as $entry) {
            if (isset($entry['messages'][1]['content'])) {
                $existing_questions[] = $entry['messages'][1]['content'];
            }
        }
        
        $new_count = 0;
        $fp = fopen($filepath, 'a');
        
        foreach ($training_data as $entry) {
            $question = $entry['messages'][1]['content'];
            
            // Duplike kontrolü
            if (!in_array($question, $existing_questions)) {
                fwrite($fp, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n");
                $existing_questions[] = $question;
                $new_count++;
            }
        }
        
        fclose($fp);
        
        return [
            'success' => true,
            'new_entries' => $new_count,
            'total_entries' => count($existing_data) + $new_count
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Cache'den kaliteli sorguları training'e ekle
 */
function collectFromCache($conn, $firma_id, $min_hit_count = 2) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                original_question,
                sql_query,
                hit_count,
                created_at
            FROM ai_cache
            WHERE firma_id = :firma_id
              AND hit_count >= :min_hit_count
              AND sql_query IS NOT NULL
              AND LENGTH(sql_query) > 50
            ORDER BY hit_count DESC, created_at DESC
            LIMIT 30
        ");
        $stmt->execute([
            ':firma_id' => $firma_id,
            ':min_hit_count' => $min_hit_count
        ]);
        $cache_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $training_data = [];
        
        foreach ($cache_entries as $entry) {
            $training_entry = [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Sen bir SQL uzmanısın. Türkçe sorulara göre MySQL sorguları oluşturuyorsun.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $entry['original_question'] . " (firma_id: {$firma_id})"
                    ],
                    [
                        'role' => 'assistant',
                        'content' => json_encode([
                            'sql' => $entry['sql_query'],
                            'explanation' => 'Sık kullanılan sorgu (hit_count: ' . $entry['hit_count'] . ')'
                        ], JSON_UNESCAPED_UNICODE)
                    ]
                ],
                'metadata' => [
                    'firma_id' => $firma_id,
                    'timestamp' => $entry['created_at'],
                    'source' => 'ai_cache',
                    'hit_count' => $entry['hit_count'],
                    'auto_generated' => true
                ]
            ];
            
            $training_data[] = $training_entry;
        }
        
        return $training_data;
        
    } catch (Exception $e) {
        error_log("collectFromCache Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Pattern matching sorgularını training'e ekle
 */
function collectPatternQueries($firma_id) {
    $patterns = [
        // Müşteri sayısı
        [
            'question' => 'Kaç müşterim var?',
            'sql' => "SELECT COUNT(*) as musteri_sayisi FROM musteri WHERE firma_id={$firma_id}",
            'explanation' => 'Toplam müşteri sayısı'
        ],
        // Fason tedarikçi
        [
            'question' => 'Keçeli tedarikçisinde kaç iş var',
            'sql' => "SELECT COUNT(*) as is_sayisi FROM planlama p JOIN siparisler s ON p.siparis_id = s.id WHERE p.fason_tedarikciler LIKE CONCAT('%',(SELECT id FROM tedarikciler WHERE tedarikci_unvani LIKE '%KEÇELİ%' LIMIT 1),'%') AND p.firma_id={$firma_id}",
            'explanation' => 'Tedarikçi bazlı fason iş sayısı'
        ],
        // Fason müşteri
        [
            'question' => 'Keçeli müşterisinde kaç fason iş var',
            'sql' => "SELECT COUNT(*) as is_sayisi FROM planlama p JOIN siparisler s ON p.siparis_id = s.id JOIN musteri m ON s.musteri_id = m.id WHERE m.firma_unvani LIKE '%KEÇELİ%' AND p.fason_tedarikciler IS NOT NULL AND p.fason_tedarikciler != '' AND p.firma_id={$firma_id}",
            'explanation' => 'Müşteri bazlı fason iş sayısı'
        ],
        // Sipariş özeti
        [
            'question' => 'Bugünkü siparişler',
            'sql' => "SELECT COUNT(*) as siparis_sayisi, SUM(adet) as toplam_adet FROM siparisler WHERE firma_id={$firma_id} AND DATE(tarih) = CURDATE()",
            'explanation' => 'Günlük sipariş özeti'
        ],
        // Planlama durumu
        [
            'question' => 'Bekleyen planlar',
            'sql' => "SELECT COUNT(*) as bekleyen_plan FROM planlama WHERE firma_id={$firma_id} AND onay_durum = 'hayır'",
            'explanation' => 'Onay bekleyen planlar'
        ]
    ];
    
    $training_data = [];
    
    foreach ($patterns as $pattern) {
        $training_entry = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen bir SQL uzmanısın. Türkçe sorulara göre MySQL sorguları oluşturuyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $pattern['question'] . " (firma_id: {$firma_id})"
                ],
                [
                    'role' => 'assistant',
                    'content' => json_encode([
                        'sql' => $pattern['sql'],
                        'explanation' => $pattern['explanation']
                    ], JSON_UNESCAPED_UNICODE)
                ]
            ],
            'metadata' => [
                'firma_id' => $firma_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'pattern_matching',
                'auto_generated' => true
            ]
        ];
        
        $training_data[] = $training_entry;
    }
    
    return $training_data;
}

/**
 * Ana self-learning fonksiyonu
 */
function selfLearn($conn, $firma_id = 16) {
    echo "🤖 HANKA AI - Self Learning System\n";
    echo "=" . str_repeat("=", 59) . "\n\n";
    
    $training_file = __DIR__ . '/ai_training/training_corrections.jsonl';
    
    // Dizin yoksa oluştur
    $dir = dirname($training_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $all_training_data = [];
    
    // 1. WhatsApp mesajlarından topla
    echo "📱 WhatsApp mesajlarından öğreniliyor...\n";
    $whatsapp_data = collectTrainingData($conn, $firma_id);
    echo "   ✅ " . count($whatsapp_data) . " yeni örnek bulundu\n\n";
    $all_training_data = array_merge($all_training_data, $whatsapp_data);
    
    // 2. AI Cache'den topla
    echo "💾 AI Cache'den popüler sorgular toplanıyor...\n";
    $cache_data = collectFromCache($conn, $firma_id);
    echo "   ✅ " . count($cache_data) . " cache örneği bulundu\n\n";
    $all_training_data = array_merge($all_training_data, $cache_data);
    
    // 3. Pattern matching örnekleri ekle
    echo "🎯 Pattern matching örnekleri ekleniyor...\n";
    $pattern_data = collectPatternQueries($firma_id);
    echo "   ✅ " . count($pattern_data) . " pattern örneği eklendi\n\n";
    $all_training_data = array_merge($all_training_data, $pattern_data);
    
    // 4. Training dosyasına kaydet
    echo "💾 Training data kaydediliyor...\n";
    $result = saveTrainingData($all_training_data, $training_file);
    
    if ($result['success']) {
        echo "   ✅ {$result['new_entries']} yeni kayıt eklendi\n";
        echo "   📊 Toplam kayıt sayısı: {$result['total_entries']}\n\n";
        
        // 5. Kalite raporu
        if ($result['total_entries'] >= 50) {
            echo "🎉 Tebrikler! Training data yeterli seviyeye ulaştı!\n";
            echo "   📈 Fine-tuning için hazır: {$result['total_entries']} kayıt\n";
            echo "   🚀 Sonraki adım: Fine-tuning başlat\n\n";
        } else {
            $remaining = 50 - $result['total_entries'];
            echo "⏳ İlerleme: {$result['total_entries']}/50 kayıt\n";
            echo "   📊 Kalan: {$remaining} kayıt\n";
            echo "   💡 Öneri: Birkaç gün daha kullanım verisi toplayın\n\n";
        }
    } else {
        echo "   ❌ Hata: {$result['error']}\n";
    }
    
    return $result;
}

// CLI çalıştırma
if (php_sapi_name() === 'cli') {
    if ($argc > 1 && $argv[1] === 'run') {
        $firma_id = isset($argv[2]) ? intval($argv[2]) : 16;
        selfLearn($conn, $firma_id);
    } else {
        echo "Kullanım: php ai_self_learning.php run [firma_id]\n";
        echo "Örnek: php ai_self_learning.php run 16\n";
    }
}

?>
