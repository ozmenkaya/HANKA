#!/bin/bash
# ============================================
# HANKA ERP - Backup Retention Policy
# ============================================
# Otomatik backup temizleme (crontab'a eklenecek)
# Hedef: Her gün eski yedekleri sil, yeni kazanç

# Dosya yedekleri: Son 3 gün
find /root/backups/files -name "html_files_*.tar.gz" -mtime +3 -delete

# DB yedekleri: Son 7 gün
find /root/backups/database -name "*.sql.gz" -mtime +7 -delete

# Cron DB yedekleri: Son 5 gün
find /var/www/html/cron/yedekler -name "panelhankasys_crm2-*.sql" -mtime +5 -delete

# Saatlik yedekler: Son 48 saat
find /root/backups/mysql_hourly -name "*.sql.gz" -mtime +2 -delete

# Journal logları: 30 gün
journalctl --vacuum-time=30d --quiet

# Log kaydet
echo "$(date): Backup cleanup completed" >> /root/backups/cleanup.log
