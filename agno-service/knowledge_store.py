"""
Knowledge Store — pgvector-backed RAG storage for AI agent knowledge files.

Fluxo:
- PHP extrai texto de PDF/DOCX/TXT/imagem e manda pro endpoint /agents/{id}/index-file
- Esse modulo chunkifica o texto, gera embeddings via OpenAI text-embedding-3-small
  e salva os chunks na tabela agent_knowledge_chunks (1536 dim, ivfflat index).
- Na hora de chat, /agents/{id}/knowledge/search recebe a mensagem do usuario,
  embeda, e devolve top-K chunks mais similares (cosine).

Reusa generate_embedding e a engine SQLAlchemy de memory_store pra nao duplicar
config nem credenciais OpenAI.
"""

import re
from datetime import datetime, timezone

from sqlalchemy import Column, DateTime, Integer, String, Text, text
from sqlalchemy.orm import Session, declarative_base

from memory_store import EMBEDDING_DIM, SessionLocal, engine, generate_embedding

Base = declarative_base()


class AgentKnowledgeChunk(Base):
    __tablename__ = "agent_knowledge_chunks"

    id = Column(Integer, primary_key=True, autoincrement=True)
    tenant_id = Column(Integer, nullable=False, index=True)
    agent_id = Column(Integer, nullable=False, index=True)
    file_id = Column(Integer, nullable=False, index=True)
    filename = Column(String(255), nullable=False)
    chunk_index = Column(Integer, nullable=False)
    content = Column(Text, nullable=False)
    token_count = Column(Integer, nullable=True)
    created_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))


# ── Chunking config ───────────────────────────────────────────────────────────
CHUNK_TARGET = 500   # chars-alvo por chunk
CHUNK_OVERLAP = 50   # overlap entre chunks pra preservar contexto


def init_knowledge_tables() -> None:
    """Cria a tabela agent_knowledge_chunks + coluna vector + indice ivfflat."""
    with engine.connect() as conn:
        conn.execute(text("CREATE EXTENSION IF NOT EXISTS vector"))
        conn.commit()

    Base.metadata.create_all(engine)

    with engine.connect() as conn:
        # Adiciona coluna vector(1536) se nao existe (pgvector type nao tem suporte
        # no SQLAlchemy declarative basico)
        conn.execute(text(f"""
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'agent_knowledge_chunks' AND column_name = 'embedding'
                ) THEN
                    ALTER TABLE agent_knowledge_chunks ADD COLUMN embedding vector({EMBEDDING_DIM});
                END IF;
            END $$;
        """))
        # Indice ivfflat pra similaridade cosine rapida
        conn.execute(text("""
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes WHERE indexname = 'idx_agent_knowledge_chunks_embedding'
                ) THEN
                    CREATE INDEX idx_agent_knowledge_chunks_embedding
                    ON agent_knowledge_chunks USING ivfflat (embedding vector_cosine_ops)
                    WITH (lists = 100);
                END IF;
            END $$;
        """))
        # Indice composto pra filtrar antes do similarity search
        conn.execute(text("""
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes WHERE indexname = 'idx_agent_knowledge_chunks_agent_file'
                ) THEN
                    CREATE INDEX idx_agent_knowledge_chunks_agent_file
                    ON agent_knowledge_chunks (agent_id, file_id);
                END IF;
            END $$;
        """))
        conn.commit()


# ── Chunking ──────────────────────────────────────────────────────────────────

def chunk_text(text_input: str, target_size: int = CHUNK_TARGET, overlap: int = CHUNK_OVERLAP) -> list[str]:
    """
    Quebra o texto em chunks de ~target_size chars respeitando boundaries semanticas.

    Estrategia (recursiva):
    1. Tenta quebrar em paragrafos (\\n\\n)
    2. Se um paragrafo > target_size, quebra em sentencas (. ! ?)
    3. Se uma sentenca > target_size, quebra em espacos
    4. Adiciona overlap entre chunks consecutivos pra preservar contexto

    Idempotente: re-chamar com o mesmo texto produz os mesmos chunks.
    """
    text_input = text_input.strip()
    if not text_input:
        return []

    if len(text_input) <= target_size:
        return [text_input]

    chunks: list[str] = []

    # Passo 1: parte em paragrafos
    paragraphs = re.split(r"\n\n+", text_input)
    buffer = ""

    for para in paragraphs:
        para = para.strip()
        if not para:
            continue

        # Se o paragrafo cabe no buffer atual, agrega
        if len(buffer) + len(para) + 2 <= target_size:
            buffer = (buffer + "\n\n" + para) if buffer else para
            continue

        # Buffer cheio: descarrega
        if buffer:
            chunks.append(buffer)
            buffer = ""

        # Se o paragrafo cabe sozinho, vira o novo buffer
        if len(para) <= target_size:
            buffer = para
            continue

        # Paragrafo gigante: quebra por sentenca
        sentences = re.split(r"(?<=[.!?])\s+", para)
        sub_buffer = ""
        for sent in sentences:
            if len(sub_buffer) + len(sent) + 1 <= target_size:
                sub_buffer = (sub_buffer + " " + sent) if sub_buffer else sent
                continue

            if sub_buffer:
                chunks.append(sub_buffer)
                sub_buffer = ""

            # Sentenca ainda gigante: corta no espaco mais proximo do limite
            while len(sent) > target_size:
                cut = sent.rfind(" ", 0, target_size)
                if cut <= 0:
                    cut = target_size
                chunks.append(sent[:cut].strip())
                sent = sent[cut:].strip()

            sub_buffer = sent

        if sub_buffer:
            buffer = sub_buffer

    if buffer:
        chunks.append(buffer)

    # Aplica overlap: cada chunk N+1 comeca repetindo as ultimas `overlap` chars do chunk N
    if overlap > 0 and len(chunks) > 1:
        with_overlap = [chunks[0]]
        for i in range(1, len(chunks)):
            prev_tail = chunks[i - 1][-overlap:] if len(chunks[i - 1]) > overlap else chunks[i - 1]
            with_overlap.append(prev_tail + " " + chunks[i])
        chunks = with_overlap

    return [c.strip() for c in chunks if c.strip()]


