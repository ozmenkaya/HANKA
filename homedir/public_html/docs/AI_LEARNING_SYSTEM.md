# 🧠 AI Otomatik Veritabanı Öğrenme Sistemi

## 📋 Genel Bakış
Bu sistem, veritabanı şemasını **tamamen otomatik** olarak öğrenir, tablo ilişkilerini keşfeder ve her başarılı sorgudan öğrenerek kendini geliştirir.

## ✨ Özellikler

### 1. Otomatik Şema Keşfi
- ✅ 83 tablo otomatik tespit edildi
- ✅ 685 sütun analiz edildi
- ✅ Veri tipleri, NULL durumları, default değerler öğrenildi

### 2. İlişki Keşfi
- ✅ Foreign Key'ler otomatik tespit edildi
- ✅ İsim Bazlı Çıkarım: musteri_id → musteri.id
- ✅ 30 ilişki otomatik öğrenildi

### 3. Semantic Analiz
- ✅ Sütun isimlerinden anlam çıkarımı
- ✅ Türkçe görüntülenebilir isimler
- ✅ Format pattern belirleme

### 4. Sorgudan Öğrenme
- ✅ Her başarılı sorgu kaydedilir
- ✅ JOIN kalıpları çıkarılır
- ✅ SQL template oluşturulur

## 📊 Dashboard
**URL:** https://lethe.com.tr/ai_learning_dashboard.php

## 🔄 Otomatik Güncelleme
Günlük 02:00'da otomatik refresh
