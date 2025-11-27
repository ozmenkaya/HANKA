-- ============================================
-- SİPARİŞLER ONAY SAYFASI -> BITENLER TABINA MANUEL TAŞIMA
-- ============================================
-- Tarih: 2025-11-27
-- Amaç: siparisler_onay sayfasındaki "Siparişler" sekmesindeki 
--       tüm onaylanmış siparişleri planlama sayfasının "Bitenler"
--       sekmesine taşımak
-- ============================================

-- ÖNCE KONTROL: Kaç sipariş etkilenecek?
SELECT 
    COUNT(*) as etkilenecek_siparis_sayisi,
    GROUP_CONCAT(siparis_no SEPARATOR ', ') as siparis_nolar
FROM siparisler
WHERE firma_id = 1  -- ⚠️ FIRMA_ID'YI GÜNCELLE!
  AND onay_baslangic_durum = 'evet'
  AND islem NOT IN ('tamamlandi', 'iptal')
  AND (aktif = 1 OR aktif IS NULL);

-- Detaylı liste
SELECT 
    id,
    siparis_no,
    isin_adi,
    islem as mevcut_durum,
    termin,
    adet
FROM siparisler
WHERE firma_id = 1  -- ⚠️ FIRMA_ID'YI GÜNCELLE!
  AND onay_baslangic_durum = 'evet'
  AND islem NOT IN ('tamamlandi', 'iptal')
  AND (aktif = 1 OR aktif IS NULL)
ORDER BY id DESC;

-- ============================================
-- ASIL GÜNCELLEMEYİ YAPMADAN ÖNCE YEDEK AL!
-- ============================================
-- BACKUP komutu (terminal'de çalıştır):
-- mysqldump -u hanka_user -p panelhankasys_crm2 siparisler > siparisler_backup_$(date +%Y%m%d_%H%M%S).sql

-- ============================================
-- GÜNCELLEMEYİ UYGULA
-- ============================================
-- ⚠️ BU QUERY'Yİ ÇALIŞTIRMADAN ÖNCE YEDEK AL!
-- ⚠️ FIRMA_ID'YI KONTROL ET!

UPDATE siparisler
SET 
    islem = 'tamamlandi',
    updated_at = NOW()  -- Eğer bu kolon varsa
WHERE firma_id = 1  -- ⚠️ FIRMA_ID'YI GÜNCELLE!
  AND onay_baslangic_durum = 'evet'
  AND islem NOT IN ('tamamlandi', 'iptal')
  AND (aktif = 1 OR aktif IS NULL);

-- Güncelleme sonrası kontrol
SELECT 
    COUNT(*) as guncellenen_sayisi
FROM siparisler
WHERE firma_id = 1  -- ⚠️ FIRMA_ID'YI GÜNCELLE!
  AND onay_baslangic_durum = 'evet'
  AND islem = 'tamamlandi'
  AND (aktif = 1 OR aktif IS NULL);

-- ============================================
-- GERİ ALMA (UNDO) - Eğer hata olursa
-- ============================================
-- Eğer yanlışlıkla güncelleme yaptıysan, geri almak için:
-- (Sadece güvenli bir backup varsa!)

-- UPDATE siparisler
-- SET islem = 'islemde'  -- veya önceki durum
-- WHERE firma_id = 1
--   AND onay_baslangic_durum = 'evet'
--   AND islem = 'tamamlandi'
--   AND updated_at >= '2025-11-27 00:00:00';  -- Bugünkü güncellemeleri geri al
