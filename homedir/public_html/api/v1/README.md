# HANKA CRM REST API v1 - Documentation

## 🔑 Authentication

API anahtarı ile kimlik doğrulama:
```bash
X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2
```

## 📡 Base URL
```
http://91.99.186.98/api/v1/
```

## 🎯 Endpoints

### 1. Status Kontrolü
**GET** `/status`

Test için API durumunu kontrol eder.

**Response:**
```json
{
    "success": true,
    "version": "1.0",
    "timestamp": "2025-10-26 23:02:41",
    "authenticated_as": "Hanka API - Test Key",
    "firma_id": 16
}
```

**Örnek:**
```bash
curl -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  http://91.99.186.98/api/v1/status
```

---

### 2. Müşteriler (Customers)

#### 2.1 Müşteri Listesi
**GET** `/customers`

**Query Parameters:**
- `limit` (optional, default: 50) - Sayfa başına kayıt sayısı
- `offset` (optional, default: 0) - Başlangıç noktası
- `search` (optional) - Firma ünvanı veya marka ile arama

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 309,
            "firma_id": 16,
            "marka": "ZİHNİ BALIKÇILIK",
            "firma_unvani": "ZİHNİ BALIKÇILIK SU ÜRÜNLERİ",
            "vergi_dairesi": "KARATAŞ",
            "vergi_numarasi": "9980809618",
            ...
        }
    ],
    "pagination": {
        "total": 309,
        "limit": 50,
        "offset": 0,
        "count": 50
    }
}
```

**Örnek:**
```bash
# İlk 10 müşteri
curl -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  "http://91.99.186.98/api/v1/customers?limit=10"

# "GÜLMAŞ" içeren müşteriler
curl -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  "http://91.99.186.98/api/v1/customers?search=GÜLMAŞ"
```

#### 2.2 Tek Müşteri Getir
**GET** `/customers/{id}`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 309,
        "firma_unvani": "ZİHNİ BALIKÇILIK",
        ...
    }
}
```

**Örnek:**
```bash
curl -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  http://91.99.186.98/api/v1/customers/309
```

#### 2.3 Yeni Müşteri Ekle
**POST** `/customers`

**Request Body:**
```json
{
    "firma_unvani": "ÖRNEK FİRMA A.Ş.",
    "marka": "ÖRNEK MARKA",
    "yetkili_adi": "Ahmet Yılmaz",
    "telefon": "0532 123 45 67",
    "email": "info@ornek.com",
    "adres": "Örnek Mahallesi, No: 123",
    "vergi_dairesi": "ANKARA",
    "vergi_no": "1234567890"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Müşteri eklendi",
    "id": 310
}
```

**Örnek:**
```bash
curl -X POST \
  -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  -H "Content-Type: application/json" \
  -d '{"firma_unvani":"TEST FİRMA","telefon":"0532 000 00 00"}' \
  http://91.99.186.98/api/v1/customers
```

#### 2.4 Müşteri Güncelle
**PUT** `/customers/{id}`

**Request Body:**
```json
{
    "telefon": "0532 999 88 77",
    "email": "yeni@email.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Müşteri güncellendi",
    "affected_rows": 1
}
```

#### 2.5 Müşteri Sil
**DELETE** `/customers/{id}`

Not: Soft delete yapılır (durum = 0)

**Response:**
```json
{
    "success": true,
    "message": "Müşteri silindi"
}
```

---

### 3. Siparişler (Orders)

#### 3.1 Sipariş Listesi
**GET** `/orders`

**Query Parameters:**
- `limit` (optional, default: 50)
- `offset` (optional, default: 0)
- `musteri_id` (optional) - Belirli müşteriye ait siparişler
- `start_date` (optional) - Başlangıç tarihi (YYYY-MM-DD)
- `end_date` (optional) - Bitiş tarihi (YYYY-MM-DD)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1557,
            "siparis_no": "GLR1364",
            "musteri_id": 1,
            "musteri_adi": "HELMEX",
            "toplam_tutar": 501402,
            "tarih": "2025-10-14 21:13:00",
            "urunler": [
                {
                    "miktar": 25000,
                    "birim_fiyat": 0.022,
                    "isim": "BUBBLE BALLS"
                },
                ...
            ]
        }
    ],
    "pagination": {
        "total": 1557,
        "limit": 50,
        "offset": 0,
        "count": 50
    }
}
```

**Örnek:**
```bash
# Son 5 sipariş
curl -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  "http://91.99.186.98/api/v1/orders?limit=5"

# Belirli müşterinin siparişleri
curl -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  "http://91.99.186.98/api/v1/orders?musteri_id=1&limit=10"

