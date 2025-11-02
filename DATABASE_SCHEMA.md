# HANKA SYS SAAS - Veritabanı Şeması Dokümantasyonu

## 📊 Genel Bakış

**Database**: `panelhankasys_crm2`  
**Toplam Tablo**: 94  
**Charset**: utf8mb4  
**Connection**: PDO  
**Credentials**: `hanka_user` / `HankaDB2025!`

---

## 🗂️ Tablo Kategorileri

### 1️⃣ Core System (Sistem Çekirdeği)
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `firmalar` | Multi-tenant ana tablo | id, firma_adi, logo, created_at |
| `personeller` | Kullanıcı hesapları | id, firma_id, username, sifre, yetki |
| `personel_sayfa_yetki` | Sayfa bazlı erişim | personel_id, sayfa_id, yetki_id |
| `giris_log` | Login geçmişi | personel_id, ip, tarih |
| `bildirimler` | Sistem bildirimleri | firma_id, mesaj, okundu, tarih |
| `sayfalar` | Sayfa tanımları | id, sayfa_adi, url |
| `yetkiler` | Yetki seviyeleri | id, yetki_adi (admin, user, viewer) |

### 2️⃣ Müşteri & Satış Yönetimi
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `musteri` | Ana müşteri kayıtları | id, firma_id, firma_unvani, vergi_no, sektor_id |
| `musteri_adresleri` | Teslimat adresleri | musteri_id, adres, ulke_id, sehir_id, ilce_id |
| `musteri_yetkilileri` | İletişim kişileri | musteri_id, adi_soyadi, cep_tel, email, gorev |
| `sektorler` | Sektör tanımları | id, sektor_adi |

### 3️⃣ Sipariş Sistemi ⭐ (JSON Tabanlı Esnek Yapı)
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `siparisler` | **Ana sipariş tablosu** | id, firma_id, musteri_id, siparis_no, **veriler (JSON)**, termin, fiyat, durum |
| `siparis_form_tipleri` | Özel form şablonları | id, firma_id, form_adi, alanlar (JSON) |
| `siparis_form_tip_degerler` | Form değerleri | siparis_id, form_tip_id, degerler (JSON) |
| `siparis_dosyalar` | Sipariş ekleri | siparis_id, dosya_yolu |
| `siparis_log` | Durum değişiklikleri | siparis_id, eski_durum, yeni_durum, tarih |
| `teslim_edilenler` | Teslimat kayıtları | siparis_id, teslim_tarih, teslim_alan |

**`siparisler.veriler` JSON Yapısı Örneği:**
```json
{
  "urun_adi": "Plastik Kalıp",
  "miktar": 500,
  "olcu": "120x80mm",
  "malzeme": "ABS Plastik",
  "renk": "Beyaz RAL 9003",
  "ozel_notlar": "Logo baskısı yapılacak",
  "form_alanlari": {
    "paketleme_turu": "Karton kutu",
    "etiket": "Var"
  }
}
```

### 4️⃣ Stok & Malzeme Yönetimi
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `stok_kalemleri` | Ana stok kartları | id, firma_id, stok_kalem (kod) |
| `stok_alt_kalemler` | Stok detayları | stok_kalem_id, alt_kalem_adi |
| `stok_alt_kalem_degerler` | Özellikler | alt_kalem_id, alan_adi, deger |
| `stok_alt_depolar` | Depo bazlı stok | stok_kalem_id, depo_id, miktar |
| `stok_alt_depolar_kullanilanlar` | Kullanım kayıtları | alt_depo_id, siparis_id, kullanilan_miktar |
| `birimler` | Ölçü birimleri | id, birim_adi (adet, kg, m, kg vb) |
| `arsiv_kalemler` | Arşiv malzemeler | id, firma_id, kalem_adi |
| `arsiv_altlar` | Arşiv alt kategoriler | kalem_id, alt_adi |

