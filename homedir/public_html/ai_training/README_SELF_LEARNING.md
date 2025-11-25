# HANKA AI - Self Learning System

## 🧠 Otomatik Öğrenme Sistemi

HANKA AI, WhatsApp üzerinden gelen başarılı sorguları otomatik olarak öğrenir ve training data'ya ekler.

## 📋 Özellikler

### 1. **Otomatik Veri Toplama**
- ✅ WhatsApp başarılı sorguları
- ✅ AI Cache'den popüler sorgular (hit_count >= 2)
- ✅ Pattern matching örnekleri
- ✅ Duplikasyon önleme

### 2. **Kalite Filtreleri**
- ❌ Hata mesajları filtrelenir
- ❌ "Bulunamadı" yanıtları atlanır
- ❌ Çok kısa cevaplar (< 20 karakter) hariç
- ✅ Sadece başarılı SQL sorguları alınır

### 3. **Otomatik Çalışma**
- 🕐 Her gün saat 03:00'da otomatik çalışır
- 📊 Training data analiz edilir
- 📈 İlerleme raporu oluşturulur
- 🎉 50+ kayıt olduğunda bildirim gönderir

## 🚀 Kullanım

### Manuel Çalıştırma
```bash
# Sunucuya bağlan
ssh root@91.99.186.98

# Self-learning'i çalıştır
cd /var/www/html
php ai_self_learning.php run 16

# Training data'yı analiz et
python3 ai_training/analyze_training.py ai_training/training_corrections.jsonl
```

### Cron Job
```bash
# Cron job otomatik kurulu
# Her gün saat 03:00'da çalışır
crontab -l | grep cron_self_learning

# Log dosyasını kontrol et
tail -f /var/log/hanka_ai_learning.log
```

## 📊 Training Data Durumu

```bash
# Mevcut durumu kontrol et
wc -l /var/www/html/ai_training/training_corrections.jsonl

# Detaylı analiz
python3 /Users/ozmenkaya/hanak_new_design/analyze_training_data.py training_corrections.jsonl
```

### Hedefler
- ✅ Minimum: 50 kayıt (fine-tuning başlatılabilir)
- 🎯 İdeal: 100+ kayıt (yüksek kalite)
- 🚀 Mevcut: **24 kayıt** (26 eksik)

## 🔄 Veri Akışı

```
WhatsApp Mesajları (başarılı)
    ↓
AI Cache (SQL sorguları)
    ↓
Self Learning System
    ↓
training_corrections.jsonl
    ↓
Fine-Tuned Model (50+ kayıt)
    ↓
Daha İyi AI Cevapları
```

## 📈 İlerleme Takibi

### Günlük İstatistikler
```bash
# Son 24 saatte eklenen kayıtlar
grep "$(date +%Y-%m-%d)" /var/www/html/ai_training/training_corrections.jsonl | wc -l

# Kaynak dağılımı
grep -o '"source":"[^"]*"' /var/www/html/ai_training/training_corrections.jsonl | sort | uniq -c
```

### Kalite Kontrolü
```bash
# Duplikasyon kontrolü (olmamalı)
jq -r '.messages[1].content' training_corrections.jsonl | sort | uniq -c | sort -rn | head -10

# SQL kalitesi
jq -r '.messages[2].content | fromjson | .sql' training_corrections.jsonl | head -5
```

## 🎯 Beklenen İyileştirmeler

50+ kayıt ile fine-tuning sonrası:
- ✅ Hata oranı: %60-80 azalma
- ✅ SQL doğruluğu: %90+ başarı oranı
- ✅ Yanıt kalitesi: 3-5x artış
- ✅ Pattern matching ihtiyacı: %50 azalma

## 🔧 Yapılandırma

### Self Learning Ayarları
```php
// ai_self_learning.php
$min_quality_score = 80;    // Minimum kalite skoru
$min_hit_count = 2;          // Minimum cache hit sayısı
$max_whatsapp = 50;          // Max WhatsApp kayıt
$max_cache = 30;             // Max cache kayıt
```

