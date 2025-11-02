# GitHub Copilot Project Instructions

## Proje: HANKA SYS SAAS - Üretim Yönetim Sistemi

### 🎯 Proje Özeti
**HANKA SYS SAAS**, üretim planlama, sipariş yönetimi ve stok takibi için geliştirilmiş, AI destekli multi-tenant ERP sistemidir.

### 🔧 Teknoloji Stack
- **Backend**: PHP 8.1.2
- **Database**: MySQL (panelhankasys_crm2) - 94 tablo
- **Connection**: PDO (MySQLi KULLANMA!)
- **AI**: OpenAI GPT-4o-mini Fine-tuned Model
- **Frontend**: Bootstrap 5, jQuery, DataTables

### 🏢 Kritik Kurallar

#### 1. Multi-Tenant Yapısı (ÇOK ÖNEMLİ!)
```php
// ✅ Her query'de firma_id kontrolü ZORUNLU
WHERE firma_id = :firma_id AND id = :id

// ❌ Asla firma_id olmadan query yazma
WHERE id = :id  // GÜVENSİZ!
```

#### 2. Veritabanı Bağlantısı
```php
// ✅ SADECE PDO kullan
$stmt = $conn->prepare("SELECT * FROM table WHERE id = :id");
$stmt->execute([':id' => $id]);

// ❌ MySQLi KULLANMA (eski sistem)
$conn->query("SELECT * FROM table");  // YANLIŞ!
```

#### 3. Güvenlik
- **SQL Injection**: Prepared statements zorunlu
- **XSS**: `htmlspecialchars()` ile escape
- **CSRF**: Token kontrolü
- **Session**: Her sayfada `oturum_kontrol.php`

#### 4. Dosya Yapısı
```
index.php               # Ana routing (index.php?url=page)
{page}.php             # View sayfası
{page}_db_islem.php    # Backend API (AJAX)
{page}_ekle.php        # Form sayfası
{page}_modal.php       # Modal content
```

### 📚 Dokümantasyon
- `README.md` - Genel bakış, kurulum
- `ARCHITECTURE.md` - Sistem mimarisi, routing
- `DATABASE_SCHEMA.md` - 94 tablo detayları
- `CODING_STANDARDS.md` - Kod standartları

### 🗄️ Önemli Tablolar
- `siparisler` - Ana sipariş (JSON `veriler` kolonu)
- `musteri` - Müşteri kayıtları
- `stok_kalemleri` - Stok kartları
- `makinalar` - Makina tanımları
- `ai_agent_settings` - Agent ayarları (27 kolon)
- `ai_cache` - Query cache (performans)

### 🤖 AI & Agent Sistem
- Fine-tuned Model: `ft:gpt-4o-mini-2024-07-18:antartika:hanka-sql-v2:CXO5sbFS`
- Agent API Key: `HANKA_AGENT_CRON_2025`
- 4 Agent: AlertAgent, AnalyticsAgent, ActionAgent, AgentOrchestrator

### 💡 Kod Önerileri Verirken

#### CRUD Pattern
```php
// CREATE
$stmt = $conn->prepare("INSERT INTO table (firma_id, ...) VALUES (:firma_id, ...)");
$stmt->execute([':firma_id' => $_SESSION['firma_id'], ...]);

// READ
$stmt = $conn->prepare("SELECT * FROM table WHERE firma_id = :firma_id");
$stmt->execute([':firma_id' => $_SESSION['firma_id']]);

// UPDATE
$stmt = $conn->prepare("UPDATE table SET col = :val WHERE id = :id AND firma_id = :firma_id");

// DELETE (Soft delete)
$stmt = $conn->prepare("UPDATE table SET silindi = 1 WHERE id = :id AND firma_id = :firma_id");
```

#### JSON İşlemleri
```php
// Kayıt
$json = json_encode($data, JSON_UNESCAPED_UNICODE);

// Okuma
$data = json_decode($row['veriler'], true);

// MySQL JSON query
JSON_UNQUOTE(JSON_EXTRACT(veriler, '$.field'))
```

