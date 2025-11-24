<?php
/**
 * HANKA WhatsApp AI Agent Webhook
 * Twilio WhatsApp API ile entegrasyon
 * 
 * Setup:
 * 1. Twilio hesabı oluştur (https://www.twilio.com)
 * 2. WhatsApp Sandbox'ı aktifleştir
 * 3. Webhook URL'sini ayarla: https://lethe.com.tr/whatsapp_webhook.php
 */

require_once "include/db.php";
require_once "include/AIChatEngine.php";

// Timeout sürelerini artır (AI query için)
set_time_limit(60); // 60 saniye
ini_set('max_execution_time', 60);

header('Content-Type: text/xml');

// Database bağlantısı
require_once __DIR__ . '/include/db.php';
require_once __DIR__ . '/include/AIChatEngine.php';

// .env yükle (Twilio credentials için)
$env_file = __DIR__ . "/.env";
if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Global cache - memory'de tut
global $ENTITY_CACHE;
if (!isset($ENTITY_CACHE)) {
    $ENTITY_CACHE = [];
}

// Twilio credentials
$twilio_account_sid = getenv('TWILIO_ACCOUNT_SID');
$twilio_auth_token = getenv('TWILIO_AUTH_TOKEN');

// Gelen mesajı al
$from = isset($_POST['From']) ? $_POST['From'] : '';
$body = isset($_POST['Body']) ? trim($_POST['Body']) : '';
$message_sid = isset($_POST['MessageSid']) ? $_POST['MessageSid'] : '';

// Log
error_log("WhatsApp mesajı geldi: From=$from, Body=$body, SID=$message_sid");

// Firma ID'sini personel tablosundan al (WhatsApp numarasına göre)
$phone = str_replace('whatsapp:', '', $from);
$phone = preg_replace('/[^0-9+]/', '', $phone); // Sadece numara ve +

