#!/usr/bin/env python3
"""
HANKA AI - Training Data Analyzer (Simplified)
Sunucuda çalışacak basitleştirilmiş versiyon
"""

import json
import sys

def analyze_training_data(filepath):
    """Training data dosyasını analiz et"""
    
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            records = [json.loads(line.strip()) for line in f if line.strip()]
    except FileNotFoundError:
        print(f"❌ Dosya bulunamadı: {filepath}")
        return
    except Exception as e:
        print(f"❌ Hata: {e}")
        return
    
    total = len(records)
    
    print(f"\n📊 Training Data Analizi")
    print(f"{'=' * 50}")
    print(f"Toplam kayıt: {total}")
    
    if total < 50:
        print(f"⚠️  Yetersiz: {50 - total} kayıt daha gerekli")
    elif total < 100:
        print(f"✅ Kabul edilebilir: {total} kayıt")
        print(f"💡 İdeal: {100 - total} kayıt daha eklenebilir")
    else:
        print(f"🎉 Mükemmel: {total} kayıt - Fine-tuning hazır!")
    
    # Kaynak dağılımı
    sources = {}
    for record in records:
        source = record.get('metadata', {}).get('source', 'unknown')
        sources[source] = sources.get(source, 0) + 1
    
    if sources:
        print(f"\n📈 Kaynak Dağılımı:")
        for source, count in sorted(sources.items(), key=lambda x: -x[1]):
            print(f"   {source}: {count} kayıt")
    
    print(f"{'=' * 50}\n")

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Kullanım: python3 analyze_training.py <training_data.jsonl>")
        sys.exit(1)
    
    analyze_training_data(sys.argv[1])
