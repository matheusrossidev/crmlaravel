<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\WebsiteConversation;
use App\Models\WebsiteMessage;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Log;

class WebsiteChatService
{
    private const MAX_ITERATIONS = 30;

    /**
     * Retorna o estado atual de input (buttons + input_type) para conversas em waiting.
     * Usado pelo init para restaurar botões ao recarregar a página.
     *
     * @return array{buttons: array, input_type: string}
     */
    public function getCurrentInputState(WebsiteConversation $conv): array
    {
        $conv->load('flow');
        if (! $conv->flow) {
            return ['buttons' => [], 'input_type' => 'text'];
        }

        $cursor = $conv->chatbot_cursor;
        if (empty($cursor['waiting'])) {
            return ['buttons' => [], 'input_type' => 'text'];
        }

        $steps   = $conv->flow->steps ?? [];
        $path    = $cursor['path'] ?? [];
        $index   = $cursor['index'] ?? 0;
        $stepsAt = $this->resolveStepsAtPath($steps, $path);
        $step    = $stepsAt[$index] ?? null;

        if (! $step || $step['type'] !== 'input') {
            return ['buttons' => [], 'input_type' => 'text'];
        }

        $config    = $step['config'] ?? [];
        $inputType = $config['field_type'] ?? 'text';
        $buttons   = [];

        if (! empty($config['show_buttons'])) {
            foreach ($step['branches'] ?? [] as $branch) {
                $label    = (string) ($branch['label'] ?? '');
                $keywords = (array) ($branch['keywords'] ?? []);
                $value    = $keywords[0] ?? $label;
                if ($label !== '') {
                    $buttons[] = ['label' => $label, 'value' => $value];
                }
            }
        }

        return ['buttons' => $buttons, 'input_type' => $inputType];
    }

    /**
     * Processa a mensagem do visitante usando a árvore de steps (JSON).
     * Retorna array com replies, buttons e input_type.
     *
     * @return array{replies: array, buttons: array, input_type: string}
     */
    public function processMessage(WebsiteConversation $conv, string $inboundBody): array
    {
        $conv->load('flow');

        if (! $conv->flow || ! $conv->flow->is_active) {
            return ['replies' => [], 'buttons' => [], 'input_type' => 'text'];
        }

        $flow   = $conv->flow;
        $steps  = $flow->steps ?? [];
        $vars   = $this->buildVars($conv);
        $cursor = $conv->chatbot_cursor; // {path: [...], index: N, waiting: bool}

        if (empty($steps)) {
            return ['replies' => [], 'buttons' => [], 'input_type' => 'text'];
        }

        // ── Resolver entrada da resposta do visitante ────────────────────
        if ($cursor && ! empty($cursor['waiting'])) {
            [$cursor, $vars] = $this->resolveInput($steps, $cursor, $inboundBody, $vars);
        } elseif (! $cursor) {
            // Início do fluxo
            $cursor = ['path' => [], 'index' => 0];
        }

        // ── Executar steps a partir do cursor ────────────────────────────
        return $this->executeFromCursor($conv, $steps, $cursor, $vars);
    }

    /**
     * Resolve a resposta do visitante a um nó de input/condition.
     * Retorna [novoCursor, vars].
     */
    private function resolveInput(array $steps, array $cursor, string $body, array $vars): array
    {
        $path  = $cursor['path'] ?? [];
        $index = $cursor['index'] ?? 0;

        $stepsAtPath = $this->resolveStepsAtPath($steps, $path);
        if (! isset($stepsAtPath[$index])) {
            return [['path' => [], 'index' => 0], $vars];
        }

        $step = $stepsAtPath[$index];
        $body = trim($body);

        if ($step['type'] === 'input') {
            $config    = $step['config'] ?? [];
            $fieldType = $config['field_type'] ?? 'text';
            $value     = $fieldType === 'phone' ? $this->normalizePhone($body) : $body;

            // Save to variable
            $saveTo = $config['save_to'] ?? null;
            if ($saveTo && ! str_starts_with($saveTo, '$')) {
                $vars[$saveTo] = $value;
            }

            // Match branch by keyword
            foreach ($step['branches'] ?? [] as $bi => $branch) {
                $keywords = array_map('strtolower', (array) ($branch['keywords'] ?? []));
                if (in_array(strtolower($body), $keywords, true)) {
                    // Enter this branch
                    $branchSteps = $branch['steps'] ?? [];
                    if (! empty($branchSteps)) {
                        return [['path' => array_merge($path, [$index, 'branches', $bi, 'steps']), 'index' => 0], $vars];
                    }
                    // Empty branch — continue after the input node
                    return [['path' => $path, 'index' => $index + 1], $vars];
                }
            }

            // Default branch
            $defSteps = $step['default_branch']['steps'] ?? [];
            if (! empty($defSteps)) {
                return [['path' => array_merge($path, [$index, 'default_branch', 'steps']), 'index' => 0], $vars];
            }

            // No default branch — continue after the input node
            return [['path' => $path, 'index' => $index + 1], $vars];
        }

        // Not an input — just move forward
        return [['path' => $path, 'index' => $index + 1], $vars];
    }

