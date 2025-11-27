-- ============================================
-- PLANLAMA TABLOSU - TESLİM DURUMU GÜNCELLEMESİ
-- ============================================
-- Tarih: 2025-11-27
-- Amaç: onay_durum = 'evet' olan tüm planlamaların
--       teslim_durumu'nu 'bitti' olarak güncellemek
-- ============================================

-- ÖNCE KONTROL: Kaç kayıt etkilenecek?
SELECT 
    COUNT(*) as etkilenecek_kayit_sayisi
FROM planlama
WHERE firma_id = 16  -- ⚠️ FIRMA_ID'YI GÜNCELLE!
  AND onay_durum = 'evet';

-- Mevcut teslim durumu dağılımı
SELECT 
    teslim_durumu, 
    COUNT(*) as adet 
FROM planlama 
WHERE firma_id = 16 
  AND onay_durum = 'evet' 
GROUP BY teslim_durumu;

-- Detaylı liste (ilk 20)
SELECT 
    p.id,
    p.siparis_id,
    s.siparis_no,
    p.onay_durum,
    p.teslim_durumu
FROM planlama p
JOIN siparisler s ON p.siparis_id = s.id
WHERE p.firma_id = 16 
  AND p.onay_durum = 'evet' 
  AND p.teslim_durumu = 'bitmedi'
LIMIT 20;

-- ============================================
-- YEDEK AL!
-- ============================================
-- Terminal'de çalıştır:
-- mysqldump -u hanka_user -p panelhankasys_crm2 planlama > /root/backup_planlama_$(date +%Y%m%d_%H%M%S).sql

-- ============================================
-- GÜNCELLEMEYİ UYGULA
-- ============================================
-- ⚠️ BU QUERY'Yİ ÇALIŞTIRMADAN ÖNCE YEDEK AL!

UPDATE planlama 
SET teslim_durumu = 'bitti'
WHERE firma_id = 16  -- ⚠️ FIRMA_ID'YI GÜNCELLE!
  AND onay_durum = 'evet'
  AND teslim_durumu = 'bitmedi';

-- Güncelleme sonrası kontrol
SELECT 
    teslim_durumu, 
    COUNT(*) as adet 
FROM planlama 
WHERE firma_id = 16 
  AND onay_durum = 'evet'
GROUP BY teslim_durumu;

-- Tüm firma için genel durum
SELECT 
    teslim_durumu, 
    COUNT(*) as adet 
FROM planlama 
WHERE firma_id = 16 
GROUP BY teslim_durumu;

-- ============================================
-- YAPILAN İŞLEM SONUÇLARI (2025-11-27)
-- ============================================
-- ✅ Yedek: /root/backup_planlama_20251127_230122.sql (486 KB)
-- ✅ Güncellenen: 571 kayıt
-- ✅ Önceki durum: 571 bitmedi, 2 bitti
-- ✅ Sonraki durum: 0 bitmedi, 573 bitti (onay_durum='evet' olanlar)

-- ============================================
-- GERİ ALMA (UNDO)
-- ============================================
-- Backup'tan geri yükle:
-- mysql -u hanka_user -p panelhankasys_crm2 < /root/backup_planlama_20251127_230122.sql
