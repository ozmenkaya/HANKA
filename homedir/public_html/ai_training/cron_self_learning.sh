#!/bin/bash
# HANKA AI - Daily Self Learning Cron Job
# Her gün otomatik training data toplar

cd /var/www/html

# Self learning scriptini çalıştır
php ai_self_learning.php run 16 >> /var/log/hanka_ai_learning.log 2>&1

# Training data'yı analiz et
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" >> /var/log/hanka_ai_learning.log
python3 /var/www/html/ai_training/analyze_training.py /var/www/html/ai_training/training_corrections.jsonl >> /var/log/hanka_ai_learning.log 2>&1

# 50+ kayıt varsa bildirim gönder
RECORD_COUNT=$(wc -l < /var/www/html/ai_training/training_corrections.jsonl)
if [ $RECORD_COUNT -ge 50 ]; then
    echo "🎉 Training data yeterli seviyeye ulaştı: $RECORD_COUNT kayıt" >> /var/log/hanka_ai_learning.log
    echo "🚀 Fine-tuning için hazır!" >> /var/log/hanka_ai_learning.log
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" >> /var/log/hanka_ai_learning.log
echo "" >> /var/log/hanka_ai_learning.log
