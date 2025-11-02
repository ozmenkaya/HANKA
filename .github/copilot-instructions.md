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

**Not**: Bu talimatlar her sohbette geçerlidir. Kod önerirken MUTLAKA bu kurallara uy!
