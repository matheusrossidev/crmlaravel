from pydantic import BaseModel
from typing import Any


class ChatRequest(BaseModel):
    agent_id: int
    tenant_id: int
    conversation_id: int
    contact_phone: str
    message: str
    history_limit: int = 20
    pipeline_stages: list[dict[str, Any]] = []
    available_tags: list[str] = []
    memories: list[str] = []


class AgentAction(BaseModel):
    type: str
    payload: dict[str, Any] = {}


class AgentResponse(BaseModel):
    reply_blocks: list[str]
    actions: list[AgentAction] = []
    memories_extracted: list[str] = []
    tokens_prompt: int = 0
    tokens_completion: int = 0
    tokens_total: int = 0
    model: str = ""
    provider: str = ""


class ConfigureRequest(BaseModel):
    tenant_id: int
    name: str
    objective: str
    company_name: str = ""
    industry: str = ""
    communication_style: str = "professional"
    persona_description: str = ""
    behavior: str = ""
    max_message_length: int = 800
    knowledge_base_text: str = ""
    llm_provider: str = "openai"
    llm_model: str = "gpt-4o-mini"
    llm_api_key: str = ""
    enable_pipeline_tool: bool = False
    enable_tags_tool: bool = False
    enable_intent_notify: bool = False
    enable_calendar_tool: bool = False


class IndexFileRequest(BaseModel):
    tenant_id: int
    text: str
    filename: str


class StoreMemoryRequest(BaseModel):
    tenant_id: int
    conversation_id: int | None = None
    contact_phone: str | None = None
    summary: str
    customer_profile: str | None = None
    key_learnings: str | None = None


class SearchMemoryRequest(BaseModel):
    tenant_id: int
    query: str
    top_k: int = 3
    contact_phone: str | None = None
