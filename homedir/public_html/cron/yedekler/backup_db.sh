#!/bin/bash
# Sabitler
BACKUP_DIR="/home/com/public_html/cron/yedekler"
DATABASE="com_panelhankasys"
DB_USER="com_ozmen"
DB_PASS="=eTJHP2T.Z%i"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_FILE="$BACKUP_DIR/$DATABASE-$DATE.sql"

# Yedekleme dizininin varlığını kontrol et
if [ ! -d "$BACKUP_DIR" ]; then
    echo "Hata: $BACKUP_DIR dizini mevcut değil!"
    exit 1
fi

# Yedekleme dizinine geç (isteğe bağlı)
cd "$BACKUP_DIR" || { echo "Hata: $BACKUP_DIR dizinine geçilemedi!"; exit 1; }

# mysqldump ile veritabanı yedeğini al
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DATABASE" > "$BACKUP_FILE" 2>/tmp/backup_error.log
if [ $? -ne 0 ]; then
    echo "Hata: Yedekleme başarısız! Hata mesajı:"
    cat /tmp/backup_error.log
    exit 1
fi

# Yedek dosyasının izinlerini ayarla
chmod 600 "$BACKUP_FILE"

# Başarılı mesajı
echo "Yedekleme başarılı: $BACKUP_FILE"

# Eski yedekleri temizle (7 günden eski)
find "$BACKUP_DIR" -name "$DATABASE-*.sql" -mtime +7 -delete
