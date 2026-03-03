import httpx
import os
from agno.tools import tool

LARAVEL_URL = os.getenv("LARAVEL_API_URL", "http://nginx:80")
LARAVEL_TOKEN = os.getenv("LARAVEL_INTERNAL_TOKEN", "")


def _headers() -> dict:
    return {"X-Agno-Token": LARAVEL_TOKEN, "Accept": "application/json"}


def make_notify_tools(lead_id: int, conversation_id: int, tenant_id: int) -> list:
    """Return notification/transfer tools bound to a specific conversation."""

    @tool
    def notify_human_intent(intent: str, reason: str = "") -> str:
        """Notifica a equipe humana de que o cliente tem intenção de compra ou precisa de atendimento humano.

        Use quando o cliente demonstrar interesse real em contratar, pedir falar com pessoa ou quando não
        conseguir resolver o problema com as informações disponíveis.

        Args:
            intent: A intenção detectada (ex: "buy", "schedule", "close", "interest")
            reason: Contexto adicional sobre o motivo
        """
        try:
            r = httpx.post(
                f"{LARAVEL_URL}/api/internal/agno/conversations/{conversation_id}/notify-intent",
                json={"intent": intent, "reason": reason, "lead_id": lead_id, "tenant_id": tenant_id},
                headers=_headers(),
                timeout=10,
            )
            if r.is_success:
                return f"Equipe notificada sobre intenção: {intent}"
            return f"Erro ao notificar: {r.status_code}"
        except Exception as e:
            return f"Erro de conexão: {str(e)}"

    @tool
    def transfer_to_human() -> str:
        """Transfere o atendimento para um agente humano e pausa o bot.

        Use apenas quando o cliente solicitar explicitamente falar com uma pessoa,
        ou quando a situação estiver fora do seu escopo de atendimento.
        """
        try:
            r = httpx.post(
                f"{LARAVEL_URL}/api/internal/agno/conversations/{conversation_id}/transfer",
                json={"tenant_id": tenant_id},
                headers=_headers(),
                timeout=10,
            )
            if r.is_success:
                return "Atendimento transferido para equipe humana."
            return f"Erro ao transferir: {r.status_code}"
        except Exception as e:
            return f"Erro de conexão: {str(e)}"

    return [notify_human_intent, transfer_to_human]
