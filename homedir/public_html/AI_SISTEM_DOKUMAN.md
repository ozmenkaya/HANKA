# 🤖 HANKA AI ASISTAN SİSTEMİ

## 📋 Genel Bakış

Self-learning (kendini eğiten) AI asistan sistemi. Firma bazlı veriler üzerinde doğal dil (Türkçe) sorguları çalıştırır, SQL oluşturur ve öğrenir.

## 🎯 Özellikler

✅ **Doğal Dil İşleme**: Türkçe soruları SQL sorgularına dönüştürür
✅ **Self-Learning**: Kullanıcı geri bildirimleriyle öğrenir
✅ **Firma Bazlı**: Multi-tenant yapı, her firma kendi verisini görür
✅ **Context-Aware**: Firma geçmişi ve verilerini bilir
✅ **RAG Architecture**: Geçmiş sorular ve cevaplardan öğrenir

## 📁 Dosya Yapısı

```
/var/www/html/
├── include/
│   ├── AIChatEngine.php        # Ana AI motor (14KB)
│   ├── OpenAI.php               # OpenAI API wrapper (5.5KB)
│   └── header.php               # AI arama çubuğu UI
├── ai_chat.php                  # AJAX chat endpoint (1.5KB)
├── ai_feedback.php              # Geri bildirim endpoint (3.1KB)
├── rapor_ai_analiz.php         # Rapor analiz endpoint (5.3KB)
└── ai_ayarlar.php              # Admin ayarlar sayfası (8.1KB)
```

## 🗄️ Veritabanı Tabloları

### 1. ai_chat_history
Tüm sohbet geçmişi:
- `soru`: Kullanıcı sorusu
- `cevap`: AI cevabı
- `sql_query`: Oluşturulan SQL
- `sonuc_sayisi`: Dönen kayıt sayısı
- `cevap_suresi`: Yanıt süresi (saniye)
- `tarih`: İşlem zamanı

### 2. ai_knowledge_base
Firma bazlı öğrenme veritabanı:
- `kategori`: musteri, uretim, siparis, makina, personel
- `anahtar_kelime`: Arama için keywords
- `icerik`: JSON formatında context
- `embedding`: Vector embedding (gelecek)
- `kullanim_sayisi`: Kaç kez kullanıldı
- `basari_orani`: 0-100 arası başarı skoru
- `son_kullanim`: Son kullanım tarihi

### 3. ai_prompts
SQL generation templates:
- `prompt_tipi`: Sorgu tipi
- `soru_ornegi`: Örnek soru
- `sistem_promptu`: System prompt
- `ornek_sql`: SQL şablonu
- `aktif`: 1/0

### 4. ai_feedback
Kullanıcı geri bildirimleri:
- `chat_id`: İlişkili sohbet
- `rating`: 1-5 yıldız
- `dogru_mu`: 1/0
- `duzeltme`: Kullanıcı düzeltmesi

### 5. ai_analiz_log
Rapor analiz logları (önceden vardı)

## 🔧 Kurulum

### 1. OpenAI API Key Ayarla

`.env` dosyasını düzenle:
```bash
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o-mini
```

### 2. Veritabanı Tabloları

Otomatik oluşturuldu ✅

### 3. Örnek Promptlar

5 adet örnek prompt eklendi ✅

## 🎮 Kullanım

### Dashboard Arama

1. Dashboard'da üst kısımda AI arama çubuğu göreceksiniz
2. Sorunuzu Türkçe yazın (örnek: "Helmex firması sipariş ortalaması nedir?")
3. Enter veya "Sor" butonuna tıklayın
4. AI yanıtı modal pencerede açılır:
   - Kısa özet yanıt
   - Detaylı veri tablosu
   - SQL sorgusu (açılabilir)
   - Geri bildirim butonları

### Örnek Sorular

```
✅ "Helmex firması sipariş ortalaması nedir?"
✅ "Bu ay kaç sipariş teslim edildi?"
✅ "Gökhan usta bu ay kaç makina arızası yaptı?"
✅ "En çok sipariş veren müşteri kim?"
✅ "Son 30 gün üretim toplamı nedir?"
✅ "Makina bazında üretim miktarları"
✅ "Personel performans raporu"
✅ "Geciken siparişler listesi"
```

