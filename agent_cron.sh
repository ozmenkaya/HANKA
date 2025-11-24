#!/bin/bash
# HANKA Agent Cron Jobs
# Periyodik agent task'lerini çalıştırır

# Konfigürasyon
API_URL="https://hankapanel.com/agent_api.php"
API_KEY="HANKA_AGENT_CRON_2025" # Değiştirilmeli
LOG_DIR="/var/log/hanka_agents"

# Log klasörü yoksa oluştur
mkdir -p "$LOG_DIR"

# Timestamp
NOW=$(date '+%Y-%m-%d %H:%M:%S')
LOG_FILE="$LOG_DIR/agent_cron_$(date '+%Y%m%d').log"

echo "[$NOW] Agent Cron başladı" >> "$LOG_FILE"

# 1. Her sabah 9:00 - Günlük rapor
if [ "$(date '+%H:%M')" = "09:00" ]; then
    echo "[$NOW] Günlük rapor çalıştırılıyor..." >> "$LOG_FILE"
    
    curl -s -X POST "$API_URL?action=daily_report" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"recipients": ["admin@hankasys.com"]}' \
        >> "$LOG_FILE" 2>&1
    
    echo "" >> "$LOG_FILE"
fi

# 2. Her saat başı - Alert kontrolü
if [ "$(date '+%M')" = "00" ]; then
    echo "[$NOW] Alert kontrolü yapılıyor..." >> "$LOG_FILE"
    
    curl -s -X POST "$API_URL?action=check_alerts" \
        -H "X-API-Key: $API_KEY" \
        >> "$LOG_FILE" 2>&1
    
    echo "" >> "$LOG_FILE"
fi

# 3. Her gün 10:00 - Düşük stok bildirimi
if [ "$(date '+%H:%M')" = "10:00" ]; then
    echo "[$NOW] Düşük stok bildirimi gönderiliyor..." >> "$LOG_FILE"
    
    curl -s -X POST "$API_URL?action=notify_low_stock" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"recipients": ["admin@hankasys.com", "stok@hankasys.com"]}' \
        >> "$LOG_FILE" 2>&1
    
    echo "" >> "$LOG_FILE"
fi

# 4. Her gün 14:00 - Ödeme hatırlatıcıları
if [ "$(date '+%H:%M')" = "14:00" ]; then
    echo "[$NOW] Ödeme hatırlatıcıları gönderiliyor..." >> "$LOG_FILE"
    
    curl -s -X POST "$API_URL?action=send_payment_reminders" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"limit": 20}' \
        >> "$LOG_FILE" 2>&1
    
    echo "" >> "$LOG_FILE"
fi

# 5. Her Pazartesi 09:00 - Haftalık rapor
if [ "$(date '+%u')" = "1" ] && [ "$(date '+%H:%M')" = "09:00" ]; then
    echo "[$NOW] Haftalık rapor oluşturuluyor..." >> "$LOG_FILE"
    
    curl -s -X POST "$API_URL?action=weekly_report" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"recipients": ["admin@hankasys.com", "yonetim@hankasys.com"]}' \
        >> "$LOG_FILE" 2>&1
    
    echo "" >> "$LOG_FILE"
fi

# 6. Her 4 saatte bir - Anomali tespiti
if [ "$(date '+%M')" = "00" ] && [ $(($(date '+%H') % 4)) -eq 0 ]; then
    echo "[$NOW] Anomali tespiti yapılıyor..." >> "$LOG_FILE"
    
    curl -s -X POST "$API_URL?action=detect_anomalies" \
        -H "X-API-Key: $API_KEY" \
        >> "$LOG_FILE" 2>&1
    
    echo "" >> "$LOG_FILE"
fi

echo "[$NOW] Agent Cron tamamlandı" >> "$LOG_FILE"
echo "----------------------------------------" >> "$LOG_FILE"

# Eski log dosyalarını temizle (30 günden eski)
find "$LOG_DIR" -name "agent_cron_*.log" -mtime +30 -delete