# ── Indexing ──────────────────────────────────────────────────────────────────

async def index_knowledge_file(
    tenant_id: int,
    agent_id: int,
    file_id: int,
    filename: str,
    text_input: str,
) -> dict:
    """
    Indexa um arquivo: chunkifica, embeda e salva no pgvector.
    Re-index e idempotente: apaga chunks antigos do mesmo (agent_id, file_id) antes.
    """
    if not text_input or not text_input.strip():
        return {"ok": False, "error": "empty text", "chunks_count": 0, "tokens_used": 0}

    chunks = chunk_text(text_input)
    if not chunks:
        return {"ok": False, "error": "no chunks generated", "chunks_count": 0, "tokens_used": 0}

    db: Session = SessionLocal()
    try:
        # Apaga indexacao antiga desse arquivo (re-index limpo)
        db.execute(
            text("DELETE FROM agent_knowledge_chunks WHERE agent_id = :aid AND file_id = :fid"),
            {"aid": agent_id, "fid": file_id},
        )

        inserted = 0
        for idx, chunk in enumerate(chunks):
            embedding = await generate_embedding(chunk)
            if embedding is None:
                # Sem API key OU rate limit — pula esse chunk mas continua os outros
                continue

            embedding_str = "[" + ",".join(str(x) for x in embedding) + "]"
            token_count = max(1, len(chunk) // 4)  # estimativa: 1 token ~ 4 chars pt-BR

            db.execute(
                text("""
                    INSERT INTO agent_knowledge_chunks
                        (tenant_id, agent_id, file_id, filename, chunk_index, content, token_count, embedding, created_at)
                    VALUES
                        (:tid, :aid, :fid, :fname, :cidx, :content, :tokens, CAST(:emb AS vector), NOW())
                """),
                {
                    "tid": tenant_id,
                    "aid": agent_id,
                    "fid": file_id,
                    "fname": filename,
                    "cidx": idx,
                    "content": chunk,
                    "tokens": token_count,
                    "emb": embedding_str,
                },
            )
            inserted += 1

        db.commit()

        # Estimativa de tokens usados pra embedding (1 token ~ 4 chars)
        tokens_used = sum(max(1, len(c) // 4) for c in chunks)

        return {
            "ok": True,
            "chunks_count": inserted,
            "tokens_used": tokens_used,
            "filename": filename,
        }
    except Exception as e:
        db.rollback()
        print(f"[knowledge_store] index failed: {e}")
        return {"ok": False, "error": str(e), "chunks_count": 0, "tokens_used": 0}
    finally:
        db.close()


# ── Search ────────────────────────────────────────────────────────────────────

async def search_knowledge(
    tenant_id: int,
    agent_id: int,
    query: str,
    top_k: int = 5,
    similarity_threshold: float = 0.25,
) -> list[dict]:
    """
    Busca top-K chunks mais relevantes pra query usando cosine similarity.
    Threshold 0.25 (mais permissivo que memories=0.3) porque queries de chat
    costumam ser keywords curtas tipo "quanto custa" — similarity natural fica baixa.
    """
    if not query or not query.strip():
        return []

    embedding = await generate_embedding(query)
    if embedding is None:
        return []

    embedding_str = "[" + ",".join(str(x) for x in embedding) + "]"

    db: Session = SessionLocal()
    try:
        sql = """
            SELECT id, file_id, filename, chunk_index, content,
                   1 - (embedding <=> CAST(:emb AS vector)) AS similarity
            FROM agent_knowledge_chunks
            WHERE tenant_id = :tid
              AND agent_id = :aid
              AND embedding IS NOT NULL
            ORDER BY embedding <=> CAST(:emb AS vector)
            LIMIT :topk
        """

        result = db.execute(
            text(sql),
            {"emb": embedding_str, "tid": tenant_id, "aid": agent_id, "topk": top_k},
        )

        return [
            {
                "id": row.id,
                "file_id": row.file_id,
                "filename": row.filename,
                "chunk_index": row.chunk_index,
                "content": row.content,
                "similarity": round(float(row.similarity), 4),
            }
            for row in result.fetchall()
            if row.similarity > similarity_threshold
        ]
    except Exception as e:
        print(f"[knowledge_store] search failed: {e}")
        return []
    finally:
        db.close()


# ── Delete ────────────────────────────────────────────────────────────────────

async def delete_chunks_by_file(agent_id: int, file_id: int) -> int:
    """Apaga todos os chunks de um arquivo. Retorna numero de rows deletadas."""
    db: Session = SessionLocal()
    try:
        result = db.execute(
            text("DELETE FROM agent_knowledge_chunks WHERE agent_id = :aid AND file_id = :fid"),
            {"aid": agent_id, "fid": file_id},
        )
        db.commit()
        return result.rowcount or 0
    except Exception as e:
        db.rollback()
        print(f"[knowledge_store] delete failed: {e}")
        return 0
    finally:
        db.close()
