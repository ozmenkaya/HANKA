# 📊 Excel Raporları Sistemi

## 🎯 Özellikler

### ✅ Tamamlanan
- **Dinamik Rapor Şablonları**: İstediğiniz veri kaynağı ve sütunları seçerek rapor şablonu oluşturma
- **Tarih Aralığı Seçimi**: Raporları belirli tarih aralıkları için çekme
- **Excel İndirme**: UTF-8 destekli, biçimlendirilmiş Excel dosyaları
- **Rapor Yönetimi**: Şablonları kaydetme, silme ve yeniden kullanma

## 📋 Kullanım

### 1. Yeni Rapor Şablonu Oluşturma

1. **Raporlar > Excel Raporları** menüsüne gidin
2. **"Yeni Rapor Şablonu"** butonuna tıklayın
3. Rapor ayarlarını yapın:
   - **Rapor Adı**: Örn: "Aylık Üretim Raporu"
   - **Veri Kaynağı**: 6 seçenek
     - Üretim Verileri
     - Siparişler
     - Planlama
     - Makinalar
     - Personel Performansı
     - Stok Hareketleri
   - **Sütunlar**: Excel'de görünmesini istediğiniz sütunları seçin
4. **"Rapor Şablonunu Kaydet"** butonuna tıklayın

### 2. Excel İndirme

1. Kayıtlı raporlar listesinde **"İndir"** butonuna tıklayın
2. **Tarih aralığı** seçin:
   - Başlangıç Tarihi
   - Bitiş Tarihi
3. **"Excel İndir"** butonuna tıklayın
4. Excel dosyası otomatik indirilecek

### 3. Rapor Silme

- Raporlar listesinde **"Sil"** butonuna tıklayarak silebilirsiniz

## �� Veri Kaynakları ve Sütunlar

### 🏭 Üretim Verileri
- Tarih
- Sipariş No
- Ürün Adı
- Makina
- Personel
- Üretilen Adet
- Fire Adet
- Başlatma Tarihi
- Bitiş Tarihi

### 📦 Siparişler
- Sipariş No
- Müşteri
- İşin Adı
- Adet
- Termin Tarihi
- Durum
- Oluşturma Tarihi

### 📅 Planlama
- Sipariş No
- Ürün
- Adet
- Mevcut Aşama
- Toplam Aşama
- Durum
- Termin

### 🔧 Makinalar
- Makina Adı
- Durum
- Toplam İş
- Tamamlanan İş
- Verimlilik %

### 👷 Personel Performansı
- Personel
- Makina
- Toplam İş
- Tamamlanan
- Üretilen Adet
- Verimlilik %

### 📦 Stok Hareketleri
- Stok Adı
- Hareket Tipi
- Miktar
- Birim
- Tarih
- Açıklama

## 🗄️ Veritabanı

### Tablo: `rapor_sablonlari`
```sql
CREATE TABLE rapor_sablonlari (
  id INT PRIMARY KEY AUTO_INCREMENT,
  firma_id INT NOT NULL,
  kullanici_id INT NOT NULL,
  rapor_adi VARCHAR(255) NOT NULL,
  veri_kaynagi VARCHAR(50) NOT NULL,
  sutunlar TEXT NOT NULL,
  olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_firma (firma_id)
);
```

## 📁 Dosyalar

### Frontend
- `/var/www/html/raporlar.php` - Ana rapor sayfası (14KB)

### Backend
- `/var/www/html/raporlar_db_islem.php` - AJAX işlemleri
  * rapor-kaydet: Yeni şablon kaydetme
  * rapor-sil: Şablon silme
  
- `/var/www/html/rapor_excel.php` - Excel oluşturucu
  * UTF-8 destekli
  * XML tabanlı .xls formatı
  * Biçimlendirilmiş (başlık, border, renk)

### Menü
- `/var/www/html/include/sol_menu.php` - "Excel Raporları" linki eklendi

## 🚀 Özellikler

### ✅ Avantajlar
- **Composer gerektirmez**: Native PHP XMLWriter kullanır
- **UTF-8 Türkçe karakter desteği**: Excel'de düzgün gösterilir
- **Hızlı**: Hafif ve optimize edilmiş
- **Esnek**: İstediğiniz sütunları seçebilirsiniz
- **Yeniden kullanılabilir**: Şablonları kaydedip tekrar kullanın

### 📈 Gelecek İyileştirmeler
- Grafik desteği
- PDF export
- Otomatik zamanlı raporlar (cron job)
- Email ile rapor gönderme
- Daha fazla veri kaynağı

## 🔗 URL'ler

- Ana Sayfa: `https://lethe.com.tr/index.php?url=raporlar`
- Menü: Raporlar > Excel Raporları

## 📝 Notlar

- Excel dosyaları `.xls` formatında (Microsoft Excel uyumlu)
- UTF-8 BOM ile kaydedilir (Türkçe karakter sorunu yok)
- Tarih filtreleri veritabanı seviyesinde yapılır (hızlı)
- Firma bazlı izolasyon (her firma sadece kendi raporlarını görür)

---
**Oluşturma Tarihi**: 19 Ekim 2025  
**Versiyon**: 1.0  
**Durum**: ✅ Hazır
