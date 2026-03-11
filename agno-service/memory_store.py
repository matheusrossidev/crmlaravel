"""
Agent Memory Store — pgvector-backed semantic memory for AI agent learning.

Stores conversation summaries as embeddings and retrieves relevant memories
via cosine similarity search for context injection.
"""

import os
from datetime import datetime, timezone

import httpx
from sqlalchemy import Column, DateTime, Integer, String, Text, create_engine, text
from sqlalchemy.orm import Session, declarative_base, sessionmaker

_raw_url = os.getenv("PGVECTOR_URL", "postgresql+psycopg://agno:agno@pgvector:5432/agno")
# Ensure we use the psycopg3 driver (not psycopg2)
PGVECTOR_URL = _raw_url.replace("postgresql://", "postgresql+psycopg://") if _raw_url.startswith("postgresql://") else _raw_url
OPENAI_API_KEY = os.getenv("LLM_API_KEY", "")
EMBEDDING_MODEL = "text-embedding-3-small"
EMBEDDING_DIM = 1536

engine = create_engine(PGVECTOR_URL, pool_pre_ping=True, pool_size=5)
SessionLocal = sessionmaker(bind=engine)
Base = declarative_base()


class AgentMemory(Base):
    __tablename__ = "agent_memories"

    id = Column(Integer, primary_key=True, autoincrement=True)
    tenant_id = Column(Integer, nullable=False, index=True)
    agent_id = Column(Integer, nullable=False, index=True)
    conversation_id = Column(Integer, nullable=True)
    contact_phone = Column(String(30), nullable=True)
    summary = Column(Text, nullable=False)
    customer_profile = Column(Text, nullable=True)
    key_learnings = Column(Text, nullable=True)
    created_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))


def init_memory_tables():
    """Create the agent_memories table and pgvector extension if not exists."""
    with engine.connect() as conn:
        conn.execute(text("CREATE EXTENSION IF NOT EXISTS vector"))
        conn.commit()

    Base.metadata.create_all(engine)

    # Add vector column if it doesn't exist (pgvector type not in SQLAlchemy model)
    with engine.connect() as conn:
        conn.execute(text(f"""
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'agent_memories' AND column_name = 'embedding'
                ) THEN
                    ALTER TABLE agent_memories ADD COLUMN embedding vector({EMBEDDING_DIM});
                END IF;
            END $$;
        """))
        # Create ivfflat index for fast similarity search
        conn.execute(text("""
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes WHERE indexname = 'idx_agent_memories_embedding'
                ) THEN
                    CREATE INDEX idx_agent_memories_embedding
                    ON agent_memories USING ivfflat (embedding vector_cosine_ops)
                    WITH (lists = 100);
                END IF;
            END $$;
        """))
        conn.commit()


async def generate_embedding(text_input: str) -> list[float] | None:
    """Generate an embedding via OpenAI's text-embedding-3-small model."""
    if not OPENAI_API_KEY:
        return None

    try:
        async with httpx.AsyncClient(timeout=15.0) as client:
            resp = await client.post(
                "https://api.openai.com/v1/embeddings",
                headers={"Authorization": f"Bearer {OPENAI_API_KEY}"},
                json={"model": EMBEDDING_MODEL, "input": text_input},
            )
            resp.raise_for_status()
            data = resp.json()
            return data["data"][0]["embedding"]
    except Exception as e:
        print(f"[memory_store] Embedding generation failed: {e}")
        return None


async def store_memory(
    tenant_id: int,
    agent_id: int,
    summary: str,
    conversation_id: int | None = None,
    contact_phone: str | None = None,
    customer_profile: str | None = None,
    key_learnings: str | None = None,
) -> int | None:
    """Store a conversation summary with its embedding in pgvector."""
    embedding = await generate_embedding(summary)
    if not embedding:
        return None

    db: Session = SessionLocal()
    try:
        memory = AgentMemory(
            tenant_id=tenant_id,
            agent_id=agent_id,
            conversation_id=conversation_id,
            contact_phone=contact_phone,
            summary=summary,
            customer_profile=customer_profile,
            key_learnings=key_learnings,
        )
        db.add(memory)
        db.flush()
        memory_id = memory.id

        # Set the vector column via raw SQL (pgvector type)
        embedding_str = "[" + ",".join(str(x) for x in embedding) + "]"
        db.execute(
            text("UPDATE agent_memories SET embedding = :emb WHERE id = :mid"),
            {"emb": embedding_str, "mid": memory_id},
        )
        db.commit()
        return memory_id
    except Exception as e:
        db.rollback()
        print(f"[memory_store] Store failed: {e}")
        return None
    finally:
        db.close()


async def search_memories(
    tenant_id: int,
    agent_id: int,
    query: str,
    top_k: int = 3,
    contact_phone: str | None = None,
) -> list[dict]:
    """Search for relevant memories using cosine similarity."""
    embedding = await generate_embedding(query)
    if not embedding:
        return []

    embedding_str = "[" + ",".join(str(x) for x in embedding) + "]"

    db: Session = SessionLocal()
    try:
        # Search with optional phone filter for contact-specific memories
        phone_filter = ""
        params: dict = {
            "emb": embedding_str,
            "tid": tenant_id,
            "aid": agent_id,
            "topk": top_k,
        }

        if contact_phone:
            # Get memories from same contact + general agent memories
            phone_filter = "AND (am.contact_phone = :phone OR am.contact_phone IS NULL)"
            params["phone"] = contact_phone

        sql = f"""
            SELECT am.id, am.summary, am.customer_profile, am.key_learnings,
                   am.contact_phone, am.conversation_id, am.created_at,
                   1 - (am.embedding <=> :emb::vector) AS similarity
            FROM agent_memories am
            WHERE am.tenant_id = :tid
              AND am.agent_id = :aid
              AND am.embedding IS NOT NULL
              {phone_filter}
            ORDER BY am.embedding <=> :emb::vector
            LIMIT :topk
        """

        result = db.execute(text(sql), params)
        rows = result.fetchall()

        return [
            {
                "id": row.id,
                "summary": row.summary,
                "customer_profile": row.customer_profile,
                "key_learnings": row.key_learnings,
                "contact_phone": row.contact_phone,
                "similarity": round(float(row.similarity), 4),
                "created_at": row.created_at.isoformat() if row.created_at else None,
            }
            for row in rows
            if row.similarity > 0.3  # Minimum relevance threshold
        ]
    except Exception as e:
        print(f"[memory_store] Search failed: {e}")
        return []
    finally:
        db.close()
