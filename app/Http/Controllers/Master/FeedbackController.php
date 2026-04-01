<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    use Traits\ChecksMasterPermission;
    private const TYPE_LABELS = [
        'new_feature' => 'Nova funcionalidade',
        'improvement' => 'Melhoria',
        'bug'         => 'Bug',
        'ux_ui'       => 'Interface/UX',
        'integration' => 'Integração',
        'other'       => 'Outro',
    ];

    private const AREA_LABELS = [
        'crm'         => 'CRM/Kanban',
        'chat'        => 'Chat/Inbox',
        'automations' => 'Automações',
        'sequences'   => 'Sequências',
        'ai_agents'   => 'Agentes IA',
        'chatbot'     => 'Chatbot',
        'reports'     => 'Relatórios',
        'goals'       => 'Metas',
        'settings'    => 'Configurações',
        'onboarding'  => 'Onboarding',
        'other'       => 'Outro',
    ];

    private const STATUS_LABELS = [
        'new'       => 'Novo',
        'reviewing' => 'Analisando',
        'planned'   => 'Planejado',
        'done'      => 'Feito',
        'dismissed' => 'Dispensado',
    ];

    private const IMPACT_LABELS = [
        'blocker' => 'Bloqueante',
        'high'    => 'Alto',
        'medium'  => 'Médio',
        'low'     => 'Baixo',
    ];

    public function index(Request $request): View
    {
        $this->authorizeModule('feedbacks');

        $query = Feedback::with(['user:id,name', 'tenant:id,name,plan'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }
        if ($request->filled('impact')) {
            $query->where('impact', $request->impact);
        }

        $feedbacks = $query->paginate(30)->withQueryString();
        $newCount  = Feedback::where('status', 'new')->count();

        return view('master.feedbacks.index', [
            'feedbacks'    => $feedbacks,
            'newCount'     => $newCount,
            'typeLabels'   => self::TYPE_LABELS,
            'areaLabels'   => self::AREA_LABELS,
            'statusLabels' => self::STATUS_LABELS,
            'impactLabels' => self::IMPACT_LABELS,
        ]);
    }

    public function show(Feedback $feedback): JsonResponse
    {
        $feedback->load(['user:id,name,email', 'tenant:id,name,plan']);

        return response()->json([
            'success'  => true,
            'feedback' => [
                'id'            => $feedback->id,
                'type'          => self::TYPE_LABELS[$feedback->type] ?? $feedback->type,
                'area'          => self::AREA_LABELS[$feedback->area] ?? $feedback->area,
                'title'         => $feedback->title,
                'description'   => $feedback->description,
                'impact'        => self::IMPACT_LABELS[$feedback->impact] ?? $feedback->impact,
                'priority'      => $feedback->priority,
                'evidence_path' => $feedback->evidence_path ? asset('storage/' . $feedback->evidence_path) : null,
                'can_contact'   => $feedback->can_contact,
                'contact_email' => $feedback->contact_email,
                'url_origin'    => $feedback->url_origin,
                'plan_name'     => $feedback->plan_name,
                'user_role'     => $feedback->user_role,
                'user_name'     => $feedback->user?->name,
                'user_email'    => $feedback->user?->email,
                'tenant_name'   => $feedback->tenant?->name,
                'status'        => $feedback->status,
                'admin_notes'   => $feedback->admin_notes,
                'created_at'    => $feedback->created_at?->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function updateStatus(Request $request, Feedback $feedback): JsonResponse
    {
        $data = $request->validate([
            'status'      => 'required|in:new,reviewing,planned,done,dismissed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $feedback->update($data);

        return response()->json(['success' => true]);
    }
}
