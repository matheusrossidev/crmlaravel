<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Renderiza o relatório público via /r/{hash}.
 *
 * Sem auth. Opcionalmente protegido por senha (session-based unlock).
 * Renderiza snapshot_json congelado na hora da geração.
 */
class PublicReportController extends Controller
{
    public function show(string $hash): View|RedirectResponse
    {
        $report = GeneratedReport::withoutGlobalScope('tenant')
            ->where('hash', $hash)
            ->first();

        if (! $report) {
            abort(404);
        }

        if ($report->isExpired()) {
            return response()->view('reports.public-expired', ['report' => $report], 410);
        }

        // Se tem senha e não está desbloqueado na sessão, mostra form
        if ($report->hasPassword() && ! session("report_unlocked:{$hash}")) {
            return view('reports.public-password', [
                'hash'  => $hash,
                'title' => $report->title,
            ]);
        }

        // Incrementa views (1ª vez por sessão)
        if (! session("report_viewed:{$hash}")) {
            $report->increment('views_count');
            $report->update(['last_viewed_at' => now()]);
            session(["report_viewed:{$hash}" => true]);
        }

        return view('reports.public', [
            'report'   => $report,
            'data'     => $report->snapshot_json,
            'filters'  => $report->filters_json ?? [],
        ]);
    }

    public function unlock(Request $request, string $hash): RedirectResponse
    {
        $request->validate(['password' => 'required|string|max:100']);

        // Rate limit: 5 tentativas / 10min por IP+hash
        $rlKey = "report-unlock:{$hash}:" . $request->ip();
        if (RateLimiter::tooManyAttempts($rlKey, 5)) {
            $seconds = RateLimiter::availableIn($rlKey);
            return back()->withErrors([
                'password' => "Muitas tentativas. Aguarde {$seconds} segundos.",
            ]);
        }

        $report = GeneratedReport::withoutGlobalScope('tenant')
            ->where('hash', $hash)
            ->first();

        if (! $report || $report->isExpired()) {
            abort(404);
        }

        if (! $report->hasPassword()) {
            return redirect("/r/{$hash}");
        }

        if (! Hash::check($request->input('password'), $report->password_hash)) {
            RateLimiter::hit($rlKey, 600);
            return back()->withErrors(['password' => 'Senha incorreta.']);
        }

        RateLimiter::clear($rlKey);
        session(["report_unlocked:{$hash}" => true]);

        return redirect("/r/{$hash}");
    }
}
