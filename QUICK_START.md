# 🚀 HANKA AI Assistant - Quick Start Guide

## 🎯 3 Kullanım Yöntemi (Hepsi Aktif!)

### 1️⃣ **GitHub Copilot** (Otomatik - En Kolay)
✅ Zaten aktif! `.github/copilot-instructions.md` dosyanızı okuyor.

**Kullanım:**
- Kod yazarken Copilot otomatik öneri verir
- Multi-tenant kurallarına uygun kod önerir
- PDO prepared statement kullanır
- MES pattern'lerini bilir

**Örnek:**
```php
// "firma kontrolü ile sipariş listesi" yazın, Copilot tamamlar:
$stmt = $conn->prepare("SELECT * FROM siparisler WHERE firma_id = :firma_id");
$stmt->execute([':firma_id' => $_SESSION['firma_id']]);
```

---

### 2️⃣ **VS Code Tasks** (Hızlı - Cmd+Shift+P)
VS Code'da `Cmd + Shift + P` → "Tasks: Run Task" → HANKA seçin

**Mevcut Task'lar:**
- 🔍 **HANKA: RAG Search** - Kod içinde arama (query sorar)
- 📊 **HANKA: RAG Stats** - 2,130 document istatistikleri
- 🧠 **HANKA: Memory Stats** - Decisions, patterns, bug fixes
- 📝 **HANKA: Search Patterns** - Tüm kod pattern'lerini listele
- 🔌 **HANKA: MCP Server Start** - MCP server başlat (background)

**Örnek Kullanım:**
1. `Cmd + Shift + P`
2. "Tasks: Run Task" yaz
3. "HANKA: RAG Search" seç
4. Query gir: "PDO prepared statement"
5. Sonuçlar terminal'de görünür

---

### 3️⃣ **Terminal Aliases** (En Güçlü - Power User)

```bash
# Alias'ları aktif et (her yeni terminal'de)
source .hanka_aliases.sh

# Hızlı komutlar:
hanka-search "PDO prepared statement"
hanka-search "firma_id kontrolü" -t backend_api
hanka-pdo           # PDO örnekleri
hanka-multi         # Multi-tenant patterns
hanka-mes           # MES patterns
hanka-stats         # RAG istatistikleri
hanka-patterns      # Tüm pattern'ler
hanka-decisions     # Tüm kararlar
```

**Kalıcı yapmak için** (opsiyonel):
```bash
echo "source /Users/ozmenkaya/hanak_new_design/.hanka_aliases.sh" >> ~/.zshrc
```

---

## 📊 Sistemdeki Veriler

### RAG Index (2,130 documents)
- 📜 JavaScript: 1,495 dosya
- 🔧 Backend API: 85 dosya
- 👁️ View: 359 dosya
- ⚙️ Core: 72 dosya
- 📝 Form: 73 dosya
- 🪟 Modal: 38 dosya
- 🤖 Agent: 8 dosya

### Local Memory
- ✅ 5 Development Decisions
- ✅ 3 Code Patterns
- ✅ 3 Bug Fixes
- ✅ 3 Learnings

---

## 🎓 Kullanım Örnekleri

### Senaryo 1: Yeni CRUD endpoint yazıyorsunuz
```
1. GitHub Copilot: Otomatik firma_id kontrolü önerir
2. VS Code Task: "HANKA: Search Patterns" → CRUD pattern'i görürsünüz
3. Terminal: hanka-search "multi-tenant CRUD" → Örnekler bulursunuz
```

### Senaryo 2: MES üretim işlemi ekliyorsunuz
```
1. Terminal: hanka-mes → MES pattern'leri görürsünüz
2. VS Code Task: "HANKA: RAG Search" → "uretim_islem_tarihler" ararsiniz
3. GitHub Copilot: MES kurallarına uygun kod önerir
```

### Senaryo 3: Bug fix yapıyorsunuz
```
1. Terminal: hanka-search "AlertAgent error" → Benzer bug'lar
2. VS Code Task: "HANKA: Memory Stats" → Geçmiş fix'leri görürsünüz
3. GitHub Copilot: Fix pattern'ini önerir
```

---

## ⚡ Performance Tips

### RAG Search
- ✅ Type filter kullanın: `-t backend_api` (daha hızlı)
- ✅ Result limit: `-n 3` (gereksiz sonuçları keser)
- ✅ Spesifik query: "PDO firma_id" > "database"

### Memory
- ✅ Category filtre: `memory.get_patterns(category="crud")`
- ✅ Usage tracking: Pattern kullandıkça `increment_pattern_usage()`
- ✅ Export: Haftalık `hanka-export` yapın

### GitHub Copilot
- ✅ `.github/copilot-instructions.md` güncel tutun
- ✅ Yeni pattern bulunca memory'ye ekleyin
- ✅ Yeni decision'larda memory'yi güncelleyin

---

## 🛠️ Maintenance

### RAG Re-index (Kod değişikliği sonrası)
```bash
export OPENAI_API_KEY='...'
.venv/bin/python rag_system.py reset  # Eski index'i sil
.venv/bin/python rag_system.py index  # Yeniden index'le
```

### Memory Güncelleme
```python
from local_memory import HANKAMemory
memory = HANKAMemory()

# Yeni pattern ekle
memory.add_pattern(
    name="API Rate Limiting Pattern",
    description="Rate limiting for API endpoints",
    code_example="// kod örneği",
    category="security",
    language="php"
)

memory.close()
```

### Memory Export (Backup)
```bash
hanka-export -o memory_backup_$(date +%Y%m%d).md
```

---

## 🔥 Quick Reference

| Ne Yapacaksınız? | Nasıl? |
|------------------|--------|
| Kod örneği bul | `hanka-search "your query"` |
| Pattern listele | `hanka-patterns` |
| Stats gör | `hanka-stats` |
| Copilot önerisi | Sadece kod yazmaya başlayın |
| VS Code'dan ara | `Cmd+Shift+P` → HANKA Task |
| Memory export | `hanka-export` |

---

## 📝 Hatırlatmalar

✅ **GitHub Copilot** her zaman aktif  
✅ **RAG Search** terminal veya VS Code task'tan  
✅ **Memory** Python script veya alias'larla  
✅ **MCP Server** (opsiyonel) MCP destekli IDE'ler için  

**En verimli:** Her 3 yöntemi birlikte kullanın!

---

**🎉 Artık AI-powered development yapabilirsiniz!**
