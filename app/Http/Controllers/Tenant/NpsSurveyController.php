<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\SendNpsSurveyJob;
use App\Models\Lead;
use App\Models\NpsSurvey;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NpsSurveyController extends Controller
{
    public function index(Request $request): View
    {
        $surveys = NpsSurvey::withCount(['responses', 'responses as answered_count' => fn ($q) => $q->where('status', 'answered')])
            ->orderByDesc('created_at')
            ->get();

        // Dashboard metrics
        $dateFrom = $request->input('from', now()->subDays(30)->toDateString());
        $dateTo   = $request->input('to', now()->toDateString());

        $responses = SurveyResponse::where('status', 'answered')
            ->whereBetween('answered_at', [$dateFrom, $dateTo . ' 23:59:59']);

        $totalAnswered = (clone $responses)->count();
        $promoters     = (clone $responses)->whereBetween('score', [9, 10])->count();
        $passives      = (clone $responses)->whereBetween('score', [7, 8])->count();
        $detractors    = (clone $responses)->whereBetween('score', [0, 6])->count();
        $npsScore      = $totalAnswered > 0
            ? round((($promoters - $detractors) / $totalAnswered) * 100, 1)
            : 0;
        $avgScore = (clone $responses)->avg('score') ?? 0;

        // Distribution 0-10
        $distribution = SurveyResponse::where('status', 'answered')
            ->whereBetween('answered_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('score, COUNT(*) as cnt')
            ->groupBy('score')
            ->orderBy('score')
            ->pluck('cnt', 'score');

        // Monthly NPS trend (last 6 months)
        $monthlyNps = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = now()->subMonths($i)->startOfMonth();
            $mEnd   = (clone $mStart)->endOfMonth();
            $mResps = SurveyResponse::where('status', 'answered')
                ->whereBetween('answered_at', [$mStart, $mEnd]);
            $mTotal = (clone $mResps)->count();
            $mProm  = (clone $mResps)->whereBetween('score', [9, 10])->count();
            $mDet   = (clone $mResps)->whereBetween('score', [0, 6])->count();
            $monthlyNps[] = [
                'label' => $mStart->format('M/y'),
                'nps'   => $mTotal > 0 ? round((($mProm - $mDet) / $mTotal) * 100, 1) : 0,
                'count' => $mTotal,
            ];
        }

        // By vendor
        $byVendor = SurveyResponse::where('survey_responses.status', 'answered')
            ->whereBetween('survey_responses.answered_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->whereNotNull('survey_responses.user_id')
            ->join('users', 'users.id', '=', 'survey_responses.user_id')
            ->selectRaw('users.name, users.id as user_id, COUNT(*) as total,
                SUM(CASE WHEN survey_responses.score >= 9 THEN 1 ELSE 0 END) as promoters,
                SUM(CASE WHEN survey_responses.score <= 6 THEN 1 ELSE 0 END) as detractors,
                ROUND(AVG(survey_responses.score), 1) as avg_score')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get();

        // Recent comments
        $recentComments = SurveyResponse::with('lead:id,name', 'user:id,name')
            ->where('status', 'answered')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderByDesc('answered_at')
            ->limit(10)
            ->get();

        return view('tenant.nps.index', compact(
            'surveys', 'dateFrom', 'dateTo', 'totalAnswered', 'promoters', 'passives',
            'detractors', 'npsScore', 'avgScore', 'distribution', 'monthlyNps',
            'byVendor', 'recentComments',
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'required|string|max:191',
            'type'               => 'required|in:nps,csat,custom',
            'question'           => 'required|string|max:500',
            'follow_up_question' => 'nullable|string|max:500',
            'trigger'            => 'required|in:lead_won,conversation_closed,manual',
            'delay_hours'        => 'nullable|integer|min:0|max:168',
            'send_via'           => 'required|in:whatsapp,link',
            'thank_you_message'  => 'nullable|string|max:500',
        ]);

        $slug = Str::slug($data['name']) . '-' . Str::random(6);

        $survey = NpsSurvey::create([
            ...$data,
            'slug'      => $slug,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'survey' => $survey]);
    }

    public function update(NpsSurvey $survey, Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'required|string|max:191',
            'question'           => 'required|string|max:500',
            'follow_up_question' => 'nullable|string|max:500',
            'trigger'            => 'required|in:lead_won,conversation_closed,manual',
            'delay_hours'        => 'nullable|integer|min:0|max:168',
            'send_via'           => 'required|in:whatsapp,link',
            'is_active'          => 'nullable|boolean',
            'thank_you_message'  => 'nullable|string|max:500',
        ]);

        $survey->update($data);

        return response()->json(['success' => true, 'survey' => $survey->fresh()]);
    }

    public function destroy(NpsSurvey $survey): JsonResponse
    {
        $survey->delete();
        return response()->json(['success' => true]);
    }

    public function sendBulk(NpsSurvey $survey, Request $request): JsonResponse
    {
        $request->validate(['lead_ids' => 'required|array', 'lead_ids.*' => 'integer']);

        $leads = Lead::whereIn('id', $request->input('lead_ids'))
            ->where('opted_out', false)
            ->whereNotNull('phone')
            ->get();

        $sent = 0;
        foreach ($leads as $lead) {
            $delay = $survey->delay_hours > 0 ? now()->addHours($survey->delay_hours) : now();
            SendNpsSurveyJob::dispatch($lead->id, $survey->id)->delay($delay);
            $sent++;
        }

        return response()->json(['success' => true, 'sent' => $sent]);
    }
}
