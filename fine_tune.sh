#!/bin/bash
# HANKA AI - Fine-Tuning Data Toplama ve Model Eğitimi

echo "🎓 HANKA AI - FINE-TUNING PROCESS"
echo "=================================="
echo ""

# Renkler
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Training data dosyasını kontrol et
echo "📊 1. Training Data Kontrolü..."
echo "--------------------------------"

DATA_FILE="/var/www/html/logs/training_data.jsonl"

# Sunucudan satır sayısını al
LINE_COUNT=$(ssh root@91.99.186.98 "wc -l < $DATA_FILE 2>/dev/null || echo 0")

echo "Toplam kayıt: $LINE_COUNT satır"

if [ "$LINE_COUNT" -lt 50 ]; then
    echo -e "${RED}❌ YETERSİZ DATA!${NC}"
    echo "   Minimum: 50 örnek"
    echo "   Mevcut: $LINE_COUNT örnek"
    echo "   Eksik: $((50 - LINE_COUNT)) örnek"
    echo ""
    echo "💡 Öneriler:"
    echo "   1. Sistemi 1-2 hafta daha kullanın"
    echo "   2. Farklı türde sorular sorun"
    echo "   3. Başarılı sorguları manuel ekleyin"
    exit 1
fi

if [ "$LINE_COUNT" -lt 100 ]; then
    echo -e "${YELLOW}⚠️  KABUL EDİLEBİLİR${NC} (idealinde 100+ olmalı)"
else
    echo -e "${GREEN}✅ YETERLİ DATA${NC}"
fi

echo ""

# 2. Data kalitesini kontrol et
echo "🔍 2. Data Kalite Kontrolü..."
echo "--------------------------------"

# Son 5 kaydı göster
echo "Son 5 kayıt:"
ssh root@91.99.186.98 "tail -5 $DATA_FILE" | jq -r '.messages[1].content | .[0:100]' 2>/dev/null || \
    ssh root@91.99.186.98 "tail -5 $DATA_FILE | cut -c1-100"

echo ""

# 3. Dosyayı locale indir
echo "📥 3. Data Dosyasını İndirme..."
echo "--------------------------------"

LOCAL_FILE="$HOME/Downloads/hanka_training_data_$(date +%Y%m%d_%H%M%S).jsonl"
scp root@91.99.186.98:$DATA_FILE "$LOCAL_FILE"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ İndirildi: $LOCAL_FILE${NC}"
else
    echo -e "${RED}❌ İndirme başarısız!${NC}"
    exit 1
fi

echo ""

# 4. OpenAI CLI kontrolü
echo "🔧 4. OpenAI CLI Kontrolü..."
echo "--------------------------------"

if ! command -v openai &> /dev/null; then
    echo -e "${YELLOW}⚠️  OpenAI CLI bulunamadı${NC}"
    echo "Yükleniyor..."
    pip install openai
fi

openai --version
echo ""

# 5. API Key kontrolü
echo "🔑 5. API Key Kontrolü..."
echo "--------------------------------"

if [ -z "$OPENAI_API_KEY" ]; then
    echo -e "${RED}❌ OPENAI_API_KEY bulunamadı!${NC}"
    echo ""
    echo "API key'i ayarlamak için:"
    echo "  export OPENAI_API_KEY='sk-proj-...'"
    echo ""
    echo "Veya .env dosyasından yükleyin:"
    echo "  source .env"
    exit 1
fi

echo -e "${GREEN}✅ API Key bulundu: ${OPENAI_API_KEY:0:20}...${NC}"
echo ""

# 6. Fine-tuning başlat
echo "🚀 6. Fine-Tuning Başlatma..."
echo "--------------------------------"

read -p "Fine-tuning başlatılsın mı? (y/N): " confirm

if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "İptal edildi."
    exit 0
fi

echo ""
echo "Fine-tuning başlatılıyor..."
echo "Model: gpt-4o-mini"
echo "Suffix: hanka-sql-v1"
echo ""

# Fine-tuning başlat
FINETUNE_OUTPUT=$(openai api fine_tunes.create \
  -t "$LOCAL_FILE" \
  -m gpt-4o-mini \
  --suffix "hanka-sql-v1" 2>&1)

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Fine-tuning başlatıldı!${NC}"
    echo "$FINETUNE_OUTPUT"
    
    # Job ID'yi çıkar
    JOB_ID=$(echo "$FINETUNE_OUTPUT" | grep -oE 'ft-[a-zA-Z0-9]+' | head -1)
    
    if [ -n "$JOB_ID" ]; then
        echo ""
        echo "📝 Job ID: $JOB_ID"
        echo ""
        echo "İzlemek için:"
        echo "  openai api fine_tunes.follow -i $JOB_ID"
        echo ""
        echo "Job ID'yi kaydedin!"
        echo "$JOB_ID" > ~/hanka_finetune_job_id.txt
        echo "Kaydedildi: ~/hanka_finetune_job_id.txt"
    fi
else
    echo -e "${RED}❌ Fine-tuning başlatılamadı!${NC}"
    echo "$FINETUNE_OUTPUT"
    exit 1
fi

echo ""
echo "🎓 Fine-Tuning Süreci"
echo "--------------------------------"
echo "Durum: Başlatıldı"
echo "Süre: 10-60 dakika (data boyutuna bağlı)"
echo "Maliyet: ~\$0.008/1K token (~\$8-12 toplam)"
echo ""
echo "📊 İzleme:"
echo "  openai api fine_tunes.follow -i $JOB_ID"
echo ""
echo "✅ Tamamlandıktan sonra:"
echo "  1. Model ID'yi alın"
echo "  2. .env dosyasına ekleyin:"
echo "     OPENAI_FINETUNED_MODEL=ft:gpt-4o-mini-xxx:hanka-sql-v1"
echo "  3. Sunucuda .env'yi güncelleyin"
echo ""
echo "🎯 Beklenen İyileştirmeler:"
echo "  - Hata oranı: %60-80 azalma"
echo "  - Yanıt kalitesi: 3-5x artış"
echo "  - SQL doğruluğu: %90+ başarı"
echo ""

echo "✅ İşlem tamamlandı!"
