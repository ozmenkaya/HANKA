#!/usr/bin/env python3
"""
HANKA RAG System - Retrieval-Augmented Generation
Kod tabanını vektörize edip semantik arama yapar
"""

import os
import sys
from pathlib import Path
from typing import List, Dict, Optional
import json
from datetime import datetime

from openai import OpenAI
import chromadb
from chromadb.config import Settings

class HANKARAGSystem:
    def __init__(self, base_path: str = "./homedir/public_html"):
        """RAG sistemini başlat"""
        self.base_path = Path(base_path)
        self.client = OpenAI(api_key=os.getenv('OPENAI_API_KEY'))
        
        # ChromaDB client
        self.chroma_client = chromadb.PersistentClient(
            path="./chroma_db",
            settings=Settings(
                anonymized_telemetry=False,
                allow_reset=True
            )
        )
        
        # Collection oluştur/getir
        try:
            self.collection = self.chroma_client.get_collection(
                name="hanka_codebase"
            )
            print("✅ Existing collection loaded")
        except:
            self.collection = self.chroma_client.create_collection(
                name="hanka_codebase",
                metadata={
                    "description": "HANKA SYS SAAS kod tabanı",
                    "indexed_at": datetime.now().isoformat()
                }
            )
            print("✅ New collection created")
    
    def _should_index(self, path: Path) -> bool:
        """Dosyanın index'lenip index'lenmeyeceğini kontrol et"""
        exclude_patterns = [
            '.backup', '.bak', 'backup_', 
            'vendor/', 'node_modules/', 
            'tmp/', 'logs/', 'dosyalar/',
            '.git/', '.venv/', '__pycache__/',
            'test_', 'backup-', '.DS_Store'
        ]
        
        path_str = str(path)
        return not any(pattern in path_str for pattern in exclude_patterns)
    
    def _get_file_type(self, path: Path) -> str:
        """Dosya tipini belirle"""
        path_str = str(path)
        
        if '_db_islem.php' in path_str:
            return 'backend_api'
        elif '_modal.php' in path_str:
            return 'modal'
        elif '_ekle.php' in path_str or '_guncelle.php' in path_str:
            return 'form'
        elif 'include/agents/' in path_str:
            return 'agent'
        elif 'include/' in path_str:
            return 'core'
        elif path.suffix == '.php':
            return 'view'
        elif path.suffix == '.js':
            return 'javascript'
        elif path.suffix == '.css':
            return 'stylesheet'
        else:
            return 'other'
    
    def _extract_metadata(self, path: Path, content: str) -> Dict:
        """Dosyadan metadata çıkar"""
        metadata = {
            "file_path": str(path),
            "file_name": path.name,
            "file_type": self._get_file_type(path),
            "size_bytes": len(content),
            "extension": path.suffix,
            "indexed_at": datetime.now().isoformat()
        }
        
        # PHP dosyalarında özel kontroller
        if path.suffix == '.php':
            metadata["has_firma_id"] = "firma_id" in content
            metadata["uses_pdo"] = "$conn->prepare" in content
            metadata["has_transaction"] = "beginTransaction" in content
            metadata["has_session"] = "oturum_kontrol" in content or "$_SESSION" in content
            
            # Fonksiyon sayısı (basit sayım)
            metadata["function_count"] = content.count("function ")
            
            # MES pattern'leri
            metadata["has_mes_tracking"] = "uretim_islem_tarihler" in content
            metadata["has_quality_control"] = "uretim_eksik_uretilen" in content
        
        return metadata
    
    def index_file(self, file_path: Path) -> bool:
        """Tek bir dosyayı index'le"""
        try:
            # Dosyayı oku
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            
            # Çok büyük dosyaları parçala
            max_chunk_size = 8000  # Token limiti
            if len(content) > max_chunk_size:
                # Dosyayı parçalara böl
                chunks = [content[i:i+max_chunk_size] 
                         for i in range(0, len(content), max_chunk_size)]
                
                for idx, chunk in enumerate(chunks):
                    # Her parça için embedding oluştur
                    embedding = self.client.embeddings.create(
                        model="text-embedding-3-small",
                        input=chunk
                    ).data[0].embedding
                    
                    metadata = self._extract_metadata(file_path, chunk)
                    metadata["chunk_index"] = idx
                    metadata["total_chunks"] = len(chunks)
                    
                    # ChromaDB'ye kaydet
                    doc_id = f"{file_path}__chunk_{idx}"
                    self.collection.add(
                        embeddings=[embedding],
                        documents=[chunk],
                        metadatas=[metadata],
                        ids=[doc_id]
                    )
            else:
                # Tek seferde index'le
                embedding = self.client.embeddings.create(
                    model="text-embedding-3-small",
                    input=content
                ).data[0].embedding
                
                metadata = self._extract_metadata(file_path, content)
                
                self.collection.add(
                    embeddings=[embedding],
                    documents=[content],
                    metadatas=[metadata],
                    ids=[str(file_path)]
                )
            
            return True
            
        except Exception as e:
            print(f"❌ Error indexing {file_path}: {e}")
            return False
    
    def index_project(self, extensions: List[str] = ['.php', '.js']) -> Dict:
        """Tüm projeyi index'le"""
        print(f"🔍 Indexing project: {self.base_path}")
        print(f"📝 Extensions: {extensions}")
        
        stats = {
            "total_files": 0,
            "indexed_files": 0,
            "failed_files": 0,
            "skipped_files": 0,
            "by_type": {}
        }
        
        for ext in extensions:
            pattern = f"**/*{ext}"
            files = list(self.base_path.rglob(pattern))
            
            for file_path in files:
                stats["total_files"] += 1
                
                if not self._should_index(file_path):
                    stats["skipped_files"] += 1
                    continue
                
                print(f"📄 Indexing: {file_path.name}...", end=" ")
                
                if self.index_file(file_path):
                    stats["indexed_files"] += 1
                    file_type = self._get_file_type(file_path)
                    stats["by_type"][file_type] = stats["by_type"].get(file_type, 0) + 1
                    print("✅")
                else:
                    stats["failed_files"] += 1
                    print("❌")
        
        print(f"\n📊 Indexing complete!")
        print(f"   Total files: {stats['total_files']}")
        print(f"   Indexed: {stats['indexed_files']}")
        print(f"   Skipped: {stats['skipped_files']}")
        print(f"   Failed: {stats['failed_files']}")
        
        if stats["by_type"]:
            print(f"\n📁 By type:")
            for file_type, count in sorted(stats["by_type"].items()):
                print(f"   {file_type}: {count}")
        
        return stats
    
    def search_code(self, query: str, n_results: int = 5, 
                    file_type: Optional[str] = None) -> List[Dict]:
        """Kod içinde semantik arama"""
        print(f"🔍 Searching: '{query}'")
        
        # Query embedding
        query_embedding = self.client.embeddings.create(
            model="text-embedding-3-small",
            input=query
        ).data[0].embedding
        
        # Where filter
        where_filter = None
        if file_type:
            where_filter = {"file_type": file_type}
        
        # Arama yap
        results = self.collection.query(
            query_embeddings=[query_embedding],
            n_results=n_results,
            where=where_filter
        )
        
        # Sonuçları formatla
        formatted_results = []
        if results['documents']:
            for idx, doc in enumerate(results['documents'][0]):
                metadata = results['metadatas'][0][idx]
                distance = results['distances'][0][idx] if 'distances' in results else None
                
                formatted_results.append({
                    "file": metadata.get('file_path', 'unknown'),
                    "type": metadata.get('file_type', 'unknown'),
                    "snippet": doc[:200] + "..." if len(doc) > 200 else doc,
                    "relevance": 1 - distance if distance else 1.0,
                    "metadata": metadata
                })
        
        return formatted_results
    
    def get_stats(self) -> Dict:
        """Collection istatistikleri"""
        count = self.collection.count()
        
        # Metadata topla
        results = self.collection.get()
        
        stats = {
            "total_documents": count,
            "by_type": {},
            "by_extension": {}
        }
        
        if results['metadatas']:
            for metadata in results['metadatas']:
                file_type = metadata.get('file_type', 'unknown')
                stats["by_type"][file_type] = stats["by_type"].get(file_type, 0) + 1
                
                ext = metadata.get('extension', 'unknown')
                stats["by_extension"][ext] = stats["by_extension"].get(ext, 0) + 1
        
        return stats


