---
type: decision
status: implemented
date: 2026-04-09
modules: ["[[AI Agents]]", "[[Agno]]"]
files:
  - app/Http/Controllers/Tenant/AiAgentController.php
  - app/Services/AgnoService.php
  - app/Jobs/ProcessAiResponse.php
  - app/Console/Commands/ReindexAgnoKnowledge.php
  - app/Models/AiAgentKnowledgeFile.php
  - database/migrations/2026_04_09_180000_add_indexed_to_ai_agent_knowledge_files.php
  - agno-service/knowledge_store.py
  - agno-service/main.py
  - agno-service/schemas.py
  - agno-service/agent_factory.py
  - composer.json
commits: ["9c1b7fb"]
related: ["[[AI Agents]]", "[[Agno]]", "[[AgnoService]]", "[[AiAgentKnowledgeFile]]"]
tags: [decision, ai, rag, knowledge-base]
---

# 2026-04-09 — RAG real implementado pra base de conhecimento dos agentes

## Contexto

O upload de "Base de Conhecimento" no painel `/ia/agentes/{id}/editar` era **enganoso**: cliente subia PDF/TXT, via o arquivo aparecer na lista, mas a IA **nao tinha acesso ao conteudo** quando `use_agno=true` (que e o caso de todos agentes hoje em prod).

Causa: o endpoint `/agents/{id}/index-file` no Agno era literal um stub que retornava `{"note": "RAG indexing not yet enabled"}`. Vetorizacao nunca tinha sido implementada. E o `syncToAgno` so enviava `knowledge_base_text` (a textarea do painel), os arquivos uploaded ficavam orfaos.

