# 🏭 HANKA SYS SAAS - Üretim Yönetim Sistemi

<div align="center">

**Multi-Tenant ERP | AI Destekli | Agent Otomasyon**

[![Version](https://img.shields.io/badge/version-2.0-blue.svg)](https://github.com/ozmenkaya/HANKA)
[![PHP](https://img.shields.io/badge/PHP-8.1.2-777BB4.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-00758F.svg)](https://mysql.com)
[![OpenAI](https://img.shields.io/badge/OpenAI-GPT--4o--mini-412991.svg)](https://openai.com)

[Demo](https://lethe.com.tr) · [Dokümantasyon](#-dokümantasyon) · [Kurulum](#-kurulum)

</div>

---

## 📖 Hakkında

**HANKA SYS SAAS**, üretim planlama, sipariş yönetimi, stok takibi ve makina iş listesi yönetimi için geliştirilmiş, **AI destekli** kurumsal bir ERP sistemidir.

### 🌟 Temel Özellikler

- 🏢 **Multi-Tenant Yapı** - Firma bazlı tam izolasyon
- 🤖 **AI Chat Engine** - Fine-tuned GPT-4o-mini ile doğal dil sorguları
- 🔄 **Multi-Agent System** - Otonom alert, analiz ve aksiyon sistemleri
- 📊 **Dinamik Raporlama** - Excel, PDF, CSV export
- 📦 **Stok Yönetimi** - Depo bazlı takip, alt kalem sistemi
- 🏭 **Üretim Planlama** - Makina bazlı iş listesi ve zaman takibi
- 👥 **CRM** - Müşteri, tedarikçi, adres yönetimi
- 📈 **Dashboard & Analytics** - Gerçek zamanlı metrikler
- 🔒 **Güvenlik** - PDO prepared statements, multi-factor authentication

### 🎯 Hedef Sektörler

Plastik kalıp, metal işleme, tekstil, ambalaj, mobilya üretimi ve benzeri üretim sektörleri için özelleştirilmiştir.

---

## 🚀 Hızlı Başlangıç

### Sistem Gereksinimleri

```
PHP >= 8.1.2
MySQL >= 8.0
Apache >= 2.4
Composer
OpenAI API Key (AI özellikleri için)
```

### Kurulum

```bash
# 1. Repository'yi klonla
git clone https://github.com/ozmenkaya/HANKA.git
cd HANKA

# 2. Veritabanını oluştur
mysql -u root -p
CREATE DATABASE panelhankasys_crm2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hanka_user'@'localhost' IDENTIFIED BY 'HankaDB2025!';
GRANT ALL PRIVILEGES ON panelhankasys_crm2.* TO 'hanka_user'@'localhost';
FLUSH PRIVILEGES;

# 3. Database schema'yı import et
mysql -u hanka_user -p panelhankasys_crm2 < mysql/schema.sql
mysql -u hanka_user -p panelhankasys_crm2 < mysql/ai_agent_settings.sql

# 4. .env dosyasını düzenle
cp .env.example .env
nano .env

# 5. Klasör izinlerini ayarla
chmod 755 dosyalar/
chmod 755 logs/
chown -R www-data:www-data dosyalar/ logs/

# 6. Apache'yi yeniden başlat
sudo systemctl restart apache2
```

### .env Konfigürasyonu

```env
# Database
DB_HOST=localhost
DB_NAME=panelhankasys_crm2
DB_USER=hanka_user
DB_PASS=HankaDB2025!

# OpenAI
OPENAI_API_KEY=sk-proj-YOUR_KEY_HERE
OPENAI_MODEL=ft:gpt-4o-mini-2024-07-18:antartika:hanka-sql-v2:CXO5sbFS

# Agent API
AGENT_API_KEY=HANKA_AGENT_CRON_2025

# Environment
APP_ENV=production
DEBUG_MODE=false
```

### İlk Giriş

```
URL: http://localhost/
Kullanıcı: admin
Şifre: admin123

⚠️ İlk girişte şifreyi değiştirin!
```

---

## 📚 Dokümantasyon

Kapsamlı dokümantasyon seti:

### 📐 [ARCHITECTURE.md](ARCHITECTURE.md)
Sistem mimarisi, klasör yapısı, veri akışı, routing sistemi
- Multi-tenant yapısı nasıl çalışır
- Routing mekanizması (index.php?url=)
- Session yönetimi
- Deployment süreci

### 🗄️ [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)
94 tablonun detaylı dokümantasyonu
- Tablo kategorileri ve ilişkileri
- Önemli kolonlar ve kullanımları
- JSON yapı örnekleri
- Query pattern'leri
- İndex stratejisi

### 📝 [CODING_STANDARDS.md](CODING_STANDARDS.md)
Kod yazım standartları ve best practices
- Naming convention'lar
- PDO kullanımı (MySQLi değil!)
- Güvenlik pratikleri (SQL injection, XSS, CSRF)
- CRUD pattern'leri
- Error handling

### 🔌 [API_REFERENCE.md](API_REFERENCE.md) *(Yakında)*
API endpoint'leri ve kullanımları
- Agent API (`agent_api.php`)
- AI Chat API (`ai_chat.php`)
- AJAX endpoints
- Authentication

### 🛠️ [DEVELOPMENT_GUIDE.md](DEVELOPMENT_GUIDE.md) *(Yakında)*
Yeni özellik geliştirme kılavuzu
- Yeni modül ekleme adımları
- Rapor oluşturma
- Form yapısı
- Testing

---

## 🏗️ Proje Yapısı

```
HANKA/
├── 📂 include/              # Core sistem dosyaları
│   ├── db.php              # PDO database connection
│   ├── oturum_kontrol.php  # Session & auth
│   ├── AIChatEngine.php    # AI query engine
│   ├── AIQueryValidator.php # SQL injection protection
│   └── agents/             # Multi-agent system
│       ├── AgentOrchestrator.php
│       ├── AlertAgent.php
│       ├── AnalyticsAgent.php
│       └── ActionAgent.php
├── 📂 assets/              # Bootstrap, jQuery, DataTables
├── 📂 css/                 # Custom stylesheets
├── 📂 js/                  # Custom JavaScript
├── 📂 dosyalar/            # Upload dosyaları (firma bazlı)
├── 📂 logs/                # Sistem logları
├── 📂 mysql/               # SQL migration dosyaları
├── 📂 cron/                # Scheduled tasks
├── 📄 index.php            # Ana routing dosyası
├── 📄 .env                 # Environment variables
├── 📄 agent_api.php        # Agent API endpoint
├── 📄 ai_chat.php          # AI chat interface
├── 📄 ai_settings.php      # AI & Agent ayarları
└── 📄 README.md            # Bu dosya
```

---

## 🤖 AI & Agent Sistemi

### AI Chat Engine

Fine-tuned GPT-4o-mini modeli ile doğal dil sorguları:

```
Kullanıcı: "Bu ay kaç sipariş var?"
AI: SELECT COUNT(*) FROM siparisler WHERE firma_id = 16 AND MONTH(tarih) = MONTH(NOW())
→ Sonuç: 47 sipariş bulundu.
```

**Özellikler:**
- 🧠 Fine-tuned model (260 SQL örneği ile eğitildi)
- 💾 Query caching (3x performans artışı)
- 🔒 SQL injection koruması
- 📊 Otomatik tablo formatı
- 🗣️ Text-to-Speech (OpenAI TTS)

### Multi-Agent System

#### 1. **AlertAgent** 🚨
Stok, sipariş, ödeme kontrolleri yaparak otomatik uyarılar oluşturur.

```php
// Kritik stok seviyesi
// Geciken siparişler
// Vadesi yaklaşan ödemeler
```

#### 2. **AnalyticsAgent** 📈
Veri analizi ve trend tahminleri yapar.

```php
// Satış trendleri
// Makina verimliliği
// Müşteri analizi
```

#### 3. **ActionAgent** ⚡
Otomatik aksiyonlar alır (email, bildirim, stok siparişi).

```php
// Otomatik email gönderimi
// WhatsApp bildirimi
// Tedarikçi siparişi
```

#### 4. **AgentOrchestrator** 🎯
Tüm agent'ları koordine eder ve görev dağıtır.

### Cron Job Kurulumu

```bash
# Crontab düzenle
crontab -e

# Her 15 dakikada bir agent kontrolü
*/15 * * * * /usr/bin/php /var/www/html/cron/agent_runner.php

# Günlük rapor (09:00)
0 9 * * * /usr/bin/php /var/www/html/cron/daily_report.php

# Haftalık analiz (Pazartesi 10:00)
0 10 * * 1 /usr/bin/php /var/www/html/cron/weekly_analytics.php
```

---

## 🔐 Güvenlik

### Güvenlik Özellikleri

- ✅ **PDO Prepared Statements** - SQL injection koruması
- ✅ **Multi-Tenant İzolasyon** - Firma bazlı veri güvenliği
- ✅ **XSS Protection** - htmlspecialchars() ile output encoding
- ✅ **CSRF Tokens** - Form güvenliği
- ✅ **Session Security** - Hijacking koruması
- ✅ **API Key Authentication** - Agent API güvenliği
- ✅ **File Upload Validation** - MIME type kontrolü
- ✅ **Error Logging** - Güvenlik olayları kaydı

### Güvenlik Best Practices

```php
// ✅ Her query'de firma_id kontrolü
WHERE firma_id = :firma_id

// ✅ Prepared statements
$stmt = $conn->prepare("SELECT * FROM table WHERE id = :id");

// ✅ Output encoding
echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

// ✅ CSRF token
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
```

---

## 📊 Performans

### Optimizasyonlar

- 💾 **AI Query Cache** - Hash-based caching, 3x hız
- 📑 **DataTables Server-Side** - Büyük veri setleri için
- 🗜️ **JSON Compression** - Kompakt veri depolama
- 📈 **Database İndexing** - Multi-tenant optimizasyonu
- ⚡ **Lazy Loading** - On-demand veri yükleme

### Performans Metrikleri

```
AI Query Response: ~2-3 saniye (cache ile <100ms)
DataTables Load: 1000+ kayıt, ~500ms
Dashboard Load: ~1.2 saniye
Agent Check Cycle: ~5 saniye (4 agent)
```

---

## 🧪 Testing

```bash
# Agent testi
curl -X POST https://lethe.com.tr/agent_api.php \
  -H "X-Agent-API-Key: HANKA_AGENT_CRON_2025" \
  -d "action=test_agent"

# AI Chat testi
curl -X POST https://lethe.com.tr/ai_chat.php \
  -H "Content-Type: application/json" \
  -d '{"question": "Kaç sipariş var?", "firma_id": 16}'
```

---

## 📝 Changelog

### Version 2.0 (2 Kasım 2025)
- ✨ Multi-Agent System eklendi
- 🤖 Fine-tuned GPT-4o-mini deployment
- 📊 AI & Agent Settings sayfası
- 🗣️ Text-to-Speech desteği
- 🔧 MySQLi → PDO migration
- 📚 Kapsamlı dokümantasyon

### Version 1.5
- 🎯 AI Chat Engine
- 💾 Query caching sistemi
- 📈 Analytics dashboard

### Version 1.0
- 🏭 Core ERP features
- 👥 Multi-tenant yapı
- 📦 Stok & sipariş yönetimi

---

## 🤝 Katkıda Bulunma

```bash
# 1. Fork'la
# 2. Feature branch oluştur
git checkout -b feature/yeni-ozellik

# 3. Commit'le (standartlara uygun)
git commit -m "feat: Yeni özellik eklendi"

# 4. Push'la
git push origin feature/yeni-ozellik

# 5. Pull Request aç
```

### Commit Convention

```
feat: Yeni özellik
fix: Bug düzeltme
docs: Dokümantasyon
refactor: Kod iyileştirme
perf: Performans optimizasyonu
test: Test ekleme
chore: Bakım işleri
```

---

## 📞 İletişim & Destek

**Geliştirici**: Özmen Kaya  
**Email**: ozmenkaya@example.com  
**Website**: https://lethe.com.tr  
**GitHub**: [@ozmenkaya](https://github.com/ozmenkaya)

### Destek

- 📖 [Dokümantasyon](https://github.com/ozmenkaya/HANKA/wiki)
- 🐛 [Bug Raporu](https://github.com/ozmenkaya/HANKA/issues)
- 💡 [Özellik İsteği](https://github.com/ozmenkaya/HANKA/issues/new?labels=enhancement)
- 💬 [Tartışmalar](https://github.com/ozmenkaya/HANKA/discussions)

---

## 📄 Lisans

Bu proje özel lisans altındadır. Kullanım hakları Antartika Yazılım'a aittir.

---

## 🙏 Teşekkürler

- [OpenAI](https://openai.com) - AI engine
- [Bootstrap](https://getbootstrap.com) - UI framework
- [DataTables](https://datatables.net) - Table plugin
- [jQuery](https://jquery.com) - JavaScript library
- [Font Awesome](https://fontawesome.com) - Icons

---

<div align="center">

**⭐ Bu projeyi faydalı bulduysanız yıldız vermeyi unutmayın!**

Made with ❤️ by [Özmen Kaya](https://github.com/ozmenkaya)

</div>

---

## 📋 Hızlı Linkler

- [Kurulum](#-kurulum)
- [Dokümantasyon](#-dokümantasyon)
- [AI Sistemi](#-ai--agent-sistemi)
- [Güvenlik](#-güvenlik)
- [Katkıda Bulunma](#-katkıda-bulunma)

---

## ##  ##        ####       ###      ## ##   ##      ####           ######  
## ##  ##       ##  ##      ## ##    ## ##  ##      ##  ##          ##      
## ##  ##      ##    ##     ##  ##   ## ####       ##    ##         ######      
## ######     ##########    ##   ##  ## ####      ##########        ######  
## ##  ##    ##        ##   ##    ## ## ##  ##   ##        ##           ##  
## ##  ##   ##          ##  ##     #### ##   ## ##          ##      ######  

**HANKA SYS SAAS v2.0** - Üretim Yönetiminde Yapay Zeka Çağı 🚀
