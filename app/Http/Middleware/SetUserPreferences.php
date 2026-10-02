<?php

namespace App\Http\Middleware;

use App\Models\UserPreference;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class SetUserPreferences
{
    /**
     * Handle an incoming request and apply user's language, timezone, date format, and currency preferences.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $preference = $user?->preference;

        // 1. Resolve Locale (Language)
        $locale = $preference?->locale
            ?? $request->header('X-User-Locale')
            ?? $this->detectAcceptLanguage($request->header('Accept-Language'))
            ?? config('app.locale', 'en');

        if (! array_key_exists($locale, UserPreference::SUPPORTED_LOCALES)) {
            $locale = 'en';
        }

        // Apply Laravel Core Locale
        App::setLocale($locale);

        // 2. Resolve Timezone
        $timezone = $preference?->timezone
            ?? $request->header('X-User-Timezone')
            ?? config('app.timezone', 'Asia/Karachi');

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'Asia/Karachi';
        }

        date_default_timezone_set($timezone);
        Config::set('app.timezone', $timezone);

        // 3. Resolve Currency
        $currency = $preference?->currency
            ?? $request->header('X-User-Currency')
            ?? 'PKR';

        if (! array_key_exists($currency, UserPreference::SUPPORTED_CURRENCIES)) {
            $currency = 'PKR';
        }

        // Apply Laravel Core Number Formatting Defaults
        Number::useCurrency($currency);
        Number::useLocale($locale);

        // Attach active preferences to request attributes for easy retrieval
        $request->attributes->set('active_locale', $locale);
        $request->attributes->set('active_timezone', $timezone);
        $request->attributes->set('active_currency', $currency);
        $request->attributes->set('user_preference', $preference);

        $response = $next($request);

        // Add response headers indicating applied localization settings
        $response->headers->set('X-Applied-Locale', $locale);
        $response->headers->set('X-Applied-Timezone', $timezone);
        $response->headers->set('X-Applied-Currency', $currency);

        return $response;
    }

    /**
     * Parse the client's Accept-Language header to find the best match.
     */
    private function detectAcceptLanguage(?string $acceptLanguage): ?string
    {
        if (! $acceptLanguage) {
            return null;
        }

        $locales = ['en', 'ur', 'ar', 'es', 'fr'];
        foreach ($locales as $loc) {
            if (stripos($acceptLanguage, $loc) !== false) {
                return $loc;
            }
        }

        return null;
    }
}