### 5️⃣ Üretim & Makina Yönetimi
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `makinalar` | Makina tanımları | id, firma_id, makina_adi, departman_id, durumu |
| `makina_personeller` | Makina operatörleri | makina_id, personel_id |
| `makina_bakim_log` | Bakım kayıtları | makina_id, bakim_tarih, aciklama |
| `makina_bakim_personeller` | Bakım görevlileri | bakim_id, personel_id |
| `makina_is_buttonlar` | İş butonları (durum) | id, button_adi, renk |
| `makina_is_buttonlar_firma_ayarlar` | Firma özel butonlar | firma_id, button_id, aktif |
| `planlama` | Üretim planı | siparis_id, makina_id, baslangic, bitis |
| `departmanlar` | Üretim bölümleri | id, firma_id, departman_adi |
| `departman_planlama` | Departman iş planı | departman_id, siparis_id, tarih |

### 6️⃣ Üretim Takip Logları (Detaylı)
| Tablo | Açıklama | Ne Zaman Oluşur |
|-------|----------|-----------------|
| `uretim_islem_tarihler` | İşlem başlangıç/bitiş | Her üretim adımında |
| `uretim_aktarma_loglar` | Makina arası transfer | İş aktarımında |
| `uretim_mevcut_asamada_aktarilan` | Aşama geçişleri | Aşama değişiminde |
| `uretim_ariza_log` | Arıza kayıtları | Makina arızasında |
| `uretim_bakim_log` | Bakım kayıtları | Bakım yapıldığında |
| `uretim_mola_log` | Mola kayıtları | Mola verildiğinde |
| `uretim_yemek_mola_log` | Yemek molası | Yemek molasında |
| `uretim_paydos_loglar` | Vardiya sonu | İş bitişinde |
| `uretim_toplanti_log` | Toplantı kayıtları | Toplantıya gidildiğinde |
| `uretim_mesaj_log` | İşçi mesajları | Mesaj gönderildiğinde |
| `uretim_yetkili_log` | Yönetici notları | Yönetici notu girildiğinde |
| `uretim_makina_ayar_log` | Ayar süreleri | Makina ayarlandığında |
| `uretim_degistir_loglar` | İş değişiklikleri | İş değiştirildiğinde |
| `uretim_makina_devretme_sebebi_loglar` | Devir nedenleri | İş devredildiğinde |
| `uretim_fason_durum_loglar` | Fason takip | Fason çıkışında |
| `uretim_eksik_uretilen_loglar` | Eksik üretim | Fire/eksik kayıt |
| `uretilen_adetler` | Üretim adetleri | Üretim tamamlanınca |
| `uretim_dosyalar` | İş dosyaları | Dosya eklendiğinde |

### 7️⃣ AI & Agent System 🤖 (Yeni Eklendi)
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `ai_agent_settings` | Agent yapılandırması | firma_id, ai_enabled, agent_enabled, 27 ayar kolonu |
| `ai_cache` | Query cache | question_hash, answer, hit_count, is_valid |
| `ai_chat_history` | Konuşma geçmişi | firma_id, personel_id, question, answer |
| `ai_knowledge_base` | Vektör bilgi tabanı | firma_id, content, embedding, kategori |
| `ai_knowledge_base_vectors` | Vektör indexleri | knowledge_id, vector_data |
| `ai_database_schema` | DB metadata | table_name, column_name, data_type |
| `ai_table_relationships` | Tablo ilişkileri | parent_table, child_table, relationship_type |
| `ai_column_semantics` | Kolon anlamları | table_name, column_name, semantic_meaning |
| `ai_query_patterns` | Query pattern'leri | pattern, template, usage_count |
| `ai_prompts` | AI prompt şablonları | prompt_type, template, variables |
| `ai_log` | AI kullanım logları | personel_id, query, response_time |
| `ai_feedback` | Kullanıcı geri bildirimi | log_id, rating, comment |
| `ai_analiz_log` | Analiz logları | analiz_tipi, sonuc, tarih |