# Tarih aralığındaki siparişler
curl -H "X-API-Key: f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2" \
  "http://91.99.186.98/api/v1/orders?start_date=2025-10-01&end_date=2025-10-31"
```

#### 3.2 Tek Sipariş Getir
**GET** `/orders/{id}`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1557,
        "siparis_no": "GLR1364",
        "musteri_adi": "HELMEX",
        "urunler": [...]
    }
}
```

#### 3.3 Yeni Sipariş Ekle
**POST** `/orders`

**Request Body:**
```json
{
    "musteri_id": 1,
    "urunler": [
        {"miktar": 25000, "birim_fiyat": 0.022, "isim": "BUBBLE BALLS"},
        {"miktar": 75000, "birim_fiyat": 0.022, "isim": "WAIKIKI"},
        {"miktar": 25000, "birim_fiyat": 0.022, "isim": "GOLDEN PISTACHIO"},
        {"miktar": 25000, "birim_fiyat": 0.022, "isim": "JAMY STRAWBERRY"},
        {"miktar": 75000, "birim_fiyat": 0.022, "isim": "ABRA CADABRA"}
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Sipariş eklendi",
    "id": 1558,
    "siparis_no": "GLR1558"
}
```

#### 3.4 Sipariş Güncelle
**PUT** `/orders/{id}`

**Request Body:**
```json
{
    "durum": 2,
    "urunler": [...]
}
```

#### 3.5 Sipariş Sil
**DELETE** `/orders/{id}`

---

## 📊 Response Format

### Başarılı Response:
```json
{
    "success": true,
    "data": {...},
    "pagination": {...}  // Sadece liste endpoint'lerinde
}
```

### Hata Response:
```json
{
    "success": false,
    "error": "Hata mesajı",
    "timestamp": "2025-10-26 23:00:00"
}
```

## 🔐 HTTP Status Codes

- `200 OK` - İşlem başarılı
- `201 Created` - Yeni kayıt oluşturuldu
- `400 Bad Request` - Geçersiz istek
- `401 Unauthorized` - API key eksik veya geçersiz
- `404 Not Found` - Kayıt bulunamadı
- `405 Method Not Allowed` - Desteklenmeyen HTTP metodu
- `500 Internal Server Error` - Sunucu hatası

## 🧪 Test Komutları

### Tüm endpoint'leri test et:
```bash
API_KEY="f2293697d94aa294ee3a25fab8a9398a72caf768bda2bdcb10d23ce7e17010b2"

# Status
curl -H "X-API-Key: $API_KEY" http://91.99.186.98/api/v1/status

# Customers
curl -H "X-API-Key: $API_KEY" "http://91.99.186.98/api/v1/customers?limit=3"

# Orders
curl -H "X-API-Key: $API_KEY" "http://91.99.186.98/api/v1/orders?limit=3"

# Single customer
curl -H "X-API-Key: $API_KEY" http://91.99.186.98/api/v1/customers/1

# Single order
curl -H "X-API-Key: $API_KEY" http://91.99.186.98/api/v1/orders/1
```

## 💡 Best Practices

1. **Rate Limiting**: Dakikada maksimum 100 istek (gelecekte eklenecek)
2. **Pagination**: Büyük veri setleri için `limit` ve `offset` kullanın
3. **Error Handling**: HTTP status code ve error mesajlarını kontrol edin
4. **API Key Security**: API key'i güvenli saklayın, public kod'a koymayın
5. **Content-Type**: POST/PUT isteklerinde `Content-Type: application/json` header'ı ekleyin

## 📝 Database Schema

### api_keys Table:
```sql
CREATE TABLE api_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    firma_id INT NOT NULL,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    permissions JSON DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL
);
```

### API Key Oluşturma:
```php
$apiKey = bin2hex(random_bytes(32));
INSERT INTO api_keys (firma_id, api_key, name, permissions) 
VALUES (16, $apiKey, 'New API Key', '{"customers":{"read":true,"write":true}}');
```

## 🚀 Gelecek Özellikler

- [ ] Rate limiting (dakikada max 100 istek)
- [ ] Webhook support (sipariş oluşturulduğunda callback)
- [ ] Bulk operations (toplu sipariş ekleme)
- [ ] File upload (sipariş ekleri)
- [ ] GraphQL endpoint
- [ ] Real-time WebSocket
- [ ] API usage analytics
- [ ] IP whitelist
- [ ] OAuth2 authentication

## 📞 Support

API sorunları için:
- Email: support@hanka.com
- Backup: /root/backups/hanka_full_backup_20251026_224447.tar.gz
- Training Data: 179 examples
- Success Rate: ~90%

---

**Version:** 1.0  
**Last Updated:** 2025-10-26  
**Status:** ✅ Production Ready
