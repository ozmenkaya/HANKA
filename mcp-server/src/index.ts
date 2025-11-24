#!/usr/bin/env node
/**
 * HANKA MCP Server
 * Model Context Protocol server exposing RAG + Memory tools to Claude
 */

import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";
import { spawn } from "child_process";
import { promisify } from "util";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Python script paths
const PROJECT_ROOT = path.resolve(__dirname, "../..");
const RAG_SCRIPT = path.join(PROJECT_ROOT, "rag_system.py");
const MEMORY_SCRIPT = path.join(PROJECT_ROOT, "local_memory.py");
const PYTHON_BIN = path.join(PROJECT_ROOT, ".venv/bin/python");

/**
 * Execute Python script
 */
async function executePython(
  scriptPath: string,
  args: string[]
): Promise<string> {
  return new Promise((resolve, reject) => {
    const childProcess = spawn(PYTHON_BIN, [scriptPath, ...args], {
      cwd: PROJECT_ROOT,
      env: {
        ...process.env,
        OPENAI_API_KEY: process.env.OPENAI_API_KEY || "",
      },
    });

    let stdout = "";
    let stderr = "";

    childProcess.stdout.on("data", (data: Buffer) => {
      stdout += data.toString();
    });

    childProcess.stderr.on("data", (data: Buffer) => {
      stderr += data.toString();
    });

    childProcess.on("close", (code: number | null) => {
      if (code !== 0) {
        reject(new Error(`Process exited with code ${code}: ${stderr}`));
      } else {
        resolve(stdout);
      }
    });

    childProcess.on("error", (err: Error) => {
      reject(err);
    });
  });
}

/**
 * MCP Server
 */
class HANKAMCPServer {
  private server: Server;

  constructor() {
    this.server = new Server(
      {
        name: "hanka-mcp-server",
        version: "1.0.0",
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.setupHandlers();
  }

  private setupHandlers() {
    // List available tools
    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: [
        {
          name: "search_code",
          description:
            "Semantik kod araması - HANKA kod tabanında RAG ile arama yap. PHP dosyalarında pattern, fonksiyon, class arar.",
          inputSchema: {
            type: "object",
            properties: {
              query: {
                type: "string",
                description:
                  "Arama sorgusu (örn: 'PDO prepared statement', 'firma_id kontrolü', 'JSON veriler')",
              },
              file_type: {
                type: "string",
                description:
                  "Dosya tipi filtresi (backend_api, view, agent, core, form, modal)",
                enum: [
                  "backend_api",
                  "view",
                  "agent",
                  "core",
                  "form",
                  "modal",
                  "javascript",
                ],
              },
              n_results: {
                type: "number",
                description: "Sonuç sayısı (default: 5)",
                default: 5,
              },
            },
            required: ["query"],
          },
        },
        {
          name: "get_rag_stats",
          description:
            "RAG collection istatistikleri - Index'lenen dosya sayıları, tipleri",
          inputSchema: {
            type: "object",
            properties: {},
          },
        },
        {
          name: "search_decisions",
          description:
            "Geliştirme kararlarında ara - Multi-tenant, PDO, security gibi teknik kararları bul",
          inputSchema: {
            type: "object",
            properties: {
              query: {
                type: "string",
                description:
                  "Karar sorgusu (örn: 'multi-tenant', 'PDO', 'security')",
              },
            },
            required: ["query"],
          },
        },
        {
          name: "search_patterns",
          description:
            "Kod pattern'lerinde ara - CRUD, AJAX, MES pattern'leri bul",
          inputSchema: {
            type: "object",
            properties: {
              query: {
                type: "string",
                description:
                  "Pattern sorgusu (örn: 'CRUD', 'AJAX', 'MES', 'JSON')",
              },
            },
            required: ["query"],
          },
        },
        {
          name: "search_bug_fixes",
          description:
            "Geçmiş bug fix'lerde ara - Benzer hataların çözümlerini bul",
          inputSchema: {
            type: "object",
            properties: {
              query: {
                type: "string",
                description:
                  "Bug sorgusu (örn: 'AlertAgent', 'MySQLi', 'JSON_EXTRACT')",
              },
            },
            required: ["query"],
          },
        },
        {
          name: "get_memory_stats",
          description:
            "Memory istatistikleri - Toplam decision, pattern, bug fix, learning sayıları",
          inputSchema: {
            type: "object",
            properties: {},
          },
        },
      ],
    }));

    // Handle tool calls
    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case "search_code": {
            const {
              query,
              file_type,
              n_results = 5,
            } = args as {
              query: string;
              file_type?: string;
              n_results?: number;
            };

            const ragArgs = ["search", "-q", query, "-n", String(n_results)];
            if (file_type) {
              ragArgs.push("-t", file_type);
            }

            const output = await executePython(RAG_SCRIPT, ragArgs);

            return {
              content: [
                {
                  type: "text",
                  text: output,
                },
              ],
            };
          }

          case "get_rag_stats": {
            const output = await executePython(RAG_SCRIPT, ["stats"]);

            return {
              content: [
                {
                  type: "text",
                  text: output,
                },
              ],
            };
          }

          case "search_decisions":
          case "search_patterns":
          case "search_bug_fixes": {
            const { query } = args as { query: string };

            // Python'da search fonksiyonunu çağır
            const pythonCode = `
from local_memory import HANKAMemory
import json

memory = HANKAMemory()

if "${name}" == "search_decisions":
    results = memory.search_decisions("${query}")
elif "${name}" == "search_patterns":
    results = memory.search_patterns("${query}")
else:
    results = memory.search_bug_fixes("${query}")

for r in results:
    print("=" * 60)
    print(f"Title: {r['title']}")
    if 'description' in r:
        print(f"Description: {r['description']}")
    if 'decision' in r:
        print(f"Decision: {r['decision']}")
    if 'code_example' in r:
        print(f"Code Example:\\n{r['code_example'][:300]}...")
    if 'solution' in r:
        print(f"Solution: {r['solution']}")
    print(f"Created: {r.get('created_at', 'N/A')}")
    print()

memory.close()
`;

            const output = await executePython(PYTHON_BIN, ["-c", pythonCode]);

            return {
              content: [
                {
                  type: "text",
                  text: output || "No results found",
                },
              ],
            };
          }

          case "get_memory_stats": {
            const pythonCode = `
from local_memory import HANKAMemory
import json

memory = HANKAMemory()
stats = memory.get_all_stats()

print(f"Decisions: {stats['decisions_count']}")
print(f"Patterns: {stats['patterns_count']}")
print(f"Bug Fixes: {stats['bug_fixes_count']}")
print(f"Learnings: {stats['learnings_count']}")
print(f"\\nQuery Stats:")
print(f"  Total: {stats['query_stats']['total_queries']}")
print(f"  Success Rate: {stats['query_stats']['success_rate']:.1f}%")

memory.close()
`;

            const output = await executePython(PYTHON_BIN, ["-c", pythonCode]);

            return {
              content: [
                {
                  type: "text",
                  text: output,
                },
              ],
            };
          }

          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : String(error);
        return {
          content: [
            {
              type: "text",
              text: `Error: ${errorMessage}`,
            },
          ],
          isError: true,
        };
      }
    });
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error("HANKA MCP Server running on stdio");
  }
}

// Start server
const server = new HANKAMCPServer();
server.run().catch(console.error);
