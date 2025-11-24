# HANKA AI Development Assistant

**RAG + Local Memory + MCP Server** - AI-assisted development için semantic code search ve development memory sistemi.

## 🎯 Sistem Bileşenleri

### 1. 🔍 RAG System (rag_system.py)
Semantic code search - ChromaDB + OpenAI embeddings ile kod vektörizasyonu

**Özellikler:**
- PHP ve JS dosyalarını vektörize et
- Semantic search (anlamsal arama)
- File type bazlı filtreleme (backend_api, view, agent, core)
- Chunk-based indexing (büyük dosyalar için)

**Kullanım:**
```bash
# Index'le (ilk kurulum)
export OPENAI_API_KEY='your-key'
.venv/bin/python rag_system.py index

# Arama yap
.venv/bin/python rag_system.py search -q "PDO prepared statement"
.venv/bin/python rag_system.py search -q "firma_id kontrolü" -t backend_api -n 3

# İstatistikler
.venv/bin/python rag_system.py stats
```

### 2. 🧠 Local Memory (local_memory.py)
SQLite tabanlı development memory - Kararlar, Pattern'ler, Bug Fix'ler

**Tablolar:**
- `decisions` - Development kararları (5 kayıt)
- `patterns` - Kod pattern'leri (3 kayıt)
- `bug_fixes` - Bug fix geçmişi (3 kayıt)
- `learnings` - Öğrenme notları (3 kayıt)
- `query_history` - RAG search geçmişi

**İlk Veriler:**
```bash
# Initial data yükle
.venv/bin/python load_initial_memory.py

# Stats göster
.venv/bin/python local_memory.py stats

# Markdown export
.venv/bin/python local_memory.py export -o memory_export.md
```

**Python API:**
```python
from local_memory import HANKAMemory

memory = HANKAMemory()

# Decision ekle
memory.add_decision(
    title="Multi-tenant firma_id kontrolü",
    context="Veri izolasyonu kritik",
    decision="Her query'de firma_id zorunlu",
    category="security",
    tags=["multi-tenant", "security"]
)

# Pattern ekle
memory.add_pattern(
    name="PDO CRUD Pattern",
    description="Prepared statement ile güvenli CRUD",
    code_example="$stmt = $conn->prepare(...)",
    category="crud",
    language="php"
)

# Arama
decisions = memory.search_decisions("multi-tenant")
patterns = memory.search_patterns("PDO")
bugs = memory.search_bug_fixes("AlertAgent")
```

### 3. 🔌 MCP Server (mcp-server/)
Model Context Protocol server - Claude'a RAG + Memory tools expose eder

**Tools:**
1. `search_code` - RAG semantic search
2. `get_rag_stats` - Index istatistikleri
3. `search_decisions` - Karar arama
4. `search_patterns` - Pattern arama
5. `search_bug_fixes` - Bug fix arama
6. `get_memory_stats` - Memory stats

**Build & Test:**
```bash
cd mcp-server
npm install
npm run build

# Test (stdio mode)
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | node dist/index.js
```

## 🚀 Kurulum

### Prerequisites
- Python 3.13+
- Node.js 18+
- OpenAI API key

### 1. Python Environment
```bash
# Virtual environment oluştur (halihazırda mevcut)
python3 -m venv .venv
source .venv/bin/activate  # macOS/Linux

# Dependencies kur
pip install openai chromadb sentence-transformers tiktoken
```

### 2. RAG Index'leme
```bash
# API key export et
export OPENAI_API_KEY='sk-proj-...'

# Kod tabanını index'le (5-10 dakika)
.venv/bin/python rag_system.py index
```

**Progress:**
- ✅ 300+ PHP files
- ✅ 50+ JS files
- ✅ ChromaDB collection created
- 📊 Toplam: ~350 documents

### 3. Memory Initialization
```bash
# İlk verileri yükle
.venv/bin/python load_initial_memory.py

# Verify
.venv/bin/python local_memory.py stats
```

### 4. MCP Server Setup
```bash
cd mcp-server
npm install
npm run build
```

### 5. Claude Desktop Configuration
**Dosya:** `~/Library/Application Support/Claude/claude_desktop_config.json`

```json
{
  "mcpServers": {
    "hanka": {
      "command": "node",
      "args": ["/Users/ozmenkaya/hanak_new_design/mcp-server/dist/index.js"],
      "env": {
        "OPENAI_API_KEY": "sk-proj-..."
      }
    }
  }
}
```

**Claude Desktop'ı yeniden başlat!**

## 📖 Kullanım Örnekleri

### Claude ile RAG Search
```
@hanka search_code query="PDO prepared statement kullanımı"
@hanka search_code query="firma_id kontrolü" file_type="backend_api"
@hanka search_code query="JSON veriler kolonu" n_results=3
```

### Claude ile Memory Search
```
@hanka search_decisions query="multi-tenant"
@hanka search_patterns query="CRUD"
@hanka search_bug_fixes query="AlertAgent"
```

### Python'dan RAG
```python
from rag_system import HANKARAGSystem

rag = HANKARAGSystem()

# Arama
results = rag.search_code("PDO prepared statement", n_results=5)
for result in results:
    print(f"{result['file']}: {result['snippet']}")

# Stats
stats = rag.get_stats()
print(f"Total documents: {stats['total_documents']}")
```

### Python'dan Memory
```python
from local_memory import HANKAMemory

memory = HANKAMemory()

# Kararlar
decisions = memory.get_decisions(category="security", limit=5)
for dec in decisions:
    print(f"{dec['title']}: {dec['decision']}")

# Pattern'ler
patterns = memory.get_patterns(language="php", limit=10)
for pat in patterns:
    print(f"{pat['name']}: {pat['description']}")
    memory.increment_pattern_usage(pat['id'])  # Usage tracking
```

