<?php

declare(strict_types=1);

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerCertificate;
use App\Models\PartnerCourse;
use App\Models\PartnerLesson;
use App\Models\PartnerLessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PartnerCourseController extends Controller
{
    public function index(): View
    {
        $tenantId = auth()->user()->tenant_id;

        $courses = PartnerCourse::published()
            ->withCount('lessons')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($course) use ($tenantId) {
                $completedCount = PartnerLessonProgress::where('tenant_id', $tenantId)
                    ->whereIn('lesson_id', $course->lessons()->pluck('id'))
                    ->whereNotNull('completed_at')
                    ->count();

                $course->setAttribute('completed_lessons', $completedCount);
                $course->setAttribute('progress_pct', $course->lessons_count > 0
                    ? round(($completedCount / $course->lessons_count) * 100)
                    : 0);

                $course->setAttribute('has_certificate', PartnerCertificate::where('tenant_id', $tenantId)
                    ->where('course_id', $course->id)->exists());

                return $course;
            });

        return view('partner.courses.index', compact('courses'));
    }

    public function show(string $slug): View
    {
        $tenantId = auth()->user()->tenant_id;

        $course = PartnerCourse::where('slug', $slug)
            ->where('is_published', true)
            ->with('lessons')
            ->firstOrFail();

        $completedIds = PartnerLessonProgress::where('tenant_id', $tenantId)
            ->whereIn('lesson_id', $course->lessons->pluck('id'))
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        $certificate = PartnerCertificate::where('tenant_id', $tenantId)
            ->where('course_id', $course->id)
            ->first();

        $allCompleted = $course->lessons->count() > 0
            && count($completedIds) >= $course->lessons->count();

        return view('partner.courses.show', compact('course', 'completedIds', 'certificate', 'allCompleted'));
    }

    public function completeLesson(PartnerLesson $lesson): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        PartnerLessonProgress::updateOrCreate(
            ['tenant_id' => $tenantId, 'lesson_id' => $lesson->id],
            ['completed_at' => now(), 'created_at' => now()]
        );

        return response()->json(['success' => true]);
    }

    public function issueCertificate(PartnerCourse $course): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // Check all lessons completed
        $totalLessons = $course->lessons()->count();
        $completedCount = PartnerLessonProgress::where('tenant_id', $tenantId)
            ->whereIn('lesson_id', $course->lessons()->pluck('id'))
            ->whereNotNull('completed_at')
            ->count();

        if ($completedCount < $totalLessons) {
            return response()->json(['success' => false, 'message' => 'Complete todas as aulas primeiro.'], 422);
        }

        // Check if already issued
        $existing = PartnerCertificate::where('tenant_id', $tenantId)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return response()->json(['success' => true, 'certificate' => $existing]);
        }

        $cert = PartnerCertificate::create([
            'tenant_id'        => $tenantId,
            'course_id'        => $course->id,
            'certificate_code' => PartnerCertificate::generateCode(),
            'issued_at'        => now(),
            'created_at'       => now(),
        ]);

        return response()->json(['success' => true, 'certificate' => $cert]);
    }
}
