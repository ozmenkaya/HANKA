-- ============================================
-- TÜM SİPARİŞLERİ TAMAMLANDI YAPMA - FULL MIGRATION
-- ============================================
-- Tarih: 2025-11-27
-- Amaç: Tüm aktif siparişleri ve planlamaları "tamamlandı/bitti" 
--       durumuna getirerek üretim bitmiş gibi göstermek
-- Hedef: 
--   1. Planlama sayfasında "Bitenler" sekmesinde görünsün
--   2. Makina listelerinden kaybolsun
-- ============================================

-- YEDEKLER ALINDI:
-- /root/backup_siparisler_tamamlandi_20251127_232716.sql (2.1 MB)
-- /root/backup_planlama_durum_20251127_231025.sql (485 KB)

-- ============================================
-- ADIM 1: SİPARİŞLERİ TAMAMLANDI YAP
-- ============================================

-- Kontrol: Kaç sipariş güncellenecek?
SELECT 
    COUNT(*) as guncellenecek_siparis
FROM siparisler 
WHERE firma_id = 16 
  AND onay_baslangic_durum = 'evet' 
  AND islem NOT IN ('tamamlandi', 'iptal', 'teslim_edildi');
-- Sonuç: 1329 sipariş

-- Mevcut durum
SELECT islem, COUNT(*) as adet 
FROM siparisler 
WHERE firma_id = 16 
  AND onay_baslangic_durum = 'evet'
GROUP BY islem;
-- Önceki:
-- yeni: 1247
-- islemde: 82
-- tamamlandi: 88

-- Güncelleme
UPDATE siparisler 
SET islem = 'tamamlandi'
WHERE firma_id = 16 
  AND onay_baslangic_durum = 'evet' 
  AND islem NOT IN ('tamamlandi', 'iptal', 'teslim_edildi');
-- Güncellenen: 1329 kayıt

-- Sonuç
SELECT islem, COUNT(*) as adet 
FROM siparisler 
WHERE firma_id = 16 
GROUP BY islem;
-- Sonrası:
-- tamamlandi: 1417
-- yeni: 19 (onay bekleyenler)
-- iptal: 64

-- ============================================
-- ADIM 2: PLANLAMA DURUMLARINI BİTTİ YAP
-- ============================================

-- Kontrol: Makina listelerinde görünen işler
SELECT COUNT(*) as makina_listesi_is
FROM planlama 
WHERE firma_id = 16 
  AND durum IN('baslamadi','basladi','beklemede') 
  AND onay_durum = 'evet' 
  AND aktar_durum = 'orijinal';
-- Öncesi: 1 iş

-- Güncelleme (son kalan iş)
UPDATE planlama 
SET durum = 'bitti'
WHERE id = 3007 
  AND firma_id = 16;
-- Güncellenen: 1 kayıt

-- Final kontrol
SELECT COUNT(*) as makina_listesi_is
FROM planlama 
WHERE firma_id = 16 
  AND durum IN('baslamadi','basladi','beklemede') 
  AND onay_durum = 'evet' 
  AND aktar_durum = 'orijinal';
-- Sonuç: 0 iş (makina listelerinde gözükmüyor!)

-- ============================================
-- SONUÇLAR
-- ============================================

-- Planlama "Bitenler" sekmesinde görünecek siparişler:
SELECT COUNT(*) as bitenler_sekmesi
FROM siparisler 
WHERE firma_id = 16 
  AND islem = 'tamamlandi' 
  AND (aktif = 1 OR aktif IS NULL)
LIMIT 100;
-- Sonuç: 1226 sipariş (LIMIT 100 ile sınırlı gösterim)

-- Tüm planlama durumları:
SELECT durum, COUNT(*) as adet 
FROM planlama 
WHERE firma_id = 16 
GROUP BY durum;
-- bitti: 1464 (tamamı)

-- ============================================
-- ÖZET
-- ============================================
-- ✅ 1329 sipariş 'tamamlandi' yapıldı
-- ✅ 1 planlama 'bitti' yapıldı
-- ✅ Planlama "Bitenler" sekmesinde: 1226 sipariş görünüyor
-- ✅ Makina listelerinde: 0 iş (hiç gözükmüyor)
-- ✅ Tüm işler üretilmiş gibi gösteriliyor

-- ============================================
-- GERİ ALMA (UNDO)
-- ============================================
-- Siparişleri geri al:
-- mysql -u hanka_user -p panelhankasys_crm2 < /root/backup_siparisler_tamamlandi_20251127_232716.sql

-- Planlamaları geri al:
-- mysql -u hanka_user -p panelhankasys_crm2 < /root/backup_planlama_durum_20251127_231025.sql
