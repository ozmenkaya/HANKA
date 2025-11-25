<?php
/**
 * HANKA AI & Agent Ayarları
 * Bu sayfa index.php içinde include edilir, o yüzden session ve DB zaten yüklü
 * NOT: Sistem PDO kullanıyor, MySQLi değil
 */

$firma_id = $_SESSION['firma_id'];
$kullanici_id = $_SESSION['id'];

// Mevcut ayarları getir veya varsayılanları oluştur
$sql = "SELECT * FROM ai_agent_settings WHERE firma_id = :firma_id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute(['firma_id' => $firma_id]);
$result = $stmt->fetchAll();

if ($result && count($result) > 0) {
    $settings = $result[0];
} else {
    // Varsayılan ayarlar
    $settings = [
        'ai_enabled' => 1,
        'ai_use_finetuned' => 1,
        'ai_cache_enabled' => 1,
        'ai_response_detail' => 'normal',
        'openai_api_key' => '',
        'agent_enabled' => 1,
        'agent_daily_report_time' => '09:00',
        'agent_daily_report_enabled' => 1,
        'agent_weekly_report_enabled' => 1,
        'agent_weekly_report_day' => 'monday',
        'alert_stock_enabled' => 1,
        'alert_stock_threshold' => 10,
        'alert_payment_enabled' => 1,
        'alert_payment_days_before' => 7,
        'alert_order_enabled' => 1,
        'notification_email_enabled' => 1,
        'notification_email_addresses' => '',
        'notification_whatsapp_enabled' => 0,
        'notification_whatsapp_numbers' => '',
        'tts_enabled' => 1,
        'tts_provider' => 'openai',
        'tts_voice' => 'nova',
        'tts_speed' => 1.0,
        'tts_auto_play' => 0
    ];
}

