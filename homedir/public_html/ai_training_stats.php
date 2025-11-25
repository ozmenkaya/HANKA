<?php
session_name("PNL");
session_start();

if (!isset($_SESSION["giris_kontrol"]) || $_SESSION["giris_kontrol"] != 1) {
    header("Location: index.php");
    exit;
}

require_once("include/db.php");

$log_file = "/var/www/html/logs/training_data.jsonl";
$total_examples = file_exists($log_file) ? count(file($log_file)) : 0;
$progress = round($total_examples / 500 * 100, 1);

// Son 7 günlük istatistikler
$stats_query = "SELECT 
    DATE(tarih) as date,
    COUNT(*) as total,
    SUM(CASE WHEN sonuc_sayisi > 0 THEN 1 ELSE 0 END) as successful
    FROM ai_chat_history 
    WHERE firma_id = {$_SESSION["firma_id"]}
    AND tarih >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(tarih)
    ORDER BY date DESC";
$daily_stats = $conn->query($stats_query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Training Stats</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .progress-bar { width: 100%; height: 30px; background: #e0e0e0; border-radius: 15px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #8BC34A); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .stat { display: inline-block; margin: 10px 20px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #4CAF50; }
        .stat-label { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #4CAF50; color: white; }
        .badge-warning { background: #FF9800; color: white; }
        .badge-info { background: #2196F3; color: white; }
    </style>
</head>
<body>
    <h1>🤖 AI Training Data Dashboard</h1>
    
    <div class="card">
        <h2>📊 Fine-tuning İlerlemesi</h2>
        <div class="stat">
            <div class="stat-value"><?php echo $total_examples; ?></div>
            <div class="stat-label">Toplam Örnek</div>
        </div>
        <div class="stat">
            <div class="stat-value"><?php echo max(0, 500 - $total_examples); ?></div>
            <div class="stat-label">Hedefe Kalan</div>
        </div>
        <div class="stat">
            <div class="stat-value"><?php echo $progress; ?>%</div>
            <div class="stat-label">İlerleme</div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo min($progress, 100); ?>%">
                <?php echo $progress; ?>%
            </div>
        </div>
        
        <?php if ($total_examples >= 500): ?>
            <p style="color: #4CAF50; font-weight: bold; margin-top: 15px;">
                ✅ Fine-tuning için yeterli veri toplandı! OpenAI veya local model ile eğitime başlayabilirsin.
            </p>
        <?php else: ?>
            <p style="color: #666; margin-top: 15px;">
                📈 Hedefe ulaşmak için yaklaşık <strong><?php echo ceil((500 - $total_examples) / 10); ?></strong> gün daha kullanım gerekli (günde ~10 sorgu).
            </p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>📅 Son 7 Günlük Aktivite</h2>
        <table>
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Toplam Sorgu</th>
                    <th>Başarılı</th>
                    <th>Başarı Oranı</th>
                    <th>Training Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daily_stats as $stat): 
                    $success_rate = round($stat["successful"] / $stat["total"] * 100);
                ?>
                <tr>
                    <td><?php echo date("d.m.Y", strtotime($stat["date"])); ?></td>
                    <td><?php echo $stat["total"]; ?></td>
                    <td><?php echo $stat["successful"]; ?></td>
                    <td>
                        <span class="badge <?php echo $success_rate >= 80 ? "badge-success" : "badge-warning"; ?>">
                            %<?php echo $success_rate; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-info">
                            +<?php echo $stat["successful"]; ?> örnek
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2>💡 Sonraki Adımlar</h2>
        <ol>
            <li><strong>500 örnek toplandığında:</strong> 
                <code>scp root@91.99.186.98:/var/www/html/logs/training_data.jsonl .</code> ile indir</li>
            <li><strong>OpenAI Fine-tuning:</strong> 
                <code>openai api fine_tuning.jobs.create -t training_data.jsonl -m gpt-3.5-turbo</code></li>
            <li><strong>Maliyet:</strong> ~$10 one-time (500 örnek için)</li>
            <li><strong>Alternatif:</strong> Local Llama 3.1 8B ile Unsloth fine-tuning (ücretsiz)</li>
        </ol>
    </div>
    
    <div class="card">
        <h2>🔗 Linkler</h2>
        <a href="index.php" style="margin-right: 15px;">← Ana Sayfa</a>
        <a href="/logs/training_data.jsonl" download target="_blank">📥 Training Data İndir</a>
    </div>
</body>
</html>