Cliente da clinica medica (Camila, agent #12) tinha 32k chars de scripts em DOCX e 15k em TXT — nada disso chegava na IA. A Camila respondia generico, inventando precos e procedimentos.

## Decisao

Implementar RAG real ponta-a-ponta usando o pgvector que ja roda como deps do Agno. **Nao** usar fine-tuning (overkill, multi-tenant impraticavel) nem injectar texto bruto inteiro no system prompt (caro, estoura context window).

### Arquitetura

```
UPLOAD                           CHAT (RAG)
PHP extrai texto                 PHP recebe mensagem do user
  ↓                                ↓
POST /index-file (Agno)          POST /knowledge/search (Agno)
  ↓                                ↓
chunkifica ~500 chars            embed query + cosine similarity
  ↓                                ↓ top 5 chunks
gera embedding por chunk         POST /chat com knowledge_chunks
  ↓                                ↓
INSERT em                        agent_factory injeta no system prompt
agent_knowledge_chunks             como "CONTEXTO RELEVANTE DA BASE"
(pgvector + ivfflat cosine)        ↓
                                 LLM responde citando o conteudo real
```

### Schema novo (pgvector)

`agent_knowledge_chunks`:
```sql
id              SERIAL PRIMARY KEY
tenant_id       INT NOT NULL (indexed)
agent_id        INT NOT NULL (indexed)
file_id         INT NOT NULL (indexed)  -- FK logica pro PHP
filename        VARCHAR(255)
chunk_index     INT
content         TEXT
token_count     INT
embedding       vector(1536)             -- pgvector type
created_at      TIMESTAMP
INDEX idx_agent_knowledge_chunks_embedding USING ivfflat (embedding vector_cosine_ops) WITH (lists=100)
INDEX idx_agent_knowledge_chunks_agent_file (agent_id, file_id)
```

### Componentes implementados

**PHP**:
- `composer.json` — `phpoffice/phpword: ^1.3` adicionado pra DOCX
- `AiAgentController::uploadKnowledgeFile` aceita .doc/.docx, novo helper `extractDocxText()` recursivo (paragrafos + tabelas + text runs)
- `AiAgentController::deleteKnowledgeFile` agora apaga chunks no Agno via cascade
- `AgnoService::indexFile($agentId, $tenantId, $fileId, $text, $filename)` — assinatura nova (passa file_id)
- `AgnoService::searchKnowledge($agentId, $tenantId, $query, $topK=5)` — novo metodo
- `AgnoService::deleteKnowledgeFile($agentId, $fileId)` — novo metodo
- `ProcessAiResponse` antes do chat call: `searchKnowledge($agent->id, ..., $messageBody, 5)` e injeta em `knowledge_chunks` no payload
- `ReindexAgnoKnowledge` command — `agno:reindex-knowledge {--agent= --file= --missing}`. Idempotente. Roda no entrypoint do app com `--missing` em background
- Migration adiciona `chunks_count`, `indexed_at`, `indexing_error` em `ai_agent_knowledge_files`
- Cost tracking: `AiUsageLog` com `type='knowledge_indexing'`, `model='text-embedding-3-small'` em ambos os spots de indexacao (controller + command)

**Python (agno-service)**:
- `knowledge_store.py` — NOVO arquivo, espelha pattern de `memory_store.py`
  - Reusa `SessionLocal`, `engine`, `generate_embedding` pra nao duplicar credenciais OpenAI
  - `init_knowledge_tables()` chamado no `lifespan` do FastAPI
  - `chunk_text(text, target_size=500, overlap=50)` — splitter recursivo (paragrafos → sentencas → espacos)
  - `index_knowledge_file()` — apaga chunks antigos do mesmo file_id, gera embeddings, INSERT em batch
  - `search_knowledge()` — embed query, cosine similarity, threshold 0.25 (mais permissivo que memories=0.3 porque queries de chat sao keywords curtas)
  - `delete_chunks_by_file()` — DELETE WHERE agent_id + file_id
- `main.py`:
  - `/agents/{id}/index-file` — implementacao real (substitui stub)
  - `/agents/{id}/knowledge/search` — novo endpoint
  - `DELETE /agents/{id}/knowledge/{file_id}` — novo endpoint
  - `lifespan` chama `init_knowledge_tables()` junto com memories
- `schemas.py`:
  - `IndexFileRequest` ganha `file_id`
  - `KnowledgeSearchRequest` novo
  - `ChatRequest` ganha `knowledge_chunks: list[dict] = []`
- `agent_factory.py`:
  - `get_or_create_agent` aceita kwarg `knowledge_chunks` (conta como contextual, bypassa cache)
  - `_build_instructions` injeta bloco "CONTEXTO RELEVANTE DA BASE DE CONHECIMENTO" com instrucao explicita: "use como FONTE DE VERDADE, se nao cobre a pergunta diga que nao tem essa info ao inves de inventar"

### Verificacao

Apos deploy + backfill, validamos com a Camila:
```sql
SELECT agent_id, file_id, count(*) FROM agent_knowledge_chunks GROUP BY 1,2;
-- agent_id=12, file_id=14, count=75    (DOCX da clinica)
-- agent_id=12, file_id=15, count=37    (TXT de procedimentos)
```

User mandou perguntas cujas respostas estao nos arquivos:
- "Quanto custa a consulta?" → IA respondeu R$ 1.274,00 com retorno (valor exato dos scripts)
- "Faz ninfoplastia?" → script completo com tempo de cirurgia, anestesia, abstinencia, etc

Zero invencao. RAG funcionando ponta-a-ponta.

## Implementacoes futuras (fora desse PR)

- **UI feedback** — mostrar `chunks_count` na lista de arquivos do painel
- **Persistir `_agent_configs`** do Agno em disco/pg pra eliminar a dependencia do `agno:reconfigure-all` no boot (Fase 9 do plano original, marcada como opcional)
- **Re-indexacao automatica** quando o user edita o arquivo (hoje so aceita upload novo)
- **Threshold customizavel por agent** (hoje hardcoded 0.25 — alguns agentes podem querer mais permissivo)

## Links
- Commit: `9c1b7fb`
- Plano original: `~/.claude/plans/eager-seeking-corbato.md`
- RCA dos bugs do Agno descobertos no caminho: [[2026-04-09 Camila e Sophia silenciosas — 5 bugs do Agno]]
