<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const ENTITY_LABELS = [
        'Lead'                  => 'Lead',
        'Sale'                  => 'Venda',
        'LostSale'              => 'Venda perdida',
        'Pipeline'              => 'Pipeline',
        'WhatsappConversation'  => 'Conversa WA',
        'AiAgent'               => 'Agente IA',
        'ChatbotFlow'           => 'Fluxo chatbot',
        'Automation'            => 'Automação',
        'User'                  => 'Usuário',
        'Department'            => 'Departamento',
        'WhatsappTag'           => 'Tag',
        'CustomFieldDefinition' => 'Campo extra',
        'ApiKey'                => 'Chave API',
        'WhatsappInstance'      => 'Instância WA',
        'InstagramInstance'     => 'Instância IG',
        'ScoringRule'           => 'Regra scoring',
    ];

    private const ACTION_LABELS = [
        'created'        => 'Criou',
        'updated'        => 'Editou',
        'deleted'        => 'Excluiu',
        'login'          => 'Login',
        'logout'         => 'Logout',
        'login_failed'   => 'Login falhou',
        'password_reset' => 'Redefiniu senha',
    ];

    /** Human-readable field names */
    private const FIELD_LABELS = [
        'name'                 => 'Nome',
        'email'                => 'E-mail',
        'phone'                => 'Telefone',
        'company'              => 'Empresa',
        'value'                => 'Valor',
        'stage_id'             => 'Etapa',
        'pipeline_id'          => 'Pipeline',
        'assigned_to'          => 'Responsável',
        'closed_by'            => 'Fechado por',
        'lead_id'              => 'Lead',
        'status'               => 'Status',
        'role'                 => 'Permissão',
        'tags'                 => 'Tags',
        'is_active'            => 'Ativo',
        'objective'            => 'Objetivo',
        'communication_style'  => 'Estilo de comunicação',
        'channel'              => 'Canal',
        'trigger_type'         => 'Tipo de gatilho',
        'assignment_strategy'  => 'Estratégia de distribuição',
        'field_type'           => 'Tipo de campo',
        'is_required'          => 'Obrigatório',
        'color'                => 'Cor',
        'points'               => 'Pontos',
        'event_type'           => 'Tipo de evento',
        'permissions'          => 'Permissões',
        'phone_number'         => 'Número',
        'session_name'         => 'Sessão',
        'birthday'             => 'Aniversário',
        'source'               => 'Origem',
    ];

    public function index(Request $request): View
    {
        $query = AuditLog::where('tenant_id', activeTenantId())
            ->with('user:id,name')
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        // Pre-load lookup data for humanizing
        $lookups = $this->buildLookups();

        // Humanize descriptions
        $logs->getCollection()->transform(function (AuditLog $log) use ($lookups) {
            $log->setAttribute('human_desc', $this->humanize($log, $lookups));
            return $log;
        });

        $users = User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $entityTypes = self::ENTITY_LABELS;
        $actionLabels = self::ACTION_LABELS;

        return view('tenant.settings.audit-log', compact('logs', 'users', 'entityTypes', 'actionLabels'));
    }

    public function show(AuditLog $log): JsonResponse
    {
        $log->load('user:id,name');
        $lookups = $this->buildLookups();

        // Humanize old and new data
        $oldHuman = $this->humanizeData($log->old_data_json ?? [], $lookups);
        $newHuman = $this->humanizeData($log->new_data_json ?? [], $lookups);

        return response()->json([
            'success' => true,
            'log'     => [
                'id'          => $log->id,
                'user'        => $log->user?->name ?? 'Sistema',
                'action'      => self::ACTION_LABELS[$log->action] ?? $log->action,
                'entity_type' => self::ENTITY_LABELS[$log->entity_type] ?? $log->entity_type,
                'entity_id'   => $log->entity_id,
                'old_data'    => $oldHuman,
                'new_data'    => $newHuman,
                'ip_address'  => $log->ip_address,
                'user_agent'  => $log->user_agent,
                'created_at'  => $log->created_at?->format('d/m/Y H:i:s'),
            ],
        ]);
    }

    // ── Humanizer ────────────────────────────────────────────────────

    private function buildLookups(): array
    {
        $tenantId = activeTenantId();

        return Cache::remember("audit:lookups:{$tenantId}", 300, function () use ($tenantId) {
            $users = User::where('tenant_id', $tenantId)
                ->pluck('name', 'id')->toArray();

            $stages = PipelineStage::pluck('name', 'id')->toArray();

            $pipelines = \App\Models\Pipeline::pluck('name', 'id')->toArray();

            return [
                'users'     => $users,
                'stages'    => $stages,
                'pipelines' => $pipelines,
            ];
        });
    }

    private function humanize(AuditLog $log, array $lookups): string
    {
        $action = $log->action;
        $old    = $log->old_data_json ?? [];
        $new    = $log->new_data_json ?? [];

        if ($action === 'created' && !empty($new)) {
            return $this->humanizeCreated($new, $lookups);
        }

        if ($action === 'updated' && !empty($new)) {
            return $this->humanizeUpdated($old, $new, $lookups);
        }

        if ($action === 'deleted' && !empty($old)) {
            $name = $old['name'] ?? $old['email'] ?? $old['phone'] ?? '';
            return $name ? "Excluiu: {$name}" : '';
        }

        if ($action === 'login_failed') {
            return 'E-mail: ' . ($new['email'] ?? '—');
        }

        return '';
    }

    private function humanizeCreated(array $new, array $lookups): string
    {
        $parts = [];
        $name = $new['name'] ?? $new['email'] ?? $new['phone'] ?? null;
        if ($name) {
            $parts[] = "<strong>{$name}</strong>";
        }
        if (isset($new['value']) && $new['value']) {
            $parts[] = 'R$ ' . number_format((float) $new['value'], 2, ',', '.');
        }
        if (isset($new['role'])) {
            $parts[] = $this->humanizeRole($new['role']);
        }
        if (isset($new['channel'])) {
            $parts[] = ucfirst($new['channel']);
        }
        return implode(' · ', $parts);
    }

    private function humanizeUpdated(array $old, array $new, array $lookups): string
    {
        $parts = [];
        foreach (array_slice($new, 0, 4) as $field => $newVal) {
            $oldVal = $old[$field] ?? null;
            $label  = self::FIELD_LABELS[$field] ?? $field;

            $oldHuman = $this->humanizeValue($field, $oldVal, $lookups);
            $newHuman = $this->humanizeValue($field, $newVal, $lookups);

            $parts[] = "<strong>{$label}:</strong> {$oldHuman} → {$newHuman}";
        }
        if (count($new) > 4) {
            $parts[] = '+' . (count($new) - 4) . ' campos';
        }
        return implode('<br>', $parts);
    }

    private function humanizeValue(string $field, mixed $value, array $lookups): string
    {
        if ($value === null || $value === '') {
            return '<span style="color:#97A3B7;">vazio</span>';
        }

        // Resolve FK IDs
        if ($field === 'stage_id' && isset($lookups['stages'][$value])) {
            return $lookups['stages'][$value];
        }
        if ($field === 'pipeline_id' && isset($lookups['pipelines'][$value])) {
            return $lookups['pipelines'][$value];
        }
        if (in_array($field, ['assigned_to', 'closed_by', 'user_id'], true) && isset($lookups['users'][$value])) {
            return $lookups['users'][$value];
        }

        // Booleans
        if ($field === 'is_active' || $field === 'is_required') {
            return $value ? 'Sim' : 'Não';
        }

        // Role
        if ($field === 'role') {
            return $this->humanizeRole($value);
        }

        // Money
        if ($field === 'value') {
            return 'R$ ' . number_format((float) $value, 2, ',', '.');
        }

        // Arrays (tags, permissions)
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return (string) $value;
    }

    private function humanizeRole(string $role): string
    {
        return match ($role) {
            'admin'   => 'Administrador',
            'manager' => 'Gestor',
            'viewer'  => 'Visualizador',
            default   => $role,
        };
    }

    /** Humanize all keys/values in a data array for the detail drawer */
    private function humanizeData(array $data, array $lookups): array
    {
        $result = [];
        foreach ($data as $field => $value) {
            $label = self::FIELD_LABELS[$field] ?? $field;
            $result[$label] = $this->humanizeValue($field, $value, $lookups);
        }
        return $result;
    }
}
