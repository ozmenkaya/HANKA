# HANKA SYS SAAS - Sistem Mimarisi Dokümantasyonu

## 📋 İçindekiler
- [Genel Bakış](#genel-bakış)
- [Sistem Mimarisi](#sistem-mimarisi)
- [Routing Sistemi](#routing-sistemi)
- [Veritabanı Yapısı](#veritabanı-yapısı)
- [Multi-Tenant (Çoklu Firma) Yapısı](#multi-tenant-yapısı)
- [AI & Agent Sistemi](#ai--agent-sistemi)
- [Güvenlik](#güvenlik)

---

## Genel Bakış

**HANKA SYS SAAS** üretim planlama, sipariş yönetimi, stok takibi ve makina iş listesi yönetimi için geliştirilmiş kurumsal bir ERP sistemidir.

### Teknoloji Stack
- **Backend**: PHP 8.1.2
- **Database**: MySQL (panelhankasys_crm2)
- **Frontend**: Bootstrap 5, jQuery, DataTables, Select2
- **AI Engine**: OpenAI GPT-4o-mini (Fine-tuned model)
- **Server**: Ubuntu 22.04, Apache 2.4.52
- **Connection**: PDO (PHP Data Objects)

### Temel Özellikler
- 🏢 Multi-tenant (çoklu firma) yapısı
- 🤖 AI destekli SQL query engine
- 🔄 Multi-agent otomasyon sistemi
- 📊 Dinamik rapor oluşturma
- 📦 Stok ve sipariş yönetimi
- 🏭 Makina planlama ve iş listesi
- 👥 Müşteri & tedarikçi yönetimi
- 📈 Dashboard & Analytics

---

## Sistem Mimarisi

### Klasör Yapısı
```
/var/www/html/
├── index.php                    # Ana routing dosyası
├── .env                         # Çevre değişkenleri (OpenAI key, DB config)
├── include/
│   ├── db.php                   # PDO veritabanı bağlantısı
│   ├── db_local.php             # Local development DB
│   ├── oturum_kontrol.php       # Session & authentication
│   ├── AIChatEngine.php         # AI query engine
│   ├── AIQueryValidator.php     # SQL injection protection
│   ├── agents/
│   │   ├── AgentOrchestrator.php    # Agent koordinasyon
│   │   ├── AlertAgent.php           # Alert & monitoring
│   │   ├── AnalyticsAgent.php       # Veri analizi
│   │   └── ActionAgent.php          # Otomatik aksiyonlar
│   └── header.php               # Ortak header & menu
├── assets/                      # CSS, JS, Bootstrap, icons
├── css/                         # Custom CSS files
├── js/                          # Custom JavaScript
├── dosyalar/                    # Upload dosyaları
│   ├── logo/                    # Firma logoları
│   ├── bildirim_dosyalar/       # Bildirim ekleri
│   └── geri_bildirim_dosyalar/  # Feedback dosyaları
├── logs/                        # Sistem logları
├── cron/                        # Scheduled tasks
└── mysql/                       # SQL migration dosyaları
```

### Veri Akışı

```
[User Request]
     ↓
[index.php - Routing]
     ↓
[oturum_kontrol.php - Auth]
     ↓
[Sayfa Dosyası (örn: siparisler.php)]
     ↓
[AJAX İstek] ──→ [*_db_islem.php]
     ↓                    ↓
[db.php - PDO]    [Validation]
     ↓                    ↓
[MySQL DB]         [Response JSON]
     ↓
[Frontend Update]
```

---

## Routing Sistemi

### URL Yapısı
```
https://lethe.com.tr/index.php?url={sayfa_adi}&param1=value1&param2=value2
```

### Routing Mekanizması (index.php)

```php
// 1. URL parametresinden sayfa belirleme
$page = isset($_GET['url']) ? basename($_GET['url']) : 'home';
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page); // Güvenlik

// 2. AJAX endpoint kontrolü (_db_islem dosyaları)
if (strpos($page, '_db_islem') !== false) {
    $file = $page . ".php";
    if (file_exists($file)) {
        include($file);
        exit; // HTML wrapper olmadan direkt çalıştır
    }
}

// 3. Normal sayfa yükleme
$file = $page . ".php";
if (file_exists($file)) {
    include($file);
} else {
    include("404.php");
}
```

### Sayfa Tipleri

1. **View Pages** (Görüntüleme sayfaları)
   - Örnek: `siparisler.php`, `musteriler.php`, `raporlar.php`
   - HTML + DataTables + Modal'lar içerir
   - AJAX ile veri çeker

2. **DB İşlem Sayfaları** (Backend API)
   - Örnek: `siparis_db_islem.php`, `musteri_db_islem.php`
   - POST verisi alır, DB işlemi yapar, JSON döner
   - HTML render etmez

3. **Form Sayfaları** (Ekleme/Güncelleme)
   - Örnek: `siparis_ekle.php`, `musteri_guncelle.php`
   - Form + validation + submit handler

4. **Modal Sayfaları**
   - Örnek: `kod1_modal.php`, `teslimat_modal.php`
   - AJAX ile yüklenen popup formlar

---

## Veritabanı Yapısı

### Bağlantı (PDO)
```php
// include/db.php
$conn = new PDO("mysql:host=localhost;dbname=panelhankasys_crm2", "hanka_user", "HankaDB2025!");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec("SET NAMES 'utf8mb4'");
```

### Ana Tablo Kategorileri (94 Tablo)

#### 1️⃣ **Core System Tables**
- `firmalar` - Firma bilgileri (multi-tenant ana tablo)
- `personel` - Kullanıcılar ve yetkiler
- `personel_sayfa_yetki` - Sayfa bazlı erişim kontrolü
- `giris_log` - Login geçmişi
- `bildirimler` - Kullanıcı bildirimleri

#### 2️⃣ **Müşteri & Satış**
- `musteri` - Müşteri ana kayıtları
- `musteri_adresleri` - Teslimat adresleri
- `musteri_yetkilileri` - İletişim kişileri
- `siparisler` - Sipariş kayıtları (JSON veriler kolonu ile esnek yapı)
- `siparis_form_tipleri` - Özel sipariş formları

#### 3️⃣ **Stok & Malzeme**
- `stok_kalemleri` - Ana stok kartları
- `stok_alt_kalemler` - Stok alt detayları
- `stok_alt_kalem_degerler` - Dinamik özellikler
- `stok_alt_depolar` - Depo bazlı stok takibi
- `arsiv_kalemler` - Arşiv malzeme listesi
- `arsiv_altlar` - Arşiv alt kategoriler

#### 4️⃣ **Üretim & Planlama**
- `makinalar` - Makina tanımları
- `makina_personeller` - Makina-personel atamaları
- `makina_bakim_log` - Bakım kayıtları
- `planlama` - Üretim planı
- `departmanlar` - Bölüm tanımları
- `departman_planlama` - Departman iş planı

#### 5️⃣ **AI & Agent System** (Yeni)
- `ai_agent_settings` - Agent yapılandırması (27 kolon)
- `ai_cache` - Query cache (performans)
- `ai_chat_history` - Konuşma geçmişi
- `ai_knowledge_base` - Vektör bilgi tabanı
- `ai_database_schema` - DB metadata
- `agent_alerts` - Sistem uyarıları
- `agent_task_queue` - Görev kuyruğu
- `agent_action_log` - Agent aksiyon geçmişi

#### 6️⃣ **Referans Tabloları**
- `birimler` - Ölçü birimleri
- `kur` - Döviz kurları
- `il`, `ilceler` - Coğrafi veriler
- `para_birim` - Para birimleri
- `sektor` - Sektör tanımları

### Önemli Kolon Pattern'leri

```sql
-- Her tabloda firma_id (multi-tenant)
firma_id INT NOT NULL

-- Soft delete pattern
silindi TINYINT(1) DEFAULT 0

-- Timestamp pattern
created_at DATETIME DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP

-- JSON data pattern (esnek yapı)
veriler LONGTEXT  -- JSON.parse() ile kullanılır
```

---

## Multi-Tenant Yapısı

### Firma İzolasyonu

Her firma verisi `firma_id` ile izole edilir:

```php
// Session'da firma bilgisi
$_SESSION['firma_id'] = 16;  // Login'de set edilir

// Her query'de firma kontrolü
$sql = "SELECT * FROM siparisler WHERE firma_id = :firma_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':firma_id' => $_SESSION['firma_id']]);
```

### Firma Bazlı Özelleştirmeler

- **Logo**: `dosyalar/logo/{firma_id}_logo.png`
- **Ayarlar**: `firma_ayarlar` tablosunda JSON
- **Menü Yetkileri**: `personel_sayfa_yetki` tablosu
- **Özel Formlar**: `siparis_form_tipleri` firma bazlı

---

## AI & Agent Sistemi

### AI Chat Engine (Fine-tuned GPT-4o-mini)

```
[User Question: "Bu ay kaç sipariş var?"]
      ↓
[AIChatEngine.php - Context Builder]
      ↓
[OpenAI API - Fine-tuned Model]
      ↓
[SQL Query: SELECT COUNT(*) FROM siparisler...]
      ↓
[Query Validator - SQL Injection Check]
      ↓
[Execute & Cache]
      ↓
[Natural Language Response]
```

**Cache Sistemi**: `ai_cache` tablosunda hash-based caching

### Multi-Agent System

#### 1. **AgentOrchestrator** (Koordinatör)
- Görev dağıtımı
- Agent senkronizasyonu
- Performans takibi

#### 2. **AlertAgent** (Monitoring)
```php
// Stok, sipariş, ödeme kontrolü
public function checkAlerts() {
    $alerts = [];
    $alerts = array_merge($alerts, $this->checkStock());
    $alerts = array_merge($alerts, $this->checkOrders());
    $alerts = array_merge($alerts, $this->checkPayments());
    return $alerts;
}
```

#### 3. **AnalyticsAgent** (Analiz)
- Trend analizi
- Tahmin modelleri
- KPI hesaplama

#### 4. **ActionAgent** (Otomasyon)
- Otomatik email
- Stok siparişi
- Bildirim gönderimi

### Agent API Endpoint

```
POST /agent_api.php
Header: X-Agent-API-Key: HANKA_AGENT_CRON_2025

Actions:
- test_agent
- run_analytics
- generate_report
- sync_data
```

---

## Güvenlik

### 1. Authentication & Session
```php
// oturum_kontrol.php
session_start();
if (!isset($_SESSION['personel_id'])) {
    header('Location: login.php');
    exit;
}
```

### 2. SQL Injection Protection
```php
// ✅ DOĞRU: PDO Prepared Statements
$stmt = $conn->prepare("SELECT * FROM musteri WHERE id = :id AND firma_id = :firma_id");
$stmt->execute([':id' => $id, ':firma_id' => $firma_id]);

// ❌ YANLIŞ: String concatenation
$sql = "SELECT * FROM musteri WHERE id = $id";  // KULLANILMAMALI
```

### 3. XSS Protection
```php
// Output'ta HTML escape
echo htmlspecialchars($firma_adi, ENT_QUOTES, 'UTF-8');
```

### 4. CSRF Protection
```php
// Form'larda token kullanımı
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

### 5. API Key Protection
```php
// Agent API
$api_key = $_SERVER['HTTP_X_AGENT_API_KEY'] ?? '';
if ($api_key !== 'HANKA_AGENT_CRON_2025') {
    http_response_code(401);
    exit;
}
```

---

## Performans Optimizasyonları

### 1. Query Caching (AI Cache)
- Hash-based: MD5(query + params)
- Hit count tracking
- Auto-invalidation

### 2. DataTables Server-side Processing
```javascript
$('#table').DataTable({
    processing: true,
    serverSide: true,
    ajax: 'veri_yukle.php'
});
```

### 3. JSON Data Compression
- `JSON.stringify()` ile kompakt storage
- `JSON.parse()` ile runtime parse

### 4. Index Strategy
```sql
-- Multi-tenant optimizasyonu
CREATE INDEX idx_firma_created ON siparisler(firma_id, created_at);
```

---

## Geliştirme Workflow

### Yeni Modül Ekleme

1. **View Sayfası Oluştur** (örn: `yeni_modul.php`)
2. **DB İşlem Sayfası Oluştur** (`yeni_modul_db_islem.php`)
3. **Menu'ye Ekle** (`include/header.php`)
4. **Yetki Tanımla** (`personel_sayfa_yetki` tablosuna ekle)
5. **Test Et**

### CRUD Pattern

```php
// CREATE
$stmt = $conn->prepare("INSERT INTO tablo (firma_id, adi) VALUES (:firma_id, :adi)");
$stmt->execute([':firma_id' => $_SESSION['firma_id'], ':adi' => $adi]);

// READ
$stmt = $conn->prepare("SELECT * FROM tablo WHERE firma_id = :firma_id");
$stmt->execute([':firma_id' => $_SESSION['firma_id']]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// UPDATE
$stmt = $conn->prepare("UPDATE tablo SET adi = :adi WHERE id = :id AND firma_id = :firma_id");
$stmt->execute([':adi' => $adi, ':id' => $id, ':firma_id' => $_SESSION['firma_id']]);

// DELETE (Soft delete)
$stmt = $conn->prepare("UPDATE tablo SET silindi = 1 WHERE id = :id AND firma_id = :firma_id");
$stmt->execute([':id' => $id, ':firma_id' => $_SESSION['firma_id']]);
```

---

## Deployment

### Production Server
- **Server**: 91.99.186.98
- **User**: root
- **Path**: /var/www/html/
- **Domain**: https://lethe.com.tr

### Deployment Command
```bash
scp file.php root@91.99.186.98:/var/www/html/
```

### Environment Variables (.env)
```env
OPENAI_API_KEY=sk-proj-...
OPENAI_MODEL=ft:gpt-4o-mini-2024-07-18:antartika:hanka-sql-v2:CXO5sbFS
DB_HOST=localhost
DB_NAME=panelhankasys_crm2
DB_USER=hanka_user
DB_PASS=HankaDB2025!
```

---

## Monitoring & Logs

### Log Dosyaları
- `logs/ai_queries.log` - AI sorgu logları
- `logs/agent_actions.log` - Agent aksiyonları
- `logs/errors.log` - PHP hataları
- `giris_log` tablosu - Login kayıtları

### Database'de Monitoring
- `agent_performance_metrics` - Agent performansı
- `ai_log` - AI kullanım istatistikleri
- `agent_alerts` - Sistem uyarıları

---

## İletişim & Destek

**Geliştirici**: Özmen Kaya
**Proje**: HANKA SYS SAAS
**Versiyon**: 2.0 (Multi-Agent + AI)
**Son Güncelleme**: 2 Kasım 2025

---

## Notlar

- ✅ Sistem PDO kullanır (MySQLi değil)
- ✅ Her query firma_id ile izole edilmelidir
- ✅ Prepared statements zorunludur
- ✅ JSON kolonları esnek veri yapısı sağlar
- ✅ Agent sistemi cron ile çalışır (15 dakikada bir)
- ✅ AI cache sistemi performansı 3x artırır