    /**
     * Executa steps a partir do cursor até encontrar um input (espera resposta) ou fim.
     */
    private function executeFromCursor(WebsiteConversation $conv, array $steps, array $cursor, array $vars): array
    {
        $replies    = [];
        $buttons    = [];
        $inputType  = 'text';
        $iterations = 0;

        while ($iterations < self::MAX_ITERATIONS) {
            $iterations++;

            $path  = $cursor['path'] ?? [];
            $index = $cursor['index'] ?? 0;

            $stepsAtPath = $this->resolveStepsAtPath($steps, $path);

            // Se o index excedeu o array de steps neste nível, voltar ao nível pai
            if ($index >= count($stepsAtPath)) {
                $parentResult = $this->returnToParent($path);
                if ($parentResult === null) {
                    // Chegou ao fim do fluxo
                    break;
                }
                $cursor = $parentResult;
                continue;
            }

            $step   = $stepsAtPath[$index];
            $config = $step['config'] ?? [];

            switch ($step['type']) {
                case 'message':
                    $text     = ChatbotVariableService::interpolate((string) ($config['text'] ?? ''), $vars);
                    $imageUrl = $config['image_url'] ?? null;
                    if ($text !== '' || $imageUrl) {
                        $replies[] = ['text' => $text, 'image_url' => $imageUrl];
                    }
                    $cursor['index'] = $index + 1;
                    break;

                case 'input':
                    $text     = ChatbotVariableService::interpolate((string) ($config['text'] ?? ''), $vars);
                    $imageUrl = $config['image_url'] ?? null;
                    if ($text !== '' || $imageUrl) {
                        $replies[] = ['text' => $text, 'image_url' => $imageUrl];
                    }

                    // Collect quick reply buttons
                    if (! empty($config['show_buttons'])) {
                        foreach ($step['branches'] ?? [] as $branch) {
                            $label    = (string) ($branch['label'] ?? '');
                            $keywords = (array) ($branch['keywords'] ?? []);
                            $value    = $keywords[0] ?? $label;
                            if ($label !== '') {
                                $buttons[] = ['label' => $label, 'value' => $value];
                            }
                        }
                    }

                    $inputType = $config['field_type'] ?? 'text';

                    // Save cursor as waiting
                    $cursor['waiting'] = true;
                    $this->persistState($conv, $cursor, $vars);
                    $this->saveOutboundMessages($conv->id, $replies);
                    return ['replies' => $replies, 'buttons' => $buttons, 'input_type' => $inputType];

                case 'condition':
                    $varName  = $config['variable'] ?? '';
                    $varValue = strtolower((string) ($vars[$varName] ?? ''));

                    $matchedBranch = null;
                    foreach ($step['branches'] ?? [] as $bi => $branch) {
                        $operator = $branch['operator'] ?? 'equals';
                        $condVal  = strtolower((string) ($branch['value'] ?? ''));

                        $matched = match ($operator) {
                            'equals'      => $varValue === $condVal,
                            'not_equals'  => $varValue !== $condVal,
                            'contains'    => str_contains($varValue, $condVal),
                            'starts_with' => str_starts_with($varValue, $condVal),
                            'ends_with'   => str_ends_with($varValue, $condVal),
                            'gt'          => is_numeric($varValue) && is_numeric($condVal) && (float) $varValue > (float) $condVal,
                            'lt'          => is_numeric($varValue) && is_numeric($condVal) && (float) $varValue < (float) $condVal,
                            default       => false,
                        };

                        if ($matched) {
                            $matchedBranch = $bi;
                            break;
                        }
                    }

                    if ($matchedBranch !== null) {
                        $branchSteps = $step['branches'][$matchedBranch]['steps'] ?? [];
                        if (! empty($branchSteps)) {
                            $cursor = ['path' => array_merge($path, [$index, 'branches', $matchedBranch, 'steps']), 'index' => 0];
                        } else {
                            $cursor['index'] = $index + 1;
                        }
                    } else {
                        $defSteps = $step['default_branch']['steps'] ?? [];
                        if (! empty($defSteps)) {
                            $cursor = ['path' => array_merge($path, [$index, 'default_branch', 'steps']), 'index' => 0];
                        } else {
                            $cursor['index'] = $index + 1;
                        }
                    }
                    break;

                case 'action':
                    $vars = $this->executeAction($config, $conv, $vars);

                    // Redirect: return immediately with the URL for the widget to handle
                    if (($config['type'] ?? '') === 'redirect' && ! empty($config['url'])) {
                        $redirectUrl = ChatbotVariableService::interpolate((string) $config['url'], $vars);
                        $cursor['index'] = $index + 1;
                        $this->persistState($conv, $cursor, $vars);
                        $this->saveOutboundMessages($conv->id, $replies);
                        return [
                            'replies'         => $replies,
                            'buttons'         => [],
                            'input_type'      => 'text',
                            'redirect_url'    => $redirectUrl,
                            'redirect_target' => $config['target'] ?? '_blank',
                        ];
                    }

                    $cursor['index'] = $index + 1;
                    break;

                case 'delay':
                    // No website channel delays are skipped
                    $cursor['index'] = $index + 1;
                    break;

                case 'end':
                    $text = ChatbotVariableService::interpolate((string) ($config['text'] ?? ''), $vars);
                    if ($text !== '') {
                        $replies[] = ['text' => $text, 'image_url' => null];
                    }
                    $this->clearFlow($conv, $vars);
                    $this->saveOutboundMessages($conv->id, $replies);
                    return ['replies' => $replies, 'buttons' => [], 'input_type' => 'text'];

                case 'cards':
                    $items = $config['items'] ?? [];
                    $cards = [];
                    $hasReplyButton = false;
                    foreach ($items as $card) {
                        if (($card['button_action'] ?? 'reply') === 'reply' && ! empty($card['button_label'])) {
                            $hasReplyButton = true;
                        }
                        $cards[] = [
                            'title'         => ChatbotVariableService::interpolate((string) ($card['title'] ?? ''), $vars),
                            'description'   => ChatbotVariableService::interpolate((string) ($card['description'] ?? ''), $vars),
                            'image_url'     => $card['image_url'] ?? null,
                            'button_label'  => $card['button_label'] ?? null,
                            'button_action' => $card['button_action'] ?? 'reply',
                            'button_value'  => $card['button_value'] ?? null,
                            'button_url'    => $card['button_url'] ?? null,
                        ];
                    }
                    if (! empty($cards)) {
                        $replies[] = ['type' => 'cards', 'cards' => $cards];
                    }
                    $cursor['index'] = $index + 1;
                    if ($hasReplyButton) {
                        $cursor['waiting'] = true;
                        $this->persistState($conv, $cursor, $vars);
                        $this->saveOutboundMessages($conv->id, $replies);
                        return ['replies' => $replies, 'buttons' => [], 'input_type' => 'text'];
                    }
                    break;

                default:
                    $cursor['index'] = $index + 1;
                    break;
            }
        }

        if ($iterations >= self::MAX_ITERATIONS) {
            Log::warning('WebsiteChatService: limite de iterações atingido', ['conversation_id' => $conv->id]);
        }

        $this->persistState($conv, null, $vars);
        $this->saveOutboundMessages($conv->id, $replies);

        return ['replies' => $replies, 'buttons' => [], 'input_type' => 'text'];
    }

