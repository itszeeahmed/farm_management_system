<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'locale',
        'timezone',
        'date_format',
        'time_format',
        'currency',
        'currency_symbol_position',
        'decimal_separator',
        'thousands_separator',
    ];

    /**
     * Supported application languages/locales.
     */
    public const SUPPORTED_LOCALES = [
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
        ],
        'ur' => [
            'code' => 'ur',
            'name' => 'Urdu',
            'native_name' => 'اردو',
            'direction' => 'rtl',
        ],
        'ar' => [
            'code' => 'ar',
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'direction' => 'rtl',
        ],
        'es' => [
            'code' => 'es',
            'name' => 'Spanish',
            'native_name' => 'Español',
            'direction' => 'ltr',
        ],
        'fr' => [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'direction' => 'ltr',
        ],
    ];

    /**
     * Supported currencies.
     */
    public const SUPPORTED_CURRENCIES = [
        'PKR' => ['code' => 'PKR', 'symbol' => 'Rs', 'name' => 'Pakistani Rupee', 'decimals' => 2],
        'USD' => ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar', 'decimals' => 2],
        'EUR' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro', 'decimals' => 2],
        'AED' => ['code' => 'AED', 'symbol' => 'AED', 'name' => 'UAE Dirham', 'decimals' => 2],
        'SAR' => ['code' => 'SAR', 'symbol' => 'SAR', 'name' => 'Saudi Riyal', 'decimals' => 2],
        'GBP' => ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound', 'decimals' => 2],
    ];

    /**
     * Supported date format patterns.
     */
    public const SUPPORTED_DATE_FORMATS = [
        'Y-m-d' => ['pattern' => 'Y-m-d', 'label' => 'YYYY-MM-DD (ISO 8601)', 'example' => '2026-10-01'],
        'd/m/Y' => ['pattern' => 'd/m/Y', 'label' => 'DD/MM/YYYY (UK/Commonwealth)', 'example' => '01/10/2026'],
        'm/d/Y' => ['pattern' => 'm/d/Y', 'label' => 'MM/DD/YYYY (US)', 'example' => '10/01/2026'],
        'd-m-Y' => ['pattern' => 'd-m-Y', 'label' => 'DD-MM-YYYY', 'example' => '01-10-2026'],
        'd M Y' => ['pattern' => 'd M Y', 'label' => 'DD Mon YYYY', 'example' => '01 Oct 2026'],
        'jS F Y' => ['pattern' => 'jS F Y', 'label' => 'DDth Month YYYY (Verbose)', 'example' => '1st October 2026'],
    ];

    /**
     * Supported time format patterns.
     */
    public const SUPPORTED_TIME_FORMATS = [
        '24h' => ['code' => '24h', 'pattern' => 'H:i', 'with_seconds' => 'H:i:s', 'example' => '16:45'],
        '12h' => ['code' => '12h', 'pattern' => 'h:i A', 'with_seconds' => 'h:i:s A', 'example' => '04:45 PM'],
    ];

    /**
     * Common farm region timezones.
     */
    public const COMMON_TIMEZONES = [
        'Asia/Karachi' => '(UTC+05:00) Pakistan Standard Time (Karachi, Islamabad, Lahore)',
        'Asia/Dubai' => '(UTC+04:00) Gulf Standard Time (Dubai, Abu Dhabi)',
        'Asia/Riyadh' => '(UTC+03:00) Arabian Standard Time (Riyadh, Jeddah)',
        'Asia/Qatar' => '(UTC+03:00) Qatar Time (Doha)',
        'UTC' => '(UTC+00:00) Coordinated Universal Time',
        'Europe/London' => '(UTC+00:00 / UTC+01:00) London, Edinburgh',
        'Europe/Paris' => '(UTC+01:00 / UTC+02:00) Paris, Amsterdam, Berlin',
        'America/New_York' => '(UTC-05:00 / UTC-04:00) Eastern Time (US & Canada)',
        'America/Chicago' => '(UTC-06:00 / UTC-05:00) Central Time (US & Canada)',
        'America/Denver' => '(UTC-07:00 / UTC-06:00) Mountain Time (US & Canada)',
        'America/Los_Angeles' => '(UTC-08:00 / UTC-07:00) Pacific Time (US & Canada)',
        'Australia/Sydney' => '(UTC+10:00 / UTC+11:00) Sydney, Melbourne',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format a date into the user's localized timezone and date format.
     */
    public function formatDate(Carbon|string|null $date): ?string
    {
        if (! $date) {
            return null;
        }

        $carbon = is_string($date) ? Carbon::parse($date) : $date->copy();
        $carbon->setTimezone($this->timezone ?? 'Asia/Karachi');

        return $carbon->format($this->date_format ?? 'Y-m-d');
    }

    /**
     * Format a time into the user's preferred 12h or 24h format.
     */
    public function formatTime(Carbon|string|null $dateTime, bool $includeSeconds = false): ?string
    {
        if (! $dateTime) {
            return null;
        }

        $carbon = is_string($dateTime) ? Carbon::parse($dateTime) : $dateTime->copy();
        $carbon->setTimezone($this->timezone ?? 'Asia/Karachi');

        $format = ($this->time_format === '12h')
            ? ($includeSeconds ? 'h:i:s A' : 'h:i A')
            : ($includeSeconds ? 'H:i:s' : 'H:i');

        return $carbon->format($format);
    }

    /**
     * Format date and time combined.
     */
    public function formatDateTime(Carbon|string|null $dateTime, bool $includeSeconds = false): ?string
    {
        if (! $dateTime) {
            return null;
        }

        return $this->formatDate($dateTime).' '.$this->formatTime($dateTime, $includeSeconds);
    }

    /**
     * Format a monetary amount using Laravel's core Number::currency utility.
     */
    public function formatCurrency(float $amount): string
    {
        $currencyCode = $this->currency ?? 'PKR';
        $locale = $this->locale ?? 'en';

        // Use Laravel's core Number::currency helper
        return (string) Number::currency($amount, $currencyCode, $locale);
    }

    /**
     * Format a numerical value using Laravel's core Number::format utility.
     */
    public function formatNumber(float $number, int $precision = 2): string
    {
        $locale = $this->locale ?? 'en';

        return (string) Number::format($number, $precision, null, $locale);
    }
}
