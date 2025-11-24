#!/usr/bin/env python3
"""
HANKA Local Memory System
SQLite tabanlı geliştirme hafızası - Kararlar, Pattern'ler, Bug Fix'ler
"""

import os
import sqlite3
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional
import json


class HANKAMemory:
    def __init__(self, db_path: str = "./hanka_memory.db"):
        """Local memory sistemini başlat"""
        self.db_path = db_path
        self.conn = sqlite3.connect(db_path)
        self.conn.row_factory = sqlite3.Row
        self._init_database()
    
    def _init_database(self):
        """Tabloları oluştur"""
        cursor = self.conn.cursor()
        
        # Development Decisions
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS decisions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                context TEXT NOT NULL,
                decision TEXT NOT NULL,
                rationale TEXT,
                alternatives TEXT,
                category TEXT,
                tags TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT
            )
        """)
        
        # Code Patterns
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS patterns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT NOT NULL,
                code_example TEXT NOT NULL,
                use_cases TEXT,
                file_references TEXT,
                category TEXT,
                language TEXT,
                tags TEXT,
                created_at TEXT NOT NULL,
                usage_count INTEGER DEFAULT 0
            )
        """)
        
        # Bug Fixes
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS bug_fixes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                error_message TEXT,
                solution TEXT NOT NULL,
                files_changed TEXT,
                commit_hash TEXT,
                severity TEXT,
                category TEXT,
                tags TEXT,
                created_at TEXT NOT NULL,
                resolved_at TEXT
            )
        """)
        
        # Learning Notes
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS learnings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                context TEXT,
                category TEXT,
                importance INTEGER DEFAULT 3,
                tags TEXT,
                created_at TEXT NOT NULL
            )
        """)
        
        # Query History (RAG search history)
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS query_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query TEXT NOT NULL,
                context TEXT,
                results_count INTEGER,
                useful BOOLEAN,
                created_at TEXT NOT NULL
            )
        """)
        
        self.conn.commit()
        print(f"✅ Memory database initialized: {self.db_path}")
    
    # ==================== DECISIONS ====================
    
    def add_decision(self, title: str, context: str, decision: str,
                    rationale: str = "", alternatives: str = "",
                    category: str = "", tags: List[str] = None) -> int:
        """Geliştirme kararı kaydet"""
        cursor = self.conn.cursor()
        
        tags_str = json.dumps(tags) if tags else "[]"
        
        cursor.execute("""
            INSERT INTO decisions 
            (title, context, decision, rationale, alternatives, category, tags, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        """, (title, context, decision, rationale, alternatives, category, 
              tags_str, datetime.now().isoformat()))
        
        self.conn.commit()
        print(f"✅ Decision saved: {title}")
        return cursor.lastrowid
    
    def get_decisions(self, category: str = None, limit: int = 10) -> List[Dict]:
        """Kararları getir"""
        cursor = self.conn.cursor()
        
        if category:
            cursor.execute("""
                SELECT * FROM decisions 
                WHERE category = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            """, (category, limit))
        else:
            cursor.execute("""
                SELECT * FROM decisions 
                ORDER BY created_at DESC 
                LIMIT ?
            """, (limit,))
        
        return [dict(row) for row in cursor.fetchall()]
    
    def search_decisions(self, query: str) -> List[Dict]:
        """Kararlarda arama yap"""
        cursor = self.conn.cursor()
        
        cursor.execute("""
            SELECT * FROM decisions 
            WHERE title LIKE ? OR context LIKE ? OR decision LIKE ?
            ORDER BY created_at DESC
        """, (f"%{query}%", f"%{query}%", f"%{query}%"))
        
        return [dict(row) for row in cursor.fetchall()]
    
    # ==================== PATTERNS ====================
    
    def add_pattern(self, name: str, description: str, code_example: str,
                   use_cases: str = "", file_references: str = "",
                   category: str = "", language: str = "php",
                   tags: List[str] = None) -> int:
        """Kod pattern'i kaydet"""
        cursor = self.conn.cursor()
        
        tags_str = json.dumps(tags) if tags else "[]"
        
        cursor.execute("""
            INSERT INTO patterns 
            (name, description, code_example, use_cases, file_references, 
             category, language, tags, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (name, description, code_example, use_cases, file_references,
              category, language, tags_str, datetime.now().isoformat()))
        
        self.conn.commit()
        print(f"✅ Pattern saved: {name}")
        return cursor.lastrowid
    
    def get_patterns(self, category: str = None, language: str = None, 
                    limit: int = 10) -> List[Dict]:
        """Pattern'leri getir"""
        cursor = self.conn.cursor()
        
        query = "SELECT * FROM patterns WHERE 1=1"
        params = []
        
        if category:
            query += " AND category = ?"
            params.append(category)
        
        if language:
            query += " AND language = ?"
            params.append(language)
        
        query += " ORDER BY usage_count DESC, created_at DESC LIMIT ?"
        params.append(limit)
        
        cursor.execute(query, params)
        return [dict(row) for row in cursor.fetchall()]
    
    def search_patterns(self, query: str) -> List[Dict]:
        """Pattern'lerde arama yap"""
        cursor = self.conn.cursor()
        
        cursor.execute("""
            SELECT * FROM patterns 
            WHERE name LIKE ? OR description LIKE ? OR code_example LIKE ?
            ORDER BY usage_count DESC, created_at DESC
        """, (f"%{query}%", f"%{query}%", f"%{query}%"))
        
        return [dict(row) for row in cursor.fetchall()]
    
    def increment_pattern_usage(self, pattern_id: int):
        """Pattern kullanım sayısını artır"""
        cursor = self.conn.cursor()
        cursor.execute("""
            UPDATE patterns SET usage_count = usage_count + 1 
            WHERE id = ?
        """, (pattern_id,))
        self.conn.commit()
    
    # ==================== BUG FIXES ====================
    
    def add_bug_fix(self, title: str, description: str, solution: str,
                   error_message: str = "", files_changed: str = "",
                   commit_hash: str = "", severity: str = "medium",
                   category: str = "", tags: List[str] = None) -> int:
        """Bug fix kaydet"""
        cursor = self.conn.cursor()
        
        tags_str = json.dumps(tags) if tags else "[]"
        
        cursor.execute("""
            INSERT INTO bug_fixes 
            (title, description, error_message, solution, files_changed, 
             commit_hash, severity, category, tags, created_at, resolved_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (title, description, error_message, solution, files_changed,
              commit_hash, severity, category, tags_str, 
              datetime.now().isoformat(), datetime.now().isoformat()))
        
        self.conn.commit()
        print(f"✅ Bug fix saved: {title}")
        return cursor.lastrowid
    
    def get_bug_fixes(self, category: str = None, severity: str = None,
                     limit: int = 10) -> List[Dict]:
        """Bug fix'leri getir"""
        cursor = self.conn.cursor()
        
        query = "SELECT * FROM bug_fixes WHERE 1=1"
        params = []
        
        if category:
            query += " AND category = ?"
            params.append(category)
        
        if severity:
            query += " AND severity = ?"
            params.append(severity)
        
        query += " ORDER BY resolved_at DESC LIMIT ?"
        params.append(limit)
        
        cursor.execute(query, params)
        return [dict(row) for row in cursor.fetchall()]
    
    def search_bug_fixes(self, query: str) -> List[Dict]:
        """Bug fix'lerde arama yap"""
        cursor = self.conn.cursor()
        
        cursor.execute("""
            SELECT * FROM bug_fixes 
            WHERE title LIKE ? OR description LIKE ? 
               OR error_message LIKE ? OR solution LIKE ?
            ORDER BY resolved_at DESC
        """, (f"%{query}%", f"%{query}%", f"%{query}%", f"%{query}%"))
        
        return [dict(row) for row in cursor.fetchall()]
    
    # ==================== LEARNINGS ====================
    
    def add_learning(self, title: str, content: str, context: str = "",
                    category: str = "", importance: int = 3,
                    tags: List[str] = None) -> int:
        """Öğrenme notu kaydet"""
        cursor = self.conn.cursor()
        
        tags_str = json.dumps(tags) if tags else "[]"
        
        cursor.execute("""
            INSERT INTO learnings 
            (title, content, context, category, importance, tags, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        """, (title, content, context, category, importance, 
              tags_str, datetime.now().isoformat()))
        
        self.conn.commit()
        print(f"✅ Learning saved: {title}")
        return cursor.lastrowid
    
    def get_learnings(self, category: str = None, 
                     min_importance: int = 1, limit: int = 10) -> List[Dict]:
        """Öğrenmeleri getir"""
        cursor = self.conn.cursor()
        
        query = "SELECT * FROM learnings WHERE importance >= ?"
        params = [min_importance]
        
        if category:
            query += " AND category = ?"
            params.append(category)
        
        query += " ORDER BY importance DESC, created_at DESC LIMIT ?"
        params.append(limit)
        
        cursor.execute(query, params)
        return [dict(row) for row in cursor.fetchall()]
    
    # ==================== QUERY HISTORY ====================
    
    def log_query(self, query: str, context: str = "", 
                 results_count: int = 0, useful: bool = True):
        """RAG query geçmişini kaydet"""
        cursor = self.conn.cursor()
        
        cursor.execute("""
            INSERT INTO query_history 
            (query, context, results_count, useful, created_at)
            VALUES (?, ?, ?, ?, ?)
        """, (query, context, results_count, useful, 
              datetime.now().isoformat()))
        
        self.conn.commit()
    
    def get_query_stats(self) -> Dict:
        """Query istatistikleri"""
        cursor = self.conn.cursor()
        
        # Toplam query sayısı
        cursor.execute("SELECT COUNT(*) as total FROM query_history")
        total = cursor.fetchone()['total']
        
        # Kullanışlı query'ler
        cursor.execute("SELECT COUNT(*) as useful FROM query_history WHERE useful = 1")
        useful = cursor.fetchone()['useful']
        
        # En çok kullanılan query'ler
        cursor.execute("""
            SELECT query, COUNT(*) as count 
            FROM query_history 
            GROUP BY query 
            ORDER BY count DESC 
            LIMIT 5
        """)
        top_queries = [dict(row) for row in cursor.fetchall()]
        
        return {
            "total_queries": total,
            "useful_queries": useful,
            "success_rate": (useful / total * 100) if total > 0 else 0,
            "top_queries": top_queries
        }
    
    # ==================== EXPORT / STATS ====================
    
    def get_all_stats(self) -> Dict:
        """Tüm istatistikler"""
        cursor = self.conn.cursor()
        
        stats = {}
        
        # Decisions
        cursor.execute("SELECT COUNT(*) as count FROM decisions")
        stats['decisions_count'] = cursor.fetchone()['count']
        
        cursor.execute("""
            SELECT category, COUNT(*) as count 
            FROM decisions 
            GROUP BY category
        """)
        stats['decisions_by_category'] = {row['category']: row['count'] 
                                         for row in cursor.fetchall()}
        
        # Patterns
        cursor.execute("SELECT COUNT(*) as count FROM patterns")
        stats['patterns_count'] = cursor.fetchone()['count']
        
        cursor.execute("""
            SELECT language, COUNT(*) as count 
            FROM patterns 
            GROUP BY language
        """)
        stats['patterns_by_language'] = {row['language']: row['count'] 
                                         for row in cursor.fetchall()}
        
        # Bug Fixes
        cursor.execute("SELECT COUNT(*) as count FROM bug_fixes")
        stats['bug_fixes_count'] = cursor.fetchone()['count']
        
        cursor.execute("""
            SELECT severity, COUNT(*) as count 
            FROM bug_fixes 
            GROUP BY severity
        """)
        stats['bug_fixes_by_severity'] = {row['severity']: row['count'] 
                                          for row in cursor.fetchall()}
        
        # Learnings
        cursor.execute("SELECT COUNT(*) as count FROM learnings")
        stats['learnings_count'] = cursor.fetchone()['count']
        
        # Query history
        stats['query_stats'] = self.get_query_stats()
        
        return stats
    
    def export_to_markdown(self, output_file: str = "hanka_memory_export.md"):
        """Memory'yi Markdown formatında export et"""
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write("# HANKA Development Memory Export\n\n")
            f.write(f"Generated: {datetime.now().isoformat()}\n\n")
            
            # Decisions
            f.write("## 📋 Development Decisions\n\n")
            decisions = self.get_decisions(limit=100)
            for dec in decisions:
                f.write(f"### {dec['title']}\n")
                f.write(f"**Category:** {dec['category']}\n")
                f.write(f"**Context:** {dec['context']}\n")
                f.write(f"**Decision:** {dec['decision']}\n")
                if dec['rationale']:
                    f.write(f"**Rationale:** {dec['rationale']}\n")
                f.write(f"**Created:** {dec['created_at']}\n\n")
            
            # Patterns
            f.write("## 🔧 Code Patterns\n\n")
            patterns = self.get_patterns(limit=100)
            for pat in patterns:
                f.write(f"### {pat['name']}\n")
                f.write(f"**Language:** {pat['language']}\n")
                f.write(f"**Description:** {pat['description']}\n")
                f.write(f"**Usage Count:** {pat['usage_count']}\n")
                f.write(f"```{pat['language']}\n{pat['code_example']}\n```\n\n")
            
            # Bug Fixes
            f.write("## 🐛 Bug Fixes\n\n")
            bugs = self.get_bug_fixes(limit=100)
            for bug in bugs:
                f.write(f"### {bug['title']}\n")
                f.write(f"**Severity:** {bug['severity']}\n")
                f.write(f"**Description:** {bug['description']}\n")
                if bug['error_message']:
                    f.write(f"**Error:** `{bug['error_message']}`\n")
                f.write(f"**Solution:** {bug['solution']}\n")
                f.write(f"**Resolved:** {bug['resolved_at']}\n\n")
        
        print(f"✅ Exported to: {output_file}")
    
    def close(self):
        """Bağlantıyı kapat"""
        self.conn.close()


def main():
    """CLI interface"""
    import argparse
    
    parser = argparse.ArgumentParser(description='HANKA Local Memory')
    parser.add_argument('action', choices=['stats', 'export', 'init'],
                       help='Action to perform')
    parser.add_argument('--output', '-o', default='hanka_memory_export.md',
                       help='Output file for export')
    
    args = parser.parse_args()
    
    memory = HANKAMemory()
    
    if args.action == 'init':
        print("✅ Memory database initialized")
    
    elif args.action == 'stats':
        stats = memory.get_all_stats()
        print("\n📊 Memory Statistics:\n")
        print(f"  Decisions: {stats['decisions_count']}")
        print(f"  Patterns: {stats['patterns_count']}")
        print(f"  Bug Fixes: {stats['bug_fixes_count']}")
        print(f"  Learnings: {stats['learnings_count']}")
        
        print(f"\n  Total Queries: {stats['query_stats']['total_queries']}")
        print(f"  Success Rate: {stats['query_stats']['success_rate']:.1f}%")
        
        if stats['decisions_by_category']:
            print("\n  Decisions by Category:")
            for cat, count in stats['decisions_by_category'].items():
                print(f"    {cat or 'uncategorized'}: {count}")
    
    elif args.action == 'export':
        memory.export_to_markdown(args.output)
    
    memory.close()


if __name__ == "__main__":
    main()
