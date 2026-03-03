import httpx
import os
from agno.tools import tool

LARAVEL_URL = os.getenv("LARAVEL_API_URL", "http://nginx:80")
LARAVEL_TOKEN = os.getenv("LARAVEL_INTERNAL_TOKEN", "")


def _headers() -> dict:
    return {"X-Agno-Token": LARAVEL_TOKEN, "Accept": "application/json"}


def make_pipeline_tools(lead_id: int, pipeline_stages: list[dict]) -> list:
    """Return a list of pipeline tools bound to a specific lead_id."""

    stages_info = "\n".join(
        f"  - id={s['id']}: {s.get('name', '')}" for s in pipeline_stages
    ) or "  (nenhuma etapa disponível)"

    @tool
    def move_to_stage(stage_id: int) -> str:
        f"""Move o lead para uma etapa do funil de vendas.

        Etapas disponíveis:
        {stages_info}

        Use APENAS os stage_ids listados acima.
        """
        try:
            r = httpx.put(
                f"{LARAVEL_URL}/api/v1/leads/{lead_id}/stage",
                json={"stage_id": stage_id},
                headers=_headers(),
                timeout=10,
            )
            if r.is_success:
                return f"Lead movido para etapa {stage_id} com sucesso."
            return f"Erro ao mover lead: {r.status_code} {r.text}"
        except Exception as e:
            return f"Erro de conexão: {str(e)}"

    return [move_to_stage]
