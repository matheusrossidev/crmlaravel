<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InstagramAutomation;
use App\Models\InstagramInstance;
use App\Services\InstagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstagramAutomationController extends Controller
{
    public function index(): View
    {
        $instance = InstagramInstance::where('status', 'connected')->first();

        $automations = InstagramAutomation::orderByDesc('created_at')->get();

        return view('tenant.settings.instagram-automations', compact('instance', 'automations'));
    }

    public function posts(Request $request): JsonResponse
    {
        $instance = InstagramInstance::where('status', 'connected')->first();

        if (! $instance) {
            return response()->json(['error' => 'Instagram não conectado.'], 422);
        }

        try {
            $token   = decrypt($instance->access_token);
            $service = new InstagramService($token);
            $after   = $request->string('after', '')->toString() ?: null;
            $result  = $service->getUserMedia($after);

            if (! empty($result['error'])) {
                return response()->json([
                    'error' => 'Falha ao buscar publicações. Verifique as permissões da conta.',
                ], 422);
            }

            $posts = collect($result['data'] ?? [])->map(function (array $item) {
                $caption = $item['caption'] ?? '';
                return [
                    'id'            => $item['id'],
                    'caption'       => mb_strlen($caption) > 80 ? mb_substr($caption, 0, 80) . '…' : $caption,
                    'thumbnail_url' => $item['thumbnail_url'] ?? $item['media_url'] ?? null,
                    'media_type'    => $item['media_type'] ?? 'IMAGE',
                    'timestamp'     => $item['timestamp'] ?? null,
                    'permalink'     => $item['permalink'] ?? null,
                ];
            })->values();

            $nextCursor = $result['paging']['cursors']['after'] ?? null;

            return response()->json([
                'data'        => $posts,
                'next_cursor' => $nextCursor,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erro ao buscar publicações: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'nullable|string|max:100',
            'media_id'      => 'nullable|string|max:191',
            'media_thumbnail_url' => 'nullable|string',
            'media_caption' => 'nullable|string',
            'keywords'      => 'required|array|min:1',
            'keywords.*'    => 'required|string|max:100',
            'match_type'    => 'nullable|in:any,all',
            'reply_comment' => 'nullable|string|max:2200',
            'dm_message'    => 'nullable|string|max:1000',
        ]);

        if (empty($data['reply_comment']) && empty($data['dm_message'])) {
            return response()->json([
                'error' => 'Defina pelo menos uma ação: resposta ao comentário ou DM.',
            ], 422);
        }

        $instance = InstagramInstance::where('status', 'connected')->first();

        if (! $instance) {
            return response()->json(['error' => 'Instagram não conectado.'], 422);
        }

        $automation = InstagramAutomation::create([
            'tenant_id'           => auth()->user()->tenant_id,
            'instance_id'         => $instance->id,
            'name'                => $data['name'] ?? null,
            'media_id'            => $data['media_id'] ?? null,
            'media_thumbnail_url' => $data['media_thumbnail_url'] ?? null,
            'media_caption'       => $data['media_caption'] ?? null,
            'keywords'            => $data['keywords'],
            'match_type'          => $data['match_type'] ?? 'any',
            'reply_comment'       => $data['reply_comment'] ?? null,
            'dm_message'          => $data['dm_message'] ?? null,
            'is_active'           => true,
        ]);

        return response()->json(['automation' => $automation], 201);
    }

    public function update(Request $request, InstagramAutomation $automation): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'nullable|string|max:100',
            'media_id'      => 'nullable|string|max:191',
            'media_thumbnail_url' => 'nullable|string',
            'media_caption' => 'nullable|string',
            'keywords'      => 'required|array|min:1',
            'keywords.*'    => 'required|string|max:100',
            'match_type'    => 'nullable|in:any,all',
            'reply_comment' => 'nullable|string|max:2200',
            'dm_message'    => 'nullable|string|max:1000',
        ]);

        if (empty($data['reply_comment']) && empty($data['dm_message'])) {
            return response()->json([
                'error' => 'Defina pelo menos uma ação: resposta ao comentário ou DM.',
            ], 422);
        }

        $automation->update($data);

        return response()->json(['automation' => $automation->fresh()]);
    }

    public function destroy(InstagramAutomation $automation): JsonResponse
    {
        $automation->delete();
        return response()->json(['ok' => true]);
    }

    public function toggleActive(InstagramAutomation $automation): JsonResponse
    {
        $automation->update(['is_active' => ! $automation->is_active]);
        return response()->json(['is_active' => $automation->is_active]);
    }
}