def main():
    """CLI interface"""
    import argparse
    
    parser = argparse.ArgumentParser(description='HANKA RAG System')
    parser.add_argument('action', choices=['index', 'search', 'stats', 'reset'],
                       help='Action to perform')
    parser.add_argument('--query', '-q', help='Search query')
    parser.add_argument('--type', '-t', help='Filter by file type')
    parser.add_argument('--results', '-n', type=int, default=5, 
                       help='Number of results')
    
    args = parser.parse_args()
    
    # RAG system başlat
    rag = HANKARAGSystem()
    
    if args.action == 'index':
        stats = rag.index_project()
        print(f"\n💾 Saved to: ./chroma_db")
        
    elif args.action == 'search':
        if not args.query:
            print("❌ --query required for search")
            sys.exit(1)
        
        results = rag.search_code(args.query, args.results, args.type)
        
        print(f"\n📊 Found {len(results)} results:\n")
        for idx, result in enumerate(results, 1):
            print(f"{idx}. {result['file']}")
            print(f"   Type: {result['type']}")
            print(f"   Relevance: {result['relevance']:.2%}")
            print(f"   Snippet: {result['snippet'][:150]}...")
            print()
    
    elif args.action == 'stats':
        stats = rag.get_stats()
        print(f"\n📊 Collection Statistics:")
        print(f"   Total documents: {stats['total_documents']}")
        
        if stats['by_type']:
            print(f"\n   By type:")
            for file_type, count in sorted(stats['by_type'].items()):
                print(f"      {file_type}: {count}")
        
        if stats['by_extension']:
            print(f"\n   By extension:")
            for ext, count in sorted(stats['by_extension'].items()):
                print(f"      {ext}: {count}")
    
    elif args.action == 'reset':
        confirm = input("⚠️  Reset collection? This will delete all indexed data. (yes/no): ")
        if confirm.lower() == 'yes':
            rag.chroma_client.delete_collection("hanka_codebase")
            print("✅ Collection reset")
        else:
            print("❌ Cancelled")


if __name__ == "__main__":
    main()