#### AJAX Pattern
```javascript
$.ajax({
    url: 'page_db_islem.php',
    type: 'POST',
    data: { action: 'save', firma_id: FIRMA_ID, ...data },
    success: function(response) {
        if (response.success) {
            showSuccess(response.message);
        }
    }
});
```

### 🚫 Yapma Listesi
- ❌ MySQLi kullanma (PDO kullan)
- ❌ firma_id olmadan query yazma
- ❌ SQL string concatenation (injection riski)
- ❌ Raw user input echo (XSS riski)
- ❌ SELECT * (gereksiz veri)
- ❌ Hard delete (soft delete kullan: silindi=1)

### ✅ Her Zaman Yap
- ✅ Prepared statements
- ✅ firma_id kontrolü
- ✅ Input validation
- ✅ Output encoding (htmlspecialchars)
- ✅ Try-catch blokları
- ✅ Error logging
- ✅ Transaction kullan (ilişkili işlemlerde)

### 📂 Önemli Dosyalar
- `include/db.php` - PDO bağlantısı
- `include/oturum_kontrol.php` - Session kontrolü
- `include/AIChatEngine.php` - AI query engine
- `include/agents/AlertAgent.php` - Alert sistemi
- `agent_api.php` - Agent API endpoint
- `ai_settings.php` - AI & Agent ayarları

### 🔐 Credentials
```php
DB_HOST: localhost
DB_NAME: panelhankasys_crm2
DB_USER: hanka_user
DB_PASS: HankaDB2025!
```

### 🌐 Production
- Server: root@91.99.186.98
- Path: /var/www/html/
- Domain: https://lethe.com.tr

### 🎨 Naming Convention
```php
$snake_case     // Değişkenler
camelCase()     // Fonksiyonlar
PascalCase      // Sınıflar
UPPER_CASE      // Sabitler
```

### 📊 Query Optimizasyonu
```sql
-- İndeksli kolonlarda filtrele
WHERE firma_id = :firma_id AND created_at > :date

-- LIMIT kullan
LIMIT :offset, :limit

-- Gerekli kolonları seç
SELECT id, name FROM table  -- SELECT * değil
```

### 🔄 Session Değişkenleri
```php
$_SESSION['firma_id']      // Firma ID (zorunlu)
$_SESSION['personel_id']   // User ID
$_SESSION['yetki']         // Yetki seviyesi
$_SESSION['firma_adi']     // Firma adı
```

### 📝 Commit Convention
```
feat: Yeni özellik
fix: Bug düzeltme
docs: Dokümantasyon
refactor: Kod iyileştirme
perf: Performans
```

---

## 🏭 MES (Manufacturing Execution System) Mantığı

### MES Prensipleri
Bu bir **üretim yönetim sistemi**dir. Kod önerirken MES standartlarını uygula:

#### 1. Gerçek Zamanlı Takip
```php
// ✅ Her üretim adımını logla
INSERT INTO uretim_islem_tarihler 
(siparis_id, makina_id, personel_id, baslangic, bitis, durum)

// ✅ Makina durumunu sürekli güncelle
UPDATE makinalar SET durumu = 'aktif' WHERE id = :makina_id
```

#### 2. Traceability (İzlenebilirlik)
```php
// ✅ Her işlemi kim, ne zaman, nerede yaptı kaydet
- takip_kodu (unique identifier)
- personel_id (kim yaptı)
- makina_id (nerede yapıldı)
- tarih (ne zaman)
- durum değişiklikleri (siparis_log tablosu)
```

#### 3. Üretim Verimliliği
```sql
-- Makina kullanım oranı
SELECT 
    makina_id,
    SUM(TIMESTAMPDIFF(MINUTE, baslangic, bitis)) as calisan_dakika,
    COUNT(*) as is_sayisi
FROM uretim_islem_tarihler
WHERE DATE(baslangic) = CURDATE()
GROUP BY makina_id;

-- OEE (Overall Equipment Effectiveness)
-- Availability × Performance × Quality
```

