# 🤖 HANKA AI Asistan Sistemi

## 📋 Genel Bakış
Firmanıza özel, sürekli öğrenen AI asistan sistemi.

## 🎯 Özellikler

### 1. Doğal Dil Sorguları
```
❓ "Helmex firması sipariş ortalaması nedir?"
✅ Yanıt: "Helmex firması son 6 ayda ortalama 45 sipariş verdi, 
   ortalama sipariş değeri 12.500 TL"

❓ "Gökhan usta bu ay makina kaç kez arızalandı?"
✅ Yanıt: "Gökhan Yılmaz bu ay Heidelberg XL105'te çalıştı. 
   Makina 3 kez arızalandı (toplam 4.5 saat duruş)"

❓ "Offset makinası bu ay kaç adet üretim yaptı?"
✅ Yanıt: "Offset makinası (Heidelberg XL105) bu ay 125.000 
   adet ürün üretti, %92 verimlilik"

❓ "Son 2 ay kaç iş teslim edildi, önceki 2 ay ile karşılaştır"
✅ Yanıt: "Son 2 ay: 89 iş teslim edildi. Önceki 2 ay: 76 iş. 
   %17 artış var."

❓ "1000 adet kartvizit işi için termin süresi ne olmalı?"
✅ Yanıt: "Benzer işlerin analizi: Kartvizit işleri ortalama 
   3-5 gün sürüyor. 1000 adet için önerilen termin: 4 gün"
```

## 🏗️ Sistem Mimarisi

### Katman 1: Veri Toplayıcı (Data Collector)
```php
- Siparişler tablosu → vektör DB
- Üretim kayıtları → vektör DB  
- Makina arıza logları → vektör DB
- Personel performans → vektör DB
- Müşteri bilgileri → vektör DB
```

### Katman 2: Vektör Veritabanı (Vector DB)
```
- Pinecone (cloud, ücretsiz 100K vektör)
- VEYA Qdrant (self-hosted, sınırsız)
- Her kayıt → embedding → vektör
```

### Katman 3: AI Engine
```
Model: GPT-4 Turbo (RAG sistemi)
1. Kullanıcı sorusu → embedding
2. Benzer vektörler bul (similarity search)
3. İlgili verileri çek
4. GPT'ye gönder + context
5. Doğal dil yanıt oluştur
```

### Katman 4: Öğrenme Sistemi (Self-Learning)
```
- Her soru-cevap kaydedilir
- Geri bildirim toplanır (👍 👎)
- İyi yanıtlar → fine-tuning dataset
- Aylık model güncelleme
```

## 📊 Veritabanı Şeması

### ai_embeddings (Vektör Cache)
```sql
CREATE TABLE ai_embeddings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_id INT NOT NULL,
    veri_tipi ENUM("siparis", "uretim", "makina", "personel", "musteri"),
    kayit_id INT NOT NULL,
    embedding JSON NOT NULL,
    metadata JSON,
    INDEX (firma_id, veri_tipi)
);
```

### ai_sohbet_gecmisi
```sql
CREATE TABLE ai_sohbet_gecmisi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_id INT NOT NULL,
    kullanici_id INT NOT NULL,
    soru TEXT NOT NULL,
    cevap TEXT NOT NULL,
    kullanilan_veriler JSON,
    geri_bildirim ENUM("iyi", "kotu", "yok") DEFAULT "yok",
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### ai_ogrenme_dataset
```sql
CREATE TABLE ai_ogrenme_dataset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_id INT NOT NULL,
    soru TEXT NOT NULL,
    ideal_cevap TEXT NOT NULL,
    oncelik INT DEFAULT 1,
    durum ENUM("aktif", "egitimde", "tamamlandi") DEFAULT "aktif"
);
```

## 🚀 Kurulum Aşamaları

### Faz 1: Temel Altyapı (1 gün)
- ✅ OpenAI helper class (mevcut)
- 🔲 Vektör DB entegrasyonu
- 🔲 Embedding oluşturucu
- 🔲 Similarity search

### Faz 2: Dashboard Arayüz (1 gün)
- 🔲 Arama çubuğu komponenti
- 🔲 Gerçek zamanlı yanıt UI
- 🔲 Sohbet geçmişi
- 🔲 Önerilen sorular

### Faz 3: Veri Entegrasyonu (2 gün)
- 🔲 Siparişler → embedding
- 🔲 Üretim → embedding
- 🔲 Makinalar → embedding
- 🔲 Personel → embedding
- 🔲 Otomatik sync (cronjob)

### Faz 4: Akıllı Sorgular (2 gün)
- 🔲 Doğal dil işleme
- 🔲 Context oluşturma
- 🔲 SQL query generator
- 🔲 Yanıt formatter

### Faz 5: Öğrenme Sistemi (1 gün)
- 🔲 Feedback mekanizması
- 🔲 Dataset builder
- 🔲 Fine-tuning pipeline

## 💰 Maliyet Tahmini

### Aylık İşletim
```
Vektör DB (Pinecone free tier): 0₺
OpenAI API:
  - Embedding: ~$0.02 / 100K token
  - GPT-4: ~$30 / 1M token
  - Tahmin: ~100₺/ay (günde 100 soru için)
```

### Geliştirme
```
Total: 7 gün geliştirme
```

## 📝 Kullanım Senaryoları

### Senaryo 1: Sipariş Analizi
```
👤 "Helmex firması son 3 ayda kaç sipariş verdi?"
🤖 Adımlar:
   1. "Helmex" → müşteri tablosu
   2. Son 3 ay siparişler → vector search
   3. COUNT + GROUP BY
   4. Yanıt: "Helmex 3 ayda 67 sipariş verdi"
```

### Senaryo 2: Personel Performans
```
👤 "Gökhan usta bu ay hangi makinada çalıştı?"
🤖 Adımlar:
   1. "Gökhan" → personel tablosu (fuzzy match)
   2. Bu ay üretim kayıtları → vector search
   3. JOIN makinalar
   4. Yanıt: "Gökhan Yılmaz bu ay Heidelberg XL105 ve 
      Komori Lithrone makinalarında çalıştı"
```

### Senaryo 3: Termin Tahmini
```
👤 "1000 adet katalog işi için termin ne olmalı?"
🤖 Adımlar:
   1. "katalog" → benzer ürünler (embedding similarity)
   2. Geçmiş süre verileri → average
   3. Mevcut iş yükü → queue analysis
   4. Yanıt: "Benzer katalog işleri 5-7 gün sürdü. 
      Mevcut iş yükü normal, önerilen termin: 6 gün"
```

## 🔐 Güvenlik

- Firma bazlı data isolation
- API key encryption
- Rate limiting
- Audit logging
- GDPR uyumlu veri saklama

## 📈 Metrikler

Dashboard'da gösterilecek:
- Günlük soru sayısı
- Ortalama yanıt süresi
- Kullanıcı memnuniyeti (👍/👎 oranı)
- En çok sorulan konular
- Model doğruluk oranı

---

## 🎯 İLK ADIM: Basit Versiyonu Kuralım mı?

Minimal başlayıp genişletelim:
1. Dashboard'a arama çubuğu
2. Temel SQL sorguları (embedding olmadan)
3. Sonra vektör DB ekleriz
4. Sonra öğrenme sistemi

Ne dersiniz? Başlayalım mı?
