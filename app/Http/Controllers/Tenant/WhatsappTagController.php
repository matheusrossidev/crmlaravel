<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappTagController extends Controller
{
    public function index(): View
    {
        $tags = WhatsappTag::orderBy('sort_order')->orderBy('name')->get();

        return view('tenant.settings.whatsapp-tags', compact('tags'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $exists = WhatsappTag::where('name', $data['name'])->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Já existe uma tag com este nome.'], 422);
        }

        $data['sort_order'] = (int) (WhatsappTag::max('sort_order') ?? 0) + 1;

        $tag = WhatsappTag::create($data);

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function update(Request $request, WhatsappTag $tag): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $exists = WhatsappTag::where('name', $data['name'])
            ->where('id', '!=', $tag->id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Já existe uma tag com este nome.'], 422);
        }

        $tag->update($data);

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function destroy(WhatsappTag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(['success' => true]);
    }
}
