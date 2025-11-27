import os
import sys
import json
import time

try:
    from openai import OpenAI
except ImportError:
    print("❌ 'openai' kütüphanesi eksik.")
    print("Lütfen yükleyin: pip install openai")
    sys.exit(1)

# Configuration
# Path relative to this script
DATA_FILE = os.path.join(os.path.dirname(__file__), '../homedir/public_html/logs/hanka_finetune_dataset.jsonl')
BASE_MODEL = "gpt-4o-mini-2024-07-18" 

def main():
    print("🚀 HANKA AI - Fine-Tuning Başlatıcı")
    print("=" * 50)

    # 1. API Key Check
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        print("⚠️  OPENAI_API_KEY çevre değişkeni bulunamadı.")
        api_key = input("🔑 Lütfen OpenAI API Anahtarınızı girin (sk-...): ").strip()
        if not api_key:
            print("❌ API anahtarı gerekli.")
            sys.exit(1)
    
    client = OpenAI(api_key=api_key)

    # 2. File Check
    if not os.path.exists(DATA_FILE):
        print(f"❌ Veri dosyası bulunamadı: {DATA_FILE}")
        print("Önce 'php tools/prepare_finetune_data.php' çalıştırın.")
        sys.exit(1)

    print(f"📂 Dosya: {DATA_FILE}")
    
    # 3. Upload File
    print("\n1️⃣  Dosya OpenAI'a yükleniyor...")
    try:
        with open(DATA_FILE, "rb") as f:
            response = client.files.create(
                file=f,
                purpose="fine-tune"
            )
        file_id = response.id
        print(f"✅ Dosya yüklendi. ID: {file_id}")
    except Exception as e:
        print(f"❌ Dosya yükleme hatası: {e}")
        sys.exit(1)

    # 4. Start Job
    print(f"\n2️⃣  Fine-Tuning işlemi başlatılıyor (Model: {BASE_MODEL})...")
    try:
        job = client.fine_tuning.jobs.create(
            training_file=file_id,
            model=BASE_MODEL,
            suffix="hanka-sql-v3"
        )
        job_id = job.id
        print(f"✅ İşlem Başlatıldı! Job ID: {job_id}")
        print(f"📊 Durum: {job.status}")
    except Exception as e:
        print(f"❌ Job başlatma hatası: {e}")
        sys.exit(1)

    # 5. Monitor Instructions
    print("\n" + "=" * 50)
    print("🎉 TEBRİKLER! Fine-tuning işlemi sıraya alındı.")
    print(f"Takip etmek için Job ID: {job_id}")
    print("\nİzlemek için şu komutu kullanabilirsiniz:")
    print(f"python3 tools/monitor_finetune.py {job_id}")

if __name__ == "__main__":
    main()