// Ayar güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $ai_enabled = isset($_POST['ai_enabled']) ? 1 : 0;
    $ai_use_finetuned = isset($_POST['ai_use_finetuned']) ? 1 : 0;
    $ai_cache_enabled = isset($_POST['ai_cache_enabled']) ? 1 : 0;
    $ai_response_detail = $_POST['ai_response_detail'];
    $openai_api_key = $_POST['openai_api_key'];
    
    $agent_enabled = isset($_POST['agent_enabled']) ? 1 : 0;
    $agent_daily_report_time = $_POST['agent_daily_report_time'];
    $agent_daily_report_enabled = isset($_POST['agent_daily_report_enabled']) ? 1 : 0;
    $agent_weekly_report_enabled = isset($_POST['agent_weekly_report_enabled']) ? 1 : 0;
    $agent_weekly_report_day = $_POST['agent_weekly_report_day'];
    
    $alert_stock_enabled = isset($_POST['alert_stock_enabled']) ? 1 : 0;
    $alert_stock_threshold = intval($_POST['alert_stock_threshold']);
    $alert_payment_enabled = isset($_POST['alert_payment_enabled']) ? 1 : 0;
    $alert_payment_days_before = intval($_POST['alert_payment_days_before']);
    $alert_order_enabled = isset($_POST['alert_order_enabled']) ? 1 : 0;
    
    $notification_email_enabled = isset($_POST['notification_email_enabled']) ? 1 : 0;
    $notification_email_addresses = $_POST['notification_email_addresses'];
    $notification_whatsapp_enabled = isset($_POST['notification_whatsapp_enabled']) ? 1 : 0;
    $notification_whatsapp_numbers = $_POST['notification_whatsapp_numbers'];
    
    $tts_enabled = isset($_POST['tts_enabled']) ? 1 : 0;
    $tts_provider = $_POST['tts_provider'];
    $tts_voice = $_POST['tts_voice'];
    $tts_speed = floatval($_POST['tts_speed']);
    $tts_auto_play = isset($_POST['tts_auto_play']) ? 1 : 0;
    
    // Güncelleme veya ekleme kontrolü
    $check_sql = "SELECT id FROM ai_agent_settings WHERE firma_id = :firma_id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute(['firma_id' => $firma_id]);
    $exists = $check_stmt->fetch();
    
    try {
        if ($exists) {
            // UPDATE
            $sql = "UPDATE ai_agent_settings SET
                    ai_enabled = :ai_enabled,
                    ai_use_finetuned = :ai_use_finetuned,
                    ai_cache_enabled = :ai_cache_enabled,
                    ai_response_detail = :ai_response_detail,
                    openai_api_key = :openai_api_key,
                    agent_enabled = :agent_enabled,
                    agent_daily_report_time = :agent_daily_report_time,
                    agent_daily_report_enabled = :agent_daily_report_enabled,
                    agent_weekly_report_enabled = :agent_weekly_report_enabled,
                    agent_weekly_report_day = :agent_weekly_report_day,
                    alert_stock_enabled = :alert_stock_enabled,
                    alert_stock_threshold = :alert_stock_threshold,
                    alert_payment_enabled = :alert_payment_enabled,
                    alert_payment_days_before = :alert_payment_days_before,
                    alert_order_enabled = :alert_order_enabled,
                    notification_email_enabled = :notification_email_enabled,
                    notification_email_addresses = :notification_email_addresses,
                    notification_whatsapp_enabled = :notification_whatsapp_enabled,
                    notification_whatsapp_numbers = :notification_whatsapp_numbers,
                    tts_enabled = :tts_enabled,
                    tts_provider = :tts_provider,
                    tts_voice = :tts_voice,
                    tts_speed = :tts_speed,
                    tts_auto_play = :tts_auto_play,
                    updated_at = NOW()
                    WHERE firma_id = :firma_id";
        } else {
            // INSERT
            $sql = "INSERT INTO ai_agent_settings (
                        firma_id, ai_enabled, ai_use_finetuned, ai_cache_enabled, ai_response_detail, openai_api_key,
                        agent_enabled, agent_daily_report_time, agent_daily_report_enabled,
                        agent_weekly_report_enabled, agent_weekly_report_day,
                        alert_stock_enabled, alert_stock_threshold, alert_payment_enabled,
                        alert_payment_days_before, alert_order_enabled,
                        notification_email_enabled, notification_email_addresses,
                        notification_whatsapp_enabled, notification_whatsapp_numbers,
                        tts_enabled, tts_provider, tts_voice, tts_speed, tts_auto_play
                    ) VALUES (
                        :firma_id, :ai_enabled, :ai_use_finetuned, :ai_cache_enabled, :ai_response_detail, :openai_api_key,
                        :agent_enabled, :agent_daily_report_time, :agent_daily_report_enabled,
                        :agent_weekly_report_enabled, :agent_weekly_report_day,
                        :alert_stock_enabled, :alert_stock_threshold, :alert_payment_enabled,
                        :alert_payment_days_before, :alert_order_enabled,
                        :notification_email_enabled, :notification_email_addresses,
                        :notification_whatsapp_enabled, :notification_whatsapp_numbers,
                        :tts_enabled, :tts_provider, :tts_voice, :tts_speed, :tts_auto_play
                    )";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'firma_id' => $firma_id,
            'ai_enabled' => $ai_enabled,
            'ai_use_finetuned' => $ai_use_finetuned,
            'ai_cache_enabled' => $ai_cache_enabled,
            'ai_response_detail' => $ai_response_detail,
            'openai_api_key' => $openai_api_key,
            'agent_enabled' => $agent_enabled,
            'agent_daily_report_time' => $agent_daily_report_time,
            'agent_daily_report_enabled' => $agent_daily_report_enabled,
            'agent_weekly_report_enabled' => $agent_weekly_report_enabled,
            'agent_weekly_report_day' => $agent_weekly_report_day,
            'alert_stock_enabled' => $alert_stock_enabled,
            'alert_stock_threshold' => $alert_stock_threshold,
            'alert_payment_enabled' => $alert_payment_enabled,
            'alert_payment_days_before' => $alert_payment_days_before,
            'alert_order_enabled' => $alert_order_enabled,
            'notification_email_enabled' => $notification_email_enabled,
            'notification_email_addresses' => $notification_email_addresses,
            'notification_whatsapp_enabled' => $notification_whatsapp_enabled,
            'notification_whatsapp_numbers' => $notification_whatsapp_numbers,
            'tts_enabled' => $tts_enabled,
            'tts_provider' => $tts_provider,
            'tts_voice' => $tts_voice,
            'tts_speed' => $tts_speed,
            'tts_auto_play' => $tts_auto_play
        ]);
        
        $success_message = "Ayarlar başarıyla kaydedildi!";
        
        // Ayarları yeniden yükle
        $stmt = $conn->prepare("SELECT * FROM ai_agent_settings WHERE firma_id = :firma_id LIMIT 1");
        $stmt->execute(['firma_id' => $firma_id]);
        $result = $stmt->fetchAll();
        if ($result) {
            $settings = $result[0];
        }
    } catch (Exception $e) {
        $error_message = "Ayarlar kaydedilemedi: " . $e->getMessage();
    }
}

