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
    private const ENTITY_KEYS = [
        'Lead'                  => 'entity_lead',
        'Sale'                  => 'entity_sale',
        'LostSale'              => 'entity_lost_sale',
        'Pipeline'              => 'entity_pipeline',
        'WhatsappConversation'  => 'entity_conversation',
        'AiAgent'               => 'entity_ai_agent',
        'ChatbotFlow'           => 'entity_chatbot',
        'Automation'            => 'entity_automation',
        'User'                  => 'entity_user',
        'Department'            => 'entity_department',
        'WhatsappTag'           => 'entity_tag',
        'CustomFieldDefinition' => 'entity_custom_field',
        'ApiKey'                => 'entity_api_key',
        'WhatsappInstance'      => 'entity_wa_instance',
        'InstagramInstance'     => 'entity_ig_instance',
        'ScoringRule'           => 'entity_scoring_rule',
    ];

    private const ACTION_KEYS = [
        'created'        => 'action_created',
        'updated'        => 'action_updated',
        'deleted'        => 'action_deleted',
        'login'          => 'action_login',
        'logout'         => 'action_logout',
        'login_failed'   => 'action_login_failed',
        'password_reset' => 'action_password_reset',
    ];

    /** Field name → translation key suffix */
    private const FIELD_KEYS = [
        'name', 'email', 'phone', 'company', 'value',
        'stage_id', 'pipeline_id', 'assigned_to', 'closed_by',
        'lead_id', 'status', 'role', 'tags', 'is_active',
        'objective', 'communication_style', 'channel',
        'trigger_type', 'assignment_strategy', 'field_type',
        'is_required', 'color', 'points', 'event_type',
        'permissions', 'phone_number', 'session_name',
        'birthday', 'source',
    ];

    private function entityLabels(): array
    {
        $out = [];
        foreach (self::ENTITY_KEYS as $key => $langKey) {
            $out[$key] = __('audit.' . $langKey);
        }
        return $out;
    }

    private function actionLabels(): array
    {
        $out = [];
        foreach (self::ACTION_KEYS as $key => $langKey) {
            $out[$key] = __('audit.' . $langKey);
        }
        return $out;
    }

    private function fieldLabel(string $field): string
    {
        return in_array($field, self::FIELD_KEYS, true)
            ? __('audit.field_' . $field)
            : $field;
    }

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

        $entityTypes = $this->entityLabels();
        $actionLabels = $this->actionLabels();

        return view('tenant.settings.audit-log', compact('logs', 'users', 'entityTypes', 'actionLabels'));
    }

    public function show(AuditLog $log): JsonResponse
    {
        $log->load('user:id,name');
        $lookups = $this->buildLookups();

        // Humanize old and new data
        $oldHuman = $this->humanizeData($log->old_data_json ?? [], $lookups);
        $newHuman = $this->humanizeData($log->new_data_json ?? [], $lookups);

        $entityLabels = $this->entityLabels();
        $actionLabels = $this->actionLabels();

        return response()->json([
            'success' => true,
            'log'     => [
                'id'          => $log->id,
                'user'        => $log->user?->name ?? __('audit.system'),
                'action'      => $actionLabels[$log->action] ?? $log->action,
                'entity_type' => $entityLabels[$log->entity_type] ?? $log->entity_type,
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
            return $name ? __('audit.deleted_prefix') . ": {$name}" : '';
        }

        if ($action === 'login_failed') {
            return __('audit.email_label') . ': ' . ($new['email'] ?? '—');
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
            $label  = $this->fieldLabel($field);

            $oldHuman = $this->humanizeValue($field, $oldVal, $lookups);
            $newHuman = $this->humanizeValue($field, $newVal, $lookups);

            $parts[] = "<strong>{$label}:</strong> {$oldHuman} → {$newHuman}";
        }
        if (count($new) > 4) {
            $parts[] = __('audit.more_fields', ['count' => count($new) - 4]);
        }
        return implode('<br>', $parts);
    }

    private function humanizeValue(string $field, mixed $value, array $lookups): string
    {
        if ($value === null || $value === '') {
            return '<span style="color:#97A3B7;">' . __('audit.empty_value') . '</span>';
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
            return $value ? __('audit.yes') : __('audit.no');
        }

        // Role
        if ($field === 'role') {
            return $this->humanizeRole($value);
        }

        // Money
        if ($field === 'value') {
            return 'R$ ' . number_format((float) $value, 2, ',', '.');
        }

        // Arrays (tags, permissions, nested objects)
        if (is_array($value)) {
            // Check if any element is an array (nested) — use json_encode
            foreach ($value as $v) {
                if (is_array($v) || is_object($v)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }
            }
            return implode(', ', array_map('strval', $value));
        }

        return (string) $value;
    }

    private function humanizeRole(string $role): string
    {
        return match ($role) {
            'admin'   => __('audit.role_admin'),
            'manager' => __('audit.role_manager'),
            'viewer'  => __('audit.role_viewer'),
            default   => $role,
        };
    }

    /** Humanize all keys/values in a data array for the detail drawer */
    private function humanizeData(array $data, array $lookups): array
    {
        $result = [];
        foreach ($data as $field => $value) {
            $label = $this->fieldLabel($field);
            $result[$label] = $this->humanizeValue($field, $value, $lookups);
        }
        return $result;
    }
}