    /**
     * Resolve o array de steps para um dado path.
     * Path: [] = root, [1, 'branches', 0, 'steps'] = branch steps
     */
    private function resolveStepsAtPath(array $rootSteps, array $path): array
    {
        $current = $rootSteps;

        foreach ($path as $key) {
            if (is_int($key) || is_numeric($key)) {
                $current = $current[(int) $key] ?? [];
            } else {
                $current = $current[$key] ?? [];
            }
        }

        return is_array($current) ? $current : [];
    }

    /**
     * Ao terminar os steps de um branch, retorna ao nível pai (step seguinte ao input/condition).
     * Retorna novo cursor ou null se chegou ao fim.
     */
    private function returnToParent(array $path): ?array
    {
        // Path examples:
        // [2, 'branches', 0, 'steps'] → parent path is [], parent index is 2 → continue at index 3
        // [1, 'default_branch', 'steps'] → parent path is [], parent index is 1 → continue at index 2
        // [0, 'branches', 1, 'steps', 2, 'branches', 0, 'steps'] → deeply nested

        if (empty($path)) {
            return null; // Already at root, no parent to return to
        }

        // Find the 'steps' key to determine the branch level
        // Walk backwards to find the pattern: [parentIndex, 'branches'|'default_branch', ...]
        $stepsKeyPos = null;
        for ($i = count($path) - 1; $i >= 0; $i--) {
            if ($path[$i] === 'steps') {
                $stepsKeyPos = $i;
                break;
            }
        }

        if ($stepsKeyPos === null) {
            return null;
        }

        // The pattern is: [...parentPath, stepIndex, 'branches'|'default_branch', ...]
        // So the step index is at stepsKeyPos - 2 (for 'branches', X, 'steps')
        // or stepsKeyPos - 1 (for 'default_branch', 'steps')

        if ($stepsKeyPos >= 2 && $path[$stepsKeyPos - 2] === 'branches') {
            // Pattern: [stepIndex, 'branches', branchIndex, 'steps']
            $stepIndex  = $path[$stepsKeyPos - 3] ?? 0;
            $parentPath = array_slice($path, 0, $stepsKeyPos - 3);
        } elseif ($stepsKeyPos >= 1 && $path[$stepsKeyPos - 1] === 'default_branch') {
            // Pattern: [stepIndex, 'default_branch', 'steps']
            $stepIndex  = $path[$stepsKeyPos - 2] ?? 0;
            $parentPath = array_slice($path, 0, $stepsKeyPos - 2);
        } else {
            return null;
        }

        return ['path' => $parentPath, 'index' => (int) $stepIndex + 1];
    }