#### 4. Stok & Malzeme Entegrasyonu
```php
// ✅ Üretimde kullanılan malzemeyi stoktan düş
$stmt = $conn->prepare("
    UPDATE stok_alt_depolar 
    SET miktar = miktar - :kullanilan 
    WHERE id = :depo_id AND firma_id = :firma_id
");

// ✅ İşlem logla
INSERT INTO stok_alt_depolar_kullanilanlar 
(alt_depo_id, siparis_id, kullanilan_miktar, tarih)
```

#### 5. Planlama & Zamanlama
```php
// ✅ Makina kapasitesini kontrol et
SELECT COUNT(*) FROM planlama 
WHERE makina_id = :makina_id 
  AND baslangic <= :yeni_bitis 
  AND bitis >= :yeni_baslangic

// ✅ Termin kontrolü
if ($termin < $tahmini_bitis) {
    // Alert oluştur
}
```

#### 6. Quality Control (Kalite Kontrol)
```php
// Fire/hata kayıtları
INSERT INTO uretim_eksik_uretilen_loglar 
(siparis_id, planlanan_adet, uretilen_adet, fire_adet, sebep)

// Üretim onayı
UPDATE siparisler 
SET islem = 'tamamlandi', 
    onay_baslangic_durum = 'evet'
WHERE id = :siparis_id
```

#### 7. Downtime Tracking (Duruş Takibi)
```php
// ✅ Makina arızaları
INSERT INTO uretim_ariza_log (makina_id, ariza_tipi, sure, aciklama)

// ✅ Molalar
INSERT INTO uretim_mola_log (personel_id, mola_tipi, baslangic, bitis)

// ✅ Bakım
INSERT INTO uretim_bakim_log (makina_id, bakim_tipi, sure)
```

### MES Modülleri (Projede Mevcut)

#### 📊 Production Planning
- `planlama` tablosu - Makina bazlı iş planı
- `departman_planlama` - Bölüm bazlı planlama
- Termin yönetimi, kaynak tahsisi

#### ⚙️ Execution Management
- `uretim_islem_tarihler` - İşlem başlangıç/bitiş
- `makina_is_buttonlar` - Durum butonları (başla, durdur, bitir)
- Real-time status tracking

#### 📈 Performance Analysis
- `uretilen_adetler` - Üretim miktarları
- `makina_bakim_log` - Bakım geçmişi
- OEE hesaplama altyapısı

#### 🔄 Material Tracking
- `stok_alt_depolar` - Depo bazlı stok
- `stok_alt_depolar_kullanilanlar` - Kullanım kayıtları
- `siparise_hazir_malzemeler` - Siparişe ayrılan malzemeler

#### 👷 Labor Management
- `makina_personeller` - Makina operatörleri
- `personel_departmanlar` - Personel bölüm atamaları
- Vardiya yönetimi (paydos_log, mola_log)

#### 📝 Documentation
- `uretim_mesaj_log` - İşçi mesajları
- `uretim_yetkili_log` - Yönetici notları
- `siparis_dosyalar` - Teknik dokümanlar

### MES Kod Pattern'leri

#### İş Başlatma
```php
// 1. Makina müsaitlik kontrolü
$stmt = $conn->prepare("SELECT durumu FROM makinalar WHERE id = :id");

// 2. Malzeme kontrolü
$stmt = $conn->prepare("
    SELECT miktar FROM stok_alt_depolar 
    WHERE stok_kalem_id = :kalem AND miktar >= :gerekli
");

// 3. İşlemi başlat
$stmt = $conn->prepare("
    INSERT INTO uretim_islem_tarihler 
    (siparis_id, makina_id, personel_id, baslangic, durum)
    VALUES (:siparis, :makina, :personel, NOW(), 'devam_ediyor')
");

// 4. Makina durumunu güncelle
$stmt = $conn->prepare("
    UPDATE makinalar SET durumu = 'aktif' WHERE id = :makina_id
");
```