### Cron Job Zamanlaması
```bash
# Varsayılan: Her gün saat 03:00
0 3 * * * /var/www/html/ai_training/cron_self_learning.sh

# Daha sık çalıştırma (her 6 saatte)
0 */6 * * * /var/www/html/ai_training/cron_self_learning.sh

# Sadece hafta içi
0 3 * * 1-5 /var/www/html/ai_training/cron_self_learning.sh
```

## 📝 Log Dosyaları

```bash
# Real-time izleme
tail -f /var/log/hanka_ai_learning.log

# Son çalıştırma
tail -100 /var/log/hanka_ai_learning.log

# Hata araştırma
grep -i error /var/log/hanka_ai_learning.log
```

## 🐛 Sorun Giderme

### Training data eklenmiyor
```bash
# WhatsApp mesajlarını kontrol et
mysql -u hanka_user -p'HankaDB2025!' panelhankasys_crm2 \
  -e "SELECT COUNT(*) FROM whatsapp_messages WHERE firma_id=16 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"

# AI Cache'i kontrol et
mysql -u hanka_user -p'HankaDB2025!' panelhankasys_crm2 \
  -e "SELECT COUNT(*) FROM ai_cache WHERE firma_id=16 AND hit_count >= 2"
```

### Cron job çalışmıyor
```bash
# Cron service kontrolü
systemctl status cron

# Manuel test
/var/www/html/ai_training/cron_self_learning.sh

# Script izinleri
ls -l /var/www/html/ai_training/*.sh
```

### Duplikasyon sorunu
```bash
# Duplike kayıtları temizle
cd /var/www/html/ai_training
cp training_corrections.jsonl training_corrections.backup.jsonl
cat training_corrections.jsonl | sort -u > training_corrections_unique.jsonl
mv training_corrections_unique.jsonl training_corrections.jsonl
```

## 🎓 Öğrenme Kaynakları

### Pattern Matching Örnekleri
Sistem otomatik olarak şu pattern'leri training'e ekler:
- Müşteri sayısı sorguları
- Fason iş sorguları (tedarikçi/müşteri)
- Günlük sipariş özeti
- Planlama durumu
- Bekleyen onaylar

### WhatsApp Geçmişi
Son 7 gün içindeki başarılı sorgular otomatik toplanır:
- Response > 20 karakter
- Hata mesajı yok
- SQL query mevcut
- Cache'de kayıtlı

### AI Cache
Popüler sorgular (hit_count >= 2):
- Sık sorulan sorular
- Başarılı SQL'ler
- Doğrulanmış sonuçlar

## 🚀 Fine-Tuning Süreci

50+ kayıt toplandıktan sonra:

1. **Training data'yı indır**
```bash
scp root@91.99.186.98:/var/www/html/ai_training/training_corrections.jsonl .
```

2. **Kalite kontrolü**
```bash
python3 analyze_training_data.py training_corrections.jsonl
```

3. **OpenAI'ya yükle**
```bash
# OpenAI CLI ile
openai api fine_tunes.create \
  -t training_corrections.jsonl \
  -m gpt-4o-mini-2024-07-18 \
  --suffix "hanka-sql-v3"
```

4. **Model ID'yi güncelle**
```bash
# .env dosyasında
OPENAI_FINETUNED_MODEL=ft:gpt-4o-mini-2024-07-18:antartika:hanka-sql-v3:XXXXXX
```

## 📞 Destek

Sorun yaşarsanız:
1. Log dosyasını kontrol edin: `/var/log/hanka_ai_learning.log`
2. Manuel çalıştırın: `php ai_self_learning.php run 16`
3. Training data'yı analiz edin

---

**Son Güncelleme**: 3 Kasım 2025
**Versiyon**: 1.0
**Durum**: ✅ Aktif (Cron job çalışıyor)
