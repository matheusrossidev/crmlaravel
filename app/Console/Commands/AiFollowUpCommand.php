<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Tenant\AiConfigurationController;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\AiAgentService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AiFollowUpCommand extends Command
{
    protected $signature   = 'ai:followup';
    protected $description = 'Envia mensagens de follow-up automático para conversas com clientes silenciosos';

    public function handle(): void
    {
        $provider = (string) config('ai.provider', 'openai');
        $apiKey   = (string) config('ai.api_key', '');
        $model    = (string) config('ai.model', 'gpt-4o-mini');
        $service  = new AiAgentService();

        if ($apiKey === '') {
            $this->warn('LLM_API_KEY não configurado — follow-up abortado.');
            return;
        }

        // Carregar todas as conversas abertas com agente de IA + follow-up ativado
        $conversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->with(['aiAgent'])
            ->where('status', 'open')
            ->where('is_group', false)
            ->whereNotNull('ai_agent_id')
            ->whereHas('aiAgent', fn ($q) => $q
                ->where('is_active', true)
                ->where('followup_enabled', true)
            )
            ->get();

        $this->info("Conversas candidatas: {$conversations->count()}");

        foreach ($conversations as $conv) {
            $agent = $conv->aiAgent;

            // Filtro de limite de tentativas (em PHP — evita JOIN cross-column)
            if ($conv->followup_count >= ($agent->followup_max_count ?? 3)) {
                continue;
            }

            // ── Verificar horário comercial ───────────────────────────────────
            $tz        = config('app.timezone', 'America/Sao_Paulo');
            $hourNow   = (int) Carbon::now($tz)->format('G');
            $hourStart = $agent->followup_hour_start ?? 8;
            $hourEnd   = $agent->followup_hour_end   ?? 18;

            if ($hourNow < $hourStart || $hourNow >= $hourEnd) {
                continue; // Fora do horário comercial
            }

            // ── Verificar janela de tempo ─────────────────────────────────────
            $delayMinutes  = max(5, $agent->followup_delay_minutes ?? 40);
            $cutoff        = now()->subMinutes($delayMinutes);
            $lastMessageAt = $conv->last_message_at;

            if (! $lastMessageAt || $lastMessageAt->gt($cutoff)) {
                continue; // Ainda dentro da janela de espera
            }

            // Respeitar intervalo entre follow-ups consecutivos
            $lastFollowupAt = $conv->last_followup_at;
            if ($lastFollowupAt && $lastFollowupAt->gt($cutoff)) {
                continue;
            }

            // ── Última mensagem deve ser outbound (IA aguarda resposta do cliente) ─
            $lastMsg = WhatsappMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conv->id)
                ->where('is_deleted', false)
                ->orderByDesc('sent_at')
                ->first();

            if (! $lastMsg || $lastMsg->direction !== 'outbound') {
                continue; // Último a falar foi o cliente — nenhuma ação necessária
            }

            // ── Chamar LLM: classificar + gerar follow-up em 1 chamada ──────
            $history = $service->buildHistory($conv, limit: 20);
            if (empty($history)) {
                continue;
            }

            $system = $this->buildFollowUpPrompt($agent);

            try {
                $raw = AiConfigurationController::callLlm(
                    provider:  $provider,
                    apiKey:    $apiKey,
                    model:     $model,
                    messages:  $history,
                    maxTokens: 300,
                    system:    $system,
                );
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('AI follow-up: LLM falhou', [
                    'conversation_id' => $conv->id,
                    'error'           => $e->getMessage(),
                ]);
                continue;
            }

            // ── Parsear JSON ──────────────────────────────────────────────────
            $clean   = trim((string) preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', $raw));
            $decoded = str_starts_with($clean, '{') ? json_decode($clean, true) : null;

            if (! is_array($decoded) || ! isset($decoded['status'])) {
                Log::channel('whatsapp')->warning('AI follow-up: resposta JSON inválida', [
                    'conversation_id' => $conv->id,
                    'raw'             => mb_substr($raw, 0, 300),
                ]);
                continue;
            }

            if ($decoded['status'] === 'finished') {
                Log::channel('whatsapp')->info('AI follow-up: conversa finalizada — sem ação', [
                    'conversation_id' => $conv->id,
                ]);
                continue;
            }

            $followupText = trim((string) ($decoded['followup_message'] ?? ''));
            if ($followupText === '') {
                continue;
            }

            // ── Enviar e atualizar contadores ─────────────────────────────────
            $service->sendWhatsappReply($conv, $followupText);

            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update([
                    'followup_count'   => $conv->followup_count + 1,
                    'last_followup_at' => now(),
                ]);

            Log::channel('whatsapp')->info('AI follow-up: enviado', [
                'conversation_id' => $conv->id,
                'attempt'         => $conv->followup_count + 1,
                'max'             => $agent->followup_max_count,
            ]);

            $this->line("  ✓ Conv #{$conv->id} — follow-up " . ($conv->followup_count + 1) . "/{$agent->followup_max_count}");
        }

        $this->info('Follow-up concluído.');
    }

    private function buildFollowUpPrompt(\App\Models\AiAgent $agent): string
    {
        $tz       = config('app.timezone', 'America/Sao_Paulo');
        $now      = Carbon::now($tz);
        $weekdays = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        $dateStr  = $now->format('d/m/Y') . ' (' . $weekdays[$now->dayOfWeek] . ') — ' . $now->format('H:i');

        $style = match ($agent->communication_style) {
            'formal' => 'formal e profissional',
            'casual' => 'descontraído e amigável',
            default  => 'natural e cordial',
        };

        $agentId = $agent->name . ($agent->company_name ? " da {$agent->company_name}" : '');

        $lines = [
            "Data e hora atual: {$dateStr}.",
            "Você é {$agentId}.",
            '',
            'Analise o histórico desta conversa e responda APENAS com JSON válido (sem markdown):',
            '{',
            '  "status": "finished" | "waiting",',
            '  "followup_message": "mensagem de follow-up (null se status=finished)"',
            '}',
            '',
            'Critérios de classificação:',
            '- "finished": conversa encerrou naturalmente (cliente despediu, problema resolvido, cliente disse que não precisa mais de ajuda)',
            '- "waiting": conversa estava em andamento mas o cliente parou de responder sem conclusão clara',
            '',
            'Se "waiting": escreva uma mensagem curta e natural para retomar o contato.',
            "Idioma: {$agent->language}. Estilo: {$style}.",
            'Não mencione que é um bot, que está fazendo follow-up automático ou que notou ausência.',
        ];

        return implode("\n", $lines);
    }
}
