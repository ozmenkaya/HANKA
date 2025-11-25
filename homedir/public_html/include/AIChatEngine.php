<?php
/**
 * HANKA AI Chat Engine
 * Firma bazlı self-learning AI asistan
 */

require_once __DIR__ . "/OpenAI.php";
require_once __DIR__ . "/AICache.php";
require_once __DIR__ . "/SQLValidator.php";
require_once __DIR__ . "/VectorKnowledgeBase.php";
require_once __DIR__ . "/AISemanticLayer.php";

class AIChatEngine {
    private $conn;
    private $ai;
    private $cache;
    private $validator;
    private $vectorKB;
    private $semanticLayer;
    private $firma_id;
    private $kullanici_id;
    
    public function __construct($conn, $firma_id, $kullanici_id) {
        $this->conn = $conn;
        $this->firma_id = $firma_id;
        $this->kullanici_id = $kullanici_id;
        $this->ai = new OpenAI();
        $this->cache = new AICache($conn);
        $this->vectorKB = new VectorKnowledgeBase($conn, $this->ai);
        $this->semanticLayer = new AISemanticLayer($conn);
        // SQLValidator lazy initialization (schema gerektiğinde yüklenecek)
        $this->validator = null;
    }
    
    /**
     * Ana chat fonksiyonu
     */
    public function chat($user_question) {
        $start_time = microtime(true);
        
        try {
            // 🚀 CACHE KONTROLÜ - Önce cache'den bak
            $cached = $this->cache->get($user_question, $this->firma_id);
            
            if ($cached) {
                error_log("✅ CACHE HIT! Soru: " . substr($user_question, 0, 50) . "... (Hit count: " . $cached['hit_count'] . ")");
                $cached['response_time'] = round(microtime(true) - $start_time, 3);
                return $cached;
            }
            
            error_log("❌ Cache miss - OpenAI'ye gidiyoruz: " . substr($user_question, 0, 50) . "...");
            
            // 1. Firma context'ini hazırla
            $context = $this->buildFirmaContext();
            
            // 2. Veritabanı şemasını al
            $schema = $this->getDatabaseSchema();
            
            // 3. Benzer geçmiş soruları bul
            $similar_questions = $this->findSimilarQuestions($user_question);
            
            $current_question = $user_question;
            $step = 0;
            $max_steps = 2;
            
            do {
                $step++;
                error_log("🔄 Chat Step: $step / $max_steps");
                
                // 4. SQL sorgusu oluştur
                $sql_result = $this->generateSQL($current_question, $schema, $context, $similar_questions);
                
                if (!$sql_result["success"]) {
                    throw new Exception($sql_result["error"]);
                }

                // 🛠️ TOOL CALL HANDLING (SEMANTIC LAYER)
                if (isset($sql_result["tool"])) {
                    error_log("🛠️ Tool Call Detected: " . $sql_result["tool"]);
                    
                    $tool_name = $sql_result["tool"];
                    $params = $sql_result["params"];
                    $tool_result = null;
                    
                    if ($tool_name === 'calculateOEE') {
                        $tool_result = $this->semanticLayer->calculateOEE(
                            $this->firma_id, 
                            $params['makina_id'], 
                            $params['date'] ?? null
                        );
                    } elseif ($tool_name === 'getMachineStatus') {
                        $tool_result = $this->semanticLayer->getMachineStatus(
                            $this->firma_id, 
                            $params['makina_id']
                        );
                    } else {
                        throw new Exception("Bilinmeyen tool: $tool_name");
                    }
                    
                    // Sonucu formatla
                    $answer = "🛠️ **İşlem Sonucu:**\n\n";
                    if (is_array($tool_result)) {
                        foreach ($tool_result as $key => $val) {
                            $answer .= "- **" . ucfirst(str_replace('_', ' ', $key)) . "**: $val\n";
                        }
                    } else {
                        $answer .= $tool_result;
                    }
                    
                    return [
                        "success" => true,
                        "answer" => $answer,
                        "data" => [$tool_result],
                        "html_table" => "",
                        "sql" => "TOOL: $tool_name",
                        "chat_id" => 0,
                        "sql_explanation" => "AI tarafından özel fonksiyon çalıştırıldı."
                    ];
                }
                
                // 🔧 4.5. SQL VALIDATOR - Hataları yakala ve düzelt
                $validated = $this->validateSQL($sql_result["sql"], $user_question, $schema);
                
                if (!$validated["success"]) {
                    error_log("❌ SQL validation başarısız: " . $validated["error"]);
                    throw new Exception("SQL doğrulama hatası: " . $validated["error"]);
                }
                
                // Düzeltilmiş SQL'i kullan
                $final_sql = $validated["sql"];
                
                if ($validated["attempts"] > 1) {
                    error_log("🔧 SQL {$validated['attempts']} denemede düzeltildi!");
                }
                
                // 5. SQL'i çalıştır
                $data = $this->executeSQL($final_sql);
                
                // 🔄 AGENTIC LOOP CHECK
                // Eğer SQL sonucu bir ID döndürdüyse ve soru OEE/Durum ile ilgiliyse, tekrar AI'ya sor.
                $is_tool_question = stripos($user_question, 'oee') !== false || 
                                    stripos($user_question, 'verim') !== false || 
                                    stripos($user_question, 'durum') !== false || 
                                    stripos($user_question, 'ne yapıyor') !== false;
                
                if ($step < $max_steps && $is_tool_question && count($data) == 1 && isset($data[0]['id'])) {
                    error_log("🔄 Agentic Loop: ID bulundu ({$data[0]['id']}), Tool çağrısı için tekrar deneniyor...");
                    $current_question = $user_question . " (Bulunan Makina ID: " . $data[0]['id'] . ")";
                    continue; // Loop'un başına dön
                }
                
                // Döngüden çık ve yanıt oluştur
                break;
                
            } while ($step < $max_steps);
            
            // 6. Sonuçları analiz et ve yanıt oluştur
            $answer = $this->generateAnswer($user_question, $data, $sql_result["explanation"]);
            
            // 6b. HTML tablo oluştur (linklerle)
            $html_table = $this->generateHTMLTable($data, $sql_result["sql"]);
            
            // 💾 10. CACHE'E KAYDET (bir sonraki aynı soru için)
            $this->cache->set(
                $user_question,
                $this->firma_id,
                $answer,
                $data,
                $final_sql,
                $html_table
            );

            // 🚀 PERFORMANS: Yanıtı kullanıcıya gönder ve bağlantıyı kapat
            // Loglama işlemleri arkada devam etsin (Kullanıcı beklemesin)
            if (function_exists('fastcgi_finish_request')) {
                $response_data = [
                    "success" => true,
                    "answer" => $answer,
                    "data" => $data,
                    "html_table" => $html_table,
                    "sql" => $final_sql,
                    "chat_id" => 0, // ID sonradan oluşacak ama UI için 0 yeterli
                    "sql_explanation" => $sql_result["explanation"],
                    "sql_validation" => [
                        "attempts" => $validated["attempts"],
                        "fixed_errors" => $validated["fixed_errors"] ?? []
                    ],
                    "response_time" => round(microtime(true) - $start_time, 2),
                    "from_cache" => false
                ];
                echo json_encode($response_data);
                fastcgi_finish_request(); // <--- BURADA BAĞLANTI KOPAR
            }

            // --- BURADAN SONRASI BACKGROUND PROCESS ---
            
            // 7. Sohbet geçmişine kaydet
            $chat_id = $this->saveChatHistory(
                $user_question,
                $answer,
                $sql_result["sql"],
                count($data),
                microtime(true) - $start_time
            );
            
            // 8. Knowledge base'i güncelle
            $this->updateKnowledgeBase($user_question, $final_sql, $data);
            
            // 9. Fine-tuning için logla
            $this->logForFineTuning($user_question, $final_sql, "BAŞARILI");
            
            error_log("💾 Cache'e kaydedildi ve loglar işlendi: " . substr($user_question, 0, 50) . "...");
            
            // Eğer fastcgi yoksa normal return (Development ortamı için)
            if (!function_exists('fastcgi_finish_request')) {
                return [
                    "success" => true,
                    "answer" => $answer,
                    "data" => $data,
                    "html_table" => $html_table,
                    "sql" => $final_sql,
                    "chat_id" => $chat_id,
                    "sql_explanation" => $sql_result["explanation"],
                    "sql_validation" => [
                        "attempts" => $validated["attempts"],
                        "fixed_errors" => $validated["fixed_errors"] ?? []
                    ],
                    "response_time" => round(microtime(true) - $start_time, 2),
                    "from_cache" => false
                ];
            }
            
            exit; // Scripti sonlandır
            
        } catch (Exception $e) {
            error_log("=== generateSQL ERROR: " . $e->getMessage());
            
            // Hata durumunda da logla
            $this->logForFineTuning($user_question, "", "HATA: " . $e->getMessage());
            
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }
    
    /**
     * Firma context bilgilerini topla
     */
    private function buildFirmaContext() {
        $context = [];
        
        // Firma bilgisi
        $sql = "SELECT firma_adi FROM firmalar WHERE id = :firma_id";
        $sth = $this->conn->prepare($sql);
        $sth->execute(["firma_id" => $this->firma_id]);
        $firma = $sth->fetch(PDO::FETCH_ASSOC);
        $context["firma_adi"] = $firma["firma_adi"] ?? "Bilinmeyen";
        
        // Son 30 günlük özet istatistikler
        $sql = "SELECT 
                    COUNT(*) as toplam_siparis,
                    SUM(adet) as toplam_adet,
                    AVG(fiyat) as ortalama_fiyat
                FROM siparisler 
                WHERE firma_id = :firma_id 
                AND tarih >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $sth = $this->conn->prepare($sql);
        $sth->execute(["firma_id" => $this->firma_id]);
        $context["siparis_stats"] = $sth->fetch(PDO::FETCH_ASSOC);
        
        // Aktif müşteri sayısı
        $sql = "SELECT COUNT(DISTINCT m.id) as aktif_musteri
                FROM musteri m
                INNER JOIN siparisler s ON s.musteri_id = m.id
                WHERE s.firma_id = :firma_id
                AND s.tarih >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $sth = $this->conn->prepare($sql);
        $sth->execute(["firma_id" => $this->firma_id]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $context["aktif_musteri"] = $result["aktif_musteri"] ?? 0;
        
        // Personel sayısı
        $sql = "SELECT COUNT(*) as personel_sayisi FROM personeller WHERE firma_id = :firma_id";
        $sth = $this->conn->prepare($sql);
        $sth->execute(["firma_id" => $this->firma_id]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $context["personel_sayisi"] = $result["personel_sayisi"] ?? 0;
        
        // Makina sayısı
        $sql = "SELECT COUNT(*) as makina_sayisi FROM makinalar WHERE firma_id = :firma_id";
        $sth = $this->conn->prepare($sql);
        $sth->execute(["firma_id" => $this->firma_id]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $context["makina_sayisi"] = $result["makina_sayisi"] ?? 0;
        
        return $context;
    }
    
    /**
     * SQL sorgusunu doğrula ve gerekirse düzelt
     */
    private function validateSQL($sql, $original_question, $schema) {
        // Validator'ı lazy initialize et
        if ($this->validator === null) {
            $this->validator = new SQLValidator($this->conn, $this->ai, $schema);
        }
        
        error_log("🔍 SQL validation başlıyor: " . substr($sql, 0, 80) . "...");
        
        // Hataları yakala ve düzelt
        $result = $this->validator->validateAndFix($sql, $original_question, 3);
        
        if ($result['success']) {
            // Performans önerileri
            $advice = $this->validator->getPerformanceAdvice($result['sql']);
            if (!empty($advice)) {
                error_log("💡 SQL performans önerileri: " . implode(", ", $advice));
            }
            
            error_log("✅ SQL validation başarılı" . 
                     ($result['attempts'] > 1 ? " ({$result['attempts']} deneme)" : ""));
        }
        
        return $result;
    }
    
    /**
     * Veritabanı şemasını al
     */
    private function getDatabaseSchema() {
        // Dinamik schema - JSON dosyasından yükle
        $schema_file = "/var/www/html/logs/ai_compact_schema.json";
        
        if (file_exists($schema_file)) {
            $smart_schema = json_decode(file_get_contents($schema_file), true);
            if ($smart_schema && count($smart_schema) > 0) {
                return $smart_schema;
            }
        }
        
        // Fallback: En önemli tablolar
        $schema = [
            "siparisler" => "Sipariş bilgileri (1361 kayıt) - veriler JSON kolonu: [{miktar,birim_fiyat,isim}] 5 eleman. TUTAR HESABI: JSON_EXTRACT(veriler,'$[0].miktar')*JSON_EXTRACT(veriler,'$[0].birim_fiyat')+JSON_EXTRACT(veriler,'$[1].miktar')*JSON_EXTRACT(veriler,'$[1].birim_fiyat')+JSON_EXTRACT(veriler,'$[2].miktar')*JSON_EXTRACT(veriler,'$[2].birim_fiyat')+JSON_EXTRACT(veriler,'$[3].miktar')*JSON_EXTRACT(veriler,'$[3].birim_fiyat')+JSON_EXTRACT(veriler,'$[4].miktar')*JSON_EXTRACT(veriler,'$[4].birim_fiyat'). Ana tablodaki adet×fiyat YANLIŞ! | JOIN: musteri_id→musteri",
            "musteri" => "Müşteri bilgileri (152 kayıt) - Kolonlar: id, marka (KOMAGENE, MIGROS), firma_unvani (YÖRPAŞ YÖRESEL LEZZETLER). KULLANICI MARKA İLE SORAR! MUTLAKA OR ile ara: (marka LIKE '%KOMAGENE%' OR firma_unvani LIKE '%KOMAGENE%'). SELECT'te HER İKİSİNİ GÖSTER! | JOIN: sehir_id→sehirler, ilce_id→ilceler",
            "planlama" => "Planlama kayıtları (1458 kayıt) - Kolonlar: id, siparis_id, isim, fason_tedarikciler | JOIN: siparis_id→siparisler.id",
            "personeller" => "Personel bilgileri (22 kayıt) - Kolonlar: id, ad, soyad, email. PERSONEL ADI ARAMA: CONCAT(ad, ' ', soyad) veya (ad LIKE '%X%' AND soyad LIKE '%Y%') | JOIN: yetki_id→yetkiler",
            "makina_personeller" => "Personel-Makina ilişkisi - Kolonlar: id, firma_id, makina_id, personel_id. Personelin hangi makinada çalıştığını gösterir | JOIN: makina_id→makinalar, personel_id→personeller",
            "personel_departmanlar" => "Personel-Departman ilişkisi - Kolonlar: id, personel_id, departman_id. ⚠️ FİRMA_ID KOLONU YOK! departmanlar ve personeller üzerinden filtrele. MUTLAKA DISTINCT kullan! | JOIN: departman_id→departmanlar.id (firma_id buradan), personel_id→personeller.id (firma_id buradan)",
            "uretim_islem_tarihler" => "Üretim işlem kayıtları - Kolonlar: id, planlama_id, departman_id, makina_id, personel_id, mevcut_asama, baslatma_tarih, bitirme_tarihi. ⚠️ FİRMA_ID KOLONU YOK! planlama.firma_id üzerinden filtrele. bitirme_tarihi IS NULL = devam eden iş, IS NOT NULL = tamamlanmış iş | JOIN: planlama_id→planlama.id (firma_id buradan), makina_id→makinalar, personel_id→personeller",
            "tedarikciler" => "Tedarikçi bilgileri - Kolonlar: id, tedarikci_unvani",
            "stok_alt_depolar" => "Stok deposu (182 kayıt) - Kolonlar: id, stok_alt_kalem_id, adet, ekleme_tarihi, tedarikci_id | JOIN: stok_alt_kalem_id→stok_alt_kalemler, tedarikci_id→tedarikciler",
            "stok_alt_kalemler" => "Stok kalemleri | JOIN: stok_id→stok_kalemleri",
            "stok_kalemleri" => "Stok ürün tanımları - Kolonlar: id, stok_kalem",
            "makinalar" => "Makina bilgileri (15 kayıt) - Kolonlar: id, makina_adi, durumu (aktif, pasif, bakımda)",
            "departmanlar" => "Departman bilgileri (20 kayıt) - Kolonlar: id, firma_id, departman. Kolon adı 'departman' (departman_adi DEĞİL!)",
            "turler" => "İş türleri",
            "birimler" => "Birim bilgileri (5 kayıt)",
            "uretilen_adetler" => "Üretilen adet bilgileri - Kolonlar: id, siparis_id, uretilen_adet, tarih. DİKKAT: Tablo adı 'uretilen_adetler' ('uretim_adetler' DEĞİL!)",
            "siparis_log" => "Sipariş durum geçmişi - Kolonlar: siparis_id, eski_durum, yeni_durum, tarih. Bir siparişin ne zaman hangi aşamadan geçtiğini gösterir.",
            "uretim_ariza_log" => "Makina arıza kayıtları - Kolonlar: makina_id, ariza_tipi, sure, aciklama, tarih. Makina neden durdu, ne kadar durdu?",
            "teslim_edilenler" => "Teslimat kayıtları - Kolonlar: siparis_id, teslim_tarih, teslim_alan, irsaliye_no. Teslim edilen işler.",
            "agent_alerts" => "Sistem uyarıları ve bildirimler - Kolonlar: alert_type, alert_level (CRITICAL, WARNING), message, created_at. Acil durumlar."
        ];

        // 🛠️ SCHEMA FIX: JSON dosyasından gelen hatalı şemayı düzelt
        if (isset($smart_schema)) {
            $schema = array_merge($schema, $smart_schema);
        }
        
        // Kritik düzeltmeler (JSON'dan yanlış gelse bile ez)
        $schema['uretilen_adetler'] = "Üretilen adet bilgileri (912 kayıt) - Kolonlar: id, firma_id, planlama_id, makina_id, personel_id, uretilen_adet, tarih. | JOIN: makina_id→makinalar, planlama_id→planlama";
        $schema['uretim_islem_tarihler'] = "Üretim işlem kayıtları - Kolonlar: id, planlama_id, makina_id, personel_id, baslatma_tarih, bitirme_tarihi. | JOIN: makina_id→makinalar, planlama_id→planlama";

        return $schema;
    }
    
    /**
     * Benzer geçmiş soruları bul (Vector KB ile semantic search)
     */
    private function findSimilarQuestions($question) {
        // 🎯 Vector Knowledge Base ile semantic search
        $vector_results = $this->vectorKB->findSimilarQuestions($question, $this->firma_id, 3);
        
        if (!empty($vector_results)) {
            error_log("🎯 Vector KB: " . count($vector_results) . " benzer soru bulundu (similarity > 75%)");
            return $vector_results;
        }
        
        // Fallback: Eski keyword-based arama
        $sql = "SELECT soru, sql_query, cevap 
                FROM ai_chat_history 
                WHERE firma_id = :firma_id 
                AND MATCH(soru) AGAINST(:question IN NATURAL LANGUAGE MODE)
                ORDER BY tarih DESC 
                LIMIT 3";
        
        try {
            $sth = $this->conn->prepare($sql);
            $sth->execute([
                "firma_id" => $this->firma_id,
                "question" => $question
            ]);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                error_log("📝 Keyword search: " . count($results) . " sonuç bulundu");
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("⚠️ Similar questions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * AI ile SQL sorgusu oluştur
     */
    private function generateSQL($question, $schema, $context, $similar_questions) {
        error_log("=== generateSQL START for: " . $question);

        // 1. Adım: İlgili tabloları belirle (Dynamic Schema Injection)
        $relevant_tables = $this->identifyRelevantTables($question, $schema);
        error_log("🤖 AI Selected Tables: " . implode(", ", $relevant_tables));

        // 2. Adım: Seçilen tabloların detaylı şemasını al (SHOW CREATE TABLE)
        $detailed_schema_sql = $this->getDetailedSchema($relevant_tables);

        $system_prompt = "Sen bir SQL uzmanısın. Türkçe sorulara göre MySQL sorguları oluşturuyorsun.

VERİTABANI ŞEMASI (SADECE İLGİLİ TABLOLAR):
```sql
" . $detailed_schema_sql . "
```

FİRMA BİLGİLERİ:
" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (!empty($similar_questions)) {
            $system_prompt .= "\n\nBENZER GEÇMIŞ SORULAR:\n";
            foreach ($similar_questions as $sq) {
                $system_prompt .= "Soru: {$sq['soru']}\nSQL: {$sq['sql_query']}\n\n";
            }
        }

        $system_prompt .= "\n\nKURALLAR:
0. 🗣️ KULLANICI SORU YAPISI (KRİTİK): Kullanıcı genelde ÖNCE anahtar kelimeyi söyler, SONRA ne istediğini belirtir. ÖRNEKLER: 'hotmelt makinasındaki işleri' = HOT MELT makina/ürün içeren işleri ara. 'solo print siparişleri' = SOLO PRINT firması siparişlerini ara. 'keçeli son 6 ay' = KEÇELİ ürün son 6 ay kayıtlarını ara. İLK KELİME = ANAHTAR (firma/ürün/makina ismi), SONRA = FİLTRE (işler/siparişler/miktar). Cümledeki ilk anlamlı kelime MUTLAKA WHERE koşulunda kullanılmalı!
1. SADECE SELECT sorguları oluştur (INSERT, UPDATE, DELETE yasak)
2. WHERE koşullarına MUTLAKA firma_id = {$this->firma_id} ekle
3. Tarih karşılaştırmalarında MySQL fonksiyonları kullan (DATE_SUB, NOW, etc.)
4. JSON formatında döndür: {\"sql\": \"...\", \"explanation\": \"...\"}
5. Personel isimleri için CONCAT(ad, ' ', soyad) kullan
6. Türkçe karakter problemleri için COLLATE utf8mb4_unicode_ci kullan
7. FUZZY MATCHING (ÖNEMLİ): Firma/tedarikçi/ürün isimlerinde SADECE ilk anlamlı kelimeyi kullan (min 5 harf). Atla: ambalaj, matbaa, kağıt, san, tic, ltd, şti, a.ş. ÜRÜN sorguları için ÇOK KELİME varsa OR ile birleştir. Örnek: 'keçeli ambalaj' → '%KEÇELİ%'. Örnek: 'hotmelt sıcak tutkal' → '%HOTMELT%' OR '%TUTKAL%' (tek kelime yazım hatası olabilir)
8. FASON iş sorguları: SELECT id,isim FROM planlama WHERE fason_tedarikciler LIKE CONCAT('%',(SELECT id FROM tedarikciler WHERE tedarikci_unvani LIKE '%KEYWORD%' LIMIT 1),'%')
9. KOLON ADI DÜZELTMELERİ: stok_alt_depolar.ekleme_tarihi (DEĞİL .tarih), makinalar.makina_adi (DEĞİL .makine_adi), musteri.firma_unvani (DEĞİL .firma_adi), tedarikciler.tedarikci_unvani (DEĞİL .tedarikci_adi)
10. TEDARİKÇİ SORGULARI (ÖNEMLİ): Tedarikçi adı sorulduğunda stok_kalem'de ARAMA YAPMA! MUTLAKA tedarikciler tablosu JOIN yap ve tedarikci_unvani kullan. ÖRNEK: 'egemet tedarikçisinden kağıt' → SELECT SUM(sad.adet) FROM stok_alt_depolar sad JOIN tedarikciler t ON sad.tedarikci_id=t.id LEFT JOIN birimler b ON sad.birim_id=b.id WHERE t.tedarikci_unvani LIKE '%EGE%' AND b.ad='KG' AND sad.firma_id=16";
        $system_prompt .= "\n11. STOK/ÜRÜN/MAKİNA/MÜŞTERİ İŞ SORULARI (KRİTİK): Kullanıcı 'X işleri', 'X deki işler', 'X te hangi işler var' dediğinde X hem ÜRÜN hem MÜŞTERİ olabilir!
   - EĞER X bir MÜŞTERİ ise: planlama → siparisler → musteri JOIN yap.
   - EĞER X bir ÜRÜN/MAKİNA ise: planlama.isim veya makinalar.makina_adi ara.
   - EN GÜVENLİ YOL (HER İKİSİNİ ARA):
     WHERE (p.isim LIKE '%KEYWORD%' OR m.marka LIKE '%KEYWORD%' OR m.firma_unvani LIKE '%KEYWORD%')
   - ÖRNEK: 'keçeli ambalaj işleri' -> Hem planlama.isim'de 'keçeli' ara, HEM musteri.marka'da 'keçeli' ara!
   - VERİTABANINDA YAZIM HATALARI OLUR: 'hotmelt' → 'HOLTMELT', 'laminasyon' → 'LAMİNASYON' vs. MUTLAKA esnek ara - her harfi tek tek kontrol etme! ÖRNEK: 'hotmelt makinası' → (p.isim LIKE '%HOT%MELT%' OR p.isim LIKE '%HOLT%MELT%' OR p.isim LIKE '%HOTMELT%' OR p.isim LIKE '%HOLTMELT%'). Türkçe klavye hataları: O/Ö, U/Ü, I/İ, S/Ş, C/Ç değişebilir! TAM ÖRNEK SQL: SELECT p.id, p.isim, p.siparis_no FROM planlama p LEFT JOIN siparisler s ON p.siparis_id=s.id LEFT JOIN musteri m ON s.musteri_id=m.id WHERE (p.isim LIKE '%HOT%MELT%' OR m.marka LIKE '%HOT%MELT%' OR m.firma_unvani LIKE '%HOT%MELT%') AND p.firma_id=16 ORDER BY p.id DESC LIMIT 20. STOK miktarı sorulursa o zaman stok_alt_depolar → stok_alt_kalemler → stok_kalemleri JOIN yap.";
        $system_prompt .= "\n12. SİPARİŞ SORGULARI (ÖNEMLİ): siparisler tablosu JOIN: s.musteri_id = m.id (DEĞİL m.vergi_numarasi!). Tarih kolonu: s.tarih (DEĞİL s.siparis_tarihi). Durum filtresi ZORUNLU DEĞİL (çoğu sipariş durum=NULL). ÖRNEK: 'en çok sipariş veren müşteri son 6 ay' → SELECT m.firma_unvani, COUNT(*) as siparis_sayisi FROM siparisler s JOIN musteri m ON s.musteri_id=m.id WHERE s.firma_id=16 AND s.tarih>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY s.musteri_id, m.firma_unvani ORDER BY siparis_sayisi DESC LIMIT 1
13. MÜŞTERİ İŞLERİNİN FASONCUSU (ÖNEMLİ): planlama tablosunda musteri_id YOK! 3 tablo JOIN: planlama → siparisler → musteri. JOIN doğru: p.siparis_id = s.id (DEĞİL s.siparis_no), s.musteri_id = m.id (DEĞİL m.musteri_id). ÖRNEK tam SQL: SELECT p.id, p.isim, GROUP_CONCAT(DISTINCT t.tedarikci_unvani) as fasoncu FROM planlama p JOIN siparisler s ON p.siparis_id=s.id JOIN musteri m ON s.musteri_id=m.id CROSS JOIN tedarikciler t WHERE m.firma_unvani LIKE '%SOLO%' AND p.fason_tedarikciler LIKE CONCAT('%',t.id,'%') AND p.firma_id=16 AND t.firma_id=16 GROUP BY p.id, p.isim";

        $system_prompt .= "\n14. MÜŞTERİ TEMSİLCİSİ/PERSONEL SORULARI: siparisler tablosunda musteri_temsilcisi_id kullan (DEĞİL personel_id). JOIN: s.musteri_temsilcisi_id = pe.id. ÖRNEK: SELECT pe.ad, pe.soyad, COUNT(*) as siparis_sayisi FROM siparisler s JOIN personeller pe ON s.musteri_temsilcisi_id = pe.id WHERE s.firma_id = {$this->firma_id} GROUP BY pe.id ORDER BY siparis_sayisi DESC";

        $system_prompt .= "\n15. 📦 STOK MİKTARI VE DETAYLI ÜRÜN SORULARI (KRİTİK): stok_alt_depolar tablosunda miktar değil ADET kullan. Stok miktarı: SUM(sad.adet). 3 TABLO JOIN: stok_alt_depolar → stok_alt_kalemler → stok_kalemleri. ÖNEMLİ: stok_alt_kalemler.veri JSON kolonu var - EBAT, TİP, GRAMAJ, MARKA bilgileri burada! DETAYLI SORGU ÖRNEK: '700 ebat amerikan bristol stokta var mı' → SELECT sk.stok_kalem, sak.veri, SUM(sad.adet) as stok_miktari FROM stok_alt_depolar sad JOIN stok_alt_kalemler sak ON sad.stok_alt_kalem_id=sak.id JOIN stok_kalemleri sk ON sak.stok_id=sk.id WHERE JSON_EXTRACT(sak.veri, '$.EBAT') = '700' AND JSON_EXTRACT(sak.veri, '$.TİP') LIKE '%AMERIKAN%BRISTOL%' AND sad.firma_id=16 GROUP BY sk.stok_kalem, sak.veri HAVING stok_miktari > 0. BASİT SORGU: SELECT SUM(sad.adet) FROM stok_alt_depolar sad JOIN stok_alt_kalemler sak ON sad.stok_alt_kalem_id=sak.id JOIN stok_kalemleri sk ON sak.stok_id=sk.id WHERE sk.stok_kalem LIKE '%KRAFT%' AND sad.firma_id=16.";
        $system_prompt .= "\n16. 🏢 MARKA VE FİRMA ARAMALARI (KRİTİK - MARKA ÖNCELİKLİ): musteri tablosu: marka (kısa tanınan isim - KOMAGENE, MIGROS), firma_unvani (resmi unvan - YÖRPAŞ YÖRESEL LEZZETLER). KULLANICI MARKA İLE SORAR! MUTLAKA OR ile her ikisinde ara: (m.marka LIKE '%İSİM%' OR m.firma_unvani LIKE '%İSİM%'). SELECT'te MARKA GÖSTER (marka öncelik): SELECT m.id, m.marka, m.firma_unvani. ÖRNEK: 'Komagene' → WHERE (m.marka LIKE '%KOMAGENE%' OR m.firma_unvani LIKE '%KOMAGENE%') → Sonuç: 'KOMAGENE (YÖRPAŞ YÖRESEL LEZZETLER)'. 'Migros' → WHERE (m.marka LIKE '%MIGROS%' OR m.firma_unvani LIKE '%MIGROS%'). SELECT'te marka kolonunu MUTLAKA dahil et!";
        $system_prompt .= "\n17. 💰 FİYAT/TUTAR HESAPLAMALARI (KRİTİK): siparisler.fiyat kolonu ZATEN hesaplanmış TOPLAM tutar içerir! CİRO/TOPLAM sorguları için DİREKT siparisler.fiyat kullan, ASLA adet×fiyat yapma! YANLIŞ ❌: SUM(s.adet * s.fiyat) - Bu 100 kat fazla hesaplar! DOĞRU ✅: SUM(s.fiyat). Ortalama: AVG(s.fiyat). En yüksek: MAX(s.fiyat). UYARI: s.adet ve s.fiyat çarpımı YAPMA, s.fiyat zaten toplam tutardır! ÖRNEK CİRO: SELECT SUM(s.fiyat) as toplam_ciro FROM siparisler s JOIN musteri m ON s.musteri_id=m.id WHERE m.firma_unvani LIKE '%FIRMA%' AND s.firma_id=16 AND s.tarih >= DATE_SUB(NOW(), INTERVAL 1 YEAR).";
        $system_prompt .= "\n18. 🔍 SUBQUERY CARDINALITY (KRİTİK): Subquery'ler sadece TEK satır döndürmeli! ÇOKLU sonuç için IN kullan, = kullanma! YANLIŞ: sektor_id = (SELECT id FROM sektorler WHERE sektor_adi LIKE '%medikal%') ❌. DOĞRU: sektor_id IN (SELECT id FROM sektorler WHERE sektor_adi LIKE '%medikal%') ✅. JOIN tercih et: LEFT JOIN sektorler s ON m.sektor_id=s.id WHERE s.sektor_adi LIKE '%medikal%'. SEKTÖR SORGULARI: musteri tablosunda sektor_id var, sektorler ile JOIN yap!";
        $system_prompt .= "\n19. 🏭 DEPARTMAN SORULARI (KRİTİK): planlama tablosunda 'departman' kolonu YOK! 'departmanlar' JSON array var [1,2,4]. JSON_CONTAINS ile CAST kullan! departmanlar tablosu: (id, departman). DOĞRU SYNTAX: JSON_CONTAINS(departmanlar, CAST(2 AS JSON)). ÖRNEK OFSET (id=2): SELECT COUNT(*) FROM planlama WHERE firma_id=16 AND JSON_CONTAINS(departmanlar, CAST(2 AS JSON)) AND mevcut_asama < asama_sayisi. Önce ID bul: (SELECT id FROM departmanlar WHERE departman LIKE '%OFSET%' LIMIT 1). Bekleyen: mevcut_asama < asama_sayisi. Tamamlanan: mevcut_asama = asama_sayisi. DİKKAT: CAST kullanmazsan '3146 Invalid data type' hatası alırsın!";
        $system_prompt .= "\n20. 🔧 MAKİNA SORULARI (KRİTİK - GELİŞMİŞ): planlama.makinalar JSON array [1,2,3,8]. makinalar tablosu: (id, makina_adi, departman_id, durumu).\n\n📋 MAKİNA İŞ LİSTESİ: SELECT p.id, p.isim, s.siparis_no, s.isin_adi, m.makina_adi FROM planlama p JOIN siparisler s ON p.siparis_id=s.id JOIN makinalar m ON JSON_CONTAINS(p.makinalar, CAST(m.id AS JSON)) WHERE m.makina_adi LIKE '%OMEGA%' AND p.firma_id=16 AND m.firma_id=16 ORDER BY p.sira LIMIT 20.\n\n📊 EN YÜKSEK ADET: SELECT p.id, p.isim, s.siparis_no, s.adet, m.makina_adi FROM planlama p JOIN siparisler s ON p.siparis_id=s.id JOIN makinalar m ON JSON_CONTAINS(p.makinalar, CAST(m.id AS JSON)) WHERE m.makina_adi LIKE '%OMEGA%' AND p.firma_id=16 AND m.firma_id=16 ORDER BY s.adet DESC LIMIT 1.\n\n📉 EN DÜŞÜK ADET: ORDER BY s.adet ASC yerine DESC kullan.\n\n🔢 İŞ SAYISI: SELECT COUNT(*) as is_sayisi FROM planlama p JOIN makinalar m ON JSON_CONTAINS(p.makinalar, CAST(m.id AS JSON)) WHERE m.makina_adi LIKE '%OMEGA%' AND p.firma_id=16 AND m.firma_id=16.\n\nDİKKAT: 1) makinalar JOIN gerekli (m.makina_adi). 2) JSON_CONTAINS ile CAST(m.id AS JSON). 3) Her iki tabloda firma_id kontrolü. 4) siparis_no için siparisler JOIN. 5) Yaygın makinalar: OMEGA, KBA, HD, HOTMELT (veya HOLTMELT), LAMİNASYON. 6) LIKE '%MAKINA%' ile esnek arama.";
        $system_prompt .= "\n21. 👥 PERSONEL PERFORMANS SORULARI (KRİTİK - ROLE GÖRE): personeller tablosu: (id, firma_id, ad, soyad, yetki_id, durum). yetkiler tablosu: (id, yetki). YETKİ TİPLERİ: 'Müşteri Temsilcisi'(2), 'Satış Temsilcisi'(3) = SATIŞ; 'Üretim'(7), 'Üretim Amiri'(8) = ÜRETİM; 'Admin'(1), 'Planlamacı'(10) = İDARİ.\n\nSATIŞ PERSONELİ (en yoğun): SELECT COUNT(*) as siparis_sayisi, SUM(s.fiyat) as toplam_fiyat, s.onaylayan_personel_id, (SELECT CONCAT(ad, ' ', soyad) FROM personeller WHERE id = s.onaylayan_personel_id) as personel, (SELECT yetki FROM yetkiler WHERE id = (SELECT yetki_id FROM personeller WHERE id = s.onaylayan_personel_id)) as rol FROM siparisler s WHERE s.firma_id = 16 AND s.onaylayan_personel_id > 0 GROUP BY s.onaylayan_personel_id ORDER BY siparis_sayisi DESC LIMIT 5.\n\nÜRETİM PERSONELİ (en yoğun): SELECT COUNT(*) as tamamlanan_is, uit.personel_id, (SELECT CONCAT(ad, ' ', soyad) FROM personeller WHERE id = uit.personel_id) as personel FROM uretim_islem_tarihler uit WHERE uit.personel_id > 0 AND uit.bitirme_tarihi IS NOT NULL GROUP BY uit.personel_id ORDER BY tamamlanan_is DESC LIMIT 5.\n\nİDARİ/PLANLAMA PERSONELİ: SELECT COUNT(*) as plan_sayisi, p.firma_id FROM planlama p WHERE p.firma_id = 16 GROUP BY p.firma_id. NOT: planlama tablosunda personel_id yok, sipariş bazlı sayım yapılabilir.\n\nDİKKAT: Table alias 's' kullan (NOT 'sl'). Üretim için uretim_islem_tarihler, satış için siparisler kullan!";
        $system_prompt .= "\n22. 🔍 MÜŞTERİ İSMİ ARAMA (KRİTİK - MARKA ÖNCELİKLİ): Kullanıcı genelde MARKA ile sorar ('Komagene', 'Migros', 'Carrefour'). musteri tablosu: marka (kısa/tanınan isim) + firma_unvani (uzun resmi unvan). ARAMA KURALI: ÖNCE marka ara, sonra firma_unvani. SQL: WHERE (m.marka LIKE '%KOMAGENE%' OR m.firma_unvani LIKE '%KOMAGENE%'). SELECT'te HEM marka HEM firma_unvani göster: SELECT m.id, m.marka, m.firma_unvani. YANIT FORMATINDA: 'KOMAGENE' (firma unvanı: YÖRPAŞ YÖRESEL LEZZETLER). TÜRKÇE KARAKTER: COLLATE utf8mb4_turkish_ci kullan. MIN 4 KARAKTER yeterli ('%KOMA%'). ÖRNEK MARKALAR: KOMAGENE=YÖRPAŞ, MİGROS, CARREFOUR=CARREFOURSA, BİM=BİM MAĞAZALAR. DIŞ TİCARET, LTD, ŞTİ eklerini görmezden gel!";
        
        $system_prompt .= "\n23. TABLO İSMİ DÜZELTMESİ: 'uretim_adetler' diye bir tablo YOK! Doğrusu 'uretilen_adetler'. Sakın uretim_adetler kullanma!";
        $system_prompt .= "\n24. MAKİNA BAZINDA ÜRETİM (KRİTİK): 'uretilen_adetler' tablosunda 'makina_id' VARDIR! Makina bazında üretim sorulursa: SELECT m.makina_adi, SUM(ua.uretilen_adet) as toplam FROM uretilen_adetler ua JOIN makinalar m ON ua.makina_id=m.id WHERE m.firma_id={$this->firma_id} GROUP BY m.id ORDER BY toplam DESC.";
        $system_prompt .= "\n25. SİPARİŞ DETAYLARI (JSON): Siparişin ürün özellikleri (renk, ebat, malzeme) 'siparisler.veriler' JSON kolonundadır. ÖRNEK: JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.urun_adi')) veya JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.renk')). Eğer kullanıcı 'kırmızı renkli işler' derse: WHERE JSON_SEARCH(s.veriler, 'one', '%kırmızı%') IS NOT NULL kullan.";
        $system_prompt .= "\n26. ARIZA VE DURUŞ ANALİZİ: 'En çok arıza yapan makina' sorulursa: SELECT m.makina_adi, COUNT(*) as ariza_sayisi, SUM(ual.sure) as toplam_sure FROM uretim_ariza_log ual JOIN makinalar m ON ual.makina_id=m.id WHERE m.firma_id={$this->firma_id} GROUP BY m.id ORDER BY ariza_sayisi DESC.";

        $system_prompt .= "\n\nMEVCUT ARAÇLAR (FONKSİYONLAR):
Eğer kullanıcı aşağıdaki hesaplamaları isterse, SQL yerine JSON formatında araç çağrısı yap:
1. OEE Hesabı: {\"tool\": \"calculateOEE\", \"params\": {\"makina_id\": 123, \"date\": \"2025-10-27\"}}
   - Tetikleyiciler: \"OEE nedir\", \"verimlilik puanı\", \"makina performansı\"
2. Makina Durumu: {\"tool\": \"getMachineStatus\", \"params\": {\"makina_id\": 123}}
   - Tetikleyiciler: \"makina ne yapıyor\", \"şu an çalışıyor mu\", \"operatör kim\"

NOT: Eğer makina ID'sini bilmiyorsan, önce SQL ile makina adından ID'yi bulacak bir sorgu yaz. Tool çağrısını ikinci adımda yapabiliriz. Şimdilik sadece SQL yaz.";

        $user_prompt = "Soru: $question";
        
        try {
            $response = $this->ai->chat([
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_prompt]
            ], 0.1, 1500);
            error_log("OpenAI response length: " . strlen($response));            error_log("OpenAI response preview: " . substr($response, 0, 300));
            
            // JSON parse et
            $response = str_replace([chr(96).chr(96).chr(96)."json",chr(96).chr(96).chr(96)], "", $response);
            $response = trim($response);
            $result = json_decode($response, true);
            
            // Tool çağrısı kontrolü (Direct JSON)
            if (isset($result["tool"])) {
                return [
                    "success" => true,
                    "tool" => $result["tool"],
                    "params" => $result["params"] ?? [],
                    "explanation" => "Tool çağrısı yapılıyor..."
                ];
            }

            // Tool çağrısı kontrolü (Nested in SQL field)
            if (isset($result["sql"]) && strpos(trim($result["sql"]), '{"tool"') === 0) {
                $nested_tool = json_decode($result["sql"], true);
                if ($nested_tool && isset($nested_tool["tool"])) {
                    return [
                        "success" => true,
                        "tool" => $nested_tool["tool"],
                        "params" => $nested_tool["params"] ?? [],
                        "explanation" => "Tool çağrısı yapılıyor (Nested)..."
                    ];
                }
            }

            if (!$result || !isset($result["sql"])) {
                throw new Exception("SQL oluşturulamadı");
            }
            
            return [
                "success" => true,
                "sql" => $result["sql"],
                "explanation" => $result["explanation"] ?? ""
            ];
            
        } catch (Exception $e) {
            error_log("=== generateSQL ERROR: " . $e->getMessage());
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }
    
    /**
     * SQL sorgusunu çalıştır
     */
    private function executeSQL($sql) {
        try {
            $sth = $this->conn->prepare($sql);
            $sth->execute();
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("=== generateSQL ERROR: " . $e->getMessage());
            throw new Exception("SQL hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Sonuçlardan Türkçe yanıt oluştur
     */
    private function generateAnswer($question, $data, $sql_explanation) {
        $system_prompt = "Sen bir iş analitiği asistanısın. Verileri analiz edip Türkçe, anlaşılır yanıtlar veriyorsun.";
        
        $record_count = count($data);
        $sample_data = array_slice($data, 0, 5); // İlk 5 kayıt
        
        $user_prompt = "Soru: $question\n\n";
        $user_prompt .= "SQL Açıklaması: $sql_explanation\n\n";
        $user_prompt .= "TOPLAM KAYIT: $record_count\n\n";
        $user_prompt .= "İLK 5 KAYIT: " . json_encode($sample_data, JSON_UNESCAPED_UNICODE) . "\n\n";
        $user_prompt .= "Bu verilere dayanarak soruyu yanıtla. Eğer TOPLAM KAYIT 0 ise, muhtemelen isim yanlış yazılmış - benzer isimleri öner. Kısa, net cevap ver.";
        
        try {
            return $this->ai->chat([
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_prompt]
            ], 0.2, 800);
        } catch (Exception $e) {
            error_log("=== generateSQL ERROR: " . $e->getMessage());
            // AI yanıt üretemezse basit özet döndür
            $count = count($data);
            return "Sorgunuz çalıştırıldı ve $count sonuç bulundu. Detaylar için aşağıdaki tabloya bakabilirsiniz.";
        }
    }
    
    /**
     * Sohbet geçmişine kaydet
     */
    private function saveChatHistory($soru, $cevap, $sql, $sonuc_sayisi, $sure) {
        $sql_insert = "INSERT INTO ai_chat_history 
                      (firma_id, kullanici_id, soru, cevap, sql_query, sonuc_sayisi, cevap_suresi, tarih)
                      VALUES (:firma_id, :kullanici_id, :soru, :cevap, :sql_query, :sonuc_sayisi, :sure, NOW())";
        
        $sth = $this->conn->prepare($sql_insert);
        $sth->execute([
            "firma_id" => $this->firma_id,
            "kullanici_id" => $this->kullanici_id,
            "soru" => $soru,
            "cevap" => $cevap,
            "sql_query" => $sql,
            "sonuc_sayisi" => $sonuc_sayisi,
            "sure" => $sure
        ]);
        
        // Training data logger ekle (fine-tuning için)
        $this->logTrainingData($soru, $sql, $sonuc_sayisi);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Training data logger (fine-tuning için)
     */
    private function logTrainingData($question, $sql, $record_count) {
        // Sadece başarılı sorguları logla (boş sonuç olanları atla)
        if ($record_count == 0) {
            return;
        }
        
        $log_file = "/var/www/html/logs/training_data.jsonl";
        
        // System prompt (kısaltılmış versiyon)
        $system_prompt = "Sen bir SQL expert asistanısın. Türkçe sorulardan MySQL sorguları oluşturuyorsun. firma_id kontrolü zorunlu. JSON formatında cevap ver.";
        
        // Training data formatı (OpenAI fine-tuning)
        $training_example = [
            "messages" => [
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $question],
                ["role" => "assistant", "content" => json_encode([
                    "sql" => $sql,
                    "explanation" => "SQL sorgusu başarıyla oluşturuldu."
                ], JSON_UNESCAPED_UNICODE)]
            ],
            "metadata" => [
                "firma_id" => $this->firma_id,
                "record_count" => $record_count,
                "timestamp" => date("Y-m-d H:i:s")
            ]
        ];
        
        // JSONL formatında kaydet (her satır bir JSON objesi)
        file_put_contents(
            $log_file,
            json_encode($training_example, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
    
    /**
     * Knowledge base güncelle (Vector KB + Keyword)
     */
    private function updateKnowledgeBase($soru, $sql, $data) {
        // 🎯 1. Vector Knowledge Base'e embedding ile kaydet
        try {
            $saved = $this->vectorKB->saveWithEmbedding($soru, $sql, $this->firma_id);
            if ($saved) {
                error_log("💾 Vector KB'ye kaydedildi: " . substr($soru, 0, 50));
            }
        } catch (Exception $e) {
            error_log("⚠️ Vector KB kayıt hatası: " . $e->getMessage());
        }
        
        // 2. Eski keyword-based system (fallback)
        $keywords = $this->extractKeywords($soru);
        
        foreach ($keywords as $keyword) {
            // Varsa güncelle, yoksa ekle
            $check_sql = "SELECT id, kullanim_sayisi FROM ai_knowledge_base 
                         WHERE firma_id = :firma_id AND anahtar_kelime = :keyword";
            $sth = $this->conn->prepare($check_sql);
            $sth->execute([
                "firma_id" => $this->firma_id,
                "keyword" => $keyword
            ]);
            $existing = $sth->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Güncelle
                $update_sql = "UPDATE ai_knowledge_base 
                              SET kullanim_sayisi = kullanim_sayisi + 1,
                                  son_kullanim = NOW()
                              WHERE id = :id";
                $sth = $this->conn->prepare($update_sql);
                $sth->execute(["id" => $existing["id"]]);
            } else {
                // Yeni ekle
                $kategori = $this->detectCategory($soru);
                $insert_sql = "INSERT INTO ai_knowledge_base 
                              (firma_id, kategori, anahtar_kelime, icerik, kullanim_sayisi, son_kullanim)
                              VALUES (:firma_id, :kategori, :keyword, :icerik, 1, NOW())";
                $sth = $this->conn->prepare($insert_sql);
                $sth->execute([
                    "firma_id" => $this->firma_id,
                    "kategori" => $kategori,
                    "keyword" => $keyword,
                    "icerik" => json_encode(["soru" => $soru, "sql" => $sql], JSON_UNESCAPED_UNICODE)
                ]);
            }
        }
    }
    
    /**
     * Anahtar kelime çıkar
     */
    private function extractKeywords($text) {
        $stopwords = ["nedir", "kadar", "nekadar", "nasıl", "bir", "için", "olan", "ise", "gibi", "çok", "daha", "mi", "mı"];
        $words = preg_split("/[\s,?.!]+/u", mb_strtolower($text, "UTF-8"));
        $keywords = array_diff($words, $stopwords);
        return array_filter($keywords, fn($w) => mb_strlen($w, "UTF-8") > 3);
    }
    
    /**
     * Kategori tespit et
     */
    private function detectCategory($question) {
        $q = mb_strtolower($question, "UTF-8");
        
        if (preg_match("/(müşteri|firma|helmex)/u", $q)) return "musteri";
        if (preg_match("/(personel|usta|çalışan|gokhan)/u", $q)) return "personel";
        if (preg_match("/(makina|arıza|üretim)/u", $q)) return "makina";
        if (preg_match("/(sipariş|iş|teslim)/u", $q)) return "siparis";
        if (preg_match("/(termin|süre|zaman)/u", $q)) return "planlama";
        
        return "genel";
    }
    
    /**
     * HTML tablo oluştur - linklerle
     */
    private function generateHTMLTable($data, $sql) {
        if (empty($data)) {
            return "";
        }
        
        // Kolon isimlerini al
        $columns = array_keys($data[0]);
        
        // SQL'den hangi tabloların kullanıldığını anla
        $has_musteri = stripos($sql, 'musteri') !== false || stripos($sql, 'FROM m ') !== false;
        $has_siparis = stripos($sql, 'siparis') !== false || stripos($sql, 'FROM s ') !== false;
        
        // Tablo başlat - div wrapper KALDIR, sadece table
        $html = '<table id="aiDataTable" class="table table-sm table-hover table-striped table-bordered">';
        
        // Header
        $html .= '<thead class="table-light"><tr>';
        foreach ($columns as $col) {
            $display_name = ucwords(str_replace('_', ' ', $col));
            $html .= '<th>' . htmlspecialchars($display_name) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Body
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            
            // Her kolon için değer veya link
            foreach ($columns as $col) {
                $value = $row[$col];
                $cell_content = htmlspecialchars($value ?? '');
                
                // Müşteri linki oluştur
                if ($has_musteri && in_array($col, ['id', 'musteri_id']) && is_numeric($value)) {
                    $cell_content = '<a href="/index.php?url=siparis&musteri_id=' . $value . '" class="text-primary fw-bold" target="_blank">' . 
                                   '<i class="mdi mdi-open-in-new"></i> ' . $value . '</a>';
                }
                // Firma unvanı için de link ekle
                elseif ($has_musteri && $col === 'firma_unvani' && isset($row['id'])) {
                    $cell_content = '<a href="/index.php?url=siparis&musteri_id=' . $row['id'] . '" class="text-primary" target="_blank">' . 
                                   htmlspecialchars($value) . ' <i class="mdi mdi-open-in-new"></i></a>';
                }
                elseif ($has_musteri && $col === 'firma_unvani' && isset($row['musteri_id'])) {
                    $cell_content = '<a href="/index.php?url=siparis&musteri_id=' . $row['musteri_id'] . '" class="text-primary" target="_blank">' . 
                                   htmlspecialchars($value) . ' <i class="mdi mdi-open-in-new"></i></a>';
                }
                // Sipariş linki oluştur
                elseif ($has_siparis && in_array($col, ['id', 'siparis_id']) && is_numeric($value)) {
                    $cell_content = '<a href="/index.php?url=siparis_gor&siparis_id=' . $value . '" class="text-success fw-bold" target="_blank">' . 
                                   '<i class="mdi mdi-open-in-new"></i> ' . $value . '</a>';
                }
                // Sipariş no için de link
                elseif ($has_siparis && $col === 'siparis_no' && isset($row['id'])) {
                    $cell_content = '<a href="/index.php?url=siparis_gor&siparis_id=' . $row['id'] . '" class="text-success" target="_blank">' . 
                                   htmlspecialchars($value) . ' <i class="mdi mdi-open-in-new"></i></a>';
                }
                elseif ($has_siparis && $col === 'siparis_no' && isset($row['siparis_id'])) {
                    $cell_content = '<a href="/index.php?url=siparis_gor&siparis_id=' . $row['siparis_id'] . '" class="text-success" target="_blank">' . 
                                   htmlspecialchars($value) . ' <i class="mdi mdi-open-in-new"></i></a>';
                }
                // Sayılar için formatla
                elseif (is_numeric($value) && strpos($value, '.') !== false) {
                    $cell_content = number_format((float)$value, 2, ',', '.');
                }
                
                $html .= '<td>' . $cell_content . '</td>';
            }
            
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    /**
     * Fine-tuning için AI loglarını kaydet
     */
    private function logForFineTuning($soru, $sql_sorgusu, $sonuc) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO ai_log (firma_id, soru, sql_sorgusu, sonuc, tarih) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$this->firma_id, $soru, $sql_sorgusu, $sonuc]);
        } catch (Exception $e) {
            error_log("AI log kaydetme hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Soruyu analiz edip ilgili tabloları belirle
     */
    private function identifyRelevantTables($question, $summary_schema) {
        $system_prompt = "Sen bir veritabanı uzmanısın. Aşağıdaki tablo özetlerine bakarak, kullanıcının sorusunu cevaplamak için HANGİ tablolara ihtiyaç olduğunu belirle.
        
TABLO ÖZETLERİ:
" . json_encode($summary_schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

GÖREV: Sadece gerekli tablo isimlerini içeren bir JSON array döndür. Gereksiz tablo ekleme.
ÖRNEK: ['siparisler', 'musteri']";

        $user_prompt = "Soru: $question";

        try {
            $response = $this->ai->chat([
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_prompt]
            ], 0.0, 200); // Düşük temperature, kısa cevap
            
            $response = str_replace([chr(96).chr(96).chr(96)."json",chr(96).chr(96).chr(96)], "", $response);
            $tables = json_decode(trim($response), true);
            
            if (is_array($tables)) {
                return $tables;
            }
            return array_keys($summary_schema); // Fallback: hepsi
        } catch (Exception $e) {
            error_log("Tablo belirleme hatası: " . $e->getMessage());
            return array_keys($summary_schema);
        }
    }

    /**
     * Seçilen tabloların detaylı şemasını (CREATE TABLE) getir
     */
    private function getDetailedSchema($tables) {
        $detailed_schema = "";
        
        foreach ($tables as $table) {
            // Güvenlik: Sadece harf, rakam ve alt çizgi
            $table = preg_replace("/[^a-zA-Z0-9_]/", "", $table);
            
            try {
                // Tablo var mı kontrol et
                $check = $this->conn->query("SHOW TABLES LIKE '$table'");
                if ($check->rowCount() == 0) continue;

                $stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                if ($row && isset($row[1])) {
                    $detailed_schema .= $row[1] . ";\n\n";
                }
            } catch (Exception $e) {
                error_log("Schema fetch error for $table: " . $e->getMessage());
            }
        }
        
        return $detailed_schema;
    }
}
