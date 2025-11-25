# 🚀 PERFORMANS OPTİMİZASYONU KILAVUZU

## ✅ Yapılan Optimizasyonlar (2025-10-19)

### 1. PHP OPcache Etkinleştirildi
- **Konum:** `/etc/php/8.1/apache2/php.ini`
- **Kazanç:** %30-50 hız artışı
- **Açıklama:** PHP kodları cache'leniyor, her istekte tekrar compile edilmiyor

### 2. GZIP Compression
- **Konum:** `/var/www/html/.htaccess`
- **Kazanç:** %40-60 dosya boyutu azalması
- **Açıklama:** HTML, CSS, JS dosyaları sıkıştırılarak gönderiliyor

### 3. Browser Caching
- **Konum:** `/var/www/html/.htaccess`
- **Kazanç:** 2-3x hızlanma (tekrar ziyaretlerde)
- **Açıklama:** Statik dosyalar tarayıcıda cache'leniyor

### 4. Ortak Fonksiyon Kütüphanesi
- **Konum:** `/var/www/html/include/functions.php`
- **Kazanç:** %10-20 kod azalması
- **Fonksiyonlar:**
  - `jsonResponse()` - JSON yanıt helper
  - `sanitizeInput()` - Input güvenliği
  - `formatDate()` - Tarih formatlama
  - `formatMoney()` - Para formatlama
  - `dbError()` - Hata yönetimi
  - `uploadFile()` - Dosya upload
  - `paginate()` - Sayfalama
  - `statusBadge()` - HTML badge
  - Ve daha fazlası...

### 5. AJAX API Sistemi
- **Konum:** `/var/www/html/api/makina_api.php`
- **Konum:** `/var/www/html/js/ajax-helper.js`
- **Kazanç:** Sayfa yenileme yok = Çok daha hızlı
- **Kullanım:** Aşağıda örnekler var

### 6. Database İndexleme
- **Tablolar:** planlama, makinalar, personeller, siparisler, uretim_islem_tarihler
- **Kazanç:** 10-100x sorgu hızı
- **Açıklama:** Sık kullanılan sütunlara index eklendi

---

## 📖 AJAX Kullanım Örnekleri

### Örnek 1: Makina Durumlarını Getirme (Sayfa Yenilemeden)

```javascript
// HTML'e ajax-helper.js ekle
<script src="/js/ajax-helper.js"></script>

// Kullanım
async function makinaDurumlariniGuncelle() {
    showLoading('makinaListesi');
    
    const result = await apiGet('/api/makina_api.php', {
        action: 'getMakinaDurumlari'
    });
    
    hideLoading('makinaListesi');
    
    if (result.success) {
        // Tabloyu güncelle
        let html = '';
        result.data.forEach(makina => {
            html += `<tr>
                <td>${makina.makina_adi}</td>
                <td>${makina.durumu}</td>
                <td>${makina.aktif_is_sayisi}</td>
            </tr>`;
        });
        document.querySelector('#makinaListesi tbody').innerHTML = html;
    }
}

// Her 30 saniyede bir otomatik güncelle
setInterval(makinaDurumlariniGuncelle, 30000);
```

### Örnek 2: Form Kaydetme (AJAX ile)

```javascript
// Eski yöntem (sayfa yenileniyor):
<form action="makina_db_islem.php" method="POST">
    <input name="makina_adi" />
    <button type="submit">Kaydet</button>
</form>

// Yeni yöntem (AJAX):
<form id="makinaForm" onsubmit="return false;">
    <input name="makina_adi" id="makina_adi" />
    <button type="button" onclick="kaydetMakina()">Kaydet</button>
</form>

<script>
async function kaydetMakina() {
    const formData = formToObject('makinaForm');
    
    const result = await apiPost('/makina_db_islem.php', {
        ...formData,
        islem: 'ekle'
    });
    
    if (result.success) {
        showMessage('Makina eklendi!', 'success');
        document.getElementById('makinaForm').reset();
        // Listeyi güncelle
        makinaListesiniYenile();
    } else {
        showMessage(result.message, 'error');
    }
}
</script>
```

### Örnek 3: Canlı Arama (Debounce ile)

```html
<input type="text" id="aramaKutusu" placeholder="Ara..." />
<div id="aramaSonuclari"></div>

<script>
const aramaInput = document.getElementById('aramaKutusu');
aramaInput.addEventListener('input', debounce(async function(e) {
    const kelime = e.target.value;
    
    if (kelime.length < 2) {
        document.getElementById('aramaSonuclari').innerHTML = '';
        return;
    }
    
    const sonuclar = await apiGet('/api/arama.php', { q: kelime });
    
    if (sonuclar.success) {
        let html = '<ul>';
        sonuclar.data.forEach(item => {
            html += `<li>${item.ad}</li>`;
        });
        html += '</ul>';
        document.getElementById('aramaSonuclari').innerHTML = html;
    }
}, 300)); // 300ms bekle
</script>
```

---

## 🔧 Ortak Fonksiyonları Kullanma

### PHP Tarafında

```php
<?php
// Her dosyada include et
require_once 'include/functions.php';

// JSON yanıt
jsonResponse(true, 'Başarılı', ['id' => 123]);

// Input güvenliği
$makina_id = sanitizeInput($_POST['makina_id'], 'int');
$makina_adi = sanitizeInput($_POST['makina_adi'], 'string');

// Tarih formatlama
echo formatDate('2025-10-19 14:30:00'); // 19.10.2025 14:30

// Para formatlama
echo formatMoney(1234.56); // 1.234,56 ₺

// Durum badge
echo statusBadge('aktif'); // <span class="badge bg-success">Aktif</span>
?>
```

---

## 📊 Performans Ölçümü

### Chrome DevTools ile Test

1. **F12** → **Network** sekmesi
2. **Disable cache** işaretle
3. Sayfayı yenile (**Ctrl+R**)
4. **DOMContentLoaded** süresine bak

**Öncesi:** ~3-5 saniye  
**Sonrası:** ~0.5-1 saniye (hedef)

### Hız Testi Siteleri

- https://gtmetrix.com/
- https://developers.google.com/speed/pagespeed/insights/

---

## 🎯 Sıradaki Adımlar

### Kısa Vadede (1 Hafta)
- [ ] En çok kullanılan 5 sayfayı AJAX'a çevir
- [ ] JavaScript dosyalarını minify et
- [ ] Gereksiz vendor paketlerini kaldır

### Orta Vadede (1 Ay)
- [ ] Redis cache ekle
- [ ] Lazy loading ekle (scroll ile yükleme)
- [ ] Resim optimizasyonu (WebP formatı)

### Uzun Vadede (3 Ay)
- [ ] CDN kullan (Cloudflare)
- [ ] Service Worker (offline çalışma)
- [ ] Database query cache

---

## 🐛 Sorun Giderme

### OPcache çalışmıyor?
```bash
# Kontrol et
php -i | grep opcache

# Yeniden başlat
systemctl restart apache2
```

### GZIP çalışmıyor?
```bash
# Test et
curl -H "Accept-Encoding: gzip" -I https://lethe.com.tr/

# mod_deflate var mı?
apache2ctl -M | grep deflate
```

### AJAX hata veriyor?
- Browser Console'u aç (F12)
- Network sekmesinde request'i kontrol et
- Response'u oku

---

## 📞 Destek

Sorun yaşarsanız:
1. `/var/log/apache2/error.log` kontrol edin
2. Browser Console'da JavaScript hataları bakın
3. PHP error_log kontrol edin

**Oluşturulma:** 2025-10-19  
**Versiyon:** 1.0
