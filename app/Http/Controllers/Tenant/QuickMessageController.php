<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappQuickMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickMessageController extends Controller
{
    public function index(): JsonResponse
    {
        $messages = WhatsappQuickMessage::orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'body', 'sort_order']);

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'body'  => 'required|string|max:2000',
        ]);

        $data['sort_order'] = WhatsappQuickMessage::max('sort_order') + 1;

        $message = WhatsappQuickMessage::create($data);

        return response()->json(['success' => true, 'message' => $message], 201);
    }

    public function update(Request $request, WhatsappQuickMessage $qm): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'body'  => 'required|string|max:2000',
        ]);

        $qm->update($data);

        return response()->json(['success' => true, 'message' => $qm]);
    }

    public function destroy(WhatsappQuickMessage $qm): JsonResponse
    {
        $qm->delete();

        return response()->json(['success' => true]);
    }
}
