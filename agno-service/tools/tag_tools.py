import httpx
import os
from agno.tools import tool

LARAVEL_URL = os.getenv("LARAVEL_API_URL", "http://nginx:80")
LARAVEL_TOKEN = os.getenv("LARAVEL_INTERNAL_TOKEN", "")


def _headers() -> dict:
    return {"X-Agno-Token": LARAVEL_TOKEN, "Accept": "application/json"}


def make_tag_tools(lead_id: int, available_tags: list[str], tenant_id: int) -> list:
    """Return tag tools bound to a specific lead_id."""

    tags_info = ", ".join(available_tags) if available_tags else "(nenhuma tag disponível)"

    @tool
    def add_tag(tag_name: str) -> str:
        f"""Adiciona uma tag ao lead para classificação ou segmentação.

        Tags disponíveis: {tags_info}

        Use APENAS tags da lista acima.
        """
        try:
            r = httpx.post(
                f"{LARAVEL_URL}/api/internal/agno/leads/{lead_id}/tags",
                json={"tag_name": tag_name, "tenant_id": tenant_id},
                headers=_headers(),
                timeout=10,
            )
            if r.is_success:
                return f"Tag '{tag_name}' adicionada ao lead."
            return f"Erro ao adicionar tag: {r.status_code} {r.text}"
        except Exception as e:
            return f"Erro de conexão: {str(e)}"

    return [add_tag]