**Agent Tabloları:**
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `agent_alerts` | Sistem uyarıları | firma_id, alert_type, alert_level, title, message |
| `agent_task_queue` | Görev kuyruğu | task_type, priority, status, scheduled_at |
| `agent_action_log` | Agent aksiyonları | agent_name, action, status, execution_time |
| `agent_conversation_log` | Agent diyalogları | agent_id, conversation, context |
| `agent_performance_metrics` | Performans metrikleri | agent_name, success_rate, avg_response_time |
| `agent_scheduled_tasks` | Zamanlanmış görevler | task_name, cron_expression, last_run |

**`ai_agent_settings` Kolonları (27 Adet):**
```sql
id, firma_id, 
-- AI Settings
ai_enabled, ai_use_finetuned, ai_cache_enabled, ai_response_detail, openai_api_key,
-- Agent Settings
agent_enabled, agent_daily_report_time, agent_daily_report_enabled, 
agent_weekly_report_enabled, agent_weekly_report_day,
-- Alert Settings
alert_stock_enabled, alert_stock_threshold, 
alert_payment_enabled, alert_payment_days_before, 
alert_order_enabled,
-- Notification Settings
notification_email_enabled, notification_email_addresses, 
notification_whatsapp_enabled, notification_whatsapp_numbers,
-- TTS Settings
tts_enabled, tts_provider, tts_voice, tts_speed, tts_auto_play,
-- Timestamps
created_at, updated_at
```

### 8️⃣ Tedarikçi & Satın Alma
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `tedarikciler` | Tedarikçi kayıtları | id, firma_id, tedarikci_adi, iletisim |
| `tedarikci_planlama` | Satın alma planı | tedarikci_id, siparis_tarih, termin |

### 9️⃣ Raporlama Sistemi
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `raporlar` | Rapor tanımları | id, firma_id, rapor_adi, sql_query |
| `rapor_sablonlari` | Rapor şablonları | id, sablon_adi, kolonlar (JSON) |
| `rapor_ayarlari` | Kullanıcı rapor ayarları | personel_id, rapor_id, filtre (JSON) |

### 🔟 Referans Tabloları
| Tablo | Açıklama | İçerik |
|-------|----------|--------|
| `ulkeler` | Ülke listesi | id, ulke_adi |
| `sehirler` | Şehir listesi | id, ulke_id, sehir_adi |
| `ilceler` | İlçe listesi | id, sehir_id, ilce_adi |
| `kur` | Döviz kurları | tarih, dolar, euro, pound |
| `odeme_tipleri` | Ödeme yöntemleri | id, tip_adi (nakit, kredi kartı, havale) |
| `turler` | Sipariş türleri | id, tur_adi |

### 1️⃣1️⃣ Geri Bildirim & Destek
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `geri_bildirim` | Kullanıcı feedback | firma_id, personel_id, mesaj, kategori |
| `geri_bildirim_dosyalar` | Feedback ekleri | bildirim_id, dosya_yolu |
| `geri_bildirim_gorunum_durumu` | Okunma durumu | bildirim_id, personel_id, okundu |
| `hata_loglari` | Hata kayıtları | sayfa, hata_mesaj, tarih |

### 1️⃣2️⃣ API & FTP
| Tablo | Açıklama | Önemli Kolonlar |
|-------|----------|-----------------|
| `api_keys` | API anahtarları | firma_id, api_key, aktif, created_at |
| `ftp_ayarlar` | FTP yapılandırması | firma_id, host, username, password |

---

## 🔑 Önemli Tablo Detayları

### `siparisler` (Ana Sipariş Tablosu) ⭐