#### İş Bitirme
```php
// 1. İşlemi kapat
UPDATE uretim_islem_tarihler 
SET bitis = NOW(), durum = 'tamamlandi' 
WHERE id = :islem_id;

// 2. Üretilen adedi kaydet
INSERT INTO uretilen_adetler 
(siparis_id, uretilen_adet, tarih) 
VALUES (:siparis, :adet, NOW());

// 3. Sipariş durumunu güncelle
UPDATE siparisler 
SET islem = CASE 
    WHEN (SELECT SUM(uretilen_adet) FROM uretilen_adetler WHERE siparis_id = :siparis) >= adet 
    THEN 'tamamlandi' 
    ELSE 'islemde' 
END
WHERE id = :siparis;

// 4. Makina durumunu güncelle
UPDATE makinalar SET durumu = 'beklemede' WHERE id = :makina_id;
```

#### İş Aktarma (Transfer)
```php
// 1. Eski makinada bitir
UPDATE uretim_islem_tarihler 
SET bitis = NOW(), durum = 'aktarildi' 
WHERE id = :islem_id;

// 2. Yeni makinada başlat
INSERT INTO uretim_islem_tarihler 
(siparis_id, makina_id, personel_id, baslangic, durum)
VALUES (:siparis, :yeni_makina, :personel, NOW(), 'devam_ediyor');

// 3. Aktarma logu
INSERT INTO uretim_aktarma_loglar 
(siparis_id, eski_makina, yeni_makina, sebep, tarih)
VALUES (:siparis, :eski, :yeni, :sebep, NOW());
```

### MES Dashboard Metrikleri

```sql
-- Günlük üretim özeti
SELECT 
    COUNT(DISTINCT siparis_id) as is_sayisi,
    SUM(uretilen_adet) as toplam_uretim,
    AVG(TIMESTAMPDIFF(MINUTE, p.baslangic, p.bitis)) as ort_sure
FROM uretilen_adetler u
JOIN planlama p ON u.siparis_id = p.siparis_id
WHERE DATE(u.tarih) = CURDATE();

-- Makina verimliliği
SELECT 
    m.makina_adi,
    COUNT(p.id) as is_sayisi,
    SUM(TIMESTAMPDIFF(MINUTE, p.baslangic, COALESCE(p.bitis, NOW()))) as calisma_suresi,
    (SELECT SUM(sure) FROM uretim_ariza_log WHERE makina_id = m.id AND DATE(tarih) = CURDATE()) as ariza_suresi
FROM makinalar m
LEFT JOIN planlama p ON m.id = p.makina_id AND DATE(p.baslangic) = CURDATE()
WHERE m.firma_id = :firma_id
GROUP BY m.id;

-- Geç kalan siparişler
SELECT 
    siparis_no, 
    isin_adi,
    termin,
    DATEDIFF(NOW(), termin) as gecikme_gun
FROM siparisler
WHERE firma_id = :firma_id 
  AND termin < CURDATE() 
  AND islem NOT IN ('tamamlandi', 'teslim_edildi', 'iptal')
ORDER BY gecikme_gun DESC;
```

### MES Geliştirme Önerileri

Yeni özellik eklerken:

#### ✅ Ekle
- Real-time durum güncellemeleri
- Otomatik bildirimler (termin yaklaşınca)
- Performans metrikleri (OEE, yield rate)
- Mobil erişim (operatör uygulaması)

#### ✅ Entegre Et
- ERP sistemleriyle (satış, finans)
- IoT sensörlerle (makina verileri)
- SCADA sistemleriyle (otomasyon)
- Kalite sistemleriyle (ISO kayıtları)

#### ✅ Optimizasyon
- Dinamik planlama (gecikme olursa yeniden planla)
- Predictive maintenance (bakım tahminleme)
- Capacity planning (kapasite optimizasyonu)
- Bottleneck analizi (darboğaz tespiti)

---

**Not**: MES mantığını her zaman göz önünde bulundur! Bu sadece bir yazılım değil, **üretim süreci yönetimi**dir.
