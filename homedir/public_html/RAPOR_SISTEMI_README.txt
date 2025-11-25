============================================
RAPOR SİSTEMİ DOKÜMANTASYONU
============================================
Tarih: 19 Ekim 2025
============================================

1. GENEL BAKIŞ
--------------
Rapor sistemi iki ana bileşenden oluşur:
- Rapor Ayarları: Hangi tabloların rapor için kullanılabileceğini belirler
- Raporlar: Seçilen tablolardan rapor şablonları oluşturur ve Excel/CSV export yapar

2. DOSYA YAPISI
---------------
/var/www/html/rapor_ayarlari.php              - Rapor ayarları ana sayfa
/var/www/html/rapor_ayarlari_db_islem.php     - Rapor ayarları AJAX backend
/var/www/html/raporlar.php                     - Rapor oluşturma sayfası
/var/www/html/raporlar_db_islem.php            - Rapor kaydetme backend
/var/www/html/rapor_tablo.php                  - Rapor tablo görünümü
/var/www/html/rapor_excel.php                  - Excel export
/var/www/html/rapor_csv.php                    - CSV export
/var/www/html/include/oturum_kontrol_ajax.php  - AJAX oturum kontrolü

Database:
- rapor_sablonlari: Kayıtlı rapor şablonları
- rapor_ayarlari: Firma bazlı tablo aktif/pasif ayarları

3. DESTEKLENEN TABLOLAR
-----------------------
Şu an sadece bu tablolar için sütun tanımları mevcut (sutunTanimlari objesi):

✓ uretim          - Üretim İşlem Tarihleri
✓ siparisler      - Siparişler
✓ planlama        - Planlama
✓ makinalar       - Makinalar
✓ personel        - Personel
✓ stok            - Stok

4. YENİ TABLO EKLEME
--------------------
Eğer yeni bir tablo eklemek isterseniz:

A) Rapor Ayarları'ndan ekleyin:
   https://lethe.com.tr/index.php?url=rapor_ayarlari
   - "Yeni Tablo Ekle" formu
   - Tablo seçin, görünen isim verin
   - Ekle butonuna tıklayın

B) Sütun tanımlarını ekleyin (manuel):
   /var/www/html/raporlar.php dosyasında sutunTanimlari objesine ekleyin:
   
   const sutunTanimlari = {
       ...
       yeni_tablo: [
           {key: 'kolon_adi', label: 'Görünen İsim'},
           {key: 'baska_kolon', label: 'Başka Görünen İsim'},
           // ... diğer kolonlar
       ]
   };

C) Backend sorgularını güncelleyin:
   /var/www/html/rapor_excel.php içinde switch-case ekleyin
   /var/www/html/rapor_tablo.php içinde switch-case ekleyin
   /var/www/html/rapor_csv.php içinde switch-case ekleyin

5. KULLANICI AKIŞI
------------------
1. Admin "Rapor Ayarları" sayfasından hangi tabloların görüneceğini seçer
2. Kullanıcı "Raporlar" sayfasına gider
3. "Yeni Rapor Şablonu" butonuna tıklar
4. Rapor adı girer
5. Veri kaynağını seçer (sadece aktif tablolar listelenir)
6. Sütunları işaretler
7. Kaydet
8. Raporu görüntülemek için tarih seçer ve "Görüntüle" tıklar
9. Tablo görünümünde veriler görünür
10. "Excel İndir" veya "CSV İndir" butonlarıyla export yapar

6. HATA ÇÖZÜMLEME
-----------------
HATA: "Cannot read properties of undefined (reading 'forEach')"
ÇÖZÜM: Bu tablo için sütun tanımı yok. sutunTanimlari objesine ekleyin.

HATA: "Oturum süresi doldu"
ÇÖZÜM: include/oturum_kontrol_ajax.php kullanıldı, normal.

HATA: Excel/CSV boş geliyor
ÇÖZÜM: rapor_excel.php veya rapor_csv.php'de bu tablo için case eklenmemiş.

7. VERİTABANI YAPISI
--------------------
rapor_ayarlari:
- id (PK)
- firma_id (FK -> firmalar.id)
- tablo_adi (VARCHAR)
- tablo_label (VARCHAR)
- aktif (TINYINT 0/1)
- sira (INT)
- olusturma_tarihi
- guncelleme_tarihi

rapor_sablonlari:
- id (PK)
- firma_id (FK)
- kullanici_id (personel_id)
- rapor_adi (VARCHAR)
- veri_kaynagi (VARCHAR)
- sutunlar (TEXT - JSON array)
- olusturma_tarihi

8. ÖNEMLİ NOTLAR
----------------
- Her firma kendi rapor ayarlarını yönetir (firma_id bazlı)
- Rapor ayarlarında pasif tablolar raporlar sayfasında görünmez
- Sütun tanımı olmayan tablolar uyarı mesajı gösterir
- AJAX istekleri için oturum_kontrol_ajax.php kullanılır (JSON döndürür)
- Excel export XML formatında (SpreadsheetML)
- CSV export UTF-8 BOM ile (Excel uyumlu)

9. GÜVENLİK
-----------
- Tüm sayfalar oturum kontrolü yapar
- Firma bazlı veri izolasyonu
- SQL injection korumalı (PDO prepared statements)
- XSS korumalı (htmlspecialchars)

10. DESTEK
----------
Sorularınız için sistem yöneticisine başvurun.