**Kolon Yapısı:**
```sql
CREATE TABLE siparisler (
  id INT PRIMARY KEY AUTO_INCREMENT,
  firma_id INT NOT NULL,
  musteri_id INT NOT NULL,
  siparis_no VARCHAR(20) NOT NULL,
  veriler JSON NOT NULL,  -- ⭐ Esnek veri yapısı
  tip_id TINYINT UNSIGNED NOT NULL,
  arsiv_kod MEDIUMINT UNSIGNED NOT NULL,
  isin_adi VARCHAR(255) NOT NULL,
  tur_id SMALLINT UNSIGNED NOT NULL,
  adet BIGINT UNSIGNED NOT NULL,
  birim_id INT NOT NULL,
  teslimat_adresi VARCHAR(255),
  ulke_id INT NOT NULL,
  sehir_id INT NOT NULL,
  ilce_id INT NOT NULL,
  termin DATE NOT NULL,
  uretim DATE NOT NULL,
  vade DATE NOT NULL,
  fiyat FLOAT NOT NULL,
  para_cinsi ENUM('TL','DOLAR','EURO','POUND') NOT NULL,
  odeme_sekli_id INT NOT NULL,
  numune ENUM('var','yok') DEFAULT 'yok',
  aciklama TEXT,
  islem ENUM('yeni','islemde','tamamlandi','teslim_edildi','iptal') DEFAULT 'yeni',
  durum TINYINT(1) DEFAULT 1,
  paketleme VARCHAR(250),
  nakliye VARCHAR(250),
  stok_alt_depo_kod CHAR(13),
  tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
  takip_kodu CHAR(36) NOT NULL,
  INDEX idx_firma_musteri (firma_id, musteri_id),
  INDEX idx_durum (durum),
  INDEX idx_islem (islem)
);
```

**JSON `veriler` Kolonu Kullanımı:**
```php
// Kayıt sırasında
$veriler = json_encode([
    'urun_detay' => 'Plastik Kalıp',
    'ozel_alan_1' => 'Değer 1',
    'custom_field' => ['sub1' => 'value1']
], JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("INSERT INTO siparisler (firma_id, veriler, ...) VALUES (:firma_id, :veriler, ...)");
$stmt->execute([':veriler' => $veriler]);

// Okuma sırasında
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$veriler = json_decode($row['veriler'], true);
echo $veriler['urun_detay']; // "Plastik Kalıp"
```

### `ai_cache` (Query Cache - Performans)

**Yapı:**
```sql
CREATE TABLE ai_cache (
  id INT PRIMARY KEY AUTO_INCREMENT,
  firma_id INT NOT NULL,
  question_hash CHAR(32) NOT NULL,  -- MD5(question)
  original_question TEXT NOT NULL,
  answer TEXT,
  data_json LONGTEXT,  -- Query sonuçları
  sql_query TEXT,
  html_table LONGTEXT,  -- Formatlı tablo
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  invalidated_at DATETIME,
  hit_count INT DEFAULT 0,  -- Kaç kez kullanıldı
  is_valid TINYINT(1) DEFAULT 1,
  INDEX idx_hash (question_hash),
  INDEX idx_firma_valid (firma_id, is_valid),
  INDEX idx_hit_count (hit_count)
);
```

**Kullanım:**
```php
$hash = md5($question . $firma_id);

// Cache kontrolü
$stmt = $conn->prepare("SELECT * FROM ai_cache WHERE question_hash = :hash AND is_valid = 1");
$stmt->execute([':hash' => $hash]);

if ($cached = $stmt->fetch()) {
    // Cache hit - hit_count artır
    $conn->prepare("UPDATE ai_cache SET hit_count = hit_count + 1 WHERE id = :id")
         ->execute([':id' => $cached['id']]);
    return $cached['answer'];
}

// Cache miss - yeni kayıt oluştur
```

### `makinalar` (Makina Tanımları)

**Önemli Enum Kolonlar:**
```sql
durumu ENUM('aktif','pasif','bakımda') DEFAULT 'aktif'
planlamada_goster ENUM('evet','hayir') DEFAULT 'evet'
stoga_geri_gonderme_durumu ENUM('evet','hayır') DEFAULT 'hayır'
```

