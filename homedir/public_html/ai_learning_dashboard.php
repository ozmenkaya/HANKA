<?php
require_once "include/oturum_kontrol.php";
require_once "include/db.php";

$firma_id = $_SESSION['firma_id'];

// Training data dosyasını kontrol et
$training_file = __DIR__ . '/logs/training_data.jsonl';
$training_count = file_exists($training_file) ? count(file($training_file)) : 0;

// İstatistikleri al
try {
    $stats_sql = "SELECT 
        (SELECT COUNT(DISTINCT table_name) FROM ai_database_schema) as total_tables,
        (SELECT COUNT(*) FROM ai_database_schema) as total_columns,
        (SELECT COUNT(*) FROM ai_database_schema WHERE is_foreign_key = 1) as foreign_keys,
        (SELECT COUNT(*) FROM ai_table_relationships) as relationships,
        (SELECT COUNT(*) FROM ai_chat_history WHERE firma_id = :firma_id) as total_queries,
        (SELECT ROUND(AVG(rating), 2) FROM ai_feedback WHERE firma_id = :firma_id AND rating > 0) as avg_rating,
        (SELECT COUNT(*) FROM ai_chat_history WHERE firma_id = :firma_id AND cevap NOT LIKE '%hata%' AND cevap NOT LIKE '%error%') as successful_queries";

    $stats_sth = $conn->prepare($stats_sql);
    $stats_sth->execute(['firma_id' => $firma_id]);
    $stats = $stats_sth->fetch(PDO::FETCH_ASSOC);
    
    // Success rate hesapla
    $success_rate = $stats['total_queries'] > 0 ? 
        round(($stats['successful_queries'] / $stats['total_queries']) * 100, 1) : 0;

    // En bağlantılı tablolar
    $top_tables_sql = "SELECT 
        from_table as table_name,
        COUNT(*) as connection_count
    FROM ai_table_relationships
    GROUP BY from_table
    ORDER BY connection_count DESC
    LIMIT 10";
    $top_tables_sth = $conn->query($top_tables_sql);
    $top_tables = $top_tables_sth->fetchAll(PDO::FETCH_ASSOC);

    // Son başarılı sorgular
    $recent_queries_sql = "SELECT 
        soru as query_text,
        cevap,
        tarih as created_at
    FROM ai_chat_history
    WHERE firma_id = :firma_id
    AND cevap NOT LIKE '%hata%' 
    AND cevap NOT LIKE '%error%'
    ORDER BY tarih DESC
    LIMIT 10";
    $recent_queries_sth = $conn->prepare($recent_queries_sql);
    $recent_queries_sth->execute(['firma_id' => $firma_id]);
    $recent_queries = $recent_queries_sth->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">
                <i class="mdi mdi-robot"></i> AI Öğrenme Sistemi
            </h4>
        </div>
    </div>
</div>

<?php if (isset($error_message)): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-danger">
            <strong>Hata:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="row">
    <!-- Toplam Tablolar -->
    <div class="col-md-3 col-sm-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="mdi mdi-database widget-icon bg-primary-lighten text-primary"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Veritabanı Tabloları">Toplam Tablo</h5>
                <h3 class="mt-3 mb-3"><?php echo $stats['total_tables'] ?? 0; ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Öğrenilmiş tablo sayısı</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Toplam Sütunlar -->
    <div class="col-md-3 col-sm-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="mdi mdi-table-column widget-icon bg-success-lighten text-success"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Toplam Sütun">Toplam Sütun</h5>
                <h3 class="mt-3 mb-3"><?php echo $stats['total_columns'] ?? 0; ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Analiz edilmiş sütun</span>
                </p>
            </div>
        </div>
    </div>

    <!-- FK İlişkileri -->
    <div class="col-md-3 col-sm-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="mdi mdi-link-variant widget-icon bg-info-lighten text-info"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Foreign Key">FK İlişkileri</h5>
                <h3 class="mt-3 mb-3"><?php echo $stats['relationships'] ?? 0; ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Tablo ilişkisi</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Training Data -->
    <div class="col-md-3 col-sm-6">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="mdi mdi-brain widget-icon bg-warning-lighten text-warning"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0" title="Training Examples">Training Data</h5>
                <h3 class="mt-3 mb-3"><?php echo $training_count; ?> / 500</h3>
                <p class="mb-0 text-muted">
                    <span class="badge badge-warning"><?php echo round($training_count/500*100, 1); ?>%</span>
                    <span class="text-nowrap ml-1">Fine-tuning hazırlığı</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- İkinci Satır: Sorgu İstatistikleri -->
<div class="row">
    <!-- Toplam Sorgular -->
    <div class="col-md-4">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="mdi mdi-message-text widget-icon bg-primary-lighten text-primary"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0">Toplam Sorgu</h5>
                <h3 class="mt-3 mb-3"><?php echo $stats['total_queries'] ?? 0; ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">İşlenmiş sorgu sayısı</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Ortalama Rating -->
    <div class="col-md-4">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="mdi mdi-star widget-icon bg-success-lighten text-success"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0">Ortalama Puan</h5>
                <h3 class="mt-3 mb-3"><?php echo $stats['avg_rating'] ?? '-'; ?></h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Kullanıcı geri bildirimi</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Başarı Oranı -->
    <div class="col-md-4">
        <div class="card widget-flat">
            <div class="card-body">
                <div class="float-right">
                    <i class="mdi mdi-check-circle widget-icon bg-info-lighten text-info"></i>
                </div>
                <h5 class="text-muted font-weight-normal mt-0">Başarı Oranı</h5>
                <h3 class="mt-3 mb-3"><?php echo $success_rate; ?>%</h3>
                <p class="mb-0 text-muted">
                    <span class="badge badge-info"><?php echo $stats['successful_queries'] ?? 0; ?> / <?php echo $stats['total_queries'] ?? 0; ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Tablolar -->
<div class="row">
    <!-- En Bağlantılı Tablolar -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">
                    <i class="mdi mdi-table-network"></i> En Bağlantılı Tablolar
                </h4>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tablo Adı</th>
                                <th class="text-right">Bağlantı Sayısı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_tables)): ?>
                                <?php foreach ($top_tables as $table): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($table['table_name']); ?></code></td>
                                    <td class="text-right">
                                        <span class="badge badge-primary"><?php echo $table['connection_count']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Henüz ilişki verisi yok</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Başarılı Sorgular -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">
                    <i class="mdi mdi-history"></i> Son Başarılı Sorgular
                </h4>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Sorgu</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_queries)): ?>
                                <?php foreach ($recent_queries as $query): ?>
                                <tr>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($query['query_text'], 0, 50)); ?>...</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($query['created_at'])); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Henüz sorgu kaydı yok</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sistem Bilgileri -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">
                    <i class="mdi mdi-information"></i> Sistem Bilgileri
                </h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <p class="text-muted mb-0">Model</p>
                            <h5>GPT-4o-mini</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <p class="text-muted mb-0">Fine-tuning Hedef</p>
                            <h5>500 örnek</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <p class="text-muted mb-0">Mevcut Progress</p>
                            <h5><?php echo round($training_count/500*100, 1); ?>%</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <p class="text-muted mb-0">Eksik Örnek</p>
                            <h5><?php echo max(0, 500 - $training_count); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
