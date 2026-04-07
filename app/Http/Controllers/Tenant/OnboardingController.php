<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateCRMFromAI;
use App\Support\PipelineTemplates;
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
            'tenant'           => $tenant,
            'user'             => auth()->user(),
            'pipelineTemplates' => PipelineTemplates::all(),
            'nicheToCategory'  => self::nicheToCategory(),
        ]);
    }

    /**
     * Mapping niche (wizard) → category (PipelineTemplates).
     * Mantém alinhado com self::NICHE_TO_CATEGORY do GenerateCRMFromAI.
     */
    public static function nicheToCategory(): array
    {
        return [
            'imobiliario' => 'imobiliaria',
            'estetica'    => 'beleza_estetica',
            'educacao'    => 'educacao',
            'saude'       => 'saude',
            'varejo'      => 'vendas_b2c',
            'b2b'         => 'servicos_b2b',
            'tecnologia'  => 'tecnologia_saas',
            'outro'       => null,
        ];
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
            'company_name'           => 'required|string|max:150',
            'niche'                  => 'required|string|max:50',
            'channels'               => 'required|array|min:1',
            'channels.*'             => 'string|max:30',
            'sales_process'          => 'nullable|string|max:500',
            'difficulty'             => 'required|string|max:50',
            'team_size'              => 'required|string|max:20',
            'pipeline_template_slug' => 'nullable|string|max:100',
        ]);

        // Valida que o slug do template realmente existe (se foi enviado)
        if (! empty($data['pipeline_template_slug'])
            && PipelineTemplates::find($data['pipeline_template_slug']) === null) {
            return response()->json([
                'success' => false,
                'message' => 'Template de pipeline inválido.',
            ], 422);
        }

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
            'sales_process' => $data['sales_process'] ?? '',
            'difficulty'    => $data['difficulty'],
            'team_size'     => $data['team_size'],
            'locale'        => $tenant->locale ?? 'pt_BR',
        ];

        $templateSlug = $data['pipeline_template_slug'] ?? null;

        // Persistir answers + template em sessão pra suportar retry
        session([
            'onboarding_answers'       => $answers,
            'onboarding_template_slug' => $templateSlug,
        ]);

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
            GenerateCRMFromAI::dispatch($tenant, $answers, $templateSlug);
        } catch (\Throwable) {
            // Redis not available (local dev) — run synchronously
            GenerateCRMFromAI::dispatchSync($tenant, $answers, $templateSlug);
            $ranSync = true;
        }

        return response()->json([
            'success'  => true,
            'redirect' => $ranSync ? route('onboarding.result') : route('onboarding.loading'),
        ]);
    }

    /**
     * Re-dispatch o job de geração da IA usando os answers da sessão.
     * Usado quando o user clica em "Tentar novamente" na result page após
     * a IA ter falhado.
     */
    public function retry(Request $request): JsonResponse
    {
        $tenant   = auth()->user()->tenant;
        $answers  = session('onboarding_answers');
        $template = session('onboarding_template_slug');

        if (! $answers) {
            return response()->json([
                'success' => false,
                'message' => 'Sessão de onboarding expirou. Recarregue a página.',
            ], 400);
        }

        // Reset progress cache
        $cacheKey = "onboarding:progress:{$tenant->id}";
        Cache::put($cacheKey, [
            'status'    => 'processing',
            'completed' => [],
            'total'     => 8,
            'error'     => null,
        ], 600);

        $ranSync = false;
        try {
            GenerateCRMFromAI::dispatch($tenant, $answers, $template);
        } catch (\Throwable) {
            GenerateCRMFromAI::dispatchSync($tenant, $answers, $template);
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
     * Result page — shows what was actually generated, real counts, real errors.
     */
    public function result(): View
    {
        $tenant = auth()->user()->tenant;

        $progress = Cache::get("onboarding:progress:{$tenant->id}", [
            'status'    => 'pending',
            'completed' => [],
            'total'     => 8,
            'error'     => null,
        ]);

        $result = Cache::get("onboarding:result:{$tenant->id}", []);

        $status = $progress['status'] ?? 'pending';

        // Só marca onboarding como completo quando o job realmente terminou OK.
        // Se status === 'error', deixa pendente pra user poder usar o botão "Tentar novamente".
        if ($status === 'done' && $tenant && $tenant->onboarding_completed_at === null) {
            $tenant->update(['onboarding_completed_at' => now()]);
        }

        return view('tenant.onboarding.result', [
            'tenant'   => $tenant,
            'status'   => $status,
            'error'    => $progress['error'] ?? null,
            'fallback' => $progress['fallback'] ?? false,
            'result'   => $result,
        ]);
    }
}
