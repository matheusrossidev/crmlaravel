<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PlanLimitChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proactive plan-limit enforcement.
 *
 * Usage in routes:  ->middleware('plan.limit:ai_agents')
 *
 * Blocks both GET (create form) and POST (store) requests
 * when the tenant has reached the resource limit.
 */
class CheckPlanLimit
{
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $message = PlanLimitChecker::check($resource);

        if ($message) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'       => false,
                    'message'       => $message,
                    'limit_reached' => true,
                ], 422);
            }

            return redirect()->back()->with('limit_error', $message);
        }

        return $next($request);
    }
}
