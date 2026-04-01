<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PartnerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PartnerResourceController extends Controller
{
    use Traits\ChecksMasterPermission;

    public function index(): View
    {
        $this->authorizeModule('partner_resources');
        $resources = PartnerResource::orderBy('sort_order')->orderByDesc('created_at')->get();

        return view('master.partner-resources.index', compact('resources'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'              => 'required|string|max:191',
            'description'        => 'nullable|string|max:500',
            'content'            => 'nullable|string',
            'category'           => 'nullable|string|max:50',
            'sort_order'         => 'nullable|integer|min:0',
            'is_published'       => 'nullable|boolean',
            'cover'              => 'nullable|image|max:2048',
            'new_attachments'    => 'nullable|array',
            'new_attachments.*'  => 'file|max:20480',
        ]);

        $resource = PartnerResource::create([
            'title'        => $data['title'],
            'slug'         => Str::slug($data['title']) . '-' . Str::random(4),
            'description'  => $data['description'] ?? null,
            'content'      => $data['content'] ?? null,
            'category'     => $data['category'] ?? null,
            'sort_order'   => $data['sort_order'] ?? 0,
            'is_published' => $data['is_published'] ?? false,
        ]);

        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('partner-resources', 'public');
            $resource->update(['cover_image' => $path]);
        }

        // Handle attachments
        $attachments = $this->processAttachments($request, []);
        if (!empty($attachments)) {
            $resource->update(['attachments' => $attachments]);
        }

        return response()->json(['success' => true, 'resource' => $resource]);
    }

    public function update(Request $request, PartnerResource $resource): JsonResponse
    {
        $data = $request->validate([
            'title'              => 'required|string|max:191',
            'description'        => 'nullable|string|max:500',
            'content'            => 'nullable|string',
            'category'           => 'nullable|string|max:50',
            'sort_order'         => 'nullable|integer|min:0',
            'is_published'       => 'nullable|boolean',
            'cover'              => 'nullable|image|max:2048',
            'new_attachments'    => 'nullable|array',
            'new_attachments.*'  => 'file|max:20480',
        ]);

        $resource->update([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? $resource->description,
            'content'      => $data['content'] ?? $resource->content,
            'category'     => $data['category'] ?? $resource->category,
            'sort_order'   => $data['sort_order'] ?? $resource->sort_order,
            'is_published' => $data['is_published'] ?? $resource->is_published,
        ]);

        if ($request->hasFile('cover')) {
            if ($resource->cover_image) {
                \Storage::disk('public')->delete($resource->cover_image);
            }
            $path = $request->file('cover')->store('partner-resources', 'public');
            $resource->update(['cover_image' => $path]);
        }

        // Merge existing attachments with new ones
        $existing = $resource->attachments ?? [];
        $attachments = $this->processAttachments($request, $existing);
        $resource->update(['attachments' => $attachments]);

        return response()->json(['success' => true, 'resource' => $resource->fresh()]);
    }

    private function processAttachments(Request $request, array $existing): array
    {
        $attachments = $existing;

        if ($request->hasFile('new_attachments')) {
            foreach ($request->file('new_attachments') as $file) {
                $path = $file->store('partner-resource-files', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $this->formatBytes($file->getSize()),
                ];
            }
        }

        return $attachments;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1024) . ' KB';
    }

    public function destroy(PartnerResource $resource): JsonResponse
    {
        if ($resource->cover_image) {
            \Storage::disk('public')->delete($resource->cover_image);
        }
        $resource->delete();

        return response()->json(['success' => true]);
    }
}
