#!/bin/bash

# HANKA AI - Fine-Tuning Script (Yeni OpenAI API)
# Bu script training data'yı upload edip fine-tuning başlatır

set -e

# Renkli output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "🎓 HANKA AI - FINE-TUNING PROCESS (New API)"
echo "================================================"
echo ""

# Değişkenler
SERVER="root@91.99.186.98"
REMOTE_DATA="/var/www/html/logs/training_data.jsonl"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
TRAINING_FILE="$HOME/Downloads/hanka_training_data_$TIMESTAMP.jsonl"
MODEL="gpt-4o-mini-2024-07-18"
SUFFIX="hanka-sql-v1"
JOB_LOG="$HOME/hanka_finetune_job_log.json"
JOB_ID_FILE="$HOME/hanka_finetune_job_id.txt"

# 1. Training data kontrolü
echo -e "${BLUE}📊 1. Training Data Kontrolü...${NC}"
echo "--------------------------------"
SSH_COUNT=$(ssh $SERVER "wc -l < $REMOTE_DATA")
echo "Toplam kayıt: $SSH_COUNT satır"

if [ "$SSH_COUNT" -lt 50 ]; then
    echo -e "${RED}❌ YETERSİZ DATA!${NC}"
    echo "En az 50 kayıt gerekli. Şu an: $SSH_COUNT"
    exit 1
fi

if [ "$SSH_COUNT" -lt 100 ]; then
    echo -e "${YELLOW}⚠️  KABUL EDİLEBİLİR (idealinde 100+)${NC}"
else
    echo -e "${GREEN}✅ YETERLİ DATA${NC}"
fi
echo ""

# 2. Data kalitesi
echo -e "${BLUE}🔍 2. Data Kalite Kontrolü...${NC}"
echo "--------------------------------"
echo "Son 5 kayıt:"
ssh $SERVER "tail -5 $REMOTE_DATA | python3 -c \"import sys, json; [print(json.loads(line)['messages'][1]['content']) for line in sys.stdin]\""
echo ""

# 3. Data dosyasını indir
echo -e "${BLUE}📥 3. Data Dosyasını İndirme...${NC}"
echo "--------------------------------"
scp "$SERVER:$REMOTE_DATA" "$TRAINING_FILE"
echo -e "${GREEN}✅ İndirildi: $TRAINING_FILE${NC}"
echo ""

# 4. API Key kontrolü
echo -e "${BLUE}🔑 4. API Key Kontrolü...${NC}"
echo "--------------------------------"

if [ -z "$OPENAI_API_KEY" ]; then
    echo -e "${RED}❌ OPENAI_API_KEY bulunamadı!${NC}"
    echo ""
    echo "API key'i ayarlamak için:"
    echo "  export OPENAI_API_KEY='sk-proj-...'"
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ API Key bulundu: ${OPENAI_API_KEY:0:20}...${NC}"
echo ""

# 5. Training dosyasını upload et
echo -e "${BLUE}📤 5. Training Dosyasını Upload Et...${NC}"
echo "--------------------------------"
echo "Dosya upload ediliyor..."

UPLOAD_RESPONSE=$(curl -s https://api.openai.com/v1/files \
  -H "Authorization: Bearer $OPENAI_API_KEY" \
  -F purpose="fine-tune" \
  -F file="@$TRAINING_FILE")

FILE_ID=$(echo "$UPLOAD_RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin).get('id', ''))" 2>/dev/null || echo "")

if [[ -z "$FILE_ID" ]]; then
    echo -e "${RED}❌ Dosya upload edilemedi!${NC}"
    echo "Response:"
    echo "$UPLOAD_RESPONSE" | python3 -m json.tool
    exit 1
fi

echo -e "${GREEN}✅ Dosya upload edildi: $FILE_ID${NC}"
echo ""

# Dosya hazır olana kadar bekle
echo "Dosya işleniyor (processing)..."
for i in {1..30}; do
    FILE_STATUS=$(curl -s https://api.openai.com/v1/files/$FILE_ID \
      -H "Authorization: Bearer $OPENAI_API_KEY" | \
      python3 -c "import sys, json; print(json.load(sys.stdin).get('status', ''))" 2>/dev/null || echo "")
    
    if [[ "$FILE_STATUS" == "processed" ]]; then
        echo -e "${GREEN}✅ Dosya hazır!${NC}"
        break
    fi
    
    echo "  Status: $FILE_STATUS ($i/30)"
    sleep 2
done
echo ""

# 6. Fine-tuning başlatma onayı
echo -e "${BLUE}🚀 6. Fine-Tuning Başlatma...${NC}"
echo "--------------------------------"
echo -e "Fine-tuning başlatılsın mı? (y/N): "

read -r answer
if [[ "$answer" != "y" && "$answer" != "Y" ]]; then
    echo "İptal edildi."
    echo "Dosya ID: $FILE_ID"
    echo "Silmek için: curl -X DELETE https://api.openai.com/v1/files/$FILE_ID -H 'Authorization: Bearer \$OPENAI_API_KEY'"
    exit 0
fi

echo ""
echo "Fine-tuning başlatılıyor..."
echo "Model: $MODEL"
echo "Suffix: $SUFFIX"
echo "File ID: $FILE_ID"
echo ""

# Fine-tuning job başlat
JOB_RESPONSE=$(curl -s https://api.openai.com/v1/fine_tuning/jobs \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $OPENAI_API_KEY" \
  -d "{
    \"training_file\": \"$FILE_ID\",
    \"model\": \"$MODEL\",
    \"suffix\": \"$SUFFIX\",
    \"hyperparameters\": {
      \"n_epochs\": \"auto\"
    }
  }")

# Response'u kaydet
echo "$JOB_RESPONSE" | python3 -m json.tool > "$JOB_LOG"

# Job ID'yi çıkar
JOB_ID=$(echo "$JOB_RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin).get('id', ''))" 2>/dev/null || echo "")

if [[ -z "$JOB_ID" ]]; then
    echo -e "${RED}❌ Fine-tuning başlatılamadı!${NC}"
    echo "Response:"
    cat "$JOB_LOG"
    exit 1
fi

# Job ID'yi kaydet
echo "$JOB_ID" > "$JOB_ID_FILE"

echo -e "${GREEN}✅ FINE-TUNING BAŞLATILDI!${NC}"
echo "================================"
echo ""
echo "Job ID: $JOB_ID"
echo "Log dosyası: $JOB_LOG"
echo ""
echo -e "${YELLOW}📋 Job durumunu takip etmek için:${NC}"
echo "  curl https://api.openai.com/v1/fine_tuning/jobs/$JOB_ID \\"
echo "    -H 'Authorization: Bearer \$OPENAI_API_KEY' | python3 -m json.tool"
echo ""
echo "Veya Python monitör kullan:"
echo "  python3 monitor_finetune.py $JOB_ID"
echo ""

# Monitor'u otomatik başlat
echo -e "${BLUE}🔍 7. Monitoring Başlatılıyor...${NC}"
echo "--------------------------------"
echo "Job'ı izlemek için Ctrl+C ile çıkabilirsiniz."
echo ""
sleep 3

# Python monitor varsa çalıştır
if [ -f "monitor_finetune.py" ]; then
    python3 monitor_finetune.py "$JOB_ID"
else
    echo -e "${YELLOW}⚠️  monitor_finetune.py bulunamadı.${NC}"
    echo "Manuel takip için yukarıdaki komutları kullanın."
fi

echo ""
echo -e "${GREEN}✅ İşlem tamamlandı!${NC}"
