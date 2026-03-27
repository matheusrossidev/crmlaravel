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
        $locale = 'pt_BR';

        if (auth()->check()) {
            // Authenticated: use tenant locale
            $tenant = auth()->user()->tenant;
            if ($tenant && in_array($tenant->locale, self::SUPPORTED_LOCALES, true)) {
                $locale = $tenant->locale;
            }
        } else {
            // Guest: check ?lang= param → session → browser Accept-Language
            $langParam = $request->query('lang');
            if ($langParam && in_array($langParam, self::SUPPORTED_LOCALES, true)) {
                $locale = $langParam;
                session(['guest_locale' => $locale]);
            } elseif (session('guest_locale') && in_array(session('guest_locale'), self::SUPPORTED_LOCALES, true)) {
                $locale = session('guest_locale');
            } else {
                $locale = $this->detectFromBrowser($request);
            }
        }

        App::setLocale($locale);

        return $next($request);
    }

    private function detectFromBrowser(Request $request): string
    {
        $accept = $request->header('Accept-Language', '');
        if (! $accept) {
            return 'pt_BR';
        }

        // Parse Accept-Language: en-US,en;q=0.9,pt-BR;q=0.8
        if (preg_match('/\ben[-_]/', $accept)) {
            return 'en';
        }

        return 'pt_BR';
    }
}
