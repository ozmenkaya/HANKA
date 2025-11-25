# 🤖 HANKA WhatsApp AI Agent Kurulum Rehberi

## 📋 Gereksinimler
- Twilio hesabı (ücretsiz trial hesabı yeterli)
- WhatsApp Business numarası (veya Twilio Sandbox)
- SSL sertifikası (webhook için HTTPS zorunlu)

## 🚀 Adım 1: Twilio Kurulumu

### 1.1 Twilio Hesabı Oluştur
1. https://www.twilio.com adresine git
2. Ücretsiz hesap aç (trial $15 kredi ile gelir)
3. Dashboard'a giriş yap

### 1.2 WhatsApp Sandbox Aktifleştir
1. Twilio Console → Messaging → Try it out → Send a WhatsApp message
2. QR kod'u telefonunuzla tara veya verilen numaraya mesaj at
3. Örnek: `join <kod>` (sizin sandbox kodunuz farklı olacak)
4. Doğrulama mesajı gelecek

### 1.3 Credentials Al
Twilio Console'dan:
- **Account SID**: AC1234567890... (Dashboard'da görünür)
- **Auth Token**: (Show butonuna tıklayarak görebilirsin)

## 🔧 Adım 2: Sunucu Kurulumu

### 2.1 Veritabanı Tabloları Oluştur
```bash
ssh root@91.99.186.98
cd /var/www/html
mysql -u hanka_user -p'HankaDB2025!' panelhankasys_crm2 < sql/whatsapp_tables.sql
```

### 2.2 Webhook Dosyasını Yükle
```bash
# Local'den
cd /Users/ozmenkaya/hanak_new_design/homedir/public_html
scp whatsapp_webhook.php root@91.99.186.98:/var/www/html/
```

### 2.3 Environment Variables Ayarla
Sunucuda `.env` dosyası oluştur:
```bash
cd /var/www/html
nano .env
```

İçeriği:
```env
TWILIO_ACCOUNT_SID=AC1234567890abcdef...
TWILIO_AUTH_TOKEN=your_auth_token_here
```

Dosyayı koru:
```bash
chmod 600 .env
```

`whatsapp_webhook.php` dosyasını güncelle (ilk satırlar):
```php
<?php
// .env dosyasını yükle
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
```

## 🌐 Adım 3: Webhook URL Ayarla

### 3.1 Twilio'da Webhook URL'i Kaydet
1. Twilio Console → Messaging → Settings → WhatsApp sandbox settings
2. **WHEN A MESSAGE COMES IN** alanına:
   ```
   https://lethe.com.tr/whatsapp_webhook.php
   ```
3. Method: **POST**
4. **Save** butonuna tıkla

### 3.2 Test Et
WhatsApp'tan sandbox numarasına mesaj gönder:
```
/help
```

Yanıt almalısın:
```
🤖 HANKA AI Assistant

Komutlar:
• /siparisler - Sipariş özeti
• /planlama - Planlama durumu
• /help veya ? - Bu mesaj
```

## 💬 Kullanım

### Komutlar
```
/siparisler     → Sipariş istatistikleri
/planlama       → Planlama durumu
/help veya ?    → Yardım mesajı
```

### Doğal Dil Sorguları
```
Bugün kaç sipariş var?
Son 7 günün üretim raporu
Termin geçmiş siparişler
GLR1362 siparişi nerede?
Hangi makinalar boşta?
```

## 🔒 Güvenlik

### Twilio Signature Validation Ekle
`whatsapp_webhook.php` dosyasına güvenlik ekle:

```php
<?php
// Twilio signature validation
function validateTwilioSignature() {
    $auth_token = getenv('TWILIO_AUTH_TOKEN');
    $signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    $validator = new Twilio\Security\RequestValidator($auth_token);
    
    if (!$validator->validate($signature, $url, $_POST)) {
        http_response_code(403);
        die('Invalid signature');
    }
}

// İlk satırda çağır
validateTwilioSignature();
```

Twilio SDK yükle:
```bash
composer require twilio/sdk
```

### IP Whitelist (Opsiyonel)
Twilio IP'lerini `.htaccess` ile beyaz listeye al:
```apache
<Files "whatsapp_webhook.php">
    Order Deny,Allow
    Deny from all
    # Twilio IPs
    Allow from 54.172.60.0/23
    Allow from 54.244.51.0/24
    # ... (tüm Twilio IP'leri)
</Files>
```

## 📊 Monitoring

### Log Kontrolü
```bash
# Apache error log
tail -f /var/log/apache2/error.log | grep WhatsApp

# Mesaj geçmişi
mysql -u hanka_user -p'HankaDB2025!' panelhankasys_crm2 \
  -e "SELECT * FROM whatsapp_messages ORDER BY created_at DESC LIMIT 10;"
```

### Dashboard Ekle
`whatsapp_dashboard.php` oluştur:
```php
<?php
require_once "include/oturum_kontrol.php";

$stmt = $conn->prepare("
    SELECT 
        w.*,
        CONCAT(p.ad, ' ', p.soyad) as kullanici
    FROM whatsapp_messages w
    LEFT JOIN personeller p ON w.user_id = p.id
    WHERE p.firma_id = :firma_id
    ORDER BY w.created_at DESC
    LIMIT 50
");
$stmt->execute([':firma_id' => $_SESSION['firma_id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h5>📱 WhatsApp Mesaj Geçmişi</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kullanıcı</th>
                    <th>Mesaj</th>
                    <th>Cevap</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                <tr>
                    <td><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($msg['kullanici']); ?></td>
                    <td><?php echo htmlspecialchars(substr($msg['message'], 0, 50)); ?>...</td>
                    <td><?php echo htmlspecialchars(substr($msg['response'], 0, 50)); ?>...</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

## 🚀 Production'a Geçiş

### WhatsApp Business API
Trial'dan çıkıp production'a geçmek için:

1. **Twilio WhatsApp Business Profile** oluştur
2. **Facebook Business Manager** ile entegre et
3. **Whats

App Business numarası** al (kendi numaranı kullanabilirsin)
4. **Meta onayı** al (24-48 saat sürer)

### Rate Limits
- Trial: 1 mesaj/saniye
- Production: 80 mesaj/saniye (varsayılan)

## 💰 Fiyatlandırma

### Twilio WhatsApp (2024 fiyatları)
- **Session içi mesaj**: $0.005 / mesaj
- **Session dışı mesaj**: $0.01 / mesaj
- **Session süresi**: 24 saat

### Örnek Maliyet
- 100 mesaj/gün = ~$15/ay
- 500 mesaj/gün = ~$75/ay
- 1000 mesaj/gün = ~$150/ay

## 🐛 Troubleshooting

### "Message not delivered" hatası
- Webhook URL'nin HTTPS olduğundan emin ol
- Twilio Dashboard → Logs'da hata detaylarını kontrol et
- `whatsapp_webhook.php` dosyasının 200 OK döndüğünü doğrula

### AI cevap vermiyor
- `AIChatEngine.php` dosyasının doğru yolda olduğunu kontrol et
- OpenAI API key'inin aktif olduğunu doğrula
- Apache error log'ları kontrol et

### Türkçe karakter sorunu
- Database charset: `utf8mb4_unicode_ci`
- PHP dosyaları: UTF-8 without BOM
- TwiML response: `<?xml version="1.0" encoding="UTF-8"?>`

## 📞 Destek

Sorun yaşarsanız:
1. `/var/log/apache2/error.log` kontrol et
2. Twilio Debugger kullan (Console → Monitor → Logs)
3. `whatsapp_messages` tablosunu incele

## 🎉 Başarılı Kurulum!

Artık WhatsApp üzerinden AI asistanınızla konuşabilirsiniz:

```
Sen: Bugün kaç sipariş var?
AI: 📊 Bugün 47 sipariş var. 23'ü işlemde, 18'i planlanmış, 6'sı onay bekliyor.

Sen: GLR1362 nerede?
AI: GLR1362 siparişi şu anda Kesim departmanında, Makina #5'te işlem görüyor. 
    Tamamlanma oranı: %65
```

🚀 **İyi kullanımlar!**