// Personel tablosunda bu numaraya sahip kullanıcıyı bul
$stmt = $conn->prepare("SELECT firma_id FROM personeller WHERE whatsapp_phone = :phone AND whatsapp_aktif = 1 LIMIT 1");
$stmt->execute([':phone' => $phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $firma_id = $user['firma_id'];
    error_log("Firma ID bulundu: $firma_id (WhatsApp: $phone)");
} else {
    // Varsayılan firma (kayıtlı değilse)
    $firma_id = 16; // Ana firma
    error_log("WhatsApp numarası kayıtlı değil ($phone), default firma_id: $firma_id");
}

try {
    // Boş mesaj kontrolü
    if (empty($body)) {
        throw new Exception("Boş mesaj gönderildi");
    }
    
    // Kullanıcıyı kaydet/bul
    $phone = str_replace('whatsapp:', '', $from);
    $phone = trim($phone); // Boşlukları temizle
    
    // WhatsApp mesajlarını firma ile ilişkilendirmek için
    // Şimdilik basit: Her mesajı whatsapp_messages'e kaydet
    // Firma ID'si: default 1 (sonra whatsapp_settings'den alınabilir)
    $user_id = null;
    
    // AI'ya sor
    $ai_response = processWhatsAppMessage($conn, $firma_id, $body);
    
    // Mesajı kaydet
    logWhatsAppMessage($conn, $phone, $body, $ai_response, $message_sid, $firma_id);
    
    // Twilio TwiML response
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response>';
    echo '<Message>' . htmlspecialchars($ai_response, ENT_XML1) . '</Message>';
    echo '</Response>';
    
} catch (Exception $e) {
    error_log("WhatsApp webhook hatası: " . $e->getMessage());
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response>';
    echo '<Message>Üzgünüm, bir hata oluştu. Lütfen daha sonra tekrar deneyin.</Message>';
    echo '</Response>';
}

/**
 * WhatsApp mesajını AI ile işle
 */
function processWhatsAppMessage($conn, $firma_id, $message) {
    // Session başlat (AI için gerekli)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['firma_id'] = $firma_id;
    $_SESSION['personel_id'] = 1; // Default admin
    
    // Komut kontrolü
    if (stripos($message, '/help') === 0 || $message === '?') {
        return getHelpMessage();
    }
    
    if (stripos($message, '/siparisler') === 0) {
        return getSiparislerOzet($conn, $firma_id);
    }
    
    if (stripos($message, '/planlama') === 0) {
        return getPlanlamaOzet($conn, $firma_id);
    }
    
    // Basit pattern matching (sadece hızlı komutlar için)
    // NOT: Bu patternlar AI'ya gitmeden önce hızlı cevaplar verir
    // Karmaşık sorular için AI kullanılacak
    
    // Müşteri sayısı sorgusu (AI yanlış yapıyor, manual)
    if (preg_match('/(kaç|toplam|sayı).*(müşteri)/i', $message) && 
        !preg_match('/(sipariş|ciro|aktif|en çok)/i', $message)) {
        return getMusteriSayisi($conn, $firma_id);
    }
    
    // Tedarikçi/Fason iş sayısı sorguları
    // "Keçeli tedarikçisinde kaç iş var" veya "Keçeli fason işleri"
    if (preg_match('/fason/i', $message) || 
        preg_match('/(tedarikçi|tedarikci).*?(kaç|sayı|adet).*?(iş|job)/i', $message) ||
        preg_match('/(kaç|sayı|adet).*?(iş|job).*?(tedarikçi|tedarikci)/i', $message)) {
        error_log("FASON PATTERN MATCHED: " . $message);
        
        // İsim çıkar
        $name_pattern = '';
        if (preg_match('/(keçeli|keçel|keceli)/i', $message)) {
            $name_pattern = '%KEÇELİ%';
        } elseif (preg_match('/(egemet|ege\s)/i', $message)) {
            $name_pattern = '%EGEMET%';
        } elseif (preg_match('/([A-ZÇĞİÖŞÜ]{4,})/u', $message, $matches)) {
            $name_pattern = '%' . strtoupper($matches[1]) . '%';
        }
        
        error_log("NAME PATTERN: " . $name_pattern);
        
        if (!$name_pattern) {
            return "Lütfen firma veya tedarikçi adı belirtin.\n\n💡 Örnek:\n• 'Keçeli fason işleri' (müşteri)\n• 'Egemet tedarikçisinde fason işler'";
        }
        
        // Sayı sorusu mu? (kaç, sayı, adet)
        $is_count_query = preg_match('/(kaç|sayı|adet)/i', $message);
        error_log("IS COUNT QUERY: " . ($is_count_query ? 'YES' : 'NO'));
        
        // 1. Tedarikçi bazlı mı?
        if (preg_match('/(tedarikçi|tedarikci|tedarikcide|tedarikçide|tedarikçisi)/i', $message)) {
            error_log("TEDARIKCI MODE - Count: " . ($is_count_query ? 'YES' : 'NO'));
            if ($is_count_query) {
                return getFasonIslerCountByTedarikci($conn, $firma_id, $name_pattern);
            } else {
                return getFasonIslerByTedarikci($conn, $firma_id, $name_pattern);
            }
        } 
        // 2. Müşteri bazlı (varsayılan)
        else {
            error_log("MUSTERI MODE - Count: " . ($is_count_query ? 'YES' : 'NO'));
            if ($is_count_query) {
                return getFasonIslerCountByMusteri($conn, $firma_id, $name_pattern);
            } else {
                return getFasonIslerByMusteri($conn, $firma_id, $name_pattern);
            }
        }
    }
    
    // Makina sorguları - pattern matching
    $makina_names = ['omega', 'kba', 'hd', 'hotmelt', 'laminasyon', 'hot\s*melt'];
    $makina_regex = '(' . implode('|', $makina_names) . ')';
    
    // 1. "X makinasında en yüksek adetli iş"
    if (preg_match('/(makina|makinası|makinasında).*(en\s+yüksek|en\s+büyük|maksimum|max).*(adet|iş)/i', $message) ||
        preg_match("/{$makina_regex}.*(en\s+yüksek|en\s+büyük|maksimum).*(adet|iş)/i", $message)) {
        
        if (preg_match("/{$makina_regex}/i", $message, $matches)) {
            $makina_pattern = '%' . strtoupper(str_replace(' ', '', $matches[1])) . '%';
            return getMakinaEnYuksekAdetIsi($conn, $firma_id, $makina_pattern);
        }
    }
    
    // 2. "X makinasında en düşük adetli iş"
    if (preg_match('/(makina|makinası|makinasında).*(en\s+düşük|en\s+küçük|minimum|min).*(adet|iş)/i', $message) ||
        preg_match("/{$makina_regex}.*(en\s+düşük|en\s+küçük|minimum).*(adet|iş)/i", $message)) {
        
        if (preg_match("/{$makina_regex}/i", $message, $matches)) {
            $makina_pattern = '%' . strtoupper(str_replace(' ', '', $matches[1])) . '%';
            return getMakinaEnDusukAdetIsi($conn, $firma_id, $makina_pattern);
        }
    }
    
    // 3. "X makinasında kaç iş var"
    if (preg_match('/(makina|makinası|makinasında).*(kaç\s+iş|iş\s+sayısı|toplam\s+iş)/i', $message) ||
        preg_match("/{$makina_regex}.*(kaç\s+iş|iş\s+sayısı)/i", $message)) {
        
        if (preg_match("/{$makina_regex}/i", $message, $matches)) {
            $makina_pattern = '%' . strtoupper(str_replace(' ', '', $matches[1])) . '%';
            return getMakinaIsSayisi($conn, $firma_id, $makina_pattern);
        }
    }
    
    // 4. "X makinası toplam üretim"
    if (preg_match('/(makina|makinası|makinasında).*(toplam\s+üretim|toplam\s+adet|total)/i', $message) ||
        preg_match("/{$makina_regex}.*(toplam\s+üretim|toplam\s+adet)/i", $message)) {
        
        if (preg_match("/{$makina_regex}/i", $message, $matches)) {
            $makina_pattern = '%' . strtoupper(str_replace(' ', '', $matches[1])) . '%';
            return getMakinaToplamUretim($conn, $firma_id, $makina_pattern);
        }
    }
    
    // Sadece /komut formatında olanlar için pattern matching
    if ($message === '/siparisler' || stripos($message, 'sipariş listele') !== false) {
        return getSiparislerOzet($conn, $firma_id);
    }
    
    if ($message === '/planlama' || $message === '/plan') {
        return getPlanlamaOzet($conn, $firma_id);
    }
    
    // Müşteri ismi doğrudan geçiyorsa hızlı ara (tek kelime)
    $words = explode(' ', trim($message));
    if (count($words) <= 3 && preg_match('/^[A-ZÇĞİÖŞÜ\s]+$/u', $message)) {
        $result = getCustomerQuery($conn, $firma_id, $message);
        if (strpos($result, 'bulunamadı') === false) {
            return $result; // Müşteri bulunduysa döndür
        }
        // Bulunamadıysa AI'ya devam et
    }
    
    // AI'ya sor (timeout ile)
    try {
        // Hızlı yanıt için zaman limiti
        $start_time = microtime(true);
        
        $ai_engine = new AIChatEngine($conn, $firma_id, 1); // firma_id ve personel_id
        $result = $ai_engine->chat($message);
        
        $elapsed = microtime(true) - $start_time;
        error_log("AI query süresi: " . round($elapsed, 2) . " saniye");
        
        if ($result['success']) {
            // Kötü cevap kontrolü
            $response_text = '';
            if (!empty($result['data'])) {
                $response_text = formatResultsForWhatsApp($result['data'], $result['explanation']);
            } else {
                $response_text = $result['explanation'] ?: "Sorgunuz çalıştırıldı ancak sonuç bulunamadı.";
            }
            
            // Kötü yanıt tespiti - cache'den sil
            if (isBadResponse($response_text, $message)) {
                invalidateAICache($conn, $message, $firma_id);
                error_log("Kötü yanıt tespit edildi, cache temizlendi: " . substr($message, 0, 50));
                return "🤔 Üzgünüm, bu soruya iyi bir cevap veremedim. Lütfen soruyu farklı şekilde sorar mısınız?\n\n💡 Örnek: 'Keçeci müşterisinin son 3 fason işi'";
            }
            
            // ✅ Başarılı sorgu - Otomatik öğrenme tetikle (background)
            // Her 10 başarılı sorguda bir self-learning çalıştır
            if (rand(1, 10) === 1) {
                // Background'da çalıştır (kullanıcıyı bekleme)
                exec('php /var/www/html/ai_self_learning.php run ' . $firma_id . ' > /dev/null 2>&1 &');
            }
            
            return $response_text;
        } else {
            return "Üzgünüm, sorunuzu anlayamadım. /help yazarak komutları görebilirsiniz.";
        }
    } catch (Exception $e) {
        error_log("AI işleme hatası: " . $e->getMessage());
        return "⚠️ Sorgunuz işleniyor ancak zaman aldı. Lütfen /siparisler veya /planlama komutlarını kullanın.";
    }
}

/**
 * Müşteri sorgusu (basit pattern)
 */
function getCustomerQuery($conn, $firma_id, $message) {
    // Müşteri adını çıkar
    $musteri_adi = '';
    if (preg_match('/helmex/i', $message)) {
        $musteri_adi = 'helmex';
    }
    
    if (empty($musteri_adi)) {
        return "Hangi müşteri için bilgi istiyorsunuz? Örnek: 'Helmex işleri'";
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                s.siparis_no,
                s.isin_adi,
                s.islem,
                s.termin,
                s.adet,
                m.firma_unvani
            FROM siparisler s
            JOIN musteri m ON s.musteri_id = m.id
            WHERE s.firma_id = :firma_id 
              AND (m.firma_unvani LIKE :musteri OR m.marka LIKE :musteri)
              AND s.islem NOT IN ('iptal', 'teslim_edildi')
            ORDER BY s.tarih DESC
            LIMIT 5
        ");
        $stmt->execute([
            ':firma_id' => $firma_id,
            ':musteri' => '%' . $musteri_adi . '%'
        ]);
        $siparisler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($siparisler)) {
            return "🔍 '{$musteri_adi}' için aktif sipariş bulunamadı.";
        }
        
        $musteri_tam_adi = $siparisler[0]['firma_unvani'];
        $mesaj = "📋 *{$musteri_tam_adi}*\n\n";
        foreach ($siparisler as $s) {
            $durum_emoji = $s['islem'] == 'tamamlandi' ? '✅' : '🔄';
            $mesaj .= "{$durum_emoji} *{$s['siparis_no']}*\n";
            $mesaj .= "  {$s['isin_adi']}\n";
            $mesaj .= "  Durum: {$s['islem']}\n";
            $mesaj .= "  Adet: {$s['adet']}\n";
            $mesaj .= "  Termin: {$s['termin']}\n\n";
        }
        
        return $mesaj;
    } catch (Exception $e) {
        error_log("Müşteri sorgu hatası: " . $e->getMessage());
        return "Müşteri bilgisi alınamadı: " . $e->getMessage();
    }
}

