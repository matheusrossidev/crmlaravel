<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\WhatsappConversationUpdated;
use App\Events\WhatsappMessageCreated;
use App\Models\AiAgent;
use App\Models\AiAgentMedia;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiAgentService
{
    /**
     * Constrói o system prompt a partir das configurações do agente.
     *
     * @param array $stages     Ex: [['id'=>1,'name'=>'Novo Lead','current'=>true], ...]
     * @param array $availTags  Ex: ['interessado','vip','retorno']
     */
    public function buildSystemPrompt(
        AiAgent $agent,
        array   $stages             = [],
        array   $availTags          = [],
        bool    $enableIntentNotify = false,
        array   $calendarEvents     = [],
        ?Lead   $lead               = null,
        ?\App\Models\WhatsappConversation $conv = null,
    ): string {
        $objective = match ($agent->objective) {
            'sales'   => 'vendas',
            'support' => 'suporte ao cliente',
            default   => 'atendimento geral',
        };

        $style = match ($agent->communication_style) {
            'formal'  => 'formal e profissional',
            'casual'  => 'descontraído e amigável',
            default   => 'natural e cordial',
        };

        // Data/hora atual no fuso do servidor — essencial para saudações corretas
        $now     = \Carbon\Carbon::now(config('app.timezone', 'America/Sao_Paulo'));
        $weekdays = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
        $dayName  = $weekdays[$now->dayOfWeek];
        $dateStr  = $now->format('d/m/Y') . ' (' . $dayName . ') — ' . $now->format('H:i');

        $lines = [
            "Data e hora atual: {$dateStr}.",
            "Você é {$agent->name}, um assistente virtual de {$objective}.",
        ];

        if ($agent->company_name) $lines[] = "Você representa a empresa: {$agent->company_name}.";
        if ($agent->industry)     $lines[] = "Setor/indústria: {$agent->industry}.";
        $lines[] = "Idioma de resposta: {$agent->language}.";
        $lines[] = "Estilo de comunicação: {$style}.";

        if ($agent->persona_description) $lines[] = "\nPerfil do atendente:\n{$agent->persona_description}";
        if ($agent->behavior)            $lines[] = "\nComportamento esperado:\n{$agent->behavior}";

        // ── Diretrizes de humanização baseadas no estilo ─────────────────────
        $lines[] = "\nDIRETRIZES DE HUMANIZAÇÃO:";
        if ($agent->communication_style === 'casual') {
            $lines[] = "- Use linguagem descontraída, mas sem exagerar em gírias.";
            $lines[] = "- Varie as saudações (ex: 'Oi!', 'Olá!', 'E aí?', 'Tudo bem?') — NUNCA repita a mesma saudação duas vezes seguidas.";
            $lines[] = "- Use emojis com moderação para expressar empatia e simpatia.";
            $lines[] = "- Demonstre entusiasmo genuíno com o cliente.";
        } elseif ($agent->communication_style === 'formal') {
            $lines[] = "- Mantenha tom profissional e respeitoso em todas as mensagens.";
            $lines[] = "- Varie as formas de tratamento (ex: 'Bom dia', 'Boa tarde', 'Como posso ajudá-lo(a)?').";
            $lines[] = "- Evite abreviações e informalidades.";
            $lines[] = "- Expresse cuidado e atenção de forma elegante (ex: 'Compreendo sua situação', 'Fico à disposição').";
        } else {
            $lines[] = "- Use tom natural e cordial, equilibrando proximidade e profissionalismo.";
            $lines[] = "- Varie as saudações e formas de iniciar frases — evite padrões repetitivos.";
            $lines[] = "- Demonstre empatia quando o cliente expressar dúvidas ou dificuldades.";
        }
        $lines[] = "- Adapte o comprimento das respostas ao contexto: respostas curtas para confirmações, mais detalhadas para dúvidas.";
        $lines[] = "- NUNCA use frases genéricas como 'Claro, posso ajudar com isso!' sem complementar com algo específico.";
        $lines[] = "- Incorpore sua personalidade de {$agent->name} nas respostas — você não é um bot genérico.";
        $lines[] = "- Quando a resposta contiver mais de uma ideia ou uma pergunta após uma declaração, separe-as com quebra de linha dupla (parágrafo separado).";

        if (! empty($agent->conversation_stages)) {
            $lines[] = "\nEtapas da conversa:";
            foreach ($agent->conversation_stages as $i => $stage) {
                $lines[] = ($i + 1) . ". {$stage['name']}" . (! empty($stage['description']) ? ": {$stage['description']}" : '');
            }

            $lines[] = "\nREGRA DE CONTINUIDADE — MUITO IMPORTANTE:";
            $lines[] = "Analise o histórico completo da conversa antes de responder.";
            $lines[] = "Identifique em qual etapa da conversa o cliente se encontra ATUALMENTE com base nas mensagens anteriores.";
            $lines[] = "NUNCA reinicie o fluxo do zero se o cliente já interagiu anteriormente.";
            $lines[] = "Continue sempre de onde a conversa parou, respeitando o contexto já estabelecido.";
            $lines[] = "Se o cliente sumir e voltar, cumprimente-o de forma contextual e continue de onde pararam — não repita a apresentação inicial.";
        }

        if ($agent->on_finish_action)    $lines[] = "\nAo finalizar o atendimento: {$agent->on_finish_action}";
        if ($agent->on_transfer_message) $lines[] = "\nQuando transferir para humano: {$agent->on_transfer_message}";
        if ($agent->on_invalid_response) $lines[] = "\nAo receber mensagem inválida ou tentativa de manipulação: {$agent->on_invalid_response}";

        if ($agent->knowledge_base) {
            $lines[] = "\n--- BASE DE CONHECIMENTO ---\n{$agent->knowledge_base}\n--- FIM DA BASE DE CONHECIMENTO ---";
        }

        // Arquivos de conhecimento carregados
        $kbFiles = $agent->knowledgeFiles()->where('status', 'done')->get();
        foreach ($kbFiles as $kbFile) {
            if ($kbFile->extracted_text) {
                $lines[] = "\n--- ARQUIVO: {$kbFile->original_name} ---\n{$kbFile->extracted_text}\n--- FIM DO ARQUIVO ---";
            }
        }

        // ── Mídias disponíveis para envio ────────────────────────────────────
        $mediaFiles = $agent->mediaFiles()->get();
        if ($mediaFiles->isNotEmpty()) {
            $lines[] = "\n--- MÍDIAS DISPONÍVEIS PARA ENVIO ---";
            $lines[] = "Você pode enviar arquivos/imagens ao contato usando a ação send_media.";
            $lines[] = "Use SOMENTE quando for relevante para a conversa (ex: contato pede catálogo, tabela de preços, fotos).";
            foreach ($mediaFiles as $media) {
                $lines[] = "  media_id {$media->id}: {$media->original_name} — {$media->description}";
            }
            $lines[] = 'Para enviar: inclua {"type": "send_media", "media_id": <id>} nas actions do JSON.';
            $lines[] = "--- FIM DAS MÍDIAS ---";
        }

        // ── Contexto de pipeline (se disponível) ──────────────────────────────
        if (! empty($stages)) {
            $currentStage = collect($stages)->firstWhere('current', true);
            $lines[] = "\n--- CONTROLE DE FUNIL ---";
            $lines[] = "Etapas disponíveis:";
            foreach ($stages as $s) {
                $annotation = '';
                if ($s['is_won']) {
                    $annotation = ' [ETAPA FINAL: GANHO — use SOMENTE quando o cliente confirmar explicitamente que quer contratar ou comprar]';
                } elseif ($s['is_lost']) {
                    $annotation = ' [ETAPA FINAL: PERDIDO — use SOMENTE quando o cliente recusar explicitamente o serviço ou demonstrar total desinteresse]';
                }
                $lines[] = "  {$s['id']}: {$s['name']}{$annotation}";
            }
            if ($currentStage) {
                $lines[] = "Etapa atual do lead: {$currentStage['name']}";
            }
            $lines[] = "REGRAS PARA MUDANÇA DE ETAPA:";
            $lines[] = "- Avance etapas gradualmente conforme a conversa evolui.";
            $lines[] = "- Mova para GANHO SOMENTE se o cliente confirmar explicitamente que deseja contratar/comprar.";
            $lines[] = "- Mova para PERDIDO SOMENTE se o cliente recusar o serviço de forma explícita.";
            $lines[] = "- Se o cliente demonstrar INTERESSE em contratar → avance para a próxima etapa intermediária, NUNCA para PERDIDO.";
            $lines[] = "- Em caso de dúvida sobre qual etapa usar → mantenha a etapa atual (actions: []).";

            // Calcular próxima etapa intermediária sugerida
            $currentIdx = array_search(true, array_column($stages, 'current'));
            $nextStage  = ($currentIdx !== false
                           && isset($stages[$currentIdx + 1])
                           && ! $stages[$currentIdx + 1]['is_won']
                           && ! $stages[$currentIdx + 1]['is_lost'])
                ? $stages[$currentIdx + 1]
                : null;
            if ($nextStage) {
                $lines[] = "PRÓXIMA ETAPA SUGERIDA (se o cliente demonstrar interesse/avançar): "
                         . "{$nextStage['name']} (use stage_id: {$nextStage['id']})";
            }
            $lines[] = "IMPORTANTE: use apenas os stage_ids listados acima. Se em dúvida, NÃO inclua set_stage nas actions.";
            $lines[] = "--- FIM DO CONTROLE DE FUNIL ---";
        }

        // ── Contexto de tags (se disponível) ──────────────────────────────────
        if (! empty($availTags)) {
            $tagList = implode(', ', $availTags);
            $lines[] = "\n--- TAGS DISPONÍVEIS ---";
            $lines[] = "Tags existentes: {$tagList}";
            $lines[] = "Você pode adicionar tags ao lead conforme o contexto da conversa.";
            $lines[] = "--- FIM DAS TAGS ---";
        }

        // ── Dados atuais do lead + ferramenta update_lead ───────────────────
        if ($lead) {
            $lines[] = "\n--- DADOS DO LEAD (CADASTRO ATUAL) ---";
            $lines[] = "Nome: " . ($lead->name ?: '(vazio)');
            $lines[] = "Telefone: " . ($lead->phone ?: '(vazio)');
            $lines[] = "E-mail: " . ($lead->email ?: '(vazio)');
            $lines[] = "Empresa: " . ($lead->company ?: '(vazio)');
            $lines[] = "Data de nascimento: " . ($lead->birthday ? $lead->birthday->format('d/m/Y') : '(vazio)');
            $lines[] = "Valor do lead: " . ($lead->value ? 'R$ ' . number_format((float) $lead->value, 2, ',', '.') : '(vazio)');
            $lines[] = "--- FIM DOS DADOS DO LEAD ---";

            // ── Campos personalizados do lead ────────────────────────────────
            $customFields = CustomFieldDefinition::where('tenant_id', $lead->tenant_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            if ($customFields->isNotEmpty()) {
                $lines[] = "\n--- CAMPOS PERSONALIZADOS DO LEAD ---";
                foreach ($customFields as $cf) {
                    $cfv = CustomFieldValue::where('lead_id', $lead->id)
                        ->where('field_id', $cf->id)->first();
                    $currentVal = $this->formatCustomFieldValue($cfv, $cf);
                    $typeHint = match ($cf->field_type) {
                        'number'      => '(número)',
                        'currency'    => '(valor em R$)',
                        'date'        => '(data: YYYY-MM-DD)',
                        'checkbox'    => '(true/false)',
                        'multiselect' => '(opções: ' . implode(', ', $cf->options_json ?? []) . ')',
                        default       => '(texto)',
                    };
                    $lines[] = "- {$cf->label} [{$cf->name}] {$typeHint}: {$currentVal}";
                }
                $lines[] = "Para preencher, use: {\"type\": \"update_custom_field\", \"field\": \"nome_do_campo\", \"value\": \"valor\"}";
                $lines[] = "Para multiselect: {\"type\": \"update_custom_field\", \"field\": \"campo\", \"value\": [\"opcao1\", \"opcao2\"]}";
                $lines[] = "--- FIM DOS CAMPOS PERSONALIZADOS ---";
            }

            // ── Notas existentes do lead ─────────────────────────────────────
            $notes = LeadNote::where('lead_id', $lead->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            if ($notes->isNotEmpty()) {
                $lines[] = "\n--- NOTAS DO LEAD (últimas 5) ---";
                foreach ($notes as $note) {
                    $author = $note->created_by ? ($note->creator->name ?? 'Usuário') : 'IA';
                    $date   = $note->created_at?->format('d/m H:i') ?? '';
                    $lines[] = "- [{$date}] ({$author}): " . Str::limit($note->body, 150);
                }
                $lines[] = "--- FIM DAS NOTAS ---";
            }
        }

        $lines[] = <<<'UPDLEAD'

--- ATUALIZAÇÃO DE DADOS DO LEAD ---
Sempre que o contato mencionar NATURALMENTE durante a conversa informações pessoais como nome completo, e-mail, empresa, data de nascimento ou valor de negócio, ATUALIZE o cadastro usando a ação update_lead.
Campos permitidos: name, email, company, birthday, value
Para birthday use YYYY-MM-DD. Para value use número decimal (ex: 1500.00).
NÃO pergunte dados sem contexto — colete apenas o que surgir naturalmente na conversa ou que for relevante para o atendimento.
Se o campo já estiver preenchido com o mesmo valor, NÃO emita update_lead.
Exemplo: {"type": "update_lead", "field": "email", "value": "joao@empresa.com"}
Exemplo: {"type": "update_lead", "field": "value", "value": "2500.00"}
--- FIM DA ATUALIZAÇÃO DE DADOS ---
UPDLEAD;

        $lines[] = <<<'NOTES'

--- CRIAR NOTAS ---
Você pode criar notas sobre o lead para registrar observações importantes da conversa.
Use create_note quando identificar: preferências, objeções, decisões, follow-ups necessários, informações relevantes para o time de vendas.
NÃO crie notas para cada mensagem — apenas quando houver informação estratégica relevante.
Exemplo: {"type": "create_note", "body": "Cliente interessado no plano Premium, pediu proposta por email. Objeção: preço alto."}
--- FIM DAS NOTAS ---
NOTES;

        $lines[] = "\nResponda sempre em {$agent->language}. Seja conciso (máximo {$agent->max_message_length} caracteres por mensagem).";

        // ── Ferramenta de Agenda (Google Calendar) ────────────────────────────
        if ($agent->enable_calendar_tool) {
            $lines[] = "\n--- FERRAMENTA DE AGENDA (Google Calendar) ---";

            if ($agent->calendar_tool_instructions) {
                $lines[] = "Instruções específicas:\n{$agent->calendar_tool_instructions}";
            }

            // Contexto do contato — pre-preenchido pra evitar que o LLM precise
            // perguntar dados que ja temos. CRITICO: o telefone aparece aqui pra
            // o agente saber EXATAMENTE qual numero usar no description do evento.
            // Sem isso, o vendedor que abre o Google Calendar nao tem como ligar
            // pro cliente direto pelo evento.
            $contactCtx = [];
            $contactPhone = $lead?->phone ?? $conv?->phone ?? null;
            $contactName  = $lead?->name  ?? $conv?->contact_name ?? null;
            $contactEmail = $lead?->email ?? null;

            if ($contactName) {
                $contactCtx[] = "Nome: {$contactName}";
            }
            if ($contactPhone) {
                $contactCtx[] = "Telefone: {$contactPhone} ← USE ESTE no campo description do calendar_create (obrigatorio)";
            } else {
                $contactCtx[] = "Telefone: nao disponivel (canal Instagram/Web) → omita a linha Telefone do description";
            }
            if ($contactEmail) {
                $contactCtx[] = "E-mail: {$contactEmail} ← USE ESTE para attendees do convite (nao pergunte novamente)";
            } else {
                $contactCtx[] = "E-mail: nao cadastrado → PERGUNTE ao contato antes de criar o evento";
            }
            if ($lead?->company) {
                $contactCtx[] = "Empresa: {$lead->company}";
            }

            $lines[] = "Dados do contato desta conversa (use TAL E QUAL no description do evento): " . implode(' | ', $contactCtx);

            if (! empty($calendarEvents)) {
                $lines[] = "\nCompromissos agendados (próximos 7 dias):";
                foreach ($calendarEvents as $ev) {
                    $start = $ev['start'] ?? '';
                    $end   = $ev['end']   ?? '';
                    $title = $ev['title'] ?? 'Sem título';
                    $loc   = $ev['location'] ?? '';
                    $line  = "- [{$start} → {$end}] {$title}";
                    if ($loc) $line .= " | Local: {$loc}";
                    if (! empty($ev['id'])) $line .= " (id: {$ev['id']})";
                    $lines[] = $line;
                }
            } else {
                $lines[] = "\nNenhum compromisso agendado nos próximos 7 dias.";
            }

            $lines[] = <<<'CALINSTR'

FERRAMENTA DE AGENDA — FLUXO EM 3 PASSOS OBRIGATÓRIOS:

PASSO 1 — VERIFICAR DISPONIBILIDADE:
Quando o usuário pedir para marcar em data/hora específica ou perguntar disponibilidade:
• Responda algo curto como "Vou verificar a agenda agora!" ou "Um momento, deixa eu checar!"
• Inclua na action: check_calendar_availability com start e end do horário solicitado
• O sistema executará a verificação e retornará o resultado

PASSO 2 — INFORMAR E PEDIR CONFIRMAÇÃO (OBRIGATÓRIO):
Após receber o resultado do sistema:
• Se DISPONÍVEL: informe o horário disponível e PERGUNTE se o usuário confirma o agendamento
  Exemplo: "O horário das 15h de amanhã está disponível! Confirma o agendamento?"
• Se INDISPONÍVEL: informe o conflito e sugira outros horários
• NUNCA emita calendar_create neste passo — aguarde a confirmação explícita do usuário
• check_calendar_availability e calendar_create JAMAIS aparecem no mesmo JSON

PASSO 3 — CRIAR O EVENTO (só após confirmação explícita):
Somente quando o usuário disser "sim", "confirma", "pode marcar", "vai lá" ou similar:
• Colete o e-mail do convidado se ainda não tiver (é OBRIGATÓRIO)
• Inclua na action: calendar_create com todos os campos
• Aguarde o resultado do sistema e confirme ao usuário

EXEMPLO DO FLUXO COMPLETO:
→ Usuário: "quero marcar amanhã às 15h"
→ Agente: {"reply": "Vou verificar a agenda agora!", "actions": [{"type":"check_calendar_availability","start":"YYYY-MM-DDT15:00","end":"YYYY-MM-DDT16:00"}]}
→ Sistema retorna: "[RESULTADO DAS FERRAMENTAS]: Horário disponível: ..."
→ Agente: {"reply": "Amanhã às 15h está disponível! Confirma o agendamento?", "actions": []}
→ Usuário: "sim, confirma"
→ Agente: {"reply": "Ótimo! Qual o seu e-mail para o convite?", "actions": []}
→ Usuário: "fulano@empresa.com"
→ Agente: {"reply": "Agendando agora!", "actions": [{"type":"calendar_create","title":"Visita cliente Ana Costa","start":"YYYY-MM-DDT15:00","end":"YYYY-MM-DDT16:00","description":"Motivo / O que foi combinado:\nCliente quer medir a sala e o quarto para instalação de cortinas blackout.\n\nCliente:\nNome: Ana Costa\nTelefone: 11912345678\n\nLocal / Endereço:\nRua das Flores, 123 – Ap. 42, São Paulo\n\nObservações:\nPreferência por cores neutras. Orçamento flexível.","attendees":"fulano@empresa.com"}]}
→ Sistema retorna: "[RESULTADO DAS FERRAMENTAS]: Evento criado com sucesso..."
→ Agente: {"reply": "Visita marcada para amanhã às 15h! Convite enviado para fulano@empresa.com.", "actions": []}

REGRAS ABSOLUTAS:
- NUNCA emita calendar_create sem confirmação explícita do usuário nesta mesma conversa
- check_calendar_availability e calendar_create NUNCA podem estar no mesmo JSON de actions
- O e-mail do convidado é OBRIGATÓRIO para calendar_create — pergunte se não tiver
- Duração padrão: 1 hora (end = start + 1h) se não informada
- Nunca confirme a criação antes de receber o resultado do sistema
- Use os ids EXATOS dos eventos listados acima ao reagendar/cancelar

TÍTULO DO EVENTO (obrigatório — nunca genérico):
O título deve refletir o contexto real da conversa e o tipo de negócio do agente.
Formato: [Tipo da atividade] [Nome do cliente]
Exemplos por tipo de negócio:
- Serviço presencial (loja, instalação, reparo) → "Visita cliente João Silva" | "Medição – Ana Costa" | "Orçamento – Família Souza"
- Agência / consultoria → "Reunião de briefing – Empresa X" | "Apresentação proposta – João" | "Alinhamento campanha – Loja Y"
- Saúde / bem-estar → "Consulta – Ana Costa" | "Avaliação – João Silva"
- Padrão geral → "[objetivo da conversa] – [nome do cliente]"
Use sempre o nome do cliente no título quando disponível.
NUNCA use apenas "Reunião", "Agendamento" ou "Evento" sem contexto adicional.

DESCRIÇÃO DO EVENTO (obrigatório — resumo útil para quem consultar depois):
O vendedor vai abrir esse evento no Google Calendar pra ligar pro cliente.
O TELEFONE PRECISA estar no description — sem isso o evento e inutil.

Use este formato exato:

Motivo / O que foi combinado:
[Resumo em 1-3 frases do objetivo do encontro e o que foi discutido]

Cliente:
Nome: [nome — SEMPRE]
Telefone: [telefone — OBRIGATORIO sempre que disponivel nos "Dados do contato desta conversa" acima. Copie o numero TAL E QUAL apareceu la, sem reformatar]
[Email: ... se disponivel]
[Empresa: ... se disponivel]

Local / Endereço:
[Se mencionado na conversa — omitir esta secao se nao houver]

Observações:
[Preferencias, detalhes relevantes ou contexto mencionado na conversa — omitir se nao houver]

REGRAS DO TELEFONE NO DESCRIPTION:
- Se "Dados do contato desta conversa" mostra "Telefone: <numero>", VOCE OBRIGATORIAMENTE inclui essa linha "Telefone: <numero>" no bloco "Cliente:"
- So pode omitir a linha Telefone se "Dados do contato desta conversa" disser "Telefone: nao disponivel" (caso de Instagram/Web sem numero)
- Nunca invente nem reformate o numero — copie igual ao que esta nos dados do contato
- O bloco "Cliente:" e obrigatorio mesmo se voce ja mencionar dados em outras secoes

Não invente dados. Extraia apenas do que foi conversado ou dos "Dados do contato desta conversa" acima.

SCHEMAS:
- check_calendar_availability: {"type":"check_calendar_availability","start":"YYYY-MM-DDTHH:MM","end":"YYYY-MM-DDTHH:MM"}
- calendar_create: {"type":"calendar_create","title":"...","start":"YYYY-MM-DDTHH:MM","end":"YYYY-MM-DDTHH:MM","description":"...","location":"...","attendees":"email@dominio.com"}
- calendar_reschedule: {"type":"calendar_reschedule","event_id":"id_exato","start":"YYYY-MM-DDTHH:MM","end":"YYYY-MM-DDTHH:MM"}
- calendar_cancel: {"type":"calendar_cancel","event_id":"id_exato"}
--- FIM DA FERRAMENTA DE AGENDA ---
CALINSTR;
        }

        // ── Detecção de intenção de compra/agendamento ────────────────────────
        if ($enableIntentNotify) {
            $lines[] = <<<'INTENTINSTR'

--- DETECÇÃO DE INTENÇÃO ---
Quando o contato demonstrar intenção CLARA e EXPLÍCITA de:
- Comprar, contratar ou adquirir o produto/serviço → intent: "buy"
- Agendar reunião, demonstração, visita ou ligação → intent: "schedule"
- Fechar negócio ou confirmar contratação → intent: "close"
Use a ação: {"type": "notify_intent", "intent": "buy|schedule|close", "context": "resumo em 1 frase do que o cliente disse"}
NÃO use notify_intent para interesse vago ou curiosidade — apenas intenção clara e explícita.
--- FIM DA DETECÇÃO DE INTENÇÃO ---
INTENTINSTR;
        }

        // ── Formato JSON obrigatório quando há pipeline, tags, intent ou calendar ─
        if (! empty($stages) || ! empty($availTags) || $enableIntentNotify || $agent->enable_calendar_tool) {
            $intentExample = $enableIntentNotify
                ? "\n    {\"type\": \"notify_intent\", \"intent\": \"buy\", \"context\": \"cliente confirmou interesse em contratar\"},"
                : '';
            $calendarExample = $agent->enable_calendar_tool
                ? "\n    {\"type\": \"calendar_create\", \"title\": \"Reunião\", \"start\": \"YYYY-MM-DDTHH:MM\", \"end\": \"YYYY-MM-DDTHH:MM\", \"attendees\": \"email@dominio.com\"},"
                : '';
            $calendarActions = $agent->enable_calendar_tool
                ? "\n- calendar_create / calendar_reschedule / calendar_cancel / calendar_list: ações de agenda (ver instruções acima)."
                : '';
            $lines[] = <<<JSONINSTR

FORMATO DE RESPOSTA OBRIGATÓRIO — responda APENAS com JSON válido, sem markdown:
{
  "reply": "sua resposta — ou array [\"bloco 1\", \"bloco 2\"] para dividir em mensagens distintas",
  "actions": [
    {"type": "set_stage", "stage_id": <id_numérico>},
    {"type": "add_tags", "tags": ["tag1", "tag2"]},
    {"type": "update_lead", "field": "email", "value": "joao@email.com"},
    {"type": "create_note", "body": "Observação relevante sobre o lead"},
    {"type": "update_custom_field", "field": "nome_do_campo", "value": "valor"},$intentExample$calendarExample
    {"type": "assign_human"}
  ]
}
Se não precisar de ações, use "actions": [].
NUNCA inclua texto fora do JSON.
Ações disponíveis:
- set_stage: mova o lead para uma etapa do funil (use o stage_id correto da lista acima).
- add_tags: adicione tags à conversa/lead.
- update_lead: atualize dados do cadastro do lead (name, email, company, birthday, value). Ex: {"type":"update_lead","field":"value","value":"2500.00"}
- create_note: registre observações estratégicas sobre o lead (preferências, objeções, decisões). NÃO crie nota para cada mensagem — apenas informações relevantes para o time.
- update_custom_field: preencha campos personalizados do lead. Use o nome do campo (field) e valor (value). Para multiselect: {"type":"update_custom_field","field":"interesses","value":["opcao1","opcao2"]}
- assign_human: use quando o cliente pedir explicitamente para falar com uma pessoa ou quando você não conseguir responder. Inclua essa action junto com a resposta de transferência.$calendarActions
JSONINSTR;
        }

        $lines[] = <<<'WAFMT'

REGRAS DE FORMATAÇÃO PARA WHATSAPP — OBRIGATÓRIO:
- NUNCA use markdown: proibido **negrito**, __sublinhado__, ## títulos, ``` código.
- NUNCA use listas com "-" ou "•" seguidas de múltiplos itens — prefira frases corridas.
- Se precisar de destaque, escreva em MAIÚSCULAS (ex: IMPORTANTE: ...).
- Separe cada ideia/bloco em uma mensagem diferente usando LINHA DUPLA (\n\n).
- Cada bloco deve ter entre 100 e 400 caracteres — evite mensagens muito longas.
- Escreva como uma pessoa real digitando no WhatsApp: frases curtas e naturais.
- Se a resposta tiver 2 ou mais ideias distintas, quebre em blocos com linha dupla.
WAFMT;

        return implode("\n", $lines);
    }

    /**
     * Constrói o system prompt para canal Web Chat.
     * Reutiliza o core do buildSystemPrompt mas com formato JSON rico (botões, cards).
     */
    public function buildWebChatSystemPrompt(
        AiAgent $agent,
        array   $stages             = [],
        array   $availTags          = [],
        bool    $enableIntentNotify = false,
        ?Lead   $lead               = null,
    ): string {
        $objective = match ($agent->objective) {
            'sales'   => 'vendas',
            'support' => 'suporte ao cliente',
            default   => 'atendimento geral',
        };

        $style = match ($agent->communication_style) {
            'formal'  => 'formal e profissional',
            'casual'  => 'descontraído e amigável',
            default   => 'natural e cordial',
        };

        $now     = \Carbon\Carbon::now(config('app.timezone', 'America/Sao_Paulo'));
        $weekdays = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
        $dayName  = $weekdays[$now->dayOfWeek];
        $dateStr  = $now->format('d/m/Y') . ' (' . $dayName . ') — ' . $now->format('H:i');

        $lines = [
            "Data e hora atual: {$dateStr}.",
            "Você é {$agent->name}, um assistente virtual de {$objective}.",
        ];

        if ($agent->company_name) $lines[] = "Você representa a empresa: {$agent->company_name}.";
        if ($agent->industry)     $lines[] = "Setor/indústria: {$agent->industry}.";
        $lines[] = "Idioma de resposta: {$agent->language}.";
        $lines[] = "Estilo de comunicação: {$style}.";

        if ($agent->persona_description) $lines[] = "\nPerfil do atendente:\n{$agent->persona_description}";
        if ($agent->behavior)            $lines[] = "\nComportamento esperado:\n{$agent->behavior}";

        // Humanização (mesmo código do WhatsApp)
        $lines[] = "\nDIRETRIZES DE HUMANIZAÇÃO:";
        if ($agent->communication_style === 'casual') {
            $lines[] = "- Use linguagem descontraída, mas sem exagerar em gírias.";
            $lines[] = "- Varie as saudações — NUNCA repita a mesma saudação duas vezes seguidas.";
            $lines[] = "- Demonstre entusiasmo genuíno com o visitante.";
        } elseif ($agent->communication_style === 'formal') {
            $lines[] = "- Mantenha tom profissional e respeitoso.";
            $lines[] = "- Varie as formas de tratamento.";
            $lines[] = "- Evite abreviações e informalidades.";
        } else {
            $lines[] = "- Use tom natural e cordial, equilibrando proximidade e profissionalismo.";
            $lines[] = "- Varie as saudações e formas de iniciar frases.";
        }
        $lines[] = "- Adapte o comprimento das respostas ao contexto.";
        $lines[] = "- Incorpore sua personalidade de {$agent->name} nas respostas.";

        // Etapas da conversa
        if (! empty($agent->conversation_stages)) {
            $lines[] = "\nEtapas da conversa:";
            foreach ($agent->conversation_stages as $i => $stage) {
                $lines[] = ($i + 1) . ". {$stage['name']}" . (! empty($stage['description']) ? ": {$stage['description']}" : '');
            }
            $lines[] = "\nREGRA DE CONTINUIDADE: Analise o histórico e continue de onde a conversa parou. NUNCA reinicie o fluxo.";
        }

        if ($agent->on_finish_action)    $lines[] = "\nAo finalizar o atendimento: {$agent->on_finish_action}";
        if ($agent->on_transfer_message) $lines[] = "\nQuando transferir para humano: {$agent->on_transfer_message}";

        // Base de conhecimento
        if ($agent->knowledge_base) {
            $lines[] = "\n--- BASE DE CONHECIMENTO ---\n{$agent->knowledge_base}\n--- FIM DA BASE DE CONHECIMENTO ---";
        }

        // Arquivos de conhecimento
        $kbFiles = $agent->knowledgeFiles()->where('status', 'done')->get();
        foreach ($kbFiles as $kbFile) {
            if ($kbFile->extracted_text) {
                $lines[] = "\n--- ARQUIVO: {$kbFile->original_name} ---\n{$kbFile->extracted_text}\n--- FIM DO ARQUIVO ---";
            }
        }

        // Mídias — fornecer URLs públicas para uso em cards
        $mediaFiles = $agent->mediaFiles()->get();
        if ($mediaFiles->isNotEmpty()) {
            $lines[] = "\n--- MÍDIAS DISPONÍVEIS (para usar em cards) ---";
            $lines[] = "Inclua a URL no campo image_url de um card quando relevante.";
            foreach ($mediaFiles as $media) {
                $url = Storage::disk('public')->url($media->storage_path);
                $lines[] = "  {$media->original_name} — {$media->description} → URL: {$url}";
            }
            $lines[] = "--- FIM DAS MÍDIAS ---";
        }

        // Pipeline
        if (! empty($stages)) {
            $currentStage = collect($stages)->firstWhere('current', true);
            $lines[] = "\n--- CONTROLE DE FUNIL ---";
            $lines[] = "Etapas disponíveis:";
            foreach ($stages as $s) {
                $annotation = $s['is_won'] ? ' [GANHO]' : ($s['is_lost'] ? ' [PERDIDO]' : '');
                $lines[] = "  {$s['id']}: {$s['name']}{$annotation}";
            }
            if ($currentStage) {
                $lines[] = "Etapa atual: {$currentStage['name']}";
            }
            $lines[] = "--- FIM DO CONTROLE DE FUNIL ---";
        }

        // Tags
        if (! empty($availTags)) {
            $lines[] = "\n--- TAGS DISPONÍVEIS ---";
            $lines[] = implode(', ', $availTags);
            $lines[] = "--- FIM DAS TAGS ---";
        }

        // Detecção de intenção
        if ($enableIntentNotify) {
            $lines[] = "\n--- DETECÇÃO DE INTENÇÃO ---";
            $lines[] = "Quando o visitante demonstrar intenção CLARA de comprar/agendar/fechar:";
            $lines[] = 'Use: {"type": "notify_intent", "intent": "buy|schedule|close", "context": "resumo"}';
            $lines[] = "--- FIM ---";
        }

        // ── FORMATO DE RESPOSTA WEB CHAT ──────────────────────────────────────
        $lines[] = <<<'WEBCHAT'

--- FORMATO DE RESPOSTA (WEB CHAT) ---
Você está atendendo em um widget de chat no site. Responda SEMPRE com JSON válido, sem markdown:

{
  "reply": "Texto da mensagem ao visitante",
  "actions": [],
  "buttons": [{"label": "Texto do botão", "value": "valor_enviado"}],
  "cards": [{"title": "Título", "description": "Descrição", "image_url": "URL", "button_label": "Ver mais", "button_value": "ver_mais"}],
  "input_type": "text"
}

REGRAS DE UI PARA CHAT WEB:
- "reply": OBRIGATÓRIO. Texto principal da resposta (máx 300 chars). Seja direto e conciso.
- "buttons": OPCIONAL. Use para apresentar 2-5 opções rápidas (respostas pré-definidas que o visitante clica).
  Exemplos: escolher departamento, confirmar sim/não, selecionar produto, responder enquete.
  O visitante clica no botão e o "value" é enviado como mensagem.
- "cards": OPCIONAL. Use para apresentar produtos, planos, serviços ou catálogo com visual rico.
  Cada card pode ter: title, description, image_url (opcional), button_label, button_value.
  Use quando tiver 2+ itens para mostrar com detalhes (preços, descrições, fotos).
- "input_type": OPCIONAL. Controla a validação do campo de input.
  "text" (padrão) → campo normal
  "email" → quando precisar coletar email do visitante
  "phone" → quando precisar coletar telefone do visitante
  Após coletar, volte para "text".
- "actions": OPCIONAL. Ações internas (set_stage, add_tags, notify_intent, assign_human). Mesmo formato do WhatsApp.

QUANDO USAR CADA RECURSO:
- Saudação/boas-vindas → reply + buttons com opções iniciais ("Vendas", "Suporte", "Preços")
- Mostrar produtos/planos → reply + cards com detalhes e preços
- Pedir confirmação → reply + buttons ["Sim", "Não"]
- Coletar email → reply pedindo email + input_type: "email"
- Coletar telefone → reply pedindo telefone + input_type: "phone"
- Conversa livre/resposta simples → apenas reply (omita buttons/cards)
- Transferir para humano → reply + actions com assign_human

Se não precisar de buttons/cards/input_type, OMITA esses campos do JSON (não envie arrays vazios).
NUNCA inclua texto fora do JSON.
--- FIM ---
WEBCHAT;

        return implode("\n", $lines);
    }

    /**
     * Constrói o histórico de mensagens da conversa para o LLM.
     * Retorna array no formato OpenAI: [{role, content}]
     */
    public function buildHistory(WhatsappConversation $conv, int $limit = 50): array
    {
        $messages = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->where('is_deleted', false)
            ->whereIn('type', ['text', 'image', 'audio', 'video', 'document', 'location', 'event'])
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $history = [];

        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'user' : 'assistant';

            // Texto puro, localização, ou áudio com transcrição disponível no body
            if ($msg->type === 'text' || $msg->type === 'location' || ! $msg->media_url || ($msg->type === 'audio' && $msg->body)) {
                $history[] = [
                    'role'    => $role,
                    'content' => $msg->body ?? '',
                ];
                continue;
            }

            // Imagens — enviar via Vision API (multimodal com base64)
            if ($msg->type === 'image' && $msg->media_url) {
                $content   = [];
                $imagePath = $this->resolveMediaPath($msg->media_url);
                if ($imagePath) {
                    $mime = $msg->media_mime ?: 'image/jpeg';
                    $b64  = base64_encode(file_get_contents($imagePath));
                    // Limitar a ~1MB de base64 (~750KB de imagem real)
                    if (strlen($b64) > 1_000_000) {
                        $b64 = $this->resizeImageBase64($imagePath, 1024, $mime);
                    }
                    if ($b64) {
                        $content[] = ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$b64}"]];
                    }
                }
                if ($msg->body) {
                    $content[] = ['type' => 'text', 'text' => $msg->body];
                } elseif (empty($content)) {
                    $content[] = ['type' => 'text', 'text' => '[imagem enviada]'];
                }
                $history[] = ['role' => $role, 'content' => $content];
                continue;
            }

            // Documentos / PDFs — extrair texto quando possível
            if ($msg->type === 'document' && $msg->media_url) {
                $docInfo = '[documento: ' . ($msg->media_filename ?: 'arquivo') . ']';
                if ($msg->media_mime === 'application/pdf') {
                    $pdfPath = $this->resolveMediaPath($msg->media_url);
                    if ($pdfPath) {
                        $pdfText = $this->extractPdfText($pdfPath);
                        if ($pdfText) {
                            $docInfo = "Conteúdo do PDF ({$msg->media_filename}):\n{$pdfText}";
                        }
                    }
                }
                $history[] = ['role' => $role, 'content' => ($msg->body ? $msg->body . "\n" : '') . $docInfo];
                continue;
            }

            // Vídeos, áudio sem transcrição e demais — placeholder
            $label = match ($msg->type) {
                'video'    => '[vídeo enviado' . ($msg->media_filename ? ': ' . $msg->media_filename : '') . ']',
                'audio'    => '[áudio enviado]',
                default    => '[mídia enviada]',
            };
            $history[] = [
                'role'    => $role,
                'content' => ($msg->body ? $msg->body . ' ' : '') . $label,
            ];
        }

        return $history;
    }

    private function formatCustomFieldValue(?CustomFieldValue $cfv, CustomFieldDefinition $cf): string
    {
        if (! $cfv) {
            return '(vazio)';
        }

        return match ($cf->field_type) {
            'number'      => $cfv->value_number !== null ? (string) $cfv->value_number : '(vazio)',
            'currency'    => $cfv->value_number !== null ? 'R$ ' . number_format((float) $cfv->value_number, 2, ',', '.') : '(vazio)',
            'date'        => $cfv->value_date ? $cfv->value_date->format('d/m/Y') : '(vazio)',
            'checkbox'    => $cfv->value_boolean ? 'Sim' : 'Não',
            'multiselect' => ! empty($cfv->value_json) ? implode(', ', (array) $cfv->value_json) : '(vazio)',
            default       => $cfv->value_text ?: '(vazio)',
        };
    }

    /**
     * Resolve media_url para caminho absoluto no filesystem.
     */
    private function resolveMediaPath(string $mediaUrl): ?string
    {
        // media_url: "/storage/whatsapp/image/media_xxx.jpg"
        if (str_starts_with($mediaUrl, '/storage/')) {
            $relative = substr($mediaUrl, strlen('/storage/'));
            $path     = storage_path('app/public/' . $relative);
            return file_exists($path) ? $path : null;
        }
        // Produção: URL completa como "https://app.syncro.chat/storage/whatsapp/..."
        $parsed = parse_url($mediaUrl, PHP_URL_PATH);
        if ($parsed && str_contains($parsed, '/storage/')) {
            $relative = substr($parsed, strpos($parsed, '/storage/') + strlen('/storage/'));
            $path     = storage_path('app/public/' . $relative);
            return file_exists($path) ? $path : null;
        }
        return null;
    }

    /**
     * Redimensiona imagem para max dimension e retorna base64.
     */
    private function resizeImageBase64(string $path, int $maxDim, string $mime): ?string
    {
        try {
            $img = @imagecreatefromstring(file_get_contents($path));
            if (! $img) {
                return null;
            }
            $w = imagesx($img);
            $h = imagesy($img);
            if ($w <= $maxDim && $h <= $maxDim) {
                imagedestroy($img);
                return base64_encode(file_get_contents($path));
            }
            $ratio  = min($maxDim / $w, $maxDim / $h);
            $newW   = (int) ($w * $ratio);
            $newH   = (int) ($h * $ratio);
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
            ob_start();
            if (str_contains($mime, 'png')) {
                imagepng($resized);
            } else {
                imagejpeg($resized, null, 85);
            }
            $data = ob_get_clean();
            imagedestroy($img);
            imagedestroy($resized);
            return base64_encode($data);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extrai texto de um PDF usando smalot/pdfparser.
     */
    private function extractPdfText(string $path): ?string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($path);
            $text   = $pdf->getText();
            return mb_substr(trim($text), 0, 2000) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Divide uma resposta em múltiplas mensagens humanizadas para envio sequencial.
     *
     * Estratégia:
     * 1. Limpar formatação markdown via cleanFormatting()
     * 2. Dividir por parágrafos (\n\n) — preserva quebras intencionais do LLM
     * 3. Para cada parágrafo > sentenceThreshold (250 chars), subdividir por sentenças
     * 4. Agrupar chunks muito curtos (< 80 chars) com o próximo, respeitando maxLength
     *
     * O sentenceThreshold (250) é intencionalmente menor que maxLength (≥200)
     * para garantir split agressivo mesmo quando o LLM ignora as instruções de \n\n.
     */
    public function splitIntoMessages(string $text, int $maxLength): array
    {
        $text = $this->cleanFormatting($text);
        $text = str_replace("\r\n", "\n", $text);

        // Threshold para dividir por sentenças — menor que maxLength para ser mais agressivo
        $sentenceThreshold = (int) min($maxLength, 250);

        // 1. Split por parágrafo (dupla quebra de linha)
        $paragraphs = preg_split('/\n{2,}/', $text);
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        // Fallback: se resultou em 1 bloco e tem \n simples, split por \n
        if (count($paragraphs) <= 1 && str_contains($text, "\n")) {
            $paragraphs = explode("\n", $text);
            $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));
        }

        if (empty($paragraphs)) {
            return [$text];
        }

        // 2. Para cada parágrafo acima do threshold → subdividir em sentenças
        $chunks = [];
        foreach ($paragraphs as $para) {
            if (mb_strlen($para) > $sentenceThreshold) {
                $sentences = preg_split('/(?<=[.!?])\s+/', $para, -1, PREG_SPLIT_NO_EMPTY);
                if (count($sentences) > 1) {
                    $chunk = '';
                    foreach ($sentences as $s) {
                        $candidate = $chunk !== '' ? $chunk . ' ' . $s : $s;
                        if ($chunk !== '' && mb_strlen($candidate) > $sentenceThreshold) {
                            $chunks[] = trim($chunk);
                            $chunk    = $s;
                        } else {
                            $chunk = $candidate;
                        }
                    }
                    if ($chunk !== '') {
                        $chunks[] = trim($chunk);
                    }
                    continue;
                }
            }
            $chunks[] = $para;
        }

        $result = array_values(array_filter(array_map('trim', $chunks)));

        return $result ?: [$text];
    }

    /**
     * Remove formatação markdown do texto para uso no WhatsApp.
     */
    public function cleanFormatting(string $text): string
    {
        // Remove **negrito** e __sublinhado__ (mantém o texto interno)
        $text = preg_replace('/\*\*(.+?)\*\*/su', '$1', $text);
        $text = preg_replace('/__(.+?)__/su', '$1', $text);
        // Remove *itálico* (asterisco simples)
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/su', '$1', $text);
        // Remove headers markdown (###, ##, #)
        $text = preg_replace('/^#{1,6}\s+/mu', '', $text);
        // Remove marcadores de lista no início de linha (- item / * item)
        $text = preg_replace('/^[\-\*]\s+/mu', '', $text);
        // Remove blocos de código (``` ... ```)
        $text = preg_replace('/```[\s\S]*?```/u', '', $text);
        // Remove código inline (`code`)
        $text = preg_replace('/`(.+?)`/u', '$1', $text);
        // Normaliza múltiplas linhas em branco para no máximo 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Envia múltiplas partes de resposta com delay entre elas.
     */
    public function sendWhatsappReplies(WhatsappConversation $conv, array $messages, int $delaySeconds = 2): void
    {
        // Resolver instância + chatId uma vez para enviar presence
        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('id', $conv->instance_id)
            ->first();

        $chatId = null;
        $waha   = null;

        if ($instance && $instance->status === 'connected') {
            $sampleId = WhatsappMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conv->id)
                ->whereNotNull('waha_message_id')
                ->where('direction', 'inbound')
                ->latest('sent_at')
                ->value('waha_message_id');

            if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
                $jid = $m[1];
                $chatId = str_ends_with($jid, '@lid')
                    ? preg_replace('/[:@].+$/', '', $jid) . '@lid'
                    : preg_replace('/[:@].+$/', '', $jid) . '@c.us';
            }

            if (! $chatId) {
                $rawPhone = ltrim((string) preg_replace('/[:@\s].+$/', '', $conv->phone), '+');
                $chatId   = $rawPhone . '@c.us';
            }

            $waha = \App\Services\WhatsappServiceFactory::for($instance);
        }

        foreach ($messages as $i => $text) {
            // Delay entre mensagens (pular na primeira)
            if ($i > 0) {
                sleep(3);
            }

            // Typing presence como indicador visual (bônus — não bloqueia envio se falhar)
            if ($waha && $chatId) {
                try {
                    $waha->setPresence($chatId, 'typing');
                } catch (\Throwable) {
                }
                sleep(2);
            }

            $this->sendWhatsappReply($conv, $text);
        }
    }

    /**
     * Transcreve um arquivo de áudio via OpenAI Whisper.
     * Aceita URLs absolutas (http/https) ou caminhos relativos do storage público.
     */
    public function transcribeAudio(string $mediaUrl): ?string
    {
        $apiKey = (string) config('ai.whisper_api_key');
        if ($apiKey === '') {
            return null;
        }

        // Obter conteúdo do áudio
        $audioContent = null;
        $ext          = 'ogg';

        if (str_starts_with($mediaUrl, 'http://') || str_starts_with($mediaUrl, 'https://')) {
            $dlResponse = Http::timeout(30)->get($mediaUrl);
            if (! $dlResponse->successful()) {
                Log::channel('whatsapp')->warning('Whisper: falha ao baixar áudio', [
                    'url'    => $mediaUrl,
                    'status' => $dlResponse->status(),
                ]);
                return null;
            }
            $audioContent = $dlResponse->body();
            // Strip path parameters (e.g. ".ogg;codecs=opus" → ".ogg") before extracting extension
            $urlPath = explode(';', parse_url($mediaUrl, PHP_URL_PATH) ?? '')[0];
            $ext     = pathinfo($urlPath, PATHINFO_EXTENSION) ?: 'ogg';
            // Whisper não aceita .opus — usar .ogg (mesmo container, codecs compatíveis)
            if ($ext === 'opus') {
                $ext = 'ogg';
            }
        } else {
            // Caminho relativo de storage público
            $path = Storage::disk('public')->path($mediaUrl);
            if (! file_exists($path)) {
                Log::channel('whatsapp')->warning('Whisper: arquivo de áudio não encontrado', ['path' => $path]);
                return null;
            }
            $audioContent = file_get_contents($path);
            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'ogg';
        }

        if (! $audioContent) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->attach('file', $audioContent, 'audio.' . $ext)
                ->attach('model', 'whisper-1')
                ->attach('language', 'pt')
                ->post('https://api.openai.com/v1/audio/transcriptions');

            if (! $response->successful()) {
                Log::channel('whatsapp')->warning('Whisper: API retornou erro', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $response->json('text') ?: null;
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Whisper: exceção ao transcrever', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Envia a resposta da IA pelo WhatsApp e salva como mensagem outbound.
     */
    public function sendWhatsappReply(WhatsappConversation $conv, string $text): void
    {
        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('id', $conv->instance_id)
            ->first();

        if (! $instance || $instance->status !== 'connected') {
            Log::channel('whatsapp')->warning('AI reply: instância WhatsApp não conectada', [
                'conversation_id' => $conv->id,
                'instance_id'     => $conv->instance_id,
            ]);
            return;
        }

        // Derivar chatId a partir do waha_message_id de uma mensagem inbound existente
        $sampleId = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->whereNotNull('waha_message_id')
            ->where('direction', 'inbound')
            ->latest('sent_at')
            ->value('waha_message_id');

        $chatId = null;
        if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
            $jid = $m[1];
            $chatId = str_ends_with($jid, '@lid')
                ? preg_replace('/[:@].+$/', '', $jid) . '@lid'
                : preg_replace('/[:@].+$/', '', $jid) . '@c.us';
        }

        if (! $chatId) {
            $rawPhone = ltrim((string) preg_replace('/[:@\s].+$/', '', $conv->phone), '+');
            $chatId   = $rawPhone . '@c.us';
        }

        $waha   = \App\Services\WhatsappServiceFactory::for($instance);
        $result = $waha->sendText($chatId, $text);

        if (isset($result['error'])) {
            Log::channel('whatsapp')->error('AI reply: falha ao enviar pelo WAHA', [
                'conversation_id' => $conv->id,
                'error'           => $result['body'] ?? 'desconhecido',
            ]);
            return;
        }

        $wahaMessageId = $result['id'] ?? null;

        $message = WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $conv->tenant_id,
            'conversation_id'  => $conv->id,
            'waha_message_id'  => $wahaMessageId,
            'direction'        => 'outbound',
            'type'             => 'text',
            'body'             => $text,
            'user_id'          => null,
            'sent_by'          => 'ai_agent',
            'sent_by_agent_id' => $conv->ai_agent_id,
            'ack'              => 'sent',
            'sent_at'          => now(),
        ]);

        // Track response time: first AI reply after customer inbound
        $conv->refresh();
        $convUpdates = ['last_message_at' => now()];
        if ($conv->last_inbound_at && ! $conv->first_response_at) {
            $convUpdates['first_response_at'] = now();
        }

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update($convUpdates);

        try {
            WhatsappMessageCreated::dispatch($message, $conv->tenant_id);
            $conv->refresh();
            WhatsappConversationUpdated::dispatch($conv, $conv->tenant_id);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AI reply: broadcast falhou', ['error' => $e->getMessage()]);
        }

        Log::channel('whatsapp')->info('AI reply enviado', [
            'conversation_id' => $conv->id,
            'waha_message_id' => $wahaMessageId,
            'length'          => mb_strlen($text),
        ]);
    }

    /**
     * Envia um arquivo de mídia do agente (imagem/documento) pelo WhatsApp.
     */
    public function sendMediaReply(WhatsappConversation $conv, AiAgent $agent, int $mediaId): void
    {
        $media = AiAgentMedia::withoutGlobalScope('tenant')
            ->where('id', $mediaId)
            ->where('ai_agent_id', $agent->id)
            ->first();

        if (! $media) {
            Log::channel('whatsapp')->warning('AI send_media: mídia não encontrada', [
                'conversation_id' => $conv->id,
                'media_id'        => $mediaId,
            ]);
            return;
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('id', $conv->instance_id)
            ->first();

        if (! $instance || $instance->status !== 'connected') {
            Log::channel('whatsapp')->warning('AI send_media: instância não conectada', [
                'conversation_id' => $conv->id,
            ]);
            return;
        }

        // Resolver chatId (mesmo padrão do sendWhatsappReply)
        $sampleId = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->whereNotNull('waha_message_id')
            ->where('direction', 'inbound')
            ->latest('sent_at')
            ->value('waha_message_id');

        $chatId = null;
        if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
            $jid = $m[1];
            $chatId = str_ends_with($jid, '@lid')
                ? preg_replace('/[:@].+$/', '', $jid) . '@lid'
                : preg_replace('/[:@].+$/', '', $jid) . '@c.us';
        }

        if (! $chatId) {
            $rawPhone = ltrim((string) preg_replace('/[:@\s].+$/', '', $conv->phone), '+');
            $chatId   = $rawPhone . '@c.us';
        }

        $localPath = Storage::disk('public')->path($media->storage_path);
        if (! file_exists($localPath)) {
            Log::channel('whatsapp')->warning('AI send_media: arquivo não encontrado no disco', [
                'path' => $media->storage_path,
            ]);
            return;
        }

        $waha    = \App\Services\WhatsappServiceFactory::for($instance);
        $caption = $media->description ?? '';
        $isImage = str_starts_with($media->mime_type, 'image/');

        if ($isImage) {
            $result = $waha->sendImageBase64($chatId, $localPath, $media->mime_type, $caption);
        } else {
            $result = $waha->sendFileBase64($chatId, $localPath, $media->mime_type, $media->original_name, $caption);
        }

        if (isset($result['error'])) {
            Log::channel('whatsapp')->error('AI send_media: falha ao enviar pelo WAHA', [
                'conversation_id' => $conv->id,
                'error'           => $result['body'] ?? 'desconhecido',
            ]);
            return;
        }

        $wahaMessageId = $result['id'] ?? null;

        $message = WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $conv->tenant_id,
            'conversation_id'  => $conv->id,
            'waha_message_id'  => $wahaMessageId,
            'direction'        => 'outbound',
            'type'             => $isImage ? 'image' : 'document',
            'body'             => $caption,
            'media_url'        => '/storage/' . $media->storage_path,
            'media_mime'       => $media->mime_type,
            'media_filename'   => $media->original_name,
            'user_id'          => null,
            'sent_by'          => 'ai_agent',
            'sent_by_agent_id' => $agent->id,
            'ack'              => 'sent',
            'sent_at'          => now(),
        ]);

        // Track response time: first AI reply after customer inbound
        $conv->refresh();
        $convUpdates = ['last_message_at' => now()];
        if ($conv->last_inbound_at && ! $conv->first_response_at) {
            $convUpdates['first_response_at'] = now();
        }

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update($convUpdates);

        try {
            WhatsappMessageCreated::dispatch($message, $conv->tenant_id);
            $conv->refresh();
            WhatsappConversationUpdated::dispatch($conv, $conv->tenant_id);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AI send_media: broadcast falhou', ['error' => $e->getMessage()]);
        }

        Log::channel('whatsapp')->info('AI send_media enviado', [
            'conversation_id' => $conv->id,
            'media_id'        => $mediaId,
            'type'            => $isImage ? 'image' : 'document',
            'file'            => $media->original_name,
        ]);
    }
}