**İlişkili Tablolar:**
- `makina_personeller` (çoka çok)
- `makina_bakim_log` (1'e çok)
- `planlama` (1'e çok)

---

## 🔗 Tablo İlişkileri

### Ana İlişki Pattern'leri

```
firmalar (1) ──────────> (Çok) siparisler
                    ├──> (Çok) musteri
                    ├──> (Çok) makinalar
                    ├──> (Çok) personeller
                    └──> (1) ai_agent_settings

musteri (1) ────────────> (Çok) siparisler
                    ├──> (Çok) musteri_adresleri
                    └──> (Çok) musteri_yetkilileri

siparisler (1) ──────────> (Çok) planlama
                    ├──> (Çok) siparis_dosyalar
                    ├──> (Çok) siparis_log
                    ├──> (Çok) uretim_islem_tarihler
                    └──> (Çok) uretilen_adetler

makinalar (1) ───────────> (Çok) planlama
                    ├──> (Çok) makina_personeller
                    ├──> (Çok) makina_bakim_log
                    └──> (Çok) uretim_* log tabloları

stok_kalemleri (1) ──────> (Çok) stok_alt_kalemler
                    └──> (Çok) stok_alt_depolar

personeller (1) ─────────> (Çok) giris_log
                    ├──> (Çok) personel_sayfa_yetki
                    └──> (Çok) ai_chat_history
```

---

## 📝 Naming Convention'lar

### Tablo İsimlendirme
- Çoğul: `siparisler`, `musteriler`, `makinalar`
- Alt tablolar: `{ana_tablo}_alt_*` (örn: `stok_alt_kalemler`)
- Log tabloları: `{modul}_log` (örn: `siparis_log`, `giris_log`)
- İlişki tabloları: `{tablo1}_{tablo2}` (örn: `makina_personeller`)

### Kolon İsimlendirme
- Primary Key: `id`
- Foreign Key: `{tablo}_id` (örn: `firma_id`, `musteri_id`)
- Boolean: `{isim}_mi`, `{isim}_enabled` (örn: `aktif`, `ai_enabled`)
- Tarih/Saat: `{isim}_tarih`, `{isim}_at` (örn: `created_at`)
- Soft delete: `silindi` (TINYINT(1))

### Veri Tipleri Pattern'leri
```sql
-- ID'ler
id INT PRIMARY KEY AUTO_INCREMENT

-- Foreign Keys
firma_id INT NOT NULL
musteri_id INT NOT NULL

-- Boolean
aktif TINYINT(1) DEFAULT 1

-- Tarih
created_at DATETIME DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP

-- JSON
veriler JSON NOT NULL
ayarlar LONGTEXT  -- JSON string

-- Enum
durum ENUM('aktif','pasif','beklemede')
```

---

## 🛡️ Güvenlik & Best Practices

### 1. Multi-Tenant İzolasyon
```sql
-- ✅ Her query'de firma_id kontrolü ZORUNLU
SELECT * FROM siparisler 
WHERE firma_id = :firma_id 
  AND id = :id;

-- ❌ Asla firma_id olmadan sorgulama
SELECT * FROM siparisler WHERE id = :id;  -- GÜVENSİZ!
```

### 2. Prepared Statements
```php
// ✅ DOĞRU
$stmt = $conn->prepare("SELECT * FROM musteri WHERE id = :id AND firma_id = :firma_id");
$stmt->execute([':id' => $id, ':firma_id' => $firma_id]);

// ❌ YANLIŞ
$query = "SELECT * FROM musteri WHERE id = $id";  // SQL Injection riski
```

### 3. Soft Delete Pattern
```sql
-- Delete yerine silindi flag'i güncelle
UPDATE siparisler SET silindi = 1 WHERE id = :id AND firma_id = :firma_id;

-- Query'lerde silindi kontrolü
SELECT * FROM siparisler WHERE firma_id = :firma_id AND silindi = 0;
```

### 4. JSON Validation
```php
// JSON kaydetmeden önce validate et
$data = json_decode($json_string, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("Invalid JSON");
}

// UTF-8 sorunları için
$json = json_encode($data, JSON_UNESCAPED_UNICODE);
```

---

## 🔍 Sık Kullanılan Query Pattern'leri

### 1. Sipariş Listesi (Multi-tenant)
```sql
SELECT s.*, m.firma_unvani, p.adi_soyadi as temsilci
FROM siparisler s
INNER JOIN musteri m ON s.musteri_id = m.id
LEFT JOIN personeller p ON s.musteri_temsilcisi_id = p.id
WHERE s.firma_id = :firma_id 
  AND s.silindi = 0
  AND s.islem != 'iptal'
ORDER BY s.tarih DESC;
```

### 2. Makina İş Listesi (JSON Parse)
```sql
SELECT s.id, s.siparis_no, s.isin_adi,
       JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.urun_adi')) as urun,
       m.makina_adi, p.baslangic, p.bitis
FROM siparisler s
INNER JOIN planlama p ON s.id = p.siparis_id
INNER JOIN makinalar m ON p.makina_id = m.id
WHERE s.firma_id = :firma_id
  AND m.durumu = 'aktif'
  AND p.bitis >= CURDATE();
```

### 3. AI Cache Hit Rate
```sql
SELECT 
    COUNT(*) as total_queries,
    SUM(CASE WHEN hit_count > 0 THEN 1 ELSE 0 END) as cached_queries,
    ROUND((SUM(CASE WHEN hit_count > 0 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as hit_rate
FROM ai_cache
WHERE firma_id = :firma_id
  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### 4. Agent Alerts (Son 24 saat)
```sql
SELECT alert_type, alert_level, COUNT(*) as count
FROM agent_alerts
WHERE firma_id = :firma_id
  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY alert_type, alert_level
ORDER BY 
    FIELD(alert_level, 'CRITICAL', 'WARNING', 'INFO'),
    count DESC;
```

---

## 📊 İndex Stratejisi

### Kritik İndeksler
```sql
-- Multi-tenant queries için
CREATE INDEX idx_firma_created ON siparisler(firma_id, created_at);
CREATE INDEX idx_firma_durum ON siparisler(firma_id, durum);

-- Join performansı için
CREATE INDEX idx_musteri_firma ON musteri(firma_id, id);

-- Cache lookup için
CREATE INDEX idx_hash ON ai_cache(question_hash);

-- Agent monitoring için
CREATE INDEX idx_firma_level ON agent_alerts(firma_id, alert_level, created_at);
```

---

## 🔄 Migration Dosyaları

**Lokasyon**: `/mysql/` klasörü

### Önemli Migration'lar
- `ai_agent_settings.sql` - Agent sistem tabloları
- `migration_add_ai_columns.sql` - AI özellikleri
- `migration_create_depolar_tables.sql` - Depo sistemi
- `migration_fix_planlama_id.sql` - Planlama düzeltmeleri

### Migration Çalıştırma
```bash
ssh root@91.99.186.98
mysql -u hanka_user -p'HankaDB2025!' panelhankasys_crm2 < /path/to/migration.sql
```

---

## 📈 Tablo Boyutları & Performans

### Yüksek Trafik Tabloları
- `siparisler` - Ana veri tablosu
- `ai_cache` - Cache tablosu (yüksek read)
- `uretim_islem_tarihler` - Log tablosu (yüksek write)
- `ai_chat_history` - Konuşma logları

### Optimizasyon Önerileri
1. `ai_cache` tablosunu düzenli temizle (60 gün üzeri)
2. Log tablolarını arşivle (90 gün üzeri)
3. JSON kolonlarını indexle (MySQL 5.7+)
4. Partitioning kullan (tarih bazlı)

---

## 🎯 Özet

- **94 Tablo** - İyi organize edilmiş kategori yapısı
- **PDO Bağlantı** - Güvenli prepared statements
- **Multi-Tenant** - firma_id ile tam izolasyon
- **JSON Flexibility** - Esnek veri yapısı
- **AI Integration** - Cache ve knowledge base
- **Agent System** - Otomasyon altyapısı
- **Comprehensive Logging** - Her işlem kayıt altında

**Son Güncelleme**: 2 Kasım 2025  
**Versiyon**: 2.0 (AI & Agent Eklendi)
