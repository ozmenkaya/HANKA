#!/bin/bash
# ============================================
# HANKA ERP - Disk Temizleme Planı
# ============================================
# Tarih: 2025-12-05
# Amaç: Sunucudaki gereksiz dosyaları temizlemek
# Toplam Kazanç: ~2.5 GB

echo "🧹 Disk Temizleme Başlıyor..."
echo ""

# ============================================
# 1. ESKİ BACKUP DOSYALARINI TEMİZLE
# ============================================
echo "📦 1. Eski backup dosyalarını temizliyorum..."

# Son 3 günlük dosya yedeklerini tut, geri kalanı sil (1.5 GB kazanç)
echo "  - Dosya yedekleri (son 3 gün kalacak)..."
find /root/backups/files -name "html_files_*.tar.gz" -mtime +3 -delete
echo "  ✓ Eski dosya yedekleri silindi"

# Son 7 günlük DB yedeklerini tut (140 MB kazanç)
echo "  - Veritabanı yedekleri (son 7 gün kalacak)..."
find /root/backups/database -name "*.sql.gz" -mtime +7 -delete
echo "  ✓ Eski DB yedekleri silindi"

# Cron yedekleri (son 5 gün)
echo "  - Cron DB yedekleri (son 5 gün kalacak)..."
find /var/www/html/cron/yedekler -name "panelhankasys_crm2-*.sql" -mtime +5 -delete
echo "  ✓ Eski cron yedekleri silindi"

# Saatlik MySQL yedekleri (son 48 saat)
echo "  - Saatlik MySQL yedekleri (son 48 saat kalacak)..."
find /root/backups/mysql_hourly -name "*.sql.gz" -mtime +2 -delete
echo "  ✓ Eski saatlik yedekler silindi"

# ============================================
# 2. SISTEM LOGLARINI TEMİZLE
# ============================================
echo ""
echo "📝 2. Sistem loglarını temizliyorum..."

# Journal logları (30 gün tut, 200 MB kazanç)
journalctl --vacuum-time=30d
echo "  ✓ Journal logları temizlendi"

# Apache logları (son 30 gün)
find /var/log/apache2 -name "*.log.*" -mtime +30 -delete
echo "  ✓ Apache logları temizlendi"

# Nginx logları (son 30 gün)
find /var/log/nginx -name "*.log.*" -mtime +30 -delete
echo "  ✓ Nginx logları temizlendi"

# ============================================
# 3. GEREKSIZ DOSYALARI TEMİZLE
# ============================================
echo ""
echo "🗑️  3. Gereksiz dosyaları temizliyorum..."

# APT cache
apt-get clean
apt-get autoclean
echo "  ✓ APT cache temizlendi"

# Eski kernel'ları temizle
apt-get autoremove --purge -y
echo "  ✓ Eski kernel'lar temizlendi"

# Tmp dosyaları (7 günden eski)
find /tmp -type f -atime +7 -delete 2>/dev/null
echo "  ✓ Eski tmp dosyaları temizlendi"

# Redis dump.rdb yedekleri
find /var/lib/redis -name "dump.rdb.*" -delete 2>/dev/null
echo "  ✓ Redis yedekleri temizlendi"

# ============================================
# 4. NODE_MODULES TEMİZLE (OPSİYONEL)
# ============================================
echo ""
echo "⚠️  4. Opsiyonel temizlik (node_modules)..."
echo "  - /var/www/html/assets/node_modules (71 MB)"
echo "  - Eğer frontend build etmiyorsanız silebilirsiniz"
# rm -rf /var/www/html/assets/node_modules
echo "  ⚠️  Manuel silme gerekli (risk var!)"

# ============================================
# 5. MANUEL SİLİNEBİLECEKLER
# ============================================
echo ""
echo "📋 Manuel silinebilecek dosyalar:"
echo "  - /root/hanka_full_clean.sql (16 MB)"
echo "  - /root/hanka_full_db.sql (16 MB)"
echo "  - /root/.cache/pip (13 MB)"
echo ""

# ============================================
# SONUÇLAR
# ============================================
echo ""
echo "✅ Temizlik tamamlandı!"
echo ""
echo "📊 Disk kullanımı:"
df -h / | tail -1
echo ""
echo "🎯 Kazanılan alan hesaplanıyor..."
du -sh /root/backups /var/log /var/cache

echo ""
echo "============================================"
echo "🎉 Temizlik başarılı!"
echo "============================================"
