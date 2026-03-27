<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['pt_BR', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'pt_BR'; // default

        if (auth()->check()) {
            $tenant = auth()->user()->tenant;
            if ($tenant && in_array($tenant->locale, self::SUPPORTED_LOCALES, true)) {
                $locale = $tenant->locale;
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
