#!/usr/bin/env python3
"""
HANKA Memory Initial Data
İlk geliştirme kayıtlarını yükle
"""

from local_memory import HANKAMemory

def load_initial_data():
    """İlk memory verilerini yükle"""
    memory = HANKAMemory()
    
    print("📝 Loading initial development memory...\n")
    
    # ==================== DECISIONS ====================
    
    memory.add_decision(
        title="Multi-Tenant: firma_id zorunlu kontrolü",
        context="94 tablolu multi-tenant ERP sisteminde veri izolasyonu kritik",
        decision="Her SQL query'de firma_id filtresi zorunlu, yoksa güvenlik açığı",
        rationale="Farklı firmaların verilerinin karışmasını önlemek için",
        category="security",
        tags=["multi-tenant", "security", "database"]
    )
    
    memory.add_decision(
        title="PDO kullan, MySQLi KULLANMA",
        context="Eski kodda MySQLi, yeni kodda PDO karışık kullanılıyor",
        decision="Tüm yeni kodlarda SADECE PDO prepared statements kullan",
        rationale="SQL injection koruması ve modern PHP standartları",
        category="database",
        tags=["pdo", "mysql", "security"]
    )
    
    memory.add_decision(
        title="JSON kolonu pattern: siparisler.veriler",
        context="Sipariş detayları dinamik, tablo şeması sık değişmesin",
        decision="Esnek veriler için JSON kolonu kullan (örn: siparisler.veriler)",
        rationale="Şema esnekliği, backward compatibility",
        category="database",
        tags=["json", "schema", "flexibility"]
    )
    
    memory.add_decision(
        title="Soft delete kullan (silindi=1)",
        context="Veri kaybını önlemek, audit trail için",
        decision="Hard DELETE yerine UPDATE SET silindi=1 kullan",
        rationale="Veri kurtarma, compliance, audit",
        category="database",
        tags=["soft-delete", "audit", "data-integrity"]
    )
    
    memory.add_decision(
        title="AI: Fine-tuned model kullan",
        context="ChatGPT API slow ve pahalı, schema sürekli değişiyor",
        decision="ft:gpt-4o-mini-2024-07-18:antartika:hanka-sql-v2:CXO5sbFS fine-tuned model",
        rationale="10x hızlı, HANKA domain knowledge, daha ucuz",
        category="ai",
        tags=["openai", "fine-tuning", "performance"]
    )
    
    # ==================== PATTERNS ====================
    
    memory.add_pattern(
        name="Multi-Tenant CRUD Pattern",
        description="Firma ID kontrolü ile güvenli CRUD işlemleri",
        code_example="""<?php
// CREATE
$stmt = $conn->prepare("
    INSERT INTO table (firma_id, col1, col2) 
    VALUES (:firma_id, :col1, :col2)
");
$stmt->execute([
    ':firma_id' => $_SESSION['firma_id'],
    ':col1' => $value1,
    ':col2' => $value2
]);

// READ
$stmt = $conn->prepare("
    SELECT * FROM table 
    WHERE firma_id = :firma_id AND id = :id
");
$stmt->execute([':firma_id' => $_SESSION['firma_id'], ':id' => $id]);

// UPDATE
$stmt = $conn->prepare("
    UPDATE table SET col1 = :val 
    WHERE id = :id AND firma_id = :firma_id
");

// DELETE (Soft)
$stmt = $conn->prepare("
    UPDATE table SET silindi = 1 
    WHERE id = :id AND firma_id = :firma_id
");
?>""",
        use_cases="Her tablo CRUD işlemi - musteri, siparisler, stok_kalemleri",
        file_references="musteri_db_islem.php, siparis_db_islem.php, stok_db_islem.php",
        category="crud",
        language="php",
        tags=["multi-tenant", "pdo", "security"]
    )
    
    memory.add_pattern(
        name="AJAX Backend Pattern",
        description="Action-based backend API endpoint pattern",
        code_example="""<?php
// {page}_db_islem.php
require_once 'include/db.php';
require_once 'include/oturum_kontrol.php';

$action = $_POST['action'] ?? '';
$firma_id = $_SESSION['firma_id'];

try {
    if ($action === 'save') {
        $stmt = $conn->prepare("INSERT INTO table ...");
        // ...
        echo json_encode(['success' => true, 'message' => 'Kayıt başarılı']);
    }
    elseif ($action === 'list') {
        $stmt = $conn->prepare("SELECT * FROM table WHERE firma_id = ?");
        // ...
        echo json_encode(['success' => true, 'data' => $rows]);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
}
?>

<script>
// Frontend
$.ajax({
    url: 'page_db_islem.php',
    type: 'POST',
    data: { action: 'save', firma_id: FIRMA_ID, ...data },
    success: function(response) {
        if (response.success) {
            showSuccess(response.message);
        }
    }
});
</script>""",
        use_cases="Tüm AJAX backend endpoints",
        file_references="musteri_db_islem.php, siparis_db_islem.php",
        category="backend",
        language="php",
        tags=["ajax", "api", "json"]
    )
    
    memory.add_pattern(
        name="MES Üretim İşlem Pattern",
        description="Makina başlatma/bitirme/aktarma MES pattern",
        code_example="""<?php
// İş Başlat
$stmt = $conn->prepare("
    INSERT INTO uretim_islem_tarihler 
    (siparis_id, makina_id, personel_id, baslangic, durum)
    VALUES (:siparis, :makina, :personel, NOW(), 'devam_ediyor')
");

// İş Bitir
$stmt = $conn->prepare("
    UPDATE uretim_islem_tarihler 
    SET bitis = NOW(), durum = 'tamamlandi' 
    WHERE id = :islem_id AND firma_id = :firma_id
");

// Üretim Adedi Kaydet
$stmt = $conn->prepare("
    INSERT INTO uretilen_adetler 
    (siparis_id, uretilen_adet, tarih) 
    VALUES (:siparis, :adet, NOW())
");

// Makina Durumu Güncelle
$stmt = $conn->prepare("
    UPDATE makinalar 
    SET durumu = :durum 
    WHERE id = :makina_id AND firma_id = :firma_id
");
?>""",
        use_cases="Üretim takip, makina yönetimi, OEE hesaplama",
        file_references="makina_is_ekran.php, uretim_kontrol.php, planlama.php",
        category="mes",
        language="php",
        tags=["mes", "production", "manufacturing"]
    )
    
    # ==================== BUG FIXES ====================
    
    memory.add_bug_fix(
        title="AlertAgent: Table 'urunler' doesn't exist",
        description="AlertAgent checkStock() metodu olmayan 'urunler' tablosunu kullanıyor",
        error_message="SQLSTATE[42S02]: Base table or view not found: 1146 Table 'panelhankasys_crm2.urunler' doesn't exist",
        solution="""AlertAgent.php'yi güncelle:
- urunler tablosu yerine stok_kalemleri kullan
- stok_alt_depolar ile join et
- Basitleştirilmiş query: low stock items direkt kontrol et""",
        files_changed="include/agents/AlertAgent.php",
        severity="high",
        category="database",
        tags=["agent", "database", "schema"]
    )
    
    memory.add_bug_fix(
        title="MySQLi vs PDO karışıklığı",
        description="Bazı dosyalar MySQLi, bazıları PDO kullanıyor - inconsistency",
        error_message="Call to undefined method PDO::query()",
        solution="""Standardize: SADECE PDO kullan
- $conn->query() yerine $conn->prepare() + execute()
- mysqli_* fonksiyonları yerine PDO methods
- Prepared statements zorunlu (security)""",
        files_changed="Multiple files",
        severity="medium",
        category="database",
        tags=["pdo", "mysqli", "refactoring"]
    )
    
    memory.add_bug_fix(
        title="JSON_EXTRACT without JSON_UNQUOTE",
        description="MySQL JSON_EXTRACT quotes döndürüyor, string comparison fail",
        error_message="None (logic bug - karşılaştırmalar hatalı)",
        solution="""Always use JSON_UNQUOTE with JSON_EXTRACT:
JSON_UNQUOTE(JSON_EXTRACT(veriler, '$.field'))

Daha iyi: MySQL 5.7.13+ için ->
veriler->'$.field'  (with quotes)
veriler->>'$.field' (without quotes)""",
        files_changed="siparis_db_islem.php, rapor_tablo.php",
        severity="medium",
        category="database",
        tags=["json", "mysql", "query"]
    )
    
    # ==================== LEARNINGS ====================
    
    memory.add_learning(
        title="HANKA multi-tenant architecture",
        content="""- 94 tablo, PDO connection
- Her tablo firma_id kolonuna sahip (multi-tenant)
- siparisler tablosu merkezi (JSON veriler kolonu)
- MES modülleri: planlama, uretim_islem_tarihler, uretilen_adetler
- AI: Fine-tuned GPT-4o-mini, cache sistemi, agent orchestration""",
        category="architecture",
        importance=5,
        tags=["hanka", "architecture", "overview"]
    )
    
    memory.add_learning(
        title="Index'leme stratejisi",
        content="""Exclude patterns:
- .backup, .bak, backup_* (yedekler)
- vendor/, node_modules/ (dependencies)
- tmp/, logs/, dosyalar/ (temporary)
- test_*, backup-* (test dosyaları)

Include priority:
1. Core: include/*.php (engine files)
2. Agents: include/agents/*.php
3. API: *_db_islem.php (backend endpoints)
4. Views: *.php (page views)
5. JS: *.js (frontend logic)""",
        category="rag",
        importance=4,
        tags=["rag", "indexing", "optimization"]
    )
    
    memory.add_learning(
        title="OpenAI API key handling",
        content="""API key location: homedir/public_html/.env
Format: OPENAI_API_KEY=sk-proj-...

Usage patterns:
- PHP: OpenAI.php class (env file parse)
- Python: os.getenv('OPENAI_API_KEY')
- RAG System: Export before running

Security: Never commit .env to git""",
        category="configuration",
        importance=5,
        tags=["openai", "security", "configuration"]
    )
    
    print("\n✅ Initial data loaded successfully!\n")
    
    # Stats
    stats = memory.get_all_stats()
    print(f"📊 Memory Contents:")
    print(f"   Decisions: {stats['decisions_count']}")
    print(f"   Patterns: {stats['patterns_count']}")
    print(f"   Bug Fixes: {stats['bug_fixes_count']}")
    print(f"   Learnings: {stats['learnings_count']}")
    
    memory.close()


if __name__ == "__main__":
    load_initial_data()