## 🧠 Self-Learning Nasıl Çalışır?

### 1. Soru Gelir
Kullanıcı: "Helmex sipariş ortalaması?"

### 2. Context Toplanır
- Firma adı, istatistikler
- Veritabanı şeması
- Benzer geçmiş sorular (FULLTEXT search)
- Başarılı SQL şablonları

### 3. OpenAI SQL Oluşturur
GPT-4o-mini modeli context ile SQL üretir

### 4. SQL Çalıştırılır
Güvenlik: Sadece SELECT, WHERE firma_id zorunlu

### 5. Sonuç Formatlanır
OpenAI verilerden Türkçe özet oluşturur

### 6. Kayıt ve Öğrenme
- ai_chat_history'e kaydedilir
- ai_knowledge_base güncellenir
- Anahtar kelimeler çıkarılır

### 7. Geri Bildirim
Kullanıcı 1-5 yıldız verir:
- ⭐⭐⭐⭐⭐ (5) → basari_orani +5
- ⭐⭐ (2) → basari_orani -5
- Düzeltme yazarsa AI bir sonraki sefere daha iyi

## 📊 Admin Panel

`/index.php?url=ai_ayarlar`

- API key yönetimi
- Son 10 analiz
- Kullanım istatistikleri
- Token maliyeti

## 🔒 Güvenlik

✅ Sadece SELECT sorguları
✅ WHERE firma_id = X zorunlu
✅ XSS koruması (strip_tags)
✅ PDO Prepared Statements
✅ Oturum kontrolü

## 💰 Maliyet

GPT-4o-mini:
- Input: $0.15 / 1M token
- Output: $0.60 / 1M token

Ortalama sorgu: ~500 token = $0.0003 (0.01₺)

## 🚀 Gelecek Geliştirmeler

### Faz 1 (Tamamlandı ✅)
- [x] Database schema
- [x] Dashboard search bar
- [x] Natural language to SQL
- [x] Basic learning system

### Faz 2 (Yapılacak)
- [ ] Vector embeddings (Pinecone/Qdrant)
- [ ] Semantic search
- [ ] Auto knowledge base population
- [ ] Sık sorulan sorular otomasyonu

### Faz 3 (Yapılacak)
- [ ] Predictive analytics ("Termin süresi ne olur?")
- [ ] Time series forecasting
- [ ] Anomaly detection
- [ ] Personalized suggestions

### Faz 4 (Yapılacak)
- [ ] Voice input (Türkçe STT)
- [ ] Multi-modal (grafik/tablo seçimi)
- [ ] Export results (PDF/Excel)
- [ ] Scheduled reports

## 🐛 Sorun Giderme

### "OpenAI API error"
→ `.env` dosyasında API key kontrolü

### "SQL hatası: firma_id"
→ Tüm tablolarda firma_id kontrolü

### "Boş sonuç"
→ SQL sorgusu hatalı olabilir, feedback verin

### "Yavaş yanıt"
→ OpenAI API latency, normal (2-5 saniye)

## 📝 Notlar

- Sistem GPT-4o-mini kullanır (hızlı + ucuz)
- Tüm sorular loglanır (GDPR uyumluluğu için kontrol)
- Firma_id=16 için test edildi
- Multi-tenant destekli

## 👨‍💻 Geliştirici Notları

### AI Engine Customize

`/var/www/html/include/AIChatEngine.php` içinde:

```php
// Kategori tespiti
private function detectCategory($question) {
    // Yeni kategoriler eklenebilir
}

// Context builder
private function buildFirmaContext() {
    // Daha fazla veri eklenebilir
}

// SQL generation
private function generateSQL() {
    // System prompt customize edilebilir
}
```

### Prompt Engineering

`ai_prompts` tablosuna yeni şablonlar:

```sql
INSERT INTO ai_prompts (firma_id, prompt_tipi, soru_ornegi, sistem_promptu, ornek_sql)
VALUES (16, 'yeni_tip', 'örnek soru', 'sistem promptu', 'SELECT ...');
```

## 📞 Destek

Sorularınız için:
- GitHub Issues
- Email: support@hankasys.com

---

**Versiyon:** 1.0.0  
**Tarih:** 24 Ekim 2024  
**Geliştirici:** Hanka Sys Team
