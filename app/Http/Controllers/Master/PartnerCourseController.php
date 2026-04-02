<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PartnerCourse;
use App\Models\PartnerLesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PartnerCourseController extends Controller
{
    use Traits\ChecksMasterPermission;

    public function index(): View
    {
        $this->authorizeModule('partner_courses');
        $courses = PartnerCourse::with(['lessons' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('lessons')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return view('master.partner-courses.index', compact('courses'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:191',
            'description'  => 'nullable|string|max:1000',
            'sort_order'   => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'cover'        => ['nullable', 'file', 'max:2048', new \App\Rules\SafeImage],
        ]);

        $course = PartnerCourse::create([
            'title'        => $data['title'],
            'slug'         => Str::slug($data['title']) . '-' . Str::random(4),
            'description'  => $data['description'] ?? null,
            'sort_order'   => $data['sort_order'] ?? 0,
            'is_published' => $data['is_published'] ?? false,
        ]);

        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('partner-courses', 'public');
            $course->update(['cover_image' => $path]);
        }

        return response()->json(['success' => true, 'course' => $course]);
    }

    public function update(Request $request, PartnerCourse $course): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:191',
            'description'  => 'nullable|string|max:1000',
            'sort_order'   => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'cover'        => ['nullable', 'file', 'max:2048', new \App\Rules\SafeImage],
        ]);

        $course->update([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? $course->description,
            'sort_order'   => $data['sort_order'] ?? $course->sort_order,
            'is_published' => $data['is_published'] ?? $course->is_published,
        ]);

        if ($request->hasFile('cover')) {
            if ($course->cover_image) {
                \Storage::disk('public')->delete($course->cover_image);
            }
            $path = $request->file('cover')->store('partner-courses', 'public');
            $course->update(['cover_image' => $path]);
        }

        return response()->json(['success' => true, 'course' => $course->fresh()]);
    }

    public function destroy(PartnerCourse $course): JsonResponse
    {
        if ($course->cover_image) {
            \Storage::disk('public')->delete($course->cover_image);
        }
        $course->delete();

        return response()->json(['success' => true]);
    }

    // ── Lessons ──────────────────────────────────────────────────────

    public function storeLesson(Request $request, PartnerCourse $course): JsonResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:191',
            'description'      => 'nullable|string|max:1000',
            'video_url'        => 'nullable|string|max:500',
            'duration_minutes' => 'nullable|integer|min:0',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        $lesson = $course->lessons()->create([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'video_url'        => $data['video_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 0,
            'sort_order'       => $data['sort_order'] ?? $course->lessons()->count(),
        ]);

        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    public function updateLesson(Request $request, PartnerLesson $lesson): JsonResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:191',
            'description'      => 'nullable|string|max:1000',
            'video_url'        => 'nullable|string|max:500',
            'duration_minutes' => 'nullable|integer|min:0',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        $lesson->update($data);

        return response()->json(['success' => true, 'lesson' => $lesson->fresh()]);
    }

    public function destroyLesson(PartnerLesson $lesson): JsonResponse
    {
        $lesson->delete();

        return response()->json(['success' => true]);
    }
}