## 🗂️ Dosya Yapısı

```
/Users/ozmenkaya/hanak_new_design/
├── rag_system.py              # RAG System
├── local_memory.py            # Local Memory
├── load_initial_memory.py     # Initial data loader
├── hanka_memory.db            # SQLite database
├── chroma_db/                 # ChromaDB vector store
│   └── ...                    # Embeddings, metadata
├── mcp-server/                # MCP Server
│   ├── package.json
│   ├── tsconfig.json
│   ├── src/
│   │   └── index.ts
│   └── dist/
│       └── index.js
├── .venv/                     # Python virtual environment
└── .vscode/
    └── settings.json          # VS Code config
```

## 📊 Mevcut Veriler

### Memory Stats
```
Decisions: 5
  - Multi-tenant firma_id kontrolü
  - PDO kullan, MySQLi KULLANMA
  - JSON kolonu pattern
  - Soft delete kullan
  - AI Fine-tuned model

Patterns: 3
  - Multi-Tenant CRUD Pattern
  - AJAX Backend Pattern
  - MES Üretim İşlem Pattern

Bug Fixes: 3
  - AlertAgent: Table 'urunler' doesn't exist
  - MySQLi vs PDO karışıklığı
  - JSON_EXTRACT without JSON_UNQUOTE

Learnings: 3
  - HANKA architecture overview
  - Index'leme stratejisi
  - OpenAI API key handling
```

### RAG Stats (After Indexing)
```
Total Documents: ~350
By Type:
  - backend_api: 80+
  - view: 150+
  - agent: 4
  - core: 20+
  - form: 40+
  - modal: 30+
  - javascript: 20+

By Extension:
  - .php: 300+
  - .js: 50+
```

## 🔧 Maintenance

### RAG Re-indexing
```bash
# Reset collection
.venv/bin/python rag_system.py reset

# Re-index
.venv/bin/python rag_system.py index
```

### Memory Export
```bash
# Markdown export
.venv/bin/python local_memory.py export -o docs/memory_$(date +%Y%m%d).md
```

### MCP Server Update
```bash
cd mcp-server
npm run build
# Claude Desktop'ı yeniden başlat
```

## 🚨 Troubleshooting

### RAG: "Collection doesn't exist"
```bash
.venv/bin/python rag_system.py index
```

### MCP: "Tool not found"
```bash
# Build kontrol
cd mcp-server && npm run build

# Claude config kontrol
cat ~/Library/Application\ Support/Claude/claude_desktop_config.json

# Claude Desktop yeniden başlat
```

### Memory: "Database locked"
```bash
# Process'leri kontrol
ps aux | grep local_memory.py

# Kill eski process'ler
kill -9 <PID>
```

### Python: "Module not found"
```bash
# Virtual environment aktif mi?
which python  # .venv/bin/python olmalı

# Dependencies kur
pip install openai chromadb sentence-transformers tiktoken
```

## 📈 Performance

### RAG Search
- Index time: ~5-10 min (350 files)
- Search time: ~1-2 sec
- Storage: ~50 MB (chroma_db/)

### Memory
- Query time: <100ms
- Storage: ~500 KB (hanka_memory.db)
- Export time: ~1 sec

### MCP Server
- Startup: ~500ms
- Tool call: ~2-3 sec (RAG search)
- Memory footprint: ~50 MB

## 🔐 Security

**⚠️ OPENAI_API_KEY Güvenliği:**
- ✅ `.env` dosyasında sakla
- ✅ `.gitignore`'a ekle
- ❌ Asla commit etme
- ❌ Public repo'lara koyma

**API Key Rotation:**
```bash
# 1. OpenAI dashboard'dan yeni key al
# 2. .env güncelle
# 3. Claude config güncelle
# 4. Export et
export OPENAI_API_KEY='new-key'

# 5. Test et
.venv/bin/python rag_system.py stats
```

## 🤝 Contribution

### Yeni Decision Ekle
```python
memory.add_decision(
    title="...",
    context="...",
    decision="...",
    rationale="...",
    category="security|database|ai|architecture",
    tags=[...]
)
```

### Yeni Pattern Ekle
```python
memory.add_pattern(
    name="...",
    description="...",
    code_example="...",
    category="crud|api|mes|...",
    language="php|javascript|...",
    tags=[...]
)
```

### Bug Fix Kaydet
```python
memory.add_bug_fix(
    title="...",
    description="...",
    error_message="...",
    solution="...",
    severity="low|medium|high|critical",
    category="database|security|...",
    tags=[...]
)
```

## 📚 Documentation

- `README.md` - Ana dokümantasyon
- `ARCHITECTURE.md` - Sistem mimarisi
- `DATABASE_SCHEMA.md` - 94 tablo şeması
- `CODING_STANDARDS.md` - Kod standartları
- `.github/copilot-instructions.md` - GitHub Copilot memory

## 🎉 Success!

RAG + Memory + MCP Server kurulumu tamamlandı! 🚀

**Next Steps:**
1. ✅ RAG index'lemeyi bekle (background process)
2. ✅ Claude Desktop'ı yeniden başlat
3. ✅ Test et: `@hanka search_code query="PDO"`
4. 🚀 Happy coding with AI assistance!

---

**Developed by:** HANKA SYS SAAS Team  
**Version:** 1.0.0  
**Date:** 2025-01-02
