<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NpsSurvey;
use App\Models\SurveyResponse;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SurveyPublicController extends Controller
{
    public function showByUuid(string $uuid): View|RedirectResponse
    {
        $response = SurveyResponse::where('uuid', $uuid)
            ->with('survey', 'lead:id,name')
            ->first();

        if (!$response) {
            abort(404);
        }

        if ($response->status === 'answered') {
            return view('survey.thanks', [
                'message' => $response->survey?->thank_you_message,
                'tenant'  => Tenant::find($response->tenant_id),
                'alreadyAnswered' => true,
            ]);
        }

        if ($response->isExpired()) {
            return view('survey.expired', [
                'tenant' => Tenant::find($response->tenant_id),
            ]);
        }

        $tenant = Tenant::find($response->tenant_id);

        return view('survey.public', [
            'response' => $response,
            'survey'   => $response->survey,
            'tenant'   => $tenant,
            'leadName' => $response->lead?->name,
        ]);
    }

    public function answer(string $uuid, Request $request): View
    {
        $response = SurveyResponse::where('uuid', $uuid)
            ->with('survey')
            ->first();

        if (!$response || $response->status === 'answered') {
            abort(404);
        }

        $request->validate([
            'score'   => 'required|integer|min:0|max:10',
            'comment' => 'nullable|string|max:2000',
        ]);

        $response->update([
            'score'       => (int) $request->input('score'),
            'comment'     => $request->input('comment'),
            'status'      => 'answered',
            'answered_at' => now(),
        ]);

        $tenant = Tenant::find($response->tenant_id);

        return view('survey.thanks', [
            'message'         => $response->survey?->thank_you_message,
            'tenant'          => $tenant,
            'alreadyAnswered' => false,
        ]);
    }

    public function showBySlug(string $slug): RedirectResponse
    {
        $survey = NpsSurvey::where('slug', $slug)->where('is_active', true)->first();

        if (!$survey) {
            abort(404);
        }

        // Create a response on-the-fly for anonymous access
        $uuid = (string) Str::uuid();
        SurveyResponse::create([
            'uuid'       => $uuid,
            'tenant_id'  => $survey->tenant_id,
            'survey_id'  => $survey->id,
            'status'     => 'pending',
            'sent_at'    => now(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        return redirect("/s/{$uuid}");
    }
}
