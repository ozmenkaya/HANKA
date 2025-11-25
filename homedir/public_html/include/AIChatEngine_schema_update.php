<?php
// Bu dosyayı AIChatEngine.php içine kopyalayın

// getDatabaseSchema() metodunu şununla değiştirin:

private function getDatabaseSchema() {
    // Dinamik schema - JSON dosyasından yükle
    \$schema_file = "/var/www/html/logs/ai_compact_schema.json";
    
    if (file_exists(\$schema_file)) {
        \$smart_schema = json_decode(file_get_contents(\$schema_file), true);
        if (\$smart_schema && count(\$smart_schema) > 0) {
            // İlişki haritasını da ekle
            \$relationship_file = "/var/www/html/logs/relationship_map.json";
            if (file_exists(\$relationship_file)) {
                \$this->relationship_map = json_decode(file_get_contents(\$relationship_file), true);
            }
            return \$smart_schema;
        }
    }
    
    // Fallback: Manuel schema
    return [
        "siparisler" => "Sipariş bilgileri (1361 kayıt) - Kolonlar: id, siparis_no, musteri_id, isin_adi, adet, fiyat, tarih, termin, durum | JOIN: musteri_id→musteri, tur_id→turler, birim_id→birimler",
        "musteri" => "Müşteri bilgileri (152 kayıt) - Kolonlar: id, firma_unvani, marka. ÖNEMLİ: Müşteri aramalarında HEM firma_unvani HEM marka kullan! | JOIN: sehir_id→sehirler, ilce_id→ilceler, ulke_id→ulkeler, sektor_id→sektorler",
        "planlama" => "Planlama kayıtları (1458 kayıt) - Kolonlar: id, siparis_id, isim, fason_tedarikciler | JOIN: siparis_id→siparisler",
        "personeller" => "Personel bilgileri (22 kayıt) - Kolonlar: id, ad, soyad, email | JOIN: yetki_id→yetkiler",
        "tedarikciler" => "Tedarikçi bilgileri - Kolonlar: id, tedarikci_unvani, telefon, email",
        "stok_alt_depolar" => "Stok deposu (182 kayıt) - Kolonlar: id, stok_alt_kalem_id, adet, ekleme_tarihi, tedarikci_id | JOIN: stok_alt_kalem_id→stok_alt_kalemler, tedarikci_id→tedarikciler, birim_id→birimler",
        "stok_alt_kalemler" => "Stok kalemleri - Kolonlar: id, stok_id, birim_id | JOIN: stok_id→stok_kalemleri, birim_id→birimler",
        "stok_kalemleri" => "Stok ürün tanımları - Kolonlar: id, stok_kalem",
        "uretim_islem_tarihler" => "Üretim kayıtları (1286 kayıt) | JOIN: planlama_id→planlama, personel_id→personeller",
        "makinalar" => "Makina bilgileri (15 kayıt) - Kolonlar: id, makina_adi",
        "departmanlar" => "Departman bilgileri (20 kayıt)",
        "turler" => "İş türleri",
        "birimler" => "Birim bilgileri (5 kayıt)"
    ];
}
