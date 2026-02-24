<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\CustomFieldDefinition;
use App\Models\Pipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function index(): View
    {
        $apiKeys      = ApiKey::orderByDesc('created_at')->get();
        $customFields = CustomFieldDefinition::where('is_active', true)
                            ->orderBy('sort_order')
                            ->get(['id', 'name', 'label', 'field_type']);
        $pipelines    = Pipeline::with(['stages' => fn ($q) => $q->orderBy('position')])
                            ->orderBy('sort_order')
                            ->get();

        return view('tenant.settings.api-keys', compact('apiKeys', 'customFields', 'pipelines'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        // Gera key: "crm_" + 56 hex chars (28 bytes) = 60 chars total
        $rawKey  = 'crm_' . bin2hex(random_bytes(28));
        $keyHash = hash('sha256', $rawKey);
        $prefix  = substr($rawKey, 0, 12) . '...';

        $apiKey = ApiKey::create([
            'name'       => $request->input('name'),
            'key_hash'   => $keyHash,
            'key_prefix' => $prefix,
            'is_active'  => true,
        ]);

        // Retorna o raw key UMA ÚNICA VEZ — nunca mais será exibido
        return response()->json([
            'success' => true,
            'raw_key' => $rawKey,
            'api_key' => [
                'id'         => $apiKey->id,
                'name'       => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'created_at' => $apiKey->created_at?->format('d/m/Y H:i'),
            ],
        ], 201);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $apiKey->delete();

        return response()->json(['success' => true]);
    }
}
