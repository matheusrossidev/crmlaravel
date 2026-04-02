<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateCRMFromAI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if ($tenant && $tenant->onboarding_completed_at !== null) {
            return redirect()->route('dashboard');
        }

        return view('tenant.onboarding.index', [
            'tenant' => $tenant,
            'user'   => auth()->user(),
        ]);
    }

    public function skip(): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if ($tenant && $tenant->onboarding_completed_at === null) {
            $tenant->update(['onboarding_completed_at' => now()]);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Receive wizard answers and dispatch AI generation job.
     */
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name'  => 'required|string|max:150',
            'niche'         => 'required|string|max:50',
            'channels'      => 'required|array|min:1',
            'channels.*'    => 'string|max:30',
            'sales_process' => 'required|string|max:500',
            'difficulty'    => 'required|string|max:50',
            'team_size'     => 'required|string|max:20',
        ]);

        $user   = auth()->user();
        $tenant = $user->tenant;

        // Upload logo if present
        if ($request->hasFile('logo')) {
            $request->validate(['logo' => ['nullable', 'file', 'max:2048', new \App\Rules\SafeImage]]);

            $file     = $request->file('logo');
            $filename = $tenant->id . '.' . $file->extension();
            Storage::disk('public')->putFileAs('workspace-logos', $file, $filename);
            $tenant->update(['logo' => Storage::disk('public')->url('workspace-logos/' . $filename)]);
        }

        // Update company name
        $tenant->update(['name' => $data['company_name']]);

        // Store answers for the AI job
        $answers = [
            'niche'         => $data['niche'],
            'channels'      => $data['channels'],
            'sales_process' => $data['sales_process'],
            'difficulty'    => $data['difficulty'],
            'team_size'     => $data['team_size'],
            'locale'        => $tenant->locale ?? 'pt_BR',
        ];

        // Reset progress cache
        $cacheKey = "onboarding:progress:{$tenant->id}";
        Cache::put($cacheKey, [
            'status'    => 'processing',
            'completed' => [],
            'total'     => 8,
            'error'     => null,
        ], 600);

        // Dispatch AI generation job
        $ranSync = false;
        try {
            GenerateCRMFromAI::dispatch($tenant, $answers);
        } catch (\Throwable) {
            // Redis not available (local dev) — run synchronously
            GenerateCRMFromAI::dispatchSync($tenant, $answers);
            $ranSync = true;
        }

        return response()->json([
            'success'  => true,
            'redirect' => $ranSync ? route('onboarding.result') : route('onboarding.loading'),
        ]);
    }

    /**
     * Loading page — shows progress while AI generates.
     */
    public function loading(): View|RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if ($tenant && $tenant->onboarding_completed_at !== null) {
            return redirect()->route('dashboard');
        }

        return view('tenant.onboarding.loading', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Poll progress from cache (called by JS on loading page).
     */
    public function progress(): JsonResponse
    {
        $tenant   = auth()->user()->tenant;
        $cacheKey = "onboarding:progress:{$tenant->id}";

        $progress = Cache::get($cacheKey, [
            'status'    => 'pending',
            'completed' => [],
            'total'     => 8,
            'error'     => null,
        ]);

        return response()->json($progress);
    }

    /**
     * Result page — shows what was generated.
     */
    public function result(): View
    {
        $tenant = auth()->user()->tenant;

        // Mark onboarding as complete if not already (safety net)
        if ($tenant && $tenant->onboarding_completed_at === null) {
            $tenant->update(['onboarding_completed_at' => now()]);
        }

        return view('tenant.onboarding.result', [
            'tenant' => $tenant,
        ]);
    }
}
