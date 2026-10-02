<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;

class UserPreferenceController extends Controller
{
    /**
     * Retrieve the authenticated user's preferences along with the supported options catalog.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user() ?? User::first();
        $preference = $user ? $user->getOrCreatePreference() : new UserPreference([
            'locale' => 'en',
            'timezone' => 'Asia/Karachi',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'currency' => 'PKR',
            'currency_symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]);

        $now = Carbon::now($preference->timezone);

        return response()->json([
            'preferences' => $preference,
            'preview' => [
                'formatted_date' => $preference->formatDate($now),
                'formatted_time' => $preference->formatTime($now),
                'formatted_datetime' => $preference->formatDateTime($now),
                'formatted_currency' => $preference->formatCurrency(245000.75),
                'formatted_number' => $preference->formatNumber(18432.50, 2),
                'sample_translation' => [
                    'dashboard' => trans('app.dashboard'),
                    'animals' => trans('app.animals'),
                    'milk_production' => trans('app.milk_production'),
                    'settings' => trans('app.settings'),
                    'saved_message' => trans('app.saved_successfully'),
                ],
            ],
            'options' => [
                'supported_locales' => array_values(UserPreference::SUPPORTED_LOCALES),
                'supported_currencies' => array_values(UserPreference::SUPPORTED_CURRENCIES),
                'supported_date_formats' => array_values(UserPreference::SUPPORTED_DATE_FORMATS),
                'supported_time_formats' => array_values(UserPreference::SUPPORTED_TIME_FORMATS),
                'common_timezones' => UserPreference::COMMON_TIMEZONES,
            ],
        ]);
    }

    /**
     * Update the authenticated user's preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_LOCALES)),
            'timezone' => 'required|string|timezone:all',
            'date_format' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_DATE_FORMATS)),
            'time_format' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_TIME_FORMATS)),
            'currency' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_CURRENCIES)),
            'currency_symbol_position' => 'nullable|string|in:before,after',
            'decimal_separator' => 'nullable|string|max:1',
            'thousands_separator' => 'nullable|string|max:1',
        ]);

        $user = $request->user() ?? User::firstOrFail();

        $preference = $user->preference()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        // Dynamically apply settings to current request
        App::setLocale($preference->locale);
        date_default_timezone_set($preference->timezone);
        Number::useCurrency($preference->currency);
        Number::useLocale($preference->locale);

        $now = Carbon::now($preference->timezone);

        return response()->json([
            'message' => trans('app.saved_successfully'),
            'preferences' => $preference,
            'preview' => [
                'formatted_date' => $preference->formatDate($now),
                'formatted_time' => $preference->formatTime($now),
                'formatted_datetime' => $preference->formatDateTime($now),
                'formatted_currency' => $preference->formatCurrency(245000.75),
                'formatted_number' => $preference->formatNumber(18432.50, 2),
                'sample_translation' => [
                    'dashboard' => trans('app.dashboard'),
                    'animals' => trans('app.animals'),
                    'milk_production' => trans('app.milk_production'),
                    'settings' => trans('app.settings'),
                    'saved_message' => trans('app.saved_successfully'),
                ],
            ],
        ]);
    }

    /**
     * Preview preferences formatting without persisting to database.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_LOCALES)),
            'timezone' => 'required|string|timezone:all',
            'date_format' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_DATE_FORMATS)),
            'time_format' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_TIME_FORMATS)),
            'currency' => 'required|string|in:'.implode(',', array_keys(UserPreference::SUPPORTED_CURRENCIES)),
            'sample_amount' => 'nullable|numeric',
        ]);

        $temp = new UserPreference($validated);
        $amount = (float) ($validated['sample_amount'] ?? 150000.00);

        App::setLocale($temp->locale);
        $now = Carbon::now($temp->timezone);

        return response()->json([
            'formatted_date' => $temp->formatDate($now),
            'formatted_time' => $temp->formatTime($now),
            'formatted_datetime' => $temp->formatDateTime($now),
            'formatted_currency' => $temp->formatCurrency($amount),
            'formatted_number' => $temp->formatNumber($amount, 2),
            'locale_info' => UserPreference::SUPPORTED_LOCALES[$temp->locale],
            'currency_info' => UserPreference::SUPPORTED_CURRENCIES[$temp->currency],
            'translated_labels' => [
                'dashboard' => trans('app.dashboard'),
                'animals' => trans('app.animals'),
                'milk_production' => trans('app.milk_production'),
                'veterinary_health' => trans('app.veterinary_health'),
                'feed_nutrition' => trans('app.feed_nutrition'),
                'breeding_reproduction' => trans('app.breeding_reproduction'),
                'settings' => trans('app.settings'),
                'user_preferences' => trans('app.user_preferences'),
            ],
        ]);
    }
}
