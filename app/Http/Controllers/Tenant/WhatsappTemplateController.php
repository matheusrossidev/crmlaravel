<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Services\Whatsapp\WhatsappTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WhatsappTemplateController extends Controller
{
    public function __construct(private readonly WhatsappTemplateService $service) {}

    public function index()
    {
        $templates = WhatsappTemplate::query()
            ->with('instance:id,label,phone_number,display_name')
            ->orderByRaw("FIELD(status, 'APPROVED', 'PENDING', 'IN_APPEAL', 'REJECTED', 'PAUSED', 'DISABLED')")
            ->orderByDesc('created_at')
            ->get();

        $instances = $this->cloudApiInstances();

        return view('tenant.settings.templates.index', compact('templates', 'instances'));
    }

    public function create()
    {
        $instances = $this->cloudApiInstances();

        if ($instances->isEmpty()) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Conecte uma instância Cloud API antes de criar templates.');
        }

        return view('tenant.settings.templates.create', compact('instances'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'whatsapp_instance_id' => ['required', 'integer'],
            'name'                 => ['required', 'string', 'max:64'],
            'language'             => ['required', 'string', 'max:10'],
            'category'             => ['required', 'in:UTILITY,MARKETING,AUTHENTICATION'],
            'body'                 => ['required', 'string', 'max:1024'],
            'footer'               => ['nullable', 'string', 'max:60'],
            'header'               => ['nullable', 'array'],
            'header.type'          => ['nullable', 'in:TEXT,IMAGE,VIDEO,DOCUMENT'],
            'header.text'          => ['nullable', 'string', 'max:60'],
            'header.sample'        => ['nullable', 'string', 'max:60'],
            'header.sample_handle' => ['nullable', 'string', 'max:500'],
            'samples'              => ['nullable', 'array'],
            'samples.*'            => ['string', 'max:200'],
            'sample_labels'        => ['nullable', 'array'],
            'sample_labels.*'      => ['string', 'max:40'],
            'buttons'              => ['nullable', 'array', 'max:10'],
        ]);

        $instance = WhatsappInstance::query()
            ->where('id', $data['whatsapp_instance_id'])
            ->where('provider', 'cloud_api')
            ->firstOrFail();

        try {
            $template = $this->service->create($instance, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('settings.whatsapp-templates.index')
            ->with('success', __('wa_templates.toast_created'));
    }

    public function show(WhatsappTemplate $template)
    {
        $template->load('instance:id,label,phone_number,display_name');

        return view('tenant.settings.templates.show', compact('template'));
    }

    public function destroy(WhatsappTemplate $template): RedirectResponse
    {
        $this->service->delete($template);

        return redirect()
            ->route('settings.whatsapp-templates.index')
            ->with('success', __('wa_templates.toast_deleted'));
    }

    public function sync(): JsonResponse
    {
        $totals = ['created' => 0, 'updated' => 0, 'removed' => 0];
        $errors = [];

        foreach ($this->cloudApiInstances() as $instance) {
            $r = $this->service->syncFromMeta($instance);
            $totals['created'] += $r['created'];
            $totals['updated'] += $r['updated'];
            $totals['removed'] += $r['removed'];
            if (! empty($r['error'])) {
                $errors[] = "Instance #{$instance->id}: {$r['error']}";
            }
        }

        return response()->json([
            'success' => empty($errors),
            'totals'  => $totals,
            'errors'  => $errors,
        ]);
    }

    /**
     * JSON endpoint usado pelo modal do chat.
     */
    public function apiList(Request $request): JsonResponse
    {
        $query = WhatsappTemplate::query()
            ->where('status', 'APPROVED');

        if ($instanceId = $request->integer('instance_id')) {
            $query->where('whatsapp_instance_id', $instanceId);
        }

        $templates = $query->orderBy('name')->get()->map(function (WhatsappTemplate $t) {
            return [
                'id'               => $t->id,
                'name'             => $t->name,
                'language'         => $t->language,
                'category'         => $t->category,
                'components'       => $t->components,
                'variables'        => $t->variables,
                'sample_variables' => $t->sample_variables,
            ];
        });

        return response()->json(['templates' => $templates]);
    }

    /**
     * Envia template numa conversa Cloud API. Chamado pelo modal do chat.
     */
    public function send(Request $request, WhatsappConversation $conversation): JsonResponse
    {
        if (session()->has('impersonating_tenant_id')) {
            return response()->json(['error' => 'Acesso somente leitura.'], 403);
        }

        $data = $request->validate([
            'template_id'  => ['required', 'integer'],
            'variables'    => ['nullable', 'array'],
            'header_media' => ['nullable', 'array'],
        ]);

        /** @var WhatsappTemplate $template */
        $template = WhatsappTemplate::findOrFail($data['template_id']);
        $instance = $conversation->instance;

        if (! $instance || ! $instance->isCloudApi() || $instance->status !== 'connected') {
            return response()->json(['error' => 'Conversa não é Cloud API ou instância desconectada.'], 422);
        }

        $result = $this->service->send(
            $instance,
            (string) $conversation->phone,
            $template,
            (array) ($data['variables'] ?? []),
            $data['header_media'] ?? null,
        );

        if (isset($result['error'])) {
            $msg = is_string($result['error']) ? $result['error'] : 'send_failed';
            return response()->json(['error' => 'Falha ao enviar template: ' . $msg], 422);
        }

        // Monta preview do body com variáveis substituídas pra salvar como body da mensagem local
        $preview = $this->buildPreview($template, (array) ($data['variables'] ?? []));

        $message = WhatsappMessage::create([
            'tenant_id'        => activeTenantId(),
            'conversation_id'  => $conversation->id,
            'cloud_message_id' => $result['id'] ?? null,
            'direction'        => 'outbound',
            'type'             => 'template',
            'body'             => $preview,
            'user_id'          => auth()->id(),
            'sent_by'          => 'human',
            'ack'              => 'sent',
            'sent_at'          => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $message->id,
                'direction'  => 'outbound',
                'type'       => 'template',
                'body'       => $message->body,
                'ack'        => $message->ack,
                'is_deleted' => false,
                'sent_at'    => $message->sent_at->toISOString(),
                'user_name'  => auth()->user()->name,
            ],
        ]);
    }

    /**
     * Upload de mídia de exemplo pra submissão de template com header IMAGE/VIDEO/DOCUMENT.
     * Meta exige URL pública no momento da criação do template — usamos storage público nosso.
     */
    public function uploadSample(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:16384', 'mimes:jpg,jpeg,png,webp,mp4,pdf'],
        ]);

        $file = $request->file('file');
        $path = $file->store('whatsapp-templates/samples', 'public');

        return response()->json([
            'success'       => true,
            'url'           => Storage::disk('public')->url($path),
            'path'          => $path,
            'mime'          => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
        ]);
    }

    private function cloudApiInstances()
    {
        return WhatsappInstance::query()
            ->where('provider', 'cloud_api')
            ->orderBy('label')
            ->get();
    }

    private function buildPreview(WhatsappTemplate $template, array $variables): string
    {
        $body = '';
        foreach ((array) $template->components as $c) {
            if (strtoupper((string) ($c['type'] ?? '')) === 'BODY') {
                $body = (string) ($c['text'] ?? '');
                break;
            }
        }

        $replaced = preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function ($m) use ($variables) {
            $id = $m[1];
            return (string) ($variables[$id] ?? $variables[(int) $id] ?? $m[0]);
        }, $body);

        return (string) $replaced;
    }
}
