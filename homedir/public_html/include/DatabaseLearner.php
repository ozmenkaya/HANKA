<?php
/**
 * DatabaseLearner - Veritabanı şemasını otomatik öğrenen ve ilişkileri keşfeden AI sistemi
 * 
 * Bu sınıf:
 * 1. INFORMATION_SCHEMA'dan tüm tabloları ve sütunları otomatik keşfeder
 * 2. Foreign key ilişkilerini tespit eder
 * 3. Sütun isimlerinden anlamları çıkarır (_id, _tarih, _adi gibi)
 * 4. Örnek verileri analiz ederek sütun tiplerini anlar
 * 5. Başarılı sorgulardan JOIN kalıplarını öğrenir
 * 6. Yeni tablolar eklendiğinde otomatik adapte olur
 */
class DatabaseLearner {
    private $conn;
    private $firma_id;
    
    public function __construct($conn, $firma_id) {
        $this->conn = $conn;
        $this->firma_id = $firma_id;
    }
    
    /**
     * Tüm veritabanı şemasını öğren (ilk kurulum veya refresh)
     */
    public function learnDatabaseSchema($force_refresh = false) {
        error_log("=== DatabaseLearner: Starting schema learning ===");
        
        try {
            // 1. Tüm tabloları keşfet
            $tables = $this->discoverTables();
            error_log("Found " . count($tables) . " tables");
            
            // 2. Her tablo için sütunları öğren
            foreach ($tables as $table) {
                $this->learnTableColumns($table, $force_refresh);
            }
            
            // 3. Foreign key ilişkilerini keşfet
            $this->discoverForeignKeys();
            
            // 4. Sütun anlamlarını çıkar
            $this->inferColumnSemantics();
            
            error_log("=== DatabaseLearner: Schema learning complete ===");
            return ["success" => true, "tables_learned" => count($tables)];
            
        } catch (Exception $e) {
            error_log("DatabaseLearner ERROR: " . $e->getMessage());
            return ["success" => false, "error" => $e->getMessage()];
        }
    }
    
    /**
     * Veritabanındaki tüm tabloları keşfet
     */
    private function discoverTables() {
        $sql = "SELECT TABLE_NAME, TABLE_TYPE, TABLE_COMMENT
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME";
        
        $sth = $this->conn->query($sql);
        return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * Bir tablonun tüm sütunlarını öğren
     */
    private function learnTableColumns($table_name, $force_refresh = false) {
        // Sütun bilgilerini INFORMATION_SCHEMA'dan al
        $sql = "SELECT 
                    COLUMN_NAME,
                    DATA_TYPE,
                    COLUMN_TYPE,
                    IS_NULLABLE,
                    COLUMN_KEY,
                    COLUMN_DEFAULT,
                    EXTRA,
                    COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :table_name
                ORDER BY ORDINAL_POSITION";
        
        $sth = $this->conn->prepare($sql);
        $sth->execute(["table_name" => $table_name]);
        $columns = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            // Örnek değerleri al (max 10)
            $sample_values = $this->getSampleValues($table_name, $column["COLUMN_NAME"]);
            
            // İstatistikleri hesapla
            $stats = $this->getColumnStats($table_name, $column["COLUMN_NAME"]);
            
            // Veritabanına kaydet veya güncelle
            $sql = "INSERT INTO ai_database_schema 
                    (table_name, column_name, data_type, column_type, is_nullable, 
                     column_key, column_default, extra, column_comment, 
                     sample_values, distinct_count, null_count, last_learned)
                    VALUES 
                    (:table_name, :column_name, :data_type, :column_type, :is_nullable,
                     :column_key, :column_default, :extra, :column_comment,
                     :sample_values, :distinct_count, :null_count, NOW())
                    ON DUPLICATE KEY UPDATE
                    data_type = VALUES(data_type),
                    column_type = VALUES(column_type),
                    is_nullable = VALUES(is_nullable),
                    column_key = VALUES(column_key),
                    column_default = VALUES(column_default),
                    extra = VALUES(extra),
                    column_comment = VALUES(column_comment),
                    sample_values = VALUES(sample_values),
                    distinct_count = VALUES(distinct_count),
                    null_count = VALUES(null_count),
                    last_learned = NOW()";
            
            $sth = $this->conn->prepare($sql);
            $sth->execute([
                "table_name" => $table_name,
                "column_name" => $column["COLUMN_NAME"],
                "data_type" => $column["DATA_TYPE"],
                "column_type" => $column["COLUMN_TYPE"],
                "is_nullable" => $column["IS_NULLABLE"],
                "column_key" => $column["COLUMN_KEY"],
                "column_default" => $column["COLUMN_DEFAULT"],
                "extra" => $column["EXTRA"],
                "column_comment" => $column["COLUMN_COMMENT"],
                "sample_values" => json_encode($sample_values),
                "distinct_count" => $stats["distinct_count"],
                "null_count" => $stats["null_count"]
            ]);
        }
        
        error_log("Learned " . count($columns) . " columns from table: $table_name");
    }
    