    // ── Action execution ──────────────────────────────────────────────

    private function executeAction(array $config, WebsiteConversation $conv, array $vars): array
    {
        $type = $config['type'] ?? '';

        switch ($type) {
            case 'create_lead':
                $this->executeCreateLead($config, $conv, $vars);
                break;

            case 'change_stage':
                if ($conv->lead_id && isset($config['stage_id'])) {
                    Lead::withoutGlobalScope('tenant')
                        ->where('id', $conv->lead_id)
                        ->update(['stage_id' => (int) $config['stage_id']]);
                }
                break;

            case 'save_variable':
                $varName = (string) ($config['variable'] ?? '');
                if ($varName && ! str_starts_with($varName, '$')) {
                    $vars[$varName] = ChatbotVariableService::interpolate(
                        (string) ($config['value'] ?? ''),
                        $vars,
                    );
                }
                break;

            case 'close_conversation':
                WebsiteConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update(['status' => 'closed']);
                break;

            case 'send_whatsapp':
                $this->executeSendWhatsapp($config, $conv, $vars);
                break;

            case 'redirect':
                // Handled client-side via redirect_url in the API response
                break;
        }

        return $vars;
    }

    private function executeCreateLead(array $config, WebsiteConversation $conv, array $vars): void
    {
        $name    = trim((string) ($vars[$config['name_var']  ?? ''] ?? ''));
        $email   = trim((string) ($vars[$config['email_var'] ?? ''] ?? ''));
        $phone   = trim((string) ($vars[$config['phone_var'] ?? ''] ?? ''));
        $stageId = isset($config['stage_id']) ? (int) $config['stage_id'] : null;

        if (! $name && ! $email && ! $phone) {
            return;
        }

        // Try to find existing lead by email first, then phone
        $lead = null;
        if ($email) {
            $lead = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $conv->tenant_id)
                ->where('email', $email)
                ->first();
        }
        if (! $lead && $phone) {
            $lead = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $conv->tenant_id)
                ->where('phone', $phone)
                ->first();
        }

        $leadData = array_filter([
            'tenant_id'    => $conv->tenant_id,
            'name'         => $name ?: ($email ?: $phone),
            'email'        => $email ?: null,
            'phone'        => $phone ?: null,
            'stage_id'     => $stageId,
            'source'       => 'website',
            'utm_id'       => $conv->utm_id       ?: null,
            'utm_source'   => $conv->utm_source   ?: null,
            'utm_medium'   => $conv->utm_medium   ?: null,
            'utm_campaign' => $conv->utm_campaign ?: null,
            'utm_content'  => $conv->utm_content  ?: null,
            'utm_term'     => $conv->utm_term     ?: null,
            'fbclid'       => $conv->fbclid       ?: null,
            'gclid'        => $conv->gclid        ?: null,
        ]);

        if ($lead) {
            $lead->update(array_filter([
                'name'         => $name ?: null,
                'email'        => $email ?: null,
                'phone'        => $phone ?: null,
                'stage_id'     => $stageId ?: $lead->stage_id,
                'utm_id'       => $lead->utm_id       ?: $conv->utm_id,
                'utm_source'   => $lead->utm_source   ?: $conv->utm_source,
                'utm_medium'   => $lead->utm_medium   ?: $conv->utm_medium,
                'utm_campaign' => $lead->utm_campaign ?: $conv->utm_campaign,
                'utm_content'  => $lead->utm_content  ?: $conv->utm_content,
                'utm_term'     => $lead->utm_term     ?: $conv->utm_term,
                'fbclid'       => $lead->fbclid       ?: $conv->fbclid,
                'gclid'        => $lead->gclid        ?: $conv->gclid,
            ]));
        } else {
            $lead = Lead::withoutGlobalScope('tenant')->create($leadData);
        }

        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'lead_id'       => $lead->id,
                'contact_name'  => $name ?: $conv->contact_name,
                'contact_email' => $email ?: $conv->contact_email,
                'contact_phone' => $phone ?: $conv->contact_phone,
            ]);

        $conv->lead_id       = $lead->id;
        $conv->contact_name  = $name ?: $conv->contact_name;
        $conv->contact_email = $email ?: $conv->contact_email;
        $conv->contact_phone = $phone ?: $conv->contact_phone;

        Log::info('WebsiteChatService: lead criado/atualizado', [
            'conversation_id' => $conv->id,
            'lead_id'         => $lead->id,
        ]);
    }

    // ── Send WhatsApp ────────────────────────────────────────────────

    private function executeSendWhatsapp(array $config, WebsiteConversation $conv, array $vars): void
    {
        $phoneMode = $config['phone_mode'] ?? 'variable';
        $rawPhone  = $phoneMode === 'custom'
            ? (string) ($config['custom_phone'] ?? '')
            : (string) ($vars[$config['phone_var'] ?? '$contact_phone'] ?? $conv->contact_phone ?? '');

        if ($rawPhone === '') {
            Log::channel('whatsapp')->warning('WebsiteChatService: send_whatsapp sem telefone', ['conv' => $conv->id]);
            return;
        }

        $phone   = $this->normalizePhone($rawPhone);
        $message = ChatbotVariableService::interpolate((string) ($config['message'] ?? ''), $vars);

        if ($message === '') {
            return;
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $conv->tenant_id)
            ->where('status', 'connected')
            ->first();

        if (! $instance) {
            Log::channel('whatsapp')->warning('WebsiteChatService: nenhuma instância WhatsApp conectada', ['tenant' => $conv->tenant_id]);
            return;
        }

        try {
            $waha = \App\Services\WhatsappServiceFactory::for($instance);
            $waha->sendText($phone . '@c.us', $message);
            Log::channel('whatsapp')->info('WebsiteChatService: WhatsApp enviado via chatbot', [
                'conv'  => $conv->id,
                'phone' => $phone,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('WebsiteChatService: erro ao enviar WhatsApp', [
                'conv'  => $conv->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) >= 10 && ! str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }
        return $digits;
    }

    private function buildVars(WebsiteConversation $conv): array
    {
        $session = $conv->chatbot_variables ?? [];
        $system  = [
            '$contact_name'  => $conv->contact_name ?? '',
            '$contact_email' => $conv->contact_email ?? '',
            '$contact_phone' => $conv->contact_phone ?? '',
            '$lead_exists'   => $conv->lead_id ? 'sim' : 'não',
        ];

        return array_merge($session, $system);
    }

    private function persistState(WebsiteConversation $conv, ?array $cursor, array $vars): void
    {
        $session = array_filter($vars, fn ($k) => ! str_starts_with($k, '$'), ARRAY_FILTER_USE_KEY);

        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'chatbot_cursor'    => $cursor ? json_encode($cursor) : null,
                'chatbot_node_id'   => null, // deprecated, keep null
                'chatbot_variables' => ! empty($session) ? json_encode($session) : null,
            ]);
    }

    private function clearFlow(WebsiteConversation $conv, array $vars): void
    {
        $this->persistState($conv, null, $vars);
    }

    private function saveOutboundMessages(int $conversationId, array $replies): void
    {
        $now = now();
        foreach ($replies as $reply) {
            $content = is_array($reply) ? ($reply['text'] ?? '') : (string) $reply;
            if ($content === '') {
                continue;
            }
            WebsiteMessage::create([
                'conversation_id' => $conversationId,
                'direction'       => 'outbound',
                'content'         => $content,
                'sent_at'         => $now,
            ]);
        }

        if (! empty($replies)) {
            WebsiteConversation::withoutGlobalScope('tenant')
                ->where('id', $conversationId)
                ->update(['last_message_at' => $now]);
        }
    }
}