// Cache temizleme
if (isset($_POST['clear_cache'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM ai_cache WHERE firma_id = :firma_id");
        $stmt->execute(['firma_id' => $firma_id]);
        $success_message = "Cache başarıyla temizlendi!";
    } catch (Exception $e) {
        $error_message = "Cache temizlenemedi: " . $e->getMessage();
    }
}

// Cache istatistikleri
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(hit_count) as hits,
    SUM(CASE WHEN is_valid=1 THEN 1 ELSE 0 END) as valid
    FROM ai_cache WHERE firma_id = :firma_id");
$stmt->execute(['firma_id' => $firma_id]);
$cache_stats = $stmt->fetch();

// Agent istatistikleri
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_alerts,
    SUM(CASE WHEN is_resolved=0 THEN 1 ELSE 0 END) as unresolved,
    SUM(CASE WHEN alert_level='critical' THEN 1 ELSE 0 END) as critical
    FROM agent_alerts WHERE firma_id = :firma_id");
$stmt->execute(['firma_id' => $firma_id]);
$agent_stats = $stmt->fetch();

?>

<style>
    .settings-section {
        background: #fff;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .section-title {
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #007bff;
    }
    .section-icon {
        margin-right: 10px;
        color: #007bff;
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .stat-card h3 {
        font-size: 2rem;
        margin: 0;
    }
    .stat-card p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
    .btn-custom {
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 500;
    }
    .info-badge {
        background: #e7f3ff;
        color: #0066cc;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        margin-top: 5px;
        display: inline-block;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card mb-2 mt-2 border-secondary">
            <div class="card-header d-flex justify-content-between border-secondary">
                <h5>
                    <i class="fas fa-robot"></i> AI & Agent Ayarları
                </h5>
                <div>
                    <a href="javascript:window.history.back();" 
                        class="btn btn-secondary"
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom" 
                        data-bs-title="Geri Dön"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h3><?= $cache_stats['total'] ?? 0 ?></h3>
                            <p>Toplam Cache</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3><?= $cache_stats['hits'] ?? 0 ?></h3>
                            <p>Cache Hits</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3><?= $agent_stats['total_alerts'] ?? 0 ?></h3>
                            <p>Toplam Alert</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <h3><?= $agent_stats['unresolved'] ?? 0 ?></h3>
                            <p>Çözülmemiş Alert</p>
                        </div>
                    </div>
                </div>

                <!-- Ayarlar Formu -->
                <form method="POST" class="needs-validation" novalidate>
                    
                    <!-- AI Ayarları -->
                    <div class="settings-section">
                        <h4 class="section-title">
                            <i class="fas fa-brain section-icon"></i> AI Ayarları
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ai_enabled" 
                                           id="ai_enabled" <?= $settings['ai_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ai_enabled">
                                        <strong>AI Sistemi Aktif</strong>
                                        <div class="info-badge">
                                            <i class="fas fa-info-circle"></i> Tüm AI özelliklerini aktif/pasif yapar
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ai_use_finetuned" 
                                           id="ai_use_finetuned" <?= $settings['ai_use_finetuned'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ai_use_finetuned">
                                        <strong>Fine-tuned Model Kullan</strong>
                                        <div class="info-badge">
                                            <i class="fas fa-info-circle"></i> Özel eğitilmiş HANKA modelini kullanır
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ai_cache_enabled" 
                                           id="ai_cache_enabled" <?= $settings['ai_cache_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ai_cache_enabled">
                                        <strong>Cache Sistemi</strong>
                                        <div class="info-badge">
                                            <i class="fas fa-info-circle"></i> Sorgular önbelleğe alınır (daha hızlı)
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="ai_response_detail" class="form-label"><strong>Yanıt Detay Seviyesi</strong></label>
                                <select class="form-select" name="ai_response_detail" id="ai_response_detail" required>
                                    <option value="minimal" <?= $settings['ai_response_detail'] === 'minimal' ? 'selected' : '' ?>>
                                        Minimal (Kısa)
                                    </option>
                                    <option value="normal" <?= $settings['ai_response_detail'] === 'normal' ? 'selected' : '' ?>>
                                        Normal (Dengeli)
                                    </option>
                                    <option value="detailed" <?= $settings['ai_response_detail'] === 'detailed' ? 'selected' : '' ?>>
                                        Detaylı (Kapsamlı)
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="openai_api_key" class="form-label"><strong>OpenAI API Key</strong></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="openai_api_key" 
                                           id="openai_api_key" 
                                           value="<?= isset($settings['openai_api_key']) ? htmlspecialchars($settings['openai_api_key']) : '' ?>" 
                                           placeholder="sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> OpenAI API anahtarınız. 
                                    <a href="https://platform.openai.com/api-keys" target="_blank">Buradan</a> alabilirsiniz.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Agent Ayarları -->
                    <div class="settings-section">
                        <h4 class="section-title">
                            <i class="fas fa-robot section-icon"></i> Agent Ayarları
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="agent_enabled" 
                                           id="agent_enabled" <?= $settings['agent_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="agent_enabled">
                                        <strong>Multi-Agent Sistemi Aktif</strong>
                                        <div class="info-badge">
                                            <i class="fas fa-info-circle"></i> Otonom agent'ları aktif eder
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="agent_daily_report_enabled" 
                                           id="agent_daily_report_enabled" <?= $settings['agent_daily_report_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="agent_daily_report_enabled">
                                        <strong>Günlük Raporlar</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="agent_daily_report_time" class="form-label"><strong>Günlük Rapor Saati</strong></label>
                                <input type="time" class="form-control" name="agent_daily_report_time" 
                                       id="agent_daily_report_time" value="<?= htmlspecialchars($settings['agent_daily_report_time']) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="agent_weekly_report_enabled" 
                                           id="agent_weekly_report_enabled" <?= $settings['agent_weekly_report_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="agent_weekly_report_enabled">
                                        <strong>Haftalık Raporlar</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="agent_weekly_report_day" class="form-label"><strong>Haftalık Rapor Günü</strong></label>
                                <select class="form-select" name="agent_weekly_report_day" id="agent_weekly_report_day" required>
                                    <option value="monday" <?= $settings['agent_weekly_report_day'] === 'monday' ? 'selected' : '' ?>>Pazartesi</option>
                                    <option value="tuesday" <?= $settings['agent_weekly_report_day'] === 'tuesday' ? 'selected' : '' ?>>Salı</option>
                                    <option value="wednesday" <?= $settings['agent_weekly_report_day'] === 'wednesday' ? 'selected' : '' ?>>Çarşamba</option>
                                    <option value="thursday" <?= $settings['agent_weekly_report_day'] === 'thursday' ? 'selected' : '' ?>>Perşembe</option>
                                    <option value="friday" <?= $settings['agent_weekly_report_day'] === 'friday' ? 'selected' : '' ?>>Cuma</option>
                                    <option value="saturday" <?= $settings['agent_weekly_report_day'] === 'saturday' ? 'selected' : '' ?>>Cumartesi</option>
                                    <option value="sunday" <?= $settings['agent_weekly_report_day'] === 'sunday' ? 'selected' : '' ?>>Pazar</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Ayarları -->
                    <div class="settings-section">
                        <h4 class="section-title">
                            <i class="fas fa-bell section-icon"></i> Alert Ayarları
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="alert_stock_enabled" 
                                           id="alert_stock_enabled" <?= $settings['alert_stock_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="alert_stock_enabled">
                                        <strong>Stok Uyarıları</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="alert_stock_threshold" class="form-label"><strong>Stok Eşiği (%)</strong></label>
                                <input type="number" class="form-control" name="alert_stock_threshold" 
                                       id="alert_stock_threshold" value="<?= htmlspecialchars($settings['alert_stock_threshold']) ?>" 
                                       min="1" max="100" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="alert_payment_enabled" 
                                           id="alert_payment_enabled" <?= $settings['alert_payment_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="alert_payment_enabled">
                                        <strong>Ödeme Uyarıları</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="alert_payment_days_before" class="form-label"><strong>Kaç Gün Önce</strong></label>
                                <input type="number" class="form-control" name="alert_payment_days_before" 
                                       id="alert_payment_days_before" value="<?= htmlspecialchars($settings['alert_payment_days_before']) ?>" 
                                       min="1" max="30" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="alert_order_enabled" 
                                           id="alert_order_enabled" <?= $settings['alert_order_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="alert_order_enabled">
                                        <strong>Sipariş Uyarıları</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bildirim Ayarları -->
                    <div class="settings-section">
                        <h4 class="section-title">
                            <i class="fas fa-paper-plane section-icon"></i> Bildirim Ayarları
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="notification_email_enabled" 
                                           id="notification_email_enabled" <?= $settings['notification_email_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notification_email_enabled">
                                        <strong>Email Bildirimleri</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="notification_email_addresses" class="form-label"><strong>Email Adresleri</strong></label>
                                <textarea class="form-control" name="notification_email_addresses" 
                                          id="notification_email_addresses" rows="2" 
                                          placeholder="email1@domain.com, email2@domain.com"><?= htmlspecialchars($settings['notification_email_addresses']) ?></textarea>
                                <small class="text-muted">Virgülle ayırarak birden fazla adres girebilirsiniz</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="notification_whatsapp_enabled" 
                                           id="notification_whatsapp_enabled" <?= $settings['notification_whatsapp_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notification_whatsapp_enabled">
                                        <strong>WhatsApp Bildirimleri</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="notification_whatsapp_numbers" class="form-label"><strong>WhatsApp Numaraları</strong></label>
                                <textarea class="form-control" name="notification_whatsapp_numbers" 
                                          id="notification_whatsapp_numbers" rows="2" 
                                          placeholder="+905xxxxxxxxx, +905xxxxxxxxx"><?= htmlspecialchars($settings['notification_whatsapp_numbers']) ?></textarea>
                                <small class="text-muted">Ülke kodu ile, virgülle ayırarak giriniz</small>
                            </div>
                        </div>
                    </div>

                    <!-- Seslendirme Ayarları (TTS) -->
                    <div class="settings-section">
                        <h4 class="section-title">
                            <i class="fas fa-volume-up section-icon"></i> Seslendirme Ayarları (TTS)
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="tts_enabled" 
                                           id="tts_enabled" <?= isset($settings['tts_enabled']) && $settings['tts_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tts_enabled">
                                        <strong>Seslendirme Aktif</strong>
                                        <div class="info-badge">
                                            <i class="fas fa-info-circle"></i> AI yanıtlarını sesli okutur
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="tts_auto_play" 
                                           id="tts_auto_play" <?= isset($settings['tts_auto_play']) && $settings['tts_auto_play'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tts_auto_play">
                                        <strong>Otomatik Oynat</strong>
                                        <div class="info-badge">
                                            <i class="fas fa-info-circle"></i> Yanıt geldiğinde otomatik seslendir
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tts_provider" class="form-label"><strong>TTS Sağlayıcı</strong></label>
                                <select class="form-select" name="tts_provider" id="tts_provider" required>
                                    <option value="openai" <?= isset($settings['tts_provider']) && $settings['tts_provider'] === 'openai' ? 'selected' : '' ?>>
                                        OpenAI TTS (Yüksek Kalite)
                                    </option>
                                    <option value="google" <?= isset($settings['tts_provider']) && $settings['tts_provider'] === 'google' ? 'selected' : '' ?>>
                                        Google TTS
                                    </option>
                                    <option value="browser" <?= isset($settings['tts_provider']) && $settings['tts_provider'] === 'browser' ? 'selected' : '' ?>>
                                        Tarayıcı TTS (Ücretsiz)
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tts_voice" class="form-label"><strong>Ses Tonu</strong></label>
                                <select class="form-select" name="tts_voice" id="tts_voice" required>
                                    <optgroup label="OpenAI Sesler">
                                        <option value="alloy" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'alloy' ? 'selected' : '' ?>>Alloy (Dengeli)</option>
                                        <option value="echo" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'echo' ? 'selected' : '' ?>>Echo (Erkek)</option>
                                        <option value="fable" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'fable' ? 'selected' : '' ?>>Fable (İngiliz)</option>
                                        <option value="onyx" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'onyx' ? 'selected' : '' ?>>Onyx (Derin)</option>
                                        <option value="nova" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'nova' ? 'selected' : '' ?>>Nova (Kadın)</option>
                                        <option value="shimmer" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'shimmer' ? 'selected' : '' ?>>Shimmer (Yumuşak)</option>
                                    </optgroup>
                                    <optgroup label="Google Sesler">
                                        <option value="tr-TR-Standard-A" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'tr-TR-Standard-A' ? 'selected' : '' ?>>Türkçe Kadın</option>
                                        <option value="tr-TR-Standard-B" <?= isset($settings['tts_voice']) && $settings['tts_voice'] === 'tr-TR-Standard-B' ? 'selected' : '' ?>>Türkçe Erkek</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tts_speed" class="form-label"><strong>Hız (<?= isset($settings['tts_speed']) ? $settings['tts_speed'] : '1.0' ?>x)</strong></label>
                                <input type="range" class="form-range" name="tts_speed" id="tts_speed" 
                                       min="0.5" max="2.0" step="0.1" 
                                       value="<?= isset($settings['tts_speed']) ? htmlspecialchars($settings['tts_speed']) : '1.0' ?>"
                                       oninput="this.previousElementSibling.innerHTML = 'Hız (' + this.value + 'x)'">
                                <small class="text-muted">0.5x (Yavaş) - 2.0x (Hızlı)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="row">
                        <div class="col-12 text-end">
                            <button type="submit" name="save_settings" class="btn btn-primary btn-custom">
                                <i class="fas fa-save"></i> Ayarları Kaydet
                            </button>
                        </div>
                    </div>

                </form>

                <!-- Hızlı İşlemler -->
                <div class="settings-section mt-4">
                    <h4 class="section-title">
                        <i class="fas fa-bolt section-icon"></i> Hızlı İşlemler
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <form method="POST" class="d-inline">
                                <button type="submit" name="clear_cache" class="btn btn-warning w-100 btn-custom" 
                                        onclick="return confirm('Cache temizlensin mi?')">
                                    <i class="fas fa-trash"></i> Cache Temizle
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-info w-100 btn-custom" onclick="testAgent()">
                                <i class="fas fa-vial"></i> Agent Test Et
                            </button>
                        </div>
                        <div class="col-md-4">
                            <a href="/index.php?url=ai_learning_dashboard" class="btn btn-success w-100 btn-custom">
                                <i class="fas fa-chart-line"></i> AI İstatistikleri
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function testAgent() {
        if (confirm('Agent sistemi test edilecek. Devam edilsin mi?')) {
            fetch('agent_api.php?action=check_alerts&api_key=HANKA_AGENT_CRON_2025')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Agent test başarılı!\n\n' + 
                              'Alert sayısı: ' + (data.alert_count || 0) + '\n' +
                              data.summary);
                    } else {
                        alert('❌ Agent test başarısız: ' + (data.error || 'Bilinmeyen hata'));
                    }
                })
                .catch(error => {
                    alert('❌ Bağlantı hatası: ' + error);
                });
        }
    }

    function toggleApiKeyVisibility() {
        const input = document.getElementById('openai_api_key');
        const icon = document.getElementById('toggleIcon');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
</script>
