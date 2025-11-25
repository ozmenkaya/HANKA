<?php
/**
 * HANKA AI Semantic Layer
 * Karmaşık iş mantığı ve hesaplamalar için AI tarafından çağrılabilir fonksiyonlar
 */

class AISemanticLayer {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Makina OEE (Overall Equipment Effectiveness) Hesapla
     * OEE = Kullanılabilirlik * Performans * Kalite
     */
    public function calculateOEE($firma_id, $makina_id, $date = null) {
        if ($date === null) $date = date('Y-m-d');
        
        // 1. Kullanılabilirlik (Availability)
        // Planlanan üretim süresi vs Gerçek çalışma süresi
        // Varsayalım: Vardiya 8 saat (480 dk), Yemek 30 dk, Mola 15+15 dk = 60 dk duruş planlı.
        // Net Planlanan Süre = 420 dk.
        
        $sql = "SELECT 
                    SUM(TIMESTAMPDIFF(MINUTE, baslatma_tarih, COALESCE(bitirme_tarihi, NOW()))) as calisma_suresi
                FROM uretim_islem_tarihler
                WHERE makina_id = :makina_id 
                AND DATE(baslatma_tarih) = :date";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['makina_id' => $makina_id, 'date' => $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $calisma_suresi = $row['calisma_suresi'] ?? 0;
        
        $planlanan_sure = 420; // Dakika (Standart vardiya varsayımı - bunu parametrik yapabiliriz)
        $availability = ($planlanan_sure > 0) ? ($calisma_suresi / $planlanan_sure) : 0;
        
        // 2. Performans (Performance)
        // Gerçekleşen Hız / Teorik Hız
        // Örnek: Bu makinada bu tarihte üretilen toplam adet
        
        $sql = "SELECT SUM(uretilen_adet) as toplam_adet FROM uretilen_adetler 
                WHERE makina_id = :makina_id AND DATE(bitis_tarihi) = :date";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['makina_id' => $makina_id, 'date' => $date]);
        $perf_row = $stmt->fetch(PDO::FETCH_ASSOC);
        $toplam_adet = $perf_row['toplam_adet'] ?? 0;
        
        // Basitleştirilmiş varsayım: Her vardiyada hedef 1000 adet olsun (Bunu makina hızına göre dinamik yapmak lazım)
        $hedef_adet = 1000; 
        $performance = ($hedef_adet > 0) ? ($toplam_adet / $hedef_adet) : 0;
        if ($performance > 1) $performance = 1; // %100'ü geçemez
        
        // 3. Kalite (Quality)
        // Sağlam Ürün / Toplam Ürün (Sağlam + Fire)
        $sql = "SELECT 
                    SUM(uretilen_adet) as saglam,
                    SUM(uretirken_verilen_fire_adet) as fire
                FROM uretilen_adetler
                WHERE makina_id = :makina_id AND DATE(bitis_tarihi) = :date";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['makina_id' => $makina_id, 'date' => $date]);
        $quality_row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $saglam = $quality_row['saglam'] ?? 0;
        $fire = $quality_row['fire'] ?? 0;
        $toplam_uretim = $saglam + $fire;
        
        $quality = ($toplam_uretim > 0) ? ($saglam / $toplam_uretim) : 1.0; // Üretim yoksa veya fire yoksa %100 kabul et
        
        $oee = $availability * $performance * $quality;
        
        return [
            "makina_id" => $makina_id,
            "tarih" => $date,
            "availability" => round($availability * 100, 1) . "%",
            "performance" => round($performance * 100, 1) . "%",
            "quality" => round($quality * 100, 1) . "%",
            "oee_score" => round($oee * 100, 1) . "%",
            "detay" => "Çalışma: $calisma_suresi dk / $planlanan_sure dk. Üretim: $saglam adet, Fire: $fire adet."
        ];
    }
    
    /**
     * Makina Anlık Durum Kartı
     */
    public function getMachineStatus($firma_id, $makina_id) {
        // Makina bilgisi
        $stmt = $this->conn->prepare("SELECT * FROM makinalar WHERE id = :id AND firma_id = :firma_id");
        $stmt->execute(['id' => $makina_id, 'firma_id' => $firma_id]);
        $makina = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$makina) return ["error" => "Makina bulunamadı"];
        
        // Aktif iş
        $sql = "SELECT 
                    uit.*, 
                    s.siparis_no, 
                    s.isin_adi,
                    CONCAT(p.ad, ' ', p.soyad) as operator
                FROM uretim_islem_tarihler uit
                JOIN planlama pl ON uit.planlama_id = pl.id
                JOIN siparisler s ON pl.siparis_id = s.id
                LEFT JOIN personeller p ON uit.personel_id = p.id
                WHERE uit.makina_id = :makina_id 
                AND uit.bitirme_tarihi IS NULL
                ORDER BY uit.baslatma_tarih DESC LIMIT 1";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['makina_id' => $makina_id]);
        $aktif_is = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Bekleyen iş sayısı
        $sql = "SELECT COUNT(*) as bekleyen FROM planlama 
                WHERE firma_id = :firma_id 
                AND JSON_CONTAINS(makinalar, CAST(:makina_id AS JSON))
                AND mevcut_asama < asama_sayisi"; // Basitleştirilmiş mantık
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['firma_id' => $firma_id, 'makina_id' => $makina_id]);
        $bekleyen = $stmt->fetch(PDO::FETCH_ASSOC)['bekleyen'];
        
        return [
            "makina" => $makina['makina_adi'],
            "durum" => $aktif_is ? "🟢 ÇALIŞIYOR" : "🔴 DURUYOR",
            "aktif_is" => $aktif_is ? $aktif_is['siparis_no'] . " - " . $aktif_is['isin_adi'] : "Yok",
            "operator" => $aktif_is ? $aktif_is['operator'] : "-",
            "baslangic" => $aktif_is ? $aktif_is['baslatma_tarih'] : "-",
            "bekleyen_is_sayisi" => $bekleyen
        ];
    }
}
