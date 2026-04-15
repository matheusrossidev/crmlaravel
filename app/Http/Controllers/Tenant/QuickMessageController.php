<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappQuickMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuickMessageController extends Controller
{
    public function index(): JsonResponse
    {
        $messages = WhatsappQuickMessage::orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'body', 'image_path', 'image_mime', 'image_filename', 'sort_order'])
            ->map(function ($m) {
                $m->image_url = $m->image_path ? asset('storage/' . $m->image_path) : null;
                return $m;
            });

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'body'  => 'nullable|string|max:2000',
            'image' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif', new \App\Rules\SafeImage],
        ]);

        // Ao menos body ou image é obrigatório
        if (empty($data['body']) && ! $request->hasFile('image')) {
            return response()->json(['success' => false, 'message' => 'Informe o texto ou adicione uma imagem.'], 422);
        }

        $data['sort_order'] = (int) WhatsappQuickMessage::max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $data['image_path']     = $file->store('quick-messages', 'public');
            $data['image_mime']     = $file->getMimeType();
            $data['image_filename'] = $file->getClientOriginalName();
        }

        unset($data['image']);
        $message = WhatsappQuickMessage::create($data);
        $message->image_url = $message->image_path ? asset('storage/' . $message->image_path) : null;

        return response()->json(['success' => true, 'message' => $message], 201);
    }

    public function update(Request $request, WhatsappQuickMessage $qm): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:100',
            'body'         => 'nullable|string|max:2000',
            'image'        => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif', new \App\Rules\SafeImage],
            'remove_image' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_image') && $qm->image_path) {
            Storage::disk('public')->delete($qm->image_path);
            $data['image_path']     = null;
            $data['image_mime']     = null;
            $data['image_filename'] = null;
        }

        if ($request->hasFile('image')) {
            // Remove antiga
            if ($qm->image_path) {
                Storage::disk('public')->delete($qm->image_path);
            }
            $file = $request->file('image');
            $data['image_path']     = $file->store('quick-messages', 'public');
            $data['image_mime']     = $file->getMimeType();
            $data['image_filename'] = $file->getClientOriginalName();
        }

        unset($data['image'], $data['remove_image']);
        $qm->update($data);
        $qm->image_url = $qm->image_path ? asset('storage/' . $qm->image_path) : null;

        return response()->json(['success' => true, 'message' => $qm]);
    }

    public function destroy(WhatsappQuickMessage $qm): JsonResponse
    {
        if ($qm->image_path) {
            Storage::disk('public')->delete($qm->image_path);
        }
        $qm->delete();

        return response()->json(['success' => true]);
    }
}
