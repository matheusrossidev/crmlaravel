<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Models\WhatsappInstance;
use App\Models\WhatsappQuickMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScheduledMessageController extends Controller
{
    public function index(Lead $lead): JsonResponse
    {
        $items = ScheduledMessage::where('lead_id', $lead->id)
            ->with('createdBy:id,name')
            ->orderBy('send_at')
            ->get()
            ->map(fn (ScheduledMessage $s) => $this->format($s));

        return response()->json(['data' => $items]);
    }

    public function store(Lead $lead, Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'             => 'required|in:text,image,document',
            'body'             => 'nullable|string|max:4000',
            'file'             => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt|max:25600',
            'send_at'          => 'required|date|after:now',
            'quick_message_id' => 'nullable|integer|exists:whatsapp_quick_messages,id',
            'instance_id'      => 'nullable|integer|exists:whatsapp_instances,id',
        ], [
            'type.required'       => 'Selecione o tipo de mensagem.',
            'type.in'             => 'Tipo inválido.',
            'body.max'            => 'A mensagem pode ter no máximo 4000 caracteres.',
            'file.mimes'          => 'Formato de arquivo não suportado.',
            'file.max'            => 'O arquivo pode ter no máximo 25 MB.',
            'send_at.required'    => 'Informe a data e hora do envio.',
            'send_at.after'       => 'A data de envio deve ser no futuro.',
        ]);

        if ($data['type'] === 'text' && empty($data['body'])) {
            return response()->json(['error' => 'O texto da mensagem é obrigatório.'], 422);
        }

        if (in_array($data['type'], ['image', 'document']) && ! $request->hasFile('file')) {
            return response()->json(['error' => 'O arquivo é obrigatório para este tipo de mensagem.'], 422);
        }

        // Resolver instance_id: explicito do form > conversa do lead > primary do tenant
        $instanceId = $data['instance_id'] ?? null;
        if (! $instanceId && $lead->whatsappConversation?->instance_id) {
            $instanceId = $lead->whatsappConversation->instance_id;
        }
        if (! $instanceId) {
            $instanceId = WhatsappInstance::resolvePrimary($lead->tenant_id)?->id;
        }

        // Validar que a instance pertence ao tenant do lead (defesa contra request forjado)
        if ($instanceId) {
            $owns = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $instanceId)
                ->where('tenant_id', $lead->tenant_id)
                ->exists();
            if (! $owns) {
                return response()->json(['error' => 'Instância inválida.'], 422);
            }
        }

        $mediaPath     = null;
        $mediaMime     = null;
        $mediaFilename = null;

        if ($request->hasFile('file')) {
            $file          = $request->file('file');
            $mediaPath     = $file->store('whatsapp/scheduled', 'public');
            $mediaMime     = $file->getMimeType();
            $mediaFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file->getClientOriginalName()));
        }

        $scheduled = ScheduledMessage::create([
            'tenant_id'        => auth()->user()->tenant_id,
            'lead_id'          => $lead->id,
            'conversation_id'  => $lead->whatsappConversation?->id,
            'instance_id'      => $instanceId,
            'created_by'       => auth()->id(),
            'type'             => $data['type'],
            'body'             => $data['body'] ?? null,
            'media_path'       => $mediaPath,
            'media_mime'       => $mediaMime,
            'media_filename'   => $mediaFilename,
            'quick_message_id' => $data['quick_message_id'] ?? null,
            'send_at'          => $data['send_at'],
            'status'           => 'pending',
        ]);

        $scheduled->load('createdBy:id,name');

        return response()->json(['success' => true, 'item' => $this->format($scheduled)], 201);
    }

    public function destroy(Lead $lead, ScheduledMessage $scheduled): JsonResponse
    {
        if ($scheduled->lead_id !== $lead->id) {
            return response()->json(['error' => 'Não encontrado.'], 404);
        }

        if ($scheduled->status !== 'pending') {
            return response()->json(['error' => 'Só é possível cancelar mensagens pendentes.'], 422);
        }

        $scheduled->update(['status' => 'cancelled']);

        return response()->json(['success' => true]);
    }

    private function format(ScheduledMessage $s): array
    {
        $mediaUrl = $s->media_path ? Storage::disk('public')->url($s->media_path) : null;

        return [
            'id'             => $s->id,
            'type'           => $s->type,
            'body'           => $s->body,
            'media_url'      => $mediaUrl,
            'media_filename' => $s->media_filename,
            'media_mime'     => $s->media_mime,
            'status'         => $s->status,
            'send_at'        => $s->send_at?->toISOString(),
            'send_at_human'  => $s->send_at?->translatedFormat('d/m/Y \à\s H:i'),
            'sent_at'        => $s->sent_at?->toISOString(),
            'error'          => $s->error,
            'created_by'     => $s->createdBy?->name,
        ];
    }
}