    /**
     * Bir sütundan örnek değerler al
     */
    private function getSampleValues($table_name, $column_name) {
        try {
            $sql = "SELECT DISTINCT `$column_name` 
                    FROM `$table_name` 
                    WHERE `$column_name` IS NOT NULL 
                    LIMIT 10";
            
            $sth = $this->conn->query($sql);
            return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Sütun istatistiklerini hesapla
     */
    private function getColumnStats($table_name, $column_name) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT `$column_name`) as distinct_count,
                        SUM(CASE WHEN `$column_name` IS NULL THEN 1 ELSE 0 END) as null_count
                    FROM `$table_name`";
            
            $sth = $this->conn->query($sql);
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ["distinct_count" => 0, "null_count" => 0];
        }
    }
    
    /**
     * Foreign key ilişkilerini keşfet
     */
    private function discoverForeignKeys() {
        // INFORMATION_SCHEMA'dan foreign key'leri al
        $sql = "SELECT 
                    kcu.TABLE_NAME as from_table,
                    kcu.COLUMN_NAME as from_column,
                    kcu.REFERENCED_TABLE_NAME as to_table,
                    kcu.REFERENCED_COLUMN_NAME as to_column
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                WHERE kcu.TABLE_SCHEMA = DATABASE()
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL";
        
        $sth = $this->conn->query($sql);
        $foreign_keys = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($foreign_keys as $fk) {
            $this->saveRelationship(
                $fk["from_table"],
                $fk["from_column"],
                $fk["to_table"],
                $fk["to_column"],
                "foreign_key"
            );
            
            // Sütunu foreign key olarak işaretle
            $sql = "UPDATE ai_database_schema 
                    SET is_foreign_key = 1,
                        references_table = :ref_table,
                        references_column = :ref_column
                    WHERE table_name = :table_name 
                    AND column_name = :column_name";
            
            $sth = $this->conn->prepare($sql);
            $sth->execute([
                "table_name" => $fk["from_table"],
                "column_name" => $fk["from_column"],
                "ref_table" => $fk["to_table"],
                "ref_column" => $fk["to_column"]
            ]);
        }
        
        error_log("Discovered " . count($foreign_keys) . " foreign key relationships");
        
        // İsim bazlı ilişkileri de keşfet (_id ile biten sütunlar)
        $this->discoverImplicitRelationships();
    }
    
    /**
     * İsim kurallarından implicit ilişkileri keşfet
     * Örnek: musteri_id -> musteri.id
     */
    private function discoverImplicitRelationships() {
        $sql = "SELECT table_name, column_name 
                FROM ai_database_schema 
                WHERE column_name LIKE '%_id' 
                AND is_foreign_key = 0
                AND column_name != 'id'";
        
        $sth = $this->conn->query($sql);
        $potential_fks = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($potential_fks as $col) {
            // musteri_id -> musteri tablosu olabilir mi?
            $potential_table = str_replace("_id", "", $col["column_name"]);
            
            // Tablo var mı kontrol et
            $check_sql = "SELECT 1 FROM ai_database_schema 
                          WHERE table_name = :table_name 
                          AND column_name = 'id' 
                          LIMIT 1";
            
            $check_sth = $this->conn->prepare($check_sql);
            $check_sth->execute(["table_name" => $potential_table]);
            
            if ($check_sth->fetch()) {
                // İlişki bulundu!
                $this->saveRelationship(
                    $col["table_name"],
                    $col["column_name"],
                    $potential_table,
                    "id",
                    "query_analysis",
                    0.7 // Lower confidence for implicit relationships
                );
                
                error_log("Discovered implicit relationship: {$col['table_name']}.{$col['column_name']} -> {$potential_table}.id");
            }
        }
    }
    
    /**
     * İlişkiyi kaydet
     */
    private function saveRelationship($from_table, $from_column, $to_table, $to_column, $discovered_by = "query_analysis", $confidence = 0.9) {
        $join_pattern = "LEFT JOIN `$to_table` ON `$from_table`.`$from_column` = `$to_table`.`$to_column`";
        
        $sql = "INSERT INTO ai_table_relationships 
                (from_table, from_column, to_table, to_column, join_pattern, 
                 confidence_score, discovered_by, created_at)
                VALUES 
                (:from_table, :from_column, :to_table, :to_column, :join_pattern,
                 :confidence, :discovered_by, NOW())
                ON DUPLICATE KEY UPDATE
                confidence_score = GREATEST(confidence_score, VALUES(confidence_score)),
                join_pattern = VALUES(join_pattern),
                updated_at = NOW()";
        
        $sth = $this->conn->prepare($sql);
        $sth->execute([
            "from_table" => $from_table,
            "from_column" => $from_column,
            "to_table" => $to_table,
            "to_column" => $to_column,
            "join_pattern" => $join_pattern,
            "confidence" => $confidence,
            "discovered_by" => $discovered_by
        ]);
    }
    
    /**
     * Sütun anlamlarını çıkar (semantic analysis)
     */
    private function inferColumnSemantics() {
        $sql = "SELECT table_name, column_name, data_type, column_type, 
                       is_foreign_key, sample_values
                FROM ai_database_schema";
        
        $sth = $this->conn->query($sql);
        $columns = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            $semantic_type = $this->inferSemanticType($col);
            $display_name = $this->generateDisplayName($col["column_name"]);
            $description = $this->generateDescription($col);
            
            $sql = "INSERT INTO ai_column_semantics 
                    (table_name, column_name, semantic_type, display_name, 
                     description, confidence_score, learned_from, created_at)
                    VALUES 
                    (:table_name, :column_name, :semantic_type, :display_name,
                     :description, 0.8, 'schema_analysis', NOW())
                    ON DUPLICATE KEY UPDATE
                    semantic_type = VALUES(semantic_type),
                    display_name = VALUES(display_name),
                    description = VALUES(description),
                    updated_at = NOW()";
            
            $sth = $this->conn->prepare($sql);
            $sth->execute([
                "table_name" => $col["table_name"],
                "column_name" => $col["column_name"],
                "semantic_type" => $semantic_type,
                "display_name" => $display_name,
                "description" => $description
            ]);
        }
        
        error_log("Inferred semantics for " . count($columns) . " columns");
    }
    
    /**
     * Sütunun semantic tipini çıkar
     */
    private function inferSemanticType($column) {
        $name = strtolower($column["column_name"]);
        $type = strtolower($column["data_type"]);
        
        if ($name == "id" || $name == "Id") return "id";
        if ($column["is_foreign_key"]) return "foreign_key";
        if (strpos($name, "_id") !== false) return "foreign_key";
        if (strpos($name, "tarih") !== false || strpos($name, "date") !== false) {
            return ($type == "datetime" || $type == "timestamp") ? "datetime" : "date";
        }
        if (strpos($name, "adi") !== false || strpos($name, "isim") !== false || 
            strpos($name, "name") !== false || strpos($name, "unvan") !== false) {
            return "name";
        }
        if (strpos($name, "adet") !== false || strpos($name, "miktar") !== false || 
            strpos($name, "quantity") !== false || strpos($name, "count") !== false) {
            return "quantity";
        }
        if (strpos($name, "fiyat") !== false || strpos($name, "tutar") !== false || 
            strpos($name, "price") !== false || strpos($name, "amount") !== false) {
            return "amount";
        }
        if (strpos($name, "durum") !== false || strpos($name, "status") !== false) {
            return "status";
        }
        if ($type == "text" || $type == "mediumtext" || $type == "longtext" || 
            strpos($name, "aciklama") !== false || strpos($name, "not") !== false) {
            return "description";
        }
        
        return "other";
    }
    
    /**
     * Görüntülenebilir isim oluştur
     */
    private function generateDisplayName($column_name) {
        $name = str_replace("_", " ", $column_name);
        $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");
        
        // Türkçe çeviriler
        $translations = [
            "Id" => "ID",
            "Tarih" => "Tarih",
            "Adi" => "Adı",
            "Soyadi" => "Soyadı",
            "Ad" => "Ad",
            "Soyad" => "Soyad",
            "Adet" => "Adet",
            "Fiyat" => "Fiyat",
            "Tutar" => "Tutar",
            "Durum" => "Durum",
            "Aciklama" => "Açıklama"
        ];
        
        foreach ($translations as $en => $tr) {
            $name = str_replace($en, $tr, $name);
        }
        
        return $name;
    }
    
    /**
     * Sütun açıklaması oluştur
     */
    private function generateDescription($column) {
        $type_map = [
            "id" => "Benzersiz kimlik numarası",
            "foreign_key" => "Başka tabloya referans",
            "name" => "İsim alanı",
            "date" => "Tarih bilgisi",
            "datetime" => "Tarih ve saat bilgisi",
            "quantity" => "Miktar/Adet bilgisi",
            "amount" => "Tutar/Fiyat bilgisi",
            "status" => "Durum bilgisi",
            "description" => "Açıklama metni"
        ];
        
        $semantic = $this->inferSemanticType($column);
        return $type_map[$semantic] ?? "Veri alanı";
    }
    
    /**
     * Başarılı bir sorgudan öğren
     */
    public function learnFromSuccessfulQuery($question, $sql, $execution_time, $result_count) {
        // Null check
        if (empty($question) || empty($sql)) {
            error_log("learnFromSuccessfulQuery: Empty question or SQL, skipping");
            return;
        }
        
        // SQL'den tabloları çıkar
        preg_match_all('/FROM\s+`?(\w+)`?/i', $sql, $from_matches);
        preg_match_all('/JOIN\s+`?(\w+)`?/i', $sql, $join_matches);
        
        $tables = array_merge($from_matches[1] ?? [], $join_matches[1] ?? []);
        $tables = array_unique($tables);
        
        // JOIN kalıplarını çıkar
        preg_match_all('/JOIN\s+`?(\w+)`?\s+(?:AS\s+)?`?(\w+)?`?\s+ON\s+(.+?)(?:WHERE|JOIN|ORDER|GROUP|LIMIT|$)/is', $sql, $join_patterns);
        
        $joins_used = [];
        for ($i = 0; $i < count($join_patterns[0]); $i++) {
            $joins_used[] = [
                "table" => $join_patterns[1][$i],
                "alias" => $join_patterns[2][$i] ?? "",
                "condition" => trim($join_patterns[3][$i])
            ];
            
            // JOIN'den ilişki öğren
            if (preg_match('/`?(\w+)`?\.`?(\w+)`?\s*=\s*`?(\w+)`?\.`?(\w+)`?/', $join_patterns[3][$i], $rel)) {
                $this->saveRelationship($rel[1], $rel[2], $rel[3], $rel[4], "query_analysis");
            }
        }
        
        // Anahtar kelimeleri çıkar
        $keywords = $this->extractKeywords($question);
        
        // Query pattern olarak kaydet
        $sql_template = $this->createSQLTemplate($sql);
        
        $insert_sql = "INSERT INTO ai_query_patterns 
                       (firma_id, pattern_name, question_keywords, tables_involved, 
                        joins_used, sql_template, usage_count, avg_execution_time, last_used)
                       VALUES 
                       (:firma_id, :pattern_name, :keywords, :tables, :joins,
                        :sql_template, 1, :exec_time, NOW())";
        
        $sth = $this->conn->prepare($insert_sql);
        $sth->execute([
            "firma_id" => $this->firma_id,
            "pattern_name" => substr($question, 0, 100),
            "keywords" => json_encode($keywords),
            "tables" => json_encode($tables),
            "joins" => json_encode($joins_used),
            "sql_template" => $sql_template,
            "exec_time" => $execution_time
        ]);
        
        error_log("Learned query pattern from: $question");
    }
    
    /**
     * Sorudan anahtar kelimeleri çıkar
     */
    private function extractKeywords($question) {
        if (empty($question)) {
            return [];
        }
        $question = mb_strtolower($question, "UTF-8");
        
        // Stop words (önemsiz kelimeler)
        $stop_words = ["nedir", "ne", "kadar", "kaç", "bu", "şu", "bir", "iki", "var", "mı", "mi", "mu", "mü"];
        
        $words = preg_split('/\s+/', $question);
        $keywords = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });
        
        return array_values($keywords);
    }
    
    /**
     * SQL'den template oluştur (parametreleri ? ile değiştir)
     */
    private function createSQLTemplate($sql) {
        // Sayıları ? ile değiştir
        $sql = preg_replace('/\b\d+\b/', '?', $sql);
        
        // Tarihleri ? ile değiştir
        $sql = preg_replace("/'\d{4}-\d{2}-\d{2}[^']*'/", '?', $sql);
        
        // String literal'leri değiştirme (LIKE için önemli)
        
        return $sql;
    }
    
    /**
     * Öğrenilmiş şemayı al (AI için)
     */
    public function getLearnedSchema() {
        $sql = "SELECT 
                    table_name,
                    column_name,
                    data_type,
                    is_foreign_key,
                    references_table,
                    references_column,
                    sample_values,
                    inferred_meaning
                FROM ai_database_schema 
                ORDER BY table_name, column_name";
        
        $sth = $this->conn->query($sql);
        $schema = [];
        
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $table = $row["table_name"];
            if (!isset($schema[$table])) {
                $schema[$table] = [
                    "columns" => [],
                    "relationships" => []
                ];
            }
            
            $schema[$table]["columns"][] = [
                "name" => $row["column_name"],
                "type" => $row["data_type"],
                "is_fk" => (bool)$row["is_foreign_key"],
                "references" => $row["references_table"] ? 
                    "{$row['references_table']}.{$row['references_column']}" : null,
                "meaning" => $row["inferred_meaning"],
                "samples" => json_decode($row["sample_values"], true)
            ];
        }
        
        // İlişkileri ekle
        $rel_sql = "SELECT from_table, to_table, join_pattern, confidence_score
                    FROM ai_table_relationships
                    WHERE confidence_score > 0.5
                    ORDER BY confidence_score DESC";
        
        $rel_sth = $this->conn->query($rel_sql);
        while ($rel = $rel_sth->fetch(PDO::FETCH_ASSOC)) {
            if (isset($schema[$rel["from_table"]])) {
                $schema[$rel["from_table"]]["relationships"][] = [
                    "to_table" => $rel["to_table"],
                    "join" => $rel["join_pattern"],
                    "confidence" => $rel["confidence_score"]
                ];
            }
        }
        
        return $schema;
    }
    
    /**
     * Semantic bilgileri al
     */
    public function getColumnSemantics($table_name = null) {
        $sql = "SELECT table_name, column_name, semantic_type, 
                       display_name, description, format_pattern
                FROM ai_column_semantics";
        
        if ($table_name) {
            $sql .= " WHERE table_name = :table_name";
        }
        
        $sth = $this->conn->prepare($sql);
        if ($table_name) {
            $sth->execute(["table_name" => $table_name]);
        } else {
            $sth->execute();
        }
        
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