/**
 * Yardım mesajı
 */
function getHelpMessage() {
    return "🤖 *HANKA AI Assistant*\n\n" .
           "Komutlar:\n" .
           "• /siparisler - Sipariş özeti\n" .
           "• /planlama - Planlama durumu\n" .
           "• /help veya ? - Bu mesaj\n\n" .
           "Sorularınızı doğal dilde yazabilirsiniz:\n" .
           "• 'Bugün kaç sipariş var?'\n" .
           "• 'Helmex işleri'\n" .
           "• 'Termin geçmiş siparişler'";
}

/**
 * Sipariş özeti
 */
function getSiparislerOzet($conn, $firma_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as toplam,
                SUM(CASE WHEN islem = 'islemde' THEN 1 ELSE 0 END) as islemde,
                SUM(CASE WHEN termin < CURDATE() AND islem NOT IN ('tamamlandi', 'teslim_edildi') THEN 1 ELSE 0 END) as geciken
            FROM siparisler 
            WHERE firma_id = :firma_id AND islem != 'iptal'
        ");
        $stmt->execute([':firma_id' => $firma_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return "📊 *Sipariş Özeti*\n\n" .
               "Toplam: {$result['toplam']}\n" .
               "İşlemde: {$result['islemde']}\n" .
               "⚠️ Geciken: {$result['geciken']}";
    } catch (Exception $e) {
        return "Sipariş bilgisi alınamadı.";
    }
}

/**
 * Entity cache yükle (müşteriler ve tedarikçiler)
 */
function loadEntityCache($conn, $firma_id) {
    global $ENTITY_CACHE;
    
    // Cache var mı kontrol
    if (isset($ENTITY_CACHE[$firma_id])) {
        return $ENTITY_CACHE[$firma_id];
    }
    
    $cache = [
        'musteriler' => [],
        'tedarikciler' => []
    ];
    
    try {
        // Müşterileri yükle
        $stmt = $conn->prepare("
            SELECT id, firma_unvani, marka 
            FROM musteri 
            WHERE firma_id = :firma_id 
            ORDER BY firma_unvani
        ");
        $stmt->execute([':firma_id' => $firma_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cache['musteriler'][] = [
                'id' => $row['id'],
                'ad' => $row['firma_unvani'],
                'marka' => $row['marka'],
                'keywords' => extractKeywords($row['firma_unvani'])
            ];
        }
        
        // Tedarikçileri yükle
        $stmt = $conn->prepare("
            SELECT id, tedarikci_unvani 
            FROM tedarikciler 
            ORDER BY tedarikci_unvani
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cache['tedarikciler'][] = [
                'id' => $row['id'],
                'ad' => $row['tedarikci_unvani'],
                'keywords' => extractKeywords($row['tedarikci_unvani'])
            ];
        }
        
        $ENTITY_CACHE[$firma_id] = $cache;
        error_log("Entity cache yüklendi: " . count($cache['musteriler']) . " müşteri, " . count($cache['tedarikciler']) . " tedarikçi");
        
    } catch (Exception $e) {
        error_log("Entity cache hatası: " . $e->getMessage());
    }
    
    return $cache;
}

/**
 * İsimden anahtar kelimeleri çıkar
 */
function extractKeywords($text) {
    // Önce noktaları kaldır (AMB. → AMB)
    $text = str_replace('.', ' ', $text);
    
    // Yaygın ekleri çıkar
    $stopwords = ['LTD', 'ŞTİ', 'STI', 'AŞ', 'AS', 'TİC', 'TIC', 'SAN', 'DIŞ', 'DIS', 'TİCARET', 'TICARET', 
                  'İNŞAAT', 'INSAAT', 'GIDA', 'TEKSTİL', 'TEKSTIL', 'AMB', 'VE', 'KUTU', 'LİMİTED', 'LIMITED', 'ŞİRKETİ', 'SIRKETI'];
    
    // Noktalama temizle
    $text = preg_replace('/[^\p{L}\s]/u', ' ', $text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    $text = mb_strtoupper($text, 'UTF-8');
    
    // Kelimelere ayır
    $words = explode(' ', $text);
    
    // 3+ harfli ve stopword olmayan kelimeleri al
    $keywords = array_filter($words, function($w) use ($stopwords) {
        return mb_strlen($w, 'UTF-8') >= 3 && !in_array($w, $stopwords);
    });
    
    return array_values($keywords);
}

/**
 * Entity bul (akıllı eşleştirme)
 */
function findEntity($message, $cache, $type = 'musteri') {
    $entities = ($type === 'musteri') ? $cache['musteriler'] : $cache['tedarikciler'];
    
    $message_upper = mb_strtoupper($message, 'UTF-8');
    $best_match = null;
    $best_score = 0;
    
    foreach ($entities as $entity) {
        $score = 0;
        
        // Tam isim geçiyor mu?
        if (stripos($message_upper, mb_strtoupper($entity['ad'], 'UTF-8')) !== false) {
            return $entity; // Tam eşleşme
        }
        
        // Keyword eşleşmesi
        foreach ($entity['keywords'] as $keyword) {
            if (stripos($message_upper, $keyword) !== false) {
                $score += strlen($keyword);
            }
        }
        
        // Marka eşleşmesi (müşteriler için)
        if ($type === 'musteri' && !empty($entity['marka'])) {
            if (stripos($message_upper, mb_strtoupper($entity['marka'], 'UTF-8')) !== false) {
                $score += 20;
            }
        }
        
        if ($score > $best_score) {
            $best_score = $score;
            $best_match = $entity;
        }
    }
    
    // En az 4 karakter eşleşmesi gerekli
    return ($best_score >= 4) ? $best_match : null;
}

/**
 * Kötü yanıt kontrolü
 */
function isBadResponse($response, $original_question) {
    // Kötü yanıt pattern'leri
    $bad_patterns = [
        '/yerine.*deneyebilirsiniz/i',  // "X yerine Y deneyebilirsiniz"
        '/daha spesifik.*olabilir/i',    // "Daha spesifik olabilir misiniz"
        '/anlayamadım/i',                 // "Anlayamadım"
        '/yeterli bilgi.*yok/i',          // "Yeterli bilgi yok"
        '/hata.*oluştu/i',                // "Hata oluştu"
        '/bulunamadı.*lütfen/i',          // "Bulunamadı, lütfen..."
        '/sorgunuz.*hatalı/i',            // "Sorgunuz hatalı"
    ];
    
    foreach ($bad_patterns as $pattern) {
        if (preg_match($pattern, $response)) {
            return true;
        }
    }
    
    // Çok kısa cevaplar (< 20 karakter)
    if (strlen(trim(strip_tags($response))) < 20) {
        return true;
    }
    
    return false;
}

/**
 * AI cache'i invalidate et
 */
function invalidateAICache($conn, $question, $firma_id) {
    try {
        $stmt = $conn->prepare("
            UPDATE ai_cache 
            SET is_valid = 0, invalidated_at = NOW() 
            WHERE original_question = :question 
              AND firma_id = :firma_id 
              AND is_valid = 1
        ");
        $stmt->execute([
            ':question' => $question,
            ':firma_id' => $firma_id
        ]);
        
        // Silme de yapabiliriz
        $stmt = $conn->prepare("
            DELETE FROM ai_cache 
            WHERE original_question = :question 
              AND firma_id = :firma_id
        ");
        $stmt->execute([
            ':question' => $question,
            ':firma_id' => $firma_id
        ]);
    } catch (Exception $e) {
        error_log("Cache invalidation hatası: " . $e->getMessage());
    }
}

/**
 * Müşteri sayısı (AI yanlış yapıyor, direkt SQL)
 */
function getMusteriSayisi($conn, $firma_id) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as toplam_musteri FROM musteri WHERE firma_id = :firma_id");
        $stmt->execute([':firma_id' => $firma_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return "━━━━━━━━━\n*toplam_musteri*: {$result['toplam_musteri']} adet\n\n💡 _Sistemde kayıtlı tüm müşteriler_";
    } catch (Exception $e) {
        return "Müşteri sayısı alınamadı.";
    }
}

/**
 * Fason işler (müşteri ID'ye göre)
 */
function getFasonIslerByMusteriId($conn, $firma_id, $musteri_id, $musteri_adi) {
    try {
        $stmt = $conn->prepare("
            SELECT p.id, p.isim, s.siparis_no
            FROM planlama p 
            JOIN siparisler s ON p.siparis_id = s.id 
            WHERE s.musteri_id = :musteri_id
              AND p.fason_tedarikciler IS NOT NULL 
              AND p.fason_tedarikciler != ''
              AND p.firma_id = :firma_id 
            ORDER BY p.id DESC 
            LIMIT 5
        ");
        $stmt->execute([
            ':musteri_id' => $musteri_id,
            ':firma_id' => $firma_id
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return "Bu müşteri için fason iş bulunamadı.";
        }
        
        $message = "🏭 *Fason İşler (Müşteri Bazlı)*\n\n";
        $message .= "*Müşteri*: " . $musteri_adi . "\n\n";
        
        foreach ($results as $i => $row) {
            $message .= "━━━━━━━━━\n";
            $message .= ($i + 1) . ". " . substr($row['isim'], 0, 60) . "\n";
            $message .= "📋 *Sipariş*: {$row['siparis_no']}\n";
        }
        
        return $message;
    } catch (Exception $e) {
        error_log("Fason işler (müşteri ID) hatası: " . $e->getMessage());
        return "Fason iş bilgisi alınamadı.";
    }
}

/**
 * Fason işler (müşteriye göre) - DEPRECATED, pattern için
 */
function getFasonIslerByMusteri($conn, $firma_id, $musteri_pattern) {
    try {
        $stmt = $conn->prepare("
            SELECT p.id, p.isim, s.siparis_no, m.firma_unvani
            FROM planlama p 
            JOIN siparisler s ON p.siparis_id = s.id 
            JOIN musteri m ON s.musteri_id = m.id 
            WHERE m.firma_unvani LIKE :musteri 
              AND p.fason_tedarikciler IS NOT NULL 
              AND p.fason_tedarikciler != ''
              AND p.firma_id = :firma_id 
            ORDER BY p.id DESC 
            LIMIT 5
        ");
        $stmt->execute([
            ':musteri' => $musteri_pattern,
            ':firma_id' => $firma_id
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return "Bu müşteri için fason iş bulunamadı.";
        }
        
        $message = "🏭 *Fason İşler (Müşteri Bazlı)*\n\n";
        $message .= "*Müşteri*: " . $results[0]['firma_unvani'] . "\n\n";
        
        foreach ($results as $i => $row) {
            $message .= "━━━━━━━━━\n";
            $message .= ($i + 1) . ". " . substr($row['isim'], 0, 60) . "\n";
            $message .= "📋 *Sipariş*: {$row['siparis_no']}\n";
        }
        
        return $message;
    } catch (Exception $e) {
        error_log("Fason işler (müşteri) hatası: " . $e->getMessage());
        return "Fason iş bilgisi alınamadı.";
    }
}

/**
 * Fason işler (tedarikçi ID'ye göre)
 */
function getFasonIslerByTedarikciId($conn, $firma_id, $tedarikci_id, $tedarikci_adi) {
    try {
        // Tedarikçideki işleri bul
        $stmt = $conn->prepare("
            SELECT p.id, p.isim, s.siparis_no, m.firma_unvani
            FROM planlama p 
            JOIN siparisler s ON p.siparis_id = s.id 
            JOIN musteri m ON s.musteri_id = m.id 
            WHERE p.fason_tedarikciler LIKE :tedarikci_id
              AND p.firma_id = :firma_id 
            ORDER BY p.id DESC 
            LIMIT 5
        ");
        $stmt->execute([
            ':tedarikci_id' => '%' . $tedarikci_id . '%',
            ':firma_id' => $firma_id
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return "🔍 *{$tedarikci_adi}* tedarikçisinde fason iş bulunamadı.";
        }
        
        $message = "🏭 *Fason İşler (Tedarikçi Bazlı)*\n\n";
        $message .= "*Tedarikçi*: " . $tedarikci_adi . "\n\n";
        
        foreach ($results as $i => $row) {
            $message .= "━━━━━━━━━\n";
            $message .= ($i + 1) . ". " . substr($row['isim'], 0, 50) . "...\n";
            $message .= "👤 *Müşteri*: " . substr($row['firma_unvani'], 0, 30) . "\n";
            $message .= "📋 *Sipariş*: {$row['siparis_no']}\n";
        }
        
        return $message;
    } catch (Exception $e) {
        error_log("Fason işler (tedarikçi ID) hatası: " . $e->getMessage());
        return "Fason iş bilgisi alınamadı.";
    }
}

/**
 * Fason işler (tedarikçiye göre) - DEPRECATED, pattern için
 */
function getFasonIslerByTedarikci($conn, $firma_id, $tedarikci_pattern) {
    try {
        // Önce tedarikçiyi bul
        $stmt = $conn->prepare("
            SELECT id, tedarikci_unvani 
            FROM tedarikciler 
            WHERE tedarikci_unvani LIKE :tedarikci 
            LIMIT 1
        ");
        $stmt->execute([':tedarikci' => $tedarikci_pattern]);
        $tedarikci = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tedarikci) {
            return "Bu tedarikçi bulunamadı. Lütfen tedarikçi adını kontrol edin.";
        }
        
        // Tedarikçideki işleri bul
        $stmt = $conn->prepare("
            SELECT p.id, p.isim, s.siparis_no, m.firma_unvani
            FROM planlama p 
            JOIN siparisler s ON p.siparis_id = s.id 
            JOIN musteri m ON s.musteri_id = m.id 
            WHERE p.fason_tedarikciler LIKE :tedarikci_id
              AND p.firma_id = :firma_id 
            ORDER BY p.id DESC 
            LIMIT 5
        ");
        $stmt->execute([
            ':tedarikci_id' => '%' . $tedarikci['id'] . '%',
            ':firma_id' => $firma_id
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return "🔍 *{$tedarikci['tedarikci_unvani']}* tedarikçisinde fason iş bulunamadı.";
        }
        
        $message = "🏭 *Fason İşler (Tedarikçi Bazlı)*\n\n";
        $message .= "*Tedarikçi*: " . $tedarikci['tedarikci_unvani'] . "\n\n";
        
        foreach ($results as $i => $row) {
            $message .= "━━━━━━━━━\n";
            $message .= ($i + 1) . ". " . substr($row['isim'], 0, 50) . "...\n";
            $message .= "👤 *Müşteri*: " . $row['firma_unvani'] . "\n";
            $message .= "📋 *Sipariş*: {$row['siparis_no']}\n";
        }
        
        return $message;
    } catch (Exception $e) {
        error_log("Fason işler (tedarikçi) hatası: " . $e->getMessage());
        return "Fason iş bilgisi alınamadı.";
    }
}

/**
 * Makina helper - makinayı bul
 */
function findMakina($conn, $firma_id, $makina_pattern) {
    $stmt = $conn->prepare("
        SELECT id, makina_adi 
        FROM makinalar 
        WHERE makina_adi LIKE :pattern 
        AND firma_id = :firma_id
        LIMIT 1
    ");
    $stmt->execute([
        ':pattern' => $makina_pattern,
        ':firma_id' => $firma_id
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 1. Makinada en yüksek adetli iş
 */
function getMakinaEnYuksekAdetIsi($conn, $firma_id, $makina_pattern) {
    try {
        $makina = findMakina($conn, $firma_id, $makina_pattern);
        
        if (!$makina) {
            $clean_name = str_replace('%', '', $makina_pattern);
            return "❌ '{$clean_name}' makinası bulunamadı.";
        }
        
        $stmt = $conn->prepare("
            SELECT p.id, p.isim, s.siparis_no, s.adet, m.firma_unvani
            FROM planlama p
            JOIN siparisler s ON p.siparis_id = s.id
            JOIN musteri m ON s.musteri_id = m.id
            WHERE JSON_CONTAINS(p.makinalar, CAST(:makina_id AS JSON))
            AND p.firma_id = :firma_id
            ORDER BY s.adet DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':makina_id' => $makina['id'],
            ':firma_id' => $firma_id
        ]);
        $is = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$is) {
            return "📊 *{$makina['makina_adi']}* makinasında iş bulunamadı.";
        }
        
        $response = "🏭 *EN YÜKSEK ADETLİ İŞ*\n";
        $response .= "━━━━━━━━━━━━━━━━━━━━\n";
        $response .= "Makina: *{$makina['makina_adi']}*\n\n";
        $response .= "📦 *{$is['siparis_no']}*\n";
        $response .= "İş: " . substr($is['isim'], 0, 50) . "\n";
        $response .= "Müşteri: {$is['firma_unvani']}\n";
        $response .= "Adet: *" . number_format($is['adet'], 0, ',', '.') . " adet*\n";
        
        return $response;
        
    } catch (Exception $e) {
        error_log("getMakinaEnYuksekAdetIsi Error: " . $e->getMessage());
        return "❌ Makina iş bilgisi alınırken hata oluştu.";
    }
}

/**
 * 2. Makinada en düşük adetli iş
 */
function getMakinaEnDusukAdetIsi($conn, $firma_id, $makina_pattern) {
    try {
        $makina = findMakina($conn, $firma_id, $makina_pattern);
        
        if (!$makina) {
            $clean_name = str_replace('%', '', $makina_pattern);
            return "❌ '{$clean_name}' makinası bulunamadı.";
        }
        
        $stmt = $conn->prepare("
            SELECT p.id, p.isim, s.siparis_no, s.adet, m.firma_unvani
            FROM planlama p
            JOIN siparisler s ON p.siparis_id = s.id
            JOIN musteri m ON s.musteri_id = m.id
            WHERE JSON_CONTAINS(p.makinalar, CAST(:makina_id AS JSON))
            AND p.firma_id = :firma_id
            ORDER BY s.adet ASC
            LIMIT 1
        ");
        $stmt->execute([
            ':makina_id' => $makina['id'],
            ':firma_id' => $firma_id
        ]);
        $is = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$is) {
            return "📊 *{$makina['makina_adi']}* makinasında iş bulunamadı.";
        }
        
        $response = "🏭 *EN DÜŞÜK ADETLİ İŞ*\n";
        $response .= "━━━━━━━━━━━━━━━━━━━━\n";
        $response .= "Makina: *{$makina['makina_adi']}*\n\n";
        $response .= "📦 *{$is['siparis_no']}*\n";
        $response .= "İş: " . substr($is['isim'], 0, 50) . "\n";
        $response .= "Müşteri: {$is['firma_unvani']}\n";
        $response .= "Adet: *" . number_format($is['adet'], 0, ',', '.') . " adet*\n";
        
        return $response;
        
    } catch (Exception $e) {
        error_log("getMakinaEnDusukAdetIsi Error: " . $e->getMessage());
        return "❌ Makina iş bilgisi alınırken hata oluştu.";
    }
}

/**
 * 3. Makinada kaç iş var
 */
function getMakinaIsSayisi($conn, $firma_id, $makina_pattern) {
    try {
        $makina = findMakina($conn, $firma_id, $makina_pattern);
        
        if (!$makina) {
            $clean_name = str_replace('%', '', $makina_pattern);
            return "❌ '{$clean_name}' makinası bulunamadı.";
        }
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as is_sayisi
            FROM planlama p
            WHERE JSON_CONTAINS(p.makinalar, CAST(:makina_id AS JSON))
            AND p.firma_id = :firma_id
        ");
        $stmt->execute([
            ':makina_id' => $makina['id'],
            ':firma_id' => $firma_id
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response = "🏭 *MAKİNA İŞ SAYISI*\n";
        $response .= "━━━━━━━━━━━━━━━━━━━━\n";
        $response .= "Makina: *{$makina['makina_adi']}*\n";
        $response .= "Toplam İş: *{$result['is_sayisi']} adet*\n";
        
        return $response;
        
    } catch (Exception $e) {
        error_log("getMakinaIsSayisi Error: " . $e->getMessage());
        return "❌ Makina iş sayısı alınırken hata oluştu.";
    }
}

/**
 * 4. Makinada toplam üretim
 */
function getMakinaToplamUretim($conn, $firma_id, $makina_pattern) {
    try {
        $makina = findMakina($conn, $firma_id, $makina_pattern);
        
        if (!$makina) {
            $clean_name = str_replace('%', '', $makina_pattern);
            return "❌ '{$clean_name}' makinası bulunamadı.";
        }
        
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as is_sayisi,
                SUM(s.adet) as toplam_adet
            FROM planlama p
            JOIN siparisler s ON p.siparis_id = s.id
            WHERE JSON_CONTAINS(p.makinalar, CAST(:makina_id AS JSON))
            AND p.firma_id = :firma_id
        ");
        $stmt->execute([
            ':makina_id' => $makina['id'],
            ':firma_id' => $firma_id
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response = "🏭 *MAKİNA TOPLAM ÜRETİM*\n";
        $response .= "━━━━━━━━━━━━━━━━━━━━\n";
        $response .= "Makina: *{$makina['makina_adi']}*\n";
        $response .= "Toplam İş: *{$result['is_sayisi']} adet*\n";
        $response .= "Toplam Üretim: *" . number_format($result['toplam_adet'] ?? 0, 0, ',', '.') . " adet*\n";
        
        return $response;
        
    } catch (Exception $e) {
        error_log("getMakinaToplamUretim Error: " . $e->getMessage());
        return "❌ Makina üretim bilgisi alınırken hata oluştu.";
    }
}

/**
 * Planlama özeti
 */
function getPlanlamaOzet($conn, $firma_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as toplam_plan,
                SUM(CASE WHEN p.onay_durum = 'evet' THEN 1 ELSE 0 END) as onaylanan,
                SUM(CASE WHEN p.onay_durum = 'hayır' THEN 1 ELSE 0 END) as bekleyen
            FROM planlama p
            JOIN siparisler s ON p.siparis_id = s.id
            WHERE p.firma_id = :firma_id 
              AND s.islem NOT IN ('tamamlandi', 'iptal')
        ");
        $stmt->execute([':firma_id' => $firma_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return "📅 *Planlama Durumu*\n\n" .
               "Toplam Plan: {$result['toplam_plan']}\n" .
               "Onaylanmış: {$result['onaylanan']}\n" .
               "⏳ Bekleyen: {$result['bekleyen']}";
    } catch (Exception $e) {
        return "Planlama bilgisi alınamadı.";
    }
}

/**
 * SQL sonuçlarını WhatsApp için formatla
 */
function formatResultsForWhatsApp($data, $explanation = '') {
    $message = "";
    
    if ($explanation) {
        $message .= "💬 " . $explanation . "\n\n";
    }
    
    if (count($data) === 0) {
        return $message . "Sonuç bulunamadı.";
    }
    
    // İlk 5 satırı göster
    $limit = min(5, count($data));
    
    // Tek satır tek değer ise (örn: COUNT(*)) - birimli göster
    if (count($data) === 1 && count($data[0]) === 1) {
        $row = $data[0];
        $key = key($row);
        $value = current($row);
        
        // Birim belirle
        $birim = '';
        if (stripos($key, 'sayisi') !== false || stripos($key, 'adet') !== false || stripos($key, 'count') !== false) {
            $birim = ' adet';
        } elseif (stripos($key, 'tutar') !== false || stripos($key, 'fiyat') !== false || stripos($key, 'toplam') !== false) {
            $birim = ' TL';
        } elseif (stripos($key, 'miktar') !== false) {
            $birim = ' kg'; // veya ünite
        } elseif (stripos($key, 'oran') !== false || stripos($key, 'yuzde') !== false) {
            $birim = '%';
        }
        
        $message .= "━━━━━━━━━\n";
        $message .= "*{$key}*: {$value}{$birim}\n";
        
        return $message;
    }
    
    // Çoklu satır sonuçlar
    for ($i = 0; $i < $limit; $i++) {
        $row = $data[$i];
        $message .= "━━━━━━━━━\n";
        
        foreach ($row as $key => $value) {
            // Birim belirle
            $birim = '';
            if (stripos($key, 'sayisi') !== false || stripos($key, 'adet') !== false) {
                $birim = ' adet';
            } elseif (stripos($key, 'tutar') !== false || stripos($key, 'fiyat') !== false) {
                $birim = ' TL';
            } elseif (stripos($key, 'miktar') !== false) {
                $birim = ' kg';
            } elseif (stripos($key, 'oran') !== false || stripos($key, 'yuzde') !== false) {
                $birim = '%';
            }
            
            // Uzun değerleri kısalt
            if (strlen($value) > 50) {
                $value = substr($value, 0, 47) . '...';
            }
            $message .= "*{$key}*: {$value}{$birim}\n";
        }
    }
    
    if (count($data) > $limit) {
        $message .= "\n_(" . (count($data) - $limit) . " kayıt daha var)_";
    }
    
    return $message;
}

// Tedarikçi bazlı fason iş SAYISI getir
function getFasonIslerCountByTedarikci($conn, $firma_id, $tedarikci_pattern) {
    try {
        // Önce tedarikçiyi bul
        $stmt = $conn->prepare("
            SELECT id, tedarikci_unvani 
            FROM tedarikciler 
            WHERE tedarikci_unvani LIKE :tedarikci 
            AND firma_id = :firma_id
            LIMIT 1
        ");
        $stmt->execute([
            ':tedarikci' => $tedarikci_pattern,
            ':firma_id' => $firma_id
        ]);
        $tedarikci = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tedarikci) {
            return "❌ Bu tedarikçi bulunamadı: " . str_replace('%', '', $tedarikci_pattern);
        }
        
        // Tedarikçideki işleri say
        $stmt = $conn->prepare("
            SELECT COUNT(*) as toplam
            FROM planlama p 
            JOIN siparisler s ON p.siparis_id = s.id 
            WHERE p.fason_tedarikciler LIKE :tedarikci_id
              AND p.firma_id = :firma_id
        ");
        $stmt->execute([
            ':tedarikci_id' => '%' . $tedarikci['id'] . '%',
            ':firma_id' => $firma_id
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['toplam'];
        
        return "📊 *FASON İŞ SAYISI*\n━━━━━━━━━━━━━━━\n" .
               "Tedarikçi: *{$tedarikci['tedarikci_unvani']}*\n" .
               "Toplam İş: *{$count} adet*";
               
    } catch (Exception $e) {
        error_log("getFasonIslerCountByTedarikci Error: " . $e->getMessage());
        return "❌ Tedarikçi fason iş sayısı alınırken hata oluştu.";
    }
}

// Müşteri bazlı fason iş SAYISI getir
function getFasonIslerCountByMusteri($conn, $firma_id, $musteri_pattern) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as toplam
            FROM planlama p
            JOIN siparisler s ON p.siparis_id = s.id
            JOIN musteri m ON s.musteri_id = m.id
            WHERE p.firma_id = :firma_id
              AND m.firma_unvani LIKE :pattern
              AND p.fason_tedarikciler IS NOT NULL
              AND p.fason_tedarikciler != ''
              AND p.fason_tedarikciler != '[]'
        ");
        $stmt->execute([
            ':firma_id' => $firma_id,
            ':pattern' => $musteri_pattern
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['toplam'];
        
        $musteri_adi = str_replace('%', '', $musteri_pattern);
        
        return "📊 *FASON İŞ SAYISI*\n━━━━━━━━━━━━━━━\n" .
               "Müşteri: *{$musteri_adi}*\n" .
               "Toplam İş: *{$count} adet*";
               
    } catch (Exception $e) {
        error_log("getFasonIslerCountByMusteri Error: " . $e->getMessage());
        return "❌ Müşteri fason iş sayısı alınırken hata oluştu.";
    }
}

/**
 * WhatsApp mesajını logla
 */
function logWhatsAppMessage($conn, $phone, $message, $response, $message_sid, $firma_id) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO whatsapp_messages (
                from_number, 
                body, 
                response, 
                message_sid,
                firma_id,
                created_at
            )
            VALUES (:from, :message, :response, :message_sid, :firma_id, NOW())
        ");
        $stmt->execute([
            ':from' => $phone,
            ':message' => $message,
            ':response' => $response,
            ':message_sid' => $message_sid,
            ':firma_id' => $firma_id
        ]);
    } catch (Exception $e) {
        error_log("WhatsApp message log hatası: " . $e->getMessage());
    }
}
