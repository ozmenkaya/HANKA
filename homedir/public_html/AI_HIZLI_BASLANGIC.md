# 🚀 AI Asistan Hızlı Başlangıç

## ✅ Kurulum Tamamlandı!

Sisteminizde şu dosyalar hazır:

```
✅ AIChatEngine.php (14KB)    - AI motor
✅ OpenAI.php (5.5KB)         - API wrapper
✅ ai_chat.php (1.5KB)        - Chat endpoint
✅ ai_feedback.php (3.1KB)    - Feedback endpoint
✅ header.php (25KB)          - UI ile arama çubuğu
✅ 4 veritabanı tablosu       - Hazır
✅ 5 örnek prompt             - Yüklü
```

## 🔑 SON ADIM: OpenAI API Key

`.env` dosyasını düzenleyin:

```bash
nano /var/www/html/.env
```

Şunu bulun:
```
OPENAI_API_KEY=your-openai-api-key-here
```

Değiştirin:
```
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxx
```

API key almak için: https://platform.openai.com/api-keys

## 🎯 İlk Testi Yapın

1. Siteye giriş yapın: http://91.99.186.98/
2. Dashboard'da üstte AI arama çubuğunu göreceksiniz
3. Şunu yazın: **"Bu ay kaç sipariş teslim edildi?"**
4. Enter'a basın veya "Sor" butonuna tıklayın
5. Modal açılacak ve AI cevap verecek! 🎉

## 💡 Deneyebileceğiniz Sorular

```
✅ "Helmex firması sipariş ortalaması nedir?"
✅ "Bu ay kaç sipariş teslim edildi?"
✅ "En çok sipariş veren müşteri kim?"
✅ "Son 30 gün üretim toplamı nedir?"
✅ "Makina bazında üretim miktarları"
✅ "Geciken siparişler listesi"
```

## 🧠 Self-Learning Nasıl Kullanılır?

1. **Soru sorun** - AI cevap versin
2. **Cevabı değerlendirin** - 1-5 yıldız verin
3. **Düzeltme yapın** (isteğe bağlı) - Yanlışsa düzeltin
4. **AI öğrenir** - Bir dahaki sefere daha iyi cevap verir!

⭐⭐⭐⭐⭐ = Mükemmel, AI bu sorguyu knowledge base'e kaydeder
⭐⭐ = Kötü, AI bu yaklaşımı kullanmayı azaltır

## 📊 Admin Paneli

http://91.99.186.98/index.php?url=ai_ayarlar

- API key yönetimi
- Son analizler
- Kullanım istatistikleri

## 🐛 Sorun mu var?

### "AI cevap vermiyor"
```bash
# .env kontrolü
cat /var/www/html/.env | grep OPENAI_API_KEY
```

### "SQL hatası"
→ Firma_id kontrol edin, WHERE firma_id=16 olmalı

### "Yavaş"
→ Normal, OpenAI API 2-5 saniye sürebilir

## 📖 Detaylı Dokümantasyon

Daha fazla bilgi: `/var/www/html/AI_SISTEM_DOKUMAN.md`

## 💰 Maliyet

GPT-4o-mini çok ucuz:
- 1000 soru ≈ 1₺
- Aylık ~10.000 soru ≈ 10₺

## ✨ Sistem Özellikleri

✅ **Türkçe doğal dil anlama**
✅ **Otomatik SQL oluşturma**
✅ **Self-learning (kendini eğitiyor)**
✅ **Firma bazlı multi-tenant**
✅ **Güvenli (sadece SELECT)**
✅ **Hızlı (2-5 saniye)**

---

**Hazır! Artık AI asistanınızı kullanabilirsiniz! 🚀**
